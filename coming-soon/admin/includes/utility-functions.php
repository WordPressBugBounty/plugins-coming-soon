<?php
/**
 * Utility functions for SeedProd Admin
 *
 * All functions must use seedprod_lite_ or seedprod_lite_v2_ prefix (renamed to seedprod_lite_* in build)
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Welcome Page On Activation Redirect (V2 Admin)
 * Handles the redirect after plugin activation to the appropriate welcome page
 *
 * @return void
 */
function seedprod_lite_v2_welcome_screen_do_activation_redirect() {
	// Check PHP Version.
	if ( version_compare( phpversion(), '5.3.3', '<=' ) ) {
		wp_die(
			esc_html__( "The minimum required version of PHP to run this plugin is PHP Version 5.3.3. Please contact your hosting company and ask them to upgrade this site's php version.", 'coming-soon' ),
			esc_html__( 'Upgrade PHP', 'coming-soon' ),
			200
		);
	}

	// Bail if no activation redirect.
	if ( ! get_transient( '_seedprod_welcome_screen_activation_redirect' ) ) {
		return;
	}

	// Delete the redirect transient.
	delete_transient( '_seedprod_welcome_screen_activation_redirect' );

	// Bail if activating from network, or bulk.
	$activate_multi = isset( $_GET['activate-multi'] ) ? sanitize_text_field( wp_unslash( $_GET['activate-multi'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( is_network_admin() || null !== $activate_multi ) {
		return;
	}

	// Check build type and redirect accordingly.
	$is_lite_view = seedprod_lite_v2_is_lite_view();
	if ( ! $is_lite_view ) {
		// Pro version goes directly to V2 dashboard (WordPress-native).
		// Set dismiss setup wizard flag for backward compatibility.
		update_option( 'seedprod_dismiss_setup_wizard', true );

		// One time flush permalinks for custom post types.
		if ( empty( get_option( 'seedprod_onetime_flush_rewrite' ) ) ) {
			flush_rewrite_rules();
			update_option( 'seedprod_onetime_flush_rewrite', true );
		}

		// Set default app settings if not exists.
		$seedprod_app_settings = get_option( 'seedprod_app_settings' );
		if ( empty( $seedprod_app_settings ) ) {
			require_once SEEDPROD_PLUGIN_PATH . 'resources/data-templates/default-settings.php';
			update_option( 'seedprod_app_settings', $seedprod_app_default_settings );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'seedprod_lite' ), admin_url( 'admin.php' ) ) );
		exit();
	} else {
		// Lite version - check if setup wizard was dismissed.
		$seedprod_dismiss_setup_wizard = get_option( 'seedprod_dismiss_setup_wizard' );

		if ( ! empty( $seedprod_dismiss_setup_wizard ) ) {
			// Wizard was dismissed, go to dashboard.
			wp_safe_redirect( add_query_arg( array( 'page' => 'seedprod_lite' ), admin_url( 'admin.php' ) ) );
		} else {
			// Show welcome page with setup wizard.
			wp_safe_redirect( add_query_arg( array( 'page' => 'seedprod_lite_welcome' ), admin_url( 'admin.php' ) ) );
		}
		exit();
	}
}

/**
 * Check if current page is a SeedProd admin page
 *
 * @return boolean True if on a SeedProd admin page.
 */
function seedprod_lite_is_admin_page() {
	$screen = get_current_screen();
	return ( strpos( $screen->id, 'seedprod' ) !== false );
}

/**
 * Get the current admin page context for UTM tracking
 *
 * @return string Page context string.
 */
function seedprod_lite_get_admin_page_context() {
	$screen = get_current_screen();

	// Handle case when screen is not available (e.g., AJAX calls).
	if ( ! $screen || ! isset( $screen->id ) ) {
		// Try to get from $_GET parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for context detection.
		if ( isset( $_GET['page'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for context detection.
			$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			if ( 'seedprod_lite' === $page ) {
				return 'dashboard';
			} elseif ( strpos( $page, 'seedprod_lite_welcome' ) !== false ) {
				return 'welcome';
			} elseif ( strpos( $page, 'seedprod_lite_settings' ) !== false ) {
				return 'settings';
			} elseif ( strpos( $page, 'seedprod_lite_pages' ) !== false ) {
				return 'landing-pages';
			} elseif ( strpos( $page, 'seedprod_lite_about' ) !== false ) {
				return 'about';
			}
		}
		return 'admin';
	}

	// Extract page name from screen ID.
	if ( 'toplevel_page_seedprod_lite' === $screen->id ) {
		return 'dashboard';
	} elseif ( strpos( $screen->id, 'seedprod_lite_welcome' ) !== false ) {
		return 'welcome';
	} elseif ( strpos( $screen->id, 'seedprod_lite_settings' ) !== false ) {
		return 'settings';
	} elseif ( strpos( $screen->id, 'seedprod_lite_pages' ) !== false ) {
		return 'landing-pages';
	} elseif ( strpos( $screen->id, 'seedprod_lite_about' ) !== false ) {
		return 'about';
	}

	// Default fallback.
	return 'admin';
}

/**
 * Check if we should show Lite version view
 *
 * Checks both build constant and test parameter for Pro build testing
 *
 * @return boolean True if Lite view should be shown.
 */
function seedprod_lite_v2_is_lite_view() {
	// Check if it's a Lite build.
	if ( 'lite' === SEEDPROD_BUILD ) {
		return true;
	}

	// Check for test parameter in Pro build.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation for testing purposes.
	return isset( $_GET['test_lite'] ) && '1' === $_GET['test_lite'];
}

/**
 * Get user IP address (V2 Admin)
 * Helper function needed by license validation
 *
 * @return string IP address.
 */
function seedprod_lite_v2_get_ip() {
	// Check if ip is from the share internet.
	$ip = '';
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// Check if ip pass from proxy.
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	return $ip;
}

/**
 * Check wizard service availability (V2 Admin)
 * Checks if the SaaS wizard service is available before redirecting
 *
 * @return void
 */
function seedprod_lite_v2_check_wizard_availability() {
	// Verify nonce.
	if ( ! check_ajax_referer( 'seedprod_lite_v2_check_wizard_availability', 'nonce', false ) ) {
		wp_send_json_error( array( 'available' => false ) );
	}

	// Build the exact same wizard URL that we'll redirect to.
	$site_token     = get_option( 'seedprod_token' );
	$admin_email    = get_option( 'admin_email' );
	$plugin_version = SEEDPROD_VERSION;
	$admin_url      = admin_url();

	// Build base URL.
	$base_url = untrailingslashit( SEEDPROD_WEB_API_URL );

	// Get upgrade URL for the check.
	$upgrade_url = seedprod_lite_v2_get_upgrade_url( 'onboarding', 'welcome' );

	// Build the exact wizard URL with all parameters.
	$check_url = sprintf(
		'%s/setup-wizard-seedprod_lite?token=%s&return=%s&version=%s&utm_campaign=%s&email=%s&upgrade_to_pro_url=%s',
		$base_url,
		rawurlencode( $site_token ),
		rawurlencode( base64_encode( $admin_url ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Used for URL encoding, not obfuscation.
		rawurlencode( $plugin_version ),
		rawurlencode( 'onboarding_seedprod_lite' ),
		rawurlencode( $admin_email ),
		rawurlencode( $upgrade_url )
	);

	// Use WordPress HTTP API to check availability.
	$response = wp_remote_head(
		$check_url,
		array(
			'timeout'     => 3,
			'redirection' => 0,
			'sslverify'   => false,
		)
	);

	// Check if request was successful.
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'available' => false ) );
	}

	// Check response code.
	$response_code = wp_remote_retrieve_response_code( $response );

	// Consider 200-399 as available (2xx success, 3xx redirects are OK).
	if ( $response_code >= 200 && $response_code < 400 ) {
		wp_send_json_success( array( 'available' => true ) );
	} else {
		wp_send_json_error( array( 'available' => false ) );
	}
}

/**
 * Get news items from SeedProd RSS feed
 *
 * @param integer $max_items Maximum number of items to retrieve.
 * @return array Array of news items or empty array on error.
 */
function seedprod_lite_v2_get_news_feed( $max_items = 5 ) {
	// Include WordPress feed functions.
	include_once ABSPATH . WPINC . '/feed.php';

	// Use WordPress's fetch_feed function which handles caching properly.
	$feed = fetch_feed( 'https://www.seedprod.com/category/release-notes/feed/' );

	$news_items = array();

	if ( ! is_wp_error( $feed ) ) {
		// Get items from feed.
		$items = $feed->get_items( 0, $max_items );

		foreach ( $items as $item ) {
			// Try to get thumbnail/featured image.
			$thumbnail = '';

			// Check for media:thumbnail.
			$enclosure = $item->get_enclosure();
			if ( $enclosure ) {
				$thumbnail = $enclosure->get_thumbnail();
			}

			// If no thumbnail, try to extract image from content.
			if ( empty( $thumbnail ) ) {
				$content = $item->get_content();
				if ( preg_match( '/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches ) ) {
					$thumbnail = esc_url( $matches[1] );
				}
			}

			// Get clean description text.
			$description = $item->get_description();
			// Strip all HTML tags and decode entities.
			$description = wp_strip_all_tags( html_entity_decode( $description, ENT_QUOTES, 'UTF-8' ) );
			// Remove any remaining special characters or pipes.
			$description = preg_replace( '/\|+/', '', $description );
			// Trim to reasonable length.
			$description = wp_trim_words( $description, 12, '...' );

			$news_items[] = array(
				'title'       => esc_html( $item->get_title() ),
				'description' => esc_html( $description ),
				'link'        => esc_url( $item->get_permalink() ),
				'date'        => $item->get_date( 'M j, Y' ),
				'thumbnail'   => $thumbnail,
			);
		}

		// Clean up.
		$feed->__destruct();
		unset( $feed );
	}

	return $news_items;
}
