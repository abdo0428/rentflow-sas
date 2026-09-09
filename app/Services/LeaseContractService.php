<?php

namespace App\Services;

use App\Models\LeaseContract;
use App\Models\Tenant;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LeaseContractService
{
    public function __construct(private RentPaymentService $payments) {}

    public function save(array $data, LeaseContract $lease): LeaseContract
    {
        Gate::authorize($lease->exists ? 'update' : 'create', $lease->exists ? $lease : LeaseContract::class);
        try {
            return DB::transaction(function () use ($data, $lease) {
                $unitIds = array_unique(array_filter([$lease->unit_id, (int) $data['unit_id']]));
                $units = Unit::whereKey($unitIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $unit = $units->get((int) $data['unit_id']);
                abort_unless($unit, 404);
                if ($lease->exists) {
                    $lease = LeaseContract::lockForUpdate()->findOrFail($lease->id);
                    Gate::authorize('update', $lease);
                    abort_unless((int) $lease->company_id === (int) $unit->company_id, 403);
                }
                $tenant = Tenant::where('company_id', $unit->company_id)->findOrFail($data['tenant_id']);
                $start = CarbonImmutable::parse($data['start_date']);
                $end = CarbonImmutable::parse($data['end_date']);
                if ($end->lt($start) || $end->gt($start->addYears(10))) {
                    throw ValidationException::withMessages(['end_date' => __('workflow.invalid_duration')]);
                }
                $oldStatus = $lease->exists ? $lease->status : 'draft';
                if ($lease->exists && $oldStatus !== 'draft') {
                    foreach (['tenant_id', 'unit_id', 'monthly_rent', 'security_deposit', 'payment_due_day'] as $field) {
                        if ((string) $lease->$field !== (string) $data[$field] && (float) $lease->$field !== (float) $data[$field]) {
                            throw ValidationException::withMessages([$field => __('workflow.lease_terms_locked')]);
                        }
                    }
                    if (! $lease->start_date->equalTo($start) || ! $lease->end_date->equalTo($end)) {
                        throw ValidationException::withMessages(['end_date' => __('workflow.lease_terms_locked')]);
                    }
                    $allowed = $oldStatus === 'active' ? ['active', 'expired', 'terminated'] : [$oldStatus];
                    if (! in_array($data['status'], $allowed, true)) {
                        throw ValidationException::withMessages(['status' => __('workflow.invalid_transition')]);
                    }
                } elseif (! in_array($data['status'], ['draft', 'active'], true)) {
                    throw ValidationException::withMessages(['status' => __('workflow.invalid_transition')]);
                }
                if ($data['status'] === 'expired' && ! $end->isBefore(today())) {
                    throw ValidationException::withMessages(['status' => __('workflow.not_expired')]);
                }
                if ($data['status'] === 'active' && $end->isBefore(today())) {
                    throw ValidationException::withMessages(['end_date' => __('workflow.past_activation')]);
                }
                if ($data['status'] === 'active') {
                    $overlaps = LeaseContract::where('unit_id', $unit->id)->where('status', 'active')
                        ->when($lease->exists, fn ($query) => $query->whereKeyNot($lease->id))
                        ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->exists();
                    if ($overlaps) {
                        throw ValidationException::withMessages(['unit_id' => __('workflow.overlapping_lease')]);
                    }
                }
                if ($oldStatus === 'draft' && ! in_array($unit->status, ['vacant', 'reserved'], true)) {
                    throw ValidationException::withMessages(['unit_id' => __('workflow.unit_unavailable')]);
                }
                $lease->fill($data);
                $lease->company_id = $unit->company_id;
                $lease->tenant_id = $tenant->id;
                $lease->save();
                if ($lease->status === 'active' && $oldStatus === 'draft') {
                    $unit->update(['status' => 'occupied']);
                    $this->payments->generate($lease);
                }
                if ($oldStatus === 'active' && in_array($lease->status, ['expired', 'terminated'], true)) {
                    $this->releaseUnit($unit);
                    if ($lease->status === 'terminated') {
                        $lease->payments()->whereIn('status', ['pending', 'overdue'])->whereDate('due_date', '>', today())->update(['status' => 'cancelled']);
                    }
                }

                return $lease;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['contract_number' => __('validation.unique', ['attribute' => __('workflow.attributes.contract_number')])]);
        }
    }

    public function delete(LeaseContract $lease): void
    {
        Gate::authorize('delete', $lease);
        DB::transaction(function () use ($lease) {
            Unit::whereKey($lease->unit_id)->lockForUpdate()->firstOrFail();
            $lease = LeaseContract::lockForUpdate()->findOrFail($lease->id);
            Gate::authorize('delete', $lease);
            if ($lease->status !== 'draft' || $lease->payments()->exists() || $lease->documents()->exists()) {
                throw ValidationException::withMessages(['lease' => __('workflow.lease_delete_locked')]);
            }
            $lease->delete();
        }, 3);
    }

    public function expireDue(): int
    {
        $count = 0;
        LeaseContract::where('status', 'active')->whereDate('end_date', '<', today())
            ->whereHas('company', fn ($query) => $query->where('status', 'active'))
            ->select(['id', 'unit_id'])->chunkById(100, function ($leases) use (&$count) {
                foreach ($leases as $candidate) {
                    $count += DB::transaction(function () use ($candidate) {
                        $unit = Unit::whereKey($candidate->unit_id)->lockForUpdate()->firstOrFail();
                        $lease = LeaseContract::lockForUpdate()->findOrFail($candidate->id);
                        if ($lease->status !== 'active' || ! $lease->end_date->isBefore(today())) {
                            return 0;
                        }
                        $lease->update(['status' => 'expired']);
                        $this->releaseUnit($unit);

                        return 1;
                    }, 3);
                }
            });

        return $count;
    }

    private function releaseUnit(Unit $unit): void
    {
        if (! $unit->leases()->where('status', 'active')->exists() && $unit->status === 'occupied') {
            $unit->update(['status' => 'vacant']);
        }
    }
}
