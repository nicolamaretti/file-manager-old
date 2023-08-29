<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolderShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'user_id',
    ];
}
