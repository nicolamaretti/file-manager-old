<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StarredFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'user_id',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
