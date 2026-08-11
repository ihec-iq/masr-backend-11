<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StorageManager
{
    public static function listZipFiles(string $disk, ?string $prefix = null): array
    {
        $fs = Storage::disk($disk);
        $files = $prefix ? $fs->allFiles($prefix) : $fs->allFiles();
        return array_values(array_filter($files, fn ($f) => str_ends_with(strtolower($f), '.zip')));
    }

    public static function move(string $disk, string $from, string $to): bool
    {
        $fs = Storage::disk($disk);
        $dir = trim(str_replace(basename($to), '', $to), '/');
        if ($dir && !$fs->exists($dir)) $fs->makeDirectory($dir);
        return $fs->move($from, $to);
    }

    public static function meta(string $disk, string $path): array
    {
        $fs = Storage::disk($disk);
        $size = $fs->size($path);
        $ctx = hash_init('sha256');
        $stream = $fs->readStream($path);
        if ($stream === false) throw new \RuntimeException("Cannot read stream for checksum: {$path}");
        while (!feof($stream)) {
            $buf = fread($stream, 1024 * 1024);
            if ($buf === false) break;
            hash_update($ctx, $buf);
        }
        fclose($stream);
        return ['size' => $size, 'checksum' => 'sha256:' . hash_final($ctx)];
    }

    /**
     * Backups/{APP}/{TYPE}/{KEY}/backup_{type}_{key}_{YYYY-MM-DD_HH-mm}.zip
     * TYPE: 'db' | 'files'
     * KEY : db name OR 'public'
     */
    public static function buildPath(string $appName, string $type, string $key, \DateTimeInterface $when): string
    {
        $stamp = $when->format('Y-m-d_H-i');
        $safeKey = preg_replace('/[^a-z0-9\-_]+/i', '-', $key);
        return static::backupPrefix($appName) . "/{$type}/{$safeKey}/backup_{$type}_{$safeKey}_{$stamp}.zip";
    }

    /**
     * جذر النسخ الاحتياطي لهذا التطبيق: Backups/{APP}
     *
     * المصدر الوحيد لهذه الاتفاقية — يستخدمه buildPath و BackupController::list
     * و delete_all وحارس BackupLink::safeBackupPath، حتى لا يسمح الحارس بمسارات
     * تطبيق آخر يشارك نفس القرص.
     */
    public static function backupPrefix(?string $appName = null): string
    {
        $appName = $appName ?? (string) config('app.name', 'laravel');

        return 'Backups/' . preg_replace('/[^a-z0-9\-_]+/i', '-', $appName);
    }
}
