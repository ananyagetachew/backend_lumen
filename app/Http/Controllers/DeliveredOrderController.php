<?php

namespace App\Http\Controllers;

use App\DeliveredItem;
use App\DeliveredOrder;
use App\DeliveredPackage;
use App\ProductionItem;
use App\ProductionOrder;
use App\Stock;
use App\Util\FinalConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class DeliveredOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $res = DeliveredOrder::where('active', true)->orderByDesc('created_at')->paginate(FinalConstants::paginationLimit);
        return response()->json($res, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        DB::beginTransaction();

        try {
            $this->validate($request, [
                'order' => 'required',
                'packages' => 'required',
                'items' => 'required'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['validation error']);
        }

        $order = $request['order'];
        $packages = $request['packages'];
        $items = $request['items'];

        $newDeliveredOrder = new DeliveredOrder();
        $newDeliveredOrder->original_production_order_id = $order['id'];
        $newDeliveredOrder->order_no = $order['order_no'];
        $newDeliveredOrder->issued_by = $request['issued_by'];
        $newDeliveredOrder->approved_by = $request['approved_by'];
        $newDeliveredOrder->recieved_by = $request['recieved_by'];
        $newDeliveredOrder->driver_plate_no = $request['driver_plate_no'];
        $newDeliveredOrder->driver_id_no = $request['driver_id_no'];
        $newDeliveredOrder->driver_name = $request['driver_name'];
        $newDeliveredOrder->company_name = $order['company_name'];
        $newDeliveredOrder->delivery_date = $order['delivery_date'];
        $newDeliveredOrder->fsno = $order['fsno'];
        $newDeliveredOrder->note = $order['note'];
        $newDeliveredOrder->save();

        foreach ($packages as $deliveryPackage) {
            $newDeliveredPackage = new DeliveredPackage();
            $newDeliveredPackage->order_id = $newDeliveredOrder->id;
            $newDeliveredPackage->original_production_package_id = $deliveryPackage['id'];
            $newDeliveredPackage->product_id = $deliveryPackage['product_id'];
            $newDeliveredPackage->unit_price = $deliveryPackage['unit_price'];
            $newDeliveredPackage->bullnose = $deliveryPackage['bullnose'];
            $newDeliveredPackage->groove = $deliveryPackage['groove'];
            $newDeliveredPackage->save();

            foreach ($items as $deliveryItem) {
                if ($newDeliveredPackage->original_production_package_id == $deliveryItem['package_id']) {
                    $newDeliveredItem = new DeliveredItem();
                    $newDeliveredItem->original_production_item_id = $deliveryItem['id'];
                    $newDeliveredItem->package_id = $newDeliveredPackage->id;
                    $newDeliveredItem->length = $deliveryItem['length'];
                    $newDeliveredItem->width = $deliveryItem['width'];
                    $newDeliveredItem->thick = $deliveryItem['thick'];
                    $newDeliveredItem->pcs = $deliveryItem['pcs'];
                    $newDeliveredItem->remark = $deliveryItem['remark'];

                    $newDeliveredItem->save();

                    // deduct the items loaded to vehicle from the stock
                    $stocks = Stock::where([
                        'product_id' => $newDeliveredItem->getProductId(),
                        'width' => $newDeliveredItem->width,
                        'length' => $newDeliveredItem->length,
                        'delivery_no' => ProductionOrder::find($newDeliveredOrder->original_production_order_id)->original_order_id,
                        'thick' => $newDeliveredItem->thick
                    ])->orWhere(
                        'delivery_no',
                        null
                    )->get();
                    foreach ($stocks as $stock) {
                        $available_for_order_pcs = $stock->pcs - $stock->loaded_pcs;
                        $available_for_order_pcs -= $newDeliveredItem->pcs;
                        if ($available_for_order_pcs >= 0) {
                            $stock->loaded_pcs += $newDeliveredItem->pcs;
                            // did user load up all pcs from stock?
                            if ($stock->loaded_pcs >= $stock->pcs) {
                                // yes, so deactivate this stock item
                                $stock->active = false;
                            }
                            $stock->save();
                            break;
                        } else {
                            // ordered pcs are commulated from different stock
                            // sources (i.e one with the same delivery no and one with 
                            // a delivery no of null)
                            $stock->loaded_pcs = $stock->pcs;
                            $stock->active = false;
                            $stock->save();
                            $newDeliveredItem->pcs = abs($available_for_order_pcs);
                        }
                    }
                }
            }
        }

        DB::commit();

        return response('', 200);
    }

    public function getProductionDetail($id)
    {
        $obj = new stdClass();
        $obj->order = DeliveredOrder::find($id);
        $packages = DeliveredPackage::where(['order_id' => $id])->get();
        $obj->packages = $packages;
        $items = [];
        foreach ($packages as $package) {
            // add a 'name' attribute to the json object cause front-end code is expecting to form dropdown content
            // from the 'name' attribute on each package
            $package['name'] = $package->getName();
            $single_package_items = DeliveredItem::where(['package_id' => $package->id])->get();
            //flatten out the array instead of having multi dimensional array
            foreach ($single_package_items as $single_package_item) {
                // $single_package_item['previously_processed_pcs'] = $single_package_item->getAlready();
                array_push($items, $single_package_item);
            }
        }
        $obj->items = $items;

        return response()->json($obj, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getHistory($delivery_id)
    {
        $wholeObj = new stdClass();
        $wholeObj->orders = [];
        $wholeObj->packages = [];
        $wholeObj->items = [];

        $selectedDeliveryOrders = DeliveredOrder::where(['original_production_order_id' => $delivery_id])->get();
        foreach ($selectedDeliveryOrders as $selectedDeliveryOrder) {
            array_push($wholeObj->orders, $selectedDeliveryOrder);

            $selectedDeliverPacks = DeliveredPackage::where(['order_id' => $selectedDeliveryOrder->id])->get();
            foreach ($selectedDeliverPacks as $selectedDeliverPack) {
                array_push($wholeObj->packages, $selectedDeliverPack);

                $selectedProductionItems = ProductionItem::where(['package_id' => $selectedDeliverPack->id])->get();
                foreach ($selectedProductionItems as $selectedProductionItem) {
                    array_push($wholeObj->items, $selectedProductionItem);
                }
            }
        }

        return response()->json($wholeObj, 200, [], JSON_NUMERIC_CHECK);
    }
}
