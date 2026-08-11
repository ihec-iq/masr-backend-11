<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RestoreService
{
    /**
     * استعادة قاعدة البيانات من ZIP يحتوي SQL (Skeleton).
     */
    public static function restoreDatabase(string $disk, string $zipPath, ?string $dbName = null): void
    {
        // 1) تحقق الملف
        $fs = Storage::disk($disk);
        if (!$fs->exists($zipPath)) {
            throw new \RuntimeException("Backup file not found: {$zipPath}");
        }

        // 2) Maintenance Mode
        Artisan::call('down', ['--render' => 'errors::503']);

        try {
            // 3) فك الضغط مؤقتًا (حسب بيئتك، يفضل إلى storage/app/tmp)
            $tmp = storage_path('app/tmp_restore_'.uniqid());
            if (!is_dir($tmp)) mkdir($tmp, 0775, true);

            $zipFile = $fs->path($zipPath);
            $zip = new \ZipArchive();
            if ($zip->open($zipFile) === true) {
                $zip->extractTo($tmp);
                $zip->close();
            } else {
                throw new \RuntimeException('Could not open backup zip');
            }

            // 4) تحديد ملف SQL (نأخذ أول .sql)
            $sql = collect(scandir($tmp))
                ->filter(fn($f) => str_ends_with(strtolower($f), '.sql'))
                ->map(fn($f) => $tmp.DIRECTORY_SEPARATOR.$f)
                ->first();

            if (!$sql) throw new \RuntimeException('No SQL file found in archive');

            // 5) إعادة تسمية القاعدة الحالية وإنشاء جديدة باسم الأصلي
            $current = $dbName ?: config('database.connections.mysql.database');
            $renamed = $current.'_old_'.now()->format('Ymd_His');

            DB::statement("CREATE DATABASE IF NOT EXISTS `{$renamed}`");
            DB::statement("RENAME TABLE `{$current}`.* TO `{$renamed}`.*"); // ملاحظة: هذه لا تعمل مباشرةً بين قواعد؛ ستحتاج نهجًا أدق حسب مزودك
            // في بيئات MySQL قياسية: الأفضل إسقاط/إنشاء القاعدة واستيراد sql بدل rename tables عبر قواعد مختلفة.

            // 6) استيراد SQL (استخدم mysql CLI الأفضل للأداء)
            // exec("mysql -uUSER -pPASS {$current} < {$sql}");

        } finally {
            // 7) Up
            Artisan::call('up');
        }
    }
}
