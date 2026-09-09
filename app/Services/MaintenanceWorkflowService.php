<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MaintenanceWorkflowService
{
    public const TRANSITIONS = [
        'new' => ['under_review', 'rejected'],
        'under_review' => ['assigned', 'rejected'],
        'assigned' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed'],
        'completed' => [], 'rejected' => [], 'cancelled' => [],
    ];

    public function create(array $data): MaintenanceRequest
    {
        Gate::authorize('create', MaintenanceRequest::class);

        return DB::transaction(function () use ($data) {
            $unit = Unit::lockForUpdate()->findOrFail($data['unit_id']);
            $tenant = auth()->user()->hasRole('tenant')
                ? Tenant::where('user_id', auth()->id())->firstOrFail()
                : Tenant::where('company_id', $unit->company_id)->findOrFail($data['tenant_id']);
            if (! LeaseContract::where('tenant_id', $tenant->id)->where('unit_id', $unit->id)
                ->where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->exists()) {
                throw ValidationException::withMessages(['unit_id' => __('workflow.maintenance_lease_required')]);
            }
            $request = MaintenanceRequest::create([
                'company_id' => $unit->company_id, 'building_id' => $unit->building_id,
                'unit_id' => $unit->id, 'tenant_id' => $tenant->id, 'title' => $data['title'],
                'description' => $data['description'], 'priority' => $data['priority'],
                'preferred_date' => $data['preferred_date'] ?? null, 'status' => 'new',
            ]);
            $this->record($request, null, 'maintenance.created');
            User::where('company_id', $request->company_id)->where('status', 'active')
                ->role(['company_admin', 'property_manager'])->each(fn (User $user) => $user->notify(
                    new WorkflowNotification('maintenance_new', 'maintenance.show', $request->id, ['title' => $request->title])
                ));

            return $request;
        }, 3);
    }

    public function assign(MaintenanceRequest $request, int $staffId): void
    {
        Gate::authorize('assign', $request);
        DB::transaction(function () use ($request, $staffId) {
            $request = MaintenanceRequest::lockForUpdate()->findOrFail($request->id);
            Gate::authorize('assign', $request);
            $this->validateTransition($request, 'assigned');
            $staff = User::where('company_id', $request->company_id)->where('status', 'active')->role('maintenance_staff')->find($staffId);
            if (! $staff) {
                throw ValidationException::withMessages(['assigned_to' => __('workflow.invalid_staff')]);
            }
            $old = $request->only(['status', 'assigned_to', 'completed_at']);
            $request->update(['assigned_to' => $staff->id, 'status' => 'assigned']);
            $this->record($request, $old, 'maintenance.assigned');
            $staff->notify(new WorkflowNotification('maintenance_assigned', 'maintenance.show', $request->id, ['title' => $request->title]));
            $this->notifyTenant($request);
        }, 3);
    }

    public function transition(MaintenanceRequest $request, string $status): void
    {
        Gate::authorize('update', $request);
        DB::transaction(function () use ($request, $status) {
            $request = MaintenanceRequest::lockForUpdate()->findOrFail($request->id);
            Gate::authorize('update', $request);
            $this->validateTransition($request, $status);
            if ($status === 'assigned') {
                throw ValidationException::withMessages(['status' => __('workflow.use_assignment')]);
            }
            if (auth()->user()->hasRole('maintenance_staff') && ! in_array($status, ['in_progress', 'completed'], true)) {
                abort(403);
            }
            $old = $request->only(['status', 'assigned_to', 'completed_at']);
            $request->update(['status' => $status, 'completed_at' => $status === 'completed' ? now() : null]);
            $this->record($request, $old, $status === 'completed' ? 'maintenance.completed' : 'maintenance.status_changed');
            $this->notifyTenant($request);
        }, 3);
    }

    public function addNote(MaintenanceRequest $request, string $body): void
    {
        Gate::authorize('note', $request);
        DB::transaction(function () use ($request, $body) {
            $request = MaintenanceRequest::lockForUpdate()->findOrFail($request->id);
            Gate::authorize('note', $request);
            $request->activities()->create(['company_id' => $request->company_id, 'user_id' => auth()->id(), 'kind' => 'note', 'body' => $body]);
        }, 3);
    }

    public function availableTransitions(MaintenanceRequest $request): array
    {
        if (! Gate::allows('update', $request)) {
            return [];
        }
        $transitions = array_diff(self::TRANSITIONS[$request->status], ['assigned']);
        if (auth()->user()->hasRole('maintenance_staff')) {
            $transitions = array_intersect($transitions, ['in_progress', 'completed']);
        }

        return array_combine($transitions, array_map(fn (string $status) => __('app.'.$status), $transitions));
    }

    private function validateTransition(MaintenanceRequest $request, string $status): void
    {
        if (! in_array($status, self::TRANSITIONS[$request->status], true)) {
            throw ValidationException::withMessages(['status' => __('workflow.invalid_transition')]);
        }
    }

    private function record(MaintenanceRequest $request, ?array $old, string $action): void
    {
        $request->activities()->create([
            'company_id' => $request->company_id, 'user_id' => auth()->id(), 'kind' => 'status',
            'from_status' => $old['status'] ?? null, 'to_status' => $request->status,
        ]);
        AuditLog::create([
            'company_id' => $request->company_id, 'user_id' => auth()->id(),
            'action' => $action, 'model_type' => $request->getMorphClass(), 'model_id' => $request->id,
            'old_values' => $old, 'new_values' => $request->only(['status', 'assigned_to', 'completed_at']),
            'ip_address' => request()->ip(),
        ]);
    }

    private function notifyTenant(MaintenanceRequest $request): void
    {
        /** Resolve only the linked recipient; staff have no general tenant browsing access. */
        User::where('company_id', $request->company_id)->where('status', 'active')
            ->whereHas('tenant', fn ($query) => $query->withoutGlobalScopes()->whereKey($request->tenant_id)->where('company_id', $request->company_id))
            ->each(fn (User $user) => $user->notify(new WorkflowNotification('maintenance_updated', 'maintenance.show', $request->id, ['title' => $request->title, 'status' => $request->status])));
    }
}
