<?php

namespace App\Http\Resources\Document;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'path' => config('app.url').Storage::url($this->path).'?'.rand(1, 999999999),
            'size' => $this->formatFileSize($this->size),
            'description' => $this->description,
            'extension' => $this->extension,
            'linkId' => $this->documentable_id,
            'numberBill' => $this->number_bill,
        ];
    }

    public function formatFileSize($bytes)
    {
        $size = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf('%.2f', $bytes / pow(1024, $factor)).' '.@$size[$factor];
    }
}
