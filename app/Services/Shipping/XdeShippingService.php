<?php

namespace App\Services\Shipping;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XdeShippingService
{
    public function bookShipment(array $payload): array
    {
        $path = (string) config('services.xde.book_path', '/v2/pickup');
        return $this->request('post', $path, $payload);
    }

    public function trackShipment(string $trackingNo): array
    {
        $pathTemplate = (string) config('services.xde.track_path', '/status/{tracking_number}');
        $path = str_replace(
            ['{tracking_number}', '{tracking_no}'],
            rawurlencode($trackingNo),
            $pathTemplate
        );
        return $this->request('get', $path);
    }

    public function cancelShipment(array $payload): array
    {
        $path = (string) config('services.xde.cancel_path', '/cancel/');
        $pathsToTry = array_values(array_unique(array_filter([
            $path,
            rtrim($path, '/'),
            str_starts_with($path, '/api/') ? $path : '/api/' . ltrim($path, '/'),
            str_starts_with($path, '/api/') ? rtrim($path, '/') : '/api/' . trim($path, '/'),
        ])));

        $lastException = null;
        foreach ($pathsToTry as $candidatePath) {
            try {
                return $this->request('post', $candidatePath, $payload);
            } catch (RuntimeException $e) {
                $lastException = $e;
                if (!str_contains($e->getMessage(), '(404)')) {
                    throw $e;
                }
            }
        }

        throw $lastException ?? new RuntimeException('XDE cancel endpoint is unavailable.');
    }

    public function getWaybillA6(string $trackingNo): Response
    {
        $pathTemplate = (string) config('services.xde.waybill_path', '/generate-waybill-a6/{tracking_number}');
        $path = $this->replaceTrackingPlaceholder($pathTemplate, $trackingNo);

        return $this->requestRaw('get', $path);
    }

    public function getEpod(string $trackingNo, ?string $type = null): Response
    {
        $pathTemplate = (string) config('services.xde.epod_path', '/epod.php?tracking_number={tracking_number}');
        $path = $this->replaceTrackingPlaceholder($pathTemplate, $trackingNo);
        if ($type !== null && $type !== '') {
            $separator = str_contains($path, '?') ? '&' : '?';
            $path .= $separator . 'type=' . rawurlencode($type);
        }

        return $this->requestRawWithoutAuth('get', $path);
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $response = $this->requestRaw($method, $path, $payload);
        return $this->decodeResponse($response, $response->effectiveUri() ?: $this->resolveUrl($path));
    }

    private function requestRaw(string $method, string $path, ?array $payload = null): Response
    {
        [$client, $url] = $this->makeClientAndUrl();

        return $method === 'get'
            ? $client->get($url . '/' . ltrim($path, '/'))
            : $client->post($url . '/' . ltrim($path, '/'), $payload ?? []);
    }

    private function requestRawWithoutAuth(string $method, string $path): Response
    {
        $baseUrl = rtrim((string) config('services.xde.base_url', ''), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('XDE_BASE_URL is not configured.');
        }

        $timeout = max(5, (int) config('services.xde.timeout', 20));
        $client = Http::timeout($timeout);
        $url = $baseUrl . '/' . ltrim($path, '/');

        return $method === 'get'
            ? $client->get($url)
            : $client->post($url);
    }

    private function makeClientAndUrl(): array
    {
        $baseUrl = rtrim((string) config('services.xde.base_url', ''), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('XDE_BASE_URL is not configured.');
        }

        $apiKey = (string) config('services.xde.api_key', '');
        $token = (string) config('services.xde.token', '');
        if ($apiKey === '' || $token === '') {
            throw new RuntimeException('XDE_API_KEY or XDE_TOKEN is not configured.');
        }

        $timeout = max(5, (int) config('services.xde.timeout', 20));

        $client = Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'apikey' => $apiKey,
                'token' => $token,
                'Content-Type' => 'application/json',
            ]);

        return [$client, $baseUrl];
    }

    private function resolveUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('services.xde.base_url', ''), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }

    private function replaceTrackingPlaceholder(string $pathTemplate, string $trackingNo): string
    {
        return str_replace(
            ['{tracking_number}', '{tracking_no}'],
            rawurlencode($trackingNo),
            $pathTemplate
        );
    }

    private function decodeResponse(Response $response, string $url): array
    {
        $json = $response->json();
        if ($response->successful()) {
            return is_array($json) ? $json : ['raw' => $response->body()];
        }

        throw new RuntimeException(
            sprintf(
                'XDE request failed (%s) at %s: %s',
                $response->status(),
                $url,
                is_array($json) ? json_encode($json) : $response->body()
            )
        );
    }
}
