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
	 * Core catalog module.
	 *
	 * @var My_Catalog_Core
	 */
	private $core;

	/**
	 * Product table module.
	 *
	 * @var My_Catalog_Product_Table
	 */
	private $product_table;

	/**
	 * News carousel module.
	 *
	 * @var My_Catalog_News_Carousel
	 */
	private $news_carousel;

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
		$this->core          = new My_Catalog_Core();
		$this->product_table = new My_Catalog_Product_Table();
		$this->news_carousel = new My_Catalog_News_Carousel();

		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
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
		$block_dir = MY_CATALOG_PATH;

		if ( file_exists( $block_dir . '/block.json' ) ) {
			register_block_type( $block_dir );
		}
	}
}
