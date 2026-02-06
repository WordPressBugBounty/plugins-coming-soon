<?php
// phpcs:ignore WordPress.Files.FileName -- Legacy filename retained for compatibility.
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for admin area
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin
 */
class SeedProd_Lite_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string $version
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Load admin function files.
		$this->load_admin_functions();

		// Register AJAX actions.
		$this->register_ajax_actions();

		// Add early redirect hook for subscribers tab in Lite.
		add_action( 'admin_init', array( $this, 'handle_subscribers_tab_redirect' ), 1 );

		// Add redirect for old dashboard URL to new dashboard.
		add_action( 'admin_init', array( $this, 'handle_old_dashboard_redirect' ), 1 );

		// Hook admin bar menu.
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );
		// Add inline CSS for admin bar (loads on all admin pages when modes are active).
		add_action( 'admin_head', array( $this, 'add_admin_bar_styles' ) );
		add_action( 'wp_head', array( $this, 'add_admin_bar_styles' ) );

		// Add inline CSS for menu badge (loads on all admin pages).
		add_action( 'admin_head', array( $this, 'add_menu_badge_styles' ) );

		// Fix menu highlighting for hidden pages.
		add_filter( 'parent_file', array( $this, 'fix_menu_highlighting' ) );
		add_filter( 'submenu_file', array( $this, 'fix_submenu_highlighting' ) );

		// Add JavaScript to fix menu highlighting for specific pages.
		add_action( 'admin_footer', array( $this, 'fix_menu_highlighting_js' ) );
	}

	/**
	 * Load all admin function files
	 */
	private function load_admin_functions() {
		$includes_dir = plugin_dir_path( __FILE__ ) . 'includes/';

		// Load UTM/link functions.
		require_once $includes_dir . 'utm-functions.php';

		// Load utility functions.
		require_once $includes_dir . 'utility-functions.php';

		// Load license functions.
		require_once $includes_dir . 'license-functions.php';

		// Load settings functions (V2 admin).
		require_once $includes_dir . 'settings-functions.php';

		// Load product education functions (for Lite version).
		require_once $includes_dir . 'product-education-functions.php';

		// Load plugin management functions (V2 admin).
		require_once $includes_dir . 'plugin-functions.php';

		// Load setup wizard functions (V2 admin).
		require_once $includes_dir . 'setup-wizard-functions.php';

		// Load system info functions (V2 admin).
		require_once $includes_dir . 'system-info-functions.php';


		// Load debug functions (V2 admin).
		require_once $includes_dir . 'debug-functions.php';

		// Load subscriber functions (V2 admin).
		require_once $includes_dir . 'subscriber-functions.php';

		// Load template functions (V2 admin).
		require_once $includes_dir . 'template-functions.php';

		// Load theme functions (V2 admin).
		require_once $includes_dir . 'theme-functions.php';

		// Load import/export functions (V2 admin).
		require_once $includes_dir . 'import-export-functions.php';

		// Load review request functions (V2 admin - Lite only).
		if ( 'lite' === SEEDPROD_BUILD ) {
			require_once $includes_dir . 'review-functions.php';
		}
	}

	/**
	 * Register AJAX actions for the admin area.
	 */
	private function register_ajax_actions() {
		// Track partner plugin activations for growth tool timing.
		add_action( 'activated_plugin', array( $this, 'track_partner_plugin_activation' ), 10, 2 );

		// License activation (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_save_api_key', array( $this, 'handle_license_activation' ) );

		// Settings save (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_save_settings', 'seedprod_lite_v2_save_settings' );

		// App settings save (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_save_app_settings', 'seedprod_lite_v2_save_app_settings' );

		// License deactivation (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_deactivate_api_key', 'seedprod_lite_v2_deactivate_api_key' );

		// Plugin management (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_install_plugin', 'seedprod_lite_v2_install_plugin' );
		add_action( 'wp_ajax_seedprod_lite_v2_activate_plugin', 'seedprod_lite_v2_activate_plugin' );
		add_action( 'wp_ajax_seedprod_lite_v2_deactivate_plugin', 'seedprod_lite_v2_deactivate_plugin' );
		add_action( 'wp_ajax_seedprod_lite_v2_get_plugins_list', 'seedprod_lite_v2_get_plugins_list' );

		// Setup wizard (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_complete_setup_wizard', 'seedprod_lite_v2_complete_setup_wizard' );
		add_action( 'wp_ajax_seedprod_lite_v2_install_addon_setup', 'seedprod_lite_v2_install_addon_setup' );
		add_action( 'wp_ajax_seedprod_lite_v2_check_wizard_availability', 'seedprod_lite_v2_check_wizard_availability' );
		add_action( 'wp_ajax_seedprod_lite_v2_dismiss_setup_wizard', 'seedprod_lite_v2_dismiss_setup_wizard' );

		// Subscribers (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_get_subscribers_datatable', 'seedprod_lite_v2_get_subscribers_datatable' );
		add_action( 'wp_ajax_seedprod_lite_v2_delete_subscribers', 'seedprod_lite_v2_delete_subscribers' );
		add_action( 'wp_ajax_seedprod_lite_v2_export_subscribers', 'seedprod_lite_v2_export_subscribers' );

		// Templates (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_get_templates', 'seedprod_lite_v2_get_templates' );
		add_action( 'wp_ajax_seedprod_lite_v2_get_favorite_templates', 'seedprod_lite_v2_get_favorite_templates' );
		add_action( 'wp_ajax_seedprod_lite_v2_toggle_favorite_template', 'seedprod_lite_v2_toggle_favorite_template' );
		add_action( 'wp_ajax_seedprod_lite_v2_get_saved_templates', 'seedprod_lite_v2_get_saved_templates' );
		add_action( 'wp_ajax_seedprod_lite_v2_create_page_from_template', 'seedprod_lite_v2_create_page_from_template' );
		add_action( 'wp_ajax_seedprod_lite_v2_subscribe_free_templates', 'seedprod_lite_v2_subscribe_free_templates' );

		// Theme functions (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_update_theme_enabled', 'seedprod_lite_v2_update_theme_enabled' );
		add_action( 'wp_ajax_seedprod_lite_v2_check_default_pages', 'seedprod_lite_v2_check_default_pages' );
		add_action( 'wp_ajax_seedprod_lite_v2_create_default_pages', 'seedprod_lite_v2_create_default_pages' );
		add_action( 'wp_ajax_seedprod_lite_v2_create_template', 'seedprod_lite_v2_create_template' );
		add_action( 'wp_ajax_seedprod_lite_v2_get_template_conditions', 'seedprod_lite_v2_get_template_conditions' );
		add_action( 'wp_ajax_seedprod_lite_v2_save_template_conditions', 'seedprod_lite_v2_save_template_conditions' );
		add_action( 'wp_ajax_seedprod_lite_v2_toggle_template_status', 'seedprod_lite_v2_toggle_template_status' );
		add_action( 'wp_ajax_seedprod_lite_v2_duplicate_template', 'seedprod_lite_v2_duplicate_template' );
		add_action( 'wp_ajax_seedprod_lite_v2_trash_template', 'seedprod_lite_v2_trash_template' );
		add_action( 'wp_ajax_seedprod_lite_v2_restore_template', 'seedprod_lite_v2_restore_template' );
		add_action( 'wp_ajax_seedprod_lite_v2_delete_template', 'seedprod_lite_v2_delete_template' );
		add_action( 'wp_ajax_seedprod_lite_v2_bulk_action_templates', 'seedprod_lite_v2_bulk_action_templates' );

		// Theme kits actions (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_get_theme_kits', 'seedprod_lite_v2_get_theme_kits' );
		add_action( 'wp_ajax_seedprod_lite_v2_toggle_favorite_theme', 'seedprod_lite_v2_toggle_favorite_theme' );

		// Theme import actions (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_import_theme_request', 'seedprod_lite_v2_import_theme_request' );
		add_action( 'wp_ajax_seedprod_lite_v2_delete_theme_pages', 'seedprod_lite_v2_delete_theme_pages' );
		add_action( 'wp_ajax_seedprod_lite_v2_get_total_theme_pages', 'seedprod_lite_v2_get_total_theme_pages' );

		// Theme Export/Import File actions (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_export_theme_files', 'seedprod_lite_v2_export_theme_files' );
		add_action( 'wp_ajax_seedprod_lite_v2_import_theme_files', 'seedprod_lite_v2_import_theme_files' );
		add_action( 'wp_ajax_seedprod_lite_v2_import_theme_by_url', 'seedprod_lite_v2_import_theme_by_url' );
		add_action( 'wp_ajax_seedprod_lite_v2_check_existing_theme', 'seedprod_lite_v2_check_existing_theme' );

		// Landing Pages Export/Import File actions (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_export_landing_pages', 'seedprod_lite_v2_export_landing_pages' );
		add_action( 'wp_ajax_seedprod_lite_v2_import_landing_pages', 'seedprod_lite_v2_import_landing_pages' );

		// Landing Pages CRUD actions (V2 - new admin system).
		add_action( 'wp_ajax_seedprod_lite_v2_duplicate_lpage', 'seedprod_lite_v2_duplicate_lpage' );
		add_action( 'wp_ajax_seedprod_lite_v2_trash_lpage', 'seedprod_lite_v2_trash_lpage' );
		add_action( 'wp_ajax_seedprod_lite_v2_restore_lpage', 'seedprod_lite_v2_restore_lpage' );
		add_action( 'wp_ajax_seedprod_lite_v2_delete_lpage', 'seedprod_lite_v2_delete_lpage' );
		add_action( 'wp_ajax_seedprod_lite_v2_bulk_action_lpages', 'seedprod_lite_v2_bulk_action_lpages' );
	}

	/**
	 * Handle redirect for subscribers tab in Lite version
	 * Must run early before any output is sent
	 */
	public function handle_subscribers_tab_redirect() {
		// Only run on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Check if we're on the settings page with subscribers tab.
		if ( isset( $_GET['page'] ) && 'seedprod_lite_settings' === $_GET['page'] &&
			isset( $_GET['tab'] ) && 'subscribers' === $_GET['tab'] ) {

			// Check if we're in Lite view.
			if ( seedprod_lite_v2_is_lite_view() ) {
				// Redirect to the hidden subscribers page which shows the education content.
				wp_safe_redirect( admin_url( 'admin.php?page=seedprod_lite_subscribers' ) );
				exit;
			}
		}
	}

	/**
	 * Handle redirect from old dashboard URL to new dashboard URL
	 * For backwards compatibility with bookmarks and saved links
	 */
	public function handle_old_dashboard_redirect() {
		// Only run on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Check if we're on the old dashboard page.
		if ( isset( $_GET['page'] ) && 'seedprod_lite_dashboard' === $_GET['page'] ) {
			// Redirect to the new dashboard URL.
			wp_safe_redirect( admin_url( 'admin.php?page=seedprod_lite' ) );
			exit;
		}
	}

	/**
	 * Redirect callback for old dashboard menu item
	 */
	public function redirect_old_dashboard() {
		// Redirect to the new dashboard URL.
		wp_safe_redirect( admin_url( 'admin.php?page=seedprod_lite' ) );
		exit;
	}

	/**
	 * Handle license activation AJAX request.
	 */
	public function handle_license_activation() {
		// Call the V2 function (already loaded in constructor).
		seedprod_lite_v2_save_api_key();
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		// Only load our CSS on our plugin pages.
		$screen = get_current_screen();
		if ( strpos( $screen->id, 'seedprod' ) !== false ) {
			// Ensure dashicons are loaded.
			wp_enqueue_style( 'dashicons' );

			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/admin.css',
				array( 'dashicons' ),
				$this->version,
				'all'
			);

			// COMMENTED OUT: Tailwind CSS - keeping existing admin.css for now
			// wp_enqueue_style(
			// $this->plugin_name . '-tailwind',
			// SEEDPROD_PLUGIN_URL . 'public/css/tailwind-admin.min.css',
			// array(),
			// $this->version,
			// 'all'
			// );

			// Load growth tools CSS on promotional pages.
			if ( strpos( $screen->id, 'seedprod_lite_popups' ) !== false ||
				strpos( $screen->id, 'seedprod_lite_custom_code' ) !== false ) {
				wp_enqueue_style(
					$this->plugin_name . '-growth-tools',
					plugin_dir_url( __FILE__ ) . 'css/admin-growth-tools.css',
					array(),
					$this->version,
					'all'
				);
			}
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		// Only load our JS on our plugin pages.
		$screen = get_current_screen();
		if ( strpos( $screen->id, 'seedprod' ) !== false ) {
			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/admin.js',
				array( 'jquery' ),
				$this->version,
				false
			);

			// Determine if we're in lite view.
			$is_lite_view = seedprod_lite_v2_is_lite_view();

			$theme_conditions = array();

			// Localize script with PHP data.
			wp_localize_script(
				$this->plugin_name,
				'seedprod_admin',
				array(
					'ajax_url'                   => admin_url( 'admin-ajax.php' ),
					'admin_url'                  => admin_url(),
					'nonce'                      => wp_create_nonce( 'seedprod_nonce' ),
					'v2_nonce'                   => wp_create_nonce( 'seedprod_v2_nonce' ),
					'notification_dismiss_nonce' => wp_create_nonce( 'seedprod_lite_notification_dismiss' ),
					'conditions'                 => $theme_conditions,
					'condition_modes'            => array(
						'include' => __( 'Include', 'coming-soon' ),
						'exclude' => __( 'Exclude', 'coming-soon' ),
						'custom'  => __( 'Custom', 'coming-soon' ),
					),
					'urls'                       => array(
						'ai_theme_builder' => 'https://ai.seedprod.com?utm_source=seedprod-plugin-proplugin&utm_medium=plugin-menu&utm_campaign=ai-themes-sidebar',
						'upgrade_lite'     => seedprod_lite_get_external_link(
							'https://www.seedprod.com/lite-upgrade/',
							'plugintemplatepage',
							'liteplugin'
						),
					),
					'strings'                    => array(
						// License messages.
						'license_empty'                 => __( 'Please enter a license key.', 'coming-soon' ),
						'license_success'               => __( 'License activated successfully! Refreshing...', 'coming-soon' ),
						'license_invalid'               => __( 'Invalid license key. Please try again.', 'coming-soon' ),
						'license_error'                 => __( 'An error occurred. Please try again.', 'coming-soon' ),

						// Toggle messages.
						'settings_error'                => __( 'Failed to update settings. Please try again.', 'coming-soon' ),
						'status_active'                 => __( 'ACTIVE', 'coming-soon' ),
						'status_inactive'               => __( 'INACTIVE', 'coming-soon' ),

						// Default pages prompt.
						'create_default_pages_prompt'   => __( 'Your site is currently set to show latest posts. Would you like to create static Home and Blog pages for use with the SeedProd theme?', 'coming-soon' ),

						// Plugin messages.
						'plugin_activate'               => __( 'Activate', 'coming-soon' ),
						'plugin_deactivate'             => __( 'Deactivate', 'coming-soon' ),
						'plugin_installed'              => __( 'Plugin installed successfully!', 'coming-soon' ),
						'plugin_activated'              => __( 'Plugin activated!', 'coming-soon' ),
						'plugin_deactivated'            => __( 'Plugin deactivated.', 'coming-soon' ),
						'plugin_error'                  => __( 'An error occurred. Please try again.', 'coming-soon' ),
						'plugin_network_error'          => __( 'Network error. Please check your connection and try again.', 'coming-soon' ),
						'plugin_install_timeout'        => __( 'Installation is taking longer than expected. Please try again or check your server logs.', 'coming-soon' ),
						'plugin_timeout'                => __( 'Operation timed out. Please try again.', 'coming-soon' ),
						'plugin_active'                 => __( 'Active', 'coming-soon' ),
						'plugin_inactive'               => __( 'Inactive', 'coming-soon' ),
						'installing'                    => __( 'Installing...', 'coming-soon' ),
						'activating'                    => __( 'Activating...', 'coming-soon' ),
						'deactivating'                  => __( 'Deactivating...', 'coming-soon' ),
						'dismiss_notice'                => __( 'Dismiss this notice.', 'coming-soon' ),

						// Copy button messages.
						'copy_to_clipboard'             => __( 'Copy to Clipboard', 'coming-soon' ),
						'copied'                        => __( 'Copied!', 'coming-soon' ),

						// Subscriber messages.
						'subscriber_delete_confirm'     => __( 'Are you sure you want to delete the selected subscribers?', 'coming-soon' ),
						'subscriber_delete_success'     => __( 'Subscribers deleted successfully.', 'coming-soon' ),
						'subscriber_export_success'     => __( 'Subscribers exported successfully.', 'coming-soon' ),
						'subscriber_no_selection'       => __( 'Please select at least one subscriber.', 'coming-soon' ),
						'subscriber_error'              => __( 'An error occurred while managing subscribers.', 'coming-soon' ),
						'loading'                       => __( 'Loading...', 'coming-soon' ),
						'search_placeholder'            => __( 'Search subscribers...', 'coming-soon' ),

						// Landing page messages.
						'page_name_required'            => __( 'Please enter a page name.', 'coming-soon' ),
						'active'                        => __( 'Active', 'coming-soon' ),
						'inactive'                      => __( 'Inactive', 'coming-soon' ),
						'pro_tip'                       => __( 'Pro Tip:', 'coming-soon' ),
						'landing_pages_upgrade_message' => __( 'Convert more visitors with 90+ Pro blocks plus MailChimp, ConvertKit integrations', 'coming-soon' ),
						'learn_more'                    => __( 'Learn more', 'coming-soon' ),
						'upgrade_to_pro'                => __( 'Upgrade to Pro', 'coming-soon' ),

						// Button states.
						'verifying'                     => __( 'Verifying...', 'coming-soon' ),
						'saving'                        => __( 'Saving...', 'coming-soon' ),
						'exporting'                     => __( 'Exporting...', 'coming-soon' ),
						'importing'                     => __( 'Importing...', 'coming-soon' ),
						'processing'                    => __( 'Processing...', 'coming-soon' ),
						'verify_key'                    => __( 'Verify Key', 'coming-soon' ),
						'deactivate_key'                => __( 'Deactivate Key', 'coming-soon' ),
						'save_settings'                 => __( 'Save Settings', 'coming-soon' ),
						'export_templates'              => __( 'Export All Templates', 'coming-soon' ),
						'import_templates'              => __( 'Import Templates', 'coming-soon' ),

						// License messages.
						'license_verify_success'        => __( 'License verified successfully!', 'coming-soon' ),
						'license_deactivate_confirm'    => __( 'Are you sure you want to deactivate your license?', 'coming-soon' ),
						'license_deactivating'          => __( 'Deactivating...', 'coming-soon' ),
						'license_deactivate_success'    => __( 'License deactivated successfully.', 'coming-soon' ),
						'license_deactivate_error'      => __( 'Could not deactivate license.', 'coming-soon' ),

						// Settings messages.
						'settings_save_success'         => __( 'Settings saved successfully!', 'coming-soon' ),
						'settings_save_error'           => __( 'Could not save settings.', 'coming-soon' ),

						// Template messages.
						'template_name_required'        => __( 'Template name is required', 'coming-soon' ),
						'template_type_required'        => __( 'Please select a template type', 'coming-soon' ),
						'template_create_success'       => __( 'Template created successfully', 'coming-soon' ),
						'template_create_error'         => __( 'Failed to create template', 'coming-soon' ),
						'template_import_success'       => __( 'Templates imported successfully!', 'coming-soon' ),
						'template_import_error'         => __( 'Import failed. Please check the file and try again.', 'coming-soon' ),
						'template_import_preparing'     => __( 'Preparing export file...', 'coming-soon' ),
						'template_import_completed'     => __( 'Export completed successfully!', 'coming-soon' ),
						'template_importing'            => __( 'Importing templates...', 'coming-soon' ),
						'template_load_error'           => __( 'Could not load templates. Please try again.', 'coming-soon' ),
						'template_network_error'        => __( 'Network error. Please check your connection and try again.', 'coming-soon' ),
						'template_no_favorites'         => __( 'No favorite templates found. Click the heart icon on any template to add it to your favorites.', 'coming-soon' ),
						'template_no_saved'             => __( 'No saved templates found. You can save pages as templates in the builder.', 'coming-soon' ),
						'template_no_found'             => __( 'No theme templates found.', 'coming-soon' ),

						// Conditions modal.
						'condition_select_type'         => __( 'Select Type', 'coming-soon' ),
						'condition_enter_value'         => __( 'Enter ids or slugs', 'coming-soon' ),

						// Theme messages.
						/* translators: %d is the number of existing theme pages */
						'theme_import_confirm'          => __( 'You have %d existing theme pages. Do you want to delete them before importing the new theme?', 'coming-soon' ),
						'theme_file_import_confirm'     => __( 'You have existing theme templates. Do you want to delete them before importing the new theme?', 'coming-soon' ),
						/* translators: %s is the theme name */
						'theme_import_starting'         => __( 'Importing theme "%s"... This may take a moment.', 'coming-soon' ),
						/* translators: %s is the theme name */
						'theme_import_success'          => __( 'Theme "%s" imported successfully!', 'coming-soon' ),
						/* translators: %s is the error message */
						'theme_import_error'            => __( 'Error importing theme: %s', 'coming-soon' ),
						/* translators: %s is the error message */
						'theme_delete_error'            => __( 'Error deleting theme pages: %s', 'coming-soon' ),
						'theme_delete_general_error'    => __( 'Error deleting theme pages.', 'coming-soon' ),

						// Confirmation messages.
						'confirm_duplicate'             => __( 'Are you sure you want to duplicate this template?', 'coming-soon' ),
						'confirm_trash'                 => __( 'Are you sure you want to trash this template?', 'coming-soon' ),
						'confirm_delete'                => __( 'Are you sure you want to permanently delete this template? This action cannot be undone.', 'coming-soon' ),
						'confirm_bulk_trash'            => __( 'Are you sure you want to trash the selected templates?', 'coming-soon' ),
						'confirm_bulk_restore'          => __( 'Are you sure you want to restore the selected templates?', 'coming-soon' ),
						'confirm_bulk_delete'           => __( 'Are you sure you want to permanently delete the selected templates?', 'coming-soon' ),
						'confirm_page_trash'            => __( 'Are you sure you want to move this page to trash?', 'coming-soon' ),
						'confirm_page_delete'           => __( 'Are you sure you want to permanently delete this page? This action cannot be undone.', 'coming-soon' ),

						// Form validation.
						'select_file_or_url'            => __( 'Please select a file or enter a URL.', 'coming-soon' ),
						'no_items_selected'             => __( 'Please select at least one template.', 'coming-soon' ),
						'no_pages_selected'             => __( 'Please select at least one page.', 'coming-soon' ),
						'template_name_required'        => __( 'Please enter a template name.', 'coming-soon' ),
						'page_data_missing'             => __( 'Page data is missing. Please try again.', 'coming-soon' ),
						'enter_value_placeholder'       => __( 'Enter value', 'coming-soon' ),
						'enter_condition_placeholder'   => __( 'Enter value (e.g., page ID, category name)', 'coming-soon' ),

						// Generic errors.
						'unknown_error'                 => __( 'Unknown error', 'coming-soon' ),
						'network_error'                 => __( 'Network error. Please try again.', 'coming-soon' ),
						'create_page_error'             => __( 'Could not create page from template. Please try again.', 'coming-soon' ),
						'save_conditions_error'         => __( 'Failed to save conditions', 'coming-soon' ),
						'duplicate_page_error'          => __( 'An error occurred while duplicating the page.', 'coming-soon' ),
						'trash_page_error'              => __( 'An error occurred while moving the page to trash.', 'coming-soon' ),
						'restore_page_error'            => __( 'An error occurred while restoring the page.', 'coming-soon' ),
						'delete_page_error'             => __( 'An error occurred while deleting the page.', 'coming-soon' ),
						'bulk_action_error'             => __( 'An error occurred while performing the bulk action.', 'coming-soon' ),
						'test_mode_notice'              => __( 'This is a test mode. To create a page, please use the "Create New Page" button from the Pages dashboard.', 'coming-soon' ),

						// Default names.
						'untitled_theme'                => __( 'Untitled Theme', 'coming-soon' ),
						'untitled_template'             => __( 'Untitled Template', 'coming-soon' ),
						'template_preview'              => __( 'Template Preview', 'coming-soon' ),

						// Additional error messages.
						'error_occurred'                => __( 'An error occurred. Please try again.', 'coming-soon' ),
						'error'                         => __( 'Error', 'coming-soon' ),
						'subscriber_load_error'         => __( 'Could not load subscribers.', 'coming-soon' ),
						'subscriber_none_found'         => __( 'No subscribers found.', 'coming-soon' ),
						'delete'                        => __( 'Delete', 'coming-soon' ),
						'loading_templates'             => __( 'Loading templates...', 'coming-soon' ),
						'preview_not_available'         => __( 'Preview Not Available', 'coming-soon' ),
						'unable_load_templates'         => __( 'Unable to Load Templates', 'coming-soon' ),
						'create_blank_template'         => __( 'You can still create a page with a blank template:', 'coming-soon' ),
						'start_blank_template'          => __( 'Start with Blank Template', 'coming-soon' ),
						'refresh_templates'             => __( 'or try refreshing the page to reload templates', 'coming-soon' ),
						'refresh_page'                  => __( 'Refresh Page', 'coming-soon' ),
						'saved_template_load_error'     => __( 'Could not load saved templates.', 'coming-soon' ),
						'no_favorites_found'            => __( 'No favorite templates found. Click the heart icon on any template to add it to your favorites.', 'coming-soon' ),
						'favorite_toggle_error'         => __( 'Could not toggle favorite', 'coming-soon' ),
						'favorite_network_error'        => __( 'Network error when toggling favorite', 'coming-soon' ),
						'email_subscribe_required'      => __( 'Please subscribe with your email above to unlock this FREE template.', 'coming-soon' ),
					),
				)
			);
		}
	}

	/**
	 * Register admin menu items - we'll add these gradually as we migrate from Vue
	 */
	public function add_plugin_admin_menu() {
		// Get notification count for menu badge.
		$notification = '';
		if ( class_exists( 'SeedProd_Notifications' ) ) {
			$n                   = SeedProd_Notifications::get_instance();
			$notifications_count = $n->get_count();

			// Check if notifications are disabled.
			$seedprod_app_settings = get_option( 'seedprod_app_settings' );
			if ( ! empty( $seedprod_app_settings ) ) {
				$seedprod_app_settings = json_decode( stripslashes( $seedprod_app_settings ) );
				if ( isset( $seedprod_app_settings->disable_seedprod_notification ) &&
					true === $seedprod_app_settings->disable_seedprod_notification ) {
					$notifications_count = 0;
				}
			}


			// Add notification badge if count > 0.
			if ( ! empty( $notifications_count ) ) {
				$notification = ' <span class="update-plugins count-' . $notifications_count . '"><span class="plugin-count">' . $notifications_count . '</span></span>';
			}
		}

		add_menu_page(
			'SeedProd',
			'SeedProd' . $notification,
			apply_filters( 'seedprod_main_menu_capability', 'edit_others_posts' ),
			'seedprod_lite',
			'seedprod_lite_dashboard_page',
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI1IiBoZWlnaHQ9IjEzMiIgdmlld0JveD0iMCAwIDEyNSAxMzIiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0wIDBDMCAwIDIuOTE2NjQgMC4xOTc4OTQgNjIuODIxMiA4LjAyNjgzQzEyMi43MjYgMTUuODU1OCAxNDMuNDU5IDc2LjYwNjQgMTA2Ljc4MSAxMjkuNjI4QzExMi40NTQgODIuMjUyNyAxMDIuMDcgMzMuMTA2MiA2MC4zNjA1IDI3LjM2MDZDMTguNjUwNSAyMS42MTUxIDIyLjI4MzQgMjIuNDk1NCAyMi4yODM0IDIyLjQ5NTRDMjIuMjgzNCAyMi40OTU0IDIyLjk3NDUgMzIuOTI5OSAyNi44ODgzIDYwLjk3OTlDMzAuODAyMSA4OS4wMjk5IDUyLjcwMzUgMTAyLjc4NiA3MS44NzA0IDEwOS44NjhDNzEuODcwNCAxMDkuODY4IDcyLjk5NDUgNzcuMDQwMSA2Mi4zMDA3IDYyLjU5MDlDNTEuNjA2OSA0OC4xNDE4IDM4LjMwMjYgMzguNTQ2IDM4LjMwMjYgMzguNTQ2QzM4LjMwMjYgMzguNTQ2IDY5LjU2OCA0Mi4yOTYgODEuMzcyMiA2NC4xMDE5QzkzLjE3NjQgODUuOTA3OCA5Mi4wMjY1IDEzMiA5Mi4wMjY1IDEzMkw3OS4yOTI1IDEzMS4zNDFDNDUuMDI4NCAxMjcuMjI1IDEzLjAxNzIgMTA2LjU5MSA3LjU3NDIzIDYzLjNDMi4xMzEzIDIwLjAwODggMCAwIDAgMFoiIGZpbGw9ImJsYWNrIi8+PHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0wIDBDMCAwIDIuOTE2NjQgMC4xOTc4OTQgNjIuODIxMiA4LjAyNjgzQzEyMi43MjYgMTUuODU1OCAxNDMuNDU5IDc2LjYwNjQgMTA2Ljc4MSAxMjkuNjI4QzExMi40NTQgODIuMjUyNyAxMDIuMDcgMzMuMTA2MiA2MC4zNjA1IDI3LjM2MDZDMTguNjUwNSAyMS42MTUxIDIyLjI4MzQgMjIuNDk1NCAyMi4yODM0IDIyLjQ5NTRDMjIuMjgzNCAyMi40OTU0IDIyLjk3NDUgMzIuOTI5OSAyNi44ODgzIDYwLjk3OTlDMzAuODAyMSA4OS4wMjk5IDUyLjcwMzUgMTAyLjc4NiA3MS44NzA0IDEwOS44NjhDNzEuODcwNCAxMDkuODY4IDcyLjk5NDUgNzcuMDQwMSA2Mi4zMDA3IDYyLjU5MDlDNTEuNjA2OSA0OC4xNDE4IDM4LjMwMjYgMzguNTQ2IDM4LjMwMjYgMzguNTQ2QzM4LjMwMjYgMzguNTQ2IDY5LjU2OCA0Mi4yOTYgODEuMzcyMiA2NC4xMDE5QzkzLjE3NjQgODUuOTA3OCA5Mi4wMjY1IDEzMiA5Mi4wMjY1IDEzMkw3OS4yOTI1IDEzMS4zNDFDNDUuMDI4NCAxMjcuMjI1IDEzLjAxNzIgMTA2LjU5MSA3LjU3NDIzIDYzLjNDMi4xMzEzIDIwLjAwODggMCAwIDAgMFoiIGZpbGw9IndoaXRlIi8+PC9zdmc+',
			apply_filters( 'seedprod_top_level_menu_postion', 58 )
		);

		// Add Dashboard as the first submenu item (overrides the default duplicate).
		// This makes the first submenu item say "Dashboard" instead of "SeedProd".
		add_submenu_page(
			'seedprod_lite',
			__( 'Dashboard', 'coming-soon' ),
			__( 'Dashboard', 'coming-soon' ),
			apply_filters( 'seedprod_dashboard_menu_capability', 'edit_others_posts' ),
			'seedprod_lite',
			array( $this, 'display_dashboard_page' )
		);

		// AI Themes - External link (handled via JavaScript) - Position #2 for maximum visibility.
		add_submenu_page(
			'seedprod_lite',
			__( 'AI Theme Builder', 'coming-soon' ),
			__( 'AI Theme Builder', 'coming-soon' ) . ' <span class="seedprod-menu-highlight">&nbsp;FREE</span>',
			apply_filters( 'seedprod_ai_themes_menu_capability', 'edit_others_posts' ),
			'seedprod_lite_ai_themes',
			'__return_false'
		);

		// Website Builder page (V2 WordPress-native).
		add_submenu_page(
			'seedprod_lite',
			__( 'Website Builder', 'coming-soon' ),
			__( 'Website Builder', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_website_builder',
			array( $this, 'display_website_builder_page' )
		);

		// Landing Pages page (V2 WordPress-native).
		add_submenu_page(
			'seedprod_lite',
			__( 'Landing Pages', 'coming-soon' ),
			__( 'Landing Pages', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_landing_pages',
			array( $this, 'display_landing_pages_page' )
		);

		// Settings page (V2 WordPress-native).
		add_submenu_page(
			'seedprod_lite',
			__( 'Settings', 'coming-soon' ),
			__( 'Settings', 'coming-soon' ),
			apply_filters( 'seedprod_settings_menu_capability', 'edit_others_posts' ),
			'seedprod_lite_settings',
			array( $this, 'display_settings_page' )
		);

		// Rotating promotional menu item.
		$rotating_item = $this->get_rotating_menu_item();
		if ( $rotating_item ) {
			add_submenu_page(
				'seedprod_lite',
				$rotating_item['page_title'],
				$rotating_item['menu_title'],
				'manage_options',
				$rotating_item['menu_slug'],
				array( $this, $rotating_item['callback'] )
			);
		}

		// Add "Upgrade to Pro" menu item for Lite version (or when testing with ?test_lite=1)
		$is_lite_view = seedprod_lite_v2_is_lite_view();
		if ( $is_lite_view ) {
			add_submenu_page(
				'seedprod_lite',
				__( 'Upgrade to Pro', 'coming-soon' ),
				'<span id="sp-lite-admin-menu__upgrade">' . __( 'Upgrade to Pro', 'coming-soon' ) . '</span>',
				apply_filters( 'seedprod_gopro_menu_capability', 'edit_others_posts' ),
				'seedprod_lite_get_pro',
				'__return_false'  // No page content, handled via redirect.
			);

			// Add green styling to upgrade menu item.
			add_action( 'admin_footer', array( $this, 'add_upgrade_menu_styling' ) );
		}

		// Hidden promotional pages for testing.
		// Using empty string '' instead of null to avoid PHP 8+ deprecation warnings.
		// This is the recommended approach for WordPress with PHP 8.1+ compatibility
		add_submenu_page(
			'options.php',  // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Popups', 'coming-soon' ),
			__( 'Popups', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_popups',
			array( $this, 'display_popups_page' )
		);

		add_submenu_page(
			'options.php',  // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Cookie Consent', 'coming-soon' ),
			__( 'Cookie Consent', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_cookie_consent',
			array( $this, 'display_cookie_consent_page' )
		);

		add_submenu_page(
			'options.php',  // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Custom Code', 'coming-soon' ),
			__( 'Custom Code', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_custom_code',
			array( $this, 'display_custom_code_page' )
		);

		// Debug Tools page (V2 WordPress-native) - Hidden menu.
		add_submenu_page(
			'options.php',  // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Debug Tools', 'coming-soon' ),
			__( 'Debug Tools', 'coming-soon' ),
			apply_filters( 'seedprod_debug_menu_capability', 'edit_others_posts' ),
			'seedprod_lite_debug',
			array( $this, 'display_debug_page' )
		);

		// Template Selection page (V2 WordPress-native) - Hidden menu.
		add_submenu_page(
			'options.php',  // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Template Selection', 'coming-soon' ),
			__( 'Template Selection', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_template_selection',
			array( $this, 'display_template_selection_page' )
		);

		// Theme Kits Selection page (V2 WordPress-native) - Hidden menu.
		add_submenu_page(
			'options.php',  // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Theme Kits Selection', 'coming-soon' ),
			__( 'Theme Kits Selection', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_theme_kits_selection',
			array( $this, 'display_theme_kits_selection_page' )
		);

		// Support page (V2 WordPress-native) - Hidden menu, accessed via help icon.
		add_submenu_page(
			'options.php',  // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Get Support', 'coming-soon' ),
			__( 'Get Support', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_support',
			array( $this, 'display_support_page' )
		);

		// Builder page (Vue.js builder) - Hidden menu, accessed via edit buttons
		add_submenu_page(
			'options.php',  // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Builder', 'coming-soon' ),
			__( 'Builder', 'coming-soon' ),
			apply_filters( 'seedprod_builder_menu_capability', 'edit_others_posts' ),
			'seedprod_lite_builder',
			'seedprod_lite_builder_page'
		);

		// Hidden welcome page (accessed via redirect on activation).
		add_submenu_page(
			'options.php', // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Welcome to SeedProd', 'coming-soon' ),
			__( 'Welcome', 'coming-soon' ),
			'manage_options',
			'seedprod_lite_welcome',
			array( $this, 'display_welcome_page' )
		);

		// Hidden debug page (V2 - accessed from settings).
		add_submenu_page(
			'', // Hidden from menu for now.
			__( 'Dashboard', 'coming-soon' ),
			__( 'Dashboard', 'coming-soon' ),
			apply_filters( 'seedprod_main_menu_capability', 'edit_others_posts' ),
			'seedprod_lite_dashboard',
			array( $this, 'redirect_old_dashboard' )
		);

		// Hidden subscribers page (V2 - shows product education for Lite).
		add_submenu_page(
			'options.php', // Use non-existent parent to hide from menu (avoids null deprecation).
			__( 'Subscribers', 'coming-soon' ),
			__( 'Subscribers', 'coming-soon' ),
			apply_filters( 'seedprod_subscribers_menu_capability', 'edit_others_posts' ),
			'seedprod_lite_subscribers',
			array( $this, 'display_subscribers_page' )
		);
	}

	/**
	 * Render the welcome page
	 */
	public function display_welcome_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-welcome.php';
	}

	/**
	 * Render the dashboard page
	 */
	public function display_dashboard_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-dashboard.php';
	}

	/**
	 * Render the landing pages page
	 */
	public function display_landing_pages_page() {
		// Enqueue admin JavaScript.
		wp_enqueue_script( 'seedprod-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery', 'wp-util', 'updates' ), $this->version, true );

		// Localize script with landing page strings.
		wp_localize_script(
			'seedprod-admin',
			'seedprod_admin',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'admin_url' => admin_url(),
				'nonce'     => wp_create_nonce( 'seedprod_nonce' ),
				'v2_nonce'  => wp_create_nonce( 'seedprod_v2_nonce' ),
				'strings'   => array(
					// Export/Import messages.
					'loading'                       => __( 'Processing...', 'coming-soon' ),
					'export_all_landing_pages'      => __( 'Export All Landing Pages', 'coming-soon' ),
					'export_selected_page'          => __( 'Export Selected Page', 'coming-soon' ),
					'export_completed'              => __( 'Export completed successfully!', 'coming-soon' ),
					'preparing_export'              => __( 'Preparing export file...', 'coming-soon' ),
					// Import messages.
					'import_success'                => __( 'Import completed successfully!', 'coming-soon' ),
					'uploading_file'                => __( 'Uploading file...', 'coming-soon' ),
					'processing_import'             => __( 'Processing import...', 'coming-soon' ),
					// Error messages.
					'error_occurred'                => __( 'An error occurred. Please try again.', 'coming-soon' ),
					'invalid_file_type'             => __( 'Please select a valid ZIP file.', 'coming-soon' ),
					// Upgrade/Lite view messages.
					'pro_tip'                       => __( 'Pro Tip:', 'coming-soon' ),
					'landing_pages_upgrade_message' => __( 'Convert more visitors with 90+ Pro blocks plus MailChimp, ConvertKit integrations', 'coming-soon' ),
					'learn_more'                    => __( 'Learn more', 'coming-soon' ),
					'upgrade_to_pro'                => __( 'Upgrade to Pro', 'coming-soon' ),
				),
			)
		);

		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-landing-pages.php';
	}

	/**
	 * Render the website builder page
	 */
	public function display_website_builder_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-website-builder.php';
	}

	/**
	 * Render the settings page
	 */
	public function display_settings_page() {
		// In Lite build, redirect Subscribers tab to the standalone education page.
		if ( 'lite' === SEEDPROD_BUILD ) {
			$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
			if ( 'subscribers' === $current_tab ) {
				wp_safe_redirect( admin_url( 'admin.php?page=seedprod_lite_subscribers' ) );
				exit;
			}
		}

		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-settings.php';
	}

	/**
	 * Render the template selection page
	 */
	public function display_template_selection_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-template-selection.php';
	}

	/**
	 * Render the theme kits selection page
	 */
	public function display_theme_kits_selection_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-theme-kits-selection.php';
	}

	/**
	 * Render the subscribers page
	 */
	public function display_subscribers_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-subscribers.php';
	}

	/**
	 * Render the debug page
	 */
	public function display_debug_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-debug.php';
	}

	/**
	 * Fix menu highlighting for hidden pages
	 *
	 * @param string $parent_file The parent file.
	 * @return string Modified parent file
	 */
	public function fix_menu_highlighting( $parent_file ) {
		global $plugin_page, $submenu_file;

		// Check if we're on one of our hidden pages.
		if ( isset( $plugin_page ) ) {
			switch ( $plugin_page ) {
				case 'seedprod_lite_theme_kits_selection':
					// Highlight Website Builder menu item and expand menu.
					$parent_file  = 'seedprod_lite';
					$submenu_file = 'seedprod_lite_website_builder';
					break;
				case 'seedprod_lite_template_selection':
					// Highlight Landing Pages menu item and expand menu.
					$parent_file  = 'seedprod_lite';
					$submenu_file = 'seedprod_lite_landing_pages';
					break;
				case 'seedprod_lite_subscribers':
					// Highlight Settings menu item and expand menu.
					$parent_file  = 'seedprod_lite';
					$submenu_file = 'seedprod_lite_settings';
					break;
			}
		}

		return $parent_file;
	}

	/**
	 * Fix submenu highlighting for hidden pages
	 *
	 * @param string $submenu_file The submenu file.
	 * @return string Modified submenu file
	 */
	public function fix_submenu_highlighting( $submenu_file ) {
		global $plugin_page;

		// Check if we're on one of our hidden pages.
		if ( isset( $plugin_page ) ) {
			switch ( $plugin_page ) {
				case 'seedprod_lite_theme_kits_selection':
					$submenu_file = 'seedprod_lite_website_builder';
					break;
				case 'seedprod_lite_template_selection':
					$submenu_file = 'seedprod_lite_landing_pages';
					break;
				case 'seedprod_lite_subscribers':
					$submenu_file = 'seedprod_lite_settings';
					break;
			}
		}

		return $submenu_file;
	}

	/**
	 * Add JavaScript to properly highlight menu for hidden pages
	 */
	public function fix_menu_highlighting_js() {
		global $plugin_page;

		// Only run on our hidden pages.
		if ( ! isset( $plugin_page ) || 'seedprod_lite_subscribers' !== $plugin_page ) {
			return;
		}

		?>
		<script>
		jQuery(document).ready(function($) {
			// Remove current class from all menu items.
			$('#adminmenu .current').removeClass('current');
			$('#adminmenu .wp-has-current-submenu').removeClass('wp-has-current-submenu').addClass('wp-not-current-submenu');
			$('#adminmenu .wp-menu-open').removeClass('wp-menu-open');
			
			// Add current class to SeedProd menu.
			$('#toplevel_page_seedprod_lite').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
			
			// Add current class to Settings submenu.
			$('#toplevel_page_seedprod_lite .wp-submenu a[href*="seedprod_lite_settings"]').parent().addClass('current');
		});
		</script>
		<?php
	}

	/**
	 * Add admin bar menu item when Coming Soon or Maintenance Mode is active
	 * V2 implementation with inline styles
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The WordPress admin bar object.
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		// Get settings.
		$settings_json = get_option( 'seedprod_settings' );
		$settings      = json_decode( $settings_json, true );

		// Check if we should show the admin bar menu.
		$coming_soon_active = ! empty( $settings['enable_coming_soon_mode'] );
		$maintenance_active = ! empty( $settings['enable_maintenance_mode'] );

		// Get theme preview mode.
		$theme_preview_mode = get_option( 'seedprod_theme_template_preview_mode' );

		// Only show if one of the modes is active.
		if ( ! $coming_soon_active && ! $maintenance_active && empty( $theme_preview_mode ) ) {
			return;
		}

		// Don't show if page builder is open.
		$pl_edit = isset( $_GET['pl_edit'] ) ? sanitize_text_field( wp_unslash( $_GET['pl_edit'] ) ) : null;
		if ( $pl_edit ) {
			return;
		}

		// SVG icon.
		$icon = '<span class="seedprod-mb-icon"><svg width="22" height="23" viewBox="0 0 22 23" fill="none" xmlns="http://www.w3.org/2000/svg">
			<g filter="url(#filter0_d)">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M4 0C4 0 4.32666 0.022488 11.036 0.91214C17.7453 1.80179 20.0674 8.70527 15.9594 14.7304C16.5949 9.34689 15.4319 3.76206 10.7604 3.10916C6.08886 2.45626 6.49574 2.5563 6.49574 2.5563C6.49574 2.5563 6.57314 3.74204 7.01149 6.92954C7.44984 10.117 9.90279 11.6803 12.0495 12.485C12.0495 12.485 12.1754 8.75455 10.9777 7.1126C9.77997 5.47066 8.2899 4.38023 8.2899 4.38023C8.2899 4.38023 11.7916 4.80636 13.1137 7.28431C14.4358 9.76225 14.307 15 14.307 15L12.8808 14.9251C9.04318 14.4574 5.45792 12.1126 4.84831 7.19318C4.23871 2.27373 4 0 4 0Z" fill="black"/>
			<path fill-rule="evenodd" clip-rule="evenodd" d="M4 0C4 0 4.32666 0.022488 11.036 0.91214C17.7453 1.80179 20.0674 8.70527 15.9594 14.7304C16.5949 9.34689 15.4319 3.76206 10.7604 3.10916C6.08886 2.45626 6.49574 2.5563 6.49574 2.5563C6.49574 2.5563 6.57314 3.74204 7.01149 6.92954C7.44984 10.117 9.90279 11.6803 12.0495 12.485C12.0495 12.485 12.1754 8.75455 10.9777 7.1126C9.77997 5.47066 8.2899 4.38023 8.2899 4.38023C8.2899 4.38023 11.7916 4.80636 13.1137 7.28431C14.4358 9.76225 14.307 15 14.307 15L12.8808 14.9251C9.04318 14.4574 5.45792 12.1126 4.84831 7.19318C4.23871 2.27373 4 0 4 0Z" fill="white"/>
			</g>
			<defs>
			<filter id="filter0_d" x="0" y="0" width="22" height="23" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
			<feFlood flood-opacity="0" result="BackgroundImageFix"/>
			<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/>
			<feOffset dy="4"/>
			<feGaussianBlur stdDeviation="2"/>
			<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0"/>
			<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow"/>
			<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow" result="shape"/>
			</filter>
			</defs>
			</svg></span>';

		// Determine text based on active mode.
		$text = '<span>SeedProd</span>';
		if ( $coming_soon_active ) {
			$text = '<span>' . __( 'Coming Soon Mode Active', 'coming-soon' ) . '</span>';
		} elseif ( $maintenance_active ) {
			$text = '<span>' . __( 'Maintenance Mode Active', 'coming-soon' ) . '</span>';
		} elseif ( ! empty( $theme_preview_mode ) ) {
			$text = '<span>' . __( 'Theme Preview Mode Active', 'coming-soon' ) . '</span>';
		}

		// Add the admin bar menu item.
		$wp_admin_bar->add_menu(
			array(
				'id'     => 'seedprod_admin_bar',
				'href'   => admin_url( 'admin.php?page=seedprod_lite' ), // Link to main admin page.
				'parent' => 'top-secondary',
				'title'  => $icon . $text,
				'meta'   => array( 'class' => 'seedprod-mode-active' ),
			)
		);
	}

	/**
	 * Add inline styles for admin bar notice
	 * Only loads when Coming Soon or Maintenance Mode is active
	 */
	public function add_admin_bar_styles() {
		// Get settings.
		$settings_json = get_option( 'seedprod_settings' );
		$settings      = json_decode( $settings_json, true );

		// Check if any mode is active.
		$coming_soon_active = ! empty( $settings['enable_coming_soon_mode'] );
		$maintenance_active = ! empty( $settings['enable_maintenance_mode'] );
		$theme_preview_mode = get_option( 'seedprod_theme_template_preview_mode' );

		// Only add styles if a mode is active.
		if ( ! $coming_soon_active && ! $maintenance_active && empty( $theme_preview_mode ) ) {
			return;
		}

		// Check if admin bar is showing.
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		?>
		<style type="text/css">
			/* SeedProd V2 Admin Bar Styles */
			.seedprod-mode-active a {
				background: #dd4a1f !important;
				color: #fff !important;
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
			}
			.seedprod-mode-active a:hover {
				background-color: #f15d32 !important;
			}
			.seedprod-mb-icon {
				margin-top: 17px !important;
				margin-right: 5px !important;
				height: 34px !important;
			}
			.seedprod-mode-active .ab-sub-wrapper a {
				justify-content: left !important;
			}
		</style>
		<?php
	}

	/**
	 * Add menu highlight styles
	 * Loads on all admin pages to style menu highlights (FREE, NEW, etc.)
	 */
	public function add_menu_badge_styles() {
		?>
		<style type="text/css">
			/* SeedProd Menu Highlight - Generic class for FREE, NEW, etc. */
			#adminmenu .seedprod-menu-highlight {
				display: inline-block !important;
				color: #f18500 !important;
				vertical-align: super !important;
				font-size: 9px !important;
				font-weight: 600 !important;
				padding-inline-start: 2px !important;
			}
		</style>
		<?php
	}

	/**
	 * Track when partner plugins are activated
	 * This is used to implement delays between growth tool suggestions
	 *
	 * @since 1.0.0
	 * @param string $plugin Plugin file path.
	 * @param bool   $network_wide Whether the plugin is being activated network-wide.
	 */
	public function track_partner_plugin_activation( $plugin, $network_wide ) {
		// Track OptinMonster activation.
		if ( 'optinmonster/optin-monster-wp-api.php' === $plugin ) {
			update_option( 'seedprod_partner_optinmonster_activated', current_time( 'timestamp' ) );
		}

		// Track WPCode activation.
		if ( 'insert-headers-and-footers/ihaf.php' === $plugin || 'wpcode-premium/wpcode.php' === $plugin ) {
			update_option( 'seedprod_partner_wpcode_activated', current_time( 'timestamp' ) );
		}
	}

	/**
	 * Get the rotating menu item to display
	 * Uses a simple priority-based approach:
	 * 1. Show OptinMonster if not installed/active
	 * 2. Show WP Code if OptinMonster is installed but WP Code is not (with optional delay)
	 * 3. Show nothing if both are installed
	 *
	 * @since 1.0.0
	 * @return array|null
	 */
	private function get_rotating_menu_item() {
		// Configuration: Set to true to enable 7-day delay between growth tool suggestions.
		$enable_growth_tool_delay = true; // Set to false to show growth tools immediately.
		$delay_days               = 7; // Number of days to wait before showing next tool.

		// Load plugin functions if needed.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Priority 1: OptinMonster.
		$optinmonster_installed = is_plugin_active( 'optinmonster/optin-monster-wp-api.php' );

		if ( ! $optinmonster_installed ) {
			return array(
				'menu_title' => __( 'Popups', 'coming-soon' ),
				'page_title' => __( 'Popups by OptinMonster', 'coming-soon' ),
				'menu_slug'  => 'seedprod_lite_popups',
				'callback'   => 'display_popups_page',
			);
		}

		// Check if we should wait before showing the next growth tool.
		if ( $enable_growth_tool_delay && $optinmonster_installed ) {
			// Get the activation date for OptinMonster.
			$activation_date = get_option( 'seedprod_partner_optinmonster_activated', false );

			// If no activation date stored, set it now (plugin was already active).
			if ( ! $activation_date ) {
				update_option( 'seedprod_partner_optinmonster_activated', current_time( 'timestamp' ) );
				$activation_date = current_time( 'timestamp' );
			}

			// Check if enough time has passed.
			$days_since_activation = ( current_time( 'timestamp' ) - $activation_date ) / DAY_IN_SECONDS;

			if ( $days_since_activation < $delay_days ) {
				// Not enough time has passed, don't show next growth tool.
				return null;
			}
		}

		// Priority 2: WPConsent (Cookie Consent).
		$wpconsent_installed = is_plugin_active( 'wpconsent-cookies-banner-privacy-suite/wpconsent.php' );

		if ( ! $wpconsent_installed ) {
			// Check if we should wait before showing WPConsent.
			if ( $enable_growth_tool_delay && $optinmonster_installed ) {
				// Get the activation date for OptinMonster.
				$activation_date = get_option( 'seedprod_partner_optinmonster_activated', false );

				// If no activation date stored, set it now.
				if ( ! $activation_date ) {
					update_option( 'seedprod_partner_optinmonster_activated', current_time( 'timestamp' ) );
					$activation_date = current_time( 'timestamp' );
				}

				// Check if enough time has passed.
				$days_since_activation = ( current_time( 'timestamp' ) - $activation_date ) / DAY_IN_SECONDS;

				if ( $days_since_activation < $delay_days ) {
					// Not enough time has passed, don't show next growth tool.
					return null;
				}
			}

			// WPConsent not installed - promote it.
			return array(
				'menu_title' => __( 'Cookie Consent', 'coming-soon' ),
				'page_title' => __( 'Cookie Consent & Privacy Compliance', 'coming-soon' ),
				'menu_slug'  => 'seedprod_lite_cookie_consent',
				'callback'   => 'display_cookie_consent_page',
			);
		}

		// Priority 3: WP Code (check both free and pro versions).
		$wpcode_free_installed = is_plugin_active( 'insert-headers-and-footers/ihaf.php' );
		$wpcode_pro_installed  = is_plugin_active( 'wpcode-premium/wpcode.php' );

		if ( ! $wpcode_free_installed && ! $wpcode_pro_installed ) {
			// Check if we should wait before showing WPCode.
			if ( $enable_growth_tool_delay && $wpconsent_installed ) {
				// Get the activation date for WPConsent.
				$activation_date = get_option( 'seedprod_partner_wpconsent_activated', false );

				// If no activation date stored, set it now.
				if ( ! $activation_date ) {
					update_option( 'seedprod_partner_wpconsent_activated', current_time( 'timestamp' ) );
					$activation_date = current_time( 'timestamp' );
				}

				// Check if enough time has passed.
				$days_since_activation = ( current_time( 'timestamp' ) - $activation_date ) / DAY_IN_SECONDS;

				if ( $days_since_activation < $delay_days ) {
					// Not enough time has passed, don't show next growth tool.
					return null;
				}
			}

			// WP Code not installed at all - promote free version.
			return array(
				'menu_title' => __( 'Custom Code', 'coming-soon' ),
				'page_title' => __( 'Custom Code - Insert Headers, Footers, and Code Snippets', 'coming-soon' ),
				'menu_slug'  => 'seedprod_lite_custom_code',
				'callback'   => 'display_custom_code_page',
			);
		}

		// All three (OptinMonster, WPConsent, and WP Code) are installed, don't show anything.
		return null;
	}

	/**
	 * Display the Popups promotional page
	 *
	 * @since 1.0.0
	 */
	public function display_popups_page() {
		// Enqueue growth tools CSS.
		wp_enqueue_style( 'seedprod-admin-growth-tools', plugin_dir_url( __FILE__ ) . 'css/admin-growth-tools.css', array(), $this->version );

		// Enqueue admin JS for plugin installer.
		wp_enqueue_script( 'seedprod-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery', 'wp-util', 'updates' ), $this->version, true );

		// Localize script for AJAX - including all required strings.
		wp_localize_script(
			'seedprod-admin',
			'seedprod_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'seedprod_nonce' ),
				'strings'  => array(
					// Template loading messages.
					'unable_load_templates'   => __( 'Unable to Load Templates', 'coming-soon' ),
					'loading_templates'       => __( 'Loading templates...', 'coming-soon' ),
					'error_loading_templates' => __( 'Error loading templates', 'coming-soon' ),
					'no_templates_found'      => __( 'No templates found', 'coming-soon' ),
					'network_error'           => __( 'Network error. Please check your connection and try again.', 'coming-soon' ),
				),
			)
		);

		// Configure OptinMonster promotional content.
		$growth_tool_config = array(
			'partner_name' => __( 'OptinMonster', 'coming-soon' ),
			'headline'     => __( 'Stop Losing 95% of Your Visitors Without Their Email', 'coming-soon' ),
			'subheadline'  => __( 'Add Smart Popups to Your SeedProd Pages in Under 60 Seconds', 'coming-soon' ),
			'benefits'     => array(
				__( 'Exit Intent™ technology captures visitors before they leave', 'coming-soon' ),
				__( '700+ proven templates ready to customize', 'coming-soon' ),
				__( 'Target by behavior, location, and page visits', 'coming-soon' ),
				__( 'A/B test popups to maximize conversions', 'coming-soon' ),
				__( 'Works with MailChimp, ConvertKit, ActiveCampaign & more', 'coming-soon' ),
			),
			'cta_headline' => __( '⏰ Every Minute You Wait = More Lost Subscribers', 'coming-soon' ),
			'cta_subtext'  => __( 'Free version includes 3 campaigns • 60-second setup', 'coming-soon' ),
			'social_proof' => __( 'Join 1,213,437+ websites already using OptinMonster', 'coming-soon' ),
			'image'        => 'om-image.png',
			'plugin_slug'  => 'optinmonster/optin-monster-wp-api.php',
			'plugin_id'    => 'optinmonster',
		);

		?>
		<div class="seedprod-dashboard-page">
			<?php
			$page_title = __( 'Popups', 'coming-soon' );
			require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-header.php';
			?>
			<div class="seedprod-dashboard-container">
				<?php require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-growth-tool.php'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the Cookie Consent promotional page
	 *
	 * @since 1.0.0
	 */
	public function display_cookie_consent_page() {
		// Enqueue growth tools CSS.
		wp_enqueue_style( 'seedprod-admin-growth-tools', plugin_dir_url( __FILE__ ) . 'css/admin-growth-tools.css', array(), $this->version );

		// Enqueue admin JS for plugin installer.
		wp_enqueue_script( 'seedprod-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery', 'wp-util', 'updates' ), $this->version, true );

		// Localize script for AJAX - including all required strings.
		wp_localize_script(
			'seedprod-admin',
			'seedprod_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'seedprod_nonce' ),
				'strings'  => array(
					// Template loading messages.
					'unable_load_templates'   => __( 'Unable to Load Templates', 'coming-soon' ),
					'loading_templates'       => __( 'Loading templates...', 'coming-soon' ),
					'error_loading_templates' => __( 'Error loading templates', 'coming-soon' ),
					'no_templates_found'      => __( 'No templates found', 'coming-soon' ),
					'network_error'           => __( 'Network error. Please check your connection and try again.', 'coming-soon' ),
				),
			)
		);

		// Configure WPConsent promotional content.
		$growth_tool_config = array(
			'partner_name' => __( 'WPConsent', 'coming-soon' ),
			'headline'     => __( 'Make Your Site Privacy Compliant in Minutes', 'coming-soon' ),
			'subheadline'  => __( 'Add Professional Cookie Consent Banners Without Coding', 'coming-soon' ),
			'benefits'     => array(
				__( 'GDPR & CCPA compliant cookie consent management', 'coming-soon' ),
				__( 'Automatic website cookie scanning and script blocking', 'coming-soon' ),
				__( 'Customizable banners that match your brand perfectly', 'coming-soon' ),
				__( 'Searchable consent records with user management', 'coming-soon' ),
				__( 'Self-hosted solution with geolocation detection', 'coming-soon' ),
			),
			'cta_headline' => __( '⚖️ Protect Your Business from Privacy Violations', 'coming-soon' ),
			'cta_subtext'  => __( 'Quick setup • No coding required • 14-day money-back guarantee', 'coming-soon' ),
			'social_proof' => __( 'Join thousands of WordPress sites achieving privacy compliance', 'coming-soon' ),
			'image'        => 'wpconsent-image.svg',
			'plugin_slug'  => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
			'plugin_id'    => 'wpconsent',
		);

		// Display the promotional page.
		?>
		<div class="seedprod-dashboard-page">
			<?php
			$page_title = __( 'Cookie Consent', 'coming-soon' );
			require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-header.php';
			?>
			<div class="seedprod-dashboard-container">
				<?php require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-growth-tool.php'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the Custom Code promotional page
	 *
	 * @since 1.0.0
	 */
	public function display_custom_code_page() {
		// Enqueue growth tools CSS.
		wp_enqueue_style( 'seedprod-admin-growth-tools', plugin_dir_url( __FILE__ ) . 'css/admin-growth-tools.css', array(), $this->version );

		// Enqueue admin JS for plugin installer.
		wp_enqueue_script( 'seedprod-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery', 'wp-util', 'updates' ), $this->version, true );

		// Localize script for AJAX - including all required strings.
		wp_localize_script(
			'seedprod-admin',
			'seedprod_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'seedprod_nonce' ),
				'strings'  => array(
					// Template loading messages.
					'unable_load_templates'   => __( 'Unable to Load Templates', 'coming-soon' ),
					'loading_templates'       => __( 'Loading templates...', 'coming-soon' ),
					'error_loading_templates' => __( 'Error loading templates', 'coming-soon' ),
					'no_templates_found'      => __( 'No templates found', 'coming-soon' ),
					'network_error'           => __( 'Network error. Please check your connection and try again.', 'coming-soon' ),
				),
			)
		);

		// Configure WPCode promotional content.
		$growth_tool_config = array(
			'partner_name' => __( 'WPCode', 'coming-soon' ),
			'headline'     => __( 'Add Custom Code Without Breaking Your Site', 'coming-soon' ),
			'subheadline'  => __( 'Safely Add Headers, Footers & Scripts That Work Perfectly with SeedProd', 'coming-soon' ),
			'benefits'     => array(
				__( 'Replace 6-8 plugins on average with lightweight code snippets', 'coming-soon' ),
				__( 'Add Google Analytics, Facebook Pixel & tracking scripts safely', 'coming-soon' ),
				__( '100+ expert-approved snippets ready to use instantly', 'coming-soon' ),
				__( 'Automatic error detection prevents site crashes', 'coming-soon' ),
				__( 'Your code survives theme updates and changes', 'coming-soon' ),
			),
			'cta_headline' => __( '⏰ Start Adding Custom Code Safely in Seconds', 'coming-soon' ),
			'cta_subtext'  => __( '100% free • Installs in seconds', 'coming-soon' ),
			'social_proof' => __( 'Join 2,000,000+ websites already using WPCode', 'coming-soon' ),
			'image'        => 'wp-code-image.png',
			'plugin_slug'  => 'insert-headers-and-footers/ihaf.php',
			'plugin_id'    => 'wpcode',
		);

		?>
		<div class="seedprod-dashboard-page">
			<?php
			$page_title = __( 'Custom Code', 'coming-soon' );
			require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-header.php';
			?>
			<div class="seedprod-dashboard-container">
				<?php require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-growth-tool.php'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Website Builder product education page (for Lite users)
	 */
	public function render_website_builder_education() {
		// Enqueue admin styles.
		wp_enqueue_style( 'seedprod-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version );

		// Configure Website Builder educational content.
		$product_education_config = array(
			'feature_name'         => __( 'Website Builder', 'coming-soon' ),
			'headline'             => __( 'Get a Full Website With One-Click Without Being a Developer', 'coming-soon' ),
			'subheadline'          => __( 'Stop losing customers to DIY-looking websites. Get a conversion-optimized site that looks like you paid $10k for it.', 'coming-soon' ),
			'description'          => __( 'Transform your entire WordPress site into a pixel-perfect, professionally designed website in minutes. No coding skills required.', 'coming-soon' ),
			'benefits'             => array(
				__( 'Build headers, footers, pages, posts & sidebars - control your entire theme', 'coming-soon' ),
				__( '350+ professionally designed templates ready to import instantly', 'coming-soon' ),
				__( 'AI Builder generates complete custom themes in seconds', 'coming-soon' ),
				__( 'WooCommerce ready - design product pages, shop & checkout', 'coming-soon' ),
				__( 'Lightning-fast imports & bloat-free code for better conversions', 'coming-soon' ),
				__( 'No coding required - 100% visual drag and drop builder', 'coming-soon' ),
			),
			'features'             => array(
				array(
					'icon'        => 'admin-appearance',
					'title'       => __( 'Theme Builder', 'coming-soon' ),
					'description' => __( 'Design every part of your WordPress theme visually including headers, footers, sidebars, and content areas.', 'coming-soon' ),
				),
				array(
					'icon'        => 'layout',
					'title'       => __( 'Dynamic Content', 'coming-soon' ),
					'description' => __( 'Create templates for posts, pages, categories, tags, and custom post types that automatically update.', 'coming-soon' ),
				),
				array(
					'icon'        => 'cart',
					'title'       => __( 'WooCommerce Builder', 'coming-soon' ),
					'description' => __( 'Design custom product pages, shop layouts, cart, and checkout pages that convert.', 'coming-soon' ),
				),
				array(
					'icon'        => 'smartphone',
					'title'       => __( 'Responsive Controls', 'coming-soon' ),
					'description' => __( 'Perfect control over how your site looks on desktop, tablet, and mobile devices.', 'coming-soon' ),
				),
			),
			'image'                => 'pe/pe-website-builder.png',
			'image_link'           => seedprod_lite_get_external_link(
				'https://www.seedprod.com/templates/',
				'website-builder-education-image',
				'liteplugin'
			),
			'testimonial'          => array(
				'text'    => 'This plugin is simple to understand and use. It doesn\'t get in the way of making the page look like I want to. If you know even just a little about HTML and CSS, all the better but it\'s not vital.',
				'author'  => 'Glenn Watson',
				'company' => 'WordPress.org User',
			),
			'cta_headline'         => __( '🚀 Ready to Build Your Dream Website?', 'coming-soon' ),
			// 'primary_button' => array(
			// 'text' => __( 'Get Instant Access to Website Builder', 'coming-soon' ),
			// ),
			'secondary_button'     => array(
				'text'    => __( 'Try Free: AI Builds Your Site in 60 Seconds', 'coming-soon' ),
				'url'     => 'https://ai.seedprod.com?utm_source=seedprod-plugin-proplugin&utm_medium=themebuilderpage-education&utm_campaign=ai-builder-secondary-cta',
				'icon'    => 'dashicons-admin-customizer',
				'new_tab' => true,
			),
			'feature_slug'         => 'website-builder',
			'cta_context'          => 'themebuilderpage-education', // UTM medium to match old Vue tracking.
			'use_exact_utm_medium' => true, // Skip 'plugin' prefix for legacy tracking compatibility.
		);

		?>
		<div class="seedprod-dashboard-page">
			<?php
			$page_title = __( 'Website Builder', 'coming-soon' );
			require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-header.php';
			?>
			<div class="seedprod-dashboard-container">
				<?php require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-product-education.php'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Subscribers product education page (for Lite users)
	 */
	public function render_subscribers_education() {
		// Enqueue admin styles.
		wp_enqueue_style( 'seedprod-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version );

		// Configure Subscribers educational content.
		$product_education_config = array(
			'feature_name'         => __( 'Subscribers', 'coming-soon' ),
			'headline'             => __( 'Start Collecting Emails in 60 Seconds - No Email Service Required', 'coming-soon' ),
			'subheadline'          => __( 'Build your list from day one. Store unlimited subscribers in WordPress and export them anywhere, anytime.', 'coming-soon' ),
			'description'          => __( 'Never miss a lead again. Collect email addresses from your visitors and manage them all in one place, even before your email service is connected.', 'coming-soon' ),
			'benefits'             => array(
				__( 'Store unlimited email subscribers directly in WordPress', 'coming-soon' ),
				__( 'Export subscriber lists to CSV for easy import to any email service', 'coming-soon' ),
				__( 'Never lose a lead - capture emails even without an email service connected', 'coming-soon' ),
				__( 'One-click sync with 15+ providers: Mailchimp, ConvertKit, ActiveCampaign & more', 'coming-soon' ),
				__( 'Connect Zapier to sync with 3000+ apps automatically', 'coming-soon' ),
				__( 'Works day one - collect emails instantly, connect your service later', 'coming-soon' ),
			),
			'features'             => array(
				array(
					'icon'        => 'groups',
					'title'       => __( 'Subscriber Dashboard', 'coming-soon' ),
					'description' => __( 'View all your subscribers in one organized dashboard with search and filter capabilities.', 'coming-soon' ),
				),
				array(
					'icon'        => 'download',
					'title'       => __( 'Export to CSV', 'coming-soon' ),
					'description' => __( 'Download your subscriber lists in CSV format for easy import to any email marketing service.', 'coming-soon' ),
				),
				array(
					'icon'        => 'chart-line',
					'title'       => __( 'Conversion Tracking', 'coming-soon' ),
					'description' => __( 'Track how many visitors convert to subscribers and monitor your growth trends.', 'coming-soon' ),
				),
				array(
					'icon'        => 'shield',
					'title'       => __( 'Privacy Compliant', 'coming-soon' ),
					'description' => __( 'Built-in GDPR compliance features including consent checkboxes and data management.', 'coming-soon' ),
				),
			),
			'image'                => 'pe/pe-subscribers.png',
			'testimonial'          => array(
				'text'    => 'Want to create a great looking landing page and grab emails easily? Check out SeedProd!',
				'author'  => 'Chris Ducker',
				'company' => 'Author of \'Virtual Freedom\'',
			),
			'cta_headline'         => __( '📧 Ready to Start Building Your Email List?', 'coming-soon' ),
			'feature_slug'         => 'subscribers',
			'cta_context'          => 'pluginsubscriberpage', // Exact UTM medium to match old tracking.
			'use_exact_utm_medium' => true, // Skip 'plugin' prefix - use exact value.
		);

		?>
		<div class="seedprod-dashboard-page">
			<?php
			$page_title = __( 'Subscribers', 'coming-soon' );
			require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-header.php';
			?>
			<div class="seedprod-dashboard-container">
				<?php require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-product-education.php'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the Support page with upsell for Lite users
	 *
	 * @since 1.0.0
	 */
	public function display_support_page() {
		// Check if this is the Lite view.
		$is_lite_view = seedprod_lite_v2_is_lite_view();

		// Pro users get redirected to documentation.
		if ( ! $is_lite_view ) {
			$support_url = seedprod_lite_get_support_link( '', 'WordPress', 'admin-support-page', 'proplugin' );
			wp_safe_redirect( $support_url );
			exit;
		}

		// Lite users see the upsell page.
		$product_education_config = array(
			'page_type'            => 'support',
			'feature_name'         => __( 'Premium Support', 'coming-soon' ),
			'feature_heading'      => __( 'Premium Support', 'coming-soon' ),
			'headline'             => __( 'Get Unstuck in Minutes, Not Days', 'coming-soon' ),
			'subheadline'          => __( 'Our WordPress experts solve your issues fast so you can focus on growing your business', 'coming-soon' ),
			'description'          => __( 'Stop wasting hours on forums hoping someone might help. With Pro support, you get direct access to our expert developers who know exactly how to fix your issue - usually within hours, not days.', 'coming-soon' ),
			'feature_intro'        => __( 'Get Priority Support from the SeedProd Team', 'coming-soon' ),
			'feature_description'  => __( 'Upgrade to SeedProd Pro and get access to our world-class support team. Our experts are ready to help you build amazing websites faster with priority email support.', 'coming-soon' ),
			'benefits'             => array(
				__( 'Priority email support - faster responses than community forums', 'coming-soon' ),
				__( 'Direct help from the developers who built SeedProd', 'coming-soon' ),
				__( 'Premium documentation and video tutorials for every feature', 'coming-soon' ),
				__( 'Personalized solutions and code snippets for your specific needs', 'coming-soon' ),
				__( 'Pro optimization tips to improve your site performance', 'coming-soon' ),
				__( '14-day money-back guarantee - zero risk to try', 'coming-soon' ),
			),
			'image'                => 'pe/pe-support.png',
			'comparison'           => array(
				'title'   => __( 'Support Comparison', 'coming-soon' ),
				'headers' => array(
					__( 'Support Feature', 'coming-soon' ),
					__( 'Free Version', 'coming-soon' ),
					__( 'Pro Version', 'coming-soon' ),
				),
				'rows'    => array(
					array(
						__( 'Support Channel', 'coming-soon' ),
						__( 'WordPress.org Forums', 'coming-soon' ),
						__( 'Priority Email Support', 'coming-soon' ),
					),
					array(
						__( 'Response Time', 'coming-soon' ),
						__( 'Community-based', 'coming-soon' ),
						__( 'Within 24 hours', 'coming-soon' ),
					),
					array(
						__( 'Support Team', 'coming-soon' ),
						__( 'Community volunteers', 'coming-soon' ),
						__( 'SeedProd experts', 'coming-soon' ),
					),
					array(
						__( 'Custom Solutions', 'coming-soon' ),
						'❌',
						'✅',
					),
					array(
						__( 'Video Tutorials', 'coming-soon' ),
						__( 'Basic tutorials', 'coming-soon' ),
						__( 'Premium video library', 'coming-soon' ),
					),
					array(
						__( 'Pro Feature Support', 'coming-soon' ),
						'❌',
						'✅',
					),
				),
			),
			'testimonial'          => array(
				'text'    => 'A most useful, reliable, and elegant plugin. Super easy to use, mighty cool, and works sooo smoothly I cried tears of joy. All praise to the dev(s)!',
				'author'  => 'kados',
				'company' => 'WordPress.org User',
			),
			'cta_headline'         => __( '🎯 Get Expert Help When You Need It', 'coming-soon' ),
			'feature_slug'         => 'support',
			'cta_context'          => 'inlinehelp', // UTM medium.
			'use_exact_utm_medium' => true,
			'secondary_button'     => array(
				'text'    => __( 'Continue to Free Community Support', 'coming-soon' ),
				'url'     => 'https://wordpress.org/support/plugin/coming-soon/',
				'icon'    => 'dashicons-groups',
				'new_tab' => true,
			),
		);

		?>
		<div class="seedprod-dashboard-page">
			<?php
			$page_title = __( 'Get Support', 'coming-soon' );
			require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-header.php';
			?>
			<div class="seedprod-dashboard-container">
				<?php require_once plugin_dir_path( __FILE__ ) . 'partials/seedprod-admin-product-education.php'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Add green styling to upgrade menu item
	 * This replicates the old Vue.js upgrade menu styling
	 */
	public function add_upgrade_menu_styling() {
		?>
		<style>
			.sp-lite-admin-menu__upgrade_wrapper {
				background-color: #1da867 !important;
			}
			.sp-lite-admin-menu__upgrade_wrapper a {
				color: #fff !important;
				font-weight: 600 !important;
			}
			.sp-lite-admin-menu__upgrade_wrapper a:hover,
			.sp-lite-admin-menu__upgrade_wrapper a:focus {
				color: #fff !important;
			}
		</style>
		<script>
			jQuery(function($) {
				// Add class to upgrade menu item.
				$('#sp-lite-admin-menu__upgrade').parent().parent().addClass('sp-lite-admin-menu__upgrade_wrapper');
				
				// Handle click to redirect to upgrade URL.
				$('#sp-lite-admin-menu__upgrade').parent().on('click', function(e) {
					e.preventDefault();
					<?php
					// Get the upgrade URL with proper UTM tracking.
					// Use 'wp-sidebar-menu' to match the old Vue sidebar menu UTM tracking.
					$upgrade_url = seedprod_lite_get_external_link(
						'https://www.seedprod.com/lite-upgrade/',
						'wp-sidebar-menu',
						'liteplugin'
					);
					?>
					var upgradeUrl = <?php echo wp_json_encode( $upgrade_url ); ?>;
					window.open(upgradeUrl, '_blank');
				});
			});
		</script>
		<?php
	}

	/**
	 * Add plugin action links on the Plugins page
	 *
	 * @param array  $links Action links.
	 * @param string $file  Plugin file.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( $links, $file ) {
		$plugin_file = SEEDPROD_SLUG;

		if ( $file === $plugin_file || 'seedprod-pro/seedprod-pro.php' === $file ) {
			// Add Settings link.
			$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=seedprod_lite' ) ) . '">' .
				esc_html__( 'Settings', 'coming-soon' ) . '</a>';
			array_unshift( $links, $settings_link );

			// Add Upgrade link for Lite version.
			if ( 'lite' === SEEDPROD_BUILD ) {
				$upgrade_url  = seedprod_lite_get_external_link(
					'https://www.seedprod.com/lite-upgrade/',
					'plugin-actions-upgrade-link',
					'liteplugin'
				);
				$upgrade_link = '<a href="' . esc_url( $upgrade_url ) . '" target="_blank" style="color: #1da867; font-weight: 600;">' .
					esc_html__( 'Upgrade to Pro', 'coming-soon' ) . '</a>';
				array_unshift( $links, $upgrade_link );
			}
		}
		return $links;
	}
}
