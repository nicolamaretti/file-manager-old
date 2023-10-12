<?php

namespace App\Services;

use App\Http\Requests\Import\FileImportRequest;
use App\DTO\FileUploadDTO;
use App\Helpers\FileUploaderHelper;
use App\Services\Interfaces\ImportFromServiceInterface;

class ImportFromFTPService implements ImportFromServiceInterface
{
    public function processFile(FileImportRequest $request): FileUploadDTO
    {
        // TODO il file in ingresso è già di tipo UploadedFile quindi non va bene

        // 1) prendo il file e l'uuid della folder dalla request
        $files = $request->get('uploaded_file');
        $folderUuid = $request->get('folder_uuid');

        /* ho un solo file, prendo il primo dell'array */
        $file = head($files);
        $fileContent = file_get_contents($file);

        // 2) Trasformo il file binario in file base64
        $fileContentBase64 = chunk_split(base64_encode($fileContent));
        
        $fileName = $file->getClientOriginalName();
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // 3) trasformo il file base64 in UploadedFile
        $uploadedFile = FileUploaderHelper::fromBase64($fileContentBase64, $fileName, $fileExtension);

        $data = [
            'uuid' => $folderUuid,
            'file_name' => $fileName,
            'file' => $uploadedFile
        ];

        // 4) Ritorno il FileUploadDTO
        return new FileUploadDTO(...$data);
    }
}
