<?php

namespace App\Http\Requests;

use App\Services\MaintenanceWorkflowService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenanceRequest'));
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(array_keys(MaintenanceWorkflowService::TRANSITIONS))]];
    }

    public function attributes(): array
    {
        return __('workflow.attributes');
    }
}
