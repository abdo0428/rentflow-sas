<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'code', 'address', 'city', 'district', 'total_floors', 'notes', 'status'])]
class Building extends Model
{
    use BelongsToCompany, HasDocuments, HasFactory;

    public function deletionBlockReason(): ?string
    {
        if ($this->units()->exists()) {
            return 'property.building_has_units';
        }

        return $this->maintenanceRequests()->exists() || $this->announcements()->exists() || $this->documents()->exists()
            ? 'property.related_records' : null;
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }
}
