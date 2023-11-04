/**
 * External dependencies
 */
import { FormEvent, useContext, useState } from 'react';
import Icon from '@mdi/react';
import { mdiIdentifier, mdiKey, mdiOpenInNew } from '@mdi/js';
import * as classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { post } from '../../js/helpers/post';
import SettingsContext from '../../context/settings';

/**
 * Login component.
 *
 * @class
 */
const CloudflareLogin = () => {
	const [loading, setLoading] = useState(false);
	const [error, setError] = useState('');

	const { setCfConnected } = useContext(SettingsContext);

	const saveCredentials = (e: FormEvent<HTMLFormElement>) => {
		e.preventDefault();
		setLoading(true);

		const formData = new FormData(e.currentTarget);
		const args = {
			'account-id': formData.get('account-id') ?? '',
			'api-key': formData.get('api-key') ?? '',
		};

		post('cf_images_do_setup', args)
			.then((response: ApiResponse) => {
				if (!response.success && 'undefined' !== typeof response.data) {
					setError(response.data);
				} else {
					setCfConnected(true);
				}
			})
			.catch(window.console.log)
			.finally(() => setLoading(false));
	};

	return (
		<section className="hero is-halfheight">
			<div className="hero-body">
				<div className="container is-max-desktop">
					<div className="content">
						<p>
							{__(
								'For proper functionality, the plugin requires access to Cloudflare Images API.',
								'cf-images'
							)}
						</p>

						<h3>{__('Manual setup', 'cf-images')}</h3>

						<p>
							{__(
								'Add the following defines to you wp-config.php file:',
								'cf-images'
							)}
						</p>

						<pre>
							<code>
								{`define( 'CF_IMAGES_ACCOUNT_ID', '<ACCOUNT ID>' );`}
								<br />
								{`define( 'CF_IMAGES_KEY_TOKEN', '<API KEY>' );`}
							</code>
						</pre>

						<h3>{__('Auto setup', 'cf-images')}</h3>

						<p>
							{__(
								'The form will attempt to automatically set the required defines in wp-config.php file.',
								'cf-images'
							)}
						</p>

						<form onSubmit={(e) => saveCredentials(e)}>
							<div className="field">
								<label className="label" htmlFor="account-id">
									{__('Cloudflare Account ID', 'cf-images')}
								</label>
								<div
									className={classNames(
										'control has-icons-left',
										{ 'is-loading': loading }
									)}
								>
									<input
										autoComplete="off"
										className={classNames('input', {
											'is-danger': error,
										})}
										disabled={loading}
										id="account-id"
										name="account-id"
										placeholder={__(
											'Paste your Cloudflare ID here',
											'cf-images'
										)}
										required
										type="text"
									/>
									<span className="icon is-small is-left">
										<Icon path={mdiIdentifier} size={1} />
									</span>
								</div>
							</div>

							<div className="field">
								<label className="label" htmlFor="api-key">
									{__('Cloudflare API Token', 'cf-images')}
								</label>
								<div
									className={classNames(
										'control has-icons-left',
										{ 'is-loading': loading }
									)}
								>
									<input
										autoComplete="off"
										className={classNames('input', {
											'is-danger': error,
										})}
										disabled={loading}
										id="api-key"
										name="api-key"
										placeholder={__(
											'Paste your Cloudflare API key here',
											'cf-images'
										)}
										required
										type="text"
									/>
									<span className="icon is-small is-left">
										<Icon path={mdiKey} size={1} />
									</span>
									{error && (
										<p className="help is-danger">
											{error}
										</p>
									)}
								</div>
							</div>

							<button
								className={classNames(
									'button is-primary is-fullwidth mt-5',
									{ 'is-loading': loading }
								)}
								type="submit"
							>
								{__('Save changes', 'cf-images')}
							</button>
						</form>

						<h3>
							{__(
								'How to get account ID and API token?',
								'cf-images'
							)}
						</h3>

						<p>
							{__(
								'A detailed guide on how to setup the plugin can be found here: ',
								'cf-images'
							)}
							<a
								className="is-inline-flex is-align-items-center"
								href="https://vcore.au/tutorials/how-to-setup-cloudflare-images-plugin/"
								rel="noopener noreferrer"
								target="_blank"
							>
								{__('How-to Guide', 'cf-images')}&nbsp;
								<Icon path={mdiOpenInNew} size={0.6} />
							</a>
						</p>

						<ol>
							<li>
								{__(
									'Log in to the Cloudflare dashboard, and select your account and website',
									'cf-images'
								)}
								&nbsp;(
								<a
									className="is-inline-flex is-align-items-center"
									href="https://dash.cloudflare.com/login"
									rel="noopener noreferrer"
									target="_blank"
								>
									{__('link', 'cf-images')}&nbsp;
									<Icon path={mdiOpenInNew} size={0.6} />
								</a>
								)
							</li>
							<li>
								{__(
									'In the "Overview" section, scroll down to find your Account ID and paste it in the form above',
									'cf-images'
								)}
							</li>
							<li>
								{__(
									'Next, create a custom token with the correct "Read" and "Update" permissions',
									'cf-images'
								)}
							</li>
							<li>
								{__(
									'In the Cloudflare dashboard, locate "API Tokens" under "My Profile > API Tokens"',
									'cf-images'
								)}
								&nbsp;(
								<a
									className="is-inline-flex is-align-items-center"
									href="https://dash.cloudflare.com/profile/api-tokens"
									rel="noopener noreferrer"
									target="_blank"
								>
									{__('link', 'cf-images')}&nbsp;
									<Icon path={mdiOpenInNew} size={0.6} />
								</a>
								)
							</li>
							<li>{__('Select "Create Token"', 'cf-images')}</li>
							<li>
								{__(
									'In Custom token, select "Get started"',
									'cf-images'
								)}
							</li>
							<li>
								{__(
									'Give your custom token a name',
									'cf-images'
								)}
							</li>
							<li>
								{__('Scroll to "Permissions"', 'cf-images')}
							</li>
							<li>
								{__(
									'On the "Select item…" drop-down menu, choose "Cloudflare Images"',
									'cf-images'
								)}
							</li>
							<li>
								{__(
									'In the next drop-down menu, choose "Edit"',
									'cf-images'
								)}
								<img
									alt={__(
										'How to create a custom token for Cloudflare images',
										'cf-images'
									)}
									src={
										window.CFImages.dirURL +
										'/assets/images/step-02-custom-token-setup.jpg'
									}
								/>
							</li>
							<li>
								{__(
									'Select "Continue to summary > Create Token"',
									'cf-images'
								)}
							</li>
							<li>
								{__(
									'Your token for Cloudflare Images is now created, paste it in the form above.',
									'cf-images'
								)}
							</li>
						</ol>
					</div>
				</div>
			</div>
		</section>
	);
};

export default CloudflareLogin;
