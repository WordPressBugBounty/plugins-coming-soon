<?php
/**
 * Settings Functions for V2 Admin
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save settings via AJAX (V2 Admin)
 * Handles toggle switches for Coming Soon, Maintenance, Login, 404 modes
 */
function seedprod_lite_v2_save_settings() {
	// Verify nonce.
	if ( ! check_ajax_referer( 'seedprod_nonce', '_wpnonce', false ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	// Check permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
	}

	// Get and sanitize settings.
	$settings_json = isset( $_POST['settings'] ) ? stripslashes( wp_unslash( $_POST['settings'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string validated after json_decode.
	$new_settings  = json_decode( $settings_json, true );

	if ( ! is_array( $new_settings ) ) {
		wp_send_json_error( 'Invalid settings format' );
	}

	// Get existing settings (stored as JSON string).
	$existing_settings_json = get_option( 'seedprod_settings' );
	if ( ! empty( $existing_settings_json ) ) {
		$existing_settings = json_decode( $existing_settings_json, true );
		if ( ! is_array( $existing_settings ) ) {
			$existing_settings = array();
		}
	} else {
		$existing_settings = array();
	}

	// Update only the mode settings.
	$mode_keys = array(
		'enable_coming_soon_mode',
		'enable_maintenance_mode',
		'enable_login_mode',
		'enable_404_mode',
	);

	// Map settings to their page ID options.
	$settings_to_page_options = array(
		'enable_coming_soon_mode' => 'seedprod_coming_soon_page_id',
		'enable_maintenance_mode' => 'seedprod_maintenance_mode_page_id',
		'enable_login_mode'       => 'seedprod_login_page_id',
		'enable_404_mode'         => 'seedprod_404_page_id',
	);

	foreach ( $mode_keys as $key ) {
		if ( isset( $new_settings[ $key ] ) ) {
			$new_value = (bool) $new_settings[ $key ];

			// Update the setting.
			$existing_settings[ $key ] = $new_value;

			// Always ensure page post_status matches the setting.
			if ( isset( $settings_to_page_options[ $key ] ) ) {
				$page_id = get_option( $settings_to_page_options[ $key ] );

				if ( $page_id ) {
					$page = get_post( $page_id );
					if ( $page ) {
						$expected_status = $new_value ? 'publish' : 'draft';
						// Only update if status doesn't match to avoid unnecessary DB writes.
						if ( $page->post_status !== $expected_status ) {
							wp_update_post(
								array(
									'ID'          => $page_id,
									'post_status' => $expected_status,
								)
							);
						}
					}
				}
			}
		}
	}

	// Ensure Coming Soon and Maintenance are mutually exclusive.
	if ( ! empty( $existing_settings['enable_coming_soon_mode'] ) && ! empty( $existing_settings['enable_maintenance_mode'] ) ) {
		// If both are true, disable the one that wasn't just enabled.
		if ( isset( $new_settings['enable_coming_soon_mode'] ) && $new_settings['enable_coming_soon_mode'] ) {
			$existing_settings['enable_maintenance_mode'] = false;
			// Set maintenance page to draft since we're disabling it.
			$mm_page_id = get_option( 'seedprod_maintenance_mode_page_id' );
			if ( $mm_page_id && get_post( $mm_page_id ) ) {
				wp_update_post(
					array(
						'ID'          => $mm_page_id,
						'post_status' => 'draft',
					)
				);
			}
		} else {
			$existing_settings['enable_coming_soon_mode'] = false;
			// Set coming soon page to draft since we're disabling it.
			$cs_page_id = get_option( 'seedprod_coming_soon_page_id' );
			if ( $cs_page_id && get_post( $cs_page_id ) ) {
				wp_update_post(
					array(
						'ID'          => $cs_page_id,
						'post_status' => 'draft',
					)
				);
			}
		}
	}

	// Save settings as JSON string (to match existing format).
	$result = update_option( 'seedprod_settings', wp_json_encode( $existing_settings ) );

	if ( false !== $result ) {
		wp_send_json_success(
			array(
				'message'  => __( 'Settings saved successfully', 'coming-soon' ),
				'settings' => $existing_settings,
			)
		);
	} else {
		wp_send_json_error( 'Failed to save settings' );
	}
}

/**
 * Get dashboard stats
 * Returns counts for pages, subscribers, etc.
 */
function seedprod_lite_v2_get_dashboard_stats() {
	global $wpdb;

	$stats = array();

	// Get page counts.
	$tablename   = $wpdb->prefix . 'postmeta';
	$posts_table = $wpdb->prefix . 'posts';

	// Get special page IDs to exclude from landing pages count.
	$coming_soon_id = get_option( 'seedprod_coming_soon_page_id' );
	$maintenance_id = get_option( 'seedprod_maintenance_mode_page_id' );
	$login_id       = get_option( 'seedprod_login_page_id' );
	$fourohfour_id  = get_option( 'seedprod_404_page_id' );

	$exclude_ids = array();
	if ( $coming_soon_id ) {
		$exclude_ids[] = (int) $coming_soon_id;
	}
	if ( $maintenance_id ) {
		$exclude_ids[] = (int) $maintenance_id;
	}
	if ( $login_id ) {
		$exclude_ids[] = (int) $login_id;
	}
	if ( $fourohfour_id ) {
		$exclude_ids[] = (int) $fourohfour_id;
	}

	$stats['coming_soon_count'] = $coming_soon_id ? 1 : 0;
	$stats['maintenance_count'] = $maintenance_id ? 1 : 0;
	$stats['theme_templates_count'] = $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM $tablename WHERE meta_key = '_seedprod_is_theme_template' AND meta_value = '1'" );

	// Count landing pages: pages with _seedprod_page that are NOT special pages.
	// Using _seedprod_page (not _seedprod_page_uuid) ensures we only count landing pages, not theme pages.
	// Match the logic from class-seedprod-landing-pages-table.php.
	if ( ! empty( $exclude_ids ) ) {
		$exclude_ids_string           = implode( ',', $exclude_ids );
		$placeholders = implode( ',', array_fill( 0, count( $exclude_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- $placeholders is a string of %d placeholders, safe for interpolation. Direct query needed for custom stats, no caching for real-time counts.
		$stats['landing_pages_count'] = $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm.post_id)
			FROM $tablename pm
			INNER JOIN $posts_table p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_seedprod_page'
			AND p.post_type = 'page'
			AND p.post_status != 'trash'
			AND pm.post_id NOT IN ($exclude_ids_string)"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query needed for custom stats, no caching for real-time counts.
		$stats['landing_pages_count'] = $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm.post_id)
			FROM $tablename pm
			INNER JOIN $posts_table p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_seedprod_page'
			AND p.post_type = 'page'
			AND p.post_status != 'trash'"
		);
	}

	// Get subscribers count.
	$subscribers_table           = $wpdb->prefix . 'csp3_subscribers';
	$stats['total_subscribers']  = 0;
	$stats['recent_subscribers'] = 0;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking table existence, caching not applicable.
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$subscribers_table'" ) === $subscribers_table ) {
		$stats['total_subscribers']  = $wpdb->get_var( "SELECT COUNT(*) FROM $subscribers_table" );
		$stats['recent_subscribers'] = $wpdb->get_var( "SELECT COUNT(*) FROM $subscribers_table WHERE created >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );
	}

	// Get page IDs.
	$stats['csp_id']    = get_option( 'seedprod_coming_soon_page_id' );
	$stats['mmp_id']    = get_option( 'seedprod_maintenance_mode_page_id' );
	$stats['loginp_id'] = get_option( 'seedprod_login_page_id' );
	$stats['p404_id']   = get_option( 'seedprod_404_page_id' );

	// Get active states (settings stored as JSON string).
	$settings_json = get_option( 'seedprod_settings' );
	if ( ! empty( $settings_json ) ) {
		$settings = json_decode( $settings_json, true );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
	} else {
		$settings = array();
	}

	$stats['csp_active']    = isset( $settings['enable_coming_soon_mode'] ) ? (bool) $settings['enable_coming_soon_mode'] : false;
	$stats['mmp_active']    = isset( $settings['enable_maintenance_mode'] ) ? (bool) $settings['enable_maintenance_mode'] : false;
	$stats['loginp_active'] = isset( $settings['enable_login_mode'] ) ? (bool) $settings['enable_login_mode'] : false;
	$stats['p404_active']   = isset( $settings['enable_404_mode'] ) ? (bool) $settings['enable_404_mode'] : false;

	// Get setup status.
	$stats['csp_setup_status'] = get_option( 'seedprod_coming_soon_page_setup_status' );

	return $stats;
}

/**
 * Register AJAX handlers for V2 admin
 * Note: These are registered when the file is included by the SeedProd_Lite_Admin class
 */
// Moved to class registration to ensure proper loading.
