<?php
/**
 * Import/export helper view.
 *
 * @package SeedProd
 * @subpackage SeedProd\Views
 */

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$sp_post_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : null;
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
$post_json_raw = isset( $_POST['sp_post_json'] ) ? wp_unslash( $_POST['sp_post_json'] ) : '';
$post_json     = sanitize_textarea_field( $post_json_raw );

$nonce_value = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
if ( ! empty( $post_json ) && wp_verify_nonce( $nonce_value, 'importexport-' . $sp_post_id ) ) {
	global $wpdb;
	$json = json_decode( $post_json );
	if ( JSON_ERROR_NONE !== json_last_error() ) {
		wp_die( esc_html__( 'JSON is not valid.', 'coming-soon' ) );
	}

	$json    = wp_json_encode( $json );
	$tablename = $wpdb->prefix . 'posts';
	$updated = $wpdb->update(
		$tablename,
		array(
			'post_content_filtered' => $json,
		),
		array( 'ID' => $sp_post_id ),
		array( '%s' ),
		array( '%d' )
	);

	if ( false === $updated ) {
		echo esc_html__( 'Update error.', 'coming-soon' ) . PHP_EOL;
	} else {
		echo esc_html__( 'Updated.', 'coming-soon' ) . PHP_EOL;
	}
}

global $wpdb;
$tablename = $wpdb->prefix . 'posts';
$sql       = "SELECT * FROM $tablename";
$sql      .= " WHERE ID = %s";
$safe_sql  = $wpdb->prepare( $sql, $sp_post_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$result    = $wpdb->get_row( $safe_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

$js = json_decode( $result->post_content_filtered );
if ( JSON_ERROR_NONE === json_last_error() ) {
	echo esc_html__( 'JSON is valid.', 'coming-soon' ) . PHP_EOL;
} else {
	echo esc_html__( 'JSON is not valid.', 'coming-soon' ) . PHP_EOL;
}

?>
<form method="post">
	<?php wp_nonce_field( 'importexport-' . $sp_post_id ); ?>
	<h1><?php esc_html_e( 'Post JSON', 'coming-soon' ); ?></h1>
	<textarea name="sp_post_json" style="width:100%; height: 500px;"><?php echo esc_textarea( $result->post_content_filtered ); ?></textarea>
	<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save', 'coming-soon' ); ?>">
</form>
