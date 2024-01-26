/**
 * External dependencies
 */
import { MouseEvent, useContext } from 'react';
import { mdiImageMultipleOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../context/settings';
import Card from '../components/card';
import ProgressBar from '../components/progress';

const FullOffload = () => {
	const { inProgress, setInProgress } = useContext(SettingsContext);

	return (
		<Card
			icon={mdiImageMultipleOutline}
			id="full-offload"
			title={__('Full offload', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Setting this option will allow removing original images from the media library.',
						'cf-images'
					)}
				</p>
				<p>
					{__(
						'By enabling this feature, you understand the potential risks of removing media files from the media library. Please ensure you have a backup of your media library.',
						'cf-images'
					)}
				</p>

				{inProgress && <ProgressBar action="full-remove" />}

				<button
					className="button is-small"
					onClick={(e) => setInProgress(true)}
					disabled={inProgress}
				>
					{__('Bulk delete', 'cf-images')}
				</button>
			</div>
		</Card>
	);
};

export default FullOffload;
