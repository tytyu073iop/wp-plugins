<?php
/**
 * News carousel slide template.
 *
 * Available variables:
 * - array $data
 *
 * @package MyCatalog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="swiper-slide my-catalog-news-carousel__slide">
	<div class="my-catalog-news-carousel__media">
		<?php if ( ! empty( $data['image_html'] ) ) : ?>
			<?php echo $data['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</div>
	<div class="my-catalog-news-carousel__body">
		<h3 class="my-catalog-news-carousel__title"><?php echo esc_html( $data['title'] ); ?></h3>
		<p class="my-catalog-news-carousel__excerpt"><?php echo esc_html( $data['excerpt'] ); ?></p>
		<a class="my-catalog-news-carousel__link" href="<?php echo esc_url( $data['link'] ); ?>">
			<?php echo esc_html( $data['button_text'] ); ?>
		</a>
	</div>
</article>
