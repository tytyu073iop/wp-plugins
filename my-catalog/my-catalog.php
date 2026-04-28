<?php
/**
 * Plugin Name:       My Catalog
 * Plugin URI:        https://example.com/my-catalog
 * Description:       A hybrid WordPress plugin starter with classic PHP structure, block tooling, and wp-env support.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Illia Biruk
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-catalog
 *
 * @package MyCatalog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MY_CATALOG_VERSION', '0.1.0' );
define( 'MY_CATALOG_FILE', __FILE__ );
define( 'MY_CATALOG_PATH', plugin_dir_path( __FILE__ ) );
define( 'MY_CATALOG_URL', plugin_dir_url( __FILE__ ) );

require_once MY_CATALOG_PATH . 'includes/class-my-catalog-plugin.php';

/**
 * Runs on plugin activation.
 */
function my_catalog_activate() {
	if ( ! get_option( 'my_catalog_version' ) ) {
		add_option( 'my_catalog_version', MY_CATALOG_VERSION );
	} else {
		update_option( 'my_catalog_version', MY_CATALOG_VERSION );
	}

	flush_rewrite_rules();
}

/**
 * Runs on plugin deactivation.
 */
function my_catalog_deactivate() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'my_catalog_activate' );
register_deactivation_hook( __FILE__, 'my_catalog_deactivate' );

My_Catalog_Plugin::instance();
