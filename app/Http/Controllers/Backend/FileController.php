<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\FileAlreadyExistsException;
use App\Helpers\FileUploaderHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\FileResource;
use App\Http\Resources\FolderResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\SharedResource;
use App\Models\File;
use App\Models\FileShare;
use App\Models\Folder;
use App\Models\FolderShare;
use App\Models\StarredFile;
use App\Models\StarredFolder;
use App\Models\User;
use ErrorException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

class FileController extends Controller
{
    public function myFiles(Request $request): InertiaResponse
    {
        $user = Auth::user();
        $rootFolder = $user->root;

        $rootFolders = null;
        $parent = null;
        $currentFolder = null;
        $files = null;
        $ancestors = [];    // array dei nomi delle cartelle per il breadcrumb
        $folderId = intval($request->input('folderId'));

        /* se non è stata selezionata una folder da aprire ritorno la root folder dell'utente */
        $folderToOpen = $folderId
            ? File::with(['parent', 'files'])->orderBy('name')->find($folderId)
            : $rootFolder;

        if ($user->is_admin && !$folderId) {
            /* se sono admin visualizzo tutte le cartelle root */
            $rootFolders = File::query()
                ->whereNotNull('file_id')
                ->where('is_root', true)
                ->orderBy('name')
                ->get();
            //                ->paginate(10);

            $rootFolders = FileResource::collection($rootFolders);
        } else {
            /* se è utente normale ritorno la folder di root con le sue cartelle e i suoi file */
            $files = FileResource::collection($folderToOpen->files);
            $ancestors = $folderToOpen->getAncestors();
            $currentFolder = FileResource::make($folderToOpen);

            //            /* se un utente sta tentando di accedere ad una cartella che non gli appartiene, ritorno errore */
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

        // dd($ancestors);

        return Inertia::render('App/MyFiles', [
            'currentFolder' => $currentFolder,
            'files' => $files,
            'userIsAdmin' => (bool)$user->is_admin,
            'rootFolders' => $rootFolders,
            'ancestors' => $ancestors,
        ]);
    }

    public function favorites(): InertiaResponse
    {
        $files = StarredFile::getFavorites();
        $files = FileResource::collection($files);

        return Inertia::render('App/Favorites', [
            'files' => $files,
        ]);
    }

    public function sharedWithMe(): InertiaResponse
    {
        $files = FileShare::getSharedWithMe();
        $files = SharedResource::collection($files);

        return Inertia::render('App/SharedWithMe', [
            'files' => $files,
        ]);
    }

    public function sharedByMe(): InertiaResponse
    {
        $files = FileShare::getSharedByMe();
        $files = SharedResource::collection($files);

        return Inertia::render('App/SharedByMe', [
            'files' => $files,
        ]);
    }

    public function trash(): InertiaResponse
    {
        $files = File::query()
            ->onlyTrashed()
            ->where('created_by', Auth::id())
            ->where('deleted_forever', false)
            ->orderBy('name')
            ->get();

        $files = FileResource::collection($files);

        return Inertia::render('App/Trash', [
            'files' => $files,
        ]);
    }

    public function createFolder(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $newFolderName = $request->input('newFolderName');
        $currentFolderId = intval($request->input('currentFolderId'));

        $currentFolder = File::query()->find($currentFolderId);

        if (!$newFolderName || $newFolderName == '') {
            return back()->withErrors([
                'message' => 'Folder name can\'t be empty',
            ]);
        }

        if ($user->is_admin && $currentFolderId === $user->root->id) {
            /* sono admin e voglio creare una root folder */
            $folderAlreadyExists = FileUploaderHelper::checkRootFolderExistence($newFolderName);

            /* se non esiste, cerco di creare una nuova ROOT folder */
            if (!$folderAlreadyExists) {
                $folder = File::create([
                    'name' => $newFolderName,
                    'path' => $newFolderName,
                    'is_folder' => true,
                    'uuid' => Str::uuid(),
                    'created_by' => $user->id,
                ]);

                Storage::makeDirectory($folder->path);

                return to_route('my-files');
            } else {
                return redirect()->back()->withErrors([
                    'message' => "Folder '$newFolderName' already exists. Please select another name."
                ]);
            }
        } else {
            /* creo una folder normale */
            $folderAlreadyExists = FileUploaderHelper::checkFolderExistence($newFolderName, $currentFolderId);

            /* se non esiste, creo una nuova folder normale */
            if (!$folderAlreadyExists) {
                $folder = File::create([
                    'name' => $newFolderName,
                    'path' => $currentFolder->path . "/$newFolderName",
                    'is_folder' => true,
                    'file_id' => $currentFolderId,
                    'uuid' => Str::uuid(),
                    'created_by' => $user->id,
                ]);

                Storage::makeDirectory($folder->path);

                return to_route('my-files', [
                    'folderId' => $currentFolder->id
                ]);
            } else {
                return back()->withErrors([
                    'message' => "Folder '$newFolderName' already exists. Please select another name."
                ]);
            }
        }
    }

    public function upload(Request $request): void
    {
        $files = $request->files->get('files');
        $currentFolderId = intval($request->input('currentFolderId'));

        if (!$files || !$currentFolderId) {
            abort(403, 'Missing parameters');
        }

        $currentFolder = File::query()
            ->where('is_folder', true)
            ->find($currentFolderId);

        foreach ($files as $file) {
            $this->saveFile($file, $currentFolder);
        }
    }

    public function delete(Request $request): void
    {
        $fileIds = $request->input('fileIds');

        File::query()
            ->with('user')
            ->whereIn('id', $fileIds)
            ->get()
            ->each(
                /* @throws AuthorizationException */
                function (File $file) {
                    if ($file->user->id !== Auth::id()) {
                        throw new AuthorizationException('You can\'t delete a file that is not yours');
                    }

                    $file->delete();
                }
            );
    }

    public function restore(Request $request): void
    {
        $fileIds = $request->input('fileIds');

        File::query()
            ->onlyTrashed()
            ->whereIn('id', $fileIds)
            ->get()
            ->each(
                function (File $file) {
                    $file->restore();
                }
            );
    }

    public function deleteForever(Request $request): void
    {
        $fileIds = $request->input('fileIds');

        File::query()
            ->onlyTrashed()
            ->whereIn('id', $fileIds)
            ->get()
            ->each(
                function (File $file) {
                    $file->is_folder
                        ? Storage::deleteDirectory($file->path)
                        : Storage::delete($file->path);

                    $file->deleted_forever = true;
                    $file->save();
                }
            );
    }

    /**
     * @throws ErrorException
     */
    public function download(Request $request)
    {
        $fileIds = $request->get('fileIds');

        if (empty($fileIds)) {
            throw new ErrorException('Please select at least one file to download');
        }

        if (count($fileIds) === 1) {
            /* è stato selezionato solo un file o una folder da scaricare */
            $file = File::query()->with('files')->find($fileIds[0]);

            if ($file->is_folder) {
                $files = $file->files;

                if ($files->isEmpty()) {
                    throw new ErrorException('This folder is empty');
                } else {
                    $zipFile = $file->name . '.zip';
                    $path = array($file->name);

                    $zip = $this->createZip($files, $zipFile, $path);

                    return response()->download($zip)->deleteFileAfterSend();
                }
            } else {
                $pathToFile = Storage::path($file->path);

                return response()->download($pathToFile, $file->name);
            }
        } else {
            /* ho più file da scaricare */

            $zipFile = 'zip.zip';
            $path = array('zip');

            $files = File::query()
                ->whereIn('id', $fileIds)
                ->get();

            $zip = $this->createZip($files, $zipFile, $path);

            return response()->download($zip)->deleteFileAfterSend();
        }
    }

    public function addRemoveFavorites(Request $request): void
    {
        $fileId = intval($request->input('fileId'));

        if ($fileId != 0) {
            /* addRemove file */
            $starred = StarredFile::query()
                ->where('file_id', $fileId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$starred) {
                StarredFile::create([
                    'file_id' => $fileId,
                    'user_id' => Auth::id()
                ]);
            } else {
                $starred->delete();
            }
        }
    }

    public function share(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        $email = $request->input('email');
        $fileIds = $request->input('fileIds');

        if ($email == $currentUser->email) {
            return redirect()->back()->withErrors([
                'message' => 'Invalid email'
            ]);
        }

        $userTo = User::query()
            ->where('email', $email)
            ->first();

        $userToId = data_get($userTo, 'id');

        if ($userToId) {
            foreach ($fileIds as $fileId) {
                $sharedFile = FileShare::query()
                    ->with('file')
                    ->where('file_id', $fileId)
                    ->where('user_id', $userToId)
                    ->first();

                if (!$sharedFile) {
                    $file = File::query()
                        ->with('user')
                        ->find($fileId);

                    if ($file->user->id !== Auth::id()) {
                        return redirect()->back()->withErrors([
                            'error' => 'You can\'t share a file that is not yours.'
                        ]);
                    }

                    FileShare::create([
                        'file_id' => $fileId,
                        'user_id' => $userToId,
                    ]);
                }
            }
            return redirect()->back();
        } else {
            return redirect()->back()->withErrors([
                'error' => 'User not found'
            ]);
        }
    }

    public function stopSharing(Request $request): void
    {
        $fileIds = $request->input('fileIds');

        if ($fileIds) {
            FileShare::query()
                ->whereIn('file_id', $fileIds)
                ->delete();

            /* rimuovo i preferiti associati ai file condivisi */
            StarredFile::query()->whereIn('file_id', $fileIds)->delete();
        }
    }

    /**
     * @throws ErrorException
     */
    public function rename(Request $request): RedirectResponse
    {
        $fileId = intval($request->input('fileId'));
        $newName = $request->input('newName');

        if (!$newName || $newName == '') {
            return back()->withErrors([
                'message' => 'Folder name can\'t be empty',
            ]);
        }

        $file = File::with('parent')
            ->find($fileId);

        try {
            $file->rename($newName);
        } catch (AuthenticationException | FileAlreadyExistsException $exception) {
            throw new ErrorException($exception);
        }

        return back();
    }

    public function copy(Request $request): void
    {
        $fileIds = $request->input('fileIds');

        File::query()
            ->with(['parent', 'files'])
            ->whereIn('id', $fileIds)
            ->each(function (File $file) {
                try {
                    $file->copy();
                } catch (UnauthorizedException | FileAlreadyExistsException $exception) {
                    dd($exception->getMessage());
                }
            });
    }

    public function selectFoldersToMove(Request $request): InertiaResponse
    {
        $fileIds = $request->input('fileIds');
        $currentFolderId = intval($request->input('currentFolderId'));

        $excludedFolderIds = [];

        /* Cerco gli id delle sottocartelle delle cartelle selezionate
         * (non posso muovere una cartella padre in una figlia)
         */
        File::query()
            ->whereIn('id', $fileIds)
            ->get()
            ->map(function (File $file) use (&$excludedFolderIds) {
                $excludedFolderIds[] = $file->file_id;
                $excludedFolderIds[] = $file->id;
                $excludedFolderIds = array_merge($excludedFolderIds, $file->getChildrenIds());
            });

        $excludedFolderIds = array_unique($excludedFolderIds);

        /* estraggo le folder che non sono comprese tra quelle estratte sopra */
        $folders = File::query()
            ->where('created_by', Auth::id())
            ->where('is_folder', true)
            ->whereNotIn('id', $excludedFolderIds)
            ->orderBy('path')
            ->get();

        return Inertia::render('App/MoveFiles', [
            'folders' => $folders,
            'moveFileIds' => $fileIds,
            'currentFolderId' => $currentFolderId
        ]);
    }

    public function move(Request $request): RedirectResponse
    {
        $moveIntoFolderId = intval($request->input('moveIntoFolderId'));
        $fileIds = $request->input('moveFileIds');

        File::query()
            ->whereIn('id', $fileIds)
            ->get()
            ->each(function (File $file) use ($moveIntoFolderId) {
                try {
                    $file->move($moveIntoFolderId);
                } catch (AuthenticationException $exception) {
                    dd($exception->getMessage());
                }
            });

        return to_route('my-files', [
            'folderId' => $moveIntoFolderId
        ]);
    }

    public function search(Request $request): InertiaResponse|RedirectResponse
    {
        $searchValue = $request->input('searchValue');
        $currentPage = $request->input('currentPage');

        if (!$searchValue)
            return redirect($currentPage);

        switch ($currentPage) {
            case '/my-files':
                $files = File::query()
                    ->where('created_by', Auth::id())
                    ->where('name', 'like', "%$searchValue%")
                    ->get();

                $files = FileResource::collection($files);

                return Inertia::render('App/MyFiles', [
                    'files' => $files,
                ]);

            case '/favorites':
                $files = $files = StarredFile::getFavorites();

                // ! NON FUNZIONA
                $files->where('name', 'like', "%$searchValue%");

                $files = FileResource::collection($files);

                return Inertia::render('App/Favorites', [
                    'files' => $files,
                ]);

            case '/shared-with-me':
                $files = FileShare::getSharedWithMe($searchValue);
                $files = SharedResource::collection($files);

                return Inertia::render('App/SharedWithMe', [
                    'files' => $files,
                ]);

            case '/shared-by-me':
                $files = FileShare::getSharedByMe($searchValue);
                $files = SharedResource::collection($files);

                return Inertia::render('App/SharedByMe', [
                    'files' => $files,
                ]);

            default:
                return redirect()->back();
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////
    // PRIVATE FUNCTIONS
    //////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////

    private function saveFile(UploadedFile $file, File $currentFolder): void
    {
        $folderPath = $currentFolder->path;
        $fileName = $file->getClientOriginalName();

        /* verifico se all'interno della cartella esiste già un file con lo stesso nome */
        $fileAlreadyExists = FileUploaderHelper::checkFileExistence($fileName, $currentFolder->id);

        if ($fileAlreadyExists) {
            /* se esiste già, aggiungo un timestamp al nome del file */
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $fileBasename = pathinfo($fileName, PATHINFO_FILENAME);

            $fileName = $fileBasename . '-' . time() . '.' . $fileExtension;
        }

        /* aggiungo il file alla cartella corrente */
        $path = $folderPath . "/$fileName";

        File::create([
            'name' => $fileName,
            'path' => $path,
            'file_id' => $currentFolder->id,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uuid' => Str::uuid(),
            'created_by' => Auth::id(),
        ]);

        Storage::disk('local')->put($path, file_get_contents($file));
    }

    private function createZip(Collection $files, string $zipFile, array $path): string
    {
        $zipArchive = new ZipArchive();

        if ($zipArchive->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($files as $file) {
                if ($file->is_folder) {
                    $this->zipFolder($file, $zipArchive, $path);
                } else {
                    $this->addFileToZip($file, $zipArchive, $path);
                }
            }
        }

        $zipArchive->close();

        return $zipFile;
    }

    private function zipFolder(File $folder, ZipArchive &$zipArchive, array $path): void
    {
        $files = $folder->files;
        $path[] = $folder->name;

        if ($files->isEmpty()) {
            // aggiunta di una cartella vuota
            $zipArchive->addEmptyDir(implode('/', $path));
        } else {
            foreach ($files as $subFile) {
                if ($subFile->is_folder) {
                    $this->zipFolder($subFile, $zipArchive, $path);
                } else {
                    $this->addFileToZip($subFile, $zipArchive, $path);
                }
            }
        }
    }

    private function addFileToZip(File $file, ZipArchive &$zipArchive, array $path): void
    {
        $pathToFile = Storage::path($file->path);
        $zipArchive->addFile($pathToFile, implode('/', $path) . "/$file->name");
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
            $files = MediaResource::collection($files);

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

            $files = MediaResource::collection($files);

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
        $folderAlreadyExists = FileUploaderHelper::checkRootFolderExistence($newRootFolderName);

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
    //            $folderAlreadyExists = FileUploaderHelper::checkRootFolderExistence($newFolderName);
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
    //            $folderAlreadyExists = FileUploaderHelper::checkFolderExistence($newFolderName, $currentFolderId);
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
            ->each(fn ($folder) => $folder->delete());

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

            $folderAlreadyExists = FileUploaderHelper::checkRootFolderExistence($newName);
        } else {
            /* altrimenti controllo se esiste già una cartella che ha lo stesso nome del nome inserito
             * all'interno del parent della cartella selezionata */

            $folderAlreadyExists = FileUploaderHelper::checkFolderExistence($newName, $parent->id);
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

        $fileAlreadyExists = FileUploaderHelper::checkFileExistence($fileFullName, $fileFolderId);

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
