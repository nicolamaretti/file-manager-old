<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StarredFile extends Model
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

    public static function getFavorites(): Collection
    {
        return StarredFile::query()
            ->with('file')
            ->where('user_id', Auth::id())
            ->get()
            ->map(function (StarredFile $starredFile) {
                return $starredFile->file;
            })
            ->sortBy('name');
    }
}
