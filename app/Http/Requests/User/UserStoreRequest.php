<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
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
            'name'       => ['required', 'string', 'min:2', 'max:255'],
            // user_name صار معرّف دخول في AuthController::login فلا بد أن يكون فريدًا
            'user_name'  => ['required', 'string', 'min:2', 'max:255', 'unique:users,user_name'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // Password::defaults() مضبوطة في AppServiceProvider: 8 أحرف + أحرف كبيرة/صغيرة + أرقام + رموز
            'password'   => ['required', 'string', Password::defaults(), 'confirmed'],
            'any_device' => ['nullable', 'boolean'],
            'active'     => ['nullable', 'boolean'],
            'window_id'  => ['nullable', 'integer'],
            'roles'      => ['nullable', 'array'],
            'roles.*'    => ['integer', 'exists:roles,id'],
        ];
    }
}
