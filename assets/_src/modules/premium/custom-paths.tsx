/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import * as classNames from 'classnames';
import Icon from '@mdi/react';
import { mdiCheck, mdiAlphabeticalVariant } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../../components/card';
import SettingsContext from '../../context/settings';

const CustomPaths = () => {
	const [done, setDone] = useState(false);
	const [error, setError] = useState('');
	const [saving, setSaving] = useState(false);

	const { modules } = useContext(SettingsContext);

	let domain = '';
	if (window.CFImages.domain) {
		domain = window.CFImages.domain;
		if (!domain.endsWith('/')) {
			domain += '/';
		}
		domain += 'cdn-cgi/imagedelivery/<account_hash>/<image>';
	} else {
		domain = 'https://imagedelivery.net/<account_hash>/<image>';
	}

	/*
	let domain = window.CFImages.domain
		? window.CFImages.domain
		: 'https://imagedelivery.net/';

	if (domain.endsWith('/')) {
		domain += 'cdn-cgi/imagedelivery/';
	}
	*/
	return (
		<Card
			icon={mdiAlphabeticalVariant}
			id="custom-domain"
			title={__('Custom image URLs', 'cf-images')}
			wide
		>
			<div className="content">
				<p>
					Current format:&nbsp;
					{domain}
				</p>
				<p>
					{__(
						'Use a custom string instead of the default `/cdn-cgi/imagedelivery/<account_hash>/` in the image URL.',
						'cf-images'
					)}
				</p>

				{'custom-domain' in modules && modules['custom-domain'] && (
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
								onChange={(e) => console.log(e.target.value)}
								placeholder="https://cdn.example.com"
								type="text"
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

export default CustomPaths;
