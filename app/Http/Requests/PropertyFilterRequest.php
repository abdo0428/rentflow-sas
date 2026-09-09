<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $building = Rule::exists('buildings', 'id');
        if (! $this->user()->hasRole('super_admin')) {
            $building->where('company_id', $this->user()->company_id);
        }

        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in($this->routeIs('buildings.index') ? ['active', 'inactive'] : ['vacant', 'occupied', 'maintenance', 'reserved'])],
            'city' => ['nullable', 'string', 'max:120'],
            'building_id' => ['nullable', 'integer', $building],
            'type' => ['nullable', Rule::in(['apartment', 'office', 'shop', 'storage'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'units_page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'leases_page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'payments_page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'maintenance_page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'documents_page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return __('property.attributes');
    }
}
