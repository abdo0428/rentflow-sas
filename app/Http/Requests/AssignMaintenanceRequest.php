<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('maintenanceRequest'));
    }

    public function rules(): array
    {
        return ['assigned_to' => ['required', 'integer']];
    }

    public function attributes(): array
    {
        return __('workflow.attributes');
    }
}
