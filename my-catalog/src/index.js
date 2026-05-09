import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useState, useEffect } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';

import metadata from '../block.json';
import productFiltersMetadata from '../blocks/product-filters/block.json';

import './editor.scss';
import './style.scss';

function NewsCarouselEdit( { attributes, setAttributes } ) {
	const { limit, category, autoplay, autoplayDelay } = attributes;
	const blockProps = useBlockProps( {
		className: 'wp-block-my-catalog-news-carousel',
	} );
	const categories = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords( 'taxonomy', 'news_category', {
				per_page: -1,
				hide_empty: false,
			} ),
		[]
	);
	const categoryOptions = [
		{ label: __( 'All categories', 'my-catalog' ), value: '' },
		...( categories || [] ).map( ( term ) => ( {
			label: term.name,
			value: term.slug,
		} ) ),
	];

	const [ posts, setPosts ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );

	useEffect( () => {
		setIsLoading( true );
		const categoryTerm = categories?.find(
			( t ) => t.slug === category || String( t.id ) === category
		);
		apiFetch( {
			path: addQueryArgs( '/wp/v2/news', {
				per_page: limit,
				_embed: true,
				orderby: 'date',
				order: 'desc',
				...( categoryTerm && { news_category: categoryTerm.id } ),
			} ),
		} )
			.then( ( data ) => {
				setPosts( data );
				setIsLoading( false );
			} )
			.catch( () => {
				setPosts( [] );
				setIsLoading( false );
			} );
	}, [ limit, category, categories ] );

	// Determine carousel content without nested ternary
	let carouselContent;
	if ( isLoading ) {
		carouselContent = (
			<div className="my-catalog-news-carousel__control-state">
				<Spinner />
			</div>
		);
	} else if ( ! posts || posts.length === 0 ) {
		carouselContent = (
			<div className="my-catalog-news-carousel__empty">
				{ __( 'No news items found.', 'my-catalog' ) }
			</div>
		);
	} else {
		carouselContent = (
			<div className="my-catalog-news-carousel">
				<div className="swiper">
					<div className="swiper-wrapper">
						{ posts.map( ( post ) => {
							const imageUrl =
								post._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]
									?.source_url;
							const link =
								post.meta?._my_catalog_read_more_url ||
								post.link;
							return (
								<article
									key={ post.id }
									className="swiper-slide my-catalog-news-carousel__slide"
								>
									{ imageUrl && (
										<div className="my-catalog-news-carousel__media">
											<img
												className="my-catalog-news-carousel__image"
												src={ imageUrl }
												alt={
													post.title?.rendered || ''
												}
											/>
										</div>
									) }
									<div className="my-catalog-news-carousel__body">
										<h3
											className="my-catalog-news-carousel__title"
											dangerouslySetInnerHTML={ {
												__html:
													post.title?.rendered || '',
											} }
										/>
										<p
											className="my-catalog-news-carousel__excerpt"
											dangerouslySetInnerHTML={ {
												__html:
													post.excerpt?.rendered ||
													'',
											} }
										/>
										<a
											className="my-catalog-news-carousel__link"
											href={ link }
										>
											{ __( 'Read More', 'my-catalog' ) }
										</a>
									</div>
								</article>
							);
						} ) }
					</div>
				</div>
				<div className="my-catalog-news-carousel__navigation">
					<button
						type="button"
						className="my-catalog-news-carousel__arrow my-catalog-news-carousel__arrow--prev"
						aria-label={ __( 'Previous slide', 'my-catalog' ) }
					>
						<span aria-hidden="true">&larr;</span>
					</button>
					<div className="swiper-pagination"></div>
					<button
						type="button"
						className="my-catalog-news-carousel__arrow my-catalog-news-carousel__arrow--next"
						aria-label={ __( 'Next slide', 'my-catalog' ) }
					>
						<span aria-hidden="true">&rarr;</span>
					</button>
				</div>
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Carousel settings', 'my-catalog' ) }>
					<RangeControl
						label={ __( 'News items', 'my-catalog' ) }
						value={ limit }
						onChange={ ( nextValue ) =>
							setAttributes( { limit: nextValue || 1 } )
						}
						min={ 1 }
						max={ 12 }
					/>
					{ categories === null ? (
						<div className="my-catalog-news-carousel__control-state">
							<Spinner />
						</div>
					) : (
						<SelectControl
							label={ __( 'Category', 'my-catalog' ) }
							value={ category }
							options={ categoryOptions }
							onChange={ ( nextValue ) =>
								setAttributes( { category: nextValue } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Autoplay', 'my-catalog' ) }
						checked={ autoplay }
						onChange={ ( nextValue ) =>
							setAttributes( { autoplay: nextValue } )
						}
					/>
					{ autoplay && (
						<RangeControl
							label={ __( 'Autoplay delay (ms)', 'my-catalog' ) }
							value={ autoplayDelay }
							onChange={ ( nextValue ) =>
								setAttributes( {
									autoplayDelay: nextValue || 1000,
								} )
							}
							min={ 1000 }
							max={ 10000 }
							step={ 500 }
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>{ carouselContent }</div>
		</>
	);
}

function ProductFiltersEdit( { attributes, setAttributes } ) {
	const { target, showCategory, showPrice } = attributes;
	const blockProps = useBlockProps( {
		className: 'wp-block-my-catalog-product-filters',
	} );
	const instanceId = useInstanceId( ProductFiltersEdit );

	const categories = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords( 'taxonomy', 'product_cat', {
				per_page: -1,
				hide_empty: true,
			} ),
		[]
	);

	const [ priceBounds, setPriceBounds ] = useState( null );
	const [ isLoadingPrice, setIsLoadingPrice ] = useState( true );

	useEffect( () => {
		apiFetch( { path: '/my-catalog/v1/price-bounds' } )
			.then( ( data ) => {
				setPriceBounds( data );
				setIsLoadingPrice( false );
			} )
			.catch( () => {
				setPriceBounds( { min: 0, max: 100 } );
				setIsLoadingPrice( false );
			} );
	}, [] );

	const isLoading = categories === null || isLoadingPrice;

	// Generate unique IDs for accessible labels
	const categorySelectId = `my-catalog-category-${ instanceId }`;
	const minPriceId = `my-catalog-price-min-${ instanceId }`;
	const maxPriceId = `my-catalog-price-max-${ instanceId }`;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Filter settings', 'my-catalog' ) }>
					<TextControl
						label={ __( 'Target table ID', 'my-catalog' ) }
						value={ target }
						onChange={ ( nextValue ) =>
							setAttributes( { target: nextValue } )
						}
						help={ __(
							'Leave empty to control every product table on the page.',
							'my-catalog'
						) }
					/>
					<ToggleControl
						label={ __( 'Category filter', 'my-catalog' ) }
						checked={ showCategory }
						onChange={ ( nextValue ) =>
							setAttributes( { showCategory: nextValue } )
						}
					/>
					<ToggleControl
						label={ __( 'Price filter', 'my-catalog' ) }
						checked={ showPrice }
						onChange={ ( nextValue ) =>
							setAttributes( { showPrice: nextValue } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ isLoading ? (
					<div className="my-catalog-news-carousel__control-state">
						<Spinner />
					</div>
				) : (
					<div
						className="my-catalog-product-table__filters my-catalog-product-filters"
						{ ...( target ? { 'data-target': target } : {} ) }
					>
						{ showCategory && (
							<div className="my-catalog-product-table__filter">
								<label
									htmlFor={ categorySelectId }
									className="my-catalog-product-table__filter-label"
								>
									<span>
										{ __( 'Category', 'my-catalog' ) }
									</span>
								</label>
								<select
									id={ categorySelectId }
									className="js-my-catalog-product-category"
								>
									<option value="">
										{ __( 'All categories', 'my-catalog' ) }
									</option>
									{ ( categories || [] ).map( ( term ) => (
										<option
											key={ term.id }
											value={ term.slug }
										>
											{ term.name }
										</option>
									) ) }
								</select>
							</div>
						) }
						{ showPrice && priceBounds && (
							<div
								className="my-catalog-product-table__filter my-catalog-product-table__filter--price-range js-my-catalog-product-price-range"
								data-min={ priceBounds.min }
								data-max={ priceBounds.max }
								data-step="0.01"
							>
								<span>{ __( 'Price', 'my-catalog' ) }</span>
								<div className="my-catalog-product-table__price-fields">
									<div className="my-catalog-product-table__price-field">
										<label
											htmlFor={ minPriceId }
											className="my-catalog-product-table__price-label"
										>
											{ __( 'Min', 'my-catalog' ) }
										</label>
										<input
											id={ minPriceId }
											className="js-my-catalog-product-price-min"
											type="number"
											min={ priceBounds.min }
											max={ priceBounds.max }
											step="0.01"
											inputMode="decimal"
											value={ priceBounds.min }
											readOnly
										/>
									</div>
									<div className="my-catalog-product-table__price-field">
										<label
											htmlFor={ maxPriceId }
											className="my-catalog-product-table__price-label"
										>
											{ __( 'Max', 'my-catalog' ) }
										</label>
										<input
											id={ maxPriceId }
											className="js-my-catalog-product-price-max"
											type="number"
											min={ priceBounds.min }
											max={ priceBounds.max }
											step="0.01"
											inputMode="decimal"
											value={ priceBounds.max }
											readOnly
										/>
									</div>
								</div>
								<div className="my-catalog-product-table__range-slider">
									<div className="my-catalog-product-table__range-track"></div>
									<input
										className="js-my-catalog-product-price-min-range"
										type="range"
										min={ priceBounds.min }
										max={ priceBounds.max }
										step="0.01"
										value={ priceBounds.min }
										readOnly
										aria-label={ __(
											'Minimum price',
											'my-catalog'
										) }
									/>
									<input
										className="js-my-catalog-product-price-max-range"
										type="range"
										min={ priceBounds.min }
										max={ priceBounds.max }
										step="0.01"
										value={ priceBounds.max }
										readOnly
										aria-label={ __(
											'Maximum price',
											'my-catalog'
										) }
									/>
								</div>
							</div>
						) }
					</div>
				) }
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: NewsCarouselEdit,
	save() {
		return null;
	},
} );

registerBlockType( productFiltersMetadata.name, {
	edit: ProductFiltersEdit,
	save() {
		return null;
	},
} );
