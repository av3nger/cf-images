/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import * as classNames from 'classnames';
import Icon from '@mdi/react';
import { mdiCheck, mdiLinkVariant } from '@mdi/js';

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

const CustomDomain = () => {
	const [done, setDone] = useState(false);
	const [error, setError] = useState('');
	const [saving, setSaving] = useState(false);

	const { domain, modules, setDomain } = useContext(SettingsContext);

	const moduleId = 'custom-domain';

	const saveDomain = () => {
		setError('');
		setSaving(true);

		post('cf_images_set_custom_domain', { domain })
			.then((response: ApiResponse) => {
				setSaving(false);

				if (!response.success && response.data) {
					setError(response.data);
					setTimeout(() => setError(''), 10000);
				} else {
					setDone(true);
					setTimeout(() => setDone(false), 2000);
				}
			})
			.catch(window.console.log);
	};

	return (
		<Card
			icon={mdiLinkVariant}
			id={moduleId}
			title={__('Serve from custom domain', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Use the current site domain instead of `imagedelivery.net`, or specify a custom domain.',
						'cf-images'
					)}
				</p>
				<p>
					{__(
						'Note: The domain must be linked with Cloudflare in order to work correctly.',
						'cf-images'
					)}
				</p>

				{moduleId in modules && modules[moduleId] && (
					<div className="field has-addons">
						<div
							className={classNames('control is-expanded', {
								'has-icons-right': done,
							})}
						>
							<label
								htmlFor="custom-domain"
								className="screen-reader-text"
							>
								{__('Set custom domain', 'cf-images')}
							</label>
							<input
								className={classNames('input is-fullwidth', {
									'is-danger': error,
									'is-success': done,
								})}
								id="custom-domain"
								onChange={(e) => setDomain(e.target.value)}
								placeholder="https://cdn.example.com"
								type="text"
								value={domain}
							/>
							{done && (
								<span className="icon is-small is-right">
									<Icon path={mdiCheck} size={1} />
								</span>
							)}
							{error && <p className="help is-danger">{error}</p>}
						</div>
						<div className="control">
							<button
								className={classNames('button is-info', {
									'is-loading': saving,
								})}
								onClick={saveDomain}
							>
								{__('Set', 'cf-images')}
							</button>
						</div>
					</div>
				)}
			</div>
		</Card>
	);
};

export default CustomDomain;
