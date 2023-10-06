<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SharedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'name' => $this['name'],
            'is_folder' => $this['is_folder'],
            'is_favourite' => !!$this['is_favourite'],
            'mime_type' => $this['mime_type'],
            'owner' => $this['owner'],
            'shared_with' => $this['shared_with'],
        ];
    }
}
