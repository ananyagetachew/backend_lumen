<?php

namespace App\Http\Controllers;

use App\DeliveryPackage;
use App\ProformaOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryPackageController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store($order_id, Request $request)
    {
        DB::beginTransaction();

        $deliveryPackage = new DeliveryPackage();
        $deliveryPackage->order_id = $order_id;
        $deliveryPackage->product_id = $request->product_id;
        $deliveryPackage->bullnose = $request->bullnose;
        $deliveryPackage->groove = $request->groove;
        $deliveryPackage->unit_price = $request->unit_price;
        $deliveryPackage->save();

        DB::commit();
        // append 'name' to package object so front-end would render it
        $deliveryPackage['name'] = $deliveryPackage->getName();

        return response()->json($deliveryPackage, 200, [], JSON_NUMERIC_CHECK);
    }
}
