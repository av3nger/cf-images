<?php
/**
 * Confirmation modal
 *
 * Used to confirm bulk remove action
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Views
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.2.0
 */

namespace CF_Images\App\Views\Modals;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<dialog id="modal-confirm">
	<article>
		<a href="#" aria-label="<?php esc_attr_e( 'Close', 'cf-images' ); ?>" class="close" data-target="modal-confirm" onClick="window.cfToggleModal(event)"></a>
		<h3><?php esc_html_e( 'Confirm your action!', 'cf-images' ); ?></h3>
		<p>
			<?php esc_html_e( 'Are you sure you want to remove all images from Cloudflare? This action will unlink all media library images from Cloudflare Images and remove all stored images on Cloudflare. Local images are not affected by this action.', 'cf-images' ); ?>
		</p>
		<footer>
			<a href="#" role="button" data-target="modal-confirm" id="cf-images-remove-all">
				<?php esc_html_e( 'Confirm', 'cf-images' ); ?>
			</a>
			<a href="#" role="button" class="secondary" data-target="modal-confirm" onClick="window.cfToggleModal(event)">
				<?php esc_html_e( 'Cancel', 'cf-images' ); ?>
			</a>
		</footer>
	</article>
</dialog>
