<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'building_id', 'unit_id', 'tenant_id', 'assigned_to', 'title', 'description', 'priority', 'status', 'preferred_date', 'completed_at'])]
class MaintenanceRequest extends Model
{
    use BelongsToCompany, HasDocuments, HasFactory;

    public function activities(): HasMany
    {
        return $this->hasMany(MaintenanceActivity::class);
    }

    protected function casts(): array
    {
        return ['preferred_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
