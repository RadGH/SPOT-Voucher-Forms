<?php
/*
Plugin Name: SPOT Voucher Forms
Description: Pet Spay and Neuter applications made through Gravity Forms can be managed by SPOT staff members, and a printable form can be downloaded.
Version: 1.0.0
Author: Radley Sustaire
Author URI: http://radleysustaire.com/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
*/

if ( !defined('ABSPATH') ) return;

define( 'SVF_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define( 'SVF_PATH', dirname(__FILE__) );
define( 'SVF_VERSION', '1.0.0' );

function svf_initialize_plugin() {
	if ( !class_exists('acf') ) {
		add_action( 'admin_notices', 'svf_no_acf_error' );
		return;
	}
	
	include( SVF_PATH . '/includes/voucher_application.php' );
	include( SVF_PATH . '/includes/shortcode.php' );
}
add_action( 'plugins_loaded', 'svf_initialize_plugin' );

function svf_no_acf_error() {
	?>
	<div class="error">
		<p><strong>SPOT Voucher Forms &ndash; Error:</strong> The required plugin "Advanced Custom Fields Pro" is not active. Please install and activate ACF Pro, or deactivate this plugin.</p>
	</div>
	<?php
}