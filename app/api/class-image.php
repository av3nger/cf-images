<?php
/**
 * Cloudflare API class that handles images manipulations
 *
 * This class defines all code necessary to communicate with the Cloudflare API.
 *
 * @link https://vcore.au
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
			/**
			 * Allow modifying the metadata, when offloading images to Cloudflare.
			 *
			 * @since 1.1.3
			 *
			 * @param array $metadata  Meta data.
			 */
			$metadata = apply_filters( 'cf_images_upload_meta_data', array( 'meta' => $id ) );

			$data['metadata'] = wp_json_encode( $metadata );
		}

		/**
		 * Allow filtering the data, when offloading images to Cloudflare.
		 *
		 * @sice 1.2.0
		 *
		 * @param array      $data  Data.
		 * @param int|string $id    Image ID.
		 */
		$data = apply_filters( 'cf_images_upload_data', $data, $id );

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

	/**
	 * Fetch usage statistics details for Cloudflare Images.
	 *
	 * @since 1.1.0
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass
	 */
	public function stats(): stdClass {

		$this->set_method( 'GET' );
		$this->set_endpoint( '/stats' );

		$result = $this->request();

		if ( ! isset( $result->result ) || ! isset( $result->result->count ) ) {
			$count = new stdClass();

			$count->allowed = 100000;
			$count->current = 0;
			return $count;
		}

		return $result->result->count;

	}

}
