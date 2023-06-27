<?php
/**
 * Settings view
 *
 * Various Cloudflare Images settings.
 *
 * @link https://vcore.au
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

$api_stats = sprintf( /* translators: %1$d - uploaded image count, %2$d - allowed image count */
	esc_html__( 'API stats: %1$d/%2$d', 'cf-images' ),
	$stats['api_current'] ?? absint( $stats['synced'] ),
	$stats['api_allowed'] ?? 100000
);

?>

<main class="cf-images-settings">
	<article>
		<header>
			<nav>
				<ul>
					<li>
						<h3><?php esc_html_e( 'Offload Images to Cloudflare', 'cf-images' ); ?></h3>
					</li>
				</ul>
				<ul>
					<li>
						<?php esc_html_e( 'Status', 'cf-images' ); ?>: <span style="color: green"><?php esc_html_e( 'Connected', 'cf-images' ); ?></span>
					</li>
				</ul>
			</nav>
		</header>

		<form id="cf-images-form" data-type="settings" onsubmit="event.preventDefault()">
			<?php do_action( 'cf_images_render_setting' ); ?>

			<div class="cf-form-item">
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
						<?php esc_html_e( 'You can either manually upload individual images from the media library, or bulk upload/remove all existing images using the buttons below.', 'cf-images' ); ?>
					</p>

					<p class="stats">
						<?php esc_html_e( 'Offloaded', 'cf-images' ); ?>: <em data-tooltip="<?php echo esc_attr( $api_stats ); ?>"><?php echo absint( $stats['synced'] ); ?> <?php esc_html_e( 'images', 'cf-images' ); ?></em>
					</p>
				</div>
			</div>

			<div class="cf-form-item">
				<span class="dashicons dashicons-trash"></span>
				<label for="cf-images-remove-all">
					<?php esc_html_e( 'Bulk remove', 'cf-images' ); ?>
				</label>
				<div>
					<a href="#" role="button" class="outline cf-images-button-red" id="cf-images-show-modal" data-target="modal-confirm">
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
			</div>
		</form>

		<footer>
			<a href="#" role="button" aria-busy="false" id="save-settings">
				<?php esc_html_e( 'Save Changes', 'cf-images' ); ?>
			</a>

			<a href="#" class="secondary" role="button" aria-busy="false" id="cf-images-disconnect">
				<?php esc_html_e( 'Disconnect', 'cf-images' ); ?>
			</a>
		</footer>
	</article>

	<?php if ( ! get_site_option( 'cf-images-hide-sidebar' ) ) : ?>
		<?php $this->view( 'sidebar' ); ?>
	<?php endif; ?>
</main>

<?php $this->view( 'modals/confirm' ); ?>
