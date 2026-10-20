<?php

namespace Database\Seeders;

use App\Models\ManualPhase;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['dashboard.view', 'reports.view', 'users.manage', 'catalogs.manage', 'manuals.create'];
        foreach (array_keys(ManualPhase::ROLES) as $slug) {
            $permissions[] = 'manuals.'.$slug;
        }
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('admin', 'web')->syncPermissions($permissions);
        Role::findOrCreate('none', 'web')->syncPermissions([]);
        Role::findOrCreate('supervisor', 'web')->syncPermissions(['dashboard.view', 'reports.view']);
        foreach (ManualPhase::ROLES as $slug => $role) {
            $grants = ['dashboard.view', 'reports.view', 'manuals.'.$slug];
            if ($slug === 'planning') {
                $grants[] = 'manuals.create';
            }
            Role::findOrCreate($role, 'web')->syncPermissions($grants);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
