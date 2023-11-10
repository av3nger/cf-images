/**
 * External dependencies
 */
import { MouseEvent, useContext, useState } from 'react';
import * as classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import { post } from '../../js/helpers/post';

const Disconnect = () => {
	const [loading, setLoading] = useState(false);
	const { setCfConnected } = useContext(SettingsContext);

	const disconnect = (e: MouseEvent) => {
		e.preventDefault();
		setLoading(true);

		post('cf_images_disconnect')
			.then(() => setCfConnected(false))
			.catch(window.console.log);
	};

	const checkStatus = (e: MouseEvent) => {
		e.preventDefault();
		setLoading(true);

		post('cf_images_check_status')
			.catch(window.console.log)
			.finally(() => setLoading(false));
	};

	return (
		<div className="column is-full has-text-centered">
			<button
				className={classNames('button is-ghost is-small', {
					'is-loading': loading,
				})}
				onClick={(e) => disconnect(e)}
			>
				{__('Disconnect from API', 'cf-images')}
			</button>
			<button
				className={classNames('button is-ghost is-small', {
					'is-loading': loading,
				})}
				onClick={(e) => checkStatus(e)}
			>
				{__('Re-check API status', 'cf-images')}
			</button>
		</div>
	);
};

export default Disconnect;
