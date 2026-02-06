<?php
/**
 * SeedProd Lite init file.
 *
 * @package    SeedProd
 * @subpackage SeedProd/includes
 *
 * phpcs:ignore WordPress.Files.FileName -- Legacy filename retained for compatibility.
 */

/**
 * Initialize the new WordPress-native admin pages
 *
 * This class defines all code necessary to run during the plugin's activation.
 * It's designed to work alongside the existing Vue-based system.
 *
 * @package    SeedProd
 * @subpackage SeedProd/includes
 */
class SeedProd_Lite_Init {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var SeedProd_Lite_Loader $loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string $plugin_name
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string $version
	 */
	protected $version;

	/**
	 * Define the core functionality of the new admin system.
	 */
	public function __construct() {
		$this->version     = SEEDPROD_VERSION;
		$this->plugin_name = 'coming-soon';

		$this->load_dependencies();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for the new admin system.
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-seedprod-loader.php';

		/**
		 * The class responsible for defining all actions in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-seedprod-admin.php';

		/**
		 * Load utility functions that contain the welcome redirect.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/includes/utility-functions.php';

		$this->loader = new SeedProd_Lite_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new SeedProd_Lite_Admin( $this->plugin_name, $this->version );

		// Enqueue scripts and styles.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Add menu items.
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		// Add plugin action links (Settings, Upgrade to Pro).
		$this->loader->add_filter( 'plugin_action_links', $plugin_admin, 'add_plugin_action_links', 10, 2 );

		// Welcome screen activation redirect (V2).
		// Note: This is a standalone function, not a class method, so we add it directly.
		add_action( 'admin_init', 'seedprod_lite_v2_welcome_screen_do_activation_redirect' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks.
	 *
	 * @return SeedProd_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}
