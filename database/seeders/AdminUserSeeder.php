<?php

namespace Database\Seeders;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'name'      => 'Admin',
            'email'     => 'admin@admin.it',
            'password'  => Hash::make('Value1234!'),
            'is_admin' => true,
        ]);

        $admin->markEmailAsVerified();
        $admin->assignRole('super_administrator');

        /* creazione root folder */
        $folder = Folder::create([
            'name' => 'admin',
            'user_id' => $admin->id,
            'storage_path' => 'admin',
            'uuid' => Str::uuid(),
        ]);

        Storage::makeDirectory($folder->storage_path);
    }
}
