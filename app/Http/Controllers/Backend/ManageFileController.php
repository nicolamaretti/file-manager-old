<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\FileManagerHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ManageFileController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $rootFolderId = $user->root_folder_id;

        $originalFileId = intval($request->input('originalFileId'));
        $originalFilePath = $request->input('originalFileFullPath');
        $originalFolderId = intval($request->input('originalFolderId'));

        if (!$originalFileId || !$originalFolderId || !$originalFilePath || $originalFilePath == '') {
            abort(400, 'Missing route parameters');
        }

        if (!$user->can_write_folder) {
            abort(403);
        }

        // recupero la folder dal db
        $originalFolder = Folder::find($originalFolderId);

        if (!$originalFolder) {
            abort(400, 'Folder not found');
        }

        $folders = null;
        $folderIsRoot = true;
        $folder = null;
        $parent = null;

        /* RICOSTRUISCO L'ALBERO DELLE CARTELLE CHE PERMETTE ALL'UTENTE DI SELEZIONARE UNA CARTELLA
         * PER SPOSTARE/COPIARE LA CARTELLA CORRENTE */

        if ($rootFolderId == null) {
            // se è utente admin inizialmente ritorno tutte le folders che non hanno padre

            $folders = Folder::whereNull('folder_id')
                ->with('folders')
                ->orderBy('name', 'ASC')
                ->get();

            $folders = FolderResource::collection($folders);
        } else {
            // se è utente normale, inizialmente ritorno la sua folder root
            $folder = Folder::findOrFail($rootFolderId);

            $folder = FolderResource::make($folder);
        }

        /* se la request ha il parametro folderId vuol dire che l'utente sta navigando tra le cartelle:
         * recupero le sottocartelle di quella selezionata, tranne lei stessa */
        if ($request->has('folderId')) {
            $folderIsRoot = false;

            $folderId = $request->input('folderId');

            $folder = Folder::with(['folders' => function ($query) use ($originalFolderId) {
                $query->where('id', '<>', $originalFolderId);
            }])->find($folderId);

            $folders = $folder->folders;
            $folders = $folders->sortBy('name');

            $parent = $folder->parent;

            /* se si sta tentando di spostare una cartella che non appartiene all'utente
             * (quindi non è presente nella sua root_folder o nelle sue sottocartelle) ritorno errore */
            if ($rootFolderId != null) {
                // se non sono utente admin

                $rootFolder = Folder::with('folders')->find($rootFolderId);
                $rootFolderChildrenIds = $rootFolder->getChildrenIds();

                if (!(in_array($folder->id, $rootFolderChildrenIds))) {
                    abort(403);
                }
            }

            $folders = FolderResource::collection($folders);
        }

        return Inertia::render('App/__old/ManageFile', [
            'originalFileId' => $originalFileId,
            'originalFilePath' => $originalFilePath,
            'originalFolderId' => $originalFolderId,
            'folderIsRoot' => $folderIsRoot,
            'folders' => $folders,
            'folder' => $folder,
            'parent' => $parent,
        ]);
    }

    public function moveOrCopyFile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $rootFolderId = $user->root_folder_id;

        if (!$user->can_write_folder) {
            abort(403, 'Unauthorized');
        }

        if (!$request->has('selectedAction') ||
            !$request->has('selectedFileId') ||
            !$request->has('selectedFolderId')) {
            abort(403, 'Missing request parameters');
        }

        $selectedAction = $request->input('selectedAction');
        $selectedFileId = intval($request->input('selectedFileId'));
        $selectedFolderId = intval($request->input('selectedFolderId'));

        $file = Media::find($selectedFileId);
        $folderTo = Folder::find($selectedFolderId);

        /* controllo che la cartella selezionata per lo spostamento sia figlia di quella di root
         * dell'utente (se presente, altrimenti sono admin) */
        if ($rootFolderId != null) {
            $rootFolder = Folder::with('folders')->find($rootFolderId);

            $rootFolderChildrenIds = $rootFolder->getChildrenIds();

            if (!in_array($selectedFolderId, $rootFolderChildrenIds)) {
                abort(403, 'You don\'t have permission to access this folder.');
            }
        }

        $fileFullName = $file->file_name;

        // verifico se all'interno della cartella di destinazione esiste già un file con lo stesso nome
        $fileAlreadyExists = FileManagerHelper::checkFileExistence($fileFullName, $selectedFolderId);

        if ($fileAlreadyExists) {
            return redirect()->back()->withErrors([
                'manageFileError' => true,
                'message' => 'A file with this name already exists in this path.',
            ]);
        } else {
            switch ($selectedAction) {
                case 'move':
                    $file->move($folderTo, 'documents');

                    return redirect()->route('backend.file-manager.index', [
                        'folderId' => $selectedFolderId
                    ])->banner("File $file->name moved correctly");

                case 'copy':
                    $file->copy($folderTo, 'documents');

                    return redirect()->route('backend.file-manager.index', [
                        'folderId' => $selectedFolderId
                    ])->banner("Folder $file->name copied correctly");
                default:
                    return redirect()->back();
            }
        }
    }
}
