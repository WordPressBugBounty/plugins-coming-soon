<?php
/**
 * Landing Pages Management Page
 *
 * @package    SeedProd_Lite
 * @subpackage SeedProd_Lite/admin/partials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load admin functions if not already loaded.
if ( ! function_exists( 'seedprod_lite_v2_is_lite_view' ) ) {
	require_once plugin_dir_path( __DIR__ ) . 'admin-functions.php';
}

// Check if we're in Lite view.
$is_lite_view = seedprod_lite_v2_is_lite_view();

// Get current settings (stored as JSON string).
$settings_json = get_option( 'seedprod_settings' );
if ( ! empty( $settings_json ) ) {
	$settings = json_decode( $settings_json, true );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
} else {
	$settings = array();
}

// Get Coming Soon page info.
$csp_id     = get_option( 'seedprod_coming_soon_page_id' );
$csp_active = ! empty( $settings['enable_coming_soon_mode'] );

// Get Maintenance Mode page info.
$mmp_id     = get_option( 'seedprod_maintenance_mode_page_id' );
$mmp_active = ! empty( $settings['enable_maintenance_mode'] );

// Get Login page info.
$loginp_id     = get_option( 'seedprod_login_page_id' );
$loginp_active = ! empty( $settings['enable_login_mode'] );

// Get 404 page info.
$p404_id     = get_option( 'seedprod_404_page_id' );
$p404_active = ! empty( $settings['enable_404_mode'] );

// Load the table class.
require_once plugin_dir_path( __DIR__ ) . 'includes/class-seedprod-landing-pages-table.php';

// Create an instance of our table class.
$landing_pages_table = new SeedProd_Landing_Pages_Table();

// Prepare table items.
$landing_pages_table->prepare_items();
?>

<div class="seedprod-dashboard-page seedprod-landing-pages-page <?php echo $is_lite_view ? 'seedprod-lite' : ''; ?>">
	<?php
	// Include header.
	$page_title = __( 'Landing Pages', 'coming-soon' );
	require_once plugin_dir_path( __FILE__ ) . 'seedprod-admin-header.php';
	?>
	
	<div class="seedprod-dashboard-container">
		<!-- Special Pages Section -->
		<h2 class="seedprod-section-title"><?php esc_html_e( 'Website Mode Pages', 'coming-soon' ); ?></h2>
		<p class="seedprod-section-description"><?php esc_html_e( 'Control what visitors see when your site is coming soon, under maintenance, or when they need to log in.', 'coming-soon' ); ?></p>
		
		<!-- Mode Cards Section -->
		<div class="seedprod-mode-cards-grid">
			<!-- Coming Soon Mode Card -->
			<div class="postbox seedprod-card seedprod-mode-card" data-mode="coming_soon">
				<div class="inside">
					<div class="seedprod-mode-card-icon">
						<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/cs-page.svg' ); ?>" alt="<?php esc_attr_e( 'Coming Soon Mode', 'coming-soon' ); ?>" />
					</div>
					<h3 class="seedprod-mode-card-title"><?php esc_html_e( 'Coming Soon Mode', 'coming-soon' ); ?></h3>
					<p class="seedprod-mode-card-description">
						<?php esc_html_e( 'The Coming Soon Page will be available to search engines if your site is not private.', 'coming-soon' ); ?>
					</p>
					<div class="seedprod-mode-card-actions">
						<?php if ( ! $csp_id ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_template_selection&type=cs&name=' . rawurlencode( __( 'Coming Soon Page', 'coming-soon' ) ) . '&slug=coming-soon-page' ) ); ?>" class="button button-primary seedprod-button-primary">
								<?php esc_html_e( 'Set up Coming Soon Page', 'coming-soon' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $csp_id ) ); ?>" class="button button-primary seedprod-button-primary">
								<?php esc_html_e( 'Edit Page', 'coming-soon' ); ?>
							</a>
							<a href="<?php echo esc_url( get_preview_post_link( $csp_id ) ); ?>" target="_blank" class="button seedprod-button-secondary">
								<?php esc_html_e( 'Preview', 'coming-soon' ); ?>
							</a>
						<?php endif; ?>
					</div>
					<?php if ( $csp_id ) : ?>
					<div class="seedprod-mode-card-toggle">
						<label class="seedprod-switch">
							<input type="checkbox" class="seedprod-toggle" data-mode="coming_soon" <?php checked( $csp_active ); ?>>
							<span class="seedprod-slider"></span>
						</label>
						<span class="seedprod-toggle-label">
							<?php if ( $csp_active ) : ?>
								<span class="active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
							<?php else : ?>
								<span class="inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
							<?php endif; ?>
						</span>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Maintenance Mode Card -->
			<div class="postbox seedprod-card seedprod-mode-card" data-mode="maintenance">
				<div class="inside">
					<div class="seedprod-mode-card-icon">
						<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/mm-page.svg' ); ?>" alt="<?php esc_attr_e( 'Maintenance Mode', 'coming-soon' ); ?>" />
					</div>
					<h3 class="seedprod-mode-card-title"><?php esc_html_e( 'Maintenance Mode', 'coming-soon' ); ?></h3>
					<p class="seedprod-mode-card-description">
						<?php esc_html_e( 'The Maintenance Mode Page will notify search engines that the site is unavailable.', 'coming-soon' ); ?>
					</p>
					<div class="seedprod-mode-card-actions">
						<?php if ( ! $mmp_id ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_template_selection&type=mm&name=' . rawurlencode( __( 'Maintenance Mode Page', 'coming-soon' ) ) . '&slug=maintenance-mode-page' ) ); ?>" class="button button-primary seedprod-button-primary">
								<?php esc_html_e( 'Set up Maintenance Page', 'coming-soon' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $mmp_id ) ); ?>" class="button button-primary seedprod-button-primary">
								<?php esc_html_e( 'Edit Page', 'coming-soon' ); ?>
							</a>
							<a href="<?php echo esc_url( get_preview_post_link( $mmp_id ) ); ?>" target="_blank" class="button seedprod-button-secondary">
								<?php esc_html_e( 'Preview', 'coming-soon' ); ?>
							</a>
						<?php endif; ?>
					</div>
					<?php if ( $mmp_id ) : ?>
					<div class="seedprod-mode-card-toggle">
						<label class="seedprod-switch">
							<input type="checkbox" class="seedprod-toggle" data-mode="maintenance" <?php checked( $mmp_active ); ?>>
							<span class="seedprod-slider"></span>
						</label>
						<span class="seedprod-toggle-label">
							<?php if ( $mmp_active ) : ?>
								<span class="active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
							<?php else : ?>
								<span class="inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
							<?php endif; ?>
						</span>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Login Page Card -->
			<div class="postbox seedprod-card seedprod-mode-card <?php echo $is_lite_view ? 'seedprod-pro-feature' : ''; ?>" data-mode="login">
				<div class="inside">
					<div class="seedprod-mode-card-icon">
						<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/login-page.svg' ); ?>" alt="<?php esc_attr_e( 'Login Page', 'coming-soon' ); ?>" />
					</div>
					<h3 class="seedprod-mode-card-title">
						<?php esc_html_e( 'Login Page', 'coming-soon' ); ?>
						<?php if ( $is_lite_view ) : ?>
							<?php echo wp_kses_post( seedprod_lite_v2_get_pro_badge( 'inline' ) ); ?>
						<?php endif; ?>
					</h3>
					<p class="seedprod-mode-card-description">
						<?php esc_html_e( 'Create a Custom Login Page for your website. Optionally replace the default login page.', 'coming-soon' ); ?>
					</p>
					<div class="seedprod-mode-card-actions">
						<?php if ( $is_lite_view ) : ?>
							<a href="<?php echo esc_url( seedprod_lite_get_upgrade_link( 'pluginloginpage', 'liteplugin' ) ); ?>" target="_blank" class="button button-primary seedprod-upgrade-button">
								<span class="dashicons dashicons-star-filled"></span>
								<?php esc_html_e( 'Upgrade to Pro', 'coming-soon' ); ?>
							</a>
						<?php else : ?>
							<?php if ( ! $loginp_id ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_template_selection&type=loginp&name=' . rawurlencode( __( 'Login Page', 'coming-soon' ) ) . '&slug=login-page' ) ); ?>" class="button button-primary seedprod-button-primary">
									<?php esc_html_e( 'Set up a Login Page', 'coming-soon' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $loginp_id ) ); ?>" class="button button-primary seedprod-button-primary">
									<?php esc_html_e( 'Edit Page', 'coming-soon' ); ?>
								</a>
								<a href="<?php echo esc_url( get_preview_post_link( $loginp_id ) ); ?>" target="_blank" class="button seedprod-button-secondary">
									<?php esc_html_e( 'Preview', 'coming-soon' ); ?>
								</a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
					<?php if ( ! $is_lite_view && $loginp_id ) : ?>
					<div class="seedprod-mode-card-toggle">
						<label class="seedprod-switch">
							<input type="checkbox" class="seedprod-toggle" data-mode="login" <?php checked( $loginp_active ); ?>>
							<span class="seedprod-slider"></span>
						</label>
						<span class="seedprod-toggle-label">
							<?php if ( $loginp_active ) : ?>
								<span class="active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
							<?php else : ?>
								<span class="inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
							<?php endif; ?>
						</span>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- 404 Page Card -->
			<div class="postbox seedprod-card seedprod-mode-card <?php echo $is_lite_view ? 'seedprod-pro-feature' : ''; ?>" data-mode="404">
				<div class="inside">
					<div class="seedprod-mode-card-icon">
						<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/404-page.svg' ); ?>" alt="<?php esc_attr_e( '404 Page', 'coming-soon' ); ?>" />
					</div>
				<h3 class="seedprod-mode-card-title">
					<?php esc_html_e( '404 Page', 'coming-soon' ); ?>
					<?php if ( $is_lite_view ) : ?>
						<?php echo wp_kses_post( seedprod_lite_v2_get_pro_badge( 'inline' ) ); ?>
					<?php endif; ?>
				</h3>
					<p class="seedprod-mode-card-description">
						<?php esc_html_e( 'Replace your default theme 404 page with a custom high converting 404 page.', 'coming-soon' ); ?>
					</p>
					<div class="seedprod-mode-card-actions">
						<?php if ( $is_lite_view ) : ?>
							<a href="<?php echo esc_url( seedprod_lite_get_upgrade_link( 'plugin404page', 'liteplugin' ) ); ?>" target="_blank" class="button button-primary seedprod-upgrade-button">
								<span class="dashicons dashicons-star-filled"></span>
								<?php esc_html_e( 'Upgrade to Pro', 'coming-soon' ); ?>
							</a>
						<?php else : ?>
							<?php if ( ! $p404_id ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_template_selection&type=p404&name=' . rawurlencode( __( '404 Page', 'coming-soon' ) ) . '&slug=404-page' ) ); ?>" class="button button-primary seedprod-button-primary">
									<?php esc_html_e( 'Set up a 404 Page', 'coming-soon' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_builder&id=' . $p404_id ) ); ?>" class="button button-primary seedprod-button-primary">
									<?php esc_html_e( 'Edit Page', 'coming-soon' ); ?>
								</a>
								<a href="<?php echo esc_url( get_preview_post_link( $p404_id ) ); ?>" target="_blank" class="button seedprod-button-secondary">
									<?php esc_html_e( 'Preview', 'coming-soon' ); ?>
								</a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
					<?php if ( ! $is_lite_view && $p404_id ) : ?>
					<div class="seedprod-mode-card-toggle">
						<label class="seedprod-switch">
							<input type="checkbox" class="seedprod-toggle" data-mode="404" <?php checked( $p404_active ); ?>>
							<span class="seedprod-slider"></span>
						</label>
						<span class="seedprod-toggle-label">
							<?php if ( $p404_active ) : ?>
								<span class="active"><?php esc_html_e( 'ACTIVE', 'coming-soon' ); ?></span>
							<?php else : ?>
								<span class="inactive"><?php esc_html_e( 'INACTIVE', 'coming-soon' ); ?></span>
							<?php endif; ?>
						</span>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Landing Pages Section -->
		<h2 class="seedprod-section-title"><?php esc_html_e( 'Landing Pages', 'coming-soon' ); ?></h2>
		<p class="seedprod-section-description"><?php esc_html_e( 'Create custom landing pages like sales pages, webinar pages, thank you pages, and more.', 'coming-soon' ); ?></p>
		
		<div class="seedprod-landing-pages-section">
			<div class="seedprod-section-header">
				<div class="seedprod-section-header-buttons">
					<button id="seedprod-show-new-page-form" class="button button-primary seedprod-button-primary">
						+ <?php esc_html_e( 'Add New Landing Page', 'coming-soon' ); ?>
					</button>
					<button id="seedprod-landing-import-export-btn" class="button seedprod-button-secondary">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Import/Export', 'coming-soon' ); ?>
					</button>
				</div>
				
				<!-- Inline New Page Form (Hidden by default) -->
				<div id="seedprod-new-page-inline">
					<div class="postbox seedprod-card">
						<div class="inside">
							<h3><?php esc_html_e( 'Create New Landing Page', 'coming-soon' ); ?></h3>
							<?php $slug_check_url = html_entity_decode( wp_nonce_url( 'admin-ajax.php?action=seedprod_lite_slug_exists', 'seedprod_lite_slug_exists' ) ); ?>
							<script type="text/javascript">
								var seedprod_slug_check_url = <?php echo wp_json_encode( esc_url_raw( $slug_check_url ) ); ?>;
							</script>
							<form id="seedprod-create-page-form" class="seedprod-inline-form">
								<table class="form-table">
									<tbody>
										<tr>
											<th scope="row">
												<label for="seedprod-page-name"><?php esc_html_e( 'Page Name', 'coming-soon' ); ?></label>
											</th>
											<td>
												<input type="text" 
														id="seedprod-page-name" 
														name="page_name" 
														class="regular-text" 
														placeholder="<?php esc_attr_e( 'My Awesome Landing Page', 'coming-soon' ); ?>" 
														required />
												<p class="description">
													<?php esc_html_e( 'Enter a descriptive name for your landing page.', 'coming-soon' ); ?>
												</p>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="seedprod-page-slug"><?php esc_html_e( 'Page URL', 'coming-soon' ); ?></label>
											</th>
											<td>
												<code><?php echo esc_url( home_url( '/' ) ); ?></code>
												<input type="text" 
														id="seedprod-page-slug" 
														name="page_slug" 
														class="regular-text code" 
														placeholder="<?php esc_attr_e( 'my-awesome-page', 'coming-soon' ); ?>" />
												<p class="description">
													<?php esc_html_e( 'The URL slug for your page. Leave blank to auto-generate from the page name.', 'coming-soon' ); ?>
												</p>
												<p id="seedprod-page-slug-error" class="description" style="display: none; color: #dc3232; font-weight: 600;">
													<?php esc_html_e( 'This page URL already exists. Please choose a unique page URL.', 'coming-soon' ); ?>
												</p>
											</td>
										</tr>
									</tbody>
								</table>
								<p class="submit">
									<button type="submit" class="button button-primary seedprod-button-primary">
										<?php esc_html_e( 'Next: Choose Template', 'coming-soon' ); ?>
									</button>
									<button type="button" id="seedprod-cancel-new-page" class="button seedprod-button-secondary">
										<?php esc_html_e( 'Cancel', 'coming-soon' ); ?>
									</button>
								</p>
							</form>
						</div>
					</div>
				</div>
			</div>

			<form id="seedprod-landing-pages-form" method="get">
				<input type="hidden" name="page" value="seedprod_lite_landing_pages" />
				
				<?php
				// Display search box (WordPress native).
				$landing_pages_table->search_box( __( 'Search Landing Pages', 'coming-soon' ), 'seedprod-landing-pages' );

				// Display the table with views and pagination.
				$landing_pages_table->views();
				$landing_pages_table->display();

				// Add upgrade row for Lite users.
				if ( $is_lite_view ) :
					?>
					<script type="text/javascript">
					jQuery(document).ready(function($) {
						// Use localized strings from seedprod_admin
						var strings = window.seedprod_admin && window.seedprod_admin.strings ? window.seedprod_admin.strings : {};

						// Add the upgrade row to the table for Lite users
						var upgradeRow = '<tr class="seedprod-upgrade-row">';
						upgradeRow += '<td class="column-cb"></td>';
						upgradeRow += '<td class="column-title" colspan="2" style="white-space: normal;">';
						upgradeRow += '<div style="display: flex; align-items: flex-start; gap: 5px;">';
						upgradeRow += '<span class="dashicons dashicons-megaphone" style="color: var(--seedprod-orange); flex-shrink: 0; margin-top: 2px;"></span>';
						upgradeRow += '<div>';
						upgradeRow += '<strong style="color: var(--seedprod-orange);">' + strings.pro_tip + '</strong> ';
						upgradeRow += strings.landing_pages_upgrade_message;
						upgradeRow += '</div>';
						upgradeRow += '</div>';
						upgradeRow += '</td>';
						upgradeRow += '<td class="column-status">';
						upgradeRow += '<a href="<?php echo esc_url( seedprod_lite_get_upgrade_link( 'landingpagestable', 'liteplugin' ) ); ?>" target="_blank" style="color: var(--seedprod-orange);">' + strings.learn_more + '</a>';
						upgradeRow += '</td>';
						upgradeRow += '<td class="column-date">';
						upgradeRow += '<a href="<?php echo esc_url( seedprod_lite_get_upgrade_link( 'landingpagestable', 'liteplugin' ) ); ?>" target="_blank" class="button button-small button-primary seedprod-upgrade-button" style="display: inline-flex; align-items: center;">';
						upgradeRow += '<span class="dashicons dashicons-star-filled" style="margin-right: 4px; font-size: 14px; width: 14px; height: 14px;"></span>';
						upgradeRow += strings.upgrade_to_pro;
						upgradeRow += '</a>';
						upgradeRow += '</td>';
						upgradeRow += '</tr>';

						// Check if we have a tbody in the table
						var $tbody = $('.wp-list-table tbody');
						if ($tbody.length > 0) {
							// Always append the upgrade row to the table
							// This preserves the "No landing pages found" message or any existing rows
							$tbody.append(upgradeRow);
						}
					});
					</script>
				<?php endif; ?>
			</form>
		</div>
	</div>
</div>

<!-- Landing Pages Import/Export Modal -->
<div id="seedprod-landing-import-export-modal" class="seedprod-modal" style="display: none;">
	<div class="seedprod-modal-content">
		<div class="seedprod-modal-header">
			<h2><?php esc_html_e( 'Import/Export Landing Pages', 'coming-soon' ); ?></h2>
			<button type="button" class="seedprod-modal-close" aria-label="<?php esc_attr_e( 'Close', 'coming-soon' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="seedprod-modal-body">
			<!-- Tab Navigation -->
			<div class="seedprod-import-export-tabs">
				<button type="button" class="seedprod-tab-button active" data-tab="export">
					<?php esc_html_e( 'Export', 'coming-soon' ); ?>
				</button>
				<button type="button" class="seedprod-tab-button" data-tab="import">
					<?php esc_html_e( 'Import', 'coming-soon' ); ?>
				</button>
			</div>
			
			<!-- Export Tab Content -->
			<div class="seedprod-tab-content" id="seedprod-landing-export-tab" style="display: block;">
				<div class="seedprod-export-section">
					<h3><?php esc_html_e( 'Export Landing Pages', 'coming-soon' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Export your landing pages including Coming Soon, Maintenance Mode, 404, and custom landing pages. The export will include all page settings, content, and associated images.', 'coming-soon' ); ?></p>

					<div class="seedprod-export-options" style="margin: 20px 0;">
						<label for="seedprod-export-page-select" style="display: block; margin-bottom: 10px;">
							<strong><?php esc_html_e( 'Select Pages to Export:', 'coming-soon' ); ?></strong>
						</label>
						<select id="seedprod-export-page-select" class="regular-text" style="min-width: 300px;">
							<option value="all"><?php esc_html_e( 'All Landing Pages', 'coming-soon' ); ?></option>
							<?php
							global $wpdb;
							$tablename      = $wpdb->prefix . 'posts';
							$meta_tablename = $wpdb->prefix . 'postmeta';

							// Get special page IDs first.
							$coming_soon_id = get_option( 'seedprod_coming_soon_page_id' );
							$maintenance_id = get_option( 'seedprod_maintenance_mode_page_id' );
							$login_id       = get_option( 'seedprod_login_page_id' );
							$fourohfour_id  = get_option( 'seedprod_404_page_id' );

							// Build list of special page IDs to include.
							$special_page_ids = array();
							if ( ! empty( $coming_soon_id ) ) {
								$special_page_ids[] = $coming_soon_id;
							}
							if ( ! empty( $maintenance_id ) ) {
								$special_page_ids[] = $maintenance_id;
							}
							if ( ! empty( $login_id ) ) {
								$special_page_ids[] = $login_id;
							}
							if ( ! empty( $fourohfour_id ) ) {
								$special_page_ids[] = $fourohfour_id;
							}

							// Query for landing pages - include multiple conditions to catch all pages:
							// 1. Pages with _seedprod_page_template_type = 'lp' (new landing pages).
							// 2. Pages with _seedprod_page = '1' (legacy/older landing pages).
							// 3. Special pages (CS, MM, Login, 404) by their specific IDs.

							// Build special pages condition separately.
							$special_pages_condition = '';
							if ( ! empty( $special_page_ids ) ) {
								$special_ids_string      = implode( ',', array_map( 'intval', $special_page_ids ) );
								$special_pages_condition = " OR p.ID IN ($special_ids_string)";
							}

							$sql = "SELECT DISTINCT p.ID, p.post_title, p.post_type, p.post_status, p.post_date, pm2.meta_value as page_type
									FROM $tablename p
									LEFT JOIN $meta_tablename pm ON (pm.post_id = p.ID AND pm.meta_key = '_seedprod_page_uuid')
									LEFT JOIN $meta_tablename pm2 ON (pm2.post_id = p.ID AND pm2.meta_key = '_seedprod_page_type')
									LEFT JOIN $meta_tablename pm4 ON (pm4.post_id = p.ID AND pm4.meta_key = '_seedprod_page_template_type')
									LEFT JOIN $meta_tablename pm5 ON (pm5.post_id = p.ID AND pm5.meta_key = '_seedprod_page')
									WHERE p.post_status != 'trash'
									AND p.post_type IN ('page', 'seedprod')
									AND (
										(p.post_type = 'page' AND pm4.meta_value = 'lp')
										OR (p.post_type = 'page' AND pm5.meta_value IS NOT NULL)
										$special_pages_condition
									)
									AND (pm.meta_value IS NOT NULL OR pm5.meta_value IS NOT NULL $special_pages_condition)
									ORDER BY p.post_date DESC";

							$pages = $wpdb->get_results( $sql ); // phpcs:ignore

							// DEBUG: Let's see what we're getting.
							if ( current_user_can( 'manage_options' ) && isset( $_GET['debug_landing_pages'] ) ) {
								echo '<h4>Debug: Landing Pages Query Results</h4>';
								echo '<pre>';
								echo 'SQL: ' . esc_html( $sql ) . "\n\n";
								echo 'Results (' . count( $pages ) . " found):\n";
								print_r( $pages ); // phpcs:ignore
								echo '</pre>';

								// Also show ALL SeedProd pages.
								$debug_sql     = "SELECT p.ID, p.post_title, p.post_type, p.post_status,
											pm1.meta_value as page_uuid,
											pm2.meta_value as page_type,
											pm3.meta_value as is_theme_template
											FROM $tablename p
											LEFT JOIN $meta_tablename pm1 ON (pm1.post_id = p.ID AND pm1.meta_key = '_seedprod_page_uuid')
											LEFT JOIN $meta_tablename pm2 ON (pm2.post_id = p.ID AND pm2.meta_key = '_seedprod_page_type')
											LEFT JOIN $meta_tablename pm3 ON (pm3.post_id = p.ID AND pm3.meta_key = '_seedprod_is_theme_template')
											WHERE pm1.meta_value IS NOT NULL
											ORDER BY p.ID";
								$debug_results = $wpdb->get_results( $wpdb->prepare( $debug_sql ) ); // phpcs:ignore
								echo '<h4>Debug: ALL SeedProd Pages</h4>';
								echo '<pre>';
								print_r( $debug_results ); // phpcs:ignore
								echo '</pre>';
							}

							foreach ( $pages as $page_item ) {
								$page_label = esc_html( $page_item->post_title );
								$page_type  = '';

								// Add page type indicator.
								if ( (int) $page_item->ID === (int) $coming_soon_id ) {
									$page_type   = 'cs';
									$page_label .= ' ' . __( '(Coming Soon)', 'coming-soon' );
								} elseif ( (int) $page_item->ID === (int) $maintenance_id ) {
									$page_type   = 'mm';
									$page_label .= ' ' . __( '(Maintenance Mode)', 'coming-soon' );
								} elseif ( (int) $page_item->ID === (int) $login_id ) {
									$page_type   = 'loginp';
									$page_label .= ' ' . __( '(Login Page)', 'coming-soon' );
								} elseif ( (int) $page_item->ID === (int) $fourohfour_id ) {
									$page_type   = 'p404';
									$page_label .= ' ' . __( '(404 Page)', 'coming-soon' );
								} elseif ( ! empty( $page_item->page_type ) ) {
									$page_type = $page_item->page_type;
								} else {
									$page_type = 'lp';
								}

								echo '<option value="' . esc_attr( $page_item->ID ) . '" data-ptype="' . esc_attr( $page_type ) . '">' . esc_html( $page_label ) . '</option>';
							}
							?>
						</select>
					</div>

					<div class="seedprod-export-actions">
						<button type="button" class="button button-primary seedprod-button-primary" id="seedprod-export-landing-btn">
							<span class="dashicons dashicons-download"></span>
							<span class="button-text"><?php esc_html_e( 'Export Selected', 'coming-soon' ); ?></span>
							<span class="spinner" style="display: none;"></span>
						</button>
					</div>

					<div class="seedprod-export-status" style="display: none;">
						<div class="notice notice-info">
							<p><?php esc_html_e( 'Preparing export file...', 'coming-soon' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Import Tab Content -->
			<div class="seedprod-tab-content" id="seedprod-landing-import-tab" style="display: none;">
				<div class="seedprod-import-section">
					<h3><?php esc_html_e( 'Import Landing Pages', 'coming-soon' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Import landing pages from a SeedProd export file (.zip). This will add the pages to your existing landing pages.', 'coming-soon' ); ?></p>
					
					<div class="seedprod-import-options">
						<div class="seedprod-import-file-section">
							<h4><?php esc_html_e( 'Upload File', 'coming-soon' ); ?></h4>
							<div class="seedprod-file-upload">
								<input type="file" id="seedprod-landing-import-file" accept=".zip" style="display: none;">
								<button type="button" class="button" id="seedprod-landing-select-file-btn">
									<span class="dashicons dashicons-upload"></span>
									<?php esc_html_e( 'Select File', 'coming-soon' ); ?>
								</button>
								<span class="seedprod-file-name"></span>
							</div>
						</div>
						
						<!-- URL import is not supported for landing pages, only for themes -->
						<div class="seedprod-import-url-section" style="display: none;">
							<h4><?php esc_html_e( 'Or Import from URL', 'coming-soon' ); ?></h4>
							<input type="url" id="seedprod-landing-import-url" class="regular-text" placeholder="https://example.com/landing-pages-export.zip">
						</div>
					</div>
					
					<div class="seedprod-import-warning">
						<div class="notice notice-warning inline">
							<p>
								<strong><?php esc_html_e( 'Important:', 'coming-soon' ); ?></strong>
								<?php esc_html_e( 'Importing will create new landing pages. If you have existing pages with the same names, duplicates will be created. Mode pages (Coming Soon, Maintenance, 404, Login) will replace existing ones if present.', 'coming-soon' ); ?>
								<?php
								printf(
									/* translators: %s: Link to Duplicator plugin. */
									esc_html__( ' We recommend creating a backup with %s (free plugin) before importing.', 'coming-soon' ),
									'<a href="' . esc_url( admin_url( 'admin.php?page=seedprod_lite_settings&tab=recommended-plugins&filter=duplicator' ) ) . '">Duplicator</a>'
								);
								?>
							</p>
						</div>
					</div>
					
					<div class="seedprod-import-actions">
						<button type="button" class="button button-primary seedprod-button-primary" id="seedprod-import-landing-btn" disabled>
							<span class="dashicons dashicons-upload"></span>
							<span class="button-text"><?php esc_html_e( 'Import Landing Pages', 'coming-soon' ); ?></span>
							<span class="spinner" style="display: none;"></span>
						</button>
					</div>
					
					<div class="seedprod-import-status" style="display: none;">
						<div class="notice">
							<p></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="seedprod-modal-footer">
			<button type="button" class="button button-secondary seedprod-modal-cancel">
				<?php esc_html_e( 'Close', 'coming-soon' ); ?>
			</button>
		</div>
	</div>
</div>
