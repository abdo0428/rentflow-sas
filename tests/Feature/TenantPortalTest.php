<?php

use App\Models\Announcement;
use App\Models\Building;
use App\Models\Document;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TenantDashboardService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;

function portalUser(string $role, ?int $companyId = null): User
{
    $user = User::factory()->create($companyId ? ['company_id' => $companyId] : []);
    $user->assignRole($role);

    return $user;
}

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 10)->setTime(12, 0));
    $this->owner = portalUser('company_admin');
    $this->resident = portalUser('tenant', $this->owner->company_id);
    $this->profile = Tenant::factory()->create(['company_id' => $this->owner->company_id, 'user_id' => $this->resident->id, 'full_name' => 'أحمد علي']);
    $building = Building::factory()->create(['company_id' => $this->owner->company_id, 'name' => 'مبنى النور']);
    $this->home = Unit::factory()->create(['building_id' => $building->id, 'status' => 'occupied']);
    $this->agreement = LeaseContract::factory()->create([
        'unit_id' => $this->home->id, 'tenant_id' => $this->profile->id,
        'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'contract_number' => 'RESIDENT-2026',
    ]);
});

test('resident dashboard aggregates only current personal data', function () {
    $overdue = RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'due_date' => '2026-09-01', 'amount' => 1200]);
    $upcoming = RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'due_date' => '2026-10-01', 'amount' => 1500]);
    RentPayment::factory()->create(['due_date' => '2026-08-01', 'amount' => 999999]);
    $data = app(TenantDashboardService::class)->forUser($this->resident);
    expect($data['lease']->id)->toBe($this->agreement->id)->and($data['nextPayment']->id)->toBe($upcoming->id)
        ->and((float) $data['overdueAmount'])->toBe(1200.0);
    $this->actingAs($this->resident)->get('/dashboard')->assertOk()->assertSee(__('portal.portal'))->assertDontSee('999,999');
});

test('resident portal uses a separate translated layout and complete navigation', function (string $locale) {
    $this->actingAs($this->resident)->withSession(['locale' => $locale]);
    foreach (['dashboard', 'tenant.unit', 'tenant.lease', 'payments.index', 'maintenance.index', 'tenant.announcements', 'tenant.documents', 'profile.edit'] as $route) {
        $response = $this->get(route($route))->assertOk()->assertSee('dir="'.($locale === 'ar' ? 'rtl' : 'ltr').'"', false)
            ->assertDontSee('workspace-sidebar')->assertDontSee('portal.');
        expect($response->headers->get('Cache-Control'))->toContain('no-store');
    }
    $this->get(route('tenant.unit'))->assertSee('مبنى النور');
    $this->get(route('tenant.lease'))->assertSee('RESIDENT-2026')->assertSee(route('leases.pdf', $this->agreement), false);
})->with(['en', 'ar']);

test('unassigned residents get useful empty states and staff cannot enter tenant endpoints', function () {
    $newResident = portalUser('tenant', $this->owner->company_id);
    $staff = portalUser('maintenance_staff', $this->owner->company_id);
    $this->actingAs($newResident)->get('/dashboard')->assertOk()->assertSee(__('portal.no_unit'));
    $this->get(route('tenant.unit'))->assertOk()->assertSee(__('portal.no_unit'));
    $this->get(route('tenant.lease'))->assertOk()->assertSee(__('portal.no_lease'));
    $this->actingAs($staff)->get(route('tenant.unit'))->assertForbidden();
    $this->actingAs($this->owner)->get(route('tenant.documents'))->assertForbidden();
});

test('announcements exclude former future and foreign buildings and unpublished items', function () {
    $former = Building::factory()->create(['company_id' => $this->owner->company_id]);
    $formerUnit = Unit::factory()->create(['building_id' => $former->id]);
    LeaseContract::factory()->create(['unit_id' => $formerUnit->id, 'tenant_id' => $this->profile->id, 'status' => 'expired', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
    $future = Building::factory()->create(['company_id' => $this->owner->company_id]);
    $futureUnit = Unit::factory()->create(['building_id' => $future->id]);
    LeaseContract::factory()->create(['unit_id' => $futureUnit->id, 'tenant_id' => $this->profile->id, 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
    $visible = collect(['all', 'company', 'building'])->map(fn ($target) => Announcement::factory()->create([
        'company_id' => $this->owner->company_id, 'target' => $target,
        'building_id' => $target === 'building' ? $this->home->building_id : null, 'published_at' => now()->subHour(),
    ]));
    $hidden = [
        Announcement::factory()->create(['company_id' => $this->owner->company_id, 'target' => 'building', 'building_id' => $former->id]),
        Announcement::factory()->create(['company_id' => $this->owner->company_id, 'target' => 'building', 'building_id' => $future->id]),
        Announcement::factory()->create(['company_id' => $this->owner->company_id, 'status' => 'draft']),
        Announcement::factory()->create(['company_id' => $this->owner->company_id, 'published_at' => now()->addDay()]),
        Announcement::factory()->create(),
    ];
    $this->actingAs($this->resident)->get(route('tenant.announcements'))->assertOk()
        ->assertViewHas('announcements', fn ($items) => $items->pluck('id')->sort()->values()->all() === $visible->pluck('id')->sort()->values()->all());
    foreach ($hidden as $announcement) {
        $this->get(route('announcements.show', $announcement))->assertNotFound();
    }
});

test('current unit excludes ended and future active agreements', function () {
    $this->agreement->update(['status' => 'expired']);
    $future = LeaseContract::factory()->create(['unit_id' => $this->home->id, 'tenant_id' => $this->profile->id, 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
    $this->actingAs($this->resident)->get(route('tenant.unit'))->assertOk()->assertViewHas('unit', null);
    expect(app(TenantDashboardService::class)->forUser($this->resident)['lease'])->toBeNull();
});

test('contract and receipt PDFs render as valid PDFs in both languages with private headers', function (string $locale) {
    $payment = RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'status' => 'paid', 'paid_at' => now(), 'payment_method' => 'cash']);
    $this->actingAs($this->resident)->withSession(['locale' => $locale]);
    foreach ([route('leases.pdf', $this->agreement), route('payments.receipt', $payment)] as $url) {
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        expect($response->headers->get('Content-Disposition'))->toContain('attachment')->and($response->headers->get('Cache-Control'))->toContain('no-store');
        expect($response->getContent())->toStartWith('%PDF-')->toContain('%%EOF');
        $parser = new PdfParser(StreamReader::createByString($response->getContent()));
        $reader = new PdfReader($parser);
        expect($reader->getPageCount())->toBeGreaterThan(0);
    }
})->with(['en', 'ar']);

test('PDF and document endpoints reject same-company other residents and other companies', function () {
    Storage::fake('local');
    $other = Tenant::factory()->create(['company_id' => $this->owner->company_id]);
    $otherLease = LeaseContract::factory()->create(['unit_id' => $this->home->id, 'tenant_id' => $other->id, 'status' => 'draft']);
    $foreign = LeaseContract::factory()->create();
    $payments = collect([$otherLease, $foreign])->map(fn ($lease) => RentPayment::factory()->create(['lease_contract_id' => $lease->id, 'status' => 'paid', 'paid_at' => now()]));
    $ownDoc = Document::factory()->create(['documentable_id' => $this->agreement->id]);
    $otherDoc = Document::factory()->create(['documentable_id' => $otherLease->id]);
    Storage::disk('local')->put($ownDoc->file_path, 'Private contract');
    $this->actingAs($this->resident);
    foreach ([$otherLease, $foreign] as $lease) {
        $this->get(route('leases.pdf', $lease))->assertNotFound();
    }
    foreach ($payments as $payment) {
        $this->get(route('payments.receipt', $payment))->assertNotFound();
    }
    $this->get(route('documents.download', $otherDoc))->assertNotFound();
    $response = $this->get(route('documents.download', $ownDoc))->assertOk()->assertDownload();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->get('/storage/'.$ownDoc->file_path)->assertNotFound();
    $this->get(route('tenant.documents'))->assertOk()->assertDontSee($ownDoc->file_path);
});

test('unpaid receipts guests and maintenance staff cannot download financial PDFs', function () {
    $pending = RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id]);
    $staff = portalUser('maintenance_staff', $this->owner->company_id);
    $this->get(route('leases.pdf', $this->agreement))->assertRedirect('/login');
    $this->actingAs($this->resident)->get(route('payments.receipt', $pending))->assertNotFound();
    $this->actingAs($staff)->get(route('leases.pdf', $this->agreement))->assertNotFound();
});

test('maintenance image is private and only the tenant management and assigned employee can download it', function () {
    Storage::fake('local');
    $staff = portalUser('maintenance_staff', $this->owner->company_id);
    $otherStaff = portalUser('maintenance_staff', $this->owner->company_id);
    $otherResident = portalUser('tenant', $this->owner->company_id);
    $this->actingAs($this->resident)->post(route('maintenance.store'), [
        'unit_id' => $this->home->id, 'title' => 'Leaking tap', 'description' => 'Please inspect the tap',
        'priority' => 'high', 'preferred_date' => '2026-09-12', 'photo' => UploadedFile::fake()->image('tap.jpg'),
        'assigned_to' => $staff->id, 'status' => 'completed',
    ])->assertSessionHasNoErrors();
    $request = MaintenanceRequest::firstOrFail();
    Storage::disk('local')->assertExists($request->photo_path);
    expect($request->status)->toBe('new')->and($request->assigned_to)->toBeNull();
    $this->get(route('maintenance.photo', $request))->assertOk()->assertDownload();
    $this->get('/storage/'.$request->photo_path)->assertNotFound();
    $this->patch(route('maintenance.assign', $request), ['assigned_to' => $staff->id])->assertForbidden();
    $this->patch(route('maintenance.transition', $request), ['status' => 'completed'])->assertForbidden();
    $this->actingAs($this->owner)->patch(route('maintenance.transition', $request), ['status' => 'under_review'])->assertSessionHasNoErrors();
    $this->patch(route('maintenance.assign', $request), ['assigned_to' => $staff->id])->assertSessionHasNoErrors();
    $this->actingAs($staff)->get(route('maintenance.photo', $request))->assertOk();
    $this->actingAs($otherStaff)->get(route('maintenance.photo', $request))->assertNotFound();
    $this->actingAs($otherResident)->get(route('maintenance.photo', $request))->assertNotFound();
});

test('invalid image types and failed requests leave no uploaded private files', function () {
    Storage::fake('local');
    $data = ['unit_id' => $this->home->id, 'title' => 'Issue', 'description' => 'Details', 'priority' => 'medium'];
    $this->actingAs($this->resident)->post(route('maintenance.store'), [...$data, 'photo' => UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('photo');
    $this->post(route('maintenance.store'), [...$data, 'unit_id' => 999999, 'photo' => UploadedFile::fake()->image('tap.jpg')])->assertNotFound();
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('company dashboard aggregates correct month boundaries and excludes other companies', function () {
    $vacant = Unit::factory()->create(['building_id' => $this->home->building_id, 'status' => 'vacant']);
    $other = LeaseContract::factory()->create();
    LeaseContract::factory()->create(['unit_id' => $vacant->id, 'tenant_id' => $this->profile->id, 'end_date' => '2026-10-10']);
    RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'amount' => 100, 'due_date' => '2026-09-01', 'status' => 'paid', 'paid_at' => '2026-09-01 00:00:00']);
    RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'amount' => 200, 'due_date' => '2026-09-20', 'status' => 'pending']);
    RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'amount' => 300, 'due_date' => '2026-09-09', 'status' => 'pending']);
    RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'amount' => 900, 'due_date' => '2026-09-30', 'status' => 'cancelled']);
    RentPayment::factory()->create(['lease_contract_id' => $other->id, 'amount' => 99999, 'due_date' => '2026-09-01', 'status' => 'paid', 'paid_at' => now()]);
    RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'amount' => 800, 'due_date' => '2026-08-01', 'status' => 'paid', 'paid_at' => '2026-10-01 00:00:00']);
    $data = app(DashboardService::class)->forUser($this->owner);
    $metrics = collect($data['metrics'])->keyBy('label');
    expect($metrics['buildings']['value'])->toBe(1)->and($metrics['units']['value'])->toBe(2)
        ->and($metrics['occupancy']['value'])->toBe('50%')->and($metrics['ending_soon']['value'])->toBe(1)
        ->and($metrics['due_this_month']['value'])->toStartWith('600.00')->and($metrics['revenue_this_month']['value'])->toStartWith('100.00')
        ->and($metrics['overdue_amount']['value'])->toStartWith('300.00');
    $this->actingAs($this->owner)->get('/dashboard')->assertOk()->assertDontSee('99,999');
});

test('super admin dashboard includes all companies and users', function () {
    $super = User::factory()->create(['company_id' => null]);
    $super->assignRole('super_admin');
    $foreign = Building::factory()->create();
    ManagementCompany::withoutGlobalScopes()->whereKey($foreign->company_id)->update(['status' => 'suspended']);
    $metrics = collect(app(DashboardService::class)->forUser($super)['metrics'])->keyBy('label');
    expect($metrics['companies']['value'])->toBe(2)->and($metrics['active_companies']['value'])->toBe(1)
        ->and($metrics['users']['value'])->toBe(3)->and($metrics['buildings']['value'])->toBe(2)->and($metrics['active_leases']['value'])->toBe(1);
});

test('accountant and maintenance dashboards respect their module permissions', function () {
    $accountant = portalUser('accountant', $this->owner->company_id);
    $staff = portalUser('maintenance_staff', $this->owner->company_id);
    $request = MaintenanceRequest::factory()->create(['unit_id' => $this->home->id, 'tenant_id' => $this->profile->id, 'assigned_to' => $staff->id, 'status' => 'assigned']);
    MaintenanceRequest::factory()->create(['unit_id' => $this->home->id, 'tenant_id' => $this->profile->id]);
    $staffData = app(DashboardService::class)->forUser($staff);
    expect(collect($staffData['metrics'])->pluck('label')->all())->toBe(['open_maintenance'])
        ->and($staffData['metrics'][0]['value'])->toBe(1)->and($staffData['requests']->modelKeys())->toBe([$request->id]);
    $accountantData = app(DashboardService::class)->forUser($accountant);
    expect(collect($accountantData['metrics'])->pluck('label'))->not->toContain('open_maintenance')
        ->and($accountantData['quickActions'])->toBeEmpty();
});

test('overdue listing includes unpaid past dates before the daily command runs', function () {
    $past = RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'due_date' => '2026-09-01']);
    RentPayment::factory()->create(['lease_contract_id' => $this->agreement->id, 'due_date' => '2026-10-01']);
    $this->actingAs($this->resident)->get(route('payments.overdue'))->assertOk()
        ->assertViewHas('payments', fn ($payments) => $payments->modelKeys() === [$past->id]);
});

test('former occupants retain their own lease files but lose access to shared unit documents', function () {
    Storage::fake('local');
    $unitDocument = Document::factory()->create([
        'company_id' => $this->owner->company_id, 'documentable_type' => 'unit', 'documentable_id' => $this->home->id,
    ]);
    Storage::disk('local')->put($unitDocument->file_path, 'Unit handbook');
    $this->agreement->update(['status' => 'expired']);
    $this->actingAs($this->resident)->get(route('documents.download', $unitDocument))->assertNotFound();
    $this->get(route('leases.pdf', $this->agreement))->assertOk()->assertHeader('Content-Type', 'application/pdf');
});
