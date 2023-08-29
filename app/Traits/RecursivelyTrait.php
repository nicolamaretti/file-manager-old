<?php

namespace App\Traits;

use App\Models\Folder;
use Illuminate\Support\Str;

trait RecursivelyTrait
{
    /**
     * Ottiene tutti gli id delle sottocartelle a partire dall'id cartella dell'oggetto che ha chiamato la funzione
     *
     * @return array $childrenIds
     */
    public function getChildrenIds(): array
    {
        $childrenIds = array();

        $this->getChildrenRecursive($this->id, $childrenIds);

        return $childrenIds;
    }

    /**
     * Ottiene il percorso completo (emulato) della cartella che chiama la funzione
     *
     * @return string
     */
    public function getFullPath(): string
    {
        $path = array();

        return $this->getFullPathRecursive($this, $path);
    }

    public function copyFolder(int $destinationFolderId): void
    {
        /* controllo che la cartella di destinazione non sia una cartella figlia di quella
         * che si sta tentando di copiare */
        $childrenIds = $this->getChildrenIds();

        if (in_array($destinationFolderId, $childrenIds)) {
            abort(500);
        }

        $this->copyFolderRecursive($this->id, $destinationFolderId);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///  PRIVATE HELPERS
    /**
     * Funzione helper - calcola ricorsivamente tutti gli id delle sottocartelle della cartella passata come argomento
     *
     * @param integer $folderId
     * @param array $childrenIds
     * @return void
     */
    private function getChildrenRecursive(int $folderId, array &$childrenIds): void
    {
        // aggiungo la folder nell'array da ritornare
        array_push($childrenIds, $folderId);

        // mi faccio dare le cartelle figlie di quella corrente
        $folders = Folder::find($folderId)
            ->folders;

        if($folders->isEmpty())
            return;
        else {
            foreach ($folders as $subFolder) {
                $this->getChildrenRecursive($subFolder->id, $childrenIds);
            }
        }
    }

    /**
     * Funzione helper - calcola ricorsivamente il percoso completo (emulato) della cartella passata come argomento
     * (che PUO' ESSERE NULL se entro in questa funzione passando il parent di una cartella root)
     *
     * @param Folder $folder
     * @param array $path
     * @return string
     */
    private function getFullPathRecursive(Folder $folder, array &$path): string
    {
        if($folder->parent === null) {
            // sono nella root folder

            // aggiungo l'ultima folder
            array_push($path, $folder->name);

            // gli elementi nell'array vengono memorizzati "a ritroso" dalla cartella corrente fino alla root, quindi li inverto
            $path = array_reverse($path);

            return implode('/', $path);
        } else {
            // aggiungo la folder corrente al path
            array_push($path, $folder->name);

            return $this->getFullPathRecursive($folder->parent, $path);
        }
    }

    private function copyFolderRecursive(int $currentFolderId, int $destinationFolderId): void
    {
        $folderA = Folder::with('folders')->find($currentFolderId);
        $folderB = Folder::find($destinationFolderId);

        $folderAChildren = $folderA->folders;
        $folderAFiles = $folderA->getMedia('documents');

        $newFolder = new Folder();
        $newFolder->name = $folderA->name;
        $newFolder->folder_id = $folderB->id;
        $newFolder->user_id = $folderA->user_id;
        $newFolder->uuid = Str::uuid();
        $newFolder->save();

        foreach ($folderAFiles as $file) {
            $file->copy($newFolder, 'documents');
        }

        if ($folderAChildren->isNotEmpty()) {
            foreach ($folderAChildren as $child) {
                $this->copyFolderRecursive($child->id, $newFolder->id);
            }
        }
    }
}
