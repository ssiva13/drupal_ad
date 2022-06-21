<?php

namespace Drupal\App;

use Drupal\app\Support;
use Drupal\Core\Form\FormStateInterface;
use Drupal\app\handler;
use Drupal\app\AttributeMapping;

class Utility {


    private $cipher = "AES-128-CBC";
    private $options = OPENSSL_RAW_DATA;
    private $as_binary = true;
    private $sha2len = 32;

    
    /**
     * Encrypt.
     */
    public static function encrypt($string)
    {
        $key = Utility::get_variable('ldap_admin_token');
        // $key = openssl_random_pseudo_bytes(32);

        $ivlen = openssl_cipher_iv_length(Utility::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($string, Utility::$cipher, $key, Utility::$options, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, Utility::$as_binary);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);

        return $ciphertext;
    }

    public static function decrypt($cipherString){
        $key = Utility::get_variable('ldap_admin_token');
        // $key = openssl_random_pseudo_bytes(32);

        $c = base64_decode($cipherString);
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, self::$sha2len);
        $ciphertext_raw = substr($c, $ivlen + self::$sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, self::$cipher, $key, self::$options, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, self::$as_binary);

    }

    public static function get_variable($variable)
    {
        return variable_get($variable, NULL);
    }

    /**
     * Check if a php extension is installed.
     */
    public static function isExtensionInstalled($extension)
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
        if (!isset($server_software) && (php_sapi_name() == 'cli' || (is_numeric($server_argc) && $server_argc > 0))){
            return true;
        }
        return false;
    }

    // http://worldtimeapi.org/api/timezone
    // http://worldtimeapi.org/api
}