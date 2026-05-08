( function ( $ ) {
	'use strict';

	function parseConfig( element ) {
		const rawConfig = element.getAttribute( 'data-config' );

		if ( ! rawConfig ) {
			return null;
		}

		try {
			return JSON.parse( rawConfig );
		} catch ( error ) {
			return null;
		}
	}

	function getFilterValue( $fields ) {
		let value = '';

		$fields.each( function () {
			const nextValue = $( this ).val();

			if ( nextValue ) {
				value = nextValue;
			}
		} );

		return value;
	}

	function toNumber( value, fallback ) {
		const number = parseFloat( value );

		return Number.isFinite( number ) ? number : fallback;
	}

	function formatPriceValue( value ) {
		return Number.isInteger( value ) ? String( value ) : value.toFixed( 2 );
	}

	function updatePriceRangeTrack( $range ) {
		const minBound = toNumber( $range.data( 'min' ), 0 );
		const maxBound = toNumber( $range.data( 'max' ), minBound + 1 );
		const minValue = toNumber( $range.find( '.js-my-catalog-product-price-min-range' ).val(), minBound );
		const maxValue = toNumber( $range.find( '.js-my-catalog-product-price-max-range' ).val(), maxBound );
		const span = maxBound - minBound || 1;
		const left = ( ( minValue - minBound ) / span ) * 100;
		const right = 100 - ( ( maxValue - minBound ) / span ) * 100;

		$range.find( '.my-catalog-product-table__range-track' ).css( {
			'--range-left': left + '%',
			'--range-right': right + '%',
		} );
	}

	function syncPriceRange( $range, changedField ) {
		const minBound = toNumber( $range.data( 'min' ), 0 );
		const maxBound = toNumber( $range.data( 'max' ), minBound + 1 );
		const $minField = $range.find( '.js-my-catalog-product-price-min' );
		const $maxField = $range.find( '.js-my-catalog-product-price-max' );
		const $minRange = $range.find( '.js-my-catalog-product-price-min-range' );
		const $maxRange = $range.find( '.js-my-catalog-product-price-max-range' );
		let minValue = toNumber( $minField.val(), minBound );
		let maxValue = toNumber( $maxField.val(), maxBound );

		if ( changedField === 'min-range' ) {
			minValue = toNumber( $minRange.val(), minBound );
		}

		if ( changedField === 'max-range' ) {
			maxValue = toNumber( $maxRange.val(), maxBound );
		}

		minValue = Math.min( Math.max( minValue, minBound ), maxBound );
		maxValue = Math.min( Math.max( maxValue, minBound ), maxBound );

		if ( minValue > maxValue ) {
			if ( changedField && changedField.indexOf( 'min' ) === 0 ) {
				maxValue = minValue;
			} else {
				minValue = maxValue;
			}
		}

		$minField.val( formatPriceValue( minValue ) );
		$maxField.val( formatPriceValue( maxValue ) );
		$minRange.val( minValue );
		$maxRange.val( maxValue );
		updatePriceRangeTrack( $range );
	}

	function getPriceFilterValue( $fields, boundName ) {
		let value = '';

		$fields.each( function () {
			const $field = $( this );
			const $range = $field.closest( '.js-my-catalog-product-price-range' );
			const bound = toNumber( $range.data( boundName ), 0 );
			const nextValue = toNumber( $field.val(), bound );

			if ( nextValue !== bound ) {
				value = nextValue;
			}
		} );

		return value;
	}

	$( function () {
		$( '.my-catalog-product-table' ).each( function () {
			const wrapper = this;
			const config = parseConfig( wrapper );

			if ( ! config || ! $.fn.DataTable ) {
				return;
			}

			const $wrapper = $( wrapper );
			const $table = $wrapper.find( 'table' );
			const $internalFilters = $wrapper.find( '.my-catalog-product-table__filters' );
			const $externalFilters = $( '.my-catalog-product-filters' ).filter( function () {
				const target = $( this ).data( 'target' );

				return ! target || target === wrapper.id;
			} );
			const $filters = $internalFilters.add( $externalFilters );
			const $category = $filters.find( '.js-my-catalog-product-category' );
			const $priceMin = $filters.find( '.js-my-catalog-product-price-min' );
			const $priceMax = $filters.find( '.js-my-catalog-product-price-max' );
			const $priceRanges = $filters.find( '.js-my-catalog-product-price-range' );
			const columns = ( config.columns || [] ).map( function ( column ) {
				return {
					data: column.key,
					name: column.key,
					orderable: !! column.orderable,
					searchable: true,
					defaultContent: '&mdash;',
				};
			} );

			const table = $table.DataTable( {
				processing: true,
				serverSide: true,
				searching: config.search !== false,
				pageLength: config.pageLength || 10,
				responsive: true,
				autoWidth: false,
				order: [ config.defaultOrder || [ 0, 'asc' ] ],
				ajax: {
					url: config.restUrl || myCatalogProductTable.restUrl,
					type: 'GET',
					data: function ( data ) {
						const order = data.order && data.order[ 0 ] ? data.order[ 0 ] : { column: 0, dir: 'asc' };
						const orderColumn = columns[ order.column ] ? columns[ order.column ].name : 'title';

						return {
							draw: data.draw,
							start: data.start,
							length: data.length,
							search: data.search ? data.search.value : '',
							order_column: orderColumn,
							order_dir: order.dir || 'asc',
							category: getFilterValue( $category ),
							tag: '',
							price_min: getPriceFilterValue( $priceMin, 'min' ),
							price_max: getPriceFilterValue( $priceMax, 'max' ),
							base_category: config.baseCategory || '',
							base_tag: config.baseTag || '',
							columns: columns.map( function ( column ) {
								return column.name;
							} ),
						};
					},
				},
				columns: columns,
			} );

			$table.on( 'click', 'tbody tr', function ( event ) {
				if ( $( event.target ).closest( 'a, button, input, select, textarea' ).length ) {
					return;
				}

				const row = table.row( this ).data();

				if ( row && row.permalink ) {
					window.location.href = row.permalink;
				}
			} );

			$category.on( 'change', function () {
				table.ajax.reload();
			} );

			$priceRanges.each( function () {
				syncPriceRange( $( this ) );
			} );

			$priceRanges.on( 'input change', 'input', function () {
				const $field = $( this );
				let changedField = '';

				if ( $field.hasClass( 'js-my-catalog-product-price-min-range' ) ) {
					changedField = 'min-range';
				} else if ( $field.hasClass( 'js-my-catalog-product-price-max-range' ) ) {
					changedField = 'max-range';
				} else if ( $field.hasClass( 'js-my-catalog-product-price-min' ) ) {
					changedField = 'min-field';
				} else if ( $field.hasClass( 'js-my-catalog-product-price-max' ) ) {
					changedField = 'max-field';
				}

				syncPriceRange( $field.closest( '.js-my-catalog-product-price-range' ), changedField );
				table.ajax.reload();
			} );
		} );
	} );
}( jQuery ) );
