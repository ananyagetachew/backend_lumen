<?php

namespace App\Http\Controllers;

use App\ProformaItem;
use App\ProformaOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProformaItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        $proformaItems = $request['newItems'];
        foreach ($proformaItems as $proformaItemObj) {
            $proformaItem = new ProformaItem();
            $proformaItem->package_id = $proformaItemObj["package_id"];
            $proformaItem->length = $proformaItemObj["length"];
            $proformaItem->width = $proformaItemObj["width"];
            $proformaItem->thick = $proformaItemObj["thick"];
            $proformaItem->pcs = $proformaItemObj["pcs"];
            $proformaItem->remark = $proformaItemObj["remark"];

            $proformaItem->save();
        }

        DB::commit();
        return response()->json(['message' => 'successfully completed!'], 200, [], JSON_NUMERIC_CHECK);
    }
}
