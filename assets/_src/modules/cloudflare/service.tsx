/**
 * External dependencies
 */
import { useState } from 'react';
import * as classNames from 'classnames';
import { mdiWrenchCogOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../../components/card';
import { post } from '../../js/helpers/post';

const Service = () => {
	const [done, setDone] = useState(false);
	const [error, setError] = useState('');
	const [loading, setLoading] = useState(false);

	const resetIgnoreMeta = () => {
		setLoading(true);

		post('cf_images_reset_ignored')
			.then((response: ApiResponse) => {
				if (!response.success && response.data) {
					setError(response.data);
					setTimeout(() => setError(''), 10000);
				} else {
					setDone(true);
					setTimeout(() => setDone(false), 2000);
				}
			})
			.catch(window.console.log)
			.finally(() => setLoading(false));
	};

	return (
		<Card
			icon={mdiWrenchCogOutline}
			title={__('Service tools', 'cf-images')}
		>
			<div className="content">
				{done && (
					<div className="notification is-success">
						<p>{__('Success', 'cf-images')}</p>
					</div>
				)}

				{error && (
					<div className="notification is-warning">
						<p>{error}</p>
					</div>
				)}

				<p>
					{__(
						'Remove "ignore" flag from all images. Will allow to re-run bulk offload on all skipped images.',
						'cf-images'
					)}
				</p>

				<button
					className={classNames('button is-small', {
						'is-loading': loading,
					})}
					onClick={resetIgnoreMeta}
				>
					{__('Reset ignored images', 'cf-images')}
				</button>
			</div>
		</Card>
	);
};

export default Service;
