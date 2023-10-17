<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getSharedByMe(): Collection
    {
        return FileShare::query()
            ->with(['user', 'file.user'])
            ->whereRelation('file.user', 'id', Auth::id())
            ->get()
            ->map(function (FileShare $sharedFile) {
                $file['id'] = $sharedFile->file->id;
                $file['name'] = $sharedFile->file->name;
                $file['is_folder'] = $sharedFile->file->is_folder;
                $file['is_favorite'] = !!$sharedFile->file->starred;
                $file['mime_type'] = $sharedFile->file->mime_type;
                $file['shared_with'] = $sharedFile->user->name;
                $file['owner'] = '';
                return $file;
            })
            ->sortBy(['name', 'shared_with']);
    }

    public static function getSharedWithMe(): Collection
    {
        return FileShare::query()
            ->where('user_id', Auth::id())
            ->with(['file', 'file.user'])
            ->whereRelation('file', 'created_by', '!=', Auth::id())
            ->get()
            ->map(function (FileShare $sharedFile) {
                $file['id'] = $sharedFile->file->id;
                $file['name'] = $sharedFile->file->name;
                $file['is_folder'] = $sharedFile->file->is_folder;
                $file['is_favorite'] = !!$sharedFile->file->starred;
                $file['mime_type'] = $sharedFile->file->mime_type;
                $file['owner'] = $sharedFile->file->user->name;
                $file['shared_with'] = '';
                return $file;
            })
            ->sortBy(['name', 'owner']);
    }
}
