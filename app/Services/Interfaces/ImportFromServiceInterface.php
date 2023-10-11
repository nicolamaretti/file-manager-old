<?php

namespace App\Services\Interfaces;

use App\DTO\FileUploadDTO as DTOFileUploadDTO;
use App\Http\Requests\Import\FileImportRequest;
use App\DTO\FileUploadDTO;

interface ImportFromServiceInterface
{
    /** Crea un FileUploadDTO a partire dal file presente nella FileImportRequest
     * 
     * @param FileImportRequest $request
     * @return DTOFileUploadDTO
     */
    public function processFile(FileImportRequest $request): FileUploadDTO;
}
