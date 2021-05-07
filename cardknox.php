<?php
/*
  Plugin Name: Give - Cardknox Payment Gateway
  Plugin URI: https://resolutesolutions.in
  description: Cardknox Payment Gateway
  Version: 1.2
  Author: Resolute Solutions
  Author URI: https://resolutesolutions.in
  License: GPL2
*/

/**
 * Register payment method.
 *
 * @since 1.0.0
 *
 * @param array $gateways List of registered gateways.
 *
 * @return array
 */
// change the prefix insta_for_give here to avoid collisions with other functions
function cardknox_register_payment_method( $gateways ) {
  
  // Duplicate this section to add support for multiple payment method from a custom payment gateway.
  $gateways['cardknox'] = array(
    'admin_label'    => __( 'Cardknox - Credit Card', 'cardknox-for-give' ), // This label will be displayed under Give settings in admin.
    'checkout_label' => __( 'Cardknox Credit Card', 'cardknox-for-give' ), // This label will be displayed on donation form in frontend.
	);
	return $gateways;
}

add_filter( 'give_payment_gateways', 'cardknox_register_payment_method' );



/**
 * Register Section for Payment Gateway Settings.
 *
 * @param array $sections List of payment gateway sections.
 *
 * @since 1.0.0
 *
 * @return array
 */

// change the insta_for_give prefix to avoid collisions with other functions.
function cardknox_register_payment_gateway_sections( $sections ) {
	
	// `cardknox-settings` is the name/slug of the payment gateway section.
	$sections['cardknox-settings'] = __( 'cardknox', 'cardknox-for-give' );

	return $sections;
}

add_filter( 'give_get_sections_gateways', 'cardknox_register_payment_gateway_sections' );



/**
 * Register Admin Settings.
 *
 * @param array $settings List of admin settings.
 *
 * @since 1.0.0
 *
 * @return array
 */
// change the insta_for_give prefix to avoid collisions with other functions.
function cardknox_register_payment_gateway_setting_fields( $settings ) {

	switch ( give_get_current_setting_section() ) {

		case 'cardknox-settings':
			$settings = array(
				array(
					'id'   => 'give_title_cardknox',
					'type' => 'title',
				),
			);

			$number_of_cardknox_gateway = give_get_option('number_of_cardknox_gateway');

			$settings[] = array(
				'name' => __( 'Number of Cardknox Accounts', 'give-square' ),
				'desc' => __( 'Enter the number to create multiple Cardknox account(s).', 'cardknox-for-give' ),
				'id'   => 'number_of_cardknox_gateway',
				'type' => 'number',
			);

			for($i=0;$i<$number_of_cardknox_gateway;$i++){
				$count = $i + 1;

				$settings[] = array(
					'name' => __( '', 'give-square' ),
					'desc' => __( '', 'cardknox-for-give' ),
					'id'   => 'insta_for_give_cardknox_make_default',
					'type' => 'radio_inline',
					'class' => 'insta_for_give_cardknox_make_default',
					'default' => '',
					'options' => [
						$count => __( 'Make as default', 'give' ),
					],

				);

				$settings[] = array(
					'name' => __( 'Account Name '.$count, 'give-square' ),
					'desc' => __( 'Enter a friendly name of your Cardknox account.', 'cardknox-for-give' ),
					'id'   => 'insta_for_give_cardknox_transaction_name'.$count,
					'type' => 'text',
				);

				$settings[] = array(
					'name' => __( 'Transaction Key '.$count, 'give-square' ),
					'desc' => __( 'Enter your Transaction Key, found in your Cardknox Dashboard.', 'cardknox-for-give' ),
					'id'   => 'insta_for_give_cardknox_transaction_key'.$count,
					'type' => 'text',
				);

				
			}
			// give_cardknox_update_account_name();
			$settings[] = array(
				'id'   => 'give_title_cardknox',
				'type' => 'sectionend',
			);
			
			

			break;

	} // End switch().

	return $settings;
}

// change the insta_for_give prefix to avoid collisions with other functions.
add_filter( 'give_get_settings_gateways', 'cardknox_register_payment_gateway_setting_fields' );


/**
 * Process Square checkout submission.
 *
 * @param array $posted_data List of posted data.
 *
 * @since  1.0.0
 * @access public
 *
 * @return void
 */

// change the insta_for_give prefix to avoid collisions with other functions.
function cardknox_process_cardknox_donation( $posted_data ) {

	// Make sure we don't have any left over errors present.
	give_clear_errors();

	// Any errors?
	$errors = give_get_errors();
	// No errors, proceed.
	if ( ! $errors ) {

		$form_id         = intval( $posted_data['post_data']['give-form-id'] );
		$price_id        = ! empty( $posted_data['post_data']['give-price-id'] ) ? $posted_data['post_data']['give-price-id'] : 0;
		$donation_amount = ! empty( $posted_data['price'] ) ? $posted_data['price'] : 0;

		// Setup the payment details.
		$donation_data = array(
			'price'           => $donation_amount,
			'give_form_title' => $posted_data['post_data']['give-form-title'],
			'give_form_id'    => $form_id,
			'give_price_id'   => $price_id,
			'date'            => $posted_data['date'],
			'user_email'      => $posted_data['user_email'],
			'purchase_key'    => $posted_data['purchase_key'],
			'currency'        => give_get_currency( $form_id ),
			'user_info'       => $posted_data['user_info'],
			'status'          => 'pending',
			'gateway'         => 'cardknox',
		);

		// Record the pending donation.
		$donation_id = give_insert_payment( $donation_data );
		
		if ( ! $donation_id ) {

			// Record Gateway Error as Pending Donation in Give is not created.
			give_record_gateway_error(
				__( 'cardknox Error', 'cardknox-for-give' ),
				sprintf(
				// translators: %s Exception error message.
					__( 'Unable to create a pending donation with Give.', 'cardknox-for-give' )
				)
			);

			// Send user back to checkout.
			// give_send_back_to_checkout( '?payment-mode=cardknox' );
			give_send_back_to_checkout( '?payment-mode=' . give_clean( $_POST['payment-mode'] ) );
			return;
		}

		$request = array();
		$request['xCardNum'] = $posted_data['card_info']['card_number'];
		$request['xExp'] = $posted_data['card_info']['card_exp_month'].substr($posted_data['card_info']['card_exp_year'],-2);
		$request['xName'] = $posted_data['card_info']['card_name'];

		$give_cardknox_per_form_accounts = give_get_meta($form_id, 'give_cardknox_per_form_accounts', true );
		if($give_cardknox_per_form_accounts == 'enabled'){
			$_give_cardknox_default_account = give_get_meta($form_id, '_give_cardknox_default_account', true );
		}else{
			$number = give_get_option('insta_for_give_cardknox_make_default');
			$_give_cardknox_default_account = give_cardknox_convert_title_to_slug(give_get_option('insta_for_give_cardknox_transaction_name'.$number));
		}
		
		$key = '' ;
		$number_of_cardknox_gateway = give_get_option('number_of_cardknox_gateway');
		for($i=0;$i<$number_of_cardknox_gateway;$i++){
			$insta_for_give_cardknox_transaction_name = give_get_option('insta_for_give_cardknox_transaction_name'.($i +1));
			$insta_for_give_cardknox_transaction_key = give_get_option('insta_for_give_cardknox_transaction_key'.($i +1));
			if(give_cardknox_convert_title_to_slug($insta_for_give_cardknox_transaction_name) == $_give_cardknox_default_account){
				$key = $insta_for_give_cardknox_transaction_key;
			}
		}

		$request['xKey'] = $key;
		$request['xVersion'] = '4.5.8';
		$request['xSoftwareName'] = get_bloginfo('name');
		
		$request['xSoftwareVersion'] = '1.4.5';
		$request['xCommand'] = 'cc:sale';
		$request['xAmount'] = $donation_amount;

		$response = wp_safe_remote_post(
			'https://x1.cardknox.com/gateway',
			array(
				'method'        => 'POST',
				'body'       => $request,
				'timeout'    => 70
			)
		);

		$parsed_response = [];
        parse_str($response['body'], $parsed_response);
        if (!empty($parsed_response['xResult'] )) {
			if ($parsed_response['xStatus'] == "Approved" ){
				give_update_payment_status( $donation_id, 'publish' );
				give_update_payment_meta( $donation_id, 'xToken', $parsed_response['xToken'] );

				if(give_get_payment_meta($donation_id,'give_recurring_cardknox_donors_choice', true) == 'yes' && give_get_payment_meta($donation_id,'give_recurring_cardknox_donors_choice_month', true) != ''){


					// Add Customer
					$request3 = array();
					// $give_cardknox_per_form_accounts = give_get_meta($form_id, 'give_cardknox_per_form_accounts', true );
					// if($give_cardknox_per_form_accounts == 'enabled'){
					// 	$_give_cardknox_default_account = give_get_meta($form_id, '_give_cardknox_default_account', true );
					// }else{
					// 	$_give_cardknox_default_account = give_cardknox_convert_title_to_slug(give_get_option('insta_for_give_cardknox_transaction_name'.give_get_option('insta_for_give_cardknox_make_default')));
					// }
					
					// $number_of_cardknox_gateway = give_get_option('number_of_cardknox_gateway');
					// for($i=0;$i<$number_of_cardknox_gateway;$i++){
					// 	$insta_for_give_cardknox_transaction_name = give_get_option('insta_for_give_cardknox_transaction_name'.($i +1));
					// 	$insta_for_give_cardknox_transaction_key = give_get_option('insta_for_give_cardknox_transaction_key'.($i +1));
					// 	if(give_cardknox_convert_title_to_slug($insta_for_give_cardknox_transaction_name) == $_give_cardknox_default_account){
					// 		$request3['xKey'] = $insta_for_give_cardknox_transaction_key;
					// 	}
					// }

					$request3['xKey'] = $key;
					$request3['xVersion'] = '1.0.0';
					$request3['xSoftwareName'] = 'Cardknox';
					$request3['xSoftwareVersion'] = '1.4.5';
					$request3['xCommand'] = 'customer:add';
					$request3['xBillCity'] = give_get_payment_meta($donation_id,'_give_donor_billing_city', true);
					$request3['xBillCountry'] = give_get_payment_meta($donation_id,'_give_donor_billing_country', true);
					$request3['xBillFirstName'] = give_get_payment_meta($donation_id,'_give_donor_billing_first_name', true);
					$request3['xBillLastName'] = give_get_payment_meta($donation_id,'_give_donor_billing_last_name', true);
					$request3['xBillState'] = give_get_payment_meta($donation_id,'_give_donor_billing_state', true);
					$request3['xBillStreet'] = give_get_payment_meta($donation_id,'_give_donor_billing_address1', true);
					$request3['xBillStreet2'] = give_get_payment_meta($donation_id,'_give_donor_billing_address2', true);
					$request3['xBillZip'] = give_get_payment_meta($donation_id,'_give_donor_billing_zip', true);
					$request3['xEmail'] = give_get_payment_meta($donation_id,'_give_payment_donor_email', true);
				
					$response_customer_create = wp_safe_remote_post(
							'https://api.cardknox.com/recurring',
							array(
								'method'        => 'POST',
								'body'       => $request3,
								'timeout'    => 70
							)
					);
					
					$parsed_response = [];
					parse_str($response_customer_create['body'], $parsed_response);
					give_update_payment_meta( $donation_id, 'xCustomerID', $parsed_response['xCustomerID'] );
				

					// Add payment Method
					$request2 = array();
					// $give_cardknox_per_form_accounts = give_get_meta($form_id, 'give_cardknox_per_form_accounts', true );
					// 	if($give_cardknox_per_form_accounts == 'enabled'){
					// 		$_give_cardknox_default_account = give_get_meta($form_id, '_give_cardknox_default_account', true );
					// 	}else{
					// 		$_give_cardknox_default_account = give_cardknox_convert_title_to_slug(give_get_option('insta_for_give_cardknox_transaction_name'.give_get_option('insta_for_give_cardknox_make_default')));
					// 	}
						
					// 	$number_of_cardknox_gateway = give_get_option('number_of_cardknox_gateway');
					// 	for($i=0;$i<$number_of_cardknox_gateway;$i++){
					// 		$insta_for_give_cardknox_transaction_name = give_get_option('insta_for_give_cardknox_transaction_name'.($i +1));
					// 		$insta_for_give_cardknox_transaction_key = give_get_option('insta_for_give_cardknox_transaction_key'.($i +1));
					// 		if(give_cardknox_convert_title_to_slug($insta_for_give_cardknox_transaction_name) == $_give_cardknox_default_account){
					// 			$request2['xKey'] = $insta_for_give_cardknox_transaction_key;
					// 		}
					// 	}

					$request2['xKey'] = $key;
					$request2['xVersion'] = '1.0.0';
					$request2['xSoftwareName'] = 'Cardknox';
					$request2['xSoftwareVersion'] = '1.4.5';
					$request2['xCommand'] = 'paymentmethod:add';
					$request2['xCustomerID'] = $parsed_response['xCustomerID'];
					$request2['xToken'] = give_get_payment_meta($donation_id,'xToken', true);
					$request2['xTokenType'] = 'CC';
				
					$response_payment_add = wp_safe_remote_post(
							'https://api.cardknox.com/recurring',
							array(
								'method'        => 'POST',
								'body'       => $request2,
								'timeout'    => 70
							)
					);
					$parsed_response_payment_add = [];
					parse_str($response_payment_add['body'], $parsed_response_payment_add);
					global $table_prefix, $wpdb;
					for($i=0;$i<give_get_payment_meta($donation_id,'give_recurring_cardknox_donors_choice_month', true);$i++){
						// $mon_add = date("Y-m-d", strtotime('+'.($i+1).' months'));
						$mon_add = date("Y-m-d", strtotime('+'.($i+1).' months'));
						if($i == 0){
							$sql = "INSERT INTO ".$table_prefix."give_cardknox_recurring(Form_ID, Donation_id, xCustomerID, status, isrecurring, active_date, last_date) VALUES (".$form_id.",".$donation_id.", '".give_get_payment_meta($donation_id,'xCustomerID', true)."', 0, 1, '".$mon_add."', '')";
						}else{
							$sql = "INSERT INTO ".$table_prefix."give_cardknox_recurring(Form_ID, Donation_id, xCustomerID, status, isrecurring, active_date, last_date) VALUES (".$form_id.",".$donation_id.", '".give_get_payment_meta($donation_id,'xCustomerID', true)."', 0, 0, '".$mon_add."', '')";
						}
						$wpdb->query($sql);
					}
				}
			}
		}
		give_send_to_success_page();
		// Do the actual payment processing using the custom payment gateway API. To access the GiveWP settings, use give_get_option() 
                // as a reference, this pulls the API key entered above: give_get_option('insta_for_give_instamojo_api_key')

	} else {

		// Send user back to checkout.
		// give_send_back_to_checkout( '?payment-mode=cardknox' );
		give_send_back_to_checkout( '?payment-mode=' . give_clean( $_POST['payment-mode'] ) );
	} // End if().
}

add_action( 'give_gateway_cardknox', 'cardknox_process_cardknox_donation' );

function give_cardknox_add_metabox_settings( $settings, $form_id ) {
	$form_account       = give_is_setting_enabled( give_clean( give_get_meta( $form_id, 'give_cardknox_per_form_accounts', true ) ) );
	$defaultAccountSlug = give_cardknox_get_default_account_slug();
	$settings['cardknox_form_account_options'] = [
		'id'        => 'cardknox_form_account_options',
		'title'     => esc_html__( 'Cardknox Account', 'give' ),
		'icon-html' => '<i class="fab fa-cardknox-s"></i>',
		'fields'    => [
			[
				'name'        => esc_html__( 'Account Options', 'give' ),
				'id'          => 'give_cardknox_per_form_accounts',
				'type'        => 'radio_inline',
				'default'     => 'disabled',
				'options'     => [
					'disabled' => esc_html__( 'Use Global Default cardknox Account', 'give' ),
					'enabled'  => esc_html__( 'Customize cardknox Account', 'give' ),
				],
				'description' => esc_html__( 'Do you want to customize the cardknox account for this donation form? The customize option allows you to modify the cardknox account this form processes payments through. By default, new donation forms will use the Global Default cardknox account.', 'give' ),
			],
			[
				'name'          => esc_html__( 'Active Cardknox Account', 'give' ),
				'id'            => '_give_cardknox_default_account',
				'type'          => 'radio',
				'default'       => $defaultAccountSlug,
				'options'       => give_cardknox_get_account_options(),
				'wrapper_class' => $form_account ? 'give-cardknox-per-form-default-account' : 'give-cardknox-per-form-default-account give-hidden',
			]
		],
	];
	return $settings;
}
add_filter( 'give_metabox_form_data_settings', 'give_cardknox_add_metabox_settings', 10, 2 );

function give_cardknox_update_account_name() {
	parse_str($_POST['form_data'], $post_data);
	$number_of_cardknox_gateway = $post_data['number_of_cardknox_gateway'];
	$insta_for_give_cardknox_make_default = $post_data['insta_for_give_cardknox_make_default'];
	for($i=0;$i<$number_of_cardknox_gateway;$i++){
		$insta_for_give_cardknox_transaction_name = 'insta_for_give_cardknox_transaction_name'.($i +1);
		$insta_for_give_cardknox_transaction_key = 'insta_for_give_cardknox_transaction_key'.($i +1);

		$account_slug     = $post_data[$insta_for_give_cardknox_transaction_name];
		$new_account_name = $post_data[$insta_for_give_cardknox_transaction_name];
		$new_key = $post_data[$insta_for_give_cardknox_transaction_key];

		if ( ! empty( $account_slug ) && ! empty( $new_account_name ) ) {
			$accounts             = give_cardknox_get_all_accounts();
			$account_keys         = array_keys( $accounts );
			$account_values       = array_values( $accounts );
			$new_account_slug     = give_cardknox_convert_title_to_slug($new_account_name);
			$default_account_slug = give_cardknox_get_default_account_slug();

			$accounts = $accounts;

			// Set Account related data. Some data will always be empty for manual API keys scenarios.
			$new_accounts[$new_account_slug]['account_name'] = $new_account_name;
			$new_accounts[$new_account_slug]['account_slug'] = $new_account_slug;
			$new_accounts[$new_account_slug]['account_key'] = $new_key;
			// Update accounts.
			give_update_option('_give_cardknox_get_all_accounts', $new_accounts );
			if(($i +1) == $number_of_cardknox_gateway){
				give_update_option( '_give_cardknox_default_account', $new_account_slug );
			}
			$success_args = [
				'message' => esc_html__( 'Account Name updated successfully.', 'give' ),
				'name'    => $new_account_name,
				'slug'    => $new_account_slug,
			];
		}
	}
	die;
}
add_action( 'wp_ajax_give_cardknox_update_account_name', 'give_cardknox_update_account_name' );

function give_cardknox_get_default_account_slug( $form_id = 0 ) {

	// Global cardknox account.
	$default_account = give_get_option( '_give_cardknox_default_account', '' );

	// Return default cardknox account of the form, if enabled.
	if (
		$form_id > 0 &&
		give_is_setting_enabled( give_get_meta( $form_id, 'give_cardknox_per_form_accounts', true ) )
	) {
		$default_account = give_get_meta( $form_id, '_give_cardknox_default_account', true );
	}

	return $default_account;
}

function give_cardknox_get_account_options() {

	$options = [];
	$number_of_cardknox_gateway = give_get_option('number_of_cardknox_gateway');
	for($i=0;$i<$number_of_cardknox_gateway;$i++){
		$insta_for_give_cardknox_transaction_name = give_get_option('insta_for_give_cardknox_transaction_name'.($i +1));
		$options[ give_cardknox_convert_title_to_slug($insta_for_give_cardknox_transaction_name) ] = $insta_for_give_cardknox_transaction_name;
	}
	return $options;
}

function give_cardknox_get_all_accounts() {
	return give_get_option( '_give_cardknox_get_all_accounts', [] );
}

function give_cardknox_convert_slug_to_title( $slug ) {
	return ucfirst( str_replace( '_', ' ', $slug ) );
}

function give_cardknox_convert_title_to_slug( $title ) {
	return str_replace( ' ', '_', strtolower( $title ) );
}


function admin_js_wp_cardknox($hook) {
	wp_enqueue_script('give_cardknow_js', plugin_dir_url(__FILE__) . '/give_cardknow.js');
	wp_enqueue_style('give_cardknow_css', plugin_dir_url(__FILE__) . '/give_cardknow.css');
}

add_action('admin_enqueue_scripts', 'admin_js_wp_cardknox');

