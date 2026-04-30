import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from '../block.json';

import './editor.scss';
import './style.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
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
									setAttributes( { autoplayDelay: nextValue || 1000 } )
								}
								min={ 1000 }
								max={ 10000 }
								step={ 500 }
							/>
						) }
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},
	save() {
		return null;
	},
} );
