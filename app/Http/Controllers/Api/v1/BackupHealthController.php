<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Models\BackupSetting;

class BackupHealthController extends Controller
{
    public function status()
    {
        $s = BackupSetting::firstOrFail();
        $staleHours = (int) ($s->stale_hours ?? 48);

        $last = BackupLog::where('status', 'success')->latest('finished_at')->first();

        if (!$last) {
            return ['status' => 'fail', 'reason' => 'no_success_backups_yet'];
        }

        $ageHours = now()->diffInHours($last->finished_at);
        if ($ageHours > $staleHours) {
            return ['status' => 'stale', 'last_success_hours_ago' => $ageHours];
        }

        return ['status' => 'ok', 'last_success_at' => $last->finished_at];
    }
}
