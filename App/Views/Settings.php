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
	<h1><?php esc_html_e( 'Offload Images to Cloudflare', 'cf-images' ); ?></h1>

	<h2><?php esc_html_e( 'Settings', 'cf-images' ); ?></h2>

	<form id="cf-images-form" data-type="settings">
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Serve from custom domain', 'cf-images' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Serve images from custom domains', 'cf-images' ); ?></span>
						</legend>
						<label for="custom_domain">
							<input name="custom-domain" type="checkbox" id="custom_domain" value="1" <?php checked( get_option( 'cf-images-custom-domain', false ) ); ?>>
							<?php esc_html_e( 'Enable', 'cf-images' ); ?>
						</label>
					</fieldset>
					<p class="description">
						<?php esc_html_e( 'Use the current site domain instead of `imagedelivery.net`.', 'cf-images' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Note: Image delivery is supported from all customer domains under the same Cloudflare account.', 'cf-images' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Disable WordPress image sizes', 'cf-images' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Disable WordPress image sizes', 'cf-images' ); ?></span>
						</legend>
						<label for="disable_sizes">
							<input name="disable-sizes" type="checkbox" id="disable_sizes" value="1" <?php checked( get_option( 'cf-images-disable-generation', false ) ); ?>>
							<?php esc_html_e( 'Enable', 'cf-images' ); ?>
						</label>
					</fieldset>
					<p class="description">
						<?php esc_html_e( 'Setting this option will disable generation of `-scaled` images and other image sizes. Only the original image will be stored in the media library. Already generated attachment sizes will not be affected.', 'cf-images' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Note: This feature is experimental. All the image sizes can be restored with the `Regenerate Thumbnails` plugin.', 'cf-images' ); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'cf-images' ); ?>">
			<span class="spinner"></span>
		</p>
	</form>

	<h2><?php esc_html_e( 'Bulk options', 'cf-images' ); ?></h2>

	<p><?php esc_html_e( 'You can either manually upload individual images from the media library, or bulk upload all the images using the button below.', 'cf-images' ); ?></p>
	<p>
		<input type="button" class="button" value="<?php esc_attr_e( 'Upload All Images to Cloudflare', 'cf-images' ); ?>" id="cf-images-upload-all">
	</p>

	<p><?php esc_html_e( 'If you wish to remove all the images, stored on Cloudflare, click the button below.', 'cf-images' ); ?></p>
	<p><?php esc_html_e( 'Note: If `Disable WordPress image sizes` option has been selected above, you will need to regenerate all the image sizes manually.', 'cf-images' ); ?></p>
	<p>
		<input type="button" class="button cf-images-button-red" value="<?php esc_attr_e( 'Remove All Images from Cloudflare', 'cf-images' ); ?>" id="cf-images-remove-all">
	</p>

</div>
