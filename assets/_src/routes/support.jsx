/**
 * External dependencies
 */
import { useContext } from 'react';
import { useNavigate } from 'react-router-dom';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { post } from '../js/helpers/post';
import SettingsContext from '../context/settings';

/**
 * Cloudflare Images experimental settings routes.
 *
 * @return {JSX.Element} Cloudflare experimental component.
 * @class
 */
const Support = () => {
	const { hideNotice } = useContext( SettingsContext );
	const navigate = useNavigate();

	const removeSection = () => {
		post( 'cf_images_hide_sidebar' )
			.then( () => {
				hideNotice( true );
				return navigate( '/' );
			} )
			.catch( window.console.log );
	};

	return (
		<div className="content is-normal">
			<h3 className="is-size-4 mb-3">{ __( 'Additional resources', 'cf-images' ) }</h3>
			<p>{ __( 'Below is a list of links to resources that will help you get started or get additional help:', 'cf-images' ) }</p>

			<ul>
				<li>
					<a href="https://vcore.au/tutorials/how-to-setup-cloudflare-images-plugin/" target="_blank" rel="noopener noreferrer">
						{ __( 'How-to Guide', 'cf-images' ) }
					</a>: { __( 'step-by-step instructions with screenshots for setting up the plugin.', 'cf-images' ) }
				</li>
				<li>
					<a href="https://wordpress.org/support/plugin/cf-images/" target="_blank" rel="noopener noreferrer">
						{ __( 'WordPress support forums', 'cf-images' ) }
					</a>: { __( 'engage with the community and find answers to common queries.', 'cf-images' ) }
				</li>
				<li>
					<a href="https://vcore.au/contact-us/" target="_blank" rel="noopener noreferrer">
						{ __( 'Contact me', 'cf-images' ) }
					</a>: { __( 'should you have specific concerns, reach out directly through my contact form.', 'cf-images' ) }
				</li>
			</ul>

			<h4 className="is-size-5 mb-3">{ __( 'Support the project', 'cf-images' ) }</h4>
			<p>{ __( 'This is a free plugin, if you find it useful, please consider supporting it by:', 'cf-images' ) }</p>

			<ul>
				<li>
					{ __( 'Share your insights and suggestions on the support forums. Your feedback drives our improvements.', 'cf-images' ) }
				</li>
				<li>
					{ __( 'Submit a pull request: ', 'cf-images' ) }
					<a href="https://github.com/av3nger/cf-images" rel="noopener noreferrer" target="_blank">
						{ __( 'GitHub', 'cf-images' ) }
					</a>
				</li>
				<li>
					<a
						href="https://www.paypal.com/donate/?business=JRR6QPRGTZ46N&no_recurring=0&item_name=Help+support+the+development+of+the+Cloudflare+Images+plugin+for+WordPress&currency_code=AUD"
						rel="noopener noreferrer"
						target="_blank"
					>
						{ __( 'Buy me a coffee', 'cf-images' ) }
					</a>
				</li>
			</ul>

			<p className="mb-2">
				{ __( 'Unlike many plugins out there, I will not spam your dashboard with notices, heck, I even have this nice option:', 'cf-images' ) }
			</p>
			<button className="button is-small" onClick={ removeSection }>
				{ __( 'Hide this section and never show it again', 'cf-images' ) }
			</button>
		</div>
	);
};

export default Support;
