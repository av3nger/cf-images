<?php
/**
 * The file that defines the admin plugin class
 *
 * This is used to define admin-specific functionality and UI elements.
 *
 * @link https://vcore.ru
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class.
 *
 * @since 1.0.0
 */
class Admin {

	use Traits\Helpers;

	/**
	 * Class constructor.
	 *
	 * Init all actions and filters for the admin area of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_notices', array( $this, 'show_notice' ) );
		add_filter( 'plugin_action_links_cf-images/cf-images.php', array( $this, 'settings_link' ) );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_save_settings', array( $this, 'ajax_save_settings' ) );
		}

	}

	/**
	 * Load plugin styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook  The current admin page.
	 *
	 * @return void
	 */
	public function enqueue_styles( string $hook ) {

		// Run only on plugin pages.
		if ( 'media_page_cf-images' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			$this->get_slug(),
			CF_IMAGES_DIR_URL . 'assets/css/cf-images.min.css',
			array(),
			$this->get_version()
		);

	}

	/**
	 * Load plugin scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook  The current admin page.
	 *
	 * @return void
	 */
	public function enqueue_scripts( string $hook ) {

		// Run only on plugin pages.
		if ( 'media_page_cf-images' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->get_slug(),
			CF_IMAGES_DIR_URL . 'assets/js/cf-images.min.js',
			array(),
			$this->get_version(),
			true
		);

		wp_localize_script(
			$this->get_slug(),
			'CFImages',
			array(
				'nonce' => wp_create_nonce( 'cf-images-nonce' ),
			)
		);

	}

	/**
	 * Add `Settings` link on the `Plugins` page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions  Actions array.
	 *
	 * @return array
	 */
	public function settings_link( array $actions ): array {

		if ( ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$actions['cf-images-settings'] = '<a href="' . admin_url( 'upload.php?page=cf-images' ) . '" aria-label="' . esc_attr( __( 'Settings', 'cf-images' ) ) . '">' . esc_html__( 'Settings', 'cf-images' ) . '</a>';
		return $actions;

	}

	/**
	 * Register sub-menu under the WordPress "Media" menu element.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_menu() {

		add_submenu_page(
			'upload.php',
			__( 'Offload Images to Cloudflare', 'cf-images' ),
			__( 'Offload Settings', 'cf-images' ),
			'manage_options',
			$this->get_slug(),
			array( $this, 'render_page' )
		);

	}

	/**
	 * Show notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function show_notice() {

		if ( false !== $this->get_error() ) {
			$message = sprintf( /* translators: %1$s - error message, %2$d - error code */
				esc_html__( '%1$s (code: %2$d)', 'cf-images' ),
				esc_html( $this->get_error()->get_error_message() ),
				(int) $this->get_error()->get_error_code()
			);

			$this->render_notice( $message, 'error' );
			return;
		}

		// Called from setup screen, when all defines have been set.
		if ( filter_input( INPUT_GET, 'saved', FILTER_VALIDATE_BOOLEAN ) ) {
			$this->render_notice( __( 'Settings saved.', 'cf-images' ) );
		}

		// Called on success after syncing image sizes.
		if ( filter_input( INPUT_GET, 'updated', FILTER_VALIDATE_BOOLEAN ) ) {
			$this->render_notice( __( 'Images sizes have been synced.', 'cf-images' ) );
		}

	}

	/**
	 * Render notice.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message  Notice message.
	 * @param string $type     Notice type.
	 *
	 * @return void
	 */
	private function render_notice( string $message, string $type = 'success' ) {
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?>" id="cf-images-notice">
			<p>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page() {

		$this->view( 'Header' );

		if ( ! $this->is_set_up() ) {
			$this->view( 'Setup' );
			return;
		}

		$this->view( 'Settings' );

	}

	/**
	 * Load an admin view.
	 *
	 * @param string $file  View file name.
	 *
	 * @return void
	 */
	public function view( string $file ) {

		$view = __DIR__ . '/Views/' . $file . '.php';

		if ( ! file_exists( $view ) ) {
			return;
		}

		ob_start();
		include $view;
		echo ob_get_clean(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */

	}

	/**
	 * Save settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_save_settings() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['data'] ) ) {
			wp_die();
		}

		// Data sanitized later in code.
		parse_str( wp_unslash( $_POST['data'] ), $form ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! isset( $form['account-id'] ) || ! isset( $form['api-key'] ) ) {
			wp_die();
		}

		$settings = new Settings();
		$settings->write_config( 'CF_IMAGES_ACCOUNT_ID', sanitize_text_field( $form['account-id'] ) );
		$settings->write_config( 'CF_IMAGES_KEY_TOKEN', sanitize_text_field( $form['api-key'] ) );

		wp_send_json_success();

	}


}
