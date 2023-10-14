/* global CFImages */

/**
 * External dependencies
 */
import { mdiChartBar } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';

const CloudflareStats = () => {
	const { stats } = CFImages;

	return (
		<Card
			icon={ mdiChartBar }
			title={ __( 'Info & stats', 'cf-images' ) }
		>
			<div className="content">
				<nav className="level">
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">{ __( 'Images offloaded', 'cf-images' ) }</p>
							<p className="title">{ stats.synced ?? 0 }</p>
						</div>
					</div>
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">{ __( 'Images on Cloudflare', 'cf-images' ) }</p>
							<p className="title">{ stats.api_current ?? stats.synced }</p>
						</div>
					</div>
				</nav>
			</div>
		</Card>
	);
};

export default CloudflareStats;
