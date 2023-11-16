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
	use Traits\Stats;

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
		add_filter( 'plugin_action_links_cf-images/cf-images.php', array( $this, 'settings_link' ) );

		if ( wp_doing_ajax() ) {
			$settings = new Settings();
			add_action( 'wp_ajax_cf_images_do_setup', array( $settings, 'ajax_do_setup' ) );
			add_action( 'wp_ajax_cf_images_disconnect', array( $settings, 'ajax_disconnect' ) );
			add_action( 'wp_ajax_cf_images_hide_sidebar', array( $settings, 'ajax_hide_sidebar' ) );
			add_action( 'wp_ajax_cf_images_check_status', array( $settings, 'ajax_check_status' ) );

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
	 * @param string $hook The current admin page.
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
	}

	/**
	 * Load plugin scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( string $hook ) {
		// Run only on plugin pages.
		if ( 'media_page_cf-images' !== $hook ) {
			return;
		}

		wp_dequeue_script( 'react' );
		wp_dequeue_script( 'react-dom' );

		// We want the latest version of React.
		wp_enqueue_script(
			'react-latest',
			CF_IMAGES_DIR_URL . 'assets/js/cf-images-react.min.js',
			array(),
			CF_IMAGES_VERSION,
			true
		);

		wp_enqueue_script(
			$this->get_slug(),
			CF_IMAGES_DIR_URL . 'assets/js/cf-images.min.js',
			array( 'jquery', 'wp-i18n', 'react-latest' ),
			CF_IMAGES_VERSION,
			true
		);

		wp_localize_script(
			$this->get_slug(),
			'CFImages',
			array(
				'nonce'       => wp_create_nonce( 'cf-images-nonce' ),
				'dirURL'      => CF_IMAGES_DIR_URL,
				'settings'    => get_option( 'cf-images-settings', Settings::DEFAULTS ),
				'cfStatus'    => $this->is_set_up(),
				'domain'      => get_option( 'cf-images-custom-domain', '' ),
				'hideSidebar' => get_site_option( 'cf-images-hide-sidebar' ),
				'fuzion'      => $this->is_fuzion_api_connected(),
				'stats'       => $this->get_stats(),
				'customPath'  => get_option( 'cf-images-custom-path', '' ),
			)
		);
	}

	/**
	 * Add `Settings` link on the `Plugins` page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions Actions array.
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
	 * Render page.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		?>
		<div class="wrap cf-images">
			<div id="cf-images" class="columns"></div>
		</div>
		<?php
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
