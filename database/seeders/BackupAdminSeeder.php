<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BackupAdmin;
use App\Models\BackupSetting;

class BackupAdminSeeder extends Seeder
{
    public function run(): void
    {
        
        BackupAdmin::create([
            'name' => 'Main Admin',
            'email' => 'ali7med@gmail.com',
            'telegram_id' => '563390643',
            'notify_via' => ['telegram', 'email'],
            'active' => true,
        ]);
    }
}
