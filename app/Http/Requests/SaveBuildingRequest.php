<?php

namespace App\Http\Requests;

use App\Models\Building;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('building');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', Building::class);
    }

    public function companyId(): ?int
    {
        return $this->route('building')?->company_id
            ?? ($this->user()->hasRole('super_admin') ? ($this->integer('company_id') ?: null) : $this->user()->company_id);
    }

    public function rules(): array
    {
        $rules = ['name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', Rule::unique('buildings')->where('company_id', $this->companyId())->ignore($this->route('building'))],
            'address' => ['required', 'string', 'max:2000'], 'city' => ['required', 'string', 'max:120'],
            'district' => ['required', 'string', 'max:120'], 'total_floors' => ['required', 'integer', 'min:0', 'max:200'],
            'notes' => ['nullable', 'string', 'max:5000'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
        if (! $this->route('building') && $this->user()->hasRole('super_admin')) {
            $rules['company_id'] = ['required', 'integer', Rule::exists('management_companies', 'id')->where('status', 'active')];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return __('property.attributes');
    }
}
