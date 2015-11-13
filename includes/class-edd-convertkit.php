<?php
/**
 * EDD ConvertKit class, extension of the EDD base newsletter classs
 *
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/
class EDD_ConvertKit extends EDD_Newsletter {

	public $api_key;
	public $tags;

	/**
	 * Sets up the checkout label
	 */
	public function init() {

		$this->checkout_label = edd_get_option( 'edd_convertkit_label', __( 'Signup for the newsletter', 'edd-convertkit' ) );

		$this->api_key = edd_get_option( 'edd_convertkit_api', '' );

		add_filter( 'edd_settings_extensions_sanitize', array( $this, 'save_settings' ) );

	}

	/**
	 * Load the plugin's textdomain
	 */
	public function textdomain() {
		// Load the translations
		load_plugin_textdomain( 'edd-convertkit', false, EDD_CONVERTKIT_PATH . '/languages/' );
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
	 * Retrieve plugin tags
	 */
	public function get_tags() {

		if( ! empty( $this->api_key ) ) {

			$tags = get_transient( 'edd_convertkit_tag_data' );

			if( false === $tags ) {

				$request = wp_remote_get( 'https://api.convertkit.com/v3/tags?api_key=' . $this->api_key );

				if( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {

					$tags = json_decode( wp_remote_retrieve_body( $request ) );

					set_transient( 'edd_convertkit_tag_data', $tags, 24*24*24 );

				}

			}

			if( ! empty( $tags ) && ! empty( $tags->tags ) ) {

				foreach( $tags->tags as $key => $tag ) {

					$this->tags[ $tag->id ] = $tag->name;

				}

			}

		}

		return (array) $this->tags;

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
				'name'    => __( 'Choose a form', 'edd-convertkit'),
				'desc'    => __( 'Select the form you wish to subscribe buyers to. The form can also be selected on a per-product basis from the product edit screen', 'edd-convertkit' ),
				'type'    => 'select',
				'options' => $this->get_lists()
			),
			array(
				'id'      => 'edd_convertkit_label',
				'name'    => __( 'Checkout Label', 'edd-convertkit' ),
				'desc'    => __( 'This is the text shown next to the signup option', 'edd-convertkit' ),
				'type'    => 'text',
				'size'    => 'regular'
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
			delete_transient( 'edd_convertkit_tag_data' );
		}
		return $input;
	}

	/**
	 * Display the metabox, which is a list of newsletter lists
	 */
	public function render_metabox() {

		global $post;

		echo '<p>' . __( 'Select the form you wish buyers to be subscribed to when purchasing.', 'edd-convertkit' ) . '</p>';

		$checked = (array) get_post_meta( $post->ID, '_edd_' . esc_attr( $this->id ), true );
		foreach( $this->get_lists() as $list_id => $list_name ) {
			echo '<label>';
				echo '<input type="checkbox" name="_edd_' . esc_attr( $this->id ) . '[]" value="' . esc_attr( $list_id ) . '"' . checked( true, in_array( $list_id, $checked ), false ) . '>';
				echo '&nbsp;' . $list_name;
			echo '</label><br/>';
		}


		$tags = $this->get_tags( $list_id );
		if( ! empty( $tags ) ) {
			$checked = (array) get_post_meta( $post->ID, '_edd_' . esc_attr( $this->id ) . '_tags', true );
			echo '<p>' . __( 'Add the following tags to subscribers.', 'edd-convertkit' ) . '</p>';
			foreach ( $tags as $tag_id => $tag_name ){
				echo '<label>';
					echo '<input type="checkbox" name="_edd_' . esc_attr( $this->id ) . '_tags[]" value="' . esc_attr( $tag_id ) . '"' . checked( true, in_array( $tag_id, $checked ), false ) . '>';
					echo '&nbsp;' . $tag_name;
				echo '</label><br/>';
			}
		}
	}

	/**
	 * Save the metabox
	 */
	public function save_metabox( $fields ) {

		$fields[] = '_edd_' . esc_attr( $this->id );
		$fields[] = '_edd_' . esc_attr( $this->id ) . '_tags';
		return $fields;
	}

	/**
	 * Determines if the checkout signup option should be displayed
	 */
	public function show_checkout_signup() {
		global $edd_options;

		return ! empty( $edd_options['edd_convertkit_show_checkout_signup'] );
	}

	/**
	 * Check if a customer needs to be subscribed on completed purchase of specific products
	 */
	public function completed_download_purchase_signup( $download_id = 0, $payment_id = 0, $download_type = 'default' ) {

		$user_info = edd_get_payment_meta_user_info( $payment_id );
		$lists     = get_post_meta( $download_id, '_edd_' . $this->id, true );
		$tags      = get_post_meta( $download_id, '_edd_' . $this->id . '_tags', true );

		if( 'bundle' == $download_type ) {

			// Get the lists of all items included in the bundle

			$downloads = edd_get_bundled_products( $download_id );
			if( $downloads ) {
				foreach( $downloads as $d_id ) {
					$d_lists = get_post_meta( $d_id, '_edd_' . $this->id, true );
					$d_tags = get_post_meta( $d_id, '_edd_' . $this->id . '_tags', true );
					if ( is_array( $d_lists ) ) {
						$lists = array_merge( $d_lists, (array) $lists );
					}
					if ( is_array( $d_tags ) ) {
						$tags = array_merge( $d_tags, (array) $tags );
					}
				}
			}
		}

		if( empty( $lists ) ) {
			$this->subscribe_email( $user_info, false, false, $tags );
			return;
		}

		$lists = array_unique( $lists );
		$tags  = array_unique( $tags );

		foreach( $lists as $list ) {

			$this->subscribe_email( $user_info, $list, false, $tags );

		}

	}

	/**
	 * Subscribe an email to a list
	 */
	public function subscribe_email( $user_info = array(), $list_id = false, $opt_in_overridde = false, $tags = array() ) {

		// Make sure an API key has been entered
		if( empty( $this->api_key ) ) {
			return false;
		}

		// Retrieve the global list ID if none is provided
		if( ! $list_id && empty( $tags ) ) {
			$list_id = edd_get_option( 'edd_convertkit_list', false );
			if( ! $list_id ) {
				return false;
			}
		}

		$args = apply_filters( 'edd_convertkit_subscribe_vars', array(
			'email' => $user_info['email'],
			'name'  => $user_info['first_name'] . ' ' . $user_info['last_name']
		) );
		
		$return = false;

		$request = wp_remote_post(
			'https://api.convertkit.com/v3/forms/' . $list_id . '/subscribe?api_key=' . $this->api_key,
			array(
				'body'    => $args,
				'timeout' => 30,
			)
		);
		
		if( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {
			$return = true;	
		}

		if( ! empty( $tags ) ) {

			foreach( $tags as $tag ) {

				$request = wp_remote_post(
					'https://api.convertkit.com/v3/tags/' . $tag . '/subscribe?api_key=' . $this->api_key,
					array(
						'body'    => $args,
						'timeout' => 15,
					)
				);
				
				if( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {
					$return = true;	
				}

			}

		}

		return $return;

	}

}
