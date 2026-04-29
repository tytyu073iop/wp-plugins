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

	$( function () {
		$( '.my-catalog-product-table' ).each( function () {
			const wrapper = this;
			const config = parseConfig( wrapper );

			if ( ! config || ! $.fn.DataTable ) {
				return;
			}

			const $wrapper = $( wrapper );
			const $table = $wrapper.find( 'table' );
			const $category = $wrapper.find( '.js-my-catalog-product-category' );
			const $tag = $wrapper.find( '.js-my-catalog-product-tag' );
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
							category: $category.val() || '',
							tag: $tag.val() || '',
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

			$category.add( $tag ).on( 'change', function () {
				table.ajax.reload();
			} );
		} );
	} );
}( jQuery ) );
