<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {


	/**
	 * Import a landing page from a URL using WP-CLI.
	 *
	 * ## OPTIONS
	 *
	 * <landing-page-url>
	 * : The URL of the landing page to import.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seedprod_import_landing_page_from_url http://example.com/landing-page.zip
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	function seedprod_lite_import_landing_page_from_url( $args, $assoc_args ) {
		list( $theme_url ) = $args;

		$nonce = wp_create_nonce( 'seedprod_lite_import_landing_pages' );
		try {

			$response = wp_remote_head( $theme_url );

			if ( is_wp_error( $response ) ) {
				WP_CLI::error( 'An error occurred while checking the URL: ' . $response->get_error_message() );
			}

			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== $response_code ) {
				WP_CLI::error( 'The ZIP file does not exist at the provided URL.' );
			}

			$result = seedprod_lite_import_landing_page_cli( $theme_url, $nonce );

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( 'Failed to import landing pages: ' . $result->get_error_message() );
			}

			if ( is_array( $result ) && isset( $result['error'] ) ) {
				WP_CLI::error( 'Failed to import landing pages: ' . $result['error'] );
			}

			if ( is_array( $result ) && ! empty( $result['warnings'] ) ) {
				foreach ( $result['warnings'] as $w ) {
					WP_CLI::warning( sprintf( 'image not imported (%s): %s', $w['reason'], $w['url'] ) );
				}
			}

			$imported_pages = array();
			if ( is_array( $result ) && isset( $result['imported_pages'] ) ) {
				$imported_pages = $result['imported_pages'];
			} elseif ( is_array( $result ) && ! isset( $result['success'] ) ) {
				// Backwards-compat: helper used to return the raw imported_pages list.
				$imported_pages = $result;
			}

			// Output imported page IDs and titles. The V2 importer returns bare IDs;
			// the older one returned an array per page.
			foreach ( $imported_pages as $page ) {
				$page_id = is_array( $page ) && isset( $page['id'] ) ? $page['id'] : $page;
				WP_CLI::success( 'Imported Page ID: ' . $page_id );
			}

			//WP_CLI::success( 'Landing pages imported successfully.' );

		} catch ( Exception $e ) {
			WP_CLI::error( 'An error occurred: ' . $e->getMessage() );
		}
	}
	WP_CLI::add_command( 'seedprod_import_landing_page_from_url', 'seedprod_lite_import_landing_page_from_url' );

	/**
	 * Export a landing page using WP-CLI.
	 *
	 * @param string $theme_url The URL of the theme to import.
	 * @param string $nonce     The nonce value for security verification.
	 * @return mixed|array|string|array[] Depending on the outcome, may return various data or an error message.
	 */
	function seedprod_lite_import_landing_page_cli( $theme_url, $nonce ) {

		if ( ! wp_verify_nonce( $nonce, 'seedprod_lite_import_landing_pages' ) ) {
			return 'Invalid request. Please provide a valid nonce.';
		}

		$is_ajax_request = false;
		if ( null === $theme_url ) {
			$is_ajax_request = check_ajax_referer( 'seedprod_lite_import_landing_pages' );
		}

		if ( $is_ajax_request || ! empty( $theme_url ) ) {

			// The V2 importer and its helpers live in the admin layer, which does
			// not load under WP-CLI because is_admin() is false there.
			if ( ! function_exists( 'seedprod_lite_v2_extract_zip' ) ) {
				require_once SEEDPROD_PLUGIN_PATH . 'admin/includes/import-export-functions.php';
			}

			$url   = wp_nonce_url( 'admin.php?page=seedprod_lite_import_landing_pages', 'seedprod_import_landing_pages' );
			$creds = request_filesystem_credentials( $url, '', false, false, null );
			if ( false === $creds ) {
				return array( 'error' => 'Failed to obtain filesystem credentials.' );
			}

			if ( ! WP_Filesystem( $creds ) ) {
				request_filesystem_credentials( $url, '', true, false, null );
				return array( 'error' => 'Failed to initialize filesystem.' );
			}

			$source = ! empty( $theme_url ) ? $theme_url : '';

			$file_import_url_json = wp_remote_get( $source, array( 'sslverify' => false ) );
			if ( is_wp_error( $file_import_url_json ) ) {
				$error_code    = wp_remote_retrieve_response_code( $file_import_url_json );
				$error_message = wp_remote_retrieve_response_message( $file_import_url_json );
				return array( 'error' => $error_message );
			}

			$response_code = (int) wp_remote_retrieve_response_code( $file_import_url_json );
			if ( 200 !== $response_code ) {
				return array( 'error' => sprintf( 'The import URL returned HTTP %d.', $response_code ) );
			}

			if ( '' !== $source && $file_import_url_json['body'] ) {
				$url_data = pathinfo( $source );

				$filename = $url_data['basename'];

				if ( false !== strpos( $filename, '.zip' ) ) {
					$filename = substr( $filename, 0, strpos( $filename, '.zip' ) + 4 );
				}

				if ( ! preg_match( '/\.zip$/i', $filename ) ) {
					return array( 'error' => 'The file you are trying to upload is not a .zip file.' );
				}

				$filename_import = 'seedprod-themes-imports';

				global $wp_filesystem;
				$upload_dir   = wp_upload_dir();
				$path         = trailingslashit( $upload_dir['basedir'] );
				$path_baseurl = trailingslashit( $upload_dir['baseurl'] );

				$filenoext = basename( $filename_import, '.zip' ); // absolute path to the directory where zipper.php is in (lowercase).
				$filenoext = basename( $filenoext, '.ZIP' ); // absolute path to the directory where zipper.php is in (when uppercase).

				$targetdir  = $path . $filenoext; // target directory.
				$targetzip  = $path . $filename; // target zip file.
				$target_url = $path_baseurl . $filenoext;

				if ( is_dir( $targetdir ) ) {
					seedprod_lite_v2_recursive_rmdir( $targetdir );
				}
				wp_mkdir_p( $targetdir );

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem may not be initialized in CLI context.
				if ( file_put_contents( $targetzip, $file_import_url_json['body'] ) ) {
					$extract_result = seedprod_lite_v2_extract_zip( $targetzip, $targetdir, false );
					wp_delete_file( $targetzip );
					if ( is_wp_error( $extract_result ) ) {
						return array( 'error' => $extract_result->get_error_message() );
					}

					$theme_json_data     = $targetdir . '/export_page.json';
					$theme_json_data_url = $target_url . '/export_page.json';

					if ( ! file_exists( $theme_json_data ) ) {
						return array( 'error' => 'The ZIP did not contain export_page.json.' );
					}

					$data = seedprod_lite_v2_read_json_file( $theme_json_data, $theme_json_data_url );
					if ( is_wp_error( $data ) ) {
						return array( 'error' => $data->get_error_message() );
					}

					if ( ! empty( $data->type ) && 'landing-page' !== $data->type ) {
						return array( 'error' => 'This does not appear to be a SeedProd landing page.' );
					}

					$import_result = seedprod_lite_v2_landing_import_json( $data );
					// remove the json file for security.
					wp_delete_file( $theme_json_data );

					return $import_result;
				} else {
					return array( 'error' => 'There was a problem with the upload. Please try again.' );
				}
			} else {
				return array( 'error' => 'There was a problem with the upload. Please try again.' );
			}
		}
	}


	/**
	 * Enable or disable the coming soon page using WP-CLI.
	 *
	 * ## OPTIONS
	 *
	 * <true|false>
	 * : Whether to enable or disable the coming soon page.
	 *
	 * [--page_id=<id>]
	 * : The ID of the page to use for coming soon.
	 *
	 * ## EXAMPLES
	 *
	 * wp seedprod_enable_coming_soon_page true --page_id=123
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	function seedprod_lite_enable_coming_soon_page( $args, $assoc_args ) {
		list( $enable ) = $args;
		$enable         = filter_var( $enable, FILTER_VALIDATE_BOOLEAN );

		$page_id = isset( $assoc_args['page_id'] ) ? intval( $assoc_args['page_id'] ) : null;

		// Generate a nonce.
		$nonce = wp_create_nonce( 'seedprod_enable_coming_soon_page' );

		try {
			// Call the function to enable/disable the coming soon page with nonce.
			$result = seedprod_enable_coming_soon_page_function_cli( $enable, $nonce, $page_id );

			if ( false !== $result ) {
				$action = $enable ? 'enabled' : 'disabled';
				WP_CLI::success( "Coming soon page $action successfully." );
			} else {
				$action = $enable ? 'enable' : 'disable';
				WP_CLI::error( "Failed to $action coming soon page." );
			}
		} catch ( Exception $e ) {
			WP_CLI::error( 'An error occurred: ' . $e->getMessage() );
		}
	}
	WP_CLI::add_command( 'seedprod_enable_coming_soon_page', 'seedprod_lite_enable_coming_soon_page' );

	/**
	 * Enable or disable the coming soon page.
	 *
	 * @param boolean      $enable  Whether to enable or disable the coming soon page.
	 * @param string       $nonce   The nonce for verification.
	 * @param integer|null $page_id The ID of the page to use for coming soon.
	 * @return boolean True on success, false on failure.
	 */
	function seedprod_enable_coming_soon_page_function_cli( $enable, $nonce, $page_id = null ) {
		// Verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'seedprod_enable_coming_soon_page' ) ) {
			return false;
		}

		$settings = get_option( 'seedprod_settings', array() );

		if ( ! is_array( $settings ) ) {
			// If settings are not an array, initialize it as an array.
			$settings = array();
		}

		// Update the settings with the new coming soon page status.
		$settings['enable_coming_soon_mode'] = $enable ? true : false;

		if ( $page_id ) {
			update_option( 'seedprod_coming_soon_page_id', $page_id );
		}

		// Update the option in the database.
		update_option( 'seedprod_settings', wp_json_encode( $settings ) );

		return true;
	}

}

