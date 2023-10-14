<?php
/**
 * Setup view
 *
 * Shown, when the Cloudflare account ID or API key are not defined.
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

<article>
	<?php if ( get_option( 'cf-images-config-written', false ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'It appears there is something wrong with the API token, and the plugin is not able to authenticate on the Cloudflare API. Please check the details below and update the API token and/or account ID.', 'cf-images' ); ?>
			</p>
		</div>
	<?php endif; ?>
</article>
