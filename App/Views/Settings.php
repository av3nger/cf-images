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

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="postbox">
					<h2><?php esc_html_e( 'Settings', 'cf-images' ); ?></h2>

					<form class="inside" id="cf-images-form" data-type="settings">
						<table class="form-table">
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
										<?php esc_html_e( 'Setting this option will disable generation of `-scaled` images and other image sizes. Only the original image will be stored in the media library. Only for newly uploaded files, current images will not be affected.', 'cf-images' ); ?>
									</p>
									<p class="description">
										<?php esc_html_e( 'Note: This feature is experimental. All the image sizes can be restored with the `Regenerate Thumbnails` plugin.', 'cf-images' ); ?>
									</p>
								</td>
							</tr>
							</tbody>
						</table>

						<p>
							<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'cf-images' ); ?>">
							<span class="spinner"></span>
						</p>
					</form>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="postbox">
					<h2><?php esc_html_e( 'Stats', 'cf-images' ); ?></h2>

					<div class="inside">
						<?php
						printf( /* translators: %d - number of offloaded images */
							esc_html__( 'Offloaded images: %d', 'cf-images' ),
							absint( $stats['synced'] )
						);
						?>
					</div>

					<h2><?php esc_html_e( 'Bulk options', 'cf-images' ); ?></h2>

					<div class="inside">
						<div class="cf-images-progress">
							<div class="cf-images-progress-bar">
								<div class="cf-images-progress-filler" style="width: 0;"></div>
							</div>
							<span><?php esc_html_e( 'Initializing...', 'cf-images' ); ?></span>
						</div>

						<p><?php esc_html_e( 'You can either manually upload individual images from the media library, or bulk upload/remove all the images using the buttons below.', 'cf-images' ); ?></p>

						<p><?php esc_html_e( 'Note: If `Disable WordPress image sizes` option has been selected above, you will need to regenerate all the image sizes manually.', 'cf-images' ); ?></p>
						<p>
							<input type="button" class="button" value="<?php esc_attr_e( 'Upload', 'cf-images' ); ?>" id="cf-images-upload-all">
							<input type="button" class="button cf-images-button-red" value="<?php esc_attr_e( 'Remove', 'cf-images' ); ?>" id="cf-images-remove-all">
						</p>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	</div>

</div>
