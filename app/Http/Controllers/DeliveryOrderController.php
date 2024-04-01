<?php

namespace App\Http\Controllers;

use App\DeliveryItem;
use App\DeliveryOrder;
use App\DeliveryPackage;
use App\Product;
use App\ProductionItem;
use App\ProductionOrder;
use App\ProductionPackage;
use App\Util\FinalConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class DeliveryOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            DeliveryOrder::where(['sent_to_production' => false])->orderByDesc('created_at')->paginate(FinalConstants::paginationLimit),
            200,
            [],
            JSON_NUMERIC_CHECK
        );
    }

    public function search($query)
    {
        $res = DeliveryOrder::where('sent_to_production', false)->where('company_name', 'LIKE', '%' . $query . '%')->orWhere('order_no', 'LIKE', '%' . $query . '%')->orderByDesc('created_at')->paginate(FinalConstants::paginationLimit);
        return response()->json($res, 200, [], JSON_NUMERIC_CHECK);
    }

    public function get($id)
    {
        return DeliveryOrder::find($id);
    }

    public function getPackages($id)
    {
        return DeliveryPackage::where(['order_id' => $id])->get();
    }

    public function getItems($id)
    {
        $items = [];
        $packages = DeliveryPackage::where(['order_id' => $id])->get();
        foreach ($packages as $package) {
            $single_package_items = DeliveryItem::where(['package_id' => $package->id])->get();
            //flatten out the array instead of having multi dimensional array
            foreach ($single_package_items as $single_package_item) {
                array_push($items, $single_package_item);
            }
        }
        return response()->json($items, 200, [], JSON_NUMERIC_CHECK);
    }

    public function sendAllToProduction($id)
    {

        DB::beginTransaction();

        $deliveryOrder = DeliveryOrder::find($id);
        $deliveryOrder->update(['sent_to_production' => true]);

        $newProductionOrder = new ProductionOrder();
        $newProductionOrder->original_order_id = $deliveryOrder->id;
        $newProductionOrder->order_no = $deliveryOrder->order_no;
        $newProductionOrder->company_name = $deliveryOrder->company_name;
        $newProductionOrder->delivery_date = $deliveryOrder->delivery_date;
        $newProductionOrder->fsno = $deliveryOrder->fsno;
        $newProductionOrder->note = $deliveryOrder->note;
        $newProductionOrder->save();

        $deliveryPackages = DeliveryPackage::where(['order_id' => $deliveryOrder->id])->get();
        foreach ($deliveryPackages as $deliveryPackage) {
            $newProductionPackage = new ProductionPackage();
            $newProductionPackage->original_package_id = $deliveryPackage->id;
            $newProductionPackage->order_id = $newProductionOrder->id;
            $newProductionPackage->product_id = $deliveryPackage->product_id;
            $newProductionPackage->unit_price = $deliveryPackage->unit_price;
            $newProductionPackage->bullnose = $deliveryPackage->bullnose;
            $newProductionPackage->groove = $deliveryPackage->groove;

            $deliveryItems = DeliveryItem::where(['package_id' => $deliveryPackage->id])->get();

            // if every item of a package has been already sent to production don't save package to production packages
            foreach ($deliveryItems as $deliveryItem) {
                // if delivery item has no available pcs to be sent to production skip
                if (($deliveryItem->pcs - $deliveryItem->getAlreadyOrderedPcs()) < 1) {
                    continue;
                }
                $newProductionPackage->save();
                $newProductionItem = new ProductionItem();
                $newProductionItem->original_item_id = $deliveryItem->id;
                $newProductionItem->package_id = $newProductionPackage->id;
                $newProductionItem->length = $deliveryItem->length;
                $newProductionItem->width = $deliveryItem->width;
                $newProductionItem->thick = $deliveryItem->thick;
                $newProductionItem->pcs = $deliveryItem->pcs - $deliveryItem->getAlreadyOrderedPcs();
                $newProductionItem->remark = $deliveryItem->remark;
                $newProductionItem->save();
            }
        }

        DB::commit();

        return response('', 200);
    }

    public function getDeliveryOrderDetail($id)
    {
        $obj = new stdClass();
        $obj->order = DeliveryOrder::find($id);
        $packages = DeliveryPackage::where(['order_id' => $id])->get();
        $obj->packages = $packages;
        $items = [];
        foreach ($packages as $package) {
            // add a 'name' attribute to the json object cause front-end code is expecting to form dropdown content
            // from the 'name' attribute on each package
            $package['name'] = $package->getName();
            $single_package_items = DeliveryItem::where(['package_id' => $package->id])->get();
            //flatten out the array instead of having multi dimensional array
            foreach ($single_package_items as $single_package_item) {
                $single_package_item['previously_processed_pcs'] = $single_package_item->getAlreadyOrderedPcs();
                array_push($items, $single_package_item);
            }
        }
        $obj->items = $items;

        return response()->json($obj, 200, [], JSON_NUMERIC_CHECK);
    }

    public function persistEditedData(Request $request)
    {
        $order = $request->order;
        $packages = $request->packages;
        $items = $request->items;


        DB::beginTransaction();

        DeliveryOrder::find($order["id"])->update($order);

        foreach ($packages as $package) {
            $deliveryPackage = DeliveryPackage::find($package['id']);
            $deliveryPackage->unit_price = $package['unit_price'];
            $deliveryPackage->bullnose = $package['bullnose'];
            $deliveryPackage->groove = $package['groove'];
            $deliveryPackage->save();
        }

        foreach ($items as $item) {
            $deliveryItem = DeliveryItem::find($item['id']);
            $deliveryItem->length = $item['length'];
            $deliveryItem->width = $item['width'];
            $deliveryItem->thick = $item['thick'];
            $deliveryItem->pcs = $item['pcs'];
            $deliveryItem->remark = $item['remark'];
            $deliveryItem->save();
        }

        $returnedObj = new stdClass();
        $returnedObj->order = $order;
        $returnedObj->packages = $packages;
        $returnedObj->items = $items;

        DB::commit();

        return response()->json($returnedObj, 200, ['content-type' => 'application/json'], JSON_NUMERIC_CHECK);
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

        $newDelivery = new DeliveryOrder();

        $newDelivery->company_name = $order['company_name'];
        $newDelivery->note = $order['note'];
        // fsno is optional so retrieve it if present, null otherwise
        $newDelivery->fsno = isset($order['fsno']) ? $order['fsno'] : null;
        $newDelivery->delivery_date = Carbon::now()->addDays($order['delivery_date_count']);
        $newDelivery->save();

        foreach ($packages as $package) {
            $newDeliveryPackage = new DeliveryPackage();
            $newDeliveryPackage->product_id = Product::where('name', $package['name'])->first()->id;
            $newDeliveryPackage->bullnose = $package['bullnose'];
            $newDeliveryPackage->groove = $package['groove'];
            $newDeliveryPackage->unit_price = $package['unit_price'];
            $newDeliveryPackage->order_id = $newDelivery->id;
            $newDeliveryPackage->save();

            foreach ($package['items'] as $item) {
                $newDeliveryItem = new DeliveryItem();
                $newDeliveryItem->length = $item['length'];
                $newDeliveryItem->width = $item['width'];
                $newDeliveryItem->thick = $item['thick'];
                $newDeliveryItem->pcs = $item['pcs'];
                $newDeliveryItem->remark = $item['remark'];
                $newDeliveryItem->package_id = $newDeliveryPackage->id;
                $newDeliveryItem->save();
            }
        }

        DB::commit();
        return $newDelivery->id;
    }

    public function manuallyInsert(Request $request)
    {
        DB::beginTransaction();

        $newDelivery = new DeliveryOrder();

        $newDelivery->company_name = $request['company_name'];
        $newDelivery->note = $request['note'];
        // fsno is optional so retrieve it if present, null otherwise
        $newDelivery->fsno = $request['fsno'] || null;
        $newDelivery->delivery_date = Carbon::now()->addDays($request['delivery_date_count']);
        $newDelivery->save();

        DB::commit();
        return response()->json($newDelivery->id);
    }
}
