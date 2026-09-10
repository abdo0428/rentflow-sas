<?php

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaseContractController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PdfDocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentPaymentController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantPortalController;
use App\Http\Controllers\UnitController;
use App\Support\Navigation;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
Route::post('/locale', LocaleController::class)->middleware('throttle:30,1')->name('locale.update');

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::prefix('tenant')->name('tenant.')->middleware('can:tenantPortal')->group(function () {
        Route::get('/unit', [TenantPortalController::class, 'unit'])->middleware('permission:units.view')->name('unit');
        Route::get('/lease', [TenantPortalController::class, 'lease'])->middleware('permission:leases.view')->name('lease');
        Route::get('/announcements', [TenantPortalController::class, 'announcements'])->middleware('permission:announcements.view')->name('announcements');
        Route::get('/documents', [TenantPortalController::class, 'documents'])->middleware('permission:documents.view')->name('documents');
    });
    Route::get('/leases/{lease}/pdf', [PdfDocumentController::class, 'contract'])->middleware(['permission:leases.view', 'throttle:30,1'])->name('leases.pdf');
    Route::get('/payments/{payment}/receipt', [PdfDocumentController::class, 'receipt'])->middleware(['permission:payments.view', 'throttle:30,1'])->name('payments.receipt');
    foreach (['buildings' => BuildingController::class, 'units' => UnitController::class, 'tenants' => TenantController::class, 'leases' => LeaseContractController::class] as $resource => $controller) {
        Route::resource($resource, $controller)
            ->middlewareFor(['index', 'show'], 'permission:'.$resource.'.view')
            ->middlewareFor(['create', 'store'], 'permission:'.$resource.'.create')
            ->middlewareFor(['edit', 'update'], 'permission:'.$resource.'.update')
            ->middlewareFor('destroy', 'permission:'.$resource.'.delete');
    }
    foreach (Navigation::MODULES as $module => $definition) {
        if (in_array($module, ['buildings', 'units', 'tenants', 'leases', 'payments', 'maintenance'], true)) {
            continue;
        }
        Route::get('/'.$module, [ModuleController::class, 'index'])
            ->defaults('module', $module)->middleware('permission:'.$module.'.view')->name($module.'.index');
        Route::get('/'.$module.'/{record}', [ModuleController::class, 'show'])
            ->whereNumber('record')->defaults('module', $module)->middleware('permission:'.$module.'.view')->name($module.'.show');
    }
    Route::get('/payments', [RentPaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments.index');
    Route::get('/payments/overdue', [RentPaymentController::class, 'overdue'])->middleware('permission:payments.view')->name('payments.overdue');
    Route::get('/payments/{payment}', [RentPaymentController::class, 'show'])->middleware('permission:payments.view')->name('payments.show');
    Route::patch('/payments/{payment}/paid', [RentPaymentController::class, 'paid'])->middleware('permission:payments.update')->name('payments.paid');
    Route::get('/maintenance', [MaintenanceRequestController::class, 'index'])->middleware('permission:maintenance.view')->name('maintenance.index');
    Route::get('/maintenance/create', [MaintenanceRequestController::class, 'create'])->middleware('permission:maintenance.create')->name('maintenance.create');
    Route::post('/maintenance', [MaintenanceRequestController::class, 'store'])->middleware('permission:maintenance.create')->name('maintenance.store');
    Route::get('/maintenance/{maintenanceRequest}', [MaintenanceRequestController::class, 'show'])->middleware('permission:maintenance.view')->name('maintenance.show');
    Route::get('/maintenance/{maintenanceRequest}/photo', [MaintenanceRequestController::class, 'photo'])->middleware('permission:maintenance.view')->name('maintenance.photo');
    Route::patch('/maintenance/{maintenanceRequest}/assign', [MaintenanceRequestController::class, 'assign'])->middleware('permission:maintenance.assign')->name('maintenance.assign');
    Route::patch('/maintenance/{maintenanceRequest}/status', [MaintenanceRequestController::class, 'transition'])->middleware('permission:maintenance.update')->name('maintenance.transition');
    Route::post('/maintenance/{maintenanceRequest}/notes', [MaintenanceRequestController::class, 'note'])->middleware('permission:maintenance.update')->name('maintenance.note');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('notifications.read');
    Route::get('/documents/{document}/download', [ModuleController::class, 'download'])
        ->middleware('permission:documents.view')->name('documents.download');
    Route::get('/reports', [ModuleController::class, 'reports'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/settings', [ModuleController::class, 'settings'])->middleware('permission:settings.view')->name('settings.index');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
