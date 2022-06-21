<?php

namespace Drupal\App;

use Drupal\app\Support;

class Response {

    public $status;

    public $message;

    public $userDn;

    public $attributeList;

    public $profileAttributesList;

    public function __construct()
    {
        //Empty constructor
    }

}