<?php

namespace App\Http\Controllers;

use App\DeliveryItem;
use App\DeliveryOrder;
use App\DeliveryPackage;
use App\Product;
use App\ProformaItem;
use App\ProformaOrder;
use App\ProformaPackage;
use App\Util\FinalConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class ProformaOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $res = ProformaOrder::where('active', true)->orderByDesc('created_at')->paginate(FinalConstants::paginationLimit);
        return response()->json($res, 200, [], JSON_NUMERIC_CHECK);
    }

    public function search($query)
    {
        $res = ProformaOrder::where('active', true)->where('company_name', 'LIKE', '%' . $query . '%')->orWhere('order_no', 'LIKE', '%' . $query . '%')->orderByDesc('created_at')->paginate(FinalConstants::paginationLimit);
        return response()->json($res, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getProformaOrderDetail($id)
    {
        $obj = new stdClass();
        $obj->order = ProformaOrder::find($id);
        $packages = ProformaPackage::where(['order_id' => $id])->get();
        $obj->packages = $packages;
        $items = [];
        foreach ($packages as $package) {
            // add a 'name' attribute to the json object cause front-end code is expecting to form dropdown content
            // from the 'name' attribute on each package
            $package['name'] = $package->getName();
            $single_package_items = ProformaItem::where(['package_id' => $package->id])->get();
            //flatten out the array instead of having multi dimensional array
            foreach ($single_package_items as $single_package_item) {
                array_push($items, $single_package_item);
            }
        }
        $obj->items = $items;

        return response()->json($obj, 200, [], JSON_NUMERIC_CHECK);
    }

    public function importFromExcel(Request $request)
    {

        try {
            $this->validate($request, [
                'order' => 'required',
                'packages' => 'required'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['validation error']);
        }

        $order = $request->order;
        $packages = $request->packages;

        DB::beginTransaction();

        $proformaOrder = new ProformaOrder();
        $proformaOrder->company_name = $order['company_name'];
        $proformaOrder->note = $order['note'];
        $proformaOrder->fsno = $order['fsno'] || null;
        $proformaOrder->validity_date = Carbon::now()->addDays($order['validity_date_count']);
        $proformaOrder->save();

        foreach ($packages as $package) {
            $proformaPackage = new ProformaPackage();
            $proformaPackage->order_id = $proformaOrder->id;
            $proformaPackage->product_id = Product::where('name', $package['name'])->first()->id;
            $proformaPackage->unit_price = $package['unit_price'];
            $proformaPackage->bullnose = $package['bullnose'];
            $proformaPackage->groove = $package['groove'];
            $proformaPackage->save();

            foreach ($package['items'] as $item) {
                $proformaItem = new ProformaItem();
                $proformaItem->package_id = $proformaPackage->id;
                $proformaItem->length = $item['length'];
                $proformaItem->width = $item['width'];
                $proformaItem->thick = $item['thick'];
                $proformaItem->pcs = $item['pcs'];
                $proformaItem->remark = $item['remark'];
                $proformaItem->save();
            }
        }

        DB::commit();

        return response()->json($proformaOrder->id, 200, [], JSON_NUMERIC_CHECK);
    }

    public function persistEditedData(Request $request, $id)
    {
        $order = $request->order;
        $packages = $request->packages;
        $items = $request->items;

        DB::beginTransaction();

        ProformaOrder::find($order["id"])->update($order);

        foreach ($packages as $package) {
            $proformaPackage = ProformaPackage::find($package['id']);
            $proformaPackage->unit_price = $package['unit_price'];
            $proformaPackage->bullnose = $package['bullnose'];
            $proformaPackage->groove = $package['groove'];
            $proformaPackage->save();
        }

        foreach ($items as $item) {
            $proformaItem = ProformaItem::find($item['id']);
            $proformaItem->length = $item['length'];
            $proformaItem->width = $item['width'];
            $proformaItem->thick = $item['thick'];
            $proformaItem->pcs = $item['pcs'];
            $proformaItem->remark = $item['remark'];
            $proformaItem->save();
        }

        $returnedObj = new stdClass();
        $returnedObj->order = $order;
        $returnedObj->packages = $packages;
        $returnedObj->items = $items;

        DB::commit();

        return response()->json($returnedObj, 200, [], JSON_NUMERIC_CHECK);
    }

    public function convertToDeliveryOrder($id, Request $request)
    {
        $proforma = ProformaOrder::find($id);

        DB::beginTransaction();

        $newDelivery = new DeliveryOrder();

        /* 
            users wanted to see current year appended to the order_no so for 2018 we do
            20181, 20182... when its 2019 we have to reset to 20191, 20192...
         */
        // get last inserted order
        $lastInserted = DeliveryOrder::find(DB::table('delivery_orders')->max('id'));
        // is it the first time we are inserting a order number
        if ($lastInserted) {
            // we have previously inserted an order so lets extract the appended year from order_no property
            $lastInsertedYear = substr($lastInserted->order_no, 0, 4);
            // lets check if current year is different from extracted year
            if ($lastInsertedYear != ("" . Carbon::now()->year)) {
                // reset order_no count to new year and with suffix of 1
                $newDelivery->order_no = Carbon::now()->year . "1";
            } else {
                // if  year is exactly same, get the number from last element's order_no without the year
                $lastNumberAfterYear = (int)substr($lastInserted->order_no, 4, strlen($lastInserted->order_no));
                // increment the suffix (order_no without the year)
                ++$lastNumberAfterYear;
                // set to new order
                $newDelivery->order_no = substr($lastInserted->order_no, 0, 4) . $lastNumberAfterYear;
            }
        } else {
            // initialize
            $newDelivery->order_no = Carbon::now()->year . "1";
        }
        $newDelivery->proforma_no = $proforma->order_no;
        $newDelivery->company_name = $proforma->company_name;
        $newDelivery->fsno = $proforma->fsno || null;
        $newDelivery->note = $proforma->note;
        $newDelivery->delivery_date = Carbon::now()->addDays($request->delivery_date_count);
        $newDelivery->save();

        $proformaPackages = ProformaPackage::where(['order_id' => $id])->get();
        foreach ($proformaPackages as $proformaPackage) {
            $newDeliveryPackage = new DeliveryPackage();
            $newDeliveryPackage->product_id = $proformaPackage->product_id;
            $newDeliveryPackage->bullnose = $proformaPackage->bullnose;
            $newDeliveryPackage->groove = $proformaPackage->groove;
            $newDeliveryPackage->unit_price = $proformaPackage->unit_price;
            $newDeliveryPackage->order_id = $newDelivery->id;
            $newDeliveryPackage->save();

            $proformaItems = ProformaItem::where(['package_id' => $proformaPackage->id])->get();
            foreach ($proformaItems as $proformaItem) {
                $newDeliveryItem = new DeliveryItem();
                $newDeliveryItem->length = $proformaItem->length;
                $newDeliveryItem->width = $proformaItem->width;
                $newDeliveryItem->thick = $proformaItem->thick;
                $newDeliveryItem->pcs = $proformaItem->pcs;
                $newDeliveryItem->remark = $proformaItem->remark;
                $newDeliveryItem->package_id = $newDeliveryPackage->id;
                $newDeliveryItem->save();
                $proformaItem->delete();
            }
            $proformaPackage->delete();
        }
        //cause we are enforcing cascade delete on our foreign key constraint we need to delete the order last
        $proforma->delete();

        DB::commit();
        return response()->json($newDelivery->id, 200, [], JSON_NUMERIC_CHECK);
    }

    public function manuallyInsert(Request $request)
    {
        DB::beginTransaction();

        $newOrder = new ProformaOrder();

        $newOrder->company_name = $request['company_name'];
        $newOrder->note = $request['note'];
        $newOrder->fsno = $request['fsno'] || null;
        $newOrder->validity_date = Carbon::now()->addDays($request['validity_date_count']);
        $newOrder->save();

        DB::commit();
        return response()->json($newOrder->id);
    }
}
