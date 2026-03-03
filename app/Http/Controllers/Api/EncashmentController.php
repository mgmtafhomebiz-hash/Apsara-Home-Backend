<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerWalletLedger;
use App\Models\EncashmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EncashmentController extends Controller
{
    public function walletOverview(Request $request)
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Only customer accounts can view wallet data.'], 403);
        }

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'wallet_type' => 'nullable|in:all,cash,pv',
        ]);

        $walletType = (string) ($validated['wallet_type'] ?? 'all');
        $perPage = (int) ($validated['per_page'] ?? 20);

        $ledgerRows = null;
        $cashCredits = 0.0;
        $cashDebits = 0.0;
        $pvCredits = 0.0;
        $pvDebits = 0.0;

        if (Schema::hasTable('tbl_customer_wallet_ledger')) {
            $ledgerQuery = CustomerWalletLedger::query()
                ->where('wl_customer_id', (int) $customer->c_userid)
                ->when($walletType !== 'all', function ($query) use ($walletType) {
                    $query->where('wl_wallet_type', $walletType);
                });

            $ledgerRows = (clone $ledgerQuery)
                ->orderByDesc('created_at')
                ->orderByDesc('wl_id')
                ->paginate($perPage);

            $cashCredits = (float) (clone $ledgerQuery)
                ->where('wl_wallet_type', 'cash')
                ->where('wl_entry_type', 'credit')
                ->sum('wl_amount');
            $cashDebits = (float) (clone $ledgerQuery)
                ->where('wl_wallet_type', 'cash')
                ->where('wl_entry_type', 'debit')
                ->sum('wl_amount');
            $pvCredits = (float) (clone $ledgerQuery)
                ->where('wl_wallet_type', 'pv')
                ->where('wl_entry_type', 'credit')
                ->sum('wl_amount');
            $pvDebits = (float) (clone $ledgerQuery)
                ->where('wl_wallet_type', 'pv')
                ->where('wl_entry_type', 'debit')
                ->sum('wl_amount');
        }

        $encashmentPendingLocked = (float) EncashmentRequest::query()
            ->where('er_customer_id', (int) $customer->c_userid)
            ->whereIn('er_status', ['pending', 'approved_by_admin', 'on_hold'])
            ->sum('er_amount');

        return response()->json([
            'summary' => [
                'cash_balance' => round((float) ($customer->c_totalincome ?? 0), 2),
                'pv_balance' => round((float) ($customer->c_gpv ?? 0), 2),
                'cash_credits' => round($cashCredits, 2),
                'cash_debits' => round($cashDebits, 2),
                'pv_credits' => round($pvCredits, 2),
                'pv_debits' => round($pvDebits, 2),
                'encashment_locked' => round($encashmentPendingLocked, 2),
                'encashment_available' => round(max(0, ((float) ($customer->c_totalincome ?? 0)) - $encashmentPendingLocked), 2),
            ],
            'ledger' => collect($ledgerRows?->items() ?? [])->map(function (CustomerWalletLedger $row) {
                return [
                    'id' => (int) $row->wl_id,
                    'wallet_type' => (string) $row->wl_wallet_type,
                    'entry_type' => (string) $row->wl_entry_type,
                    'amount' => (float) $row->wl_amount,
                    'source_type' => $row->wl_source_type,
                    'source_id' => $row->wl_source_id ? (int) $row->wl_source_id : null,
                    'reference_no' => $row->wl_reference_no,
                    'notes' => $row->wl_notes,
                    'created_by' => $row->wl_created_by ? (int) $row->wl_created_by : null,
                    'created_at' => optional($row->created_at)->toDateTimeString(),
                    'updated_at' => optional($row->updated_at)->toDateTimeString(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $ledgerRows?->currentPage() ?? 1,
                'last_page' => $ledgerRows?->lastPage() ?? 1,
                'per_page' => $ledgerRows?->perPage() ?? $perPage,
                'total' => $ledgerRows?->total() ?? 0,
                'from' => $ledgerRows?->firstItem() ?? null,
                'to' => $ledgerRows?->lastItem() ?? null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Only customer accounts can submit encashment requests.'], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'channel' => 'required|in:bank,gcash,maya',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
        ]);

        $rules = $this->rules();
        $eligibility = $this->evaluateEligibility($customer, $rules);
        if (!$eligibility['eligible']) {
            return response()->json([
                'message' => $eligibility['message'],
                'eligibility' => $eligibility,
                'policy' => $this->policyMeta($rules),
            ], 422);
        }

        $amount = (float) $validated['amount'];
        if ($amount < $rules['min_amount']) {
            return response()->json([
                'message' => 'Minimum encashment amount is ' . number_format($rules['min_amount'], 2) . '.',
                'policy' => $this->policyMeta($rules),
            ], 422);
        }

        if ($amount > $eligibility['available_amount']) {
            return response()->json([
                'message' => 'Requested amount exceeds your available encashment balance.',
                'eligibility' => $eligibility,
                'policy' => $this->policyMeta($rules),
            ], 422);
        }

        $requestRow = EncashmentRequest::create([
            'er_reference_no' => $this->generateReferenceNo(),
            'er_customer_id' => (int) $customer->c_userid,
            'er_amount' => $amount,
            'er_channel' => $validated['channel'],
            'er_account_name' => $validated['account_name'] ?? null,
            'er_account_number' => $validated['account_number'] ?? null,
            'er_notes' => $validated['notes'] ?? null,
            'er_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Encashment request submitted.',
            'request' => $this->transform($requestRow, $customer),
            'eligibility' => $this->evaluateEligibility($customer->fresh(), $rules),
            'policy' => $this->policyMeta($rules),
        ], 201);
    }

    public function myRequests(Request $request)
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Only customer accounts can view this resource.'], 403);
        }

        $rows = EncashmentRequest::query()
            ->where('er_customer_id', (int) $customer->c_userid)
            ->orderByDesc('created_at')
            ->paginate(20);

        $rules = $this->rules();

        return response()->json([
            'requests' => collect($rows->items())->map(fn (EncashmentRequest $row) => $this->transform($row, $customer))->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ],
            'eligibility' => $this->evaluateEligibility($customer, $rules),
            'policy' => $this->policyMeta($rules),
        ]);
    }

    private function transform(EncashmentRequest $row, Customer $customer): array
    {
        $name = trim(implode(' ', array_filter([
            $customer->c_fname ?? null,
            $customer->c_mname ?? null,
            $customer->c_lname ?? null,
        ])));

        return [
            'id' => (int) $row->er_id,
            'reference_no' => $row->er_reference_no,
            'invoice_no' => $row->er_invoice_no,
            'amount' => (float) $row->er_amount,
            'channel' => $row->er_channel,
            'account_name' => $row->er_account_name,
            'account_number' => $row->er_account_number,
            'notes' => $row->er_notes,
            'status' => $row->er_status,
            'affiliate_name' => $name !== '' ? $name : ($customer->c_username ?? 'Affiliate'),
            'affiliate_email' => $customer->c_email ?? null,
            'approved_at' => optional($row->er_approved_at)->toDateTimeString(),
            'released_at' => optional($row->er_released_at)->toDateTimeString(),
            'created_at' => optional($row->created_at)->toDateTimeString(),
            'updated_at' => optional($row->updated_at)->toDateTimeString(),
        ];
    }

    private function generateReferenceNo(): string
    {
        $date = now()->format('Ymd');

        for ($attempt = 0; $attempt < 15; $attempt++) {
            $count = EncashmentRequest::query()
                ->whereDate('created_at', now()->toDateString())
                ->count() + 1 + $attempt;
            $candidate = sprintf('ENC-%s-%04d', $date, $count);
            if (!EncashmentRequest::query()->where('er_reference_no', $candidate)->exists()) {
                return $candidate;
            }
        }

        return sprintf('ENC-%s-%s', $date, strtoupper(substr(md5((string) microtime(true)), 0, 6)));
    }

    private function rules(): array
    {
        return [
            'min_amount' => max(1, (float) env('ENCASHMENT_MIN_AMOUNT', 500)),
            'min_points' => max(0, (float) env('ENCASHMENT_MIN_POINTS', 0)),
            'cooldown_hours' => max(0, (int) env('ENCASHMENT_COOLDOWN_HOURS', 24)),
            'require_active_account' => filter_var(env('ENCASHMENT_REQUIRE_ACTIVE_ACCOUNT', true), FILTER_VALIDATE_BOOL),
        ];
    }

    private function evaluateEligibility(Customer $customer, array $rules): array
    {
        $grossEarnings = (float) ($customer->c_totalincome ?? 0);
        $points = (float) ($customer->c_gpv ?? 0);

        $openStatuses = ['pending', 'approved_by_admin', 'on_hold'];
        $lockedAmount = (float) EncashmentRequest::query()
            ->where('er_customer_id', (int) $customer->c_userid)
            ->whereIn('er_status', $openStatuses)
            ->sum('er_amount');
        $availableAmount = max(0, $grossEarnings - $lockedAmount);

        $lastRequest = EncashmentRequest::query()
            ->where('er_customer_id', (int) $customer->c_userid)
            ->latest('created_at')
            ->first();

        $remainingCooldownMinutes = 0;
        if ($rules['cooldown_hours'] > 0 && $lastRequest?->created_at) {
            $cooldownEndsAt = $lastRequest->created_at->copy()->addHours($rules['cooldown_hours']);
            if ($cooldownEndsAt->isFuture()) {
                $remainingCooldownMinutes = now()->diffInMinutes($cooldownEndsAt);
            }
        }

        $blocked = false;
        $message = 'Eligible for encashment request.';

        if ($rules['require_active_account'] && ((int) ($customer->c_lockstatus ?? 0) === 1 || (int) ($customer->c_accnt_status ?? 0) !== 1)) {
            $blocked = true;
            $message = 'Your account must be active and verified before encashment.';
        } elseif ($points < $rules['min_points']) {
            $blocked = true;
            $message = 'Minimum points requirement not met for encashment.';
        } elseif ($availableAmount < $rules['min_amount']) {
            $blocked = true;
            $message = 'You do not have enough available balance for minimum encashment.';
        } elseif ($remainingCooldownMinutes > 0) {
            $blocked = true;
            $message = 'Please wait for cooldown period before submitting another request.';
        }

        return [
            'eligible' => !$blocked,
            'message' => $message,
            'available_amount' => round($availableAmount, 2),
            'locked_amount' => round($lockedAmount, 2),
            'gross_earnings' => round($grossEarnings, 2),
            'current_points' => round($points, 2),
            'remaining_cooldown_minutes' => $remainingCooldownMinutes,
            'has_active_account' => ((int) ($customer->c_lockstatus ?? 0) === 0) && ((int) ($customer->c_accnt_status ?? 0) === 1),
        ];
    }

    private function policyMeta(array $rules): array
    {
        return [
            'min_amount' => round((float) $rules['min_amount'], 2),
            'min_points' => round((float) $rules['min_points'], 2),
            'cooldown_hours' => (int) $rules['cooldown_hours'],
            'require_active_account' => (bool) $rules['require_active_account'],
        ];
    }
}
