<?php
/**
 * Welcome page for plugin activation
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/partials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get necessary data.
$is_pro         = ! seedprod_lite_v2_is_lite_view();
$page_slug      = $is_pro ? 'seedprod_lite' : 'seedprod_lite';
$site_token     = get_option( 'seedprod_token' );
$admin_email    = get_option( 'admin_email' );
$plugin_version = SEEDPROD_VERSION;
// Laravel wizard appends 'admin.php?page=seedprod_lite#/setup/{id}', so we only need base admin URL.
$admin_url = admin_url();

// Build SaaS wizard URL for Lite.
$wizard_url = '';
if ( ! $is_pro ) {
	$upgrade_url = seedprod_lite_v2_get_upgrade_url( 'onboarding', 'welcome' );

	// Build base URL without trailing slash.
	$base_url = untrailingslashit( SEEDPROD_WEB_API_URL );

	// Build wizard URL - Laravel expects return to be base64 encoded then URL encoded.
	$wizard_url = sprintf(
		'%s/setup-wizard-seedprod_lite?token=%s&return=%s&version=%s&utm_campaign=%s&email=%s&upgrade_to_pro_url=%s',
		$base_url,
		rawurlencode( $site_token ),
		rawurlencode( base64_encode( $admin_url ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Base64 required for Laravel API, not for obfuscation.
		rawurlencode( $plugin_version ),
		rawurlencode( 'onboarding_seedprod_lite' ),
		rawurlencode( $admin_email ),
		rawurlencode( $upgrade_url )
	);
}
?>

<div class="seedprod-welcome-page">
	<div class="seedprod-welcome-top">
		<div class="seedprod-welcome-container">
			<div class="seedprod-welcome-header">
			<img 
				src="<?php echo esc_url( SEEDPROD_PLUGIN_URL . 'public/svg/seedprod-logo.svg' ); ?>" 
				alt="SeedProd" 
				class="seedprod-welcome-logo"
			/>
			
			<p class="seedprod-welcome-subtitle">
				<?php esc_html_e( 'Thank you for choosing SeedProd - The Best Website Builder, Landing Page Builder, Coming Soon, Maintenance Mode & more...', 'coming-soon' ); ?>
			</p>
		</div>

		<div class="seedprod-welcome-content">
			<?php
				?>
				<!-- LITE Version: External wizard redirect -->
				<div class="seedprod-welcome-box">
					<h2>
					<?php
						esc_html_e( 'Use our setup wizard to get started in less than 2 minutes and unlock free templates!', 'coming-soon' );
					?>
					</h2>
					
					<div class="seedprod-welcome-actions">
						<a href="<?php echo esc_url( $wizard_url ); ?>" 
							class="button button-primary button-hero seedprod-button-primary seedprod-wizard-button"
							data-fallback-url="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite' ) ); ?>"
							rel="noopener noreferrer">
							<?php esc_html_e( 'Get Started', 'coming-soon' ); ?> →
						</a>
					</div>
				</div>
				
				<div class="seedprod-welcome-footer">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite' ) ); ?>" 
						class="seedprod-skip-link seedprod-exit-setup">
						<?php esc_html_e( '← Exit Setup', 'coming-soon' ); ?>
					</a>
					<p class="seedprod-welcome-note">
						<?php esc_html_e( 'Note: You will be transfered to an SeedProd.com to complete the setup wizard.', 'coming-soon' ); ?>
					</p>
				</div>
				<?php
			?>
		</div>
	</div>
</div>

<?php if ( ! $is_pro ) : ?>
<script>
jQuery(document).ready(function($) {
	// Check wizard service availability before redirecting
	$('.seedprod-wizard-button').on('click', function(e) {
		e.preventDefault();
		var $button = $(this);
		var wizardUrl = $button.attr('href');
		var fallbackUrl = $button.data('fallback-url');
		
		// Add loading state
		$button.addClass('disabled').html('<?php esc_html_e( 'Redirecting...', 'coming-soon' ); ?>');

		// TEMPORARILY DISABLED: Skip health check and redirect directly
		window.location.href = wizardUrl;
		return;

		// Check if wizard service is available using AJAX to avoid CORS
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_check_wizard_availability',
				nonce: '<?php echo esc_js( wp_create_nonce( 'seedprod_lite_v2_check_wizard_availability' ) ); ?>'
			},
			timeout: 5000, // 5 second timeout
			success: function(response) {
				if (response.success && response.data.available) {
					// Service is available, redirect to wizard
					window.location.href = wizardUrl;
				} else {
					// Service unavailable, go to dashboard
					window.location.href = fallbackUrl;
				}
			},
			error: function() {
				// Error checking, assume service is down, go to dashboard
				window.location.href = fallbackUrl;
			}
		});
	});
	
	// Exit setup confirmation for Lite version only
	$('.seedprod-exit-setup').on('click', function(e) {
		e.preventDefault();
		var confirmText =
		<?php
		echo wp_json_encode(
			__( "Are you sure you want to exit the setup wizard?\n\nYou will miss out on our free templates. 😬", 'coming-soon' )
		);
		?>
		;

		if (confirm(confirmText)) {
			// Set dismiss option via AJAX before redirecting
			var redirectUrl = $(this).attr('href');
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'seedprod_lite_v2_dismiss_setup_wizard',
					_ajax_nonce: '<?php echo esc_js( wp_create_nonce( 'seedprod_v2_nonce' ) ); ?>'
				},
				success: function() {
					window.location.href = redirectUrl;
				},
				error: function() {
					// Redirect even if AJAX fails
					window.location.href = redirectUrl;
				}
			});
		}
	});
});
</script>
<?php endif; ?>
