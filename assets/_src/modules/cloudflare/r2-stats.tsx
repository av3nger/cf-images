/**
 * External dependencies
 */
import { MouseEvent, useContext, useState } from 'react';
import { mdiChartBar } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import Card from '../../components/card';
import ProgressBar from '../../components/progress';

const R2Stats = () => {
	const [action, setAction] = useState('');
	const { inProgress, setInProgress, stats } = useContext(SettingsContext);

	const runAction = (e: MouseEvent, actionName: string) => {
		e.preventDefault();
		setAction(actionName);
		setInProgress(true);
	};

	const getFooter = () => {
		return (
			<div className="card-footer mt-auto">
				<p className="card-footer-item">
					<button
						className="button is-fullwidth is-small is-ghost"
						onClick={(e) => runAction(e, 'remove')}
					>
						{__('Bulk remove', 'cf-images')}
					</button>
				</p>
				<p className="card-footer-item">
					<button
						className="button is-fullwidth is-small is-ghost"
						onClick={(e) => runAction(e, 'r2-upload')}
					>
						{__('Bulk offload', 'cf-images')}
					</button>
				</p>
			</div>
		);
	};

	return (
		<Card
			icon={mdiChartBar}
			title={__('Info & stats', 'cf-images')}
			footer={getFooter()}
		>
			<div className="content">
				<div className="level">
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">
								{__('Images offloaded', 'cf-images')}
							</p>
							<p className="title">{stats.r2_count ?? 0}</p>
						</div>
					</div>
				</div>

				{inProgress && <ProgressBar action={action} />}
			</div>
		</Card>
	);
};

export default R2Stats;
