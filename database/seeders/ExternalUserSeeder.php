<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ExternalUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'ExternalUser',
            'email' => 'external@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        /* creazione root folder dell'utente appena creato */
        $folder = File::create([
            'name' => strtolower($user->name),
            'path' => '/home/' . strtolower($user->name),
            'is_folder' => true,
            'file_id' => 1,     // home folder
            'is_root' => true,
            'created_by' => $user->id,
            'uuid' => Str::uuid(),
        ]);

        Storage::makeDirectory("home/$folder->name");
    }
}
