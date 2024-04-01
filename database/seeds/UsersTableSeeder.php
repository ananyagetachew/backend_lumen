<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'username' => 'admin@firdows.online',
            'department_id' => 1,
            'password' => app('hash')->make('admin')
        ]);

        DB::table('users')->insert([
            'username' => 'sales@firdows.online',
            'department_id' => 2,
            'password' => app('hash')->make('sales')
        ]);

        DB::table('users')->insert([
            'username' => 'factoryloader@firdows.online',
            'department_id' => 3,
            'password' => app('hash')->make('admin')
        ]);

        DB::table('users')->insert([
            'username' => 'stockmanager@firdows.online',
            'department_id' => 4,
            'password' => app('hash')->make('admin')
        ]);

        DB::table('users')->insert([
            'username' => 'productionmanager@firdows.online',
            'department_id' => 5,
            'password' => app('hash')->make('admin')
        ]);

        DB::table('users')->insert([
            'username' => 'finance@firdows.online',
            'department_id' => 6,
            'password' => app('hash')->make('admin')
        ]);
    }
}
