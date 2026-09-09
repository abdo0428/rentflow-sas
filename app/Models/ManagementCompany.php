<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'email', 'phone', 'address', 'logo', 'status', 'trial_ends_at'])]
class ManagementCompany extends Model
{
    use BelongsToCompany, HasDocuments, HasFactory;

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'company_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'company_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'company_id');
    }

    public function leases(): HasMany
    {
        return $this->hasMany(LeaseContract::class, 'company_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class, 'company_id');
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'company_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'company_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'company_id');
    }
}
