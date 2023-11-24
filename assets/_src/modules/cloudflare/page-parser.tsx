/**
 * External dependencies
 */
import { useContext } from 'react';
import { mdiImageSearch } from '@mdi/js';

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
	const submoduleId = 'auto-resize';

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

				<p className="is-size-5 mb-2">
					{__('Auto resize images on front-end', 'cf-images')}
				</p>
				<p>
					{__(
						'Make images responsive by adding missing image sizes to the srcset attribute.',
						'cf-images'
					)}
				</p>

				<div className="field">
					<input
						checked={
							!!(submoduleId in modules && modules[submoduleId])
						}
						className="switch is-rtl is-rounded is-small"
						disabled={!(moduleId in modules) || !modules[moduleId]}
						id={`cf-images-${submoduleId}`}
						name={`cf-images-${submoduleId}`}
						onChange={(e) =>
							setModule(submoduleId, e.target.checked)
						}
						type="checkbox"
					/>
					<label htmlFor={`cf-images-${submoduleId}`}>
						{__('Enable feature', 'cf-images')}
					</label>
				</div>
			</div>
		</Card>
	);
};

export default PageParser;
