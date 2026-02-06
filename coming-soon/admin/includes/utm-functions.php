<?php
/**
 * UTM tracking and external link functions for SeedProd Admin
 *
 * All functions must use seedprod_lite_ prefix (renamed to seedprod_lite_ in build)
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get support/documentation link with UTM parameters
 *
 * @param string $path     Optional path to append to base URL.
 * @param string $source   UTM source (default: 'WordPress').
 * @param string $medium   UTM medium context.
 * @param string $campaign UTM campaign (default: 'proplugin' or 'liteplugin').
 * @return string Full URL with UTM parameters.
 */
function seedprod_lite_get_support_link( $path = '', $source = 'WordPress', $medium = 'admin-header', $campaign = '' ) {
	// For Lite version, redirect support links to WordPress.org forums.
	if ( function_exists( 'seedprod_lite_v2_is_lite_view' ) && seedprod_lite_v2_is_lite_view() ) {
		return 'https://wordpress.org/support/plugin/coming-soon/';
	}

	$base_url = 'https://www.seedprod.com/docs/';

	// Add path if provided.
	if ( ! empty( $path ) ) {
		$base_url .= ltrim( $path, '/' );
	}

	// Determine campaign based on build.
	if ( empty( $campaign ) ) {
		$is_lite_view = seedprod_lite_v2_is_lite_view();
		$campaign     = ! $is_lite_view ? 'proplugin' : 'liteplugin';
	}

	// Get referrer if exists.
	$referrer    = '';
	$referred_by = get_option( 'seedprod_referred_by' );
	if ( ! empty( $referred_by ) ) {
		$source .= '-' . $referred_by;
	}

	// Build UTM parameters.
	$utm_params = array(
		'utm_source'   => sanitize_key( $source ),
		'utm_medium'   => sanitize_key( $medium ),
		'utm_campaign' => sanitize_key( $campaign ),
	);

	// Add version for tracking.
	$utm_params['utm_content'] = SEEDPROD_VERSION;

	// Apply filter for customization.
	$utm_params = apply_filters( 'seedprod_lite_admin_utm_params', $utm_params, $path, $medium );

	return add_query_arg( $utm_params, $base_url );
}

/**
 * Get upgrade link with UTM parameters (Lite only)
 *
 * @param string $medium   UTM medium context.
 * @param string $campaign UTM campaign.
 * @return string Full upgrade URL with UTM parameters.
 */
function seedprod_lite_get_upgrade_link( $medium = 'admin-header', $campaign = 'liteplugin' ) {
	$base_url = 'https://www.seedprod.com/lite-upgrade/';

	// Get referrer if exists.
	$source      = 'WordPress';
	$referred_by = get_option( 'seedprod_referred_by' );
	if ( ! empty( $referred_by ) ) {
		$source .= '-' . $referred_by;
	}

	// Build UTM parameters.
	$utm_params = array(
		'utm_source'   => sanitize_key( $source ),
		'utm_medium'   => sanitize_key( $medium ),
		'utm_campaign' => sanitize_key( $campaign ),
	);

	// Apply filter for customization.
	$utm_params = apply_filters( 'seedprod_lite_upgrade_utm_params', $utm_params, $medium );

	return add_query_arg( $utm_params, $base_url );
}

/**
 * Get pricing/purchase link with UTM parameters
 *
 * @param string $medium   UTM medium context.
 * @param string $campaign UTM campaign.
 * @return string Full pricing URL with UTM parameters.
 */
function seedprod_lite_get_pricing_link( $medium = 'settings-license', $campaign = 'proplugin' ) {
	$base_url = 'https://www.seedprod.com/pricing/';

	// Get referrer if exists.
	$source      = 'WordPress';
	$referred_by = get_option( 'seedprod_referred_by' );
	if ( ! empty( $referred_by ) ) {
		$source .= '-' . $referred_by;
	}

	// Build UTM parameters.
	$utm_params = array(
		'utm_source'   => sanitize_key( $source ),
		'utm_medium'   => sanitize_key( $medium ),
		'utm_campaign' => sanitize_key( $campaign ),
	);

	// Apply filter for customization.
	$utm_params = apply_filters( 'seedprod_lite_pricing_utm_params', $utm_params, $medium );

	return add_query_arg( $utm_params, $base_url );
}

/**
 * Get external link with UTM parameters
 *
 * @param string $url      Base URL.
 * @param string $medium   UTM medium context.
 * @param string $campaign UTM campaign.
 * @param string $source   UTM source.
 * @return string Full URL with UTM parameters.
 */
function seedprod_lite_get_external_link( $url, $medium = '', $campaign = '', $source = 'WordPress' ) {
	// If no UTM params needed, return URL as is.
	if ( empty( $medium ) && empty( $campaign ) ) {
		return $url;
	}

	// Determine campaign based on build if not provided.
	if ( empty( $campaign ) ) {
		$is_lite_view = seedprod_lite_v2_is_lite_view();
		$campaign     = ! $is_lite_view ? 'proplugin' : 'liteplugin';
	}

	// Build UTM parameters.
	$utm_params = array();

	if ( ! empty( $source ) ) {
		$utm_params['utm_source'] = sanitize_key( $source );
	}

	if ( ! empty( $medium ) ) {
		$utm_params['utm_medium'] = sanitize_key( $medium );
	}

	if ( ! empty( $campaign ) ) {
		$utm_params['utm_campaign'] = sanitize_key( $campaign );
	}

	// Apply filter for customization.
	$utm_params = apply_filters( 'seedprod_lite_external_utm_params', $utm_params, $url, $medium );

	return add_query_arg( $utm_params, $url );
}
