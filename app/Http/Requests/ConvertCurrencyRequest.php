<?php

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;

class ConvertCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $codes = Currency::availableCodes();
        $list = $codes === [] ? ',' : implode(',', $codes);

        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'string', 'size:3', 'in:'.$list],
            'to' => ['required', 'string', 'size:3', 'in:'.$list],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => __('currency.validation.amount_required'),
            'from' => __('currency.validation.from_required'),
            'to' => __('currency.validation.to_required'),
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => __('currency.validation.amount_required'),
            'amount.numeric' => __('currency.validation.amount_numeric'),
            'amount.min' => __('currency.validation.amount_min'),
            'from.required' => __('currency.validation.from_required'),
            'from.in' => __('currency.validation.from_in'),
            'to.required' => __('currency.validation.to_required'),
            'to.in' => __('currency.validation.to_in'),
        ];
    }
}
