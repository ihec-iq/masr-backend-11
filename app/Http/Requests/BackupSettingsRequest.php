<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class BackupSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * تحويل القيم النصية 'true'/'false' إلى boolean قبل الـ validation
     * هذا يحل مشكلة إرسال الـ Frontend للقيم البوليانية كـ strings
     */
    protected function prepareForValidation(): void
    {
        Log::info('prepareForValidation called', ['input_sample' => [
            'auto_backup_enabled' => $this->input('auto_backup_enabled'),
            'multi_db' => $this->input('multi_db'),
        ]]);

        $booleanFields = [
            'include_files',
            'multi_db',
            'checksum_enabled',
            'notify_enabled',
            'telegram_enabled',
            'email_enabled',
            'webhook_enabled',
            'auto_backup_enabled',
        ];

        $data = [];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                // تحويل 'true'/'false' strings إلى boolean
                if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
                    $data[$field] = true;
                } elseif ($value === 'false' || $value === '0' || $value === 0 || $value === false) {
                    $data[$field] = false;
                }
            }
        }

        // تحويل الحقول الرقمية من string إلى integer
        $integerFields = [
            'max_storage_mb',
            'keep_daily_days',
            'keep_weekly_weeks',
            'keep_monthly_months',
            'keep_yearly_years',
            'temp_link_expiry',
            'stale_hours',
            'auto_backup_interval',
        ];

        foreach ($integerFields as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $data[$field] = (int) $this->input($field);
            }
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            // عام
            'cron' => ['string'],
            'timezone' => ['string'],
            'max_storage_mb' => ['integer','min:100'],

            // النطاق
            'include_files' => ['boolean'],
            'include_paths' => ['array','nullable'],
            'include_paths.*' => ['string'],
            'exclude_paths' => ['array','nullable'],
            'exclude_paths.*' => ['string'],
            'multi_db' => ['boolean'],
            'selected_databases' => ['array','nullable'],
            'selected_databases.*' => ['string'],

            // الاحتفاظ
            'keep_daily_days' => ['integer','min:0'],
            'keep_weekly_weeks' => ['integer','min:0'],
            'keep_monthly_months' => ['integer','min:0'],
            'keep_yearly_years' => ['integer','min:0'],

            // التخزين
            'disk' => ['string'],
            'drive_folder' => ['string'],
            'temp_link_expiry' => ['integer','min:5','max:1440'],

            // التحقق
            'checksum_enabled' => ['boolean'],

            // الإشعارات
            'notify_enabled' => ['boolean'],
            'notify_on' => ['in:success,failure,both'],

            // تفعيل/تعطيل قنوات الإشعار
            'telegram_enabled' => ['boolean'],
            'email_enabled' => ['boolean'],
            'webhook_enabled' => ['boolean'],

            'emails' => ['nullable','string'], // نفصّلها بفارزة في السيرفر
            'telegram_bot_token' => ['nullable','string'],
            'telegram_chat_ids' => ['nullable','string'], // عدة IDs مفصولة بفارزة
            'webhook_urls' => ['nullable','string'], // عدة URLs بفارزة
            'webhook_secret' => ['nullable','string'],
            'stale_hours' => ['integer','min:6','max:168'], // 6 إلى 7 أيام

            // النسخ التلقائي
            'auto_backup_enabled' => ['boolean'],
            'auto_backup_interval' => ['integer','min:1'], // بالدقائق، على الأقل دقيقة واحدة
            'auto_backup_type' => ['in:db,files,both'],
        ];
    }
}
