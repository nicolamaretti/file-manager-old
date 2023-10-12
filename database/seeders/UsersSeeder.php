<?php

namespace Database\Seeders;

use App\Models\File;
use Illuminate\Database\Seeder;
use App\Models\User;
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

        // NM
        $user = User::create([
            'name' => 'NM',
            'email' => 'nm@example.com',
            'password' => Hash::make('Value1234!'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        /* creazione root folder dell'utente appena creato */
        $folder = File::create([
            'name' => strtolower($user->name),
            'path' => 'home/' . strtolower($user->name),
            'is_folder' => true,
            'file_id' => 1,     // home folder
            'is_root' => true,
            'created_by' => $user->id,
            'uuid' => Str::uuid(),
        ]);

        Storage::makeDirectory($folder->path);

        // BetaTester
        $user = User::create([
            'name' => 'BetaTester',
            'email' => 'betatester@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        /* creazione root folder dell'utente appena creato */
        $folder = File::create([
            'name' => strtolower($user->name),
            'path' => 'home/' . strtolower($user->name),
            'is_folder' => true,
            'file_id' => 1,     // home folder
            'is_root' => true,
            'created_by' => $user->id,
            'uuid' => Str::uuid(),
        ]);

        Storage::makeDirectory($folder->path);

        // Test
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        /* creazione root folder dell'utente appena creato */
        $folder = File::create([
            'name' => strtolower($user->name),
            'path' => 'home/' . strtolower($user->name),
            'is_folder' => true,
            'file_id' => 1,     // home folder
            'is_root' => true,
            'created_by' => $user->id,
            'uuid' => Str::uuid(),
        ]);

        Storage::makeDirectory($folder->path);
    }
}
