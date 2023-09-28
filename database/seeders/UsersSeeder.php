<?php

namespace Database\Seeders;

use App\Models\Folder;
use Illuminate\Database\Seeder;
Use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        User::factory(5)
//            ->create()
//            ->each(function ($user) {
//                $user->assignRole('organization_administrator');
//            });

        $user = User::create([
            'name' => 'NM',
            'email' => 'nm@example.com',
            'password' => Hash::make('Value1234!'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        /* creazione root folder dell'utente appena creato */
        $folder = Folder::create([
            'name' => 'nm',
            'user_id' => $user->id,
            'storage_path' => 'nm',
            'uuid' => Str::uuid(),
        ]);

        Storage::makeDirectory($folder->storage_path);
    }
}
