<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use JsonSerializable;

class FileResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'parent' => $this->file_id,
            // 'files' => FileResource::collection($this->files),
            'path' => $this->path,
            'is_folder' => $this->is_folder,
            'size' => $this->size ? $this->getFileSize() : '-----',
            'mime_type' => $this->mime_type,
            'owner' => $this->user->name,
            'updated_at' => $this->updated_at->diffForHumans(),
            'is_favorite' => !!$this->starred,
        ];
    }
}
