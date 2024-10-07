/**
 * External dependencies
 */
import { ReactElement, useContext } from 'react';
import * as classNames from 'classnames';
import Icon from '@mdi/react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../context/settings';

type CardProps = {
	children: ReactElement;
	footer?: ReactElement;
	icon?: string;
	id?: string;
	title: string;
	wide?: boolean;
};

/**
 * Card component.
 *
 * @param {Object}       props
 * @param {ReactElement} props.children
 * @param {ReactElement} props.footer
 * @param {string}       props.icon
 * @param {string}       props.id
 * @param {string}       props.title
 * @param {boolean}      props.wide
 * @class
 */
const Card = ({
	children,
	footer,
	icon = '',
	id,
	title,
	wide = false,
}: CardProps): ReactElement => {
	const { modules, setModule } = useContext(SettingsContext);

	return (
		<div
			className={classNames('column', {
				'is-half-tablet is-one-third-fullhd': !wide,
				'is-full-tablet is-two-thirds-fullhd': wide,
			})}
		>
			<div className="card is-flex is-flex-direction-column">
				<div
					className={classNames('card-content', {
						'has-text-grey-light is-unselectable':
							id && !modules[id],
					})}
				>
					<div className="media is-align-content-center is-align-items-center">
						{icon && (
							<div className="media-left">
								<Icon path={icon} size={2} />
							</div>
						)}
						<div className="media-content">
							<p
								className={classNames('title is-4', {
									'has-text-grey-light': id && !modules[id],
								})}
							>
								{title}
							</p>
						</div>
					</div>

					{children}
				</div>
				{id && (
					<div className="card-footer mt-auto">
						<div className="field card-footer-item py-2">
							<input
								checked={modules[id]}
								className="switch is-rtl is-rounded"
								id={`cf-images-${id}`}
								name={`cf-images-${id}`}
								onChange={(e) =>
									setModule(id, e.target.checked)
								}
								type="checkbox"
							/>
							<label htmlFor={`cf-images-${id}`}>
								{__('Enable feature', 'cf-images')}
							</label>
						</div>
					</div>
				)}
				{footer}
			</div>
		</div>
	);
};

export default Card;
