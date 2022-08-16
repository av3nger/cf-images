<?php
/**
 * Asynchronous upload class.
 *
 * @link       https://wpmudev.com
 * @since      1.0.0
 *
 * @package    CF_Images
 * @subpackage CF_Images/App/Async
 */

namespace CF_Images\App\Async;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Asynchronous upload class.
 *
 * This class defines all code necessary to implement async processing of image uploads.
 *
 * @since      1.0.0
 * @package    CF_Images
 * @subpackage CF_Images/App/Async
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Upload extends Task {

	/**
	 * Action.
	 *
	 * @var string
	 */
	protected $action = 'wp_generate_attachment_metadata';

	/**
	 * This is the argument count for the main action set in the constructor. It
	 * is set to an arbitrarily high value of twenty, but can be overridden if
	 * necessary.
	 *
	 * @var int
	 */
	protected $argument_count = 3;

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

		return array(
			'metadata'      => $data[0],
			'attachment_id' => $data[1],
			'context'       => $data[2],
		);

	}

	/**
	 * Run the do_action function for the asynchronous postback.
	 *
	 * @since 1.0.0
	 */
	protected function run_action() {

		$metadata      = filter_input( INPUT_POST, 'metadata', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );
		$context       = filter_input( INPUT_POST, 'context', FILTER_SANITIZE_STRING );

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		// Allow the Asynchronous task to run.
		do_action( "wp_async_$this->action", $metadata, $attachment_id, $context );

	}

}
