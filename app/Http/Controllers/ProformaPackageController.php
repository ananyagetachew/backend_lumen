<?php

namespace App\Http\Controllers;

use App\ProformaOrder;
use App\ProformaPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProformaPackageController extends Controller
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

        $proformaPackage = new ProformaPackage();
        $proformaPackage->order_id = $order_id;
        $proformaPackage->product_id = $request->product_id;
        $proformaPackage->bullnose = $request->bullnose;
        $proformaPackage->groove = $request->groove;
        $proformaPackage->unit_price = $request->unit_price;
        $proformaPackage->save();
        // append 'name' to package object so front-end would render it
        $proformaPackage->name = $proformaPackage->getName();

        DB::commit();
        return response()->json($proformaPackage, 200, [], JSON_NUMERIC_CHECK);
    }
}
