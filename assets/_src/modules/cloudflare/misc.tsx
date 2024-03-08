/**
 * External dependencies
 */
import { useContext } from 'react';
import Icon from '@mdi/react';
import { mdiInformationOutline, mdiCogs } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../../components/card';
import SettingsContext from '../../context/settings';

const MiscOptions = () => {
	const { modules, setModule } = useContext(SettingsContext);

	const subModules = {
		'rss-feeds': {
			label: __('RSS Feeds', 'cf-images'),
			description: __('Replace images inside RSS feeds.', 'cf-images'),
		},
		'no-offload-user': {
			label: __('Skip logged-in', 'cf-images'),
			description: __(
				'Serve original (non-offloaded) images for logged-in users.',
				'cf-images'
			),
		},
	};

	const options = Object.entries(subModules).map((module) => {
		const { label, description } = module[1];
		return (
			<div className="field" key={module[0]}>
				<input
					checked={!!(module[0] in modules && modules[module[0]])}
					className="switch is-rtl is-rounded is-small"
					id={`cf-images-${module[0]}`}
					name={`cf-images-${module[0]}`}
					onChange={(e) => setModule(module[0], e.target.checked)}
					type="checkbox"
				/>
				<label htmlFor={`cf-images-${module[0]}`}>
					{label}
					<span
						className="icon is-small ml-2 has-tooltip-arrow has-tooltip-multiline"
						data-tooltip={description}
					>
						<Icon path={mdiInformationOutline} size={1} />
					</span>
				</label>
			</div>
		);
	});

	return (
		<Card icon={mdiCogs} title={__('Misc Options', 'cf-images')}>
			<div className="content">{options}</div>
		</Card>
	);
};

export default MiscOptions;
