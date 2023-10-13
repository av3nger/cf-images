/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import { mdiLinkVariant } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { post } from '../js/helpers/post';
import Card from '../components/card';
import SettingsContext from '../context/settings';
import classNames from 'classnames';

const CustomDomain = () => {
	const [ domain, setDomain ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ saving, setSaving ] = useState( false );

	const { modules } = useContext( SettingsContext );

	const moduleId = 'custom-domain';

	const saveDomain = () => {
		setError( '' );
		setSaving( true );

		post( 'cf_images_set_custom_domain', { domain } )
			.then( ( response ) => {
				setSaving( false );

				if ( ! response.success && response.data ) {
					setError( response.data );
				}
			} )
			.catch( window.console.log );
	};

	return (
		<Card
			icon={ mdiLinkVariant }
			id={ moduleId }
			title={ __( 'Serve from custom domain', 'cf-images' ) }
		>
			<div className="content">
				<p>{ __( 'Use the current site domain instead of `imagedelivery.net`, or specify a custom domain.', 'cf-images' ) }</p>
				<p>{ __( 'Note: The domain must be linked with Cloudflare in order to work correctly.', 'cf-images' ) }</p>

				{ moduleId in modules && modules[ moduleId ] && (
					<div className="field has-addons">
						<div className="control is-expanded">
							<label htmlFor="custom-domain" className="screen-reader-text">
								{ __( 'Set custom domain', 'cf-images' ) }
							</label>
							<input
								className={ classNames( 'input is-fullwidth', { 'is-danger': error } ) }
								id="custom-domain"
								onChange={ ( e ) => setDomain( e.target.value ) }
								placeholder="https://cdn.example.com"
								type="text"
								value={ domain }
							/>
							{ error && (
								<p className="help is-danger">{ error }</p>
							) }
						</div>
						<div className="control">
							<button
								className={ classNames( 'button is-info', { 'is-loading': saving } ) }
								onClick={ saveDomain }
							>
								{ __( 'Set', 'cf-images' ) }
							</button>
						</div>
					</div>
				) }
			</div>
		</Card>
	);
};

export default CustomDomain;
