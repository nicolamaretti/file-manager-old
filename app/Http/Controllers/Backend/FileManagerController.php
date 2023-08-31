<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\FileManagerHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\File\FileResource;
use App\Http\Resources\Folder\FolderResource;
use App\Models\Folder;
use App\Models\FileShare;
use App\Models\FolderShare;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class FileManagerController extends Controller
{
    public function index(Request $request): InertiaResponse
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
            $files = $files = Media::where('model_id', $folder->id)
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

        return Inertia::render('Dashboard', [
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

            return redirect()->back()->banner("Root folder $newRootFolderName created successfully");
        } else {
            return redirect()->back()->withErrors([
                'folderExistsError' => true
            ]);
        }
    }

    public function createFolder(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!($user->can_write_folder))
            abort(403);

        $userId = intval($user->id);
        $newFolderName = $request->input('newFolderName');
        $currentFolderId = intval($request->input('currentFolderId'));

        if (!$newFolderName || $newFolderName == '' || !$currentFolderId) {
            abort(403, 'Missing parameters');
        }

        $folderAlreadyExists = FileManagerHelper::checkFolderExistence($newFolderName, $currentFolderId);

        if (!$folderAlreadyExists) {
            $newFolder = new Folder();
            $newFolder->name = $newFolderName;
            $newFolder->user_id = $userId;
            $newFolder->folder_id = $currentFolderId;
            $newFolder->uuid = Str::uuid();
            $newFolder->save();

            return redirect()->back()->banner("Folder '$newFolderName' created successfully");
        } else {
            return redirect()->back()->withErrors([
                'folderExistsError' => true
            ]);
        }
    }

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

    public function uploadFile(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!($user->can_write_folder))
            abort(403);

//        $validated = $request->validate([
//            'file' => 'required|file',
//            'currentFolderId' => 'required'
//        ]);

        /* dopo il check $validated sono sicuro di avere sia 'file' che 'currentFolderId' */

        $file = $request->files->get('file');
        $currentFolderId = intval($request->input('currentFolderId'));

        if (!$file || !$currentFolderId) {
            abort(403, 'Missing parameters');
        }

        $currentFolder = Folder::find($currentFolderId);

        $fileFullName = $file->getClientOriginalName();

        // verifico se all'interno della cartella esiste già un file con lo stesso nome
        $fileAlreadyExists = FileManagerHelper::checkFileExistence($fileFullName, $currentFolderId);

        if (!$fileAlreadyExists) {
            // se non esiste, lo aggiungo normalmente alla cartella corrente
            $currentFolder->addMedia($file)->toMediaCollection('documents');

            return redirect()->back()->banner("File '$fileFullName' uploaded successfully");
        } else {
            // se esiste già, aggiungo un timestamp al nome del nuovo file e lo aggiungo alla cartella corrente

            // prendo nome ed estensione del file
            $fileName = pathinfo($fileFullName, PATHINFO_FILENAME);
            $fileExt = pathinfo($fileFullName, PATHINFO_EXTENSION);

            // aggiungo il timestamp e ricreo il nome del file
            $fileNameTS = $fileName . '-' . time();
            $fileFullNameTS = $fileNameTS . '.' . $fileExt;

            $currentFolder->addMedia($file)
                ->usingFileName($fileFullNameTS)
                ->usingName($fileNameTS)
                ->toMediaCollection('documents');

            return redirect()->back()->banner("File '$fileFullNameTS' uploaded successfully");
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
                    'user_id'   => $userId
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
                    'user_id'   => $userId
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
}
