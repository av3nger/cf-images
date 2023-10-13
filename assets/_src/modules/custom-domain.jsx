/**
 * External dependencies
 */
import { mdiLinkVariant } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';

const CustomDomain = () => {
	return (
		<Card
			icon={ mdiLinkVariant }
			id="custom-domain"
			title={ __( 'Serve from custom domain', 'cf-images' ) }
		>
			<div className="content">
				<p>{ __( 'Use the current site domain instead of `imagedelivery.net`, or specify a custom domain.', 'cf-images' ) }</p>
				<p>{ __( 'Note: The domain must be linked with Cloudflare in order to work correctly.', 'cf-images' ) }</p>
			</div>
		</Card>
	);
};

export default CustomDomain;
