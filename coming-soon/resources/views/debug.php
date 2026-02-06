<?php
/**
 * Debug tools view.
 *
 * @package SeedProd
 * @subpackage SeedProd\Views
 */

$posted_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.NoNonceVerification

// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.NoNonceVerification
if ( ! empty( $posted_data ) ) {
	$nonce = isset( $posted_data['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $posted_data['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'debug-reset' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'coming-soon' ) );
	}

	$checkboxes = array(
		'sp_reset_cs'     => 'seedprod_coming_soon_page_id',
		'sp_reset_mm'     => 'seedprod_maintenance_mode_page_id',
		'sp_reset_p404'   => 'seedprod_404_page_id',
		'sp_reset_loginp' => 'seedprod_login_page_id',
	);

	foreach ( $checkboxes as $field => $option ) {
		$value = isset( $posted_data[ $field ] ) ? absint( wp_unslash( $posted_data[ $field ] ) ) : 0;
		if ( 1 === $value ) {
			update_option( $option, false );
		}
	}

	$builder_debug_value = isset( $posted_data['sp_builder_debug'] ) ? absint( wp_unslash( $posted_data['sp_builder_debug'] ) ) : 0;
	update_option( 'seedprod_builder_debug', ( 1 === $builder_debug_value ) );
}
// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.NoNonceVerification

$seedprod_builder_debug = get_option( 'seedprod_builder_debug' );
?>
<h1 class="sp-text-xl sp-mt-4 sp-mb-1"><?php esc_html_e( 'System Information', 'coming-soon' ); ?></h1>
<textarea readonly="readonly" style="width: 100%; height: 500px"><?php echo esc_textarea( seedprod_lite_get_system_info() ); ?></textarea>


<h1 class="sp-text-xl sp-mt-4 sp-mb-1"><?php esc_html_e( 'Debug Tools', 'coming-soon' ); ?></h1>
<?php if ( ! empty( $posted_data ) ) { ?>
<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible" style="margin:0px 20px 0 0">
<p><strong><?php esc_html_e( 'Updated.', 'coming-soon' ); ?></strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'coming-soon' ); ?></span></button></div>
<?php } ?>
<form method="post" novalidate="novalidate">
<?php wp_nonce_field( 'debug-reset' ); ?>
<table class="form-table" role="presentation">
<tbody>
<tr>
<th scope="row"><?php esc_html_e( 'Builder Debug', 'coming-soon' ); ?><br><small><?php esc_html_e( 'If you are having a problem in the builder like inserting an image or some other feature in the builder is broken, check this box.', 'coming-soon' ); ?></small></th>
<td> <fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Builder Debug', 'coming-soon' ); ?></span></legend><label for="sp_builder_debug">
<input name="sp_builder_debug" type="checkbox" id="sp_builder_debug" value="1" <?php checked( ! empty( $seedprod_builder_debug ) ); ?>>
	<?php esc_html_e( 'Enable Builder Debug', 'coming-soon' ); ?></label>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'Reset Coming Soon Page', 'coming-soon' ); ?><br><small><?php esc_html_e( 'This will delete the current coming soon page.', 'coming-soon' ); ?></small></th>
<td> <fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Reset Coming Soon Page', 'coming-soon' ); ?></span></legend><label for="sp_reset_cs">
<input name="sp_reset_cs" type="checkbox" id="sp_reset_cs" value="1">
<?php esc_html_e( 'Check Box and Save to Reset', 'coming-soon' ); ?></label>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'Reset Maintenance Mode Page', 'coming-soon' ); ?><br><small><?php esc_html_e( 'This will delete the current maintenance page.', 'coming-soon' ); ?></small></th>
<td> <fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Reset Maintenance Mode Page', 'coming-soon' ); ?></span></legend><label for="sp_reset_mm">
<input name="sp_reset_mm" type="checkbox" id="sp_reset_mm" value="1">
<?php esc_html_e( 'Check Box and Save to Reset', 'coming-soon' ); ?></label>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'Reset Login Page', 'coming-soon' ); ?><br><small><?php esc_html_e( 'This will delete the current Custom Login page.', 'coming-soon' ); ?></small></th>
<td> <fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Reset Login Page', 'coming-soon' ); ?></span></legend><label for="sp_reset_loginp">
<input name="sp_reset_loginp" type="checkbox" id="sp_reset_loginp" value="1">
<?php esc_html_e( 'Check Box and Save to Reset', 'coming-soon' ); ?></label>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'Reset 404 Page', 'coming-soon' ); ?><br><small><?php esc_html_e( 'This will delete the current 404 page.', 'coming-soon' ); ?></small></th>
<td> <fieldset><legend class="screen-reader-text"><span><?php esc_html_e( 'Reset 404 Page', 'coming-soon' ); ?></span></legend><label for="sp_reset_p404">
<input name="sp_reset_p404" type="checkbox" id="sp_reset_p404" value="1">
<?php esc_html_e( 'Check Box and Save to Reset', 'coming-soon' ); ?></label>
</fieldset></td>
</tr></tbody>
</table>
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'coming-soon' ); ?>"></p>
</form>
