<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

class FileUploadDTO
{
    /**
     * Summary of __construct
     * @param string $name
     * @param string $uuid - l'uuid della cartella dentro cui vogliamo fare l'upload
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     */
    public function __construct(
        public string $name,
        public string $uuid,
        public UploadedFile $uploadedFile
    ) {}
}
