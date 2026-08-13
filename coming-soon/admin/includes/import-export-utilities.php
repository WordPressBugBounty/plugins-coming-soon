<?php
/**
 * Import/Export Utility Functions (V2)
 *
 * This file contains all utility functions used by the V2 import/export system.
 * These functions are completely independent from the old /app/ functions.
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Recursively remove directory (V2 implementation).
 *
 * @param string $dir Directory path to remove.
 * @return boolean True on success, false on failure.
 */
function seedprod_lite_v2_recursive_rmdir( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return false;
	}

	$files = array_diff( scandir( $dir ), array( '.', '..' ) );
	foreach ( $files as $file ) {
		$path = "$dir/$file";
		if ( is_dir( $path ) ) {
			seedprod_lite_v2_recursive_rmdir( $path );
		} else {
			wp_delete_file( $path );
		}
	}
	return rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- No WP alternative for rmdir, used within controlled recursive delete function.
}

/**
 * Validate import ZIP file (V2 implementation).
 * Checks ZIP file structure and contents for security.
 *
 * @param string  $zip_file Path to ZIP file.
 * @param boolean $is_theme Whether this is a theme import (vs landing page).
 * @return boolean|WP_Error True if valid, WP_Error on failure.
 */
function seedprod_lite_v2_validate_import_zip( $zip_file, $is_theme = false ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'missing_ziparchive', __( 'ZipArchive class not available', 'coming-soon' ) );
	}

	$zip = new ZipArchive();

	if ( $zip->open( $zip_file ) !== true ) {
		return new WP_Error( 'invalid_zip', __( 'Unable to open zip file', 'coming-soon' ) );
	}

	// Check for required JSON file.
	$required_file     = $is_theme ? 'export_theme.json' : 'export_page.json';
	$other_type_file   = $is_theme ? 'export_page.json' : 'export_theme.json';
	$has_required_file = false;
	$has_other_type    = false;

	// Validate file structure.
	$allowed_extensions = array( 'json', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'css', 'ico', 'bmp', 'tiff' );
	$max_file_size      = 100 * 1024 * 1024; // 100MB max per file.

	for ( $i = 0; $i < $zip->numFiles; $i++ ) {
		$stat     = $zip->statIndex( $i );
		$filename = $stat['name'];

		// Check for directory traversal attempts.
		if ( false !== strpos( $filename, '..' ) || 0 === strpos( $filename, '/' ) ) {
			$zip->close();
			return new WP_Error( 'security_risk', __( 'Invalid file path detected in ZIP', 'coming-soon' ) );
		}

		// Check for required file.
		if ( basename( $filename ) === $required_file ) {
			$has_required_file = true;
		} elseif ( basename( $filename ) === $other_type_file ) {
			$has_other_type = true;
		}

		// Skip directories.
		if ( '/' === substr( $filename, -1 ) ) {
			continue;
		}

		// Check file extension.
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_extensions, true ) ) {
			$zip->close();
			/* translators: %s: File extension */
			return new WP_Error( 'invalid_file_type', sprintf( __( 'Invalid file type: %s', 'coming-soon' ), $ext ) );
		}

		// Check file size.
		if ( $stat['size'] > $max_file_size ) {
			$zip->close();
			/* translators: %s: Filename */
			return new WP_Error( 'file_too_large', sprintf( __( 'File too large: %s', 'coming-soon' ), $filename ) );
		}
	}

	$zip->close();

	if ( ! $has_required_file ) {
		if ( $has_other_type ) {
			$error_msg = $is_theme ?
				__( 'This ZIP is a landing page export (export_page.json), not a theme. Import it from the Landing Pages screen instead.', 'coming-soon' ) :
				__( 'This ZIP is a theme export (export_theme.json), not a landing page. Import it from the Website Builder screen instead.', 'coming-soon' );
			return new WP_Error( 'wrong_import_type', $error_msg );
		}
		$error_msg = $is_theme ?
			__( 'Theme data file (export_theme.json) not found in ZIP', 'coming-soon' ) :
			__( 'Landing page data file (export_page.json) not found in ZIP', 'coming-soon' );
		return new WP_Error( 'missing_data_file', $error_msg );
	}

	return true;
}

/**
 * Process image filenames for export (V2 implementation).
 * Extracts and processes images from page data.
 *
 * @param string $data Page data (JSON string).
 * @param string $html Page HTML.
 * @return array Processed data with images array.
 */
function seedprod_lite_v2_process_image_filenames( $data, $html ) {
	// Check for exclusion domains but log what we find.
	$has_unsplash        = false !== strpos( $data, 'unsplash.com' );
	$has_placehold       = false !== strpos( $data, 'placehold.co' );
	$has_assets_seedprod = false !== strpos( $data, 'assets.seedprod.com' );

	// Enhanced regex pattern matching the old function.
	$regex = '/(http)[^\s\'"]+?\.(png|jpg|jpeg|gif|ico|svg|bmp|tiff|webp)[^\s\'"]*?(?=[\'"])/i';

	// if this is a template return - but only if it's ONLY these domains.
	if ( $has_unsplash && ! $has_placehold && ! $has_assets_seedprod ) {
		// Check if there are any other images besides unsplash.
		preg_match_all( $regex, $data, $temp_matches );
		$non_unsplash_images = array_filter(
			$temp_matches[0],
			function ( $url ) {
				return strpos( $url, 'unsplash.com' ) === false;
			}
		);

		if ( empty( $non_unsplash_images ) ) {
			return array(
				'data'   => $data,
				'html'   => $html,
				'images' => array(),
			);
		}
	}

	if ( $has_placehold && ! $has_unsplash && ! $has_assets_seedprod ) {
		// Check if there are any other images besides placehold.
		preg_match_all( $regex, $data, $temp_matches );
		$non_placehold_images = array_filter(
			$temp_matches[0],
			function ( $url ) {
				return false === strpos( $url, 'placehold.co' );
			}
		);

		if ( empty( $non_placehold_images ) ) {
			return array(
				'data'   => $data,
				'html'   => $html,
				'images' => array(),
			);
		}
	}

	$output = array(
		'data'   => '',
		'html'   => '',
		'images' => array(),
	);

	$img_srcs_data = array();
	$img_srcs_html = array();

	preg_match_all( $regex, $data, $img_srcs_data );
	preg_match_all( $regex, $html, $img_srcs_html );

	$img_srcs    = array();
	$img_srcs[0] = array_merge( $img_srcs_data[0], $img_srcs_html[0] );
	$img_srcs[2] = array_merge( $img_srcs_data[2], $img_srcs_html[2] );

	// Eliminate duplicates & pair with extension match from above.
	$unique_img_srcs_extensions = array();
	foreach ( $img_srcs[0] as $index => $img_src ) {
		$unique_img_srcs_extensions[ $img_src ] = $img_srcs[2][ $index ];
	}

	// Need to decode data as WordPress is encoding special characters such as & to &amp; which is.
	// interfering with Unsplash URLs & making it hard to find / replace URLs in strings.
	$processed_data = wp_specialchars_decode( $data );
	$processed_html = wp_specialchars_decode( $html );

	$upload_dir = wp_upload_dir();
	$contentdir = trailingslashit( $upload_dir['baseurl'] ) . 'seedprod-themes-exports/';

	foreach ( $unique_img_srcs_extensions as $old_url => $extension ) {
		// Skip specific excluded domains.
		if ( false !== strpos( $old_url, 'unsplash.com' ) ) {
			continue;
		}

		if ( false !== strpos( $old_url, 'placehold.co' ) ) {
			continue;
		}

		if ( false !== strpos( $old_url, 'assets.seedprod.com' ) ) {
			continue;
		}
		if ( false !== strpos( $old_url, 'w3.org' ) ) {
			continue;
		}

		$prefix = 'theme-builder';

		// Likewise, decode search string.
		$old_url_decoded = wp_specialchars_decode( $old_url );

		// Fix URL mismatch: Replace old domain with current WordPress domain.
		$current_upload_baseurl = $upload_dir['baseurl'];

		// Extract the file path from the old URL (everything after /wp-content/uploads/).
		if ( preg_match( '#/wp-content/uploads/(.+)$#', $old_url_decoded, $matches ) ) {
			$file_path         = $matches[1];
			$corrected_old_url = $current_upload_baseurl . '/' . $file_path;
			$old_url_decoded   = $corrected_old_url;
		}

		$alphanumeric_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$random_chars       = substr( str_shuffle( $alphanumeric_chars ), 0, 16 );

		$filename = $prefix . '-' . $random_chars . '.' . $extension;
		$new_url  = $contentdir . $filename;

		// Replace URL for local preview.
		$processed_data = str_replace( $old_url_decoded, $new_url, $processed_data );
		$processed_html = str_replace( $old_url_decoded, $new_url, $processed_html );

		$output['images'][] = array(
			'prefix'    => $prefix,
			'extension' => $extension,
			'filename'  => $filename,
			'old_url'   => $old_url_decoded,
			'new_url'   => $new_url,
		);
	}

	$output['data'] = $processed_data;
	$output['html'] = $processed_html;

	return $output;
}

/**
 * Save images locally (V2 implementation).
 * Downloads remote images and saves them locally.
 *
 * @param array $img_arr Array of image data.
 * @return array Array of images that failed to download.
 */
function seedprod_lite_v2_save_images_locally( $img_arr ) {
	$failed_images = array();

	if ( empty( $img_arr ) ) {
		return $failed_images;
	}

	$upload_dir = wp_upload_dir();
	$export_dir = trailingslashit( $upload_dir['basedir'] ) . 'seedprod-themes-exports/';

	// Ensure export directory exists.
	if ( ! file_exists( $export_dir ) ) {
		wp_mkdir_p( $export_dir );
	}

	foreach ( $img_arr as $index => $image ) {
		if ( empty( $image['old_url'] ) || empty( $image['filename'] ) ) {
			continue;
		}

		// Download image.
		$response = wp_remote_get(
			$image['old_url'],
			array(
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			$failed_images[] = $image;
			continue;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			$failed_images[] = $image;
			continue;
		}

		$image_data = wp_remote_retrieve_body( $response );
		if ( empty( $image_data ) ) {
			$failed_images[] = $image;
			continue;
		}

		// Save image to file.
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$save_path = $export_dir . $image['filename'];
		$saved     = $wp_filesystem->put_contents( $save_path, $image_data, FS_CHMOD_FILE );

		if ( ! $saved ) {
			$failed_images[] = $image;
		}
	}

	return $failed_images;
}


/**
 * Import landing page JSON data (V2 implementation).
 * Processes and imports landing page data.
 *
 * @param object $json_content Landing page data object.
 * @return array {
 *     Result of the import.
 *
 *     @type int[] $imported_pages IDs of pages created during the import.
 *     @type array $warnings       Per-image warnings collected from the sideload step.
 * }
 */
function seedprod_lite_v2_landing_import_json( $json_content = null ) {
	$imported_pages = array();
	$warnings       = array();

	// Validate input.
	if ( null === $json_content || ! is_object( $json_content ) ) {
		return array(
			'imported_pages' => $imported_pages,
			'warnings'       => $warnings,
		);
	}

	if ( empty( $json_content->theme ) || ! is_array( $json_content->theme ) ) {
		return array(
			'imported_pages' => $imported_pages,
			'warnings'       => $warnings,
		);
	}

	global $wpdb;
	$tablename = $wpdb->prefix . 'posts';

	$old_home_url = isset( $json_content->current_home_url ) ? $json_content->current_home_url : '';
	$new_home_url = home_url();

	// Get existing special page IDs.
	$csp_id    = get_option( 'seedprod_coming_soon_page_id' );
	$mmp_id    = get_option( 'seedprod_maintenance_mode_page_id' );
	$p404_id   = get_option( 'seedprod_404_page_id' );
	$loginp_id = get_option( 'seedprod_login_page_id' );

	// Track shortcode mappings.
	$shortcode_array = array();
	if ( ! empty( $json_content->mapped ) && is_array( $json_content->mapped ) ) {
		foreach ( $json_content->mapped as $k => $t ) {
			$shortcode_array[] = array(
				'id'         => isset( $t->id ) ? $t->id : '',
				'shortcode' => base64_decode( $t->shortcode ), // phpcs:ignore
				'page_title' => $t->page_title,
			);
		}
	}

	$import_page_array = array();

	// Process each landing page.
	foreach ( $json_content->theme as $v ) {
		// Browsers do not unescape "\/" inside HTML attributes; normalize so <img src> renders.
		$post_content          = ! empty( $v->post_content ) ? str_replace( '\\/', '/', base64_decode( $v->post_content ) ) : '';
		$post_content_filtered = ! empty( $v->post_content_filtered ) ? str_replace( '\\/', '/', base64_decode( $v->post_content_filtered ) ) : '';
		$post_title            = ! empty( $v->post_title ) ? base64_decode( $v->post_title ) : '';
		$post_type             = ! empty( $v->post_type ) ? base64_decode( $v->post_type ) : 'page';
		$post_status           = ! empty( $v->post_status ) ? base64_decode( $v->post_status ) : 'draft';
		$ptype                 = ! empty( $v->ptype ) ? base64_decode( $v->ptype ) : '';
		$meta                  = ! empty( $v->meta ) ? json_decode( base64_decode( $v->meta ), true ) : array();

		if ( function_exists( 'seedprod_lite_heal_import_pcf' ) ) {
			$post_content_filtered = seedprod_lite_heal_import_pcf( $post_content_filtered );
		}

		// Create post.
		$post_data = array(
			'post_title'            => $post_title,
			'post_content'          => $post_content,
			'post_content_filtered' => $post_content_filtered,
			'post_status'           => $post_status,
			'post_type'             => $post_type,
			'menu_order'            => ! empty( $v->order ) ? $v->order : 0,
		);

		$post_id = wp_insert_post( $post_data );

		if ( ! is_wp_error( $post_id ) ) {
			// For CSS templates, ensure page_type is set in the JSON.
			if ( ! empty( $meta['_seedprod_page_template_type'][0] ) && 'css' === $meta['_seedprod_page_template_type'][0] ) {
				$json_data = json_decode( $post_content_filtered, true );
				if ( null !== $json_data ) {
					// Ensure page_type is set at the root level.
					$json_data['page_type'] = 'css';
					$post_content_filtered  = wp_json_encode( $json_data );
				}
			}

			// Reinsert settings because wp_insert screws up json (following old working logic).
			if ( ! empty( $post_content_filtered ) ) {
				global $wpdb;
				$tablename = esc_sql( $wpdb->prefix . 'posts' );
				$sql       = "UPDATE $tablename SET post_content_filtered = %s, post_content = %s WHERE id = %d";
				$safe_sql  = $wpdb->prepare( $sql, $post_content_filtered, $post_content, $post_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped with esc_sql(), dynamic SQL assembled for prepare().
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name escaped with esc_sql(), values prepared with wpdb->prepare().
				$wpdb->query( $safe_sql );
			}

			$imported_pages[] = $post_id;

			// Store for shortcode mapping.
			$import_page_array[] = array(
				'id'                    => $post_id,
				'title'                 => $post_title,
				'post_content'          => $post_content,
				'post_content_filtered' => $post_content_filtered,
				'meta'                  => $meta,
			);

			// Reinsert content to preserve JSON integrity using direct database update.
			$sql      = "UPDATE $tablename SET post_content_filtered = %s, post_content = %s WHERE id = %d";
			$safe_sql = $wpdb->prepare( $sql, $post_content_filtered, $post_content, absint( $post_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped with esc_sql(), dynamic SQL assembled for prepare().
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name escaped with esc_sql(), values prepared with wpdb->prepare().
			$wpdb->query( $safe_sql );

			// Add post meta.
			if ( ! empty( $meta ) ) {
				foreach ( $meta as $meta_key => $meta_value ) {
					if ( is_array( $meta_value ) && count( $meta_value ) === 1 ) {
						$meta_value = maybe_unserialize( $meta_value[0] );
					}
					update_post_meta( $post_id, $meta_key, $meta_value );
				}
			}

			// Generate UUID if needed.
			if ( empty( $meta['_seedprod_page_uuid'] ) ) {
				update_post_meta( $post_id, '_seedprod_page_uuid', wp_generate_uuid4() );
			}

			// Set page type if specified.
			if ( ! empty( $ptype ) ) {
				update_post_meta( $post_id, '_seedprod_page_type', $ptype );

				// Update special page options based on ptype.
				if ( 'cs' === $ptype ) {
					update_option( 'seedprod_coming_soon_page_id', $post_id );
				}
				if ( 'mm' === $ptype ) {
					update_option( 'seedprod_maintenance_mode_page_id', $post_id );
				}
				if ( 'p404' === $ptype ) {
					update_option( 'seedprod_404_page_id', $post_id );
				}
				if ( 'loginp' === $ptype ) {
					update_option( 'seedprod_login_page_id', $post_id );
				}
			}
		}
	}

	// Process shortcode remapping, image import, and CSS extraction.
	foreach ( $import_page_array as $t => $val ) {
		$post_content          = $val['post_content'];
		$post_content_filtered = $val['post_content_filtered'];
		$post_id               = $val['id'];

		// Replace old home URL with new home URL.
		if ( ! empty( $old_home_url ) ) {
			$post_content          = str_replace( $old_home_url, $new_home_url, $post_content );
			$post_content_filtered = str_replace( $old_home_url, $new_home_url, $post_content_filtered );
		}

		// Replace export path with import path.
		$post_content          = str_replace( 'seedprod-themes-exports', 'seedprod-themes-imports', $post_content );
		$post_content_filtered = str_replace( 'seedprod-themes-exports', 'seedprod-themes-imports', $post_content_filtered );

		// Import images into the WordPress media library.
		if ( function_exists( 'seedprod_lite_process_image_filenames_import_theme' ) ) {
			$processed_data_import = seedprod_lite_process_image_filenames_import_theme( $post_content_filtered, $post_content );
			$post_content          = $processed_data_import['html'];
			$post_content_filtered = $processed_data_import['data'];
			if ( ! empty( $processed_data_import['warnings'] ) ) {
				$warnings = array_merge( $warnings, $processed_data_import['warnings'] );
			}
		}

		// Replace shortcodes if we have mappings.
		if ( count( $shortcode_array ) > 0 ) {
			foreach ( $shortcode_array as $k => $sc ) {
				$shortcode_page_title = $sc['page_title'];
				$fetch_shortcode_key  = array_search( $shortcode_page_title, array_column( $import_page_array, 'title' ), true ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Handled by third parameter.

				if ( false !== $fetch_shortcode_key ) {
					$fetch_shortcode_id = $import_page_array[ $fetch_shortcode_key ]['id'];

					$shortcode_page_sc = $sc['shortcode'];
					$shortcode_page_sc = str_replace( '[sp_template_part id="', '', $shortcode_page_sc );
					$shortcode_page_sc = str_replace( '"]', '', $shortcode_page_sc );

					if ( $fetch_shortcode_id ) {
						// Replace in HTML.
						$updated_shortcode = '[sp_template_part id="' . $fetch_shortcode_id . '"]';
						$post_content      = str_replace( $sc['shortcode'], $updated_shortcode, $post_content );

						// Replace in JSON data.
						$old_templateparts     = '"templateparts":"' . $shortcode_page_sc . '"';
						$new_templateparts     = '"templateparts":"' . $fetch_shortcode_id . '"';
						$post_content_filtered = str_replace( $old_templateparts, $new_templateparts, $post_content_filtered );
					}
				}
			}
		}

		// CSS templates carry their CSS in meta; every other page has it extracted
		// from HTML below. Meta values are get_post_meta() shaped, so index them.
		$item_meta       = isset( $val['meta'] ) && is_array( $val['meta'] ) ? $val['meta'] : array();
		$is_css_template = isset( $item_meta['_seedprod_page_template_type'][0] ) &&
							'css' === $item_meta['_seedprod_page_template_type'][0];

		if ( $is_css_template ) {
			// For CSS templates (Global CSS), handle special CSS meta.
			// Get CSS from meta if available (following old import logic exactly).
			$custom_css  = '';
			$builder_css = '';

			if ( isset( $item_meta['_seedprod_css'][0] ) ) {
				$css = str_replace( 'TO_BE_REPLACED', home_url(), $item_meta['_seedprod_css'][0] );
			} else {
				// Fallback to post_content if no meta.
				$css = str_replace( 'TO_BE_REPLACED', home_url(), $post_content );
			}

			if ( isset( $item_meta['_seedprod_builder_css'][0] ) ) {
				$builder_css = str_replace( 'TO_BE_REPLACED', home_url(), $item_meta['_seedprod_builder_css'][0] );
			}

			// Update all CSS meta fields.
			update_post_meta( $post_id, '_seedprod_css', $css );
			update_post_meta( $post_id, '_seedprod_custom_css', $custom_css );
			update_post_meta( $post_id, '_seedprod_builder_css', $builder_css );

			// Set BOTH option names (old system uses both).
			$previous_css_page_id = get_option( 'seedprod_global_css_page_id' );
			update_option( 'global_css_page_id', $post_id );
			// seedprod_lite_generate_css_file() reads this option to decide whether to
			// write style-global.css, so it must point at the new page before generating.
			update_option( 'seedprod_global_css_page_id', $post_id );

			// Generate CSS file with combined CSS and proper @import handling.
			$combined_css = function_exists( 'seedprod_lite_merge_global_custom_css' )
				? seedprod_lite_merge_global_custom_css( $css, $custom_css )
				: $css;
			if ( function_exists( 'seedprod_lite_generate_css_file' ) ) {
				seedprod_lite_generate_css_file( $post_id, $combined_css );
			}

			// Trash the superseded page last so a failure above leaves it in place.
			if ( ! empty( $previous_css_page_id ) && (int) $previous_css_page_id !== (int) $post_id ) {
				wp_trash_post( $previous_css_page_id );
			}
		} elseif ( function_exists( 'seedprod_lite_extract_page_css' ) ) {
			// For regular templates, extract CSS from HTML.
			$code = seedprod_lite_extract_page_css( $post_content, $post_id );
			update_post_meta( $post_id, '_seedprod_css', $code['css'] );
			update_post_meta( $post_id, '_seedprod_html', $code['html'] );

			if ( function_exists( 'seedprod_lite_generate_css_file' ) ) {
				seedprod_lite_generate_css_file( $post_id, $code['css'] );
			}
		}

		// Update database with remapped content.
		$sql      = "UPDATE $tablename SET post_content_filtered = %s, post_content = %s WHERE id = %d";
		$safe_sql = $wpdb->prepare( $sql, $post_content_filtered, $post_content, $post_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped with esc_sql(), dynamic SQL assembled for prepare().
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name escaped with esc_sql(), values prepared with wpdb->prepare().
		$wpdb->query( $safe_sql );
	}

	return array(
		'imported_pages' => $imported_pages,
		'warnings'       => $warnings,
	);
}

/**
 * Prepare ZIP file for download (V2 implementation).
 * Creates a ZIP file with exported data.
 *
 * @param array  $filenames   Array of filenames to include.
 * @param string $export_json JSON data to include.
 * @param string $type        Export type ('theme' or 'page').
 * @return array Download information including URL and file size.
 * @throws Exception If ZIP operations fail.
 */
function seedprod_lite_v2_prepare_zip( $filenames, $export_json, $type = 'theme' ) {
	global $wp_filesystem;

	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}

	$upload_dir = wp_upload_dir();
	$export_dir = trailingslashit( $upload_dir['basedir'] ) . 'seedprod-themes-exports/';

	// Determine JSON filename based on type.
	$json_filename = ( 'theme' === $type ) ? 'export_theme.json' : 'export_page.json';

	// Save JSON file.
	$json_path  = $export_dir . $json_filename;
	$json_saved = $wp_filesystem->put_contents( $json_path, $export_json, FS_CHMOD_FILE );

	if ( ! $json_saved ) {
		throw new Exception( esc_html__( 'Failed to save export JSON file.', 'coming-soon' ) );
	}

	// Create ZIP file.
	if ( ! class_exists( 'ZipArchive' ) ) {
		throw new Exception( esc_html__( 'ZipArchive class not available. Please contact your host.', 'coming-soon' ) );
	}

	$zip          = new ZipArchive();
	$zip_filename = 'seedprod-' . $type . '-export-' . gmdate( 'Y-m-d-His' ) . '.zip';
	$zip_path     = $export_dir . $zip_filename;

	$zip_open_result = $zip->open( $zip_path, ZipArchive::CREATE );
	if ( true !== $zip_open_result ) {
		// translators: %s: ZipArchive error code.
		throw new Exception( sprintf( esc_html__( 'Cannot create ZIP file. Error code: %s', 'coming-soon' ), esc_html( $zip_open_result ) ) );
	}

	// Add JSON file.
	if ( ! $zip->addFile( $json_path, $json_filename ) ) {
		$zip->close();
		throw new Exception( esc_html__( 'Failed to add JSON file to ZIP.', 'coming-soon' ) );
	}

	// Add image files.
	foreach ( $filenames as $filename ) {
		$file_path = $export_dir . $filename;
		if ( file_exists( $file_path ) ) {
			$zip->addFile( $file_path, $filename );
		}
	}

	$zip->close();

	// Verify ZIP was created.
	if ( ! file_exists( $zip_path ) ) {
		throw new Exception( esc_html__( 'ZIP file was not created successfully.', 'coming-soon' ) );
	}

	$zip_size = filesize( $zip_path );

	// Clean up temporary files (keep ZIP for download).
	wp_delete_file( $json_path );
	foreach ( $filenames as $filename ) {
		wp_delete_file( $export_dir . $filename );
	}

	// Read ZIP and base64-encode for inline delivery.
	// This ensures downloads work in all environments including WordPress Playground.
	$zip_contents = file_get_contents( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $zip_contents ) {
		throw new Exception( esc_html__( 'Failed to read export file.', 'coming-soon' ) );
	}
	$zip_base64 = base64_encode( $zip_contents ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	unset( $zip_contents );

	// Clean up ZIP file since it is delivered inline.
	wp_delete_file( $zip_path );

	return array(
		'success'  => true,
		'filedata' => $zip_base64,
		'filename' => $zip_filename,
	);
}
