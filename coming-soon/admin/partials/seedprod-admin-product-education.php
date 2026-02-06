<?php
/**
 * Product Education page template
 *
 * This template displays educational content for Pro features to Lite users.
 * It expects a $product_education_config array to be set with the following keys:
 * - feature_name: Name of the feature (e.g., 'Website Builder')
 * - headline: Main headline text
 * - subheadline: Subtitle text
 * - description: Detailed description paragraph
 * - benefits: Array of benefit strings
 * - features: Array of feature highlights
 * - image: Image filename or path
 * - video_url: Optional video URL
 * - testimonial: Optional testimonial array with 'text', 'author', 'company'
 * - cta_headline: Call-to-action headline
 * - feature_slug: Feature identifier for tracking
 *
 * @package SeedProd
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure config is set.
if ( ! isset( $product_education_config ) ) {
	return;
}

// Extract config for easier use.
$config = $product_education_config;

// Get upgrade URL with proper tracking.
if ( isset( $config['use_exact_utm_medium'] ) && true === $config['use_exact_utm_medium'] ) {
	// Use exact UTM medium without 'plugin' prefix for legacy tracking compatibility.
	$medium      = isset( $config['cta_context'] ) ? $config['cta_context'] : 'product-education';
	$upgrade_url = seedprod_lite_get_external_link(
		'https://www.seedprod.com/lite-upgrade/',
		$medium,
		'liteplugin'
	);
	// Add utm_content if feature_slug is set.
	if ( ! empty( $config['feature_slug'] ) ) {
		$upgrade_url = add_query_arg( 'utm_content', $config['feature_slug'], $upgrade_url );
	}
} else {
	// Default: use the standard function which adds 'plugin' prefix.
	$context     = isset( $config['cta_context'] ) ? $config['cta_context'] : 'product-education';
	$upgrade_url = seedprod_lite_v2_get_upgrade_url( $config['feature_slug'], $context );
}
?>

<div class="seedprod-product-education-page">
	<div class="postbox seedprod-card">
		<div class="inside">
			<!-- Main 50-50 Layout Container -->
			<div class="seedprod-product-education-main">
				
				<!-- Left Column -->
				<div class="seedprod-product-education-left">
					<!-- PRO Feature Badge -->
					<div class="seedprod-product-education-badge">
						<span class="dashicons dashicons-lock"></span>
						<span class="seedprod-product-education-badge-text">
							<strong><?php esc_html_e( 'PRO Feature', 'coming-soon' ); ?></strong>
						</span>
					</div>

					<!-- Headline -->
					<h1><?php echo esc_html( $config['headline'] ); ?></h1>
					
					<!-- Subheadline -->
					<p class="seedprod-product-education-subtitle">
						<?php echo esc_html( $config['subheadline'] ); ?>
					</p>
					
					<!-- Description -->
					<?php if ( ! empty( $config['description'] ) ) : ?>
						<p class="seedprod-product-education-description">
							<?php echo esc_html( $config['description'] ); ?>
						</p>
					<?php endif; ?>

					<!-- Benefits List -->
					<?php if ( ! empty( $config['benefits'] ) ) : ?>
						<div class="seedprod-product-education-benefits">
							<ul>
								<?php foreach ( $config['benefits'] as $benefit ) : ?>
									<li>
										<span class="dashicons dashicons-yes-alt"></span>
										<?php echo esc_html( $benefit ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<!-- CTA Button -->
					<div class="seedprod-product-education-cta">
						<a href="<?php echo esc_url( $upgrade_url ); ?>"
							target="_blank"
							rel="noopener"
							class="button button-hero seedprod-upgrade-button">
							<span class="dashicons dashicons-star-filled seedprod-upgrade-icon"></span>
							<?php
							// Use custom button text if provided, otherwise default to "Unlock [Feature]".
							if ( ! empty( $config['primary_button']['text'] ) ) {
								$button_text = $config['primary_button']['text'];
							} else {
								/* translators: %s: Feature name (e.g., Website Builder, Login Page) */
								$button_text = sprintf( __( 'Unlock %s', 'coming-soon' ), $config['feature_name'] );
							}
							echo esc_html( $button_text );
							?>
						</a>

						<?php // Secondary button (optional) - for alternative actions like community support. ?>
						<?php if ( ! empty( $config['secondary_button'] ) ) : ?>
							<a href="<?php echo esc_url( $config['secondary_button']['url'] ); ?>"
								<?php echo ! empty( $config['secondary_button']['new_tab'] ) ? 'target="_blank" rel="noopener"' : ''; ?>
								class="button button-hero seedprod-button-secondary seedprod-secondary-button">
								<?php if ( ! empty( $config['secondary_button']['icon'] ) ) : ?>
									<span class="dashicons <?php echo esc_attr( $config['secondary_button']['icon'] ); ?>"></span>
								<?php endif; ?>
								<?php echo esc_html( $config['secondary_button']['text'] ); ?>
							</a>
						<?php endif; ?>

						<p class="seedprod-product-education-guarantee">
							<span class="dashicons dashicons-shield"></span>
							<?php esc_html_e( '14-Day Money Back Guarantee', 'coming-soon' ); ?>
						</p>
					</div>
				</div>

				<!-- Right Column -->
				<div class="seedprod-product-education-right">
					<!-- Hero Image/Video -->
					<?php if ( ! empty( $config['video_url'] ) ) : ?>
						<div class="seedprod-product-education-video">
							<iframe src="<?php echo esc_url( $config['video_url'] ); ?>" 
									frameborder="0" 
									allowfullscreen
									title="<?php echo esc_attr( $config['feature_name'] . ' Demo' ); ?>">
							</iframe>
						</div>
					<?php elseif ( ! empty( $config['image'] ) ) : ?>
						<?php if ( ! empty( $config['image_link'] ) ) : ?>
							<a href="<?php echo esc_url( $config['image_link'] ); ?>" target="_blank" rel="noopener">
						<?php endif; ?>
						<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'images/' . $config['image'] ); ?>"
							alt="<?php echo esc_attr( $config['feature_name'] ); ?>"
							class="seedprod-product-education-screenshot">
						<?php if ( ! empty( $config['image_link'] ) ) : ?>
							</a>
							<div class="seedprod-template-link-wrapper" style="text-align: center; margin-top: 15px;">
								<a href="<?php echo esc_url( $config['image_link'] ); ?>" target="_blank" rel="noopener" class="seedprod-template-link" style="color: #E14E1B; font-size: 16px; font-weight: 500; text-decoration: none;">
									<?php esc_html_e( 'Browse 350+ Website Templates', 'coming-soon' ); ?> &rarr;
								</a>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<!-- Bottom Full Width Section -->
			<div class="seedprod-product-education-bottom">
				<!-- Testimonial Section -->
				<?php if ( ! empty( $config['testimonial'] ) ) : ?>
					<div class="seedprod-product-education-testimonial">
						<blockquote>
							<p>"<?php echo esc_html( $config['testimonial']['text'] ); ?>"</p>
							<cite>
								<strong><?php echo esc_html( $config['testimonial']['author'] ); ?></strong>
								<?php if ( ! empty( $config['testimonial']['company'] ) ) : ?>
									<span> - <?php echo esc_html( $config['testimonial']['company'] ); ?></span>
								<?php endif; ?>
							</cite>
						</blockquote>
					</div>
				<?php endif; ?>

				<!-- Social Proof -->
				<p class="seedprod-product-education-users">
					<?php esc_html_e( 'Join 1,000,000+ professionals using SeedProd', 'coming-soon' ); ?>
				</p>
			</div>
		</div>
	</div>
</div>
