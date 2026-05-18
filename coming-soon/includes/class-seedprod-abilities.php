<?php
/**
 * SeedProd Abilities API Integration
 *
 * Registers SeedProd capabilities with the WordPress Abilities API (WP 6.9+)
 * for discoverability by automation tools, AI agents, and third-party integrations.
 *
 * @package    SeedProd
 * @subpackage SeedProd/includes
 * @since      6.20.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SeedProd_Lite_Abilities
 *
 * Handles registration of SeedProd abilities with the WordPress Abilities API.
 */
class SeedProd_Lite_Abilities {

	/**
	 * Initialize the abilities registration.
	 */
	public function __construct() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the SeedProd category.
	 */
	public function register_category() {
		wp_register_ability_category(
			'seedprod',
			array(
				'label'       => __( 'SeedProd', 'coming-soon' ),
				'description' => __( 'SeedProd page builder and website customization operations.', 'coming-soon' ),
			)
		);
	}

	/**
	 * Register all SeedProd abilities.
	 */
	public function register_abilities() {
		$this->register_get_status();
		$this->register_toggle_coming_soon();
		$this->register_toggle_maintenance();
		$this->register_list_pages();
		$this->register_save_page();
		$this->register_get_page();

	}

	// -------------------------------------------------------------------------
	// Status
	// -------------------------------------------------------------------------

	/**
	 * Register the get-status ability.
	 */
	private function register_get_status() {
		wp_register_ability(
			'seedprod/get-status',
			array(
				'label'        => __( 'Get SeedProd Status', 'coming-soon' ),
				'description'  => __( 'Get the current status of SeedProd including coming soon mode, maintenance mode, theme builder status, and license information.', 'coming-soon' ),
				'category'     => 'seedprod',
				'input_schema' => array(
					'type'       => array( 'object', 'null' ),
					// Cast to stdClass so wp_json_encode emits {} not [].
					'properties' => (object) array(),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'coming_soon_enabled' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether coming soon mode is enabled.', 'coming-soon' ),
						),
						'coming_soon_page_id' => array(
							'type'        => 'integer',
							'description' => __( 'The ID of the coming soon page.', 'coming-soon' ),
						),
						'maintenance_enabled' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether maintenance mode is enabled.', 'coming-soon' ),
						),
						'maintenance_page_id' => array(
							'type'        => 'integer',
							'description' => __( 'The ID of the maintenance mode page.', 'coming-soon' ),
						),
						'login_enabled' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the custom login mode is enabled (Pro). When on, SeedProd serves the bound login page instead of wp-login.', 'coming-soon' ),
						),
						'login_page_id' => array(
							'type'        => 'integer',
							'description' => __( 'The ID of the SeedProd login page (Pro).', 'coming-soon' ),
						),
						'404_enabled' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the custom 404 mode is enabled (Pro). When on, SeedProd serves the bound 404 page instead of the WordPress default.', 'coming-soon' ),
						),
						'404_page_id' => array(
							'type'        => 'integer',
							'description' => __( 'The ID of the SeedProd 404 page (Pro).', 'coming-soon' ),
						),
						'theme_enabled' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the SeedProd theme builder is enabled.', 'coming-soon' ),
						),
						'license_active' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether a valid license is active.', 'coming-soon' ),
						),
						'license_type' => array(
							'type'        => 'string',
							'description' => __( 'The type of license (e.g., Basic, Plus, Pro, Elite).', 'coming-soon' ),
						),
						'version' => array(
							'type'        => 'string',
							'description' => __( 'The current SeedProd plugin version.', 'coming-soon' ),
						),
						'build' => array(
							'type'        => 'string',
							'description' => __( 'The build type (pro or lite).', 'coming-soon' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_status' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta' => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'   => true,
						'idempotent' => true,
					),
				),
			)
		);
	}

	/**
	 * Execute the get-status ability.
	 *
	 * @param array $input The input parameters (unused).
	 * @return array The current SeedProd status.
	 */
	public function execute_get_status( $input ) {
		$settings = $this->get_settings();

		$theme_enabled = ! empty( $settings['enable_seedprod_theme'] )
			|| ! empty( get_option( 'seedprod_theme_enabled', false ) );

		return array(
			'coming_soon_enabled'  => ! empty( $settings['enable_coming_soon_mode'] ),
			'coming_soon_page_id'  => absint( get_option( 'seedprod_coming_soon_page_id', 0 ) ),
			'maintenance_enabled'  => ! empty( $settings['enable_maintenance_mode'] ),
			'maintenance_page_id'  => absint( get_option( 'seedprod_maintenance_mode_page_id', 0 ) ),
			'login_enabled'        => ! empty( $settings['enable_login_mode'] ),
			'login_page_id'        => absint( get_option( 'seedprod_login_page_id', 0 ) ),
			'404_enabled'          => ! empty( $settings['enable_404_mode'] ),
			'404_page_id'          => absint( get_option( 'seedprod_404_page_id', 0 ) ),
			'theme_enabled'        => $theme_enabled,
			'license_active'       => (bool) get_option( 'seedprod_a', false ),
			'license_type'         => get_option( 'seedprod_license_name', '' ),
			'version'              => SEEDPROD_VERSION,
			'build'                => SEEDPROD_BUILD,
		);
	}

	// -------------------------------------------------------------------------
	// Mode toggles
	// -------------------------------------------------------------------------

	/**
	 * Register the toggle-coming-soon ability.
	 */
	private function register_toggle_coming_soon() {
		wp_register_ability(
			'seedprod/toggle-coming-soon',
			array(
				'label'        => __( 'Toggle Coming Soon Mode', 'coming-soon' ),
				'description'  => __( 'Enable or disable the SeedProd coming soon page. When enabled, non-logged-in users see the coming soon page instead of the live site. Automatically disables maintenance mode (they are mutually exclusive).', 'coming-soon' ),
				'category'     => 'seedprod',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'enabled' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether to enable (true) or disable (false) coming soon mode.', 'coming-soon' ),
						),
						'page_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional. The ID of the SeedProd page to use. If omitted, uses the existing page.', 'coming-soon' ),
						),
					),
					'required' => array( 'enabled' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'enabled' => array( 'type' => 'boolean' ),
						'page_id' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_toggle_coming_soon' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta' => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'idempotent' => true,
					),
				),
			)
		);
	}

	/**
	 * Execute the toggle-coming-soon ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error The result or error.
	 */
	public function execute_toggle_coming_soon( $input ) {
		$enabled = isset( $input['enabled'] ) ? (bool) $input['enabled'] : false;
		$page_id = isset( $input['page_id'] ) ? absint( $input['page_id'] ) : null;

		$settings = $this->get_settings();

		if ( $enabled && ! $page_id ) {
			$page_id = absint( get_option( 'seedprod_coming_soon_page_id', 0 ) );
			if ( ! $page_id ) {
				return new WP_Error(
					'seedprod_no_page',
					__( 'No coming soon page exists. Create one with seedprod/save-page first, then pass its ID.', 'coming-soon' )
				);
			}
		}

		// Mutually exclusive with maintenance mode.
		if ( $enabled ) {
			$settings['enable_maintenance_mode'] = false;
		}

		$settings['enable_coming_soon_mode'] = $enabled;

		if ( $page_id ) {
			update_option( 'seedprod_coming_soon_page_id', $page_id );
		}

		update_option( 'seedprod_settings', wp_json_encode( $settings ) );

		return array(
			'success' => true,
			'enabled' => $enabled,
			'page_id' => $page_id ? $page_id : absint( get_option( 'seedprod_coming_soon_page_id', 0 ) ),
		);
	}

	/**
	 * Register the toggle-maintenance ability.
	 */
	private function register_toggle_maintenance() {
		wp_register_ability(
			'seedprod/toggle-maintenance',
			array(
				'label'        => __( 'Toggle Maintenance Mode', 'coming-soon' ),
				'description'  => __( 'Enable or disable the SeedProd maintenance mode page. When enabled, non-logged-in users see the maintenance page and search engines receive a 503 status code. Automatically disables coming soon mode (they are mutually exclusive).', 'coming-soon' ),
				'category'     => 'seedprod',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'enabled' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether to enable (true) or disable (false) maintenance mode.', 'coming-soon' ),
						),
						'page_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional. The ID of the SeedProd page to use. If omitted, uses the existing page.', 'coming-soon' ),
						),
					),
					'required' => array( 'enabled' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'enabled' => array( 'type' => 'boolean' ),
						'page_id' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_toggle_maintenance' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta' => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'idempotent' => true,
					),
				),
			)
		);
	}

	/**
	 * Execute the toggle-maintenance ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error The result or error.
	 */
	public function execute_toggle_maintenance( $input ) {
		$enabled = isset( $input['enabled'] ) ? (bool) $input['enabled'] : false;
		$page_id = isset( $input['page_id'] ) ? absint( $input['page_id'] ) : null;

		$settings = $this->get_settings();

		if ( $enabled && ! $page_id ) {
			$page_id = absint( get_option( 'seedprod_maintenance_mode_page_id', 0 ) );
			if ( ! $page_id ) {
				return new WP_Error(
					'seedprod_no_page',
					__( 'No maintenance mode page exists. Create one with seedprod/save-page first, then pass its ID.', 'coming-soon' )
				);
			}
		}

		// Mutually exclusive with coming soon mode.
		if ( $enabled ) {
			$settings['enable_coming_soon_mode'] = false;
		}

		$settings['enable_maintenance_mode'] = $enabled;

		if ( $page_id ) {
			update_option( 'seedprod_maintenance_mode_page_id', $page_id );
		}

		update_option( 'seedprod_settings', wp_json_encode( $settings ) );

		return array(
			'success' => true,
			'enabled' => $enabled,
			'page_id' => $page_id ? $page_id : absint( get_option( 'seedprod_maintenance_mode_page_id', 0 ) ),
		);
	}

	// -------------------------------------------------------------------------
	// Pages
	// -------------------------------------------------------------------------

	/**
	 * Register the list-pages ability.
	 */
	private function register_list_pages() {
		// strval cast: PHP stores numeric-string keys as ints (e.g. '404' => 404),
		// so array_keys() returns int 404 here. JSON Schema rejects mixed-type
		// enums; coerce back to strings.
		$type_enum   = array_map( 'strval', array_keys( $this->get_save_page_type_map() ) );
		$type_enum[] = 'all';

		wp_register_ability(
			'seedprod/list-pages',
			array(
				'label'        => __( 'List SeedProd Pages', 'coming-soon' ),
				'description'  => __( 'Get a list of all SeedProd pages (landing pages, mode pages, and theme templates) with their IDs, titles, types, and status.', 'coming-soon' ),
				'category'     => 'seedprod',
				'input_schema' => array(
					'type'       => array( 'object', 'null' ),
					'properties' => array(
						'type' => array(
							'type'        => 'string',
							'enum'        => $type_enum,
							'description' => __( 'Filter by page type. Uses the same long-form values as save-page (landing-page, coming-soon, maintenance, ...). Default: all', 'coming-soon' ),
						),
						'status' => array(
							'type'        => 'string',
							'enum'        => array( 'publish', 'draft', 'all' ),
							'description' => __( 'Filter by status. Default: all', 'coming-soon' ),
						),
					),
				),
				'output_schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'       => array( 'type' => 'integer' ),
							'title'    => array( 'type' => 'string' ),
							'type'     => array( 'type' => 'string' ),
							'status'   => array( 'type' => 'string' ),
							'edit_url' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_pages' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_pages' );
				},
				'meta' => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'   => true,
						'idempotent' => true,
					),
				),
			)
		);
	}

	/**
	 * Execute the list-pages ability.
	 *
	 * @param array $input The input parameters.
	 * @return array The list of pages.
	 */
	public function execute_list_pages( $input ) {
		global $wpdb;

		$type   = isset( $input['type'] ) ? sanitize_text_field( $input['type'] ) : 'all';
		$status = isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : 'all';

		$meta_table  = $wpdb->prefix . 'postmeta';
		$posts_table = $wpdb->prefix . 'posts';

		$sql = "SELECT p.ID, p.post_title, p.post_status, pm_type.meta_value as page_type
				FROM {$posts_table} p
				LEFT JOIN {$meta_table} pm_uuid ON (pm_uuid.post_id = p.ID AND pm_uuid.meta_key = '_seedprod_page_uuid')
				LEFT JOIN {$meta_table} pm_type ON (pm_type.post_id = p.ID AND pm_type.meta_key = '_seedprod_page_template_type')
				WHERE p.post_status != 'trash'
				AND pm_uuid.meta_value IS NOT NULL
				AND (pm_type.meta_value IS NULL OR pm_type.meta_value != 'css')";

		if ( 'all' !== $status ) {
			$sql .= $wpdb->prepare( ' AND p.post_status = %s', $status );
		}

		if ( 'all' !== $type ) {
			$type_map   = $this->get_save_page_type_map();
			$type_short = isset( $type_map[ $type ] ) ? $type_map[ $type ]['template_type'] : $type;
			$sql       .= $wpdb->prepare( ' AND pm_type.meta_value = %s', $type_short );
		}

		$sql .= ' ORDER BY p.post_date DESC';

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$pages = array();
		foreach ( $results as $row ) {
			$short = $row->page_type ? $row->page_type : 'lp';
			$pages[] = array(
				'id'       => absint( $row->ID ),
				'title'    => $row->post_title,
				'type'     => $this->page_type_long_form( $short ),
				'status'   => $row->post_status,
				'edit_url' => admin_url( 'admin.php?page=seedprod_lite_builder&id=' . absint( $row->ID ) ),
			);
		}

		return $pages;
	}

	/**
	 * Get the supported save-page types.
	 *
	 * @return array<string, array{post_type: string, template_type: string, is_theme: bool}>
	 */
	private function get_save_page_type_map() {
		$type_map = array(
			'landing-page' => array( 'post_type' => 'page',     'template_type' => 'lp', 'is_theme' => false ),
			'coming-soon'  => array( 'post_type' => 'seedprod', 'template_type' => 'cs', 'is_theme' => false ),
			'maintenance'  => array( 'post_type' => 'seedprod', 'template_type' => 'mm', 'is_theme' => false ),
		);

		if ( 'lite' !== SEEDPROD_BUILD ) {
			$type_map['login']         = array( 'post_type' => 'seedprod', 'template_type' => 'loginp', 'is_theme' => false );
			$type_map['404']           = array( 'post_type' => 'seedprod', 'template_type' => 'p404',   'is_theme' => false );
			$type_map['header']        = array( 'post_type' => 'seedprod', 'template_type' => 'header', 'is_theme' => true );
			$type_map['footer']        = array( 'post_type' => 'seedprod', 'template_type' => 'footer', 'is_theme' => true );
			$type_map['page-template'] = array( 'post_type' => 'seedprod', 'template_type' => 'page',   'is_theme' => true );
			$type_map['part']          = array( 'post_type' => 'seedprod', 'template_type' => 'part',   'is_theme' => true );
		}

		// CAREFUL: PHP stores numeric-string array keys as integers ('404' => …
		// is stored under int 404). There's no way to keep them as strings at the
		// array level; consumers that read keys (array_keys, foreach …as $key)
		// must coerce back to string themselves. See page_type_long_form() and
		// the two type_enum sites in register_save_page / register_list_pages.
		return $type_map;
	}

	/**
	 * Map a stored short-form page_type (e.g. "lp", "cs", "page") to the long-form
	 * value used by the save-page input enum (e.g. "landing-page", "coming-soon",
	 * "page-template"). Derived from get_save_page_type_map() so the two stay in
	 * sync; returns the input unchanged when no entry in the current build's map
	 * declares it as a template_type.
	 *
	 * @param string $short The short-form page_type stored on the page.
	 * @return string The long-form value, suitable for use as a save-page input type.
	 */
	private function page_type_long_form( $short ) {
		foreach ( $this->get_save_page_type_map() as $long => $config ) {
			if ( $config['template_type'] === $short ) {
				// (string) cast: PHP stores numeric-string keys as ints, so the
				// '404' map key is yielded as int 404. The output_schema declares
				// 'type' as string; without this cast list-pages 500s with
				// "output[0][type] is not of type string".
				return (string) $long;
			}
		}
		return $short;
	}

	/**
	 * Mapping of short-form mode page type to its activation settings.
	 *
	 * Mirrors app/settings.php $settings_to_update — the plugin runtime reads
	 * the matching flag from seedprod_settings and the matching option for the
	 * page id when serving each mode.
	 *
	 * @return array<string, array{flag: string, option: string}>
	 */
	private function get_mode_activation_map() {
		return array(
			'cs'     => array( 'flag' => 'enable_coming_soon_mode', 'option' => 'seedprod_coming_soon_page_id' ),
			'mm'     => array( 'flag' => 'enable_maintenance_mode', 'option' => 'seedprod_maintenance_mode_page_id' ),
			'loginp' => array( 'flag' => 'enable_login_mode',       'option' => 'seedprod_login_page_id' ),
			'p404'   => array( 'flag' => 'enable_404_mode',         'option' => 'seedprod_404_page_id' ),
		);
	}

	/**
	 * Activate the mode bound to a mode page (coming-soon, maintenance, login,
	 * or 404). Coming-soon and maintenance are mutually exclusive (both signal
	 * "site is offline"); login and 404 are independent of every other mode.
	 *
	 * Used by both the create and update paths of execute_save_page so the
	 * activate=true flag behaves consistently regardless of code path.
	 *
	 * @param int    $page_id          The post ID of the page to activate.
	 * @param string $page_type_short  The page's short-form type (cs|mm|loginp|p404|...).
	 * @param bool   $activate         Whether the caller asked to activate.
	 * @return bool True if a mode was activated, false otherwise.
	 */
	private function maybe_activate_mode( $page_id, $page_type_short, $activate ) {
		if ( ! $activate ) {
			return false;
		}

		$map = $this->get_mode_activation_map();
		if ( ! isset( $map[ $page_type_short ] ) ) {
			return false;
		}

		$entry    = $map[ $page_type_short ];
		$settings = $this->get_settings();
		$settings[ $entry['flag'] ] = true;

		if ( 'cs' === $page_type_short ) {
			$settings['enable_maintenance_mode'] = false;
		} elseif ( 'mm' === $page_type_short ) {
			$settings['enable_coming_soon_mode'] = false;
		}

		update_option( $entry['option'], $page_id );
		update_option( 'seedprod_settings', wp_json_encode( $settings ) );
		return true;
	}

	/**
	 * Register the save-page ability.
	 */
	private function register_save_page() {
		$is_lite  = ( 'lite' === SEEDPROD_BUILD );
		// strval cast: PHP stores numeric-string keys as ints (e.g. '404' => 404),
		// so array_keys() returns int 404 here. JSON Schema rejects mixed-type
		// enums; coerce back to strings.
		$type_enum = array_map( 'strval', array_keys( $this->get_save_page_type_map() ) );

		$type_description = $is_lite
			? __( 'Page type. Required when creating; ignored when updating an existing page (id is set). landing-page = standard landing page, coming-soon/maintenance = mode pages.', 'coming-soon' )
			: __( 'Page type. Required when creating; ignored when updating an existing page (id is set). landing-page = standard landing page, coming-soon/maintenance/login/404 = special mode pages, header/footer/page-template/part = theme templates.', 'coming-soon' );

		$description = $is_lite
			? __( 'Create or update a SeedProd page. Lite supports landing pages, coming soon, and maintenance pages. Pass an id to update an existing page, or omit to create a new one.', 'coming-soon' )
			: __( 'Create or update any SeedProd page. Handles landing pages, coming soon, maintenance, login, and theme templates (header, footer, page templates, parts). Pass an id to update an existing page, or omit to create a new one.', 'coming-soon' );

		wp_register_ability(
			'seedprod/save-page',
			array(
				'label'        => __( 'Save SeedProd Page', 'coming-soon' ),
				'description'  => $description,
				'category'     => 'seedprod',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => __( 'Post ID of an existing page to update. Omit to create a new page.', 'coming-soon' ),
						),
						'type' => array(
							'type'        => 'string',
							'enum'        => $type_enum,
							'description' => $type_description,
						),
						'title' => array(
							'type'        => 'string',
							'description' => __( 'Page title. Required when creating.', 'coming-soon' ),
						),
						'status' => array(
							'type'        => 'string',
							'enum'        => array( 'publish', 'draft' ),
							'description' => __( 'Post status. Default: draft.', 'coming-soon' ),
						),
						'sections' => array(
							'type'        => 'array',
							'description' => __( 'The document sections array containing rows, columns, and blocks. This is the page content.', 'coming-soon' ),
						),
						'condition' => array(
							'type'        => 'string',
							'enum'        => array( '_entire_site', 'is_front_page', 'is_home', 'is_page(x)', 'is_single(x)', 'is_404', 'is_archive' ),
							'description' => __( 'Template condition for theme templates. Controls where the template renders. Required when creating a header/footer/page-template/part; ignored when updating an existing page (id is set).', 'coming-soon' ),
						),
						'activate' => array(
							'type'        => 'boolean',
							'description' => __( 'For coming-soon, maintenance, login, and 404 pages: whether to bind this page as the active mode after saving. Works on both create and update. Coming-soon and maintenance are mutually exclusive (activating one disables the other); login and 404 are independent. Default: false.', 'coming-soon' ),
						),
					),
					'required' => array(),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => __( 'The post ID of the created or updated page.', 'coming-soon' ),
						),
						'title' => array(
							'type'        => 'string',
							'description' => __( 'The page title.', 'coming-soon' ),
						),
						'type' => array(
							'type'        => 'string',
							'description' => __( 'The page type.', 'coming-soon' ),
						),
						'edit_url' => array(
							'type'        => 'string',
							'description' => __( 'URL to edit this page in the SeedProd builder.', 'coming-soon' ),
						),
						'activated' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether a mode was activated (for coming-soon/maintenance).', 'coming-soon' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_save_page' ),
				'permission_callback' => function () {
					return current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) );
				},
				'meta' => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'idempotent'   => false,
						'instructions' => 'Load load_skill("seedprod-building") first for page structure, JSON hierarchy, and design guidelines. Then load load_skill("seedprod-blocks") for block schemas before building sections.',
					),
				),
			)
		);
	}

	/**
	 * Execute the save-page ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error The result or error.
	 */
	public function execute_save_page( $input ) {
		$id       = isset( $input['id'] ) ? absint( $input['id'] ) : 0;
		$type     = isset( $input['type'] ) ? sanitize_text_field( $input['type'] ) : '';
		$title    = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '';
		$status   = isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : 'draft';
		$sections = isset( $input['sections'] ) ? $input['sections'] : array();
		$condition = isset( $input['condition'] ) ? sanitize_text_field( $input['condition'] ) : '';
		$activate = isset( $input['activate'] ) ? (bool) $input['activate'] : false;

		// Validate sections is an array.
		if ( ! empty( $sections ) && ! is_array( $sections ) ) {
			return new WP_Error( 'invalid_sections', __( 'sections must be an array of section objects.', 'coming-soon' ) );
		}

		// --- Update existing page ---
		if ( $id ) {
			$post = get_post( $id );
			if ( ! $post ) {
				return new WP_Error( 'not_found', __( 'Page not found.', 'coming-soon' ) );
			}

			// Read existing content_filtered and merge sections into it.
			$existing = json_decode( $post->post_content_filtered, true );
			if ( ! is_array( $existing ) ) {
				$existing = array();
			}

			if ( ! empty( $sections ) ) {
				if ( ! isset( $existing['document'] ) ) {
					$existing['document'] = array();
				}
				$existing['document']['sections'] = $sections;
			}

			if ( $title ) {
				$existing['post_title'] = $title;
				wp_update_post( array( 'ID' => $id, 'post_title' => $title ) );
			}

			if ( $status && $status !== $post->post_status ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => $status ) );
			}

			// Validate JSON before saving.
			$json = wp_json_encode( $existing );
			if ( false === $json || null === $json ) {
				return new WP_Error( 'invalid_json', __( 'Failed to encode page content as JSON. Check that sections contain valid data.', 'coming-soon' ) );
			}

			// Write content_filtered via direct SQL to bypass KSES.
			global $wpdb;
			$result = $wpdb->update(
				$wpdb->posts,
				array( 'post_content_filtered' => $json ),
				array( 'ID' => $id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error( 'db_error', __( 'Failed to update page content.', 'coming-soon' ) );
			}

			clean_post_cache( $id );

			// _seedprod_page_template_type post meta is the authoritative source
			// (it's what list-pages reads, and it survives content_filtered drift).
			// Fall back to the JSON page_type, then to "lp", for older rows that
			// didn't seed the meta.
			$meta_type           = get_post_meta( $id, '_seedprod_page_template_type', true );
			$existing_type_short = $meta_type ? $meta_type : ( isset( $existing['page_type'] ) ? $existing['page_type'] : 'lp' );
			$activated           = $this->maybe_activate_mode( $id, $existing_type_short, $activate );

			return array(
				'id'        => $id,
				'title'     => $title ? $title : $post->post_title,
				'type'      => $this->page_type_long_form( $existing_type_short ),
				'edit_url'  => admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $id ),
				'activated' => $activated,
			);
		}

		// --- Create new page ---
		if ( ! $type ) {
			return new WP_Error( 'missing_type', __( 'type is required when creating a new page.', 'coming-soon' ) );
		}
		if ( ! $title ) {
			return new WP_Error( 'missing_title', __( 'title is required when creating a new page.', 'coming-soon' ) );
		}

		$type_map = $this->get_save_page_type_map();

		if ( ! isset( $type_map[ $type ] ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid page type for this build.', 'coming-soon' ) );
		}

		$config = $type_map[ $type ];

		// Require condition for theme templates.
		if ( $config['is_theme'] && ! $condition ) {
			return new WP_Error( 'missing_condition', __( 'condition is required for theme templates (header, footer, page-template, part).', 'coming-soon' ) );
		}

		// Build the document.
		$document = $this->build_document( $sections );

		// Build the content_filtered JSON wrapper.
		$content = array(
			'page_type'             => $config['template_type'],
			'post_title'            => $title,
			'post_name'             => sanitize_title( $title ),
			'post_status'           => $status,
			'show_header_template'  => true,
			'show_footer_template'  => true,
			'no_conflict_mode'      => false,
			'no_index'              => false,
			'is_new'                => false,
			'template_id'           => 0,
			'document'              => $document,
		);

		// Build meta_input.
		$meta_input = array(
			'_seedprod_page'               => true,
			'_seedprod_page_uuid'          => wp_generate_uuid4(),
			'_seedprod_page_template_type' => $config['template_type'],
		);

		if ( $config['is_theme'] ) {
			$meta_input['_seedprod_is_theme_template'] = true;
			$meta_input['_seedprod_theme_template_condition'] = wp_json_encode(
				array( array( 'condition' => 'include', 'type' => $condition, 'value' => '' ) )
			);
		}

		// Insert the post.
		$post_id = wp_insert_post(
			array(
				'post_type'    => $config['post_type'],
				'post_title'   => $title,
				'post_name'    => sanitize_title( $title ),
				'post_status'  => $status,
				'post_content' => '',
				'meta_input'   => $meta_input,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Validate JSON before saving.
		$json = wp_json_encode( $content );
		if ( false === $json || null === $json ) {
			wp_delete_post( $post_id, true );
			return new WP_Error( 'invalid_json', __( 'Failed to encode page content as JSON. Check that sections contain valid data.', 'coming-soon' ) );
		}

		// Write content_filtered via direct SQL to bypass KSES.
		global $wpdb;
		$result = $wpdb->update(
			$wpdb->posts,
			array( 'post_content_filtered' => $json ),
			array( 'ID' => $post_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_delete_post( $post_id, true );
			return new WP_Error( 'db_error', __( 'Failed to save page content.', 'coming-soon' ) );
		}

		clean_post_cache( $post_id );

		// Activate mode if requested.
		$activated = $this->maybe_activate_mode( $post_id, $config['template_type'], $activate );

		return array(
			'id'        => $post_id,
			'title'     => $title,
			'type'      => $type,
			'edit_url'  => admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $post_id ),
			'activated' => $activated,
		);
	}

	/**
	 * Build a SeedProd document structure with default settings.
	 *
	 * @param array $sections Optional sections array. If empty, creates a blank document.
	 * @return array The document structure.
	 */
	private function build_document( $sections = array() ) {
		// If sections provided by the AI, use them directly.
		if ( ! empty( $sections ) ) {
			return array(
				'sections' => $sections,
				'settings' => $this->get_default_document_settings(),
			);
		}

		// Build a minimal blank document: one section > one row > one col.
		$col_settings = array(
			'bgStyle'          => 's',
			'paddingTop'       => '40',
			'paddingBottom'    => '40',
			'paddingLeft'      => '20',
			'paddingRight'     => '20',
			'paddingSync'      => false,
			'marginSync'       => false,
			'borderRadiusSync' => true,
			'borderSync'       => true,
			'borderStyle'      => 'solid',
			'hideOnDesktop'    => false,
			'hideOnMobile'     => false,
			'hideOnTablet'     => false,
		);

		$row_settings = array(
			'bgStyle'          => 's',
			'colGutter'        => 0,
			'contentWidth'     => 2,
			'width'            => '1000',
			'paddingTop'       => '0',
			'paddingBottom'    => '0',
			'paddingLeft'      => '0',
			'paddingRight'     => '0',
			'paddingSync'      => true,
			'marginSync'       => false,
			'borderRadiusSync' => true,
			'borderSync'       => true,
			'borderStyle'      => 'solid',
			'hideOnDesktop'    => false,
			'hideOnMobile'     => false,
			'hideOnTablet'     => false,
		);

		$section_settings = array(
			'bgStyle'          => 's',
			'contentWidth'     => 1,
			'width'            => '1000',
			'paddingTop'       => '10',
			'paddingBottom'    => '10',
			'paddingLeft'      => '10',
			'paddingRight'     => '10',
			'paddingSync'      => true,
			'marginSync'       => false,
			'borderRadiusSync' => true,
			'borderSync'       => true,
			'borderStyle'      => 'solid',
			'hideOnDesktop'    => false,
			'hideOnMobile'     => false,
			'hideOnTablet'     => false,
		);

		return array(
			'sections' => array(
				array(
					'id'       => $this->make_uid(),
					'type'     => 'section',
					'rows'     => array(
						array(
							'id'       => $this->make_uid(),
							'type'     => 'row',
							'colType'  => '1-col',
							'cols'     => array(
								array(
									'id'       => $this->make_uid(),
									'type'     => 'col',
									'blocks'   => array(),
									'settings' => $col_settings,
								),
							),
							'settings' => $row_settings,
						),
					),
					'settings' => $section_settings,
				),
			),
			'settings' => $this->get_default_document_settings(),
		);
	}

	/**
	 * Get default document-level settings.
	 *
	 * @return array Default document settings.
	 */
	private function get_default_document_settings() {
		return array(
			'bgStyle'         => 's',
			'bgColor'         => '#FFFFFF',
			'textColor'       => '#333333',
			'headerColor'     => '#111111',
			'buttonColor'     => '#3858E9',
			'textFont'        => 'Roboto',
			'textFontVariant' => '400',
		);
	}

	/**
	 * Generate a 6-character UID matching SeedProd's frontend format.
	 *
	 * @return string A 6-character alphanumeric ID.
	 */
	private function make_uid() {
		$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$id    = $chars[ wp_rand( 0, 25 ) ];
		for ( $i = 0; $i < 5; $i++ ) {
			$id .= $chars[ wp_rand( 0, 35 ) ];
		}
		return $id;
	}

	// -------------------------------------------------------------------------
	// Get page
	// -------------------------------------------------------------------------

	/**
	 * Register the get-page ability.
	 *
	 * Two-tier response: metadata-only by default (cheap, useful for inspection),
	 * full sections JSON when include_sections=true (heavy, for read-then-edit
	 * workflows). The page-content JSON can run to hundreds of KB on complex
	 * pages — opting out by default keeps AI host context windows usable.
	 */
	private function register_get_page() {
		wp_register_ability(
			'seedprod/get-page',
			array(
				'label'        => __( 'Get SeedProd Page', 'coming-soon' ),
				'description'  => __( 'Get a single SeedProd page by ID. Returns metadata by default (lightweight); pass include_sections=true to also return the full sections JSON for editing.', 'coming-soon' ),
				'category'     => 'seedprod',
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'               => array(
							'type'        => 'integer',
							'description' => __( 'The post ID of the page to retrieve.', 'coming-soon' ),
						),
						'include_sections' => array(
							'type'        => 'boolean',
							'description' => __( 'When true, include the full document sections array. Default false returns metadata only — much cheaper for inspection, since sections can be hundreds of KB on complex pages.', 'coming-soon' ),
							'default'     => false,
						),
					),
					'required'   => array( 'id' ),
				),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id'            => array( 'type' => 'integer' ),
						'title'         => array( 'type' => 'string' ),
						'type'          => array(
							'type'        => 'string',
							'description' => __( 'Long-form page type (landing-page, coming-soon, ...).', 'coming-soon' ),
						),
						'status'        => array( 'type' => 'string' ),
						'slug'          => array( 'type' => 'string' ),
						'condition'     => array(
							'type'        => 'string',
							'description' => __( 'Template routing condition (theme templates only; empty string otherwise).', 'coming-soon' ),
						),
						'settings'      => array(
							'type'        => 'object',
							'description' => __( 'Page-level globals (fonts, layout). Populated for landing pages.', 'coming-soon' ),
						),
						'section_count' => array( 'type' => 'integer' ),
						'block_count'   => array( 'type' => 'integer' ),
						'activated'     => array(
							'type'        => 'boolean',
							'description' => __( 'True when this page is currently the bound active mode page (coming-soon, maintenance, login, or 404).', 'coming-soon' ),
						),
						'last_modified' => array(
							'type'        => 'string',
							'description' => __( 'ISO 8601 last-modified timestamp.', 'coming-soon' ),
						),
						'edit_url'      => array( 'type' => 'string' ),
						'sections'      => array(
							// Nullable: null when include_sections=false; array when true (possibly empty if the page has no sections).
							'type'        => array( 'array', 'null' ),
							'description' => __( 'Full document sections array. null when include_sections is false; an array (possibly empty) when true.', 'coming-soon' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_page' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_pages' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'     => true,
						'idempotent'   => true,
						'instructions' => 'Load load_skill("seedprod-building") for the document JSON hierarchy if you plan to modify and round-trip the sections.',
					),
				),
			)
		);
	}

	/**
	 * Execute the get-page ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error The page data.
	 */
	public function execute_get_page( $input ) {
		$id               = isset( $input['id'] ) ? absint( $input['id'] ) : 0;
		$include_sections = isset( $input['include_sections'] ) ? (bool) $input['include_sections'] : false;

		if ( ! $id ) {
			return new WP_Error( 'missing_id', __( 'id is required.', 'coming-soon' ), array( 'status' => 400 ) );
		}

		$post = get_post( $id );
		if ( ! $post ) {
			return new WP_Error( 'not_found', __( 'Page not found.', 'coming-soon' ), array( 'status' => 404 ) );
		}

		// Only return SeedProd-marked pages. execute_save_page sets _seedprod_page
		// on every page we own; without that filter we'd happily hand back
		// arbitrary wp_posts rows by id.
		if ( ! get_post_meta( $id, '_seedprod_page', true ) ) {
			return new WP_Error( 'not_seedprod', __( 'Post is not a SeedProd page.', 'coming-soon' ), array( 'status' => 400 ) );
		}

		// Global CSS is a different shape (no sections/rows/cols, just a CSS
		// string in document.globalHeadCss) and save-page rejects type=css, so
		// keep the read/write surface symmetric — list-pages hides css rows for
		// the same reason. Hand back not_seedprod with an explanatory message.
		if ( 'css' === get_post_meta( $id, '_seedprod_page_template_type', true ) ) {
			return new WP_Error( 'not_seedprod', __( 'Global CSS is not a content page.', 'coming-soon' ), array( 'status' => 400 ) );
		}

		$content = json_decode( $post->post_content_filtered, true );
		if ( ! is_array( $content ) ) {
			$content = array();
		}

		$document      = ( isset( $content['document'] ) && is_array( $content['document'] ) ) ? $content['document'] : array();
		$sections      = ( isset( $document['sections'] ) && is_array( $document['sections'] ) ) ? $document['sections'] : array();
		$page_settings = ( isset( $document['settings'] ) && is_array( $document['settings'] ) ) ? $document['settings'] : array();

		// Resolve long-form type. Authoritative source is _seedprod_page_template_type
		// (matches list-pages); fall back to the JSON page_type for older rows.
		$meta_type  = get_post_meta( $id, '_seedprod_page_template_type', true );
		$type_short = $meta_type ? $meta_type : ( isset( $content['page_type'] ) ? $content['page_type'] : 'lp' );

		// Theme template condition: stored as a JSON-encoded array of condition
		// objects. v1 returns the first entry's type — matches the single
		// condition string save-page accepts. If future versions accept
		// multi-rule conditions, return the full array instead.
		$condition      = '';
		$condition_meta = get_post_meta( $id, '_seedprod_theme_template_condition', true );
		if ( $condition_meta ) {
			$decoded = json_decode( $condition_meta, true );
			if ( is_array( $decoded ) && isset( $decoded[0]['type'] ) ) {
				$condition = (string) $decoded[0]['type'];
			}
		}

		// Walk section > row > col > block to count blocks.
		$block_count = 0;
		foreach ( $sections as $section ) {
			if ( ! isset( $section['rows'] ) || ! is_array( $section['rows'] ) ) {
				continue;
			}
			foreach ( $section['rows'] as $row ) {
				if ( ! isset( $row['cols'] ) || ! is_array( $row['cols'] ) ) {
					continue;
				}
				foreach ( $row['cols'] as $col ) {
					if ( ! isset( $col['blocks'] ) || ! is_array( $col['blocks'] ) ) {
						continue;
					}
					$block_count += count( $col['blocks'] );
				}
			}
		}

		// Is this page currently bound as the active mode page?
		$activated = false;
		$mode_map  = $this->get_mode_activation_map();
		if ( isset( $mode_map[ $type_short ] ) ) {
			$entry     = $mode_map[ $type_short ];
			$settings  = $this->get_settings();
			$bound_id  = (int) get_option( $entry['option'] );
			$activated = ( $bound_id === $id ) && ! empty( $settings[ $entry['flag'] ] );
		}

		return array(
			'id'            => $id,
			'title'         => $post->post_title,
			'type'          => (string) $this->page_type_long_form( $type_short ),
			'status'        => $post->post_status,
			'slug'          => $post->post_name,
			'condition'     => $condition,
			// Cast empty arrays through stdClass so wp_json_encode emits {} not [].
			'settings'      => empty( $page_settings ) ? (object) array() : $page_settings,
			'section_count' => count( $sections ),
			'block_count'   => $block_count,
			'activated'     => $activated,
			'last_modified' => mysql_to_rfc3339( $post->post_modified ),
			'edit_url'      => admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $id ),
			'sections'      => $include_sections ? $sections : null,
		);
	}


	/**
	 * Get the SeedProd settings as an array.
	 *
	 * @return array The settings array.
	 */
	private function get_settings() {
		$settings = get_option( 'seedprod_settings', array() );

		if ( is_string( $settings ) ) {
			$settings = json_decode( $settings, true );
		}

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return $settings;
	}
}

// Initialize the abilities.
new SeedProd_Lite_Abilities();
