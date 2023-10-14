/**
 * External dependencies
 */
import { useContext } from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import { post } from '../../js/helpers/post';

const Disconnect = () => {
	const { setCfConnected } = useContext( SettingsContext );

	const disconnect = ( e ) => {
		e.preventDefault();

		post( 'cf_images_disconnect' )
			.then( () => setCfConnected( false ) )
			.catch( window.console.log );
	};

	return (
		<div className="column is-full has-text-centered">
			<button
				className="button is-ghost is-small"
				onClick={ ( e ) => disconnect( e ) }
			>
				{ __( 'Disconnect from API', 'cf-images' ) }
			</button>
		</div>
	);
};

export default Disconnect;
