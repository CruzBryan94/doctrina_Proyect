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
         ['id' => 1, 'grade_name' => 'S.P', 'code' => 1],
         ['id' => 2, 'grade_name' => 'Sldo', 'code' => 2],
         ['id' => 3, 'grade_name' => 'Cbos', 'code' => 3],
         ['id' => 4, 'grade_name' => 'Cbop', 'code' => 4],
         ['id' => 5, 'grade_name' => 'Sgos', 'code' => 5],
         ['id' => 6, 'grade_name' => 'Sgop', 'code' => 6],
         ['id' => 7, 'grade_name' => 'Subs', 'code' => 7],
         ['id' => 8, 'grade_name' => 'Subp', 'code' => 8],
         ['id' => 9, 'grade_name' => 'Subm', 'code' => 9],
         ['id' => 10, 'grade_name' => 'Subt', 'code' => 10],
         ['id' => 11, 'grade_name' => 'Tnte', 'code' => 11],
         ['id' => 12, 'grade_name' => 'Capt', 'code' => 12],
         ['id' => 13, 'grade_name' => 'Mayo', 'code' => 13],
         ['id' => 14, 'grade_name' => 'Tcrn', 'code' => 14],
         ['id' => 15, 'grade_name' => 'Crnl', 'code' => 15],
         ['id' => 16, 'grade_name' => 'Grab', 'code' => 16],
         ['id' => 17, 'grade_name' => 'NA', 'code' => 17],
     ];

     DB::table('grades')->insert($data);

     }
 }
