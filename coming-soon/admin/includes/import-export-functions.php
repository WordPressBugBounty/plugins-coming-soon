<?php
/**
 * V2 Import/Export Functions
 *
 * These are wrapper functions for the existing import/export functionality
 * to work with the new WordPress-native admin system
 *
 * @package    SeedProd_Lite
 * @subpackage SeedProd_Lite/admin/includes
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include V2 utility functions
require_once __DIR__ . '/import-export-utilities.php';

/*
==============================================
	HELPER FUNCTIONS FOR IMPORT/EXPORT OPERATIONS
	============================================== */

/**
 * Validate AJAX request with nonce and permissions.
 *
 * @param string $capability The capability to check.
 */
function seedprod_lite_v2_validate_ajax_request( $capability = 'export' ) {
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Invalid security token', 'coming-soon' ) );
	}

	if ( ! current_user_can( apply_filters( 'seedprod_import_export', $capability ) ) ) {
		wp_send_json_error( __( 'Insufficient permissions', 'coming-soon' ) );
	}
}

/**
 * Initialize WordPress filesystem.
 *
 * @return object WP_Filesystem instance.
 */
function seedprod_lite_v2_init_filesystem() {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	// Force direct filesystem method for AJAX requests
	add_filter(
		'filesystem_method',
		function () {
			return 'direct';
		}
	);

	global $wp_filesystem;
	WP_Filesystem();

	return $wp_filesystem;
}

/**
 * Get upload error message.
 *
 * @param int $error_code The PHP upload error code.
 * @return string The error message.
 */
function seedprod_lite_v2_get_upload_error_message( $error_code ) {
	switch ( $error_code ) {
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			return __( 'File is too large', 'coming-soon' );
		case UPLOAD_ERR_PARTIAL:
			return __( 'File was only partially uploaded', 'coming-soon' );
		case UPLOAD_ERR_NO_FILE:
			return __( 'No file was uploaded', 'coming-soon' );
		default:
			return __( 'File upload failed', 'coming-soon' );
	}
}

/**
 * Validate ZIP file upload.
 *
 * @param string $file_key The $_FILES array key.
 * @return array File information or sends JSON error.
 */
function seedprod_lite_v2_validate_zip_upload( $file_key ) {
	// Check if file was uploaded.
	if ( ! isset( $_FILES[ $file_key ] ) ) {
		wp_send_json_error( __( 'No file uploaded', 'coming-soon' ) );
	}

	// Check for upload errors.
	if ( UPLOAD_ERR_OK !== $_FILES[ $file_key ]['error'] ) {
		$error_message = seedprod_lite_v2_get_upload_error_message( $_FILES[ $file_key ]['error'] );
		wp_send_json_error( $error_message );
	}

	$filename = wp_unslash( $_FILES[ $file_key ]['name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$source = $_FILES[ $file_key ]['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$type = $_FILES[ $file_key ]['type']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

	// Validate ZIP file type.
	$accepted_types = array(
		'application/zip',
		'application/x-zip-compressed',
		'multipart/x-zip',
		'application/x-compressed',
	);

	$is_valid_type     = in_array( $type, $accepted_types, true );
	$has_zip_extension = preg_match( '/\.zip$/i', $filename );

	if ( ! $is_valid_type && ! $has_zip_extension ) {
		wp_send_json_error( __( 'The file you are trying to upload is not a .zip file. Please try again.', 'coming-soon' ) );
	}

	return array(
		'filename' => $filename,
		'source'   => $source,
		'type'     => $type,
	);
}

/**
 * Setup import/export directories.
 *
 * @param string $folder_name The folder name to create.
 * @return array Directory paths.
 */
function seedprod_lite_v2_setup_directories( $folder_name ) {
	$upload_dir = wp_upload_dir();
	$path       = trailingslashit( $upload_dir['basedir'] );
	$url        = trailingslashit( $upload_dir['baseurl'] );

	$target_dir = $path . $folder_name;
	$target_url = $url . $folder_name;

	// Clean up existing directory.
	if ( is_dir( $target_dir ) ) {
		// Directory cleanup handled by V2 function.
		seedprod_lite_v2_recursive_rmdir( $target_dir );
	}

	// Create fresh directory.
	mkdir( $target_dir, 0755 );

	return array(
		'path'       => $path,
		'url'        => $url,
		'target_dir' => $target_dir,
		'target_url' => $target_url,
	);
}

/**
 * Extract ZIP file with validation.
 *
 * @param string $zip_path   Path to ZIP file.
 * @param string $extract_to Directory to extract to.
 * @param bool   $is_theme   Whether this is a theme import.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function seedprod_lite_v2_extract_zip( $zip_path, $extract_to, $is_theme = true ) {
	// Validate ZIP contents.
	$validation = seedprod_lite_v2_validate_import_zip( $zip_path, $is_theme );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error(
			'zip_not_available',
			__( 'ZipArchive class not available. Please contact your host to enable it.', 'coming-soon' )
		);
	}

	$zip = new ZipArchive();
	if ( true === $zip->open( $zip_path ) ) {
		$zip->extractTo( $extract_to );
		$zip->close();
		return true;
	}

	return new WP_Error( 'extract_failed', __( 'Failed to extract ZIP file.', 'coming-soon' ) );
}

/**
 * Read JSON file with HTTP/filesystem fallback.
 *
 * @param string $file_path Local file path.
 * @param string $url_path  Optional URL for HTTP attempt.
 * @return object|WP_Error Decoded JSON data or WP_Error.
 */
function seedprod_lite_v2_read_json_file( $file_path, $url_path = null ) {
	global $wp_filesystem;
	$json_data = null;

	// Try HTTP first if URL provided.
	if ( $url_path ) {
		$response = wp_remote_get( $url_path, array( 'sslverify' => false ) );
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$json_data = wp_remote_retrieve_body( $response );
		}
	}

	// Fallback to direct filesystem reading.
	if ( ! $json_data && $wp_filesystem && $wp_filesystem->exists( $file_path ) ) {
		$json_data = $wp_filesystem->get_contents( $file_path );
	}

	if ( ! $json_data ) {
		return new WP_Error( 'read_failed', __( 'Unable to read data file.', 'coming-soon' ) );
	}

	// Decode and validate JSON.
	$data = json_decode( $json_data );
	if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
		return new WP_Error( 'invalid_json', __( 'Invalid data format.', 'coming-soon' ) );
	}

	return $data;
}

/**
 * Process and prepare export data for themes/pages.
 *
 * @param array  $results Database results.
 * @param string $type    Export type ('theme' or 'page').
 * @param mixed  $ptype   Page type for landing pages.
 * @return array Processed export data.
 */
function seedprod_lite_v2_process_export_data( $results, $type = 'theme', $ptype = null ) {
	$processed_data    = array();
	$export            = array();
	$shortcode_exports = array();

	// Set export type
	$export['type']             = ( 'theme' === $type ) ? 'theme-builder' : 'landing-page';
	$export['current_home_url'] = home_url();
	$export['theme']            = array();
	$export['mapped']           = array();

	// Check if this is a template (contains placehold.co)
	$is_template = false;
	if ( 'theme' === $type ) {
		foreach ( $results as $v ) {
			if ( strpos( $v->post_content_filtered, 'placehold.co' ) !== false ) {
				$is_template = true;
				break;
			}
		}
	}

	// Process each result
	foreach ( $results as $k => $v ) {
		// Skip if essential data is missing
		if ( empty( $v->ID ) ) {
			continue;
		}

		$meta = ( 'theme' === $type ) ? get_post_meta( $v->ID ) : wp_json_encode( get_post_meta( $v->ID ) );

		if ( 'theme' === $type && $is_template ) {
			unset( $meta['_seedprod_css'] );
			unset( $meta['_seedprod_html'] );
		}

		if ( 'theme' === $type ) {
			$meta = json_encode( $meta ); // phpcs:ignore
		}

		// Handle null/empty content safely
		$content          = ! empty( $v->post_content ) ? $v->post_content : '';
		$content_filtered = ! empty( $v->post_content_filtered ) ? $v->post_content_filtered : '';

		// Process image filenames

		if ( 'theme' === $type && $is_template ) {
			$processed_data[ $k ] = array(
				'data'   => $content_filtered,
				'html'   => $content,
				'images' => array(),
			);
		} else {
			$processed_result = seedprod_lite_v2_process_image_filenames( $content_filtered, $content );

			// Ensure the result has the expected structure
			$processed_data[ $k ] = array(
				'data'   => isset( $processed_result['data'] ) ? $processed_result['data'] : $content_filtered,
				'html'   => isset( $processed_result['html'] ) ? $processed_result['html'] : $content,
				'images' => isset( $processed_result['images'] ) && is_array( $processed_result['images'] ) ? $processed_result['images'] : array(),
			);
		}

		// Safely get processed data with fallbacks
		$processed_html         = isset( $processed_data[ $k ]['html'] ) ? $processed_data[ $k ]['html'] : '';
		$processed_data_content = isset( $processed_data[ $k ]['data'] ) ? $processed_data[ $k ]['data'] : '';

		// Build export entry
		$entry = array(
			'order'                 => $v->menu_order,
			'post_content'          => base64_encode( $processed_html ), // phpcs:ignore
			'post_content_filtered' => base64_encode( $processed_data_content ), // phpcs:ignore
			'post_title'            => base64_encode( $v->post_title ), // phpcs:ignore
			'meta'                  => base64_encode( $meta ), // phpcs:ignore
		);

		// Add page-specific fields
		if ( 'page' === $type ) {
			$entry['post_type'] = base64_encode( $v->post_type ); // phpcs:ignore
			$entry['post_status'] = base64_encode( $v->post_status ); // phpcs:ignore

			// Determine page type: check both meta field AND WordPress options
			// Special pages are identified by options, not meta
			$actual_ptype = get_post_meta( $v->ID, '_seedprod_page_type', true );

			// If no meta, check if this is a special page by checking WordPress options
			if ( empty( $actual_ptype ) ) {
				$coming_soon_id = get_option( 'seedprod_coming_soon_page_id' );
				$maintenance_id = get_option( 'seedprod_maintenance_mode_page_id' );
				$login_id       = get_option( 'seedprod_login_page_id' );
				$fourohfour_id  = get_option( 'seedprod_404_page_id' );

				if ( $v->ID == $coming_soon_id ) {
					$actual_ptype = 'cs';
				} elseif ( $v->ID == $maintenance_id ) {
					$actual_ptype = 'mm';
				} elseif ( $v->ID == $login_id ) {
					$actual_ptype = 'loginp';
				} elseif ( $v->ID == $fourohfour_id ) {
					$actual_ptype = 'p404';
				}
			}

			$entry['ptype'] = base64_encode( $actual_ptype ?? '' ); // phpcs:ignore
		}

		$export['theme'][] = $entry;

		// Find shortcodes in content (for both themes and landing pages)
		if ( ! empty( $processed_html ) ) {
			$post_content_shortcode = base64_decode( base64_encode( $processed_html ) ); // phpcs:ignore
			$re                     = '/((\[)(sp_template_part id="){1}[0-9]*["](\]))/m';
			preg_match_all( $re, $post_content_shortcode, $matches, PREG_SET_ORDER, 0 );

			if ( $matches ) {
				foreach ( $matches as $t => $val ) {
					$shortcode_content                       = $val[0];
					$shortcode_page_sc                       = str_replace( '[sp_template_part id="', '', $shortcode_content );
					$shortcode_page_sc                       = str_replace( '"]', '', $shortcode_page_sc );
					$shortcode_exports[ $shortcode_page_sc ] = array(
						'id'        => $shortcode_page_sc,
						'shortcode' => $shortcode_content,
					);
				}
			}
		}
	}

	return array(
		'export'            => $export,
		'processed_data'    => $processed_data,
		'shortcode_exports' => $shortcode_exports,
	);
}

/*
==============================================
	MAIN EXPORT/IMPORT FUNCTIONS
	============================================== */

/**
 * Export theme files (V2 implementation)
 * Exports all theme templates as a downloadable ZIP file
 */
function seedprod_lite_v2_export_theme_files() {
	// Validate request
	seedprod_lite_v2_validate_ajax_request( 'export' );

	// Initialize filesystem
	$wp_filesystem = seedprod_lite_v2_init_filesystem();

	global $wpdb;
	$tablename      = $wpdb->prefix . 'posts';
	$meta_tablename = $wpdb->prefix . 'postmeta';

	// Get list of theme templates and create json file
	$sql     = "SELECT * FROM $tablename p LEFT JOIN $meta_tablename pm ON (pm.post_id = p.ID)";
	$sql    .= " WHERE post_status='publish' AND post_type = 'seedprod' AND meta_key = '_seedprod_is_theme_template' ";
	$results = $wpdb->get_results( $sql ); // phpcs:ignore

	$processed_data             = array();
	$export                     = array();
	$export['type']             = 'theme-builder';
	$export['current_home_url'] = home_url();
	$export['theme']            = array();
	$export['mapped']           = array();
	$shortcode_exports          = array();

	// Check if this is a template (contains placehold.co)
	$is_template = false;
	foreach ( $results as $k => $v ) {
		if ( strpos( $v->post_content_filtered, 'placehold.co' ) !== false ) {
			$is_template = true;
			break;
		}
	}

	// Process each template
	foreach ( $results as $k => $v ) {
		// Get post meta
		$meta = get_post_meta( $v->ID );
		if ( $is_template === true ) {
			unset( $meta['_seedprod_css'] );
			unset( $meta['_seedprod_html'] );
		}
		$meta = json_encode( $meta ); // phpcs:ignore
		$content          = $v->post_content;
		$content_filtered = $v->post_content_filtered;

		// Process image filenames if not a template
		if ( $is_template === false ) {
			// Include helper functions if needed
			// Image processing handled by V2 function
			$processed_data[ $k ] = seedprod_lite_v2_process_image_filenames( $content_filtered, $content );
		} else {
			$processed_data[ $k ] = array(
				'data'   => $content_filtered,
				'html'   => $content,
				'images' => array(),
			);
		}

		$export['theme'][] = array(
			'order'                 => $v->menu_order,
			'post_content'          => base64_encode( $processed_data[ $k ]['html'] ), // phpcs:ignore
			'post_content_filtered' => base64_encode( $processed_data[ $k ]['data'] ), // phpcs:ignore
			'post_title'            => base64_encode( $v->post_title ), // phpcs:ignore
			'meta'                  => base64_encode( $meta ), // phpcs:ignore
		);

		// Find shortcodes in content
		$post_content_shortcode = base64_decode( base64_encode( $processed_data[ $k ]['html'] ) ); // phpcs:ignore
		$re                     = '/((\[)(sp_template_part id="){1}[0-9]*["](\]))/m';
		preg_match_all( $re, $post_content_shortcode, $matches, PREG_SET_ORDER, 0 );
		if ( $matches ) {
			foreach ( $matches as $t => $val ) {
				$shortcode_content                       = $val[0];
				$shortcode_page_sc                       = str_replace( '[sp_template_part id="', '', $shortcode_content );
				$shortcode_page_sc                       = str_replace( '"]', '', $shortcode_page_sc );
				$shortcode_exports[ $shortcode_page_sc ] = array(
					'id'        => $shortcode_page_sc,
					'shortcode' => $shortcode_content,
				);
			}
		}
	}

	// Map shortcodes
	foreach ( $shortcode_exports as $n => $t ) {
		$page_id            = $t['id'];
		$sql                = "SELECT post_title FROM $tablename WHERE ID = %d";
		$fetch_page_title   = $wpdb->get_var( $wpdb->prepare( $sql, absint( $page_id ) ) ); // phpcs:ignore
		$export['mapped'][] = array(
			'shortcode'  => base64_encode( $t['shortcode'] ), // phpcs:ignore
			'page_title' => $fetch_page_title,
		);
	}

	// Process images for export
	$export_data_images = array();
	foreach ( $processed_data as $p => $v ) {
		foreach ( $v['images'] as $d => $x ) {
			$export_data_images[] = $x;
		}
	}

	// Create export directory
	$upload_dir    = wp_upload_dir();
	$path          = trailingslashit( $upload_dir['basedir'] );
	$webpath       = trailingslashit( $upload_dir['baseurl'] );
	$export_folder = 'seedprod-themes-exports';
	$export_path   = $path . $export_folder;

	// Remove existing export folder if exists
	if ( is_dir( $export_path ) ) {
		// Directory cleanup handled by V2 function
		seedprod_lite_v2_recursive_rmdir( $export_path );
	}

	// Create export folder
	mkdir( $export_path, 0755 );

	// Save JSON export file
	$json_name   = 'export_theme.json';
	$json_file   = $export_path . '/' . $json_name;
	$export_json = json_encode( $export ); // phpcs:ignore
	$wp_filesystem->put_contents( $json_file, $export_json, FS_CHMOD_FILE );

	// Copy images to export folder
	// Save images locally and track failed ones
	$all_failed_images = array();
	if ( count( $export_data_images ) > 0 ) {
		$failed_images     = seedprod_lite_v2_save_images_locally( $export_data_images );
		$all_failed_images = array_merge( $all_failed_images, $failed_images );
	}

	// Create ZIP file
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_send_json_error( __( 'ZipArchive class not available. Please contact your host to enable it.', 'coming-soon' ) );
	}

	$zip      = new ZipArchive();
	$zip_name = 'seedprod-theme-export-' . date( 'Y-m-d-H-i-s' ) . '.zip';
	$zip_path = $path . $zip_name;

	if ( $zip->open( $zip_path, ZipArchive::CREATE ) === true ) {
		// Add all files from export folder to zip
		seedprod_lite_v2_zipdir( $export_path, $zip, strlen( "$export_path/" ) );
		$zip->close();

		// Check if we should report failed images
		if ( ! empty( $all_failed_images ) ) {
			// Clean up before sending response
			wp_delete_file( $zip_path );
			seedprod_lite_v2_recursive_rmdir( $export_path );

			// Send response with warning about failed images
			$warning_message = sprintf( __( '%d images could not be downloaded and were excluded from the export.', 'coming-soon' ), count( $all_failed_images ) );
			wp_send_json_success(
				array(
					'success'       => true,
					'message'       => __( 'Export completed successfully', 'coming-soon' ),
					'warning'       => $warning_message,
					'failed_images' => count( $all_failed_images ),
					'download_url'  => admin_url( 'admin-ajax.php?action=seedprod_lite_v2_download_export&file=' . basename( $zip_path ) ),
				)
			);
		} else {
			// Send file for download
			header( 'Content-Type: application/zip' );
			header( 'Content-Disposition: attachment; filename="' . $zip_name . '"' );
			header( 'Content-Length: ' . filesize( $zip_path ) );
			readfile( $zip_path );

			// Clean up
			wp_delete_file( $zip_path );
			seedprod_lite_v2_recursive_rmdir( $export_path );
			exit;
		}
	} else {
		wp_send_json_error( __( 'Failed to create ZIP file.', 'coming-soon' ) );
	}
}

/**
 * Import theme files (V2 implementation)
 * Imports theme templates from an uploaded ZIP file
 */
function seedprod_lite_v2_import_theme_files() {
	// Validate request and permissions
	seedprod_lite_v2_validate_ajax_request( 'install_themes' );

	// Check if user wants to delete existing theme templates
	$delete_existing = isset( $_POST['delete_existing'] ) && '1' === $_POST['delete_existing'];

	// Delete existing theme templates if requested
	if ( $delete_existing ) {
		seedprod_lite_v2_delete_theme_templates();
	}

	// Validate and get uploaded file
	$zip_validation = seedprod_lite_v2_validate_zip_upload( 'seedprod_theme_files' );
	if ( is_wp_error( $zip_validation ) ) {
		wp_send_json_error( $zip_validation->get_error_message() );
	}

	// Set script timeout longer
	set_time_limit( 60 );

	// Initialize filesystem
	seedprod_lite_v2_init_filesystem();

	if ( isset( $_FILES['seedprod_theme_files']['name'] ) ) {
		$filename = wp_unslash( $_FILES['seedprod_theme_files']['name'] ); // phpcs:ignore
		$source = $_FILES['seedprod_theme_files']['tmp_name']; // phpcs:ignore

		// Setup directories
		$filename_import = 'seedprod-themes-imports';
		$upload_dir      = wp_upload_dir();
		$path            = trailingslashit( $upload_dir['basedir'] );
		$webpath         = trailingslashit( $upload_dir['baseurl'] );
		$filenoext       = basename( $filename_import, '.zip' );
		$filenoext       = basename( $filenoext, '.ZIP' );
		$targetdir       = $path . $filenoext;
		$targetzip       = $path . $filename;
		$webtargetdir    = $webpath . $filenoext;

		// Remove existing import directory if exists
		if ( is_dir( $targetdir ) ) {
			// Directory cleanup handled by V2 function
			seedprod_lite_v2_recursive_rmdir( $targetdir );
		}

		// Create import directory
		mkdir( $targetdir, 0755 );

		if ( move_uploaded_file( $source, $targetzip ) ) {
			// Extract ZIP file
			$extract_result = seedprod_lite_v2_extract_zip( $targetzip, $targetdir, true );
			if ( is_wp_error( $extract_result ) ) {
				seedprod_lite_v2_recursive_rmdir( $targetdir );
				wp_send_json_error( $extract_result->get_error_message() );
			}

			// Delete the zip file after extraction
			wp_delete_file( $targetzip );

			$theme_json_data     = $targetdir . '/export_theme.json';
			$web_theme_json_data = $webtargetdir . '/export_theme.json';

			// Read and validate theme.json
			$data = seedprod_lite_v2_read_json_file( $theme_json_data, $web_theme_json_data );
			if ( is_wp_error( $data ) ) {
				seedprod_lite_v2_recursive_rmdir( $targetdir );
				wp_send_json_error( $data->get_error_message() );
			}

			// Validate theme type
			if ( ! empty( $data->type ) && 'theme-builder' !== $data->type ) {
				seedprod_lite_v2_recursive_rmdir( $targetdir );
				wp_send_json_error( __( 'This does not appear to be a SeedProd theme.', 'coming-soon' ) );
			}

			// Process the import
			// Process the theme import
			seedprod_lite_v2_theme_import_json( $data );

			// Remove the json file for security
			wp_delete_file( $theme_json_data );

			// Clean up import directory
			seedprod_lite_v2_recursive_rmdir( $targetdir );

			wp_send_json( true );
		} else {
			$message = __( 'There was a problem with the upload. Please try again.', 'coming-soon' );
			wp_send_json_error( $message );
		}
	} else {
		$message = __( 'There was a problem with the upload. Please try again.', 'coming-soon' );
		wp_send_json_error( $message );
	}
}

/**
 * Import theme by URL (V2 implementation)
 * Downloads and imports a theme from a remote URL
 */
function seedprod_lite_v2_import_theme_by_url( $theme_url = null ) {
	// Track if this is a direct AJAX call or internal call
	$is_direct_ajax = ( $theme_url === null );

	// Helper function to handle errors for both AJAX and internal calls
	$handle_error = function ( $message, $code = 'error' ) use ( $is_direct_ajax ) {
		if ( $is_direct_ajax ) {
			wp_send_json_error( $message );
		} else {
			return new WP_Error( $code, $message );
		}
	};

	// Check if this is an AJAX request or direct call
	if ( $is_direct_ajax ) {
		// Validate request and permissions for AJAX
		seedprod_lite_v2_validate_ajax_request( 'install_themes' );
	} else {
		// Check permissions for direct call
		if ( ! current_user_can( apply_filters( 'seedprod_import_export', 'install_themes' ) ) ) {
			return $handle_error( __( 'Insufficient permissions', 'coming-soon' ), 'insufficient_permissions' );
		}
	}

	// Check if user wants to delete existing theme templates
	$delete_existing = isset( $_REQUEST['delete_existing'] ) && '1' === $_REQUEST['delete_existing'];

	// Delete existing theme templates if requested
	if ( $delete_existing ) {
		seedprod_lite_v2_delete_theme_templates();
	}

	// Initialize filesystem
	seedprod_lite_v2_init_filesystem();

	// Get the source URL
	$source = isset( $_REQUEST['seedprod_theme_url'] ) ? wp_kses_post( wp_unslash( $_REQUEST['seedprod_theme_url'] ) ) : '';
	if ( ! empty( $theme_url ) ) {
		$source = $theme_url;
	}

	if ( empty( $source ) ) {
		return $handle_error( __( 'No URL provided', 'coming-soon' ), 'no_url' );
	}

	// Download the file from URL
	$file_import_url_json = wp_remote_get(
		$source,
		array(
			'sslverify' => false,
			'timeout'   => 60,
		)
	);

	if ( is_wp_error( $file_import_url_json ) ) {
		return $handle_error( $file_import_url_json->get_error_message(), $file_import_url_json->get_error_code() );
	}

	// Check if it's a ZIP file
	$content_type = wp_remote_retrieve_header( $file_import_url_json, 'content-type' );
	if ( ! preg_match( '/zip/', $content_type ) ) {
		return $handle_error( __( 'The file is not a valid ZIP archive.', 'coming-soon' ), 'invalid_zip' );
	}

	$body = wp_remote_retrieve_body( $file_import_url_json );
	if ( empty( $body ) ) {
		return $handle_error( __( 'Failed to download file content.', 'coming-soon' ), 'download_failed' );
	}

	// Process the filename
	$url_data = pathinfo( $source );
	$filename = $url_data['basename'];

	// Truncate filename at .zip to remove query parameters (matching old logic)
	if ( strpos( $filename, '.zip' ) !== false ) {
		$filename = substr( $filename, 0, strpos( $filename, '.zip' ) + 4 );
	}

	// Ensure it's a ZIP file
	if ( ! preg_match( '/\.zip$/i', $filename ) ) {
		return $handle_error( __( 'The file you are trying to upload is not a .zip file. Please try again.', 'coming-soon' ), 'not_zip' );
	}

	// Setup directories
	$filename_import = 'seedprod-themes-imports';
	$upload_dir      = wp_upload_dir();
	$path            = trailingslashit( $upload_dir['basedir'] );
	$webpath         = trailingslashit( $upload_dir['baseurl'] );
	$filenoext       = basename( $filename_import, '.zip' );
	$filenoext       = basename( $filenoext, '.ZIP' );
	$targetdir       = $path . $filenoext;
	$targetzip       = $path . $filename;
	$webtargetdir    = $webpath . $filenoext;

	// Remove existing import directory if exists
	if ( is_dir( $targetdir ) ) {
		// Clean up existing directory
		seedprod_lite_v2_recursive_rmdir( $targetdir );
	}

	// Create import directory
	mkdir( $targetdir, 0755 );

	// Save the ZIP file
	if ( ! file_put_contents( $targetzip, $body ) ) { // phpcs:ignore
		seedprod_lite_v2_recursive_rmdir( $targetdir );
		return $handle_error( __( 'There was a problem saving the file. Please try again.', 'coming-soon' ), 'save_failed' );
	}

	// Validate zip contents
	$validation_result = seedprod_lite_v2_validate_import_zip( $targetzip, true );
	if ( is_wp_error( $validation_result ) ) {
		wp_delete_file( $targetzip );
		seedprod_lite_v2_recursive_rmdir( $targetdir );
		return $handle_error( $validation_result->get_error_message(), $validation_result->get_error_code() );
	}

	// Extract ZIP file
	$extract_result = seedprod_lite_v2_extract_zip( $targetzip, $targetdir, true );
	if ( is_wp_error( $extract_result ) ) {
		wp_delete_file( $targetzip );
		seedprod_lite_v2_recursive_rmdir( $targetdir );
		return $handle_error( $extract_result->get_error_message(), $extract_result->get_error_code() );
	}

	// Delete the zip file after extraction
	wp_delete_file( $targetzip );

	// Read and validate theme.json
	$theme_json_path = $targetdir . '/export_theme.json';
	$url_path        = $webtargetdir . '/export_theme.json';

	$data = seedprod_lite_v2_read_json_file( $theme_json_path, $url_path );
	if ( is_wp_error( $data ) ) {
		seedprod_lite_v2_recursive_rmdir( $targetdir );
		return $handle_error( $data->get_error_message(), $data->get_error_code() );
	}

	// Validate theme type
	if ( ! empty( $data->type ) && 'theme-builder' !== $data->type ) {
		seedprod_lite_v2_recursive_rmdir( $targetdir );
		return $handle_error( __( 'This does not appear to be a SeedProd theme.', 'coming-soon' ), 'invalid_theme' );
	}

	// Process the import
	// Process the theme import
	seedprod_lite_v2_theme_import_json( $data );

	// Remove the json file for security
	wp_delete_file( $theme_json_path );

	// Clean up import directory
	seedprod_lite_v2_recursive_rmdir( $targetdir );

	// Only send JSON if this is a direct AJAX call, not an internal call
	if ( $is_direct_ajax ) {
		wp_send_json( true );
	} else {
		// Return success for internal calls
		return true;
	}
}

/**
 * Export landing pages (V2 implementation)
 * Exports all landing pages as a downloadable ZIP file
 */
function seedprod_lite_v2_export_landing_pages() {

	// Validate request and permissions
	try {
		seedprod_lite_v2_validate_ajax_request( 'export' );
	} catch ( Exception $e ) {
		wp_send_json_error( 'Validation failed: ' . $e->getMessage() );
	}

	// Initialize filesystem
	try {
		seedprod_lite_v2_init_filesystem();
	} catch ( Exception $e ) {
		wp_send_json_error( 'Filesystem initialization failed: ' . $e->getMessage() );
	}

	$page_id = isset( $_POST['page_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['page_id'] ) ) ) : 0;
	$ptype   = isset( $_POST['ptype'] ) ? sanitize_text_field( wp_unslash( $_POST['ptype'] ) ) : null;

	global $wpdb;
	$tablename      = $wpdb->prefix . 'posts';
	$meta_tablename = $wpdb->prefix . 'postmeta';

	// Get list of landing pages - EXACT same logic as dropdown query
	// Get special page IDs (Coming Soon, Maintenance Mode, Login, 404)
	$coming_soon_id = get_option( 'seedprod_coming_soon_page_id' );
	$maintenance_id = get_option( 'seedprod_maintenance_mode_page_id' );
	$login_id       = get_option( 'seedprod_login_page_id' );
	$fourohfour_id  = get_option( 'seedprod_404_page_id' );

	$special_page_ids = array();
	if ( ! empty( $coming_soon_id ) ) {
		$special_page_ids[] = $coming_soon_id;
	}
	if ( ! empty( $maintenance_id ) ) {
		$special_page_ids[] = $maintenance_id;
	}
	if ( ! empty( $login_id ) ) {
		$special_page_ids[] = $login_id;
	}
	if ( ! empty( $fourohfour_id ) ) {
		$special_page_ids[] = $fourohfour_id;
	}

	$special_pages_condition = '';
	if ( ! empty( $special_page_ids ) ) {
		$special_ids_string      = implode( ',', array_map( 'intval', $special_page_ids ) );
		$special_pages_condition = " OR p.ID IN ($special_ids_string)";
	}

	$sql  = "SELECT p.*, pm.meta_key, pm.meta_value FROM $tablename p ";
	$sql .= "LEFT JOIN $meta_tablename pm ON (pm.post_id = p.ID AND pm.meta_key = '_seedprod_page_uuid') ";
	$sql .= "LEFT JOIN $meta_tablename pm4 ON (pm4.post_id = p.ID AND pm4.meta_key = '_seedprod_page_template_type') ";
	$sql .= "LEFT JOIN $meta_tablename pm5 ON (pm5.post_id = p.ID AND pm5.meta_key = '_seedprod_page') ";
	$sql .= "WHERE p.post_status != 'trash' ";
	$sql .= "AND p.post_type IN ('page', 'seedprod') ";
	$sql .= "AND ((p.post_type = 'page' AND pm4.meta_value = 'lp') OR (p.post_type = 'page' AND pm5.meta_value IS NOT NULL) $special_pages_condition) ";
	$sql .= "AND (pm.meta_value IS NOT NULL OR pm5.meta_value IS NOT NULL $special_pages_condition) ";

	if ( 0 !== $page_id ) {
		$sql .= "AND p.ID = $page_id ";
	}

	$sql .= 'GROUP BY p.ID ';
	$sql .= 'ORDER BY p.post_date DESC';

	$results = $wpdb->get_results( $sql ); // phpcs:ignore

	if ( $wpdb->last_error ) {
		wp_send_json_error( 'Database error: ' . $wpdb->last_error );
	}

	// Check if we have any pages to export
	if ( empty( $results ) ) {
		wp_send_json_error( __( 'No landing pages found to export.', 'coming-soon' ) );
	}

	// Wrap in try-catch to catch any errors
	try {
		// Process export data using helper function
		$export_result = seedprod_lite_v2_process_export_data( $results, 'page', $ptype );

		if ( is_wp_error( $export_result ) ) {
			wp_send_json_error( $export_result->get_error_message() );
		}

		// Validate export result structure
		if ( ! isset( $export_result['export'] ) || ! isset( $export_result['processed_data'] ) || ! isset( $export_result['shortcode_exports'] ) ) {
			wp_send_json_error( __( 'Export data structure is invalid.', 'coming-soon' ) );
		}

		$export            = $export_result['export'];
		$processed_data    = $export_result['processed_data'];
		$shortcode_exports = $export_result['shortcode_exports'];

	} catch ( Exception $e ) {
		wp_send_json_error( sprintf( __( 'Export failed during processing: %s', 'coming-soon' ), $e->getMessage() ) );
	}

	// Process shortcode mapping for landing pages (restored from old logic)
	foreach ( $shortcode_exports as $k => $sc_val ) {
		$shortcode_page_id = $sc_val['id'];  // Use different variable name to avoid overwriting original $page_id
		$page_shortcode    = $sc_val['shortcode'];

		$sql      = "SELECT p.post_title FROM $tablename p LEFT JOIN $meta_tablename pm ON (pm.post_id = p.ID)";
		$sql     .= " WHERE p.ID = %d and post_status != 'trash' AND post_type IN ('page','seedprod') AND meta_key = '_seedprod_page_uuid' ";
		$safe_sql = $wpdb->prepare( $sql, absint( $shortcode_page_id ) ); // phpcs:ignore
		$page     = $wpdb->get_row( $safe_sql ); // phpcs:ignore

		if ( ! empty( $page ) ) {
			$export['mapped'][] = array(
				'id'         => $shortcode_page_id,
				'shortcode'  => base64_encode( $page_shortcode ),// phpcs:ignore
				'page_title' => $page->post_title,
			);
		}
	}

	$export_json = wp_json_encode( $export );

	// Check if JSON encoding failed
	if ( false === $export_json ) {
		$json_error = json_last_error_msg();
		wp_send_json_error( __( 'Failed to encode export data. The page data may be too large or contain invalid characters.', 'coming-soon' ) . ' Error: ' . $json_error );
	}

	$files_to_download = array();

	global $wp_filesystem;
	$upload_dir = wp_upload_dir();
	$path       = trailingslashit( $upload_dir['basedir'] ) . 'seedprod-themes-exports/';
	$targetdir  = $path;

	// Setup export directory
	if ( is_dir( $targetdir ) ) {
		seedprod_lite_v2_recursive_rmdir( $targetdir );
	}

	// Create directory if it doesn't exist
	if ( ! is_dir( $targetdir ) ) {
		if ( ! mkdir( $targetdir, 0755, true ) ) {
			wp_send_json_error( __( 'Failed to create export directory.', 'coming-soon' ) );
		}
	}

	// Save images locally
	$all_failed_images = array();

	// Process images
	try {
		foreach ( $processed_data as $k1 => $v1 ) {
			// Check if images key exists and has items
			if ( isset( $processed_data[ $k1 ]['images'] ) && is_array( $processed_data[ $k1 ]['images'] ) && count( $processed_data[ $k1 ]['images'] ) > 0 ) {
				$failed_images = seedprod_lite_v2_save_images_locally( $processed_data[ $k1 ]['images'] );

				if ( is_array( $failed_images ) ) {
					$all_failed_images = array_merge( $all_failed_images, $failed_images );
				}
			}

			// Only add successfully downloaded images to the zip
			if ( isset( $processed_data[ $k1 ]['images'] ) && is_array( $processed_data[ $k1 ]['images'] ) ) {
				foreach ( $processed_data[ $k1 ]['images'] as $image ) {
					if ( ! isset( $image['filename'] ) ) {
						continue;
					}

					$image_path = trailingslashit( $upload_dir['basedir'] ) . 'seedprod-themes-exports/' . $image['filename'];

					if ( file_exists( $image_path ) ) {
						$files_to_download[] = $image['filename'];
					}
				}
			}
		}
	} catch ( Exception $e ) {
		wp_send_json_error( sprintf( __( 'Export failed during image processing: %s', 'coming-soon' ), $e->getMessage() ) );
	}

	// Create zip and return download URL
	try {
		$zip_result = seedprod_lite_v2_prepare_zip( $files_to_download, $export_json, 'page' );

		if ( $zip_result && isset( $zip_result['download_url'] ) ) {
			wp_send_json_success( $zip_result );
		} else {
			wp_send_json_error( __( 'Export completed but download URL not generated.', 'coming-soon' ) );
		}
	} catch ( Exception $e ) {
		wp_send_json_error( sprintf( __( 'Export failed during ZIP creation: %s', 'coming-soon' ), $e->getMessage() ) );
	}

	exit;
}

/**
 * Import landing pages (V2 implementation)
 * Imports landing pages from an uploaded ZIP file
 */
function seedprod_lite_v2_import_landing_pages() {
	// Validate request and permissions
	seedprod_lite_v2_validate_ajax_request( 'install_themes' );

	// Validate and get uploaded file
	$zip_validation = seedprod_lite_v2_validate_zip_upload( 'seedprod_landing_files' );
	if ( is_wp_error( $zip_validation ) ) {
		wp_send_json_error( $zip_validation->get_error_message() );
	}

	// Initialize filesystem
	// Note: V2 uses direct filesystem access because this is an AJAX handler
	// and request_filesystem_credentials() doesn't work properly in AJAX context
	seedprod_lite_v2_init_filesystem();

	if ( isset( $_FILES['seedprod_landing_files']['name'] ) ) {
		$filename = wp_unslash( $_FILES['seedprod_landing_files']['name'] ); // phpcs:ignore
		$source = $_FILES['seedprod_landing_files']['tmp_name']; // phpcs:ignore

		// Setup directories
		$filename_import = 'seedprod-themes-imports';
		$upload_dir      = wp_upload_dir();
		$path            = trailingslashit( $upload_dir['basedir'] );
		$path_baseurl    = trailingslashit( $upload_dir['baseurl'] );
		$filenoext       = basename( $filename_import, '.zip' );
		$filenoext       = basename( $filenoext, '.ZIP' );
		$targetdir       = $path . $filenoext;
		$targetzip       = $path . $filename;
		$target_url      = $path_baseurl . $filenoext;

		// Remove existing import directory if exists
		if ( is_dir( $targetdir ) ) {
			// Directory cleanup handled by V2 function
			seedprod_lite_v2_recursive_rmdir( $targetdir );
		}

		// Create import directory
		mkdir( $targetdir, 0755 );

		if ( move_uploaded_file( $source, $targetzip ) ) {
			// Validate zip contents
			$validation_result = seedprod_lite_v2_validate_import_zip( $targetzip, false );
			if ( is_wp_error( $validation_result ) ) {
				wp_delete_file( $targetzip );
				seedprod_lite_v2_recursive_rmdir( $targetdir );
				wp_send_json_error( $validation_result->get_error_message() );
			}

			// Extract ZIP file
			$extract_result = seedprod_lite_v2_extract_zip( $targetzip, $targetdir, false );
			if ( is_wp_error( $extract_result ) ) {
				wp_delete_file( $targetzip );
				seedprod_lite_v2_recursive_rmdir( $targetdir );
				wp_send_json_error( $extract_result->get_error_message() );
			}

			// Delete the zip file after extraction
			wp_delete_file( $targetzip );

			// Read and validate landing page data
			$theme_json_path = $targetdir . '/export_page.json';
			$url_path        = $target_url . '/export_page.json';

			$data = seedprod_lite_v2_read_json_file( $theme_json_path, $url_path );
			if ( is_wp_error( $data ) ) {
				seedprod_lite_v2_recursive_rmdir( $targetdir );
				wp_send_json_error( $data->get_error_message() );
			}

			// Validate landing page type
			if ( ! empty( $data->type ) && 'landing-page' !== $data->type ) {
				seedprod_lite_v2_recursive_rmdir( $targetdir );
				wp_send_json_error( __( 'This does not appear to be a SeedProd landing page.', 'coming-soon' ) );
			}

			// Process the import
			// Process the landing page import
			seedprod_lite_v2_landing_import_json( $data );

			// Remove the json file for security
			wp_delete_file( $theme_json_path );

			// Clean up import directory
			seedprod_lite_v2_recursive_rmdir( $targetdir );

			wp_send_json( true );
		} else {
			$message = __( 'There was a problem with the upload. Please try again.', 'coming-soon' );
			wp_send_json_error( $message );
		}
	} else {
		wp_send_json_error( __( 'Invalid file upload.', 'coming-soon' ) );
	}
}

/**
 * Check if theme templates exist (AJAX handler)
 */
function seedprod_lite_v2_check_existing_theme() {
	// Validate request
	seedprod_lite_v2_validate_ajax_request( 'install_themes' );

	$has_templates = seedprod_lite_v2_has_theme_templates();

	wp_send_json_success( array( 'has_templates' => $has_templates ) );
}
