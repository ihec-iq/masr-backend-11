<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Telegram Bot integration including bot token,
    | webhook settings, and notification preferences.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'webhook' => [
        'base_url' => env('TELEGRAM_WEBHOOK_BASE_URL', env('APP_URL')),
        'path' => '/api/bot/onBoard',

        // يُرسل من Telegram في ترويسة X-Telegram-Bot-Api-Secret-Token مع كل تحديث،
        // ويُسجَّل لدى Telegram عبر باراميتر secret_token في setWebhook.
        'secret_token' => env('TELEGRAM_WEBHOOK_SECRET', ''),

        // قائمة سماح لوجهة setWebhook. دومين APP_URL مسموح دائمًا؛ أضف هنا أي
        // دومين إضافي (staging مثلًا) مفصولًا بفاصلة.
        'allowed_hosts' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TELEGRAM_WEBHOOK_ALLOWED_HOSTS', ''))
        ))),
    ],

    'notifications' => [
        'enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
        'default_chat_ids' => env('TELEGRAM_DEFAULT_CHAT_IDS', ''),
    ],

    'api' => [
        'base_url' => 'https://api.telegram.org/bot',
        'timeout' => env('TELEGRAM_API_TIMEOUT', 30),
    ],

];
