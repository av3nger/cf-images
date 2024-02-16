<?php
/**
 * Full Offload module
 *
 * Manage removing physical files from the media library.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.8.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Traits;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Full_Offload class.
 *
 * @since 1.8.0
 */
class Full_Offload extends Module {
	use Traits\Ajax;
	use Traits\Stats;

	/**
	 * Action names.
	 *
	 * @var array
	 */
	private $actions = array( 'full-remove', 'full-restore' );

	/**
	 * Init the module.
	 *
	 * @since 1.8.0
	 */
	public function init() {
		// Bulk remove actions.
		add_filter( 'cf_images_bulk_actions', array( $this, 'add_bulk_action' ) );
		add_filter( 'cf_images_wp_query_args', array( $this, 'add_wp_query_args' ), 10, 2 );
		add_action( 'cf_images_bulk_step', array( $this, 'bulk_step' ), 10, 2 );
	}

	/**
	 * Extend bulk action so that the AJAX callback accepts the bulk request.
	 *
	 * @since 1.8.0
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
	 * Adjust the WP_Query args for bulk compress action.
	 *
	 * @since 1.8.0
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
				'key'     => '_cloudflare_image_offloaded',
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
		if ( 'full-remove' === $action ) {
			$this->media()->ajax_delete_image( $attachment_id );
		}

		if ( 'full-restore' === $action ) {
			$this->media()->ajax_restore_image( $attachment_id );
		}
	}
}
