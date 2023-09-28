<?php

use App\Http\Controllers\Backend\ManageFileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Backend\FileController;
use App\Http\Controllers\Backend\ManageFolderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('/');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // NEW
    Route::get('/my-files', [FileController::class, 'myFiles'])->name('my-files');
    Route::get('/favourites', [FileController::class, 'favourites'])->name('favourites');
    Route::get('/shared-with-me', [FileController::class, 'sharedWithMe'])->name('shared-with-me');
    Route::get('/shared-by-me', [FileController::class, 'sharedByMe'])->name('shared-by-me');
    Route::post('/create/folder', [FileController::class, 'createFolder'])->name('create-folder');
    Route::delete('/delete/', [FileController::class, 'delete'])->name('delete');
    Route::post('/upload/', [FileController::class, 'upload'])->name('upload');
    Route::get('/download/', [FileController::class, 'download'])->name('download');
    Route::post('/add-remove-favourites/', [FileController::class, 'addRemoveFavourites'])->name('add-remove-favourites');
    Route::post('/share/', [FileController::class, 'share'])->name('share');
    Route::delete('/share/stop', [FileController::class, 'stopSharing'])->name('stop-sharing');
    Route::post('/copy/', [FileController::class, 'copy'])->name('copy');
    Route::post('/rename/', [FileController::class, 'rename'])->name('rename');
    Route::get('/move/select', [FileController::class, 'selectFoldersToMove'])->name('select-folders-to-move');
    Route::post('/move/', [FileController::class, 'move'])->name('move');
    Route::get('/search/', [FileController::class, 'search'])->name('search');

    ////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////

//    Route::get('/dashboard', [FileController::class, 'myFiles'])->name('dashboard');
    Route::get('/__old-my-files/{folderId?}', [FileController::class, '__oldMyFiles'])->name('__old-my-files');
    Route::post('/create/root-folder', [FileController::class, 'createRootFolder'])->name('folder.create-root');
//    Route::post('/create/folder', [FileController::class, 'createFolder'])->name('folder.create');
//    Route::post('/upload/file', [FileController::class, 'uploadFile'])->name('file.upload');
    Route::delete('/delete/folder/{folderId}', [FileController::class, 'deleteFolderAndChildren'])->name('folder.delete');
    Route::delete('/delete/file/{fileId}', [FileController::class, 'deleteFile'])->name('file.delete');
    Route::get('/download/file/{file}', [FileController::class, 'downloadFile'])->name('file.download');
    Route::get('/zip/{folder}', [FileController::class, 'zipFolder'])->name('folder.zip');
    Route::put('/rename/folder/{folderId}', [FileController::class, 'renameFolder'])->name('folder.rename');
    Route::put('/rename/file/{fileId}', [FileController::class, 'renameFile'])->name('file.rename');
//    Route::get('/file-manager/open-file/{fileId}', [FileController::class, 'openFile'])->name('backend.file-manager.open-file');
    Route::post('/share/folder/{folderId}', [FileController::class, 'shareFolder'])->name('folder.share');
    Route::post('/share/file/{fileId}', [FileController::class, 'shareFile'])->name('file.share');

    /* shared */
    Route::get('/__old-shared-with-me', [FileController::class, '__oldSharedWithMe'])->name('__old-shared-with-me');
    Route::get('/__old-shared-by-me', [FileController::class, '__oldSharedByMe'])->name('__old-shared-by-me');
    Route::delete('/__old-shared-by-me/stop-sharing-folder/{folderId}', [FileController::class, 'stopSharingFolder'])->name('__old-shared-by-me.stop-sharing-folder');
    Route::delete('/__old-shared-by-me/stop-sharing-file/{fileId}', [FileController::class, 'stopSharingFile'])->name('__old-shared-by-me.stop-sharing-file');

    /* copia/spostamento cartelle */
    Route::get('/file-system/manage-folder', [ManageFolderController::class, 'index'])->name('backend.file-system.manage-folder');
    Route::post('/file-system/moveOrCopyFolder', [ManageFolderController::class, 'moveOrCopyFolder'])->name('backend.file-system.move-or-copy-folder');

    /* copia/spostamento files */
    Route::get('/file-system/manage-file', [ManageFileController::class, 'index'])->name('backend.file-system.manage-file');
    Route::post('/file-system/moveOrCopyFile', [ManageFileController::class, 'moveOrCopyFile'])->name('backend.file-system.move-or-copy-file');
});
