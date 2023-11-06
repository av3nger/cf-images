/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { post } from '../../js/helpers/post';

/**
 * Cloudflare Images experimental settings routes.
 *
 * @class
 */
const Logs = () => {
	const [error, setError] = useState('');
	const [logs, setLogs] = useState('');

	useEffect(() => {
		post('cf_images_get_logs')
			.then((response: ApiResponse) => {
				if (!response.success && response.data) {
					setError(response.data);
					return;
				}

				setLogs(response.data);
			})
			.catch(window.console.log);
	}, []);

	return (
		<section className="card">
			<div className="card-content">
				<div className="content">
					<p className="title is-4">{__('Logs', 'cf-images')}</p>

					{error && (
						<div className="notification is-warning">
							<p>{error}</p>
						</div>
					)}

					{logs && <pre className="cf-images-logs">{logs}</pre>}
				</div>
			</div>
		</section>
	);
};

export default Logs;
