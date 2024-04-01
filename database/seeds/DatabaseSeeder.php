<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         $this->call([
             DepartmentsTableSeeder::class,
             UsersTableSeeder::class,
             ProductsSeeder::class,
            //  OrderSeeder::class,
            //  PackageSeeder::class,
            //  ItemSeeder::class,
            //  StockTableSeeder::class,
         ]);
    }
}
