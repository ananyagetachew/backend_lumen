<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = collect([
            "Window Sill", "Door Sill|Treshold", "Tread", "Riser", "Tiles|Landing", "Skirting|Zekolo", "Coping", "Border", "Chips", "Powder", "Block", "Cladding", "Edging", "Bolder", "Pattern",
            "Marbel-3-Thickness", "Marbel-4-Thickness"
        ]);
        foreach ($products as $product) {
            DB::table('products')->insert([
                'name' => $product
            ]);
        }
    }
}
