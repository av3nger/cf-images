<?php
/**
 * Abstract module class
 *
 * This class defines all the base code, which is inherited by stand-alone classes.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.3.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Traits\Helpers;
use CF_Images\App\Traits\Settings;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Abstract Module class.
 *
 * @since 1.3.0
 */
abstract class Module {

	use Helpers, Settings;

	/**
	 * This is a core module, meaning it can't be enabled/disabled via options.
	 *
	 * @since 1.3.0
	 *
	 * @var bool
	 */
	protected $core = false;

	/**
	 * Module ID.
	 *
	 * @since 1.3.0
	 * @access protected
	 *
	 * @var string $module
	 */
	protected $module = '';

	/**
	 * Should the module only run on front-end?
	 *
	 * @since 1.3.0
	 * @access protected
	 *
	 * @var bool $only_frontend
	 */
	protected $only_frontend = false;

	/**
	 * Module constructor.
	 *
	 * @since 1.3.0
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function __construct( string $module ) {

		$this->module = $module;
		$this->register_ui();
		$this->pre_init();

		add_filter( 'cf_images_module_enabled', array( $this, 'is_module_enabled' ), 10, 2 );
		add_action( 'cf_images_render_setting', array( $this, 'render_setting' ), $this->order );
		add_action( 'cf_images_setting_description', array( $this, 'render_description' ) );

		if ( ! $this->is_set_up() || ! $this->is_enabled() ) {
			return;
		}

		// Some modules are front-end only, make sure we can run them.
		if ( $this->only_frontend && ( is_admin() || ! $this->can_run() ) ) {
			return;
		}

		$this->init();

	}

	/**
	 * Module init.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	abstract public function init();

	/**
	 * Module pre-init actions.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	protected function pre_init() {}

	/**
	 * Check if module is enabled via plugin settings.
	 *
	 * @since 1.3.0
	 *
	 * @return bool
	 */
	protected function is_enabled(): bool {

		// Core modules cannot be disabled.
		if ( $this->core ) {
			return true;
		}

		return (bool) get_option( 'cf-images-' . $this->module, false );

	}

	/**
	 * Check if we can run the plugin. Not all images should be converted, for example,
	 * SEO images from meta tags should be left untouched.
	 *
	 * @since 1.1.3
	 *
	 * @return bool
	 */
	protected function can_run(): bool {

		if ( $this->is_rest_request() || wp_doing_cron() ) {
			return false;
		}

		if ( apply_filters( 'cf_images_can_run', false ) ) {
			return false;
		}

		return true;

	}

	/**
	 * This is how WordPress treats us developers - doesn't give a sh*t about is_admin(), so we have to do these
	 * custom checks to make sure we don't break the admin area.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	private function is_rest_request(): bool {

		$wordpress_has_no_logic = filter_input( INPUT_GET, '_wp-find-template' );
		$wordpress_has_no_logic = sanitize_key( $wordpress_has_no_logic );

		if ( ! empty( $wordpress_has_no_logic ) && 'true' === $wordpress_has_no_logic ) {
			// And if below was not enough - we also need to check this bs...
			return true;
		}

		$rest_url_prefix = rest_get_url_prefix();
		if ( empty( $rest_url_prefix ) ) {
			return false;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return strpos( $request_uri, $rest_url_prefix ) !== false;

	}

	/**
	 * Render module setting.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function render_setting() {

		if ( ! $this->title ) {
			return;
		}
		?>

		<div class="cf-form-item">
			<?php if ( ! empty( $this->icon ) ) : ?>
				<span class="dashicons dashicons-<?php echo esc_attr( $this->icon ); ?>"></span>
			<?php endif; ?>
			<?php if ( ! empty( $this->title ) ) : ?>
				<label for="<?php echo esc_html( str_replace( '-', '_', $this->module ) ); ?>">
					<?php echo esc_html( $this->title ); ?>
					<?php if ( $this->experimental || $this->new ) : ?>
						<span class="cf-images-badge">
							<?php if ( $this->experimental ) : ?>
								<?php esc_html_e( 'Experimental', 'cf-images' ); ?>
							<?php endif; ?>
							<?php if ( $this->new ) : ?>
								<?php esc_html_e( 'New', 'cf-images' ); ?>
							<?php endif; ?>
						</span>
					<?php endif; ?>
				</label>
			<?php endif; ?>
			<div>
				<input type="checkbox" value="1" role="switch"
					name="<?php echo esc_html( $this->module ); ?>"
					id="<?php echo esc_html( str_replace( '-', '_', $this->module ) ); ?>"
					<?php checked( (bool) get_option( 'cf-images-' . $this->module, false ) ); ?>
				>
				<?php do_action( 'cf_images_setting_description', $this->module ); ?>
			</div>
		</div>

		<?php

	}

	/**
	 * Filter callback to check if a specific module is enabled.
	 *
	 * @since 1.4.0
	 *
	 * @param bool   $enabled  Status.
	 * @param string $module   Module ID.
	 *
	 * @return bool
	 */
	public function is_module_enabled( bool $enabled, string $module ): bool {

		// Core modules cannot be disabled.
		if ( $this->core ) {
			return true;
		}

		return (bool) get_option( 'cf-images-' . $module, false );

	}

}
