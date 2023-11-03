/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import classNames from 'classnames';

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
import ImageGenerate from '../../modules/image-generate';

/**
 * Cloudflare Images settings routes.
 *
 * @return {JSX.Element} Cloudflare settings component.
 * @class
 */
const ToolsSettings = () => {
	const [ loading, setLoading ] = useState( false );
	const { hasFuzion, setFuzion } = useContext( SettingsContext );

	const disconnect = ( e ) => {
		e.preventDefault();
		setLoading( true );

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
				<ImageGenerate />

				<div className="column is-full has-text-centered">
					<button
						className={ classNames( 'button is-ghost is-small', { 'is-loading': loading } ) }
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
