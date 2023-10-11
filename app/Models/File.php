<?php

namespace App\Models;

use App\Exceptions\FileAlreadyExistsException;
use App\Helpers\FileUploaderHelper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use ZipArchive;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'path',
        'storage_path',
        'is_folder',
        'file_id',
        'mime_type',
        'size',
        'uuid',
        'created_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class)->orderBy('name');
    }

    public function starred(): HasOne
    {
        return $this->hasOne(StarredFile::class)
            ->where('file_id', $this->id)
            ->where('user_id', Auth::id());
    }

    public function shared(): HasMany
    {
        return $this->hasMany(FileShare::class)
            ->where('user_id', Auth::id());
    }

    public function getFileSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $this->size > 0 ? floor(log($this->size, 1024)) : 0;

        return number_format($this->size / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * @throws AuthenticationException
     * @throws FileAlreadyExistsException
     */
    public function rename(string $newName): void
    {
        if ($this->user->id !== Auth::id()) {
            throw new AuthenticationException('You don\'t have permissions to rename the selected folder');
        }

        $parent = $this->parent;

        if ($this->is_folder) {
            $this->renameFolder($newName, $parent);
        } else {
            $this->renameFile($newName, $parent);
        }
    }

    /**
     * @throws UnauthorizedException
     * @throws AuthenticationException
     */
    public function copy(): void
    {
        if ($this->user->id !== Auth::id()) {
            throw new AuthenticationException('You don\'t have permissions to rename the selected folder');
        }

        $parent = $this->parent;

        if ($this->is_folder) {
            $this->copyFolder($parent);
        } else {
            $this->copyFile($parent);
        }
    }

    /**
     * @throws AuthenticationException
     */
    public function move(int $moveIntoFolderId): void
    {
        if ($this->user->id !== Auth::id()) {
            throw new AuthenticationException('You don\'t have permissions to rename the selected folder');
        }

        if ($this->is_folder) {
            $this->moveFolder($moveIntoFolderId);
        } else {
            $this->moveFile($moveIntoFolderId);
        }
    }

    /**
     * Ritorna un array contenente gli id delle sottocartelle
     *
     * @return array $childrenIds
     */
    public function getChildrenIds(): array
    {
        $childrenIds = array();

        $this->getChildrenIdsRecursive($this->id, $childrenIds);

        /* rimuovo il primo id che è quello della cartella corrente */
        array_shift($childrenIds);

        return $childrenIds;
    }

    /**
     * Ritorna un array contenente i nomi delle cartelle dalla root alla cartella corrente
     *
     * @return array $ancestors
     */
    public function getAncestors(): array
    {
        $isUserAdmin = Auth::user()->is_admin;

        $ancestors = array();

        return $this->getAncestorsRecursive($isUserAdmin, $this, $ancestors);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// PRIVATE HELPERS

    /**
     * @throws FileAlreadyExistsException
     */
    private function renameFile(string $newName, File $parent): void
    {
        /* controllo se all'interno della cartella in cui si trova il file esiste già un
         * altro file con lo stesso nome */
        $fileExt = pathinfo($this->name, PATHINFO_EXTENSION);
        $newFileFullName = $newName . '.' . $fileExt;

        $fileAlreadyExists = FileUploaderHelper::checkFileExistence($newFileFullName, $parent->id);

        if ($fileAlreadyExists) {
            throw new FileAlreadyExistsException('A folder with this name already exists.');
        }

        $newPath = $parent->path . "/$newFileFullName";

        Storage::move($this->path, $newPath);

        // modifico il nome del file selezionato (sia name che file_name) e il path
        $this->name = $newFileFullName;
        $this->path = $newPath;
        $this->save();
    }

    /**
     * @throws AuthenticationException
     * @throws FileAlreadyExistsException
     */
    private function renameFolder(string $newName, File $parent = null): void
    {
        if (!$parent) {
            /* se parent è null significa che si vuole rinominare una root folder,
             * quindi controllo se esiste già una cartella che ha lo stesso nome del nome inserito */

            if (!Auth::user()->is_admin) {
                throw new AuthenticationException('You don\'t have permissions to rename the selected folder');
            }

            $folderAlreadyExists = FileUploaderHelper::checkRootFolderExistence($newName);
        } else {
            /* altrimenti controllo se esiste già una cartella che ha lo stesso nome del nome inserito
             * all'interno del parent della cartella selezionata */

            $folderAlreadyExists = FileUploaderHelper::checkFolderExistence($newName, $parent->id);
        }

        if ($folderAlreadyExists) {
            throw new FileAlreadyExistsException('A folder with this name already exists.');
        }

        $oldPath = $this->path;
        $newPath = $parent->path . "/$newName";

        $this->name = $newName;
        $this->path = $newPath;
        $this->save();

        Storage::move($oldPath, $newPath);
        $this->moveStorageRecursive($this->files);

        Storage::deleteDirectory($oldPath);
    }

    private function copyFile(File $parent): void
    {
        $fileExt = pathinfo($this->name, PATHINFO_EXTENSION);
        $fileName = pathinfo($this->name, PATHINFO_FILENAME);

        $newFileName = $fileName . '-copy' . ".$fileExt";
        $newFilePath = $parent->path . "/$newFileName";

        $replicatedFile = $this->replicate();
        $replicatedFile->name = $newFileName;
        $replicatedFile->path = $newFilePath;
        $replicatedFile->uuid = Str::uuid();
        $replicatedFile->created_by = Auth::id();
        $replicatedFile->save();

        Storage::copy($this->path, $replicatedFile->path);
    }

    /**
     * @throw  UnauthorizedException
     * @return void
     */
    private function copyFolder(File $parent): void
    {
        /* controllo che la cartella di destinazione non sia una cartella figlia di quella
         * che si sta tentando di copiare */
        $childrenIds = $this->getChildrenIds();

        if (in_array($parent->id, $childrenIds)) {
            throw new UnauthorizedException('Permission denied', 404);
        }

        /* copia */
        $newName = $this->name . '-copy';

        $replicatedFolder = $this->replicate();
        $replicatedFolder->name = $newName;
        $replicatedFolder->path = $parent->path . "/$newName";
        $replicatedFolder->uuid = Str::uuid();
        $replicatedFolder->created_by = Auth::id();
        $replicatedFolder->save();

        Storage::createDirectory($replicatedFolder->path);

        $this->copyFolderRecursive($this->files, $replicatedFolder);
    }

    private function copyFolderRecursive(Collection $files, File $destinationFolder): void
    {
        if ($files->isNotEmpty()) {
            foreach ($files as $file) {
                $replicated = $file->replicate();
                $replicated->path = $destinationFolder->path . "/$file->name";
                $replicated->file_id = $destinationFolder->id;
                $replicated->uuid = Str::uuid();
                $replicated->created_by = Auth::id();
                $replicated->save();

                if ($file->is_folder) {
                    $replicated->is_folder = true;
                    $replicated->save();

                    Storage::makeDirectory($replicated->path);

                    $this->copyFolderRecursive($file->files, $replicated);
                } else {
                    Storage::copy($file->path, $replicated->path);
                }
            }
        }
    }

    private function moveFile(int $moveIntoFolderId): void
    {
        $moveIntoFolder = File::query()
            ->where('is_folder', true)
            ->find($moveIntoFolderId);

        $oldPath = $this->path;
        $newPath = $moveIntoFolder->path . "/$this->name";

        $this->file_id = $moveIntoFolderId;
        $this->path = $newPath;
        $this->save();

        Storage::move($oldPath, $newPath);
    }

    private function moveFolder(int $moveIntoFolderId): void
    {
        $moveIntoFolder = File::query()
            ->where('is_folder', true)
            ->find($moveIntoFolderId);

        $oldPath = $this->path;

        $this->file_id = $moveIntoFolderId;
        $this->path = $moveIntoFolder->path . "/$this->name";
        $this->save();

        Storage::move($oldPath, $this->path);
        $this->moveStorageRecursive($this->files);

        Storage::deleteDirectory($oldPath);
    }

    private function moveStorageRecursive(Collection $files): void
    {
        if ($files->isNotEmpty()) {
            foreach ($files as $file) {
                $parent = $file->parent;
                $newPath = $parent->path . "/$file->name";
                $oldPath = $file->path;

                $file->path = $newPath;
                $file->save();

                Storage::move($oldPath, $newPath);

                if ($file->is_folder) {
                    $this->moveStorageRecursive($file->files);
                }
            }
        }
    }

    private function getAncestorsRecursive(bool $isUserAdmin, File $folder, array &$ancestors): array
    {
        if ($folder->parent === null) {
            // sono nella root folder

            if ($isUserAdmin) {
                // se sono admin aggiungo anche l'ultima folder
                $ancestors[] = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                ];
            }

            // gli elementi nell'array vengono memorizzati "a ritroso" dalla cartella corrente fino alla root, quindi li inverto
            $ancestors = array_reverse($ancestors);

            return $ancestors;
        } else {
            // aggiungo la folder corrente al path
            $ancestors[] = [
                'id' => $folder->id,
                'name' => $folder->name,
            ];

            return $this->getAncestorsRecursive($isUserAdmin, $folder->parent, $ancestors);
        }
    }

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
        $subFolders = File::query()
            ->find($folderId)
            ->files
            ->where('is_folder', true);

        if($subFolders->isEmpty())
            return;
        else {
            foreach ($subFolders as $subFolder) {
                $this->getChildrenIdsRecursive($subFolder->id, $childrenIds);
            }
        }
    }
}
