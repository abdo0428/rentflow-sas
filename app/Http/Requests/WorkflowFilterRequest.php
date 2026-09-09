<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasActiveWorkspace();
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'expired', 'terminated', 'pending', 'paid', 'overdue', 'cancelled', 'new', 'under_review', 'assigned', 'in_progress', 'completed', 'rejected'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'unit_id' => ['nullable', 'integer', 'min:1'],
            'building_id' => ['nullable', 'integer', 'min:1'],
            'assigned_to' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('date_from'), 'after_or_equal:date_from')],
        ];
    }

    public function attributes(): array
    {
        return __('workflow.attributes');
    }
}
