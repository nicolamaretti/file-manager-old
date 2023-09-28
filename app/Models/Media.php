<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    use HasFactory;

    protected $fillable = [
        'model_id',
        'name',
        'file_name',
        'mime_type',
        'size',
        'uuid',
        'disk',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function starred(): HasOne
    {
        return $this->hasOne(StarredMedia::class)
            ->where('media_id', $this->id)
            ->where('user_id', Auth::id());
    }

    public function shared(): HasMany
    {
        return $this->hasMany(MediaShare::class);
    }

    public function getFileSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $this->size > 0 ? floor(log($this->size, 1024)) : 0;

        return number_format($this->size / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
