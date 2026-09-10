<?php

use App\Models\Building;
use App\Models\LeaseContract;
use App\Models\RentPayment;
use App\Models\User;
use App\Support\Tenancy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

test('demo leases have complete in-range schedules at year and leap boundaries', function (string $date) {
    $this->travelTo(new DateTimeImmutable($date));
    $this->seed(DatabaseSeeder::class);

    app(Tenancy::class)->runWithoutScope(function () {
        $leases = LeaseContract::with('payments')->get();
        expect($leases)->toHaveCount(3);
        foreach ($leases as $lease) {
            expect($lease->start_date->lte(today()))->toBeTrue()
                ->and($lease->end_date->gte(today()))->toBeTrue()
                ->and($lease->payments)->toHaveCount(12);
            foreach ($lease->payments as $payment) {
                expect($payment->due_date->betweenIncluded($lease->start_date, $lease->end_date))->toBeTrue();
                if ($payment->status === 'overdue') {
                    expect($payment->due_date->lt(today()))->toBeTrue();
                }
            }
            expect($lease->payments->pluck('status')->unique()->sort()->values()->all())->toBe(['overdue', 'paid', 'pending']);
        }
        $payment = RentPayment::where('status', 'pending')->firstOrFail();
        $payment->update(['status' => 'paid', 'paid_at' => now(), 'payment_method' => 'cash']);
        $this->seed(DatabaseSeeder::class);
        expect($payment->fresh()->payment_method)->toBe('cash');
        $this->assertDatabaseCount('rent_payments', 36);
    });
})->with(['2027-01-01 12:00:00', '2026-12-31 12:00:00', '2028-02-29 12:00:00']);

test('property table query count stays bounded when the number of rows grows', function () {
    $admin = User::factory()->create();
    $admin->assignRole('company_admin');
    $this->actingAs($admin);
    Building::factory()->create(['company_id' => $admin->company_id]);
    $this->get('/buildings')->assertOk();
    DB::enableQueryLog();
    try {
        DB::flushQueryLog();
        $this->get('/buildings')->assertOk();
        $oneRow = count(DB::getQueryLog());
        Building::factory()->count(8)->create(['company_id' => $admin->company_id]);
        DB::flushQueryLog();
        $this->get('/buildings')->assertOk();
        expect(count(DB::getQueryLog()))->toBeLessThanOrEqual($oneRow + 1);
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
});

test('Arabic invalid uploads and dates return translated feedback without storing files', function () {
    Storage::fake('local');
    $admin = User::factory()->create();
    $admin->assignRole('company_admin');
    $this->actingAs($admin)->withSession(['locale' => 'ar']);
    $response = $this->post('/maintenance', [
        'tenant_id' => 1, 'unit_id' => 1, 'title' => 'طلب صيانة', 'description' => 'تفاصيل المشكلة',
        'priority' => 'high', 'preferred_date' => today()->subDay()->toDateString(),
        'photo' => UploadedFile::fake()->create('unsafe.svg', 6000, 'image/svg+xml'),
    ]);
    $response->assertSessionHasErrors(['preferred_date', 'photo']);
    $errors = session('errors');
    expect($errors->first('photo'))->toContain('صورة صالحة')
        ->and($errors->first('preferred_date'))->toContain('يجب')
        ->and(implode(' ', $errors->all()))->not->toContain('validation.', 'The ', 'today');
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('production seeding never creates public demo credentials', function () {
    $this->app->instance('env', 'production');
    $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('management_companies', 0);
    $this->assertDatabaseCount('roles', 6);
});
