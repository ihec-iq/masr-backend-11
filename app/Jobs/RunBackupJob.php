<?php

namespace App\Jobs;

use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Support\BackupConfigurator;
use App\Support\StorageManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $triggerType = 'manual',
        public ?string $backupType = null
    ) {}

    public function handle(): void
    {
        $settings = BackupSetting::first();
        if (!$settings) return;

        BackupConfigurator::apply($settings);

        $storageDisk = $settings->disk;
        $currentTime = now()->setTimezone($settings->timezone ?? 'Asia/Baghdad');
        $appName = config('app.name', 'laravel');

        // تحديد نوع النسخ الاحتياطي
        // إذا لم يتم تمرير backupType، نأخذ من الإعدادات
        $includeFiles = $this->determineIncludeFiles($settings);

        $backupLog = BackupLog::create([
            'type'               => $this->triggerType === 'manual' ? 'manual' : 'auto',
            'include_files'      => $includeFiles,
            'databases'          => ($settings->multi_db ? ($settings->selected_databases ?? []) : ['mysql']),
            'files'              => $includeFiles ? [storage_path('app/public')] : [],
            'status'             => 'running',
            'storage_disk'       => $storageDisk,
            'started_at'         => $currentTime,
            'backup_setting_id'  => $settings->id,
        ]);

        $totalSize  = 0;
        $checksums  = [];
        $finalPaths = [];
        $fileSystem = Storage::disk($storageDisk);

        try {
            // ===== (1) نسخ قاعدة البيانات (إلا إذا كان النوع 'files' فقط) =====
            $shouldBackupDatabase = $this->backupType !== 'files';

            if ($shouldBackupDatabase) {
                $databaseList = $settings->multi_db ? ($settings->selected_databases ?? []) : ['mysql'];
                $databaseList = array_values(array_unique(array_filter($databaseList)));

                foreach ($databaseList as $database) {
                $filesBeforeBackup = collect($fileSystem->allFiles())
                    ->filter(fn($file) => str_ends_with(strtolower($file), '.zip'))
                    ->all();

                Config::set('backup.backup.source.databases', [$database]);
                Config::set('backup.backup.source.files', [
                    'include' => [],
                    'exclude' => [],
                    'follow_links' => false,
                    'ignore_unreadable_directories' => false,
                    'relative_path' => base_path(),
                ]);
                Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true, '--no-interaction' => true]);

                $filesAfterBackup = collect($fileSystem->allFiles())
                    ->filter(fn($file) => str_ends_with(strtolower($file), '.zip'))
                    ->all();

                $newFiles = array_diff($filesAfterBackup, $filesBeforeBackup);

                foreach ($newFiles as $originalPath) {
                    if (!$fileSystem->exists($originalPath)) {
                        continue;
                    }

                    $destinationPath = StorageManager::buildPath($appName, 'db', $database, $currentTime);
                    StorageManager::move($storageDisk, $originalPath, $destinationPath);
                    $metadata = StorageManager::meta($storageDisk, $destinationPath);
                    $totalSize += $metadata['size'];
                    $checksums[$destinationPath] = $metadata['checksum'];
                    $finalPaths[] = $destinationPath;
                }
            }
            } // نهاية شرط shouldBackupDatabase

            // ===== (2) ملفات storage/app/public فقط =====
            if ($includeFiles) {

                // طبّق مسار آمن موجود فعليًا
                $publicDir = storage_path('app/public');

                // لو المجلد غير موجود أنشئه (أو تجاوز خطوة الملفات)
                if (!is_dir($publicDir)) {
                    @mkdir($publicDir, 0775, true);
                }

                // تحقق سريع: لو فارغ بالكامل، تجاوز خطوة الملفات بدل ما نرمي استثناء
                $hasAnyFile = false;
                if (is_dir($publicDir)) {
                    try {
                        $iter = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($publicDir, \FilesystemIterator::SKIP_DOTS)
                        );
                        foreach ($iter as $fileInfo) {
                            if ($fileInfo->isFile()) {
                                $hasAnyFile = true;
                                break;
                            }
                        }
                    } catch (\Throwable) {
                        // في حالة حدوث خطأ في القراءة، نفترض أن هناك ملفات ونحاول النسخ
                        $hasAnyFile = true;
                    }
                }

                if (!$hasAnyFile) {
                    // لا توجد ملفات — فقط تجاوز هذه المرحلة بدون فشل
                    \Illuminate\Support\Facades\Log::info('Backup files skipped: No files found in ' . $publicDir);
                } else {
                    $filesBeforeBackup = collect($fileSystem->allFiles())
                        ->filter(fn($file) => str_ends_with(strtolower($file), '.zip'))
                        ->all();

                    // مهم: لا نكتب مصفوفة files بالكامل — نضبط المفاتيح مباشرة
                    Config::set('backup.backup.source.databases', []);                          // حتى ما يدخل DB داخل هذا الأرشيف
                    Config::set('backup.backup.source.files.include', [$publicDir]);
                    Config::set('backup.backup.source.files.exclude', []);
                    Config::set('backup.backup.source.files.follow_links', false);
                    Config::set('backup.backup.source.files.ignore_unreadable_directories', false);

                    // (اختياري وآمن) اضبط relative_path على base_path() بدل null
                    Config::set('backup.backup.source.files.relative_path', base_path());

                    Artisan::call('backup:run', [
                        '--only-files' => true,
                        '--disable-notifications' => true,
                        '--no-interaction' => true,
                    ]);

                    $filesAfterBackup = collect($fileSystem->allFiles())
                        ->filter(fn($file) => str_ends_with(strtolower($file), '.zip'))
                        ->all();

                    $newFiles = array_diff($filesAfterBackup, $filesBeforeBackup);

                    $fileCount = 0;
                    foreach ($newFiles as $originalPath) {
                        if (!$fileSystem->exists($originalPath)) continue;

                        $fileSize = $fileSystem->size($originalPath);

                        // تحذير: لا نحذف الملفات الصغيرة، فقط نسجل تحذير
                        if ($fileSize < 100) { // فقط نتجاهل الملفات الفارغة تمامًا (أقل من 100 بايت)
                            \Illuminate\Support\Facades\Log::warning("Backup file too small, skipping: {$originalPath} (size: {$fileSize} bytes)");
                            $fileSystem->delete($originalPath);
                            continue;
                        }

                        $destinationPath = \App\Support\StorageManager::buildPath($appName, 'files', 'public', $currentTime);
                        if ($fileCount > 0) {
                            $destinationPath = str_replace('.zip', "_part{$fileCount}.zip", $destinationPath);
                        }

                        \App\Support\StorageManager::move($storageDisk, $originalPath, $destinationPath);
                        $meta = \App\Support\StorageManager::meta($storageDisk, $destinationPath);
                        $totalSize += $meta['size'];
                        $checksums[$destinationPath] = $meta['checksum'];
                        $finalPaths[] = $destinationPath;
                        $fileCount++;
                    }
                }
            }


            Artisan::call('backup:clean');

            $backupLog->update([
                'status'       => 'success',
                'backup_paths' => $finalPaths,
                'checksums'    => $checksums,
                'total_size'   => $totalSize,
                'finished_at'  => now(),
                'message'      => 'Backup completed successfully.',
            ]);

            \App\Support\NotificationManager::notify($backupLog);

            $settings->forceFill(['last_run_at' => now()])->save();
        } catch (Throwable $exception) {
            $backupLog->update([
                'status'      => 'failed',
                'finished_at' => now(),
                'message'     => $exception->getMessage(),
            ]);

            \App\Support\NotificationManager::notify($backupLog);

            throw $exception;
        }
    }

    /**
     * تحديد ما إذا كان يجب تضمين الملفات في النسخ الاحتياطي
     * بناءً على البارامتر المُمرّر أو الإعدادات الافتراضية
     */
    private function determineIncludeFiles(BackupSetting $settings): bool
    {
        // إذا تم تمرير backupType، نستخدمه
        if ($this->backupType !== null) {
            return match ($this->backupType) {
                'db' => false,           // قاعدة البيانات فقط
                'files' => true,         // الملفات فقط (سيتم تخطي DB في المنطق)
                'both' => true,          // كلاهما
                default => (bool)$settings->include_files,
            };
        }

        // إذا لم يتم تمرير backupType، نأخذ من الإعدادات
        return (bool)$settings->include_files;
    }
}
