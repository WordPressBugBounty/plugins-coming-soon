<?php
/**
 * SeedProd Lite routes.
 *
 * @package SeedProd
 * @subpackage SeedProd/app
 */

/**
 * Dashboard page - V2 WordPress native.
 * This redirects to our new V2 dashboard.
 */
function seedprod_lite_dashboard_page() {
	// Load the V2 dashboard directly.
	require_once SEEDPROD_PLUGIN_PATH . 'admin/partials/seedprod-admin-dashboard.php';
}

/**
 * Builder page.
 */
function seedprod_lite_builder_page() {
	require_once SEEDPROD_PLUGIN_PATH . 'resources/views/builder.php';
}



/* Short circuit new request */

add_action( 'admin_init', 'seedprod_lite_new_lpage', 1 );



/**
 * Preview Shortcode
 */
function seedprod_lite_render_shortcode() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {
		if ( ! current_user_can( apply_filters( 'seedprod_builder_preview_render_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}
		if ( ! empty( $_POST['shortcode'] ) ) {
			$shortcode = sanitize_text_field( wp_unslash( $_POST['shortcode'] ) );

			do_action( 'wp_print_footer_scripts' );
			do_action( 'wp_footer' );
			$content = do_shortcode( $shortcode );
			// $content = do_shortcode( $content );
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		exit();
	}
	exit;
}

if ( defined( 'DOING_AJAX' ) ) {
	// phpcs:ignore Squiz.Commenting.BlockComment
	// phpcs:ignore Squiz.Commenting.BlockComment

	add_action( 'wp_ajax_seedprod_lite_dismiss_settings_lite_cta', 'seedprod_lite_dismiss_settings_lite_cta' );

	add_action( 'wp_ajax_seedprod_lite_save_settings', 'seedprod_lite_save_settings' );
	add_action( 'wp_ajax_seedprod_lite_save_api_key', 'seedprod_lite_save_api_key' );

	add_action( 'wp_ajax_seedprod_lite_save_app_settings', 'seedprod_lite_save_app_settings' );
	// phpcs:ignore Squiz.Commenting.BlockComment

	add_action( 'wp_ajax_seedprod_lite_template_subscribe', 'seedprod_lite_template_subscribe' );
	add_action( 'wp_ajax_seedprod_lite_save_template', 'seedprod_lite_save_template' );
	add_action( 'wp_ajax_seedprod_lite_save_lpage', 'seedprod_lite_save_lpage' );
	add_action( 'wp_ajax_seedprod_lite_get_revisions', 'seedprod_lite_get_revisisons' );
	add_action( 'wp_ajax_seedprod_lite_get_utc_offset', 'seedprod_lite_get_utc_offset' );
	add_action( 'wp_ajax_seedprod_lite_get_namespaced_custom_css', 'seedprod_lite_get_namespaced_custom_css' );
	add_action( 'wp_ajax_seedprod_lite_get_stockimages', 'seedprod_lite_get_stockimages' );

	// Landing pages.
	add_action( 'wp_ajax_seedprod_lite_slug_exists', 'seedprod_lite_slug_exists' );
	add_action( 'wp_ajax_seedprod_lite_lpage_datatable', 'seedprod_lite_lpage_datatable' );
	add_action( 'wp_ajax_seedprod_lite_duplicate_lpage', 'seedprod_lite_duplicate_lpage' );
	add_action( 'wp_ajax_seedprod_lite_get_lpage_list', 'seedprod_lite_get_lpage_list' );
	add_action( 'wp_ajax_seedprod_lite_archive_selected_lpages', 'seedprod_lite_archive_selected_lpages' );
	add_action( 'wp_ajax_seedprod_lite_unarchive_selected_lpages', 'seedprod_lite_unarchive_selected_lpages' );
	add_action( 'wp_ajax_seedprod_lite_delete_archived_lpages', 'seedprod_lite_delete_archived_lpages' );

	// Theme templates.

	add_action( 'wp_ajax_seedprod_lite_update_subscriber_count', 'seedprod_lite_update_subscriber_count' );
	add_action( 'wp_ajax_seedprod_lite_subscribers_datatable', 'seedprod_lite_subscribers_datatable' );


	add_action( 'wp_ajax_seedprod_lite_get_plugins_list', 'seedprod_lite_get_plugins_list' );

	add_action( 'wp_ajax_seedprod_lite_install_addon', 'seedprod_lite_install_addon' );
	add_action( 'wp_ajax_seedprod_lite_activate_addon', 'seedprod_lite_activate_addon' );
	add_action( 'wp_ajax_seedprod_lite_deactivate_addon', 'seedprod_lite_deactivate_addon' );

	add_action( 'wp_ajax_seedprod_lite_install_addon', 'seedprod_lite_install_addon' );
	add_action( 'wp_ajax_seedprod_lite_deactivate_addon', 'seedprod_lite_deactivate_addon' );
	add_action( 'wp_ajax_seedprod_lite_activate_addon', 'seedprod_lite_activate_addon' );
	add_action( 'wp_ajax_seedprod_lite_plugin_nonce', 'seedprod_lite_plugin_nonce' );

	add_action( 'wp_ajax_nopriv_seedprod_lite_run_one_click_upgrade', 'seedprod_lite_run_one_click_upgrade' );
	add_action( 'wp_ajax_seedprod_lite_upgrade_license', 'seedprod_lite_upgrade_license' );

	add_action( 'wp_ajax_seedprod_lite_get_wpforms', 'seedprod_lite_get_wpforms' );
	add_action( 'wp_ajax_seedprod_lite_get_wpform', 'seedprod_lite_get_wpform' );
	add_action( 'wp_ajax_seedprod_lite_get_rafflepress', 'seedprod_lite_get_rafflepress' );
	add_action( 'wp_ajax_seedprod_lite_get_rafflepress_code', 'seedprod_lite_get_rafflepress_code' );

	add_action( 'wp_ajax_seedprod_lite_get_mypaykit', 'seedprod_lite_get_mypaykit' );
	add_action( 'wp_ajax_seedprod_lite_get_mypaykit_code', 'seedprod_lite_get_mypaykit_code' );

	add_action( 'wp_ajax_seedprod_lite_get_widget_wpforms', 'seedprod_lite_get_widget_wpforms' );
	add_action( 'wp_ajax_seedprod_lite_get_widget_wpresults', 'seedprod_lite_get_widget_wpresults' );

	add_action( 'wp_ajax_seedprod_lite_get_envira_galleries', 'seedprod_lite_get_envira_galleries' );

	add_action( 'wp_ajax_seedprod_lite_dismiss_upsell', 'seedprod_lite_dismiss_upsell' );

	// WooCommerce.
	add_action( 'wp_ajax_seedprod_lite_get_woocommerce_products', 'seedprod_lite_get_woocommerce_products' );
	add_action( 'wp_ajax_seedprod_lite_get_woocommerce_product_taxonomy', 'seedprod_lite_get_woocommerce_product_taxonomy' );
	add_action( 'wp_ajax_seedprod_lite_get_woocommerce_product_attributes', 'seedprod_lite_get_woocommerce_product_attributes' );
	add_action( 'wp_ajax_seedprod_lite_get_woocommerce_product_attribute_terms', 'seedprod_lite_get_woocommerce_product_attribute_terms' );

	// EDD.
	add_action( 'wp_ajax_seedprod_lite_get_edd_downloads', 'seedprod_lite_get_edd_downloads' );
	add_action( 'wp_ajax_seedprod_lite_get_edd_download_taxonomy', 'seedprod_lite_get_edd_download_taxonomy' );






}






