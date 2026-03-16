<?php
/**
 * Page parser
 *
 * Instead of replacing the images via hooks, replace images by parsing the page on the front-end.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.4.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Image;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Page_Parser class.
 *
 * @since 1.4.0
 */
class Page_Parser extends Module {
	/**
	 * Should the module only run on front-end?
	 *
	 * @since 1.4.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $only_frontend = true;

	/**
	 * Run everything regardless of module status.
	 *
	 * @since 1.8.0
	 */
	public function pre_init() {
		add_filter( 'cf_images_default_settings', array( $this, 'add_setting' ) );
	}

	/**
	 * Init the module.
	 *
	 * @since 1.4.0
	 */
	public function init() {
		if ( ! $this->can_offload() ) {
			return;
		}

		add_action( 'template_redirect', array( $this, 'output_buffering' ), 1 );
	}

	/**
	 * Turn on output buffering.
	 *
	 * @since 1.4.0
	 */
	public function output_buffering() {
		ob_start( array( $this, 'replace_images' ) );
	}

	/**
	 * Output buffer callback.
	 *
	 * @since 1.4.0
	 *
	 * @param string $buffer Contents of the output buffer.
	 *
	 * @return string
	 */
	public function replace_images( string $buffer ): string {
		$images = $this->get_images( $buffer );

		if ( empty( $images ) ) {
			return $buffer;
		}

		foreach ( $images[0] as $key => $image_dom ) {
			$src    = $images[1][ $key ];
			$srcset = $images[2][ $key ];

			// Promote data-src/data-srcset for lazy-loaded images.
			if ( empty( $src ) || str_starts_with( $src, 'data:' ) ) {
				if ( preg_match( '/\sdata-src=[\'"]([^\'"]+)[\'"]/', $image_dom, $ds ) ) {
					$src = $ds[1];
				}
			}

			if ( empty( $srcset ) && preg_match( '/\sdata-srcset=[\'"]([^\'"]+)[\'"]/', $image_dom, $dss ) ) {
				$srcset = $dss[1];
			}

			/**
			 * Allow integrations to resolve metadata for images before the Image class processes them.
			 *
			 * Integrations can use this filter to cache URL-to-Cloudflare-ID mappings
			 * or modify src/srcset values for images they manage.
			 *
			 * @since 1.9.9
			 *
			 * @param array  $sources {
			 *     @type string $src    Image src attribute value.
			 *     @type string $srcset Image srcset attribute value.
			 * }
			 * @param string $image_dom Original image DOM element string (unmodified).
			 */
			$sources = apply_filters( 'cf_images_page_parser_sources', compact( 'src', 'srcset' ), $image_dom );
			$src     = $sources['src'] ?? $src;
			$srcset  = $sources['srcset'] ?? $srcset;

			$image  = new Image( $image_dom, $src, $srcset );
			$buffer = str_replace( $images[0][ $key ], $image->get_processed(), $buffer );
		}

		return $buffer;
	}

	/**
	 * Get images from source code.
	 *
	 * Optimize this regex better than I did - I'll pay for your coffee.
	 *
	 * @since 1.4.0
	 *
	 * @param string $buffer Output buffer.
	 *
	 * @return array
	 */
	private function get_images( string $buffer ): array {
		if ( preg_match( '/(?=<body).*<\/body>/is', $buffer, $body ) ) {
			$pattern = '/<(?:img|source)\b(?>\s+(?:src=[\'"]([^\'"]*)[\'"]|srcset=[\'"]([^\'"]*)[\'"])|[^\s>]+|\s+)*>/i';
			if ( preg_match_all( $pattern, $body[0], $images ) ) {
				return $images;
			}
		}

		return array();
	}

	/**
	 * Add default option.
	 *
	 * @since 1.8.0
	 *
	 * @param array $defaults Default settings.
	 *
	 * @return array
	 */
	public function add_setting( array $defaults ): array {
		if ( ! isset( $defaults['smallest-size'] ) ) {
			$defaults['smallest-size'] = false;
		}

		if ( ! isset( $defaults['auto-crop'] ) ) {
			$defaults['auto-crop'] = false;
		}

		return $defaults;
	}
}
