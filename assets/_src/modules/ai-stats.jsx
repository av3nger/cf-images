/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import { mdiChartBar } from '@mdi/js';

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
import { formatBytes } from '../js/helpers/format';

const CompressionStats = () => {
	const [ action, setAction ] = useState( '' );
	const { inProgress, setInProgress, stats } = useContext( SettingsContext );

	const runAction = ( e, actionName ) => {
		e.preventDefault();
		setAction( actionName );
		setInProgress( true );
	};

	const getFooter = () => {
		return (
			<div className="card-footer mt-auto">
				<a
					className="card-footer-item"
					href="#"
					onClick={ ( e ) => runAction( e, 'compress' ) }
				>
					{ __( 'Bulk compress', 'cf-images' ) }
				</a>
			</div>
		);
	};

	return (
		<Card
			icon={ mdiChartBar }
			title={ __( 'Info & stats', 'cf-images' ) }
			footer={ getFooter() }
		>
			<div className="content">
				<div className="level">
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">{ __( 'Saved', 'cf-images' ) }</p>
							<p className="title">{ formatBytes( ( stats.size_before ?? 0 ) - ( stats.size_after ?? 0 ) ) }</p>
						</div>
					</div>
				</div>

				{ inProgress && <ProgressBar action={ action } /> }
			</div>
		</Card>
	);
};

export default CompressionStats;
