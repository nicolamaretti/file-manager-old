<?php

namespace App\Helpers\Interfaces;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileUploaderInterface
{
    /**
     * Verifica l'esistenza di una cartella root avente lo stesso nome
     *
     * @param string $folderName
     * @return boolean
     */
    public static function checkRootFolderExistence(string $folderName): bool;

    /**
     * Verifica l'esistenza di una cartella avente lo stesso nome, all'interno della medesima cartella padre
     *
     * @param string $folderName
     * @param integer $parentFolderId
     * @return boolean
     */
    public static function checkFolderExistence(string $folderName, int $parentFolderId): bool;

    /**
     * Verifica l'esistenza di un file con lo stesso nome, all'interno della cartella specificata
     *
     * @param string $fileName
     * @param integer $folderId
     * @return boolean
     */
    public static function checkFileExistence(string $fileName, int $folderId): bool;

    /**
     * Trasforma un file da Base64 ad UploadedFile
     *
     * @param string $base64File
     * @param string $fileName
     * @return UploadedFile
     */
    public static function fromBase64(string $base64File, string $fileName): UploadedFile;
}
