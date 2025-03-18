<?php
/**
 * Cloudflare R2 module
 *
 * This class defines all code necessary for offloading media to Cloudflare R2 object storage.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.5
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * R2 class.
 *
 * @since 1.9.5
 */
class R2 extends Module {
	/**
	 * This is a core module, meaning it can't be enabled/disabled via options.
	 *
	 * @since 1.9.5
	 *
	 * @var bool
	 */
	protected $core = true;

	/**
	 * Action names.
	 *
	 * @since 1.9.5
	 *
	 * @var array
	 */
	private $actions = array( 'r2-upload', 'r2-remove' );

	/**
	 * Init the module.
	 *
	 * @since 1.9.5
	 */
	public function init() {
		add_filter( 'cf_images_bulk_actions', array( $this, 'add_bulk_action' ) );
		add_filter( 'cf_images_wp_query_args', array( $this, 'add_wp_query_args' ), 10, 2 );
		add_action( 'cf_images_bulk_step', array( $this, 'bulk_step' ), 10, 2 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_r2_upload', array( $this, 'ajax_r2_upload' ) );
		}
	}

	/**
	 * Extend bulk action so that the AJAX callback accepts the bulk request.
	 *
	 * @since 1.9.5
	 * @see Media::ajax_bulk_process()
	 *
	 * @param array $actions Supported actions.
	 *
	 * @return array
	 */
	public function add_bulk_action( array $actions ): array {
		foreach ( $this->actions as $action ) {
			if ( ! in_array( $action, $actions, true ) ) {
				$actions[] = $action;
			}
		}

		return $actions;
	}

	/**
	 * Adjust the WP_Query args for bulk offload action.
	 *
	 * @since 1.9.5
	 * @see Ajax::get_wp_query_args()
	 *
	 * @param array  $args   WP_Query args.
	 * @param string $action Executing action.
	 *
	 * @return array
	 */
	public function add_wp_query_args( array $args, string $action ): array {
		if ( ! in_array( $action, $this->actions, true ) ) {
			return $args;
		}

		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_cloudflare_image_r2_offloaded',
				'compare' => 'full-remove' === $action ? 'NOT EXISTS' : 'EXISTS',
			),
			array(
				'key'     => '_cloudflare_image_id',
				'compare' => 'EXISTS',
			),
		);

		return $args;
	}

	/**
	 * Perform bulk step.
	 *
	 * @since 1.8.0
	 * @see Media::ajax_bulk_process()
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $action        Executing action.
	 */
	public function bulk_step( int $attachment_id, string $action ) {
		if ( 'r2-upload' === $action ) {
			$this->ajax_r2_upload( $attachment_id );
		}

		// TODO: Add `r2-remove` action.
	}

	/**
	 * Upload images to R2.
	 *
	 * @since 1.9.5
	 *
	 * @param int|string|null $attachment_id Attachment ID.
	 */
	public function ajax_r2_upload( $attachment_id = null ) {

	}
}
