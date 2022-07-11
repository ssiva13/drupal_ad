<?php

namespace Drupal\drupal_ad\Model;

class Response
{

  const SUCCESS = 'SUCCESS';

  const NOT_EXIST = 'NOT_EXIST';

  const BIND_ERROR = 'BIND_ERROR';

  const CONNECTION_ERROR = 'CONNECTION_ERROR';

  public bool $status;

  public string $messageDetails;

  public string $message;

  public string $userDn;

  public array $userAttributes;

  public $profileAttributes;

  public function __construct()
  {
    //Empty constructor
  }

}
