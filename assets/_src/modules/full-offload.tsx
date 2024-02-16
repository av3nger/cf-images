/**
 * External dependencies
 */
import { MouseEvent, useContext, useState } from 'react';
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
	const [action, setAction] = useState('');
	const { inProgress, setInProgress } = useContext(SettingsContext);

	const runAction = (e: MouseEvent, actionName: string) => {
		e.preventDefault();
		setAction(actionName);
		setInProgress(true);
	};

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

				{inProgress && <ProgressBar action={action} />}

				<div className="buttons mb-1">
					<button
						className="button is-small"
						onClick={(e) => runAction(e, 'full-remove')}
						disabled={inProgress}
					>
						{__('Bulk delete', 'cf-images')}
					</button>

					<button
						className="button is-small"
						onClick={(e) => runAction(e, 'full-restore')}
						disabled={inProgress}
					>
						{__('Bulk restore', 'cf-images')}
					</button>
				</div>

				<p>
					{__(
						'* Bulk actions will remove/restore physical files from the WordPress media library. Images that have not been offloaded to Cloudflare will be skipped.',
						'cf-images'
					)}
				</p>
			</div>
		</Card>
	);
};

export default FullOffload;
