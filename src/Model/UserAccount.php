<?php

namespace Drupal\drupal_ad\Model;

use Drupal;
use Drupal\user\Entity\User;
use Drupal\drupal_ad\Model\Response as LdapResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class UserAccount {

  use StringTranslationTrait;

  public LdapConn $ldapConn;

  public function __construct() {
    $this->ldapConn = new LdapConn();
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createAccount($username, $password, $form_state) {
    global $base_url;
    $authResponse = $this->ldapConn->ldapLogin($username, $password);
    if ($authResponse->message === LdapResponse::SUCCESS) {
      $ldapUsernameAttribute = Utility::decrypt(Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_custom_username_attribute'));
      $ldapEmailAttribute = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_email_attribute');
      $ldapEmailDomain = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_email_domain_attribute');
      $ldapEnableRoleMapping = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_enable_role_mapping');
      $defaultRole = ['anonymous'];
      if ($ldapEnableRoleMapping) {
        $ldapDefaultRole = Drupal::config('drupal_ad.settings')
          ->get('drupal_ldap_default_role');
        $defaultRole = ($ldapDefaultRole) ? [$defaultRole] : $defaultRole;
      }
      $userEmail = ($ldapEmailAttribute) ? $authResponse->profileAttributes[strtolower($ldapEmailAttribute)] : $authResponse->profileAttributes[$ldapUsernameAttribute] . $ldapEmailDomain;
      $newUser = [
        'name' => $authResponse->profileAttributes[strtolower($ldapUsernameAttribute)],
        'pass' => $password,
        'mail' => $userEmail,
        'roles' => $defaultRole,
        'access' => '0',
        'preferred_langcode' => 'en',
        'status' => 1,
        'notify' => TRUE,
      ];

      if ($account = User::create($newUser)) {
        $account->save();
         //_user_mail_notify('status_activated', $account);
        Utility::add_message($this->t('@username User Account Created Successfully.', ['@username' => ucwords($account->getDisplayName())]), 'status');
        return $this->finalizeLogin($account, $base_url, $authResponse);
      }
      else {
        Utility::add_message($this->t('Your user could not be created in the Drupal. Please contact your administrator.'), 'form_error', $form_state);
      }
    }
    elseif ($authResponse->message === LdapResponse::NOT_EXIST) {
      Utility::add_message($this->t('There is no ldap user with the provided username! <strong> @username </strong>', ['@username' => ucwords($username)]), 'form_error', $form_state);
    }
    elseif ($authResponse->message === LdapResponse::BIND_ERROR) {
      Utility::add_message($this->t('There is an error contacting the LDAP server @messageDetails. Please check your configurations or contact the administrator.', ['@messageDetails' => $authResponse->messageDetails]), 'form_error', $form_state);
    }
    else {
      Utility::add_message($this->t('@message Invalid username or incorrect password. Please try again.', ['@message' => $authResponse->message]), 'form_error', $form_state);
    }
    return FALSE;
  }

  public function processUserLogin($username, $password, $form_state, $account) {
    global $base_url;

    if ($account->hasRole('administrator')) {
      //admin configs
      $multiAuthAdmins = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_enable_auth_admin');
      $adminDrupalOnly = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_admin_drupal_only');
      $adminADOnly = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_admin_ad_only');

      if ($adminADOnly) {

        $authResponse = $this->ldapConn->ldapLogin($username, $password);
        if ($authResponse->message === LdapResponse::SUCCESS) {
          Utility::add_message($this->t('@username Logged in Successfully via AD!', ['@username' => $account->getDisplayName()]), 'status');
          return $this->finalizeLogin($account, $base_url, $authResponse);
        }
        elseif ($authResponse->message === LdapResponse::NOT_EXIST) {
          Utility::add_message($this->t('There is no ldap user with the provided username! <strong> @username </strong>', ['@username' => ucwords($username)]), 'form_error', $form_state);
        }
        else {
          Utility::add_message($this->t('Invalid username or incorrect password. Please try again.'), 'form_error', $form_state);
        }

      }elseif ($adminDrupalOnly){
        $this->drupalLogin($username, $password);
      }elseif($multiAuthAdmins){

        $authResponse = $this->ldapConn->ldapLogin($username, $password);
        if ($authResponse->message === LdapResponse::SUCCESS) {
          Utility::add_message($this->t('@username Admin Logged in Successfully via AD!', ['@username' => $account->getDisplayName()]), 'status');
          return $this->finalizeLogin($account, $base_url, $authResponse);
        }
        else {
          $this->drupalLogin($username, $password);
        }

      }else{
        Utility::add_message($this->t('Invalid username or incorrect password. Please try again.'), 'form_error', $form_state);
      }

    }
    else {

      //user configs
      $multiAuthUsers = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_enable_auth_users');
      $userDrupalOnly = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_user_drupal_only');
      $userADOnly = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_user_ad_only');

      if ($userADOnly) {

        $authResponse = $this->ldapConn->ldapLogin($username, $password);
        if ($authResponse->message === LdapResponse::SUCCESS) {
          Utility::add_message($this->t('@username Admin Logged in Successfully via AD!', ['@username' => $account->getDisplayName()]), 'status');
          return $this->finalizeLogin($account, $base_url, $authResponse);
        }
        elseif ($authResponse->message === LdapResponse::NOT_EXIST) {
          Utility::add_message($this->t('There is no ldap user with the provided username! <strong> @username </strong>', ['@username' => ucwords($username)]), 'form_error', $form_state);
        }
        else {
          Utility::add_message($this->t('Invalid username or incorrect password. Please try again.'), 'form_error', $form_state);
        }

      }elseif ($userDrupalOnly){
        $this->drupalLogin($username, $password);

      }elseif($multiAuthUsers){
        $authResponse = $this->ldapConn->ldapLogin($username, $password);
        if ($authResponse->message === LdapResponse::SUCCESS) {
          Utility::add_message($this->t('@username logged in Successfully via ldap!', ['@username' => $account->getDisplayName()]), 'status');
          return $this->finalizeLogin($account, $base_url, $authResponse);
        }
        else {
          $this->drupalLogin($username, $password);
        }
      }else{
        Utility::add_message($this->t('Invalid username or incorrect password. Please try again.'), 'form_error', $form_state);
      }

    }
    return FALSE;
  }

  /**
   * @param \Drupal\user\Entity\User $account
   * @param $base_url
   * @param $authResponse
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function finalizeLogin(User $account, $base_url, $authResponse): HttpResponse {
    Drupal::configFactory()
      ->getEditable('drupal_ad.settings')
      ->set('ldap_drupal_login', $authResponse->message)
      ->save();

    user_login_finalize($account);
    $response = new RedirectResponse($base_url);
    $request = Drupal::request();
    $request->getSession()->save();
    $response->prepare($request);
    Drupal::service('kernel')->terminate($request, $response);
    $response->send();
    return new HttpResponse();
  }

  /**
   * @param $username
   * @param $password
   *
   * @return void
   */
  public function drupalLogin($username, $password): void {
    $userId = Drupal::service('user.auth')->authenticate($username, $password);
    $user = User::load($userId);
    Utility::add_message($this->t('@username Logged in Successfully!', ['@username' => $user->getDisplayName()]), 'status');
  }
}
