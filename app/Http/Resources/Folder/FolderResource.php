<?php

namespace App\Http\Resources\Folder;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Contracts\Support\Arrayable;
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
        $user = User::find($this->user_id);

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'parent'        => $this->folder_id,
            'uuid'          => $this->uuid,
            'folders'       => FolderResource::collection($this->folders),
            'fullPath'      => $this->getFullPath(),
            'owner'         => $user->name,
        ];
    }
}
