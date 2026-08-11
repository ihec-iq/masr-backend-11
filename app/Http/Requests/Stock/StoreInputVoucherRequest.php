<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreInputVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:255', 'unique:input_vouchers,number'],
            'date' => ['required', 'date'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'stock_id' => ['required', 'integer', 'exists:stocks,id'],
            'input_voucher_state_id' => ['required', 'integer', 'exists:input_voucher_states,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            
            // Voucher Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'number' => 'رقم السند',
            'date' => 'التاريخ',
            'employee_id' => 'الموظف',
            'stock_id' => 'المخزن',
            'input_voucher_state_id' => 'حالة السند',
            'notes' => 'ملاحظات',
            'items' => 'المواد',
            'items.*.item_id' => 'المادة',
            'items.*.quantity' => 'الكمية',
            'items.*.price' => 'السعر',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'number.unique' => 'رقم السند موجود مسبقاً',
            'items.required' => 'يجب إضافة مادة واحدة على الأقل',
            'items.min' => 'يجب إضافة مادة واحدة على الأقل',
            'items.*.item_id.exists' => 'المادة المحددة غير موجودة',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر',
        ];
    }
}
