<?php
/**
 * Subscribers page template
 *
 * Shows subscriber management for Pro users and product education for Lite users.
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if this is the Lite view (or testing with test_lite=1).
$is_lite_view = seedprod_lite_v2_is_lite_view() || ( isset( $_GET['test_lite'] ) && '1' === $_GET['test_lite'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for display logic.

// If Lite view, show product education.
if ( $is_lite_view ) {
	if ( ! class_exists( 'SeedProd_Lite_Admin' ) ) {
		require_once plugin_dir_path( __DIR__ ) . 'class-seedprod-admin.php';
	}
	$admin = new SeedProd_Lite_Admin( 'coming-soon', SEEDPROD_VERSION );
	$admin->render_subscribers_education();
	return;
}

// Pro version: Show actual subscribers functionality.
?>
<div class="wrap">
	<div class="seedprod-dashboard-page">
		<?php
		$page_title = __( 'Subscribers', 'coming-soon' );
		require_once plugin_dir_path( __FILE__ ) . 'seedprod-admin-header.php';
		?>
		<div class="seedprod-dashboard-container">
			<div class="postbox seedprod-card">
				<div class="inside">
					<h2><?php esc_html_e( 'Email Subscribers', 'coming-soon' ); ?></h2>
					<p><?php esc_html_e( 'Manage email subscribers collected from your landing pages and coming soon pages.', 'coming-soon' ); ?></p>
					
					<!-- Placeholder for future subscriber management functionality -->
					<div class="notice notice-info">
						<p><?php esc_html_e( 'Subscriber management interface coming soon. Subscribers are currently stored in the database and can be accessed via the legacy interface.', 'coming-soon' ); ?></p>
					</div>
					
					<!-- Temporary link to old Vue interface if needed -->
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite#/subscribers' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'View in Legacy Interface', 'coming-soon' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
