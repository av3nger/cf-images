/**
 * External dependencies
 */
import { useContext, useEffect, useState } from 'react';
import Icon from '@mdi/react';
import { mdiOpenInNew, mdiAlphabeticalVariant } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../../components/card';
import SettingsContext from '../../context/settings';
import { post } from '../../js/helpers/post';

const CustomPath = () => {
	const { domain, modules } = useContext(SettingsContext);

	const [error, setError] = useState('');
	const [loading, setLoading] = useState(true);
	const [path, setPath] = useState('');

	useEffect(() => {
		setError('');
		getStatus();
	}, []);

	const getStatus = (force = false) => {
		post('cf_images_get_cf_status', { force })
			.then((response: ApiResponse) => {
				if (!response.success && response.data) {
					setError(response.data);
					return;
				}

				if ('string' === typeof response.data) {
					setPath(response.data);
				} else if ('path' in response.data) {
					setPath(response.data.path);
				}
			})
			.catch(window.console.log)
			.finally(() => setLoading(false));
	};

	const reSyncStatus = () => {
		setLoading(true);
		setError('');
		getStatus(true);
	};

	if (('custom-domain' in modules && !modules['custom-domain']) || !domain) {
		return (
			<Card
				icon={mdiAlphabeticalVariant}
				title={__('Custom image URLs & stats', 'cf-images')}
				wide
			>
				<div className="content">
					<p>{__('To activate this option:', 'cf-images')}</p>
					<ol>
						<li>
							{__(
								'Enable the "Serve from custom domain" module in "Settings"',
								'cf-images'
							)}
						</li>
						<li>{__('Set a custom domain', 'cf-images')}</li>
						<li>
							<a
								href="https://getfuzion.io/cloudflare"
								target="_blank"
								rel="noopener noreferrer"
							>
								{__('Set up a Cloudflare worker')}
							</a>
						</li>
					</ol>
				</div>
			</Card>
		);
	}

	const format =
		domain +
		'/' +
		(path ? path : 'cdn-cgi/imagedelivery/<account_hash>') +
		'/<image>';

	return (
		<Card
			icon={mdiAlphabeticalVariant}
			id="custom-path"
			title={__('Custom image URLs & stats', 'cf-images')}
			wide
		>
			<div className="content">
				<p>
					{__('Format', 'cf-images')}:&nbsp;
					{loading ? __('Updating from API...', 'cf-images') : format}
				</p>
				<p>
					{__(
						'Use a custom string instead of the default `cdn-cgi/imagedelivery/<account_hash>` in the image URL.',
						'cf-images'
					)}
				</p>

				{'custom-path' in modules && modules['custom-path'] && (
					<>
						<div className="field has-addons">
							<div className="control is-expanded">
								<label
									htmlFor="custom-path"
									className="screen-reader-text"
								>
									{__('Set custom domain', 'cf-images')}
								</label>
								<input
									className="input is-fullwidth"
									disabled
									id="custom-path"
									onChange={(e) => setPath(e.target.value)}
									placeholder="cf-images"
									type="text"
									value={path}
								/>
								{error && (
									<p className="help is-danger">{error}</p>
								)}
							</div>
							<div className="control">
								<button
									className="button is-primary"
									disabled={loading}
									onClick={reSyncStatus}
								>
									{__('Re-sync', 'cf-images')}
								</button>
							</div>
						</div>
						{!loading && !path && (
							<a
								className="button is-link is-outlined is-small"
								href="https://getfuzion.io/cloudflare"
								rel="noopener noreferrer"
								target="_blank"
							>
								<span>
									{__('Setup Cloudflare worker', 'cf-images')}
								</span>
								<span className="icon">
									<Icon path={mdiOpenInNew} size={0.6} />
								</span>
							</a>
						)}
					</>
				)}
			</div>
		</Card>
	);
};

export default CustomPath;
