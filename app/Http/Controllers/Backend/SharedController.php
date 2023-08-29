<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SharedController extends Controller
{
    public function sharedWithMe(Request $request): InertiaResponse
    {
        $user = $request->user();

        /* cerco le folders */
        $sharedFolders = DB::table('folder_shares as fs')
            ->join('folders', 'fs.folder_id', '=', 'folders.id')
            ->join('users', 'fs.user_id', '=', 'users.id')
            ->join('users as users_owner', 'fs.owner_id', '=', 'users_owner.id')
            ->where('folders.user_id', '!=',  $user->id)
            ->where('fs.user_id', $user->id)
            ->select('folders.id as id', 'folders.name as name', 'users_owner.name as owner')
            ->get()
            ->sortBy('folderName');

        /* cerco i files */
        $sharedFiles = DB::table('file_shares as fs')
            ->join('media', 'fs.file_id', '=', 'media.id')
            ->join('users', 'fs.owner_id', '=', 'users.id')
            ->where('fs.user_id', $user->id)
            ->select('media.id as id', 'media.file_name as name', 'users.name as owner')
            ->get()
            ->sortBy('name');

//        dd($sharedFolders, $sharedFiles);

        return Inertia::render('Backend/SharedWithMe', [
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
            ->select('folders.name as foldername', 'users.name as username')
            ->get()
            ->sortBy('foldername');


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
            ->select('media.file_name as filename', 'users.name as username')
            ->get()
            ->sortBy('filename');

        return Inertia::render('Backend/SharedByMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
    }
}
