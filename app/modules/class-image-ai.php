<?php
/**
 * Image AI module
 *
 * Add AI-based functionality for tagging and captioning images.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.4.0
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image_Ai class.
 *
 * @since 1.4.0
 */
class Image_Ai extends Module {

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->icon  = 'format-image';
		$this->new   = true;
		$this->title = esc_html__( 'Image AI', 'cf-images' );
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
		?>
		<p>
			<?php esc_html_e( 'Use the power of AI to tag and caption your images.', 'cf-images' ); ?>
		</p>
		<?php if ( ! get_option( 'cf-image-ai-api-key', false ) ) : ?>
			<p>
				Don't have an account? Register for a free account. Already have an API key? Add it here.
			</p>

			<p>
				<label class="screen-reader-text" for="cf-ai-email-address"><?php esc_html_e( 'Email address', 'cf-images' ); ?></label>
				<input type="email" name="custom_domain_input" id="cf-ai-email-address" placeholder="<?php esc_attr_e( 'Email address', 'cf-images' ); ?>">

				<label class="screen-reader-text" for="cf-ai-password"><?php esc_html_e( 'Password', 'cf-images' ); ?></label>
				<input type="password" name="custom_domain_input" id="cf-ai-password" placeholder="<?php esc_attr_e( 'Password', 'cf-images' ); ?>">
			</p>
			<p>
				<a href="#" role="button" class="outline" aria-busy="false" style="float:none;">
					<?php esc_html_e( 'Login', 'cf-images' ); ?>
				</a>
			</p>
		<?php endif; ?>
		<?php

	}

	/**
	 * Init the module.
	 *
	 * @since 1.4.0
	 */
	public function init() {

	}

}
