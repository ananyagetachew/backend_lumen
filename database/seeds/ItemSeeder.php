<?php

use App\DeliveryItem;
use App\DeliveryPackage;
use App\ProformaItem;
use App\ProformaPackage;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $deliveryPackages = DeliveryPackage::all();
        foreach ($deliveryPackages as $deliveryPackage) {
            for ($x = 1; $x <= 2; $x++) {
                $deliveryItem = new DeliveryItem();
                $deliveryItem->package_id = $deliveryPackage->id;
                $deliveryItem->length = 0.3 * $x;
                $deliveryItem->width = 0.1 * $x;
                $deliveryItem->thick = 0.6 * $x;
                $deliveryItem->pcs = $x;
                $deliveryItem->remark = "Bullnose&Groove";
                $deliveryItem->save();
            }
        }

        $proformaPackages = ProformaPackage::all();
        foreach ($proformaPackages as $proformaPackage) {
            for ($x = 1; $x <= 2; $x++) {
                $deliveryItem = new ProformaItem();
                $deliveryItem->package_id = $proformaPackage->id;
                $deliveryItem->length = 0.4 * $x;
                $deliveryItem->width = 0.2 * $x;
                $deliveryItem->thick = 0.7 * $x;
                $deliveryItem->pcs = $x * 2;
                $deliveryItem->remark = "2SideBullnose&2SideGroove";
                $deliveryItem->save();
            }
        }
    }
}
