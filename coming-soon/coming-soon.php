<?php
/**
 * Plugin Name: Coming Soon Page, Maintenance Mode, Landing Pages & WordPress Website Builder by SeedProd
 * Plugin URI: https://www.seedprod.com/lite-upgrade/?utm_source=WordPress&utm_campaign=liteplugin&utm_medium=plugin-uri-link
 * Description: The Easiest WordPress Drag & Drop Page Builder that allows you to build your website, create Landing Pages, Coming Soon Pages, Maintenance Mode Pages and more.
 * Version:  6.19.7
 * Author: SeedProd
 * Author URI: https://www.seedprod.com/lite-upgrade/?utm_source=WordPress&utm_campaign=liteplugin&utm_medium=author-uri-link
 * Text Domain: coming-soon
 * Domain Path: /languages
 * License: GPLv2 or later
 *
 * @package SeedProd
 * @subpackage SeedProd
 */

/**
 * Default Constants
 */

define( 'SEEDPROD_BUILD', 'lite' );
define( 'SEEDPROD_SLUG', 'coming-soon/coming-soon.php' );
define( 'SEEDPROD_VERSION', '6.19.7' );
define( 'SEEDPROD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
// Example output: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/seedprod/.
define( 'SEEDPROD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
// Example output: http://localhost:8888/wordpress/wp-content/plugins/seedprod/.


if ( defined( 'SEEDPROD_LOCAL_JS' ) ) {
	// phpcs:disable Squiz.PHP.CommentedOutCode,Squiz.Commenting.InlineComment.InvalidEndChar,WordPress.Commenting.InlineComment.InvalidEndChar
	define( 'SEEDPROD_API_URL', 'http://v4app.seedprod.test/v4/' );
	define( 'SEEDPROD_WEB_API_URL', 'http://v4app.seedprod.test/' );
	define( 'SEEDPROD_BACKGROUND_DOWNLOAD_API_URL', 'https://api.seedprod.com/v3/background_download' );

} else {
	define( 'SEEDPROD_API_URL', 'https://api.seedprod.com/v4/' );
	define( 'SEEDPROD_WEB_API_URL', 'https://app.seedprod.com/' );
	define( 'SEEDPROD_BACKGROUND_DOWNLOAD_API_URL', 'https://api.seedprod.com/v3/background_download' );
}



/**
 * Load Translation
 */
function seedprod_lite_load_textdomain() {
	load_plugin_textdomain( 'coming-soon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'seedprod_lite_load_textdomain' );


/**
 * Upon activation of the plugin check php version, load defaults and show welcome screen.
 */
function seedprod_lite_activation() {
	// Include plugin.php to use is_plugin_active() and deactivate_plugins() .
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	seedprod_lite_check_for_free_version();

	// Deactivate the other version to prevent conflicts.
	if ( SEEDPROD_BUILD === 'pro' ) {
		// Pro is being activated, check if lite is active and deactivate it.
		if ( is_plugin_active( 'coming-soon/coming-soon.php' ) ) {
			deactivate_plugins( 'coming-soon/coming-soon.php' );
		}
	} elseif ( is_plugin_active( 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php' ) ) {
		// Lite is being activated, check if pro is active and deactivate it.
		deactivate_plugins( 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php' );
	}

	update_option( 'seedprod_run_activation', true, '', false );

	// Load and set default settings.
	require_once SEEDPROD_PLUGIN_PATH . 'resources/data-templates/default-settings.php';
	add_option( 'seedprod_settings', $seedprod_default_settings );

	// Set initial version.
	$data = array(
		'installed_version' => SEEDPROD_VERSION,
		'installed_date'    => time(),
		'installed_pro'     => SEEDPROD_BUILD,
	);

	add_option( 'seedprod_over_time', $data );

	// Set a token.
	add_option( 'seedprod_token', wp_generate_uuid4() );

	// Welcome page flag.
	set_transient( '_seedprod_welcome_screen_activation_redirect', true, 60 );

	// Set cron to fetch feed.
	if ( ! wp_next_scheduled( 'seedprod_notifications' ) ) {
		if ( SEEDPROD_BUILD === 'pro' ) {
			wp_schedule_event( time() + 7200, 'daily', 'seedprod_notifications' );
		} else {
			wp_schedule_event( time(), 'daily', 'seedprod_notifications' );
		}
	}

	// Copy help docs on installation.
	$upload_dir = wp_upload_dir();
	$path       = trailingslashit( $upload_dir['basedir'] ) . 'seedprod-help-docs/'; // target directory.
	$cache_file = wp_normalize_path( trailingslashit( $path ) . 'articles.json' );

	// Copy articles file.
	if ( true === seedprod_lite_set_up_upload_dir( $path, $cache_file ) ) {
		$initial_location = SEEDPROD_PLUGIN_PATH . 'resources/data-templates/articles.json';
		copy( $initial_location, $cache_file );
	}

	// Set cron to fetch help docs.
	if ( ! wp_next_scheduled( 'seedprod_lite_fetch_help_docs' ) ) {
		if ( SEEDPROD_BUILD === 'pro' ) {
			wp_schedule_event( time() + 7200, 'weekly', 'seedprod_lite_fetch_help_docs' );
		} else {
			wp_schedule_event( time(), 'weekly', 'seedprod_lite_fetch_help_docs' );
		}
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'seedprod_lite_activation' );


/**
 * Deactivate Flush Rules
 */
function seedprod_lite_deactivate() {
	wp_clear_scheduled_hook( 'seedprod_notifications' );
	wp_clear_scheduled_hook( 'seedprod_fetch_help_docs' );
}

register_deactivation_hook( __FILE__, 'seedprod_lite_deactivate' );



/**
 * Load Plugin
 */
require_once SEEDPROD_PLUGIN_PATH . 'app/bootstrap.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/routes.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/load_controller.php';
// Load Custom Gutenberg Blocks
// Commented out - not currently in use
// if ( file_exists( SEEDPROD_PLUGIN_PATH . 'blocks/countdown/index.php' ) ) {
// 	require_once SEEDPROD_PLUGIN_PATH . 'blocks/countdown/index.php';
// }


/**
 * Initialize the new WordPress-native admin pages
 * This runs alongside the existing Vue system for gradual migration
 */
add_action( 'plugins_loaded', 'seedprod_lite_init_native_admin', 15 );
/**
 * Initialize WordPress-native admin pages (alongside Vue system).
 *
 * @return void
 */
function seedprod_lite_init_native_admin() {
	// Only load in admin.
	if ( is_admin() ) {
		require_once SEEDPROD_PLUGIN_PATH . 'includes/class-seedprod-init.php';
		$seedprod_native = new SeedProd_Lite_Init();
		$seedprod_native->run();
	}
}

/**
 * Register WP-CLI commands
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once SEEDPROD_PLUGIN_PATH . 'wp-cli-functions.php';
}

/**
 * Maybe Migrate
 */
add_action( 'upgrader_process_complete', 'seedprod_lite_check_for_free_version' );
add_action( 'init', 'seedprod_lite_check_for_free_version' );
