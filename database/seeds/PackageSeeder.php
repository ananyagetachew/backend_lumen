<?php

use App\DeliveryOrder;
use App\DeliveryPackage;
use App\ProformaOrder;
use App\ProformaPackage;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (DeliveryOrder::all() as $deliveryOrder) {
            for ($x = 1; $x <= 6; $x++) {
                $deliveryPackage = new DeliveryPackage();
                $deliveryPackage->order_id = $deliveryOrder->id;
                $deliveryPackage->product_id = $x;
                $deliveryPackage->unit_price = $x * 80;
                $deliveryPackage->bullnose = $x * 1.9;
                $deliveryPackage->groove = $x * 1.6;
                $deliveryPackage->save();
            }
        }

        foreach (ProformaOrder::all() as $proformaOrder) {
            for ($x = 1; $x <= 6; $x++) {
                $deliveryPackage = new ProformaPackage();
                $deliveryPackage->order_id = $proformaOrder->id;
                $deliveryPackage->product_id = $x;
                $deliveryPackage->unit_price = $x * 80;
                $deliveryPackage->bullnose = $x * 1.9;
                $deliveryPackage->groove = $x * 1.6;
                $deliveryPackage->save();
            }
        }
    }
}
