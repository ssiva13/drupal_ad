<?php

namespace Drupal\drupal_ad\Form;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\drupal_ad\Model\LdapConn;
use Drupal\drupal_ad\Model\Utility;
use Symfony\Component\HttpFoundation\JsonResponse;

class DrupalLdapForm extends FormBase
{
  /**
   * The module handler service.
   *
   * @var ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  public function __construct()
  {
    $this->moduleHandler = $moduleHandler = Drupal::service('module_handler');
  }

  /**
   * @inheritDoc
   */
  public function getFormId(): string {
    return 'ldap_config_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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

    if (!Utility::isExtensionInstalled('ldap')) {
      $form['errors']['ldap_check'] = array(
        '#markup' => '<div class="message message--error"><b>The PHP ldap extension is not enabled.</b><br> Please Enable the PHP Ldap Extension for you server to continue. If you want, you refer to the steps given on the link  <a target="_blank" href="https://gist.github.com/ssiva13/16480feae02061ae90b9ac3a4718ba0d" >here</a> to enable the extension for your server.</div><br>',
      );
    }
    if (!$this->moduleHandler->moduleExists('field_group')) {
      $form['errors']['field_group_check'] = [
        '#markup' => '<div class="message message--error"><b>The Field Group module does not exist.</b><br>Enable the <em>Field Group</em> module to allow cron execution at the end of a server response.</div><br>',
      ];
    }

    $form['description'] = [
      '#markup' => '<p>Custom Active Directory (ldap) for Drupal allows your users to log in to your Drupal site using their Ldap / AD credentials</p>',
    ];

    $form['drupal_ldap_tabs'] = [
      '#type' => 'horizontal_tabs',
      '#title' => 'Drupal Ldap Settings',
    ];

//      drupal_ldap_tabs
    $form['drupal_ldap_config'] = [
      '#title' => 'LDAP Configurations',
      '#type' => 'details',
      '#open' => TRUE,
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
    $form['drupal_ldap_attribute_mapping'] = [
      '#title' => 'User Attribute Mapping (LDAP)',
      '#type' => 'details',
      '#group' => 'drupal_ldap_tabs',
      '#open' => FALSE,
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
      '#open' => FALSE,
      '#group' => 'drupal_ldap_tabs',
    ];
//      drupal_ldap_tabs

#      drupal_ldap_config
    $form['drupal_ldap_config']['description'] = [
      '#markup' => '<p class="message message--primary"><strong>NOTE:</strong> You need to find out the values for the below given fields from your LDAP Administrator.</p>',
    ];
    $form['drupal_ldap_config']['drupal_ldap_directory_server'] = [
      '#type' => 'select',
      '#title' => 'Directory Server',
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_directory_server')),
      '#options' => [
        '' => $this->t('-- Select --'),
        'msad' => $this->t('Microsoft Active Directory'),
        'openldap' => $this->t('OpenLDAP'),
      ],
      '#attributes' => [
        'class' => ['custom-select2'],
      ],
    ];
    $form['drupal_ldap_config']['drupal_ldap_protocol'] = [
      '#type' => 'select',
      '#title' => 'LDAP Protocol',
      '#description' => $this->t("Pick <strong>ldap://</strong> or <strong>ldaps://</strong> from the dropdwon list"),
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_protocol')),
      '#options' => [
        '' => $this->t('-- Select --'),
        'ldap://' => $this->t('LDAP (ldap://)'),
        'ldaps://' => $this->t('LDAPS (ldaps://)'),
      ],
      '#attributes' => [
        'class' => ['custom-select2'],
      ],
    ];
    $form['drupal_ldap_config']['drupal_ldap_server_url'] = [
      '#type' => 'textfield',
      '#title' => 'LDAP Server Address',
      '#description' => $this->t("Specify the host name for the LDAP server in the above text field."),
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_url')),
      '#attributes' => [
        'placeholder' => '127.0.0.1 or domain.com',
      ],
    ];
    $form['drupal_ldap_config']['drupal_ldap_server_port'] = [
      '#type' => 'textfield',
      '#description' => $this->t("Edit the port number if you have custom port number."),
      '#title' => 'LDAP Server Port',
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_port')),
      '#attributes' => [
        'placeholder' => '389',
      ],
    ];
    $form['drupal_ldap_config']['drupal_ldap_server_username'] = [
      '#type' => 'textfield',
      '#description' => $this->t("You can specify the Username of the LDAP server in the either way as follows, <strong>username@domainname</strong> or <strong>Distinguished Name(DN) format</strong>"),
      '#title' => 'Account Username',
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_username')),
      '#attributes' => [
        'placeholder' => 'admin',
      ],
    ];
    $form['drupal_ldap_config']['drupal_ldap_server_password'] = [
      '#type' => 'password',
      '#description' => $this->t("The above username and password will be used to establish the connection to your LDAP server."),
      '#title' => 'Account Password',
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_server_password')),
      '#attributes' => [
        'placeholder' => '**********',
      ],
    ];
    $form['drupal_ldap_config']['drupal_ldap_test_connection'] = [
      '#type' => 'submit',
      '#prefix' => '<br>',
      '#value' =>  $this->t('Test Configuration'),
      '#submit' => ['::ldapTestConnection'],
      '#validate' => ['::ldapConfigValidate'],
      '#limit_validation_errors' => [
        ["drupal_ldap_server_password"],
        ["drupal_ldap_directory_server"],
        ["drupal_ldap_server_port"],
        ["drupal_ldap_server_url"],
        ["drupal_ldap_protocol"],
        ["drupal_ldap_server_username"],
      ],
      '#attributes' => [
        'class' => ['btn--error'],
      ],
    ];
    $form['drupal_ldap_config']['drupal_ldap_save_configuration'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
      '#submit' => ['::ldapSaveConfig'],
      '#validate' => ['::ldapConfigValidate'],
      '#limit_validation_errors' => [
        ["drupal_ldap_server_password"],
        ["drupal_ldap_directory_server"],
        ["drupal_ldap_server_port"],
        ["drupal_ldap_server_url"],
        ["drupal_ldap_protocol"],
        ["drupal_ldap_server_username"],
      ],
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
      '#id' => 'drupal_ldap_search_base',
      '#description' => $this->t("<p>
           This is the LDAP tree under which we will search for the users for authentication.
           If we are not able to find a user in LDAP it means they are not present in this search base or any of its sub trees.
           Provide the distinguished name of the Search Base object. eg. <strong>cn=Users,dc=domain,dc=com; ou=people,dc=domian,dc=com</strong>
        </p>"),
      '#title' => 'LDAP Search Base',
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => '::SearchBaseAjax',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'drupal_ldap_custom_base', // This element is updated with this AJAX callback.
        'progress' => [
          'message' => 'Updating...',
        ],
      ],
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_search_base')),
      '#attributes' => [
        'class' => ['custom-select2'],
      ],
    ];
    $form['drupal_ldap_user_mapping']['drupal_ldap_custom_base'] = [
      '#type' => 'textfield',
      '#id' => 'drupal_ldap_custom_base',
      '#description' => $this->t("<p>This is the LDAP Tree under which we will search for the users for authentication.
           If we are not able to find a user in LDAP it means they are not present in this search base or any of its sub trees. They may be present in some other .
           Provide the distinguished name of the Search Base object. eg. cn=Users,dc=domain,dc=com.
           If you have users in different locations in the directory(OU's), separate the distinguished names of the search base objects by a semi-colon(;). eg. <strong>cn=Users,dc=domain,dc=com; ou=people,dc=domian,dc=com</strong>
        </p>"),
      '#title' => "Custom LDAP Search Base - Click Here For
            <a data-dialog-type='modal' class='use-ajax ajax--link' data-dialog-options='{&quot;width&quot;:600}' data-ajax-progres='fullscreen' data-ajax-focus=''
            href='". Url::fromRoute('drupal_ad.search_bases_markup')->toString()."' methods='GET' ><strong> Search Bases / DNs</strong> </a>",
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_custom_base')),
      '#attributes' => [
        'placeholder' => 'cn=Users,dc=domain,dc=com; ou=people,dc=domian,dc=org',
        'readonly' => true,
        'class' => ['custom--input-ro']
      ],
    ];
    $form['drupal_ldap_user_mapping']['drupal_ldap_username_attribute'] = [
      '#type' => 'select',
      '#title' => 'Search Filter/Username Attribute',
      '#description' => $this->t("Please make clear that the attributes that we are showing are examples and the actual ones could be different. These should be confirmed with the LDAP Admin."),
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_username_attribute')),
      '#options' => [
        '' => $this->t('-- Select --'),
        'samaccountname' => $this->t('sAMAccountName'),
        'userPrincipalName' => $this->t('userPrincipalName'),
        'mail' => $this->t('mail'),
        'cn' => $this->t('cn'),
        'custom' => $this->t('Provide Custom LDAP attribute name'),
      ],
      '#ajax' => [
        'callback' => '::SearchFilterAjax',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'custom_username_attribute', // This element is updated with this AJAX callback.
        'progress' => [
          'message' => 'Updating...',
        ],
      ],
      '#attributes' => [
        'class' => ['custom-select2'],
      ],
    ];
    $form['drupal_ldap_user_mapping']['drupal_ldap_custom_username_attribute'] = [
      '#type' => 'textfield',
      '#title' => 'Custom Search Filter/Username Attribute',
      '#id' => 'custom_username_attribute',
      '#default_value' => Utility::decrypt(Drupal::config('drupal_ad.settings')->get('drupal_ldap_custom_username_attribute')),
      '#attributes' => [
        'readonly' => true,
        'class' => ['custom--input-ro']
      ],
    ];
    $form['drupal_ldap_user_mapping']['drupal_ldap_save_user_mapping'] = [
      '#type' => 'submit',
      '#value' => 'Save User Mapping',
      '#submit' => ['::saveUserMapping'],
      '#validate' => ['::ldapConfigValidate'],
      '#limit_validation_errors' => [
        ["drupal_ldap_search_base"],
        ["drupal_ldap_custom_base"],
        ["drupal_ldap_username_attribute"],
        ["drupal_ldap_custom_username_attribute"],
      ],
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
      '#attributes' => [
        'placeholder' => 'Username',
      ],
    ];
    $form['drupal_ldap_test_auth']['drupal_ldap_test_password'] = [
      '#type' => 'password',
      '#title' => 'Test Password',
      '#attributes' => [
        'placeholder' => 'Password',
      ],
    ];
    $form['drupal_ldap_test_auth']['drupal_ldap_test_auth'] = [
      '#type' => 'submit',
      '#prefix' => '<br>',
      '#value' => $this->t('Test Authentication'),
      '#submit' => ['::ldapTestAuth'],
      '#validate' => ['::ldapConfigValidate'],
      '#limit_validation_errors' => [
        ["drupal_ldap_test_username"],
        ["drupal_ldap_test_password"],
      ],
      '#attributes' => [
        'class' => ['btn--primary'],
        'style' => 'float:right;'
      ],
    ];
//      drupal_ldap_test_auth

//      drupal_ldap_options
    $form['drupal_ldap_options']['description'] = [
      '#prefix' => '<div class="options--block">',
      '#markup' => '<p class="message message--primary"><strong>NOTE:</strong> Enable login and sign in options using LDAP.</p>',
    ];
    $form['drupal_ldap_options']['drupal_ldap_enable_ldap'] = [
      '#prefix' => '<div class="switch_box">',
      '#suffix' => '</div>',
      '#id' => 'drupal_ldap_enable_ldap',
      '#type' => 'checkbox',
      '#description' => $this->t('Enabling LDAP login will protect your login page by your configured LDAP. Please check this only after you have successfully tested your configuration as the default Drupal login will stop working'),
      '#title' => 'Enable Login with LDAP.',
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_ldap'),
      '#attributes' => [
        'class' => ['switch option-switch']
      ]
    ];
    $form['drupal_ldap_options']['drupal_ldap_enable_auto_reg'] = [
      '#prefix' => '<div class="switch_box ldap--enabled ">',
      '#suffix' => '</div></div>',
      '#type' => 'checkbox',
      '#title' => 'Enable Auto Registering users if they do not exist.',
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_auto_reg'),
      '#attributes' => [
        'class' => ['switch']
      ]
    ];



    $form['drupal_ldap_options']['admin-description'] = [
      '#prefix' => '<div id="drupal_ldap_admin_options" class="ldap--enabled options--block">',
      '#markup' => '<p class="message message--primary"><strong>NOTE:</strong> Manage Admins login sign in options within your application.</p>',
    ];
    $form['drupal_ldap_options']['drupal_ldap_admin_drupal_only'] = [
      '#prefix' => '<div class="switch_box ldap--enabled ">',
      '#suffix' => '</div>',
      '#id' => 'drupal_ldap_admin_drupal_only',
      '#type' => 'checkbox',
      '#title' => ' Authenticate Admins from Drupal Only',
      '#tree' => TRUE,
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_admin_drupal_only'),
      '#attributes' => [
        'class' => ['switch option-switch']
      ]
    ];
    $form['drupal_ldap_options']['drupal_ldap_admin_ad_only'] = [
      '#prefix' => '<div class="switch_box ldap--enabled ">',
      '#suffix' => '</div>',
      '#type' => 'checkbox',
      '#id' => 'drupal_ldap_admin_ad_only',
      '#title' => ' Authenticate Admins from LDAP Only',
      '#tree' => TRUE,
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_admin_ad_only'),
      '#attributes' => [
        'class' => ['switch option-switch']
      ]
    ];
    $form['drupal_ldap_options']['drupal_ldap_enable_auth_admin'] = [
      '#prefix' => '<div class="switch_box ldap--enabled ">',
      '#suffix' => '</div></div>',
      '#id' => 'drupal_ldap_enable_auth_admin',
      '#type' => 'checkbox',
      '#title' => ' Authenticate Administrators from both LDAP and Drupal.',
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_auth_admin'),
      '#attributes' => [
        'class' => ['switch option-switch']
      ]
    ];


    $form['drupal_ldap_options']['user-description'] = [
      '#prefix' => '<div id="drupal_ldap_user_options" class="ldap--enabled options--block">',
      '#markup' => '<p class="message message--primary"><strong>NOTE:</strong> Manage User login sign in options within your application.</p>',
    ];
    $form['drupal_ldap_options']['drupal_ldap_user_drupal_only'] = [
      '#prefix' => '<div class="switch_box ldap--enabled ">',
      '#suffix' => '</div>',
      '#id' => 'drupal_ldap_user_drupal_only',
      '#type' => 'checkbox',
      '#title' => ' Authenticate Users from Drupal Only',
      '#tree' => TRUE,
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_user_drupal_only'),
      '#attributes' => [
        'class' => ['switch option-switch']
      ]
    ];
    $form['drupal_ldap_options']['drupal_ldap_user_ad_only'] = [
      '#prefix' => '<div class="switch_box ldap--enabled ">',
      '#suffix' => '</div>',
      '#type' => 'checkbox',
      '#id' => 'drupal_ldap_user_ad_only',
      '#title' => ' Authenticate User from LDAP Only',
      '#tree' => TRUE,
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_user_ad_only'),
      '#attributes' => [
        'class' => ['switch option-switch']
      ]
    ];
    $form['drupal_ldap_options']['drupal_ldap_enable_auth_users'] = [
      '#prefix' => '<div class="switch_box ldap--enabled ">',
      '#suffix' => '</div></div>',
      '#type' => 'checkbox',
      '#id' => 'drupal_ldap_enable_auth_users',
      '#title' => ' Authenticate Users from both LDAP and Drupal.',
      '#tree' => TRUE,
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_auth_users'),
      '#attributes' => [
        'class' => ['switch option-switch']
      ]
    ];
    $form['drupal_ldap_options']['drupal_ldap_save_options'] = [
      '#type' => 'submit',
      '#prefix' => '<br>',
      '#value' => $this->t('Save Sign In Options'),
      '#submit' => ['::ldapSigninOptions'],
      '#attributes' => [
        'class' => ['btn--primary'],
        'style' => 'float:right;'
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
      '#description' => $this->t('Enabling Role Mapping will assign the below selected default WordPress Role to the LDAP users.'),
      '#title' => 'Enable Role Mapping',
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_role_mapping'),
      '#attributes' => [
        'class' => ['switch']
      ]
    ];
    $form['drupal_ldap_role_mapping']['drupal_ldap_default_role'] = [
      '#type' => 'select',
      '#title' => 'Default Role Mapping',
      '#options' => array_merge(['' => $this->t(' -- Select -- ')], user_role_names()),
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_default_role'),
      '#attributes' => [
        'class' => ['custom-select2'],
      ],
    ];
    $form['drupal_ldap_role_mapping']['drupal_ldap_save_role_mapping'] = [
      '#type' => 'submit',
      '#prefix' => '<br>',
      '#value' => $this->t('Save Role Mapping'),
      '#submit' => ['::ldapRoleMapping'],
      '#validate' => ['::ldapConfigValidate'],
      '#limit_validation_errors' => [
        ["drupal_ldap_enable_role_mapping"],
        ["drupal_ldap_default_role"],
      ],
      '#attributes' => [
        'class' => ['btn--primary'],
        'style' => 'float:right;'
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
      '#attributes' => [
        'placeholder' => 'Email Attribute',
      ],
    ];
    $form['drupal_ldap_attribute_mapping']['drupal_ldap_fname_attribute'] = [
      '#type' => 'textfield',
      '#title' => 'First Name Attribute',
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_fname_attribute'),
      '#attributes' => [
        'placeholder' => 'First Name Attribute',
      ],
    ];
    $form['drupal_ldap_attribute_mapping']['drupal_ldap_lname_attribute'] = [
      '#type' => 'textfield',
      '#title' => 'Last Name Attribute',
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_lname_attribute'),
      '#attributes' => [
        'placeholder' => 'Last Name Attribute',
      ],
    ];
    $form['drupal_ldap_attribute_mapping']['drupal_ldap_phone_attribute'] = [
      '#type' => 'textfield',
      '#title' => 'Phone Attribute',
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_phone_attribute'),
      '#attributes' => [
        'placeholder' => 'Phone Attribute',
      ],
    ];
    $form['drupal_ldap_attribute_mapping']['drupal_ldap_email_domain_attribute'] = [
      '#type' => 'textfield',
      '#title' => 'Email Domain Attribute',
      '#default_value' => Drupal::config('drupal_ad.settings')->get('drupal_ldap_email_domain_attribute'),
      '#attributes' => [
        'placeholder' => '@domain.com',
      ],
    ];
    $form['drupal_ldap_attribute_mapping']['drupal_ldap_save_attribute_mapping'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Attribute Mapping'),
      '#submit' => ['::ldapAttributeMapping'],
      '#validate' => ['::ldapConfigValidate'],
      '#limit_validation_errors' => [
        ["drupal_ldap_email_attribute"],
        ["drupal_ldap_fname_attribute"],
        ["drupal_ldap_lname_attribute"],
        ["drupal_ldap_phone_attribute"],
        ["drupal_ldap_email_domain_attribute"],
      ],
      '#attributes' => [
        'class' => ['btn--primary'],
        'style' => 'float:right;'
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

  // custom form submission methods
  public function SearchBaseAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
    $selectedValue = $form_state->getValue('drupal_ldap_search_base');
    return (new AjaxResponse())->addCommand(new InvokeCommand(NULL, 'SearchBaseAjax', [ $selectedValue ]));
  }

  public function SearchFilterAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
    $selectedValue = $form_state->getValue('drupal_ldap_username_attribute');
    return (new AjaxResponse())->addCommand(new InvokeCommand(NULL, 'SearchFilterAjax', [ $selectedValue ]));
  }

  /**
   * Form validation handler for $form['drupal_ldap_config']
   */
  public function ldapConfigValidate(array &$form, FormStateInterface $form_state)
  {
    $formData = $form_state->getValues();
    foreach ($formData as $inputElement => $formDatum) {
      (new Utility)->setFormError($inputElement, $formDatum, $form_state);
    }
  }

  /**
   * Form submission hanndeler handler for $form['drupal_ldap_config']
   * This methid tests the connection to the ldap server using the provided credentials.
   */
  public function ldapTestConnection(array &$form, FormStateInterface $form_state)
  {

    $formData = $form_state->getValues();
    $ldapConn = new LdapConn();
    $ldapConn->setServerUsername($formData["drupal_ldap_server_username"]);
    $ldapConn->setServerPassword($formData["drupal_ldap_server_password"]);
    $ldapServerAddress = $formData["drupal_ldap_protocol"] . $formData["drupal_ldap_server_url"] . ':' . $formData["drupal_ldap_server_port"];
    $ldapConn->setServerName($ldapServerAddress);
    $ldapConnection = $ldapConn->getConnection();
    if ($ldapConnection) {
      Utility::add_message(t('Congratulations, you were able to successfully connect to your LDAP Server!'), 'status');
    } else {
      Utility::add_message(t('There seems to be an error trying to contact your LDAP server. Please check your configurations or contact the administrator for the same.'), 'error');
    }
  }

  /**
   * Form submission handeler handler for $form['drupal_ldap_config']
   * This method tests the connection to the ldap server using the provided credentials and saves them.
   */
  public function ldapSaveConfig(array &$form, FormStateInterface $form_state)
  {
    $formData = $form_state->getValues();
    $ldapConn = new LdapConn();
    $ldapConn->setServerUsername($formData["drupal_ldap_server_username"], true);
    $ldapConn->setServerPassword($formData["drupal_ldap_server_password"], true);
    $ldapServerAddress = $formData["drupal_ldap_protocol"] . $formData["drupal_ldap_server_url"] . ':' . $formData["drupal_ldap_server_port"];
    $ldapConn->setServerName($ldapServerAddress, true);
    $ldapConnection = $ldapConn->getConnection();
    if ($ldapConnection) {
      if ($bind = @ldap_bind($ldapConnection, $ldapConn->getServeUsername(), $ldapConn->getServePassword())) {
        Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_directory_server', Utility::encrypt($formData["drupal_ldap_directory_server"]))->save();
        Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_server_url', Utility::encrypt($formData["drupal_ldap_server_url"]))->save();
        Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_protocol', Utility::encrypt($formData["drupal_ldap_protocol"]))->save();
        Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_server_port', Utility::encrypt($formData["drupal_ldap_server_port"]))->save();

        Utility::add_message(t('Congratulations, you were able to successfully connect to your LDAP Server.'), 'status');
      }else{
        Utility::add_message(t('Invalid credentials to your LDAP Server, contact the administrator.'), 'warning');
      }
    } else {
      Utility::add_message(t('There seems to be an error trying to contact your LDAP server. Please check your configurations or contact the administrator for the same.'), 'error');
    }
  }

  /**
   * Form submission handeler handler for $form['drupal_ldap_user_mapping']
   * This methid tests the connection to the ldap server using the provided credentials and saves them.
   */
  public function saveUserMapping(array &$form, FormStateInterface $form_state)
  {
    $formData = $form_state->getValues();
    $ldapConn = new LdapConn();
    $ldapConnection = $ldapConn->getConnection();
    if ($ldapConnection) {
        Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_search_base', Utility::encrypt($formData["drupal_ldap_search_base"]))->save();
        Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_custom_base', Utility::encrypt($formData["drupal_ldap_custom_base"]))->save();
        Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_username_attribute', Utility::encrypt($formData["drupal_ldap_username_attribute"]))->save();
        Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_custom_username_attribute', Utility::encrypt($formData["drupal_ldap_custom_username_attribute"]))->save();

        Utility::add_message(t('Congratulations, LDAP server user mapping saved successfully.'), 'status');
    } else {
      Utility::add_message(t('There seems to be an error trying to contact your LDAP server. Please check your configurations or contact the administrator for the same.'), 'error');
    }
  }

  /**
   * Form submission handeler handler for $form['drupal_ldap_test_auth']
   * This methid tests the connection to the ldap server using the provided credentials and saves them.
   */
  public function ldapTestAuth(array &$form, FormStateInterface $form_state)
  {
    $formData = $form_state->getValues();
    $ldapLogin = (new LdapConn())->ldapLogin($formData["drupal_ldap_test_username"], $formData["drupal_ldap_test_password"]);
    if ($ldapLogin->message == "SUCCESS") {
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_mapping_status', 1)->save();
      Utility::add_message(t('Congratulations, Test Authentication successful for <strong style="color: #556B2F;"> @username </strong>!', [ '@username' => $formData["drupal_ldap_test_username"] ]), 'status');
    }else{
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_mapping_status', 0)->save();
      Utility::add_message(t('Error, Test User Authentication failed - <strong style="color: #B22222;"> @ldap_error </strong> ! Fix Error And Try Again !', [ '@ldap_error' => $ldapLogin->messageDetails ]), 'error');
    }
  }
  /**
   * Form submission handeler handler for $form['drupal_ldap_test_auth']
   * This methid tests the connection to the ldap server using the provided credentials and saves them.
   */
  public function ldapAttributeMapping(array &$form, FormStateInterface $form_state)
  {
    $formData = $form_state->getValues();
    $ldapConn = new LdapConn();
    $ldapConnection = $ldapConn->getConnection();
    if ($ldapConnection) {

      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_email_attribute', $formData["drupal_ldap_email_attribute"])->save();
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_fname_attribute', $formData["drupal_ldap_fname_attribute"])->save();
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_lname_attribute', $formData["drupal_ldap_lname_attribute"])->save();
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_phone_attribute', $formData["drupal_ldap_phone_attribute"])->save();
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_email_domain_attribute', $formData["drupal_ldap_email_domain_attribute"])->save();

      Utility::add_message(t('Congratulations, LDAP server user attributes mapping saved successfully.'), 'status');
    } else {
      Utility::add_message(t('There seems to be an error trying to contact your LDAP server. Please check your configurations or contact the administrator for the same.'), 'error');
    }
  }
  /**
   * Form submission handeler handler for $form['drupal_ldap_attribute_mapping']
   * This methid tests the connection to the ldap server using the provided credentials and saves them.
   */
  public function ldapSigninOptions(array &$form, FormStateInterface $form_state)
  {
    $formData = $form_state->getValues();
    $ldapConn = new LdapConn();
    $ldapConnection = $ldapConn->getConnection();
    if ($ldapConnection) {
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_enable_ldap', $formData["drupal_ldap_enable_ldap"])->save();
      if($enableLdapLogin = Drupal::config('drupal_ad.settings')->get('drupal_ldap_enable_ldap')) {
        Drupal::configFactory()
          ->getEditable('drupal_ad.settings')
          ->set('drupal_ldap_enable_auto_reg', $formData["drupal_ldap_enable_auto_reg"])
          ->save();
        Drupal::configFactory()
          ->getEditable('drupal_ad.settings')
          ->set('drupal_ldap_enable_auth_admin', $formData["drupal_ldap_enable_auth_admin"])
          ->save();
        Drupal::configFactory()
          ->getEditable('drupal_ad.settings')
          ->set('drupal_ldap_enable_auth_users', $formData["drupal_ldap_enable_auth_users"])
          ->save();
        Drupal::configFactory()
          ->getEditable('drupal_ad.settings')
          ->set('drupal_ldap_admin_drupal_only', $formData["drupal_ldap_admin_drupal_only"])
          ->save();
        Drupal::configFactory()
          ->getEditable('drupal_ad.settings')
          ->set('drupal_ldap_admin_ad_only', $formData["drupal_ldap_admin_ad_only"])
          ->save();
        Drupal::configFactory()
          ->getEditable('drupal_ad.settings')
          ->set('drupal_ldap_user_drupal_only', $formData["drupal_ldap_user_drupal_only"])
          ->save();
        Drupal::configFactory()
          ->getEditable('drupal_ad.settings')
          ->set('drupal_ldap_user_ad_only', $formData["drupal_ldap_user_ad_only"])
          ->save();
      }else{
        Drupal::configFactory()->getEditable('drupal_ad.settings')->clear('drupal_ldap_enable_auto_reg');
        Drupal::configFactory()->getEditable('drupal_ad.settings')->clear('drupal_ldap_enable_auth_admin');
        Drupal::configFactory()->getEditable('drupal_ad.settings')->clear('drupal_ldap_enable_auth_users');
        Drupal::configFactory()->getEditable('drupal_ad.settings')->clear('drupal_ldap_admin_drupal_only');
        Drupal::configFactory()->getEditable('drupal_ad.settings')->clear('drupal_ldap_admin_ad_only');
        Drupal::configFactory()->getEditable('drupal_ad.settings')->clear('drupal_ldap_user_drupal_only');
        Drupal::configFactory()->getEditable('drupal_ad.settings')->clear('drupal_ldap_user_ad_only');
      }
      Utility::add_message(t('Congratulations, LDAP sign in options saved successfully.'), 'status');
    } else {
      Utility::add_message(t('There seems to be an error trying to contact your LDAP server. Please check your configurations or contact the administrator for the same.'), 'error');
    }
  }
  /**
   * Form submission handeler handler for $form['drupal_ldap_role_mapping']
   * This methid tests the connection to the ldap server using the provided credentials and saves them.
   */
  public function ldapRoleMapping(array &$form, FormStateInterface $form_state)
  {
    $formData = $form_state->getValues();
    $ldapConn = new LdapConn();
    $ldapConnection = $ldapConn->getConnection();
    if ($ldapConnection) {
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_enable_role_mapping', $formData["drupal_ldap_enable_role_mapping"])->save();
      Drupal::configFactory()->getEditable('drupal_ad.settings')->set('drupal_ldap_default_role', $formData["drupal_ldap_default_role"])->save();

      Utility::add_message(t('Congratulations, LDAP server user role mapping saved successfully.'), 'status');
    } else {
      Utility::add_message(t('There seems to be an error trying to contact your LDAP server. Please check your configurations or contact the administrator for the same.'), 'error');
    }
  }


}
