<?php

namespace App\Http\Resources\File;

use App\Helpers\FileManagerHelper;
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
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'file_name'     => $this->file_name,
            'size'          => FileManagerHelper::getFileSize($this),
            'mime_type'     => $this->mime_type,
            'updated_at'    => $this->updated_at,
            'is_favourite'  => FileManagerHelper::fileIsFavourite($this->id),
        ];
    }
}
