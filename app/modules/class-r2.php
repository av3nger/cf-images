<?php
/**
 * Cloudflare R2 module
 *
 * This class defines all code necessary for offloading media to Cloudflare R2 object storage.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.5
 */

namespace CF_Images\App\Modules;

use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * R2 class.
 *
 * @since 1.9.5
 */
class R2 extends Module {
	/**
	 * This is a core module, meaning it can't be enabled/disabled via options.
	 *
	 * @since 1.9.5
	 *
	 * @var bool
	 */
	protected $core = true;

	/**
	 * Action names.
	 *
	 * @since 1.9.5
	 *
	 * @var array
	 */
	private $actions = array( 'r2-upload', 'r2-remove' );

	/**
	 * R2 public URL.
	 *
	 * @since 1.9.5
	 *
	 * @var string
	 */
	private $r2_public_url = '';

	/**
	 * Init the module.
	 *
	 * @since 1.9.5
	 */
	public function init() {
		$this->set_r2_public_url();

		add_filter( 'cf_images_bulk_actions', array( $this, 'add_bulk_action' ) );
		add_filter( 'cf_images_wp_query_args', array( $this, 'add_wp_query_args' ), 10, 2 );
		add_action( 'cf_images_bulk_step', array( $this, 'bulk_step' ), 10, 2 );

		// Add filter to replace Cloudflare Images URLs with R2 URLs.
		add_filter( 'cf_images_attachment_meta', array( $this, 'maybe_use_r2_url' ), 20, 2 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_r2_upload', array( $this, 'ajax_r2_upload' ) );
			add_action( 'wp_ajax_cf_images_r2_remove', array( $this, 'ajax_r2_remove' ) );
		}
	}

	/**
	 * Set the R2 public URL.
	 *
	 * @since 1.9.5
	 */
	private function set_r2_public_url() {
		if ( defined( 'CF_IMAGES_R2_PUBLIC_URL' ) ) {
			$this->r2_public_url = constant( 'CF_IMAGES_R2_PUBLIC_URL' );
		} else {
			$this->r2_public_url = get_site_option( 'cf-images-r2-public-url', '' );
		}

		// Ensure the URL ends with a trailing slash.
		if ( ! empty( $this->r2_public_url ) && substr( $this->r2_public_url, -1 ) !== '/' ) {
			$this->r2_public_url .= '/';
		}
	}

	/**
	 * Extend bulk action so that the AJAX callback accepts the bulk request.
	 *
	 * @since 1.9.5
	 * @see Media::ajax_bulk_process()
	 *
	 * @param array $actions Supported actions.
	 *
	 * @return array
	 */
	public function add_bulk_action( array $actions ): array {
		foreach ( $this->actions as $action ) {
			if ( ! in_array( $action, $actions, true ) ) {
				$actions[] = $action;
			}
		}

		return $actions;
	}

	/**
	 * Adjust the WP_Query args for bulk offload action.
	 *
	 * @since 1.9.5
	 * @see Ajax::get_wp_query_args()
	 *
	 * @param array  $args   WP_Query args.
	 * @param string $action Executing action.
	 *
	 * @return array
	 */
	public function add_wp_query_args( array $args, string $action ): array {
		if ( ! in_array( $action, $this->actions, true ) ) {
			return $args;
		}

		if ( 'r2-upload' === $action ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_cloudflare_image_r2_offloaded',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_cloudflare_image_id',
					'compare' => 'EXISTS',
				),
			);
		} elseif ( 'r2-remove' === $action ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_cloudflare_image_r2_offloaded',
					'compare' => 'EXISTS',
				),
			);
		}

		return $args;
	}

	/**
	 * Perform bulk step.
	 *
	 * @since 1.9.5
	 * @see Media::ajax_bulk_process()
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $action        Executing action.
	 */
	public function bulk_step( int $attachment_id, string $action ) {
		if ( 'r2-upload' === $action ) {
			$this->ajax_r2_upload( $attachment_id );
		} elseif ( 'r2-remove' === $action ) {
			$this->ajax_r2_remove( $attachment_id );
		}
	}

	/**
	 * Upload images to R2.
	 *
	 * @since 1.9.5
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function ajax_r2_upload( int $attachment_id = 0 ) {
		// Check if we have a valid R2 public URL.
		if ( empty( $this->r2_public_url ) ) {
			do_action( 'cf_images_log', 'R2 public URL is not set. Cannot upload to R2.' );
			wp_send_json_error( esc_html__( 'R2 public URL is not set', 'cloudflare-images' ) );
		}

		if ( ! $attachment_id ) {
			$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );
		}

		// Check if the attachment is already offloaded to R2.
		$r2_offloaded = get_post_meta( $attachment_id, '_cloudflare_image_r2_offloaded', true );
		if ( ! empty( $r2_offloaded ) ) {
			wp_send_json_success( esc_html__( 'Image already offloaded to R2', 'cloudflare-images' ) );
		}

		// Get the file path.
		$file_path = get_attached_file( $attachment_id );
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			do_action( 'cf_images_log', 'File not found: ' . $file_path );
			wp_send_json_error( esc_html__( 'File not found', 'cloudflare-images' ) );
		}

		// Get the file mime type.
		$mime_type = get_post_mime_type( $attachment_id );
		if ( empty( $mime_type ) ) {
			$mime_type = 'image/jpeg'; // Default to JPEG if mime type not found.
		}

		// Initialize R2 API.
		try {
			$r2_api         = new \CF_Images\App\Api\R2();
			$uploaded_files = array();
			$errors         = array();

			// Upload original image.
			$object_name = $this->get_r2_object_name( $attachment_id );
			$result      = $r2_api->upload_object( $file_path, $object_name, $mime_type );

			if ( ! $result || empty( $result['success'] ) ) {
				do_action( 'cf_images_log', 'Failed to upload original image to R2: ' . wp_json_encode( $result ) );
				$errors[] = 'Failed to upload original image';
			} else {
				$uploaded_files[] = $object_name;
			}

			// Upload all registered image sizes.
			$metadata = wp_get_attachment_metadata( $attachment_id );

			if ( ! empty( $metadata['sizes'] ) ) {
				$base_dir = dirname( $file_path ) . '/';

				foreach ( $metadata['sizes'] as $size_name => $size_data ) {
					if ( empty( $size_data['file'] ) ) {
						continue;
					}

					$size_file_path = $base_dir . $size_data['file'];

					if ( ! file_exists( $size_file_path ) ) {
						do_action( 'cf_images_log', 'Size file not found: ' . $size_file_path );
						continue;
					}

					// Create object name for the size.
					$size_object_name = dirname( $object_name ) . '/' . $size_data['file'];

					// Upload the size.
					$size_result = $r2_api->upload_object( $size_file_path, $size_object_name, $mime_type );

					if ( ! $size_result || empty( $size_result['success'] ) ) {
						do_action( 'cf_images_log', 'Failed to upload size ' . $size_name . ' to R2: ' . wp_json_encode( $size_result ) );
						$errors[] = 'Failed to upload size ' . $size_name;
					} else {
						$uploaded_files[] = $size_object_name;

						// Store the size object name in the metadata.
						$metadata['sizes'][ $size_name ]['r2_object_name'] = $size_object_name;
					}
				}

				// Update the attachment metadata with R2 object names for sizes.
				wp_update_attachment_metadata( $attachment_id, $metadata );
			}

			// If we have errors but also uploaded some files, we'll continue but log the errors.
			if ( ! empty( $errors ) && ! empty( $uploaded_files ) ) {
				do_action( 'cf_images_log', 'Some sizes failed to upload to R2: ' . implode( ', ', $errors ) );
			}

			// If we have no uploaded files, return an error.
			if ( empty( $uploaded_files ) ) {
				do_action( 'cf_images_log', 'Failed to upload any files to R2' );
				wp_send_json_error( esc_html__( 'Failed to upload to R2', 'cloudflare-images' ) );
			}

			// Store the R2 object name in the attachment metadata.
			update_post_meta( $attachment_id, '_cloudflare_image_r2_object_name', $object_name );
			update_post_meta( $attachment_id, '_cloudflare_image_r2_offloaded', time() );

			// Store the R2 URL in the Cloudflare image ID field with a special prefix
			// This will allow the Image class to recognize and handle R2 URLs.
			update_post_meta( $attachment_id, '_cloudflare_image_id', 'r2:' . $object_name );

			do_action( 'cf_images_log', 'Successfully uploaded to R2: ' . $object_name . ' and ' . ( count( $uploaded_files ) - 1 ) . ' additional sizes' );
			wp_send_json_success( esc_html__( 'Successfully uploaded to R2', 'cloudflare-images' ) );
		} catch ( Exception $e ) {
			do_action( 'cf_images_log', 'Exception when uploading to R2: ' . $e->getMessage() );
			wp_send_json_error( esc_html__( 'Failed to upload to R2: ', 'cloudflare-images' ) . $e->getMessage() );
		}
	}

	/**
	 * Remove images from R2.
	 *
	 * @since 1.9.5
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function ajax_r2_remove( int $attachment_id = 0 ) {
		if ( ! $attachment_id ) {
			$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );
		}

		// Check if the attachment is offloaded to R2.
		$object_name = get_post_meta( $attachment_id, '_cloudflare_image_r2_object_name', true );
		if ( empty( $object_name ) ) {
			wp_send_json_error( esc_html__( 'Image not found in R2', 'cloudflare-images' ) );
		}

		// Initialize R2 API.
		try {
			$r2_api        = new \CF_Images\App\Api\R2();
			$deleted_files = array();
			$errors        = array();

			// Delete original image.
			$result = $r2_api->delete_object( $object_name );

			if ( ! $result || empty( $result['success'] ) ) {
				do_action( 'cf_images_log', 'Failed to delete original image from R2: ' . wp_json_encode( $result ) );
				$errors[] = 'Failed to delete original image';
			} else {
				$deleted_files[] = $object_name;
			}

			// Delete all registered image sizes.
			$metadata = wp_get_attachment_metadata( $attachment_id );

			if ( ! empty( $metadata['sizes'] ) ) {
				foreach ( $metadata['sizes'] as $size_name => $size_data ) {
					if ( empty( $size_data['r2_object_name'] ) ) {
						// If we don't have an R2 object name for this size, try to construct it.
						if ( ! empty( $size_data['file'] ) ) {
							$size_data['r2_object_name'] = dirname( $object_name ) . '/' . $size_data['file'];
						} else {
							continue;
						}
					}

					// Delete the size.
					$size_result = $r2_api->delete_object( $size_data['r2_object_name'] );

					if ( ! $size_result || empty( $size_result['success'] ) ) {
						do_action( 'cf_images_log', 'Failed to delete size ' . $size_name . ' from R2: ' . wp_json_encode( $size_result ) );
						$errors[] = 'Failed to delete size ' . $size_name;
					} else {
						$deleted_files[] = $size_data['r2_object_name'];

						// Remove the R2 object name from the metadata.
						unset( $metadata['sizes'][ $size_name ]['r2_object_name'] );
					}
				}

				// Update the attachment metadata without R2 object names for sizes.
				wp_update_attachment_metadata( $attachment_id, $metadata );
			}

			// If we have errors but also deleted some files, we'll continue but log the errors.
			if ( ! empty( $errors ) && ! empty( $deleted_files ) ) {
				do_action( 'cf_images_log', 'Some sizes failed to delete from R2: ' . implode( ', ', $errors ) );
			}

			// Remove the R2 metadata.
			delete_post_meta( $attachment_id, '_cloudflare_image_r2_object_name' );
			delete_post_meta( $attachment_id, '_cloudflare_image_r2_offloaded' );

			// Check if the Cloudflare image ID has the R2 prefix and remove it.
			$cf_image_id = get_post_meta( $attachment_id, '_cloudflare_image_id', true );
			if ( ! empty( $cf_image_id ) && 0 === strpos( $cf_image_id, 'r2:' ) ) {
				delete_post_meta( $attachment_id, '_cloudflare_image_id' );
			}

			do_action( 'cf_images_log', 'Successfully deleted from R2: ' . $object_name . ' and ' . ( count( $deleted_files ) - 1 ) . ' additional sizes' );
			wp_send_json_success( esc_html__( 'Successfully deleted from R2', 'cloudflare-images' ) );
		} catch ( Exception $e ) {
			do_action( 'cf_images_log', 'Exception when deleting from R2: ' . $e->getMessage() );
			wp_send_json_error( esc_html__( 'Failed to delete from R2: ', 'cloudflare-images' ) . $e->getMessage() );
		}
	}

	/**
	 * Generate a unique object name for R2 storage.
	 *
	 * @since 1.9.5
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return string
	 */
	private function get_r2_object_name( int $attachment_id ): string {
		$file_path = get_attached_file( $attachment_id );
		$file_name = basename( $file_path );

		// Get the upload directory structure.
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];

		// Get the relative path from the upload directory.
		$relative_path = str_replace( $base_dir . '/', '', dirname( $file_path ) );

		// Combine the relative path with the filename.
		return trailingslashit( $relative_path ) . $file_name;
	}

	/**
	 * Check if we should use the R2 URL instead of Cloudflare Images URL.
	 *
	 * @since 1.9.5
	 *
	 * @param mixed $cloudflare_image_id Image meta.
	 * @param int   $attachment_id       Attachment ID.
	 *
	 * @return mixed
	 */
	public function maybe_use_r2_url( $cloudflare_image_id, int $attachment_id ) {
		// If R2 public URL is not set, return the original value.
		if ( empty( $this->r2_public_url ) ) {
			return $cloudflare_image_id;
		}

		// Check if the image is offloaded to R2.
		$r2_object_name = get_post_meta( $attachment_id, '_cloudflare_image_r2_offloaded', true );
		if ( empty( $r2_object_name ) ) {
			return $cloudflare_image_id;
		}

		// Return a special marker that will be recognized by the Image class.
		return 'r2:' . $r2_object_name;
	}
}
