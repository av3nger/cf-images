<?php
/**
 * Asynchronous upload class
 *
 * This class defines all code necessary to implement async processing of image uploads.
 *
 * @link https://vcore.ru
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

		return array(
			'metadata'      => $data[0],
			'attachment_id' => $data[1],
		);

	}

	/**
	 * Run the do_action function for the asynchronous postback.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function run_action() {

		$metadata      = filter_input( INPUT_POST, 'metadata', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		// Allow the Asynchronous task to run.
		do_action( "wp_async_$this->action", $metadata, $attachment_id );

	}

}
