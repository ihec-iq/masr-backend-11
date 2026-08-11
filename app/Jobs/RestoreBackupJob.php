<?php

namespace App\Jobs;

use App\Models\BackupLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $backupLogId,
        public bool $restoreDatabase = true,
        public bool $restoreFiles = true,
        public bool $verifyChecksum = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = BackupLog::findOrFail($this->backupLogId);

        if ($log->status !== 'success') {
            throw new \Exception('Cannot restore from a failed backup');
        }

        $disk = $log->storage_disk;
        $paths = (array)$log->backup_paths;
        $checksums = (array)($log->checksums ?? []);

        Log::info('Starting restore process', [
            'backup_log_id' => $this->backupLogId,
            'restore_db' => $this->restoreDatabase,
            'restore_files' => $this->restoreFiles,
        ]);

        // التحقق من الـ checksums قبل البدء
        if ($this->verifyChecksum) {
            foreach ($paths as $path) {
                if (isset($checksums[$path])) {
                    $this->verifyFileChecksum($disk, $path, $checksums[$path]);
                }
            }
        }

        // استرجاع قاعدة البيانات
        if ($this->restoreDatabase) {
            $dbPaths = array_filter($paths, fn($p) => str_contains($p, '/db/'));
            foreach ($dbPaths as $dbPath) {
                $this->restoreDatabase($disk, $dbPath);
            }
        }

        // استرجاع الملفات
        if ($this->restoreFiles) {
            $filePaths = array_filter($paths, fn($p) => str_contains($p, '/files/'));
            foreach ($filePaths as $filePath) {
                $this->restoreFiles($disk, $filePath);
            }
        }

        Log::info('Restore process completed successfully', [
            'backup_log_id' => $this->backupLogId,
        ]);
    }

    /**
     * التحقق من checksum الملف
     */
    protected function verifyFileChecksum(string $disk, string $path, string $expectedChecksum): void
    {
        $fs = Storage::disk($disk);

        if (!$fs->exists($path)) {
            throw new \Exception("Backup file not found: {$path}");
        }

        $content = $fs->get($path);
        $actualChecksum = 'sha256:' . hash('sha256', $content);

        if ($actualChecksum !== $expectedChecksum) {
            throw new \Exception("Checksum mismatch for {$path}. Expected: {$expectedChecksum}, Got: {$actualChecksum}");
        }

        Log::info("Checksum verified for {$path}");
    }

    /**
     * استرجاع قاعدة البيانات من ملف zip
     */
    protected function restoreDatabase(string $disk, string $path): void
    {
        Log::info("Restoring database from {$path}");

        $fs = Storage::disk($disk);
        $tempDir = storage_path('app/temp/restore_' . uniqid());
        File::ensureDirectoryExists($tempDir);

        try {
            // تحميل الملف المضغوط
            $zipPath = $tempDir . '/' . basename($path);
            File::put($zipPath, $fs->get($path));

            // فك الضغط
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception("Failed to open zip file: {$path}");
            }

            $zip->extractTo($tempDir);
            $zip->close();

            // البحث عن ملف SQL (في المجلد الرئيسي والمجلدات الفرعية)
            $sqlFiles = File::allFiles($tempDir);
            $sqlFiles = array_filter($sqlFiles, fn($file) => $file->getExtension() === 'sql');

            if (empty($sqlFiles)) {
                throw new \Exception("No SQL file found in backup: {$path}");
            }

            $sqlFile = reset($sqlFiles)->getPathname();
            $sqlContent = File::get($sqlFile);

            // تنفيذ SQL
            // ملاحظة: هذه طريقة بسيطة، قد تحتاج تحسين للملفات الكبيرة
            DB::unprepared($sqlContent);

            Log::info("Database restored successfully from {$path}");

        } finally {
            // تنظيف الملفات المؤقتة
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * استرجاع الملفات من ملف zip
     */
    protected function restoreFiles(string $disk, string $path): void
    {
        Log::info("Restoring files from {$path}");

        $fs = Storage::disk($disk);
        $tempDir = storage_path('app/temp/restore_' . uniqid());
        File::ensureDirectoryExists($tempDir);

        try {
            // تحميل الملف المضغوط
            $zipPath = $tempDir . '/' . basename($path);
            File::put($zipPath, $fs->get($path));

            // فك الضغط
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception("Failed to open zip file: {$path}");
            }

            // فك الضغط إلى المجلد المؤقت
            $extractPath = $tempDir . '/extracted';
            $zip->extractTo($extractPath);
            $zip->close();

            // نسخ الملفات إلى storage/app/public
            $targetPath = storage_path('app/public');

            // البحث عن مجلد public داخل الأرشيف
            $publicDir = $extractPath;
            if (File::exists($extractPath . '/public')) {
                $publicDir = $extractPath . '/public';
            }

            // نسخ جميع الملفات
            if (File::isDirectory($publicDir)) {
                File::copyDirectory($publicDir, $targetPath);
                Log::info("Files restored successfully from {$path}");
            } else {
                throw new \Exception("Public directory not found in backup");
            }

        } finally {
            // تنظيف الملفات المؤقتة
            File::deleteDirectory($tempDir);
        }
    }
}
