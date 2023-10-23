/**
 * External dependencies
 */
import { useContext, useEffect, useState } from 'react';

/**
 * WordPress dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../context/settings';
import { post } from '../js/helpers/post';

const ProgressBar = ( { action } ) => {
	const [ progress, setProgress ] = useState( 0 );
	const [ currentStep, setCurrentStep ] = useState( 0 );
	const [ totalSteps, setTotalSteps ] = useState( 0 );
	const [ error, setError ] = useState( '' );
	const [ success, setSuccess ] = useState( false );
	const { inProgress, setInProgress, setStats } = useContext( SettingsContext );

	useEffect( () => {
		const resetProgressBar = () => {
			setProgress( 0 );
			setCurrentStep( 0 );
			setTotalSteps( 0 );
		};

		const runProgressBar = () => {
			if ( ! inProgress || error || success ) {
				return;
			}

			post( 'cf_images_bulk_process', { currentStep, totalSteps, action } )
				.then( ( response ) => {
					if ( ! response.success || response.data.hasOwnProperty( 'error' ) ) {
						setError( response.data?.error ?? response.data );
						resetProgressBar();
						return;
					}

					const { step, total, stats } = response.data;
					setProgress( Math.round( ( 100 / total ) * step ) );
					setStats( stats );

					if ( step < total ) {
						setCurrentStep( step );
						setTotalSteps( total );
					} else {
						setSuccess( true );
						resetProgressBar();
					}
				} )
				.catch( window.console.log );
		};

		runProgressBar();
	}, [ action, currentStep ] );

	useEffect( () => {
		if ( error || success ) {
			setTimeout( () => {
				setError( '' );
				setSuccess( false );
				setInProgress( false );
			}, 5000 );
		}
	}, [ error, success ] );

	if ( error ) {
		return <div className="notification is-warning">{ error }</div>;
	}

	if ( success ) {
		return <div className="notification is-success">{ __( 'All images processed', 'cf-images' ) }</div>;
	}

	if ( ! inProgress ) {
		return null;
	}

	return (
		<div className="has-text-centered">
			<progress className="progress is-info mb-1" value={ progress } max="100">{ progress }%</progress>
			<small>
				{ ( currentStep === totalSteps && totalSteps === 0 )
					? __( 'Processing first image...', 'cf-images' )
					: sprintf( /* translators: %d - current step, %d - total steps */
						__( 'Processing %1$d of %2$d images...', 'cf-images' ),
						currentStep + 1,
						totalSteps
					)
				}
			</small>
		</div>
	);
};

export default ProgressBar;
