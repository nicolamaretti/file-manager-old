<?php

namespace Database\Seeders;

use App\Models\File;
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
            'name' => 'Admin',
            'email' => 'admin@admin.it',
            'password' => Hash::make('Value1234!'),
            'is_admin' => true,
            'remember_token' => Str::random(10),
        ]);

        $admin->markEmailAsVerified();
        $admin->assignRole('super_administrator');

        /* creazione root folder */
        $folder = File::create([
            'name' => 'admin',
            'path' => 'admin',
//            'storage_path' => 'files/admin',
            'is_folder' => true,
            'uuid' => Str::uuid(),
            'created_by' => $admin->id,
        ]);

        Storage::makeDirectory("$folder->name");
    }
}
