/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Cloudflare Images experimental settings routes.
 *
 * @class
 */
const Logs = () => {
	return (
		<section className="card">
			<div className="card-content">
				<div className="content">
					<p className="title is-4">{__('Logs', 'cf-images')}</p>

					<pre>
						{__(
							'Below is a list of links to resources that will help you get started or get additional help:',
							'cf-images'
						)}
					</pre>
				</div>
			</div>
		</section>
	);
};

export default Logs;
