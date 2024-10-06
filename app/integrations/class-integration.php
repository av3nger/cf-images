<?php
/**
 * Base integration functionality
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.3
 */

namespace CF_Images\App\Integrations;

use CF_Images\App\Traits;
use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Integration class.
 *
 * @since 1.9.3
 */
abstract class Integration {
	use Traits\Ajax;

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Integration slug.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Class constructor.
	 *
	 * @since 1.9.3
	 * @throws Exception If the integration slug is not defined.
	 */
	public function __construct() {
		if ( ! $this->should_run() ) {
			return;
		}

		$this->init();

		if ( empty( $this->slug ) || empty( $this->name ) ) {
			throw new Exception( 'Integration slug or name is not defined.' );
		}

		add_filter( 'cf_images_i10n', array( $this, 'add_integration' ) );
		add_filter( 'cf_images_integration_options', array( $this, 'integration_options' ), 10, 2 );
		add_filter( 'cf_images_integration_option_value', array( $this, 'integration_option_value' ), 10, 2 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_update_integrations', array( $this, 'ajax_update_integrations' ) );
		}
	}

	/**
	 * Check if the integration should run.
	 *
	 * This will usually be a check if a plugin is active, or a certain class/hook exists.
	 *
	 * @since 1.9.3
	 *
	 * @return bool
	 */
	abstract protected function should_run(): bool;

	/**
	 * Define the variables for the integration.
	 *
	 * @since 1.9.3
	 */
	abstract protected function init();

	/**
	 * Define the integration options.
	 *
	 * @since 1.9.3
	 *
	 * @param array  $options Integration options.
	 * @param string $slug    Integration slug.
	 *
	 * @return array
	 */
	abstract public function integration_options( array $options, string $slug ): array;

	/**
	 * Add the integration data to the plugin's i10n array.
	 *
	 * @since 1.9.3
	 *
	 * @param array $i10n Current i10n array.
	 *
	 * @return array
	 */
	public function add_integration( array $i10n ): array {
		$i10n['integrationData'][ $this->slug ] = array(
			'name'    => $this->name,
			'options' => apply_filters( 'cf_images_integration_options', array(), $this->slug ),
		);

		return $i10n;
	}

	/**
	 * Get the value for the integration option.
	 *
	 * @since 1.9.3
	 *
	 * @param bool   $fallback    Fallback value.
	 * @param string $option_name Option name.
	 *
	 * @return bool
	 */
	public function integration_option_value( bool $fallback, string $option_name ): bool {
		$options = get_option( 'cf-images-integrations', array() );

		return $options[ $this->slug ][ $option_name ] ?? $fallback;
	}

	/**
	 * Update integrations settings.
	 *
	 * @since 1.9.3
	 */
	public function ajax_update_integrations() {
		$this->check_ajax_request();

		$data     = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$settings = get_option( 'cf-images-integrations', array() );

		foreach ( $data as $slug => $options ) {
			$integration = apply_filters( 'cf_images_integration_options', array(), $slug );
			if ( empty( $integration ) || empty( $options['options'] ) ) {
				continue;
			}

			foreach ( $options['options'] as $option ) {
				if ( empty( $option['name'] ) || ! isset( $option['value'] ) ) {
					continue;
				}

				$settings[ $slug ][ $option['name'] ] = filter_var( $option['value'], FILTER_VALIDATE_BOOLEAN );
			}
		}

		update_option( 'cf-images-integrations', $settings, false );
		wp_send_json_success();
	}
}
