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
        $super_admin = User::create([
            'name'      => 'Admin',
            'email'     => 'admin@admin.it',
            'password'  => Hash::make('Value1234!'),
            'is_admin' => true,
        ]);

        $super_admin->markEmailAsVerified();
        $super_admin->assignRole('super_administrator');

        /* creazione root folder */
        $folder = Folder::create([
            'name' => 'AdminFolder',
            'user_id' => $super_admin->id,
            'storage_path' =>'AdminFolder',
            'is_root_folder' => true,
            'uuid' => Str::uuid(),
        ]);

        Storage::makeDirectory($folder->storage_path);

        if ($folder) {
            $super_admin->root_folder_id = $folder->id;
            $super_admin->save();
        }
    }
}
