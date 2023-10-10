<?php

namespace App\Library\Services\Interfaces;

use App\Library\DTO\FileUploadDTO;
use Illuminate\Http\Request;

interface ImportFileServiceInterface
{
    public function processFile(Request $request): FileUploadDTO;
}
