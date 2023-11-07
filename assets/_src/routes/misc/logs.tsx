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
import * as classNames from 'classnames';

/**
 * Cloudflare Images experimental settings routes.
 *
 * @class
 */
const Logs = () => {
	const [error, setError] = useState('');
	const [logs, setLogs] = useState('');
	const [loading, setLoading] = useState(false);

	const resetState = () => {
		setLoading(true);
		setLogs('');
		setError('');
	};

	const getLogs = () => {
		resetState();
		post('cf_images_get_logs')
			.then((response: ApiResponse) => {
				if (!response.success && response.data) {
					setError(response.data);
					return;
				}

				setLogs(response.data);
			})
			.catch(window.console.log)
			.finally(() => setLoading(false));
	};

	const clearLogs = () => {
		resetState();
		post('cf_images_clear_logs')
			.catch(window.console.log)
			.finally(() => setLoading(false));
	};

	useEffect(() => {
		getLogs();
	}, []);

	return (
		<section className="card">
			<div className="card-content">
				<div className="content">
					<div className="buttons are-small is-pulled-right">
						<button
							className={classNames('button', {
								'is-loading': loading,
							})}
							onClick={() => getLogs()}
						>
							{__('Refresh', 'cf-images')}
						</button>
						<button
							className={classNames('button', {
								'is-loading': loading,
							})}
							onClick={() => clearLogs()}
						>
							{__('Clear logs', 'cf-images')}
						</button>
					</div>

					<p className="title is-4">{__('Logs', 'cf-images')}</p>

					<div className="is-clearfix" />

					{error && (
						<div className="notification is-warning">
							<p>{error}</p>
						</div>
					)}

					{!loading && (
						<pre className="cf-images-logs">
							{logs ? logs : __('Log is empty', 'cf-images')}
						</pre>
					)}
				</div>
			</div>
		</section>
	);
};

export default Logs;
