<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\CheckoutHistory;
use App\Services\Shipping\XdeShippingService;
use Illuminate\Http\Request;
use RuntimeException;

class XdeShippingController extends Controller
{
    public function __construct(private readonly XdeShippingService $xdeShippingService)
    {
    }

    public function bookForOrder(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageShipping($admin)) {
            return response()->json(['message' => 'Forbidden: shipping access is limited.'], 403);
        }

        $validated = $request->validate([
            'payload' => 'nullable|array',
        ]);

        $order = CheckoutHistory::query()->where('ch_id', $id)->firstOrFail();
        $shipmentPayload = array_merge($this->payloadFromOrder($order), $validated['payload'] ?? []);

        try {
            $response = $this->xdeShippingService->bookShipment($shipmentPayload);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to book XDE shipment.',
                'error' => $e->getMessage(),
            ], 422);
        }

        $trackingNo = $this->extractTrackingNoFromRequestPayload($shipmentPayload)
            ?: $this->extractTrackingNo($response);
        $status = $this->normalizeShipmentStatus($this->extractStatus($response)) ?? 'for_pickup';

        $order->ch_courier = 'xde';
        $order->ch_tracking_no = $trackingNo ?: $order->ch_tracking_no;
        $order->ch_shipment_status = $status ?: $order->ch_shipment_status;
        $order->ch_shipment_payload = $response;
        if ($trackingNo && !$order->ch_shipped_at) {
            $order->ch_shipped_at = now();
        }
        $order->save();

        return response()->json([
            'message' => 'XDE shipment booked successfully.',
            'order_id' => (int) $order->ch_id,
            'tracking_no' => $order->ch_tracking_no,
            'shipment_status' => $order->ch_shipment_status,
            'payload' => $response,
        ]);
    }

    public function trackByOrder(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageShipping($admin)) {
            return response()->json(['message' => 'Forbidden: shipping access is limited.'], 403);
        }

        $order = CheckoutHistory::query()->where('ch_id', $id)->firstOrFail();
        $trackingNo = $this->resolveTrackableTrackingNo($order);
        if ($trackingNo === '') {
            return response()->json(['message' => 'Order has no tracking number yet.'], 422);
        }

        return $this->trackByTrackingNo($request, $trackingNo, $order);
    }

    public function trackByTrackingNo(Request $request, string $trackingNo, ?CheckoutHistory $order = null)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageShipping($admin)) {
            return response()->json(['message' => 'Forbidden: shipping access is limited.'], 403);
        }

        try {
            $response = $this->xdeShippingService->trackShipment($trackingNo);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to fetch XDE tracking.',
                'error' => $e->getMessage(),
            ], 422);
        }

        if ($order) {
            $status = $this->normalizeShipmentStatus($this->extractStatus($response));
            $order->ch_shipment_status = $status ?: $order->ch_shipment_status;
            $order->ch_shipment_payload = $response;
            $order->save();
        }

        return response()->json([
            'tracking_no' => $trackingNo,
            'shipment_status' => $this->normalizeShipmentStatus($this->extractStatus($response)),
            'payload' => $response,
        ]);
    }

    public function waybillByOrder(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageShipping($admin)) {
            return response()->json(['message' => 'Forbidden: shipping access is limited.'], 403);
        }

        $order = CheckoutHistory::query()->where('ch_id', $id)->firstOrFail();
        $trackingNo = trim((string) ($order->ch_tracking_no ?? ''));
        if ($trackingNo === '') {
            return response()->json(['message' => 'Order has no tracking number yet.'], 422);
        }

        try {
            $response = $this->xdeShippingService->getWaybillA6($trackingNo);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to generate XDE waybill.',
                'error' => $e->getMessage(),
            ], 422);
        }

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to generate XDE waybill.',
                'error' => $response->body(),
            ], $response->status());
        }

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'text/html; charset=UTF-8'),
            'Content-Disposition' => 'inline; filename="xde-waybill-' . $trackingNo . '.html"',
        ]);
    }

    public function cancelByOrder(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageShipping($admin)) {
            return response()->json(['message' => 'Forbidden: shipping access is limited.'], 403);
        }

        $order = CheckoutHistory::query()->where('ch_id', $id)->firstOrFail();
        $trackingNo = $this->resolveTrackableTrackingNo($order);
        if ($trackingNo === '') {
            return response()->json(['message' => 'Order has no tracking number yet.'], 422);
        }

        $payload = [
            'tracking_number' => $trackingNo,
            'uid' => $trackingNo,
            'date' => now()->format('Y-m-d H:i:s'),
        ];

        try {
            $response = $this->xdeShippingService->cancelShipment($payload);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to cancel XDE shipment.',
                'error' => $e->getMessage(),
            ], 422);
        }

        $order->ch_shipment_status = 'cancelled';
        $order->ch_shipment_payload = $response;
        $order->save();

        return response()->json([
            'message' => 'XDE shipment cancellation submitted.',
            'tracking_no' => $trackingNo,
            'payload' => $response,
        ]);
    }

    public function epodByOrder(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageShipping($admin)) {
            return response()->json(['message' => 'Forbidden: shipping access is limited.'], 403);
        }

        $order = CheckoutHistory::query()->where('ch_id', $id)->firstOrFail();
        $trackingNo = $this->resolveTrackableTrackingNo($order);
        if ($trackingNo === '') {
            return response()->json(['message' => 'Order has no tracking number yet.'], 422);
        }

        $type = $request->query('type');

        try {
            $response = $this->xdeShippingService->getEpod($trackingNo, is_string($type) ? $type : null);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to fetch XDE EPOD.',
                'error' => $e->getMessage(),
            ], 422);
        }

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to fetch XDE EPOD.',
                'error' => $response->body(),
            ], $response->status());
        }

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'image/jpeg'),
            'Content-Disposition' => 'inline; filename="xde-epod-' . $trackingNo . '.jpg"',
        ]);
    }

    private function payloadFromOrder(CheckoutHistory $order): array
    {
        $quantity = max(1, (int) ($order->ch_quantity ?? 1));
        $amount = max(0, (float) ($order->ch_amount ?? 0));
        $referenceNo = (string) ($order->ch_checkout_id ?? '');
        $xdeTrackingNo = $this->generateXdeTrackingNumber($order);
        $productName = trim((string) ($order->ch_product_name ?? 'Order Item')) ?: 'Order Item';
        $recipientAddress = trim((string) ($order->ch_customer_address ?? ''));
        $recipientParts = $this->parseAddressParts($recipientAddress);

        return [
            [
                'package' => [
                    'id' => $referenceNo,
                    'tracking_number' => $xdeTrackingNo,
                    'order_no' => $referenceNo,
                    'serial_number' => $referenceNo,
                    'asset_number' => $referenceNo,
                    'payment_type' => strtoupper((string) ($order->ch_payment_method ?? 'Prepaid')) === 'COD' ? 'COD' : 'Prepaid',
                    'total_price' => $amount,
                    'declared_value' => $amount,
                    'package_size' => (string) config('services.xde.package_size', 'Bulky'),
                    'total_quantity' => $quantity,
                    'length' => (float) config('services.xde.default_length', 10),
                    'width' => (float) config('services.xde.default_width', 10),
                    'height' => (float) config('services.xde.default_height', 10),
                    'weight' => (float) config('services.xde.default_weight', 1),
                    'package_type' => (string) config('services.xde.package_type', 'Sales_order'),
                    'delivery_type' => (string) config('services.xde.delivery_type', 'Standard'),
                    'shipping_type' => (string) config('services.xde.shipping_type', 'Local'),
                    'journey_type' => (string) config('services.xde.journey_type', 'Last Mile'),
                    'transport_mode' => (string) config('services.xde.transport_mode', 'land'),
                    'port_code' => (string) config('services.xde.port_code', 'MAIN'),
                    'shipment_provider' => (string) config('services.xde.shipment_provider', 'Ximex Delivery Express'),
                    'reference_number' => $referenceNo,
                    'remarks' => (string) ($order->ch_payment_method ?? 'AF Home order'),
                    'pickup_date_time' => (string) now()->timestamp,
                ],
                'consignee' => [
                    'name' => (string) ($order->ch_customer_name ?? ''),
                    'mobile_number' => (string) ($order->ch_customer_phone ?? ''),
                    'email_address' => (string) ($order->ch_customer_email ?? ''),
                    'full_address' => $recipientAddress,
                    'province' => $recipientParts['province'],
                    'city' => $recipientParts['city'],
                    'barangay' => $recipientParts['barangay'],
                    'building_type' => 'Residential',
                ],
                'merchant' => [
                    'name' => (string) config('services.xde.merchant_name', 'AF Home Warehouse'),
                    'full_address' => (string) config('services.xde.merchant_address', ''),
                    'mobile_number' => (string) config('services.xde.merchant_mobile', ''),
                    'email_address' => (string) config('services.xde.merchant_email', ''),
                    'address_province' => (string) config('services.xde.merchant_province', ''),
                    'address_city' => (string) config('services.xde.merchant_city', ''),
                    'address_barangay' => (string) config('services.xde.merchant_barangay', ''),
                ],
                'items' => [[
                    'reference' => $referenceNo,
                    'description' => $productName,
                    'quantity' => $quantity,
                    'uom' => 'pc',
                    'length' => (float) config('services.xde.default_length', 10),
                    'width' => (float) config('services.xde.default_width', 10),
                    'height' => (float) config('services.xde.default_height', 10),
                    'weight' => (float) config('services.xde.default_weight', 1),
                    'volume' => (float) config('services.xde.default_volume', 1),
                    'value' => $amount,
                    'type' => 'Standard',
                    'remarks' => (string) ($order->ch_payment_method ?? 'AF Home order'),
                ]],
            ],
        ];
    }

    private function extractTrackingNo(array $response): ?string
    {
        $candidates = [
            data_get($response, 'tracking_no'),
            data_get($response, 'tracking_number'),
            data_get($response, 'waybill_no'),
            data_get($response, 'awb'),
            data_get($response, 'data.tracking_no'),
            data_get($response, 'data.tracking_number'),
            data_get($response, 'data.waybill_no'),
            data_get($response, 'result.tracking_no'),
            data_get($response, 'result.tracking_number'),
            data_get($response, '0.package.tracking_number'),
            data_get($response, '0.package.id'),
            data_get($response, '0.tracking_number'),
            data_get($response, 'package.tracking_number'),
            data_get($response, 'success.detail.0'),
            data_get($response, 'detail.0'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    private function resolveTrackableTrackingNo(CheckoutHistory $order): string
    {
        $trackingNo = trim((string) ($order->ch_tracking_no ?? ''));
        if ($trackingNo !== '' && !str_starts_with(strtolower($trackingNo), 'cs_')) {
            return $trackingNo;
        }

        return $this->generateXdeTrackingNumber($order);
    }

    private function generateXdeTrackingNumber(CheckoutHistory $order): string
    {
        $existingTrackingNo = trim((string) ($order->ch_tracking_no ?? ''));
        if ($existingTrackingNo !== '' && !str_starts_with(strtolower($existingTrackingNo), 'cs_')) {
            return $existingTrackingNo;
        }

        $checkoutId = strtoupper((string) ($order->ch_checkout_id ?? ''));
        $sanitized = preg_replace('/[^A-Z0-9]/', '', $checkoutId) ?: (string) $order->ch_id;

        return 'XDE' . substr($sanitized, 0, 24);
    }

    private function extractStatus(array $response): ?string
    {
        if (array_is_list($response) && !empty($response)) {
            $latestEntry = collect($response)
                ->filter(static fn ($entry) => is_array($entry))
                ->sortByDesc(static fn (array $entry) => (string) ($entry['created_at'] ?? ''))
                ->first();

            $firstStatus = is_array($latestEntry) ? data_get($latestEntry, 'status') : null;
            if (is_string($firstStatus) && trim($firstStatus) !== '') {
                return strtolower(trim($firstStatus));
            }
        }

        $candidates = [
            data_get($response, 'status'),
            data_get($response, 'shipment_status'),
            data_get($response, 'data.status'),
            data_get($response, 'data.shipment_status'),
            data_get($response, 'result.status'),
            data_get($response, 'result.shipment_status'),
            data_get($response, 'message'),
            data_get($response, 'success.message'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return strtolower(trim($candidate));
            }
        }

        return null;
    }

    private function normalizeShipmentStatus(?string $status): ?string
    {
        if (!is_string($status) || trim($status) === '') {
            return null;
        }

        return match (strtolower(trim($status))) {
            'package/s added successfully.',
            'package/s added successfully',
            'for_pickup',
            'pickup_request_cancelled_1',
            'pickup_request_cancelled_2',
            'pickup_request_cancelled_3' => 'for_pickup',
            'accepted_by_courier',
            'picked',
            'accepted_to_warehouse' => 'picked_up',
            'released',
            'forwarded_to_branch',
            'accepted_to_branch',
            'returned_to_warehouse',
            'forwarded_to_warehouse',
            'for_dispatch',
            'forwarded_to_main',
            'main_received_rts',
            'branch_received_rts',
            'received_refused_rts',
            'dimweight_update' => 'in_transit',
            'first_delivery_attempt',
            'redelivery_attempt',
            'lm_assigned_to_driver',
            'lm_rejected_by_driver' => 'out_for_delivery',
            'delivery_successful',
            'pod_returned' => 'delivered',
            'first_attempt_failed',
            'redelivery_attempt_failed',
            'rejected',
            'for_disposition',
            'claims' => 'failed_delivery',
            'failed_delivery_return',
            'out_for_return',
            'returned',
            'refused_rts' => 'returned_to_sender',
            default => $status,
        };
    }

    private function extractTrackingNoFromRequestPayload(array $payload): ?string
    {
        $candidates = [
            data_get($payload, '0.package.tracking_number'),
            data_get($payload, 'package.tracking_number'),
            data_get($payload, '0.package.id'),
            data_get($payload, 'package.id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    private function resolveAdmin(Request $request): ?Admin
    {
        $user = $request->user();
        return $user instanceof Admin ? $user : null;
    }

    private function canManageShipping(Admin $admin): bool
    {
        return in_array($this->roleFromAdmin($admin), ['super_admin', 'admin', 'csr', 'merchant_admin'], true);
    }

    private function roleFromAdmin(Admin $admin): string
    {
        return match ((int) $admin->user_level_id) {
            1 => 'super_admin',
            2 => 'admin',
            3 => 'csr',
            4 => 'web_content',
            7 => 'merchant_admin',
            8 => 'supplier_admin',
            default => 'staff',
        };
    }

    private function parseAddressParts(string $fullAddress): array
    {
        $parts = array_values(array_filter(array_map(
            static fn ($part) => trim($part),
            explode(',', $fullAddress)
        )));

        $count = count($parts);

        return [
            'barangay' => $count >= 2 ? $parts[$count - 2] : '',
            'city' => $count >= 3 ? $parts[$count - 3] : '',
            'province' => $count >= 4 ? $parts[$count - 4] : ($count >= 1 ? $parts[$count - 1] : ''),
        ];
    }
}
