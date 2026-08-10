<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackupRestoreRequest;
use App\Jobs\RunBackupJob;
use App\Models\BackupSetting;
use App\Support\BackupLink;
use App\Support\StorageManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    // تشغيل يدوي
    public function runNow(Request $request)
    {
        $backupSetting = BackupSetting::firstOrFail();

        // التحقق من صحة المدخلات
        $request->validate([
            'backup_type' => ['nullable', 'in:db,files,both'],
        ]);

        // إذا لم يرسل backup_type، نأخذ من الإعدادات
        $backupType = $request->input('backup_type');

        dispatch_sync(new RunBackupJob('manual', $backupType));

        return response()->json([
            'status' => 'ok',
            'ran_at' => now(),
            'backup_type' => $backupType ?? ($backupSetting->include_files ? 'both' : 'db')
        ]);
    }

    // قائمة النسخ (نعرض .zip فقط)
    public function list(Request $request)
    {
        $backupSetting = BackupSetting::firstOrFail();
        $disk = $backupSetting->disk;
        $prefix = StorageManager::backupPrefix();
        $files = StorageManager::listZipFiles($disk, $prefix);

        $data = array_map(function ($path) use ($disk) {
            $diskFs = Storage::disk($disk);
            return [
                'path' => $path,
                'size' => $diskFs->size($path),
                'lastModified' => $diskFs->lastModified($path),
                'url' => null, // سنوفر عبر tempLink
            ];
        }, $files);

        return $data;
    }

    // حذف نسخة
    public function delete(Request $request)
    {
        $request->validate(['path' => ['required', 'string']]);
        $path = BackupLink::safeBackupPath((string) $request->string('path'));

        // أمان: منع Path Traversal والتأكد من حذف ملفات النسخ الاحتياطي فقط
        if ($path === null) {
            return response()->json(['error' => 'Invalid or unauthorized file path.'], 403);
        }

        $backupSetting = BackupSetting::firstOrFail();
        $deletedOk = Storage::disk($backupSetting->disk)->delete($path);
        return response()->json(['deleted' => (bool)$deletedOk]);
    }
    // حذف نسخة
    // Laravel 12 — نظيف وبسيط، مع تعليقات توضيحية
    public function delete_all()
    {
        // جذر النسخ القياسي Backups/{APP_NAME} من StorageManager (مصدر واحد للاتفاقية)
        $rootPrefix = StorageManager::backupPrefix();

        // اجلب كل الأقراص المستخدمة في الإعدادات (قد يكون عندك أكثر من قرص)
        $disks = \App\Models\BackupSetting::query()
            ->pluck('disk')
            ->unique()
            ->filter()
            ->values();

        $totalDeleted = 0;
        $errors = [];

        foreach ($disks as $disk) {
            $diskFs = \Illuminate\Support\Facades\Storage::disk($disk);
            $prefix = $rootPrefix;

            // لو المجلد غير موجود على هذا القرص، انتقل للي بعده
            if (!$diskFs->exists($prefix)) {
                continue;
            }

            // 1) احذف كل الملفات تحت Backups/{APP_NAME}/...
            $files = $diskFs->allFiles($prefix);
            foreach ($files as $path) {
                try {
                    if ($diskFs->delete($path)) {
                        $totalDeleted++;
                    } else {
                        $errors[] = "{$disk}: failed to delete file {$path}";
                    }
                } catch (\Throwable $e) {
                    $errors[] = "{$disk}: exception deleting {$path} -> " . $e->getMessage();
                }
            }

            // 2) احذف المجلدات (ابدأ بالأعمق لتفادي فشل الحذف)
            $dirs = $diskFs->allDirectories($prefix);
            foreach (array_reverse($dirs) as $dir) {
                try {
                    $diskFs->deleteDirectory($dir);
                } catch (\Throwable $e) {
                    $errors[] = "{$disk}: exception deleting dir {$dir} -> " . $e->getMessage();
                }
            }

            // 3) أخيرًا احذف الجذر Backups/{APP_NAME} إن بقي موجودًا (قد تبقى ملفات مخفية أحيانًا)
            try {
                if ($diskFs->exists($prefix)) {
                    $diskFs->deleteDirectory($prefix);
                }
            } catch (\Throwable $e) {
                $errors[] = "{$disk}: exception deleting root {$prefix} -> " . $e->getMessage();
            }
        }

        // 4) امسح سجلات النسخ (اختياري: لو تريد الاحتفاظ بها لا تمسح)
        try {
            \App\Models\BackupLog::truncate();
        } catch (\Throwable $e) {
            $errors[] = "db: failed to truncate backup_logs -> " . $e->getMessage();
        }

        return response()->json([
            'deleted_files' => $totalDeleted,
            'errors' => $errors,
        ]);
    }
    public function deleteAllByLogs()
    {
        $logs = \App\Models\BackupLog::query()->get();

        $deleted = 0;
        $errors = [];

        foreach ($logs as $log) {
            $disk = $log->storage_disk ?? 'local';
            $fs = \Illuminate\Support\Facades\Storage::disk($disk);
            $paths = (array) ($log->backup_paths ?? []);

            foreach ($paths as $p) {
                try {
                    if ($p && $fs->exists($p) && $fs->delete($p)) {
                        $deleted++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "{$disk}: {$p} -> " . $e->getMessage();
                }
            }
        }

        // ثم امسح السجلات
        \App\Models\BackupLog::truncate();

        return ['deleted_files' => $deleted, 'errors' => $errors];
    }

    // رابط مؤقت (Proxy بسيط موقّع)
    public function tempLink(Request $request)
    {
        $request->validate(['path' => ['required', 'string']]);

        // نفس حارس delete(): لا نوقّع رابطًا إلا لملفات النسخ الاحتياطي
        $path = BackupLink::safeBackupPath((string) $request->string('path'));
        if ($path === null) {
            return response()->json(['error' => 'Invalid or unauthorized file path.'], 403);
        }

        $backupSetting = BackupSetting::firstOrFail();

        // ننشئ URL موقّع إلى مسار تحميل داخلي
        // التوقيع يتم على المسار النسبي ليطابق التحقق في routes/web.php
        $minutes = max(5, (int) $backupSetting->temp_link_expiry);
        $signed = BackupLink::temporaryDownloadUrl(
            $backupSetting->disk,
            $path,
            $minutes
        );

        return ['url' => $signed, 'expires_in_minutes' => $minutes];
    }

    // اختبار إرسال البريد الإلكتروني
    public function testEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'event' => ['nullable', 'in:success,failure'],
        ]);

        $email = $request->input('email');
        $event = $request->input('event', 'success');

        // بيانات تجريبية
        $payload = [
            'event' => 'backup.' . $event,
            'timestamp' => now()->toIso8601String(),
            'type' => 'db+files',
            'paths' => [
                'Backups/laravel/db/mysql/2025-01-28_123456.zip',
                'Backups/laravel/files/public/2025-01-28_123456.zip',
            ],
            'temp_urls' => [
                'Backups/laravel/db/mysql/2025-01-28_123456.zip' => url('/download/test1'),
                'Backups/laravel/files/public/2025-01-28_123456.zip' => url('/download/test2'),
            ],
            'size' => 15728640, // 15 MB
            'message' => $event === 'success'
                ? 'Backup completed successfully.'
                : 'Backup failed due to insufficient disk space.',
            'checksums' => [
                'Backups/laravel/db/mysql/2025-01-28_123456.zip' => 'abc123def456...',
                'Backups/laravel/files/public/2025-01-28_123456.zip' => 'xyz789uvw012...',
            ],
        ];

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(
                new \App\Mail\BackupNotificationMail(
                    payload: $payload,
                    event: $event,
                    attachmentData: [] // بدون مرفقات للتجربة
                )
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Test email sent successfully',
                'sent_to' => $email,
                'event' => $event,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // معاينة البريد الإلكتروني في المتصفح
    public function previewEmail(Request $request)
    {
        $event = $request->input('event', 'success');

        // بيانات تجريبية
        $payload = [
            'event' => 'backup.' . $event,
            'timestamp' => now()->toIso8601String(),
            'type' => 'db+files',
            'paths' => [
                'Backups/laravel/db/mysql/2025-01-28_123456.zip',
                'Backups/laravel/files/public/2025-01-28_123456.zip',
            ],
            'temp_urls' => [
                'Backups/laravel/db/mysql/2025-01-28_123456.zip' => url('/download/test1'),
                'Backups/laravel/files/public/2025-01-28_123456.zip' => url('/download/test2'),
            ],
            'size' => 15728640, // 15 MB
            'message' => $event === 'success'
                ? 'Backup completed successfully.'
                : 'Backup failed due to insufficient disk space.',
            'checksums' => [
                'Backups/laravel/db/mysql/2025-01-28_123456.zip' => 'abc123def456789012345678901234567890',
                'Backups/laravel/files/public/2025-01-28_123456.zip' => 'xyz789uvw012345678901234567890123456',
            ],
        ];

        return view('emails.backup-notification', [
            'payload' => $payload,
            'event' => $event,
            'isSuccess' => $event === 'success',
        ]);
    }

    // جلب سجلات النسخ الاحتياطي (backup logs)
    public function logs(Request $request)
    {
        $perPage = $request->integer('per_page', 15);

        $query = \App\Models\BackupLog::query()
            ->orderBy('created_at', 'desc');

        // فلترة حسب النوع
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $logs = $query->paginate($perPage);

        // استخدام Resource لتنسيق البيانات
        return \App\Http\Resources\BackupLogResource::collection($logs);
    }

    // استعادة
    public function restore(BackupRestoreRequest $request)
    {
        $backupLogId = $request->integer('backup_log_id');
        $restoreDatabase = $request->boolean('restore_database', true);
        $restoreFiles = $request->boolean('restore_files', true);
        $verifyChecksum = $request->boolean('verify_checksum', true);

        // التحقق من وجود النسخة الاحتياطية
        $log = \App\Models\BackupLog::findOrFail($backupLogId);

        if ($log->status !== 'success') {
            return response()->json([
                'error' => 'Cannot restore from a failed backup'
            ], 400);
        }

        // تشغيل عملية الاستعادة
        try {
            dispatch_sync(new \App\Jobs\RestoreBackupJob(
                $backupLogId,
                $restoreDatabase,
                $restoreFiles,
                $verifyChecksum
            ));

            return response()->json([
                'status' => 'completed',
                'message' => 'Backup restored successfully',
                'backup_log_id' => $backupLogId,
                'restored_at' => now()
            ]);
        } catch (\Throwable $e) {
            Log::error('Restore failed', [
                'backup_log_id' => $backupLogId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * اختبار إرسال رسالة Telegram
     * يرسل رسالة اختبار لجميع الـ Chat IDs المسجلة في الإعدادات والأدمنز
     */
    public function testTelegram(Request $request)
    {
        $settings = BackupSetting::firstOrFail();

        // التحقق من تفعيل Telegram
        if (!$settings->telegram_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Telegram notifications are disabled in settings'
            ], 400);
        }

        // التحقق من وجود Bot Token
        if (empty($settings->telegram_bot_token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Telegram bot token is not configured'
            ], 400);
        }

        // جمع Chat IDs من المصدرين (admins + settings)
        $admins = \App\Models\BackupAdmin::where('active', true)->get();

        $adminChatIds = $admins
            ->filter(fn($a) => in_array('telegram', (array)$a->notify_via) && $a->telegram_id)
            ->flatMap(fn($a) => $this->splitByComma($a->telegram_id))
            ->unique()
            ->all();

        $settingsChatIds = !empty($settings->telegram_chat_ids)
            ? collect($this->splitByComma($settings->telegram_chat_ids))->unique()->all()
            : [];

        $chatIds = collect($adminChatIds)
            ->merge($settingsChatIds)
            ->unique()
            ->filter()
            ->all();

        if (empty($chatIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No Telegram chat IDs found. Please add chat IDs in settings or admins.'
            ], 400);
        }

        // إرسال رسالة اختبار
        $token = trim($settings->telegram_bot_token);
        $testMessage = "🧪 Test Message from " . config('app.name', 'Backup System') . "\n\n";
        $testMessage .= "✅ Telegram notifications are working correctly!\n";
        $testMessage .= "📅 Time: " . now()->toDateTimeString() . "\n";
        $testMessage .= "🔔 This is a test notification.";

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($chatIds as $chatId) {
            try {
                $response = \Illuminate\Support\Facades\Http::post(
                    "https://api.telegram.org/bot{$token}/sendMessage",
                    [
                        'chat_id' => $chatId,
                        'text' => $testMessage,
                        'parse_mode' => 'HTML',
                    ]
                );

                if ($response->successful()) {
                    $successCount++;
                    $results[] = [
                        'chat_id' => $chatId,
                        'status' => 'success',
                        'message' => 'Message sent successfully'
                    ];
                } else {
                    $failureCount++;
                    $results[] = [
                        'chat_id' => $chatId,
                        'status' => 'failed',
                        'message' => $response->json()['description'] ?? 'Unknown error'
                    ];
                }
            } catch (\Throwable $e) {
                $failureCount++;
                $results[] = [
                    'chat_id' => $chatId,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'status' => 'completed',
            'summary' => [
                'total' => count($chatIds),
                'success' => $successCount,
                'failed' => $failureCount
            ],
            'results' => $results
        ]);
    }

    /**
     * اختبار إرسال Webhook
     * يرسل payload اختباري لجميع الـ Webhook URLs المسجلة
     */
    public function testWebhook(Request $request)
    {
        $settings = BackupSetting::firstOrFail();

        // التحقق من تفعيل Webhook
        if (!$settings->webhook_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook notifications are disabled in settings'
            ], 400);
        }

        // جمع Webhook URLs من المصدرين (admins + settings)
        $admins = \App\Models\BackupAdmin::where('active', true)->get();

        $adminWebhooks = $admins
            ->filter(fn($a) => in_array('webhook', (array)$a->notify_via) && $a->webhook_url)
            ->flatMap(fn($a) => $this->splitByComma($a->webhook_url))
            ->unique()
            ->all();

        $settingsWebhooks = !empty($settings->webhook_urls)
            ? collect($this->splitByComma($settings->webhook_urls))->unique()->all()
            : [];

        $webhookUrls = collect($adminWebhooks)
            ->merge($settingsWebhooks)
            ->unique()
            ->filter()
            ->all();

        if (empty($webhookUrls)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No webhook URLs found. Please add URLs in settings or admins.'
            ], 400);
        }

        // إعداد الـ Payload الاختباري
        $testPayload = [
            'event' => 'backup.test',
            'timestamp' => now()->toIso8601String(),
            'message' => 'This is a test webhook from ' . config('app.name', 'Backup System'),
            'test' => true,
            'data' => [
                'app_name' => config('app.name', 'Backup System'),
                'environment' => config('app.env', 'production'),
                'test_time' => now()->toDateTimeString(),
            ]
        ];

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($webhookUrls as $url) {
            try {
                $req = \Illuminate\Support\Facades\Http::asJson();

                // إضافة التوقيع إذا كان Secret موجود
                if (!empty($settings->webhook_secret)) {
                    $json = json_encode($testPayload);
                    $signature = hash_hmac('sha256', $json, $settings->webhook_secret);
                    $req = $req->withHeaders(['X-Backup-Signature' => "sha256={$signature}"]);
                }

                $response = $req->post($url, $testPayload);

                if ($response->successful()) {
                    $successCount++;
                    $results[] = [
                        'url' => $url,
                        'status' => 'success',
                        'http_code' => $response->status(),
                        'message' => 'Webhook sent successfully'
                    ];
                } else {
                    $failureCount++;
                    $results[] = [
                        'url' => $url,
                        'status' => 'failed',
                        'http_code' => $response->status(),
                        'message' => 'HTTP ' . $response->status()
                    ];
                }
            } catch (\Throwable $e) {
                $failureCount++;
                $results[] = [
                    'url' => $url,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'status' => 'completed',
            'summary' => [
                'total' => count($webhookUrls),
                'success' => $successCount,
                'failed' => $failureCount
            ],
            'payload_sent' => $testPayload,
            'results' => $results
        ]);
    }

    /**
     * Helper: تقسيم النص بالفاصلة
     */
    private function splitByComma(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->all();
    }
}
