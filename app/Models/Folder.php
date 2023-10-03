<?php

namespace App\Models;

use App\Interfaces\Recursively;
use App\Interfaces\Zipable;
use App\Traits\RecursivelyTrait;
use App\Traits\ZipableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Folder extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'user_id',
        'folder_id',
        'storage_path',
        'uuid',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
        $this->addMediaCollection('files');
        $this->addMediaCollection('trash');
    }

    public function isRoot(): bool
    {
        return $this->folder_id === null;
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection_name', 'files');
    }

    public function trash(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection_name', 'trash');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function starred(): HasOne
    {
        return $this->hasOne(StarredFolder::class)
            ->where('folder_id', $this->id)
            ->where('user_id', Auth::id());
    }

    public function shared(): HasMany
    {
        return $this->hasMany(FolderShare::class);
    }
}
