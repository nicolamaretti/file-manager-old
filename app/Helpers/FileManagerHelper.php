<?php

namespace App\Helpers;

use App\Models\File;
use App\Models\Folder;
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
        $folder = File::query()
            ->where('is_folder', true)
            ->whereNull('file_id')
            ->where('name', $folderName)
            ->get();

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
        $folder = File::query()
            ->where('is_folder', true)
            ->where('file_id', $parentFolderId)
            ->where('name', $folderName)
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
        $file = File::query()
            ->with('files')
            ->where('is_folder', true)
            ->find($folderId)
            ->files
            ->where('name', $fileName);

        return $file->isNotEmpty();
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
}
