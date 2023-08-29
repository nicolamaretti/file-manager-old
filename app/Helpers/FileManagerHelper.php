<?php

namespace App\Helpers;

use App\Http\Resources\Folder\FolderResource;
use App\Models\Folder;
use http\Env\Request;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileManagerHelper
{
    /**
     * Verifica l'esistenza di una cartella root avente lo stesso nome
     *
     * @param string $folderName
     * @return boolean
     */
    public static function checkRootFolderExistence(string $folderName): bool
    {
        $folder = Folder::where(function ($query) use($folderName){
            $query->where('name', $folderName)
                ->whereNull('folder_id');
        })->get();

        return $folder->isNotEmpty();
    }

    /**
     * Verifica l'esistenza di una cartella avente lo stesso nome, all'interno della medesima cartella padre
     *
     * @param string $folderName
     * @param integer $parentFolderId
     * @return boolean
     */
    public static function checkFolderExistence(string $folderName, int $parentFolderId): bool
    {
        $folder = Folder::where('name', $folderName)
            ->where('folder_id', $parentFolderId)
            ->get();

        return $folder->isNotEmpty();
    }

    /**
     * Verifica l'esistenza di un file con lo stesso nome, all'interno della cartella specificata
     *
     * @param string $fileName
     * @param integer $folderId
     * @return boolean
     */
    public static function checkFileExistence(string $fileName, int $folderId): bool
    {
        $files = Folder::find($folderId)->getMedia('documents')->where('file_name', $fileName);

        return $files->isNotEmpty();
    }

    /**
     * Calcola il path di un file partendo dal file stesso fino alla route
     *
     * @param int $fileId
     * @return void
     */
    public static function getFilePath(int $fileId): string
    {
        $file = Media::findOrFail($fileId);
        $parentId = $file->model_id;
        $path = [];

        self::getFilePathRecursive($parentId, $path);

        /* a questo punto ho il path */

        return implode('/', $path) . '/' . $file->file_name;
    }

    /**
     * Trasforma un file da Base64 ad UploadedFile
     *
     * @param string $base64File
     * @param string $fileName
     * @return UploadedFile
     */
    public static function fromBase64(string $base64File, string $fileName): UploadedFile
    {
        // Get file data from base64 string
        $fileData = base64_decode($base64File);

        // Create temp file and get its absolute path
        $tempFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        // Save file data in file
        file_put_contents($tempFilePath, $fileData);

        // Constructs a new file from the given path
        $tempFileObject = new File($tempFilePath);

        $uploadedFile = new UploadedFile(
            $tempFileObject->getPathname(),
            $fileName,
            $tempFileObject->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        // Close this file after response is sent.
        // Closing the file will cause to remove it from temp director!
        app()->terminating(function () use ($tempFile) {
            fclose($tempFile);
        });

        // return UploadedFile object
        return $uploadedFile;
    }

    /**
     * Private helper per calcolare il path ricorsivamente
     *
     * @param int $folderId
     * @param $path
     * @return void
     */
    private static function getFilePathRecursive(int $folderId, &$path): void
    {
        $folder = Folder::findOrFail($folderId);

        array_push($path, $folder->id);

        if (!($folder->parent)) {
            // se non c'è il parent vuol dire che sono nella route

            // faccio il reverse dell'array calcolato perché sono partito dalla fine
            $path = array_reverse($path);
        } else {
            // se c'è un parent della folder corrente, faccio la chiamata ricorsiva
            $parentId = $folder->parent->id;

            self::getFilePathRecursive($parentId, $path);
        }
    }
}
