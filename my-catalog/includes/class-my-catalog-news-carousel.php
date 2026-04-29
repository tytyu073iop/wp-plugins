<?php
/**
 * News carousel rendering.
 *
 * @package MyCatalog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the news carousel feature set.
 */
class My_Catalog_News_Carousel {
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
		add_action( 'init', array( $this, 'register_assets' ) );
	}

	/**
	 * Registers front-end assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'my-catalog-news-carousel',
			MY_CATALOG_URL . 'assets/css/news-carousel.css',
			array(),
			MY_CATALOG_VERSION
		);

		wp_register_script_module(
			'my-catalog/news-carousel',
			MY_CATALOG_URL . 'assets/js/news-carousel-view.js',
			array( '@wordpress/interactivity' ),
			MY_CATALOG_VERSION
		);
	}

	/**
	 * Renders the news carousel block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		$atts = array(
			'limit'           => isset( $attributes['limit'] ) ? $attributes['limit'] : 6,
			'category'        => isset( $attributes['category'] ) ? $attributes['category'] : '',
			'slides_per_view' => isset( $attributes['slidesPerView'] ) ? $attributes['slidesPerView'] : 3,
			'autoplay'        => isset( $attributes['autoplay'] ) ? $attributes['autoplay'] : true,
			'autoplay_delay'  => isset( $attributes['autoplayDelay'] ) ? $attributes['autoplayDelay'] : 5000,
		);

		return $this->render( $atts );
	}

	/**
	 * Renders the news carousel markup.
	 *
	 * @param array<string, mixed> $atts Carousel attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$query_args = array(
			'post_type'           => My_Catalog_Core::NEWS_POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, absint( $atts['limit'] ) ),
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $atts['category'] ) ) {
			$field                  = is_numeric( $atts['category'] ) ? 'term_id' : 'slug';
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => My_Catalog_Core::NEWS_CATEGORY_TAXONOMY,
					'field'    => $field,
					'terms'    => is_numeric( $atts['category'] ) ? absint( $atts['category'] ) : sanitize_title( $atts['category'] ),
				),
			);
		}

		$query = new WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			return sprintf(
				'<div class="my-catalog-news-carousel__empty">%s</div>',
				esc_html__( 'No news items found.', 'my-catalog' )
			);
		}

		wp_enqueue_style( 'my-catalog-news-carousel' );
		wp_enqueue_script_module( 'my-catalog/news-carousel' );

		$instance_id = 'my-catalog-news-carousel-' . ++self::$instance;
		$config      = array(
			'autoplay'        => filter_var( $atts['autoplay'], FILTER_VALIDATE_BOOLEAN ),
			'autoplayDelay'   => max( 1000, absint( $atts['autoplay_delay'] ) ),
			'currentIndex'    => 0,
			'desktopSlides'   => max( 1, absint( $atts['slides_per_view'] ) ),
			'visibleSlides'   => 1,
			'slideCount'      => (int) $query->post_count,
			'isPaused'        => false,
		);

		ob_start();
		?>
		<div
			<?php echo get_block_wrapper_attributes( array( 'class' => 'my-catalog-news-carousel' ) ); ?>
			id="<?php echo esc_attr( $instance_id ); ?>"
			data-wp-interactive="my-catalog/news-carousel"
			data-wp-context='<?php echo esc_attr( wp_json_encode( $config ) ); ?>'
			data-wp-init="callbacks.initCarousel"
			data-wp-init--autoplay="callbacks.initAutoplay"
			data-wp-on-window--resize="callbacks.handleResize"
			data-wp-on--mouseenter="actions.pause"
			data-wp-on--mouseleave="actions.resume"
		>
			<div class="my-catalog-news-carousel__viewport">
				<div class="my-catalog-news-carousel__track" data-wp-watch="callbacks.updateTrack">
					<?php
					foreach ( $query->posts as $post ) {
						echo $this->render_slide( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>
			<div class="my-catalog-news-carousel__navigation" data-wp-bind--hidden="!state.hasOverflow">
				<button
					type="button"
					class="my-catalog-news-carousel__arrow my-catalog-news-carousel__arrow--prev"
					aria-label="<?php esc_attr_e( 'Previous slide', 'my-catalog' ); ?>"
					data-wp-on--click="actions.previous"
					data-wp-bind--disabled="state.isAtStart"
				>
					<span aria-hidden="true">&larr;</span>
				</button>
				<div class="my-catalog-news-carousel__pagination" aria-label="<?php esc_attr_e( 'News carousel pagination', 'my-catalog' ); ?>">
					<?php for ( $index = 0; $index < $query->post_count; $index++ ) : ?>
						<button
							type="button"
							class="my-catalog-news-carousel__dot"
							aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'my-catalog' ), $index + 1 ) ); ?>"
							data-wp-context='{ "targetIndex": <?php echo esc_attr( wp_json_encode( $index ) ); ?> }'
							data-wp-on--click="actions.goTo"
							data-wp-class--is-active="state.isDotActive"
						></button>
					<?php endfor; ?>
				</div>
				<button
					type="button"
					class="my-catalog-news-carousel__arrow my-catalog-news-carousel__arrow--next"
					aria-label="<?php esc_attr_e( 'Next slide', 'my-catalog' ); ?>"
					data-wp-on--click="actions.next"
					data-wp-bind--disabled="state.isAtEnd"
				>
					<span aria-hidden="true">&rarr;</span>
				</button>
			</div>
		</div>
		<?php
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * Renders a single news slide using a template override when available.
	 *
	 * @param WP_Post $post News post.
	 * @return string
	 */
	private function render_slide( WP_Post $post ) {
		$template = $this->locate_slide_template();
		$link     = get_post_meta( $post->ID, My_Catalog_Core::NEWS_META_READ_MORE_URL, true );
		$link     = $link ? $link : get_permalink( $post );
		$excerpt  = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
		$data     = array(
			'post'        => $post,
			'title'       => get_the_title( $post ),
			'excerpt'     => $excerpt,
			'image_html'  => get_the_post_thumbnail( $post, 'large', array( 'class' => 'my-catalog-news-carousel__image' ) ),
			'link'        => $link,
			'button_text' => __( 'Read More', 'my-catalog' ),
		);

		ob_start();
		include $template;
		return (string) ob_get_clean();
	}

	/**
	 * Locates the slide template with theme overrides.
	 *
	 * @return string
	 */
	private function locate_slide_template() {
		$template = locate_template(
			array(
				'my-catalog/news-slide.php',
				'my-catalog-news-slide.php',
			)
		);

		if ( $template ) {
			return $template;
		}

		return MY_CATALOG_PATH . 'templates/news-slide.php';
	}
}
