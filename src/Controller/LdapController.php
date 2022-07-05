<?php

namespace Drupal\drupal_ad\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\drupal_ad\Model\LdapConn;
use Symfony\Component\HttpFoundation\JsonResponse;

class LdapController extends ControllerBase
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
  public function ldapSearchBasesMarkUp(): array {
    $ldapConn = new LdapConn();
    $possibleSearchBases = $ldapConn->getSearchBases();
    $searchBasesList = '<p class="message message--primary">Here are some possible ldap search bases in your directory. Click to copy and paste in custom search base field! -  '. count($possibleSearchBases) .' ldap search bases</p>';
    $searchBasesList .= '<div class="container-fluid"><ol class="list--group list--group-numbered">';
    foreach ($possibleSearchBases as $possibleSearchBase){
      $searchBasesList .= '<li title="Copy  '. $possibleSearchBase .' " class="list--group-item d--flex js--listcopybtn" > '. $possibleSearchBase .'  <i class="fas fa-clipboard list--btn"></i> </li>';
    }
    $searchBasesList .= '</ol></div>';
    return [
      '#markup' => $searchBasesList,
    ];
  }


}
