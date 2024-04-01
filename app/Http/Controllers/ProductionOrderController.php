<?php

namespace App\Http\Controllers;

use App\ProductionItem;
use App\ProductionOrder;
use App\ProductionPackage;
use App\ProformaOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;
use Illuminate\Support\Carbon;
use App\Stock;
use App\Util\FinalConstants;

class ProductionOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $res =  ProductionOrder::where('active', true)->orderByDesc('created_at')->paginate(FinalConstants::paginationLimit);
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
        $order = $request['order'];
        $packages = $request['packages'];
        $items = $request['items'];

        DB::beginTransaction();

        $newProductionOrder = new ProductionOrder();
        $newProductionOrder->original_order_id = $order['id'];
        $newProductionOrder->order_no = $order['order_no'];
        $newProductionOrder->company_name = $order['company_name'];
        $newProductionOrder->delivery_date = Carbon::now()->addDays($order['delivery_date_count']);
        $newProductionOrder->fsno = $order['fsno'];
        $newProductionOrder->note = $order['note'];
        $newProductionOrder->save();

        foreach ($packages as $deliveryPackage) {
            $newProductionPackage = new ProductionPackage();
            $newProductionPackage->order_id = $newProductionOrder->id;
            $newProductionPackage->original_package_id = $deliveryPackage['id'];
            $newProductionPackage->product_id = $deliveryPackage['product_id'];
            $newProductionPackage->unit_price = $deliveryPackage['unit_price'];
            $newProductionPackage->bullnose = $deliveryPackage['bullnose'];
            $newProductionPackage->groove = $deliveryPackage['groove'];
            $newProductionPackage->save();

            foreach ($items as $deliveryItem) {
                if ($newProductionPackage->original_package_id == $deliveryItem['package_id']) {
                    $newProductionItem = new ProductionItem();
                    $newProductionItem->original_item_id = $deliveryItem['id'];
                    $newProductionItem->package_id = $newProductionPackage->id;
                    $newProductionItem->length = $deliveryItem['length'];
                    $newProductionItem->width = $deliveryItem['width'];
                    $newProductionItem->thick = $deliveryItem['thick'];
                    $newProductionItem->pcs = $deliveryItem['pcs'];
                    $newProductionItem->remark = $deliveryItem['remark'];

                    $newProductionItem->save();
                }
            }
        }

        DB::commit();
        return response('', 200);
    }

    public function getProductionDetail($id)
    {
        $obj = new stdClass();
        $order = ProductionOrder::find($id);
        $order['proforma_no'] = $order->getProformaIfExists();
        $obj->order = $order;
        $packages = ProductionPackage::where(['order_id' => $id])->get();
        $obj->packages = $packages;
        $items = [];
        foreach ($packages as $package) {
            // add a 'name' attribute to the json object cause front-end code is expecting to form dropdown content
            // from the 'name' attribute on each package
            $package['name'] = $package->getName();
            $single_package_items = ProductionItem::where(['package_id' => $package->id])->get();
            //flatten out the array instead of having multi dimensional array
            foreach ($single_package_items as $single_package_item) {
                $single_package_item['previously_processed_pcs'] = $single_package_item->getAlreadyLoadedPcs();
                array_push($items, $single_package_item);
            }
        }

        $stockItems = Stock::where('active', true)->get();
        foreach ($items as $item) {
            $item['in_stock_pcs'] = 0;
            foreach ($stockItems as $stockItem) {
                if (
                    $stockItem->product_id == $item->getProductId() &&
                    $stockItem->width == $item->width &&
                    $stockItem->length == $item->length &&
                    $stockItem->thick == $item->thick && ($stockItem->delivery_no == $item->getDeliveryNo() || $stockItem->delivery_no == null)
                ) {
                    $orderablePcs = $item->pcs - $item->previously_processed_pcs;
                    $leftInStockPcs = $stockItem->pcs - $stockItem->loaded_pcs;
                    $item['in_stock_pcs'] += $leftInStockPcs >= $orderablePcs ? $orderablePcs : $leftInStockPcs;
                    $stockItem->pcs -= $item->in_stock_pcs;
                }
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

        $selectedProductionOrders = ProductionOrder::where(['original_order_id' => $delivery_id])->get();
        foreach ($selectedProductionOrders as $selectedProductionOrder) {
            $selectedProductionOrder['proforma_no'] = $selectedProductionOrder->getProformaIfExists();
            array_push($wholeObj->orders, $selectedProductionOrder);

            $selectedProductionPackages = ProductionPackage::where(['order_id' => $selectedProductionOrder->id])->get();
            foreach ($selectedProductionPackages as $selectedProductionPackage) {
                array_push($wholeObj->packages, $selectedProductionPackage);

                $selectedProductionItems = ProductionItem::where(['package_id' => $selectedProductionPackage->id])->get();
                foreach ($selectedProductionItems as $selectedProductionItem) {
                    array_push($wholeObj->items, $selectedProductionItem);
                }
            }
        }

        return response()->json($wholeObj, 200, [], JSON_NUMERIC_CHECK);
    }
}
