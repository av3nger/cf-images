/* global CFImages */

/**
 * External dependencies
 */
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { post } from '../utils/helpers';
import SettingsContext from './settings';

/**
 * Settings provider
 *
 * @param {Object} props
 * @param {Object} props.children
 * @return {JSX.Element} Settings context provider.
 * @class
 */
const SettingsProvider = ( { children } ) => {
	const [ modules, setModules ] = useState( CFImages.settings );

	const setModule = ( module, value ) => {
		const newSettings = { ...modules };
		console.log( newSettings, module, value );
		newSettings[ module ] = value;
		console.log( newSettings );

		post( 'cf_images_update_settings', { data: newSettings } )
			.then( () => setModules( newSettings ) )
			.catch( window.console.log );
	};

	return (
		<SettingsContext.Provider value={ { modules, setModule } }>
			{ children }
		</SettingsContext.Provider>
	);
};

export default SettingsProvider;
