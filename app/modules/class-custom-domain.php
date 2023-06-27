<?php
/**
 * Serve from custom domain
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.4.0  Moved out into its own module.
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Custom_Domain class.
 *
 * @since 1.4.0
 */
class Custom_Domain extends Module {

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->icon  = 'admin-links';
		$this->order = 20;
		$this->title = esc_html__( 'Serve from custom domain', 'cf-images' );
	}

	/**
	 * Render module description.
	 *
	 * @since 1.4.0
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function render_description( string $module ) {

		if ( $module !== $this->module ) {
			return;
		}
		$custom_domain = get_option( 'cf-images-custom-domain', false );
		?>
		<p>
			<?php esc_html_e( 'Use the current site domain instead of `imagedelivery.net`, or specify a custom domain.', 'cf-images' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Note: The domain must be linked with Cloudflare in order to work correctly.', 'cf-images' ); ?>
		</p>

		<p>
			<label class="screen-reader-text" for="custom-domain-input"><?php esc_html_e( 'Custom domain', 'cf-images' ); ?></label>
			<input class="<?php echo $custom_domain ? '' : 'hidden'; ?>" value="<?php echo wp_http_validate_url( $custom_domain ) ? esc_attr( $custom_domain ) : ''; ?>" type="text" name="custom_domain_input" id="custom-domain-input" placeholder="https://cdn.example.com">
		</p>
		<?php

	}

	/**
	 * Init the module.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function init() {}
}
