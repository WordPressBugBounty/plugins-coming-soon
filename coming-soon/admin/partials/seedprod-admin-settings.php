<?php
/**
 * Settings page for SeedProd Lite
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current tab.
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for tab display.

// Define tabs.
$tabs_array = array(
	'general'             => __( 'General', 'coming-soon' ),
	'subscribers'         => __( 'Subscribers', 'coming-soon' ),
	'recommended-plugins' => __( 'Recommended Plugins', 'coming-soon' ),
	'about'               => __( 'About Us', 'coming-soon' ),
);
?>

<div class="seedprod-dashboard-page seedprod-settings-page">
	<?php
	// Include header with page title.
	$page_title = __( 'Settings', 'coming-soon' );
	require_once plugin_dir_path( __FILE__ ) . 'seedprod-admin-header.php';
	?>
	
	<div class="seedprod-dashboard-container">
		<!-- Tab Navigation using WordPress native classes -->
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs_array as $tab_key => $tab_label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, admin_url( 'admin.php?page=seedprod_lite_settings' ) ) ); ?>" 
					class="nav-tab <?php echo esc_attr( $current_tab === $tab_key ? 'nav-tab-active' : '' ); ?>">
					<?php echo esc_html( $tab_label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<!-- Tab Content using existing card class -->
		<div class="postbox seedprod-card">
			<div class="inside">
			<?php if ( 'general' === $current_tab ) : ?>
				<!-- General Tab -->
				<div class="seedprod-tab-panel" id="seedprod-general-tab">
					<?php
					// Get app settings and decode if JSON.
					$app_settings_json = get_option( 'seedprod_app_settings' );
					$app_settings      = ! empty( $app_settings_json ) ? json_decode( $app_settings_json, true ) : array();
					?>
					<form method="post" action="" id="seedprod-settings-form">
						<?php wp_nonce_field( 'seedprod_settings_save', 'seedprod_settings_nonce' ); ?>
						
						<!-- License Section -->
						<h2><?php esc_html_e( 'License', 'coming-soon' ); ?></h2>
						<?php
						$is_lite_view = seedprod_lite_v2_is_lite_view();
						if ( ! $is_lite_view ) :
							?>
							<p><?php esc_html_e( 'Your license key provides access to updates and addons.', 'coming-soon' ); ?></p>
						<?php else : ?>
							<p>
								<?php echo wp_kses_post( __( 'You\'re using <strong>SeedProd Lite</strong> - No License needed. Enjoy! 🙂', 'coming-soon' ) ); ?>
							</p>
							<p>
								<?php
								$upgrade_url = seedprod_lite_get_external_link(
									'https://www.seedprod.com/lite-upgrade/?discount=LITEUPGRADE',
									'seedprod-license-page',
									'liteplugin'
								);
								/* translators: %s: Upgrade URL */
								printf( wp_kses_post( __( 'To unlock more features consider <a href="%s" target="_blank">upgrading to PRO</a>. As a valued SeedProd Lite user you\'ll receive <strong>a discount off the regular price</strong>, automatically applied at checkout!', 'coming-soon' ) ), esc_url( $upgrade_url ) );
								?>
							</p>
						<?php endif; ?>
						
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">
										<label for="seedprod-license-key"><?php esc_html_e( 'License Key', 'coming-soon' ); ?></label>
									</th>
									<td>
										<?php
										$license_key  = get_option( 'seedprod_api_key' );
										$license_name = get_option( 'seedprod_license_name' );
										?>
										<input type="password" 
												id="seedprod-license-key" 
												name="seedprod_license_key" 
												value="<?php echo esc_attr( $license_key ); ?>" 
												class="regular-text" 
												placeholder="<?php esc_attr_e( 'Enter Your License Key Here', 'coming-soon' ); ?>" />
										
										<?php if ( $license_name ) : ?>
											<button type="button" class="button button-primary seedprod-button-primary" id="seedprod-recheck-license">
												<?php esc_html_e( 'Recheck Key', 'coming-soon' ); ?>
											</button>
											<button type="button" class="button seedprod-button-secondary" id="seedprod-deactivate-license">
												<?php esc_html_e( 'Deactivate Key', 'coming-soon' ); ?>
											</button>
										<?php else : ?>
											<button type="button" class="button button-primary seedprod-button-primary" id="seedprod-verify-license">
												<?php
												if ( seedprod_lite_v2_is_lite_view() ) {
													esc_html_e( 'Connect to SeedProd.com', 'coming-soon' );
												} else {
													esc_html_e( 'Verify Key', 'coming-soon' );
												}
												?>
											</button>
										<?php endif; ?>

										<div id="seedprod-license-message"></div>

										<?php if ( $license_name ) : ?>
											<div class="seedprod-license-badge seedprod-license-badge-active">
												<div class="seedprod-license-badge-icon">
													<span class="dashicons dashicons-awards"></span>
												</div>
												<div class="seedprod-license-badge-content">
													<span class="seedprod-license-badge-label"><?php esc_html_e( 'ACTIVE LICENSE', 'coming-soon' ); ?></span>
													<span class="seedprod-license-badge-type"><?php echo esc_html( $license_name ); ?></span>
												</div>
												<div class="seedprod-license-badge-check">
													<span class="dashicons dashicons-yes"></span>
												</div>
											</div>
										<?php else : ?>
											<div class="seedprod-license-badge seedprod-license-badge-inactive">
												<div class="seedprod-license-badge-icon">
													<span class="dashicons dashicons-warning"></span>
												</div>
												<div class="seedprod-license-badge-content">
													<span class="seedprod-license-badge-label"><?php esc_html_e( 'NO ACTIVE LICENSE', 'coming-soon' ); ?></span>
													<span class="seedprod-license-badge-help">
														<?php esc_html_e( "Don't have a license?", 'coming-soon' ); ?>
														<a href="<?php echo esc_url( seedprod_lite_get_pricing_link( 'settings-license-badge' ) ); ?>" target="_blank" rel="noopener">
															<?php esc_html_e( 'Get one here', 'coming-soon' ); ?>
														</a>
													</span>
												</div>
												<div class="seedprod-license-badge-action">
													<span class="dashicons dashicons-no"></span>
												</div>
											</div>
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
						
						<!-- Global Settings Section -->
						<h2><?php esc_html_e( 'Global Settings', 'coming-soon' ); ?></h2>
						<table class="form-table">
							<tbody>
								<?php if ( ! $is_lite_view ) : ?>
								<tr>
									<th scope="row">
										<label for="seedprod-facebook-app-id"><?php esc_html_e( 'Facebook APP ID', 'coming-soon' ); ?></label>
									</th>
									<td>
										<?php
										$fb_app_id = ! empty( $app_settings['facebook_g_app_id'] ) ? $app_settings['facebook_g_app_id'] : '';
										?>
										<input type="text" 
												id="seedprod-facebook-app-id" 
												name="seedprod_facebook_app_id" 
												value="<?php echo esc_attr( $fb_app_id ); ?>" 
												class="regular-text" />
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="seedprod-google-places-key"><?php esc_html_e( 'Google Places API Key', 'coming-soon' ); ?></label>
									</th>
									<td>
										<?php $google_key = ! empty( $app_settings['google_places_app_key'] ) ? $app_settings['google_places_app_key'] : ''; ?>
										<input type="text" 
												id="seedprod-google-places-key" 
												name="seedprod_google_places_key" 
												value="<?php echo esc_attr( $google_key ); ?>" 
												class="regular-text" />
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="seedprod-yelp-api-key"><?php esc_html_e( 'Yelp API Key', 'coming-soon' ); ?></label>
									</th>
									<td>
										<?php $yelp_key = ! empty( $app_settings['yelp_app_api_key'] ) ? $app_settings['yelp_app_api_key'] : ''; ?>
										<input type="text" 
												id="seedprod-yelp-api-key" 
												name="seedprod_yelp_api_key" 
												value="<?php echo esc_attr( $yelp_key ); ?>" 
												class="regular-text" />
									</td>
								</tr>
								<?php endif; ?>
								
								<tr>
									<th scope="row">
										<?php esc_html_e( 'Disable Edit with SeedProd Button', 'coming-soon' ); ?>
									</th>
									<td>
										<?php
										$disable_button = ! empty( $app_settings['disable_seedprod_button'] ) ? $app_settings['disable_seedprod_button'] : false;
										?>
										<label for="seedprod-disable-button">
											<input type="checkbox" 
													id="seedprod-disable-button" 
													name="seedprod_disable_button" 
													value="1" 
													<?php checked( $disable_button, true ); ?> />
											<?php esc_html_e( 'Disable the Edit with SeedProd button in the admin area', 'coming-soon' ); ?>
										</label>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<?php esc_html_e( 'Enable Usage Tracking', 'coming-soon' ); ?>
									</th>
									<td>
										<?php
										$usage_tracking = ! empty( $app_settings['enable_usage_tracking'] ) ? $app_settings['enable_usage_tracking'] : false;
										?>
										<label for="seedprod-usage-tracking">
											<input type="checkbox" 
													id="seedprod-usage-tracking" 
													name="seedprod_usage_tracking" 
													value="1" 
													<?php checked( $usage_tracking, true ); ?> />
											<?php esc_html_e( 'Help improve SeedProd by sharing anonymous usage data', 'coming-soon' ); ?>
										</label>
										<p class="description">
											<a href="<?php echo esc_url( seedprod_lite_get_external_link( 'https://www.seedprod.com/docs/usage-tracking/', 'settings-usage-tracking', seedprod_lite_v2_is_lite_view() ? 'liteplugin' : 'proplugin' ) ); ?>" target="_blank">
												<?php esc_html_e( 'Learn More', 'coming-soon' ); ?>
											</a>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<?php esc_html_e( 'Disable SeedProd Notifications', 'coming-soon' ); ?>
									</th>
									<td>
										<?php
										$disable_notifications = ! empty( $app_settings['disable_seedprod_notification'] ) ? $app_settings['disable_seedprod_notification'] : false;
										?>
										<label for="seedprod-disable-notifications">
											<input type="checkbox" 
													id="seedprod-disable-notifications" 
													name="seedprod_disable_notifications" 
													value="1" 
													<?php checked( $disable_notifications, true ); ?> />
											<?php esc_html_e( 'Disable admin notifications from SeedProd', 'coming-soon' ); ?>
										</label>
									</td>
								</tr>
							</tbody>
						</table>
						
						<p class="submit">
							<button type="submit" class="button button-primary seedprod-button-primary" id="seedprod-save-settings">
								<?php esc_html_e( 'Save Settings', 'coming-soon' ); ?>
							</button>
						</p>
					</form>
					
					<!-- Debug Information Section -->
					<hr />
					<h2><?php esc_html_e( 'Debug Information', 'coming-soon' ); ?></h2>
					
					<div class="seedprod-debug-section">
						<p>
							<button type="button" class="button seedprod-button-secondary" id="seedprod-toggle-system-info">
								<span class="dashicons dashicons-arrow-down-alt2"></span>
								<?php esc_html_e( 'View System Information', 'coming-soon' ); ?>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_debug' ) ); ?>" class="button seedprod-button-secondary">
								<span class="dashicons dashicons-admin-tools"></span>
								<?php esc_html_e( 'Advanced Debug Tools', 'coming-soon' ); ?>
							</a>
						</p>
						
						<div id="seedprod-system-info" style="display: none; margin-top: 20px;">
							<p class="description">
								<?php esc_html_e( 'Copy this information when contacting support to help us assist you better.', 'coming-soon' ); ?>
							</p>
							<textarea id="seedprod-system-info-text" readonly="readonly" class="large-text" rows="15" onclick="this.select();"><?php echo esc_textarea( seedprod_lite_v2_get_system_info() ); ?></textarea>
							<p>
								<button type="button" class="button seedprod-button-secondary" id="seedprod-copy-system-info">
									<?php esc_html_e( 'Copy to Clipboard', 'coming-soon' ); ?>
								</button>
							</p>
						</div>
					</div>
				</div>

			<?php elseif ( 'subscribers' === $current_tab ) : ?>
				<!-- Subscribers Tab -->
			<div class="seedprod-tab-panel" id="seedprod-subscribers-tab">
				<?php
				// Get subscriber stats.
				$stats      = seedprod_lite_v2_get_subscriber_stats();
				$pages_list = seedprod_lite_v2_get_subscriber_pages();

				// Check OptinMonster status.
					$optinmonster_installed = false;
					$optinmonster_active    = false;
					$optinmonster_slug      = 'optinmonster/optin-monster-wp-api.php';

				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
					$all_plugins = get_plugins();
				if ( isset( $all_plugins[ $optinmonster_slug ] ) ) {
					$optinmonster_installed = true;
					$optinmonster_active    = is_plugin_active( $optinmonster_slug );
				}
				?>
					
					<script type="text/javascript">
					window.seedprodOptinMonster = {
						installed: <?php echo $optinmonster_installed ? 'true' : 'false'; ?>,
						active: <?php echo $optinmonster_active ? 'true' : 'false'; ?>,
						slug: '<?php echo esc_js( $optinmonster_slug ); ?>'
					};
					</script>
					
					<!-- Header with export and filters -->
					<div class="seedprod-subscribers-header">
						<h2><?php esc_html_e( 'Subscribers Overview', 'coming-soon' ); ?></h2>
						
						<?php if ( $stats['total'] > 0 ) : ?>
						<div class="seedprod-subscribers-controls">
							<button type="button" class="button seedprod-button-secondary" id="seedprod-export-subscribers">
								<span class="dashicons dashicons-download"></span>
								<?php esc_html_e( 'Export to CSV', 'coming-soon' ); ?>
						</button>
						
						<select id="seedprod-subscriber-page-filter" class="seedprod-select">
							<option value="all"><?php esc_html_e( 'All Pages', 'coming-soon' ); ?></option>
							<?php foreach ( $pages_list as $page_item ) : ?>
								<option value="<?php echo esc_attr( $page_item['uuid'] ); ?>">
									<?php echo esc_html( $page_item['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>
					</div>
					
					<?php if ( $stats['total'] > 0 ) : ?>
					<!-- Search Box -->
					<div class="seedprod-subscribers-search-box">
						<input type="text" 
								id="seedprod-subscriber-search" 
								placeholder="<?php esc_attr_e( 'Search by email or name...', 'coming-soon' ); ?>" 
								class="regular-text" />
						<button type="button" class="button" id="seedprod-subscriber-search-btn">
							<?php esc_html_e( 'Search', 'coming-soon' ); ?>
						</button>
						<button type="button" class="button" id="seedprod-subscriber-clear-search" style="display: none;">
							<?php esc_html_e( 'Clear', 'coming-soon' ); ?>
						</button>
					</div>
					
					<!-- Subscribers Table -->
					<table class="wp-list-table widefat fixed striped table-view-list" id="seedprod-subscribers-table">
							<thead>
								<tr>
									<td class="manage-column column-cb check-column">
										<input type="checkbox" id="seedprod-subscriber-select-all" />
									</td>
									<th scope="col" class="manage-column column-email sortable">
										<a href="#" data-sort="email">
											<span><?php esc_html_e( 'Email', 'coming-soon' ); ?></span>
											<span class="sorting-indicator"></span>
										</a>
									</th>
									<th scope="col" class="manage-column column-name sortable">
										<a href="#" data-sort="fname">
											<span><?php esc_html_e( 'Name', 'coming-soon' ); ?></span>
											<span class="sorting-indicator"></span>
										</a>
									</th>
									<th scope="col" class="manage-column column-created sortable desc">
										<a href="#" data-sort="created">
											<span><?php esc_html_e( 'Created', 'coming-soon' ); ?></span>
											<span class="sorting-indicator"></span>
										</a>
									</th>
									<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'coming-soon' ); ?></th>
								</tr>
							</thead>
							<tbody id="seedprod-subscribers-list">
								<tr>
									<td colspan="5" class="seedprod-loading-message">
										<span class="dashicons dashicons-update spin"></span>
										<?php esc_html_e( 'Loading subscribers...', 'coming-soon' ); ?>
									</td>
								</tr>
							</tbody>
							<tfoot>
								<tr>
									<td class="manage-column column-cb check-column">
										<input type="checkbox" class="seedprod-subscriber-select-all-bottom" />
									</td>
									<th scope="col" class="manage-column column-email"><?php esc_html_e( 'Email', 'coming-soon' ); ?></th>
									<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Name', 'coming-soon' ); ?></th>
									<th scope="col" class="manage-column column-created"><?php esc_html_e( 'Created', 'coming-soon' ); ?></th>
									<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'coming-soon' ); ?></th>
								</tr>
							</tfoot>
						</table>
					
					<!-- Bulk Actions -->
					<div class="tablenav bottom">
						<div class="alignleft actions bulkactions">
							<select id="seedprod-subscriber-bulk-action">
								<option value=""><?php esc_html_e( 'Bulk Actions', 'coming-soon' ); ?></option>
								<option value="delete"><?php esc_html_e( 'Delete', 'coming-soon' ); ?></option>
							</select>
							<button type="button" class="button action" id="seedprod-subscriber-bulk-apply">
								<?php esc_html_e( 'Apply', 'coming-soon' ); ?>
							</button>
						</div>
						
						<!-- Pagination -->
						<div class="tablenav-pages">
							<span class="displaying-num">
								<span id="seedprod-subscriber-count">0</span> <?php esc_html_e( 'items', 'coming-soon' ); ?>
							</span>
							<span class="seedprod-pagination-links" id="seedprod-subscriber-pagination">
								<!-- Pagination will be inserted here via JS -->
							</span>
						</div>
					</div>
					
					<?php else : ?>
					
					<!-- Show table with just OptinMonster row when no subscribers -->
					<table class="wp-list-table widefat fixed striped table-view-list" id="seedprod-subscribers-table">
						<thead>
							<tr>
								<td class="manage-column column-cb check-column">
									<input type="checkbox" id="seedprod-subscriber-select-all" />
								</td>
								<th scope="col" class="manage-column column-email sortable">
									<a href="#" data-sort="email">
										<span><?php esc_html_e( 'Email', 'coming-soon' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th scope="col" class="manage-column column-name sortable">
									<a href="#" data-sort="name">
										<span><?php esc_html_e( 'Name', 'coming-soon' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th scope="col" class="manage-column column-created sortable">
									<a href="#" data-sort="created_at">
										<span><?php esc_html_e( 'Created', 'coming-soon' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'coming-soon' ); ?></th>
							</tr>
						</thead>
						<tbody id="seedprod-subscribers-list">
							<!-- OptinMonster row will be inserted here by JavaScript -->
						</tbody>
					</table>
					
					<?php endif; ?>
				</div>

		<?php elseif ( 'recommended-plugins' === $current_tab ) : ?>
			<!-- Recommended Plugins Tab -->
			<div class="seedprod-tab-panel" id="seedprod-recommended-plugins-tab">
				<?php
				// Check for filter parameter.
				$filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering.

				if ( $filter ) :
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seedprod_lite_settings&tab=recommended-plugins' ) ); ?>" class="button button-link" style="margin-bottom: 10px;">
						← <?php esc_html_e( 'View All Plugins', 'coming-soon' ); ?>
					</a>
				<?php endif; ?>
				
				<h2><?php esc_html_e( 'Recommended Plugins', 'coming-soon' ); ?></h2>
				<p><?php esc_html_e( 'Supercharge your website with our recommended WordPress plugins.', 'coming-soon' ); ?></p>
				
				<div class="seedprod-plugins-grid">
					<?php
					// When filtering, get ALL plugins (including active ones).
					// Otherwise, get only non-active plugins.
					if ( isset( $_GET['filter'] ) && ! empty( $_GET['filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Already sanitized above.
						$all_plugins = seedprod_lite_v2_get_recommended_plugins();
					} else {
						$all_plugins = seedprod_lite_v2_get_all_recommended_plugins();
					}

					// If filter is set, only show that plugin.
					if ( $filter && isset( $all_plugins[ $filter ] ) ) {
						$filtered_plugins = array( $filter => $all_plugins[ $filter ] );
					} else {
						// Shuffle the plugins array to randomize order on each page load.
						$keys = array_keys( $all_plugins );
						shuffle( $keys );
						$shuffled_plugins = array();
						foreach ( $keys as $key ) {
							$shuffled_plugins[ $key ] = $all_plugins[ $key ];
						}
						$filtered_plugins = $shuffled_plugins;
					}                       foreach ( $filtered_plugins as $plugin_key => $plugin_data ) :
						$plugin_status = seedprod_lite_v2_get_plugin_status( $plugin_data['slug'] );
						?>
							<div class="seedprod-plugin-card" data-plugin="<?php echo esc_attr( $plugin_key ); ?>">
								<div class="seedprod-plugin-card-content">
										<div class="seedprod-plugin-header">
											<img src="<?php echo esc_url( $plugin_data['icon'] ); ?>" 
												alt="<?php echo esc_attr( $plugin_data['name'] ); ?>" 
												class="seedprod-plugin-icon" />
											<div class="seedprod-plugin-info">
												<h4><?php echo esc_html( $plugin_data['name'] ); ?></h4>
												<p class="seedprod-plugin-description"><?php echo esc_html( $plugin_data['desc'] ); ?></p>
											</div>
										</div>
										
										<div class="seedprod-plugin-footer">
											<div class="seedprod-plugin-status">
												<?php
												switch ( $plugin_status['status'] ) {
													case 0:
														echo '<span class="seedprod-status-badge seedprod-status-not-installed">' . esc_html__( 'NOT INSTALLED', 'coming-soon' ) . '</span>';
														break;
													case 1:
														echo '<span class="seedprod-status-badge seedprod-status-active">' . esc_html__( 'ACTIVE', 'coming-soon' ) . '</span>';
														break;
													case 2:
														echo '<span class="seedprod-status-badge seedprod-status-inactive">' . esc_html__( 'INACTIVE', 'coming-soon' ) . '</span>';
														break;
													case 3:
														echo '<span class="seedprod-status-badge seedprod-status-active">' . esc_html__( 'PRO VERSION', 'coming-soon' ) . '</span>';
														break;
												}
												?>
											</div>
											
											<?php if ( 3 !== $plugin_status['status'] ) : // Not Pro version. ?>
												<button class="button seedprod-plugin-button 
													<?php echo 1 === $plugin_status['status'] ? 'seedprod-button-secondary' : 'button-primary seedprod-button-primary'; ?>"
													data-plugin-slug="<?php echo esc_attr( $plugin_data['slug'] ); ?>"
													data-plugin-id="<?php echo esc_attr( $plugin_key ); ?>"
													data-status="<?php echo esc_attr( $plugin_status['status'] ); ?>">
													<span class="button-text">
														<?php
														switch ( $plugin_status['status'] ) {
															case 0:
																esc_html_e( 'Install', 'coming-soon' );
																break;
															case 1:
																esc_html_e( 'Deactivate', 'coming-soon' );
																break;
															case 2:
																esc_html_e( 'Activate', 'coming-soon' );
																break;
														}
														?>
													</span>
													<span class="button-spinner" style="display: none;">
														<span class="dashicons dashicons-update"></span>
													</span>
												</button>
											<?php endif; ?>
										</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

			<?php elseif ( 'about' === $current_tab ) : ?>
				<!-- About Us Tab -->
				<div class="seedprod-tab-panel" id="seedprod-about-tab">
					<div class="seedprod-about-content">
						<div class="seedprod-about-main">
							<div class="seedprod-about-text">
								<h2><?php esc_html_e( 'About SeedProd', 'coming-soon' ); ?></h2>
								
								<p>
									<?php esc_html_e( 'Hello and welcome to SeedProd, the #1 WordPress Website Builder with AI. At SeedProd, we build software that helps you create stunning WordPress websites in minutes, not weeks. Our drag & drop builder and AI-powered tools help you design complete websites, landing pages, and custom themes without writing any code.', 'coming-soon' ); ?>
								</p>
								
								<p>
									<?php esc_html_e( 'What started as a simple coming soon page plugin has evolved into a complete website builder. Today, SeedProd powers millions of websites with our AI website generator, 90+ professional design blocks, theme builder, and extensive template library. Build everything from business websites to online stores, portfolios, and blogs.', 'coming-soon' ); ?>
								</p>
								
								<p>
									<?php esc_html_e( 'Our mission is simple: empower anyone to build a professional WordPress website without technical skills. With our new AI technology, you can describe your business and watch as a complete website is generated in just 60 seconds.', 'coming-soon' ); ?>
								</p>
								
								<p>
									<?php
									/* translators: 1: WPBeginner link, 2: OptinMonster link, 3: MonsterInsights link, 4: WPForms link, 5: RafflePress link, 6: TrustPulse link */
									printf( esc_html__( 'SeedProd is brought to you by the same team that\'s behind the largest WordPress resource site, %1$s, the most popular lead-generation software, %2$s, the best WordPress analytics plugin, %3$s, the best WordPress forms plugin, %4$s, the best WordPress giveaway plugin, %5$s, and finally the best WordPress FOMO plugin, %6$s.', 'coming-soon' ), '<a href="https://www.wpbeginner.com/?utm_source=seedprodplugin&utm_medium=pluginaboutpage&utm_campaign=aboutseedprod" target="_blank" rel="noopener">WPBeginner</a>', '<a href="https://optinmonster.com/?utm_source=seedprodplugin&utm_medium=pluginaboutpage&utm_campaign=aboutseedprod" target="_blank" rel="noopener">OptinMonster</a>', '<a href="https://www.monsterinsights.com/?utm_source=seedprodplugin&utm_medium=pluginaboutpage&utm_campaign=aboutseedprod" target="_blank" rel="noopener">MonsterInsights</a>', '<a href="https://www.wpforms.com/?utm_source=seedprodplugin&utm_medium=pluginaboutpage&utm_campaign=aboutseedprod" target="_blank" rel="noopener">WPForms</a>', '<a href="https://rafflepress.com/?utm_source=seedprodplugin&utm_medium=pluginaboutpage&utm_campaign=aboutseedprod" target="_blank" rel="noopener">RafflePress</a>', '<a href="https://trustpulse.com/?utm_source=seedprodplugin&utm_medium=pluginaboutpage&utm_campaign=aboutseedprod" target="_blank" rel="noopener">TrustPulse</a>' );
									?>
								</p>
								
								<p>
									<?php esc_html_e( 'Yup, we know a thing or two about building awesome products that customers love.', 'coming-soon' ); ?>
								</p>
							</div>
							
							<div class="seedprod-about-image">
								<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/team.jpg' ); ?>" 
									alt="<?php esc_attr_e( 'SeedProd Team photo', 'coming-soon' ); ?>" 
									class="seedprod-team-photo" />
								<p class="seedprod-team-caption">
									<?php esc_html_e( 'The Awesome Motive Team', 'coming-soon' ); ?>
								</p>
							</div>
						</div>
						
						<?php if ( $is_lite_view ) : ?>
						<!-- Lite CTA -->
						<div class="seedprod-about-cta">
							<h3><?php esc_html_e( 'Unlock SeedProd Lite Today', 'coming-soon' ); ?></h3>
							<p><?php esc_html_e( 'Get access to all our premium features including theme builder, WooCommerce blocks, advanced integrations, and more!', 'coming-soon' ); ?></p>
							<a href="<?php echo esc_url( seedprod_lite_get_upgrade_link( 'about-us' ) ); ?>"
								target="_blank"
								class="button button-primary seedprod-upgrade-button">
								<span class="dashicons dashicons-star-filled seedprod-upgrade-icon"></span>
								<?php esc_html_e( 'Upgrade to Pro', 'coming-soon' ); ?>
							</a>
						</div>
						<?php endif; ?>
					</div>
				</div>

			<?php endif; ?>
			</div><!-- /.inside -->
		</div><!-- /.postbox -->
	</div><!-- /.seedprod-dashboard-container -->
</div><!-- /.seedprod-settings-page -->
