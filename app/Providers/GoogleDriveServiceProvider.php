<?php

namespace App\Providers;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDriveService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

// 🟢 مهم: هذا الـ Adapter من masbug متوافق مع Flysystem v3
use Masbug\Flysystem\GoogleDriveAdapter;

// 🟢 Laravel يحتاج FilesystemAdapter (الـ wrapper) وليس League\Flysystem\Filesystem مباشرة
use Illuminate\Filesystem\FilesystemAdapter;

// هذا هو Filesystem الخاص بـ League (نستخدمه كطبقة داخلية فقط)
use League\Flysystem\Filesystem as Flysystem;

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            // 1) إعداد Google Client بمفاتيح Service Account
            $client = new GoogleClient();
            $client->setAuthConfig($config['service_account_json'] ?? storage_path('app/google-service-account.json'));
            $client->addScope(GoogleDriveService::DRIVE);
            $service = new GoogleDriveService($client);

            // 2) تهيئة الـ Adapter لمجلد الجذر المحدد
            $rootFolderId = $config['folderId'] ?? 'root';
            $adapter = new GoogleDriveAdapter($service, $rootFolderId, [
                'useDisplayPaths' => true,
            ]);

            // 3) إنشاء Flysystem الداخلي
            $flysystem = new Flysystem($adapter, [
                'case_sensitive' => false, // اختياري
            ]);

            // 4) إرجاع Laravel FilesystemAdapter — هذا اللي عليه put()/get()/exists()...
            return new FilesystemAdapter($flysystem, $adapter);
        });
    }
}
