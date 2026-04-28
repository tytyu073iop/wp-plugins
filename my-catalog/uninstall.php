<?php
/**
 * Handles plugin uninstall cleanup.
 *
 * @package MyCatalog
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'my_catalog_version' );
