<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleUser = Role::create(['name' => 'user']);
        $roleNone = Role::create(['name' => 'none']);

        Permission::create(['name' => 'users.index'])->syncRoles([$roleAdmin]);
        Permission::create(['name' => 'home'])->syncRoles([$roleAdmin, $roleUser]);
    }
}
