// custom functions


(function ($) {

  // Argument passed from InvokeCommand.
  $.fn.myAjaxCallback = function(argument) {
    // Set textfield's value to the passed arguments.
    $('input#drupal_ldap_custom_base').attr('value', argument);
  };


  let drupal_ldap_search_base = $(`#drupal_ldap_search_base`);
  $.ajax({
    url: Drupal.url('admin/config/drupal_ad/search_bases'),
    type: 'GET',
    dataType: 'json',
    success: function ({possibleSearchBases}) {
      drupal_ldap_search_base.empty();
      drupal_ldap_search_base.append(new Option("Provide Custom LDAP Search Base", "custom_base"));
      possibleSearchBases.forEach((value, key) => {
        drupal_ldap_search_base.append(new Option(value, value));
      });
    }
  });


})(jQuery, Drupal);
