<?php

namespace Drupal\drupal_ad\Controller;

use Drupal\Core\Form\FormState;
use Drupal\drupal_ad\Model\LdapConn;
use Symfony\Component\HttpFoundation\JsonResponse;

class LdapController
{


  public function drupalUserRoles()
  {
    return new JsonResponse(array(
      'roles' => user_role_names(),
    ));
  }

  public function ldapSearchBases(): JsonResponse
  {
    $ldapConn = new LdapConn();
    $possibleSearchBases = $ldapConn->getSearchBases();
    return new JsonResponse(array(
      'possibleSearchBases' => $possibleSearchBases,
    ));
  }
  


}
