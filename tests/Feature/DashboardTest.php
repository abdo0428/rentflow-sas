<?php

use App\Models\Building;
use App\Models\Document;
use App\Models\ManagementCompany;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;

test('all six demo roles can sign in and navigate only to permitted modules', function (string $email) {
    $this->seed(DatabaseSeeder::class);
    $this->post('/login', ['email' => $email.'@example.com', 'password' => 'password'])
        ->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
    $user = auth()->user();
    $this->get('/dashboard')->assertOk()->assertSee(__('app.'.$user->getRoleNames()->first()));
    foreach ([...array_keys(Navigation::MODULES), 'reports', 'settings'] as $module) {
        $response = $this->get(route($module.'.index'));
        $user->can($module.'.view') ? $response->assertOk() : $response->assertForbidden();
    }
})->with(['super', 'admin', 'manager', 'accountant', 'maintenance', 'tenant']);

test('Arabic and English switch layout direction and translate validation', function () {
    $this->from('/login')->post('/locale', ['locale' => 'ar'])->assertRedirect('/login');
    $this->get('/login')->assertOk()->assertSee('dir="rtl"', false)->assertSee('تسجيل الدخول');
    $this->post('/login', ['email' => 'invalid'])->assertSessionHasErrors(['email', 'password']);
    expect(session('errors')->first('password'))->toContain('مطلوب');
    $this->post('/locale', ['locale' => 'fr'])->assertSessionHasErrors('locale');
    $this->post('/locale', ['locale' => 'en'])->assertSessionHasNoErrors();
    $this->get('/login')->assertSee('dir="ltr"', false)->assertSee('Sign in');
});

test('every permitted demo detail view renders in both languages', function (string $locale) {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'super@example.com')->firstOrFail())->withSession(['locale' => $locale]);
    foreach (Navigation::MODULES as $module => $definition) {
        $record = $definition['model']::firstOrFail();
        $this->get(route($module.'.show', $record))->assertOk();
    }
    $this->get('/profile')->assertOk();
})->with(['en', 'ar']);

test('registration assigns a new company and owner role while ignoring privilege input', function () {
    $other = ManagementCompany::factory()->create();
    $this->post('/register', [
        'company_name' => 'New workspace', 'name' => 'New Owner', 'email' => 'owner@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
        'company_id' => $other->id, 'role' => 'super_admin', 'status' => 'inactive',
    ])->assertSessionHasNoErrors()->assertRedirect('/dashboard');
    $user = auth()->user();
    expect($user->company_id)->not->toBe($other->id)
        ->and($user->hasRole('company_admin'))->toBeTrue()
        ->and($user->hasRole('super_admin'))->toBeFalse()
        ->and($user->status)->toBe('active');
    $this->get('/dashboard')->assertRedirect(route('verification.notice'));
});

test('inactive users and suspended companies cannot authenticate or use existing sessions', function (string $state) {
    $user = User::factory()->create();
    $user->assignRole('company_admin');
    if ($state === 'user') {
        $user->forceFill(['status' => 'inactive'])->save();
    } else {
        ManagementCompany::withoutGlobalScopes()->findOrFail($user->company_id)->update(['status' => 'suspended']);
    }
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->actingAs($user)->get('/dashboard')->assertForbidden();
    $this->get('/profile')->assertForbidden();
    expect(Building::count())->toBe(0);
})->with(['user', 'company']);

test('demo seeding can be repeated without duplicating records', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);
    expect(User::count())->toBe(6);
    $this->assertDatabaseCount('management_companies', 1);
    $this->assertDatabaseCount('buildings', 2);
    $this->assertDatabaseCount('units', 12);
    $this->assertDatabaseCount('rent_payments', 36);
    $this->assertDatabaseCount('documents', 1);
});

test('guests cannot open workspace routes', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/buildings')->assertRedirect('/login');
});

test('linked demo users cannot delete their accounts and break foreign keys', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::where('email', 'tenant@example.com')->firstOrFail();
    $this->actingAs($user)->delete('/profile', ['password' => 'password'])
        ->assertSessionHasErrorsIn('userDeletion', 'password');
    expect($user->fresh())->not->toBeNull();
});

test('tenant can download a private document belonging to their lease', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'tenant@example.com')->firstOrFail());
    $document = Document::firstOrFail();
    $this->get(route('documents.download', $document))->assertOk()->assertDownload('lease-information.txt');
    $this->get(route('documents.show', $document))->assertOk()->assertDontSee($document->file_path);
});

test('profile updates cannot alter company role or account status', function () {
    $user = User::factory()->create();
    $user->assignRole('tenant');
    $other = ManagementCompany::factory()->create();
    $this->actingAs($user)->patch('/profile', [
        'name' => 'Updated Name', 'email' => $user->email, 'phone' => '+966555123456',
        'company_id' => $other->id, 'role' => 'super_admin', 'status' => 'inactive',
    ])->assertSessionHasNoErrors();
    $user->refresh();
    expect($user->company_id)->not->toBe($other->id)
        ->and($user->hasRole('tenant'))->toBeTrue()
        ->and($user->status)->toBe('active')
        ->and($user->phone)->toBe('+966555123456');
});
