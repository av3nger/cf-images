/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const Nav = () => {
	return (
		<aside className="menu">
			<h1 className="is-size-4 pb-3">
				{ __( 'Offload. Store. Resize.', 'cf-images' ) }<br />
				{ __( 'Image Optimize', 'cf-images' ) }
			</h1>
			<p className="menu-label">
				Cloudflare Images
			</p>
			<ul className="menu-list">
				<li><a className="is-active">Settings</a></li>
				<li><a>Experimental</a></li>
			</ul>
			<p className="menu-label">
				Image Optimization
			</p>
			<ul className="menu-list">
				<li><a>Settings</a></li>
			</ul>
			<p className="menu-label">
				Pro Features
			</p>
			<ul className="menu-list">
				<li><a>Payments</a></li>
				<li><a>Transfers</a></li>
				<li><a>Balance</a></li>
			</ul>
		</aside>
	);
};

export default Nav;
