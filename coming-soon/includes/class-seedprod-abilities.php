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
		// Available in both Pro and Lite.
		$this->register_get_status();

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
					'properties' => array(),
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
						'readonly' => true,
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

		// Theme is enabled if EITHER the new JSON format or legacy standalone option reports it on.
		$theme_enabled = ! empty( $settings['enable_seedprod_theme'] )
			|| ! empty( get_option( 'seedprod_theme_enabled', false ) );

		return array(
			'coming_soon_enabled'  => ! empty( $settings['enable_coming_soon_mode'] ),
			'coming_soon_page_id'  => absint( get_option( 'seedprod_coming_soon_page_id', 0 ) ),
			'maintenance_enabled'  => ! empty( $settings['enable_maintenance_mode'] ),
			'maintenance_page_id'  => absint( get_option( 'seedprod_maintenance_mode_page_id', 0 ) ),
			'theme_enabled'        => $theme_enabled,
			'license_active'       => (bool) get_option( 'seedprod_a', false ),
			'license_type'         => get_option( 'seedprod_license_name', '' ),
			'version'              => SEEDPROD_VERSION,
			'build'                => SEEDPROD_BUILD,
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
