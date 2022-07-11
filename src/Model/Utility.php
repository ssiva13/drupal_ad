<?php

namespace Drupal\drupal_ad\Model;

use Drupal;
use Drupal\Core\Form\FormStateInterface;

//use Drupal\drupal_ad\Model\AttributeMapping;

class Utility
{


  private static string $cipher = "AES-128-CBC";
  private static int $options = OPENSSL_RAW_DATA;
  private static bool $as_binary = true;
  private static int $sha2len = 32;


  /**
   * @param $string
   * Encrypt string.
   * @return string
   */
  public static function encrypt($string): string {
    $key = Drupal::config('drupal_ad.settings')->get('drupal_ldap_encryption_token');
    $ivlen = openssl_cipher_iv_length(self::$cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($string, Utility::$cipher, $key, Utility::$options, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, $key, Utility::$as_binary);
    return base64_encode($iv . $hmac . $ciphertext_raw);
  }

  /**
   * @param $cipherString
   *
   * @return false|string
   */
  public static function decrypt($cipherString)
  {
    if($cipherString) {
      $key = Drupal::config('drupal_ad.settings')
        ->get('drupal_ldap_encryption_token');
      $c = base64_decode($cipherString);
      $ivlen = openssl_cipher_iv_length(self::$cipher);
      $iv = substr($c, 0, $ivlen);
      $hmac = substr($c, $ivlen, self::$sha2len);
      $ciphertext_raw = substr($c, $ivlen + self::$sha2len);
      $original_plaintext = openssl_decrypt($ciphertext_raw, self::$cipher, $key, self::$options, $iv);
      $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, self::$as_binary);
      return hash_equals($hmac, $calcmac) ? $original_plaintext : 'Decryption Error';
    }
    return '';
  }

  /**
   * Check if a php extension is installed.
   */
  public static function isExtensionInstalled($extension): bool
  {
    if (in_array($extension, get_loaded_extensions())) {
      return true;
    }
    return false;
  }

  public static function get_drupal_version()
  {
    return \DRUPAL::VERSION[0];
  }

  public static function drupal_is_cli()
  {
    $server = \Drupal::request()->server;
    $server_software = $server->get('SERVER_SOFTWARE');
    $server_argc = $server->get('argc');
    if (!isset($server_software) && (php_sapi_name() == 'cli' || (is_numeric($server_argc) && $server_argc > 0))) {
      return true;
    }
    return false;
  }

  /**
   * Shows support block
   */
  public static function add_message($_message, $type, $form_state = NULL)
  {
    if ($type == 'form_error' && $form_state != NULL) {
      $form_state->setErrorByName('name', $_message);
      return;
    }
    Drupal::messenger()->addMessage($_message, $type);
  }

  /**
   * @param $inputValue
   * @param $inputElement
   * @param FormStateInterface $form_state
   * @return void
   */
  public function setFormError($inputElement, $inputValue, FormStateInterface $form_state): void
  {
    if ($inputValue === '') {
      $inputLabel = str_replace('_', ' ', str_replace('drupal_ldap_', '', $inputElement));
      $form_state->setErrorByName($inputElement, 'You must provide a value for ldap ' . ucwords($inputLabel) . ' !');
    }
  }


}
