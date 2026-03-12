<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckoutHistory;
use App\Models\Customer;
use App\Models\CustomerVerificationRequest;
use App\Models\EncashmentRequest;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Only customer accounts can access notifications.'], 403);
        }

        $customerId = (int) $customer->c_userid;
        $now = now();

        $pendingOrdersCount = (int) CheckoutHistory::query()
            ->where('ch_customer_id', $customerId)
            ->whereNotIn('ch_fulfillment_status', ['delivered', 'cancelled', 'refunded'])
            ->count();

        $shippingUpdatesCount = (int) CheckoutHistory::query()
            ->where('ch_customer_id', $customerId)
            ->whereIn('ch_fulfillment_status', ['shipped', 'out_for_delivery', 'delivered'])
            ->where('updated_at', '>=', $now->copy()->subDays(7))
            ->count();

        $encashmentUpdatesCount = (int) EncashmentRequest::query()
            ->where('er_customer_id', $customerId)
            ->whereIn('er_status', ['approved_by_admin', 'released', 'rejected', 'failed'])
            ->where('updated_at', '>=', $now->copy()->subDays(14))
            ->count();

        $recentReferrals = Customer::query()
            ->where('c_sponsor', $customerId)
            ->where(function ($query) use ($now) {
                $query->where('c_date_started', '>=', $now->copy()->subDays(14))
                    ->orWhere('created_at', '>=', $now->copy()->subDays(14));
            })
            ->orderByDesc('c_date_started')
            ->orderByDesc('c_userid')
            ->get([
                'c_userid',
                'c_username',
                'c_fname',
                'c_mname',
                'c_lname',
                'c_date_started',
            ]);

        $recentReferralCount = $recentReferrals->count();
        $recentReferralNames = $recentReferrals
            ->take(3)
            ->map(function (Customer $referral) {
                $name = trim(implode(' ', array_filter([
                    $referral->c_fname ?? null,
                    $referral->c_mname ?? null,
                    $referral->c_lname ?? null,
                ])));

                return $name !== '' ? $name : ((string) ($referral->c_username ?? 'New referral'));
            })
            ->values();

        $kycMeta = $this->resolveKycMeta($customer);
        $kycActionCount = $kycMeta['count'];

        $items = [
            [
                'id' => 'orders_pending',
                'title' => 'Orders In Progress',
                'description' => $pendingOrdersCount > 0
                    ? $pendingOrdersCount . ' order(s) are still being processed.'
                    : 'No active order processing right now.',
                'count' => $pendingOrdersCount,
                'severity' => $pendingOrdersCount > 0 ? 'info' : 'success',
                'href' => '/orders',
            ],
            [
                'id' => 'shipping_updates',
                'title' => 'Shipping & Delivery Updates',
                'description' => $shippingUpdatesCount > 0
                    ? $shippingUpdatesCount . ' order update(s) were posted this week.'
                    : 'No new shipping updates yet.',
                'count' => $shippingUpdatesCount,
                'severity' => $shippingUpdatesCount > 0 ? 'warning' : 'success',
                'href' => '/orders',
            ],
            [
                'id' => 'encashment_updates',
                'title' => 'Encashment Updates',
                'description' => $encashmentUpdatesCount > 0
                    ? $encashmentUpdatesCount . ' encashment request(s) changed status.'
                    : 'No encashment status changes recently.',
                'count' => $encashmentUpdatesCount,
                'severity' => $encashmentUpdatesCount > 0 ? 'warning' : 'success',
                'href' => '/profile',
            ],
            [
                'id' => 'referral_registrations',
                'title' => 'Referral Registrations',
                'description' => $recentReferralCount > 0
                    ? $this->buildReferralDescription($recentReferralCount, $recentReferralNames->all())
                    : 'No new referral registrations recently.',
                'count' => $recentReferralCount,
                'severity' => $recentReferralCount > 0 ? 'success' : 'info',
                'href' => '/profile',
            ],
            [
                'id' => 'kyc_status',
                'title' => 'KYC Verification',
                'description' => $kycMeta['description'],
                'count' => $kycActionCount,
                'severity' => $kycMeta['severity'],
                'href' => '/profile',
            ],
        ];

        $unreadCount = $shippingUpdatesCount + $encashmentUpdatesCount + $recentReferralCount + $kycActionCount;

        return response()->json([
            'unread_count' => $unreadCount,
            'items' => $items,
            'generated_at' => $now->toDateTimeString(),
        ]);
    }

    private function resolveKycMeta(Customer $customer): array
    {
        $status = (int) ($customer->c_accnt_status ?? 0);
        $lock = (int) ($customer->c_lockstatus ?? 0);
        $latestKyc = CustomerVerificationRequest::query()
            ->where('cvr_customer_id', (int) $customer->c_userid)
            ->latest('cvr_id')
            ->first();
        $recentKycWindow = now()->subDays(14);

        if ($lock === 1) {
            return [
                'count' => 1,
                'severity' => 'critical',
                'description' => 'Account is blocked. Please contact support.',
            ];
        }

        if ($latestKyc && (string) $latestKyc->cvr_status === 'approved') {
            $reviewedAt = $latestKyc->cvr_reviewed_at ?? $latestKyc->updated_at ?? $latestKyc->created_at;

            return [
                'count' => ($reviewedAt && $reviewedAt >= $recentKycWindow) ? 1 : 0,
                'severity' => 'success',
                'description' => 'Your KYC verification has been approved. Your affiliate account is now verified.',
            ];
        }

        if ($latestKyc && (string) $latestKyc->cvr_status === 'rejected') {
            $reviewedAt = $latestKyc->cvr_reviewed_at ?? $latestKyc->updated_at ?? $latestKyc->created_at;

            return [
                'count' => ($reviewedAt && $reviewedAt >= $recentKycWindow) ? 1 : 0,
                'severity' => 'critical',
                'description' => 'Your KYC verification was rejected. Please review the requirements and resubmit your documents.',
            ];
        }

        if ($status === 1) {
            return [
                'count' => 0,
                'severity' => 'success',
                'description' => 'Your account is verified.',
            ];
        }

        $hasPendingKyc = CustomerVerificationRequest::query()
            ->where('cvr_customer_id', (int) $customer->c_userid)
            ->whereIn('cvr_status', ['pending_review', 'for_review', 'on_hold'])
            ->exists();

        if ($hasPendingKyc || $status === 2) {
            return [
                'count' => 1,
                'severity' => 'warning',
                'description' => 'KYC is under review. Wait for admin update.',
            ];
        }

        return [
            'count' => 1,
            'severity' => 'warning',
            'description' => 'KYC not submitted. Complete verification to unlock full features.',
        ];
    }

    private function buildReferralDescription(int $count, array $names): string
    {
        if ($count <= 0) {
            return 'No new referral registrations recently.';
        }

        if (empty($names)) {
            return $count . ' new referral registration(s) used your link recently.';
        }

        $preview = implode(', ', array_slice($names, 0, 3));

        if ($count <= 3) {
            return sprintf('%s registered using your referral link.', $preview);
        }

        return sprintf('%s and %d more registered using your referral link.', $preview, $count - 3);
    }
}
