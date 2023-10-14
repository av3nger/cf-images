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

?>

<main class="cf-images-settings">
	<article>
		<form id="cf-images-form" data-type="settings" onsubmit="event.preventDefault()">
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
	</article>
</main>

<?php $this->view( 'modals/confirm' ); ?>
