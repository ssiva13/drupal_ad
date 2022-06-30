<?php

namespace Drupal\drupal_ad\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupal_ad\Model\LdapConn;
use Drupal\drupal_ad\Model\Utility;

class DrupalLdapForm extends FormBase
{
  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler;

  public function __construct()
  {
    $this->moduleHandler = $moduleHandler = \Drupal::service('module_handler');
  }

  /**
     * @inheritDoc
     */
    public function getFormId()
    {
      return 'ldap_config_form';
    }

    /**
     * @inheritDoc
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_disabled', FALSE)->save();
      $form['markup_library'] = array(
        '#attached' => array(
          'library' => array(
            "drupal_ad/drupal_ad.usernamefield",
            "drupal_ad/drupal_ad.admin",
            "drupal_ad/drupal_ad.main",
          )
        ),
      );

      if(!Utility::isExtensionInstalled('ldap')){
        $form['errors']['ldap_check'] = array(
          '#markup' => '<div class="message message--error"><b>The PHP ldap extension is not enabled.</b><br> Please Enable the PHP Ldap Extension for you server to continue. If you want, you refer to the steps given on the link  <a target="_blank" href="https://gist.github.com/ssiva13/16480feae02061ae90b9ac3a4718ba0d" >here</a> to enable the extension for your server.</div><br>',
        );
      }
      if (!$this->moduleHandler->moduleExists('field_group')) {
        $form['errors']['field_group_check'] = [
          '#markup' => '<div class="message message--error"><b>The Field Group module does not exist.</b><br>Enable the <em>Field Group</em> module to allow cron execution at the end of a server response.</div><br>',
        ];
      }else{
        $form['#attached']['library'][] = 'field_group/formatter.horizontal_tabs';
      }

      $form['description'] = [
        '#markup' => '<p>Custom Active Directory(Ldap) for Drupal allows your users to log in to your Drupal site using their Ldap / AD credentials</p>',
      ];

      $form['drupal_ldap_tabs'] = [
        '#type' => 'horizontal_tabs',
        '#title' => 'Drupal Ldap Settings',
      ];

//       drupal_ldap_tabs
      $form['drupal_ldap_config'] = [
        '#title' => 'LDAP Configurations',
        '#type' => 'details',
        '#open' => FALSE,
        '#group' => 'drupal_ldap_tabs',
        '#attributes' => [
          'class' => ['custom_ldap_form'],
        ],
      ];
      $form['drupal_ldap_user_mapping'] = [
        '#title' => 'User Mapping Configuration',
        '#type' => 'details',
        '#open' => FALSE,
        '#group' => 'drupal_ldap_tabs',
        '#attributes' => [
          'class' => ['custom_ldap_form'],
        ],
      ];
      $form['drupal_ldap_test_auth'] = [
        '#title' => 'Test Authentication',
        '#type' => 'details',
        '#open' => FALSE,
        '#group' => 'drupal_ldap_tabs',
      ];
      $form['drupal_ldap_options'] = [
        '#title' => 'LDAP Sign in Options',
        '#type' => 'details',
        '#open' => FALSE,
        '#group' => 'drupal_ldap_tabs',
      ];
      $form['drupal_ldap_role_mapping'] = [
        '#title' => 'Role Mapping (LDAP)',
        '#type' => 'details',
        '#open' => TRUE,
        '#group' => 'drupal_ldap_tabs',
      ];
      $form['drupal_ldap_attribute_mapping'] = [
        '#title' => 'Attribute Mapping (LDAP)',
        '#type' => 'details',
        '#group' => 'drupal_ldap_tabs',
        '#open' => FALSE,
      ];
//      drupal_ldap_tabs

//      drupal_ldap_config
      $form['drupal_ldap_config']['description'] = [
        '#markup' => '<p class="message message--primary"><strong>NOTE:</strong> You need to find out the values for the below given fields from your LDAP Administrator.</p>',
      ];
      $form['drupal_ldap_config']['drupal_ldap_directory_server'] = [
        '#type' => 'select',
        '#title' => 'Directory Server',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_directory_server'),
        '#options' => [
          '' => '-- Select --',
          'msad' => 'Microsoft Active Directory',
          'openldap' => 'OpenLDAP',
        ],
        '#attributes' => [

        ],
      ];
      $form['drupal_ldap_config']['drupal_ldap_protocol'] = [
        '#type' => 'select',
        '#title' => 'LDAP Protocol',
        '#description' => "Pick <strong>ldap://</strong> or <strong>ldaps://</strong> from the dropdwon list",
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_protocol'),
        '#options' => [
          '' => '-- Select --',
          'ldap://' => 'LDAP (ldap://)',
          'ldaps://' => 'LDAPS (ldaps://)',
        ],
        '#attributes' => [

        ],
      ];
      $form['drupal_ldap_config']['drupal_ldap_server_address'] = [
        '#type' => 'textfield',
        '#title' => 'LDAP Server Address',
        '#description' => "Specify the host name for the LDAP server in the above text field.",
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_address'),
        '#attributes' => [
          'placeholder' => '127.0.0.1 or domain.com',
        ],
      ];
      $form['drupal_ldap_config']['drupal_ldap_server_port'] = [
        '#type' => 'textfield',
        '#description' => "Edit the port number if you have custom port number.",
        '#title' => 'LDAP Server Port',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_port'),
        '#attributes' => [
          'placeholder' => '389',
        ],
      ];
      $form['drupal_ldap_config']['drupal_ldap_server_username'] = [
        '#type' => 'textfield',
        '#description' => "You can specify the Username of the LDAP server in the either way as follows, <strong>username@domainname</strong> or <strong>Distinguished Name(DN) format</strong>",
        '#title' => 'Account Username',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_username'),
        '#attributes' => [
          'placeholder' => 'admin',
        ],
      ];
      $form['drupal_ldap_config']['drupal_ldap_server_password'] = [
        '#type' => 'password',
        '#description' => "The above username and password will be used to establish the connection to your LDAP server.",
        '#title' => 'Account Password',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_password'),
        '#attributes' => [
          'placeholder' => 'password',
        ],
      ];
      $form['drupal_ldap_config']['drupal_ldap_test_connection'] = [
        '#type' => 'submit',
        '#prefix' => '<br>',
        '#value' => t('Test Configuration'),
        '#submit' => ['::drupal_ldap_test_connection'],
        '#attributes' => [
          'class' => ['btn--success'],
        ],
      ];
      $form['drupal_ldap_config']['drupal_ldap_save_configuration'] = [
        '#type' => 'submit',
        '#value' => t('Save Configuration'),
        '#submit' => ['::drupal_ldap_save_configuration'],
        '#attributes' => [
          'class' => ['btn--primary'],
          'style' => 'float:right;'
        ],
      ];
//      drupal_ldap_config

//      drupal_ldap_user_mapping
      $form['drupal_ldap_user_mapping']['description'] = [
        '#markup' => '<p class="message message--warning"><strong>NOTE:</strong> The attributes that we are showing are examples and the actual ones could be different. These should be confirmed with the LDAP Admin.</p>',
      ];
      $form['drupal_ldap_user_mapping']['drupal_ldap_search_base'] = [
        '#type' => 'select',
        '#description' => "<p>
           This is the LDAP tree under which we will search for the users for authentication.
           If we are not able to find a user in LDAP it means they are not present in this search base or any of its sub trees.
           Provide the distinguished name of the Search Base object. eg. <strong>cn=Users,dc=domain,dc=com; ou=people,dc=domian,dc=com</strong>
        </p>",
        '#title' => 'LDAP Search Base',
        '#options' => [
          'custome_base' => 'Provide Custom LDAP Search Base'
        ],
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_search_base'),
      ];
      $form['drupal_ldap_user_mapping']['drupal_ldap_custom_base'] = [
        '#type' => 'textfield',
        '#description' => "<p>This is the LDAP Tree under which we will search for the users for authentication.
           If we are not able to find a user in LDAP it means they are not present in this search base or any of its sub trees. They may be present in some other .
           Provide the distinguished name of the Search Base object. eg. cn=Users,dc=domain,dc=com.
           If you have users in different locations in the directory(OU's), separate the distinguished names of the search base objects by a semi-colon(;). eg. <strong>cn=Users,dc=domain,dc=com; ou=people,dc=domian,dc=com</strong>
        </p>",
        '#title' => "Custom LDAP Search Base (Click Here For <a style='text-decoration:none;' href='javascript:void(0)' id='ldap_searchbases' data-action='https://kwtrp-lms'><strong> Search Bases / DNs</strong></a>)",
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_custom_base'),
        '#attributes' =>[
          'placeholder' => 'cn=Users,dc=domain,dc=com; ou=people,dc=domian,dc=org',
        ],
      ];
      $form['drupal_ldap_user_mapping']['drupal_ldap_username_attribute'] = [
        '#type' => 'select',
        '#title' => 'Search Filter/Username Attribute',
        '#description' => "Please make clear that the attributes that we are showing are examples and the actual ones could be different. These should be confirmed with the LDAP Admin.",
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_username_attribute'),
        '#options' => [
          '' => '-- Select --',
          'samaccountname' => 'sAMAccountName',
          'userPrincipalName' => 'userPrincipalName',
          'mail' => 'mail',
          'cn' => 'cn',
          'custom' => 'Provide Custom LDAP attribute name',
        ],
        '#attributes' => [

        ],
      ];
      $form['drupal_ldap_user_mapping']['drupal_ldap_test_user_mapping'] = [
        '#type' => 'submit',
        '#prefix' => '<br>',
        '#value' => t('Test User Mapping'),
        '#submit' => ['::drupal_ldap_test_user_mapping'],
        '#attributes' => [
          'class' => ['btn--success'],
        ],
      ];
      $form['drupal_ldap_user_mapping']['drupal_ldap_save_user_mapping'] = [
        '#type' => 'submit',
        '#value' => 'Save User Mapping',
        '#submit' => ['::drupal_ldap_save_user_mapping'],
        '#attributes' => [
          'class' => ['btn--primary'],
          'style' => 'float:right;'
        ],
      ];
//      drupal_ldap_user_mapping

//      drupal_ldap_test_auth
      $form['drupal_ldap_test_auth']['description'] = [
        '#markup' => '<p class="message message--primary">Drupal username is mapped to the LDAP attribute defined in the Search Filter attribute in LDAP. Ensure that you have an administrator user in LDAP with the same attribute value.</p>',
      ];
      $form['drupal_ldap_test_auth']['drupal_ldap_test_username'] = [
        '#type' => 'textfield',
        '#title' => 'Test Username',
        '#attributes' =>[
          'placeholder' => 'Username',
        ],
      ];
      $form['drupal_ldap_test_auth']['drupal_ldap_test_password'] = [
        '#type' => 'textfield',
        '#title' => 'Test Password',
        '#attributes' =>[
          'placeholder' => 'Password',
        ],
      ];
      $form['drupal_ldap_test_auth']['drupal_ldap_test_auth'] = [
        '#type' => 'submit',
        '#prefix' => '<br>',
        '#value' => t('Test Authentication'),
        '#submit' => ['::drupal_ldap_test_auth'],
        '#attributes' => [
          'class' => ['btn--primary'],
        ],
      ];
//      drupal_ldap_test_auth

//      drupal_ldap_options
      $form['drupal_ldap_options']['description'] = [
        '#markup' => '<p class="message message--primary"><strong>NOTE:</strong> Enable login and sign in options using LDAP.</p>',
      ];
      $form['drupal_ldap_options']['drupal_ldap_enable_ldap'] = [
        '#prefix' => '<div class="switch_box">',
        '#suffix' => '</div>',
        '#type' => 'checkbox',
        '#description' => 'Enabling LDAP login will protect your login page by your configured LDAP. Please check this only after you have successfully tested your configuration as the default Drupal login will stop working',
        '#title' => 'Enable Login with LDAP ',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_ldap'),
        '#attributes' => [
          'class' => ['switch']
        ]
      ];
      $form['drupal_ldap_options']['drupal_ldap_enable_auto_reg'] = [
        '#prefix' => '<div class="switch_box">',
        '#suffix' => '</div>',
        '#type' => 'checkbox',
        '#title' => 'Enable Auto Registering users if they do not exist.',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('ldap_enable_auto_reg'),
        '#attributes' => [
          'class' => ['switch']
        ]
      ];
      $form['drupal_ldap_options']['drupal_ldap_enable_auth_admin'] = [
        '#prefix' => '<div class="switch_box">',
        '#suffix' => '</div>',
        '#type' => 'checkbox',
        '#title' => ' Authenticate Administrators from both LDAP and Drupal.',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('ldap_enable_auth_admin'),
        '#attributes' => [
          'class' => ['switch']
        ]
      ];
      $form['drupal_ldap_options']['drupal_ldap_enable_auth_users'] = [
        '#prefix' => '<div class="switch_box">',
        '#suffix' => '</div>',
        '#type' => 'checkbox',
        '#title' => ' Authenticate Users from both LDAP and Drupal.',
        '#tree' => TRUE,
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_auth_users'),
        '#attributes' => [
          'class' => ['switch']
        ]
      ];
      $form['drupal_ldap_options']['drupal_ldap_save_options'] = [
        '#type' => 'submit',
        '#prefix' => '<br>',
        '#value' => t('Save Options'),
        '#submit' => ['::drupal_ldap_save_options'],
        '#attributes' => [
          'class' => ['btn--primary'],
        ],
      ];
//      drupal_ldap_options

//      drupal_ldap_role_mapping
      $form['drupal_ldap_role_mapping']['description'] = [
        '#markup' => '<p class="message message--primary"><strong>NOTE:</strong> Default Role Mapping.</p>',
      ];
      $form['drupal_ldap_role_mapping']['drupal_ldap_enable_role_mapping'] = [
        '#prefix' => '<div class="switch_box">',
        '#suffix' => '</div>',
        '#type' => 'checkbox',
        '#description' => 'Enabling Role Mapping will assign the below selected default WordPress Role to the LDAP users.',
        '#title' => 'Enable Role Mapping',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_role_mapping'),
        '#attributes' => [
          'class' => ['switch']
        ]
      ];
      $form['drupal_ldap_role_mapping']['drupal_ldap_default_role'] = [
        '#type' => 'select',
        '#title' => 'Default Role Mapping',
        '#options' => user_role_names(),
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_default_role'),
      ];
      $form['drupal_ldap_role_mapping']['drupal_ldap_save_role_mapping'] = [
        '#type' => 'submit',
        '#prefix' => '<br>',
        '#value' => t('Save Options'),
        '#submit' => ['::drupal_ldap_save_role_mapping'],
        '#attributes' => [
          'class' => ['btn--primary'],
        ],
      ];
//      drupal_ldap_role_mapping

//      drupal_ldap_attribute_mapping
      $form['drupal_ldap_attribute_mapping']['description'] = [
        '#markup' => '<p class="message message--primary"><strong>NOTE:</strong> Enter the LDAP attribute names for Email, Phone, First Name and Last Name attributes.</p>',
      ];
      $form['drupal_ldap_attribute_mapping']['drupal_ldap_email_attribute'] = [
        '#type' => 'textfield',
        '#title' => 'Email Attribute',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_email_attribute'),
        '#attributes' =>[
          'placeholder' => 'Email Attribute',
        ],
      ];
      $form['drupal_ldap_attribute_mapping']['drupal_ldap_fname_attribute'] = [
        '#type' => 'textfield',
        '#title' => 'First Name Attribute',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_fname_attribute'),
        '#attributes' =>[
          'placeholder' => 'First Name Attribute',
        ],
      ];
      $form['drupal_ldap_attribute_mapping']['drupal_ldap_lname_attribute'] = [
        '#type' => 'textfield',
        '#title' => 'Last Name Attribute',
        '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_lname_attribute'),
        '#attributes' =>[
          'placeholder' => 'Last Name Attribute',
        ],
      ];
      $form['drupal_ldap_attribute_mapping']['drupal_ldap_phone_attribute'] = [
        '#type' => 'textfield',
        '#title' => 'Phone Attribute',
        '#default_value' => Drupal::config('drupal_ldap.settings')->get('drupal_ldap_phone_attribute'),
        '#attributes' =>[
          'placeholder' => 'Phone Attribute',
        ],
      ];
      $form['drupal_ldap_attribute_mapping']['drupal_ldap_email_domain_attribute'] = [
        '#type' => 'textfield',
        '#title' => 'Email Domain Attribute',
        '#default_value' => Drupal::config('drupal_ldap.settings')->get('drupal_ldap_email_domain_attribute'),
        '#attributes' =>[
          'placeholder' => '@domain.com',
        ],
      ];
      $form['drupal_ldap_attribute_mapping']['drupal_ldap_save_attribute_mapping'] = [
        '#type' => 'submit',
        '#value' => t('Save Attribute Mapping'),
        '#submit' => ['::drupal_ldap_save_attribute_mapping'],
        '#attributes' => [
          'class' => ['btn--primary'],
        ],
      ];
//      drupal_ldap_attribute_mapping


      $status = Drupal::config('drupal_ldap.settings')->get('drupal_ldap_config_status');

      return $form;
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }

}
