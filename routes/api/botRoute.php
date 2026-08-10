<?php

use App\Http\Controllers\Api\v1\BotController;
use Illuminate\Support\Facades\Route;

// نقطة استقبال Telegram Webhook — يجب أن تبقى خارج auth:sanctum لأن خوادم
// Telegram لا ترسل Bearer token؛ الحماية عبر secret_token في الترويسة.
// راجع config('telegram.webhook.path') = /api/bot/onBoard
// throttle:api (60/دقيقة لكل IP) لا يناسب Telegram: تحديثاته تأتي من مجموعة IP
// صغيرة وبمعدل عالٍ، و429 يجعله يتراجع ثم يُسقط التحديثات — فنستبدله بـ throttle:telegram.
// و maintenance محذوف عمدًا: 503 أثناء الصيانة يعني فقدان تحديثات لا استئنافها.
Route::prefix('/bot')
    ->middleware(['telegram.webhook', 'locale', 'throttle:telegram'])
    ->withoutMiddleware(['throttle:api'])
    ->group(function () {
        Route::post('/onBoard', [BotController::class, 'onBoard']);
    });

Route::prefix('/bot')->middleware(['auth:sanctum', 'locale', 'maintenance'])->group(function () {
    Route::get('/', [BotController::class, 'index']);
    Route::get('/getMe', [BotController::class, 'getMe']);
    Route::post('/getUpdates', [BotController::class, 'getUpdates']);
    Route::get('/sendMessage', [BotController::class, 'sendMessage']);
    Route::get('/sendPhoto', [BotController::class, 'sendPhoto']);
    Route::get(
        '/getEmployeeVacationReportByID/{vacationId}',
        [BotController::class, 'getEmployeeVacationReportByID']
    );
    Route::get('/getEmployeeVacationReportMy', [BotController::class, 'getEmployeeVacationReportMy']);
    Route::options('/getEmployeeVacationReportMy', [BotController::class, 'getEmployeeVacationReportMy']);

    Route::get('/getUserProfilePhotos', [BotController::class, 'getUserProfilePhotos']);
    Route::get('/getEmployeeVacationReport/{chat_id}', [BotController::class, 'getEmployeeVacationReport']);
    Route::get('/setHookUrl', [BotController::class, 'setHookUrl']);
});
