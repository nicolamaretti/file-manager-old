<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Backend\FileController;

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
    Route::get('/my-files', [FileController::class, 'myFiles'])->name('my-files');
    Route::get('/favorites', [FileController::class, 'favorites'])->name('favorites');
    Route::get('/shared-with-me', [FileController::class, 'sharedWithMe'])->name('shared-with-me');
    Route::get('/shared-by-me', [FileController::class, 'sharedByMe'])->name('shared-by-me');
    Route::get('/trash', [FileController::class, 'trash'])->name('trash');
    Route::post('/create/folder', [FileController::class, 'createFolder'])->name('create-folder');
    Route::post('/upload', [FileController::class, 'upload'])->name('upload');
    Route::delete('/delete', [FileController::class, 'delete'])->name('delete');
    Route::get('/download', [FileController::class, 'download'])->name('download');
    Route::put('/restore', [FileController::class, 'restore'])->name('restore');
    Route::delete('/delete-forever', [FileController::class, 'deleteForever'])->name('delete-forever');
    Route::post('/add-remove-favorites', [FileController::class, 'addRemoveFavorites'])->name('add-remove-favorites');
    Route::post('/share', [FileController::class, 'share'])->name('share');
    Route::delete('/share/stop', [FileController::class, 'stopSharing'])->name('stop-sharing');
    Route::post('/copy', [FileController::class, 'copy'])->name('copy');
    Route::post('/rename', [FileController::class, 'rename'])->name('rename');
    Route::get('/move/select', [FileController::class, 'selectFoldersToMove'])->name('select-folders-to-move');
    Route::post('/move', [FileController::class, 'move'])->name('move');
    Route::get('/search', [FileController::class, 'search'])->name('search');
});