<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * ملاحظة: المصادقة نفسها تتم في AuthController::login (بحث عن email أو user_name
     * ثم Hash::check)، والحد من المحاولات يأتي من middleware('throttle:auth') على
     * المسار. لذلك لا يحتوي هذا الصنف على authenticate()/throttleKey() — وجودها
     * سابقًا كان كودًا ميتًا يوحي بأن المصادقة تحدث هنا.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'email.email' => trans('auth.email'),
        ];
    }
}
