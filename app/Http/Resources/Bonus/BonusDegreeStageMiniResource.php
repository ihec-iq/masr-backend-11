<?php

namespace App\Http\Resources\Bonus;

use App\Http\Resources\GeneralIdNameResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonusDegreeStageMiniResource extends JsonResource
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
            'title' =>   $this->title,
            'degree'=> $this->Degree->name,
            'stage'=> $this->Stage->name, 
            'salary' => $this->salary,
        ];
    }
}
