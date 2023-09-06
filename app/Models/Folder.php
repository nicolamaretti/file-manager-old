<?php

namespace App\Models;

use App\Interfaces\Recursively;
use App\Interfaces\Zipable;
use App\Traits\RecursivelyTrait;
use App\Traits\ZipableTrait;
use Hamcrest\AssertionError;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Folder extends Model implements HasMedia, Zipable, Recursively
{
    use HasFactory;
    use InteractsWithMedia;
    use ZipableTrait;
    use RecursivelyTrait;

    protected $fillable = [
        'name',
        'user_id',
        'folder_id',
        'uuid',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'folder_id', 'id')->with('folders');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
