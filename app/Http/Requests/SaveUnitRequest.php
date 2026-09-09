<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('unit');

        return $unit ? $this->user()->can('update', $unit) : $this->user()->can('create', Unit::class);
    }

    public function rules(): array
    {
        $companyId = $this->route('unit')?->company_id ?? $this->user()->company_id;
        $buildingRule = Rule::exists('buildings', 'id');
        if (! $this->user()->hasRole('super_admin') || $this->route('unit')) {
            $buildingRule->where('company_id', $companyId);
        }

        return [
            'building_id' => ['required', 'integer', $buildingRule],
            'unit_number' => ['required', 'string', 'max:64', Rule::unique('units')->where('building_id', $this->integer('building_id'))->ignore($this->route('unit'))],
            'floor' => ['required', 'integer', 'min:-20', 'max:200'],
            'type' => ['required', Rule::in(['apartment', 'office', 'shop', 'storage'])],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'area' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'rent_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'status' => ['required', Rule::in(['vacant', 'occupied', 'maintenance', 'reserved'])],
        ];
    }

    public function attributes(): array
    {
        return __('property.attributes');
    }
}
