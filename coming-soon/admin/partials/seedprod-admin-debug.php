<?php
/**
 * Debug page for SeedProd Lite
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle form submission using V2 functions.
$update_message = '';
$update_status  = false;

if ( ! empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified inside seedprod_lite_v2_save_debug_settings().
	$result         = seedprod_lite_v2_save_debug_settings();
	$update_status  = $result['status'];
	$update_message = $result['message'];
}

// Get current settings using V2 function.
$seedprod_builder_debug = seedprod_lite_v2_get_builder_debug_status();
?>

<div class="seedprod-dashboard-page seedprod-debug-page">
	<?php
	// Include header with page title.
	$page_title = __( 'Debug Tools', 'coming-soon' );
	require_once plugin_dir_path( __FILE__ ) . 'seedprod-admin-header.php';
	?>
	
	<div class="seedprod-dashboard-container">
		<?php if ( ! empty( $update_message ) ) : ?>
			<div class="notice notice-<?php echo $update_status ? 'success' : 'error'; ?> is-dismissible seedprod-notice-compact">
				<p><?php echo esc_html( $update_message ); ?></p>
			</div>
		<?php endif; ?>
		
		<!-- Debug Tools Form -->
		<form method="post" action="" novalidate="novalidate">
			<?php wp_nonce_field( 'seedprod-debug-reset' ); ?>
			
			<table class="form-table" role="presentation">
				<tbody>
					<!-- Builder Debug -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Builder Debug Mode', 'coming-soon' ); ?>
						</th>
						<td>
							<label for="sp_builder_debug">
								<input name="sp_builder_debug" type="checkbox" id="sp_builder_debug" value="1" <?php checked( ! empty( $seedprod_builder_debug ) ); ?>>
								<?php esc_html_e( 'Enable Builder Debug Mode', 'coming-soon' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Enable this if you are having problems in the builder like inserting images or other features not working correctly.', 'coming-soon' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			
			<hr />
			
			<h2><?php esc_html_e( 'Reset Pages', 'coming-soon' ); ?></h2>
			
			<div class="notice notice-warning inline seedprod-notice-compact">
				<p>
					<strong><?php esc_html_e( 'Warning:', 'coming-soon' ); ?></strong>
					<?php esc_html_e( 'These actions will permanently delete the selected pages. This cannot be undone.', 'coming-soon' ); ?>
					<br>
					<span class="dashicons dashicons-lightbulb" style="color: #f0ad4e; vertical-align: middle;"></span>
					<strong><?php esc_html_e( 'Pro Tip:', 'coming-soon' ); ?></strong>
					<?php
					printf(
						/* translators: %s: Link to Duplicator plugin */
						esc_html__( 'We recommend creating a backup with %s (free plugin) before proceeding.', 'coming-soon' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=seedprod_lite_settings&tab=recommended-plugins&filter=duplicator' ) ) . '">Duplicator</a>'
					);
					?>
				</p>
			</div>
			
			<table class="form-table" role="presentation">
				<tbody>
					<!-- Reset Coming Soon -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Reset Coming Soon Page', 'coming-soon' ); ?>
						</th>
						<td>
							<label for="sp_reset_cs">
								<input name="sp_reset_cs" type="checkbox" id="sp_reset_cs" value="1">
								<?php esc_html_e( 'Check and save to delete Coming Soon page', 'coming-soon' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'This will delete your current Coming Soon page.', 'coming-soon' ); ?>
							</p>
						</td>
					</tr>
					
					<!-- Reset Maintenance Mode -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Reset Maintenance Mode Page', 'coming-soon' ); ?>
						</th>
						<td>
							<label for="sp_reset_mm">
								<input name="sp_reset_mm" type="checkbox" id="sp_reset_mm" value="1">
								<?php esc_html_e( 'Check and save to delete Maintenance Mode page', 'coming-soon' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'This will delete your current Maintenance Mode page.', 'coming-soon' ); ?>
							</p>
						</td>
					</tr>
					
					<!-- Reset 404 Page -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Reset 404 Page', 'coming-soon' ); ?>
						</th>
						<td>
							<label for="sp_reset_p404">
								<input name="sp_reset_p404" type="checkbox" id="sp_reset_p404" value="1">
								<?php esc_html_e( 'Check and save to delete 404 page', 'coming-soon' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'This will delete your current custom 404 page.', 'coming-soon' ); ?>
							</p>
						</td>
					</tr>
					
					<!-- Reset Login Page -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Reset Login Page', 'coming-soon' ); ?>
						</th>
						<td>
							<label for="sp_reset_loginp">
								<input name="sp_reset_loginp" type="checkbox" id="sp_reset_loginp" value="1">
								<?php esc_html_e( 'Check and save to delete Login page', 'coming-soon' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'This will delete your current custom Login page.', 'coming-soon' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			
			<p class="submit">
				<button type="submit" name="submit" class="button button-primary seedprod-button-primary">
					<?php esc_html_e( 'Save Changes', 'coming-soon' ); ?>
				</button>
			</p>
		</form>
	</div>
</div>
