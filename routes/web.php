<?php

use App\Http\Controllers\Backend\ManageFileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Backend\FileManagerController;
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
    Route::get('/newMyFiles', [FileManagerController::class, 'newMyFiles'])->name('newMyFiles');
    Route::get('/newSharedWithMe', [FileManagerController::class, 'newSharedWithMe'])->name('newSharedWithMe');
    Route::get('/newSharedByMe', [FileManagerController::class, 'newSharedByMe'])->name('newSharedByMe');
    Route::delete('/delete/', [FileManagerController::class, 'deleteFilesAndFolders'])->name('delete');

//    Route::get('/dashboard', [FileManagerController::class, 'myFiles'])->name('dashboard');
    Route::get('/my-files/{folderId?}', [FileManagerController::class, 'myFiles'])->name('my-files');
    Route::post('/create/root-folder', [FileManagerController::class, 'createRootFolder'])->name('folder.create-root');
    Route::post('/create/folder', [FileManagerController::class, 'createFolder'])->name('folder.create');
    Route::post('/upload/file', [FileManagerController::class, 'uploadFile'])->name('file.upload');
    Route::delete('/delete/folder/{folderId}', [FileManagerController::class, 'deleteFolderAndChildren'])->name('folder.delete');
    Route::delete('/delete/file/{fileId}', [FileManagerController::class, 'deleteFile'])->name('file.delete');
    Route::get('/download/file/{file}', [FileManagerController::class, 'downloadFile'])->name('file.download');
    Route::get('/zip/{folder}', [FileManagerController::class, 'zipFolder'])->name('folder.zip');
    Route::put('/rename/folder/{folderId}', [FileManagerController::class, 'renameFolder'])->name('folder.rename');
    Route::put('/rename/file/{fileId}', [FileManagerController::class, 'renameFile'])->name('file.rename');
//    Route::get('/file-manager/open-file/{fileId}', [FileManagerController::class, 'openFile'])->name('backend.file-manager.open-file');
    Route::post('/share/folder/{folderId}', [FileManagerController::class, 'shareFolder'])->name('folder.share');
    Route::post('/share/file/{fileId}', [FileManagerController::class, 'shareFile'])->name('file.share');

    /* shared */
    Route::get('/shared-with-me', [FileManagerController::class, 'sharedWithMe'])->name('shared-with-me');
    Route::get('/shared-by-me', [FileManagerController::class, 'sharedByMe'])->name('shared-by-me');
    Route::delete('/shared-by-me/stop-sharing-folder/{folderId}', [FileManagerController::class, 'stopSharingFolder'])->name('shared-by-me.stop-sharing-folder');
    Route::delete('/shared-by-me/stop-sharing-file/{fileId}', [FileManagerController::class, 'stopSharingFile'])->name('shared-by-me.stop-sharing-file');

    /* copia/spostamento cartelle */
    Route::get('/file-system/manage-folder', [ManageFolderController::class, 'index'])->name('backend.file-system.manage-folder');
    Route::post('/file-system/moveOrCopyFolder', [ManageFolderController::class, 'moveOrCopyFolder'])->name('backend.file-system.move-or-copy-folder');

    /* copia/spostamento files */
    Route::get('/file-system/manage-file', [ManageFileController::class, 'index'])->name('backend.file-system.manage-file');
    Route::post('/file-system/moveOrCopyFile', [ManageFileController::class, 'moveOrCopyFile'])->name('backend.file-system.move-or-copy-file');
});
