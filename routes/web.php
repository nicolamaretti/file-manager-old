<?php

use App\Http\Controllers\Backend\ManageFileController;
use App\Http\Controllers\Backend\SharedController;
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

    ////// MY
    Route::get('/dashboard', [FileManagerController::class, 'index'])->name('dashboard');
    Route::post('file-manager/create-root-folder', [FileManagerController::class, 'createRootFolder'])->name('backend.file-manager.create-root-folder');
    Route::post('file-manager/create-folder', [FileManagerController::class, 'createFolder'])->name('backend.file-manager.create-folder');
    Route::post('/file-manager/upload-file', [FileManagerController::class, 'uploadFile'])->name('backend.file-manager.upload-file');
    Route::delete('/file-manager/{folderId}', [FileManagerController::class, 'deleteFolderAndChildren'])->name('backend.file-manager.delete-folder');
    Route::delete('/file-manager/delete-file/{fileId}', [FileManagerController::class, 'deleteFile'])->name('backend.file-manager.delete-file');
    Route::get('/file-manager/download-file/{file}', [FileManagerController::class, 'downloadFile'])->name('backend.file-manager.download-file');
    Route::get('/file-manager/zip-folder/{folder}', [FileManagerController::class, 'zipFolder'])->name('backend.file-manager.zip-folder');
    Route::put('/file-manager/rename-folder/{folderId}', [FileManagerController::class, 'renameFolder'])->name('backend.file-manager.rename-folder');
    Route::put('/file-manager/rename-file/{fileId}', [FileManagerController::class, 'renameFile'])->name('backend.file-manager.rename-file');
//    Route::get('/file-manager/open-file/{fileId}', [FileManagerController::class, 'openFile'])->name('backend.file-manager.open-file');
    Route::post('/file-manager/share-folder/{folderId}', [FileManagerController::class, 'shareFolder'])->name('backend.file-manager.share-folder');
    Route::post('/file-manager/share-file/{fileId}', [FileManagerController::class, 'shareFile'])->name('backend.file-manager.share-file');

    /* copia/spostamento cartelle */
    Route::get('/file-system/manage-folder', [ManageFolderController::class, 'index'])->name('backend.file-system.manage-folder');
    Route::post('/file-system/moveOrCopyFolder', [ManageFolderController::class, 'moveOrCopyFolder'])->name('backend.file-system.move-or-copy-folder');

    /* copia/spostamento files */
    Route::get('/file-system/manage-file', [ManageFileController::class, 'index'])->name('backend.file-system.manage-file');
    Route::post('/file-system/moveOrCopyFile', [ManageFileController::class, 'moveOrCopyFile'])->name('backend.file-system.move-or-copy-file');

    /* shared */
    Route::get('/shared-with-me', [SharedController::class, 'sharedWithMe'])->name('backend.shared-with-me');
    Route::get('/shared-by-me', [SharedController::class, 'sharedByMe'])->name('backend.shared-by-me');
    Route::delete('/shared-by-me/stop-sharing-folder/{folderId}', [SharedController::class, 'stopSharingFolder'])->name('backend.shared-by-me.stop-sharing-folder');
    Route::delete('/shared-by-me/stop-sharing-file/{fileId}', [SharedController::class, 'stopSharingFile'])->name('backend.shared-by-me.stop-sharing-file');
});
