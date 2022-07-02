<?php

namespace Drupal\drupal_ad\Model;

class LdapConn
{
  public $ldapconn;
  public $bind;
  public $anon_bind;
  public $server_name;
  public $server_username;
  public $server_password;
  public $search_base;
  public $search_filter;
  public $custom_base;

  function __construct()
  {
    $this->server_name = \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_address') ? \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_address') : "";
    $this->server_username = \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_username') ? \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_username') : "";
    $this->server_password = \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_password') ? \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_password') : "";

  }


  /**
   * @return string
   */
  public function getServeUsername()
  {
    return $this->server_username;
  }

  /**
   * @return string
   */
  public function getServePassword()
  {
    return $this->server_password;
  }

  /**
   * @return string
   */
  public function getSearchBase()
  {
    return $this->search_base;
  }

  /**
   * @return string
   */
  public function getServerName()
  {
    return $this->server_name;
  }

  public function getLdapconn()
  {
    return $this->ldapconn;
  }

  public function getAnonBind()
  {
    $this->anon_bind = @ldap_bind($this->getLdapconn());
    return $this->anon_bind;
  }


  public function setLdapconn($ldapconn): void
  {
    $this->ldapconn = $ldapconn;
  }

  /**
   * @param string $server_username
   * @param bool $save
   */
  public function setServerUsername(string $server_username, bool $save = false): void
  {
    if ($save) {
      \Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_server_username', $server_username)->save();
    }
    $this->server_username = $server_username;
  }

  /**
   * @param string $server_password
   * @param bool $save
   */
  public function setServerPassword(string $server_password, bool $save = false): void
  {
    if ($save) {
      \Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_server_password', $server_password)->save();
    }
    $this->server_password = $server_password;
  }

  /**
   * @param string $ldapServerAddress
   * @param bool $save
   */
  public function setServerName(string $ldapServerAddress, bool $save = false): void
  {
    if ($save) {
      \Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_server_address', $ldapServerAddress)->save();
    }
    $this->server_name = $ldapServerAddress;
  }


  /**
   * @return false|resource
   */
  public function getConnection()
  {
    $this->ldapconn = ldap_connect($this->getServerName());
    ldap_set_option($this->ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($this->ldapconn, LDAP_OPT_REFERRALS, 0);
    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
      ldap_set_option($this->ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
    }
    $this->anon_bind = $this->getAnonBind();
    if ($this->anon_bind) {
      $this->setLdapconn($this->ldapconn);
    } else {
      $this->ldapconn = false;
    }
    return $this->ldapconn;
  }

  /**
   * Returns an array of all search Bases from AD
   */
  public function getSearchBases(): array
  {
    $ldap_dn = $this->getServeUsername();
    $ldap_password = $this->getServePassword();
    $ldapconn = $this->getConnection();
    $bind = @ldap_bind($ldapconn, $ldap_dn, $ldap_password);
    $searchBases = [];
    if ($bind) {
      $result = @ldap_read($ldapconn, '', '(objectclass=*)', array('namingContexts'));
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
      $search_attr = array("dn", "ou");
      $ldapsearch = ldap_search($ldapconn, $base_dn, $filter, $search_attr);
      $info = ldap_get_entries($ldapconn, $ldapsearch);

      for ($i = 0; $i < $info["count"]; $i++) {
        $searchBases[] = $info[$i]["dn"];
      }
      return $searchBases;
    }
    return $searchBases;
  }




}
