<?php

namespace App\Traits;

use App\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use ZipArchive;

trait ZipableTrait
{
    public function getZipFolder(): string
    {
        // $this è l'oggetto Folder su cui è stato chiamato getZipFolder()

        // primo elemento del path è la cartella corrente
        $path = array($this->name);

        $zip_file = $this->name . '.zip';

        // creo (o sovrascrivo) l'archivio e lo apro
        $zipArchive = new ZipArchive();
        $zipArchive->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->zipFolderRecursive($this, $zipArchive, $path);

        $zipArchive->close();

        return $zip_file;
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///  PRIVATE HELPERS
    /**
     * $path è passato per copia perché per ogni sottocartella il percorso cambia
     *
     * @param Folder $folder
     * @param ZipArchive $zipArchive
     * @param array $path
     * @return void
     */
    private function zipFolderRecursive(Folder $folder, ZipArchive &$zipArchive, array $path): void
    {
        $folders = $folder->folders;
        $files = $folder->getMedia('documents');

        if ($files->isNotEmpty()) {
            $this->zipFiles($files, $zipArchive, $path);
        }

        if($folders->isNotEmpty()) {
            foreach ($folders as $subFolder) {
                // copia locale del path per arrivare al file
                $myPath = $path;

                // aggiungo la cartella corrente al path
                $myPath[] = $subFolder->name;

                $this->zipFolderRecursive($subFolder, $zipArchive, $myPath);
            }
        }

        if ($folders->isEmpty() && $files->isEmpty()) {
            // aggiunta di una cartella vuota
            $zipArchive->addEmptyDir(implode('/', $path));
        }
    }
    private function zipFiles(MediaCollection $files, ZipArchive &$zipArchive, array $path): void
    {
        foreach ($files as $file) {
            $filePath = $file->getPath();
            $myPath = implode('/', $path) . '/' . $file->file_name;

            $zipArchive->addFile($filePath, $myPath);
        }
    }
}
