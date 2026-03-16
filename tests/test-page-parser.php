<?php /* phpcs:ignore WordPress.Files.FileName.InvalidClassFileName */
/**
 * Tests for Page Parser module.
 *
 * @package Cf_Images
 */

use CF_Images\App\Modules\Page_Parser;

/**
 * Class Test_Page_Parser.
 */
class Test_Page_Parser extends Unit_Test_Base {
	/**
	 * Page Parser instance.
	 *
	 * @var Page_Parser
	 */
	private $parser;

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->parser = new Page_Parser( 'page-parser' );
	}

	/**
	 * Wrap image HTML in a minimal page body so get_images() can find it.
	 *
	 * @param string $img Image HTML.
	 *
	 * @return string
	 */
	private function wrap_in_body( string $img ): string {
		return '<html><body>' . $img . '</body></html>';
	}

	/**
	 * Test: data-src is promoted when src is empty.
	 *
	 * @covers Page_Parser::replace_images()
	 */
	public function test_data_src_promoted_when_src_empty() {
		$captured = null;

		add_filter(
			'cf_images_page_parser_sources',
			function ( $sources ) use ( &$captured ) {
				$captured = $sources;
				return $sources;
			},
			5
		);

		$buffer = $this->wrap_in_body(
			'<img src="" data-src="http://example.org/wp-content/uploads/lazy.jpg" />'
		);

		$this->parser->replace_images( $buffer );

		$this->assertNotNull( $captured, 'Filter should have fired.' );
		$this->assertSame(
			'http://example.org/wp-content/uploads/lazy.jpg',
			$captured['src'],
			'data-src should be promoted to src when src is empty.'
		);
	}

	/**
	 * Test: data-src is promoted when src is a data URI.
	 *
	 * @covers Page_Parser::replace_images()
	 */
	public function test_data_src_promoted_when_src_is_data_uri() {
		$captured = null;

		add_filter(
			'cf_images_page_parser_sources',
			function ( $sources ) use ( &$captured ) {
				$captured = $sources;
				return $sources;
			},
			5
		);

		$buffer = $this->wrap_in_body(
			'<img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="http://example.org/wp-content/uploads/lazy.jpg" />'
		);

		$this->parser->replace_images( $buffer );

		$this->assertNotNull( $captured, 'Filter should have fired.' );
		$this->assertSame(
			'http://example.org/wp-content/uploads/lazy.jpg',
			$captured['src'],
			'data-src should be promoted to src when src is a data URI.'
		);
	}

	/**
	 * Test: data-src is NOT promoted when src is already present.
	 *
	 * @covers Page_Parser::replace_images()
	 */
	public function test_data_src_not_promoted_when_src_present() {
		$captured = null;

		add_filter(
			'cf_images_page_parser_sources',
			function ( $sources ) use ( &$captured ) {
				$captured = $sources;
				return $sources;
			},
			5
		);

		$real_src = 'http://example.org/wp-content/uploads/real.jpg';
		$buffer   = $this->wrap_in_body(
			'<img src="' . $real_src . '" data-src="http://example.org/wp-content/uploads/lazy.jpg" />'
		);

		$this->parser->replace_images( $buffer );

		$this->assertNotNull( $captured, 'Filter should have fired.' );
		$this->assertSame( $real_src, $captured['src'], 'src should remain unchanged when it has a real value.' );
	}

	/**
	 * Test: data-srcset is promoted when srcset is empty.
	 *
	 * @covers Page_Parser::replace_images()
	 */
	public function test_data_srcset_promoted_when_srcset_empty() {
		$captured = null;

		add_filter(
			'cf_images_page_parser_sources',
			function ( $sources ) use ( &$captured ) {
				$captured = $sources;
				return $sources;
			},
			5
		);

		$buffer = $this->wrap_in_body(
			'<img src="http://example.org/wp-content/uploads/img.jpg" data-srcset="http://example.org/wp-content/uploads/img-300x200.jpg 300w" />'
		);

		$this->parser->replace_images( $buffer );

		$this->assertNotNull( $captured, 'Filter should have fired.' );
		$this->assertSame(
			'http://example.org/wp-content/uploads/img-300x200.jpg 300w',
			$captured['srcset'],
			'data-srcset should be promoted to srcset when srcset is empty.'
		);
	}

	/**
	 * Test: data-srcset is NOT promoted when srcset is already present.
	 *
	 * @covers Page_Parser::replace_images()
	 */
	public function test_data_srcset_not_promoted_when_srcset_present() {
		$captured = null;

		add_filter(
			'cf_images_page_parser_sources',
			function ( $sources ) use ( &$captured ) {
				$captured = $sources;
				return $sources;
			},
			5
		);

		$real_srcset = 'http://example.org/wp-content/uploads/img-300x200.jpg 300w';
		$buffer      = $this->wrap_in_body(
			'<img src="http://example.org/wp-content/uploads/img.jpg" srcset="' . $real_srcset . '" data-srcset="http://example.org/wp-content/uploads/img-150x100.jpg 150w" />'
		);

		$this->parser->replace_images( $buffer );

		$this->assertNotNull( $captured, 'Filter should have fired.' );
		$this->assertSame( $real_srcset, $captured['srcset'], 'srcset should remain unchanged when it has a real value.' );
	}

	/**
	 * Test: cf_images_page_parser_sources filter receives correct arguments.
	 *
	 * @covers Page_Parser::replace_images()
	 */
	public function test_page_parser_sources_filter_receives_correct_args() {
		$captured_sources   = null;
		$captured_image_dom = null;

		add_filter(
			'cf_images_page_parser_sources',
			function ( $sources, $image_dom ) use ( &$captured_sources, &$captured_image_dom ) {
				$captured_sources   = $sources;
				$captured_image_dom = $image_dom;
				return $sources;
			},
			5,
			2
		);

		$src    = 'http://example.org/wp-content/uploads/photo.jpg';
		$srcset = 'http://example.org/wp-content/uploads/photo-300x200.jpg 300w';
		$img    = '<img src="' . $src . '" srcset="' . $srcset . '" alt="test" />';
		$buffer = $this->wrap_in_body( $img );

		$this->parser->replace_images( $buffer );

		$this->assertNotNull( $captured_sources, 'Filter should have fired.' );
		$this->assertSame( $src, $captured_sources['src'], 'Filter should receive the correct src.' );
		$this->assertSame( $srcset, $captured_sources['srcset'], 'Filter should receive the correct srcset.' );
		$this->assertSame( $img, $captured_image_dom, 'Filter should receive the full image DOM string.' );
	}

	/**
	 * Test: cf_images_page_parser_sources filter can override src.
	 *
	 * @covers Page_Parser::replace_images()
	 */
	public function test_page_parser_sources_filter_can_override_src() {
		$override_src = 'http://example.org/wp-content/uploads/override.jpg';

		add_filter(
			'cf_images_page_parser_sources',
			function ( $sources ) use ( $override_src ) {
				$sources['src'] = $override_src;
				return $sources;
			},
			5
		);

		// Return a CF ID for the overridden URL so it gets processed.
		add_filter(
			'cf_images_external_image_id',
			function ( $cf_image_id, $original ) use ( $override_src ) {
				if ( $original === $override_src ) {
					return 'OVERRIDE_CF_ID';
				}
				return $cf_image_id;
			},
			10,
			2
		);

		update_site_option( 'cf-images-hash', 'CF_IMAGES_HASH' );

		$buffer = $this->wrap_in_body(
			'<img src="http://example.org/wp-content/uploads/original.jpg" alt="test" />'
		);

		$result = $this->parser->replace_images( $buffer );

		$this->assertStringContainsString( 'OVERRIDE_CF_ID', $result, 'Overridden src should be used for CF image lookup.' );
	}
}
