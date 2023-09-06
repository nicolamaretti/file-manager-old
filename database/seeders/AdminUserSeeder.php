<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            'can_write_folder' => true,
            'is_admin' => true,
        ]);

        $super_admin->markEmailAsVerified();
        $super_admin->assignRole('super_administrator');
    }
}
