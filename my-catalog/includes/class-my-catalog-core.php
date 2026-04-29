<?php
/**
 * Core catalog content types and metadata.
 *
 * @package MyCatalog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers custom post types, taxonomies, and meta boxes.
 */
class My_Catalog_Core {
	/**
	 * Product post type slug.
	 */
	const PRODUCT_POST_TYPE = 'product';

	/**
	 * Product category taxonomy slug.
	 */
	const PRODUCT_CATEGORY_TAXONOMY = 'product_cat';

	/**
	 * Product attribute taxonomy slug.
	 */
	const PRODUCT_TAG_TAXONOMY = 'product_tag';

	/**
	 * News post type slug.
	 */
	const NEWS_POST_TYPE = 'news';

	/**
	 * News category taxonomy slug.
	 */
	const NEWS_CATEGORY_TAXONOMY = 'news_category';

	/**
	 * Product price meta key.
	 */
	const PRODUCT_META_PRICE = '_my_catalog_price';

	/**
	 * Product SKU meta key.
	 */
	const PRODUCT_META_SKU = '_my_catalog_sku';

	/**
	 * Product stock status meta key.
	 */
	const PRODUCT_META_STOCK = '_my_catalog_stock_status';

	/**
	 * Product weight meta key.
	 */
	const PRODUCT_META_WEIGHT = '_my_catalog_weight';

	/**
	 * Product external URL meta key.
	 */
	const PRODUCT_META_EXTERNAL_URL = '_my_catalog_external_url';

	/**
	 * News read more URL meta key.
	 */
	const NEWS_META_READ_MORE_URL = '_my_catalog_read_more_url';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_content_types' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_' . self::PRODUCT_POST_TYPE, array( $this, 'save_product_meta' ) );
		add_action( 'save_post_' . self::NEWS_POST_TYPE, array( $this, 'save_news_meta' ) );
	}

	/**
	 * Registers product and news post types plus taxonomies.
	 *
	 * @return void
	 */
	public static function register_content_types() {
		register_post_type(
			self::PRODUCT_POST_TYPE,
			array(
				'labels'       => array(
					'name'               => __( 'Products', 'my-catalog' ),
					'singular_name'      => __( 'Product', 'my-catalog' ),
					'add_new_item'       => __( 'Add Product', 'my-catalog' ),
					'edit_item'          => __( 'Edit Product', 'my-catalog' ),
					'new_item'           => __( 'New Product', 'my-catalog' ),
					'view_item'          => __( 'View Product', 'my-catalog' ),
					'search_items'       => __( 'Search Products', 'my-catalog' ),
					'not_found'          => __( 'No products found.', 'my-catalog' ),
					'not_found_in_trash' => __( 'No products found in Trash.', 'my-catalog' ),
					'menu_name'          => __( 'Products', 'my-catalog' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-products',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'products' ),
			)
		);

		register_taxonomy(
			self::PRODUCT_CATEGORY_TAXONOMY,
			self::PRODUCT_POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Product Categories', 'my-catalog' ),
					'singular_name' => __( 'Product Category', 'my-catalog' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'product-category' ),
			)
		);

		register_taxonomy(
			self::PRODUCT_TAG_TAXONOMY,
			self::PRODUCT_POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Product Attributes', 'my-catalog' ),
					'singular_name' => __( 'Product Attribute', 'my-catalog' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'product-attribute' ),
			)
		);

		register_post_type(
			self::NEWS_POST_TYPE,
			array(
				'labels'       => array(
					'name'               => __( 'News', 'my-catalog' ),
					'singular_name'      => __( 'News Item', 'my-catalog' ),
					'add_new_item'       => __( 'Add News Item', 'my-catalog' ),
					'edit_item'          => __( 'Edit News Item', 'my-catalog' ),
					'new_item'           => __( 'New News Item', 'my-catalog' ),
					'view_item'          => __( 'View News Item', 'my-catalog' ),
					'search_items'       => __( 'Search News', 'my-catalog' ),
					'not_found'          => __( 'No news found.', 'my-catalog' ),
					'not_found_in_trash' => __( 'No news found in Trash.', 'my-catalog' ),
					'menu_name'          => __( 'News', 'my-catalog' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-megaphone',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'news' ),
			)
		);

		register_taxonomy(
			self::NEWS_CATEGORY_TAXONOMY,
			self::NEWS_POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'News Categories', 'my-catalog' ),
					'singular_name' => __( 'News Category', 'my-catalog' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'news-category' ),
			)
		);
	}

	/**
	 * Registers product and news meta boxes.
	 *
	 * @return void
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'my-catalog-product-details',
			__( 'Product Details', 'my-catalog' ),
			array( $this, 'render_product_meta_box' ),
			self::PRODUCT_POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'my-catalog-news-details',
			__( 'News Details', 'my-catalog' ),
			array( $this, 'render_news_meta_box' ),
			self::NEWS_POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Renders the product details meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render_product_meta_box( $post ) {
		wp_nonce_field( 'my_catalog_save_product_meta', 'my_catalog_product_meta_nonce' );

		$price        = get_post_meta( $post->ID, self::PRODUCT_META_PRICE, true );
		$sku          = get_post_meta( $post->ID, self::PRODUCT_META_SKU, true );
		$stock_status = get_post_meta( $post->ID, self::PRODUCT_META_STOCK, true );
		$weight       = get_post_meta( $post->ID, self::PRODUCT_META_WEIGHT, true );
		$external_url = get_post_meta( $post->ID, self::PRODUCT_META_EXTERNAL_URL, true );
		$stock_map    = self::get_stock_statuses();
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="my-catalog-price"><?php esc_html_e( 'Price', 'my-catalog' ); ?></label>
					</th>
					<td>
						<!--maybe should be number -->
						<input type="text" class="regular-text" id="my-catalog-price" name="my_catalog_price" value="<?php echo esc_attr( $price ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-catalog-sku"><?php esc_html_e( 'SKU', 'my-catalog' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="my-catalog-sku" name="my_catalog_sku" value="<?php echo esc_attr( $sku ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-catalog-stock-status"><?php esc_html_e( 'Availability', 'my-catalog' ); ?></label>
					</th>
					<td>
						<select id="my-catalog-stock-status" name="my_catalog_stock_status">
							<?php foreach ( $stock_map as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $stock_status, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-catalog-weight"><?php esc_html_e( 'Weight', 'my-catalog' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="my-catalog-weight" name="my_catalog_weight" value="<?php echo esc_attr( $weight ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-catalog-external-url"><?php esc_html_e( 'External Store URL', 'my-catalog' ); ?></label>
					</th>
					<td>
						<input type="url" class="regular-text" id="my-catalog-external-url" name="my_catalog_external_url" value="<?php echo esc_attr( $external_url ); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Renders the news details meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render_news_meta_box( $post ) {
		wp_nonce_field( 'my_catalog_save_news_meta', 'my_catalog_news_meta_nonce' );

		$read_more_url = get_post_meta( $post->ID, self::NEWS_META_READ_MORE_URL, true );
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="my-catalog-news-read-more-url"><?php esc_html_e( 'Read More URL', 'my-catalog' ); ?></label>
					</th>
					<td>
						<input type="url" class="regular-text" id="my-catalog-news-read-more-url" name="my_catalog_read_more_url" value="<?php echo esc_attr( $read_more_url ); ?>" />
						<p class="description"><?php esc_html_e( 'Use an internal site link for the detailed news page.', 'my-catalog' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Saves product meta box fields.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_product_meta( $post_id ) {
		if ( ! $this->can_save_post( $post_id, 'my_catalog_product_meta_nonce', 'my_catalog_save_product_meta' ) ) {
			return;
		}

		$stock_status = isset( $_POST['my_catalog_stock_status'] ) ? sanitize_key( wp_unslash( $_POST['my_catalog_stock_status'] ) ) : 'in_stock';
		$stock_map    = self::get_stock_statuses();

		update_post_meta( $post_id, self::PRODUCT_META_PRICE, $this->sanitize_decimal_field( 'my_catalog_price' ) );
		update_post_meta( $post_id, self::PRODUCT_META_SKU, isset( $_POST['my_catalog_sku'] ) ? sanitize_text_field( wp_unslash( $_POST['my_catalog_sku'] ) ) : '' );
		update_post_meta( $post_id, self::PRODUCT_META_STOCK, isset( $stock_map[ $stock_status ] ) ? $stock_status : 'in_stock' );
		update_post_meta( $post_id, self::PRODUCT_META_WEIGHT, $this->sanitize_decimal_field( 'my_catalog_weight' ) );
		update_post_meta( $post_id, self::PRODUCT_META_EXTERNAL_URL, isset( $_POST['my_catalog_external_url'] ) ? esc_url_raw( wp_unslash( $_POST['my_catalog_external_url'] ) ) : '' );
	}

	/**
	 * Saves news meta box fields.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_news_meta( $post_id ) {
		if ( ! $this->can_save_post( $post_id, 'my_catalog_news_meta_nonce', 'my_catalog_save_news_meta' ) ) {
			return;
		}

		update_post_meta( $post_id, self::NEWS_META_READ_MORE_URL, isset( $_POST['my_catalog_read_more_url'] ) ? esc_url_raw( wp_unslash( $_POST['my_catalog_read_more_url'] ) ) : '' );
	}

	/**
	 * Shared save guard for custom meta boxes.
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $nonce_name Nonce input name.
	 * @param string $action     Nonce action.
	 * @return bool
	 */
	private function can_save_post( $post_id, $nonce_name, $action ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}

		if ( ! isset( $_POST[ $nonce_name ] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) ), $action ) ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Sanitizes a decimal field from POST data.
	 *
	 * @param string $field_name POST field name.
	 * @return string
	 */
	private function sanitize_decimal_field( $field_name ) {
		if ( ! isset( $_POST[ $field_name ] ) ) {
			return '';
		}

		$value = wp_unslash( $_POST[ $field_name ] );
		$value = str_replace( ',', '.', $value );
		$value = preg_replace( '/[^0-9.]/', '', $value );

		if ( '' === $value ) {
			return '';
		}

		return (string) floatval( $value );
	}

	/**
	 * Returns supported stock statuses.
	 *
	 * @return array<string, string>
	 */
	public static function get_stock_statuses() {
		return array(
			'in_stock'     => __( 'In stock', 'my-catalog' ),
			'out_of_stock' => __( 'Out of stock', 'my-catalog' ),
			'preorder'     => __( 'Pre-order', 'my-catalog' ),
		);
	}
}
