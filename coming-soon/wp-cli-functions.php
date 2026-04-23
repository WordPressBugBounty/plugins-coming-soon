<?php
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

			$imported_pages = seedprod_lite_import_landing_page_cli( $theme_url, $nonce );

			if ( is_wp_error( $imported_pages ) ) {
				WP_CLI::error( 'Failed to import landing pages: ' . $imported_pages->get_error_message() );
			}

			// Output imported page IDs and titles.
			foreach ( $imported_pages as $page ) {
				WP_CLI::success( 'Imported Page ID: ' . $page['id'] );
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

			$url   = wp_nonce_url( 'admin.php?page=seedprod_lite_import_landing_pages', 'seedprod_import_landing_pages' );
			$creds = request_filesystem_credentials( $url, '', false, false, null );
			if ( false === $creds ) {
				return array( 'error' => 'Failed to obtain filesystem credentials.' );
			}

			if ( ! WP_Filesystem( $creds ) ) {
				request_filesystem_credentials( $url, '', true, false, null );
				return array( 'error' => 'Failed to initialize filesystem.' );
			}

			$source = isset( $_REQUEST['seedprod_landing_url'] ) ? wp_kses_post( wp_unslash( $_REQUEST['seedprod_landing_url'] ) ) : '';

			if ( ! empty( $theme_url ) ) {
				$source = $theme_url;
			}

			$file_import_url_json = wp_remote_get( $source, array( 'sslverify' => false ) );
			if ( is_wp_error( $file_import_url_json ) ) {
				$error_code    = wp_remote_retrieve_response_code( $file_import_url_json );
				$error_message = wp_remote_retrieve_response_message( $file_import_url_json );
				return array( 'error' => $error_message );
			}

			preg_match( '/zip/', $file_import_url_json['headers']['content-type'], $match );
			if ( is_array( $match ) && count( $match ) <= 0 ) {
				return array( 'error' => 'Invalid file format. Please upload a .zip file.' );
			}

			if ( '' !== $source && $file_import_url_json['body'] ) {
				$url_data = pathinfo( $source );

				$filename = $url_data['basename'];
				$type     = $url_data['extension'];

				$filename = substr( $filename, 0, strpos( $filename, '.zip' ) + 4 );

				$name           = explode( '.', $filename );
				$accepted_types = array( 'application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed' );
				foreach ( $accepted_types as $mime_type ) {
					if ( $mime_type === $type ) {
						$okay = true;
						break;
					}
				}

				$continue = strtolower( $name[1] ) === 'zip' ? true : false;
				if ( ! $continue ) {
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
					recursive_rmdir( $targetdir );
				}
				wp_mkdir_p( $targetdir );

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem may not be initialized in CLI context.
				if ( file_put_contents( $targetzip, $file_import_url_json['body'] ) ) {
					$zip = new ZipArchive();
					$x   = $zip->open( $targetzip );
					if ( true === $x ) {
						$zip->extractTo( $targetdir );
						$zip->close();

						wp_delete_file( $targetzip );
					}

					$theme_json_data     = $targetdir . '/export_page.json';
					$theme_json_data_url = $target_url . '/export_page.json';

					if ( file_exists( $theme_json_data ) ) {
						$file_theme_json = wp_remote_get( $theme_json_data_url, array( 'sslverify' => false ) );
						if ( is_wp_error( $file_theme_json ) ) {
							$error_code    = wp_remote_retrieve_response_code( $file_theme_json );
							$error_message = wp_remote_retrieve_response_message( $file_theme_json );
							return array( 'error' => $error_message );
						}
						$data = json_decode( $file_theme_json['body'] );
						if ( ! empty( $data->type ) && 'landing-page' !== $data->type ) {
							return array( 'error' => 'This does not appear to be a SeedProd landing page.' );
						}
						$imported_pages = seedprod_lite_landing_import_json( $data );
						// remove the json file for security.
						wp_delete_file( $theme_json_data );

						return $imported_pages;
					}
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

