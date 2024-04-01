<?php

namespace App;

class FilterableDeliveryItem extends Item
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

}
