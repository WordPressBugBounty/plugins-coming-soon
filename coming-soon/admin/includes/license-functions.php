<?php
/**
 * License management functions for SeedProd Admin (V2)
 *
 * All functions must use seedprod_lite_v2_ prefix (renamed to seedprod_lite_v2_ in build)
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save/Activate API Key (V2 Admin)
 * Migrated from /app/license.php for new admin dashboard
 *
 * @param string|null $api_key The API key to validate (optional, will get from POST if not provided).
 * @return array|void Response array with status and message.
 */
function seedprod_lite_v2_save_api_key( $api_key = null ) {
	if ( check_ajax_referer( 'seedprod_nonce', '_wpnonce', false ) || ! empty( $api_key ) ) {
		if ( ! current_user_can( apply_filters( 'seedprod_license_capability', 'manage_options' ) ) ) {
			wp_send_json_error();
		}

		if ( empty( $api_key ) ) {
			$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : null;
		}

		if ( defined( 'SEEDPROD_LOCAL_JS' ) ) {
			$slug = 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php';
		} else {
			$slug = SEEDPROD_SLUG;
		}

		// Get token and generate one if one does not exist.
		$token = get_option( 'seedprod_token' );
		if ( empty( $token ) ) {
			$token = strtolower( wp_generate_password( 32, false, false ) );
			update_option( 'seedprod_token', $token );
		}

		// Validate the api key.
		$data = array(
			'action'            => 'info',
			'license_key'       => $api_key,
			'token'             => $token,
			'wp_version'        => get_bloginfo( 'version' ),
			'domain'            => home_url(),
			'installed_version' => SEEDPROD_VERSION,
			'slug'              => $slug,
		);

		if ( empty( $data['license_key'] ) ) {
			$response = array(
				'status' => 'false',
				'msg'    => __( 'License Key is Required.', 'coming-soon' ),
			);
			wp_send_json( $response );
			exit;
		}

		$headers = array();

		// Build the headers of the request.
		$headers = wp_parse_args(
			$headers,
			array(
				'Accept' => 'application/json',
			)
		);

		$url      = SEEDPROD_API_URL . 'update';
		$response = wp_remote_post(
			$url,
			array(
				'body'    => $data,
				'headers' => $headers,
			)
		);

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) ) {
			// Load utility functions for get_ip.
			if ( ! function_exists( 'seedprod_lite_v2_get_ip' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'utility-functions.php';
			}
			$response = array(
				'status' => 'false',
				'ip'     => seedprod_lite_v2_get_ip(),
				'msg'    => $response->get_error_message(),
			);
			wp_send_json( $response );
		}

		if ( 200 !== $status_code ) {
			// Load utility functions for get_ip.
			if ( ! function_exists( 'seedprod_lite_v2_get_ip' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'utility-functions.php';
			}
			$response = array(
				'status' => 'false',
				'ip'     => seedprod_lite_v2_get_ip(),
				'msg'    => $response['response']['message'],
			);
			wp_send_json( $response );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! empty( $body ) ) {
			$body = json_decode( $body );
		}

		if ( ! empty( $body->valid ) && true === $body->valid ) {
			// Store API key.
			update_option( 'seedprod_user_id', $body->user_id );
			update_option( 'seedprod_api_token', $body->api_token );
			update_option( 'seedprod_api_key', $data['license_key'] );
			update_option( 'seedprod_api_message', $body->message );
			update_option( 'seedprod_license_name', $body->license_name );
			update_option( 'seedprod_a', true );
			update_option( 'seedprod_per', $body->per );
			$response = array(
				'status'       => 'true',
				/* translators: 1. License name.*/
				'license_name' => sprintf( __( 'You currently have the <strong>%s</strong> license.', 'coming-soon' ), $body->license_name ),
				'msg'          => $body->message,
				'body'         => $body,
			);
		} elseif ( isset( $body->valid ) && false === $body->valid ) {
			$api_msg = __( 'Invalid License Key.', 'coming-soon' );
			if ( 'Unauthenticated.' !== $body->message ) {
				$api_msg = $body->message;
			}
			update_option( 'seedprod_license_name', '' );
			update_option( 'seedprod_api_token', '' );
			update_option( 'seedprod_api_key', '' );
			update_option( 'seedprod_api_message', $api_msg );
			update_option( 'seedprod_a', false );
			update_option( 'seedprod_per', '' );
			$response = array(
				'status'       => 'false',
				'license_name' => '',
				'msg'          => $api_msg,
				'body'         => $body,
			);
		}

		// Send Response.
		if ( ! empty( $_POST['api_key'] ) ) {
			wp_send_json( $response );
			exit;
		} else {
			return $response;
		}
	}
}


/**
 * Save App Settings (V2 Admin)
 * Saves global application settings
 *
 * @return void
 */
function seedprod_lite_v2_save_app_settings() {
	if ( ! check_ajax_referer( 'seedprod_settings_save', '_wpnonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'coming-soon' ) ) );
	}

	if ( ! current_user_can( apply_filters( 'seedprod_save_app_settings_capability', 'manage_options' ) ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'coming-soon' ) ) );
	}

	if ( ! empty( $_POST['app_settings'] ) ) {
		$app_settings = wp_unslash( $_POST['app_settings'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Get existing settings first.
		$existing_settings_json = get_option( 'seedprod_app_settings' );
		$existing_settings      = ! empty( $existing_settings_json ) ? json_decode( $existing_settings_json, true ) : array();

		// Merge with existing settings to preserve any settings not in this form.
		$new_app_settings = is_array( $existing_settings ) ? $existing_settings : array();

		// Edit Button - properly handle boolean values from JavaScript.
		$new_app_settings['disable_seedprod_button'] = isset( $app_settings['disable_seedprod_button'] ) &&
			( true === $app_settings['disable_seedprod_button'] || 'true' === $app_settings['disable_seedprod_button'] || '1' === $app_settings['disable_seedprod_button'] || 1 === $app_settings['disable_seedprod_button'] );

		// Usage Tracking - properly handle boolean values from JavaScript.
		$new_app_settings['enable_usage_tracking'] = isset( $app_settings['enable_usage_tracking'] ) &&
			( true === $app_settings['enable_usage_tracking'] || 'true' === $app_settings['enable_usage_tracking'] || '1' === $app_settings['enable_usage_tracking'] || 1 === $app_settings['enable_usage_tracking'] );
		update_option( 'seedprod_allow_usage_tracking', $new_app_settings['enable_usage_tracking'] );

		// Notifications - properly handle boolean values from JavaScript.
		$new_app_settings['disable_seedprod_notification'] = isset( $app_settings['disable_seedprod_notification'] ) &&
			( true === $app_settings['disable_seedprod_notification'] || 'true' === $app_settings['disable_seedprod_notification'] || '1' === $app_settings['disable_seedprod_notification'] || 1 === $app_settings['disable_seedprod_notification'] );

		// API Keys (Pro only).
		$is_lite_view = seedprod_lite_v2_is_lite_view();
		if ( ! $is_lite_view ) {
			$new_app_settings['facebook_g_app_id']     = isset( $app_settings['facebook_g_app_id'] ) ? sanitize_text_field( $app_settings['facebook_g_app_id'] ) : '';
			$new_app_settings['google_places_app_key'] = isset( $app_settings['google_places_app_key'] ) ? sanitize_text_field( $app_settings['google_places_app_key'] ) : '';
			$new_app_settings['yelp_app_api_key']      = isset( $app_settings['yelp_app_api_key'] ) ? sanitize_text_field( $app_settings['yelp_app_api_key'] ) : '';
		}

		// Save settings.
		$app_settings_json = wp_json_encode( $new_app_settings );
		update_option( 'seedprod_app_settings', $app_settings_json );

		wp_send_json_success(
			array(
				'message' => __( 'Settings saved successfully!', 'coming-soon' ),
			)
		);
	} else {
		wp_send_json_error(
			array(
				'message' => __( 'No settings data received.', 'coming-soon' ),
			)
		);
	}
}
