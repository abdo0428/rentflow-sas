<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkPaymentPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payment'));
    }

    public function rules(): array
    {
        return [
            'paid_at' => ['required', 'date_format:Y-m-d\\TH:i', 'before_or_equal:now'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'card'])],
        ];
    }

    public function attributes(): array
    {
        return __('workflow.attributes');
    }
}
