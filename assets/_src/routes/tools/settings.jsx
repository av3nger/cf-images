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
import ImageAI from '../../modules/image-ai';
import ImageCompress from '../../modules/image-compress';
import Login from './login';
import CompressionStats from '../../modules/ai-stats';

/**
 * Cloudflare Images settings routes.
 *
 * @return {JSX.Element} Cloudflare settings component.
 * @class
 */
const ToolsSettings = () => {
	const { hasFuzion, setFuzion } = useContext( SettingsContext );

	const disconnect = ( e ) => {
		e.preventDefault();

		post( 'cf_images_ai_disconnect' )
			.then( () => setFuzion( false ) )
			.catch( window.console.log );
	};

	if ( hasFuzion ) {
		return (
			<div className="columns is-multiline">
				<CompressionStats />
				<ImageAI />
				<ImageCompress />

				<div className="column is-full has-text-centered">
					<button
						className="button is-ghost is-small"
						onClick={ ( e ) => disconnect( e ) }
					>
						{ __( 'Disconnect from API', 'cf-images' ) }
					</button>
				</div>
			</div>
		);
	}

	return (
		<Login />
	);
};

export default ToolsSettings;
