<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'user_id', 'full_name', 'email', 'phone', 'national_id', 'address', 'emergency_contact_name', 'emergency_contact_phone', 'notes'])]
class Tenant extends Model
{
    use BelongsToCompany, HasDocuments, HasFactory;

    public function deletionBlockReason(): ?string
    {
        if ($this->leases()->where('status', 'active')->exists()) {
            return 'property.tenant_active_lease';
        }

        return $this->leases()->exists() || $this->payments()->exists() || $this->maintenanceRequests()->exists() || $this->documents()->exists()
            ? 'property.related_records' : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(LeaseContract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }
}
