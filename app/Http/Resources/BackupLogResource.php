<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BackupLog Resource
 *
 * Transforms BackupLog model to match IBackupLog TypeScript interface
 */
class BackupLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // حساب المدة الزمنية (duration)
        $startedAt = $this->started_at ? strtotime($this->started_at) : null;
        $finishedAt = $this->finished_at ? strtotime($this->finished_at) : null;
        $duration = null;

        if ($startedAt && $finishedAt) {
            $duration = $finishedAt - $startedAt; // بالثواني
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'include_files' => (bool) $this->include_files,
            'total_size' => $this->total_size,
            'duration' => $duration,
            'message' => $this->message,
            'error_details' => null, // يمكن إضافة حقل منفصل للأخطاء إذا لزم
            'databases' => $this->databases ?? [],
            'files' => $this->files ?? [],
            'checksum' => null, // deprecated - use checksums array instead
            'checksums' => $this->checksums ?? [],
            'backup_paths' => $this->backup_paths ?? [],
            'storage_disk' => $this->storage_disk,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->finished_at?->toIso8601String(),
        ];
    }
}
