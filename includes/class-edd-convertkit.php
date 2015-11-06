<?php
/**
 * EDD ConvertKit class, extension of the EDD base newsletter classs
 *
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
*/

class EDD_ConvertKit extends EDD_Newsletter {

	public $api_key;

	/**
	 * Sets up the checkout label
	 */
	public function init() {

		$this->checkout_label = edd_get_option( 'edd_convertkit_label', __( 'Signup for the newsletter', 'edd-convertkit' ) );

		$this->api_key = edd_get_option( 'edd_convertkit_api', '' );

		add_filter( 'edd_settings_extensions_sanitize', array( $this, 'save_settings' ) );

	}

	/**
	 * Retrieves the lists from ConvertKit
	 */
	public function get_lists() {

		if( ! empty( $this->api_key ) ) {

			$lists = get_transient( 'edd_convertkit_list_data' );

			if( false === $lists ) {

				$request = wp_remote_get( 'https://api.convertkit.com/v3/forms?api_key=' . $this->api_key );

				if( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {

					$lists = json_decode( wp_remote_retrieve_body( $request ) );

					set_transient( 'edd_convertkit_list_data', $lists, 24*24*24 );

				}

			}

			if( ! empty( $lists ) && ! empty( $lists->forms ) ) {

				foreach( $lists->forms as $key => $form ) {

					$this->lists[ $form->id ] = $form->name;

				}

			}

		}

		return (array) $this->lists;
	}

	/**
	 * Registers the plugin settings
	 */
	public function settings( $settings ) {

		$edd_convertkit_settings = array(
			array(
				'id'      => 'edd_convertkit_settings',
				'name'    => '<strong>' . __( 'ConvertKit Settings', 'edd-convertkit' ) . '</strong>',
				'desc'    => __( 'Configure ConvertKit Integration Settings', 'edd-convertkit' ),
				'type'    => 'header'
			),
			array(
				'id'      => 'edd_convertkit_api',
				'name'    => __( 'ConvertKit API Key', 'edd-convertkit' ),
				'desc'    => __( 'Enter your ConvertKit API key', 'edd-convertkit' ),
				'type'    => 'text',
				'size'    => 'regular'
			),
			array(
				'id'      => 'edd_convertkit_show_checkout_signup',
				'name'    => __( 'Show Signup on Checkout', 'edd-convertkit' ),
				'desc'    => __( 'Allow customers to signup for the list selected below during checkout?', 'edd-convertkit' ),
				'type'    => 'checkbox'
			),
			array(
				'id'      => 'edd_convertkit_list',
				'name'    => __( 'Choose a list', 'edda'),
				'desc'    => __( 'Select the list you wish to subscribe buyers to', 'edd-convertkit' ),
				'type'    => 'select',
				'options' => $this->get_lists()
			),
			array(
				'id'      => 'edd_convertkit_label',
				'name'    => __( 'Checkout Label', 'edd-convertkit' ),
				'desc'    => __( 'This is the text shown next to the signup option', 'edd-convertkit' ),
				'type'    => 'text',
				'size'    => 'regular'
			),
			array(
				'id'      => 'edd_convertkit_double_opt_in',
				'name'    => __( 'Double Opt-In', 'edd-convertkit' ),
				'desc'    => __( 'When checked, users will be sent a confirmation email after signing up, and will only be added once they have confirmed the subscription.', 'edd-convertkit' ),
				'type'    => 'checkbox'
			)
		);

		return array_merge( $settings, $edd_convertkit_settings );
	}

	/**
	 * Flush the list transient on save
	 */
	public function save_settings( $input ) {
		if( isset( $input['edd_convertkit_api'] ) ) {
			delete_transient( 'edd_convertkit_list_data' );
		}
		return $input;
	}

	/**
	 * Determines if the checkout signup option should be displayed
	 */
	public function show_checkout_signup() {
		global $edd_options;

		return ! empty( $edd_options['edd_convertkit_show_checkout_signup'] );
	}

	/**
	 * Subscribe an email to a list
	 */
	public function subscribe_email( $user_info = array(), $list_id = false, $opt_in_overridde = false ) {

		// Make sure an API key has been entered
		if( empty( $this->api_key ) ) {
			return false;
		}

		// Retrieve the global list ID if none is provided
		if( ! $list_id ) {
			$list_id = edd_get_option( 'edd_convertkit_list', false );
			if( ! $list_id ) {
				return false;
			}
		}

		$opt_in = edd_get_option( 'edd_convertkit_double_opt_in' ) && ! $opt_in_overridde;

		$merge_vars = array( 'FNAME' => $user_info['first_name'], 'LNAME' => $user_info['last_name'] );

		$args = apply_filters( 'edd_convertkit_subscribe_vars', array(
			'email' => $user_info['email'],
			'name'  => $user_info['first_name'] . ' ' . $user_info['last_name']
		) );

		$request = wp_remote_post(
			'https://api.convertkit.com/v3/forms/' . $list_id . '/subscribe?api_key=' . $this->api_key,
			array(
				'body'    => $args,
				'timeout' => 30,
			)
		);

		if( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {
			return true;
		}

		return false;

	}

}