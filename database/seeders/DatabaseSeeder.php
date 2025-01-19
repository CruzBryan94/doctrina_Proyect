<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(Grade_Seeder::class);
        $this->call(CommitteeTypeSeeder::class);
        $this->call(MilitaryUnitsSeeder::class);
        $this->call(ManualPhasesSeeder::class);
        $this->call(CatalogSubphasesSeeder::class);
        $this->call(ManualTypesSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
    }
}
