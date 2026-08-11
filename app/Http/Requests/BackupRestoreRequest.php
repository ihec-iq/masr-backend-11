<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackupRestoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'scope' => ['required','in:db,files,both'],
            // مصدر النسخة: رفع يدوي أم من القرص
            'source' => ['required','in:upload,disk'],

            // عند source=upload
            'upload' => ['nullable','file'],

            // عند source=disk
            'path_db' => ['nullable','string'],   // مسار ملف DB zip
            'path_files' => ['nullable','string'],// مسار ملفات zip

            // خيارات DB
            'database_name' => ['nullable','string'], // يمكن تجاهله إذا داخل الأرشيف الاسم واضح
            'confirm_text' => ['nullable','string'],  // تأكيد نصّي قبل التنفيذ
        ];
    }
}
