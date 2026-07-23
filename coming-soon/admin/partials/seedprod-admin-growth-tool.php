<?php
/**
 * Growth Tool promotional page template
 *
 * This template displays promotional content for partner plugins.
 * It expects a $growth_tool_config array to be set with the following keys:
 * - partner_name: Name of the partner (e.g., 'OptinMonster', 'WPCode')
 * - headline: Main headline text
 * - subheadline: Subtitle text
 * - benefits: Array of benefit strings
 * - cta_headline: Call-to-action headline
 * - cta_subtext: Text below the button
 * - social_proof: Social proof text
 * - testimonials: Optional array of quotes, each with 'text' and 'author' keys
 * - setup_url: Optional URL to redirect to after the plugin is activated
 * - learn_more_url: Optional external partner-site link shown under the CTA
 * - learn_more_text: Link text for learn_more_url
 * - image: Image filename in growth-tools folder
 * - plugin_slug: Full plugin path (e.g., 'optinmonster/optin-monster-wp-api.php')
 * - plugin_id: Plugin ID for installation (e.g., 'optinmonster')
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure config is set.
if ( ! isset( $growth_tool_config ) ) {
	return;
}

// Extract config for easier use.
$config = $growth_tool_config;

// Get plugin status.
$is_installed = false;
$is_active    = false;
$status_code  = 0; // 0 = not installed, 1 = active, 2 = inactive

if ( file_exists( WP_PLUGIN_DIR . '/' . $config['plugin_slug'] ) ) {
	$is_installed = true;
	if ( is_plugin_active( $config['plugin_slug'] ) ) {
		$is_active   = true;
		$status_code = 1;
	} else {
		$status_code = 2;
	}
}

// Set button text and class based on status.
/* translators: %s: Partner name (e.g., OptinMonster, WPCode) */
$button_text  = sprintf( __( 'Install %s Now', 'coming-soon' ), $config['partner_name'] );
$button_class = 'button button-primary seedprod-button-primary seedprod-plugin-button';

if ( 2 === $status_code ) {
	// Installed but not active.
	/* translators: %s: Partner name (e.g., OptinMonster, WPCode) */
	$button_text  = sprintf( __( 'Activate %s', 'coming-soon' ), $config['partner_name'] );
	$button_class = 'button button-primary seedprod-button-primary seedprod-plugin-button';
} elseif ( 1 === $status_code ) {
	// Active - show deactivate.
	$button_text  = __( 'Deactivate', 'coming-soon' );
	$button_class = 'button seedprod-button-secondary seedprod-plugin-button';
}
?>

<div class="seedprod-growth-tools-page">
	<div class="postbox seedprod-card">
		<div class="inside">
			<!-- Partner Badge -->
			<div class="seedprod-partner-badge">
				<div class="seedprod-partner-badge-content">
					<span class="dashicons dashicons-heart"></span>
					<span class="seedprod-partner-badge-text">
						<strong><?php esc_html_e( 'SeedProd', 'coming-soon' ); ?></strong>
						<?php esc_html_e( 'recommends', 'coming-soon' ); ?>
						<strong><?php echo esc_html( $config['partner_name'] ); ?></strong>
						<span class="seedprod-partner-badge-separator">•</span>
						<span class="seedprod-partner-badge-trusted">
							<span class="dashicons dashicons-awards"></span>
							<?php esc_html_e( 'Trusted Partner', 'coming-soon' ); ?>
						</span>
					</span>
				</div>
			</div>

			<!-- Header Section -->
			<div class="seedprod-growth-header">
				<h1><?php echo esc_html( $config['headline'] ); ?></h1>
				<p class="seedprod-subtitle">
					<?php echo esc_html( $config['subheadline'] ); ?>
				</p>
			</div>

			<!-- Hero Image Section -->
			<div class="seedprod-growth-hero">
				<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/growth-tools/' . $config['image'] ); ?>" 
					alt="<?php echo esc_attr( $config['partner_name'] ); ?>">
			</div>

			<!-- Benefit Points Section -->
			<div class="seedprod-benefit-points">
				<ul>
					<?php foreach ( $config['benefits'] as $benefit ) : ?>
						<li><?php echo esc_html( $benefit ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<?php if ( ! empty( $config['testimonials'] ) ) : ?>
				<!-- Testimonials Section -->
				<div class="seedprod-growth-testimonials">
					<?php foreach ( $config['testimonials'] as $testimonial ) : ?>
						<blockquote class="seedprod-growth-testimonial">
							<span class="seedprod-testimonial-stars" aria-label="<?php esc_attr_e( '5-star rating', 'coming-soon' ); ?>">
								<?php for ( $i = 0; $i < 5; $i++ ) : ?>
									<span class="dashicons dashicons-star-filled"></span>
								<?php endfor; ?>
							</span>
							<p><?php echo esc_html( $testimonial['text'] ); ?></p>
							<cite>
								<?php echo esc_html( $testimonial['author'] ); ?>
								<span class="seedprod-testimonial-source"><?php esc_html_e( 'WordPress.org review', 'coming-soon' ); ?></span>
							</cite>
						</blockquote>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- CTA Section -->
			<div class="seedprod-growth-cta">
				<p class="seedprod-cta-headline">
					<?php echo esc_html( $config['cta_headline'] ); ?>
				</p>
				<button
					class="<?php echo esc_attr( $button_class ); ?>"
					data-plugin-slug="<?php echo esc_attr( $config['plugin_slug'] ); ?>"
					data-plugin-id="<?php echo esc_attr( $config['plugin_id'] ); ?>"
					data-status="<?php echo esc_attr( $status_code ); ?>"
					<?php if ( ! empty( $config['setup_url'] ) ) : ?>
						data-redirect="<?php echo esc_url( $config['setup_url'] ); ?>"
					<?php endif; ?>>
					<span class="button-text"><?php echo esc_html( $button_text ); ?></span>
					<span class="button-spinner" style="display:none;">
						<span class="spinner is-active" style="float: none; margin: 0;"></span>
					</span>
				</button>
				<p class="seedprod-cta-subtext">
					<?php echo esc_html( $config['cta_subtext'] ); ?>
				</p>
				<p class="seedprod-cta-social-proof">
					<?php echo esc_html( $config['social_proof'] ); ?>
				</p>
				<?php if ( ! empty( $config['learn_more_url'] ) ) : ?>
					<p class="seedprod-cta-learn-more">
						<a href="<?php echo esc_url( $config['learn_more_url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $config['learn_more_text'] ?? __( 'Learn more', 'coming-soon' ) ); ?> &rarr;
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
