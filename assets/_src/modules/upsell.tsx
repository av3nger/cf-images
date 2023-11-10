/**
 * External dependencies
 */
import { mdiRocketLaunchOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';

const UpsellModule = () => {
	return (
		<Card
			icon={mdiRocketLaunchOutline}
			title={__('Need more AI credits?', 'cf-images')}
		>
			<div className="content">
				{__(
					'Increase the number of images you can process or generate with AI + get access to additional features.',
					'cf-images'
				)}
				&nbsp;
				<a
					href="https://getfuzion.io/price"
					target="_blank"
					rel="noopener noreferrer"
				>
					{__('Learn more', 'cf-images')}
				</a>
			</div>
		</Card>
	);
};

export default UpsellModule;
