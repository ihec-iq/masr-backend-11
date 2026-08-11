<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class BackupLink
{
    /**
     * ينشئ رابط تنزيل موقّت (Temporary Signed URL) لنسخة احتياطية.
     *
     * مهم: التوقيع يُحسب على المسار النسبي (absolute: false) لأن
     * routes/web.php يتحقق منه بـ URL::hasValidSignature($r, false, ...).
     * الطرفان يجب أن يستخدما نفس القيمة وإلا فشل كل رابط بـ 403.
     * نضيف APP_URL يدويًا بعد التوقيع ليبقى الرابط مطلقًا وقابلًا للنقر في
     * البريد/Telegram، دون أن يدخل الدومين في حساب الـ HMAC (وهذا ما يجعله
     * يعمل خلف البروكسي ومع اختلاف الدومين في الإنتاج).
     */
    public static function temporaryDownloadUrl(string $disk, string $path, int $minutes): string
    {
        $relative = URL::temporarySignedRoute(
            'backup.download',
            now()->addMinutes($minutes),
            ['disk' => $disk, 'p' => static::encodePath($path)],
            absolute: false
        );

        return rtrim((string) config('app.url'), '/') . $relative;
    }

    /**
     * التحقق الموحّد من مسارات النسخ الاحتياطي.
     *
     * يمنع Path Traversal ويحصر العمليات بملفات .zip تحت جذر نسخ هذا التطبيق فقط.
     * يُستخدم من delete() و tempLink() ومسار التنزيل في routes/web.php حتى لا
     * يبقى التحقق في مكان واحد بينما تُترك بقية المداخل مفتوحة.
     *
     * @return string|null المسار بعد التنظيف، أو null إذا كان غير مسموح.
     */
    public static function safeBackupPath(string $path): ?string
    {
        $path = ltrim($path, "/\\");

        // الجذر يأتي من StorageManager وليس 'Backups/' المجرّد، وإلا سمح الحارس
        // بمسارات تطبيق آخر يشارك نفس القرص (Backups/other-app/...).
        $prefix = StorageManager::backupPrefix() . '/';

        if (
            $path === ''
            || str_contains($path, '..')
            || ! str_starts_with($path, $prefix)
            || ! str_ends_with(strtolower($path), '.zip')
        ) {
            return null;
        }

        return $path;
    }

    /**
     * ترميز المسار Base64 بصيغة آمنة للـ URL.
     */
    public static function encodePath(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }
}
