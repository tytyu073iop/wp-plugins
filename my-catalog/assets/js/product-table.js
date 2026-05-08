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
		} );
	} );
}( jQuery ) );
