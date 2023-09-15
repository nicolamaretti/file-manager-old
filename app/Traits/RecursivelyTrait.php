<?php

namespace App\Traits;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait RecursivelyTrait
{
    /**
     * Ottiene l'id della cartella corrente e delle sottocartelle
     *
     * @return array $childrenIds
     */
    public function getChildrenIds(): array
    {
        $childrenIds = array();

        $this->getChildrenIdsRecursive($this->id, $childrenIds);

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

    public function copyFolder(int $destinationFolderId = null): void
    {
        if (!$destinationFolderId) {
            /* se non è specificata la folder di destinazione, copio la folder nella folder padre in cui si trova */
            $destinationFolderId = $this->folder_id;
        }

        /*
         * controllo che la cartella di destinazione non sia una cartella figlia di quella
         * che si sta tentando di copiare (rimuovo il primo elemento dell'array che è la folder che sto copiando)
         */
        $childrenIds = $this->getChildrenIds();
        array_shift($childrenIds);

        if (in_array($destinationFolderId, $childrenIds)) {
            abort(403, 'Permission denied');
        }

        /* copia */

        $newFolder = new Folder();
        $newFolder->name = $this->name . '-copy';
        $newFolder->folder_id = $destinationFolderId;
        $newFolder->user_id = $this->user_id;
        $newFolder->uuid = Str::uuid();
        $newFolder->save();

        $this->copyFolderRecursive($this, $newFolder);
    }

    public function getAncestors(): array
    {
        $userIsAdmin = Auth::user()->is_admin;

        $ancestors = array();

        return $this->getAncestorsRecursive($userIsAdmin, $this, $ancestors);
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
    private function getChildrenIdsRecursive(int $folderId, array &$childrenIds): void
    {
        // aggiungo la folder nell'array da ritornare
        $childrenIds[] = $folderId;

        // mi faccio dare le cartelle figlie di quella corrente
        $subFolders = Folder::query()
            ->find($folderId)
            ->folders;

        if($subFolders->isEmpty())
            return;
        else {
            foreach ($subFolders as $subFolder) {
                $this->getChildrenIdsRecursive($subFolder->id, $childrenIds);
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
        // aggiungo la folder corrente al path
        $path[] = $folder->name;

        if($folder->parent === null) {
            // sono nella root folder

            // gli elementi nell'array vengono memorizzati "a ritroso" dalla cartella corrente fino alla root, quindi li inverto
            $path = array_reverse($path);

            return implode('/', $path);
        } else {
            return $this->getFullPathRecursive($folder->parent, $path);
        }
    }

    private function copyFolderRecursive(Folder $currentFolder, Folder $destinationFolder): void
    {
        $folderChildren = $currentFolder->folders;
        $folderFiles = $currentFolder->getMedia('documents');

        if ($folderFiles->isNotEmpty()) {
            foreach ($folderFiles as $file) {
                $file->copy($destinationFolder, 'documents');
            }
        }

        if ($folderChildren->isNotEmpty()) {
            foreach ($folderChildren as $childFolder) {
                $newChildFolder = new Folder();
                $newChildFolder->name = $childFolder->name;
                $newChildFolder->folder_id = $destinationFolder->id;
                $newChildFolder->user_id = $destinationFolder->user_id;
                $newChildFolder->uuid = Str::uuid();
                $newChildFolder->save();

                $this->copyFolderRecursive($childFolder, $newChildFolder);
            }
        }
    }

    private function getAncestorsRecursive(bool $userIsAdmin, Folder $folder, array &$ancestors): array
    {
        if($folder->parent === null) {
            // sono nella root folder

            if ($userIsAdmin) {
                // se sono admin aggiungo anche l'ultima folder
                array_push($ancestors, [
                    'id' => $folder->id,
                    'name' => $folder->name,
                ]);
            }

            // gli elementi nell'array vengono memorizzati "a ritroso" dalla cartella corrente fino alla root, quindi li inverto
            $ancestors = array_reverse($ancestors);

            return $ancestors;
        } else {
            // aggiungo la folder corrente al path
            array_push($ancestors, [
                'id' => $folder->id,
                'name' => $folder->name,
            ]);

            return $this->getAncestorsRecursive($userIsAdmin, $folder->parent, $ancestors);
        }
    }
}
