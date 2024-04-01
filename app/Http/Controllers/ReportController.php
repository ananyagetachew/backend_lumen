<?php

namespace App\Http\Controllers;

use App\DeliveredItem;
use App\DeliveredOrder;
use App\DeliveredPackage;
use App\DeliveryItem;
use App\DeliveryOrder;
use App\DeliveryPackage;
use DateTime;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    public function getReport(Request $request, $report_table)
    {
        $orders = $report_table === 'delivery_orders' ? DeliveryOrder::where(['active' => true]) : DeliveredOrder::where(['active' => true]);
        // filter by company name
        if ($request->company_name) {
            $orders->where('company_name', 'LIKE', '%' . $request->company_name . '%');
        }
        // filter by date
        if (
            $request->from && $request->to &&
            $request->from !== 'undefined' && $request->to !== 'undefined'
        ) {
            // parse JavaScript date to PHP date
            $from_date = DateTime::createFromFormat('D M d Y H:i:s e+', $request->from);
            $to_date = DateTime::createFromFormat('D M d Y H:i:s e+', $request->to);
            $orders->whereBetween('created_at', [$from_date, $to_date]);
        }
        $orders = $orders->get();

        foreach ($orders as $order) {
            // total amount of pcs(of all products) per order
            $total_pcs = 0;
            // total meter square of all items per order
            $total_m2 = 0;
            $packages = $report_table === 'delivery_orders' ? new DeliveryPackage() : new DeliveredPackage();
            $packages = $packages->where(['active' => true, 'order_id' => $order->id])->get();
            foreach ($packages as $package) {
                $items = $report_table === 'delivery_orders' ? new DeliveryItem() : new DeliveredItem();
                $items = $items->where(['active' => true, 'package_id' => $package->id])->get();
                foreach ($items as $item) {
                    $total_pcs += $item->pcs;
                    // calculate meter square => m2 = length * width * pcs
                    $total_m2 += $item->length * $item->pcs * $item->width;
                }
            }
            $order->total_pcs = $total_pcs;
            $order->total_m2 = $total_m2;
        }
        return response()->json($orders, 200, [], JSON_NUMERIC_CHECK);
    }
}
