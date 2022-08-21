<?php
/**
 * Settings view
 *
 * Various Cloudflare Images settings.
 *
 * @link https://vcore.ru
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Views
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App\Views;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Offload Images to Cloudflare Settings', 'cf-images' ); ?></h1>

	<h2><?php esc_html_e( 'Image variants', 'cf-images' ); ?></h2>
	<p><?php esc_html_e( 'Syncing up image variants is required to make sure Cloudflare Images have all the WordPress registered sizes.', 'cf-images' ); ?></p>
	<p><?php esc_html_e( 'This action is only required once.', 'cf-images' ); ?></p>

	<input class="button" type="button" value="<?php esc_attr_e( 'Sync image variants', 'cf-images' ); ?>" />
</div>
