<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحمي نقطة استقبال Telegram Webhook.
 *
 * لا يمكن استخدام auth:sanctum هنا لأن خوادم Telegram لا ترسل أي Bearer token،
 * فيرجع 401 ويتوقف البوت. البديل الرسمي من Telegram هو secret_token الذي يُرسل
 * في ترويسة X-Telegram-Bot-Api-Secret-Token مع كل تحديث.
 *
 * إذا لم يُضبط TELEGRAM_WEBHOOK_SECRET يمر الطلب (سلوك ما قبل التفعيل) مع
 * تسجيل تحذير، حتى لا ينكسر البوت في بيئة لم تُضبط بعد.
 */
class VerifyTelegramWebhook
{
    /** تم تسجيل تحذير "بلا secret" في هذه العملية؟ */
    protected static bool $warned = false;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('telegram.webhook.secret_token');

        if ($secret === '') {
            // مرة واحدة لكل عملية: البوت قد يستقبل عدة تحديثات في الثانية، وتسجيل
            // نفس التحذير مع كل طلب يغرق laravel.log ويخفي الأخطاء الحقيقية.
            if (! static::$warned) {
                static::$warned = true;
                Log::warning('Telegram webhook is unprotected: TELEGRAM_WEBHOOK_SECRET is not set.');
            }

            return $next($request);
        }

        $provided = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if (! hash_equals($secret, $provided)) {
            Log::warning('Rejected Telegram webhook call with invalid secret token', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
