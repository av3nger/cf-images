<?php
/**
 * Cloudflare API class that handles images manipulations
 *
 * This class defines all code necessary to communicate with the Cloudflare API.
 *
 * @link https://vcore.ru
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Api
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App\Api;

use Exception;
use stdClass;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Cloudflare API class that handles images manipulations.
 *
 * @since 1.0.0
 */
class Image extends Api {

	/**
	 * Upload image to Cloudflare Images.
	 *
	 * @since 1.0.0
	 *
	 * @param string $image  Image path.
	 * @param int    $id     Image ID.
	 * @param string $name   File name.
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass
	 */
	public function upload( string $image, int $id = 0, string $name = '' ): stdClass {

		// CURLFILE only works on PHP 5.5 and higher curl_file_create().
		$data['file'] = curl_file_create( $image, '', $name );

		if ( 0 !== $id ) {
			$data['metadata'] = wp_json_encode( array( 'meta' => $id ) );
		}

		$this->set_method( 'UPLOAD' );
		$this->set_endpoint( '' );
		$this->set_request_body( $data );

		$results = $this->request();

		if ( isset( $results->result ) ) {
			return $results->result;
		}

		return $results;

	}

	/**
	 * Delete an image on Cloudflare Images. On success, all copies of the image are deleted and purged from Cache.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id  Image identifier.
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass
	 */
	public function delete( string $id ): stdClass {

		$this->set_method( 'DELETE' );
		$this->set_endpoint( "/$id" );

		return $this->request();

	}

}
