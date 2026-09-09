<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'building_id', 'unit_number', 'floor', 'type', 'bedrooms', 'bathrooms', 'area', 'rent_amount', 'status'])]
class Unit extends Model
{
    use BelongsToCompany, HasDocuments, HasFactory;

    public function deletionBlockReason(): ?string
    {
        return $this->leases()->exists() || $this->payments()->exists() || $this->maintenanceRequests()->exists() || $this->documents()->exists()
            ? 'property.related_records' : null;
    }

    protected function casts(): array
    {
        return ['area' => 'decimal:2', 'rent_amount' => 'decimal:2'];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
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
