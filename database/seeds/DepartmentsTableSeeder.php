<?php

use App\Util\FinalConstants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('departments')->insert([
            'name' => FinalConstants::adminLabel
        ]);

        DB::table('departments')->insert([
            'name' => FinalConstants::salesLabel
        ]);

        DB::table('departments')->insert([
            'name' => FinalConstants::factoryLoaderLabel
        ]);

        DB::table('departments')->insert([
            'name' => FinalConstants::stockManager
        ]);

        DB::table('departments')->insert([
            'name' => FinalConstants::productionManager
        ]);

        DB::table('departments')->insert([
            'name' => FinalConstants::financeManager
        ]);
    }
}
