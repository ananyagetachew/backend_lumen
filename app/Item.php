<?php

namespace App;

class Item extends CustomModel
{
    public function getML() {
        return $this->length * $this->pcs;
    }

    public function getM2() {
        return $this->getML() * $this->width;
    }
}
