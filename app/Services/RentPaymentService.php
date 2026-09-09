<?php

namespace App\Services;

use App\Models\LeaseContract;
use App\Models\RentPayment;
use App\Notifications\WorkflowNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RentPaymentService
{
    /** The caller holds the lease and unit locks in the activation transaction. */
    public function generate(LeaseContract $lease): void
    {
        $start = CarbonImmutable::instance($lease->start_date);
        $end = CarbonImmutable::instance($lease->end_date);
        for ($month = $start->startOfMonth(); $month->lte($end); $month = $month->addMonth()) {
            $due = $month->day(min((int) $lease->payment_due_day, $month->daysInMonth));
            $due = $due->max($start)->min($end);
            $lease->payments()->firstOrCreate(['due_date' => $due->toDateString()], [
                'company_id' => $lease->company_id, 'tenant_id' => $lease->tenant_id,
                'unit_id' => $lease->unit_id, 'amount' => $lease->monthly_rent, 'status' => 'pending',
            ]);
        }
    }

    public function markPaid(RentPayment $payment, array $data): RentPayment
    {
        Gate::authorize('update', $payment);

        return DB::transaction(function () use ($payment, $data) {
            $payment = RentPayment::lockForUpdate()->findOrFail($payment->id);
            Gate::authorize('update', $payment);
            if (! in_array($payment->status, ['pending', 'overdue'], true)) {
                throw ValidationException::withMessages(['payment' => __('workflow.payment_locked')]);
            }
            $payment->update(['status' => 'paid', 'paid_at' => $data['paid_at'], 'payment_method' => $data['payment_method']]);
            $this->notify($payment, 'payment_registered');

            return $payment;
        }, 3);
    }

    public function updateDuePayments(): array
    {
        $counts = ['overdue' => 0, 'upcoming' => 0];
        RentPayment::whereIn('status', ['pending', 'overdue'])
            ->whereDate('due_date', '<=', today()->addDays(3))
            ->whereHas('company', fn ($query) => $query->where('status', 'active'))
            ->select('id')->chunkById(100, function ($payments) use (&$counts) {
                foreach ($payments as $candidate) {
                    $updated = DB::transaction(function () use ($candidate) {
                        $changes = ['overdue' => 0, 'upcoming' => 0];
                        $payment = RentPayment::lockForUpdate()->findOrFail($candidate->id);
                        if (! in_array($payment->status, ['pending', 'overdue'], true)) {
                            return $changes;
                        }
                        if ($payment->due_date->isBefore(today())) {
                            if ($payment->status === 'pending') {
                                $payment->status = 'overdue';
                                $changes['overdue']++;
                            }
                            if (! $payment->overdue_notified_at && $this->notify($payment, 'payment_overdue')) {
                                $payment->overdue_notified_at = now();
                            }
                        } elseif ($payment->status === 'pending' && ! $payment->upcoming_notified_at && $this->notify($payment, 'payment_upcoming')) {
                            $payment->upcoming_notified_at = now();
                            $changes['upcoming']++;
                        }
                        $payment->save();

                        return $changes;
                    }, 3);
                    $counts['overdue'] += $updated['overdue'];
                    $counts['upcoming'] += $updated['upcoming'];
                }
            });

        return $counts;
    }

    private function notify(RentPayment $payment, string $message): bool
    {
        $user = $payment->tenant?->user;
        if (! $user || ! $user->hasActiveWorkspace() || (int) $user->company_id !== (int) $payment->company_id) {
            return false;
        }
        $user->notify(new WorkflowNotification($message, 'payments.show', $payment->id, ['date' => $payment->due_date->toDateString(), 'amount' => $payment->amount]));

        return true;
    }
}
