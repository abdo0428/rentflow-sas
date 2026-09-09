<?php

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Document;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

function companyUser(string $role, ?int $companyId = null): User
{
    $user = User::factory()->create($companyId ? ['company_id' => $companyId] : []);
    $user->assignRole($role);

    return $user;
}

test('company queries, routes and policies block access to another company', function () {
    $user = companyUser('company_admin');
    $own = Building::factory()->create(['company_id' => $user->company_id]);
    $foreign = Building::factory()->create(['name' => 'Foreign private building']);
    $this->actingAs($user);
    expect(Building::pluck('id')->all())->toBe([$own->id])
        ->and(Gate::allows('view', $foreign))->toBeFalse()
        ->and(Gate::allows('update', $foreign))->toBeFalse();
    $this->get('/buildings')->assertOk()->assertDontSee('Foreign private building');
    $this->get(route('buildings.show', $foreign))->assertNotFound();
});

test('super admin sees records across companies', function () {
    $user = User::factory()->create(['company_id' => null]);
    $user->assignRole('super_admin');
    Building::factory()->count(2)->create();
    $this->actingAs($user);
    expect(Building::count())->toBe(2);
    foreach (Building::all() as $building) {
        expect(Gate::allows('view', $building))->toBeTrue();
        $this->get(route('buildings.show', $building))->assertOk();
    }
});

test('tenant sees only personal leases payments units documents and published relevant announcements', function () {
    $user = companyUser('tenant');
    $ownTenant = Tenant::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
    $otherTenant = Tenant::factory()->create(['company_id' => $user->company_id]);
    $building = Building::factory()->create(['company_id' => $user->company_id]);
    $otherBuilding = Building::factory()->create(['company_id' => $user->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $otherUnit = Unit::factory()->create(['building_id' => $otherBuilding->id]);
    $lease = LeaseContract::factory()->create(['unit_id' => $unit->id, 'tenant_id' => $ownTenant->id]);
    $otherLease = LeaseContract::factory()->create(['unit_id' => $otherUnit->id, 'tenant_id' => $otherTenant->id]);
    $payment = RentPayment::factory()->create(['lease_contract_id' => $lease->id]);
    $otherPayment = RentPayment::factory()->create(['lease_contract_id' => $otherLease->id]);
    $ownDoc = Document::factory()->create(['documentable_id' => $lease->id]);
    $foreignDoc = Document::factory()->create(['documentable_id' => $otherLease->id]);
    $visible = Announcement::factory()->create(['company_id' => $user->company_id]);
    Announcement::factory()->create(['company_id' => $user->company_id, 'status' => 'draft']);
    Announcement::factory()->create(['company_id' => $user->company_id, 'published_at' => now()->addDay()]);
    Announcement::factory()->create(['company_id' => $user->company_id, 'target' => 'building', 'building_id' => $otherBuilding->id]);
    $this->actingAs($user);
    expect(Tenant::pluck('id')->all())->toBe([$ownTenant->id])
        ->and(Unit::pluck('id')->all())->toBe([$unit->id])
        ->and(LeaseContract::pluck('id')->all())->toBe([$lease->id])
        ->and(RentPayment::pluck('id')->all())->toBe([$payment->id])
        ->and(Document::pluck('id')->all())->toBe([$ownDoc->id])
        ->and(Announcement::pluck('id')->all())->toBe([$visible->id]);
    $this->get(route('payments.show', $otherPayment))->assertNotFound();
    $this->get(route('leases.show', $otherLease))->assertNotFound();
    $this->get(route('documents.download', $foreignDoc))->assertNotFound();
    $this->get('/tenants')->assertForbidden();
    expect(Gate::allows('update', $lease))->toBeFalse();
});

test('maintenance staff can access only assigned requests and cannot assign work', function () {
    $user = companyUser('maintenance_staff');
    $building = Building::factory()->create(['company_id' => $user->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $assigned = MaintenanceRequest::factory()->create(['unit_id' => $unit->id, 'assigned_to' => $user->id]);
    $unassigned = MaintenanceRequest::factory()->create(['unit_id' => $unit->id]);
    $this->actingAs($user);
    expect(MaintenanceRequest::pluck('id')->all())->toBe([$assigned->id])
        ->and(Building::count())->toBe(0)
        ->and(Tenant::count())->toBe(0)
        ->and(RentPayment::count())->toBe(0)
        ->and(Gate::allows('update', $assigned))->toBeTrue()
        ->and(Gate::allows('update', $unassigned))->toBeFalse()
        ->and(Gate::allows('assign', $assigned))->toBeFalse();
    $this->get(route('maintenance.show', $assigned))->assertOk();
    $this->get(route('maintenance.show', $unassigned))->assertNotFound();
    $this->get('/payments')->assertForbidden();
});

test('company is inferred on creation and cannot be changed on update', function () {
    $user = companyUser('company_admin');
    $foreign = ManagementCompany::factory()->create();
    $this->actingAs($user);
    $building = Building::factory()->create(['company_id' => $foreign->id]);
    expect($building->company_id)->toBe($user->company_id);
    expect(fn () => $building->update(['company_id' => $foreign->id]))->toThrow(HttpException::class);
});

test('unscoped background reads fail closed until trusted context is explicit', function () {
    Building::factory()->create();
    expect(Building::count())->toBe(0);
    expect(app(Tenancy::class)->runWithoutScope(fn () => Building::count()))->toBe(1);
    expect(Building::count())->toBe(0);
    try {
        app(Tenancy::class)->runWithoutScope(fn () => throw new RuntimeException('test'));
    } catch (RuntimeException) {
    }
    expect(Building::count())->toBe(0);
});

test('MySQL enforces unique unit number within a building', function () {
    $unit = Unit::factory()->create();
    expect(fn () => Unit::factory()->create(['building_id' => $unit->building_id, 'unit_number' => $unit->unit_number]))->toThrow(QueryException::class);
});

test('MySQL enforces unique contract number within a company', function () {
    $lease = LeaseContract::factory()->create();
    expect(fn () => LeaseContract::factory()->create(['unit_id' => $lease->unit_id, 'contract_number' => $lease->contract_number]))->toThrow(QueryException::class);
});

test('MySQL rejects a unit attached to a building from another company', function () {
    $building = Building::factory()->create();
    $foreign = ManagementCompany::factory()->create();
    expect(fn () => Unit::factory()->create(['building_id' => $building->id, 'company_id' => $foreign->id]))->toThrow(QueryException::class);
});

test('MySQL rejects payments whose tenant does not match the lease', function () {
    $lease = LeaseContract::factory()->create();
    $other = Tenant::factory()->create(['company_id' => $lease->company_id]);
    expect(fn () => RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'tenant_id' => $other->id]))->toThrow(QueryException::class);
});

test('documents reject a polymorphic parent from another company', function () {
    $lease = LeaseContract::factory()->create();
    $foreign = ManagementCompany::factory()->create();
    expect(fn () => Document::factory()->create(['documentable_id' => $lease->id, 'company_id' => $foreign->id]))->toThrow(HttpException::class);
});

test('audit values are cast to arrays and remain hidden from tenant users', function () {
    $user = companyUser('tenant');
    $audit = AuditLog::factory()->create(['company_id' => $user->company_id]);
    expect($audit->new_values)->toBe(['source' => 'demo']);
    $this->actingAs($user);
    expect(AuditLog::count())->toBe(0);
});

test('policy evaluation and background work use the supplied user rather than the current session', function () {
    $admin = companyUser('company_admin');
    $otherAdmin = companyUser('company_admin');
    $own = Building::factory()->create(['company_id' => $admin->company_id]);
    $foreign = Building::factory()->create(['company_id' => $otherAdmin->company_id]);
    $this->actingAs($otherAdmin);
    expect(Gate::forUser($admin)->allows('view', $own))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $foreign))->toBeFalse()
        ->and(app(Tenancy::class)->runFor($admin, fn () => Building::pluck('id')->all()))->toBe([$own->id])
        ->and(Building::pluck('id')->all())->toBe([$foreign->id]);
});

test('suspension also blocks policy creation and model writes outside HTTP middleware', function () {
    $user = companyUser('company_admin');
    ManagementCompany::withoutGlobalScopes()->findOrFail($user->company_id)->update(['status' => 'suspended']);
    $this->actingAs($user);
    expect(Gate::allows('create', Building::class))->toBeFalse()
        ->and(Gate::allows('viewAny', Building::class))->toBeFalse();
    expect(fn () => Building::factory()->create(['company_id' => $user->company_id]))->toThrow(HttpException::class);
});

test('users without an assigned role cannot query company records', function () {
    $user = User::factory()->create();
    Building::factory()->create(['company_id' => $user->company_id]);
    $this->actingAs($user);
    expect(Building::count())->toBe(0);
    $this->get('/buildings')->assertForbidden();
});
