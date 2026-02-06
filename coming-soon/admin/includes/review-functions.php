<?php
/**
 * Review Request Functions for V2 Admin
 *
 * Handles plugin review requests using native WordPress admin notices.
 * Only loaded for Lite builds to encourage WordPress.org reviews.
 *
 * @package    SeedProd
 * @since      7.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize review request functionality
 *
 * @since 7.0.0
 */
function seedprod_lite_v2_init_review_request() {
	// Temporarily disabled - will be converted to trigger-based system.
	return;

	// Only show for Lite builds.
	if ( 'lite' !== SEEDPROD_BUILD ) {
		return;
	}

	// Enqueue review notice JavaScript.
	add_action( 'admin_enqueue_scripts', 'seedprod_lite_v2_enqueue_review_scripts' );

	// Admin notice requesting review.
	add_action( 'admin_notices', 'seedprod_lite_v2_review_request' );

	// AJAX handler for dismissing review.
	add_action( 'wp_ajax_seedprod_v2_review_dismiss', 'seedprod_lite_v2_review_dismiss' );
}
add_action( 'admin_init', 'seedprod_lite_v2_init_review_request' );

/**
 * Enqueue review notice JavaScript
 *
 * Only loads on pages where review notice might display.
 *
 * @since 7.0.0
 */
function seedprod_lite_v2_enqueue_review_scripts() {
	// Only enqueue for super admins (same check as review display).
	if ( ! is_super_admin() ) {
		return;
	}

	// Don't load on SeedProd pages (review notice doesn't show there).
	$screen = get_current_screen();
	if ( $screen && false !== strpos( $screen->id, 'seedprod' ) ) {
		return;
	}

	// Enqueue the review notice handler.
	wp_enqueue_script(
		'seedprod-review-notice',
		plugin_dir_url( __DIR__ ) . 'js/review-notice.js',
		array( 'jquery' ),
		SEEDPROD_VERSION,
		true
	);

	// Localize script with nonce for AJAX security.
	wp_localize_script(
		'seedprod-review-notice',
		'seedprodReviewNotice',
		array(
			'nonce' => wp_create_nonce( 'seedprod_review_dismiss' ),
		)
	);
}

/**
 * Display review request admin notice
 *
 * @since 7.0.0
 */
function seedprod_lite_v2_review_request() {
	// Only consider showing the review request to admin users.
	if ( ! is_super_admin() ) {
		return;
	}

	// Don't show on SeedProd pages - too intrusive.
	$screen = get_current_screen();
	if ( $screen && false !== strpos( $screen->id, 'seedprod' ) ) {
		return;
	}

	// If the user has opted out of product announcement notifications.
	if ( get_option( 'seedprod_hide_review' ) ) {
		return;
	}

	// Verify that we can do a check for reviews.
	$review = get_option( 'seedprod_review' );
	$time   = time();
	$load   = false;

	if ( ! $review ) {
		$review = array(
			'time'      => $time,
			'dismissed' => false,
		);
		update_option( 'seedprod_review', $review );
	} elseif ( ( isset( $review['dismissed'] ) && ! $review['dismissed'] ) &&
		( isset( $review['time'] ) && ( ( $review['time'] + DAY_IN_SECONDS ) <= $time ) ) ) {
		// Check if it has been dismissed or not (show after 1 day).
		$load = true;
	}

	// If we cannot load, return early.
	if ( ! $load ) {
		return;
	}

	// Check if plugin has been installed for at least 7 days.
	$activated = get_option( 'seedprod_over_time', array() );

	if ( ! empty( $activated['installed_date'] ) ) {
		// Only continue if plugin has been installed for at least 7 days.
		if ( ( $activated['installed_date'] + ( DAY_IN_SECONDS * 7 ) ) > time() ) {
			return;
		}

		// Only if version greater than or equal to 6.0.8.5.
		if ( ! empty( $activated['installed_version'] ) &&
			version_compare( $activated['installed_version'], '6.0.8.5' ) < 0 ) {
			return;
		}
	} else {
		// First time tracking installation.
		$data = array(
			'installed_version' => SEEDPROD_VERSION,
			'installed_date'    => time(),
		);
		update_option( 'seedprod_over_time', $data );
		return;
	}

	// Display the review notice.
	seedprod_lite_v2_display_review_notice();
}

/**
 * Display the actual review notice HTML
 *
 * @since 7.0.0
 */
function seedprod_lite_v2_display_review_notice() {
	$feedback_url = 'https://www.seedprod.com/plugin-feedback/?utm_source=liteplugin&utm_medium=review-notice&utm_campaign=feedback&utm_content=' . SEEDPROD_VERSION;
	?>
	<div class="notice notice-info is-dismissible seedprod-v2-review-notice" data-seedprod-review="1">
		<div class="seedprod-review-step seedprod-review-step-1">
			<p><strong><?php esc_html_e( 'Are you enjoying SeedProd?', 'coming-soon' ); ?></strong></p>
			<p>
				<a href="#" class="button button-primary seedprod-review-switch-step" data-step="3">
					<?php esc_html_e( 'Yes, I love it!', 'coming-soon' ); ?>
				</a>
				&nbsp;
				<a href="#" class="button seedprod-review-switch-step" data-step="2">
					<?php esc_html_e( 'Not Really', 'coming-soon' ); ?>
				</a>
				&nbsp;
				<a href="#" class="seedprod-review-dismiss-link">
					<?php esc_html_e( 'Ask me later', 'coming-soon' ); ?>
				</a>
			</p>
		</div>

		<div class="seedprod-review-step seedprod-review-step-2" style="display: none">
			<p><?php esc_html_e( 'We\'re sorry to hear you aren\'t enjoying SeedProd. We would love a chance to improve. Could you take a minute and let us know what we can do better?', 'coming-soon' ); ?></p>
			<p>
				<a href="<?php echo esc_url( $feedback_url ); ?>"
					class="button button-primary seedprod-dismiss-review-notice seedprod-review-out"
					target="_blank"
					rel="noopener noreferrer">
					<?php esc_html_e( 'Give Feedback', 'coming-soon' ); ?>
				</a>
				&nbsp;
				<a href="#" class="button seedprod-dismiss-review-notice">
					<?php esc_html_e( 'No thanks', 'coming-soon' ); ?>
				</a>
			</p>
		</div>

		<div class="seedprod-review-step seedprod-review-step-3" style="display: none">
			<p><?php esc_html_e( 'That\'s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'coming-soon' ); ?></p>
			<p><strong><?php echo wp_kses( __( '~ John Turner<br>Co-Founder of SeedProd', 'coming-soon' ), array( 'br' => array() ) ); ?></strong></p>
			<p>
				<a href="https://wordpress.org/support/plugin/coming-soon/reviews/?filter=5#new-post"
					class="button button-primary seedprod-dismiss-review-notice seedprod-review-out"
					target="_blank"
					rel="noopener noreferrer">
					<?php esc_html_e( 'Ok, you deserve it', 'coming-soon' ); ?>
				</a>
				&nbsp;
				<a href="#" class="button seedprod-dismiss-review-notice">
					<?php esc_html_e( 'Nope, maybe later', 'coming-soon' ); ?>
				</a>
				&nbsp;
				<a href="#" class="seedprod-dismiss-review-notice-permanent">
					<?php esc_html_e( 'I already did', 'coming-soon' ); ?>
				</a>
			</p>
		</div>
	</div>
	<?php
}

/**
 * AJAX handler to dismiss the review notice
 *
 * @since 7.0.0
 */
function seedprod_lite_v2_review_dismiss() {
	// Verify nonce for security.
	check_ajax_referer( 'seedprod_review_dismiss', 'nonce' );

	// Security check - verify user capability.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die();
	}

	// Check if this is a permanent dismissal (sanitize input).
	$permanent = isset( $_POST['permanent'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['permanent'] ) );

	if ( $permanent ) {
		// User already reviewed - don't ask again.
		update_option( 'seedprod_hide_review', true );
	} else {
		// Temporary dismissal - ask again later.
		$review              = get_option( 'seedprod_review', array() );
		$review['time']      = time();
		$review['dismissed'] = true;
		update_option( 'seedprod_review', $review );
	}

	wp_die();
}
