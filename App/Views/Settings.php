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

$wp_sizes = apply_filters( 'cf_images_registered_sizes', get_intermediate_image_sizes() );
$variants = get_option( 'cf-images-variants', array() );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Offload Images to Cloudflare', 'cf-images' ); ?></h1>

	<?php if ( ! get_option( 'cf-images-flexible-variants', false ) ) : ?>
		<h2><?php esc_html_e( 'Image variants', 'cf-images' ); ?></h2>
		<p><?php esc_html_e( 'Syncing up image sizes is required to make sure Cloudflare Images have all the WordPress registered image sizes.', 'cf-images' ); ?></p>
		<p><?php esc_html_e( 'This action is only required once.', 'cf-images' ); ?></p>

		<h4><?php esc_html_e( 'Synced image sizes:', 'cf-images' ); ?></h4>
		<table class="widefat">
			<thead>
			<tr>
				<th><?php esc_html_e( 'WordPress image size ID', 'cf-images' ); ?></th>
				<th><?php esc_html_e( 'Cloudflare Images variant', 'cf-images' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $wp_sizes as $registered_size ) : ?>
				<tr data-id="<?php echo esc_attr( $registered_size ); ?>">
					<td><?php echo esc_html( $registered_size ); ?></td>
					<td>
						<?php
						if ( isset( $variants[ $registered_size ] ) ) {
							echo esc_html( $variants[ $registered_size ]['variant'] );
						} else {
							esc_html_e( 'Unregistered size', 'cf-images' );
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<p>
			<input class="button" type="button" value="<?php esc_attr_e( 'Sync Image Sizes', 'cf-images' ); ?>" id="cf-images-sync-image-sizes" />
			<span class="spinner"></span>
		</p>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Misc options', 'cf-images' ); ?></h2>

	<form id="cf-images-form" data-type="settings">
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Generate WordPress image sizes', 'cf-images' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Generate WordPress image sizes', 'cf-images' ); ?></span>
						</legend>
						<label for="disable_sizes">
							<input name="disable-sizes" type="checkbox" id="disable_sizes" value="1" <?php checked( get_option( 'cf-images-disable-generation', false ) ); ?>>
							<?php esc_html_e( 'Disable', 'cf-images' ); ?>
						</label>
					</fieldset>
					<p class="description">
						<?php esc_html_e( 'Setting this option will disable generation of `-scaled` images and other image sizes. Only the original image will be stored in the media library.', 'cf-images' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Note: Already generated attachment sizes will not be affected.', 'cf-images' ); ?>
					</p>
				</td>
			</tr>

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
					<?php esc_html_e( 'Flexible variants', 'cf-images' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Enable flexible variants', 'cf-images' ); ?></span>
						</legend>
						<label for="flexible_variants">
							<input name="flexible-variants" type="checkbox" id="flexible_variants" value="1" <?php checked( get_option( 'cf-images-flexible-variants', false ) ); ?>>
							<?php esc_html_e( 'Enable', 'cf-images' ); ?>
						</label>
					</fieldset>
					<p class="description">
						<?php esc_html_e( 'Flexible variants allow you to create variants with dynamic resizing.', 'cf-images' ); ?>
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

</div>
