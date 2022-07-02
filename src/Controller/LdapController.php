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

  /**
   * @throws \Drupal\Core\Form\FormAjaxException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   */
  public function ldapSearchBasesMarkUp()
  {
    $ldapConn = new LdapConn();
    $possibleSearchBases = $ldapConn->getSearchBases();
    $searchBasesList = '<p>Here are some possible ldap search bases in your directory. Copy and paste in custom search base field!</p>';
    $searchBasesList .= '<div class="container-fluid"><ol class="list--group list-group-numbered">';
    foreach ($possibleSearchBases as $possibleSearchBase){
      $searchBasesList .= '<li class="list--group-item">'. $possibleSearchBase .'</li>';
    }
    $searchBasesList .= '</ol></div>';
    return [
      '#markup' => $searchBasesList,
      '#description' => '<p>This is html markup</p>',
    ];
  }


}
