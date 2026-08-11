<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class InputVoucherStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            
            'itemId' => 'integer |exists:items,id',
            //'inputVoucherStateId' => 'integer |exists:input_voucher_states,id',
            'number' => 'string|unique:input_vouchers,number',
            'date' => 'date',
            //'notes' => 'string',
            //'requestedBy' => 'string',
        ];
    }
    public function messages(): array
    {
        return [
            'itemId.integer' => __('validation.integer', ['attribute' => 'item']),
            'itemId.exists' => __('validation.exists', ['attribute' => 'item']),
            'number.unique' => __('validation.unique', ['attribute' => 'voucher number']),
            'date.date' => __('validation.date', ['attribute' => 'date']),
        ];
    }
}
