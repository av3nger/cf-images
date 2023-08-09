/**
 * Show a notice.
 *
 * @since 1.0.0
 * @param {string} message Message text.
 * @param {string} type    Notice type.
 */
export const showNotice = function( message, type = 'success' ) {
	const notice = jQuery( '#cf-images-ajax-notice' );

	notice.addClass( 'notice-' + type );
	notice.find( 'p' ).html( message );

	notice.slideDown().delay( 5000 ).queue( function() {
		jQuery( this ).slideUp( 'slow', function() {
			jQuery( this ).removeClass( 'notice-' + type );
			jQuery( this ).find( 'p' ).html( '' );
		} );
		jQuery.dequeue( this );
	} );
};
