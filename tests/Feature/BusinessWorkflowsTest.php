<?php

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\LeaseContract;
use App\Models\MaintenanceActivity;
use App\Models\MaintenanceRequest;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

function workflowUser(string $role, ?int $companyId = null): User
{
    $user = User::factory()->create($companyId ? ['company_id' => $companyId] : []);
    $user->assignRole($role);

    return $user;
}

function leaseInput(Unit $unit, Tenant $tenant, array $overrides = []): array
{
    return [...[
        'unit_id' => $unit->id, 'tenant_id' => $tenant->id, 'contract_number' => 'RF-WORKFLOW-001',
        'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'monthly_rent' => '2500.00',
        'security_deposit' => '2500.00', 'payment_due_day' => 5, 'status' => 'active',
    ], ...$overrides];
}

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 1, 1)->setTime(12, 0));
    $this->admin = workflowUser('company_admin');
    $this->tenantUser = workflowUser('tenant', $this->admin->company_id);
    $this->staff = workflowUser('maintenance_staff', $this->admin->company_id);
    $building = Building::factory()->create(['company_id' => $this->admin->company_id]);
    $this->unit = Unit::factory()->create(['building_id' => $building->id, 'status' => 'vacant']);
    $this->tenant = Tenant::factory()->create(['company_id' => $this->admin->company_id, 'user_id' => $this->tenantUser->id]);
});

function workflowLease(object $test, array $overrides = []): LeaseContract
{
    return LeaseContract::factory()->create(['company_id' => $test->admin->company_id, 'unit_id' => $test->unit->id, 'tenant_id' => $test->tenant->id, ...$overrides]);
}

function workflowRequest(object $test, string $status = 'new'): MaintenanceRequest
{
    return MaintenanceRequest::factory()->create([
        'company_id' => $test->admin->company_id, 'unit_id' => $test->unit->id, 'building_id' => $test->unit->building_id,
        'tenant_id' => $test->tenant->id, 'status' => $status,
        'assigned_to' => in_array($status, ['assigned', 'in_progress', 'completed', 'cancelled'], true) ? $test->staff->id : null,
    ]);
}

test('active contract creation infers company occupies the unit and generates twelve exact payments', function () {
    $this->actingAs($this->admin)->post(route('leases.store'), leaseInput($this->unit, $this->tenant, ['company_id' => 999999]))
        ->assertSessionHasNoErrors()->assertRedirect();
    $lease = LeaseContract::firstOrFail();
    expect($lease->company_id)->toBe($this->admin->company_id)
        ->and($this->unit->fresh()->status)->toBe('occupied')
        ->and($lease->payments()->count())->toBe(12)
        ->and($lease->payments()->sum('amount'))->toEqual(30000);
    expect($lease->payments()->orderBy('due_date')->get()->pluck('due_date')->map->format('Y-m-d')->all())
        ->toBe(array_map(fn ($month) => sprintf('2026-%02d-05', $month), range(1, 12)));
    $this->get(route('leases.show', $lease))->assertOk()->assertSee('RF-WORKFLOW-001');
});

test('monthly schedule clamps short months including leap years', function (string $start, string $end, array $dates) {
    $this->actingAs($this->admin)->post(route('leases.store'), leaseInput($this->unit, $this->tenant, [
        'start_date' => $start, 'end_date' => $end, 'payment_due_day' => 31,
    ]))->assertSessionHasNoErrors();
    expect(RentPayment::orderBy('due_date')->get()->pluck('due_date')->map->format('Y-m-d')->all())->toBe($dates);
})->with([
    ['2026-01-01', '2026-03-31', ['2026-01-31', '2026-02-28', '2026-03-31']],
    ['2028-01-01', '2028-03-31', ['2028-01-31', '2028-02-29', '2028-03-31']],
    ['2026-01-20', '2026-02-10', ['2026-01-31', '2026-02-10']],
]);

test('first partial month never creates a due date before the lease starts', function () {
    $this->actingAs($this->admin)->post(route('leases.store'), leaseInput($this->unit, $this->tenant, ['start_date' => '2026-01-20', 'end_date' => '2026-02-20']))->assertSessionHasNoErrors();
    expect(RentPayment::orderBy('due_date')->get()->pluck('due_date')->map->format('Y-m-d')->all())->toBe(['2026-01-20', '2026-02-05']);
});

test('draft activation generates the schedule once and active terms cannot be rewritten', function () {
    $data = leaseInput($this->unit, $this->tenant, ['status' => 'draft']);
    $this->actingAs($this->admin)->post(route('leases.store'), $data)->assertSessionHasNoErrors();
    $lease = LeaseContract::firstOrFail();
    expect($lease->payments()->count())->toBe(0)->and($this->unit->fresh()->status)->toBe('vacant');
    $data['status'] = 'active';
    $this->put(route('leases.update', $lease), $data)->assertSessionHasNoErrors();
    $this->put(route('leases.update', $lease), $data)->assertSessionHasNoErrors();
    expect($lease->payments()->count())->toBe(12);
    $this->put(route('leases.update', $lease), [...$data, 'monthly_rent' => 999])->assertSessionHasErrors('monthly_rent');
    $this->put(route('leases.update', $lease), [...$data, 'status' => 'draft'])->assertSessionHasErrors('status');
    expect($lease->fresh()->monthly_rent)->toBe('2500.00');
});

test('active lease overlap is prevented including touching end dates', function (string $start, string $end) {
    workflowLease($this, ['start_date' => '2026-02-01', 'end_date' => '2026-04-30']);
    $this->actingAs($this->admin)->post(route('leases.store'), leaseInput($this->unit, $this->tenant, ['start_date' => $start, 'end_date' => $end]))
        ->assertSessionHasErrors('unit_id');
    expect(LeaseContract::count())->toBe(1)->and(RentPayment::count())->toBe(0);
})->with([['2026-01-01', '2026-02-01'], ['2026-04-30', '2026-06-30'], ['2026-03-01', '2026-03-31']]);

test('invalid dates and unavailable units fail validation without creating financial records', function () {
    $this->unit->update(['status' => 'maintenance']);
    $this->actingAs($this->admin);
    $this->post(route('leases.store'), leaseInput($this->unit, $this->tenant))->assertSessionHasErrors('unit_id');
    $this->post(route('leases.store'), leaseInput($this->unit, $this->tenant, ['start_date' => 'nonsense']))->assertSessionHasErrors('start_date');
    $this->post(route('leases.store'), leaseInput($this->unit, $this->tenant, ['payment_due_day' => 32]))->assertSessionHasErrors('payment_due_day');
    expect(LeaseContract::count())->toBe(0);
});

test('termination releases occupancy cancels future unpaid amounts and retains previous balances and receipts', function () {
    $data = leaseInput($this->unit, $this->tenant);
    $this->actingAs($this->admin)->post(route('leases.store'), $data)->assertSessionHasNoErrors();
    $lease = LeaseContract::firstOrFail();
    $lease->payments()->whereDate('due_date', '2026-01-05')->update(['status' => 'paid', 'paid_at' => '2026-01-05', 'payment_method' => 'cash']);
    $this->travelTo(now()->setDate(2026, 3, 10));
    $this->put(route('leases.update', $lease), [...$data, 'status' => 'terminated'])->assertSessionHasNoErrors();
    expect($this->unit->fresh()->status)->toBe('vacant')
        ->and($lease->payments()->where('status', 'cancelled')->count())->toBe(9)
        ->and($lease->payments()->where('status', 'paid')->count())->toBe(1)
        ->and($lease->payments()->where('status', 'pending')->count())->toBe(2);
    $this->delete(route('leases.destroy', $lease))->assertSessionHasErrors('lease');
});

test('only unused draft contracts can be deleted', function () {
    $draft = workflowLease($this, ['status' => 'draft']);
    $this->actingAs($this->admin)->delete(route('leases.destroy', $draft))->assertSessionHasNoErrors()->assertRedirect(route('leases.index'));
    $this->assertDatabaseMissing('lease_contracts', ['id' => $draft->id]);
});

test('expiry command releases ended units once but preserves maintenance state', function () {
    $this->unit->update(['status' => 'occupied']);
    $lease = workflowLease($this, ['start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
    $other = LeaseContract::factory()->create(['start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
    Unit::withoutGlobalScopes()->whereKey($other->unit_id)->update(['status' => 'maintenance']);
    $this->artisan('leases:expire')->assertSuccessful();
    $this->artisan('leases:expire')->assertSuccessful();
    expect($lease->fresh()->status)->toBe('expired')->and($this->unit->fresh()->status)->toBe('vacant')
        ->and(Unit::withoutGlobalScopes()->find($other->unit_id)->status)->toBe('maintenance');
});

test('accountant can register full payment once and notify the linked tenant', function () {
    Notification::fake();
    $accountant = workflowUser('accountant', $this->admin->company_id);
    $payment = RentPayment::factory()->create(['lease_contract_id' => workflowLease($this)->id]);
    $data = ['paid_at' => '2026-01-01T11:30', 'payment_method' => 'bank_transfer', 'amount' => 1, 'company_id' => 9999];
    $this->actingAs($accountant)->patch(route('payments.paid', $payment), $data)->assertSessionHasNoErrors();
    expect($payment->fresh()->status)->toBe('paid')->and($payment->fresh()->paid_at->format('Y-m-d H:i'))->toBe('2026-01-01 11:30')
        ->and($payment->fresh()->payment_method)->toBe('bank_transfer')->and($payment->fresh()->amount)->toBe('3500.00');
    Notification::assertSentTo($this->tenantUser, WorkflowNotification::class, fn ($notice) => $notice->message === 'payment_registered');
    $this->patch(route('payments.paid', $payment), $data)->assertSessionHasErrors('payment');
    Notification::assertCount(1);
});

test('payment validation rejects future receipt times unknown methods and cancelled records', function () {
    $payment = RentPayment::factory()->create(['lease_contract_id' => workflowLease($this)->id, 'status' => 'cancelled']);
    $this->actingAs($this->admin)->patch(route('payments.paid', $payment), ['paid_at' => '2030-01-01T12:00', 'payment_method' => 'unknown'])
        ->assertSessionHasErrors(['paid_at', 'payment_method']);
    $this->patch(route('payments.paid', $payment), ['paid_at' => '2026-01-01T11:00', 'payment_method' => 'cash'])->assertSessionHasErrors('payment');
});

test('overdue command respects today and final statuses and deduplicates database reminders', function () {
    $lease = workflowLease($this);
    $past = RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'due_date' => '2025-12-31']);
    $today = RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'due_date' => '2026-01-01']);
    $future = RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'due_date' => '2026-01-04']);
    $later = RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'due_date' => '2026-01-05']);
    $paid = RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'due_date' => '2025-12-29', 'status' => 'paid']);
    $cancelled = RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'due_date' => '2025-12-28', 'status' => 'cancelled']);
    $this->artisan('payments:update-overdue')->assertSuccessful();
    $this->artisan('payments:update-overdue')->assertSuccessful();
    expect($past->fresh()->status)->toBe('overdue')->and($today->fresh()->status)->toBe('pending')
        ->and($paid->fresh()->status)->toBe('paid')->and($cancelled->fresh()->status)->toBe('cancelled')
        ->and($later->fresh()->upcoming_notified_at)->toBeNull()
        ->and($this->tenantUser->notifications()->count())->toBe(3);
    $this->travelTo(now()->addDays(5));
    $this->artisan('payments:update-overdue')->assertSuccessful();
    expect($this->tenantUser->notifications()->where('data->message', 'payment_overdue')->count())->toBe(4);
});

test('tenant creates a request only for their current lease and notifies management with an audit event', function () {
    Notification::fake();
    workflowLease($this);
    $this->actingAs($this->tenantUser)->post(route('maintenance.store'), [
        'unit_id' => $this->unit->id, 'tenant_id' => 999999, 'assigned_to' => $this->staff->id, 'status' => 'completed',
        'title' => 'Water leak', 'description' => 'Leak under the sink', 'priority' => 'urgent',
    ])->assertSessionHasNoErrors();
    $request = MaintenanceRequest::firstOrFail();
    expect($request->status)->toBe('new')->and($request->assigned_to)->toBeNull()->and($request->tenant_id)->toBe($this->tenant->id)
        ->and($request->activities()->count())->toBe(1);
    $this->assertDatabaseHas('audit_logs', ['action' => 'maintenance.created', 'model_id' => $request->id]);
    Notification::assertSentTo($this->admin, WorkflowNotification::class, fn ($notice) => $notice->message === 'maintenance_new');
});

test('maintenance creation requires a current active lease', function () {
    workflowLease($this, ['status' => 'expired']);
    $this->actingAs($this->admin)->post(route('maintenance.store'), ['tenant_id' => $this->tenant->id, 'unit_id' => $this->unit->id, 'title' => 'Issue', 'description' => 'Details', 'priority' => 'medium'])
        ->assertSessionHasErrors('unit_id');
    expect(MaintenanceRequest::count())->toBe(0);
});

test('maintenance allowed transitions write timeline audit and tenant notifications', function (string $from, string $to) {
    Notification::fake();
    $request = workflowRequest($this, $from);
    $this->actingAs($this->admin)->patch(route('maintenance.transition', $request), ['status' => $to])->assertSessionHasNoErrors();
    expect($request->fresh()->status)->toBe($to)->and($request->activities()->count())->toBe(1);
    $this->assertDatabaseHas('maintenance_activities', ['maintenance_request_id' => $request->id, 'from_status' => $from, 'to_status' => $to]);
    $this->assertDatabaseHas('audit_logs', ['action' => $to === 'completed' ? 'maintenance.completed' : 'maintenance.status_changed', 'model_id' => $request->id]);
    if ($to === 'completed') {
        expect($request->fresh()->completed_at)->not->toBeNull();
    }
    Notification::assertSentTo($this->tenantUser, WorkflowNotification::class);
})->with([['new', 'under_review'], ['new', 'rejected'], ['under_review', 'rejected'], ['assigned', 'in_progress'], ['assigned', 'cancelled'], ['in_progress', 'completed']]);

test('forbidden maintenance transitions leave no timeline or audit side effects', function (string $from, string $to) {
    $request = workflowRequest($this, $from);
    $this->actingAs($this->admin)->patch(route('maintenance.transition', $request), ['status' => $to])->assertSessionHasErrors('status');
    expect($request->fresh()->status)->toBe($from)->and($request->activities()->count())->toBe(0)->and(AuditLog::count())->toBe(0);
})->with([['new', 'completed'], ['new', 'assigned'], ['under_review', 'assigned'], ['assigned', 'new'], ['in_progress', 'cancelled'], ['completed', 'new'], ['rejected', 'under_review'], ['cancelled', 'assigned']]);

test('assignment requires review and an active same-company maintenance employee', function () {
    Notification::fake();
    $foreign = workflowUser('maintenance_staff');
    $request = workflowRequest($this);
    $this->actingAs($this->admin)->patch(route('maintenance.assign', $request), ['assigned_to' => $this->staff->id])->assertSessionHasErrors('status');
    $this->patch(route('maintenance.transition', $request), ['status' => 'under_review'])->assertSessionHasNoErrors();
    $this->patch(route('maintenance.assign', $request), ['assigned_to' => $foreign->id])->assertSessionHasErrors('assigned_to');
    $this->patch(route('maintenance.assign', $request), ['assigned_to' => $this->admin->id])->assertSessionHasErrors('assigned_to');
    $this->patch(route('maintenance.assign', $request), ['assigned_to' => $this->staff->id])->assertSessionHasNoErrors();
    expect($request->fresh()->status)->toBe('assigned')->and($request->fresh()->assigned_to)->toBe($this->staff->id);
    $this->assertDatabaseHas('audit_logs', ['action' => 'maintenance.assigned', 'model_id' => $request->id]);
    Notification::assertSentTo($this->staff, WorkflowNotification::class, fn ($notice) => $notice->message === 'maintenance_assigned');
});

test('maintenance staff see only assigned requests and can progress but cannot assign or cancel', function () {
    $own = workflowRequest($this, 'assigned');
    $other = workflowRequest($this);
    $this->actingAs($this->staff)->get(route('maintenance.index'))->assertOk()->assertViewHas('requests', fn ($requests) => $requests->modelKeys() === [$own->id]);
    $this->get(route('maintenance.show', $other))->assertNotFound();
    $this->patch(route('maintenance.transition', $other), ['status' => 'under_review'])->assertNotFound();
    $this->patch(route('maintenance.assign', $own), ['assigned_to' => $this->staff->id])->assertForbidden();
    $this->patch(route('maintenance.transition', $own), ['status' => 'cancelled'])->assertForbidden();
    $this->patch(route('maintenance.transition', $own), ['status' => 'in_progress'])->assertSessionHasNoErrors();
    $this->post(route('maintenance.note', $own), ['body' => 'Valve replaced'])->assertSessionHasNoErrors();
    $this->patch(route('maintenance.transition', $own), ['status' => 'completed'])->assertSessionHasNoErrors();
    $this->get(route('maintenance.show', $own))->assertOk()->assertSee('Valve replaced')->assertSee($this->unit->unit_number);
    expect(Gate::allows('update', $other))->toBeFalse();
});

test('internal notes are hidden at query and page level from tenants', function () {
    $request = workflowRequest($this, 'assigned');
    $this->actingAs($this->admin)->post(route('maintenance.note', $request), ['body' => 'Private supplier pricing'])->assertSessionHasNoErrors();
    $this->actingAs($this->tenantUser)->get(route('maintenance.show', $request))->assertOk()->assertDontSee('Private supplier pricing')->assertDontSee(__('workflow.internal_notes'));
    expect(MaintenanceActivity::where('kind', 'note')->count())->toBe(0);
    $this->post(route('maintenance.note', $request), ['body' => 'Forged'])->assertForbidden();
    $this->patch(route('maintenance.transition', $request), ['status' => 'in_progress'])->assertForbidden();
});

test('cross-company workflow ids are inaccessible even when forged in URLs and bodies', function () {
    $foreignLease = LeaseContract::factory()->create();
    $foreignPayment = RentPayment::factory()->create(['lease_contract_id' => $foreignLease->id]);
    $foreignRequest = MaintenanceRequest::factory()->create();
    $this->actingAs($this->admin);
    foreach ([['leases.show', $foreignLease], ['payments.show', $foreignPayment], ['maintenance.show', $foreignRequest]] as [$route, $record]) {
        $this->get(route($route, $record))->assertNotFound();
        expect(Gate::allows('view', $record))->toBeFalse();
    }
    $this->post(route('leases.store'), leaseInput($this->unit, $this->tenant, ['tenant_id' => $foreignLease->tenant_id]))->assertSessionHasErrors('tenant_id');
    $this->patch(route('payments.paid', $foreignPayment), ['paid_at' => '2026-01-01T11:00', 'payment_method' => 'cash'])->assertNotFound();
    $this->patch(route('maintenance.assign', $foreignRequest), ['assigned_to' => $this->staff->id])->assertNotFound();
    $this->post(route('maintenance.note', $foreignRequest), ['body' => 'Forged'])->assertNotFound();
});

test('tenants can read their own finances but cannot create leases or register payments', function () {
    $lease = workflowLease($this);
    $payment = RentPayment::factory()->create(['lease_contract_id' => $lease->id]);
    $other = RentPayment::factory()->create();
    $this->actingAs($this->tenantUser)->get(route('leases.show', $lease))->assertOk();
    $this->get(route('payments.show', $payment))->assertOk()->assertDontSee(__('workflow.mark_paid'));
    $this->get(route('payments.show', $other))->assertNotFound();
    $this->post(route('leases.store'), leaseInput($this->unit, $this->tenant))->assertForbidden();
    $this->patch(route('payments.paid', $payment), ['paid_at' => '2026-01-01T11:00', 'payment_method' => 'cash'])->assertForbidden();
});

test('payment filters include tenant building date and overdue-only pages', function () {
    $lease = workflowLease($this);
    $overdue = RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'due_date' => '2025-12-31', 'status' => 'overdue']);
    RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'due_date' => '2026-01-05']);
    $this->actingAs($this->admin)->get(route('payments.index', ['tenant_id' => $this->tenant->id, 'building_id' => $this->unit->building_id, 'date_to' => '2025-12-31']))
        ->assertOk()->assertViewHas('payments', fn ($payments) => $payments->modelKeys() === [$overdue->id]);
    $this->get(route('payments.overdue', ['status' => 'paid']))->assertOk()->assertViewHas('payments', fn ($payments) => $payments->modelKeys() === [$overdue->id]);
});

test('notifications are localized and only their owner can mark them read', function () {
    $this->tenantUser->notify(new WorkflowNotification('maintenance_new', 'maintenance.show', 1, ['title' => 'Water leak']));
    $notice = $this->tenantUser->notifications()->firstOrFail();
    $this->actingAs($this->admin)->patch(route('notifications.read', $notice->id))->assertNotFound();
    $this->actingAs($this->tenantUser)->withSession(['locale' => 'ar'])->get(route('notifications.index'))->assertOk()->assertSee('طلب صيانة جديد')->assertSee('dir="rtl"', false);
    $this->patch(route('notifications.read', $notice->id))->assertSessionHasNoErrors();
    expect($notice->fresh()->read_at)->not->toBeNull();
});

test('workflow forms and detail screens render translated in both directions', function (string $locale) {
    $lease = workflowLease($this);
    $payment = RentPayment::factory()->create(['lease_contract_id' => $lease->id]);
    $request = workflowRequest($this, 'under_review');
    $this->actingAs($this->admin)->withSession(['locale' => $locale]);
    foreach ([route('leases.create'), route('leases.edit', $lease), route('leases.show', $lease), route('payments.index'), route('payments.show', $payment), route('maintenance.create'), route('maintenance.show', $request), route('notifications.index')] as $url) {
        $this->get($url)->assertOk()->assertSee('dir="'.($locale === 'ar' ? 'rtl' : 'ltr').'"', false)
            ->assertDontSee('workflow.')->assertDontSee('property.clear_filters')->assertDontSee('app.currency');
    }
})->with(['en', 'ar']);
