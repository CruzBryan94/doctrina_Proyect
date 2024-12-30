<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Grade_Seeder extends Seeder
{
    /**
      * Run the database seeds.
      */
     public function run(): void
     {
     $data = [
         ['id' => 1, 'name' => 'S.P', 'code' => 1],
         ['id' => 2, 'name' => 'Sldo', 'code' => 2],
         ['id' => 3, 'name' => 'Cbos', 'code' => 3],
         ['id' => 4, 'name' => 'Cbop', 'code' => 4],
         ['id' => 5, 'name' => 'Sgos', 'code' => 5],
         ['id' => 6, 'name' => 'Sgop', 'code' => 6],
         ['id' => 7, 'name' => 'Subs', 'code' => 7],
         ['id' => 8, 'name' => 'Subp', 'code' => 8],
         ['id' => 9, 'name' => 'Subm', 'code' => 9],
         ['id' => 10, 'name' => 'Subt', 'code' => 10],
         ['id' => 11, 'name' => 'Tnte', 'code' => 11],
         ['id' => 12, 'name' => 'Capt', 'code' => 12],
         ['id' => 13, 'name' => 'Mayo', 'code' => 13],
         ['id' => 14, 'name' => 'Tcrn', 'code' => 14],
         ['id' => 15, 'name' => 'Crnl', 'code' => 15],
         ['id' => 16, 'name' => 'Grab', 'code' => 16],
         ['id' => 17, 'name' => 'NA', 'code' => 17],
     ];

     DB::table('grades')->insert($data);

     }
 }
