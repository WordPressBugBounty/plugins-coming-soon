<?php
/**
 * Template Functions for V2 Admin
 *
 * @package SeedProd_Lite
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Subscribe to free templates (Lite users).
 */
function seedprod_lite_v2_subscribe_free_templates() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce' );

	// Get email.
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( empty( $email ) || ! is_email( $email ) ) {
		wp_send_json_error( __( 'Please enter a valid email address.', 'coming-soon' ) );
	}

	// Get site token.
	$site_token = get_option( 'seedprod_token', '' );

	// Call SeedProd API to subscribe.
	$api_url = SEEDPROD_API_URL . 'templates-subscribe';

	$response = wp_remote_post(
		$api_url,
		array(
			'body'    => array(
				'email'      => $email,
				'site_token' => $site_token,
			),
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( __( 'Failed to subscribe. Please try again.', 'coming-soon' ) );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	// Mark as subscribed in database.
	update_option( 'seedprod_free_templates_subscribed', '1' );

	wp_send_json_success(
		array(
			'message' => __( 'You now have access to 10 FREE templates!', 'coming-soon' ),
		)
	);
}

/**
 * Get templates from API or cache.
 */
function seedprod_lite_v2_get_templates() {
	// Check if request is valid.
	check_ajax_referer( 'seedprod_v2_nonce' );

	$filter   = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'all';
	$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';

	// Get Lite view status from AJAX request.
	$is_lite_view    = isset( $_POST['is_lite_view'] ) && 'true' === $_POST['is_lite_view'];
	$free_subscribed = isset( $_POST['free_subscribed'] ) && 'true' === $_POST['free_subscribed'] ? '1' : '0';

	// Always fetch fresh data from API to ensure favorites are current.
	// (Caching removed temporarily to fix favorites issue).
	$templates = seedprod_lite_v2_fetch_templates_from_api( $search, $category, $is_lite_view, $free_subscribed );

	wp_send_json_success( array_values( $templates ) );
}

/**
 * Fetch templates from SeedProd API.
 */
function seedprod_lite_v2_fetch_templates_from_api( $search = '', $category = '', $is_lite_view = null, $free_subscribed = null ) {
	// If not passed, check if this is Lite view.
	if ( null === $is_lite_view ) {
		$is_lite_view = seedprod_lite_v2_is_lite_view();
	}

	// Get tokens.
	$api_token  = get_option( 'seedprod_api_token', '' );
	$site_token = get_option( 'seedprod_token', '' );

	// Determine which endpoint to use.
	if ( $is_lite_view ) {
		// Lite users ALWAYS use preview endpoint to get free/package_id fields.
		$api_url = SEEDPROD_API_URL . 'templates-preview?page=1';
	} else {
		// Pro users use regular endpoint.
		$api_url = SEEDPROD_API_URL . 'templates?page=1';
	}

	// Build URL with required parameters.
	$params = array(
		'filter'     => 'templates',
		'api_token'  => $api_token,
		'site_token' => $site_token,
	);

	// Add free_subscribed parameter for Lite users.
	if ( $is_lite_view ) {
		if ( null === $free_subscribed ) {
			$free_subscribed = get_option( 'seedprod_free_templates_subscribed', '0' );
		}
		$params['free_subscribed'] = $free_subscribed;
	}

	// Add search parameter if provided.
	if ( ! empty( $search ) ) {
		$params['s'] = $search;
	}

	// Add category parameter if provided.
	if ( ! empty( $category ) && 'all' !== $category ) {
		$params['cat'] = $category;
	}

	$url = add_query_arg( $params, $api_url );

	$args = array(
		'timeout' => 10,
	);

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		return array();
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	// Handle the response structure (templates are in templates.data).
	if ( isset( $data['templates']['data'] ) && is_array( $data['templates']['data'] ) ) {
		// Get favorites array from API response.
		$favs = isset( $data['favs'] ) ? $data['favs'] : array();

		// Mark each template with favorite status (matching Vue.js logic exactly).
		foreach ( $data['templates']['data'] as &$template ) {
			// Check if template ID is in favorites array (no type conversion needed).
			$template['is_favorite'] = in_array( $template['id'], $favs, true );
		}
		return $data['templates']['data'];
	}

	return array();
}

/**
 * Get favorite templates.
 */
function seedprod_lite_v2_get_favorite_templates() {
	check_ajax_referer( 'seedprod_v2_nonce' );

	// WORKAROUND: The filter=favorites endpoint is not returning all favorites correctly.
	// Instead, we'll use filter=templates and filter client-side.
	// This matches what the "All Templates" tab does and works correctly.

	// Get ALL templates with favorites marked.
	$all_templates = seedprod_lite_v2_fetch_templates_from_api();

	// Filter to only favorites.
	$favorite_templates = array();
	foreach ( $all_templates as $template ) {
		if ( isset( $template['is_favorite'] ) && true === $template['is_favorite'] ) {
			$favorite_templates[] = $template;
		}
	}

	wp_send_json_success( $favorite_templates );
}

/**
 * Toggle favorite template.
 */
function seedprod_lite_v2_toggle_favorite_template() {
	check_ajax_referer( 'seedprod_v2_nonce' );

	$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';
	$method      = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '';

	if ( empty( $template_id ) ) {
		wp_send_json_error( __( 'Invalid template ID', 'coming-soon' ) );
	}

	// Determine method if not provided.
	if ( empty( $method ) ) {
		// Check current favorites from API to determine method.
		$all_templates         = seedprod_lite_v2_fetch_templates_from_api();
		$is_currently_favorite = false;

		foreach ( $all_templates as $template ) {
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( $template['id'] == $template_id && isset( $template['is_favorite'] ) && $template['is_favorite'] ) {
				$is_currently_favorite = true;
				break;
			}
		}

		$method = $is_currently_favorite ? 'detach' : 'attach';
	}

	// Get tokens for API call.
	$api_token  = get_option( 'seedprod_api_token', '' );
	$site_token = get_option( 'seedprod_token', '' );

	// Call SeedProd API to update favorite status.
	$api_url = SEEDPROD_API_URL . 'template-update';

	$body = array(
		'template_id' => $template_id,
		'method'      => $method,
		'api_token'   => $api_token,
		'site_token'  => $site_token,
	);

	$args = array(
		'body'    => $body,
		'timeout' => 10,
	);

	$response = wp_remote_post( $api_url, $args );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( __( 'Failed to update favorite status', 'coming-soon' ) );
	}

	$is_favorite = ( 'attach' === $method );

	wp_send_json_success( array( 'is_favorite' => $is_favorite ) );
}

/**
 * Get template code from API.
 */
function seedprod_lite_v2_get_template_code( $template_id ) {
	// Get template code from SeedProd API.
	$api_token = get_option( 'seedprod_api_token', '' );

	// Determine API endpoint based on whether user has API key.
	if ( empty( $api_token ) ) {
		$api_url = SEEDPROD_API_URL . 'templates-preview';
		$params  = array(
			'id'     => $template_id,
			'filter' => 'template_code',
		);
	} else {
		$api_url = SEEDPROD_API_URL . 'templates';
		$params  = array(
			'id'        => $template_id,
			'filter'    => 'template_code',
			'api_token' => $api_token,
		);
	}

	$url = add_query_arg( $params, $api_url );

	$args = array(
		'timeout' => 15,
	);

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body          = wp_remote_retrieve_body( $response );
	$response_code = wp_remote_retrieve_response_code( $response );

	if ( '200' !== strval( $response_code ) ) {
		return false;
	}

	return $body;
}

/**
 * Get saved templates (user's custom templates).
 */
function seedprod_lite_v2_get_saved_templates() {
	check_ajax_referer( 'seedprod_v2_nonce' );

	// Get tokens for API call.
	$api_token  = get_option( 'seedprod_api_token', '' );
	$site_token = get_option( 'seedprod_token', '' );

	// Use the SeedProd API to get saved templates (matching Vue.js implementation).
	$api_url = SEEDPROD_API_URL . 'templates?page=1';

	$url = add_query_arg(
		array(
			'filter'     => 'saved',
			'api_token'  => $api_token,
			'site_token' => $site_token,
		),
		$api_url
	);

	$args = array(
		'timeout' => 10,
	);

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		wp_send_json_success( array() );
		return;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	// Handle the response structure.
	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		wp_send_json_success( $data['data'] );
	} else {
		wp_send_json_success( array() );
	}
}

/**
 * Create page from template.
 */
function seedprod_lite_v2_create_page_from_template() {
	check_ajax_referer( 'seedprod_v2_nonce' );

	$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';
	$page_name   = isset( $_POST['page_name'] ) ? sanitize_text_field( wp_unslash( $_POST['page_name'] ) ) : '';
	$page_slug   = isset( $_POST['page_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['page_slug'] ) ) : '';
	$page_type   = isset( $_POST['page_type'] ) ? sanitize_text_field( wp_unslash( $_POST['page_type'] ) ) : 'lp';
	$page_id     = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;

	// Override slug and name for special pages to match old flow requirements.
	// These hardcoded slugs are critical for system identification.
	if ( 'cs' === $page_type ) {
		$page_slug = 'sp-cs';
		$page_name = $page_slug; // Old flow uses slug as name initially.
	} elseif ( 'mm' === $page_type ) {
		$page_slug = 'sp-mm';
		$page_name = $page_slug;
	} elseif ( 'p404' === $page_type ) {
		$page_slug = 'sp-p404';
		$page_name = $page_slug;
	} elseif ( 'loginp' === $page_type ) {
		$page_slug = 'sp-login';
		$page_name = $page_slug;
	}

	if ( empty( $template_id ) || empty( $page_name ) ) {
		wp_send_json_error( __( 'Missing required fields', 'coming-soon' ) );
	}

	// Check if we're updating an existing page (edge case: page without template).
	if ( $page_id > 0 ) {
		// Verify the page exists.
		$existing_page = get_post( $page_id );
		if ( ! $existing_page ) {
			wp_send_json_error( __( 'Page not found', 'coming-soon' ) );
		}

		// Load existing settings from the page.
		$existing_settings = json_decode( $existing_page->post_content_filtered, true );
		if ( ! is_array( $existing_settings ) ) {
			// If no valid settings, load defaults.
			require_once SEEDPROD_PLUGIN_PATH . 'resources/data-templates/basic-page.php';
			$settings = json_decode( $seedprod_basic_lpage, true );
		} else {
			$settings = $existing_settings;
		}

		// Get page type from meta if not already set.
		if ( empty( $page_type ) || 'lp' === $page_type ) {
			$stored_page_type = get_post_meta( $page_id, '_seedprod_page_template_type', true );
			if ( $stored_page_type ) {
				$page_type = $stored_page_type;
			}
		}
	} else {
		// Load basic page defaults first (matching old flow which always loads basic-page.php).
		require_once SEEDPROD_PLUGIN_PATH . 'resources/data-templates/basic-page.php';
		$settings = json_decode( $seedprod_basic_lpage, true );
	}

	// Override with our specific settings.
	$settings['template_id'] = absint( $template_id ); // Convert to integer to match old flow.
	$settings['page_type']   = $page_type;
	$settings['is_new']      = true; // Mark as new page (matching old flow).

	// Set no_conflict_mode for special page types (matching old lpage.php logic).
	if ( in_array( $page_type, array( 'cs', 'mm', 'p404', 'loginp' ), true ) ) {
		$settings['no_conflict_mode'] = true;
	}

	// Set default template for template parts (matching old flow line 158).
	$template_parts = array( 'header', 'footer', 'part', 'page' );
	if ( in_array( $page_type, $template_parts, true ) ) {
		// Template ID 71 is the default blank template for theme parts.
		$settings['template_id'] = 71;
	}

	// Get template code from API if not blank template.
	if ( 'blank' !== $template_id && '99999' !== $template_id ) {
		// Use our V2 function to get template code.
		$template_code_json = seedprod_lite_v2_get_template_code( $template_id );

		if ( $template_code_json ) {
			$template_data = json_decode( $template_code_json, true );

			// Merge template data with settings (template overwrites defaults).
			if ( is_array( $template_data ) ) {
				unset( $settings['document'] ); // Remove default document before merging (matching old flow).
				$settings = array_merge( $settings, $template_data );
			}
		}
	}

	// Determine post type based on page type (matching lpage.php logic).
	$post_type = 'page';
	// seedprod cpt types - these should be created as 'seedprod' post type.
	// Note: 'loginp' intentionally excluded - login pages use regular 'page' post type
	// so they work with any slug without needing custom rewrite rules.
	$cpt_types = array(
		'cs',
		'mm',
		'p404',
		'header',
		'footer',
		'part',
		'page',
	);

	if ( in_array( $page_type, $cpt_types ) ) {
		$post_type = 'seedprod';
	}

	// Create the page with proper post_content_filtered (matching old flow structure).
	$encoded_settings = wp_json_encode( $settings );

	if ( $page_id > 0 ) {
		// Update existing page.
		$page_data = array(
			'ID'                    => $page_id,
			'post_title'            => $page_name,
			'post_name'             => $page_slug,
			'post_content_filtered' => $encoded_settings,
		);

		$update_result = wp_update_post( $page_data );

		if ( is_wp_error( $update_result ) ) {
			wp_send_json_error( __( 'Failed to update page', 'coming-soon' ) );
		}
	} else {
		// Create new page.
		$page_data = array(
			'post_title'            => $page_name,
			'post_name'             => $page_slug,
			'post_status'           => 'draft',
			'post_type'             => $post_type,
			'comment_status'        => 'closed', // Match old flow.
			'ping_status'           => 'closed',    // Match old flow.
			'post_content'          => '', // SeedProd doesn't use post_content for builder pages.
			'post_content_filtered' => $encoded_settings, // This is where SeedProd stores the page data.
		);

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			wp_send_json_error( __( 'Failed to create page', 'coming-soon' ) );
		}
	}

	// Reinsert settings because wp_insert screws up json (following old working logic).
	global $wpdb;
	$tablename     = esc_sql( $wpdb->prefix . 'posts' );
	$sql           = "UPDATE $tablename SET post_content_filtered = %s WHERE id = %d";
	$safe_sql      = $wpdb->prepare( $sql, $encoded_settings, $page_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped with esc_sql(), dynamic SQL assembled for prepare().
	$update_result = $wpdb->query( $safe_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Set SeedProd page meta (matching old flow - no _seedprod_page_id).
	update_post_meta( $page_id, '_seedprod_page', '1' );
	update_post_meta( $page_id, '_seedprod_page_template_type', $page_type );

	// Generate page UUID if not exists (matching existing SeedProd pattern).
	$existing_uuid = get_post_meta( $page_id, '_seedprod_page_uuid', true );
	if ( empty( $existing_uuid ) ) {
		$uuid = wp_generate_uuid4();
		update_post_meta( $page_id, '_seedprod_page_uuid', $uuid );
	}

	// Set theme template meta for template parts (matching old flow).
	$template_parts = array( 'header', 'footer', 'part', 'page' );
	if ( in_array( $page_type, $template_parts, true ) ) {
		update_post_meta( $page_id, '_seedprod_is_theme_template', true );
		// Note: _seedprod_theme_template_condition would need conditions data.
		// which should come from the template selection process
	}

	// Update WordPress options for special page types (matching old Vue logic).
	if ( 'cs' === $page_type ) {
		update_option( 'seedprod_coming_soon_page_id', $page_id );
	} elseif ( 'mm' === $page_type ) {
		update_option( 'seedprod_maintenance_mode_page_id', $page_id );
	} elseif ( 'loginp' === $page_type ) {
		update_option( 'seedprod_login_page_id', $page_id );
		update_option( 'seedprod_login_page_slug', $page_slug );
	} elseif ( 'p404' === $page_type ) {
		update_option( 'seedprod_404_page_id', $page_id );
	}

	// Return builder URL with setup hash fragment (matching Vue.js flow)
	// The hash fragment tells the builder it's a new page in setup mode.
	$builder_url = admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $page_id . '#/setup/' . $page_id . '/block-options' );

	wp_send_json_success(
		array(
			'page_id'     => $page_id,
			'builder_url' => $builder_url,
		)
	);
}

/**
 * Duplicate landing page - V2 wrapper
 *
 * This function wraps the existing seedprod_lite_duplicate_lpage function
 * for use with the new V2 admin interface.
 */
function seedprod_lite_v2_duplicate_lpage() {
	// Check nonce using V2 nonce.
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Security check failed.', 'coming-soon' ) );
	}

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to duplicate pages.', 'coming-soon' ) );
	}

	// Get the page ID.
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

	if ( ! $id ) {
		wp_send_json_error( __( 'Invalid page ID.', 'coming-soon' ) );
	}

	// Get the original post.
	$post = get_post( $id );

	if ( ! $post ) {
		wp_send_json_error( __( 'Page not found.', 'coming-soon' ) );
	}

	// Get the page content.
	$json = $post->post_content_filtered;

	// Create the duplicate post.
	$args = array(
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_content'   => $post->post_content,
		'post_status'    => 'draft',
		'post_title'     => $post->post_title . ' - Copy',
		'post_type'      => 'page',
		'post_name'      => '',
		'meta_input'     => array(
			'_seedprod_page'      => true,
			'_seedprod_page_uuid' => wp_generate_uuid4(),
		),
	);

	// Insert the new post.
	$new_post_id = wp_insert_post( $args, true );

	if ( is_wp_error( $new_post_id ) ) {
		wp_send_json_error( $new_post_id->get_error_message() );
	}

	// Reinsert JSON content to avoid slash issues.
	global $wpdb;
	$tablename = $wpdb->prefix . 'posts';
	$wpdb->update(
		$tablename,
		array(
			'post_content_filtered' => $json,
		),
		array( 'ID' => $new_post_id ),
		array( '%s' ),
		array( '%d' )
	);

	// Copy additional meta fields if they exist.
	$meta_to_copy = array(
		'_seedprod_page_type',
		'_seedprod_page_template_id',
		'_seedprod_page_template_type',
	);

	foreach ( $meta_to_copy as $meta_key ) {
		$meta_value = get_post_meta( $id, $meta_key, true );
		if ( ! empty( $meta_value ) ) {
			update_post_meta( $new_post_id, $meta_key, $meta_value );
		}
	}

	// Get the new post details for the response.
	$new_post = get_post( $new_post_id );

	wp_send_json_success(
		array(
			'message' => __( 'Page duplicated successfully.', 'coming-soon' ),
			'page'    => array(
				'id'    => $new_post_id,
				'title' => $new_post->post_title,
				'url'   => get_preview_post_link( $new_post_id ),
			),
		)
	);
}

/**
 * Trash landing page - V2 wrapper
 */
function seedprod_lite_v2_trash_lpage() {
	// Check nonce.
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Security check failed.', 'coming-soon' ) );
	}

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to trash pages.', 'coming-soon' ) );
	}

	// Get the page ID.
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

	if ( ! $id ) {
		wp_send_json_error( __( 'Invalid page ID.', 'coming-soon' ) );
	}

	// Trash the post.
	$result = wp_trash_post( $id );

	if ( ! $result ) {
		wp_send_json_error( __( 'Failed to trash page.', 'coming-soon' ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Page moved to trash.', 'coming-soon' ),
		)
	);
}

/**
 * Restore landing page from trash - V2 wrapper
 */
function seedprod_lite_v2_restore_lpage() {
	// Check nonce.
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Security check failed.', 'coming-soon' ) );
	}

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to restore pages.', 'coming-soon' ) );
	}

	// Get the page ID.
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

	if ( ! $id ) {
		wp_send_json_error( __( 'Invalid page ID.', 'coming-soon' ) );
	}

	// Restore the post.
	$result = wp_untrash_post( $id );

	if ( ! $result ) {
		wp_send_json_error( __( 'Failed to restore page.', 'coming-soon' ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Page restored successfully.', 'coming-soon' ),
		)
	);
}

/**
 * Delete landing page permanently - V2 wrapper
 */
function seedprod_lite_v2_delete_lpage() {
	// Check nonce.
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Security check failed.', 'coming-soon' ) );
	}

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to delete pages.', 'coming-soon' ) );
	}

	// Get the page ID.
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

	if ( ! $id ) {
		wp_send_json_error( __( 'Invalid page ID.', 'coming-soon' ) );
	}

	// Delete the post permanently.
	$result = wp_delete_post( $id, true );

	if ( ! $result ) {
		wp_send_json_error( __( 'Failed to delete page.', 'coming-soon' ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Page deleted permanently.', 'coming-soon' ),
		)
	);
}

/**
 * Bulk action for landing pages - V2 wrapper
 */
function seedprod_lite_v2_bulk_action_lpages() {
	// Check nonce.
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Security check failed.', 'coming-soon' ) );
	}

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to perform bulk actions.', 'coming-soon' ) );
	}

	// Get the action and page IDs.
	$action   = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
	$page_ids = isset( $_POST['page_ids'] ) ? array_map( 'absint', $_POST['page_ids'] ) : array();

	if ( empty( $action ) || empty( $page_ids ) ) {
		wp_send_json_error( __( 'Invalid request.', 'coming-soon' ) );
	}

	$success_count = 0;
	$error_count   = 0;

	foreach ( $page_ids as $id ) {
		$result = false;

		switch ( $action ) {
			case 'trash':
				$result = wp_trash_post( $id );
				break;
			case 'restore':
				$result = wp_untrash_post( $id );
				break;
			case 'delete':
				$result = wp_delete_post( $id, true );
				break;
		}

		if ( $result ) {
			++$success_count;
		} else {
			++$error_count;
		}
	}

	$message = '';
	switch ( $action ) {
		case 'trash':
			/* translators: %d: number of pages */
			$message = sprintf( __( '%d pages moved to trash.', 'coming-soon' ), $success_count );
			break;
		case 'restore':
			/* translators: %d: number of pages */
			$message = sprintf( __( '%d pages restored.', 'coming-soon' ), $success_count );
			break;
		case 'delete':
			/* translators: %d: number of pages */
			$message = sprintf( __( '%d pages deleted permanently.', 'coming-soon' ), $success_count );
			break;
	}

	if ( $error_count > 0 ) {
		/* translators: %d: number of pages */
		$message .= ' ' . sprintf( __( '%d pages failed.', 'coming-soon' ), $error_count );
	}

	if ( $success_count > 0 ) {
		wp_send_json_success( array( 'message' => $message ) );
	} else {
		wp_send_json_error( $message );
	}
}
