<?php
/**
 * Header view
 *
 * Common view for all pages.
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

<div class="notice is-dismiss" id="cf-images-ajax-notice" style="display: none;">
	<p></p>
</div>

<?php if ( get_option( 'cf-images-install-notice', false ) ) : ?>
	<div class="notice notice-info is-dismissible" id="cf-images-install-notice">
		<p><?php esc_html_e( 'Thank you for installing the plugin. This is the first release, not all plugins/themes might be supported. Please report all issues on the wp.org support forums for the plugin, and I will try to fix everything ASAP.', 'cf-images' ); ?></p>
	</div>
<?php endif; ?>
