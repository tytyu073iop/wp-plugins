import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, TextareaControl } from '@wordpress/components';

import metadata from '../block.json';

import './editor.scss';
import './style.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const { message } = attributes;
		const blockProps = useBlockProps( {
			className: 'wp-block-my-catalog-notice',
		} );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Notice settings', 'my-catalog' ) }>
						<TextareaControl
							label={ __( 'Message', 'my-catalog' ) }
							value={ message }
							onChange={ ( nextMessage ) =>
								setAttributes( { message: nextMessage } )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<RichText
						tagName="p"
						value={ message }
						onChange={ ( nextMessage ) =>
							setAttributes( { message: nextMessage } )
						}
						placeholder={ __( 'Write a catalog message…', 'my-catalog' ) }
					/>
				</div>
			</>
		);
	},
	save( { attributes } ) {
		const { message } = attributes;
		const blockProps = useBlockProps.save( {
			className: 'wp-block-my-catalog-notice',
		} );

		return (
			<div { ...blockProps }>
				<RichText.Content tagName="p" value={ message } />
			</div>
		);
	},
} );
