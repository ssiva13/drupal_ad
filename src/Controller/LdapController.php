<?php

namespace Drupal\drupal_ad\Controller;

class LdapController
{


    public function welcome()
    {
        return array(
          '#type' => 'markup',
          '#markup' => 'Active Directory for Drupal.'
        );
    }


}
