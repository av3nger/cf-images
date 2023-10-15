/* global CFImages */

/**
 * External dependencies
 */
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { post } from '../js/helpers/post';
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
	const { cfStatus, fuzion, hideSidebar, settings } = CFImages;
	const [ stats, setStats ] = useState( CFImages.stats );
	const [ modules, setModules ] = useState( settings );
	const [ noticeHidden, hideNotice ] = useState( hideSidebar );
	const [ hasFuzion, setFuzion ] = useState( fuzion );
	const [ cfConnected, setCfConnected ] = useState( cfStatus );
	const [ inProgress, setInProgress ] = useState( false );

	const setModule = ( module, value ) => {
		const newSettings = { ...modules };
		newSettings[ module ] = value;

		post( 'cf_images_update_settings', newSettings )
			.then( () => setModules( newSettings ) )
			.catch( window.console.log );
	};

	return (
		<SettingsContext.Provider value={ {
			modules, setModule,
			noticeHidden, hideNotice,
			hasFuzion, setFuzion,
			cfConnected, setCfConnected,
			inProgress, setInProgress,
			stats, setStats
		} }>
			{ children }
		</SettingsContext.Provider>
	);
};

export default SettingsProvider;
