<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\FileManagerHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\File\FileResource;
use App\Http\Resources\Folder\FolderResource;
use App\Models\Folder;
use App\Models\FileShare;
use App\Models\FolderShare;
use App\Models\StarredFile;
use App\Models\StarredFolder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileManagerController extends Controller
{
    public function newMyFiles(Request $request): InertiaResponse
    {
        $user = $request->user();
        $rootFolderId = $user->root_folder_id;
        $isUserAdmin = (bool) $user->is_admin;
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

            $folders = FolderResource::collection($folders);
        } else {
            /* se è utente normale ritorno la folder di root con le sue cartelle e i suoi file */
            $currentFolder = Folder::query()->findOrFail($folderToOpenId);
            $parent = $currentFolder->parent;

            /* folders */
            $folders = Folder::query()
                ->where('folder_id', $folderToOpenId)
                ->orderBy('name', 'ASC')
                ->get();

            /* files */
            $files = Media::query()
                ->where('model_id', $folderToOpenId)
                ->orderBy('file_name', 'ASC')
                ->get();

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

        return Inertia::render('NewMyFiles', [
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
//        $ancestors = [];
//        $folderId = intval($request->input('folderId'));

//        if ($folderId) {
//            /* è stata selezionata una folder da aprire, ritorno il suo contenuto */
//            $currentFolder = Folder::query()->find($folderId);
//            $ancestors = $currentFolder->getAncestors();
//
//            /* folders */
//            $folders = Folder::query()
//                ->where('folder_id', $folderId)
//                ->orderBy('name', 'ASC')
//                ->get();
//
//            /* files */
//            $files = Media::query()
//                ->where('model_id', $folderId)
//                ->orderBy('file_name', 'ASC')
//                ->get();
//        } else {
//
//        }

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

        return Inertia::render('Favourites', [
            'folders'       => $folders,
            'files'         => $files,
        ]);
    }

    public function newSharedWithMe(Request $request): InertiaResponse
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
            ->select('media.id as id', 'media.file_name as name', 'users.name as owner')
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

        return Inertia::render('NewSharedWithMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
    }

    public function newSharedByMe(Request $request): InertiaResponse
    {
        $user = $request->user();

        $sharedFolders = FolderShare::query()
            ->from('folder_shares as fs')
            ->where('fs.owner_id', $user->id)
            ->join('folders', 'fs.folder_id', '=', 'folders.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->select('fs.id as id', 'folders.id as folderId', 'folders.name as name', 'users.name as username')
            ->orderBy('name', 'ASC')
            ->orderBy('username', 'ASC')
            ->get();

        /* is_favourite ? */
        foreach ($sharedFolders as $sharedFolder) {
            $starred = StarredFolder::query()
                ->where('folder_id', $sharedFolder->folderId)
                ->where('user_id', $user->id)
                ->get();
            if ($starred->isNotEmpty()) {
                $sharedFolder->is_favourite = true;
            } else {
                $sharedFolder->is_favourite = false;
            }
        }

        $sharedFiles = FileShare::query()
            ->from('file_shares as fs')
            ->where('fs.owner_id', $user->id)
            ->join('media', 'fs.file_id', '=', 'media.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->select('fs.id as id', 'media.id as fileId', 'media.file_name as name', 'users.name as username')
            ->orderBy('name', 'ASC')
            ->orderBy('username', 'ASC')
            ->get();

        /* is_favourite ? */
        foreach ($sharedFiles as $sharedFile) {
            $starred = StarredFile::query()
                ->where('file_id', $sharedFile->fileId)
                ->where('user_id', $user->id)
                ->get();

            if ($starred->isNotEmpty()) {
                $sharedFile->is_favourite = true;
            } else {
                $sharedFile->is_favourite = false;
            }
        }

        return Inertia::render('NewSharedByMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
    }

    public function createFolder(Request $request): RedirectResponse
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
                $newFolder->uuid = Str::uuid();
                $newFolder->save();

                return redirect()->back()->with([
                    'message' => "Folder '$newFolderName' created successfully"
                ]);
            } else {
                return redirect()->back()->withErrors([
                    'message' => "Folder '$newFolderName' already exists"
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
                    $newFolder->uuid = Str::uuid();
                    $newFolder->save();

                    return redirect()->back()->with([
                        'message' => "Folder '$newFolderName' created successfully"
                    ]);
                } else {
                    return redirect()->back()->withErrors([
                        'message' => "Folder '$newFolderName' already exists"
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

        foreach ($files as $file) {
            $fileFullName = $file->getClientOriginalName();

            // verifico se all'interno della cartella esiste già un file con lo stesso nome
            $fileAlreadyExists = FileManagerHelper::checkFileExistence($fileFullName, $currentFolderId);

            if (!$fileAlreadyExists) {
                // se non esiste, lo aggiungo normalmente alla cartella corrente
                $currentFolder->addMedia($file)->toMediaCollection('documents');
            } else {
                // se esiste già, aggiungo un timestamp al nome del nuovo file e lo aggiungo alla cartella corrente

                // prendo nome ed estensione del file
                $fileName = pathinfo($fileFullName, PATHINFO_FILENAME);
                $fileExt = pathinfo($fileFullName, PATHINFO_EXTENSION);

                // aggiungo il timestamp e ricreo il nome del file
//                $fileNameTS = $fileName . '-' . time();
                $fileNameTS = $fileName . '-copy';
                $fileFullNameTS = $fileNameTS . '.' . $fileExt;

                $currentFolder->addMedia($file)
                    ->usingFileName($fileFullNameTS)
                    ->usingName($fileNameTS)
                    ->toMediaCollection('documents');
            }
        }

        return redirect()->back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!($user->can_write_folder))
            abort(403);

        $deleteFileIds = $request->input('deleteFileIds');
        $deleteFolderIds = $request->input('deleteFolderIds');

        /* eliminazione dei file */
        if ($deleteFileIds) {
            Media::query()
                ->whereIn('id', $deleteFileIds)
                ->get()
                ->each(fn($file) => $file->delete());
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
            }
        }

        return redirect()->back();
    }

    public function download(Request $request)
    {
        dd($request);
        $user = $request->user();

        $fileIds = $request->input('downloadFileIds');
        $folderIds = $request->input('downloadFolderIds');

//        dd($fileIds, count($fileIds) === 1, $folderIds);


        /* DOWNLOAD FILES */
        if (!$folderIds && $fileIds && count($fileIds) === 1) {
            // ho un solo file da scaricare

            $file = Media::findOrFail($fileIds[0]);

//            dd($file->getPath(), pathinfo($file->getPath()));

//            $dest = 'public/' . $file->getPath();
//
//            dd($dest);

            // il file è il suo percorso intero
            return response()->download($file->getPath(), $file->file_name);
        }


        /* FOLDERS */
//        $folder = Folder::findOrFail($folderId);
//
//        $zip = $folder->getZipFolder();
//
//        return response()->download($zip)->deleteFileAfterSend();
    }

    public function addRemoveFavourites(Request $request): RedirectResponse
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

        return redirect()->back();
    }

    public function share(Request $request): RedirectResponse
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
            if($fileIds) {
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

            return redirect()->back()->with([
                'message' => 'Files shared successfully'
            ]);
        } else {
            return redirect()->back()->withErrors([
                'message' => 'User not found'
            ]);
        }
    }

    public function stopSharing(Request $request): RedirectResponse
    {
        $fileIds = $request->input('stopShareFileIds');
        $folderIds = $request->input('stopShareFolderIds');

        /* eliminazione dei file */
        if ($fileIds) {
            FileShare::query()
                ->whereIn('id', $fileIds)
                ->get()
                ->each(fn($file) => $file->delete());
        }

        /* eliminazione delle folder */
        if ($folderIds) {
            FolderShare::query()
                ->whereIn('id', $folderIds)
                ->get()
                ->each(fn($folder) => $folder->delete());
        }

        return redirect()->back()->with([
            'message' => 'Success'
        ]);
    }

    public function rename(Request $request): RedirectResponse
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

            /* controllo se all'interno della cartella in cui si trova il file esiste già un
             * altro file con lo stesso nome */
            $file = Media::query()->find($fileId);
            $fileFolderId = $file->model_id;

            // mi serve il fullname per fare il controllo dell'esistenza
            $fileExt = pathinfo($file->file_name, PATHINFO_EXTENSION);
            $fileFullName = $newName . '.' . $fileExt;

            $fileAlreadyExists = FileManagerHelper::checkFileExistence($fileFullName, $fileFolderId);

            if ($fileAlreadyExists) {
                return redirect()->back()->withErrors([
                    'message' => 'There is already another file with this name',
                ]);
            }

            // se non esiste, modifico il nome del file selezionato (sia name che file_name)
            $file->name = $newName;
            $file->file_name = $fileFullName;
            $file->save();

            return redirect()->back()->with([
                'message' => 'File renamed correctly'
            ]);
        } else {
            /* RENAME FOLDER */
            $folder = Folder::query()->find($folderId);
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
                    'message' => 'There is already another folder with this name',
                ]);
            }

            // salvo la cartella rinominata
            $folder->name = $newName;
            $folder->save();

            return redirect()->back()->with([
                'message' => 'Folder renamed correctly'
            ]);
        }
    }

    public function copy(Request $request): RedirectResponse
    {
        $fileIds = $request->input('copyFileIds');
        $folderIds = $request->input('copyFolderIds');
        $currentFolderId = intval($request->input('currentFolderId'));
        $currentFolder = Folder::query()->find($currentFolderId);

        if (!$currentFolder) {
            abort(403, 'Missing parameters');
        }

        if ($folderIds) {
            Folder::query()
                ->whereIn('id', $folderIds)
                ->get()
                ->each(function ($folder) {
                    $folder->copyFolder();
                });
        }

        if ($fileIds) {
            Media::query()
                ->whereIn('id', $fileIds)
                ->get()
                ->each(function ($file) use($currentFolder) {
                    $fileExt = pathinfo($file->file_name, PATHINFO_EXTENSION);
                    $newFileName = $file->name . '-copy';

                    $file->name = $newFileName;
                    $file->file_name = $newFileName . '.' . $fileExt;

                    $file->copy($currentFolder, 'documents');
                });
        }

        return redirect()->back()->with([
            'message' => 'Files copied correctly'
        ]);
    }

    public function move()
    {
        dd('move');
    }

    public function getFoldersForMoveModal(Request $request) {
        $user = $request->user();
        $moveFileIds = $request->input('moveFileIds');
        $moveFolderIds = $request->input('moveFolderIds');

        $folders = Folder::query()
            ->where('user_id', $user->id)
            ->whereNotNull('folder_id')
            ->whereNotIn('id', $moveFolderIds)
            ->get();

        return Inertia::render('MoveFilesModalTable', [
            'folders' => $folders,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function myFiles(Request $request): InertiaResponse
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

        return Inertia::render('App/MyFiles', [
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

    public function sharedWithMe(Request $request): InertiaResponse
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

        return Inertia::render('App/SharedWithMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
    }

    public function sharedByMe(Request $request): InertiaResponse
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

        return Inertia::render('App/SharedByMe', [
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

    public function zipFolder(int $folderId): BinaryFileResponse
    {
        $folder = Folder::findOrFail($folderId);

        $zip = $folder->getZipFolder();

        return response()->download($zip)->deleteFileAfterSend();
    }

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


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///  PRIVATE HELPERS
//    private function getZipFolder(): string
//    {
//        // $this è l'oggetto Folder su cui è stato chiamato getZipFolder()
//
//        // primo elemento del path è la cartella corrente
//        $path = array($this->name);
//
//        $zip_file = $this->name . '.zip';
//
//        // creo (o sovrascrivo) l'archivio e lo apro
//        $zipArchive = new ZipArchive();
//        $zipArchive->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
//
//        $this->zipFolderRecursive($this, $zipArchive, $path);
//
//        $zipArchive->close();
//
//        return $zip_file;
//    }
//
//    /**
//     * $path è passato per copia perché per ogni sottocartella il percorso cambia
//     *
//     * @param Folder $folder
//     * @param ZipArchive $zipArchive
//     * @param array $path
//     * @return void
//     */
//    private function zipFolderRecursive(Folder $folder, ZipArchive &$zipArchive, array $path): void
//    {
//        $folders = $folder->folders;
//        $files = $folder->getMedia('documents');
//
//        if ($files->isNotEmpty()) {
//            $this->zipFiles($files, $zipArchive, $path);
//        }
//
//        if($folders->isNotEmpty()) {
//            foreach ($folders as $subFolder) {
//                // copia locale del path per arrivare al file
//                $myPath = $path;
//
//                // aggiungo la cartella corrente al path
//                array_push($myPath, $subFolder->name);
//
//                $this->zipFolderRecursive($subFolder, $zipArchive, $myPath);
//            }
//        }
//
//        if ($folders->isEmpty() && $files->isEmpty()) {
//            // aggiunta di una cartella vuota
//            $zipArchive->addEmptyDir(implode('/', $path));
//        }
//    }
//    private function zipFiles(MediaCollection $files, ZipArchive &$zipArchive, array $path): void
//    {
//        foreach ($files as $file) {
//            $filePath = $file->getPath();
//            $myPath = implode('/', $path) . '/' . $file->file_name;
//
//            $zipArchive->addFile($filePath, $myPath);
//        }
//    }
}
