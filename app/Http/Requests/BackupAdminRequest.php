<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackupAdminRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // Coerce 'active' (which may come as string) into a boolean before validation
        if ($this->has('active')) {
            $value = $this->input('active');
            if (!is_bool($value)) {
            // handles "true"/"false", "1"/"0", 1/0, "on"/"off", "yes"/"no"
            $this->merge(['active' => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);
            }
        }

        return [
            'name' => ['required','string','max:100'],
            // يدعم email واحد أو عدة emails مفصولة بفاصلة
            'email' => ['nullable','string','max:500'],
            // يدعم telegram_id واحد أو عدة IDs مفصولة بفاصلة
            'telegram_id' => ['nullable','string','max:200'],
            // يدعم webhook_url واحد أو عدة URLs مفصولة بفاصلة
            'webhook_url' => ['nullable','string','max:1000'],
            'active' => ['boolean'],
            'notify_via' => ['array'],
            // إضافة webhook كخيار في notify_via
            'notify_via.*' => ['in:telegram,email,webhook'],
        ];
    }
}
