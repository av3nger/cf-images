/**
 * External dependencies
 */
import { useContext } from 'react';
import Icon from '@mdi/react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../context/settings';

/**
 * Card component.
 *
 * @param {Object} props
 * @param {Object} props.children
 * @param {string} props.icon
 * @param {string} props.id
 * @param {string} props.title
 * @return {JSX.Element} Card component.
 * @class
 */
const Card = ( { children, icon, id, title } ) => {
	const { modules, setModule } = useContext( SettingsContext );

	return (
		<div className="column is-half-tablet is-one-third-desktop">
			<div className="card is-flex is-flex-direction-column">
				<div className="card-content">
					<div className="media is-align-content-center is-align-items-center">
						<div className="media-left">
							<Icon path={ icon } size={ 2 } />
						</div>
						<div className="media-content">
							<p className="title is-4">{ title }</p>
						</div>
					</div>

					{ children }
				</div>
				<div className="card-footer mt-auto">
					<div className="field card-footer-item">
						<input
							checked={ modules[ id ] }
							className="switch is-rtl is-rounded"
							id={ `cf-images-${ id }` }
							name={ `cf-images-${ id }` }
							onChange={ ( e ) => setModule( id, e.target.checked ) }
							type="checkbox"
						/>
						<label htmlFor={ `cf-images-${ id }` }>
							{ __( 'Enable feature', 'cf-images' ) }
						</label>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Card;
