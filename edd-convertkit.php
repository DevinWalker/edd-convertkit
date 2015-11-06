<?php
/*
Plugin Name: Easy Digital Downloads - ConvertKit
Plugin URL: http://easydigitaldownloads.com/extension/convertkit
Description: Subscribe your customers to ConvertKit forms during purchase
Version: 1.0
Author: EDD Team
Author URI: http://easydigitaldownloads.com
*/


define( 'EDD_CONVERTKIT_PATH', dirname( __FILE__ ) );

if ( class_exists( 'EDD_License' ) && is_admin() ) {
  $edd_convert_kit_license = new EDD_License( __FILE__, 'ConvertKit', '1.0', 'EDD Team' );
}

if( ! class_exists( 'EDD_Newsletter' ) ) {
	include( EDD_CONVERTKIT_PATH . '/includes/class-edd-newsletter.php' );
}

if( ! class_exists( 'EDD_ConvertKit' ) ) {
	include( EDD_CONVERTKIT_PATH . '/includes/class-edd-convertkit.php' );
}

$edd_convert_kit = new EDD_ConvertKit( 'convertkit', 'ConvertKit' );