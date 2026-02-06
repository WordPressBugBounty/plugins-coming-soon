<?php
/**
 * System Information functions for SeedProd Admin (V2)
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get System Info (V2 Admin)
 * Returns a string containing system information for support
 *
 * @return string System information
 */
function seedprod_lite_v2_get_system_info() {
	global $wpdb;

	// Get theme info.
	$theme_data   = wp_get_theme();
	$theme_name   = $theme_data->get( 'Name' );
	$theme_version = $theme_data->get( 'Version' );
	$theme        = $theme_name . ' ' . $theme_version;

	$return = '### Begin System Info ###' . "\n\n";

	// SeedProd info.
	$return .= '-- SeedProd Info' . "\n\n";
	$return .= 'Version:                  ' . SEEDPROD_VERSION . "\n";
	$return .= 'Build:                    ' . SEEDPROD_BUILD . "\n";

	// Site info.
	$return .= "\n" . '-- Site Info' . "\n\n";
	$return .= 'Site URL:                 ' . site_url() . "\n";
	$return .= 'Home URL:                 ' . home_url() . "\n";
	$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	// WordPress configuration.
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . get_locale() . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if front page is set to 'page'.
	if ( 'page' === get_option( 'show_on_front' ) ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id  = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( 0 !== $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( 0 !== $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	$return .= 'ABSPATH:                  ' . ABSPATH . "\n";

	// Make sure wp_remote_post() is working.
	$request['cmd'] = '_notify-validate';

	$params = array(
		'sslverify' => false,
		'timeout'   => 60,
		'body'      => $request,
	);

	$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

	if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
		$wp_remote_post_status = 'wp_remote_post() works';
	} else {
		$wp_remote_post_status = 'wp_remote_post() does not work';
	}

	$return .= 'Remote Post:              ' . $wp_remote_post_status . "\n";
	$return .= 'Table Prefix:             Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";

	// Display PHP Environment.
	$return .= "\n" . '-- PHP Environment' . "\n\n";
	$return .= 'Version:                  ' . PHP_VERSION . "\n";
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

	// PHP extensions and such.
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	// Session stuff.
	$return .= "\n" . '-- Session Configuration' . "\n\n";
	$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

	// The rest of this is only relevant if session is enabled.
	if ( isset( $_SESSION ) ) {
		$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
		$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
		$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
		$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
		$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
	}

	// Get plugins that have an update.
	$updates = get_plugin_updates();

	// WordPress active plugins.
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

	$plugins        = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( ! in_array( $plugin_path, $active_plugins, true ) ) {
			continue;
		}

		$update     = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$plugin_url = '';
		if ( ! empty( $plugin['PluginURI'] ) ) {
			$plugin_url = $plugin['PluginURI'];
		} elseif ( ! empty( $plugin['AuthorURI'] ) ) {
			$plugin_url = $plugin['AuthorURI'];
		} elseif ( ! empty( $plugin['Author'] ) ) {
			$plugin_url = $plugin['Author'];
		}
		if ( $plugin_url ) {
			$plugin_url = "\n" . $plugin_url;
		}
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . $plugin_url . "\n\n";
	}

	// WordPress inactive plugins.
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( in_array( $plugin_path, $active_plugins, true ) ) {
			continue;
		}

		$update     = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$plugin_url = '';
		if ( ! empty( $plugin['PluginURI'] ) ) {
			$plugin_url = $plugin['PluginURI'];
		} elseif ( ! empty( $plugin['AuthorURI'] ) ) {
			$plugin_url = $plugin['AuthorURI'];
		} elseif ( ! empty( $plugin['Author'] ) ) {
			$plugin_url = $plugin['Author'];
		}
		if ( $plugin_url ) {
			$plugin_url = "\n" . $plugin_url;
		}
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . $plugin_url . "\n\n";
	}

	if ( is_multisite() ) {
		// WordPress Multisite active plugins.
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins        = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$update     = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$plugin     = get_plugin_data( $plugin_path );
			$plugin_url = '';
			if ( ! empty( $plugin['PluginURI'] ) ) {
				$plugin_url = $plugin['PluginURI'];
			} elseif ( ! empty( $plugin['AuthorURI'] ) ) {
				$plugin_url = $plugin['AuthorURI'];
			} elseif ( ! empty( $plugin['Author'] ) ) {
				$plugin_url = $plugin['Author'];
			}
			if ( $plugin_url ) {
				$plugin_url = "\n" . $plugin_url;
			}
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . $plugin_url . "\n\n";
		}
	}

	// Server configuration.
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'N/A' ) . "\n";

	$return .= "\n" . '### End System Info ###';

	return $return;
}
