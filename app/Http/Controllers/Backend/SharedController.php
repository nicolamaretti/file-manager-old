<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\FileShare;
use App\Models\Folder;
use App\Models\FolderShare;
use Illuminate\Http\RedirectResponse;
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

        return Inertia::render('Backend/SharedByMe', [
            'folders' => $sharedFolders,
            'files' => $sharedFiles,
        ]);
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
