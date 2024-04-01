<?php

namespace App\Http\Controllers;

use App\DeliveryItem;
use App\ProformaOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $deliveryItems = $request['newItems'];
        foreach ($deliveryItems as $deliveryItemObj) {
            DB::beginTransaction();

            $deliveryItem = new DeliveryItem();
            $deliveryItem->package_id = $deliveryItemObj["package_id"];
            $deliveryItem->length = $deliveryItemObj["length"];
            $deliveryItem->width = $deliveryItemObj["width"];
            $deliveryItem->thick = $deliveryItemObj["thick"];
            $deliveryItem->pcs = $deliveryItemObj["pcs"];
            $deliveryItem->remark = $deliveryItemObj["remark"];

            $deliveryItem->save();

            DB::commit();
        }

        return response()->json(['message' => 'successfully completed!'], 200, [], JSON_NUMERIC_CHECK);
    }
}
