/**
 * External dependencies
 */
import { useContext } from 'react';
import Icon from '@mdi/react';
import { mdiInformationOutline } from '@mdi/js';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import CloudflareLogin from './login';
import Card from '../../components/card';

/**
 * Cloudflare Images integration settings routes.
 *
 * @class
 */
const Integrations = () => {
	const { cfConnected, integrations, setIntegration } =
		useContext(SettingsContext);

	if (!cfConnected) {
		return <CloudflareLogin />;
	}

	const modules = Object.entries(integrations).map(
		([module, integration]) => {
			const options = Object.entries(integration.options).map(
				([, option]: [string, IntegrationOption]) => {
					return (
						<div className="field" key={option.name}>
							<input
								checked={!!option.value}
								className="switch is-rtl is-rounded is-small"
								id={`cf-images-${option.name}`}
								name={`cf-images-${option.name}`}
								onChange={(e) =>
									setIntegration(
										module,
										option.name,
										e.target.checked
									)
								}
								type="checkbox"
							/>
							<label htmlFor={`cf-images-${option.name}`}>
								{option.label}
								<span
									className="icon is-small ml-2 has-tooltip-arrow has-tooltip-multiline"
									data-tooltip={option.description}
								>
									<Icon
										path={mdiInformationOutline}
										size={1}
									/>
								</span>
							</label>
						</div>
					);
				}
			);

			return (
				<Card title={integration.name} key={module}>
					<div className="content">{options}</div>
				</Card>
			);
		}
	);

	return <div className="columns is-multiline">{modules}</div>;
};

export default Integrations;
