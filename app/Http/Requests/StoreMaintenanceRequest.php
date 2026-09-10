<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MaintenanceRequest::class);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [$this->user()->hasRole('tenant') ? 'exclude' : 'required', 'integer'],
            'unit_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'preferred_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ];
    }

    public function attributes(): array
    {
        return [...__('workflow.attributes'), 'photo' => __('portal.photo')];
    }
}
