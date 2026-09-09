<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('note', $this->route('maintenanceRequest'));
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:5000']];
    }

    public function attributes(): array
    {
        return ['body' => __('workflow.internal_note')];
    }
}
