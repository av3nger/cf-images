<?php
/**
 * Base unit test class
 *
 * @package Cf_Images
 */

/**
 * Class Unit_Test_Base.
 */
class Unit_Test_Base extends WP_UnitTestCase {
	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	protected static $attachment_id;

	/**
	 * Runs the routine before setting up all tests
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		self::$attachment_id = self::factory()->attachment->create_upload_object( __DIR__ . '/assets/test-image.jpg' );
	}

	/**
	 * Runs the routine after all tests have been run.
	 */
	public static function tear_down_after_class() {
		wp_delete_attachment( self::$attachment_id, true );
		delete_post_meta( self::$attachment_id, '_cloudflare_image_id' ); // Just in case.
		delete_site_option( 'cf-images-hash' );

		parent::tear_down_after_class();
	}

	/**
	 * Get original image object.
	 *
	 * @param string $size WordPress attachment size.
	 *
	 * @return array
	 */
	protected function get_original_image_object( string $size = 'medium' ): array {
		$year  = gmdate( 'Y' );
		$month = gmdate( 'm' );

		switch ( $size ) {
			case 'thumbnail':
				$image = array( "http://example.org/wp-content/uploads/$year/$month/test-image-150x150.jpg", 150, 150, true );
				break;
			case 'large':
				$image = array( "http://example.org/wp-content/uploads/$year/$month/test-image-1024x683.jpg", 1024, 1024, false );
				break;
			case 'scaled':
				$image = array( "http://example.org/wp-content/uploads/$year/$month/test-image-scaled.jpg", 2560, 0, false );
				break;
			case 'full':
			case 'original':
				$image = array( "http://example.org/wp-content/uploads/$year/$month/test-image.jpg", 2400, 1600, false );
				break;
			case 'medium':
			default:
				$image = array( "http://example.org/wp-content/uploads/$year/$month/test-image-300x200.jpg", 300, 200, true );
				break;
		}

		return $image;
	}

	/**
	 * Add Cloudflare image ID and hash.
	 */
	protected function add_cf_image_id_and_hash() {
		add_post_meta( self::$attachment_id, '_cloudflare_image_id', 'CF_IMAGE_ID' );
		update_site_option( 'cf-images-hash', 'CF_IMAGES_HASH' );
	}
}
