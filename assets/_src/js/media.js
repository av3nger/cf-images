/* global _ */

import '../css/media.scss';

( function( _ ) {
	'use strict';

	const Details = wp.media.view.Attachment.Details;

	// Do not run on media library list mode.
	if ( 'undefined' === typeof Details.TwoColumn ) {
		return;
	}

	const sharedTemplate =
		"<span class='setting cf-images'>" +
		"<span class='name'><%= label %></span>" +
		"<span class='value cf-images-status'><%= value %></span>" +
		'</span>';

	const template = _.template( sharedTemplate );

	Details.TwoColumn = Details.TwoColumn.extend(
		{
			initialize() {
				Details.prototype.initialize.apply( this, arguments );
				this.listenTo( this.model, 'change:cf-images-status', this.render );
			},

			render() {
				Details.prototype.render.apply( this, arguments );

				const html = this.model.get( 'cf-images-status' );
				if ( 'undefined' === typeof html ) {
					return this;
				}

				this.model.fetch();
				this.views.detach();

				const status = template( {
					label: 'Offload status',
					value: html,
				} );

				this.$el.find( '.settings' ).prepend( status );
				this.views.render();
			},
		}
	);
}( _ ) );
