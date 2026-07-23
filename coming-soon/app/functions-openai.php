<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Make Open AI call to fetch result
 */
function seedprod_lite_call_open_ai() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {

		if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$seedprod_api_key = seedprod_lite_get_api_key();
		$api_key          = $seedprod_api_key;
		$token            = get_option( 'seedprod_token' );
		$api_token        = get_option( 'seedprod_api_token' );

		$prompt = isset( $_REQUEST['prompt'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['prompt'] ) ) : '';
		$data   = array(
			'prompt'    => $prompt,
			'api_token' => $api_token,
			'api_key'   => $api_key,
			'token'     => $token,
		);

		$headers = array(
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $api_token,
		);

		$url = SEEDPROD_WEB_API_URL . 'v4/openaigenerate';

		try {

			$response = wp_remote_post(
				$url,
				array(
					'body'      => wp_json_encode( $data ),
					'headers'   => $headers,
					'sslverify' => true,
					'timeout'   => 60,
				)
			);

			if ( is_wp_error( $response ) ) {

				$curl_error = $response->get_error_code();
				if ( 'http_request_failed' === $curl_error ) {
					$result = wp_json_encode( array( 'error' => __( 'cURL error:', 'coming-soon' ) . $response->get_error_message() ) );
				} else {
					$result = wp_json_encode( array( 'error' => $response->get_error_message() ) );
				}
			} else {
				$http_status = wp_remote_retrieve_response_code( $response );
				if ( 200 === $http_status ) {
					$response_body = wp_remote_retrieve_body( $response );
					$result_data   = json_decode( $response_body, true );

					if ( null === $result_data && json_last_error() !== JSON_ERROR_NONE ) {
						$result = wp_json_encode( array( 'error' => __( 'Invalid JSON response', 'coming-soon' ) ) );
					} else {
						$result = wp_json_encode( $result_data );
					}
				} else {
					// Request timeout error.
					$result = wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
				}
			}
		} catch ( Exception $e ) {
			$result = wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
		}

		echo wp_kses_post( $result );

		exit;

	}
}

/**
 * Make Open AI call to fetch result with instruction for model.
 */
function seedprod_lite_call_open_ai_edit() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {

		if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$seedprod_api_key = seedprod_lite_get_api_key();
		$api_key          = $seedprod_api_key;
		$token            = get_option( 'seedprod_token' );
		$api_token        = get_option( 'seedprod_api_token' );

		$prompt      = isset( $_REQUEST['prompt'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['prompt'] ) ) : '';
		$instruction = isset( $_REQUEST['instruction'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['instruction'] ) ) : '';
		$data        = array(
			'prompt'      => $prompt,
			'instruction' => $instruction,
			'api_token'   => $api_token,
			'api_key'     => $api_key,
			'token'       => $token,
		);

		$headers = array(
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $api_token,
		);

		$url = SEEDPROD_WEB_API_URL . 'v4/openaiedittext';

		try {
				$response = wp_remote_post(
					$url,
					array(
						'body'      => wp_json_encode( $data ),
						'headers'   => $headers,
						'sslverify' => true,
						'timeout'   => 60,
					)
				);

			if ( is_wp_error( $response ) ) {
				$curl_error = $response->get_error_code();
				if ( 'http_request_failed' === $curl_error ) {
					$result = wp_json_encode( array( 'error' => __( 'cURL error:', 'coming-soon' ) . $response->get_error_message() ) );
				} else {
					$result = wp_json_encode( array( 'error' => $response->get_error_message() ) );
				}
			} else {

				$http_status = wp_remote_retrieve_response_code( $response );

				if ( 200 === $http_status ) {
					$response_body = wp_remote_retrieve_body( $response );
					$result_data   = json_decode( $response_body, true );
					$result        = wp_json_encode( $result_data );
				} else {
					// Request timeout error.
					$result = wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
				}
			}
		} catch ( Exception $e ) {
			$result = wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
		}

		echo wp_kses_post( $result );

		exit;

	}
}



/**
 * Make Open AI call to fetch images result from prompt
 */
function seedprod_lite_generate_image_open_ai() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {

		if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$seedprod_api_key = seedprod_lite_get_api_key();
		$api_key          = $seedprod_api_key;
		$token            = get_option( 'seedprod_token' );
		$api_token        = get_option( 'seedprod_api_token' );

		$prompt = isset( $_REQUEST['prompt'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['prompt'] ) ) : '';

		$headers = array(
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $api_token,
		);

		$data = array(
			'prompt'    => $prompt,
			'api_token' => $api_token,
			'api_key'   => $api_key,
			'token'     => $token,
		);

		$url = SEEDPROD_WEB_API_URL . 'v4/openaiimagegenerate';

		try {
			$response = wp_remote_post(
				$url,
				array(
					'body'      => wp_json_encode( $data ),
					'headers'   => $headers,
					'sslverify' => true,
					'timeout'   => 120,
				)
			);

			if ( is_wp_error( $response ) ) {
				$curl_error = $response->get_error_code();
				if ( 'http_request_failed' === $curl_error ) {
					echo wp_json_encode( array( 'error' => __( 'cURL error:', 'coming-soon' ) . $response->get_error_message() ) );
				} else {
					echo wp_json_encode( array( 'error' => $response->get_error_message() ) );
				}
			} else {
				$http_status = wp_remote_retrieve_response_code( $response );
				if ( 200 === $http_status ) {
					$response_body = wp_remote_retrieve_body( $response );
					$result_data   = json_decode( $response_body, true );

					if ( null === $result_data && json_last_error() !== JSON_ERROR_NONE ) {
						echo wp_json_encode( array( 'error' => __( 'Invalid JSON response', 'coming-soon' ) ) );
					} else {
						echo wp_json_encode( $result_data );
					}
				} else {
					echo wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
				}
			}
		} catch ( Exception $e ) {
			echo wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
		}

		exit;

	}
}


/**
 * Make Open AI call to fetch images variations
 */
function seedprod_lite_generate_image_open_ai_variations() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {

		if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$image_url = isset( $_REQUEST['image'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['image'] ) ) : '';

		$image_bytes = seedprod_lite_load_image_bytes( $image_url );
		if ( false === $image_bytes ) {
			echo wp_json_encode( array( 'error' => __( 'Could not retrieve source image.', 'coming-soon' ) ) );
			exit;
		}

		$image_bytes = seedprod_lite_resize_image_to_1024( $image_bytes );
		if ( false === $image_bytes ) {
			echo wp_json_encode( array( 'error' => __( 'Failed to process source image.', 'coming-soon' ) ) );
			exit;
		}

		$seedprod_api_key = seedprod_lite_get_api_key();
		$api_key          = $seedprod_api_key;
		$token            = get_option( 'seedprod_token' );
		$api_token        = get_option( 'seedprod_api_token' );

		$headers_array = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_token,
		);

		$data = array(
			'image'     => base64_encode( $image_bytes ), // phpcs:ignore
			'api_token' => $api_token,
			'api_key'   => $api_key,
			'token'     => $token,
		);

		$url = SEEDPROD_WEB_API_URL . 'v4/openaiimagevariationsgenerate';

		try {
			$response = wp_remote_post(
				$url,
				array(
					'body'      => wp_json_encode( $data ),
					'headers'   => $headers_array,
					'sslverify' => true,
					'timeout'   => 120,
				)
			);

			if ( is_wp_error( $response ) ) {

				$curl_error = $response->get_error_code();
				if ( 'http_request_failed' === $curl_error ) {
					echo wp_json_encode( array( 'error' => __( 'cURL error:', 'coming-soon' ) . $response->get_error_message() ) );
				} else {
					echo wp_json_encode( array( 'error' => $response->get_error_message() ) );
				}
			} else {

				$http_status = wp_remote_retrieve_response_code( $response );
				if ( 200 === $http_status ) {
					$response_body = wp_remote_retrieve_body( $response );
					$result_data   = json_decode( $response_body, true );

					if ( null === $result_data && json_last_error() !== JSON_ERROR_NONE ) {
						echo wp_json_encode( array( 'error' => __( 'Invalid JSON response', 'coming-soon' ) ) );
					} else {
						echo wp_json_encode( $result_data );
					}
				} else {
					echo wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
				}
			}
		} catch ( Exception $e ) {
			echo wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
		}

		exit;

	}
}

/**
 * Resolve a source image URL (local upload or remote http(s)) to raw bytes.
 * Remote fetch is gated by a 10MB size cap matching the API's MAX_IMAGE_BYTES.
 *
 * @param string $image_url URL from $_REQUEST['image'].
 * @return string|false Raw image bytes, or false on any failure.
 */
function seedprod_lite_load_image_bytes( $image_url ) {
	if ( '' === $image_url ) {
		return false;
	}

	$max_bytes  = 7 * 1024 * 1024; // ~7MB binary leaves headroom under the worker's 10MB base64 cap.
	$upload_dir = wp_upload_dir();
	$is_local   = 0 === strpos( $image_url, $upload_dir['baseurl'] );
	$local_path = $is_local ? str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url ) : '';

	if ( $is_local && file_exists( $local_path ) ) {
		if ( filesize( $local_path ) > $max_bytes ) {
			return false;
		}
		return file_get_contents( $local_path ); // phpcs:ignore
	}

	if ( ! preg_match( '#^https?://#i', $image_url ) ) {
		return false;
	}

	$response = wp_safe_remote_get(
		$image_url,
		array(
			'timeout'   => 30,
			'sslverify' => true,
		)
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	if ( ! $body || strlen( $body ) > $max_bytes ) {
		return false;
	}

	return $body;
}

/**
 * Normalize image bytes to a 1024x1024 PNG with alpha so /v1/images/edits
 * accepts them. gpt-image-1 requires the mask and source to match the `size`
 * parameter exactly; we always request 1024x1024.
 *
 * @param string $image_bytes Raw image bytes (any common format GD reads).
 * @return string|false 1024x1024 PNG bytes, or false on decode failure.
 */
function seedprod_lite_resize_image_to_1024( $image_bytes ) {
	if ( ! function_exists( 'imagecreatefromstring' ) || ! function_exists( 'getimagesizefromstring' ) ) {
		return false;
	}

	// Reject decompression bombs before GD allocates the full bitmap.
	$info = getimagesizefromstring( $image_bytes );
	if ( ! $info || $info[0] > 4096 || $info[1] > 4096 ) {
		return false;
	}

	$source = imagecreatefromstring( $image_bytes );
	if ( ! $source ) {
		return false;
	}

	$src_w = imagesx( $source );
	$src_h = imagesy( $source );

	$resized = imagecreatetruecolor( 1024, 1024 );
	imagealphablending( $resized, false );
	imagesavealpha( $resized, true );
	$transparent = imagecolorallocatealpha( $resized, 0, 0, 0, 127 );
	imagefilledrectangle( $resized, 0, 0, 1024, 1024, $transparent );
	imagecopyresampled( $resized, $source, 0, 0, 0, 0, 1024, 1024, $src_w, $src_h );

	ob_start();
	imagepng( $resized );
	$bytes = ob_get_clean();

	imagedestroy( $source );
	imagedestroy( $resized );

	return ! empty( $bytes ) ? $bytes : false;
}



/**
 * Make Open AI call to get edit images data
 */
function seedprod_lite_generate_image_open_ai_edit_image() {

	if ( check_ajax_referer( 'seedprod_nonce' ) ) {

		if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$post_data = file_get_contents( 'php://input' );

		$data = json_decode( $post_data, true );

		$mask   = isset( $data['edit_image'] ) ? sanitize_text_field( wp_unslash( $data['edit_image'] ) ) : '';
		$prompt = isset( $data['prompt'] ) ? sanitize_text_field( wp_unslash( $data['prompt'] ) ) : '';

		$seedprod_api_key = seedprod_lite_get_api_key();
		$api_key          = $seedprod_api_key;
		$token            = get_option( 'seedprod_token' );
		$api_token        = get_option( 'seedprod_api_token' );

		$image_data = base64_decode( preg_replace( '#^data:image/\w+;base64,#i', '', $mask ) ); // phpcs:ignore

		$image_url = isset( $data['image'] ) ? $data['image'] : '';

		$image_bytes = seedprod_lite_load_image_bytes( $image_url );
		if ( false === $image_bytes ) {
			echo wp_json_encode( array( 'error' => __( 'Could not retrieve source image.', 'coming-soon' ) ) );
			exit;
		}

		// gpt-image-1 requires the mask and source to share dimensions with the
		// `size` param (1024x1024). Resize both — source from its native size,
		// mask from the canvas (whatever the editor produced).
		$image_bytes = seedprod_lite_resize_image_to_1024( $image_bytes );
		if ( false === $image_bytes ) {
			echo wp_json_encode( array( 'error' => __( 'Failed to process source image.', 'coming-soon' ) ) );
			exit;
		}

		$masked_image = imagecreatefromstring( $image_data );
		if ( ! $masked_image ) {
			echo wp_json_encode( array( 'error' => __( 'Failed to process image mask.', 'coming-soon' ) ) );
			exit;
		}
		$rgba_masked_image = imagecreatetruecolor( 1024, 1024 );
		imagealphablending( $rgba_masked_image, false );
		imagesavealpha( $rgba_masked_image, true );
		$transparent_color = imagecolorallocatealpha( $rgba_masked_image, 0, 0, 0, 127 );
		imagefilledrectangle( $rgba_masked_image, 0, 0, 1024, 1024, $transparent_color );
		imagecopyresampled( $rgba_masked_image, $masked_image, 0, 0, 0, 0, 1024, 1024, imagesx( $masked_image ), imagesy( $masked_image ) );
		ob_start();
		imagepng( $rgba_masked_image );
		$mask_bytes = ob_get_clean();
		imagedestroy( $masked_image );
		imagedestroy( $rgba_masked_image );

		$headers_array = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_token,
		);

		$data = array(
			'image'     => base64_encode( $image_bytes ), // phpcs:ignore
			'mask'      => base64_encode( $mask_bytes ), // phpcs:ignore
			'prompt'    => $prompt,
			'api_token' => $api_token,
			'api_key'   => $api_key,
			'token'     => $token,
		);

		$url = SEEDPROD_WEB_API_URL . 'v4/openaieditimagegenerate';

		try {
			$response = wp_remote_post(
				$url,
				array(
					'body'      => wp_json_encode( $data ),
					'headers'   => $headers_array,
					'sslverify' => true,
					'timeout'   => 120,
				)
			);

			if ( is_wp_error( $response ) ) {
				$curl_error = $response->get_error_code();
				if ( 'http_request_failed' === $curl_error ) {
					echo wp_json_encode( array( 'error' => __( 'cURL error:', 'coming-soon' ) . $response->get_error_message() ) );
				} else {
					echo wp_json_encode( array( 'error' => $response->get_error_message() ) );
				}
			} else {

				$http_status = wp_remote_retrieve_response_code( $response );
				if ( 200 === $http_status ) {
					$response_body = wp_remote_retrieve_body( $response );
					$result_data   = json_decode( $response_body, true );

					if ( null === $result_data && json_last_error() !== JSON_ERROR_NONE ) {
						echo wp_json_encode( array( 'error' => __( 'Invalid JSON response', 'coming-soon' ) ) );
					} else {
						echo wp_json_encode( $result_data );
					}
				} else {
					echo wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
				}
			}
		} catch ( Exception $e ) {
			echo wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
		}

		exit;

	}
}

/**
 * Make Open AI call to fetch user credits available.
 */
function seedprod_lite_call_ai_credits() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {

		if ( ! current_user_can( apply_filters( 'seedprod_lpage_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}

		$seedprod_api_key = seedprod_lite_get_api_key();
		$api_key          = $seedprod_api_key;
		$token            = get_option( 'seedprod_token' );
		$api_token        = get_option( 'seedprod_api_token' );

		$data = array(
			'api_token' => $api_token,
			'api_key'   => $api_key,
			'token'     => $token,
		);

		$headers = array(
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $api_token,
		);

		$url = SEEDPROD_WEB_API_URL . 'v4/openaicredits';

		try {

			$response = wp_remote_post(
				$url,
				array(
					'body'      => wp_json_encode( $data ),
					'headers'   => $headers,
					'sslverify' => true,
					'timeout'   => 60,
				)
			);

			if ( is_wp_error( $response ) ) {

				$curl_error = $response->get_error_code();
				if ( 'http_request_failed' === $curl_error ) {
					$result = wp_json_encode( array( 'error' => __( 'cURL error:', 'coming-soon' ) . $response->get_error_message() ) );
				} else {
					$result = wp_json_encode( array( 'error' => $response->get_error_message() ) );
				}
			} else {
				$http_status = wp_remote_retrieve_response_code( $response );
				if ( 200 === $http_status ) {
					$response_body = wp_remote_retrieve_body( $response );
					$result_data   = json_decode( $response_body, true );

					if ( null === $result_data && json_last_error() !== JSON_ERROR_NONE ) {
						$result = wp_json_encode( array( 'error' => __( 'Invalid JSON response', 'coming-soon' ) ) );
					} else {
						$result = wp_json_encode( $result_data );
					}
				} else {
					// Request timeout error.
					$result = wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
				}
			}
		} catch ( Exception $e ) {
			$result = wp_json_encode( array( 'error' => __( 'Server error or request timeout. Try again later.', 'coming-soon' ) ) );
		}

		echo wp_kses_post( $result );

		exit;

	}
}
