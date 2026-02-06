<?php
/**
 * Theme Functions for V2 Admin
 *
 * @package    SeedProd_Lite
 * @subpackage SeedProd_Lite/admin/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if SeedProd theme is enabled.
 * Checks BOTH old (seedprod_theme_enabled) and new (seedprod_settings JSON) formats.
 *
 * @return bool True if theme is enabled in either location.
 */
function seedprod_lite_v2_is_theme_enabled() {
	// Check new format (seedprod_settings JSON).
	$settings_json = get_option( 'seedprod_settings' );
	if ( ! empty( $settings_json ) ) {
		$settings = json_decode( $settings_json, true );
		if ( is_array( $settings ) && ! empty( $settings['enable_seedprod_theme'] ) ) {
			return true;
		}
	}

	// Check old format (direct option).
	$old_enabled = get_option( 'seedprod_theme_enabled' );
	if ( ! empty( $old_enabled ) ) {
		return true;
	}

	return false;
}

/**
 * Update SeedProd theme enabled status (V2 version).
 * AJAX handler for toggling the theme on/off.
 */
function seedprod_lite_v2_update_theme_enabled() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_update_seedprod_theme_enabled', 'switch_themes' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to change theme settings.', 'coming-soon' ), 403 );
	}

	// Get the enabled status.
	$enabled = isset( $_POST['enabled'] ) ? filter_var( wp_unslash( $_POST['enabled'] ), FILTER_VALIDATE_BOOLEAN ) : false;

	// Get current settings.
	$settings_json = get_option( 'seedprod_settings' );
	if ( ! empty( $settings_json ) ) {
		$settings = json_decode( $settings_json, true );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
	} else {
		$settings = array();
	}

	// Update the theme enabled setting.
	$settings['enable_seedprod_theme'] = $enabled;

	// Save settings back as JSON.
	update_option( 'seedprod_settings', wp_json_encode( $settings ) );

	// Also update the standalone option for compatibility.
	update_option( 'seedprod_theme_enabled', $enabled );

	// If enabling theme, create Global CSS post if it doesn't exist.
	if ( $enabled ) {
		$global_css_page_id = get_option( 'seedprod_global_css_page_id' );
		if ( empty( $global_css_page_id ) ) {
			// Check if the global CSS template file exists.
			$global_css_file = SEEDPROD_PLUGIN_PATH . 'resources/data-templates/global-css.php';
			if ( file_exists( $global_css_file ) ) {
				require_once $global_css_file;
				$args = array(
					'comment_status'        => 'closed',
					'ping_status'           => 'closed',
					'post_content_filtered' => $seedprod_global_css,
					'post_status'           => 'publish',
					'post_title'            => __( 'Global CSS', 'coming-soon' ),
					'post_type'             => 'seedprod',
					'post_name'             => '',
					'meta_input'            => array(
						'_seedprod_page'               => true,
						'_seedprod_page_uuid'          => wp_generate_uuid4(),
						'_seedprod_page_template_type' => 'css',
						'_seedprod_is_theme_template'  => true,
					),
				);

				$global_css_page_id = wp_insert_post( $args, true );
				if ( ! is_wp_error( $global_css_page_id ) ) {
					update_option( 'seedprod_global_css_page_id', $global_css_page_id );
				}
			}
		}
	}

	// Return success response.
	if ( $enabled ) {
		wp_send_json_success(
			array(
				'message' => __( 'Theme has been enabled.', 'coming-soon' ),
				'enabled' => true,
			)
		);
	} else {
		wp_send_json_success(
			array(
				'message' => __( 'Theme has been disabled.', 'coming-soon' ),
				'enabled' => false,
			)
		);
	}
}

/**
 * Toggle theme template publish status
 */
function seedprod_lite_v2_toggle_template_status() {
	// Check nonce.
	$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
	check_ajax_referer( 'seedprod_toggle_template_' . $template_id, 'nonce' );

	// Check permissions.
	if ( ! current_user_can( 'edit_post', $template_id ) ) {
		wp_send_json_error( __( 'You do not have permission to edit this template.', 'coming-soon' ), 403 );
	}

	// Get current status.
	$current_status = get_post_status( $template_id );
	$new_status     = ( 'publish' === $current_status ) ? 'draft' : 'publish';

	// Update post status.
	$result = wp_update_post(
		array(
			'ID'          => $template_id,
			'post_status' => $new_status,
		)
	);

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message() );
	}

	wp_send_json_success(
		array(
			'status'  => $new_status,
			'message' => ( 'publish' === $new_status ) ?
				__( 'Template published.', 'coming-soon' ) :
				__( 'Template unpublished.', 'coming-soon' ),
		)
	);
}

/**
 * Duplicate theme template
 */
function seedprod_lite_v2_duplicate_template() {
	// Check nonce.
	$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
	check_ajax_referer( 'seedprod_duplicate_template_' . $template_id, 'nonce' );

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_themetemplate_capability', 'edit_others_posts' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to duplicate templates.', 'coming-soon' ), 403 );
	}

	// Get original post.
	$post = get_post( $template_id );
	if ( ! $post ) {
		wp_send_json_error( __( 'Template not found.', 'coming-soon' ) );
	}

	// Get the template content (JSON builder data).
	$json = $post->post_content_filtered;

	// Create duplicate.
	$new_post = array(
		'post_title'   => $post->post_title . ' (Copy)',
		'post_content' => $post->post_content,
		'post_status'  => 'draft',
		'post_type'    => $post->post_type,
		'post_author'  => get_current_user_id(),
		'menu_order'   => $post->menu_order,
	);

	$new_post_id = wp_insert_post( $new_post );

	if ( is_wp_error( $new_post_id ) ) {
		wp_send_json_error( $new_post_id->get_error_message() );
	}

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

	// Copy post meta.
	$meta_keys = get_post_custom_keys( $template_id );
	if ( ! empty( $meta_keys ) ) {
		foreach ( $meta_keys as $meta_key ) {
			$meta_values = get_post_custom_values( $meta_key, $template_id );
			foreach ( $meta_values as $meta_value ) {
				add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
			}
		}
	}

	wp_send_json_success(
		array(
			'message'  => __( 'Template duplicated successfully.', 'coming-soon' ),
			'redirect' => admin_url( 'admin.php?page=seedprod_lite_website_builder' ),
		)
	);
}

/**
 * Get template conditions
 */
function seedprod_lite_v2_get_template_conditions() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Check permissions.
	$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
	if ( ! current_user_can( 'edit_post', $template_id ) ) {
		wp_send_json_error( __( 'You do not have permission to edit this template.', 'coming-soon' ), 403 );
	}

	// Check template type - conditions not applicable for CSS or Part types.
	$template_type = get_post_meta( $template_id, '_seedprod_page_template_type', true );
	if ( 'css' === $template_type || 'part' === $template_type ) {
		wp_send_json_error( __( 'Conditions are not applicable for this template type.', 'coming-soon' ), 400 );
	}

	// Get template name and priority from post.
	$post          = get_post( $template_id );
	$template_name = $post ? $post->post_title : '';

	// Get priority from menu_order (default to 20 if not set).
	$priority = $post && isset( $post->menu_order ) ? $post->menu_order : 20;

	// Get conditions.
	$conditions_json = get_post_meta( $template_id, '_seedprod_theme_template_condition', true );
	$conditions      = array();

	if ( ! empty( $conditions_json ) ) {
		$decoded = json_decode( $conditions_json, true );
		if ( is_array( $decoded ) ) {
			$conditions = $decoded;
		}
	}

	wp_send_json_success(
		array(
			'name'       => $template_name,
			'type'       => $template_type,
			'priority'   => absint( $priority ),
			'conditions' => $conditions,
		)
	);
}

/**
 * Save template conditions
 */
function seedprod_lite_v2_save_template_conditions() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Check permissions.
	$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
	if ( ! current_user_can( 'edit_post', $template_id ) ) {
		wp_send_json_error( __( 'You do not have permission to edit this template.', 'coming-soon' ), 403 );
	}

	// Check template type - conditions not applicable for CSS or Part types.
	$template_type = get_post_meta( $template_id, '_seedprod_page_template_type', true );
	if ( 'css' === $template_type || 'part' === $template_type ) {
		wp_send_json_error( __( 'Conditions are not applicable for this template type.', 'coming-soon' ), 400 );
	}

	// Get and validate template name and priority.
	$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
	$priority      = isset( $_POST['priority'] ) ? absint( wp_unslash( $_POST['priority'] ) ) : 20;

	// Update post title and menu_order (priority).
	if ( ! empty( $template_name ) ) {
		wp_update_post(
			array(
				'ID'         => $template_id,
				'post_title' => $template_name,
				'menu_order' => $priority,
			)
		);
	} else {
		// Update only priority if name is empty.
		wp_update_post(
			array(
				'ID'         => $template_id,
				'menu_order' => $priority,
			)
		);
	}

	// Also save priority in meta for consistency.
	update_post_meta( $template_id, '_seedprod_priority', $priority );

	// Get and validate conditions.
	$conditions_json = isset( $_POST['conditions'] ) ? sanitize_text_field( wp_unslash( $_POST['conditions'] ) ) : '[]';
	$conditions      = json_decode( $conditions_json, true );

	if ( ! is_array( $conditions ) ) {
		wp_send_json_error( __( 'Invalid conditions format.', 'coming-soon' ) );
	}

	// Sanitize conditions.
	$sanitized_conditions = array();
	foreach ( $conditions as $condition ) {
		if ( isset( $condition['type'] ) && ! empty( $condition['type'] ) ) {
			$sanitized_condition    = array(
				'type'      => sanitize_text_field( $condition['type'] ),
				'condition' => isset( $condition['condition'] ) ? sanitize_text_field( $condition['condition'] ) : 'include',
				'value'     => isset( $condition['value'] ) ? sanitize_text_field( $condition['value'] ) : '',
			);
			$sanitized_conditions[] = $sanitized_condition;
		}
	}

	// Save conditions.
	update_post_meta( $template_id, '_seedprod_theme_template_condition', wp_json_encode( $sanitized_conditions ) );

	wp_send_json_success(
		array(
			'message'    => __( 'Conditions saved successfully.', 'coming-soon' ),
			'conditions' => $sanitized_conditions,
		)
	);
}

/**
 * Create new theme template
 */
function seedprod_lite_v2_create_template() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_themetemplate_capability', 'edit_others_posts' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to create templates.', 'coming-soon' ), 403 );
	}

	// Get and validate input.
	$template_name       = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
	$template_type       = isset( $_POST['template_type'] ) ? sanitize_text_field( wp_unslash( $_POST['template_type'] ) ) : '';
	$template_priority   = isset( $_POST['template_priority'] ) ? absint( wp_unslash( $_POST['template_priority'] ) ) : 20;
	$template_conditions = isset( $_POST['template_conditions'] ) ? sanitize_text_field( wp_unslash( $_POST['template_conditions'] ) ) : '[]';

	if ( empty( $template_name ) ) {
		wp_send_json_error( __( 'Template name is required.', 'coming-soon' ) );
	}

	if ( empty( $template_type ) ) {
		wp_send_json_error( __( 'Template type is required.', 'coming-soon' ) );
	}

	// Validate and sanitize conditions.
	$conditions = json_decode( $template_conditions, true );
	if ( ! is_array( $conditions ) ) {
		$conditions = array();
	}

	$sanitized_conditions = array();
	foreach ( $conditions as $condition ) {
		if ( isset( $condition['type'] ) && ! empty( $condition['type'] ) ) {
			$sanitized_condition    = array(
				'type'      => sanitize_text_field( $condition['type'] ),
				'condition' => isset( $condition['condition'] ) ? sanitize_text_field( $condition['condition'] ) : 'include',
				'value'     => isset( $condition['value'] ) ? sanitize_text_field( $condition['value'] ) : '',
			);
			$sanitized_conditions[] = $sanitized_condition;
		}
	}

	// Load base page settings from template - matches old logic in app/lpage.php line 87-90.
	require_once SEEDPROD_PLUGIN_PATH . 'resources/data-templates/basic-page.php';
	$settings = json_decode( $seedprod_basic_lpage );

	// Define template parts - matches old logic line 105-110.
	// In old system, most theme templates use page_type="page".
	$template_parts = array( 'header', 'footer', 'part', 'page' );

	// For theme templates, use "page" as page_type (matches old logic).
	// Only header/footer/part use their specific type.
	if ( in_array( $template_type, array( 'header', 'footer', 'part' ), true ) ) {
		$seedprod_page_type = $template_type;
	} else {
		// All other theme templates (single_post, archive, etc.) use type="page".
		$seedprod_page_type = 'page';
	}

	$settings->is_new      = true;
	$settings->page_type   = $seedprod_page_type;
	$settings->post_title  = $template_name;
	$settings->post_status = 'draft';

	// Set blank template for template parts - matches old logic line 157-159.
	if ( in_array( $seedprod_page_type, $template_parts, true ) ) {
		$settings->template_id = 71;
	}

	$settings_json = wp_json_encode( $settings );

	// Create the post with settings in post_content_filtered - matches old logic line 164-182.
	$post_data = array(
		'post_title'            => $template_name,
		'post_status'           => 'draft',
		'post_type'             => 'seedprod',
		'post_content'          => '',
		'post_content_filtered' => $settings_json,
		'comment_status'        => 'closed',
		'ping_status'           => 'closed',
		'menu_order'            => $template_priority,
		'meta_input'            => array(
			'_seedprod_page'               => true,
			'_seedprod_page_uuid'          => wp_generate_uuid4(),
			'_seedprod_page_template_type' => $seedprod_page_type,
		),
	);

	$template_id = wp_insert_post( $post_data, true );

	if ( is_wp_error( $template_id ) ) {
		wp_send_json_error( $template_id->get_error_message() );
	}

	// Set template metadata - matches old logic line 209.
	update_post_meta( $template_id, '_seedprod_is_theme_template', true );

	// Set conditions - matches old logic line 210.
	update_post_meta( $template_id, '_seedprod_theme_template_condition', wp_json_encode( $sanitized_conditions ) );

	// Build the builder URL - matches old redirect format from app/lpage.php line 222.
	$builder_url = add_query_arg(
		array(
			'page' => 'seedprod_lite_builder',
			'id'   => $template_id,
		),
		admin_url( 'admin.php' )
	) . '#/setup/' . $template_id . '/block-options';

	wp_send_json_success(
		array(
			'message'      => sprintf(
			/* translators: %s: template name */
				__( 'Template "%s" created successfully.', 'coming-soon' ),
				$template_name
			),
			'template_id'  => $template_id,
			'redirect_url' => $builder_url,
		)
	);
}

/**
 * Trash theme template
 */
function seedprod_lite_v2_trash_template() {
	// Check nonce.
	$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
	check_ajax_referer( 'seedprod_trash_template_' . $template_id, 'nonce' );

	// Check permissions.
	if ( ! current_user_can( 'delete_post', $template_id ) ) {
		wp_send_json_error( __( 'You do not have permission to trash this template.', 'coming-soon' ), 403 );
	}

	// Trash the post.
	$result = wp_trash_post( $template_id );

	if ( ! $result ) {
		wp_send_json_error( __( 'Failed to trash template.', 'coming-soon' ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Template moved to trash.', 'coming-soon' ),
		)
	);
}

/**
 * Restore theme template from trash
 */
function seedprod_lite_v2_restore_template() {
	// Check nonce.
	$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
	check_ajax_referer( 'seedprod_restore_template_' . $template_id, 'nonce' );

	// Check permissions.
	if ( ! current_user_can( 'delete_post', $template_id ) ) {
		wp_send_json_error( __( 'You do not have permission to restore this template.', 'coming-soon' ), 403 );
	}

	// Restore the post.
	$result = wp_untrash_post( $template_id );

	if ( ! $result ) {
		wp_send_json_error( __( 'Failed to restore template.', 'coming-soon' ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Template restored.', 'coming-soon' ),
		)
	);
}

/**
 * Delete theme template permanently
 */
function seedprod_lite_v2_delete_template() {
	// Check nonce.
	$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
	check_ajax_referer( 'seedprod_delete_template_' . $template_id, 'nonce' );

	// Check permissions.
	if ( ! current_user_can( 'delete_post', $template_id ) ) {
		wp_send_json_error( __( 'You do not have permission to delete this template.', 'coming-soon' ), 403 );
	}

	// Delete the post permanently.
	$result = wp_delete_post( $template_id, true );

	if ( ! $result ) {
		wp_send_json_error( __( 'Failed to delete template.', 'coming-soon' ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Template deleted permanently.', 'coming-soon' ),
		)
	);
}

/**
 * Handle bulk actions for theme templates
 */
function seedprod_lite_v2_bulk_action_templates() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Check permissions.
	if ( ! current_user_can( apply_filters( 'seedprod_themetemplate_capability', 'edit_others_posts' ) ) ) {
		wp_send_json_error( __( 'You do not have permission to perform this action.', 'coming-soon' ), 403 );
	}

	$action       = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
	$template_ids = isset( $_POST['template_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['template_ids'] ) ) : array();

	if ( empty( $action ) || empty( $template_ids ) ) {
		wp_send_json_error( __( 'Invalid request.', 'coming-soon' ) );
	}

	$success_count = 0;
	$error_count   = 0;

	foreach ( $template_ids as $template_id ) {
		// Check individual permissions.
		if ( ! current_user_can( 'delete_post', $template_id ) ) {
			++$error_count;
			continue;
		}

		switch ( $action ) {
			case 'trash':
				if ( wp_trash_post( $template_id ) ) {
					++$success_count;
				} else {
					++$error_count;
				}
				break;
			case 'restore':
				if ( wp_untrash_post( $template_id ) ) {
					++$success_count;
				} else {
					++$error_count;
				}
				break;
			case 'delete':
				if ( wp_delete_post( $template_id, true ) ) {
					++$success_count;
				} else {
					++$error_count;
				}
				break;
		}
	}

	if ( $success_count > 0 ) {
		$message = sprintf(
			/* translators: %d is the number of templates */
			_n( '%d template processed.', '%d templates processed.', $success_count, 'coming-soon' ),
			$success_count
		);

		if ( $error_count > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d is the number of errors */
				_n( '%d error.', '%d errors.', $error_count, 'coming-soon' ),
				$error_count
			);
		}

		wp_send_json_success(
			array(
				'message'       => $message,
				'success_count' => $success_count,
				'error_count'   => $error_count,
			)
		);
	} else {
		wp_send_json_error( __( 'Failed to process templates.', 'coming-soon' ) );
	}
}

/**
 * Get theme template counts
 */
function seedprod_lite_v2_get_theme_template_counts() {
	$counts = array(
		'headers' => 0,
		'footers' => 0,
		'pages'   => 0,
		'parts'   => 0,
		'total'   => 0,
	);

	// Query theme templates.
	$args = array(
		'post_type'      => 'seedprod_lite_theme_template',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_seedprod_type',
				'compare' => 'EXISTS',
			),
		),
	);

	$templates = get_posts( $args );

	foreach ( $templates as $template ) {
		$type = get_post_meta( $template->ID, '_seedprod_type', true );

		switch ( $type ) {
			case 'header':
				++$counts['headers'];
				break;
			case 'footer':
				++$counts['footers'];
				break;
			case 'page':
			case 'single_post':
			case 'single_page':
			case 'archive':
			case 'search':
			case 'author':
			case 'single_product':
			case 'archive_product':
				++$counts['pages'];
				break;
			case 'part':
				++$counts['parts'];
				break;
		}
		++$counts['total'];
	}

	return $counts;
}

/**
 * Check if we should prompt to create default pages when enabling theme
 */
function seedprod_lite_v2_should_create_default_pages() {
	$show_on_front = get_option( 'show_on_front' );
	return ( 'page' !== $show_on_front );
}

/**
 * AJAX handler to check if default pages should be created
 */
function seedprod_lite_v2_check_default_pages() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Check permissions.
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_send_json_error( __( 'You do not have permission to check page settings.', 'coming-soon' ), 403 );
	}

	// Check if we should create default pages.
	$should_create = seedprod_lite_v2_should_create_default_pages();

	wp_send_json_success(
		array(
			'should_create' => $should_create,
			'show_on_front' => get_option( 'show_on_front' ),
		)
	);
}

/**
 * Create default blog and home pages for theme
 */
function seedprod_lite_v2_create_default_pages() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Check permissions.
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_send_json_error( __( 'You do not have permission to create pages.', 'coming-soon' ), 403 );
	}

	$created = array();

	// Create blog page if it doesn't exist.
	$blog_page = get_page_by_path( 'blog' );
	if ( empty( $blog_page ) ) {
		$blog_page_id = wp_insert_post(
			array(
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_author'    => get_current_user_id(),
				'post_title'     => 'Blog',
				'post_name'      => 'blog',
				'post_status'    => 'publish',
				'post_content'   => '',
				'post_type'      => 'page',
			)
		);

		if ( $blog_page_id ) {
			$created['blog'] = $blog_page_id;
		}
	} else {
		$blog_page_id = $blog_page->ID;
	}

	// Create home page if it doesn't exist.
	$home_page = get_page_by_path( 'home' );
	if ( empty( $home_page ) ) {
		$home_page_id = wp_insert_post(
			array(
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_author'    => get_current_user_id(),
				'post_title'     => 'Home',
				'post_name'      => 'home',
				'post_status'    => 'publish',
				'post_content'   => '',
				'post_type'      => 'page',
			)
		);

		if ( $home_page_id ) {
			$created['home'] = $home_page_id;
		}
	} else {
		$home_page_id = $home_page->ID;
	}

	// Set as front and posts pages.
	if ( $home_page_id && $blog_page_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_page_id );
		update_option( 'page_for_posts', $blog_page_id );

		wp_send_json_success(
			array(
				'message' => __( 'Home and Blog pages have been created and set as your front and posts pages.', 'coming-soon' ),
				'created' => $created,
			)
		);
	} else {
		wp_send_json_error( __( 'Failed to create pages. Please check your permissions.', 'coming-soon' ) );
	}
}

/**
 * Get theme kits from API (V2 version)
 * AJAX handler for fetching theme kits from SeedProd API
 */
function seedprod_lite_v2_get_theme_kits() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Check permissions.
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_send_json_error( __( 'You do not have permission to access theme kits.', 'coming-soon' ), 403 );
	}

	// Get parameters.
	$api_url  = isset( $_POST['api_url'] ) ? sanitize_text_field( wp_unslash( $_POST['api_url'] ) ) : '';
	$filter   = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'themes';
	$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
	$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$sort     = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : '';

	// Get API key and site token.
	$api_token  = get_option( 'seedprod_api_token' );
	$site_token = get_option( 'seedprod_token' );

	// Build full API URL with parameters.
	$api_url .= '&plugin_version=' . SEEDPROD_VERSION;

	// WORKAROUND: The filter=favorites endpoint has caching issues.
	// Instead of using filter=favorites, we'll get all themes and filter client-side.
	if ( 'favorites' === $filter ) {
		// Get all themes instead of using broken favorites endpoint.
		$api_url .= '&filter=themes';
	} else {
		$api_url .= '&filter=' . $filter;
	}

	if ( ! empty( $category ) ) {
		$api_url .= '&cat=' . $category;
	}

	if ( ! empty( $search ) ) {
		$api_url .= '&s=' . rawurlencode( $search );
	}

	if ( ! empty( $sort ) ) {
		$api_url .= '&sort=' . $sort;
	}

	if ( ! empty( $api_token ) ) {
		$api_url .= '&api_token=' . $api_token;
	}

	if ( ! empty( $site_token ) ) {
		$api_url .= '&site_token=' . $site_token;
	}

	// Make API request.
	$response = wp_remote_get(
		$api_url,
		array(
			'timeout' => 30,
			'headers' => array(
				'Referer' => home_url(),
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message() );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		wp_send_json_error( __( 'Invalid response from API', 'coming-soon' ) );
	}

	// WORKAROUND: Filter favorites on our side if needed.
	if ( 'favorites' === $filter && isset( $data['templates']['data'] ) && isset( $data['favs'] ) ) {
		// We got all themes, now filter to only favorites.
		$favorites_only = array();
		$favs_array     = $data['favs'];

		foreach ( $data['templates']['data'] as $theme ) {
			if ( in_array( $theme['id'], $favs_array, true ) ) {
				$theme['favorited'] = true;
				$favorites_only[]   = $theme;
			}
		}

		// Parse the page number from the API URL if available.
		$current_page = 1;
		if ( preg_match( '/page=(\d+)/', $api_url, $matches ) ) {
			$current_page = intval( $matches[1] );
		}

		// Implement pagination on the filtered results.
		$per_page        = 50; // Match the API's per_page.
		$total_favorites = count( $favorites_only );
		$total_pages     = ceil( $total_favorites / $per_page );

		// Calculate offset and slice the array for current page.
		$offset              = ( $current_page - 1 ) * $per_page;
		$paginated_favorites = array_slice( $favorites_only, $offset, $per_page );

		// Return filtered data in the expected format with proper pagination.
		$filtered_data = array(
			'data'         => $paginated_favorites,
			'current_page' => $current_page,
			'last_page'    => $total_pages,
			'per_page'     => $per_page,
			'total'        => $total_favorites,
			'from'         => $offset + 1,
			'to'           => min( $offset + $per_page, $total_favorites ),
		);

		wp_send_json_success( $filtered_data );
	} else {
		// For non-favorites requests, send data as-is.
		wp_send_json_success( $data );
	}
}

/**
 * Toggle favorite status for a theme kit via SeedProd API
 */
function seedprod_lite_v2_toggle_favorite_theme() {
	// Check nonce.
	check_ajax_referer( 'seedprod_v2_nonce', 'nonce' );

	// Get parameters.
	$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';
	$method      = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : 'attach';

	if ( empty( $template_id ) ) {
		wp_send_json_error( __( 'Invalid template ID', 'coming-soon' ) );
	}

	// Get API tokens.
	$api_token  = get_option( 'seedprod_api_token' );
	$site_token = get_option( 'seedprod_token' );

	// Call SeedProd API to update favorite status.
	$api_url = SEEDPROD_API_URL . 'template-update';

	$response = wp_remote_post(
		$api_url,
		array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Referer'      => home_url(),
			),
			'body'    => array(
				'template_id' => $template_id,
				'method'      => $method,
				'api_token'   => $api_token,
				'site_token'  => $site_token,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message() );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	wp_send_json_success(
		array(
			'message'  => 'attach' === $method ? __( 'Theme added to favorites', 'coming-soon' ) : __( 'Theme removed from favorites', 'coming-soon' ),
			'response' => $data,
		)
	);
}

/**
 * Import theme from API (V2 implementation)
 * Downloads and imports a theme kit from the SeedProd API
 */
function seedprod_lite_v2_import_theme_request() {
	// Check nonce.
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Invalid security token', 'coming-soon' ) );
	}

	// Check permissions.
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_send_json_error( __( 'Insufficient permissions', 'coming-soon' ) );
	}

	// Get theme ID.
	$theme_id = isset( $_POST['theme_id'] ) ? absint( $_POST['theme_id'] ) : 0;

	if ( empty( $theme_id ) ) {
		wp_send_json_error( __( 'Invalid theme ID', 'coming-soon' ) );
	}

	// Get remote theme from API.
	$code   = '';
	$apikey = get_option( 'seedprod_api_token' );
	$url    = SEEDPROD_API_URL . 'themes?plugin_version=' . SEEDPROD_VERSION . '&id=' . $theme_id . '&filter=theme_code_zip&api_token=' . $apikey;

	$response = wp_remote_get( $url, array( 'timeout' => 60 ) );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message() );
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $response_code ) {
		$error_message = __( 'Please enter a valid license key to access the themes.', 'coming-soon' );
		wp_send_json_error( $error_message );
	}

	$code      = wp_remote_retrieve_body( $response );
	$full_code = json_decode( $code );

	// Check if it's a zip file or legacy code.
	if ( ! empty( $full_code->zipfile ) ) {
		// Call the V2 import by URL function.
		$result = seedprod_lite_v2_import_theme_by_url( $full_code->zipfile );

		// Handle the result.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Success - send JSON response.
		wp_send_json_success( array( 'message' => __( 'Theme imported successfully.', 'coming-soon' ) ) );
		return; // This won't execute due to wp_send_json_success.
	}

	// Process legacy theme import (non-zip format).
	if ( empty( $full_code->code ) ) {
		wp_send_json_error( __( 'Invalid theme data received from API.', 'coming-soon' ) );
	}

	$full_code        = $full_code->code;
	$theme            = isset( $full_code->theme ) ? $full_code->theme : array();
	$shortcode_update = isset( $full_code->mapped ) ? $full_code->mapped : array();

	$imports = array();
	foreach ( $theme as $k => $v ) {
		$imports[] = array(
			'post_content'          => base64_decode( $v->post_content ), // phpcs:ignore
			'post_content_filtered' => base64_decode( $v->post_content_filtered ), // phpcs:ignore
			'post_title'            => base64_decode( $v->post_title ), // phpcs:ignore
			'meta'                  => json_decode( base64_decode( $v->meta ) ), // phpcs:ignore
			'order'                 => $v->order,
		);
	}

	$shortcode_array = array();
	foreach ( $shortcode_update as $k => $t ) {
		$shortcode_array[] = array(
			'shortcode'  => base64_decode( $t->shortcode ), // phpcs:ignore
			'page_title' => $t->page_title,
		);
	}

	$import_page_array = array();

	// Process each template.
	foreach ( $imports as $k1 => $v1 ) {
		$meta = $v1['meta'];

		$data = array(
			'comment_status' => 'closed',
			'menu_order'     => $v1['order'],
			'ping_status'    => 'closed',
			'post_status'    => 'publish',
			'post_title'     => $v1['post_title'],
			'post_type'      => 'seedprod',
			'meta_input'     => array(
				'_seedprod_page'               => true,
				'_seedprod_is_theme_template'  => true,
				'_seedprod_page_uuid'          => wp_generate_uuid4(),
				'_seedprod_page_template_type' => $meta->_seedprod_page_template_type[0],
			),
		);

		$id = wp_insert_post( $data, true );

		if ( is_wp_error( $id ) ) {
			continue;
		}

		$import_page_array[] = array(
			'id'                    => $id,
			'title'                 => $v1['post_title'],
			'post_content'          => $v1['post_content'],
			'post_content_filtered' => $v1['post_content_filtered'],
		);

		// Reinsert settings because wp_insert can mess up JSON.
		$post_content_filtered = $v1['post_content_filtered'];
		$post_content          = $v1['post_content'];

		// For CSS templates, ensure page_type is set in the JSON.
		if ( 'css' === $meta->_seedprod_page_template_type[0] ) {
			$json_data = json_decode( $post_content_filtered, true );
			if ( null !== $json_data ) {
				// Ensure page_type is set at the root level.
				$json_data['page_type'] = 'css';
				$post_content_filtered  = wp_json_encode( $json_data );
			}
		}

		global $wpdb;
		$tablename = esc_sql( $wpdb->prefix . 'posts' );
		$sql       = "UPDATE $tablename SET post_content_filtered = %s, post_content = %s WHERE id = %d";
		$safe_sql  = $wpdb->prepare( $sql, $post_content_filtered, $post_content, $id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $safe_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Add meta data.
		if ( 'css' === $meta->_seedprod_page_template_type[0] ) {
			// Handle CSS template (Global CSS).
			// The CSS content for Global CSS templates is in post_content, not meta.
			// We need to use the meta if available, otherwise use post_content.
			if ( isset( $meta->_seedprod_css[0] ) && ! empty( $meta->_seedprod_css[0] ) ) {
				// Use meta if available (legacy format).
				$css = str_replace( 'TO_BE_REPLACED', home_url(), $meta->_seedprod_css[0] );
			} else {
				// For newer exports, CSS is in post_content.
				$css = str_replace( 'TO_BE_REPLACED', home_url(), $v1['post_content'] );
			}

			// Handle custom CSS.
			$custom_css = isset( $meta->_seedprod_custom_css[0] ) ?
				str_replace( 'TO_BE_REPLACED', home_url(), $meta->_seedprod_custom_css[0] ) :
				'';

			// Note: Old code sets custom_css to empty string even after replacement.
			$custom_css = '';

			// Handle builder CSS.
			$builder_css = isset( $meta->_seedprod_builder_css[0] ) ?
				str_replace( 'TO_BE_REPLACED', home_url(), $meta->_seedprod_builder_css[0] ) :
				'';

			update_post_meta( $id, '_seedprod_css', $css );
			update_post_meta( $id, '_seedprod_custom_css', $custom_css );
			update_post_meta( $id, '_seedprod_builder_css', $builder_css );

			// Set BOTH option names (old system uses both).
			update_option( 'global_css_page_id', $id );

			// Trash current CSS file if exists (matching old logic).
			$current_css_file = get_option( 'seedprod_global_css_page_id' );
			if ( ! empty( $current_css_file ) && (int) $current_css_file !== (int) $id ) {
				wp_trash_post( $current_css_file );
			}
			update_option( 'seedprod_global_css_page_id', $id );

			// Generate CSS file.
			if ( ! function_exists( 'seedprod_lite_generate_css_file' ) ) {
				require_once SEEDPROD_PLUGIN_PATH . 'app/functions-utils.php';
			}
			seedprod_lite_generate_css_file( $id, $css . $custom_css );
		} else {
			// Handle other template types.
			if ( ! function_exists( 'seedprod_lite_extract_page_css' ) ) {
				require_once SEEDPROD_PLUGIN_PATH . 'app/functions-utils.php';
			}
			$code = seedprod_lite_extract_page_css( $v1['post_content'], $id );

			if ( isset( $meta->_seedprod_theme_template_condition[0] ) ) {
				update_post_meta( $id, '_seedprod_theme_template_condition', $meta->_seedprod_theme_template_condition[0] );
			}

			update_post_meta( $id, '_seedprod_css', $code['css'] );
			update_post_meta( $id, '_seedprod_html', $code['html'] );

			if ( ! function_exists( 'seedprod_lite_generate_css_file' ) ) {
				require_once SEEDPROD_PLUGIN_PATH . 'app/functions-utils.php';
			}
			seedprod_lite_generate_css_file( $id, $code['css'] );

			// Process condition to see if we need to create a placeholder page (matching old logic).
			if ( isset( $meta->_seedprod_theme_template_condition[0] ) ) {
				$conditions = $meta->_seedprod_theme_template_condition[0];

				if ( ! empty( $conditions ) ) {
					$conditions = json_decode( $conditions );
					if ( is_array( $conditions ) ) {
						// Check if this is a single page condition that needs a placeholder.
						if ( 1 === count( $conditions ) &&
							'include' === $conditions[0]->condition &&
							'is_page(x)' === $conditions[0]->type &&
							! empty( $conditions[0]->value ) &&
							! is_numeric( $conditions[0]->value ) ) {

							// Check if slug already exists.
							global $wpdb;
							$slug_tablename  = esc_sql( $wpdb->prefix . 'posts' );
							$sql             = "SELECT id FROM $slug_tablename WHERE post_name = %s AND post_type = 'page'";
							$safe_sql        = $wpdb->prepare( $sql, $conditions[0]->value ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							$this_slug_exist = $wpdb->get_var( $safe_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

							if ( empty( $this_slug_exist ) ) {
								// Create placeholder page.
								$page_details = array(
									'post_title'   => $v1['post_title'],
									'post_name'    => $conditions[0]->value,
									'post_content' => __( 'This page was auto-generated and is a placeholder page for the SeedProd theme. To manage the contents of this page please visit SeedProd > Theme Builder in the left menu in WordPress.', 'coming-soon' ),
									'post_status'  => 'publish',
									'post_type'    => 'page',
								);
								wp_insert_post( $page_details );
							}
						}
					}
				}
			}
		}
	}

	// Process shortcode replacements.
	foreach ( $import_page_array as $t => $val ) {
		if ( 'Global CSS' === $val['title'] ) {
			continue;
		}

		$post_content          = $val['post_content'];
		$post_content_filtered = $val['post_content_filtered'];
		$post_id               = $val['id'];

		foreach ( $shortcode_array as $k => $t ) {
			$shortcode_page_title = $shortcode_array[ $k ]['page_title'];
			$fetch_shortcode_key = array_search( $shortcode_page_title, array_column( $import_page_array, 'title' ) ); // phpcs:ignore

			if ( false !== $fetch_shortcode_key ) {
				$fetch_shortcode_id = $import_page_array[ $fetch_shortcode_key ]['id'];
				$shortcode_page_sc  = $shortcode_array[ $k ]['shortcode'];
				$shortcode_page_sc  = str_replace( '[sp_template_part id="', '', $shortcode_page_sc );
				$shortcode_page_sc  = str_replace( '"]', '', $shortcode_page_sc );

				if ( $fetch_shortcode_id ) {
					$shortcode_array[ $k ]['updated_shortcode'] = '[sp_template_part id="' . $fetch_shortcode_id . '"]';
					$post_content                               = str_replace( $shortcode_array[ $k ]['shortcode'], $shortcode_array[ $k ]['updated_shortcode'], $post_content );

					$shortcode_array[ $k ]['updated_shortcode_filtered'] = '"templateparts":"' . $fetch_shortcode_id . '"';
					$shortcode_array[ $k ]['shortcode_filtered']         = '"templateparts":"' . $shortcode_page_sc . '"';
					$post_content_filtered                               = str_replace( $shortcode_array[ $k ]['shortcode_filtered'], $shortcode_array[ $k ]['updated_shortcode_filtered'], $post_content_filtered );

					// Update generated HTML.
					$generate_html = get_post_meta( $post_id, '_seedprod_html', true );
					if ( ! empty( $generate_html ) ) {
						$generate_html = str_replace( $shortcode_array[ $k ]['shortcode'], $shortcode_array[ $k ]['updated_shortcode'], $generate_html );
						update_post_meta( $post_id, '_seedprod_html', $generate_html );
					}
				}
			}
		}

		// Update the post with replaced shortcodes.
		global $wpdb;
		$tablename = esc_sql( $wpdb->prefix . 'posts' );
		$sql       = "UPDATE $tablename SET post_content_filtered = %s, post_content = %s WHERE id = %d";
		$safe_sql  = $wpdb->prepare( $sql, $post_content_filtered, $post_content, $post_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $safe_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	// Store the theme ID.
	update_option( 'seedprod_theme_id', $theme_id );

	wp_send_json_success(
		array(
			'message'  => __( 'Theme imported successfully', 'coming-soon' ),
			'theme_id' => $theme_id,
		)
	);
}

/**
 * Delete theme pages (V2 wrapper for existing function)
 * This calls the existing seedprod_lite_delete_theme_pages function
 */
function seedprod_lite_v2_delete_theme_pages() {
	// Check nonce.
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Invalid security token', 'coming-soon' ) );
	}

	// Check permissions.
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_send_json_error( __( 'Insufficient permissions', 'coming-soon' ) );
	}

	global $wpdb;
	$tablename      = esc_sql( $wpdb->prefix . 'posts' );
	$meta_tablename = esc_sql( $wpdb->prefix . 'postmeta' );

	// Find all theme template pages.
	$sql = "SELECT p.ID FROM $tablename p
			LEFT JOIN $meta_tablename pm ON (pm.post_id = p.ID)
			WHERE post_type = 'seedprod'
			AND meta_key = '_seedprod_is_theme_template'
			AND post_status != 'trash'"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( empty( $results ) ) {
		wp_send_json_error( __( 'No theme template pages found to delete', 'coming-soon' ) );
	}

	$deleted_count = 0;
	foreach ( $results as $result ) {
		if ( wp_trash_post( $result->ID ) ) {
			++$deleted_count;
		}
	}

	wp_send_json_success(
		array(
			/* translators: %d is the number of theme templates */
			'message'       => sprintf( __( '%d theme templates moved to trash', 'coming-soon' ), $deleted_count ),
			'deleted_count' => $deleted_count,
		)
	);
}

/**
 * AJAX handler to get total theme pages count (V2)
 */
function seedprod_lite_v2_get_total_theme_pages() {
	// Check nonce.
	if ( ! check_ajax_referer( 'seedprod_v2_nonce', 'nonce', false ) ) {
		wp_send_json_error( __( 'Invalid security token', 'coming-soon' ) );
	}

	// Check permissions.
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_send_json_error( __( 'Insufficient permissions', 'coming-soon' ) );
	}

	global $wpdb;
	$tablename      = esc_sql( $wpdb->prefix . 'posts' );
	$meta_tablename = esc_sql( $wpdb->prefix . 'postmeta' );

	$sql = "SELECT COUNT(DISTINCT p.ID) FROM $tablename p
			LEFT JOIN $meta_tablename pm ON (pm.post_id = p.ID)
			WHERE p.post_type = 'seedprod'
			AND pm.meta_key = '_seedprod_is_theme_template'
			AND p.post_status != 'trash'"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$count = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	wp_send_json_success(
		array(
			'count' => intval( $count ),
		)
	);
}
