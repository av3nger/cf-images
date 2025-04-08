<?php
/**
 * The file that defines the R2 API class
 *
 * This class handles communication with the Cloudflare R2 API using the AWS SDK for PHP.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Api
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.5
 */

namespace CF_Images\App\Api;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The R2 API class.
 *
 * @since 1.9.5
 */
class R2 {
	/**
	 * S3 client instance.
	 *
	 * @since 1.9.5
	 * @var S3Client $client
	 */
	private $client;

	/**
	 * Bucket name.
	 *
	 * @since 1.9.5
	 * @var string $bucket_name
	 */
	private $bucket_name = '';

	/**
	 * Class constructor.
	 *
	 * @since 1.9.5
	 */
	public function __construct() {
		// Check if AWS SDK is available.
		if ( ! class_exists( '\Aws\S3\S3Client' ) ) {
			do_action( 'cf_images_log', 'AWS SDK not available' );
			throw new Exception( 'AWS SDK not available' );
		}

		// Get bucket name.
		if ( defined( 'CF_IMAGES_R2_BUCKET' ) ) {
			$this->bucket_name = constant( 'CF_IMAGES_R2_BUCKET' );
		} else {
			$this->bucket_name = get_site_option( 'cf-images-r2-bucket' );
		}

		// Check if bucket name is set.
		if ( empty( $this->bucket_name ) ) {
			do_action( 'cf_images_log', 'R2 bucket name not set' );
			throw new Exception( 'R2 bucket name not set' );
		}

		// Initialize S3 client.
		$this->client = $this->get_s3_client();
	}

	/**
	 * Get S3 client instance.
	 *
	 * @since 1.9.5
	 *
	 * @return S3Client
	 */
	private function get_s3_client(): S3Client {
		// Get account ID for endpoint URL.
		$account_id = '';
		if ( defined( 'CF_IMAGES_ACCOUNT_ID' ) ) {
			$account_id = constant( 'CF_IMAGES_ACCOUNT_ID' );
		} else {
			$account_id = get_site_option( 'cf-images-account-id' );
		}

		// Get R2 access keys.
		if ( defined( 'CF_IMAGES_R2_KEY_ID' ) && defined( 'CF_IMAGES_R2_KEY_SECRET' ) ) {
			$access_key_id     = constant( 'CF_IMAGES_R2_KEY_ID' );
			$access_key_secret = constant( 'CF_IMAGES_R2_KEY_SECRET' );
		} else {
			$access_key_id     = get_site_option( 'cf-images-r2-key-id', '' );
			$access_key_secret = get_site_option( 'cf-images-r2-key-secret', '' );
		}

		// Check if we have valid credentials.
		if ( empty( $access_key_id ) || empty( $access_key_secret ) ) {
			do_action( 'cf_images_log', 'R2 API keys not set. Cannot initialize S3 client.' );
			throw new Exception( 'R2 API keys not set' );
		}

		// Create credentials.
		$credentials = new Credentials( $access_key_id, $access_key_secret );

		// Create S3 client.
		return new S3Client(
			array(
				'region'                  => 'auto',
				'endpoint'                => "https://$account_id.r2.cloudflarestorage.com",
				'version'                 => 'latest',
				'credentials'             => $credentials,
				'use_path_style_endpoint' => true, // Add these options to ensure compatibility with R2.
			)
		);
	}

	/**
	 * Upload object to R2.
	 *
	 * @since 1.9.5
	 *
	 * @param string $file_path    Path to the file to upload.
	 * @param string $object_name  Name of the object in R2.
	 * @param string $content_type Content type of the file.
	 *
	 * @return array
	 */
	public function upload_object( string $file_path, string $object_name, string $content_type = 'image/jpeg' ): array {
		try {
			$result = $this->client->putObject(
				array(
					'Bucket'      => $this->bucket_name,
					'Key'         => $object_name,
					'SourceFile'  => $file_path,
					'ContentType' => $content_type,
				)
			);

			do_action( 'cf_images_log', 'Successfully uploaded object to R2: ' . $object_name );

			return array(
				'success' => true,
				'data'    => $result,
			);
		} catch ( Exception $e ) {
			do_action( 'cf_images_log', 'Failed to upload object to R2: ' . $e->getMessage() );

			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Delete object from R2.
	 *
	 * @since 1.9.5
	 *
	 * @param string $object_name Name of the object in R2.
	 *
	 * @return array
	 */
	public function delete_object( string $object_name ): array {
		try {
			$result = $this->client->deleteObject(
				array(
					'Bucket' => $this->bucket_name,
					'Key'    => $object_name,
				)
			);

			do_action( 'cf_images_log', 'Successfully deleted object from R2: ' . $object_name );

			return array(
				'success' => true,
				'data'    => $result,
			);
		} catch ( Exception $e ) {
			do_action( 'cf_images_log', 'Failed to delete object from R2: ' . $e->getMessage() );

			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}
}
