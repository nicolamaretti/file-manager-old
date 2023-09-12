<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_name',
        'size',
    ];

    public function starred() {
        return $this->hasOne(StarredFile::class, 'file_id', 'id')
            ->where('user_id', Auth::id());
    }
}
