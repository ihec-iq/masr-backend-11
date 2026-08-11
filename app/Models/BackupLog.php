<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    protected $fillable = [
        'type',
        'include_files',
        'databases',
        'files',
        'status',
        'message',
        'storage_disk',
        'backup_paths',
        'checksums',
        'total_size',
        'started_at',
        'finished_at',
        'backup_setting_id',
    ];

    protected $casts = [
        'include_files' => 'boolean',
        'databases' => 'array',
        'files' => 'array',
        'backup_paths' => 'array',
        'checksums' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * العلاقة مع إعداد النسخ
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(BackupSetting::class, 'backup_setting_id');
    }
}
