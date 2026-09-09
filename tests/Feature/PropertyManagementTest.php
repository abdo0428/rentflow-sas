<?php

use App\Models\Building;
use App\Models\Document;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

function propertyUser(string $role = 'company_admin', ?int $companyId = null): User
{
    $user = User::factory()->create($companyId ? ['company_id' => $companyId] : []);
    $user->assignRole($role);

    return $user;
}

function buildingInput(array $overrides = []): array
{
    return [...['name' => 'River House', 'code' => 'RIV-01', 'address' => '12 River Street', 'city' => 'Riyadh', 'district' => 'Olaya', 'total_floors' => 5, 'status' => 'active', 'notes' => 'Near the park'], ...$overrides];
}

function unitInput(int $buildingId, array $overrides = []): array
{
    return [...['building_id' => $buildingId, 'unit_number' => 'A-101', 'floor' => 1, 'type' => 'apartment', 'bedrooms' => 2, 'bathrooms' => 1, 'area' => '95.50', 'rent_amount' => '3200.50', 'status' => 'vacant'], ...$overrides];
}

function tenantInput(array $overrides = []): array
{
    return [...['full_name' => 'Lina Hassan', 'email' => 'lina@example.com', 'phone' => '+966555123456', 'national_id' => 'DEMO-123', 'address' => '12 River Street', 'emergency_contact_name' => 'Ali Hassan', 'emergency_contact_phone' => '+966555654321', 'notes' => 'Prefers email'], ...$overrides];
}

test('create building infers company and ignores supplied company id', function () {
    $admin = propertyUser();
    $foreign = ManagementCompany::factory()->create();
    $this->actingAs($admin)->post(route('buildings.store'), buildingInput(['company_id' => $foreign->id]))
        ->assertSessionHasNoErrors()->assertRedirect();
    $building = Building::firstOrFail();
    expect($building->company_id)->toBe($admin->company_id)->and($building->code)->toBe('RIV-01');
    $this->get(route('buildings.show', $building))->assertOk()->assertSee('River House')->assertSee(__('property.building_created'));
});

test('create unit derives company from an accessible building and validates uniqueness', function () {
    $admin = propertyUser();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    $other = Building::factory()->create(['company_id' => $admin->company_id]);
    $this->actingAs($admin)->post(route('units.store'), unitInput($building->id, ['company_id' => 999999]))
        ->assertSessionHasNoErrors()->assertRedirect();
    $unit = Unit::firstOrFail();
    expect($unit->company_id)->toBe($admin->company_id)->and($unit->rent_amount)->toBe('3200.50');
    $this->post(route('units.store'), unitInput($building->id))->assertSessionHasErrors('unit_number');
    $this->post(route('units.store'), unitInput($other->id))->assertSessionHasNoErrors();
    expect(Unit::count())->toBe(2);
});

test('unit requires an existing same company building and valid dimensions', function () {
    $admin = propertyUser();
    $foreign = Building::factory()->create();
    $own = Building::factory()->create(['company_id' => $admin->company_id, 'total_floors' => 2]);
    $this->actingAs($admin);
    $this->post(route('units.store'), unitInput(0))->assertSessionHasErrors('building_id');
    $this->post(route('units.store'), unitInput($foreign->id))->assertSessionHasErrors('building_id');
    $this->post(route('units.store'), unitInput($own->id, ['floor' => 3]))->assertSessionHasErrors('floor');
    $this->post(route('units.store'), unitInput($own->id, ['rent_amount' => -1, 'area' => -2, 'type' => 'castle']))->assertSessionHasErrors(['rent_amount', 'area', 'type']);
    expect(Unit::count())->toBe(0);
});

test('create tenant saves personal data without accepting a forged linked account', function () {
    $admin = propertyUser();
    $other = propertyUser('tenant');
    $this->actingAs($admin)->post(route('tenants.store'), tenantInput(['company_id' => $other->company_id, 'user_id' => $other->id]))
        ->assertSessionHasNoErrors()->assertRedirect();
    $tenant = Tenant::firstOrFail();
    expect($tenant->company_id)->toBe($admin->company_id)->and($tenant->user_id)->toBeNull()
        ->and($tenant->emergency_contact_name)->toBe('Ali Hassan');
    $this->get(route('tenants.show', $tenant))->assertOk()->assertSee('Lina Hassan')->assertSee('DEMO-123');
});

test('building and tenant create validate required fields with Arabic feedback', function () {
    $admin = propertyUser();
    $this->actingAs($admin)->withSession(['locale' => 'ar']);
    $this->post(route('buildings.store'), buildingInput(['name' => '', 'total_floors' => -1, 'status' => 'unknown']))->assertSessionHasErrors(['name', 'total_floors', 'status']);
    expect(session('errors')->first('total_floors'))->toContain('عدد الطوابق');
    $this->post(route('tenants.store'), tenantInput(['email' => 'invalid', 'phone' => '']))->assertSessionHasErrors(['email', 'phone']);
});

test('building code is unique inside a company and updates ignore the current record', function () {
    $admin = propertyUser();
    $own = Building::factory()->create(['company_id' => $admin->company_id, 'code' => 'EXISTING']);
    Building::factory()->create(['code' => 'RIV-01']);
    $this->actingAs($admin);
    $this->post(route('buildings.store'), buildingInput(['code' => 'EXISTING']))->assertSessionHasErrors('code');
    $this->post(route('buildings.store'), buildingInput())->assertSessionHasNoErrors();
    $this->put(route('buildings.update', $own), buildingInput(['code' => 'EXISTING']))->assertSessionHasNoErrors();
});

test('all resource edit pages and updates work without changing company ownership', function () {
    $admin = propertyUser();
    $foreign = ManagementCompany::factory()->create();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $tenant = Tenant::factory()->create(['company_id' => $admin->company_id]);
    $this->actingAs($admin);
    foreach (['buildings' => $building, 'units' => $unit, 'tenants' => $tenant] as $module => $record) {
        $this->get(route($module.'.edit', $record))->assertOk();
    }
    $this->put(route('buildings.update', $building), buildingInput(['company_id' => $foreign->id]))->assertSessionHasNoErrors();
    $this->put(route('units.update', $unit), unitInput($building->id, ['company_id' => $foreign->id]))->assertSessionHasNoErrors();
    $this->put(route('tenants.update', $tenant), tenantInput(['company_id' => $foreign->id]))->assertSessionHasNoErrors();
    expect($building->refresh()->name)->toBe('River House')
        ->and($unit->refresh()->unit_number)->toBe('A-101')
        ->and($tenant->refresh()->full_name)->toBe('Lina Hassan');
    foreach ([$building, $unit, $tenant] as $record) {
        expect($record->company_id)->toBe($admin->company_id);
    }
});

test('company B identifiers cannot be used in resource read edit update or delete URLs', function (string $module, string $class) {
    $admin = propertyUser();
    $foreign = $class::factory()->create();
    $this->actingAs($admin);
    $this->get(route($module.'.show', $foreign))->assertNotFound();
    $this->get(route($module.'.edit', $foreign))->assertNotFound();
    $this->put(route($module.'.update', $foreign), [])->assertNotFound();
    $this->delete(route($module.'.destroy', $foreign))->assertNotFound();
    expect(Gate::allows('view', $foreign))->toBeFalse()
        ->and(Gate::allows('update', $foreign))->toBeFalse()
        ->and(Gate::allows('delete', $foreign))->toBeFalse();
})->with([['buildings', Building::class], ['units', Unit::class], ['tenants', Tenant::class]]);

test('read only roles cannot create update or delete property records', function (string $role) {
    $user = propertyUser($role);
    $building = Building::factory()->create(['company_id' => $user->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $tenant = Tenant::factory()->create(['company_id' => $user->company_id]);
    $this->actingAs($user);
    foreach (['buildings' => $building, 'units' => $unit, 'tenants' => $tenant] as $module => $record) {
        $this->get(route($module.'.create'))->assertForbidden();
        $this->post(route($module.'.store'), [])->assertForbidden();
        $response = $this->put(route($module.'.update', $record), []);
        expect(in_array($response->status(), [403, 404], true))->toBeTrue();
        expect(Gate::allows('create', $record::class))->toBeFalse();
        expect(Gate::allows('update', $record))->toBeFalse();
        expect(Gate::allows('delete', $record))->toBeFalse();
    }
})->with(['accountant', 'tenant', 'maintenance_staff']);

test('property manager policies permit CRUD for their own company', function () {
    $manager = propertyUser('property_manager');
    $building = Building::factory()->create(['company_id' => $manager->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $tenant = Tenant::factory()->create(['company_id' => $manager->company_id]);
    $this->actingAs($manager);
    foreach ([$building, $unit, $tenant] as $record) {
        expect(Gate::allows('create', $record::class))->toBeTrue()
            ->and(Gate::allows('update', $record))->toBeTrue()
            ->and(Gate::allows('delete', $record))->toBeTrue();
    }
});

test('empty records can be deleted safely', function () {
    $admin = propertyUser();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $tenant = Tenant::factory()->create(['company_id' => $admin->company_id]);
    $this->actingAs($admin);
    foreach (['units' => $unit, 'tenants' => $tenant, 'buildings' => $building] as $module => $record) {
        $this->delete(route($module.'.destroy', $record))->assertSessionHasNoErrors()->assertRedirect(route($module.'.index'));
        $this->assertDatabaseMissing($record->getTable(), ['id' => $record->id]);
    }
});

test('building with units cannot be deleted and returns useful feedback', function () {
    $admin = propertyUser();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    Unit::factory()->create(['building_id' => $building->id]);
    $this->actingAs($admin)->from(route('buildings.show', $building))->delete(route('buildings.destroy', $building))
        ->assertRedirect(route('buildings.show', $building))->assertSessionHasErrors('deletion');
    expect(session('errors')->first('deletion'))->toBe(__('property.building_has_units'));
    $this->assertDatabaseHas('buildings', ['id' => $building->id]);
});

test('active and historical leases prevent tenant deletion without losing records', function (string $status) {
    $admin = propertyUser();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $lease = LeaseContract::factory()->create(['unit_id' => $unit->id, 'status' => $status]);
    $tenant = Tenant::withoutGlobalScopes()->findOrFail($lease->tenant_id);
    $this->actingAs($admin)->delete(route('tenants.destroy', $tenant))->assertSessionHasErrors('deletion');
    expect(session('errors')->first('deletion'))->toBe(__($status === 'active' ? 'property.tenant_active_lease' : 'property.related_records'));
    $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    $this->assertDatabaseHas('lease_contracts', ['id' => $lease->id]);
})->with(['active', 'expired']);

test('units with tenancy history cannot be deleted moved or marked vacant during an active lease', function () {
    $admin = propertyUser();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    $other = Building::factory()->create(['company_id' => $admin->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id, 'status' => 'occupied']);
    LeaseContract::factory()->create(['unit_id' => $unit->id]);
    $this->actingAs($admin);
    $this->delete(route('units.destroy', $unit))->assertSessionHasErrors('deletion');
    $this->put(route('units.update', $unit), unitInput($other->id, ['status' => 'occupied']))->assertSessionHasErrors('building_id');
    $this->put(route('units.update', $unit), unitInput($building->id))->assertSessionHasErrors('status');
    expect($unit->refresh()->status)->toBe('occupied')->and($unit->building_id)->toBe($building->id);
});

test('unoccupied unit can move to another building only inside its company', function () {
    $admin = propertyUser();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    $other = Building::factory()->create(['company_id' => $admin->company_id]);
    $foreign = Building::factory()->create();
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $this->actingAs($admin);
    $this->put(route('units.update', $unit), unitInput($foreign->id))->assertSessionHasErrors('building_id');
    $this->put(route('units.update', $unit), unitInput($other->id))->assertSessionHasNoErrors();
    expect($unit->refresh()->building_id)->toBe($other->id);
});

test('building filters are combined inside company scope and survive pagination', function () {
    $admin = propertyUser();
    Building::factory()->count(11)->create(['company_id' => $admin->company_id, 'city' => 'Riyadh', 'name' => 'Matching building', 'status' => 'active']);
    Building::factory()->create(['company_id' => $admin->company_id, 'city' => 'Jeddah', 'name' => 'Hidden by filter', 'status' => 'inactive']);
    Building::factory()->create(['name' => 'Matching foreign secret', 'city' => 'Riyadh']);
    $this->actingAs($admin)->get(route('buildings.index', ['q' => 'Matching', 'city' => 'Riyadh', 'status' => 'active']))
        ->assertOk()->assertSee('Matching building')->assertDontSee('Hidden by filter')->assertDontSee('Matching foreign secret')->assertSee('city=Riyadh', false)->assertSee('page=2', false);
});

test('unit filters and statistics do not include another company', function () {
    $admin = propertyUser();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    Unit::factory()->create(['building_id' => $building->id, 'unit_number' => 'MATCH', 'type' => 'office', 'status' => 'vacant']);
    Unit::factory()->create(['building_id' => $building->id, 'unit_number' => 'HIDDEN', 'type' => 'shop', 'status' => 'maintenance']);
    Unit::factory()->create(['unit_number' => 'FOREIGN']);
    $this->actingAs($admin)->get(route('units.index', ['building_id' => $building->id, 'type' => 'office', 'status' => 'vacant']))
        ->assertOk()->assertSee('MATCH')->assertDontSee('HIDDEN')->assertDontSee('FOREIGN')
        ->assertViewHas('total', 2)->assertViewHas('counts', fn ($counts) => $counts['maintenance'] === 1);
});

test('tenant details show only related records and respect related module permissions', function () {
    $admin = propertyUser();
    $building = Building::factory()->create(['company_id' => $admin->company_id]);
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $lease = LeaseContract::factory()->create(['unit_id' => $unit->id, 'contract_number' => 'PERSONAL-LEASE']);
    $tenant = Tenant::withoutGlobalScopes()->findOrFail($lease->tenant_id);
    RentPayment::factory()->create(['lease_contract_id' => $lease->id]);
    MaintenanceRequest::factory()->create(['unit_id' => $unit->id, 'tenant_id' => $tenant->id, 'title' => 'Personal repair']);
    Document::factory()->create(['documentable_id' => $lease->id, 'title' => 'Personal document']);
    $otherLease = LeaseContract::factory()->create(['unit_id' => $unit->id, 'contract_number' => 'UNRELATED-LEASE']);
    Document::factory()->create(['documentable_id' => $otherLease->id, 'title' => 'Unrelated document']);
    $accountant = propertyUser('accountant', $admin->company_id);
    $this->actingAs($admin)->get(route('tenants.show', $tenant))->assertOk()->assertSee('PERSONAL-LEASE')
        ->assertSee('Personal repair')->assertSee('Personal document')->assertDontSee('UNRELATED-LEASE')->assertDontSee('Unrelated document');
    $this->actingAs($accountant)->get(route('tenants.show', $tenant))->assertOk()->assertSee('PERSONAL-LEASE')->assertDontSee('Personal repair');
});

test('private polymorphic attachments prevent orphaning records on deletion', function () {
    $admin = propertyUser();
    $tenant = Tenant::factory()->create(['company_id' => $admin->company_id]);
    Document::factory()->create(['company_id' => $admin->company_id, 'documentable_type' => 'tenant', 'documentable_id' => $tenant->id, 'uploaded_by' => $admin->id]);
    $this->actingAs($admin)->delete(route('tenants.destroy', $tenant))->assertSessionHasErrors('deletion');
    $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
});

test('super admin chooses a company for creation but cannot move an existing unit between companies', function () {
    $super = propertyUser('super_admin');
    $company = ManagementCompany::factory()->create();
    $building = Building::factory()->create(['company_id' => $company->id]);
    $foreign = Building::factory()->create();
    $unit = Unit::factory()->create(['building_id' => $building->id]);
    $this->actingAs($super);
    $this->post(route('buildings.store'), buildingInput())->assertSessionHasErrors('company_id');
    $this->post(route('buildings.store'), buildingInput(['company_id' => $company->id]))->assertSessionHasNoErrors();
    $this->post(route('tenants.store'), tenantInput(['company_id' => $company->id]))->assertSessionHasNoErrors();
    $this->put(route('units.update', $unit), unitInput($foreign->id))->assertSessionHasErrors('building_id');
});

test('Arabic and English property forms empty states and details render', function (string $locale) {
    $admin = propertyUser();
    $this->actingAs($admin)->withSession(['locale' => $locale]);
    foreach (['buildings', 'units', 'tenants'] as $module) {
        $this->get(route($module.'.index'))->assertOk()->assertSee('dir="'.($locale === 'ar' ? 'rtl' : 'ltr').'"', false);
        $this->get(route($module.'.create'))->assertOk();
    }
})->with(['en', 'ar']);

test('a search for zero remains a real search term', function () {
    $admin = propertyUser();
    $match = Building::factory()->create(['company_id' => $admin->company_id, 'name' => 'Block 0', 'code' => 'ZERO', 'address' => 'River Street']);
    Building::factory()->create(['company_id' => $admin->company_id, 'name' => 'Other block', 'code' => 'OTHER', 'address' => 'River Street']);
    $this->actingAs($admin)->get(route('buildings.index', ['q' => '0']))->assertOk()
        ->assertViewHas('buildings', fn ($buildings) => $buildings->pluck('id')->all() === [$match->id]);
});
