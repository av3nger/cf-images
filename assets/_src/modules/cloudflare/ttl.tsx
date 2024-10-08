/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import * as classNames from 'classnames';
import { mdiCached } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { post } from '../../js/helpers/post';
import Card from '../../components/card';
import SettingsContext from '../../context/settings';

const BrowserTTL = () => {
	const { browserTTL } = useContext(SettingsContext);

	const [ttl, setTTL] = useState(parseInt(browserTTL));
	const [done, setDone] = useState(false);
	const [error, setError] = useState('');
	const [saving, setSaving] = useState(false);

	const saveTTL = () => {
		setError('');
		setSaving(true);

		post('cf_images_set_ttl', { ttl })
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
		<Card icon={mdiCached} title={__('Browser TTL', 'cf-images')}>
			<div className="content">
				<p>
					{__(
						'Browser TTL controls how long an image stays in a browser’s cache and specifically configures the cache-control response header',
						'cf-images'
					)}
				</p>

				<div className="field has-addons">
					<div className="control is-expanded">
						<div
							className={classNames('select is-fullwidth', {
								'is-danger': error,
								'is-success': done,
							})}
						>
							<label
								htmlFor="browser-ttl"
								className="screen-reader-text"
							>
								{__('Set browser TTL', 'cf-images')}
							</label>
							<select
								id="browser-ttl"
								onChange={(e) =>
									setTTL(parseInt(e.target.value))
								}
							>
								<option
									value="172800"
									selected={172800 === ttl}
								>
									{__('2 days', 'cf-images')}
								</option>
								<option
									value="604800"
									selected={604800 === ttl}
								>
									{__('1 week', 'cf-images')}
								</option>
								<option
									value="2628000"
									selected={2628000 === ttl}
								>
									{__('1 month', 'cf-images')}
								</option>
								<option
									value="31536000"
									selected={31536000 === ttl}
								>
									{__('1 year', 'cf-images')}
								</option>
							</select>
							{error && <p className="help is-danger">{error}</p>}
						</div>
					</div>
					<div className="control">
						<button
							className={classNames('button is-primary', {
								'is-loading': saving,
							})}
							onClick={saveTTL}
						>
							{__('Set', 'cf-images')}
						</button>
					</div>
				</div>
			</div>
		</Card>
	);
};

export default BrowserTTL;
