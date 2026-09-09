<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'tenant_id', 'unit_id', 'contract_number', 'start_date', 'end_date', 'monthly_rent', 'security_deposit', 'payment_due_day', 'status', 'contract_file'])]
class LeaseContract extends Model
{
    use BelongsToCompany, HasDocuments, HasFactory;

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'monthly_rent' => 'decimal:2', 'security_deposit' => 'decimal:2'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }
}
