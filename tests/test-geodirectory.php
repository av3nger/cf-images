<?php /* phpcs:ignore WordPress.Files.FileName.InvalidClassFileName */
/**
 * Tests for GeoDirectory integration (frontend methods).
 *
 * @package Cf_Images
 */

use CF_Images\App\Integrations\Geodirectory;

/**
 * Class Test_Geodirectory.
 */
class Test_Geodirectory extends Unit_Test_Base {
	/**
	 * Geodirectory integration instance.
	 *
	 * @var Geodirectory
	 */
	private $geodir;

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		// Reset the static URL-to-CF map between tests.
		$ref = new ReflectionProperty( Geodirectory::class, 'url_cf_map' );
		$ref->setAccessible( true );
		$ref->setValue( null, array() );

		$this->geodir = new Geodirectory();
	}

	/**
	 * Test: resolve_image_sources() skips non-GeoDirectory images.
	 *
	 * @covers Geodirectory::resolve_image_sources()
	 */
	public function test_resolve_skips_non_geodir_images() {
		$sources = array(
			'src'    => 'http://example.org/wp-content/uploads/photo.jpg',
			'srcset' => '',
		);

		$image_dom = '<img src="http://example.org/wp-content/uploads/photo.jpg" class="wp-image-1" />';

		$result = $this->geodir->resolve_image_sources( $sources, $image_dom );

		$this->assertSame( $sources, $result, 'Non-GeoDirectory images should be returned unchanged.' );
	}

	/**
	 * Test: resolve_image_sources() caches URL for a GeoDirectory image with meta.
	 *
	 * @covers Geodirectory::resolve_image_sources()
	 */
	public function test_resolve_caches_url_for_geodir_image() {
		$post_id = self::factory()->post->create();
		$gd_id   = 42;
		$cf_id   = 'cf-geodir-test-id';

		update_post_meta( $post_id, Geodirectory::META_KEY, array( $gd_id => $cf_id ) );

		// Set the global post so get_the_ID() works.
		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$src     = 'http://example.org/wp-content/uploads/geodir/photo.jpg';
		$sources = array(
			'src'    => $src,
			'srcset' => '',
		);

		$image_dom = '<img src="' . $src . '" class="geodir-image-' . $gd_id . '" />';

		$result = $this->geodir->resolve_image_sources( $sources, $image_dom );

		$this->assertSame( $sources, $result, 'Sources should be returned as-is (caching happens internally).' );

		// Now verify the cache was populated by calling resolve_external_image_id.
		$resolved = $this->geodir->resolve_external_image_id( '', $src, $src );
		$this->assertSame( $cf_id, $resolved, 'The URL-to-CF-ID cache should be populated after resolve_image_sources().' );

		// Cleanup.
		unset( $GLOBALS['post'] );
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test: resolve_image_sources() returns sources unchanged when no meta exists.
	 *
	 * @covers Geodirectory::resolve_image_sources()
	 */
	public function test_resolve_returns_sources_unchanged_when_no_meta() {
		$post_id = self::factory()->post->create();

		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$sources = array(
			'src'    => 'http://example.org/wp-content/uploads/geodir/no-meta.jpg',
			'srcset' => '',
		);

		$image_dom = '<img src="http://example.org/wp-content/uploads/geodir/no-meta.jpg" class="geodir-image-99" />';

		$result = $this->geodir->resolve_image_sources( $sources, $image_dom );

		$this->assertSame( $sources, $result, 'Sources should be unchanged when no GeoDirectory meta exists.' );

		unset( $GLOBALS['post'] );
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test: resolve_external_image_id() returns cached CF ID for a known URL.
	 *
	 * @covers Geodirectory::resolve_external_image_id()
	 */
	public function test_returns_cached_cf_id_for_known_url() {
		$post_id = self::factory()->post->create();
		$gd_id   = 10;
		$cf_id   = 'cf-known-id';
		$src     = 'http://example.org/wp-content/uploads/geodir/known.jpg';

		update_post_meta( $post_id, Geodirectory::META_KEY, array( $gd_id => $cf_id ) );
		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Prime the cache.
		$this->geodir->resolve_image_sources(
			array(
				'src'    => $src,
				'srcset' => '',
			),
			'<img src="' . $src . '" class="geodir-image-' . $gd_id . '" />'
		);

		$result = $this->geodir->resolve_external_image_id( '', $src, $src );
		$this->assertSame( $cf_id, $result, 'Should return cached CF ID for a known URL.' );

		unset( $GLOBALS['post'] );
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test: resolve_external_image_id() returns empty for unknown URL.
	 *
	 * @covers Geodirectory::resolve_external_image_id()
	 */
	public function test_returns_empty_for_unknown_url() {
		$result = $this->geodir->resolve_external_image_id( '', 'http://example.org/unknown.jpg', 'http://example.org/unknown.jpg' );
		$this->assertSame( '', $result, 'Should return empty string for an unknown URL.' );
	}

	/**
	 * Test: resolve_external_image_id() preserves an existing CF ID.
	 *
	 * @covers Geodirectory::resolve_external_image_id()
	 */
	public function test_preserves_existing_cf_id() {
		$existing = 'already-resolved-cf-id';

		$result = $this->geodir->resolve_external_image_id( $existing, 'http://example.org/any.jpg', 'http://example.org/any.jpg' );
		$this->assertSame( $existing, $result, 'Should preserve an existing CF ID without overwriting.' );
	}

	/**
	 * Test: add_wp_query_args() returns args unchanged when GeoDirectory functions are not available.
	 *
	 * @covers Geodirectory::add_wp_query_args()
	 */
	public function test_add_wp_query_args_returns_unchanged_without_geodir() {
		$args = array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
		);

		$result = $this->geodir->add_wp_query_args( $args, 'upload' );

		$this->assertSame( $args, $result, 'Args should be unchanged when geodir_get_posttypes() is not available.' );
	}

	/**
	 * Test: add_wp_query_args() returns args unchanged for non-bulk actions.
	 *
	 * @covers Geodirectory::add_wp_query_args()
	 */
	public function test_add_wp_query_args_skips_non_bulk_actions() {
		$args = array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
		);

		$result = $this->geodir->add_wp_query_args( $args, 'compress' );

		$this->assertSame( $args, $result, 'Args should be unchanged for non-upload/remove actions.' );
	}

	/**
	 * Test: bulk_process_post() returns false for non-GeoDirectory post types.
	 *
	 * @covers Geodirectory::bulk_process_post()
	 */
	public function test_bulk_process_post_returns_false_for_non_geodir() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$post    = get_post( $post_id );

		$result = $this->geodir->bulk_process_post( false, $post, 'upload' );

		$this->assertFalse( $result, 'Should return false for non-GeoDirectory post types.' );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test: bulk_process_post() preserves already-handled state.
	 *
	 * @covers Geodirectory::bulk_process_post()
	 */
	public function test_bulk_process_post_preserves_handled() {
		$post_id = self::factory()->post->create();
		$post    = get_post( $post_id );

		$result = $this->geodir->bulk_process_post( true, $post, 'upload' );

		$this->assertTrue( $result, 'Should return true when already handled by another integration.' );

		wp_delete_post( $post_id, true );
	}
}
