<?php /* phpcs:ignore WordPress.Files.FileName.InvalidClassFileName */
/**
 * Test cases for the Cloudflare_Images module
 *
 * @package Cf_Images
 */

use CF_Images\App\Modules\Cloudflare_Images;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class Test_Cloudflare_Images.
 */
class Test_Cloudflare_Images extends Unit_Test_Base {
	/**
	 * Cloudflare Images module.
	 *
	 * @var Cloudflare_Images|MockObject
	 */
	protected $cf_images;

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		// Instantiate the module.
		$this->cf_images = $this->getMockBuilder( Cloudflare_Images::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_run', 'is_module_enabled' ) )
			->getMock();

		$this->cf_images->init();

		remove_filter( 'wp_get_attachment_url', array( $this->cf_images, 'get_attachment_url' ) );

		$this->cf_images
			->expects( $this->once() )
			->method( 'can_run' )
			->willReturn( true );
	}

	/**
	 * Test: Returns the original image if `can_run()` is false.
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_returns_original_if_can_run_is_false() {
		$original = $this->get_original_image_object();

		// Force `can_run()` to return false.
		$this->cf_images
			->expects( $this->once() )
			->method( 'can_run' )
			->willReturn( false );

		$image = wp_get_attachment_image_src( self::$attachment_id, 'medium' );
		$this->assertSame( $original, $image, 'Should return original image if can_run() is false.' );
	}

	/**
	 * Test: Returns original if Cloudflare image ID is missing.
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_returns_original_if_missing_cloudflare_image_id() {
		$original = $this->get_original_image_object();

		$image = wp_get_attachment_image_src( self::$attachment_id, 'medium' );
		$this->assertSame( $original, $image, 'Should return original image if Cloudflare image ID is missing.' );
	}

	/**
	 * Test: Returns original if Cloudflare hash is missing.
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_returns_original_if_missing_cloudflare_hash() {
		$original = $this->get_original_image_object();

		add_post_meta( self::$attachment_id, '_cloudflare_image_id', 'CF_IMAGE_ID' );

		$image = wp_get_attachment_image_src( self::$attachment_id, 'medium' );
		$this->assertSame( $original, $image, 'Should return original image if Cloudflare hash is missing.' );
	}

	/**
	 * Test: Known crop image with a named size.
	 *
	 * @see Cloudflare_Images::$registered_sizes
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_known_crop_image_size() {
		$this->cf_images->populate_image_sizes();
		$this->add_cf_image_id_and_hash();

		$image = wp_get_attachment_image_src( self::$attachment_id ); // Default thumbnail size is cropped 150x150.
		$this->assertStringEndsWith( 'w=150,h=150,fit=crop', $image[0], 'Should return offloaded image.' );
	}

	/**
	 * Test: Image with defined dimensions [width, height].
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_defined_dimensions() {
		$this->add_cf_image_id_and_hash();

		$image = wp_get_attachment_image_src( self::$attachment_id, 'medium' );
		$this->assertStringEndsWith( 'w=300,h=200', $image[0], 'Should return offloaded image.' );
	}

	/**
	 * Test: Image with `-<width>x<height>` in the filename.
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_variant_image_filename() {
		$original = $this->get_original_image_object( 'large' );

		$this->cf_images->populate_image_sizes();
		$this->add_cf_image_id_and_hash();

		// Pass in just an image link without the sizes.
		$image = $this->cf_images->get_attachment_image_src( array( $original[0] ), self::$attachment_id, null );
		$this->assertStringEndsWith( 'w=1024,h=683', $image[0], 'Should detect file suffix from file name.' );
	}

	/**
	 * Test: Image with `-<width>x<height>` in the filename, and width = height.
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_variant_image_filename_with_crop() {
		$original = $this->get_original_image_object( 'thumbnail' );

		$this->cf_images->populate_image_sizes();
		$this->add_cf_image_id_and_hash();

		// Pass in just an image link without the sizes.
		$image = $this->cf_images->get_attachment_image_src( array( $original[0] ), self::$attachment_id, null );
		$this->assertStringEndsWith(
			'w=150,h=150,fit=crop',
			$image[0],
			'Should detect file suffix and apply cropping if matched in arrays.'
		);
	}

	/**
	 * Test: `$size` is an integer => /w=$size
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_size_is_int() {
		$original = $this->get_original_image_object( 'original' );

		$this->add_cf_image_id_and_hash();

		$image = $this->cf_images->get_attachment_image_src( array( $original[0] ), self::$attachment_id, 2400 );
		$this->assertStringEndsWith( '/w=2400', $image[0], 'Should add /w=2400 if $size is an integer.' );
	}

	/**
	 * Test: `-scaled` image handling.
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_scaled_image_handling() {
		$original = $this->get_original_image_object( 'scaled' );

		$this->add_cf_image_id_and_hash();

		$image = $this->cf_images->get_attachment_image_src( array( $original[0] ), self::$attachment_id, null );
		$this->assertStringEndsWith( '/w=2560', $image[0], 'Should add /w=2560 if image is scaled.' );
	}

	/**
	 * Test: No size prefix, no $image[1], => default to /w=9999
	 *
	 * @covers Cloudflare_Images::get_attachment_image_src()
	 */
	public function test_no_size_prefix_and_no_width_property() {
		$original = $this->get_original_image_object( 'original' );

		$this->add_cf_image_id_and_hash();

		$image = $this->cf_images->get_attachment_image_src( array( $original[0] ), self::$attachment_id, null );
		$this->assertStringContainsString( '/w=9999', $image[0], 'Should default to /w=9999 if no size is found.' );
	}
}
