<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DeliveryOrder extends CustomModel
{
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        /* 
            users wanted to see current year appended to the order_no so for 2018 we do
            20181, 20182... when its 2019 we have to reset to 20191, 20192...
         */
        // get last inserted order
        $lastInserted = DB::table('delivery_orders')->latest()->first();
        // is it the first time we are inserting an order number
        if ($lastInserted) {
            // we have previously inserted an order so lets extract the appended year from order_no property
            $lastInsertedYear = substr($lastInserted->order_no, 0, 4);
            // lets check if current year is different from extracted year
            if ($lastInsertedYear != ("" . Carbon::now()->year)) {
                // reset order_no count to new year and with suffix of 1
                $this->order_no = Carbon::now()->year . "1";
            } else {
                // if  year is exactly same, get the number from last element's order_no without the year
                $lastNumberAfterYear = (int) substr($lastInserted->order_no, 4, strlen($lastInserted->order_no));
                // increment the suffix (order_no without the year)
                ++$lastNumberAfterYear;
                // set to new order
                $this->order_no = substr($lastInserted->order_no, 0, 4) . $lastNumberAfterYear;
            }
        } else {
            // initialize
            $this->order_no = Carbon::now()->year . "1";
        }
    }
}
