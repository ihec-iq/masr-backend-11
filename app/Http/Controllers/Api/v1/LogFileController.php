<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class LogFileController extends  Controller
{
    /** المسار الفعلي لملف اللوج */
    private function logPath(): string
    {
        // laravel.log الافتراضي
        return storage_path('logs/laravel.log');
    }

    /** معلومات أساسية عن الملف (الحجم وتاريخ آخر تعديل) */
    public function meta(Request $request)
    {
        $path = $this->logPath();
        if (!file_exists($path)) {
            return response()->json([
                'exists' => false,
                'size' => 0,
                'updated_at' => null,
            ]);
        }

        return response()->json([
            'exists'     => true,
            'size'       => filesize($path),
            'updated_at' => date('c', filemtime($path)),
        ]);
    }

    /** إرجاع آخر N أسطر (عرض سريع داخل الواجهة) */
    public function tail(Request $request)
    {
        $path = $this->logPath();
        if (!file_exists($path)) {
            return response()->json(['lines' => []]);
        }

        $linesRequested = (int) $request->query('lines', 500);
        $linesRequested = max(1, min($linesRequested, 5000)); // سقف أمان

        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return response()->json(['lines' => []]);
        }

        // حجم الملف وقراءة كتل من النهاية
        $fileSize  = filesize($path);
        $chunkSize = 4096;

        // إن كان الملف صغير جدًا، نقرأه مرة واحدة وننهي
        if ($fileSize === 0) {
            fclose($fh);
            return response()->json(['lines' => []]);
        }

        $buffer    = '';
        $remaining = $fileSize;

        // نقرأ من الخلف للأمام على كتل دون استعمال إزاحات سالبة
        while ($remaining > 0) {
            $readSize = ($remaining >= $chunkSize) ? $chunkSize : $remaining;
            $offset   = $remaining - $readSize; // الموضع الذي سنقف عليه للقراءة

            // تموضع دقيق ثم قراءة
            fseek($fh, $offset, SEEK_SET);
            $chunk   = fread($fh, $readSize);
            $buffer  = $chunk . $buffer; // نبني البوفر من الخلف للأمام
            $remaining -= $readSize;

            // نتحقق إن كان وصلنا لعدد الأسطر المطلوب
            // نعدّ \n (سيعمل أيضًا مع CRLF لأن السطر ينتهي بـ \r\n -> يحوي \n)
            if (substr_count($buffer, "\n") >= $linesRequested) {
                break;
            }
        }

        fclose($fh);

        // تقسيم آمن للأسطر (يدعم \r\n و \n)
        $allLines = preg_split("/\r\n|\n|\r/", $buffer);
        // آخر N أسطر
        $tail = array_slice($allLines, -$linesRequested);

        return response()->json(['lines' => $tail]);
    }


    /** تنزيل الملف (stream) — المتصفّح سيتعامل معه كنص */
    public function download(Request $request): StreamedResponse
    {
        // @set_time_limit(0);
        // @ini_set('output_buffering', 'off');
        // @ini_set('zlib.output_compression', '0');
        $path = $this->logPath();

        if (!file_exists($path)) {
            abort(404, 'Log file not found');
        }

        return response()->streamDownload(function () use ($path) {
            $fp = fopen($path, 'rb');
            while (!feof($fp)) {
                echo fread($fp, 8192);
                flush();
            }
            fclose($fp);
        }, 'laravel.log', [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /** رفع/استبدال ملف اللوج (مثلاً من بيئة أخرى) */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/plain', 'max:51200'], // 50MB
        ]);

        $path = $this->logPath();

        // احتياط: خزن نسخة قديمة
        if (file_exists($path)) {
            @copy($path, $path . '.bak.' . date('Ymd_His'));
        }

        // استبدال بالمحتوى المرفوع
        $uploaded = $request->file('file')->getRealPath();
        @copy($uploaded, $path);

        return response()->json(['message' => 'Log file uploaded successfully.']);
    }

    /** حذف/تفريغ الملف (إنشاء ملف فارغ بدلاً من إزالة المسار) */
    public function destroy(Request $request)
    {
        $path = $this->logPath();

        if (File::exists($path)) {
            File::put($path, ''); // هذا يُفرغ الملف
            return response()->json(['message' => 'Log file cleared.']);
        }

        return response()->json(['error' => 'Log file not found.'], 404);
    }
}
