<?php
/**
 * Get MyPayKit forms.
 *
 * @return void
 */
function seedprod_lite_get_mypaykit() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {
		if ( ! current_user_can( apply_filters( 'seedprod_builder_preview_render_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$forms = array();

		if ( defined( 'MYPAYKIT_API_URL' ) ) {
			// Make the API request.
			$response = wp_remote_get(
				MYPAYKIT_API_URL . '/forms',
				array(
					'timeout' => 15,
					'headers' => array(
						'Accept'       => 'application/json',
						'Content-Type' => 'application/json',
					),
					'body'    => array(
						'site_token'     => get_option( 'mypaykit_site_token' ),
						'mypaykit_token' => get_option( 'mypaykit_token' ),
					),
				)
			);

			if ( ! is_wp_error( $response ) ) {
				$body       = wp_remote_retrieve_body( $response );
				$forms_data = json_decode( $body, true );

				if ( ! empty( $forms_data['forms']['data'] ) && is_array( $forms_data['forms']['data'] ) ) {
					foreach ( $forms_data['forms']['data'] as $form ) {
						$forms[] = array(
							'id'   => $form['uuid'],  // Using uuid as the id.
							'name' => $form['name'],
						);
					}
				}
			}
		}

		wp_send_json( $forms );
	}
}

/**
 * Get MyPayKit form code.
 *
 * @return void
 */
function seedprod_lite_get_mypaykit_code() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {
		if ( ! current_user_can( apply_filters( 'seedprod_builder_preview_render_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$id = filter_input( INPUT_GET, 'form_id' );
		ob_start();
		?>

		<div class="sp-relative">
			<div class="mypaykit-iframe-wrapper rpoverlay">
				
				<?php // phpcs:ignore /* echo do_shortcode("[mypaykit_form id='$id']"); */ ?>
				<?php echo '<iframe src="' . esc_url( MYPAYKIT_WEB_URL . '/pf/' . $id ) . '" style="width: 100%; min-height: 600px; border: none;" scrolling="no"></iframe>'; ?>
			</div>
		</div>

		<?php
		$code = ob_get_clean();
		wp_send_json( $code );
	}
}
