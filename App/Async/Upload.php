<?php
/**
 * Asynchronous upload class
 *
 * This class defines all code necessary to implement async processing of image uploads.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Async
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App\Async;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Asynchronous upload class.
 *
 * @since 1.0.0
 */
class Upload extends Task {

	/**
	 * Action.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $action = 'wp_generate_attachment_metadata';

	/**
	 * This is the argument count for the main action set in the constructor. It
	 * is set to an arbitrarily high value of twenty, but can be overridden if
	 * necessary.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var int
	 */
	protected $argument_count = 2;

	/**
	 * Prepare any data to be passed to the asynchronous postback.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data  Indexed array of input data.
	 *
	 * @return array
	 */
	protected function prepare_data( array $data ): array {

		$out_data = array(
			'images'   => array(),
			'metadata' => array(),
			'current'  => $data[0], // Current image data, sent out to WordPress in Task::launch().
		);

		if ( ! empty( $this->body_data['images'] ) ) {
			$out_data['images']   = $this->body_data['images'];
			$out_data['metadata'] = $this->body_data['metadata'];
		}

		// Store attachment IDs and metadata inside the body data.
		$out_data['images'][]             = $data[1];
		$out_data['metadata'][ $data[1] ] = $data[0];

		return $out_data;

	}

	/**
	 * Run the do_action function for the asynchronous postback.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function run_action() {

		$image_ids = wp_parse_id_list( $_POST['images'] ); // phpcs:ignore

		array_walk(
			$image_ids,
			function( $attachment_id ) {
				if ( ! wp_attachment_is_image( $attachment_id ) ) {
					return;
				}

				$metadata = $_POST['metadata'][ $attachment_id ]; // phpcs:ignore
				if ( $metadata ) {
					do_action( "wp_async_$this->action", $metadata, $attachment_id );
				}
			}
		);

	}

}
