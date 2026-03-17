<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JntWebhookController extends Controller
{
    public function sandboxLogisticsTrackback(Request $request): JsonResponse
    {
        return $this->handleIncomingWebhook($request, 'sandbox_logistics_trackback');
    }

    public function sandboxOrderStatus(Request $request): JsonResponse
    {
        return $this->handleIncomingWebhook($request, 'sandbox_order_status');
    }

    public function productionLogisticsTrackback(Request $request): JsonResponse
    {
        return $this->handleIncomingWebhook($request, 'production_logistics_trackback');
    }

    public function productionOrderStatus(Request $request): JsonResponse
    {
        return $this->handleIncomingWebhook($request, 'production_order_status');
    }

    private function handleIncomingWebhook(Request $request, string $event): JsonResponse
    {
        Log::info('J&T webhook received.', [
            'event' => $event,
            'method' => $request->method(),
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'query' => $request->query(),
            'payload' => $request->all(),
            'raw' => $request->getContent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'J&T webhook received.',
            'event' => $event,
            'received_at' => now()->toDateTimeString(),
        ]);
    }
}
