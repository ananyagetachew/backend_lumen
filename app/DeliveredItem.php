<?php

namespace App;

class DeliveredItem extends CustomModel
{
    public function getProductId()
    {
        return DeliveredPackage::find($this->package_id)->product_id;
    }
}
