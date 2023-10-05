import { runProgressBar } from '../helpers/progress';

( function( $ ) {
	'use strict';

	/**
	 * Bulk compress.
	 *
	 * @since 1.5.0
	 */
	$( '#cf-images-compress-all' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '.media_page_cf-images [role=button]' ).attr( 'disabled', true );
		runProgressBar( 'compress' );
	} );
}( jQuery ) );
