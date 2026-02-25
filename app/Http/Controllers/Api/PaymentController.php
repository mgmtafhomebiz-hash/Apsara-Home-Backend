<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\Checkout\CheckoutCompletedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;


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

            'customer' => 'nullable|array',
            'customer.name' => 'nullable|string|max:255',
            'customer.email' => 'nullable|email|max:255',
            'customer.phone' => 'nullable|string|max:50',
            'customer.address' => 'nullable|string|max:500',
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
        $checkoutId = $data['id'] ?? null;

        if ($checkoutId && !empty($validated['customer']['email'])) {
            Cache::put("checkout_customer:{$checkoutId}", [
                'name' => $validated['customer']['name'] ?? 'Customer',
                'email' => $validated['customer']['email'],
                'description' => $validated['description'],
                'amount' => (float) $validated['amount'],
                'payment_method' => $validated['payment_method'],
            ], now()->addDays(3));
        }

        return response()->json([
            'checkout_id' => $checkoutId,
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
        $status = $attrs['status'] ?? null;

        if (is_string($status) && strtolower($status) === 'paid') {
            $this->sendCheckoutCompletedEmailIfNeeded($checkoutId, $attrs);
        }

        return response()->json([
            'checkout_id' => $checkoutId,
            'payment_intent_id' => $attrs['payment_intent']['id'] ?? null,
            'status' => $status, // usually paid / unpaid / failed
            'raw' => $attrs,
        ]);
    }

    private function sendCheckoutCompletedEmailIfNeeded(string $checkoutId, array $attrs): void
    {
        $customer = Cache::get("checkout_customer:{$checkoutId}");
        if (!$customer || empty($customer['email'])) return;

        $notifiedKey = "checkout_email_sent:{$checkoutId}";
        if (!Cache::add($notifiedKey, true, now()->addDays(7))) return;

        try {
            Mail::to($customer['email'])->send(new CheckoutCompletedMail([
                'checkout_id' => $checkoutId,
                'customer_name' => $customer['name'] ?? 'Customer',
                'description' => $customer['description'] ?? 'Order',
                'amount' => $customer['amount'] ?? 0,
                'payment_method' => $customer['payment_method'] ?? null,
                'status' => $attrs['status'] ?? 'paid',
                'payment_intent_id' => $attrs['payment_intent']['id'] ?? null,
            ]));
        } catch (\Throwable $e) {
            Cache::forget($notifiedKey);
            report($e);
        }
    }
}
