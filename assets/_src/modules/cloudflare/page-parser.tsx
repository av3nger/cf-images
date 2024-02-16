/**
 * External dependencies
 */
import { useContext } from 'react';
import Icon from '@mdi/react';
import { mdiImageSearch, mdiInformationOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../../components/card';
import SettingsContext from '../../context/settings';

const PageParser = () => {
	const { modules, setModule } = useContext(SettingsContext);

	const moduleId = 'page-parser';
	const subModules = {
		'auto-resize': {
			label: __('Auto resize images on front-end', 'cf-images'),
			description: __(
				'Make images responsive by adding missing image sizes to the srcset attribute.',
				'cf-images'
			),
		},
		'smallest-size': {
			label: __('Use img width size', 'cf-images'),
			description: __(
				'If the image DOM element has a width/height attribute set, use the value for the image size. Fixes issues when an incorrect attachment size is selected in the editor.',
				'cf-images'
			),
		},
		'auto-crop': {
			label: __('Auto crop', 'cf-images'),
			description: __(
				'If the image height matches the image width, try to auto crop the image.',
				'cf-images'
			),
		},
	};

	const subOptions = Object.entries(subModules).map((module) => {
		const { label, description } = module[1];
		return (
			<div className="field" key={module[0]}>
				<input
					checked={!!(module[0] in modules && modules[module[0]])}
					className="switch is-rtl is-rounded is-small"
					disabled={!(moduleId in modules) || !modules[moduleId]}
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
		<Card
			icon={mdiImageSearch}
			id="page-parser"
			title={__('Parse page for images', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Compatibility module to support themes that do not use WordPress hooks and filters. If images are not replaced on the site, try enabling this module.',
						'cf-images'
					)}
				</p>

				{subOptions}
			</div>
		</Card>
	);
};

export default PageParser;
