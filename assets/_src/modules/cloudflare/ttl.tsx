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
import Card from '../../components/card';
import SettingsContext from '../../context/settings';
import { useApiSave } from '../../hooks/useApiSave';

const BrowserTTL = () => {
	const { browserTTL } = useContext(SettingsContext);

	const [ttl, setTTL] = useState(parseInt(browserTTL));
	const { saving, done, error, execute } = useApiSave();

	const saveTTL = () => {
		execute('cf_images_set_ttl', { ttl });
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
									value="7884000"
									selected={7884000 === ttl}
								>
									{__('3 month', 'cf-images')}
								</option>
								<option
									value="15768000"
									selected={15768000 === ttl}
								>
									{__('6 month', 'cf-images')}
								</option>
								<option
									value="23652000"
									selected={23652000 === ttl}
								>
									{__('9 month', 'cf-images')}
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
