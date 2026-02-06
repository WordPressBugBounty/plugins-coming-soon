<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
// Must load first.
require_once SEEDPROD_PLUGIN_PATH . 'app/functions-utils.php';

require_once SEEDPROD_PLUGIN_PATH . 'app/cpt.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/class-seedprod-notifications.php';

// Helper functions.
require_once SEEDPROD_PLUGIN_PATH . 'app/functions-inline-help.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/functions-wpforms.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/functions-rafflepress.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/functions-mypaykit.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/functions-envira-gallaries.php';

require_once SEEDPROD_PLUGIN_PATH . 'app/render-lp.php';



require_once SEEDPROD_PLUGIN_PATH . 'app/render-csp-mm.php';

require_once SEEDPROD_PLUGIN_PATH . 'app/nestednavmenu.php';

require_once SEEDPROD_PLUGIN_PATH . 'app/setup-wizard.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/backwards/backwards_compatibility.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/subscriber.php';
add_action( 'plugins_loaded', array( 'SeedProd_Lite_Render', 'get_instance' ) );
add_action( 'plugins_loaded', array( 'SeedProd_Notifications', 'get_instance' ) );

if ( is_admin() ) {
	// Admin only.
	require_once SEEDPROD_PLUGIN_PATH . 'app/settings.php';
	require_once SEEDPROD_PLUGIN_PATH . 'app/lpage.php';
	// Edit with SeedProd functionality moved to V2 admin: admin/includes/edit-with-seedprod-functions.php.
	require_once SEEDPROD_PLUGIN_PATH . 'app/functions-addons.php';
	// Review functionality has been moved to V2 admin: admin/includes/review-functions.php.
}

// Load on public and admin.
require_once SEEDPROD_PLUGIN_PATH . 'app/license.php';
require_once SEEDPROD_PLUGIN_PATH . 'app/includes/upgrade.php';


