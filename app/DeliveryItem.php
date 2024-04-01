<?php

namespace App;

class DeliveryItem extends Item
{

    public function getAlreadyOrderedPcs()
    {
        $alreadyOrderedPcs = 0;
        $orderedPieces = ProductionItem::where(['original_item_id' => $this->id])->get();
        foreach ($orderedPieces as $orderedPiece) {
            $alreadyOrderedPcs += $orderedPiece->pcs;
        }
        return $alreadyOrderedPcs;
    }

    public function getDeliveryNo()
    {
        $deliveryOrderId = DeliveryPackage::find($this->package_id)->order_id;
        return DeliveryOrder::find($deliveryOrderId)->id;
    }

    public function getProductId()
    {
        return DeliveryPackage::find($this->package_id)->product_id;
    }

    public function getDeliveryCompanyName()
    {
        $deliveryOrderId = DeliveryPackage::find($this->package_id)->order_id;
        return DeliveryOrder::find($deliveryOrderId)->company_name;
    }
}
