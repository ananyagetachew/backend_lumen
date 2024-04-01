<?php

namespace App;

class ProductionOrder extends CustomModel
{
    public function getProformaIfExists()
    {
        return DeliveryOrder::find($this->original_order_id)->proforma_no;
    }
}
