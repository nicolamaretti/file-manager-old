<?php

namespace App\Services;

use App\Http\Requests\Import\FileImportRequest;
use App\Services\Interfaces\ImportFromServiceInterface;
use Illuminate\Http\UploadedFile;

class FileSharingService
{
    /**
     * Funzione che carica il file nella cartella specificata dopo aver calcolato il DTO
     * 
     * @param \App\Services\Interfaces\ImportFromServiceInterface $importService
     * @param \App\Services\UploadFileService $uploadFileService
     * @param \Illuminate\Http\UploadedFile|array $uploadedFile
     * @param string $folderUuid
     * @return void
     */
    public function upload(ImportFromServiceInterface $importService, UploadFileService $uploadFileService, UploadedFile|array $uploadedFile, string $folderUuid): void
    {
        // dd($importService, $uploadFileService, $uploadedFile, $folderUuid);

        // TODO
        // 0)? creazione nuova request
        $request = new FileImportRequest();
        $request->merge([
            'uploaded_file' => $uploadedFile,
            'folder_uuid' => $folderUuid,
        ]);

        // dd($request);

        // 1) process the file from $importService
        $fileDTO = $importService->processFile($request);

        // 2) upload the file with UploadFileService
        $uploadFileService->loadFileToFolder($fileDTO);
    }
}
