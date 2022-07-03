// custom functions


(function ($, Drupal) {
  let drupal_ldap_search_base = $(`#drupal_ldap_search_base`); // used in ajax

  let drupal_ldap_custom_base = $('input#drupal_ldap_custom_base'); //used in SearchBaseAjax
  let custom_username_attribute = $('input#custom_username_attribute'); // used in SearchFilterAjax
  // disable right click in custom ldap base field
  drupal_ldap_custom_base.bind("contextmenu", function (e) {
    return false;
  });
  custom_username_attribute.bind("contextmenu", function (e) {
    return false;
  });

  // disable  submit with enter button
  $(window).keydown(function(event){
    if(event.keyCode === 13 || event.keyCode === '13') {
      event.preventDefault();
      return false;
    }
  });

  // Argument passed from InvokeCommand.
  $.fn.SearchBaseAjax = function (ldap_search_base) {
    // Set textfield's value to the passed arguments.
    console.log(ldap_search_base)
    if (ldap_search_base === '' || ldap_search_base !== 'custom_base') {
      drupal_ldap_custom_base.val(ldap_search_base).attr('readonly', true).addClass('custom--input-ro');
    } else {
      drupal_ldap_custom_base.val('').attr('readonly', false).removeClass('custom--input-ro');
    }
  };
  // Argument passed from InvokeCommand.
  $.fn.SearchFilterAjax = function (custom_uname_attribute) {
    // Set textfield's value to the passed arguments.
    if (custom_uname_attribute === '' || custom_uname_attribute !== 'custom') {
      custom_username_attribute.val(custom_uname_attribute).attr('readonly', true).addClass('custom--input-ro');
    } else {
      custom_username_attribute.val('').attr('readonly', false).removeClass('custom--input-ro');
    }
  };

  // populate search base dropdown list
  $.ajax({
    url: Drupal.url('admin/config/drupal_ad/search_bases'),
    type: 'GET',
    dataType: 'json',
    success: function ({possibleSearchBases}) {
      let selectedBase = 'custom_base';
      drupal_ldap_search_base.empty();
      drupal_ldap_search_base.append(new Option(" -- Select -- ", ""));
      possibleSearchBases.forEach((value, key) => {
        drupal_ldap_search_base.append(new Option(value, $.trim(value)));
        if($.trim(drupal_ldap_custom_base.val()) === $.trim(value) ){
          selectedBase =  drupal_ldap_custom_base.val()
        }
      });
      drupal_ldap_search_base.append(new Option("Provide Custom LDAP Search Base", "custom_base")).val( selectedBase )
    }
  });
  // create custom behaviours for drupal dialog modal beforecreate, aftercreate, beforeclose, afterclose
  Drupal.behaviors.copyCustomLdap = {
    attach: function (context) {
      $(window).once('copy-custom-ldap').on({
        'dialog:beforecreate': function (event, dialog, $element, settings) {
        },
        'dialog:aftercreate': function (event, dialog, $element, settings) {
          // click to copy list item when clicked
          $(`.js--listcopybtn`).click(function () {
            let drupal_ldap_custom_value = drupal_ldap_custom_base.val();
            if(drupal_ldap_custom_value === ''){
              drupal_ldap_custom_base.val( $.trim( $(this).text() ) );
            }else{
              //premium feature
              drupal_ldap_custom_base.val('')
              drupal_ldap_custom_base.val( drupal_ldap_custom_value + '; ' + $.trim( $(this).text() ) );
            }
            drupal_ldap_custom_base.select();
            if(document.execCommand('copy')){
              $element.dialog('close');
            }
          });
        }
      });
    }
  };
})(jQuery, Drupal );
