<?php
/**
 * Product table shortcode, settings, and REST endpoint.
 *
 * @package MyCatalog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the product table feature set.
 */
class My_Catalog_Product_Table {
	/**
	 * Option name for default product table columns.
	 */
	const OPTION_COLUMNS = 'my_catalog_product_table_columns';

	/**
	 * Shortcode instance counter.
	 *
	 * @var int
	 */
	private static $instance = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'product_table', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
	}

	/**
	 * Registers front-end assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'my-catalog-datatables',
			'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css',
			array(),
			'1.13.8'
		);

		wp_register_style(
			'my-catalog-datatables-responsive',
			'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css',
			array( 'my-catalog-datatables' ),
			'2.5.0'
		);

		wp_register_style(
			'my-catalog-product-table',
			MY_CATALOG_URL . 'assets/css/product-table.css',
			array( 'my-catalog-datatables-responsive' ),
			MY_CATALOG_VERSION
		);

		wp_register_script(
			'my-catalog-datatables',
			'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
			array( 'jquery' ),
			'1.13.8',
			true
		);

		wp_register_script(
			'my-catalog-datatables-responsive',
			'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
			array( 'my-catalog-datatables' ),
			'2.5.0',
			true
		);

		wp_register_script(
			'my-catalog-product-table',
			MY_CATALOG_URL . 'assets/js/product-table.js',
			array( 'jquery', 'my-catalog-datatables-responsive' ),
			MY_CATALOG_VERSION,
			true
		);

		wp_localize_script(
			'my-catalog-product-table',
			'myCatalogProductTable',
			array(
				'restUrl' => esc_url_raw( rest_url( 'my-catalog/v1/product-table' ) ),
			)
		);
	}

	/**
	 * Registers plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'my_catalog_settings',
			self::OPTION_COLUMNS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_columns' ),
				'default'           => $this->get_default_columns(),
			)
		);
	}

	/**
	 * Registers the admin settings page.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'My Catalog Settings', 'my-catalog' ),
			__( 'My Catalog', 'my-catalog' ),
			'manage_options',
			'my-catalog-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Renders the admin settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$columns = $this->get_selected_columns();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'My Catalog Settings', 'my-catalog' ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'my_catalog_settings' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Default Product Table Columns', 'my-catalog' ); ?></th>
							<td>
								<fieldset>
									<?php foreach ( $this->get_available_columns() as $key => $column ) : ?>
										<label style="display:block; margin-bottom: 8px;">
											<input type="checkbox" name="<?php echo esc_attr( self::OPTION_COLUMNS ); ?>[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $columns, true ) ); ?> />
											<?php echo esc_html( $column['label'] ); ?>
										</label>
									<?php endforeach; ?>
								</fieldset>
								<p class="description"><?php esc_html_e( 'Shortcode attribute "columns" overrides these defaults.', 'my-catalog' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers REST endpoints.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'my-catalog/v1',
			'/product-table',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_rest_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Renders the product table shortcode.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'         => 10,
				'category'      => '',
				'tag'           => '',
				'columns'       => '',
				'show_filters'  => 'true',
				'search'        => 'true',
				'empty_message' => __( 'No products found.', 'my-catalog' ),
			),
			$atts,
			'product_table'
		);

		$column_keys = $this->parse_columns_attribute( $atts['columns'] );
		$columns     = $this->prepare_columns_for_frontend( $column_keys );
		$instance_id = 'my-catalog-product-table-' . ++self::$instance;

		wp_enqueue_style( 'my-catalog-product-table' );
		wp_enqueue_script( 'my-catalog-product-table' );

		$config = array(
			'restUrl'      => rest_url( 'my-catalog/v1/product-table' ),
			'baseCategory' => sanitize_text_field( $atts['category'] ),
			'baseTag'      => sanitize_text_field( $atts['tag'] ),
			'columns'      => $columns,
			'pageLength'   => max( 1, absint( $atts['limit'] ) ),
			'search'       => filter_var( $atts['search'], FILTER_VALIDATE_BOOLEAN ),
			'defaultOrder' => $this->get_default_order_for_columns( $columns ),
		);

		ob_start();
		?>
		<div class="my-catalog-product-table" id="<?php echo esc_attr( $instance_id ); ?>" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<?php if ( filter_var( $atts['show_filters'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
				<div class="my-catalog-product-table__filters">
					<label class="my-catalog-product-table__filter">
						<span><?php esc_html_e( 'Category', 'my-catalog' ); ?></span>
						<select class="js-my-catalog-product-category">
							<option value=""><?php esc_html_e( 'All categories', 'my-catalog' ); ?></option>
							<?php foreach ( $this->get_terms_for_filter( My_Catalog_Core::PRODUCT_CATEGORY_TAXONOMY ) as $term ) : ?>
								<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="my-catalog-product-table__filter">
						<span><?php esc_html_e( 'Attribute', 'my-catalog' ); ?></span>
						<select class="js-my-catalog-product-tag">
							<option value=""><?php esc_html_e( 'All attributes', 'my-catalog' ); ?></option>
							<?php foreach ( $this->get_terms_for_filter( My_Catalog_Core::PRODUCT_TAG_TAXONOMY ) as $term ) : ?>
								<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
			<?php endif; ?>

			<div class="my-catalog-product-table__frame">
				<table class="display responsive nowrap" style="width:100%">
					<thead>
						<tr>
							<?php foreach ( $columns as $column ) : ?>
								<th><?php echo esc_html( $column['label'] ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="<?php echo esc_attr( count( $columns ) ); ?>">
								<?php echo esc_html( $atts['empty_message'] ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Handles product table REST requests.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function handle_rest_request( WP_REST_Request $request ) {
		$requested_columns = $request->get_param( 'columns' );
		$column_keys       = is_array( $requested_columns ) ? array_map( 'sanitize_key', $requested_columns ) : $this->get_selected_columns();
		$columns           = $this->prepare_columns_for_frontend( $column_keys );
		$search_term       = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$filter_category   = sanitize_text_field( (string) $request->get_param( 'category' ) );
		$filter_tag        = sanitize_text_field( (string) $request->get_param( 'tag' ) );
		$base_category     = sanitize_text_field( (string) $request->get_param( 'base_category' ) );
		$base_tag          = sanitize_text_field( (string) $request->get_param( 'base_tag' ) );
		$length            = max( 1, absint( $request->get_param( 'length' ) ) );
		$start             = max( 0, absint( $request->get_param( 'start' ) ) );
		$order_column      = sanitize_key( (string) $request->get_param( 'order_column' ) );
		$order_direction   = 'desc' === strtolower( (string) $request->get_param( 'order_dir' ) ) ? 'DESC' : 'ASC';
		$draw              = absint( $request->get_param( 'draw' ) );

		$total_query    = $this->run_products_query(
			array(
				'base_category'   => $base_category,
				'base_tag'        => $base_tag,
				'filter_category' => '',
				'filter_tag'      => '',
				'search'          => '',
				'posts_per_page'  => 1,
				'offset'          => 0,
				'paginate'        => false,
			)
		);
		$filtered_query = $this->run_products_query(
			array(
				'base_category'   => $base_category,
				'base_tag'        => $base_tag,
				'filter_category' => $filter_category,
				'filter_tag'      => $filter_tag,
				'search'          => $search_term,
				'posts_per_page'  => $length,
				'offset'          => $start,
				'paginate'        => true,
				'order_column'    => $order_column,
				'order_direction' => $order_direction,
			)
		);

		$data = array();

		if ( $filtered_query->have_posts() ) {
			foreach ( $filtered_query->posts as $product ) {
				$data[] = $this->format_product_row( $product, $columns );
			}
		}

		return rest_ensure_response(
			array(
				'draw'            => $draw,
				'recordsTotal'    => absint( $total_query->found_posts ),
				'recordsFiltered' => absint( $filtered_query->found_posts ),
				'data'            => $data,
			)
		);
	}

	/**
	 * Formats a product row for DataTables.
	 *
	 * @param WP_Post               $product Product post object.
	 * @param array<int, array> $columns Active columns.
	 * @return array<string, string>
	 */
	private function format_product_row( WP_Post $product, $columns ) {
		$row = array(
			'permalink' => esc_url_raw( get_permalink( $product ) ),
		);

		foreach ( $columns as $column ) {
			$key = $column['key'];

			switch ( $key ) {
				case 'image':
					$thumbnail = get_the_post_thumbnail( $product, 'thumbnail', array( 'class' => 'my-catalog-product-table__thumb' ) );
					$row[ $key ] = $thumbnail ? $thumbnail : '<span class="my-catalog-product-table__placeholder">' . esc_html__( 'No image', 'my-catalog' ) . '</span>';
					break;
				case 'title':
					$row[ $key ] = sprintf(
						'<a href="%1$s" class="my-catalog-product-table__title-link">%2$s</a>',
						esc_url( get_permalink( $product ) ),
						esc_html( get_the_title( $product ) )
					);
					break;
				case 'price':
					$price      = get_post_meta( $product->ID, My_Catalog_Core::PRODUCT_META_PRICE, true );
					$row[ $key ] = '' !== $price ? esc_html( number_format_i18n( (float) $price, 2 ) ) : '&mdash;';
					break;
				case 'category':
					$row[ $key ] = esc_html( $this->get_term_list_for_post( $product->ID, My_Catalog_Core::PRODUCT_CATEGORY_TAXONOMY ) );
					break;
				case 'attributes':
					$row[ $key ] = esc_html( $this->get_term_list_for_post( $product->ID, My_Catalog_Core::PRODUCT_TAG_TAXONOMY ) );
					break;
				case 'sku':
					$sku        = get_post_meta( $product->ID, My_Catalog_Core::PRODUCT_META_SKU, true );
					$row[ $key ] = $sku ? esc_html( $sku ) : '&mdash;';
					break;
				case 'stock':
					$stock      = get_post_meta( $product->ID, My_Catalog_Core::PRODUCT_META_STOCK, true );
					$statuses   = My_Catalog_Core::get_stock_statuses();
					$row[ $key ] = isset( $statuses[ $stock ] ) ? esc_html( $statuses[ $stock ] ) : '&mdash;';
					break;
				case 'weight':
					$weight     = get_post_meta( $product->ID, My_Catalog_Core::PRODUCT_META_WEIGHT, true );
					$row[ $key ] = '' !== $weight ? esc_html( $weight ) : '&mdash;';
					break;
				case 'external':
					$link = get_post_meta( $product->ID, My_Catalog_Core::PRODUCT_META_EXTERNAL_URL, true );
					$row[ $key ] = $link
						? sprintf(
							'<a class="my-catalog-product-table__button" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
							esc_url( $link ),
							esc_html__( 'Buy', 'my-catalog' )
						)
						: '&mdash;';
					break;
			}
		}

		return $row;
	}

	/**
	 * Runs a product query for the table endpoint.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return WP_Query
	 */
	private function run_products_query( $args ) {
		$order_mapping = $this->get_order_mapping();
		$query_args    = array(
			'post_type'           => My_Catalog_Core::PRODUCT_POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => ! empty( $args['paginate'] ) ? (int) $args['posts_per_page'] : 1,
			'offset'              => ! empty( $args['paginate'] ) ? (int) $args['offset'] : 0,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);

		$tax_query = $this->build_tax_query(
			$args['base_category'],
			$args['base_tag'],
			$args['filter_category'],
			$args['filter_tag']
		);

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query;
		}

		$order_column = ! empty( $args['order_column'] ) ? $args['order_column'] : 'title';

		if ( isset( $order_mapping[ $order_column ] ) ) {
			$query_args = array_merge( $query_args, $order_mapping[ $order_column ] );
			$query_args['order'] = ! empty( $args['order_direction'] ) ? $args['order_direction'] : 'ASC';
		} else {
			$query_args['orderby'] = 'title';
			$query_args['order']   = 'ASC';
		}

		if ( ! empty( $args['search'] ) ) {
			$query_args['my_catalog_search'] = sanitize_text_field( $args['search'] );
			add_filter( 'posts_clauses', array( $this, 'add_search_clauses' ), 10, 2 );
		}

		$query = new WP_Query( $query_args );

		if ( ! empty( $args['search'] ) ) {
			remove_filter( 'posts_clauses', array( $this, 'add_search_clauses' ), 10 );
		}

		return $query;
	}

	/**
	 * Adds custom search clauses across post content, meta, and taxonomy terms.
	 *
	 * @param array<string, string> $clauses SQL clauses.
	 * @param WP_Query              $query Query instance.
	 * @return array<string, string>
	 */
	public function add_search_clauses( $clauses, $query ) {
		global $wpdb;

		$search = $query->get( 'my_catalog_search' );

		if ( ! $search ) {
			return $clauses;
		}

		$like = '%' . $wpdb->esc_like( $search ) . '%';

		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS my_catalog_meta_search ON ({$wpdb->posts}.ID = my_catalog_meta_search.post_id)";
		$clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} AS my_catalog_term_relationships ON ({$wpdb->posts}.ID = my_catalog_term_relationships.object_id)";
		$clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} AS my_catalog_term_taxonomy ON (my_catalog_term_relationships.term_taxonomy_id = my_catalog_term_taxonomy.term_taxonomy_id)";
		$clauses['join'] .= " LEFT JOIN {$wpdb->terms} AS my_catalog_terms ON (my_catalog_term_taxonomy.term_id = my_catalog_terms.term_id)";

		$clauses['where'] .= $wpdb->prepare(
			" AND (
				{$wpdb->posts}.post_title LIKE %1\$s
				OR {$wpdb->posts}.post_content LIKE %1\$s
				OR {$wpdb->posts}.post_excerpt LIKE %1\$s
				OR (
					my_catalog_meta_search.meta_key IN (%2\$s, %3\$s, %4\$s, %5\$s)
					AND my_catalog_meta_search.meta_value LIKE %1\$s
				)
				OR (
					my_catalog_term_taxonomy.taxonomy IN (%6\$s, %7\$s)
					AND my_catalog_terms.name LIKE %1\$s
				)
			)",
			$like,
			My_Catalog_Core::PRODUCT_META_PRICE,
			My_Catalog_Core::PRODUCT_META_SKU,
			My_Catalog_Core::PRODUCT_META_STOCK,
			My_Catalog_Core::PRODUCT_META_WEIGHT,
			My_Catalog_Core::PRODUCT_CATEGORY_TAXONOMY,
			My_Catalog_Core::PRODUCT_TAG_TAXONOMY
		);

		$clauses['groupby'] = "{$wpdb->posts}.ID";

		return $clauses;
	}

	/**
	 * Builds a tax query from base shortcode filters and active UI filters.
	 *
	 * @param string $base_category Base category filter.
	 * @param string $base_tag      Base tag filter.
	 * @param string $filter_category Active category filter.
	 * @param string $filter_tag      Active tag filter.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_tax_query( $base_category, $base_tag, $filter_category, $filter_tag ) {
		$tax_query = array();

		foreach (
			array(
				array(
					'taxonomy' => My_Catalog_Core::PRODUCT_CATEGORY_TAXONOMY,
					'term'     => $base_category,
				),
				array(
					'taxonomy' => My_Catalog_Core::PRODUCT_TAG_TAXONOMY,
					'term'     => $base_tag,
				),
				array(
					'taxonomy' => My_Catalog_Core::PRODUCT_CATEGORY_TAXONOMY,
					'term'     => $filter_category,
				),
				array(
					'taxonomy' => My_Catalog_Core::PRODUCT_TAG_TAXONOMY,
					'term'     => $filter_tag,
				),
			) as $tax_filter
		) {
			if ( empty( $tax_filter['term'] ) ) {
				continue;
			}

			$field       = is_numeric( $tax_filter['term'] ) ? 'term_id' : 'slug';
			$tax_query[] = array(
				'taxonomy' => $tax_filter['taxonomy'],
				'field'    => $field,
				'terms'    => is_numeric( $tax_filter['term'] ) ? absint( $tax_filter['term'] ) : sanitize_title( $tax_filter['term'] ),
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}

	/**
	 * Returns sortable mappings for DataTables columns.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_order_mapping() {
		return array(
			'title'  => array(
				'orderby' => 'title',
			),
			'price'  => array(
				'meta_key' => My_Catalog_Core::PRODUCT_META_PRICE,
				'orderby'  => 'meta_value_num',
			),
			'sku'    => array(
				'meta_key' => My_Catalog_Core::PRODUCT_META_SKU,
				'orderby'  => 'meta_value',
			),
			'stock'  => array(
				'meta_key' => My_Catalog_Core::PRODUCT_META_STOCK,
				'orderby'  => 'meta_value',
			),
			'weight' => array(
				'meta_key' => My_Catalog_Core::PRODUCT_META_WEIGHT,
				'orderby'  => 'meta_value_num',
			),
		);
	}

	/**
	 * Returns terms for a filter dropdown.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return WP_Term[]
	 */
	private function get_terms_for_filter( $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * Returns a comma-separated term list for a product.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	private function get_term_list_for_post( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return __( 'Unassigned', 'my-catalog' );
		}

		return implode( ', ', wp_list_pluck( $terms, 'name' ) );
	}

	/**
	 * Parses columns from shortcode attributes.
	 *
	 * @param string $columns Columns list.
	 * @return array<int, string>
	 */
	private function parse_columns_attribute( $columns ) {
		if ( empty( $columns ) ) {
			return $this->get_selected_columns();
		}

		return $this->sanitize_columns( array_map( 'trim', explode( ',', (string) $columns ) ) );
	}

	/**
	 * Returns the saved columns or defaults.
	 *
	 * @return array<int, string>
	 */
	private function get_selected_columns() {
		$columns = get_option( self::OPTION_COLUMNS, $this->get_default_columns() );

		return $this->sanitize_columns( is_array( $columns ) ? $columns : array() );
	}

	/**
	 * Sanitizes selected columns.
	 *
	 * @param mixed $columns Submitted columns.
	 * @return array<int, string>
	 */
	public function sanitize_columns( $columns ) {
		$available = array_keys( $this->get_available_columns() );
		$columns   = is_array( $columns ) ? array_map( 'sanitize_key', $columns ) : array();
		$columns   = array_values( array_intersect( $columns, $available ) );

		return ! empty( $columns ) ? $columns : $this->get_default_columns();
	}

	/**
	 * Returns columns for the front-end table.
	 *
	 * @param array<int, string> $column_keys Column keys.
	 * @return array<int, array<string, mixed>>
	 */
	private function prepare_columns_for_frontend( $column_keys ) {
		$available = $this->get_available_columns();
		$columns   = array();

		foreach ( $column_keys as $column_key ) {
			if ( isset( $available[ $column_key ] ) ) {
				$columns[] = array(
					'key'       => $column_key,
					'label'     => $available[ $column_key ]['label'],
					'orderable' => $available[ $column_key ]['orderable'],
				);
			}
		}

		return $columns;
	}

	/**
	 * Returns the default columns for the product table.
	 *
	 * @return array<int, string>
	 */
	private function get_default_columns() {
		return array( 'image', 'title', 'price', 'category', 'sku' );
	}

	/**
	 * Returns available table columns.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_available_columns() {
		return array(
			'image'      => array(
				'label'     => __( 'Image', 'my-catalog' ),
				'orderable' => false,
			),
			'title'      => array(
				'label'     => __( 'Name', 'my-catalog' ),
				'orderable' => true,
			),
			'price'      => array(
				'label'     => __( 'Price', 'my-catalog' ),
				'orderable' => true,
			),
			'category'   => array(
				'label'     => __( 'Category', 'my-catalog' ),
				'orderable' => false,
			),
			'attributes' => array(
				'label'     => __( 'Attributes', 'my-catalog' ),
				'orderable' => false,
			),
			'sku'        => array(
				'label'     => __( 'SKU', 'my-catalog' ),
				'orderable' => true,
			),
			'stock'      => array(
				'label'     => __( 'Availability', 'my-catalog' ),
				'orderable' => true,
			),
			'weight'     => array(
				'label'     => __( 'Weight', 'my-catalog' ),
				'orderable' => true,
			),
			'external'   => array(
				'label'     => __( 'Buy', 'my-catalog' ),
				'orderable' => false,
			),
		);
	}

	/**
	 * Picks a sensible default sort column.
	 *
	 * @param array<int, array<string, mixed>> $columns Active columns.
	 * @return array<int, mixed>
	 */
	private function get_default_order_for_columns( $columns ) {
		foreach ( $columns as $index => $column ) {
			if ( ! empty( $column['orderable'] ) ) {
				return array( $index, 'asc' );
			}
		}

		return array( 0, 'asc' );
	}
}
