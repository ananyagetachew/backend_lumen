<?php

namespace App\Http\Controllers;

use App\DeliveredItem;
use App\FilterableDeliveryItem;
use App\FilterableProductionItem;
use App\ProductionItem;
use App\SearchableStocks;
use App\Stock;
use App\Util\FinalConstants;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterableController extends Controller
{

    public function filter(Request $request, $filterable_table)
    {
        $table_to_be_filtered = $filterable_table === 'production_items' ? new FilterableProductionItem() : new FilterableDeliveryItem();
        $items = $table_to_be_filtered->where(['active' => true]);
        // did user provide length filter?
        if ($request->length) {
            // yes, so filter by length
            $items->where(['length' => $request->length]);
        }
        // ...
        if ($request->width) {
            $items->where(['width' => $request->width]);
        }
        // ...
        if ($request->thick) {
            $items->where(['thick' => $request->thick]);
        }
        // filter by date
        $this->filterByDate($items, $request->from, $request->to);
        // execute query
        $items = $items->get();
        // filter by delivery number
        $this->filterByDeliveryNo($items, $request->delivery_no);
        // filter items by company
        $this->filterByCompanyName($items, $request->company_name);
        // filter by product
        $this->filterByProductID($items, $request->product_id);
        // deduct processed pcs from items
        $this->deductProcessedPcs($items, $table_to_be_filtered);
        // remove items with balance 0(i.e all pcs have been processed)
        $this->rejectBalance0Items($items);
        // is filtering production items?
        if ($table_to_be_filtered instanceof FilterableProductionItem) {
            // yes, so let's also append stock items that correspond
            // with the following item
            $this->addStockPcsForItems($items);
        }

        // flatten out resulting array
        $flattenArray = [];
        foreach ($items as $item) {
            array_push($flattenArray, $item);
        }
        return response()->json($flattenArray, 200, [], JSON_NUMERIC_CHECK);
    }

    // deduct processed pcs from original pcs
    private function deductProcessedPcs(&$items, $table_to_be_filtered)
    {
        foreach ($items as $item) {
            $processed_items = null;
            $table_to_be_filtered_reference = null;
            $item->original_pcs = $item->pcs;
            if ($table_to_be_filtered instanceof FilterableDeliveryItem) {
                $processed_items = ProductionItem::where(['active' => true, 'original_item_id' => $item->id])->get();
            } else {
                $processed_items = DeliveredItem::where(['active' => true, 'original_production_item_id' => $item->id])->get();
            }

            foreach ($processed_items as $processed_item) {
                $item->pcs -= $processed_item->pcs;
            }
        }
    }

    /**
     * @param $items
     * @param $delivery_no
     */
    private function filterByDeliveryNo(&$items, $delivery_no)
    {
        if ($delivery_no && $delivery_no !== 'undefined') {
            $items = $items->filter(function ($item) use ($delivery_no) {
                return $item->order_id == $delivery_no;
            });
        }
    }

    /**
     * @param $items
     * @param $product_id
     */
    private function filterByProductID(&$items, $product_id): void
    {
        if ($product_id && $product_id !== 'undefined') {
            $items = $items->filter(function ($item) use ($product_id) {
                return $item->product_id == $product_id;
            });
        }
    }

    /**
     * @param $items
     * @param $company_name
     */
    private function filterByCompanyName(&$items, $company_name): void
    {
        if ($company_name) {
            $items = $items->filter(function ($item) use ($company_name) {
                return stripos($item->company_name, $company_name);
            });
        }
    }

    // reject all items that have a balance of 0 pcs
    // meaning that they have all their pcs
    // processed, either by sending them to
    // production or delivering them
    private function rejectBalance0Items(&$items)
    {
        $items = $items->reject(function ($item) {
            return !$item->pcs;
        });
    }

    /**
     * @param $items
     * @param Request $request
     */
    private function filterByDate(&$items, $from, $to): void
    {
        if ($from && $to && $from !== 'undefined' && $to !== 'undefined') {
            // parse JavaScript date to PHP date
            $from_date = DateTime::createFromFormat('D M d Y H:i:s e+', $from);
            $to_date = DateTime::createFromFormat('D M d Y H:i:s e+', $to);
            $items->whereBetween('created_at', [$from_date, $to_date]);
        }
    }

    // add how many items are in stock that correspond to this item
    private function addStockPcsForItems(&$items)
    {
        $stockItems = Stock::where('active', true)->get();
        foreach ($items as $item) {
            $item['in_stock_pcs'] = 0;
            foreach ($stockItems as $stockItem) {
                if ($stockItem->product_id == $item->product_id &&
                    $stockItem->width == $item->width &&
                    $stockItem->length == $item->length &&
                    $stockItem->thick == $item->thick && ($stockItem->delivery_no == $item->order_id || $stockItem->delivery_no == null)
                ) {
                    $leftInStockPcs = $stockItem->pcs - $stockItem->loaded_pcs;
                    // has stock been depleted?
                    // stock depletion is calculated by checking if left-in-stock pcs is <= 0 or
                    // by if loaded-pcs are greater than manufactured pcs(which is impossible
                    // but is there just to guarantee correct program flow)
                    if ($leftInStockPcs <= 0 || $stockItem->loaded_pcs > $stockItem->pcs) {
                        // yes, if left-in-stock pcs are less than or equal to 0 that gives
                        // this stock item has been loaded completely. we need to
                        // deactivate it and for sanity check make sure it
                        // doesn't have more loaded-pcs than actual pcs
                        DB::beginTransaction();

                        $stockItem->active = false;
                        // just a corrective measure just in case we have
                        // more loaded-pcs than actual manufactured pcs
                        $stockItem->loaded_pcs = $stockItem->pcs;
                        $stockItem->save();

                        DB::commit();
                    }
                    $item['in_stock_pcs'] += $leftInStockPcs;
                }
            }
        }
    }

    public function filterStockItems(Request $request)
    {
        $stocks = SearchableStocks::where(['active' => true]);
        // did user provide a customer name filter?
        if ($request->company_name && $request->company_name != 'null') {
            // yes, so let's filter by company name
            $stocks->where('company_name', 'LIKE', '%' . $request->company_name . '%');
        }
        if ($request->delivery_no && $request->delivery_no != 'null') {
            // filter by delivery number
            $stocks->where('delivery_no', $request->delivery_no);
        }
        if ($request->name && $request->name != 'null') {
            // filter by delivery number
            $stocks->where('name', $request->name);
        }
        if ($request->length && $request->length != 'null') {
            $stocks->where('length', $request->length);
        }
        if ($request->width && $request->width != 'null') {
            $stocks->where('width', $request->width);
        }
        if ($request->thick && $request->thick != 'null') {
            $stocks->where('thick', $request->thick);
        }
        // filter by date
        $this->filterByDate($stocks, $request->from, $request->to);
        return response()->json($stocks->orderByDesc('created_at')->paginate(FinalConstants::paginationLimit * 3));
    }
}
