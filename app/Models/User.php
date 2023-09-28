<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $table = 'users';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function rootFolder(): HasOne
    {
        return $this->hasOne(Folder::class)
            ->whereNull('folder_id');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class)
            ->whereNotNull('folder_id');
    }

    public function starredFolder(): HasMany
    {
        return $this->HasMany(StarredFolder::class)
            ->where('user_id', Auth::id());
    }

    public function starredMedia(): HasMany
    {
        return $this->HasMany(StarredMedia::class)
            ->where('user_id', Auth::id());
    }

    public function sharedMedia(): HasMany
    {
        return $this->hasMany(MediaShare::class)
            ->where('user_id', Auth::id());
    }

    public function sharedFolders(): HasMany
    {
        return $this->hasMany(FolderShare::class)
            ->where('user_id', Auth::id());
    }
}
