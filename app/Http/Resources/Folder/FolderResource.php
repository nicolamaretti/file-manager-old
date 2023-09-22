<?php

namespace App\Http\Resources\Folder;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\DB;
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
        $user = Folder::query()
            ->join('users', 'users.id', '=', 'folders.user_id')
            ->where('folders.id', $this->id)
            ->select('users.name as userName')
            ->first();

//        $user = Folder::find($this->id)->with('user')->get();

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'parent'        => $this->folder_id,
            'uuid'          => $this->uuid,
            'folders'       => FolderResource::collection($this->folders),
            'updated_at'    => $this->updated_at->diffForHumans(),
            'owner'         => $user->userName,
            'is_favourite'  => $this->isFavourite(),
            'path'          => $this->path,
        ];
    }
}
