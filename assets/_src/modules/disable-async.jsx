/**
 * External dependencies
 */
import { mdiSyncOff } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';

const DisableAsync = () => {
	return (
		<Card
			icon={ mdiSyncOff }
			id="disable-async"
			title={ __( 'Disable async processing', 'cf-images' ) }
		>
			<div className="content">
				<p>{ __( 'By default, the plugin will try to offload images in asynchronous mode, meaning that the processing will be done in the background. If, for some reason, the host does not allow async processing, disable this option for backward compatibility.', 'cf-images' ) }</p>
				<p>{ __( 'Note: disabling this option will increase the time to upload new images to the media library.', 'cf-images' ) }</p>
			</div>
		</Card>
	);
};

export default DisableAsync;
