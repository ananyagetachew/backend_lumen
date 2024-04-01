<?php

namespace App;

class Package extends CustomModel
{
    // all packages need to have on the fly access to the name of the product
    // that is linked to product by 'product_id'
    // every package(DeliveryPackage, ProfromaPackage, ProductionPackage...) inherit this method
    public function getName()
    {
        $product = Product::find($this->product_id);
        return $product ? $product->name : "error_product_not_found";
    }
}
