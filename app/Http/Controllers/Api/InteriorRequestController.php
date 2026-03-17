<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\Interior\InteriorRequestSubmittedMail;
use App\Mail\Interior\InteriorRequestUpdateMail;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\InteriorRequest;
use App\Models\InteriorRequestUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InteriorRequestController extends Controller
{
    public function store(Request $request)
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Only customer accounts can submit interior service requests.'], 403);
        }

        $validated = $request->validate([
            'service_type' => 'required|string|max:120',
            'project_type' => 'required|string|max:120',
            'property_type' => 'nullable|string|max:120',
            'project_scope' => 'nullable|string|max:1000',
            'budget' => 'nullable|string|max:120',
            'style_preference' => 'nullable|string|max:255',
            'preferred_date' => 'required|date',
            'preferred_time' => 'required|string|max:80',
            'flexibility' => 'nullable|string|max:120',
            'target_timeline' => 'nullable|string|max:120',
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:5000',
            'referral' => 'nullable|string|max:255',
            'inspiration_files' => 'nullable|array|max:20',
            'inspiration_files.*' => 'string|max:500',
        ]);

        $interiorRequest = DB::transaction(function () use ($validated, $customer) {
            $row = InteriorRequest::query()->create([
                'ir_customer_id' => (int) $customer->c_userid,
                'ir_reference' => $this->buildReference(),
                'ir_service_type' => trim((string) $validated['service_type']),
                'ir_project_type' => trim((string) $validated['project_type']),
                'ir_property_type' => trim((string) ($validated['property_type'] ?? '')),
                'ir_project_scope' => trim((string) ($validated['project_scope'] ?? '')),
                'ir_budget' => trim((string) ($validated['budget'] ?? '')),
                'ir_style_preference' => trim((string) ($validated['style_preference'] ?? '')),
                'ir_preferred_date' => $validated['preferred_date'],
                'ir_preferred_time' => trim((string) $validated['preferred_time']),
                'ir_flexibility' => trim((string) ($validated['flexibility'] ?? '')),
                'ir_target_timeline' => trim((string) ($validated['target_timeline'] ?? '')),
                'ir_first_name' => trim((string) $validated['first_name']),
                'ir_last_name' => trim((string) $validated['last_name']),
                'ir_email' => trim((string) $validated['email']),
                'ir_phone' => trim((string) ($validated['phone'] ?? '')),
                'ir_notes' => trim((string) ($validated['notes'] ?? '')),
                'ir_referral' => trim((string) ($validated['referral'] ?? '')),
                'ir_inspiration_files' => $validated['inspiration_files'] ?? [],
                'ir_status' => 'pending',
                'ir_priority' => 'normal',
            ]);

            InteriorRequestUpdate::query()->create([
                'iru_request_id' => (int) $row->ir_id,
                'iru_type' => 'message',
                'iru_title' => 'Request received',
                'iru_message' => 'Your interior service request is now queued for admin review. We will confirm the next step and consultation schedule soon.',
                'iru_payload' => [
                    'system' => true,
                    'status' => 'pending',
                ],
                'iru_visible_to_customer' => true,
            ]);

            $this->storeCustomerNotification(
                customerId: (int) $customer->c_userid,
                title: 'Interior request received',
                message: 'Your booking request is pending admin review. We will email you once the schedule or estimate is updated.',
                href: $this->customerInboxHref((int) $row->ir_id),
                sourceId: (int) $row->ir_id
            );

            $this->storeAdminNotification($row);

            return $row->fresh(['customer', 'assignedAdmin', 'updates.admin']);
        });

        $this->sendSubmittedEmail($interiorRequest);

        return response()->json([
            'message' => 'Interior request submitted successfully.',
            'request' => $this->formatRequest($interiorRequest, true),
        ], 201);
    }

    public function myRequests(Request $request)
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Only customer accounts can access interior service requests.'], 403);
        }

        $rows = InteriorRequest::query()
            ->with(['assignedAdmin', 'updates' => function ($query) {
                $query->where('iru_visible_to_customer', true)->with('admin');
            }])
            ->where('ir_customer_id', (int) $customer->c_userid)
            ->orderByDesc('updated_at')
            ->orderByDesc('ir_id')
            ->get();

        return response()->json([
            'requests' => $rows->map(fn (InteriorRequest $row) => $this->formatRequest($row, false, true))->values(),
            'counts' => $this->buildCounts($rows),
            'generated_at' => now()->toDateTimeString(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Only customer accounts can access interior service requests.'], 403);
        }

        $row = InteriorRequest::query()
            ->with(['assignedAdmin', 'updates' => function ($query) {
                $query->where('iru_visible_to_customer', true)->with('admin');
            }])
            ->where('ir_customer_id', (int) $customer->c_userid)
            ->where('ir_id', $id)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Interior request not found.'], 404);
        }

        return response()->json([
            'request' => $this->formatRequest($row, false, true),
        ]);
    }

    public function adminIndex(Request $request)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageInteriorRequests($admin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => 'nullable|string|max:50',
            'q' => 'nullable|string|max:120',
        ]);

        $status = strtolower(trim((string) ($validated['status'] ?? 'all')));
        $search = trim((string) ($validated['q'] ?? ''));

        $rows = InteriorRequest::query()
            ->with(['customer', 'assignedAdmin', 'updates.admin'])
            ->when($status !== '' && $status !== 'all', function ($query) use ($status) {
                $query->where('ir_status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('ir_reference', 'like', "%{$search}%")
                        ->orWhere('ir_service_type', 'like', "%{$search}%")
                        ->orWhere('ir_project_type', 'like', "%{$search}%")
                        ->orWhere('ir_property_type', 'like', "%{$search}%")
                        ->orWhere('ir_first_name', 'like', "%{$search}%")
                        ->orWhere('ir_last_name', 'like', "%{$search}%")
                        ->orWhere('ir_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('ir_id')
            ->get();

        return response()->json([
            'requests' => $rows->map(fn (InteriorRequest $row) => $this->formatRequest($row, true))->values(),
            'counts' => $this->buildCounts($rows),
            'generated_at' => now()->toDateTimeString(),
        ]);
    }

    public function adminUpdate(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageInteriorRequests($admin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,reviewing,estimate_ready,scheduled,completed,cancelled',
            'priority' => 'nullable|string|in:normal,priority',
            'assign_to_me' => 'nullable|boolean',
        ]);

        $row = InteriorRequest::query()->with(['customer', 'assignedAdmin', 'updates.admin'])->where('ir_id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Interior request not found.'], 404);
        }

        $changes = [];
        if (array_key_exists('status', $validated) && $validated['status'] !== $row->ir_status) {
            $changes[] = 'status updated to ' . str_replace('_', ' ', (string) $validated['status']);
            $row->ir_status = $validated['status'];
        }
        if (array_key_exists('priority', $validated) && $validated['priority'] !== $row->ir_priority) {
            $changes[] = 'priority set to ' . (string) $validated['priority'];
            $row->ir_priority = $validated['priority'];
        }
        if (!empty($validated['assign_to_me']) && (int) ($row->ir_assigned_admin_id ?? 0) !== (int) $admin->id) {
            $changes[] = 'assigned to ' . $this->adminDisplayName($admin);
            $row->ir_assigned_admin_id = (int) $admin->id;
        }

        if (empty($changes)) {
            return response()->json([
                'message' => 'No changes to save.',
                'request' => $this->formatRequest($row, true),
            ]);
        }

        $row->save();

        InteriorRequestUpdate::query()->create([
            'iru_request_id' => (int) $row->ir_id,
            'iru_actor_admin_id' => (int) $admin->id,
            'iru_type' => 'schedule',
            'iru_title' => 'Admin updated request details',
            'iru_message' => 'Your request details were updated: ' . implode(', ', $changes) . '.',
            'iru_payload' => [
                'status' => $row->ir_status,
                'priority' => $row->ir_priority,
                'assigned_admin_id' => $row->ir_assigned_admin_id ? (int) $row->ir_assigned_admin_id : null,
            ],
            'iru_visible_to_customer' => true,
        ]);

        $row = $row->fresh(['customer', 'assignedAdmin', 'updates.admin']);

        $this->storeCustomerNotification(
            customerId: (int) $row->ir_customer_id,
            title: 'Interior request updated',
            message: 'Your booking request now has a new admin update. Open your project inbox to review the latest schedule or status change.',
            href: $this->customerInboxHref((int) $row->ir_id),
            sourceId: (int) $row->ir_id
        );

        $this->sendUpdateEmail(
            $row,
            'Your booking request has a new status update.',
            'Open your project inbox to view the latest schedule or request status.'
        );

        return response()->json([
            'message' => 'Interior request updated.',
            'request' => $this->formatRequest($row, true),
        ]);
    }

    public function adminStoreUpdate(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->canManageInteriorRequests($admin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:message,estimate,design,schedule',
            'title' => 'required|string|max:160',
            'message' => 'required|string|max:5000',
            'visible_to_customer' => 'nullable|boolean',
            'status' => 'nullable|string|in:pending,reviewing,estimate_ready,scheduled,completed,cancelled',
            'assign_to_me' => 'nullable|boolean',
            'payload' => 'nullable|array',
        ]);

        $row = InteriorRequest::query()->with(['customer', 'assignedAdmin', 'updates.admin'])->where('ir_id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Interior request not found.'], 404);
        }

        if (!empty($validated['assign_to_me']) && (int) ($row->ir_assigned_admin_id ?? 0) !== (int) $admin->id) {
            $row->ir_assigned_admin_id = (int) $admin->id;
        }
        if (!empty($validated['status']) && $validated['status'] !== $row->ir_status) {
            $row->ir_status = $validated['status'];
        }
        $row->save();

        $visibleToCustomer = array_key_exists('visible_to_customer', $validated)
            ? (bool) $validated['visible_to_customer']
            : true;

        InteriorRequestUpdate::query()->create([
            'iru_request_id' => (int) $row->ir_id,
            'iru_actor_admin_id' => (int) $admin->id,
            'iru_type' => $validated['type'],
            'iru_title' => trim((string) $validated['title']),
            'iru_message' => trim((string) $validated['message']),
            'iru_payload' => $validated['payload'] ?? [
                'status' => $row->ir_status,
            ],
            'iru_visible_to_customer' => $visibleToCustomer,
        ]);

        $row = $row->fresh(['customer', 'assignedAdmin', 'updates.admin']);

        if ($visibleToCustomer) {
            $title = match ((string) $validated['type']) {
                'estimate' => 'Interior estimate ready',
                'design' => 'Interior design update',
                'schedule' => 'Interior schedule update',
                default => 'New interior request message',
            };

            $this->storeCustomerNotification(
                customerId: (int) $row->ir_customer_id,
                title: $title,
                message: trim((string) $validated['title']) . ': ' . trim((string) $validated['message']),
                href: $this->customerInboxHref((int) $row->ir_id),
                sourceId: (int) $row->ir_id
            );

            $this->sendUpdateEmail(
                $row,
                trim((string) $validated['title']),
                trim((string) $validated['message'])
            );
        }

        return response()->json([
            'message' => 'Interior request update posted.',
            'request' => $this->formatRequest($row, true),
        ]);
    }

    private function resolveAdmin(Request $request): ?Admin
    {
        $user = $request->user();

        return $user instanceof Admin ? $user : null;
    }

    private function canManageInteriorRequests(Admin $admin): bool
    {
        return in_array($this->roleFromAdmin($admin), ['super_admin', 'admin', 'csr', 'merchant_admin'], true);
    }

    private function roleFromAdmin(Admin $admin): string
    {
        return match ((int) $admin->user_level_id) {
            1 => 'super_admin',
            2 => 'admin',
            3 => 'csr',
            7 => 'merchant_admin',
            default => 'staff',
        };
    }

    private function buildReference(): string
    {
        return 'INT-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(4));
    }

    private function buildCounts($rows): array
    {
        $collection = collect($rows);

        return [
            'all' => (int) $collection->count(),
            'pending' => (int) $collection->where('ir_status', 'pending')->count(),
            'reviewing' => (int) $collection->where('ir_status', 'reviewing')->count(),
            'estimate_ready' => (int) $collection->where('ir_status', 'estimate_ready')->count(),
            'scheduled' => (int) $collection->where('ir_status', 'scheduled')->count(),
            'completed' => (int) $collection->where('ir_status', 'completed')->count(),
        ];
    }

    private function formatRequest(InteriorRequest $row, bool $includeCustomer = false, bool $customerOnlyUpdates = false): array
    {
        $updates = $row->relationLoaded('updates') ? $row->updates : collect();
        if ($customerOnlyUpdates) {
            $updates = $updates->filter(fn (InteriorRequestUpdate $update) => (bool) $update->iru_visible_to_customer)->values();
        }

        $latestUpdate = $updates->sortByDesc(fn (InteriorRequestUpdate $update) => $update->created_at?->getTimestamp() ?? 0)->first();

        return [
            'id' => (int) $row->ir_id,
            'reference' => (string) ($row->ir_reference ?? ''),
            'service_type' => (string) ($row->ir_service_type ?? ''),
            'project_type' => (string) ($row->ir_project_type ?? ''),
            'property_type' => (string) ($row->ir_property_type ?? ''),
            'project_scope' => (string) ($row->ir_project_scope ?? ''),
            'budget' => (string) ($row->ir_budget ?? ''),
            'style_preference' => (string) ($row->ir_style_preference ?? ''),
            'preferred_date' => $row->ir_preferred_date?->toDateString(),
            'preferred_time' => (string) ($row->ir_preferred_time ?? ''),
            'flexibility' => (string) ($row->ir_flexibility ?? ''),
            'target_timeline' => (string) ($row->ir_target_timeline ?? ''),
            'first_name' => (string) ($row->ir_first_name ?? ''),
            'last_name' => (string) ($row->ir_last_name ?? ''),
            'email' => (string) ($row->ir_email ?? ''),
            'phone' => (string) ($row->ir_phone ?? ''),
            'notes' => (string) ($row->ir_notes ?? ''),
            'referral' => (string) ($row->ir_referral ?? ''),
            'inspiration_files' => is_array($row->ir_inspiration_files) ? $row->ir_inspiration_files : [],
            'status' => (string) ($row->ir_status ?? 'pending'),
            'priority' => (string) ($row->ir_priority ?? 'normal'),
            'submitted_at' => optional($row->created_at)->toDateTimeString(),
            'updated_at' => optional($row->updated_at)->toDateTimeString(),
            'latest_update' => $latestUpdate ? [
                'title' => (string) ($latestUpdate->iru_title ?? ''),
                'message' => (string) ($latestUpdate->iru_message ?? ''),
                'created_at' => optional($latestUpdate->created_at)->toDateTimeString(),
            ] : null,
            'assigned_admin' => $row->assignedAdmin ? [
                'id' => (int) $row->assignedAdmin->id,
                'name' => $this->adminDisplayName($row->assignedAdmin),
                'email' => (string) ($row->assignedAdmin->user_email ?? ''),
            ] : null,
            'customer' => $includeCustomer && $row->customer ? [
                'id' => (int) $row->customer->c_userid,
                'name' => trim((string) implode(' ', array_filter([
                    $row->customer->c_fname ?? null,
                    $row->customer->c_lname ?? null,
                ]))) ?: trim((string) ($row->ir_first_name . ' ' . $row->ir_last_name)),
                'email' => (string) ($row->customer->c_email ?? $row->ir_email ?? ''),
            ] : null,
            'updates' => $updates->map(function (InteriorRequestUpdate $update) {
                return [
                    'id' => (int) $update->iru_id,
                    'type' => (string) ($update->iru_type ?? 'message'),
                    'title' => (string) ($update->iru_title ?? ''),
                    'message' => (string) ($update->iru_message ?? ''),
                    'visible_to_customer' => (bool) $update->iru_visible_to_customer,
                    'payload' => is_array($update->iru_payload) ? $update->iru_payload : null,
                    'created_at' => optional($update->created_at)->toDateTimeString(),
                    'actor_admin' => $update->admin ? [
                        'id' => (int) $update->admin->id,
                        'name' => $this->adminDisplayName($update->admin),
                        'email' => (string) ($update->admin->user_email ?? ''),
                    ] : null,
                ];
            })->values(),
        ];
    }

    private function adminDisplayName(Admin $admin): string
    {
        return trim((string) ($admin->fname ?: $admin->username ?: $admin->user_email ?: 'Admin'));
    }

    private function customerInboxHref(int $requestId): string
    {
        return '/profile?tab=interior-requests&request=' . $requestId;
    }

    private function adminInboxHref(int $requestId): string
    {
        return '/admin/interior-requests?request=' . $requestId;
    }

    private function storeCustomerNotification(int $customerId, string $title, string $message, string $href, int $sourceId): void
    {
        CustomerNotification::query()->create([
            'cn_customer_id' => $customerId,
            'cn_type' => 'interior_request',
            'cn_severity' => 'info',
            'cn_title' => $title,
            'cn_message' => $message,
            'cn_href' => $href,
            'cn_payload' => [
                'request_id' => $sourceId,
            ],
            'cn_source_type' => 'interior_request',
            'cn_source_id' => $sourceId,
            'cn_created_at' => now(),
        ]);
    }

    private function storeAdminNotification(InteriorRequest $row): void
    {
        AdminNotification::query()->create([
            'an_type' => 'interior_request_created',
            'an_severity' => 'warning',
            'an_title' => 'New Interior Booking Request',
            'an_message' => sprintf(
                '%s %s submitted an interior request for %s.',
                trim((string) ($row->ir_first_name ?? 'Customer')),
                trim((string) ($row->ir_last_name ?? '')),
                trim((string) ($row->ir_service_type ?? 'Interior Service'))
            ),
            'an_href' => $this->adminInboxHref((int) $row->ir_id),
            'an_payload' => [
                'request_id' => (int) $row->ir_id,
                'reference' => (string) ($row->ir_reference ?? ''),
                'service_type' => (string) ($row->ir_service_type ?? ''),
                'customer_email' => (string) ($row->ir_email ?? ''),
            ],
            'an_source_type' => 'interior_request',
            'an_source_id' => (int) $row->ir_id,
            'an_created_at' => now(),
        ]);
    }

    private function sendSubmittedEmail(InteriorRequest $row): void
    {
        $recipient = trim((string) ($row->ir_email ?? ''));
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $frontend = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');
        $inboxUrl = $frontend . $this->customerInboxHref((int) $row->ir_id);
        $mailRecipient = env('MAIL_TEST_TO') ?: $recipient;

        try {
            Mail::mailer('resend')->to($mailRecipient)->send(new InteriorRequestSubmittedMail([
                'customer_name' => trim((string) ($row->ir_first_name . ' ' . $row->ir_last_name)) ?: 'Customer',
                'reference' => (string) ($row->ir_reference ?? ''),
                'service_type' => (string) ($row->ir_service_type ?? ''),
                'project_type' => (string) ($row->ir_project_type ?? ''),
                'preferred_date' => $row->ir_preferred_date?->toFormattedDateString(),
                'preferred_time' => (string) ($row->ir_preferred_time ?? ''),
                'status_label' => 'Pending Review',
                'inbox_url' => $inboxUrl,
            ]));
        } catch (\Throwable $e) {
            Log::error('Interior submitted email send failed.', [
                'request_id' => (int) $row->ir_id,
                'recipient' => $mailRecipient,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    private function sendUpdateEmail(InteriorRequest $row, string $headline, string $message): void
    {
        $recipient = trim((string) ($row->ir_email ?? ''));
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $frontend = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');
        $inboxUrl = $frontend . $this->customerInboxHref((int) $row->ir_id);
        $mailRecipient = env('MAIL_TEST_TO') ?: $recipient;

        try {
            Mail::mailer('resend')->to($mailRecipient)->send(new InteriorRequestUpdateMail([
                'customer_name' => trim((string) ($row->ir_first_name . ' ' . $row->ir_last_name)) ?: 'Customer',
                'reference' => (string) ($row->ir_reference ?? ''),
                'headline' => $headline,
                'message' => $message,
                'service_type' => (string) ($row->ir_service_type ?? ''),
                'status_label' => ucwords(str_replace('_', ' ', (string) ($row->ir_status ?? 'pending'))),
                'inbox_url' => $inboxUrl,
            ]));
        } catch (\Throwable $e) {
            Log::error('Interior update email send failed.', [
                'request_id' => (int) $row->ir_id,
                'recipient' => $mailRecipient,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
