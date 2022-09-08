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

$stats = get_option( 'cf-images-stats', array( 'synced' => 0 ) );

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Offload Images to Cloudflare', 'cf-images' ); ?></h1>

	<article>
		<header>
			<h3><?php esc_html_e( 'Settings', 'cf-images' ); ?></h3>
		</header>

		<form id="cf-images-form" data-type="settings">
			<span class="dashicons dashicons-admin-links"></span>
			<label for="custom_domain">
				<?php esc_html_e( 'Serve from custom domain', 'cf-images' ); ?>
			</label>
			<div>
				<input name="custom-domain" type="checkbox" id="custom_domain" value="1" <?php checked( get_option( 'cf-images-custom-domain', false ) ); ?> role="switch">
				<p>
					<?php esc_html_e( 'Use the current site domain instead of `imagedelivery.net`.', 'cf-images' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Note: Image delivery is supported from all customer domains under the same Cloudflare account.', 'cf-images' ); ?>
				</p>
			</div>

			<hr>

			<span class="dashicons dashicons-images-alt2"></span>
			<label for="disable_sizes">
				<?php esc_html_e( 'Disable WordPress image sizes', 'cf-images' ); ?>
			</label>
			<div>
				<input name="disable-sizes" type="checkbox" id="disable_sizes" value="1" <?php checked( get_option( 'cf-images-disable-generation', false ) ); ?> role="switch">
				<p>
					<?php esc_html_e( 'Setting this option will disable generation of `-scaled` images and other image sizes. Only the original image will be stored in the media library. Only for newly uploaded files, current images will not be affected.', 'cf-images' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Note: This feature is experimental. All the image sizes can be restored with the `Regenerate Thumbnails` plugin.', 'cf-images' ); ?>
				</p>
			</div>

			<hr>

			<span class="dashicons dashicons-cloud-upload"></span>
			<label for="cf-images-upload-all">
				<?php esc_html_e( 'Bulk upload images', 'cf-images' ); ?>
			</label>
			<div>
				<a href="#" role="button" class="outline" id="cf-images-upload-all">
					<?php esc_html_e( 'Upload', 'cf-images' ); ?>
				</a>

				<div class="cf-images-progress upload">
					<progress value="0" max="100" style="width: 80%"></progress>
					<p><small><?php esc_html_e( 'Initializing...', 'cf-images' ); ?></small></p>
				</div>

				<p>
					<?php esc_html_e( 'You can either manually upload individual images from the media library, or bulk upload/remove all the images using the buttons below.', 'cf-images' ); ?>
				</p>

				<p class="stats">
					<?php
					printf( /* translators: %d - number of offloaded images */
						esc_html__( 'Offloaded images: %d', 'cf-images' ),
						absint( $stats['synced'] )
					);
					?>
				</p>
			</div>

			<hr>

			<span class="dashicons dashicons-trash"></span>
			<label for="cf-images-remove-all">
				<?php esc_html_e( 'Bulk remove', 'cf-images' ); ?>
			</label>
			<div>
				<a href="#" role="button" class="outline cf-images-button-red" id="cf-images-remove-all">
					<?php esc_attr_e( 'Remove', 'cf-images' ); ?>
				</a>

				<div class="cf-images-progress remove">
					<progress value="0" max="100" style="width: 80%"></progress>
					<p><small><?php esc_html_e( 'Initializing...', 'cf-images' ); ?></small></p>
				</div>

				<p>
					<?php esc_html_e( 'Remove all previously uploaded images.', 'cf-images' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Note: If `Disable WordPress image sizes` option has been selected above, you will need to regenerate all the image sizes manually.', 'cf-images' ); ?>
				</p>
			</div>
		</form>

		<footer>
			<a href="#" role="button" aria-busy="false" id="save-settings">
				<?php esc_html_e( 'Save Changes', 'cf-images' ); ?>
			</a>
		</footer>
	</article>
</div>
