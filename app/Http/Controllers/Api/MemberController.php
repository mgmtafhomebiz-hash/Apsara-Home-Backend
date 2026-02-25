<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = max(1, min($perPage, 100));
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $tier = trim((string) $request->query('tier', ''));

        $paginator = Customer::query()
            ->select([
                'c_userid',
                'c_username',
                'c_fname',
                'c_mname',
                'c_lname',
                'c_email',
                'c_lockstatus',
                'c_accnt_status',
                'c_rank',
                'c_totalpair',
                'c_gpv',
                'c_totalincome',
                'c_date_started',
                'c_last_logindate',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('c_username', 'ilike', $like)
                        ->orWhere('c_email', 'ilike', $like)
                        ->orWhere('c_fname', 'ilike', $like)
                        ->orWhere('c_mname', 'ilike', $like)
                        ->orWhere('c_lname', 'ilike', $like)
                        ->orWhereRaw(
                            "TRIM(COALESCE(c_fname, '') || ' ' || COALESCE(c_mname, '') || ' ' || COALESCE(c_lname, '')) ILIKE ?",
                            [$like]
                        );
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'blocked') {
                    $query->where('c_lockstatus', 1);
                    return;
                }

                if ($status === 'pending') {
                    $query->where('c_lockstatus', 0)->where('c_accnt_status', 0);
                    return;
                }

                if ($status === 'kyc_review') {
                    $query->where('c_lockstatus', 0)->where('c_accnt_status', 2);
                    return;
                }

                if ($status === 'active') {
                    $query->where('c_lockstatus', 0)->where('c_accnt_status', 1);
                }
            })
            ->when($tier !== '', function ($query) use ($tier) {
                if ($tier === 'Platinum') {
                    $query->where('c_rank', '>=', 4);
                    return;
                }

                if ($tier === 'Gold') {
                    $query->where('c_rank', 3);
                    return;
                }

                if ($tier === 'Silver') {
                    $query->where('c_rank', 2);
                    return;
                }

                if ($tier === 'Bronze') {
                    $query->where('c_rank', '<=', 1);
                }
            })
            ->orderByDesc('c_userid')
            ->paginate($perPage);

        $pageUserIds = collect($paginator->items())->pluck('c_userid')->all();

        $referralCounts = Customer::query()
            ->selectRaw('c_sponsor, COUNT(*) as total')
            ->whereIn('c_sponsor', $pageUserIds)
            ->groupBy('c_sponsor')
            ->pluck('total', 'c_sponsor');

        $members = collect($paginator->items())
            ->map(function (Customer $customer) use ($referralCounts): array {
                $fullName = trim(implode(' ', array_filter([
                    (string) $customer->c_fname,
                    (string) $customer->c_mname,
                    (string) $customer->c_lname,
                ])));

                if ($fullName === '') {
                    $fullName = (string) ($customer->c_username ?: ('Member #' . $customer->c_userid));
                }

                $status = $this->mapStatus(
                    (int) $customer->c_lockstatus,
                    (int) $customer->c_accnt_status
                );

                $rank = (int) $customer->c_rank;
                $tier = $this->mapTier($rank);
                $joinedAt = $this->formatDate($customer->c_date_started);
                $lastActiveAt = $this->formatDate($customer->c_last_logindate) ?: $joinedAt;

                return [
                    'id' => (int) $customer->c_userid,
                    'name' => $fullName,
                    'email' => (string) ($customer->c_email ?: ''),
                    'status' => $status,
                    'tier' => $tier,
                    'orders' => (int) $customer->c_totalpair,
                    'totalSpent' => (float) $customer->c_gpv,
                    'earnings' => (float) $customer->c_totalincome,
                    'referrals' => (int) ($referralCounts[(int) $customer->c_userid] ?? 0),
                    'joinedAt' => $joinedAt,
                    'lastActiveAt' => $lastActiveAt,
                ];
            })
            ->values();

        return response()->json([
            'members' => $members,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        $total = Customer::count();
        $active = Customer::where('c_lockstatus', 0)->where('c_accnt_status', 1)->count();
        $pending = Customer::where('c_lockstatus', 0)->whereIn('c_accnt_status', [0, 2])->count();
        $blocked = Customer::where('c_lockstatus', 1)->count();
        $totalSpent = (float) Customer::sum('c_gpv');
        $totalEarnings = (float) Customer::sum('c_totalincome');
        $totalReferrals = Customer::whereNotNull('c_sponsor')
            ->where('c_sponsor', '!=', 0)
            ->count();

        return response()->json([
            'total' => $total,
            'active' => $active,
            'pending' => $pending,
            'blocked' => $blocked,
            'totalSpent' => $totalSpent,
            'totalEarnings' => $totalEarnings,
            'totalReferrals' => $totalReferrals,
        ]);
    }

    private function mapStatus(int $lockStatus, int $accountStatus): string
    {
        if ($lockStatus === 1) {
            return 'blocked';
        }

        if ($accountStatus === 0) {
            return 'pending';
        }

        return 'active';
    }

    private function mapTier(int $rank): string
    {
        if ($rank >= 4) {
            return 'Platinum';
        }

        if ($rank >= 3) {
            return 'Gold';
        }

        if ($rank >= 2) {
            return 'Silver';
        }

        return 'Bronze';
    }

    private function formatDate(?string $value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return '';
        }
    }
}
