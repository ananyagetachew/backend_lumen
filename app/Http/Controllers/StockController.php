<?php

namespace App\Http\Controllers;

use App\DeliveredOrder;
use App\DeliveredPackage;
use App\DeliveryOrder;
use App\DeliveryPackage;
use App\FilterableDeliveredItem;
use App\FilterableDeliveryItem;
use App\FilterableProductionItem;
use App\Product;
use App\ProductionOrder;
use App\ProductionPackage;
use App\SearchableStocks;
use App\Stock;
use App\Util\FinalConstants;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // for the reason that stocks don't hold much info, we can render
        // three times the threshold set for ordinary pagination
        return SearchableStocks::where('active', 1)->orderByDesc('created_at')->paginate(FinalConstants::paginationLimit * 3);
    }

    public function search($query)
    {
        $res = SearchableStocks::where('active', true)->where('company_name', 'LIKE', '%' . $query . '%')
            ->orWhere('order_no', 'LIKE', '%' . $query . '%')->orderByDesc('created_at')
            ->paginate(FinalConstants::paginationLimit);
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

        $stockItems = Stock::where('active', 1)->get();
        foreach ($stockItems as $stock) {
            if (
                $stock->product_id == $request->product_id &&
                $stock->delivery_no == $request->delivery_no &&
                $stock->length == $request->length &&
                $stock->width == $request->width &&
                $stock->thick == $request->thick
            ) {
                $stock->pcs += $request->pcs;
                $stock->save();
                return $stock->id;
            }
        }
        $newStockItem = new Stock();
        $newStockItem->delivery_no = $request->delivery_no;
        $newStockItem->product_id = $request->product_id;
        $newStockItem->length = $request->length;
        $newStockItem->width = $request->width;
        $newStockItem->thick = $request->thick;
        $newStockItem->pcs = $request->pcs;
        $newStockItem->save();

        DB::commit();
        return $newStockItem->id;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Example $example
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        DB::beginTransaction();

        $dn = $request->delivery_no;
        $s_id = $request->s_id;

        DB::table('Stocks')->where('id', $s_id)->update(['delivery_no' => $dn]);
        DB::commit();
        return response()->json($request->delivery_no, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getProducts()
    {
        return Product::all();
    }

    public function getDeliveryNos()
    {
        $delivery_nos = DeliveryOrder::select('id', 'order_no')->where('sent_to_production', true)->get();
        return response()->json($delivery_nos, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getAggregateStockStatus()
    {
        $all_stocks = SearchableStocks::where('active', true)->get();
        $aggregatedStatus = null;
        $all_stocks_with_prices = $this->getPrices($all_stocks->pluck('id'));
        foreach ($all_stocks as $stock) {
            $stock_name = $stock->name;
            $m2 = $stock->length * $stock->width * ($stock->pcs - $stock->loaded_pcs);
            if (isset($aggregatedStatus[$stock_name])) {
                $aggregatedStatus[$stock_name]['pcs'] += $stock->pcs - $stock->loaded_pcs;
                $aggregatedStatus[$stock_name]['m2'] += $m2;
                $aggregatedStatus[$stock_name]['price'] += $all_stocks_with_prices[$stock->id] ?? 0;
            } else {
                $aggregatedStatus[$stock_name]['pcs'] = $stock->pcs - $stock->loaded_pcs;
                $aggregatedStatus[$stock_name]['m2'] = $m2;
                $aggregatedStatus[$stock_name]['price'] = $all_stocks_with_prices[$stock->id] ?? 0;
            }
        }
        return response()->json($aggregatedStatus, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getPriceForEachStock(Request $request)
    {
        if ($request->stockIds) {
            $stocks_with_unit_price = $this->getPrices($request->stockIds);
            return response()->json($stocks_with_unit_price, 200, [], JSON_NUMERIC_CHECK);
        }
        return response()->json([]);
    }

    private function getPrices($stock_ids)
    {
        $stocks_with_unit_price = [];
        $stocks = Stock::find($stock_ids);
        foreach ($stocks as $stock) {
            $delivery_item = FilterableDeliveryItem::where(function ($query) use ($stock) {
                $query->where([
                    'active' => true, 'product_id' => $stock->product_id,
                    'width' => $stock->width, 'length' => $stock->length, 'thick' => $stock->thick
                ]);
            })->where(function ($query) use ($stock) {
                $query->where('order_id', $stock->delivery_no)->orWhere('order_id', null);
            })->first();

            // in-order to retrieve stock item's price we need the matching delivery item
            // then deduce the package's unit price as stock item's price
            if ($delivery_item) {
                $package = DeliveryPackage::find($delivery_item->package_id);
                $ml = $stock->length * $stock->pcs;
                $m2 = $stock->width * $ml;
                $bullnose_price = $ml * $package->bullnose;
                $groove_price = $ml * $package->groove;
                $total_item_price = $bullnose_price + $groove_price + ($m2 * $package->unit_price);
                $stocks_with_unit_price[$stock->id] = $total_item_price;
            }
        }
        return $stocks_with_unit_price;
    }

    public function getOrdersAggregateFinanceReport(Request $request, $type)
    {
        $order = $order_package = $order_item = null;
        if ($type === 'delivery_orders') {
            $order = DeliveryOrder::where('active', true);
            $order_package = new DeliveryPackage();
            $order_item = new FilterableDeliveryItem();
        } else if ($type === 'production_orders') {
            $order = ProductionOrder::where('active', true);
            $order_package = new ProductionPackage();
            $order_item = new FilterableProductionItem();
        } else if ($type === 'delivered_orders') {
            $order = DeliveredOrder::where('active', true);
            $order_package = new DeliveredPackage();
            $order_item = new FilterableDeliveredItem();
        }
        // filter by date
        if (
            $request->from && $request->to &&
            $request->from !== 'undefined' && $request->to !== 'undefined'
        ) {
            // parse JavaScript date to PHP date
            $from_date = DateTime::createFromFormat('D M d Y H:i:s e+', $request->from);
            $to_date = DateTime::createFromFormat('D M d Y H:i:s e+', $request->to);
            $order->whereBetween('created_at', [$from_date, $to_date]);
        }
        // filter by company name, if one is present
        if ($request->company_name && $request->company_name !== 'null')
            $order->where('company_name', 'LIKE', '%' . $request->company_name . '%');

        // filter out by delivery no, if one is present
        if ($request->delivery_no && $request->delivery_no != '')
            $order->where('order_no', $request->delivery_no);

        $aggregatedStatus = $this->getAggregatedOrderData($order, $order_package, $order_item);
        return response()->json($aggregatedStatus, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * @param $order
     * @param $order_package
     * @param $order_item
     * @return null
     */
    private function getAggregatedOrderData(&$order, &$order_package, &$order_item)
    {
        $aggregatedStatus = null;
        $orders = $order->get();
        foreach ($orders as $order) {
            $order_no = $order->order_no;
            $order_items = $order_item::where(['active' => true, 'order_id' => $order->id])->get();
            foreach ($order_items as $item) {
                $package = $order_package::select(['bullnose', 'groove', 'unit_price'])->find($item->package_id);
                $bullnose_price = $item->getML() * $package->bullnose;
                $groove_price = $item->getM2() * $package->groove;
                $total_item_price = $bullnose_price + $groove_price + ($item->getM2() * $package->unit_price);
                if (isset($aggregatedStatus[$order_no])) {
                    $aggregatedStatus[$order_no]['price'] += $total_item_price;
                } else {
                    // initialize
                    $aggregatedStatus[$order_no]['price'] = $total_item_price;
                    $aggregatedStatus[$order_no]['id'] = $order->id;
                    $aggregatedStatus[$order_no]['company_name'] = $order->company_name;
                }
            }
        }
        return $aggregatedStatus;
    }
}
