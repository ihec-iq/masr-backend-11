<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class ItemStoreRequest extends FormRequest
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
            'name' => 'string|unique:items,name',
            'code' => 'string |unique:items,code| nullable',
            'description' => 'string | nullable',
            'Category.id' => 'integer | exists:item_categories,id',
            'measuringUnit' => 'string | nullable',
        ];
    }

    public function messages()
    {
        return [
            'description.string' => 'description Must be String',
        ];
    }
}
