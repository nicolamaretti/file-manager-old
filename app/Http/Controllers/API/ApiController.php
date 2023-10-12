<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\FolderApiResource;
use App\Models\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class ApiController extends Controller
{
    public function getFolders(Request $request)
    {
        $folders = File::query()
            ->with('user')
            ->where('is_folder', true)
            ->get();

        return FolderApiResource::collection($folders);
    }

    public function uploadFile(Request $request)
    {
        $uploadedFile = $request->file();
        $folderUuid = $request->get('folder_uuid');

        // dd($uploadedFile, $folderUuid);

        /* Chiamo il comando Artisan per fare l'importazione */
        $exitCode = Artisan::call('fileimport:ftp', [
            'file' => $uploadedFile,
            '--uuid' => $folderUuid,
        ]);

        return response()->json([
            'success' => $exitCode == 0 ? true : false,
        ]);
    }
}
