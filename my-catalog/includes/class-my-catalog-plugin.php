<?php
/**
 * Main plugin bootstrap.
 *
 * @package MyCatalog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin bootstrapping.
 */
final class My_Catalog_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var My_Catalog_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Returns plugin instance.
	 *
	 * @return My_Catalog_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_shortcode( 'my_catalog_message', array( $this, 'render_catalog_message' ) );
	}

	/**
	 * Loads plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'my-catalog', false, dirname( plugin_basename( MY_CATALOG_FILE ) ) . '/languages' );
	}

	/**
	 * Registers the example block if the build exists.
	 *
	 * @return void
	 */
	public function register_block() {
		$build_dir = MY_CATALOG_PATH . 'build';

		if ( file_exists( $build_dir . '/block.json' ) ) {
			register_block_type( $build_dir );
		}
	}

	/**
	 * Renders a simple shortcode example for classic plugin functionality.
	 *
	 * @return string
	 */
	public function render_catalog_message() {
		return sprintf(
			'<div class="my-catalog-message">%s</div>',
			esc_html__( 'My Catalog is active and ready for custom features.', 'my-catalog' )
		);
	}
}
