<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@admin.com'], [
            'name' => 'Administrador ARTEMIS', 'password' => Hash::make('admin'),
            'is_active' => true, 'grade' => 14, 'google_id' => null,
        ]);
        $admin->syncRoles(['admin']);
    }
}
