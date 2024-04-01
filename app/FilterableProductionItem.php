<?php

namespace App;

class FilterableProductionItem extends Item
{

    public function getAlreadyOrderedPcs()
    {
        $alreadyOrderedPcs = 0;
        $orderedPieces = DeliveredItem::where(['original_production_item_id' => $this->id])->get();
        foreach ($orderedPieces as $orderedPiece) {
            $alreadyOrderedPcs += $orderedPiece->pcs;
        }
        return $alreadyOrderedPcs;
    }

}
