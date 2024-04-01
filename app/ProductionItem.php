<?php

namespace App;

class ProductionItem extends Item
{
    /*
        function should probably be renamed since its not only providing already loaded pcs anymore but
        also providing if item has a matching item in stock so we can deduct from stock and load out
    */
    public function getAlreadyLoadedPcs()
    {
        $alreadyLoadedPcs = 0;
        $loadedPcs = DeliveredItem::where(['original_production_item_id' => $this->id])->get();
        foreach ($loadedPcs as $loadedPc) {
            $alreadyLoadedPcs += $loadedPc->pcs;
        }
        return $alreadyLoadedPcs;
    }

    public function getProductId()
    {
        return ProductionPackage::find($this->package_id)->product_id;
    }

    public function getDeliveryNo()
    {
        $productionOrderId = ProductionPackage::find($this->package_id)->order_id;
        return ProductionOrder::find($productionOrderId)->original_order_id;
    }

    public function getDeliveryCompanyName()
    {
        $productionOrderId = ProductionPackage::find($this->package_id)->order_id;
        return ProductionOrder::find($productionOrderId)->company_name;
    }
}
