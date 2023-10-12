<?php

namespace App\Services;

use ErrorException;
use App\Models\File;
use App\DTO\FileUploadDTO;
use Illuminate\Support\Str;
use App\Helpers\FileUploaderHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UploadFileService
{
    /**
     * Carica il file presente nel DTO all'interno della cartella specificata
     * 
     * @param \App\DTO\FileUploadDTO $fileUploadDTO
     * @throws \ErrorException
     * @return void
     */
    public function loadFileToFolder(FileUploadDTO $fileUploadDTO): void
    {
        // TODO
        $uuid = $fileUploadDTO->uuid;
        $file = $fileUploadDTO->file;
        $fileName = $fileUploadDTO->file_name;

        // 1) Trovo la folder associata all'uuid del FileUploadDTO passato come argomento
        $folder = File::query()
            ->where('is_folder', true)
            ->where('uuid', $uuid)
            ->first();

        if(!$folder) {
            throw new ErrorException("Folder with uuid of '$uuid' not found.");
        }

        // 2) Carico il file all'interno di quella folder
        /* verifico se all'interno della cartella esiste già un file con lo stesso nome */
        $fileAlreadyExists = FileUploaderHelper::checkFileExistence($fileName, $folder->id);

        if ($fileAlreadyExists) {
            /* se esiste già, aggiungo un timestamp al nome del file */
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $fileBasename = pathinfo($fileName, PATHINFO_FILENAME);

            $fileName = $fileBasename . '-' . time() . '.' . $fileExtension;
        }

        /* aggiunta del file */
        $path = $folder->path . "/$fileName";

        File::create([
            'name' => $fileName,
            'path' => $path,
            'file_id' => $folder->id,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uuid' => Str::uuid(),
            'created_by' => $folder->created_by,
            // 'created_by' => 5,
        ]);

        Storage::disk('local')->put($path, file_get_contents($file));
    }
}
