/**
 * External dependencies
 */
import { useContext } from 'react';
import { mdiAutoUpload } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import Card from '../../components/card';

const AutoOffload = () => {
	const { modules, setModule } = useContext(SettingsContext);

	const moduleId = 'auto-offload';
	const submoduleId = 'offload-rest-api';

	return (
		<Card
			icon={mdiAutoUpload}
			id={moduleId}
			title={__('Auto offload new images', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Enable this option if you want to enable automatic offloading for newly uploaded images.',
						'cf-images'
					)}
				</p>

				<p className="is-size-5 mb-2">
					{__('REST API integration', 'cf-images')}
				</p>
				<p>
					{__(
						'Intercept image uploads via the REST API and automatically offload them to Cloudflare.',
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

export default AutoOffload;
