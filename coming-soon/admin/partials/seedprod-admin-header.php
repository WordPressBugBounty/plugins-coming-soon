<?php
/**
 * Admin header partial for SeedProd
 *
 * This partial displays the consistent header across all admin pages.
 *
 * @param string $page_title The title to display in the header
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/partials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load admin functions if not already loaded.
if ( ! function_exists( 'seedprod_lite_get_support_link' ) ) {
	require_once plugin_dir_path( __DIR__ ) . 'admin-functions.php';
}

// Check if we're in Lite view.
$is_lite_view = seedprod_lite_v2_is_lite_view();

// Get the page title from the passed variable or use a default.
$page_title = isset( $page_title ) ? $page_title : __( 'Dashboard', 'coming-soon' );

// Get the current page context for UTM tracking.
$page_context = seedprod_lite_get_admin_page_context();

// Check license status for Pro version.
$show_license_warning = false;
$is_lite_view         = seedprod_lite_v2_is_lite_view();
if ( ! $is_lite_view ) {
	$seedprod_a  = get_option( 'seedprod_a' );
	$license_key = get_option( 'seedprod_api_key' );
	// Show warning if no license or invalid license.
	if ( empty( $seedprod_a ) || empty( $license_key ) ) {
		// Don't show warning on dashboard when it's showing the welcome/activation screen.
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for display logic.
		if ( 'seedprod_lite' !== $current_page ) {
			// Only show on non-dashboard pages (Landing Pages, Website Builder, Settings, etc.).
			$show_license_warning = true;
		}
	}
}

// Get notifications.
$notifications        = array();
$notification_to_show = null;
if ( class_exists( 'SeedProd_Notifications' ) ) {
	$n                  = SeedProd_Notifications::get_instance();
	$notifications      = $n->get();
	$notification_count = $n->get_count();

	// Check if notifications are disabled.
	$seedprod_app_settings = get_option( 'seedprod_app_settings' );
	if ( ! empty( $seedprod_app_settings ) ) {
		$seedprod_app_settings = json_decode( stripslashes( $seedprod_app_settings ) );
		if ( isset( $seedprod_app_settings->disable_seedprod_notification ) &&
			true === $seedprod_app_settings->disable_seedprod_notification ) {
			$notification_count = 0;
			$notifications      = array();
		}
	}


	// Get the first non-dismissed notification to show.
	if ( ! empty( $notifications ) && is_array( $notifications ) ) {
		$notification_to_show = $notifications[0];
	}

	// Test mode: Show mock notification if test_notification=1 is in URL.
	if ( isset( $_GET['test_notification'] ) && '1' === $_GET['test_notification'] ) {
		$notification_to_show = array(
			'id'      => 'test-notification',
			'title'   => __( '🎉 NEW: AI Theme Builder Now Available!', 'coming-soon' ),
			'content' => __( 'Create stunning WordPress themes in seconds with our new AI-powered theme builder. Generate complete websites with just a few prompts!', 'coming-soon' ),
			'icon'    => '', // Will use default bell icon.
			'btns'    => array(
				'main' => array(
					'text'   => __( 'Try It Now', 'coming-soon' ),
					'url'    => seedprod_lite_get_external_link( 'https://www.seedprod.com/ai-theme-builder/', 'notification-test', 'notification' ),
					'target' => '_blank',
				),
				'alt'  => array(
					'text'   => __( 'Watch Demo', 'coming-soon' ),
					'url'    => seedprod_lite_get_external_link( 'https://www.seedprod.com/ai-theme-builder-demo/', 'notification-test', 'notification' ),
					'target' => '_blank',
				),
			),
		);
	}
}
?>

<?php if ( $show_license_warning ) : ?>
<!-- License warning bar for Pro version -->
<div class="seedprod-license-warning-bar">
	<div class="seedprod-license-bar-content">
		<span class="seedprod-license-warning-text">
			<?php esc_html_e( 'License Activation Required', 'coming-soon' ); ?>
			<span class="seedprod-license-separator">•</span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_settings' ) ); ?>" class="seedprod-license-link">
				<?php esc_html_e( 'Activate now to unlock updates, support & all Pro features', 'coming-soon' ); ?>
			</a>
		</span>
	</div>
</div>
<?php endif; ?>

<?php if ( $is_lite_view ) : ?>
<!-- Upgrade bar for Lite version -->
<div class="seedprod-lite-upgrade-bar">
	<div class="seedprod-upgrade-bar-content">
		<div class="seedprod-upgrade-bar-message">
			<span class="seedprod-upgrade-icon">★</span>
			<span class="seedprod-upgrade-text">
				<strong><?php esc_html_e( 'You\'re using SeedProd Lite.', 'coming-soon' ); ?></strong>
				<?php esc_html_e( 'Unlock more features and grow your website faster with SeedProd!', 'coming-soon' ); ?>
			</span>
		</div>
		<div class="seedprod-upgrade-bar-action">
			<a href="<?php echo esc_url( seedprod_lite_get_upgrade_link( 'plugintopbar', 'liteplugin' ) ); ?>" target="_blank" rel="noopener" class="button button-primary seedprod-button-black">
				<?php esc_html_e( 'Upgrade to Pro', 'coming-soon' ); ?> &rarr;
			</a>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Header with logo and help -->
<div class="seedprod-dashboard-header">
	<div class="seedprod-header-content">
		<div class="seedprod-header-left">
			<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/seedprod-logo.svg' ); ?>" alt="SeedProd" class="seedprod-logo" />
			<span class="seedprod-header-separator">/</span>
			<h1 class="seedprod-page-title"><?php echo esc_html( $page_title ); ?></h1>
		</div>
		<div class="seedprod-header-right">
			<?php if ( $is_lite_view ) : ?>
				<?php // Lite users go to support upsell page. ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_support' ) ); ?>" class="seedprod-help-link">
					<span class="dashicons dashicons-editor-help"></span>
					<span class="seedprod-tooltip"><?php esc_html_e( 'Get Help', 'coming-soon' ); ?></span>
				</a>
			<?php else : ?>
				<?php // Pro users go directly to documentation. ?>
				<a href="<?php echo esc_url( seedprod_lite_get_support_link( '', 'WordPress', 'admin-header-' . $page_context ) ); ?>" target="_blank" rel="noopener" class="seedprod-help-link">
					<span class="dashicons dashicons-editor-help"></span>
					<span class="seedprod-tooltip"><?php esc_html_e( 'Get Help', 'coming-soon' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ( ! empty( $notification_to_show ) ) : ?>
<!-- Notification bar -->
<div class="seedprod-notification-bar notice notice-info" data-id="<?php echo esc_attr( $notification_to_show['id'] ); ?>">
	<div class="seedprod-notification-content">
		<div class="seedprod-notification-icon">
			<?php if ( ! empty( $notification_to_show['icon'] ) ) : ?>
				<img src="<?php echo esc_url( $notification_to_show['icon'] ); ?>" alt="<?php echo esc_attr( $notification_to_show['title'] ); ?>" />
			<?php else : ?>
				<span class="dashicons dashicons-bell"></span>
			<?php endif; ?>
		</div>
		
		<div class="seedprod-notification-message">
			<?php if ( ! empty( $notification_to_show['title'] ) ) : ?>
				<strong><?php echo esc_html( $notification_to_show['title'] ); ?></strong>
			<?php endif; ?>
			<?php if ( ! empty( $notification_to_show['content'] ) ) : ?>
				<div class="seedprod-notification-text">
					<?php echo wp_kses_post( $notification_to_show['content'] ); ?>
				</div>
			<?php endif; ?>
		</div>
		
		<?php if ( ! empty( $notification_to_show['btns'] ) ) : ?>
			<div class="seedprod-notification-actions">
				<?php if ( ! empty( $notification_to_show['btns']['main'] ) ) : ?>
					<a href="<?php echo esc_url( $notification_to_show['btns']['main']['url'] ); ?>" 
						class="button button-primary button-small" 
						<?php
						if ( ( ! empty( $notification_to_show['btns']['main']['target'] ) && '_blank' === $notification_to_show['btns']['main']['target'] ) || ( empty( $notification_to_show['btns']['main']['target'] ) && false === strpos( $notification_to_show['btns']['main']['url'], admin_url() ) ) ) :
							?>
							target="_blank" rel="noopener"<?php endif; ?>>
						<?php echo esc_html( $notification_to_show['btns']['main']['text'] ); ?>
					</a>
				<?php endif; ?>
				<?php if ( ! empty( $notification_to_show['btns']['alt'] ) ) : ?>
					<a href="<?php echo esc_url( $notification_to_show['btns']['alt']['url'] ); ?>" 
						class="button button-small" 
						<?php
						if ( ( ! empty( $notification_to_show['btns']['alt']['target'] ) && '_blank' === $notification_to_show['btns']['alt']['target'] ) || ( empty( $notification_to_show['btns']['alt']['target'] ) && false === strpos( $notification_to_show['btns']['alt']['url'], admin_url() ) ) ) :
							?>
							target="_blank" rel="noopener"<?php endif; ?>>
						<?php echo esc_html( $notification_to_show['btns']['alt']['text'] ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		
		<button type="button" class="seedprod-notification-dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice', 'coming-soon' ); ?>">
			<span class="dashicons dashicons-dismiss"></span>
		</button>
	</div>
</div>
<?php endif; ?>
