import { getContext, getElement, store } from '@wordpress/interactivity';

const namespace = 'my-catalog/news-carousel';

const getVisibleSlides = ( context ) => {
	if ( typeof window === 'undefined' ) {
		return 1;
	}

	if ( window.innerWidth >= 1024 ) {
		return Math.max( 1, parseInt( context.desktopSlides, 10 ) || 1 );
	}

	if ( window.innerWidth >= 640 ) {
		return Math.min( Math.max( 1, parseInt( context.desktopSlides, 10 ) || 1 ), 2 );
	}

	return 1;
};

const getMaxIndex = ( context ) =>
	Math.max( 0, ( parseInt( context.slideCount, 10 ) || 0 ) - ( parseInt( context.visibleSlides, 10 ) || 1 ) );

const clampIndex = ( index, context ) =>
	Math.min( Math.max( 0, index ), getMaxIndex( context ) );

const hasOverflow = ( context ) =>
	( parseInt( context.slideCount, 10 ) || 0 ) > ( parseInt( context.visibleSlides, 10 ) || 1 );

store( namespace, {
	state: {
		get hasOverflow() {
			const context = getContext();
			return hasOverflow( context );
		},
		get isAtStart() {
			const context = getContext();
			return ! hasOverflow( context ) || ( parseInt( context.currentIndex, 10 ) || 0 ) <= 0;
		},
		get isAtEnd() {
			const context = getContext();
			return ! hasOverflow( context ) || ( parseInt( context.currentIndex, 10 ) || 0 ) >= getMaxIndex( context );
		},
		get isDotActive() {
			const context = getContext();
			return ( parseInt( context.currentIndex, 10 ) || 0 ) === ( parseInt( context.targetIndex, 10 ) || 0 );
		},
	},
	actions: {
		previous() {
			const context = getContext();
			context.currentIndex = clampIndex( ( parseInt( context.currentIndex, 10 ) || 0 ) - 1, context );
		},
		next() {
			const context = getContext();
			context.currentIndex = clampIndex( ( parseInt( context.currentIndex, 10 ) || 0 ) + 1, context );
		},
		goTo() {
			const context = getContext();
			context.currentIndex = clampIndex( parseInt( context.targetIndex, 10 ) || 0, context );
		},
		pause() {
			const context = getContext();
			context.isPaused = true;
		},
		resume() {
			const context = getContext();
			context.isPaused = false;
		},
	},
	callbacks: {
		handleResize() {
			const context = getContext();
			context.visibleSlides = getVisibleSlides( context );
			context.currentIndex = clampIndex( parseInt( context.currentIndex, 10 ) || 0, context );
		},
		initCarousel() {
			const context = getContext();
			context.visibleSlides = getVisibleSlides( context );
			context.currentIndex = clampIndex( parseInt( context.currentIndex, 10 ) || 0, context );
		},
		initAutoplay() {
			const context = getContext();

			if ( ! context.autoplay ) {
				return undefined;
			}

			const intervalId = window.setInterval( () => {
				if ( context.isPaused ) {
					return;
				}

				const maxIndex = getMaxIndex( context );

				if ( maxIndex <= 0 ) {
					return;
				}

				context.currentIndex =
					( parseInt( context.currentIndex, 10 ) || 0 ) >= maxIndex
						? 0
						: ( parseInt( context.currentIndex, 10 ) || 0 ) + 1;
			}, Math.max( 1000, parseInt( context.autoplayDelay, 10 ) || 5000 ) );

			return () => window.clearInterval( intervalId );
		},
		updateTrack() {
			const context = getContext();
			const { ref } = getElement();
			const visibleSlides = Math.max( 1, parseInt( context.visibleSlides, 10 ) || 1 );
			const currentIndex = clampIndex( parseInt( context.currentIndex, 10 ) || 0, context );

			ref.style.setProperty( '--my-catalog-visible-slides', String( visibleSlides ) );
			ref.style.transform = `translate3d(calc(-${ currentIndex } * ((100% - (var(--my-catalog-gap) * (${ visibleSlides } - 1))) / ${ visibleSlides } + var(--my-catalog-gap))), 0, 0)`;
		},
	},
} );
