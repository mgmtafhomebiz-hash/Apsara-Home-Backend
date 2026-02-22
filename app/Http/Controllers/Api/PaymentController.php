<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    private function mapMethods(string $method): array
    {
        return match ($method) {
            'card' => ['card'],
            'gcash' => ['gcash'],
            'maya' => ['paymaya'],
            'online_banking' => ['dob', 'ubp'], // adjust based on enabled methods in your PayMongo account
            default => ['gcash'],
        };
    }

    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'payment_method' => 'required|in:online_banking,card,gcash,maya',
        ]);

        $secretKey = config('services.paymongo.secret_key');
        if (!$secretKey) {
            return response()->json(['message' => 'PAYMONGO_SECRET_KEY missing'], 500);
        }

        $frontend = env('FRONTEND_URL', 'http://localhost:3000');

        $payload = [
            'data' => [
                'attributes' => [
                    'line_items' => [[
                        'currency' => 'PHP',
                        'amount' => (int) round($validated['amount'] * 100), // centavos
                        'name' => $validated['description'],
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => $this->mapMethods($validated['payment_method']),
                    'success_url' => $frontend . '/checkout/success',
                    'cancel_url' => $frontend . '/checkout/failed',
                    'description' => $validated['description'],
                ],
            ],
        ];

        $res = Http::withBasicAuth($secretKey, '')
            ->post('https://api.paymongo.com/v1/checkout_sessions', $payload);

        if ($res->failed()) {
            return response()->json([
                'message' => 'PayMongo create session failed',
                'error' => $res->json(),
            ], $res->status());
        }

        $data = $res->json('data');

        return response()->json([
            'checkout_id' => $data['id'] ?? null,
            'checkout_url' => $data['attributes']['checkout_url'] ?? null,
        ]);
    }

    public function verifyCheckoutSession(Request $request, string $checkoutId)
    {
        $secretKey = config('services.paymongo.secret_key');
        if (!$secretKey) {
            return response()->json(['message' => 'PAYMONGO_SECRET_KEY missing'], 500);
        }

        $res = Http::withBasicAuth($secretKey, '')
            ->get("https://api.paymongo.com/v1/checkout_sessions/{$checkoutId}");

        if ($res->failed()) {
            return response()->json([
                'message' => 'PayMongo verify failed',
                'error' => $res->json(),
            ], $res->status());
        }

        $attrs = $res->json('data.attributes');

        return response()->json([
            'checkout_id' => $checkoutId,
            'payment_intent_id' => $attrs['payment_intent']['id'] ?? null,
            'status' => $attrs['status'] ?? null, // usually paid / unpaid / failed
            'raw' => $attrs,
        ]);
    }
}
