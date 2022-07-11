<?php

namespace Drupal\drupal_ad\Model;

use Drupal;
use Drupal\drupal_ad\Model\Response;

class LdapConn {

  public $ldapconn;

  public $bind;

  public $anon_bind;

  public $server_name;

  public $server_username;

  public $server_password;

  public $search_base;

  function __construct() {
    $this->server_name = Drupal::config('drupal_ad.settings')
      ->get('drupal_ldap_server_address') ? Utility::decrypt(Drupal::config('drupal_ad.settings')
      ->get('drupal_ldap_server_address')) : "";
    $this->server_username = Drupal::config('drupal_ad.settings')
      ->get('drupal_ldap_server_username') ? Utility::decrypt(Drupal::config('drupal_ad.settings')
      ->get('drupal_ldap_server_username')) : "";
    $this->server_password = Drupal::config('drupal_ad.settings')
      ->get('drupal_ldap_server_password') ? Utility::decrypt(Drupal::config('drupal_ad.settings')
      ->get('drupal_ldap_server_password')) : "";

  }

  /**
   * @return string
   */
  public function getServeUsername() {
    return $this->server_username;
  }

  /**
   * @return string
   */
  public function getServePassword() {
    return $this->server_password;
  }

  /**
   * @return string
   */
  public function getSearchBase() {
    return $this->search_base;
  }

  /**
   * @return string
   */
  public function getServerName() {
    return $this->server_name;
  }

  public function getLdapconn() {
    return $this->ldapconn;
  }

  public function getAnonBind() {
    $this->anon_bind = @ldap_bind($this->getLdapconn());
    return $this->anon_bind;
  }

  public function setLdapconn($ldapconn): void {
    $this->ldapconn = $ldapconn;
  }

  /**
   * @param string $server_username
   * @param bool $save
   */
  public function setServerUsername(string $server_username, bool $save = FALSE): void {
    if ($save) {
      Drupal::configFactory()
        ->getEditable('drupal_ad.settings')
        ->set('drupal_ldap_server_username', Utility::encrypt($server_username))
        ->save();
    }
    $this->server_username = $server_username;
  }

  /**
   * @param string $server_password
   * @param bool $save
   */
  public function setServerPassword(string $server_password, bool $save = FALSE): void {
    if ($save) {
      Drupal::configFactory()
        ->getEditable('drupal_ad.settings')
        ->set('drupal_ldap_server_password', Utility::encrypt($server_password))
        ->save();
    }
    $this->server_password = $server_password;
  }

  /**
   * @param string $ldapServerAddress
   * @param bool $save
   */
  public function setServerName(string $ldapServerAddress, bool $save = FALSE): void {
    if ($save) {
      Drupal::configFactory()
        ->getEditable('drupal_ad.settings')
        ->set('drupal_ldap_server_address', Utility::encrypt($ldapServerAddress))
        ->save();
    }
    $this->server_name = $ldapServerAddress;
  }


  /**
   * @return false|resource
   */
  public function getConnection() {
    $this->ldapconn = ldap_connect($this->getServerName());
    ldap_set_option($this->ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($this->ldapconn, LDAP_OPT_REFERRALS, 0);
    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
      ldap_set_option($this->ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
    }
    $this->anon_bind = $this->getAnonBind();
    if ($this->anon_bind) {
      $this->setLdapconn($this->ldapconn);
    }
    else {
      $this->ldapconn = FALSE;
    }
    return $this->ldapconn;
  }

  /**
   * Returns an array of all search Bases from AD
   */
  public function getSearchBases(): array {
    $ldap_dn = $this->getServeUsername();
    $ldap_password = $this->getServePassword();
    $ldapconn = $this->getConnection();
    $bind = @ldap_bind($ldapconn, $ldap_dn, $ldap_password);
    $searchBases = [];
    if ($bind) {
      $result = @ldap_read($ldapconn, '', '(objectclass=*)', ['namingContexts']);
      $data = @ldap_get_entries($ldapconn, $result);
      $count = $data[0]['namingcontexts']['count'];
      $base_dn = '';
      for ($i = 0; $i < $count; $i++) {
        $valuetext = $data[0]['namingcontexts'][$i];
        if ($i == 0) {
          $base_dn = $valuetext;
        }
        $searchBases[] = $valuetext;
      }
      $filter = "(|(objectclass=organizationalUnit)(&(objectClass=top)(cn=users)))";
      $search_attr = ["dn", "ou"];
      $ldapsearch = ldap_search($ldapconn, $base_dn, $filter, $search_attr);
      $info = ldap_get_entries($ldapconn, $ldapsearch);

      for ($i = 0; $i < $info["count"]; $i++) {
        $searchBases[] = $info[$i]["dn"];
      }
      return $searchBases;
    }
    return $searchBases;
  }

  /**
   * @param $username
   * @param $password
   *
   * @return \Drupal\drupal_ad\Model\Response
   */
  public function ldapLogin($username, $password): \Drupal\drupal_ad\Model\Response {
    $ldapConnection = $this->getConnection();
    $ldapResponse = new Response();
    if ($ldapConnection) {
      $searchBase = Utility::decrypt(Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_custom_base'));
      $usernameAttribute = Utility::decrypt(Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_custom_username_attribute'));
      $searchFilter = str_replace('?', $username, '(&(objectClass=*)(' . $usernameAttribute . '=?))');
      $searchResult = $userInfo = $userEntry = NULL;
      $userAttributes = $this->getUserAttributes($usernameAttribute);
      if ($bind = @ldap_bind($ldapConnection, $this->getServeUsername(), $this->getServePassword())) {
        if (strpos($searchBase, ";") !== FALSE) {
          // search using an array
          $searchBases = explode(";", $searchBase); // premium feature
          foreach ($searchBases as $searchBaseString) {
            if (!@ldap_search($ldapConnection, $searchBaseString, $searchFilter)) {
              $ldapResponse->status = FALSE;
              $ldapResponse->message = 'NOT_EXIST';
              return $ldapResponse;
            }
            [
              $userInfo,
              $userEntry,
            ] = $this->getUserInfo($ldapConnection, $searchBaseString, $searchFilter, $userAttributes);
            if ($userInfo) {
              break;
            }
          }
        }
        else { // search using a string
          if (!@ldap_search($ldapConnection, $searchBase, $searchFilter)) {
            $ldapResponse->status = FALSE;
            $ldapResponse->message = Response::NOT_EXIST;
            $ldapResponse->messageDetails = ldap_error($ldapConnection);
            return $ldapResponse;
          }
          [
            $userInfo,
            $userEntry,
          ] = $this->getUserInfo($ldapConnection, $searchBase, $searchFilter, $userAttributes);
        }

        if ($userInfo) {
          $userDn = ldap_get_dn($ldapConnection, $userInfo);
          $authResponse = $this->ldapAuthenticate($userDn, $password);
          if ($authResponse->message == Response::SUCCESS) {
            foreach ($userAttributes as $attribute) {
              $userRecord = $userEntry[0];
              $authResponse->userAttributes['drupal_ldap_' . $attribute] = (array_key_exists(strtolower($attribute), $userRecord)) ? $userRecord[strtolower($attribute)][0] : '';
              $authResponse->profileAttributes[strtolower($attribute)] = (array_key_exists(strtolower($attribute), $userRecord)) ? $userRecord[strtolower($attribute)][0] : '';
            }
          }
          return $authResponse;
        }
        $ldapResponse->status = FALSE;
        $ldapResponse->message = Response::NOT_EXIST;
        $ldapResponse->messageDetails = ldap_error($ldapConnection);
        return $ldapResponse;
      }
      $ldapResponse->status = FALSE;
      $ldapResponse->message = Response::BIND_ERROR;
      $ldapResponse->messageDetails = ldap_error($ldapConnection);
      return $ldapResponse;
    }
    $ldapResponse->status = FALSE;
    $ldapResponse->message = Response::CONNECTION_ERROR;
    $ldapResponse->messageDetails = ldap_error($ldapConnection);
    return $ldapResponse;

  }

  /**
   * @param $userDn
   * @param $password
   *
   * @return \Drupal\drupal_ad\Model\Response
   */
  public function ldapAuthenticate($userDn, $password): Response {
    $this->ldapconn = ldap_connect($this->server_name);
    $this->bind = ldap_bind($this->ldapconn, $userDn, $password);
    $usernameAttribute = Utility::decrypt(Drupal::config('drupal_ad.settings')
      ->get('drupal_ldap_custom_username_attribute'));
    $searchFilter = str_replace('?', $userDn, '(&(objectClass=*)(' . $usernameAttribute . '=?))');
    $ldapResponse = new Response();
    if ($this->bind) {
      $searchResult = ldap_search($this->ldapconn, $userDn, $searchFilter);
      $ldapResponse->status = TRUE;
      $ldapResponse->message = Response::SUCCESS;
      $ldapResponse->userDn = $userDn;
      return $ldapResponse;
    }
    $ldapResponse->status = FALSE;
    $ldapResponse->messageDetails = ldap_error($this->ldapconn);
    $ldapResponse->message = Response::BIND_ERROR;
    $ldapResponse->userDn = $userDn;
    return $ldapResponse;
  }

  /**
   * @param $ldapConnection
   * @param $searchBase
   * @param $searchFilter
   * @param array $userAttributes
   *
   * @return array
   */
  public function getUserInfo($ldapConnection, $searchBase, $searchFilter, array $userAttributes): array {
    $searchResult = @ldap_search($ldapConnection, $searchBase, $searchFilter, $userAttributes);
    $userInfo = @ldap_first_entry($ldapConnection, $searchResult);
    $userEntry = @ldap_get_entries($ldapConnection, $searchResult);
    return [$userInfo, $userEntry];
  }

  /**
   * @param $usernameAttribute
   * Mapping user attributes
   * @return array
   */
  public function getUserAttributes($usernameAttribute): array {
    // mapping user attributes
    $email_attribute = Drupal::configFactory()
      ->getEditable('drupal_ad.settings')
      ->get('drupal_ldap_email_attribute');
    $fname_attribute = Drupal::configFactory()
      ->getEditable('drupal_ad.settings')
      ->get('drupal_ldap_fname_attribute');
    $lname_attribute = Drupal::configFactory()
      ->getEditable('drupal_ad.settings')
      ->get('drupal_ldap_lname_attribute');
    $phone_attribute = Drupal::configFactory()
      ->getEditable('drupal_ad.settings')
      ->get('drupal_ldap_phone_attribute');
    $email_domain_attribute = Drupal::configFactory()
      ->getEditable('drupal_ad.settings')
      ->get('drupal_ldap_email_domain_attribute');

    $userAttributes[] = $usernameAttribute;
    if (isset($email_attribute) && !empty($email_attribute)) {
      $userAttributes[] = $email_attribute;
    }
    if (isset($fname_attribute) && !empty($fname_attribute)) {
      $userAttributes[] = $fname_attribute;
    }
    if (isset($lname_attribute) && !empty($lname_attribute)) {
      $userAttributes[] = $lname_attribute;
    }
    if (isset($fname_attribute) && !empty($fname_attribute)) {
      $userAttributes[] = $fname_attribute;
    }
    if (isset($phone_attribute) && !empty($phone_attribute)) {
      $userAttributes[] = $phone_attribute;
    }
    return $userAttributes;
  }


}
