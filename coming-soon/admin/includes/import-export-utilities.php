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
 * Check if theme templates exist.
 *
 * @return boolean True if theme templates exist, false otherwise.
 */
function seedprod_lite_v2_has_theme_templates() {
	$args = array(
		'post_type'      => 'seedprod',
		'posts_per_page' => 1,
		'post_status'    => 'any',
		'meta_query'     => array(
			array(
				'key'   => '_seedprod_is_theme_template',
				'value' => true,
			),
		),
		'fields'         => 'ids',
	);

	$query = new WP_Query( $args );
	return $query->have_posts();
}

/**
 * Delete all existing theme templates.
 *
 * @return integer Number of templates deleted.
 */
function seedprod_lite_v2_delete_theme_templates() {
	$args = array(
		'post_type'      => 'seedprod',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'meta_query'     => array(
			array(
				'key'   => '_seedprod_is_theme_template',
				'value' => true,
			),
		),
		'fields'         => 'ids',
	);

	$query = new WP_Query( $args );
	$count = 0;

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post_id ) {
				wp_delete_post( $post_id, true ); // Force delete.
			++$count;
		}
	}

	return $count;
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
	return rmdir( $dir );
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
	$has_required_file = false;

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
	$regex = '/(http)[^\s\'"]+?(png|jpg|jpeg|gif|ico|svg|bmp|tiff|webp)[^\s\'"]*?(?=[\'"])/i';

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

	$img_srcs = array();

	preg_match_all( $regex, $data, $img_srcs );
	preg_match_all( $regex, $html, $img_srcs );

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
 * Process image filenames for import (V2 implementation).
 * Replaces image placeholders with actual URLs.
 *
 * @param string $data Page data with image placeholders.
 * @param string $html Page HTML with image placeholders.
 * @return array Processed data and HTML.
 */
function seedprod_lite_v2_process_image_filenames_import( $data, $html ) {
	// Skip if template/placeholder content.
	if ( false !== strpos( $data, 'unsplash.com' ) ) {
		return array(
			'data' => $data,
			'html' => $html,
		);
	}

	$upload_dir = wp_upload_dir();
	$import_url = trailingslashit( $upload_dir['baseurl'] ) . 'seedprod-themes-imports/';

	// Find and replace image placeholders.
	$pattern = '/{{image}}([^{}]+){{\/image}}/';
	preg_match_all( $pattern, $data, $matches );

	if ( ! empty( $matches[0] ) ) {
		foreach ( $matches[0] as $index => $placeholder ) {
			$filename = $matches[1][ $index ];
			$new_url  = $import_url . $filename;

			$data = str_replace( $placeholder, $new_url, $data );
			$html = str_replace( $placeholder, $new_url, $html );
		}
	}

	return array(
		'data' => $data,
		'html' => $html,
	);
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
 * Recursively add directory contents to ZIP (V2 implementation).
 *
 * @param string     $source      Source directory path.
 * @param ZipArchive $zip         ZIP archive object.
 * @param integer    $path_length Length of base path to remove.
 * @return void
 */
function seedprod_lite_v2_zipdir( $source, $zip, $path_length ) {
	if ( ! is_dir( $source ) ) {
		return;
	}

	$files = scandir( $source );
	foreach ( $files as $file ) {
		if ( in_array( $file, array( '.', '..' ), true ) ) {
			continue;
		}

		$file_path  = $source . '/' . $file;
		$local_path = substr( $file_path, $path_length );

		if ( is_dir( $file_path ) ) {
			$zip->addEmptyDir( $local_path );
			seedprod_lite_v2_zipdir( $file_path, $zip, $path_length );
		} else {
			$zip->addFile( $file_path, $local_path );
		}
	}
}

/**
 * Import theme JSON data (V2 implementation).
 * Processes and imports theme data.
 *
 * @param object $json_content Theme data object.
 * @return void
 */
function seedprod_lite_v2_theme_import_json( $json_content = null ) {

	// Validate input data.
	if ( null === $json_content || ! is_object( $json_content ) ) {
		return;
	}

	$full_code = $json_content;

	$theme            = isset( $full_code->theme ) ? $full_code->theme : array();
	$shortcode_update = isset( $full_code->mapped ) ? $full_code->mapped : array();
	$old_home_url     = isset( $full_code->current_home_url ) ? $full_code->current_home_url : '';
	$new_home_url     = home_url();

	$imports = array();
	if ( is_array( $theme ) && count( $theme ) > 0 ) {
		foreach ( $theme as $k => $v ) {
			$imports[] = array(
				'post_content'          => base64_decode( $v->post_content ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'post_content_filtered' => base64_decode( $v->post_content_filtered ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'post_title'            => base64_decode( $v->post_title ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'meta'                  => json_decode( base64_decode( $v->meta ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'order'                 => $v->order,
			);
		}
	}

	$shortcode_array = array();
	if ( is_array( $shortcode_update ) && count( $shortcode_update ) > 0 ) {
		foreach ( $shortcode_update as $k => $t ) {
			$shortcode_array[] = array(
				'shortcode'  => base64_decode( $t->shortcode ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				'page_title' => $t->page_title,
			);
		}
	}

	$import_page_array = array();
	if ( count( $imports ) > 0 ) {
		foreach ( $imports as $k1 => $v1 ) {

			$meta = $v1['meta'];

			$data = array(
				'comment_status' => 'closed',
				'menu_order'     => $v1['order'],
				'ping_status'    => 'closed',
				'post_status'    => 'publish',
				'post_title'     => $v1['post_title'],
				'post_type'      => 'seedprod',
				'meta_input'     => array(
					'_seedprod_page'               => true,
					'_seedprod_is_theme_template'  => true,
					'_seedprod_page_uuid'          => wp_generate_uuid4(),
					'_seedprod_page_template_type' => $meta->_seedprod_page_template_type[0],
				),
			);

			$id = wp_insert_post(
				$data,
				true
			);

			$import_page_array[] = array(
				'id'                    => $id,
				'title'                 => $v1['post_title'],
				'post_content'          => $v1['post_content'],
				'post_content_filtered' => $v1['post_content_filtered'],
			);

			// reinsert settings because wp_insert screws up json.
			$post_content_filtered = $v1['post_content_filtered'];
			$post_content          = $v1['post_content'];

			// For CSS templates, ensure page_type is set in the JSON.
			if ( 'css' === $meta->_seedprod_page_template_type[0] ) {
				$json_data = json_decode( $post_content_filtered, true );
				if ( null !== $json_data ) {
					// Ensure page_type is set at the root level.
					$json_data['page_type'] = 'css';
					$post_content_filtered  = wp_json_encode( $json_data );
				}
			}

			global $wpdb;
			$tablename = esc_sql( $wpdb->prefix . 'posts' );
			$sql       = "UPDATE $tablename SET post_content_filtered = %s,post_content = %s WHERE id = %d";
			$safe_sql  = $wpdb->prepare( $sql, $post_content_filtered, $post_content, $id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped with esc_sql(), dynamic SQL assembled for prepare().
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name escaped with esc_sql(), values prepared with wpdb->prepare().
			$wpdb->query( $safe_sql );

			// Add meta.
			if ( 'css' === $meta->_seedprod_page_template_type[0] ) {
				// Set css file.
				// Find and replace url.
				$css = str_replace( $old_home_url, $new_home_url, $v1['post_content'] );
				$css = str_replace( 'seedprod-themes-exports', 'seedprod-themes-imports', $css );
				// Custom CSS is intentionally set to empty (following old logic).
				$custom_css  = '';
				$builder_css = $meta->_seedprod_builder_css[0];

				update_post_meta( $id, '_seedprod_css', $css );
				update_post_meta( $id, '_seedprod_custom_css', $custom_css );
				update_post_meta( $id, '_seedprod_builder_css', $builder_css );
				update_option( 'global_css_page_id', $id );
				// Generate css.
				$css = $css . $custom_css;

				// Trash current css file and set css file pointer.
				$current_css_file = get_option( 'seedprod_global_css_page_id' );
				if ( ! empty( $current_css_file ) ) {
					wp_trash_post( $current_css_file );
				}

				update_option( 'seedprod_global_css_page_id', $id );

				// Generate CSS file.
				if ( function_exists( 'seedprod_lite_generate_css_file' ) ) {
					seedprod_lite_generate_css_file( $id, $css );
				}
			} else {
				// Find and replace preview urls.
				$new_post_content = str_replace( $old_home_url, $new_home_url, $v1['post_content'] );
				$new_post_content = str_replace( 'seedprod-themes-exports', 'seedprod-themes-imports', $new_post_content );
				// Extract CSS from page content.
				if ( function_exists( 'seedprod_lite_extract_page_css' ) ) {
					$code = seedprod_lite_extract_page_css( $new_post_content, $id );
					update_post_meta( $id, '_seedprod_css', $code['css'] );
					update_post_meta( $id, '_seedprod_html', $code['html'] );

					if ( function_exists( 'seedprod_lite_generate_css_file' ) ) {
						seedprod_lite_generate_css_file( $id, $code['css'] );
					}
				}
				update_post_meta( $id, '_seedprod_theme_template_condition', $meta->_seedprod_theme_template_condition[0] );
				// Process conditon to see if we need to create a placeholder page.
				$conditions = $meta->_seedprod_theme_template_condition[0];

				if ( ! empty( $conditions ) ) {

					$conditions = json_decode( $conditions );
					if ( is_array( $conditions ) ) {
						if ( 1 === count( $conditions ) && 'include' === $conditions[0]->condition && 'is_page(x)' === $conditions[0]->type && ! empty( $conditions[0]->value ) && ! is_numeric( $conditions[0]->value ) ) {
							// Check if slug exists.
							$slug_tablename = esc_sql( $wpdb->prefix . 'posts' );
							$sql            = "SELECT id FROM $slug_tablename WHERE post_name = %s AND post_type = 'page' AND post_status != 'trash'";
							$safe_sql       = $wpdb->prepare( $sql, $conditions[0]->value ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped with esc_sql(), dynamic SQL assembled for prepare().
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name escaped with esc_sql(), values prepared with wpdb->prepare().
							$this_slug_exist = $wpdb->get_var( $safe_sql );
							if ( empty( $this_slug_exist ) ) {
								// Create page with content.
								$page_details                  = array(
									'post_title'   => $v1['post_title'],
									'post_name'    => $conditions[0]->value,
									'post_content' => $new_post_content,
									'post_status'  => 'publish',
									'post_type'    => 'page',
								);
								$seedprod_remove_page_template = apply_filters( 'seedprod_remove_page_template', true );
								if ( $seedprod_remove_page_template ) {
									$new_page_id = wp_insert_post( $page_details );
									if ( ! empty( $new_page_id ) ) {
										// Add meta.
										update_post_meta( $new_page_id, '_seedprod_edited_with_seedprod', '1' );
										// Reinsert settings because wp_insert screws up json.
										$post_content_filtered_new_page = $v1['post_content_filtered'];
										global $wpdb;
										$tablename = esc_sql( $wpdb->prefix . 'posts' );
										$sql       = "UPDATE $tablename SET post_content_filtered = %s,post_content = %s WHERE id = %d";
										$safe_sql  = $wpdb->prepare( $sql, $post_content_filtered_new_page, $new_post_content, $new_page_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped with esc_sql(), dynamic SQL assembled for prepare().
										// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name escaped with esc_sql(), values prepared with wpdb->prepare().
										$wpdb->query( $safe_sql );
										// Update import array map with new id.
										foreach ( $import_page_array as $k5 => $v5 ) {
											if ( $id == $v5['id'] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Intentional loose comparison for mixed types.
												$import_page_array[ $k5 ]['id'] = $new_page_id;
											}
										}

										// Remove template page.
										wp_delete_post( $id, true );

									}
								} else {
									// Add place holder page.
									wp_insert_post( $page_details );
								}
							}
						}
					}
				}
			}
		}
	}

	// Find and replace shortcodes.
	if ( count( $import_page_array ) > 0 ) {
		foreach ( $import_page_array as $t => $val ) {
			if ( 'Global CSS' !== $val['title'] ) {
				$post_content          = $val['post_content'];
				$post_content_filtered = $val['post_content_filtered'];
				$post_id               = $val['id'];

				// Process image filenames.
				if ( function_exists( 'seedprod_lite_process_image_filenames_import_theme' ) ) {
					$processed_data_import = seedprod_lite_process_image_filenames_import_theme( $post_content_filtered, $post_content );
					$post_content          = $processed_data_import['html'];
					$post_content_filtered = $processed_data_import['data'];
				}

				// Extract and generate CSS for non-Global CSS templates.
				if ( function_exists( 'seedprod_lite_extract_page_css' ) ) {
					$code = seedprod_lite_extract_page_css( $post_content, $post_id );
					update_post_meta( $post_id, '_seedprod_css', $code['css'] );
					update_post_meta( $post_id, '_seedprod_html', $code['html'] );

					if ( function_exists( 'seedprod_lite_generate_css_file' ) ) {
						seedprod_lite_generate_css_file( $post_id, $code['css'] );
					}
				}

				if ( count( $shortcode_array ) > 0 ) {
					foreach ( $shortcode_array as $k => $t ) {
						$shortcode_page_title = $shortcode_array[ $k ]['page_title'];
						$fetch_shortcode_key  = array_search( $shortcode_page_title, array_column( $import_page_array, 'title' ), true ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Handled by third parameter.
						$fetch_shortcode_id   = $import_page_array[ $fetch_shortcode_key ]['id'];

						$shortcode_page_sc = $shortcode_array[ $k ]['shortcode'];
						$shortcode_page_sc = str_replace( '[sp_template_part id="', '', $shortcode_page_sc );
						$shortcode_page_sc = str_replace( '"]', '', $shortcode_page_sc );

						if ( $fetch_shortcode_id ) {
							$shortcode_array[ $k ]['updated_shortcode'] = '[sp_template_part id="' . $fetch_shortcode_id . '"]';
							$post_content                               = str_replace( $shortcode_array[ $k ]['shortcode'], $shortcode_array[ $k ]['updated_shortcode'], $post_content );

							$shortcode_array[ $k ]['updated_shortcode_filtered'] = '"templateparts":"' . $fetch_shortcode_id . '"';
							$shortcode_array[ $k ]['shortcode_filtered_id']      = $shortcode_page_sc;
							$shortcode_array[ $k ]['shortcode_filtered']         = '"templateparts":"' . $shortcode_page_sc . '"';

							$post_content_filtered = str_replace( $shortcode_array[ $k ]['shortcode_filtered'], $shortcode_array[ $k ]['updated_shortcode_filtered'], $post_content_filtered );

							// update generated html.
							$generate_html = get_post_meta( $post_id, '_seedprod_html', true );
							$generate_html = str_replace( $shortcode_array[ $k ]['shortcode'], $shortcode_array[ $k ]['updated_shortcode'], $generate_html );
							update_post_meta( $post_id, '_seedprod_html', $generate_html );
						}
					}
				}

				global $wpdb;
				$tablename = esc_sql( $wpdb->prefix . 'posts' );
				$sql       = "UPDATE $tablename SET post_content_filtered = %s,post_content = %s WHERE id = %d";
				$safe_sql  = $wpdb->prepare( $sql, $post_content_filtered, $post_content, absint( $post_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped with esc_sql(), dynamic SQL assembled for prepare().
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name escaped with esc_sql(), values prepared with wpdb->prepare().
				$wpdb->query( $safe_sql );
			}
		}
	}
}

/**
 * Import landing page JSON data (V2 implementation).
 * Processes and imports landing page data.
 *
 * @param object $json_content Landing page data object.
 * @return array Array of imported page IDs.
 */
function seedprod_lite_v2_landing_import_json( $json_content = null ) {
	$imported_pages = array();

	// Validate input.
	if ( null === $json_content || ! is_object( $json_content ) ) {
		return $imported_pages;
	}

	if ( empty( $json_content->theme ) || ! is_array( $json_content->theme ) ) {
		return $imported_pages;
	}

	global $wpdb;
	$tablename = $wpdb->prefix . 'posts';

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
		// Decode data.
		$post_content          = ! empty( $v->post_content ) ? base64_decode( $v->post_content ) : '';
		$post_content_filtered = ! empty( $v->post_content_filtered ) ? base64_decode( $v->post_content_filtered ) : '';
		$post_title            = ! empty( $v->post_title ) ? base64_decode( $v->post_title ) : '';
		$post_type             = ! empty( $v->post_type ) ? base64_decode( $v->post_type ) : 'page';
		$post_status           = ! empty( $v->post_status ) ? base64_decode( $v->post_status ) : 'draft';
		$ptype                 = ! empty( $v->ptype ) ? base64_decode( $v->ptype ) : '';
		$meta                  = ! empty( $v->meta ) ? json_decode( base64_decode( $v->meta ), true ) : array();

		// Process images if needed.
		if ( ! empty( $post_content_filtered ) ) {
			$processed             = seedprod_lite_v2_process_image_filenames_import( $post_content_filtered, $post_content );
			$post_content          = $processed['html'];
			$post_content_filtered = $processed['data'];
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
			if ( ! empty( $meta['_seedprod_page_template_type'] ) && 'css' === $meta['_seedprod_page_template_type'] ) {
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

	// Process shortcode remapping and CSS extraction.
	foreach ( $import_page_array as $t => $val ) {
		$post_content          = $val['post_content'];
		$post_content_filtered = $val['post_content_filtered'];
		$post_id               = $val['id'];

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

		// Extract and generate CSS (but not for CSS templates).
		$is_css_template = isset( $meta['_seedprod_page_template_type'] ) &&
							'css' === $meta['_seedprod_page_template_type'];

		if ( $is_css_template ) {
			// For CSS templates (Global CSS), handle special CSS meta.
			// Get CSS from meta if available (following old import logic exactly).
			$css         = '';
			$custom_css  = '';
			$builder_css = '';

			if ( isset( $meta['_seedprod_css'] ) ) {
				$css = str_replace( 'TO_BE_REPLACED', home_url(), $meta['_seedprod_css'] );
			} else {
				// Fallback to post_content if no meta.
				$css = str_replace( 'TO_BE_REPLACED', home_url(), $post_content );
			}

			if ( isset( $meta['_seedprod_custom_css'] ) ) {
				$custom_css = str_replace( 'TO_BE_REPLACED', home_url(), $meta['_seedprod_custom_css'] );
			}
			// Old logic explicitly sets custom_css to empty string.
			$custom_css = '';

			if ( isset( $meta['_seedprod_builder_css'] ) ) {
				$builder_css = str_replace( 'TO_BE_REPLACED', home_url(), $meta['_seedprod_builder_css'] );
			}

			// Update all CSS meta fields.
			update_post_meta( $post_id, '_seedprod_css', $css );
			update_post_meta( $post_id, '_seedprod_custom_css', $custom_css );
			update_post_meta( $post_id, '_seedprod_builder_css', $builder_css );

			// Set BOTH option names (old system uses both).
			update_option( 'global_css_page_id', $post_id );

			// Trash current CSS file and set new pointer (following old logic).
			$current_css_file = get_option( 'seedprod_global_css_page_id' );
			if ( ! empty( $current_css_file ) ) {
				wp_trash_post( $current_css_file );
			}
			update_option( 'seedprod_global_css_page_id', $post_id );

			// Generate CSS file with combined CSS.
			$combined_css = $css . $custom_css;
			if ( function_exists( 'seedprod_lite_generate_css_file' ) ) {
				seedprod_lite_generate_css_file( $post_id, $combined_css );
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

	return $imported_pages;
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
		throw new Exception( __( 'Failed to save export JSON file.', 'coming-soon' ) );
	}

	// Create ZIP file.
	if ( ! class_exists( 'ZipArchive' ) ) {
		throw new Exception( __( 'ZipArchive class not available. Please contact your host.', 'coming-soon' ) );
	}

	$zip          = new ZipArchive();
	$zip_filename = 'seedprod-' . $type . '-export-' . gmdate( 'Y-m-d-His' ) . '.zip';
	$zip_path     = $export_dir . $zip_filename;

	$zip_open_result = $zip->open( $zip_path, ZipArchive::CREATE );
	if ( true !== $zip_open_result ) {
		throw new Exception( __( 'Cannot create ZIP file. Error code: ', 'coming-soon' ) . $zip_open_result );
	}

	// Add JSON file.
	if ( ! $zip->addFile( $json_path, $json_filename ) ) {
		$zip->close();
		throw new Exception( __( 'Failed to add JSON file to ZIP.', 'coming-soon' ) );
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
		throw new Exception( __( 'ZIP file was not created successfully.', 'coming-soon' ) );
	}

	$zip_size = filesize( $zip_path );

	// Clean up temporary files (keep ZIP for download).
	wp_delete_file( $json_path );
	foreach ( $filenames as $filename ) {
		wp_delete_file( $export_dir . $filename );
	}

	// Return download URL via JSON (AJAX-friendly approach).
	$download_url = trailingslashit( $upload_dir['baseurl'] ) . 'seedprod-themes-exports/' . $zip_filename;

	return array(
		'success'      => true,
		'download_url' => $download_url,
		'filename'     => $zip_filename,
		'size'         => $zip_size,
	);
}
