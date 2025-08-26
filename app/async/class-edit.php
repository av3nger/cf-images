<?php
/**
 * Asynchronous edit class
 *
 * This class defines all code necessary to implement async processing of image edits.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Async
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.6
 */

namespace CF_Images\App\Async;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Asynchronous edit class.
 *
 * @since 1.9.6
 */
class Edit extends Task {
	/**
	 * Action.
	 *
	 * @since 1.9.6
	 * @access protected
	 * @var string
	 */
	protected $action = 'wp_save_image_editor_file';

	/**
	 * This is the argument count for the main action set in the constructor. It
	 * is set to an arbitrarily high value of twenty, but can be overridden if
	 * necessary.
	 *
	 * @since 1.9.6
	 * @access protected
	 * @var int
	 */
	protected $argument_count = 2;

	/**
	 * Priority.
	 *
	 * @since 1.9.6
	 * @access protected
	 * @var int
	 */
	protected $priority = 12;

	/**
	 * Prepare any data to be passed to the asynchronous postback.
	 *
	 * @since 1.9.6
	 *
	 * @param array $data Indexed array of input data.
	 *
	 * @return array
	 */
	protected function prepare_data( array $data ): array {
		// Store the post data in $data variable.
		if ( ! empty( $data ) ) {
			$data = array_merge( $data, $_POST );
		}

		// Store the image path.
		$data['filepath']  = ! empty( $data[1] ) ? $data[1] : '';
		$data['wp-action'] = ! empty( $data['action'] ) ? $data['action'] : '';
		unset( $data['action'], $data[1] );

		return $data;
	}

	/**
	 * Run the do_action function for the asynchronous postback.
	 *
	 * @since 1.9.6
	 */
	protected function run_action() {
		if ( isset( $_POST['wp-action'], $_POST['do'], $_POST['postid'] )
			&& 'image-editor' === $_POST['wp-action']
			&& check_ajax_referer( 'image_editor-' . (int) $_POST['postid'] )
			&& 'open' !== $_POST['do']
		) {
			$attachment_id = ! empty( $_POST['postid'] ) ? (int) $_POST['postid'] : '';

			if ( ! wp_attachment_is_image( $attachment_id ) ) {
				return;
			}

			$metadata = array(
				'file' => ! empty( $_POST['filepath'] ) ? wp_unslash( $_POST['filepath'] ) : '',
			);

			do_action( "wp_async_$this->action", $metadata, $attachment_id, 'replace' );
		}
	}
}
