<?php

namespace App\Http\Requests;

use App\Models\LeaseContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLeaseContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('lease') ? $this->user()->can('update', $this->route('lease')) : $this->user()->can('create', LeaseContract::class);
    }

    public function rules(): array
    {
        $companyId = $this->route('lease')?->company_id ?? $this->user()->company_id;
        $exists = function (string $table) use ($companyId) {
            $rule = Rule::exists($table, 'id');

            return $companyId ? $rule->where('company_id', $companyId) : $rule;
        };

        return [
            'tenant_id' => ['required', 'integer', $exists('tenants')],
            'unit_id' => ['required', 'integer', $exists('units')],
            'contract_number' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
            'security_deposit' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'payment_due_day' => ['required', 'integer', 'between:1,31'],
            'status' => ['required', Rule::in($this->route('lease') ? ['draft', 'active', 'expired', 'terminated'] : ['draft', 'active'])],
        ];
    }

    public function attributes(): array
    {
        return __('workflow.attributes');
    }
}
