<?php
/**
 * Image compress API class that handles image optimization
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Api
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.5.0
 */

namespace CF_Images\App\Api;

use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image compress API class.
 *
 * @since 1.5.0
 */
class Compress extends API_Multi {
	/**
	 * Fuzion API URL.
	 *
	 * @since 1.5.0
	 * @access protected
	 * @var string
	 */
	protected $api_url = 'https://images.getfuzion.io';

	/**
	 * Get arguments for request.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	protected function get_args(): array {
		$args = parent::get_args();

		$args['headers']['apiKey'] = get_option( 'cf-image-ai-api-key', '' );

		return $args;
	}

	/**
	 * Compress images.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $images    All image sizes for a selected attachment ID.
	 * @param string $mime_type Mime type.
	 *
	 * @return array
	 * @throws Exception If API request failed.
	 */
	public function optimize( array $images, string $mime_type ): array {
		$this->set_header( 'Content-Type', $mime_type );
		$this->set_timeout( 30 );
		$this->set_data( $images );

		$results = array();
		$errors  = array();
		foreach ( $this->requests() as $id => $response ) {
			if ( ! isset( $response->success ) || ! $response->success ) {
				// Get the error code and message.
				if ( isset( $response->status_code ) && isset( $response->body ) ) {
					$errors[ $response->status_code ] = $response->body;
				} elseif ( isset( $response->status_code ) && 401 === $response->status_code ) {
					$errors[401] = esc_html__( 'Rate limits enforced', 'cf-images' );
				} else {
					$errors[500] = esc_html__( 'Unknown API error. Please try again later.', 'cf-images' );
				}

				continue;
			}

			$results[ $id ] = array(
				'stats' => $response->headers->getValues( 'fuzion-stats' )[0] ?? '',
				'image' => $response->body,
			);
		}

		if ( empty( $results ) && ! empty( $errors ) ) {
			$error_code = array_key_first( $errors );
			throw new Exception( $errors[ $error_code ], (int) $error_code );
		}

		return $results;
	}
}
