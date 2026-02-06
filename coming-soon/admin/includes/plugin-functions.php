<?php
/**
 * Plugin management functions for SeedProd Admin V2
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
 * Verify installed plugin matches expected plugin.
 *
 * @param string $plugin_id   The expected plugin ID.
 * @param array  $plugin_data The installed plugin's data.
 * @return bool True if plugin is valid, false otherwise.
 */
function seedprod_lite_v2_verify_installed_plugin( $plugin_id, $plugin_data ) {
	// Expected plugin names mapping.
	$expected_plugins = array(
		'rafflepress'          => array( 'RafflePress', 'RafflePress – WordPress Giveaway Plugin' ),
		'wpforms'              => array( 'WPForms Lite', 'Contact Form by WPForms', 'WPForms' ),
		'optinmonster'         => array( 'OptinMonster', 'OptinMonster API' ),
		'wpmailsmtp'           => array( 'WP Mail SMTP', 'WP Mail SMTP by WPForms' ),
		'monsterinsights'      => array( 'MonsterInsights', 'Google Analytics for WordPress by MonsterInsights' ),
		'exactmetrics'         => array( 'ExactMetrics', 'Google Analytics Dashboard for WP by ExactMetrics' ),
		'aioseo'               => array( 'All in One SEO', 'All in One SEO Pack', 'AIOSEO' ),
		'trustpulse'           => array( 'TrustPulse', 'TrustPulse API' ),
		'pushengage'           => array( 'PushEngage', 'PushEngage - Web Push Notifications' ),
		'wpcode'               => array( 'WPCode', 'Insert Headers and Footers', 'WPCode Lite' ),
		'wpconsent'            => array( 'WPConsent', 'WPConsent – Cookie Consent Banner' ),
		'instagramfeed'        => array( 'Instagram Feed', 'Smash Balloon Instagram Feed' ),
		'customfacebookfeed'   => array( 'Custom Facebook Feed', 'Smash Balloon Custom Facebook Feed' ),
		'customtwitterfeeds'   => array( 'Custom Twitter Feeds', 'Smash Balloon Custom Twitter Feeds' ),
		'feedsforyoutube'      => array( 'YouTube Feed', 'Feeds for YouTube' ),
		'sugarcalendar'        => array( 'Sugar Calendar', 'Sugar Calendar Lite' ),
		'wpsimplepay'          => array( 'WP Simple Pay', 'WP Simple Pay Lite', 'Stripe Payments' ),
		'easydigitaldownloads' => array( 'Easy Digital Downloads' ),
		'searchwp'             => array( 'SearchWP Live Ajax Search' ),
		'affiliatewp'          => array( 'AffiliateWP', 'AffiliateWP - Affiliate Area Shortcodes' ),
		'duplicator'           => array( 'Duplicator', 'Duplicator – WordPress Migration Plugin' ),
	);

	// Check if we have expected names for this plugin.
	if ( ! isset( $expected_plugins[ $plugin_id ] ) ) {
		return false;
	}

	$expected_names = $expected_plugins[ $plugin_id ];
	$installed_name = $plugin_data['Name'];

	// Check if installed plugin name matches any expected name.
	foreach ( $expected_names as $expected_name ) {
		if ( false !== stripos( $installed_name, $expected_name ) ||
			false !== stripos( $expected_name, $installed_name ) ) {
			return true;
		}
	}

	// Also check the plugin author for additional verification.
	$trusted_authors = array(
		'WPForms',
		'MonsterInsights',
		'OptinMonster',
		'RafflePress',
		'TrustPulse',
		'PushEngage',
		'ExactMetrics',
		'AIOSEO',
		'Smash Balloon',
		'Sugar Calendar',
		'WP Simple Pay',
		'Easy Digital Downloads',
		'SearchWP',
		'AffiliateWP',
		'WPBeginner',
		'WPCode',
		'Awesome Motive',
	);

	$plugin_author = $plugin_data['Author'] ?? '';
	foreach ( $trusted_authors as $trusted_author ) {
		if ( false !== stripos( $plugin_author, $trusted_author ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Get secure plugin download URL from plugin ID.
 * Server-side only mapping for security.
 *
 * @param string $plugin_id The plugin identifier.
 * @return string|false The download URL or false if invalid.
 */
function seedprod_lite_v2_get_plugin_download_url( $plugin_id ) {
	// Secure server-side URL mapping.
	$allowed_plugins = array(
		'rafflepress'          => 'https://downloads.wordpress.org/plugin/rafflepress.zip',
		'wpforms'              => 'https://downloads.wordpress.org/plugin/wpforms-lite.zip',
		'optinmonster'         => 'https://downloads.wordpress.org/plugin/optinmonster.zip',
		'wpmailsmtp'           => 'https://downloads.wordpress.org/plugin/wp-mail-smtp.zip',
		'monsterinsights'      => 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip',
		'exactmetrics'         => 'https://downloads.wordpress.org/plugin/google-analytics-dashboard-for-wp.zip',
		'aioseo'               => 'https://downloads.wordpress.org/plugin/all-in-one-seo-pack.zip',
		'trustpulse'           => 'https://downloads.wordpress.org/plugin/trustpulse-api.zip',
		'pushengage'           => 'https://downloads.wordpress.org/plugin/pushengage.zip',
		'wpcode'               => 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip',
		'wpconsent'            => 'https://downloads.wordpress.org/plugin/wpconsent-cookies-banner-privacy-suite.zip',
		'instagramfeed'        => 'https://downloads.wordpress.org/plugin/instagram-feed.zip',
		'customfacebookfeed'   => 'https://downloads.wordpress.org/plugin/custom-facebook-feed.zip',
		'customtwitterfeeds'   => 'https://downloads.wordpress.org/plugin/custom-twitter-feeds.zip',
		'feedsforyoutube'      => 'https://downloads.wordpress.org/plugin/feeds-for-youtube.zip',
		'sugarcalendar'        => 'https://downloads.wordpress.org/plugin/sugar-calendar-lite.zip',
		'wpsimplepay'          => 'https://downloads.wordpress.org/plugin/stripe.zip',
		'easydigitaldownloads' => 'https://downloads.wordpress.org/plugin/easy-digital-downloads.zip',
		'searchwp'             => 'https://downloads.wordpress.org/plugin/searchwp-live-ajax-search.zip',
		'affiliatewp'          => 'https://downloads.wordpress.org/plugin/affiliatewp-affiliate-area-shortcodes.zip',
		'duplicator'           => 'https://downloads.wordpress.org/plugin/duplicator.zip',
	);

	// Check if plugin ID exists in our allowed list.
	if ( ! isset( $allowed_plugins[ $plugin_id ] ) ) {
		return false;
	}

	$url = $allowed_plugins[ $plugin_id ];

	// Additional validation - ensure URL is from WordPress.org.
	$parsed_url    = wp_parse_url( $url );
	$allowed_hosts = array( 'downloads.wordpress.org', 'wordpress.org' );

	if ( ! in_array( $parsed_url['host'], $allowed_hosts, true ) ) {
		return false;
	}

	// Validate it's a zip file.
	if ( ! preg_match( '/\.zip$/i', $parsed_url['path'] ) ) {
		return false;
	}

	return $url;
}

/**
 * Get recommended plugins data.
 *
 * @return array Array of plugin data with metadata and installation info.
 */
function seedprod_lite_v2_get_recommended_plugins() {
	$plugins = array(
		'rafflepress'          => array(
			'slug_base' => 'rafflepress',
			'slug'      => 'rafflepress/rafflepress.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-rp.png',
			'name'      => __( 'RafflePress', 'coming-soon' ),
			'desc'      => __( 'Turn visitors into brand ambassadors with viral giveaways & contests.', 'coming-soon' ),
			'priority'  => 10,
		),
		'wpforms'              => array(
			'slug_base' => 'wpforms-lite',
			'slug'      => 'wpforms-lite/wpforms.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-wpforms.png',
			'name'      => __( 'WPForms', 'coming-soon' ),
			'desc'      => __( 'The most beginner friendly drag & drop WordPress forms plugin.', 'coming-soon' ),
			'priority'  => 9,
		),
		'optinmonster'         => array(
			'slug_base' => 'optinmonster',
			'slug'      => 'optinmonster/optin-monster-wp-api.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-om.png',
			'name'      => __( 'OptinMonster', 'coming-soon' ),
			'desc'      => __( 'Boost conversions with Exit-Intent® popups and optin forms.', 'coming-soon' ),
			'priority'  => 8,
		),
		'wpmailsmtp'           => array(
			'slug_base' => 'wp-mail-smtp',
			'slug'      => 'wp-mail-smtp/wp_mail_smtp.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-smtp.png',
			'name'      => __( 'WP Mail SMTP', 'coming-soon' ),
			'desc'      => __( 'Fix email deliverability issues with proper SMTP authentication.', 'coming-soon' ),
			'priority'  => 7,
		),
		'monsterinsights'      => array(
			'slug_base' => 'google-analytics-for-wordpress',
			'slug'      => 'google-analytics-for-wordpress/googleanalytics.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-mi.png',
			'name'      => __( 'MonsterInsights', 'coming-soon' ),
			'desc'      => __( 'Connect WordPress with Google Analytics to grow your business.', 'coming-soon' ),
			'priority'  => 6,
		),
		'trustpulse'           => array(
			'slug_base' => 'trustpulse-api',
			'slug'      => 'trustpulse-api/trustpulse.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-trustpulse.png',
			'name'      => __( 'TrustPulse', 'coming-soon' ),
			'desc'      => __( 'Use FOMO to boost sales with social proof notifications.', 'coming-soon' ),
			'priority'  => 5,
		),
		'aioseo'               => array(
			'slug_base' => 'all-in-one-seo-pack',
			'slug'      => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-aioseo.png',
			'name'      => __( 'All in One SEO', 'coming-soon' ),
			'desc'      => __( 'Improve your WordPress SEO rankings with comprehensive tools.', 'coming-soon' ),
			'priority'  => 4,
		),
		'pushengage'           => array(
			'slug_base' => 'pushengage',
			'slug'      => 'pushengage/main.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-pushengage.png',
			'name'      => __( 'PushEngage', 'coming-soon' ),
			'desc'      => __( 'Connect with visitors after they leave with web push notifications.', 'coming-soon' ),
			'priority'  => 3,
		),
		'wpcode'               => array(
			'slug_base' => 'insert-headers-and-footers',
			'slug'      => 'insert-headers-and-footers/ihaf.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-wpcode.png',
			'name'      => __( 'WPCode', 'coming-soon' ),
			'desc'      => __( 'Future proof your site with custom code snippets.', 'coming-soon' ),
			'priority'  => 2,
		),
		'exactmetrics'         => array(
			'slug_base' => 'google-analytics-dashboard-for-wp',
			'slug'      => 'google-analytics-dashboard-for-wp/gadwp.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-exactmetrics.png',
			'name'      => __( 'ExactMetrics', 'coming-soon' ),
			'desc'      => __( 'Setup Google Analytics tracking without writing code.', 'coming-soon' ),
			'priority'  => 1,
		),
		'instagramfeed'        => array(
			'slug_base' => 'instagram-feed',
			'slug'      => 'instagram-feed/instagram-feed.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-social-photo-feed.png',
			'name'      => __( 'Smash Balloon Social Photo Feed', 'coming-soon' ),
			'desc'      => __( 'Display Instagram content on your site without code.', 'coming-soon' ),
			'priority'  => 1,
		),
		'customfacebookfeed'   => array(
			'slug_base' => 'custom-facebook-feed',
			'slug'      => 'custom-facebook-feed/custom-facebook-feed.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-custom-facebook-feed.png',
			'name'      => __( 'Smash Balloon Social Post Feed', 'coming-soon' ),
			'desc'      => __( 'Display Facebook content including albums and reviews.', 'coming-soon' ),
			'priority'  => 1,
		),
		'customtwitterfeeds'   => array(
			'slug_base' => 'custom-twitter-feeds',
			'slug'      => 'custom-twitter-feeds/custom-twitter-feed.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-custom-twitter-feeds.png',
			'name'      => __( 'Smash Balloon Twitter Feeds', 'coming-soon' ),
			'desc'      => __( 'Display Twitter content with multiple layouts and moderation.', 'coming-soon' ),
			'priority'  => 1,
		),
		'feedsforyoutube'      => array(
			'slug_base' => 'feeds-for-youtube',
			'slug'      => 'feeds-for-youtube/youtube-feed.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-feeds-for-youtube.png',
			'name'      => __( 'Smash Balloon YouTube Feeds', 'coming-soon' ),
			'desc'      => __( 'Display YouTube videos and live streams on your site.', 'coming-soon' ),
			'priority'  => 1,
		),
		'sugarcalendar'        => array(
			'slug_base' => 'sugar-calendar-lite',
			'slug'      => 'sugar-calendar-lite/sugar-calendar-lite.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-sugarcalendar.png',
			'name'      => __( 'Sugar Calendar', 'coming-soon' ),
			'desc'      => __( 'Simple event calendar with ticketing and scheduling.', 'coming-soon' ),
			'priority'  => 1,
		),
		'wpsimplepay'          => array(
			'slug_base' => 'stripe',
			'slug'      => 'stripe/stripe-checkout.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-stripe.png',
			'name'      => __( 'WP Simple Pay', 'coming-soon' ),
			'desc'      => __( 'Accept Stripe payments without a shopping cart.', 'coming-soon' ),
			'priority'  => 1,
		),
		'easydigitaldownloads' => array(
			'slug_base' => 'easy-digital-downloads',
			'slug'      => 'easy-digital-downloads/easy-digital-downloads.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-easydigitaldownloads.png',
			'name'      => __( 'Easy Digital Downloads', 'coming-soon' ),
			'desc'      => __( 'Sell digital downloads, software, music, and more.', 'coming-soon' ),
			'priority'  => 1,
		),
		'searchwp'             => array(
			'slug_base' => 'searchwp-live-ajax-search',
			'slug'      => 'searchwp-live-ajax-search/searchwp-live-ajax-search.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-searchwp.svg',
			'name'      => __( 'SearchWP', 'coming-soon' ),
			'desc'      => __( 'Advanced WordPress search with custom algorithms.', 'coming-soon' ),
			'priority'  => 1,
		),
		'affiliatewp'          => array(
			'slug_base' => 'affiliatewp-affiliate-area-shortcodes',
			'slug'      => 'affiliatewp-affiliate-area-shortcodes/affiliatewp-affiliate-area-shortcodes.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-affiliatewp.png',
			'name'      => __( 'AffiliateWP', 'coming-soon' ),
			'desc'      => __( 'Create an affiliate program to grow sales with referrals.', 'coming-soon' ),
			'priority'  => 1,
		),
		'duplicator'           => array(
			'slug_base' => 'duplicator',
			'slug'      => 'duplicator/duplicator.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-duplicator.png',
			'name'      => __( 'Duplicator', 'coming-soon' ),
			'desc'      => __( 'Backup, migrate, and clone your WordPress site with one click.', 'coming-soon' ),
			'priority'  => 11,
		),
		'wpconsent'            => array(
			'slug_base' => 'wpconsent-cookies-banner-privacy-suite',
			'slug'      => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
			'icon'      => SEEDPROD_PLUGIN_URL . 'admin/images/plugin-wpconsent.png',
			'name'      => __( 'WPConsent', 'coming-soon' ),
			'desc'      => __( 'Cookie consent banner and privacy compliance suite for WordPress.', 'coming-soon' ),
			'priority'  => 12,
		),
	);

	// Get plugin statuses.
	$plugin_statuses = seedprod_lite_v2_get_plugins_status();

	// Add status to each plugin.
	foreach ( $plugins as $key => &$plugin ) {
		if ( isset( $plugin_statuses[ $key ] ) ) {
			$plugin['status']      = $plugin_statuses[ $key ]['label'];
			$plugin['status_code'] = $plugin_statuses[ $key ]['status'];
		} else {
			$plugin['status']      = __( 'Not Installed', 'coming-soon' );
			$plugin['status_code'] = 0;
		}
	}

	return $plugins;
}

/**
 * Get single plugin status.
 *
 * @param string $plugin_slug Plugin slug (e.g., 'plugin-dir/plugin-file.php').
 * @return array Status array with 'status' code and 'label'.
 */
function seedprod_lite_v2_get_plugin_status( $plugin_slug ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$status = array(
		'status' => 0, // Default: Not Installed.
		'label'  => __( 'Not Installed', 'coming-soon' ),
	);

	// Check if it's a pro plugin (special handling).
	$pro_plugins = array(
		'wpforms-premium/wpforms.php',
		'wpforms/wpforms.php', // Check if this is actually the pro version.
	);

	// Check plugin status.
	$installed_plugins = get_plugins();

	if ( isset( $installed_plugins[ $plugin_slug ] ) ) {
		// Plugin is installed.
		if ( is_plugin_active( $plugin_slug ) ) {
			// Plugin is active.
			$status['status'] = 1;
			$status['label']  = __( 'Active', 'coming-soon' );
		} else {
			// Plugin is inactive.
			$status['status'] = 2;
			$status['label']  = __( 'Inactive', 'coming-soon' );
		}

		// Check if it's a pro version.
		if ( in_array( $plugin_slug, $pro_plugins, true ) || false !== strpos( $plugin_slug, '-pro/' ) || false !== strpos( $plugin_slug, '-premium/' ) ) {
			// Additional check for pro features.
			if ( false !== strpos( strtolower( $installed_plugins[ $plugin_slug ]['Name'] ), 'pro' ) ||
				false !== strpos( strtolower( $installed_plugins[ $plugin_slug ]['Name'] ), 'premium' ) ) {
				$status['status'] = 3;
				$status['label']  = __( 'Pro Version', 'coming-soon' );
			}
		}
	}

	return $status;
}

/**
 * Get all recommended plugins excluding active ones.
 * For use in the Recommended Plugins settings page.
 *
 * @return array Array of plugins that are not currently active.
 */
function seedprod_lite_v2_get_all_recommended_plugins() {
	$all_plugins = seedprod_lite_v2_get_recommended_plugins();

	// Filter out already active plugins and plugins with pro versions installed.
	$available_plugins = array();
	foreach ( $all_plugins as $key => $plugin ) {
		// Skip if plugin is active (status_code 1).
		if ( 1 === $plugin['status_code'] ) {
			continue;
		}

		// Skip if pro version is installed.
		if ( seedprod_lite_v2_has_pro_version( $key ) ) {
			continue;
		}

		// Include everything else (Not Installed, Inactive plugins without pro versions).
		$available_plugins[ $key ] = $plugin;
	}

	return $available_plugins;
}

/**
 * Get dashboard recommended plugins.
 * Shows specific plugins: WPConsent, AIOSEO, WPForms, Duplicator.
 * If any are active, replaces them with other available plugins to always show 4.
 *
 * @return array Array of dashboard plugins.
 */
function seedprod_lite_v2_get_dashboard_recommended_plugins() {
	$all_plugins = seedprod_lite_v2_get_recommended_plugins();

	// Primary plugins to show on dashboard (in priority order).
	$dashboard_plugins = array( 'wpconsent', 'aioseo', 'wpforms', 'duplicator' );

	$filtered_plugins = array();
	$backup_plugins   = array();

	// First, try to add the preferred dashboard plugins.
	foreach ( $dashboard_plugins as $plugin_key ) {
		if ( ! isset( $all_plugins[ $plugin_key ] ) ) {
			continue;
		}

		$plugin = $all_plugins[ $plugin_key ];

		// Skip if plugin is active (status_code 1) or has pro version.
		if ( 1 === $plugin['status_code'] || seedprod_lite_v2_has_pro_version( $plugin_key ) ) {
			continue;
		}

		$filtered_plugins[ $plugin_key ] = $plugin;
	}

	// If we have less than 4, get backup plugins from the remaining list.
	if ( count( $filtered_plugins ) < 4 ) {
		// Get all available plugins that aren't in our preferred list.
		foreach ( $all_plugins as $key => $plugin ) {
			// Skip if it's one of our dashboard plugins.
			if ( in_array( $key, $dashboard_plugins, true ) ) {
				continue;
			}

			// Skip if plugin is active or has pro version.
			if ( 1 === $plugin['status_code'] || seedprod_lite_v2_has_pro_version( $key ) ) {
				continue;
			}

			$backup_plugins[ $key ] = $plugin;
		}

		// Sort backup plugins by priority.
		uasort(
			$backup_plugins,
			function ( $a, $b ) {
				return $b['priority'] - $a['priority'];
			}
		);

		// Add backup plugins until we have 4 total.
		foreach ( $backup_plugins as $key => $plugin ) {
			if ( count( $filtered_plugins ) >= 4 ) {
				break;
			}
			$filtered_plugins[ $key ] = $plugin;
		}
	}

	// Ensure we only return maximum 4 plugins.
	return array_slice( $filtered_plugins, 0, 4, true );
}

/**
 * Get random recommended plugins.
 *
 * @param int $count Number of plugins to return.
 * @return array Array of randomly selected plugins.
 */
function seedprod_lite_v2_get_random_recommended_plugins( $count = 3 ) {
	$all_plugins = seedprod_lite_v2_get_recommended_plugins();

	// Filter out already active plugins and pro versions.
	$available_plugins = array();
	foreach ( $all_plugins as $key => $plugin ) {
		// Skip if plugin is active (status_code 1).
		if ( 1 === $plugin['status_code'] ) {
			continue;
		}

		// Skip if pro version is installed.
		if ( seedprod_lite_v2_has_pro_version( $key ) ) {
			continue;
		}

		$available_plugins[ $key ] = $plugin;
	}

	// If we don't have enough available plugins, return what we have.
	if ( count( $available_plugins ) <= $count ) {
		return $available_plugins;
	}

	// Sort by priority (higher priority = more likely to show).
	uasort(
		$available_plugins,
		function ( $a, $b ) {
			return $b['priority'] - $a['priority'];
		}
	);

	// Get weighted random selection.
	$selected          = array();
	$selected_count    = 0;
	$plugin_keys       = array_keys( $available_plugins );
	$plugin_keys_count = count( $plugin_keys );

	// Weighted selection: higher priority plugins more likely.
	while ( $selected_count < $count && $plugin_keys_count > 0 ) {
		// Use weighted random selection.
		$weights      = array();
		$total_weight = 0;

		foreach ( $plugin_keys as $key ) {
			$weight          = $available_plugins[ $key ]['priority'];
			$weights[ $key ] = $weight;
			$total_weight   += $weight;
		}

		// Pick random weighted plugin.
		$rand    = wp_rand( 1, $total_weight );
		$current = 0;

		foreach ( $weights as $key => $weight ) {
			$current += $weight;
			if ( $rand <= $current ) {
				$selected[ $key ] = $available_plugins[ $key ];
				++$selected_count;
				// Remove from available plugins.
				$plugin_keys       = array_diff( $plugin_keys, array( $key ) );
				$plugin_keys_count = count( $plugin_keys );
				break;
			}
		}
	}

	return $selected;
}

/**
 * Get plugins status.
 *
 * @return array Array of plugin statuses.
 */
function seedprod_lite_v2_get_plugins_status() {
	$plugin_map = array(
		'google-analytics-for-wordpress/googleanalytics.php' => 'monsterinsights',
		'google-analytics-premium/googleanalytics-premium.php' => 'monsterinsights-pro',
		'optinmonster/optin-monster-wp-api.php'           => 'optinmonster',
		'wp-mail-smtp/wp_mail_smtp.php'                   => 'wpmailsmtp',
		'wp-mail-smtp-pro/wp_mail_smtp.php'               => 'wpmailsmtp-pro',
		'wpforms-lite/wpforms.php'                        => 'wpforms',
		'wpforms/wpforms.php'                             => 'wpforms-pro',
		'rafflepress/rafflepress.php'                     => 'rafflepress',
		'rafflepress-pro/rafflepress-pro.php'             => 'rafflepress-pro',
		'trustpulse-api/trustpulse.php'                   => 'trustpulse',
		'all-in-one-seo-pack/all_in_one_seo_pack.php'     => 'aioseo',
		'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' => 'aioseo-pro',
		'pushengage/main.php'                             => 'pushengage',
		'insert-headers-and-footers/ihaf.php'             => 'wpcode',
		'wpcode-premium/wpcode.php'                       => 'wpcode-pro',
		'duplicator/duplicator.php'                       => 'duplicator',
		'duplicator-pro/duplicator-pro.php'               => 'duplicator-pro',
		'wpconsent-cookies-banner-privacy-suite/wpconsent.php' => 'wpconsent',
		'wpconsent-premium/wpconsent.php'                 => 'wpconsent-premium',
		'google-analytics-dashboard-for-wp/gadwp.php'     => 'exactmetrics',
		'exactmetrics-premium/exactmetrics-premium.php'   => 'exactmetrics-pro',
		'instagram-feed/instagram-feed.php'               => 'instagramfeed',
		'instagram-feed-pro/instagram-feed.php'           => 'instagramfeed-pro',
		'custom-facebook-feed/custom-facebook-feed.php'   => 'customfacebookfeed',
		'custom-facebook-feed-pro/custom-facebook-feed.php' => 'customfacebookfeed-pro',
		'custom-twitter-feeds/custom-twitter-feed.php'    => 'customtwitterfeeds',
		'custom-twitter-feeds-pro/custom-twitter-feeds.php' => 'customtwitterfeeds-pro',
		'feeds-for-youtube/youtube-feed.php'              => 'feedsforyoutube',
		'youtube-feed-pro/youtube-feed-pro.php'           => 'feedsforyoutube-pro',
		'sugar-calendar-lite/sugar-calendar-lite.php'     => 'sugarcalendar',
		'sugar-calendar/sugar-calendar.php'               => 'sugarcalendar-pro',
		'stripe/stripe-checkout.php'                      => 'wpsimplepay',
		'wp-simple-pay-pro/simple-pay.php'                => 'wpsimplepay-pro',
		'easy-digital-downloads/easy-digital-downloads.php' => 'easydigitaldownloads',
		'easy-digital-downloads-pro/easy-digital-downloads.php' => 'easydigitaldownloads-pro',
		'searchwp-live-ajax-search/searchwp-live-ajax-search.php' => 'searchwp',
		'searchwp/searchwp.php'                           => 'searchwp-pro',
		'affiliatewp-affiliate-area-shortcodes/affiliatewp-affiliate-area-shortcodes.php' => 'affiliatewp',
		'affiliate-wp/affiliate-wp.php'                   => 'affiliatewp-pro',
	);

	$all_plugins = get_plugins();
	$response    = array();

	foreach ( $plugin_map as $slug => $label ) {
		if ( array_key_exists( $slug, $all_plugins ) ) {
			if ( is_plugin_active( $slug ) ) {
				$response[ $label ] = array(
					'label'  => __( 'Active', 'coming-soon' ),
					'status' => 1,
				);
			} else {
				$response[ $label ] = array(
					'label'  => __( 'Inactive', 'coming-soon' ),
					'status' => 2,
				);
			}
		} else {
			$response[ $label ] = array(
				'label'  => __( 'Not Installed', 'coming-soon' ),
				'status' => 0,
			);
		}

		// Check for pro versions.
		if ( false === strpos( $label, '-pro' ) ) {
			$pro_label = $label . '-pro';
			if ( isset( $response[ $pro_label ] ) && $response[ $pro_label ]['status'] > 0 ) {
				// Pro version exists, mark base version as having pro.
				$response[ $label ]['is_pro'] = true;
			}
		}
	}

	return $response;
}

/**
 * Install plugin AJAX handler.
 */
function seedprod_lite_v2_install_plugin() {
	// Clean any output that might have been generated.
	if ( ob_get_length() ) {
		ob_clean();
	}

	// Start output buffering to catch any unexpected output.
	ob_start();

	// Suppress error display for this request.
	$display_errors = ini_get( 'display_errors' );
	@ini_set( 'display_errors', 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.display_errors_Blacklisted -- Temporarily suppress errors during plugin installation.

	// Security check.
	check_ajax_referer( 'seedprod_nonce' );

	// Check permissions.
	if ( ! current_user_can( 'install_plugins' ) ) {
		ob_clean();
		wp_send_json_error( __( 'Permission denied.', 'coming-soon' ) );
	}

	// Get plugin ID (not URL for security).
	if ( ! isset( $_POST['plugin_id'] ) ) {
		ob_clean();
		wp_send_json_error( __( 'Plugin ID required.', 'coming-soon' ) );
	}

	$plugin_id = sanitize_key( wp_unslash( $_POST['plugin_id'] ) );

	// Get the download URL from server-side mapping.
	$download_url = seedprod_lite_v2_get_plugin_download_url( $plugin_id );

	if ( ! $download_url ) {
		ob_clean();
		@ini_set( 'display_errors', $display_errors ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.display_errors_Blacklisted -- Restore error display setting after plugin installation.
		wp_send_json_error( __( 'Invalid plugin.', 'coming-soon' ) );
	}

	// Set current screen.
	set_current_screen();

	// Prepare variables.
	$method = '';
	$url    = add_query_arg(
		array( 'page' => 'seedprod_lite' ),
		admin_url( 'admin.php' )
	);

	// Handle filesystem credentials (nested output buffering).
	$creds = request_filesystem_credentials( $url, $method, false, false, null );
	if ( false === $creds ) {
		$form = ob_get_contents();
		ob_clean();
		@ini_set( 'display_errors', $display_errors ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.display_errors_Blacklisted -- Restore error display setting after plugin installation.
		wp_send_json_error( array( 'form' => $form ) );
	}

	// Check filesystem authentication.
	if ( ! WP_Filesystem( $creds ) ) {
		request_filesystem_credentials( $url, $method, true, false, null );
		$form = ob_get_contents();
		ob_clean();
		@ini_set( 'display_errors', $display_errors ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.display_errors_Blacklisted -- Restore error display setting after plugin installation.
		wp_send_json_error( array( 'form' => $form ) );
	}

	// Include required files.
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	// Load appropriate skin based on WordPress version.
	global $wp_version;
	if ( version_compare( $wp_version, '5.3.0' ) >= 0 ) {
		require_once SEEDPROD_PLUGIN_PATH . 'admin/includes/skin53.php';
	} else {
		require_once SEEDPROD_PLUGIN_PATH . 'admin/includes/skin.php';
	}

	// Temporarily disable translation updates during plugin installation.
	add_filter( 'auto_update_translation', '__return_false' );
	remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

	// Install plugin.
	$installer = new Plugin_Upgrader( new SeedProd_Skin() );
	$result    = $installer->install( $download_url );

	// Re-enable translation updates.
	remove_filter( 'auto_update_translation', '__return_false' );
	add_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

	// Clear cache.
	wp_cache_flush();

	if ( $installer->plugin_info() ) {
		// Verify the installed plugin is what we expected.
		$plugin_file = $installer->plugin_info();
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );

		// Validate plugin is from our allowed list.
		if ( ! seedprod_lite_v2_verify_installed_plugin( $plugin_id, $plugin_data ) ) {
			// Remove potentially malicious plugin.
			deactivate_plugins( $plugin_file );
			delete_plugins( array( $plugin_file ) );
			ob_clean();
			@ini_set( 'display_errors', $display_errors ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.display_errors_Blacklisted -- Restore error display setting after plugin installation.
			wp_send_json_error( __( 'Plugin verification failed. The installed plugin was removed.', 'coming-soon' ) );
		}

		// Clear any output before sending success.
		ob_clean();
		@ini_set( 'display_errors', $display_errors ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.display_errors_Blacklisted -- Restore error display setting after plugin installation.
		wp_send_json_success(
			array(
				'plugin'  => $installer->plugin_info(),
				'message' => __( 'Plugin installed successfully.', 'coming-soon' ),
			)
		);
	} else {
		// Clear any output before sending error.
		ob_clean();
		@ini_set( 'display_errors', $display_errors ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.display_errors_Blacklisted -- Restore error display setting after plugin installation.
		wp_send_json_error( __( 'Plugin installation failed.', 'coming-soon' ) );
	}
}

/**
 * Activate plugin AJAX handler.
 */
function seedprod_lite_v2_activate_plugin() {
	// Clean any output that might have been generated.
	if ( ob_get_length() ) {
		ob_clean();
	}

	// Start output buffering to catch any unexpected output.
	ob_start();

	// Security check.
	check_ajax_referer( 'seedprod_nonce' );

	// Check permissions.
	if ( ! current_user_can( 'activate_plugins' ) ) {
		ob_clean();
		wp_send_json_error( __( 'Permission denied.', 'coming-soon' ) );
	}

	// Get plugin slug.
	if ( ! isset( $_POST['plugin'] ) ) {
		ob_clean();
		wp_send_json_error( __( 'Plugin slug required.', 'coming-soon' ) );
	}

	$plugin   = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );
	$activate = activate_plugin( $plugin, '', false, true );

	// Clear any output before sending response.
	ob_clean();

	if ( ! is_wp_error( $activate ) ) {
		wp_send_json_success( __( 'Plugin activated.', 'coming-soon' ) );
	} else {
		wp_send_json_error( $activate->get_error_message() );
	}
}

/**
 * Deactivate plugin AJAX handler.
 */
function seedprod_lite_v2_deactivate_plugin() {
	// Clean any output that might have been generated.
	if ( ob_get_length() ) {
		ob_clean();
	}

	// Start output buffering to catch any unexpected output.
	ob_start();

	// Security check.
	check_ajax_referer( 'seedprod_nonce' );

	// Check permissions.
	if ( ! current_user_can( 'deactivate_plugins' ) ) {
		ob_clean();
		wp_send_json_error( __( 'Permission denied.', 'coming-soon' ) );
	}

	// Get plugin slug.
	if ( ! isset( $_POST['plugin'] ) ) {
		ob_clean();
		wp_send_json_error( __( 'Plugin slug required.', 'coming-soon' ) );
	}

	$plugin = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );
	deactivate_plugins( $plugin );

	// Clear any output before sending response.
	ob_clean();
	wp_send_json_success( __( 'Plugin deactivated.', 'coming-soon' ) );
}

/**
 * Get plugin list AJAX handler.
 */
function seedprod_lite_v2_get_plugins_list() {
	// Security check.
	check_ajax_referer( 'seedprod_nonce' );

	$response = seedprod_lite_v2_get_plugins_status();
	wp_send_json( $response );
}

/**
 * Get plugin action button text.
 *
 * @param array $plugin Plugin data array.
 * @return string Button text.
 */
function seedprod_lite_v2_get_plugin_action_text( $plugin ) {
	if ( ! isset( $plugin['status_code'] ) ) {
		return __( 'Install', 'coming-soon' );
	}

	switch ( $plugin['status_code'] ) {
		case 0:
			return __( 'Install', 'coming-soon' );
		case 1:
			return __( 'Deactivate', 'coming-soon' );
		case 2:
			return __( 'Activate', 'coming-soon' );
		default:
			return __( 'Install', 'coming-soon' );
	}
}

/**
 * Check if plugin has pro version installed.
 *
 * @param string $plugin_key Plugin key.
 * @return bool True if pro version is installed.
 */
function seedprod_lite_v2_has_pro_version( $plugin_key ) {
	// Complete mappings for all recommended plugins that have pro versions.
	$pro_mappings = array(
		'wpforms'              => 'wpforms-pro',
		'monsterinsights'      => 'monsterinsights-pro',
		'wpmailsmtp'           => 'wpmailsmtp-pro',
		'rafflepress'          => 'rafflepress-pro',
		'aioseo'               => 'aioseo-pro',
		'wpcode'               => 'wpcode-pro',
		'duplicator'           => 'duplicator-pro',
		'wpconsent'            => 'wpconsent-premium',
		'exactmetrics'         => 'monsterinsights-pro', // ExactMetrics is MonsterInsights rebrand.
		// These plugins don't have separate pro versions - they're single plugins.
		'optinmonster'         => null, // Single plugin (SaaS service).
		'trustpulse'           => null, // Single plugin (SaaS service).
		'pushengage'           => null, // Single plugin (SaaS service).
		// These plugins use different naming/versioning patterns, not lite/pro.
		'instagramfeed'        => null, // Smash Balloon uses different versioning.
		'customfacebookfeed'   => null,
		'customtwitterfeeds'   => null,
		'feedsforyoutube'      => null,
		'sugarcalendar'        => null, // Sugar Calendar Lite vs Sugar Calendar (different naming).
		'wpsimplepay'          => null, // WP Simple Pay uses different versioning.
		'easydigitaldownloads' => null, // EDD doesn't have lite/pro split.
		'searchwp'             => null, // SearchWP Live Ajax Search is helper, main SearchWP is separate.
		'affiliatewp'          => null, // AffiliateWP shortcodes is helper plugin, not lite version.
	);

	// If no pro mapping exists, there's no pro version to check.
	if ( ! isset( $pro_mappings[ $plugin_key ] ) || null === $pro_mappings[ $plugin_key ] ) {
		return false;
	}

	$statuses = seedprod_lite_v2_get_plugins_status();
	$pro_key  = $pro_mappings[ $plugin_key ];

	// Check if pro version is installed (either active or inactive).
	return isset( $statuses[ $pro_key ] ) && $statuses[ $pro_key ]['status'] > 0;
}
