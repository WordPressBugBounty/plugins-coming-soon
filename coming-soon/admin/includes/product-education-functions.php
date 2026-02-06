<?php
/**
 * Product Education functions for SeedProd Lite
 *
 * Modular, reusable functions for displaying upgrade prompts and product education
 * in the Lite version. These functions are designed to be DRY and consistent.
 *
 * @package    SeedProd
 * @subpackage SeedProd/admin/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the upgrade URL for a specific feature
 *
 * @param string $feature The feature identifier (e.g., 'website-builder', 'login-page', '404-page').
 * @param string $context Additional context for tracking (e.g., 'dashboard', 'settings').
 * @return string The upgrade URL with proper UTM parameters.
 */
function seedprod_lite_v2_get_upgrade_url( $feature = '', $context = 'dashboard' ) {
	$base_url = 'https://www.seedprod.com/lite-upgrade/';

	// Build UTM parameters.
	$utm_params = array(
		'utm_source'   => 'WordPress',
		'utm_campaign' => 'liteplugin',
		'utm_medium'   => 'plugin' . $context,
		'utm_content'  => $feature,
	);

	return add_query_arg( $utm_params, $base_url );
}

/**
 * Display a Pro feature badge
 *
 * @param string $type Badge type ('inline' or 'overlay').
 * @return string HTML for the Pro badge.
 */
function seedprod_lite_v2_get_pro_badge( $type = 'inline' ) {
	if ( 'overlay' === $type ) {
		// Overlay badge with lock icon (for feature cards).
		return '<div class="seedprod-pro-overlay-badge">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="seedprod-lock-icon">
				<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
			</svg>
		</div>';
	} else {
		// Inline badge for text labels.
		return '<span class="seedprod-pro-badge">PRO</span>';
	}
}

/**
 * Display an upgrade button for Pro features
 *
 * @param array $args {
 *     Optional. An array of arguments for the upgrade button.
 *     @type string $feature   Feature identifier for tracking.
 *     @type string $context   Context for tracking.
 *     @type string $text      Button text. Default 'Upgrade to Pro'.
 *     @type string $class     Additional CSS classes.
 *     @type string $size      Button size ('small', 'medium', 'large'). Default 'small'.
 *     @type bool   $show_icon Whether to show star icon. Default true.
 * }
 * @return string HTML for the upgrade button.
 */
function seedprod_lite_v2_get_upgrade_button( $args = array() ) {
	$defaults = array(
		'feature'   => '',
		'context'   => 'dashboard',
		'text'      => __( 'Upgrade to Pro', 'coming-soon' ),
		'class'     => '',
		'size'      => 'small',
		'show_icon' => true,
	);

	$args = wp_parse_args( $args, $defaults );

	$url = seedprod_lite_v2_get_upgrade_url( $args['feature'], $args['context'] );

	// Build button classes.
	$button_classes = array( 'button', 'seedprod-upgrade-button' );

	// Size class.
	if ( 'large' === $args['size'] ) {
		$button_classes[] = 'button-hero';
	} elseif ( 'small' === $args['size'] ) {
		$button_classes[] = 'button-small';
	}

	// Custom classes.
	if ( ! empty( $args['class'] ) ) {
		$button_classes[] = $args['class'];
	}

	$button_class = implode( ' ', $button_classes );

	// Build button HTML.
	$button_html = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="' . esc_attr( $button_class ) . '">';

	if ( $args['show_icon'] ) {
		$button_html .= '<span class="dashicons dashicons-star-filled seedprod-upgrade-icon"></span>';
	}

	$button_html .= esc_html( $args['text'] );
	$button_html .= '</a>';

	return $button_html;
}

/**
 * Display a product education card for Pro features
 *
 * @param array $args {
 *     Required. An array of arguments for the education card.
 *     @type string $title       Card title.
 *     @type string $description Card description.
 *     @type string $feature     Feature identifier for tracking.
 *     @type string $icon        Dashicon class (e.g., 'dashicons-admin-appearance').
 *     @type bool   $show_badge  Whether to show PRO badge. Default true.
 *     @type array  $benefits    Array of benefit strings to display.
 * }
 * @return string HTML for the product education card.
 */
function seedprod_lite_v2_get_education_card( $args = array() ) {
	$defaults = array(
		'title'       => '',
		'description' => '',
		'feature'     => '',
		'icon'        => '',
		'show_badge'  => true,
		'benefits'    => array(),
	);

	$args = wp_parse_args( $args, $defaults );

	ob_start();
	?>
	<div class="seedprod-education-card seedprod-pro-feature" data-feature="<?php echo esc_attr( $args['feature'] ); ?>">
		<?php if ( $args['show_badge'] ) : ?>
			<?php echo seedprod_lite_v2_get_pro_badge( 'overlay' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function returns safe HTML. ?>
		<?php endif; ?>
		
		<div class="seedprod-education-content">
			<?php if ( ! empty( $args['icon'] ) ) : ?>
				<span class="dashicons <?php echo esc_attr( $args['icon'] ); ?> seedprod-education-icon"></span>
			<?php endif; ?>
			
			<h3 class="seedprod-education-title">
				<?php echo esc_html( $args['title'] ); ?>
				<?php if ( $args['show_badge'] ) : ?>
					<?php echo seedprod_lite_v2_get_pro_badge( 'inline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function returns safe HTML. ?>
				<?php endif; ?>
			</h3>
			
			<?php if ( ! empty( $args['description'] ) ) : ?>
				<p class="seedprod-education-description"><?php echo esc_html( $args['description'] ); ?></p>
			<?php endif; ?>
			
			<?php if ( ! empty( $args['benefits'] ) ) : ?>
				<ul class="seedprod-education-benefits">
					<?php foreach ( $args['benefits'] as $benefit ) : ?>
						<li>
							<span class="dashicons dashicons-yes-alt"></span>
							<?php echo esc_html( $benefit ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			
			<div class="seedprod-education-action">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function returns safe HTML.
				echo seedprod_lite_v2_get_upgrade_button(
					array(
						'feature' => $args['feature'],
						'size'    => 'medium',
					)
				);
				?>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Display an inline upgrade prompt for setup items
 *
 * This is used in the dashboard for Pro-only features in Lite version.
 * It replaces the toggle/edit controls with an upgrade button.
 *
 * @param string $feature Feature identifier for tracking.
 * @return string HTML for the inline upgrade prompt.
 */
function seedprod_lite_v2_get_setup_item_upgrade( $feature = '' ) {
	ob_start();
	?>
	<div class="seedprod-setup-item-upgrade">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function returns safe HTML.
		echo seedprod_lite_v2_get_upgrade_button(
			array(
				'feature' => $feature,
				'context' => 'dashboard-setup',
				'size'    => 'small',
			)
		);
		?>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Display a blurred overlay with upgrade prompt
 *
 * Used for sections that are visible but not accessible in Lite.
 * Similar to the Vue Lite CTA pattern.
 *
 * @param array $args {
 *     Optional. An array of arguments for the overlay.
 *     @type string $title       Overlay title.
 *     @type string $description Overlay description.
 *     @type string $feature     Feature identifier for tracking.
 *     @type string $button_text Button text. Default 'Upgrade to Pro'.
 * }
 * @return string HTML for the blurred overlay.
 */
function seedprod_lite_v2_get_blurred_overlay( $args = array() ) {
	$defaults = array(
		'title'       => __( 'This is a Pro Feature', 'coming-soon' ),
		'description' => __( 'Upgrade to Pro to unlock all features and take your website to the next level.', 'coming-soon' ),
		'feature'     => '',
		'button_text' => __( 'Upgrade to Pro', 'coming-soon' ),
	);

	$args = wp_parse_args( $args, $defaults );

	ob_start();
	?>
	<div class="seedprod-blurred-overlay-container">
		<div class="seedprod-blurred-overlay">
			<div class="seedprod-overlay-content">
				<h2><?php echo esc_html( $args['title'] ); ?></h2>
				<p><?php echo esc_html( $args['description'] ); ?></p>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function returns safe HTML.
				echo seedprod_lite_v2_get_upgrade_button(
					array(
						'feature'   => $args['feature'],
						'text'      => $args['button_text'],
						'size'      => 'large',
						'class'     => 'seedprod-overlay-button',
						'show_icon' => true,
					)
				);
				?>
			</div>
		</div>
		<div class="seedprod-blurred-content">
			<!-- Content to be blurred goes here -->
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Check if we should show product education for a feature
 *
 * @param string $feature Feature to check.
 * @return boolean True if we should show product education (Lite version).
 */
function seedprod_lite_v2_should_show_education( $feature = '' ) {
	// Check if this is the Lite version.
	$is_lite_view = seedprod_lite_v2_is_lite_view();
	if ( ! $is_lite_view ) {
		return false;
	}

	// List of features that are Pro-only in Lite.
	$pro_only_features = array(
		'website-builder',
		'login-page',
		'404-page',
		'advanced-integrations',
		'custom-code',
		'popups',
		'woocommerce',
		'edd',
		'domain-mapping',
		'dynamic-text',
		'conditional-logic',
		'user-roles',
		'scheduled-maintenance',
	);

	return in_array( $feature, $pro_only_features, true );
}

/**
 * Get education content for specific features
 *
 * Returns predefined education content for consistency.
 *
 * @param string $feature Feature identifier.
 * @return array Education content array with title, description, and benefits.
 */
function seedprod_lite_v2_get_education_content( $feature = '' ) {
	$content = array(
		'website-builder' => array(
			'title'       => __( 'Website Builder', 'coming-soon' ),
			'description' => __( 'Build your entire website with drag & drop simplicity. Create headers, footers, pages, posts, and more.', 'coming-soon' ),
			'icon'        => 'dashicons-admin-appearance',
			'benefits'    => array(
				__( 'Complete theme builder', 'coming-soon' ),
				__( 'Custom headers & footers', 'coming-soon' ),
				__( 'Archive & category pages', 'coming-soon' ),
				__( 'WooCommerce integration', 'coming-soon' ),
			),
		),
		'login-page'      => array(
			'title'       => __( 'Custom Login Page', 'coming-soon' ),
			'description' => __( 'Create a beautiful custom login page that matches your brand.', 'coming-soon' ),
			'icon'        => 'dashicons-admin-network',
			'benefits'    => array(
				__( 'Brand-matched login', 'coming-soon' ),
				__( 'Custom backgrounds', 'coming-soon' ),
				__( 'Social login options', 'coming-soon' ),
				__( 'Redirect rules', 'coming-soon' ),
			),
		),
		'404-page'        => array(
			'title'       => __( 'Custom 404 Page', 'coming-soon' ),
			'description' => __( 'Turn lost visitors into customers with a custom 404 page.', 'coming-soon' ),
			'icon'        => 'dashicons-warning',
			'benefits'    => array(
				__( 'Reduce bounce rate', 'coming-soon' ),
				__( 'Custom messaging', 'coming-soon' ),
				__( 'Search functionality', 'coming-soon' ),
				__( 'Popular pages links', 'coming-soon' ),
			),
		),
	);

	return isset( $content[ $feature ] ) ? $content[ $feature ] : array();
}
