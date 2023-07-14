<?php
/**
 * The file that defines the admin plugin class
 *
 * This is used to define admin-specific functionality and UI elements.
 *
 * @link https://vcore.au
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
	 * Media class instance.
	 *
	 * @since 1.3.0
	 * @var Media $media
	 */
	private $media;

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

		$this->media = new Media();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_notices', array( $this, 'show_notice' ) );
		add_filter( 'plugin_action_links_cf-images/cf-images.php', array( $this, 'settings_link' ) );

		if ( wp_doing_ajax() ) {
			$settings = new Settings();
			add_action( 'wp_ajax_cf_images_do_setup', array( $settings, 'ajax_do_setup' ) );
			add_action( 'wp_ajax_cf_images_save_settings', array( $settings, 'ajax_save_settings' ) );
			add_action( 'wp_ajax_cf_images_disconnect', array( $settings, 'ajax_disconnect' ) );
			add_action( 'wp_ajax_cf_images_hide_sidebar', array( $settings, 'ajax_hide_sidebar' ) );

			add_action( 'wp_ajax_cf_images_offload_image', array( $this->media, 'ajax_offload_image' ) );
			add_action( 'wp_ajax_cf_images_bulk_process', array( $this->media, 'ajax_bulk_process' ) );
			add_action( 'wp_ajax_cf_images_skip_image', array( $this->media, 'ajax_skip_image' ) );
			add_action( 'wp_ajax_cf_images_undo_image', array( $this->media, 'ajax_undo_image' ) );
			add_action( 'wp_ajax_cf_images_delete_image', array( $this->media, 'ajax_delete_image' ) );
			add_action( 'wp_ajax_cf_images_restore_image', array( $this->media, 'ajax_restore_image' ) );
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
		if ( 'media_page_cf-images' === $hook ) {
			wp_enqueue_style(
				$this->get_slug(),
				CF_IMAGES_DIR_URL . 'assets/css/cf-images.min.css',
				array(),
				CF_IMAGES_VERSION
			);
		}

		// Run only on media library pages.
		if ( 'upload.php' === $hook ) {
			wp_enqueue_style(
				$this->get_slug(),
				CF_IMAGES_DIR_URL . 'assets/css/cf-images-media.min.css',
				array(),
				CF_IMAGES_VERSION
			);
		}

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
		if ( 'media_page_cf-images' !== $hook && 'upload.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->get_slug(),
			CF_IMAGES_DIR_URL . 'assets/js/cf-images.min.js',
			array(),
			CF_IMAGES_VERSION,
			true
		);

		wp_localize_script(
			$this->get_slug(),
			'CFImages',
			array(
				'nonce'   => wp_create_nonce( 'cf-images-nonce' ),
				'strings' => array(
					'disconnecting' => esc_html__( 'Disconnecting...', 'cf-images' ),
					'saveChange'    => esc_html__( 'Save Changes', 'cf-images' ),
					'inProgress'    => esc_html__( 'Processing', 'cf-images' ),
					'offloadError'  => esc_html__( 'Processing error', 'cf-images' ),
					'savingChanges' => esc_html__( 'Saving...', 'cf-images' ),
					'login'         => esc_html__( 'Login', 'cf-images' ),
				),
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

		// Called on success after removing all images from Cloudflare.
		if ( filter_input( INPUT_GET, 'deleted', FILTER_VALIDATE_BOOLEAN ) ) {
			$this->render_notice( __( 'All images have been successfully removed from Cloudflare Images.', 'cf-images' ) );
		}

		// Called on success after uploading all images to Cloudflare.
		if ( filter_input( INPUT_GET, 'updated', FILTER_VALIDATE_BOOLEAN ) ) {
			$this->render_notice( __( 'All images have been successfully uploaded to Cloudflare Images.', 'cf-images' ) );
		}

		// Logged in.
		if ( filter_input( INPUT_GET, 'login', FILTER_VALIDATE_BOOLEAN ) ) {
			$this->render_notice( __( 'API key generated', 'cf-images' ) );
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
		<div class="cf-images-notifications">
			<div class="notice notice-<?php echo esc_attr( $type ); ?>" id="cf-images-notice">
				<p>
					<?php echo esc_html( $message ); ?>
				</p>
			</div>
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

		$this->view( 'header' );

		if ( ! $this->is_set_up() ) {
			$this->view( 'setup' );
			return;
		}

		$this->view( 'settings' );

	}

	/**
	 * Load an admin view.
	 *
	 * @param string $file  View file name.
	 *
	 * @return void
	 */
	public function view( string $file ) {

		$view = __DIR__ . '/views/' . $file . '.php';

		if ( ! file_exists( $view ) ) {
			return;
		}

		ob_start();
		include_once $view;
		echo ob_get_clean(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */

	}

	/**
	 * Return Media instance.
	 *
	 * @since 1.3.0
	 *
	 * @return Media
	 */
	public function media(): Media {
		return $this->media;
	}

}
