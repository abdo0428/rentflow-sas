<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('tenant');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', Tenant::class);
    }

    public function companyId(): ?int
    {
        return $this->route('tenant')?->company_id
            ?? ($this->user()->hasRole('super_admin') ? ($this->integer('company_id') ?: null) : $this->user()->company_id);
    }

    public function rules(): array
    {
        $rules = ['full_name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'], 'national_id' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:2000'], 'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'], 'notes' => ['nullable', 'string', 'max:5000']];
        if (! $this->route('tenant') && $this->user()->hasRole('super_admin')) {
            $rules['company_id'] = ['required', 'integer', Rule::exists('management_companies', 'id')->where('status', 'active')];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return __('property.attributes');
    }
}
