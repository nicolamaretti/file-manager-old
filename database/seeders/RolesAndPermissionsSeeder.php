<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'view-all-level']);
        Permission::create(['name' => 'view-organization-level']);
        Permission::create(['name' => 'view-department-level']);

        // create roles
        $super_admin_role = Role::create(['name' => 'super_administrator']);
        $admin_organization_role = Role::create(['name' => 'organization_administrator']);
        $department_role = Role::create(['name' => 'department_role']);

        // assign permission
        $super_admin_role->givePermissionTo('view-all-level');
        $admin_organization_role->givePermissionTo('view-organization-level');
        $department_role->givePermissionTo('view-department-level');
    }
}
