<?php

namespace App;

class User extends CustomModel
{
    

    public function getAPIToken()
    {
        return $this->api_token;
    }
}
