<?php

namespace Drupal\drupal_ad\Model;

class Response
{

  public bool $status;

  public string $message;

  public string $userDn;

  public array $userAttributes;

  public $profileAttributes;

  public function __construct()
  {
    //Empty constructor
  }

}
