<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class FolderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array|Arrayable|JsonSerializable
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'parent'        => $this->folder_id,
            'folders'       => FolderResource::collection($this->folders),
            'files'         => MediaResource::collection($this->media),
            'owner'         => $this->user->name,
            'path'          => $this->storage_path,
            'updated_at'    => $this->updated_at->diffForHumans(),
            'is_favourite'  => !!$this->starred,
        ];
    }
}
