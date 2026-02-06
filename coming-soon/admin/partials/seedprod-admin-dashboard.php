<?php
/**
 * Dashboard page for SeedProd
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/partials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if we have a valid license (Pro version).
$license_key       = get_option( 'seedprod_api_key' );
$license_status    = get_option( 'seedprod_api_message' );
$has_valid_license = ! empty( $license_key ) && ! empty( get_option( 'seedprod_api_token' ) );
// Use utility function to check for Lite view (includes test parameter).
$is_lite_view = seedprod_lite_v2_is_lite_view();
$is_pro       = ! $is_lite_view;

// Check if returning from wizard (query parameter format).
$wizard_id = isset( $_GET['wizard_id'] ) ? sanitize_text_field( wp_unslash( $_GET['wizard_id'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for display logic.
// Note: Laravel currently returns admin.php?page=seedprod_lite#/setup/{wizard_id}.
// We'll handle the hash format in JavaScript since PHP can't read URL fragments.

?>

<div class="seedprod-dashboard-page <?php echo ( $is_pro && ! $has_valid_license ) ? 'seedprod-license-activation-view' : ''; ?> <?php echo $is_lite_view ? 'seedprod-lite' : ''; ?>">
	<?php
	// Include header with page title.
	$page_title = __( 'Dashboard', 'coming-soon' );
	require_once plugin_dir_path( __FILE__ ) . 'seedprod-admin-header.php';
	?>

	<div class="seedprod-dashboard-container">
		<?php if ( $is_pro && ! $has_valid_license ) : ?>
			<!-- Welcome section for new Pro users -->
			<div class="seedprod-welcome-section">
				<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/seedprod-logo.svg' ); ?>" alt="SeedProd" class="seedprod-welcome-logo" />
				<h1 class="seedprod-welcome-title"><?php esc_html_e( '🎉 Welcome to SeedProd Lite!', 'coming-soon' ); ?></h1>
				<p class="seedprod-welcome-subtitle"><?php esc_html_e( 'The Next-Gen WordPress Website Builder', 'coming-soon' ); ?></p>
				<p class="seedprod-welcome-message">
					<?php esc_html_e( 'Build stunning websites in under 30 seconds with AI-powered design, drag & drop simplicity, and professional templates. No coding required.', 'coming-soon' ); ?>
				</p>
			</div>

			<!-- Pro version but no license activated -->
			<div class="postbox seedprod-card seedprod-license-notice seedprod-license-activation-focused">
				<div class="inside">
					<div class="seedprod-activation-steps">
						<h2><?php esc_html_e( "Let's get started!", 'coming-soon' ); ?></h2>
						
						<div class="seedprod-step-indicator seedprod-step-active">
							<span class="seedprod-step-number">1</span>
							<span class="seedprod-step-label"><?php esc_html_e( 'Activate your license to unlock all features', 'coming-soon' ); ?></span>
						</div>
						
						<div class="seedprod-step-indicator seedprod-step-upcoming">
							<span class="seedprod-step-number">2</span>
							<span class="seedprod-step-label"><?php esc_html_e( 'Create your first stunning page', 'coming-soon' ); ?></span>
						</div>
					</div>
					
					<form id="seedprod-license-form" class="seedprod-license-form">
						<div class="seedprod-license-input-group">
							<input 
								type="password" 
								id="seedprod-license-key" 
								name="license_key" 
								placeholder="<?php esc_attr_e( 'Enter your license key here', 'coming-soon' ); ?>"
								class="regular-text seedprod-license-input"
							/>
							<button type="submit" class="button button-primary button-hero seedprod-button-primary">
								<span class="button-text"><?php esc_html_e( 'Activate License', 'coming-soon' ); ?></span>
								<span class="button-spinner" style="display:none;">
									<span class="dashicons dashicons-update-alt"></span>
								</span>
							</button>
						</div>
						
						<!-- Inline message area -->
						<div id="seedprod-license-message" class="seedprod-license-message"></div>
					</form>
					
					<p class="seedprod-license-help">
						<?php
						printf(
							/* translators: %1$s: link to account page */
							wp_kses_post( __( "Don't have a license key or need to renew? Visit %1\$s", 'coming-soon' ) ),
							'<a href="' . esc_url( seedprod_lite_get_external_link( 'https://www.seedprod.com/', 'admin-license-help', 'proplugin' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'seedprod.com', 'coming-soon' ) . '</a>'
						);
						?>
					</p>
				</div>
			</div>
			
			<!-- Quick actions section -->
			<div class="seedprod-quick-actions">
				<a href="<?php echo esc_url( seedprod_lite_get_external_link( 'https://www.seedprod.com/docs/', 'dashboard-quick-actions', seedprod_lite_v2_is_lite_view() ? 'liteplugin' : 'proplugin' ) ); ?>" target="_blank" rel="noopener" class="seedprod-quick-action">
					<span class="dashicons dashicons-book-alt"></span>
					<?php esc_html_e( 'Documentation', 'coming-soon' ); ?>
				</a>
				<a href="https://www.youtube.com/seedprod" target="_blank" rel="noopener" class="seedprod-quick-action">
					<span class="dashicons dashicons-video-alt3"></span>
					<?php esc_html_e( 'Video Tutorials', 'coming-soon' ); ?>
				</a>
				<a href="https://www.facebook.com/groups/wpbeginner" target="_blank" rel="noopener" class="seedprod-quick-action">
					<span class="dashicons dashicons-groups"></span>
					<?php esc_html_e( 'Community', 'coming-soon' ); ?>
				</a>
			</div>
			<?php
		else :
			// Get stats data using V2 function.
			$stats = seedprod_lite_v2_get_dashboard_stats();

			// Extract stats for easier use in template.
			$coming_soon_count     = $stats['coming_soon_count'];
			$maintenance_count     = $stats['maintenance_count'];
			$landing_pages_count   = $stats['landing_pages_count'];
			$theme_templates_count = $stats['theme_templates_count'];
			$total_subscribers     = $stats['total_subscribers'];
			$recent_subscribers    = $stats['recent_subscribers'];

			// Check if theme builder is enabled (checks both old and new format).
			$theme_builder_enabled = seedprod_lite_v2_is_theme_enabled();
			?>
			<!-- Main Dashboard Content -->
			<div class="seedprod-dashboard-content">
				<!-- Top row: Setup & Status | What's New -->
				<div class="seedprod-dashboard-row">
					<!-- Setup & Status Widget (Left) -->
					<div class="postbox seedprod-card">
						<h2 class="hndle">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php esc_html_e( 'Setup & Status', 'coming-soon' ); ?>
						</h2>
						<div class="inside">
						<?php
						// Check if user just activated their license.
						$just_activated = isset( $_GET['activated'] ) && 'true' === $_GET['activated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for display logic.
						if ( true === $just_activated ) :
							?>
								<div class="seedprod-welcome-activated">
									<h3>🎉 <?php esc_html_e( 'Welcome to SeedProd Lite!', 'coming-soon' ); ?></h3>
									<p><?php esc_html_e( 'Your license is activated! Now choose what you\'d like to create first:', 'coming-soon' ); ?></p>
								</div>
							<?php else : ?>
								<p class="seedprod-setup-intro"><?php esc_html_e( 'Get your website set up quickly with these essential features:', 'coming-soon' ); ?></p>
							<?php endif; ?>
							
							<?php
							// Use stats from V2 function.
							$csp_id           = $stats['csp_id'];
							$csp_setup_status = $stats['csp_setup_status'];
							$csp_active       = $stats['csp_active'];

							$mmp_id     = $stats['mmp_id'];
							$mmp_active = $stats['mmp_active'];

							$loginp_id     = $stats['loginp_id'];
							$loginp_active = $stats['loginp_active'];

							$p404_id     = $stats['p404_id'];
							$p404_active = $stats['p404_active'];

							// Check if returning from wizard (URL parameter indicates wizard completion).
							$is_wizard_return = isset( $_GET['seedprod_wizard_complete'] ) || isset( $_GET['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for display logic.
							?>
							
							<!-- Coming Soon Mode -->
							<div class="seedprod-setup-item">
								<span class="dashicons dashicons-clock seedprod-setup-icon <?php echo $csp_id && $csp_active ? 'active' : ''; ?>"></span>
								<div class="seedprod-setup-item-content">
									<div class="seedprod-setup-item-header">
										<h3 class="seedprod-setup-item-title">
											<span class="seedprod-setup-title-text">
												<?php esc_html_e( 'Coming Soon Mode', 'coming-soon' ); ?>
												<span class="dashicons dashicons-info seedprod-info-icon"></span>
												<span class="seedprod-tooltip seedprod-tooltip-large"><?php esc_html_e( 'A Coming Soon Page will hide your site from public but you\'ll still be able to see it and work on it if logged in.', 'coming-soon' ); ?></span>
											</span>
										</h3>
										<div class="seedprod-setup-item-controls">
											<?php if ( $csp_id && $csp_active ) : ?>
												<span class="seedprod-status-badge seedprod-status-active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
											<?php elseif ( $csp_id && ! $csp_active ) : ?>
												<span class="seedprod-status-badge seedprod-status-inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
											<?php endif; ?>
											<?php if ( $csp_id ) : ?>
												<label class="seedprod-switch">
													<input type="checkbox" class="seedprod-toggle" data-mode="coming_soon" <?php checked( $csp_active ); ?>>
													<span class="seedprod-slider"></span>
												</label>
											<?php endif; ?>
											<?php if ( ! $csp_id ) : ?>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_template_selection&type=cs&name=' . rawurlencode( __( 'Coming Soon Page', 'coming-soon' ) ) . '&slug=coming-soon-page' ) ); ?>" class="button button-primary button-small seedprod-button-primary">
													<?php esc_html_e( 'Setup', 'coming-soon' ); ?>
												</a>
											<?php elseif ( $is_wizard_return && ! $csp_setup_status ) : ?>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $csp_id . '#/setup/cs' ) ); ?>" class="button button-primary button-small seedprod-button-primary seedprod-pulse">
													<span class="dashicons dashicons-arrow-right-alt"></span>
													<?php esc_html_e( 'Finish', 'coming-soon' ); ?>
												</a>
											<?php else : ?>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $csp_id ) ); ?>" class="button button-small seedprod-button-secondary">
													<?php esc_html_e( 'Edit', 'coming-soon' ); ?>
												</a>
												<a href="<?php echo esc_url( get_preview_post_link( $csp_id ) ); ?>" target="_blank" class="button button-small seedprod-button-secondary">
													<?php esc_html_e( 'Preview', 'coming-soon' ); ?>
												</a>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
							
							<!-- Maintenance Mode -->
							<div class="seedprod-setup-item">
								<span class="dashicons dashicons-admin-tools seedprod-setup-icon <?php echo $mmp_id && $mmp_active ? 'active' : ''; ?>"></span>
								<div class="seedprod-setup-item-content">
									<div class="seedprod-setup-item-header">
										<h3 class="seedprod-setup-item-title">
											<span class="seedprod-setup-title-text">
												<?php esc_html_e( 'Maintenance Mode', 'coming-soon' ); ?>
												<span class="dashicons dashicons-info seedprod-info-icon"></span>
												<span class="seedprod-tooltip seedprod-tooltip-large"><?php esc_html_e( 'A Maintenance Page will notify search engines that the site is unavailable.', 'coming-soon' ); ?></span>
											</span>
										</h3>
										<div class="seedprod-setup-item-controls">
											<?php if ( $mmp_id && $mmp_active ) : ?>
												<span class="seedprod-status-badge seedprod-status-active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
											<?php elseif ( $mmp_id && ! $mmp_active ) : ?>
												<span class="seedprod-status-badge seedprod-status-inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
											<?php endif; ?>
											<?php if ( $mmp_id ) : ?>
												<label class="seedprod-switch">
													<input type="checkbox" class="seedprod-toggle" data-mode="maintenance" <?php checked( $mmp_active ); ?>>
													<span class="seedprod-slider"></span>
												</label>
											<?php endif; ?>
											<?php if ( ! $mmp_id ) : ?>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_template_selection&type=mm&name=' . rawurlencode( __( 'Maintenance Mode Page', 'coming-soon' ) ) . '&slug=maintenance-mode-page' ) ); ?>" class="button button-primary button-small seedprod-button-primary">
													<?php esc_html_e( 'Setup', 'coming-soon' ); ?>
												</a>
											<?php else : ?>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $mmp_id ) ); ?>" class="button button-small seedprod-button-secondary">
													<?php esc_html_e( 'Edit', 'coming-soon' ); ?>
												</a>
												<a href="<?php echo esc_url( get_preview_post_link( $mmp_id ) ); ?>" target="_blank" class="button button-small seedprod-button-secondary">
													<?php esc_html_e( 'Preview', 'coming-soon' ); ?>
												</a>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
							
							<!-- Landing Pages -->
							<div class="seedprod-setup-item">
								<span class="dashicons dashicons-admin-page seedprod-setup-icon <?php echo $landing_pages_count > 0 ? 'active' : ''; ?>"></span>
								<div class="seedprod-setup-item-content">
									<div class="seedprod-setup-item-header">
										<h3 class="seedprod-setup-item-title">
											<span class="seedprod-setup-title-text">
												<?php esc_html_e( 'Landing Pages', 'coming-soon' ); ?>
												<span class="dashicons dashicons-info seedprod-info-icon"></span>
												<span class="seedprod-tooltip seedprod-tooltip-large"><?php esc_html_e( 'Landing Pages are meant to be standalone pages separate from the design of your site and theme.', 'coming-soon' ); ?></span>
											</span>
										</h3>
										<div class="seedprod-setup-item-controls">
										<?php if ( $landing_pages_count > 0 ) : ?>
											<span class="seedprod-count-badge"><?php echo esc_html( $landing_pages_count ); ?> <?php esc_html_e( 'pages', 'coming-soon' ); ?></span>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_landing_pages' ) ); ?>" class="button button-small seedprod-button-secondary">
												<?php esc_html_e( 'Manage', 'coming-soon' ); ?>
											</a>
										<?php else : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_landing_pages' ) ); ?>" class="button button-primary button-small seedprod-button-primary">
												<?php esc_html_e( 'Setup', 'coming-soon' ); ?>
											</a>
										<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
							
							<!-- Website Builder -->
							<div class="seedprod-setup-item <?php echo $is_lite_view ? 'seedprod-pro-feature' : ''; ?>">
								<span class="dashicons dashicons-admin-appearance seedprod-setup-icon <?php echo $theme_builder_enabled ? 'active' : ''; ?>"></span>
								<div class="seedprod-setup-item-content">
									<div class="seedprod-setup-item-header">
										<h3 class="seedprod-setup-item-title">
											<span class="seedprod-setup-title-text">
												<?php esc_html_e( 'Website Builder', 'coming-soon' ); ?>
												<?php if ( $is_lite_view ) : ?>
													<?php echo wp_kses_post( seedprod_lite_v2_get_pro_badge( 'inline' ) ); ?>
													<span class="seedprod-pro-value"><?php esc_html_e( 'Build custom themes', 'coming-soon' ); ?></span>
												<?php endif; ?>
												<span class="dashicons dashicons-info seedprod-info-icon"></span>
												<span class="seedprod-tooltip seedprod-tooltip-large"><?php esc_html_e( 'Build your entire Website. Create Headers, Footers, Pages, Posts, Archives, Sidebars, and more.', 'coming-soon' ); ?></span>
											</span>
										</h3>
										<div class="seedprod-setup-item-controls">
											<?php if ( $is_lite_view ) : ?>
												<?php echo wp_kses_post( seedprod_lite_v2_get_setup_item_upgrade( 'website-builder' ) ); ?>
											<?php else : ?>
												<?php if ( $theme_builder_enabled ) : ?>
													<span class="seedprod-status-badge seedprod-status-active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
													<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_website_builder' ) ); ?>" class="button button-small seedprod-button-secondary">
														<?php esc_html_e( 'Manage', 'coming-soon' ); ?>
													</a>
												<?php else : ?>
													<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_website_builder' ) ); ?>" class="button button-primary button-small seedprod-button-primary">
														<?php esc_html_e( 'Setup', 'coming-soon' ); ?>
													</a>
												<?php endif; ?>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
							
							<!-- Login Page -->
							<div class="seedprod-setup-item <?php echo $is_lite_view ? 'seedprod-pro-feature' : ''; ?>">
								<span class="dashicons dashicons-admin-network seedprod-setup-icon <?php echo $loginp_id && $loginp_active ? 'active' : ''; ?>"></span>
								<div class="seedprod-setup-item-content">
									<div class="seedprod-setup-item-header">
										<h3 class="seedprod-setup-item-title">
											<span class="seedprod-setup-title-text">
												<?php esc_html_e( 'Login Page', 'coming-soon' ); ?>
												<?php if ( $is_lite_view ) : ?>
													<?php echo wp_kses_post( seedprod_lite_v2_get_pro_badge( 'inline' ) ); ?>
													<span class="seedprod-pro-value"><?php esc_html_e( 'Match your brand', 'coming-soon' ); ?></span>
												<?php endif; ?>
												<span class="dashicons dashicons-info seedprod-info-icon"></span>
												<span class="seedprod-tooltip seedprod-tooltip-large"><?php esc_html_e( 'Create a custom Login page for your website.', 'coming-soon' ); ?></span>
											</span>
										</h3>
										<div class="seedprod-setup-item-controls">
											<?php if ( $is_lite_view ) : ?>
												<?php echo wp_kses_post( seedprod_lite_v2_get_setup_item_upgrade( 'login-page' ) ); ?>
											<?php else : ?>
												<?php if ( $loginp_id && $loginp_active ) : ?>
													<span class="seedprod-status-badge seedprod-status-active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
												<?php elseif ( $loginp_id && ! $loginp_active ) : ?>
													<span class="seedprod-status-badge seedprod-status-inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
												<?php endif; ?>
												<?php if ( $loginp_id ) : ?>
													<label class="seedprod-switch">
														<input type="checkbox" class="seedprod-toggle" data-mode="login" <?php checked( $loginp_active ); ?>>
														<span class="seedprod-slider"></span>
													</label>
												<?php endif; ?>
												<?php if ( ! $loginp_id ) : ?>
													<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_template_selection&type=loginp&name=' . rawurlencode( __( 'Login Page', 'coming-soon' ) ) . '&slug=login-page' ) ); ?>" class="button button-primary button-small seedprod-button-primary">
														<?php esc_html_e( 'Setup', 'coming-soon' ); ?>
													</a>
												<?php else : ?>
													<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $loginp_id ) ); ?>" class="button button-small seedprod-button-secondary">
														<?php esc_html_e( 'Edit', 'coming-soon' ); ?>
													</a>
													<a href="<?php echo esc_url( get_preview_post_link( $loginp_id ) ); ?>" target="_blank" class="button button-small seedprod-button-secondary">
														<?php esc_html_e( 'Preview', 'coming-soon' ); ?>
													</a>
												<?php endif; ?>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
							
							<!-- 404 Page -->
							<div class="seedprod-setup-item <?php echo $is_lite_view ? 'seedprod-pro-feature' : ''; ?>">
								<span class="dashicons dashicons-warning seedprod-setup-icon <?php echo $p404_id && $p404_active ? 'active' : ''; ?>"></span>
								<div class="seedprod-setup-item-content">
									<div class="seedprod-setup-item-header">
										<h3 class="seedprod-setup-item-title">
											<span class="seedprod-setup-title-text">
												<?php esc_html_e( '404 Page', 'coming-soon' ); ?>
												<?php if ( $is_lite_view ) : ?>
													<?php echo wp_kses_post( seedprod_lite_v2_get_pro_badge( 'inline' ) ); ?>
													<span class="seedprod-pro-value"><?php esc_html_e( 'Convert lost visitors', 'coming-soon' ); ?></span>
												<?php endif; ?>
												<span class="dashicons dashicons-info seedprod-info-icon"></span>
												<span class="seedprod-tooltip seedprod-tooltip-large"><?php esc_html_e( 'Create a custom 404 page for your website.', 'coming-soon' ); ?></span>
											</span>
										</h3>
										<div class="seedprod-setup-item-controls">
											<?php if ( $is_lite_view ) : ?>
												<?php echo wp_kses_post( seedprod_lite_v2_get_setup_item_upgrade( '404-page' ) ); ?>
											<?php else : ?>
												<?php if ( $p404_id && $p404_active ) : ?>
													<span class="seedprod-status-badge seedprod-status-active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
												<?php elseif ( $p404_id && ! $p404_active ) : ?>
													<span class="seedprod-status-badge seedprod-status-inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
												<?php endif; ?>
												<?php if ( $p404_id ) : ?>
													<label class="seedprod-switch">
														<input type="checkbox" class="seedprod-toggle" data-mode="404" <?php checked( $p404_active ); ?>>
														<span class="seedprod-slider"></span>
													</label>
												<?php endif; ?>
												<?php if ( ! $p404_id ) : ?>
													<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_template_selection&type=p404&name=' . rawurlencode( __( '404 Page', 'coming-soon' ) ) . '&slug=404-page' ) ); ?>" class="button button-primary button-small seedprod-button-primary">
														<?php esc_html_e( 'Setup', 'coming-soon' ); ?>
													</a>
												<?php else : ?>
													<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $p404_id ) ); ?>" class="button button-small seedprod-button-secondary">
														<?php esc_html_e( 'Edit', 'coming-soon' ); ?>
													</a>
													<a href="<?php echo esc_url( get_preview_post_link( $p404_id ) ); ?>" target="_blank" class="button button-small seedprod-button-secondary">
														<?php esc_html_e( 'Preview', 'coming-soon' ); ?>
													</a>
												<?php endif; ?>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
							
							<!-- Subscribers Stat -->
							<div class="seedprod-setup-item seedprod-subscribers-item <?php echo $is_lite_view ? 'seedprod-pro-feature' : ''; ?>">
								<span class="dashicons dashicons-groups seedprod-setup-icon"></span>
								<div class="seedprod-setup-item-content">
									<div class="seedprod-setup-item-header">
										<h3 class="seedprod-setup-item-title">
											<span class="seedprod-setup-title-text">
												<?php esc_html_e( 'Subscribers', 'coming-soon' ); ?>
												<?php if ( $is_lite_view ) : ?>
													<?php echo wp_kses_post( seedprod_lite_v2_get_pro_badge( 'inline' ) ); ?>
													<span class="seedprod-pro-value"><?php esc_html_e( 'Grow your email list', 'coming-soon' ); ?></span>
												<?php endif; ?>
											</span>
										</h3>
										<div class="seedprod-setup-item-controls">
											<?php if ( $is_lite_view ) : ?>
												<?php echo wp_kses_post( seedprod_lite_v2_get_setup_item_upgrade( 'subscribers' ) ); ?>
											<?php else : ?>
												<span class="seedprod-subscriber-count">
													<strong><?php echo esc_html( number_format( $total_subscribers ) ); ?></strong> <?php esc_html_e( 'total', 'coming-soon' ); ?>
													<?php if ( $recent_subscribers > 0 ) : ?>
														<span class="seedprod-stat-trend">+<?php echo esc_html( $recent_subscribers ); ?> <?php esc_html_e( 'this week', 'coming-soon' ); ?></span>
													<?php endif; ?>
												</span>
												<?php if ( $total_subscribers > 0 ) : ?>
													<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_settings&tab=subscribers' ) ); ?>" class="button button-small seedprod-button-secondary">
														<?php esc_html_e( 'View', 'coming-soon' ); ?>
													</a>
												<?php endif; ?>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Recommended Plugins Widget (Right) -->
					<div class="postbox seedprod-card">
						<h2 class="hndle">
							<span class="dashicons dashicons-admin-plugins"></span>
							<?php esc_html_e( 'Recommended Plugins', 'coming-soon' ); ?>
						</h2>
						<div class="inside">
						<div class="seedprod-plugins-grid">
							<?php
							// Get dashboard specific recommended plugins.
							$recommended_plugins = seedprod_lite_v2_get_dashboard_recommended_plugins();

							foreach ( $recommended_plugins as $plugin_key => $plugin_info ) :
								// Check if pro version is installed.
								$has_pro      = seedprod_lite_v2_has_pro_version( $plugin_key );
								$button_text  = seedprod_lite_v2_get_plugin_action_text( $plugin_info );
								$button_class = '';

								// Determine button class based on status.
								if ( $has_pro ) {
									$button_text  = __( 'PRO Version Installed', 'coming-soon' );
									$button_class = 'button-small button-disabled';
								} elseif ( 1 === $plugin_info['status_code'] ) {
									// Active - show deactivate as secondary button (WordPress .button + SeedProd modifier).
									$button_class = 'button-small seedprod-button-secondary';
								} elseif ( 2 === $plugin_info['status_code'] ) {
									// Inactive - show activate as primary button (WordPress .button-primary + SeedProd modifier).
									$button_class = 'button-primary button-small seedprod-button-primary';
								} else {
									// Not installed - show install as primary button (WordPress .button-primary + SeedProd modifier).
									$button_class = 'button-primary button-small seedprod-button-primary';
								}
								?>
								<div class="seedprod-plugin-card" data-plugin="<?php echo esc_attr( $plugin_key ); ?>">
									<div class="seedprod-plugin-card-header">
										<img src="<?php echo esc_url( $plugin_info['icon'] ); ?>" alt="<?php echo esc_attr( $plugin_info['name'] ); ?>" class="seedprod-plugin-icon">
										<h4 class="seedprod-plugin-name"><?php echo esc_html( $plugin_info['name'] ); ?></h4>
									</div>
									<p class="seedprod-plugin-desc"><?php echo esc_html( $plugin_info['desc'] ); ?></p>
									<div class="seedprod-plugin-action">
										<?php if ( ! $has_pro ) : ?>
											<button class="button <?php echo esc_attr( $button_class ); ?> seedprod-plugin-button"
												data-plugin-slug="<?php echo esc_attr( $plugin_info['slug'] ); ?>"
												data-plugin-id="<?php echo esc_attr( $plugin_key ); ?>"
												data-status="<?php echo esc_attr( $plugin_info['status_code'] ); ?>">
												<span class="button-text"><?php echo esc_html( $button_text ); ?></span>
												<span class="button-spinner" style="display:none;">
													<span class="dashicons dashicons-update-alt"></span>
												</span>
											</button>
										<?php endif; ?>
									</div>
								</div>
								<?php endforeach; ?>
							</div>
							
							<!-- View All Plugins Link -->
							<div class="seedprod-plugins-footer">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_settings&tab=recommended-plugins' ) ); ?>" class="seedprod-view-all-plugins">
									<?php esc_html_e( 'View All Recommended Plugins', 'coming-soon' ); ?> →
								</a>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Bottom row: Help & Resources | What's New -->
				<div class="seedprod-dashboard-row">
					<!-- Help & Resources Widget (Left) -->
					<div class="postbox seedprod-card">
						<h2 class="hndle">
							<span class="dashicons dashicons-sos"></span>
							<?php esc_html_e( 'Help & Resources', 'coming-soon' ); ?>
						</h2>
						<div class="inside">
							<div class="seedprod-resources-list">
								<!-- Documentation -->
								<div class="seedprod-resource-item">
									<span class="dashicons dashicons-book-alt seedprod-resource-icon"></span>
									<div class="seedprod-resource-content">
										<span class="seedprod-resource-title"><?php esc_html_e( 'Documentation', 'coming-soon' ); ?></span>
										<a href="<?php echo esc_url( seedprod_lite_get_external_link( 'https://www.seedprod.com/docs/', 'dashboard-resources-docs', seedprod_lite_v2_is_lite_view() ? 'liteplugin' : 'proplugin' ) ); ?>" target="_blank" class="seedprod-resource-action">
											<?php esc_html_e( 'View Docs', 'coming-soon' ); ?> →
										</a>
									</div>
								</div>
								
								<!-- Video Tutorials -->
								<div class="seedprod-resource-item">
									<span class="dashicons dashicons-video-alt3 seedprod-resource-icon"></span>
									<div class="seedprod-resource-content">
										<span class="seedprod-resource-title"><?php esc_html_e( 'Video Tutorials', 'coming-soon' ); ?></span>
										<a href="https://www.youtube.com/seedprod" target="_blank" class="seedprod-resource-action">
											<?php esc_html_e( 'Watch Videos', 'coming-soon' ); ?> →
										</a>
									</div>
								</div>
								
								<!-- Support Center -->
								<div class="seedprod-resource-item">
									<span class="dashicons dashicons-editor-help seedprod-resource-icon"></span>
									<div class="seedprod-resource-content">
										<span class="seedprod-resource-title"><?php esc_html_e( 'Support Center', 'coming-soon' ); ?></span>
										<a href="<?php echo esc_url( seedprod_lite_get_support_link( '', 'WordPress', 'dashboard-resources' ) ); ?>" target="_blank" class="seedprod-resource-action">
											<?php esc_html_e( 'Get Help', 'coming-soon' ); ?> →
										</a>
									</div>
								</div>
								
								<!-- Community -->
								<div class="seedprod-resource-item">
									<span class="dashicons dashicons-groups seedprod-resource-icon"></span>
									<div class="seedprod-resource-content">
										<span class="seedprod-resource-title"><?php esc_html_e( 'Community', 'coming-soon' ); ?></span>
										<a href="https://www.facebook.com/groups/wpbeginner" target="_blank" class="seedprod-resource-action">
											<?php esc_html_e( 'Join Community', 'coming-soon' ); ?> →
										</a>
									</div>
								</div>
								
								<!-- Request a Feature -->
								<div class="seedprod-resource-item">
									<span class="dashicons dashicons-admin-comments seedprod-resource-icon"></span>
									<div class="seedprod-resource-content">
										<span class="seedprod-resource-title"><?php esc_html_e( 'Request a Feature', 'coming-soon' ); ?></span>
										<a href="<?php echo esc_url( seedprod_lite_get_external_link( 'https://www.seedprod.com/suggest-a-feature/', 'plugin-dashboard', 'suggest-a-feature' ) ); ?>" target="_blank" class="seedprod-resource-action">
											<?php esc_html_e( 'Suggest', 'coming-soon' ); ?> →
										</a>
									</div>
								</div>
								
								<!-- Settings -->
								<div class="seedprod-resource-item">
									<span class="dashicons dashicons-admin-generic seedprod-resource-icon"></span>
									<div class="seedprod-resource-content">
										<span class="seedprod-resource-title"><?php esc_html_e( 'Settings', 'coming-soon' ); ?></span>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_settings' ) ); ?>" class="seedprod-resource-action">
											<?php esc_html_e( 'Manage', 'coming-soon' ); ?> →
										</a>
									</div>
								</div>
								
								<!-- Changelog -->
								<div class="seedprod-resource-item">
									<span class="dashicons dashicons-megaphone seedprod-resource-icon"></span>
									<div class="seedprod-resource-content">
										<span class="seedprod-resource-title"><?php esc_html_e( 'Changelog', 'coming-soon' ); ?></span>
										<a href="<?php echo esc_url( seedprod_lite_get_external_link( 'https://www.seedprod.com/docs/changelog/', 'dashboard-resources-changelog', seedprod_lite_v2_is_lite_view() ? 'liteplugin' : 'proplugin' ) ); ?>" target="_blank" class="seedprod-resource-action">
											<?php esc_html_e( 'View Updates', 'coming-soon' ); ?> →
										</a>
									</div>
								</div>
							</div>

							<!-- Fun Fact -->
							<div class="seedprod-fun-fact">
								<div class="seedprod-fun-fact-content">
									<span class="dashicons dashicons-lightbulb"></span>
									<p>
										<strong><?php esc_html_e( 'Did You Know?', 'coming-soon' ); ?></strong>
										<?php esc_html_e( "We've been part of the WordPress community since 2011, growing from a simple coming soon plugin to help over 1 million users build beautiful websites!", 'coming-soon' ); ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_settings&tab=about' ) ); ?>" style="margin-left: 5px;">
											<?php esc_html_e( 'Learn More →', 'coming-soon' ); ?>
										</a>
									</p>
								</div>
							</div>
						</div>
					</div>

					<!-- What's New Widget (Right) -->
					<div class="postbox seedprod-card">
						<h2 class="hndle">
							<span class="dashicons dashicons-megaphone"></span>
							<?php esc_html_e( "What's New", 'coming-soon' ); ?>
						</h2>
					<div class="inside">
						<?php
						// Get only 2 news items from RSS feed.
						$news_items = seedprod_lite_v2_get_news_feed( 2 );                           if ( ! empty( $news_items ) ) :
							?>
								<div class="seedprod-news-items">
									<?php foreach ( $news_items as $item ) : ?>
										<div class="seedprod-news-item <?php echo ! empty( $item['thumbnail'] ) ? 'has-thumbnail' : ''; ?>">
											<?php if ( ! empty( $item['thumbnail'] ) ) : ?>
												<div class="seedprod-news-thumbnail">
													<img src="<?php echo esc_url( $item['thumbnail'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" />
												</div>
											<?php endif; ?>
											<div class="seedprod-news-content">
												<h4><?php echo wp_kses_post( $item['title'] ); ?></h4>
												<?php if ( ! empty( $item['description'] ) ) : ?>
													<p class="seedprod-news-excerpt"><?php echo wp_kses_post( $item['description'] ); ?></p>
												<?php endif; ?>
												<div class="seedprod-news-meta">
													<?php if ( ! empty( $item['date'] ) ) : ?>
														<span class="seedprod-news-date"><?php echo esc_html( $item['date'] ); ?></span>
													<?php endif; ?>
													<a href="<?php echo esc_url( $item['link'] ); ?>" target="_blank" rel="noopener">
														<?php esc_html_e( 'Read More →', 'coming-soon' ); ?>
													</a>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
								<div class="seedprod-news-footer">
									<a href="<?php echo esc_url( seedprod_lite_get_external_link( 'https://www.seedprod.com/blog/', 'dashboard-blog-header', seedprod_lite_v2_is_lite_view() ? 'liteplugin' : 'proplugin' ) ); ?>" target="_blank" rel="noopener" class="seedprod-view-all-link">
										<?php esc_html_e( 'View All Articles →', 'coming-soon' ); ?>
									</a>
								</div>
							<?php else : ?>
								<p><?php esc_html_e( 'Stay updated with the latest features and tips!', 'coming-soon' ); ?></p>
								<a href="<?php echo esc_url( seedprod_lite_get_external_link( 'https://www.seedprod.com/blog/', 'dashboard-blog-button', seedprod_lite_v2_is_lite_view() ? 'liteplugin' : 'proplugin' ) ); ?>" target="_blank" rel="noopener" class="button button-secondary">
									<?php esc_html_e( 'Visit Our Blog', 'coming-soon' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>

<!-- Wizard Completion Modal (shown via JavaScript if wizard_id detected) -->
<div id="seedprod-wizard-completion-modal" class="seedprod-modal-overlay" style="display: none;">
	<div class="seedprod-modal-content">
		<!-- Processing State -->
		<div id="seedprod-wizard-processing" class="seedprod-wizard-state">
			<h2><?php esc_html_e( 'Finishing Up!', 'coming-soon' ); ?></h2>
			<p><?php esc_html_e( 'Please do not refresh or exit this page until this process is complete.', 'coming-soon' ); ?></p>
			<div class="seedprod-spinner-wrapper">
				<span class="spinner is-active"></span>
			</div>
		</div>

		<!-- Plugin Installation State -->
		<div id="seedprod-wizard-plugins" class="seedprod-wizard-state" style="display: none;">
			<h2><?php esc_html_e( 'Setup Almost Complete!', 'coming-soon' ); ?></h2>
			<p><?php esc_html_e( 'We recommend installing these free plugins:', 'coming-soon' ); ?></p>
			<div id="seedprod-recommended-plugins-list"></div>
			<div class="seedprod-modal-buttons">
				<button id="seedprod-install-plugins" class="button button-primary">
					<?php esc_html_e( 'Install & Activate Now', 'coming-soon' ); ?>
				</button>
				<button id="seedprod-skip-plugins" class="button button-secondary">
					<?php esc_html_e( "I'll do it later", 'coming-soon' ); ?>
				</button>
			</div>
		</div>

		<!-- Success State -->
		<div id="seedprod-wizard-success" class="seedprod-wizard-state" style="display: none;">
			<h2><?php esc_html_e( '🎉 Setup Complete!', 'coming-soon' ); ?></h2>
			<p id="seedprod-success-message"></p>
			<div class="seedprod-modal-buttons">
				<a id="seedprod-edit-page-button" href="#" class="button button-primary">
					<?php esc_html_e( 'Finish Setup', 'coming-soon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Go to Dashboard', 'coming-soon' ); ?>
				</a>
			</div>
		</div>

		<!-- Error State -->
		<div id="seedprod-wizard-error" class="seedprod-wizard-state" style="display: none;">
			<h2><?php esc_html_e( 'Setup Error', 'coming-soon' ); ?></h2>
			<p id="seedprod-error-message"></p>
			<div class="seedprod-modal-buttons">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Go to Dashboard', 'coming-soon' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Check for wizard ID in multiple formats
	var wizardId = '<?php echo esc_js( $wizard_id ); ?>'; // Query parameter format
	
	// Check for hash format (Laravel returns: #/setup/{wizard_id})
	if (!wizardId && window.location.hash) {
		var hashMatch = window.location.hash.match(/#\/setup\/(\d+)/);
		if (hashMatch && hashMatch[1]) {
			wizardId = hashMatch[1];
		}
	}
	
	if (wizardId) {
		// Show modal immediately
		$('#seedprod-wizard-completion-modal').show();
		$('#seedprod-wizard-processing').show();
		
		// Call complete setup wizard
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_complete_setup_wizard',
				wizard_id: wizardId,
				_ajax_nonce: '<?php echo esc_js( wp_create_nonce( 'seedprod_lite_v2_complete_setup_wizard' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					handleWizardSuccess(response.data);
				} else {
					showWizardError(response.data ? response.data.message : '<?php esc_html_e( 'There was an issue completing the setup. Please refresh the page and try again.', 'coming-soon' ); ?>');
				}
			},
			error: function() {
				showWizardError('<?php esc_html_e( 'Connection error. Please refresh the page and try again.', 'coming-soon' ); ?>');
			}
		});
	}
	
	function handleWizardSuccess(data) {
		// Hide processing
		$('#seedprod-wizard-processing').hide();
		
		// Check if there are plugins to install
		if (data.options && data.options.length > 0) {
			showPluginOptions(data);
		} else {
			showSuccessState(data);
		}
	}
	
	function showPluginOptions(data) {
		// Store data for later use
		window.wizardData = data;
		
		// Parse plugin options
		var plugins = [];
		if (data.options.includes('rafflepress')) plugins.push('RafflePress');
		if (data.options.includes('allinoneseo')) plugins.push('All in One SEO');
		if (data.options.includes('wpforms')) plugins.push('WPForms');
		if (data.options.includes('optinmonster')) plugins.push('OptinMonster');
		if (data.options.includes('ga')) plugins.push('MonsterInsights');
		
		if (plugins.length > 0) {
			$('#seedprod-recommended-plugins-list').html('<ul><li>' + plugins.join('</li><li>') + '</li></ul>');
			$('#seedprod-wizard-plugins').show();
		} else {
			showSuccessState(data);
		}
	}
	
	function showSuccessState(data) {
		$('#seedprod-wizard-plugins').hide();
		
		// Set success message based on page type
		var message = '<?php esc_html_e( 'Your page has been created successfully!', 'coming-soon' ); ?>';
		if (data.page_type) {
			switch(data.page_type) {
				case 'cs':
					message = '<?php esc_html_e( 'Your Coming Soon page is ready!', 'coming-soon' ); ?>';
					break;
				case 'mm':
					message = '<?php esc_html_e( 'Your Maintenance Mode page is ready!', 'coming-soon' ); ?>';
					break;
				case 'p404':
					message = '<?php esc_html_e( 'Your 404 page is ready!', 'coming-soon' ); ?>';
					break;
				case 'loginpage':
					message = '<?php esc_html_e( 'Your Login page is ready!', 'coming-soon' ); ?>';
					break;
			}
		}
		
		$('#seedprod-success-message').text(message);
		
		// Set edit button URL if page was created
		if (data.id) {
			var editUrl = '<?php echo esc_js( admin_url( 'admin.php' ) ); ?>?page=seedprod_lite_builder&id=' + data.id;
			$('#seedprod-edit-page-button').attr('href', editUrl).show();
		} else {
			$('#seedprod-edit-page-button').hide();
		}
		
		$('#seedprod-wizard-success').show();
		
		// Remove wizard_id from URL without reload (handles both query param and hash format)
		if (window.history && window.history.replaceState) {
			var newUrl = window.location.href.replace(/[?&]wizard_id=[^&]+/, '').replace(/#\/setup\/\d+/, '');
			window.history.replaceState({}, document.title, newUrl);
		}
	}
	
	function showWizardError(message) {
		$('#seedprod-wizard-processing').hide();
		$('#seedprod-wizard-plugins').hide();
		$('#seedprod-error-message').text(message);
		$('#seedprod-wizard-error').show();
	}
	
	// Install plugins button
	$('#seedprod-install-plugins').on('click', function() {
		var $button = $(this);
		$button.prop('disabled', true).text('<?php esc_html_e( 'Installing...', 'coming-soon' ); ?>');
		
		// Call install addon setup
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'seedprod_lite_v2_install_addon_setup',
				plugins: window.wizardData.options,
				nonce: '<?php echo esc_js( wp_create_nonce( 'seedprod_lite_v2_install_addon_setup' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					showSuccessState(window.wizardData);
				} else {
					alert('<?php esc_html_e( 'There was an error installing the plugins.', 'coming-soon' ); ?>');
					showSuccessState(window.wizardData);
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'There was an error installing the plugins.', 'coming-soon' ); ?>');
				showSuccessState(window.wizardData);
			}
		});
	});
	
	// Skip plugins button
	$('#seedprod-skip-plugins').on('click', function() {
		showSuccessState(window.wizardData);
	});
});
</script>

