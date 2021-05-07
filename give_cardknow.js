jQuery(function () {
	jQuery('#cardknox_form_account_options').on('change', '[name="give_cardknox_per_form_accounts"]', function () {
		var give_cardknox_per_form_accounts = jQuery(this).val();
		if (give_cardknox_per_form_accounts == 'enabled') {
			jQuery('._give_cardknox_default_account_field').show();
		} else {
			jQuery('._give_cardknox_default_account_field').hide();
		}
  });
  jQuery('.give-settings-cardknox-settings-section').find('.give-submit-wrap input').css('display','none'); 
  jQuery('.give-settings-cardknox-settings-section').find('.give-submit-wrap input').addClass('ctm_save_cardknox_setting_mail'); 
  jQuery('.give-settings-cardknox-settings-section').find('.give-submit-wrap').append('<input name="save" class=" ctm_save_cardknox_setting  button-primary give-save-button" type="button" value="Save changes">'); 
  jQuery('.give-settings-cardknox-settings-section').on('click', '.ctm_save_cardknox_setting', function () {
		var form_data = jQuery(this).serialize();
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				"action": "give_cardknox_update_account_name",
				"form_data": form_data
			},
			success: function (data) {
				jQuery('.ctm_save_cardknox_setting_mail').click();
			}
   	 });
  });
});