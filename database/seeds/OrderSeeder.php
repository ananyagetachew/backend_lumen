<?php

use App\DeliveryOrder;
use App\ProformaOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($x = 1; $x <= 10; $x++) {
            $proformaOrder = new ProformaOrder();
            $proformaOrder->company_name = "Afro Tsion Construction " . $x;

            // its needed to separate order_no's by year (appending the current year to order no and reseting every year to 1)

            $lastInserted = ProformaOrder::find(DB::table('proforma_orders')->max('id'));
            if ($lastInserted) {
                $lastInsertedYear = substr($lastInserted->order_no, 0, 4);
                if ($lastInsertedYear != ("" . Carbon::now()->year)) {
                    $proformaOrder->order_no = Carbon::now()->year . "1";
                } else {
                    $lastNumberAfterYear = (int)substr($lastInserted->order_no, 4, strlen($lastInserted->order_no));
                    ++$lastNumberAfterYear;
                    $proformaOrder->order_no = substr($lastInserted->order_no, 0, 4) . $lastNumberAfterYear;
                }
            } else {
                $proformaOrder->order_no = Carbon::now()->year . "1";
            }
            $proformaOrder->note = "Note: The marble is multicolor____ Delivery time_____ Dates for payment____ ";
            $proformaOrder->validity_date = "2018-3-" . $x;
            $proformaOrder->save();
        }

        for ($x = 1; $x <= 6; $x++) {
            $deliveryOrder = new DeliveryOrder();
            
            // its needed to separate order_no's by year (appending the current year to order no and reseting every year to 1)

            $lastInserted = DeliveryOrder::find(DB::table('delivery_orders')->max('id'));
            if ($lastInserted) {
                $lastInsertedYear = substr($lastInserted->order_no, 0, 4);
                if ($lastInsertedYear != ("" . Carbon::now()->year)) {
                    $deliveryOrder->order_no = Carbon::now()->year . "1";
                } else {
                    $lastNumberAfterYear = (int)substr($lastInserted->order_no, 4, strlen($lastInserted->order_no));
                    ++$lastNumberAfterYear;
                    $deliveryOrder->order_no = substr($lastInserted->order_no, 0, 4) . $lastNumberAfterYear;
                }
            } else {
                $deliveryOrder->order_no = Carbon::now()->year . "1";
            }
            $deliveryOrder->company_name = "Afro Tsion Construction " . $x;
            $deliveryOrder->note = "Note: The marble is multicolor____ Delivery time_____ Dates for payment____ ";
            $deliveryOrder->delivery_date = "2018-3-" . $x;
            $deliveryOrder->fsno = "00001000" . $x;
            $deliveryOrder->save();
        }
    }
}
