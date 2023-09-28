<?php

namespace App\Http\Resources;

use App\Helpers\FileManagerHelper;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use JsonSerializable;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array|Arrayable|JsonSerializable
    {
        $owner = User::with('folders.user')
            ->whereRelation('folders', 'user_id', Auth::id())
            ->first();

        return [
            'id' => $this->id,
            'name' => $this->file_name,
            'path' => $this->getCustomProperty('path'),
            'size' => $this->getFileSize(),
            'mime_type' => $this->mime_type,
            'owner' => $owner->name,
            'updated_at' => $this->updated_at->diffForHumans(),
            'is_favourite'  => !!$this->starred,
        ];
    }
}
