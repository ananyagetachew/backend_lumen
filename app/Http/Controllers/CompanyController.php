<?php

namespace App\Http\Controllers;

use App\DeliveryOrder;
use App\ProductionOrder;

class CompanyController extends Controller
{

    public function index($from_table)
    {
        if ($from_table == "delivery_orders") {
            $companies = DeliveryOrder::select('company_name')->get()->unique('company_name')->pluck('company_name');
        } else {
            $companies = ProductionOrder::select('company_name')->get()->unique('company_name')->pluck('company_name');
        }
        return response()->json($companies);
    }
}
