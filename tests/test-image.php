<?php /* phpcs:ignore WordPress.Files.FileName.InvalidClassFileName */
/**
 * Tests for Image module.
 *
 * @package Cf_Images
 */

use CF_Images\App\Image;

/**
 * Class Image_Test.
 */
class Test_Image extends Unit_Test_Base {
	/**
	 * Test: basic image replacement.
	 *
	 * @covers Image::generate_url()
	 */
	public function test_generate_url() {
		$original = $this->get_original_image_object();
		$this->add_cf_image_id_and_hash();

		$image_html = '<img src="' . $original[0] . '" />';
		$image      = new Image( $image_html, $original[0], '' );

		$processed = '<img src="https://imagedelivery.net/CF_IMAGES_HASH/CF_IMAGE_ID/w=300" class="wp-image-' . self::$attachment_id . '" />';

		$this->assertSame( self::$attachment_id, $image->get_id(), 'Expected attachment ID match the detected ID.' );
		$this->assertSame( $original[0], $image->get_src(), 'get_src() should return the original src.' );
		$this->assertSame( '', $image->get_srcset(), 'get_srcset() should return the original srcset.' );
		$this->assertSame( $processed, $image->get_processed(), 'Processed image should contain the Cloudflare image.' );
		$this->assertFalse( $image->is_source_tag(), 'Should not be recognized as a <source> tag.' );
	}

	/**
	 * Test: attachment ID from image class.
	 */
	public function test_id_from_class_name() {
		$image_html = '<img src="https://example.com/wp-content/uploads/image/jpg" class="wp-image-25" alt="test">';

		$image = new Image( $image_html, 'https://example.com/wp-content/uploads/image/jpg', '' );

		$this->assertSame( 25, $image->get_id(), 'Expected attachment ID match the detected ID.' );
	}

	/**
	 * Test: scaled image processing.
	 */
	public function test_scaled_image() {
		$original = $this->get_original_image_object( 'scaled' );
		$this->add_cf_image_id_and_hash();

		$image_html = '<img src="' . $original[0] . '" />';
		$image      = new Image( $image_html, $original[0], '' );

		$this->assertStringContainsString( '/w=2560', $image->get_processed(), 'Processed image should contain the Cloudflare image.' );
	}

	/**
	 * Test: image processing with width and height tags.
	 */
	public function test_width_and_height_tags() {
		$original = $this->get_original_image_object( 'original' );
		$this->add_cf_image_id_and_hash();

		add_filter(
			'cf_images_module_enabled',
			function ( $value, $module ) {
				if ( 'smallest-size' !== $module ) {
					return $value;
				}

				return true;
			},
			10,
			2
		);

		$image_html = '<img src="' . $original[0] . '" width="300" />';
		$image      = new Image( $image_html, $original[0], '' );

		$this->assertStringContainsString( '/w=300', $image->get_processed(), 'Processed image should contain the Cloudflare image.' );
	}

	/**
	 * Test: image processing and auto crop.
	 */
	public function test_auto_crop() {
		$original = $this->get_original_image_object( 'original' );
		$this->add_cf_image_id_and_hash();

		add_filter(
			'cf_images_module_enabled',
			function ( $value, $module ) {
				if ( 'auto-crop' === $module || 'smallest-size' === $module ) {
					return true;
				}

				return $value;
			},
			10,
			2
		);

		$image_html = '<img src="' . $original[0] . '" width="350" height="350" />';
		$image      = new Image( $image_html, $original[0], '' );

		$this->assertStringContainsString( '/w=350,h=350,fit=crop', $image->get_processed(), 'Processed image should contain the Cloudflare image.' );
	}

	/**
	 * Test: webp image with size suffix is normalized correctly.
	 *
	 * @covers Image::generate_url()
	 */
	public function test_webp_size_suffix_stripped() {
		$this->add_cf_image_id_and_hash();

		$year  = gmdate( 'Y' );
		$month = gmdate( 'm' );
		$src   = "http://example.org/wp-content/uploads/$year/$month/test-image-300x200.webp";

		$image_html = '<img src="' . $src . '" class="wp-image-' . self::$attachment_id . '" />';
		$image      = new Image( $image_html, $src, '' );

		$this->assertStringContainsString( 'imagedelivery.net', $image->get_processed(), 'Webp image with size suffix should be processed.' );
		$this->assertStringContainsString( '/w=300', $image->get_processed(), 'Width should be extracted from the size suffix.' );
	}

	/**
	 * Test: avif image with size suffix is normalized correctly.
	 *
	 * @covers Image::generate_url()
	 */
	public function test_avif_size_suffix_stripped() {
		$this->add_cf_image_id_and_hash();

		$year  = gmdate( 'Y' );
		$month = gmdate( 'm' );
		$src   = "http://example.org/wp-content/uploads/$year/$month/test-image-400x300.avif";

		$image_html = '<img src="' . $src . '" class="wp-image-' . self::$attachment_id . '" />';
		$image      = new Image( $image_html, $src, '' );

		$this->assertStringContainsString( 'imagedelivery.net', $image->get_processed(), 'Avif image with size suffix should be processed.' );
		$this->assertStringContainsString( '/w=400', $image->get_processed(), 'Width should be extracted from the size suffix.' );
	}

	/**
	 * Test: external image resolved via cf_images_external_image_id filter.
	 *
	 * @covers Image::generate_url()
	 */
	public function test_external_image_resolved_via_filter() {
		update_site_option( 'cf-images-hash', 'CF_IMAGES_HASH' );

		$src = 'http://example.org/wp-content/uploads/external/photo.jpg';

		add_filter(
			'cf_images_external_image_id',
			function ( $cf_image_id, $original ) use ( $src ) {
				if ( $original === $src ) {
					return 'EXTERNAL_CF_ID';
				}
				return $cf_image_id;
			},
			10,
			2
		);

		$image_html = '<img src="' . $src . '" alt="external" />';
		$image      = new Image( $image_html, $src, '' );

		$this->assertStringContainsString( 'EXTERNAL_CF_ID', $image->get_processed(), 'External image should use the CF ID from the filter.' );
		$this->assertStringContainsString( 'imagedelivery.net', $image->get_processed(), 'External image should use Cloudflare delivery URL.' );
	}

	/**
	 * Test: external image skipped when filter returns empty.
	 *
	 * @covers Image::generate_url()
	 */
	public function test_external_image_skipped_when_filter_returns_empty() {
		update_site_option( 'cf-images-hash', 'CF_IMAGES_HASH' );

		$src = 'http://example.org/wp-content/uploads/external/unknown.jpg';

		add_filter(
			'cf_images_external_image_id',
			function () {
				return '';
			}
		);

		$image_html = '<img src="' . $src . '" alt="unknown" />';
		$image      = new Image( $image_html, $src, '' );

		$this->assertStringNotContainsString( 'imagedelivery.net', $image->get_processed(), 'Image should be left unchanged when filter returns empty.' );
		$this->assertSame( $image_html, $image->get_processed(), 'Original HTML should be returned unchanged.' );
	}
}
