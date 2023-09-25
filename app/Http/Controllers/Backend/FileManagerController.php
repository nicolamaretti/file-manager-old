<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\FileManagerHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\File\FileResource;
use App\Http\Resources\Folder\FolderResource;
use App\Jobs\UploadFiles;
use App\Models\Folder;
use App\Models\FileShare;
use App\Models\FolderShare;
use App\Models\StarredFile;
use App\Models\StarredFolder;
use App\Models\User;
use http\Env\Response;
use http\Params;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class FileManagerController extends Controller
{
    public function myFiles(Request $request): InertiaResponse
    {
        $user = $request->user();
        $rootFolderId = $user->root_folder_id;
        $isUserAdmin = (bool)$user->is_admin;
        $files = null;
        $parent = null;
        $currentFolder = null;

        // array dei nomi delle cartelle per il breadcrumb
        $ancestors = [];
        $folderId = intval($request->input('folderId'));

        if (!$folderId) {
            /* non è stata selezionata una folder da aprire, quindi ritorno la root folder dell'utente */
            $folderToOpenId = $rootFolderId;
        } else {
            $folderToOpenId = $folderId;
        }

        if ($isUserAdmin && !$folderId) {
            /* se sono admin visualizzo tutte le cartelle root */
            $folders = Folder::query()
                ->whereNull('folder_id')
                ->orderBy('name', 'ASC')
                ->get();
//                ->paginate(10);

            $folders = FolderResource::collection($folders);
        } else {
            /* se è utente normale ritorno la folder di root con le sue cartelle e i suoi file */
            $currentFolder = Folder::query()->findOrFail($folderToOpenId);
            $parent = $currentFolder->parent;

            /* folders */
            $folders = Folder::query()
                ->where('folder_id', $folderToOpenId)
                ->orderBy('name', 'ASC')
//                ->get();
                ->paginate(10);

            /* files */
            $files = Media::query()
                ->where('model_id', $folderToOpenId)
                ->orderBy('file_name', 'ASC')
//                ->get();
                ->paginate(10);

            $currentFolder = FolderResource::make($currentFolder);
            $folders = FolderResource::collection($folders);
            $files = FileResource::collection($files);

            $ancestors = $currentFolder->getAncestors();

            /* se un utente sta tentando di accedere ad una cartella che non gli appartiene, ritorno errore */
//            if (!$isUserAdmin) {
//                // cerco la rootFolder nel db
//                $rootFolder = Folder::query()->find($rootFolderId);
//
//                $rootFolderChildrenIds = $rootFolder->getChildrenIds();
//
//                if (in_array($folderId, $rootFolderChildrenIds)) {
//                    abort(403);
//                }
//            }
        }

        return Inertia::render('App/MyFiles', [
            'currentFolder' => $currentFolder,
            'rootFolderId' => $rootFolderId,
            'isUserAdmin' => $isUserAdmin,
            'folders' => $folders,
            'files' => $files,
            'parent' => $parent,
            'ancestors' => $ancestors,
        ]);
    }

    public function favourites(Request $request): InertiaResponse
    {
        $user = $request->user();

        /* favourite folders */
        $folders = Folder::query()
            ->select('folders.*')
            ->join('starred_folders', 'starred_folders.folder_id', '=', 'folders.id')
            ->where('starred_folders.user_id', $user->id)
            ->orderBy('name', 'ASC')
            ->get();

        /* favourite files */
        $files = Media::query()
            ->select('media.*')
            ->join('starred_files', 'starred_files.file_id', '=', 'media.id')
            ->where('starred_files.user_id', $user->id)
            ->orderBy('file_name', 'ASC')
            ->get();

        $folders = FolderResource::collection($folders);
        $files = FileResource::collection($files);

        return Inertia::render('App/Favourites', [
            'folders' => $folders,
            'files' => $files,
        ]);
    }

    public function sharedWithMe(Request $request): InertiaResponse
    {
        $user = $request->user();

        /* cerco le folders */
        $sharedFolders = FolderShare::query()
            ->from('folder_shares as fs')
            ->join('folders', 'fs.folder_id', '=', 'folders.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->join('users as users_owner', 'fs.owner_id', '=', 'users_owner.id')
            ->where('folders.user_id', '!=', $user->id)
            ->where('fs.user_id', $user->id)
            ->select('folders.id as id', 'folders.name as name', 'users_owner.name as owner')
            ->orderBy('name', 'ASC')
            ->orderBy('owner', 'ASC')
            ->get();

        foreach ($sharedFolders as $sharedFolder) {
            $starred = StarredFolder::query()
                ->where('folder_id', $sharedFolder->id)
                ->where('user_id', $user->id)
                ->get();
            if ($starred->isNotEmpty()) {
                $sharedFolder->is_favourite = true;
            } else {
                $sharedFolder->is_favourite = false;
            }
        }

        /* cerco i files */
        $sharedFiles = FileShare::query()
            ->from('file_shares as fs')
            ->join('media', 'fs.file_id', '=', 'media.id')
            ->join('users', 'fs.owner_id', '=', 'users.id')
            ->where('fs.user_id', $user->id)
            ->select('media.id as id', 'media.file_name as name', 'users.name as owner', 'media.mime_type as mime_type')
            ->orderBy('name', 'ASC')
            ->orderBy('owner', 'ASC')
            ->get();

        foreach ($sharedFiles as $sharedFile) {
            $starred = StarredFile::query()
                ->where('file_id', $sharedFile->id)
                ->where('user_id', $user->id)
                ->get();

            if ($starred->isNotEmpty()) {
                $sharedFile->is_favourite = true;
            } else {
                $sharedFile->is_favourite = false;
            }
        }

        return Inertia::render('App/SharedWithMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
    }

    public function sharedByMe(Request $request): InertiaResponse
    {
        $user = $request->user();

        $sharedFolders = FolderShare::query()
            ->from('folder_shares as fs')
            ->where('fs.owner_id', $user->id)
            ->join('folders', 'fs.folder_id', '=', 'folders.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->select('folders.id as id', 'folders.name as name', 'users.name as username')
            ->orderBy('name', 'ASC')
            ->orderBy('username', 'ASC')
            ->get()
            ->each(function ($sharedFolder) use ($user) {
                $starred = StarredFolder::query()
                    ->where('folder_id', $sharedFolder->id)
                    ->where('user_id', $user->id)
                    ->get();
                if ($starred->isNotEmpty()) {
                    $sharedFolder->is_favourite = true;
                } else {
                    $sharedFolder->is_favourite = false;
                }
            });

        $sharedFiles = FileShare::query()
            ->from('file_shares as fs')
            ->where('fs.owner_id', $user->id)
            ->join('media', 'fs.file_id', '=', 'media.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->select('media.id as id', 'media.file_name as name', 'users.name as username', 'media.mime_type as mime_type')
            ->orderBy('name', 'ASC')
            ->orderBy('username', 'ASC')
            ->get()
            ->each(function ($sharedFile) use ($user) {
                $starred = StarredFile::query()
                    ->where('file_id', $sharedFile->id)
                    ->where('user_id', $user->id)
                    ->get();

                if ($starred->isNotEmpty()) {
                    $sharedFile->is_favourite = true;
                } else {
                    $sharedFile->is_favourite = false;
                }
            });

        return Inertia::render('App/SharedByMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
    }

    public function createFolder(Request $request)
    {
        $user = $request->user();

        if (!($user->can_write_folder))
            abort(403);

        $userId = intval($user->id);
        $newFolderName = $request->input('newFolderName');
        $currentFolderId = intval($request->input('currentFolderId'));

        /* || !$currentFolderId */
        if (!$newFolderName || $newFolderName == '') {
            return redirect()->back()->with([
                'message' => 'Folder name can\'t be empty',
            ]);
        }

        if ($currentFolderId) {
            $folderAlreadyExists = FileManagerHelper::checkFolderExistence($newFolderName, $currentFolderId);

            /* se non esiste, creo una nuova folder normale */
            if (!$folderAlreadyExists) {
                $newFolder = new Folder();
                $newFolder->name = $newFolderName;
                $newFolder->user_id = $userId;
                $newFolder->folder_id = $currentFolderId;
                $newFolder->path = $newFolder->getFullPath();
                $newFolder->uuid = Str::uuid();
                $newFolder->save();

                Storage::makeDirectory($newFolder->path);
            } else {
                return redirect()->back()->withErrors([
                    'message' => "Folder '$newFolderName' already exists. Please select another name."
                ]);
            }
        } else {
            if ($user->is_admin) {
                // sono admin e voglio creare una root folder

                $folderAlreadyExists = FileManagerHelper::checkRootFolderExistence($newFolderName);

                /* se non esiste, cerco di creare una nuova ROOT folder */
                if (!$folderAlreadyExists) {
                    $newFolder = new Folder();
                    $newFolder->name = $newFolderName;
                    $newFolder->user_id = $userId;
                    $newFolder->folder_id = null;
                    $newFolder->is_root_folder = true;
                    $newFolder->path = $newFolder->getFullPath();
                    $newFolder->uuid = Str::uuid();
                    $newFolder->save();

                    Storage::makeDirectory($newFolderName);
                } else {
                    return redirect()->back()->withErrors([
                        'message' => "Folder '$newFolderName' already exists. Please select another name."
                    ]);
                }
            } else {
                // uno user normale non ha i permessi per creare una root folder
                abort(403);
            }
        }
    }

    public function upload(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!($user->can_write_folder))
            abort(403);

        $files = $request->files->get('files');
        $currentFolderId = intval($request->input('currentFolderId'));

        if (!$files || !$currentFolderId) {
            abort(403, 'Missing parameters');
        }

        $currentFolder = Folder::query()->find($currentFolderId);

//        $path = $currentFolder->path;

        foreach ($files as $file) {
//            $media = new Media($file);
//            dd($media);

            UploadFiles::dispatch($file, $currentFolder);
//            $fileFullName = $file->getClientOriginalName();
//
//            // verifico se all'interno della cartella esiste già un file con lo stesso nome
//            $fileAlreadyExists = FileManagerHelper::checkFileExistence($fileFullName, $currentFolderId);
//
//            if (!$fileAlreadyExists) {
//                // se non esiste, lo aggiungo normalmente alla cartella corrente
//
//                Storage::putFileAs($path, $file, $fileFullName);
//
//                $currentFolder->addMedia($file)
//                    ->withCustomProperties(['path' => $path . '/' . $fileFullName])
//                    ->toMediaCollection('documents');
//            } else {
//                // se esiste già, aggiungo "-copy" al nome del nuovo file e lo aggiungo alla cartella corrente
//
//                // prendo nome ed estensione del file
//                $fileName = pathinfo($fileFullName, PATHINFO_FILENAME);
//                $fileExt = pathinfo($fileFullName, PATHINFO_EXTENSION);
//
//                // aggiungo "-copy" e ricreo il nome del file
//                $fileNameCopy = $fileName . '-copy';
//                $fileFullNameCopy = $fileNameCopy . '.' . $fileExt;
//
//                Storage::putFileAs($path, $file, $fileFullNameCopy);
//
//                $currentFolder->addMedia($file)
//                    ->usingFileName($fileFullNameCopy)
//                    ->usingName($fileNameCopy)
//                    ->withCustomProperties(['path' => $path . '/' . $fileFullNameCopy])
//                    ->toMediaCollection('documents');
//            }
        }

        return redirect()->back();
    }

    public function delete(Request $request)
    {
        $user = $request->user();

        if (!($user->can_write_folder))
            abort(403);

        $deleteFileIds = $request->input('deleteFileIds');
        $deleteFolderIds = $request->input('deleteFolderIds');

        /* eliminazione dei file */
        if ($deleteFileIds) {
            $files = Media::query()
                ->whereIn('id', $deleteFileIds)
                ->get();

            foreach ($files as $file) {
                Storage::delete($file->getCustomProperty('path'));

                $file->delete();
            }
        }

        /* eliminazione delle folder */
        if ($deleteFolderIds) {
            foreach ($deleteFolderIds as $id) {
                $folder = Folder::query()->findOrFail($id);

                // recupero gli id delle cartelle figlie (comprende anche l'id della cartella padre)
                $childrenIds = $folder->getChildrenIds();

                Folder::query()
                    ->whereIn('id', $childrenIds)
                    ->get()
                    ->each(fn($folder) => $folder->delete());

                Storage::deleteDirectory($folder->path);
            }
        }
    }

    public function download(Request $request): BinaryFileResponse
    {
        $fileIds = $request->get('fileIds');
        $folderIds = $request->get('folderIds');

//        if (empty($folderIds) && empty($fileIds)) {
//            return redirect()->back();
//        }

        if (empty($folderIds) && count($fileIds) === 1) {
            // ho solo un file da scaricare
            $file = Media::query()->find($fileIds[0]);

            return response()->download($file->getPath(), $file->file_name);
        } else {
            // ho più file da scaricare o una/più folders
            $folders = null;
            $files = null;

            if (!empty($folderIds)) {
                $folders = Folder::query()->whereIn('id', $folderIds)
                    ->with('folders')
                    ->get();
            }

            if (!empty($fileIds)) {
                $files = Media::query()->whereIn('id', $fileIds)->get();
            }

            $zip = $this->createZip($folders, $files);

            return response()->download($zip);
        }
    }

    public function addRemoveFavourites(Request $request): void
    {
        $userId = $request->user()->id;
        $fileId = intval($request->input('fileId'));
        $folderId = intval($request->input('folderId'));

        /* ad ogni request, c'è solo uno tra fileId e folderId */

        if ($fileId != 0) {
            /* addRemove file */

            $starredFile = StarredFile::query()
                ->where('file_id', $fileId)
                ->where('user_id', $userId)
                ->first();

            if (!$starredFile) {
                $newStarredFile = new StarredFile();
                $newStarredFile->file_id = $fileId;
                $newStarredFile->user_id = $userId;
                $newStarredFile->save();
            } else {
                $starredFile->delete();
            }
        }

        if ($folderId != 0) {
            /* addRemove folder */

            $starredFolder = StarredFolder::query()
                ->where('folder_id', $folderId)
                ->where('user_id', $userId)
                ->first();

            if (!$starredFolder) {
                $newStarredFolder = new StarredFolder();
                $newStarredFolder->folder_id = $folderId;
                $newStarredFolder->user_id = $userId;
                $newStarredFolder->save();
            } else {
                $starredFolder->delete();
            }
        }
    }

    public function share(Request $request)
    {
        $currentUser = $request->user();

        $email = $request->input('email');
        $fileIds = $request->input('shareFileIds');
        $folderIds = $request->input('shareFolderIds');

        if ($email == $currentUser->email) {
            return redirect()->back()->withErrors([
                'message' => 'Invalid email'
            ]);
        }

        $user = User::query()->where('email', $email)->first();
        $userId = data_get($user, 'id');

        if ($userId) {
            if ($fileIds) {
                foreach ($fileIds as $fileId) {
                    $sharedFile = FileShare::query()
                        ->where('file_id', $fileId)
                        ->where('user_id', $userId)
                        ->first();

                    if (!$sharedFile) {
                        $newSharedFile = new FileShare();
                        $newSharedFile->file_id = $fileId;
                        $newSharedFile->user_id = $user->id;
                        $newSharedFile->owner_id = $currentUser->id;
                        $newSharedFile->save();
                    }
                }
            }

            if ($folderIds) {
                foreach ($folderIds as $folderId) {
                    $sharedFolder = FolderShare::query()
                        ->where('folder_id', $folderId)
                        ->where('user_id', $userId)
                        ->first();

                    if (!$sharedFolder) {
                        $newSharedFolder = new FolderShare();
                        $newSharedFolder->folder_id = $folderId;
                        $newSharedFolder->user_id = $user->id;
                        $newSharedFolder->owner_id = $currentUser->id;
                        $newSharedFolder->save();
                    }
                }
            }
        } else {
            return redirect()->back()->withErrors([
                'error' => 'User not found'
            ]);
        }
    }

    public function stopSharing(Request $request): void
    {
        $fileIds = $request->input('stopShareFileIds');
        $folderIds = $request->input('stopShareFolderIds');

        /* eliminazione dei file */
        if ($fileIds) {
            FileShare::query()
                ->whereIn('file_id', $fileIds)
                ->get()
                ->each(fn($file) => $file->delete());
        }

        /* eliminazione delle folder */
        if ($folderIds) {
            FolderShare::query()
                ->whereIn('folder_id', $folderIds)
                ->get()
                ->each(fn($folder) => $folder->delete());
        }
    }

    public function rename(Request $request)
    {
        $user = $request->user();
        $folderId = intval($request->input('folderId'));
        $fileId = intval($request->input('fileId'));
        $newName = $request->input('newName');

        if (!$newName || $newName == '') {
            return redirect()->back()->withErrors([
                'message' => 'Folder name can\'t be empty',
            ]);
        }

        /* c'è fileId o folderId, non entrambi */

        if ($fileId != 0) {
            /* RENAME FILE */

            $file = Media::query()->find($fileId);
            $fileFolderId = $file->model_id;
            $fileFolder = Folder::query()->find($fileFolderId);

            /* controllo se all'interno della cartella in cui si trova il file esiste già un
             * altro file con lo stesso nome */
            $fileExt = pathinfo($file->file_name, PATHINFO_EXTENSION);
            $newFileFullName = $newName . '.' . $fileExt;

            $fileAlreadyExists = FileManagerHelper::checkFileExistence($newFileFullName, $fileFolderId);

            if ($fileAlreadyExists) {
                return redirect()->back()->withErrors([
                    'message' => 'A file with this name already exists in this folder. Please choose another one.',
                ]);
            }

            $newPath = $fileFolder->path . '/' . $newFileFullName;

            // se non esiste, modifico la posizione nello storage
            Storage::move($file->getCustomProperty('path'), $newPath);

            // modifico il nome del file selezionato (sia name che file_name) e il path
            $file->name = $newName;
            $file->file_name = $newFileFullName;
            $file->setCustomProperty('path', $newPath);
            $file->save();
        } else {
            /* RENAME FOLDER */
            $folder = Folder::find($folderId);
            $parent = $folder->parent;

            if (!$parent) {
                /* se parent è null significa che si vuole rinominare una root folder,
                 * quindi controllo se esiste già una cartella che ha lo stesso nome del nome inserito */

                if (!$user->is_admin) {
                    return redirect()->back()->withErrors([
                        'message' => 'You don\'t have permissions to rename the selected folder',
                    ]);
                }

                $folderAlreadyExists = FileManagerHelper::checkRootFolderExistence($newName);
            } else {
                /* altrimenti controllo se esiste già una cartella che ha lo stesso nome del nome inserito
                 * all'interno del parent della cartella selezionata */

                $folderAlreadyExists = FileManagerHelper::checkFolderExistence($newName, $parent->id);
            }

            if ($folderAlreadyExists) {
                return redirect()->back()->withErrors([
                    'message' => 'A folder with this name already exists. Please choose another one.',
                ]);
            }

            $oldPath = $folder->path;

            // salvo la cartella rinominata
            $folder->name = $newName;
            $folder->path = $folder->getFullPath();
            $folder->save();

            // salvo la nuova posizione nello storage
            Storage::move($oldPath, $folder->path);
            $this->moveStorageRecursive($folder);

            Storage::deleteDirectory($oldPath);
        }
    }

    public function copy(Request $request): void
    {
        $fileIds = $request->input('copyFileIds');
        $folderIds = $request->input('copyFolderIds');
        $currentFolderId = intval($request->input('currentFolderId'));
        $currentFolder = Folder::query()->find($currentFolderId);

        if (!$currentFolder) {
            abort(403, 'Missing parameters');
        }

        /* copy folders and subfolders/subfiles */
        if ($folderIds) {
            Folder::query()
                ->whereIn('id', $folderIds)
                ->get()
                ->each(function ($folder) {
                    $folder->copyFolder();
                });
        }

        /* duplicate files of the current folder */
        if ($fileIds) {
            Media::query()
                ->whereIn('id', $fileIds)
                ->get()
                ->each(function ($file) use ($currentFolder) {
                    $fileExt = pathinfo($file->file_name, PATHINFO_EXTENSION);
                    $newFileName = $file->name . '-copy';
                    $newFileFullName = $newFileName . '.' . $fileExt;
                    $filePath = $currentFolder->path . '/' . $newFileFullName;

                    $copiedFile = $file->copy($currentFolder, 'documents');
                    $copiedFile->name = $newFileName;
                    $copiedFile->file_name = $newFileFullName;
                    $copiedFile->uuid = Str::uuid();
                    $copiedFile->setCustomProperty('path', $filePath);
                    $copiedFile->save();

                    Storage::putFileAs($currentFolder->path, $copiedFile->getPath(), $newFileFullName);
                });
        }
    }

    public function selectFoldersToMove(Request $request): InertiaResponse
    {
        $user = $request->user();
        $fileIds = $request->input('moveFileIds');
        $folderIds = $request->input('moveFolderIds');
        $currentFolderId = $request->input('currentFolderId');

        if ($currentFolderId) {
            /* se non è null significa che non è una root folder, quindi lo converto in numero */
            $currentFolderId = intval($currentFolderId);
        }

        // escludo la cartella corrente come destinazione
        $excludedFolderIds[] = $currentFolderId;

        if ($folderIds) {
            /* Cerco gli id delle sottocartelle delle cartelle selezionate
             * (non posso muovere una cartella padre in una figlia)
             */
            foreach ($folderIds as $folderId) {
                $folder = Folder::query()->find($folderId);

                $childrenFolderIds = $folder->getChildrenIds();

                $excludedFolderIds = array_merge($excludedFolderIds, $childrenFolderIds);
            }
        }

        $folders = Folder::query()
            ->where('user_id', $user->id)
            ->whereNotIn('id', $excludedFolderIds)
            ->get();

        return Inertia::render('App/MoveFiles', [
            'folders' => $folders,
            'moveFolderIds' => $folderIds,
            'moveFileIds' => $fileIds,
            'currentFolderId' => $currentFolderId
        ]);
    }

    public function move(Request $request): RedirectResponse
    {
        $moveIntoFolderId = intval($request->input('moveIntoFolderId'));
        $fileIds = $request->input('moveFileIds');
        $folderIds = $request->input('moveFolderIds');

//        dd($moveIntoFolderId, $fileIds, $folderIds);

        if ($folderIds) {
            Folder::query()
                ->whereIn('id', $folderIds)
                ->get()
                ->each(function ($folder) use ($moveIntoFolderId) {
                    $oldPath = $folder->path;

                    $folder->folder_id = $moveIntoFolderId;
                    $folder->path = $folder->getFullPath();
                    $folder->save();

                    Storage::move($oldPath, $folder->path);
                    $this->moveStorageRecursive($folder);

                    Storage::deleteDirectory($oldPath);
                });
        }

        if ($fileIds) {
            Media::query()
                ->whereIn('id', $fileIds)
                ->get()
                ->each(function ($file) use ($moveIntoFolderId) {
                    $moveIntoFolder = Folder::query()->find($moveIntoFolderId);

                    $oldPath = $file->getCustomProperty('path');
                    $newPath = $moveIntoFolder->getFullPath() . '/' . $file->file_name;

                    $file->model_id = $moveIntoFolderId;
                    $file->setCustomProperty('path', $newPath);
                    $file->save();

                    Storage::move($oldPath, $newPath);
                });
        }

        return to_route('my-files', [
            'folderId' => $moveIntoFolderId
        ]);
    }

    public function search(Request $request): InertiaResponse|RedirectResponse
    {
        $userId = $request->user()->id;
        $searchValue = $request->input('searchValue');
        $currentPage = $request->input('currentPage');

        $userFolderIds = Folder::query()
            ->select('id')
            ->where('user_id', $userId)
            ->get()
            ->toArray();

        switch ($currentPage) {
            case '/my-files':
                $folders = Folder::query()
                    ->where('user_id', $userId)
                    ->where('name', 'like', "%$searchValue%")
                    ->get();

                $files = Media::query()
                    ->whereIn('model_id', $userFolderIds)
                    ->where('name', 'like', "%$searchValue%")
                    ->get();

                $folders = FolderResource::collection($folders);
                $files = FileResource::collection($files);

                return Inertia::render('App/MyFiles', [
                    'folders' => $folders,
                    'files' => $files,
                ]);

            case '/favourites':
                $folders = Folder::query()
                    ->select('folders.*')
                    ->join('starred_folders as sf', 'sf.folder_id', '=', 'folders.id')
                    ->where('sf.user_id', $userId)
                    ->where('folders.name', 'like', "%$searchValue%")
                    ->orderBy('folders.name', 'ASC')
                    ->get();

                $files = Media::query()
                    ->select('media.*')
                    ->join('starred_files as sf', 'sf.file_id', '=', 'media.id')
                    ->where('sf.user_id', $userId)
                    ->where('media.name', 'like', "%$searchValue%")
                    ->orderBy('media.file_name', 'ASC')
                    ->get();

                $folders = FolderResource::collection($folders);
                $files = FileResource::collection($files);

                return Inertia::render('App/Favourites', [
                    'folders' => $folders,
                    'files' => $files,
                ]);

            case '/shared-with-me':
                $folders = FolderShare::query()
                    ->from('folder_shares as fs')
                    ->join('folders', 'fs.folder_id', '=', 'folders.id')
                    ->join('users', 'fs.user_id', '=', 'users.id')
                    ->join('users as users_owner', 'fs.owner_id', '=', 'users_owner.id')
                    ->where('folders.user_id', '!=', $userId)
                    ->where('fs.user_id', $userId)
                    ->where('folders.name', 'like', "%$searchValue%")
                    ->select('folders.id as id', 'folders.name as name', 'users_owner.name as owner')
                    ->orderBy('name', 'ASC')
                    ->orderBy('owner', 'ASC')
                    ->get()
                    ->each(function ($folder) use ($userId) {
                        $starred = StarredFolder::query()
                            ->where('folder_id', $folder->id)
                            ->where('user_id', $userId)
                            ->get();
                        if ($starred->isNotEmpty()) {
                            $folder->is_favourite = true;
                        } else {
                            $folder->is_favourite = false;
                        }
                    });

                $files = FileShare::query()
                    ->from('file_shares as fs')
                    ->join('media', 'fs.file_id', '=', 'media.id')
                    ->join('users', 'fs.owner_id', '=', 'users.id')
                    ->where('fs.user_id', $userId)
                    ->where('media.name', 'like', "%$searchValue%")
                    ->select('media.id as id', 'media.file_name as name', 'users.name as owner')
                    ->orderBy('name', 'ASC')
                    ->orderBy('owner', 'ASC')
                    ->get()
                    ->each(function ($file) use ($userId) {
                        $starred = StarredFile::query()
                            ->where('file_id', $file->id)
                            ->where('user_id', $userId)
                            ->get();

                        if ($starred->isNotEmpty()) {
                            $file->is_favourite = true;
                        } else {
                            $file->is_favourite = false;
                        }
                    });

                return Inertia::render('App/SharedWithMe', [
                    'folders' => $folders,
                    'files' => $files,
                ]);

            case '/shared-by-me':
                $folders = FolderShare::query()
                    ->from('folder_shares as fs')
                    ->where('fs.owner_id', $userId)
                    ->join('folders', 'fs.folder_id', '=', 'folders.id')
                    ->join('users', 'fs.user_id', '=', 'users.id')
                    ->select('folders.id as id', 'folders.name as name', 'users.name as username')
                    ->where('folders.name', 'like', "%$searchValue%")
                    ->orderBy('name', 'ASC')
                    ->orderBy('username', 'ASC')
                    ->get()
                    ->each(function ($folder) use ($userId) {
                        $starred = StarredFolder::query()
                            ->where('folder_id', $folder->id)
                            ->where('user_id', $userId)
                            ->get();
                        if ($starred->isNotEmpty()) {
                            $folder->is_favourite = true;
                        } else {
                            $folder->is_favourite = false;
                        }
                    });

                $files = FileShare::query()
                    ->from('file_shares as fs')
                    ->where('fs.owner_id', $userId)
                    ->join('media', 'fs.file_id', '=', 'media.id')
                    ->join('users', 'fs.user_id', '=', 'users.id')
                    ->select('media.id as id', 'media.file_name as name', 'users.name as username')
                    ->where('media.name', 'like', "%$searchValue%")
                    ->orderBy('name', 'ASC')
                    ->orderBy('username', 'ASC')
                    ->get()->each(function ($file) use ($userId) {
                        $starred = StarredFile::query()
                            ->where('file_id', $file->id)
                            ->where('user_id', $userId)
                            ->get();

                        if ($starred->isNotEmpty()) {
                            $file->is_favourite = true;
                        } else {
                            $file->is_favourite = false;
                        }
                    });

                return Inertia::render('App/SharedByMe', [
                    'folders' => $folders,
                    'files' => $files,
                ]);

            default:
                return redirect()->back();
        }
    }

    private function createZip(Collection $folders = null, Collection $files = null): string
    {
        $zipFile = 'Zip.zip';

        $zipArchive = new ZipArchive();

        if ($zipArchive->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            if ($files) {
                foreach ($files as $file) {
                    $zipArchive->addFile($file->getPath(), $file->file_name);
                }
            }
            if ($folders) {
                foreach ($folders as $folder) {
                    $path = array($folder->name);

                    $this->zipFolder($folder, $zipArchive, $path);
                }
            }
        }

        $zipArchive->close();

        return $zipFile;
    }

    private function zipFolder(Folder $folder, ZipArchive &$zipArchive, array $path): void
    {
        $folders = $folder->folders;
        $files = $folder->getMedia('documents');

        if ($files->isNotEmpty()) {
            $this->addFilesToZip($files, $zipArchive, $path);
        }

        if ($folders->isNotEmpty()) {
            foreach ($folders as $subFolder) {
                // copia locale del path per arrivare al file
                $myPath = $path;

                // aggiungo la cartella corrente al path
                $myPath[] = $subFolder->name;

                $this->zipFolder($subFolder, $zipArchive, $myPath);
            }
        }

        if ($folders->isEmpty() && $files->isEmpty()) {
            // aggiunta di una cartella vuota
            $zipArchive->addEmptyDir(implode('/', $path));
        }
    }

    private function addFilesToZip(Collection $files, ZipArchive &$zipArchive, array $path): void
    {
        foreach ($files as $file) {
            $filePath = $file->getPath();
            $myPath = implode('/', $path) . '/' . $file->file_name;

            $zipArchive->addFile($filePath, $myPath);
        }
    }

    private function moveStorageRecursive(Folder $currentFolder): void
    {
        $folders = $currentFolder->folders;
        $files = $currentFolder->getMedia('documents');

        if ($files->isNotEmpty()) {
            foreach ($files as $file) {
                $oldPath = $file->getCustomProperty('path');

                $newPath = $currentFolder->path . '/' . $file->file_name;

                $file->setCustomProperty('path', $newPath);
                $file->save();

                Storage::move($oldPath, $newPath);
            }
        }

        if ($folders->isNotEmpty()) {
            foreach ($folders as $folder) {
                $oldPath = $folder->path;
                $newPath = $folder->getFullPath();

                $folder->path = $newPath;
                $folder->save();

                Storage::move($oldPath, $newPath);

                $this->moveStorageRecursive($folder);
            }
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __oldMyFiles(Request $request): InertiaResponse
    {
        $user = $request->user();
        $rootFolderId = $user->root_folder_id;

        $currentFolderId = null;
        $currentFolderName = null;
        $currentFolderFullPath = null;
        $isUserAdmin = $user->can('view-all-level');
        $folders = null;
        $files = null;
        $parent = null;
        $folderIsRoot = true;
        $folder = null;

//        $userOrganizationAdmin = $user->can('view-organization-level');
//        $userDepartment = $user->can('view-department-level');

        // se sono admin visualizzo tutte le cartelle root
        if ($rootFolderId == null) {
            $folders = Folder::whereNull('folder_id')
                ->with('folders')
                ->orderBy('name', 'ASC')
                ->get();

            $folders = FolderResource::collection($folders);
        } else {
            // se è utente normale ritorno la folder di root
            $folder = Folder::with('folders')
                ->findOrFail($rootFolderId);

            // cartelle figlie da visualizzare
            $folders = $folder->folders;
            $folders = $folders->sortBy('name');

            // files da visualizzare
            $files = Media::where('model_id', $folder->id)
                ->orderBy('file_name', 'ASC')
                ->get();

            $folder = FolderResource::make($folder);
            $folders = FolderResource::collection($folders);
            $files = FileResource::collection($files);

            $currentFolderId = $folder->id;
            $currentFolderName = $folder->name;
            $currentFolderFullPath = $folder->fullPath;
        }

        // se la request ha il parametro folderId vuol dire che è stata selezionata una cartella: recupero le sue sottocartelle e i file
        if ($request->has('folderId')) {
            $folderId = intval($request->input('folderId'));

            if ($folderId != $rootFolderId) {
                $folderIsRoot = false;
            }

            $folder = Folder::with('folders')
                ->find($folderId);

            // cartelle figlie da visualizzare
            $folders = $folder->folders->sortBy('name');

            $folder = FolderResource::make($folder);
            $folders = FolderResource::collection($folders);

            $currentFolderId = $folder->id;
            $currentFolderName = $folder->name;
            $currentFolderFullPath = $folder->fullPath;

            $parent = $folder->parent;

            $files = Media::where('model_id', $folderId)
                ->orderBy('file_name', 'ASC')
                ->get();

            $files = FileResource::collection($files);

            // se si sta tentando di accedere ad una cartella che non è presente nella root folder dell'utente o in una delle sue sottocartelle, ritorno errore
            if ($rootFolderId != null) {
                // cerco la rootFolder nel db
                $rootFolder = Folder::with('folders')->find($rootFolderId);

                $rootFolderChildrenIds = $rootFolder->getChildrenIds();

                if (!in_array($folderId, $rootFolderChildrenIds)) {
                    abort(403);
                }
            }
        }

        return Inertia::render('App/__old/__oldMyFiles', [
            'currentFolderId' => $currentFolderId,
            'currentFolderName' => $currentFolderName,
            'currentFolderFullPath' => $currentFolderFullPath,
            'rootFolderId' => $rootFolderId,
            'isUserAdmin' => $isUserAdmin,
            'folders' => $folders,
            'files' => $files,
            'parent' => $parent,
            'folderIsRoot' => $folderIsRoot,
            'folder' => $folder,
        ]);
    }

    public function __oldSharedWithMe(Request $request): InertiaResponse
    {
        $user = $request->user();

        /* cerco le folders */
        $sharedFolders = DB::table('folder_shares as fs')
            ->join('folders', 'fs.folder_id', '=', 'folders.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->join('users as users_owner', 'fs.owner_id', '=', 'users_owner.id')
            ->where('folders.user_id', '!=', $user->id)
            ->where('fs.user_id', $user->id)
            ->select('folders.id as folderId', 'folders.name as folderName', 'users_owner.name as folderOwner')
            ->orderBy('folderName', 'ASC')
            ->orderBy('folderOwner', 'ASC')
            ->get();

//        dd($sharedFolders);

        /* cerco i files */
        $sharedFiles = DB::table('file_shares as fs')
            ->join('media', 'fs.file_id', '=', 'media.id')
            ->join('users', 'fs.owner_id', '=', 'users.id')
            ->where('fs.user_id', $user->id)
            ->select('media.id as fileId', 'media.file_name as fileName', 'users.name as fileOwner')
            ->orderBy('fileName', 'ASC')
            ->orderBy('fileOwner', 'ASC')
            ->get();

//        dd($sharedFolders, $sharedFiles);

        return Inertia::render('App/__old/__oldSharedWithMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
    }

    public function __oldSharedByMe(Request $request): InertiaResponse
    {
        $user = $request->user();

        /* folders condivise dall'utente che fa la richiesta */
        $sharedFolders = DB::table('folder_shares as fs')
            ->join('folders', 'fs.folder_id', '=', 'folders.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->where('folders.user_id', $user->id)
//            ->whereIn('fs.folder_id', $folderIds)
            ->select('fs.id as folderId', 'folders.name as folderName', 'users.name as userName')
            ->orderBy('folderName', 'ASC')
            ->orderBy('userName', 'ASC')
            ->get();


        /* files condivisi dall'utente che fa la richiesta */

        // 1) trovo la root folder dell'utente e tutte le sue figlie
        $userRootFolderId = $user->root_folder_id;
        $userRootFolder = Folder::findOrFail($userRootFolderId);
        $childrenFolderIds = $userRootFolder->getChildrenIds();

        // 2) estraggo i files dell'utente corrente che fanno parte dell'albero di cartelle trovato sopra e che sono is_shared
        $sharedFiles = DB::table('file_shares as fs')
            ->join('media', 'fs.file_id', '=', 'media.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->whereIn('media.model_id', $childrenFolderIds)
            ->select('fs.id as fileId', 'media.file_name as fileName', 'users.name as userName')
            ->orderBy('fileName', 'ASC')
            ->orderBy('userName', 'ASC')
            ->get();

        return Inertia::render('App/__old/__oldSharedByMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
    }

    public function createRootFolder(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!($user->can_write_folder))
            abort(403);

        $userId = intval($user->id);
        $newRootFolderName = $request->input('newRootFolderName');

        if (!$newRootFolderName || $newRootFolderName == '') {
            return redirect()->back()->withErrors([
                'missingParams' => true
            ]);
        }

        // verifico se esiste già una cartella root con lo stesso nome
        $folderAlreadyExists = FileManagerHelper::checkRootFolderExistence($newRootFolderName);

        // se non esiste creo la cartella normalmente
        if (!$folderAlreadyExists) {
            $newFolder = new Folder();
            $newFolder->name = $newRootFolderName;
            $newFolder->user_id = $userId;
            $newFolder->uuid = Str::uuid();
            $newFolder->save();;

            return redirect()->back()->banner("Root folder '$newRootFolderName' created successfully");
        } else {
            return redirect()->back()->withErrors([
                'folderExistsError' => true
            ]);
        }
    }

//    public function createFolder(Request $request): RedirectResponse
//    {
//        $user = $request->user();
//
//        if (!($user->can_write_folder))
//            abort(403);
//
//        $userId = intval($user->id);
//        $newFolderName = $request->input('newFolderName');
//        $currentFolderId = intval($request->input('currentFolderId'));
//
//        /* || !$currentFolderId */
//        if (!$newFolderName || $newFolderName == '') {
//            abort(403, 'Missing parameters');
//        }
//
//        $folderAlreadyExists = null;
//
//        if ($user->is_admin) {
//            // sono admin
//
//            $folderAlreadyExists = FileManagerHelper::checkRootFolderExistence($newFolderName);
//
//            /* se non esiste, cerco di creare una nuova ROOT folder */
//            if (!$folderAlreadyExists) {
//                $newFolder = new Folder();
//                $newFolder->name = $newFolderName;
//                $newFolder->user_id = $userId;
//                $newFolder->folder_id = null;
//                $newFolder->uuid = Str::uuid();
//                $newFolder->save();
//
//                return redirect()->back()->with([
//                    'message' => "Folder '$newFolderName' created successfully"
//                ]);
//            } else {
//                return redirect()->back()->withErrors([
//                    'message' => "Folder '$newFolderName' already exists"
//                ]);
//            }
//        } else {
//            /* sono utente normale */
//
//            $folderAlreadyExists = FileManagerHelper::checkFolderExistence($newFolderName, $currentFolderId);
//
//            /* se non esiste, cerco di creare una nuova folder normale */
//            if (!$folderAlreadyExists) {
//                $newFolder = new Folder();
//                $newFolder->name = $newFolderName;
//                $newFolder->user_id = $userId;
//                $newFolder->folder_id = $currentFolderId;
//                $newFolder->uuid = Str::uuid();
//                $newFolder->save();
//
//                return redirect()->back()->with([
//                    'message' => "Folder '$newFolderName' created successfully"
//                ]);
//            } else {
//                return redirect()->back()->withErrors([
//                    'message' => "Folder '$newFolderName' already exists"
//                ]);
//            }
//        }
//    }

    public function deleteFolderAndChildren(int $folderId, Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!($user->can_write_folder))
            abort(403);

        $folder = Folder::findOrFail($folderId);

        // recupero gli id delle cartelle figlie (comprende anche l'id della cartella padre)
        $childrenIds = $folder->getChildrenIds();

        // elimino la cartella, le sottocartelle e tutti i file a loro associati
//        $deleted = Folder::find(123456789);
        $deleted = Folder::whereIn('id', $childrenIds)
            ->get()
            ->each(fn($folder) => $folder->delete());

        if ($deleted) {
            return redirect()->back()->banner("Folder '$folder->name' deleted successfully");
        } else {
            return redirect()->back()->withErrors([
                'folderDeletionError' => true,
            ]);
        }
    }

    public function deleteFile(string $fileId, Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!($user->can_write_folder)) {
            abort(403);
        }

        $file = Media::find($fileId);
//        $file = Media::find(33333);

        if ($file) {
            $file->delete();

            return redirect()->back()->banner("File '$file->file_name' deleted succesfully");
        } else {
            return redirect()->back()->withErrors([
                'fileDeletionError' => true,
            ]);
        }
    }

    public function downloadFile(int $fileId): BinaryFileResponse
    {
        $file = Media::findOrFail($fileId);
//        $file = Media::query()->findOrFail(270);
//        dd($file);

        // il file è il suo percorso intero
        return response()->download($file->getPath(), $file->file_name);
    }

//    public function openFile(int $fileId): void
//    {
//        $file = Media::where('id', $fileId)->first();
//        $filePath = $file->getPath();
//        $filePath = str_replace('/', '\\', $filePath);
//
//        try {
//            $process = new Process(['start', $filePath]);
//            $process->run();
//
//            if (!$process->isSuccessful()) {
//                throw new RuntimeException($process->getErrorOutput());
//            }
//        } catch (Exception $exception) {
//            dd($exception);
//        }
//    }

//    public function zipFolder(int $folderId): BinaryFileResponse
//    {
//        $folder = Folder::findOrFail($folderId);
//
//        $zip = $folder->getZipFolder();
//
//        return response()->download($zip)->deleteFileAfterSend();
//    }

    public function renameFolder(int $folderId, Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->can_write_folder) {
            abort(403);
        }

        // nella request deve esserci la voce 'newName'
        $newName = $request->newName;

        if (!$newName || $newName == '') {
            return redirect()->back()->withErrors([
                'missingName' => true,
            ]);
        }

        $folder = Folder::find($folderId);
        $parent = $folder->parent;
        $folderAlreadyExists = null;

        if (!$parent) {
            /* se parent è null significa che si vuole rinominare una root folder,
             * quindi controllo se esiste già una cartella che ha lo stesso nome del nome inserito */

            $folderAlreadyExists = FileManagerHelper::checkRootFolderExistence($newName);
        } else {
            /* altrimenti controllo se esiste già una cartella che ha lo stesso nome del nome inserito
             * all'interno del parent della cartella selezionata */

            $folderAlreadyExists = FileManagerHelper::checkFolderExistence($newName, $parent->id);
        }

        if ($folderAlreadyExists) {
            return redirect()->back()->withErrors([
                'folderAlreadyExists' => true,
            ]);
        }

        // salvo la cartella rinominata
        $folder->name = $newName;
        $folder->save();

        return redirect()->back();
    }

    public function renameFile(int $fileId, Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->can_write_folder) {
            abort(403);
        }

        // nella request deve esserci la voce 'newName'
        $newName = $request->newName;

        if (!$newName || $newName == '') {
            return redirect()->back()->withErrors([
                'missingName' => true,
            ]);
        }

        /* controllo se all'interno della cartella in cui si trova il file esiste già un
         * altro file con lo stesso nome */
        $file = Media::find($fileId);
        $fileFolderId = $file->model_id;

        // mi serve il fullname per fare il controllo dell'esistenza
        $fileExt = pathinfo($file->file_name, PATHINFO_EXTENSION);
        $fileFullName = $newName . '.' . $fileExt;

        $fileAlreadyExists = FileManagerHelper::checkFileExistence($fileFullName, $fileFolderId);

        if ($fileAlreadyExists) {
            return redirect()->back()->withErrors([
                'fileAlreadyExists' => true,
            ]);
        }

        // se non esiste, modifico il nome del file selezionato (sia name che file_name)
        $file->name = $newName;
        $file->file_name = $fileFullName;
        $file->save();

        return redirect()->back();
    }

    /** Condivisione di una cartella con un utente, specificandone la mail che viene passata tramite la $request
     * @param int $folderId
     * @param Request $request
     * @return RedirectResponse
     */
    public function shareFolder(int $folderId, Request $request): RedirectResponse
    {
        $currentUser = $request->user();

        // nella request deve esserci la voce 'email'
        $email = $request->email;

        if (!$email || $email == '') {
            return redirect()->back()->withErrors([
                'missingEmail' => true,
            ]);
        } else if ($email == $currentUser->email) {
            return redirect()->back()->withErrors([
                'invalidEmail' => true,
            ]);
        }

        $user = User::where('email', $email)->first();
        $userId = data_get($user, 'id');

        if ($userId) {
            // controllo se l'utente ha già condiviso la stessa cartella con lo stesso utente
            $sharedFolderAlreadyExists = DB::table('folder_shares')
                ->where([
                    'folder_id' => $folderId,
                    'user_id' => $userId
                ])->get();

            if ($sharedFolderAlreadyExists->isNotEmpty()) {
                return redirect()->back()->dangerBanner('You already shared this folder with this user');
            }

            $newSharedFolder = new FolderShare();
            $newSharedFolder->folder_id = $folderId;
            $newSharedFolder->user_id = $user->id;
            $newSharedFolder->owner_id = $currentUser->id;
            $newSharedFolder->save();

            return redirect()->back()->banner('Folder shared successfully');
        } else {
            return redirect()->back()->dangerBanner('User not found');
        }
    }

    /** Condivisione di un file con un utente, specificandone la mail che viene passata tramite la $request
     * @param int $fileId
     * @param Request $request
     * @return RedirectResponse
     */
    public function shareFile(int $fileId, Request $request): RedirectResponse
    {
        $currentUser = $request->user();

        // nella request deve esserci la voce 'email'
        $email = $request->email;

        if (!$email || $email == '') {
            return redirect()->back()->withErrors([
                'missingEmail' => true,
            ]);
        } else if ($email == $currentUser->email) {
            return redirect()->back()->withErrors([
                'invalidEmail' => true,
            ]);
        }

        $user = User::where('email', $email)->first();
        $userId = data_get($user, 'id');

        if ($userId) {
            // controllo se l'utente ha già condiviso la stessa cartella con lo stesso utente
            $sharedFileAlreadyExists = DB::table('file_shares')
                ->where([
                    'file_id' => $fileId,
                    'user_id' => $userId
                ])->get();

            if ($sharedFileAlreadyExists->isNotEmpty()) {
                return redirect()->back()->dangerBanner('You already shared this file with this user');
            }

            $newSharedFile = new FileShare();
            $newSharedFile->file_id = $fileId;
            $newSharedFile->user_id = $user->id;
            $newSharedFile->owner_id = $currentUser->id;
            $newSharedFile->save();

            return redirect()->back()->banner('File shared successfully');
        } else {
            return redirect()->back()->dangerBanner('User not found');
        }
    }

    public function stopSharingFolder(int $folderId): RedirectResponse
    {
//        $folder = DB::table('folder_shares as fs')
//            ->join('folders', 'folders.id', '=', 'fs.folder_id')
//            ->join('users', 'users.id', '=', 'fs.user_id')
//            ->where('fs.id', $folderId)
//            ->select('folders.name as folderName', 'users.name as userName')
//            ->first();

        $folder = FolderShare::find($folderId);

        if ($folder) {
            $folder->delete();

            return redirect()->back();
        } else {
            return redirect()->back()->dangerBanner('An error occurred when trying to stop sharing this folder');
        }
    }

    public function stopSharingFile(int $fileId): RedirectResponse
    {
//        $folder = DB::table('folder_shares as fs')
//            ->join('folders', 'folders.id', '=', 'fs.folder_id')
//            ->join('users', 'users.id', '=', 'fs.user_id')
//            ->where('fs.id', $folderId)
//            ->select('folders.name as folderName', 'users.name as userName')
//            ->first();

        $file = FileShare::find($fileId);

        if ($file) {
            $file->delete();

            return redirect()->back();
        } else {
            return redirect()->back()->dangerBanner('An error occurred when trying to stop sharing this file');
        }
    }
}
