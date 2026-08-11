<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutputVoucherRequest extends FormRequest
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
            'number' => ['required', 'string', 'max:255', 'unique:output_vouchers,number'],
            'date' => ['required', 'date'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'stock_id' => ['required', 'integer', 'exists:stocks,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            
            // Voucher Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
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
            'number' => 'رقم سند الصرف',
            'date' => 'التاريخ',
            'employee_id' => 'الموظف المستلم',
            'stock_id' => 'المخزن',
            'section_id' => 'القسم',
            'notes' => 'ملاحظات',
            'items' => 'المواد',
            'items.*.item_id' => 'المادة',
            'items.*.quantity' => 'الكمية',
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
            'number.unique' => 'رقم سند الصرف موجود مسبقاً',
            'items.required' => 'يجب إضافة مادة واحدة على الأقل',
            'items.min' => 'يجب إضافة مادة واحدة على الأقل',
        ];
    }
}
