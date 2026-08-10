<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateMyPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * العملاء القدامى يرسلون RePassword بدل password_confirmation،
     * فنقبل الاثنين حتى لا ينكسر تغيير كلمة المرور عليهم.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('password_confirmation') && $this->filled('RePassword')) {
            $this->merge(['password_confirmation' => $this->input('RePassword')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            // Password::defaults() مضبوطة في AppServiceProvider: 8 أحرف + أحرف كبيرة/صغيرة + أرقام + رموز
            'password'         => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
