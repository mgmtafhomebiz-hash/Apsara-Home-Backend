<?php

namespace App\Services\Shipping;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XdeShippingService
{
    public function bookShipment(array $payload): array
    {
        $path = (string) config('services.xde.book_path', '/api/v1/shipments');
        return $this->request('post', $path, $payload);
    }

    public function trackShipment(string $trackingNo): array
    {
        $pathTemplate = (string) config('services.xde.track_path', '/api/v1/shipments/{tracking_no}');
        $path = str_replace('{tracking_no}', rawurlencode($trackingNo), $pathTemplate);
        return $this->request('get', $path);
    }

    private function request(string $method, string $path, ?array $payload = null): array
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
        $url = $baseUrl . '/' . ltrim($path, '/');

        $client = Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
                'Authorization' => 'Bearer ' . $token,
            ]);

        $response = $method === 'get'
            ? $client->get($url)
            : $client->post($url, $payload ?? []);

        return $this->decodeResponse($response, $url);
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

