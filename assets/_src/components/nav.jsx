/**
 * External dependencies
 */
import { NavLink } from 'react-router-dom';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const Nav = () => {
	const getClass = ( status ) => {
		return status ? 'is-active' : '';
	};

	return (
		<aside className="menu">
			<h1 className="is-size-4 pb-3">
				{ __( 'Offload. Store. Resize.', 'cf-images' ) }<br />
				{ __( 'Image Optimize', 'cf-images' ) }
			</h1>

			<p className="menu-label">
				{ __( 'Cloudflare Images', 'cf-images' ) }
			</p>

			<ul className="menu-list">
				<li>
					<NavLink to="/" className={ ( { isActive } ) => getClass( isActive ) }>
						{ __( 'Settings', 'cf-images' ) }
					</NavLink>
				</li>
				<li>
					<NavLink to="/main/experimental" className={ ( { isActive } ) => getClass( isActive ) }>
						{ __( 'Experimental', 'cf-images' ) }
					</NavLink>
				</li>
			</ul>

			<p className="menu-label">
				{ __( 'Image Optimization', 'cf-images' ) }
			</p>

			<ul className="menu-list">
				<li>
					<NavLink to="/image/settings" className={ ( { isActive } ) => getClass( isActive ) }>
						{ __( 'Settings', 'cf-images' ) }
					</NavLink>
				</li>
			</ul>

			<p className="menu-label">
				{ __( 'Misc', 'cf-images' ) }
			</p>

			<ul className="menu-list">
				<li>
					<NavLink to="/misc/support" className={ ( { isActive } ) => getClass( isActive ) }>
						{ __( 'Support', 'cf-images' ) }
					</NavLink>
				</li>
			</ul>
		</aside>
	);
};

export default Nav;
