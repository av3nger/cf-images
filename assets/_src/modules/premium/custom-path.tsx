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
import { post } from '../../js/helpers/post';

const CustomPath = () => {
	const { customPath, domain, modules } = useContext(SettingsContext);

	const [done, setDone] = useState(false);
	const [saving, setSaving] = useState(false);
	const [path, setPath] = useState(customPath);

	const savePath = () => {
		setSaving(true);

		post('cf_images_set_custom_path', { path })
			.then(() => {
				setSaving(false);
				setDone(true);
				setTimeout(() => setDone(false), 2000);
			})
			.catch(window.console.log);
	};

	if (('custom-domain' in modules && !modules['custom-domain']) || !domain) {
		return (
			<Card
				icon={mdiAlphabeticalVariant}
				title={__('Custom image URLs', 'cf-images')}
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
			title={__('Custom image URLs', 'cf-images')}
			wide
		>
			<div className="content">
				<p>
					{__('Format', 'cf-images')}:&nbsp;
					{format}
				</p>
				<p>
					{__(
						'Use a custom string instead of the default `cdn-cgi/imagedelivery/<account_hash>` in the image URL.',
						'cf-images'
					)}
				</p>

				{'custom-path' in modules && modules['custom-path'] && (
					<div className="field has-addons">
						<div
							className={classNames('control is-expanded', {
								'has-icons-right': done,
							})}
						>
							<label
								htmlFor="custom-path"
								className="screen-reader-text"
							>
								{__('Set custom domain', 'cf-images')}
							</label>
							<input
								className={classNames('input is-fullwidth', {
									'is-success': done,
								})}
								id="custom-path"
								onChange={(e) => setPath(e.target.value)}
								placeholder="cf-images"
								type="text"
								value={path}
							/>
							{done && (
								<span className="icon is-small is-right">
									<Icon path={mdiCheck} size={1} />
								</span>
							)}
						</div>
						<div className="control">
							<button
								className={classNames('button is-info', {
									'is-loading': saving,
								})}
								onClick={savePath}
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

export default CustomPath;
