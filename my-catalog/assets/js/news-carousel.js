document.addEventListener( 'DOMContentLoaded', function () {
	document
		.querySelectorAll( '.my-catalog-news-carousel' )
		.forEach( function ( carousel ) {
			const rawConfig = carousel.getAttribute( 'data-config' );

			if ( ! rawConfig || typeof Swiper === 'undefined' ) {
				return;
			}

			let config;

			try {
				config = JSON.parse( rawConfig );
			} catch ( error ) {
				return;
			}

			const slideCount = Math.max(
				1,
				parseInt( config.slideCount, 10 ) || 1
			);
			const autoplayEnabled =
				Boolean( config.autoplay ) && slideCount > 1;

			new Swiper( carousel.querySelector( '.swiper' ), {
				slidesPerView: 1,
				spaceBetween: 24,
				loop: slideCount > 1,
				grabCursor: true,
				autoplay: autoplayEnabled
					? {
							delay: Math.max(
								1000,
								parseInt( config.autoplayDelay, 10 ) || 5000
							),
							disableOnInteraction: false,
							pauseOnMouseEnter: true,
					  }
					: false,
				navigation: {
					nextEl: carousel.querySelector(
						'.my-catalog-news-carousel__arrow--next'
					),
					prevEl: carousel.querySelector(
						'.my-catalog-news-carousel__arrow--prev'
					),
				},
				pagination: {
					el: carousel.querySelector( '.swiper-pagination' ),
					clickable: true,
				},
			} );
		} );
} );
