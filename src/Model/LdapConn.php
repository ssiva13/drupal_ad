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

  function __construct( ) {
    $this->server_name = \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server') ? \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server') : "";
    $this->server_username = \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_username') ? \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_account_username') : "";
    $this->server_password = \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_password') ? \Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_account_password') : "";

  }

}
