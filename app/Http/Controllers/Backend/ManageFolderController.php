<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\FileManagerHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Folder\FolderResource;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ManageFolderController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $rootFolderId = $user->root_folder_id;

        $originalFolderId = intval($request->input('originalFolderId'));
        $originalFolderPath = $request->input('originalFolderPath');

        if (!$originalFolderId || !$originalFolderPath || $originalFolderPath == '') {
            abort(400, 'Missing request parameters');
        }

        if (!$user->can_write_folder) {
            abort(403);
        }

        // recupero la folder dal db
        $originalFolder = Folder::find($originalFolderId);

        if (!$originalFolder) {
            abort(400, 'Folder not found');
        }

        // per il redirect
        $originalFolderParent = $originalFolder->parent;

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

        return Inertia::render('Backend/FileManager/ManageFolder', [
            'originalFolderId' => $originalFolderId,
            'originalFolderPath' => $originalFolderPath,
            'originalFolderParent' => $originalFolderParent,
            'folderIsRoot' => $folderIsRoot,
            'folders' => $folders,
            'folder' => $folder,
            'parent' => $parent,
        ]);
    }

    public function moveOrCopyFolder(Request $request): RedirectResponse
    {
//        dd($request);
        $user = $request->user();

        if (!$user->can_write_folder) {
            abort(403, 'Unauthorized');
        }

        if (!$request->has('selectedAction') ||
            !$request->has('selectedFolderId') ||
            !$request->has('originalFolderId')) {
            abort(403, 'Missing request parameters');
        }

        $selectedAction = $request->input('selectedAction');
        $selectedFolderId = intval($request->input('selectedFolderId'));
        $originalFolderId = intval($request->input('originalFolderId'));

//        $validate = $request->validate([
//            'selectedAction' => 'required',
//            'selectedFolderId' => 'required',
//            'originalFolderId' => 'required',
//        ]);
//
//        dd($validate);

        $folderFrom = Folder::findOrFail($originalFolderId);
        $folderTo = Folder::findOrFail($selectedFolderId);

        /* controllo che la cartella di destinazione non sia una cartella figlia di quella che si sta tentando di
         * spostare/copiare */
        $folderFromChildrenIds = $folderFrom->getChildrenIds();

        if (in_array($folderTo->id, $folderFromChildrenIds)) {
            return redirect()->back()->withErrors([
                'manageFolderError' => true,
//                'destinationNotAllowed' => true,
                'message' => 'You can\'t move/copy a folder inside one of its children.',
            ]);
        }

        /* se si sta tentando di spostare una cartella che non appartiene all'utente
         * (quindi non è presente nella sua root_folder o nelle sue sottocartelle) ritorno errore */
        $rootFolderId = $user->root_folder_id;

        if ($rootFolderId != null) {
            // se non sono utente admin

            $rootFolder = Folder::with('folders')->find($rootFolderId);
            $rootFolderChildrenIds = $rootFolder->getChildrenIds();

            if (!(in_array($folderFrom->id, $rootFolderChildrenIds))) {
                return redirect()->back()->withErrors([
                    'manageFolderError' => true,
//                    'folderNotOwned' => true,
                    'message' => 'You are not the folder owner. You can\'t move/copy it.',
                ]);
            }
        }

        /* verifico se la cartella che si sta tentando di spostare/copiare
         * esiste già nella cartella di destinazione */
        $folderAlreadyExists = FileManagerHelper::checkFolderExistence($folderFrom->name, $folderTo->id);

        if ($folderAlreadyExists) {
            return redirect()->back()->withErrors([
                'manageFolderError' => true,
                'message' => 'A folder with this name already exists in this path.',
                'subMessage' => 'Please, choose another one.',
            ]);
        } else {
            switch ($selectedAction) {
                case 'move':
                    // il nuovo parent della folderFrom diventa la folderTo
                    $folderFrom->folder_id = $folderTo->id;
                    $folderFrom->save();

                    return redirect()->route('backend.file-manager.index', [
                        'folderId' => $selectedFolderId
                    ])->banner("Folder $folderFrom->name moved correctly");

                case 'copy':
                    $folderFrom->copyFolder($folderTo->id);

                    return redirect()->route('backend.file-manager.index', [
                        'folderId' => $selectedFolderId
                    ])->banner("Folder $folderFrom->name copied correctly");
                default:
                    return redirect()->back();
            }
        }
    }
}
