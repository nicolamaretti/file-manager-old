<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadDTO
{
    /**
     * Summary of __construct
     * 
     * @param string $name
     * @param string $uuid - l'uuid della cartella dentro cui vogliamo fare l'upload
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     */
    public function __construct(
        public string $uuid,
        public string $file_name,
        public UploadedFile $file
    ) {}
}
