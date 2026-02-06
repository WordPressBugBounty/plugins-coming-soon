<?php
/**
 * Setup Wizard functions for SeedProd Admin (V2)
 *
 * Handles the return flow from the external SaaS setup wizard
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
 * Complete Setup Wizard (V2 Admin)
 * Handles the return from the external SaaS setup wizard
 * Creates pages based on the wizard selections
 *
 * Migrated from /app/setup-wizard.php for new admin system
 */
function seedprod_lite_v2_complete_setup_wizard() {
	if ( check_ajax_referer( 'seedprod_lite_v2_complete_setup_wizard' ) ) {
		if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$wizard_id = isset( $_POST['wizard_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wizard_id'] ) ) : null;

		// Get the wizard data with id and token.
		$site_token = get_option( 'seedprod_token' );

		$data = array(
			'wizard_id'  => $wizard_id,
			'site_token' => $site_token,
		);

		$headers = array();

		// Build the headers of the request.
		$headers = wp_parse_args(
			$headers,
			array(
				'Accept' => 'application/json',
			)
		);

		$url      = SEEDPROD_API_URL . 'get-wizard-data';
		$response = wp_remote_post(
			$url,
			array(
				'body'    => $data,
				'headers' => $headers,
			)
		);

		$status_code = wp_remote_retrieve_response_code( $response );

		// Handle errors.
		if ( is_wp_error( $response ) ) {
			// Load utility functions for get_ip if needed.
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
			// Load utility functions for get_ip if needed.
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

		// Store the wizard id and data locally.
		$onboarding = $body->onboarding;

		// Store the wizard verify plugins.
		update_option( 'seedprod_verify_wizard_options', $onboarding->options );

		// Mark wizard as completed/dismissed so it won't show again.
		update_option( 'seedprod_dismiss_setup_wizard', 1 );

		// Set tracking if they have opted in.
		if ( ! empty( $onboarding->allow_usagetracking ) ) {
			update_option( 'seedprod_allow_usage_tracking', true );
		}

		// Free templates.
		if ( ! empty( $onboarding->email ) ) {
			update_option( 'seedprod_free_templates_subscribed', true );
		}

		// Get template type that was setup in the onboarding.
		$type = 'lp';
		if ( ! empty( $onboarding->sp_type ) ) {
			$type = $onboarding->sp_type;
		}

		$id = null;

		// Create a landing page/coming soon/maintenance/404/login page.
		if ( 'lp' === $type || 'cs' === $type || 'mm' === $type || 'p404' === $type || 'loginp' === $type ) {

			// Install template.
			$cpt = 'page';
			// SeedProd CPT types.
			$cpt_types = array(
				'cs',
				'mm',
				'p404',
				'loginp',
				'header',
				'footer',
				'part',
				'page',
			);

			if ( in_array( $type, $cpt_types, true ) ) {
				$cpt = 'seedprod';
			}

			// Base page settings.
			require_once SEEDPROD_PLUGIN_PATH . 'resources/data-templates/basic-page.php';
			$basic_settings              = json_decode( $seedprod_basic_lpage, true );
			$basic_settings['is_new']    = true;
			$basic_settings['page_type'] = $type;

			// Set slug based on type.
			$slug       = '';
			$lpage_name = '';

			if ( 'cs' === $type ) {
				$slug                               = 'sp-cs';
				$lpage_name                         = $slug;
				$basic_settings['no_conflict_mode'] = true;
			}
			if ( 'mm' === $type ) {
				$slug                               = 'sp-mm';
				$lpage_name                         = $slug;
				$basic_settings['no_conflict_mode'] = true;
			}
			if ( 'p404' === $type ) {
				$slug                               = 'sp-p404';
				$lpage_name                         = $slug;
				$basic_settings['no_conflict_mode'] = true;
			}
			if ( 'loginp' === $type ) {
				$slug                               = 'sp-login';
				$lpage_name                         = $slug;
				$basic_settings['no_conflict_mode'] = true;
			}

			// Insert page code.
			$code = '';
			if ( ! empty( $onboarding->code ) ) {
				$code = base64_decode( $onboarding->code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Used for decoding template data, not obfuscation.
			}

			$code = json_decode( $code, true );

			// Merge in code.
			if ( ! empty( $slug ) ) {
				$basic_settings['post_title'] = $slug;
				$basic_settings['post_name']  = $slug;
			}

			$basic_settings['template_id'] = intval( $onboarding->template_id );

			$new_settings = $basic_settings;
			if ( 99999 !== $onboarding->template_id ) {
				unset( $basic_settings['document'] );
				if ( is_array( $code ) ) {
					$new_settings = $basic_settings + $code;
				}
			}

			$encoded_settings = wp_json_encode( $new_settings );

			$id = wp_insert_post(
				array(
					'comment_status'        => 'closed',
					'ping_status'           => 'closed',
					'post_content'          => '',
					'post_status'           => 'draft',
					'post_title'            => 'seedprod',
					'post_type'             => $cpt,
					'post_name'             => $slug,
					'post_content_filtered' => $encoded_settings,
					'meta_input'            => array(
						'_seedprod_page'               => true,
						'_seedprod_page_uuid'          => wp_generate_uuid4(),
						'_seedprod_page_template_type' => $type,
					),
				),
				true
			);

			// Reinsert settings because wp_insert screws up json (following old working logic).
			if ( ! is_wp_error( $id ) && ! empty( $encoded_settings ) ) {
				global $wpdb;
				$tablename = $wpdb->prefix . 'posts';
				$sql = "UPDATE $tablename SET post_content_filtered = %s WHERE id = %d";
				$safe_sql = $wpdb->prepare( $sql, $encoded_settings, $id );
				$wpdb->query( $safe_sql );
			}

			// Update pointer - record page IDs for each type.
			if ( 'cs' === $type ) {
				update_option( 'seedprod_coming_soon_page_id', $id );
			}
			if ( 'mm' === $type ) {
				update_option( 'seedprod_maintenance_mode_page_id', $id );
			}
			if ( 'p404' === $type ) {
				update_option( 'seedprod_404_page_id', $id );
			}
			if ( 'loginp' === $type ) {
				update_option( 'seedprod_login_page_id', $id );
			}

			// If landing page set a temp name.
			if ( 'lp' === $type ) {
				if ( is_numeric( $id ) ) {
					$lpage_name = esc_html__( 'New Page', 'coming-soon' ) . " (ID #$id)";
				} else {
					$lpage_name = esc_html__( 'New Page', 'coming-soon' );
				}
			}

			wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => $lpage_name,
				)
			);
		}

		// Install theme if theme is the type.
		if ( 'websitebuilder' === $type || 'woocommerce' === $type ) {
			$template_id = $onboarding->template_id;

			// Call theme import function if it exists.
			if ( function_exists( 'seedprod_lite_theme_import' ) ) {
				seedprod_lite_theme_import( $template_id );
			}
		}

		// Filter out already installed plugins.
		$filtered_options = array();
		if ( ! empty( $onboarding->options ) ) {
			$options_array = json_decode( $onboarding->options, true );
			if ( is_array( $options_array ) ) {
				$all_plugins = get_plugins();

				// Check each recommended plugin.
				foreach ( $options_array as $plugin_key ) {
					$needs_install = false;

					switch ( $plugin_key ) {
						case 'rafflepress':
							if ( ! isset( $all_plugins['rafflepress/rafflepress.php'] ) &&
								! isset( $all_plugins['rafflepress-pro/rafflepress-pro.php'] ) ) {
								$needs_install = true;
							}
							break;
						case 'allinoneseo':
							if ( ! isset( $all_plugins['all-in-one-seo-pack/all_in_one_seo_pack.php'] ) &&
								! isset( $all_plugins['all-in-one-seo-pack-pro/all_in_one_seo_pack.php'] ) &&
								! isset( $all_plugins['seo-by-rank-math/rank-math.php'] ) &&
								! isset( $all_plugins['wordpress-seo/wp-seo.php'] ) &&
								! isset( $all_plugins['wordpress-seo-premium/wp-seo-premium.php'] ) ) {
								$needs_install = true;
							}
							break;
						case 'wpforms':
							if ( ! isset( $all_plugins['wpforms-lite/wpforms.php'] ) &&
								! isset( $all_plugins['wpforms/wpforms.php'] ) ) {
								$needs_install = true;
							}
							break;
						case 'optinmonster':
							if ( ! isset( $all_plugins['optinmonster/optin-monster-wp-api.php'] ) ) {
								$needs_install = true;
							}
							break;
						case 'ga':
						case 'monsterinsights':
							if ( ! isset( $all_plugins['google-analytics-for-wordpress/googleanalytics.php'] ) &&
								! isset( $all_plugins['google-analytics-premium/googleanalytics-premium.php'] ) ) {
								$needs_install = true;
							}
							break;
					}

					if ( $needs_install ) {
						$filtered_options[] = $plugin_key;
					}
				}
			}
		}

		// Return response.
		$response = array(
			'status'  => 'true',
			'type'    => $type,
			'id'      => $id,
			'options' => $filtered_options,  // Return filtered array, not JSON string.
		);

		wp_send_json_success( $response );
	}
}

/**
 * Install Add-on Setup (V2 Admin)
 * Installs and activates recommended plugins from the setup wizard
 *
 * Migrated from /app/setup-wizard.php for new admin system
 */
function seedprod_lite_v2_install_addon_setup() {
	// Run a security check.
	check_ajax_referer( 'seedprod_lite_v2_install_addon_setup', 'nonce' );

	// Check for permissions.
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error();
	}

	// Plugin mapping.
	$paths_map = array(
		'rafflepress'  => array(
			'slug' => 'rafflepress/rafflepress.php',
			'url'  => 'https://downloads.wordpress.org/plugin/rafflepress.zip',
		),
		'allinoneseo'  => array(
			'slug' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
			'url'  => 'https://downloads.wordpress.org/plugin/all-in-one-seo-pack.zip',
		),
		'ga'           => array(
			'slug' => 'google-analytics-for-wordpress/googleanalytics.php',
			'url'  => 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip',
		),
		'wpforms'      => array(
			'slug' => 'wpforms-lite/wpforms.php',
			'url'  => 'https://downloads.wordpress.org/plugin/wpforms-lite.zip',
		),
		'optinmonster' => array(
			'slug' => 'optinmonster/optin-monster-wp-api.php',
			'url'  => 'https://downloads.wordpress.org/plugin/optinmonster.zip',
		),
	);

	$options = get_option( 'seedprod_verify_wizard_options' );
	$options = json_decode( $options );

	// This allows us to do one at a time.
	if ( isset( $_POST['plugin'] ) ) {
		$plugin  = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );
		$options = array( $plugin );
	}

	$install_plugins = array();
	$all_plugins     = get_plugins();

	// Purge options to make sure we don't install plugins with conflicts.
	if ( in_array( 'allinoneseo', $options, true ) ) {
		if (
			isset( $all_plugins['all-in-one-seo-pack/all_in_one_seo_pack.php'] ) ||
			isset( $all_plugins['all-in-one-seo-pack-pro/all_in_one_seo_pack.php'] ) ||
			isset( $all_plugins['seo-by-rank-math/rank-math.php'] ) ||
			isset( $all_plugins['wordpress-seo/wp-seo.php'] ) ||
			isset( $all_plugins['wordpress-seo-premium/wp-seo-premium.php'] ) ||
			isset( $all_plugins['autodescription/autodescription.php'] )
		) {
			$key = array_search( 'allinoneseo', $options, true );
			if ( false !== $key ) {
				unset( $options[ $key ] );
			}
		}
	}

	if ( in_array( 'rafflepress', $options, true ) ) {
		if (
			isset( $all_plugins['rafflepress/rafflepress.php'] ) ||
			isset( $all_plugins['rafflepress-pro/rafflepress-pro.php'] )
		) {
			$key = array_search( 'rafflepress', $options, true );
			if ( false !== $key ) {
				unset( $options[ $key ] );
			}
		}
	}

	if ( in_array( 'wpforms', $options, true ) ) {
		if (
			isset( $all_plugins['wpforms-lite/wpforms.php'] ) ||
			isset( $all_plugins['wpforms/wpforms.php'] )
		) {
			$key = array_search( 'wpforms', $options, true );
			if ( false !== $key ) {
				unset( $options[ $key ] );
			}
		}
	}

	if ( in_array( 'monsterinsights', $options, true ) ) {
		if (
			isset( $all_plugins['google-analytics-for-wordpress/googleanalytics.php'] ) ||
			isset( $all_plugins['google-analytics-premium/googleanalytics-premium.php'] )
		) {
			$key = array_search( 'monsterinsights', $options, true );
			if ( false !== $key ) {
				unset( $options[ $key ] );
			}
		}
	}

	// Install plugins.
	if ( ! empty( $options ) ) {
		foreach ( $options as $p ) {
			if ( ! empty( $paths_map[ $p ] ) ) {
				$plugin       = $paths_map[ $p ]['slug'];
				$download_url = $paths_map[ $p ]['url'];

				global $hook_suffix;

				// Set the current screen to avoid undefined notices.
				set_current_screen();

				// Prepare variables.
				$method = '';
				$url    = add_query_arg(
					array(
						'page' => 'seedprod_lite',
					),
					admin_url( 'admin.php' )
				);
				$url    = esc_url( $url );

				// Start output buffering to catch the filesystem form if credentials are needed.
				ob_start();
				$creds = request_filesystem_credentials( $url, $method, false, false, null );
				if ( false === $creds ) {
					wp_send_json_error();
				}

				// If we are not authenticated, make it happen now.
				if ( ! WP_Filesystem( $creds ) ) {
					request_filesystem_credentials( $url, $method, true, false, null );
					$form = ob_get_clean();
					return;
				}

				// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

				// Check for skin files.
				global $wp_version;
				if ( version_compare( $wp_version, '5.3.0' ) >= 0 ) {
					$skin_file = plugin_dir_path( __DIR__ ) . 'includes/skin53.php';
				} else {
					$skin_file = plugin_dir_path( __DIR__ ) . 'includes/skin.php';
				}

				if ( file_exists( $skin_file ) ) {
					require_once $skin_file;
				}

				// Create the plugin upgrader with our custom skin.
				ob_start();
				$installer = new Plugin_Upgrader( new SeedProd_Skin() );
				$installer->install( $download_url );
				$output = ob_get_clean();

				// Flush the cache and return the newly installed plugin basename.
				wp_cache_flush();
				if ( $installer->plugin_info() ) {
					$plugin_basename   = $installer->plugin_info();
					$install_plugins[] = $plugin_basename;
				}
			}
		}
	}

	// Activate plugins.
	foreach ( $install_plugins as $ip ) {
		activate_plugin( $ip, '', false, true );
	}

	wp_send_json_success( $install_plugins );
}

/**
 * Dismiss Setup Wizard (V2 Admin)
 * Sets option to prevent setup wizard from showing again
 * Called when user clicks "Exit Setup" on welcome page
 */
function seedprod_lite_v2_dismiss_setup_wizard() {
	// Verify nonce.
	check_ajax_referer( 'seedprod_v2_nonce' );

	// Check capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
	}

	// Set the dismiss option.
	update_option( 'seedprod_dismiss_setup_wizard', 1 );

	wp_send_json_success();
}
