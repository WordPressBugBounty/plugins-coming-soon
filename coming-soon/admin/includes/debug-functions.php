<?php
/**
 * Debug functions for SeedProd Admin (V2)
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save Debug Settings (V2 Admin)
 * Handles all debug page form submissions
 *
 * @return array Response with status and message
 */
function seedprod_lite_v2_save_debug_settings() {
	// Verify nonce.
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'seedprod-debug-reset' ) ) {
		return array(
			'status'  => false,
			'message' => __( 'Security check failed.', 'coming-soon' ),
		);
	}

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_debug_menu_capability', 'edit_others_posts' ) ) ) {
		return array(
			'status'  => false,
			'message' => __( 'Insufficient permissions.', 'coming-soon' ),
		);
	}

	$messages = array();

	// Process reset options.
	if ( ! empty( $_POST['sp_reset_cs'] ) && 1 === (int) $_POST['sp_reset_cs'] ) {
		delete_option( 'seedprod_coming_soon_page_id' );
		$messages[] = __( 'Coming Soon page has been reset.', 'coming-soon' );
	}

	if ( ! empty( $_POST['sp_reset_mm'] ) && 1 === (int) $_POST['sp_reset_mm'] ) {
		delete_option( 'seedprod_maintenance_mode_page_id' );
		$messages[] = __( 'Maintenance Mode page has been reset.', 'coming-soon' );
	}

	if ( ! empty( $_POST['sp_reset_p404'] ) && 1 === (int) $_POST['sp_reset_p404'] ) {
		delete_option( 'seedprod_404_page_id' );
		$messages[] = __( '404 page has been reset.', 'coming-soon' );
	}

	if ( ! empty( $_POST['sp_reset_loginp'] ) && 1 === (int) $_POST['sp_reset_loginp'] ) {
		delete_option( 'seedprod_login_page_id' );
		$messages[] = __( 'Login page has been reset.', 'coming-soon' );
	}

	// Handle builder debug toggle.
	if ( ! empty( $_POST['sp_builder_debug'] ) && 1 === (int) $_POST['sp_builder_debug'] ) {
		update_option( 'seedprod_builder_debug', true );
		if ( empty( $messages ) ) {
			$messages[] = __( 'Builder Debug mode enabled.', 'coming-soon' );
		}
	} elseif ( isset( $_POST['submit'] ) ) {
		update_option( 'seedprod_builder_debug', false );
		if ( empty( $messages ) ) {
			$messages[] = __( 'Builder Debug mode disabled.', 'coming-soon' );
		}
	}

	// Return appropriate message.
	if ( ! empty( $messages ) ) {
		return array(
			'status'  => true,
			'message' => implode( ' ', $messages ),
		);
	}

	return array(
		'status'  => true,
		'message' => __( 'Settings saved.', 'coming-soon' ),
	);
}

/**
 * Get Builder Debug Status (V2 Admin)
 *
 * @return boolean Whether builder debug is enabled
 */
function seedprod_lite_v2_get_builder_debug_status() {
	return (bool) get_option( 'seedprod_builder_debug', false );
}
