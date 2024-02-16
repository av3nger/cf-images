/**
 * External dependencies
 */
import { mdiRss } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../../components/card';

const RSSFeeds = () => {
	return (
		<Card icon={mdiRss} id="rss-feeds" title={__('RSS Feeds', 'cf-images')}>
			<div className="content">
				<p>{__('Replace images inside RSS feeds.', 'cf-images')}</p>
			</div>
		</Card>
	);
};

export default RSSFeeds;
