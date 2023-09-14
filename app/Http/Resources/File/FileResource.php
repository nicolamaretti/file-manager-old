<?php

namespace App\Http\Resources\File;

use App\Helpers\FileManagerHelper;
use App\Models\Media;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        $owner = User::query()
            ->select('users.*')
            ->join('folders', 'folders.user_id', '=', 'users.id')
            ->where('folders.id', $this->model_id)
            ->first();

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'file_name'     => $this->file_name,
            'size'          => FileManagerHelper::getFileSize($this),
            'mime_type'     => $this->mime_type,
            'updated_at'    => $this->updated_at,
            'owner'         => $owner->name,
            'is_favourite'  => FileManagerHelper::fileIsFavourite($this->id),
        ];
    }
}
