<?php

add_filter( 'template_include', 'seedprod_lite_lppage_render', PHP_INT_MAX );

/**
 * Landing Page Render
 */
function seedprod_lite_lppage_render( $template ) {
	global $post;
	if ( ! empty( $post ) ) {
		$has_settings = get_post_meta( $post->ID, '_seedprod_page', true );

		if ( ! empty( $has_settings ) && ( 'page' === $post->post_type || 'seedprod' === $post->post_type ) && ! is_search() ) {

			$template = SEEDPROD_PLUGIN_PATH . 'resources/views/seedprod-preview.php';
			add_action( 'wp_enqueue_scripts', 'seedprod_lite_deregister_styles', PHP_INT_MAX );
		}
	}
	return $template;
}

/**
 * Clean theme styles on our custom landing pages.
 */
function seedprod_lite_deregister_styles() {
	global $wp_styles;

	// Remove WordPress global styles that override custom typography.
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'global-styles-inline-css' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'wp-block-library-theme' );

	// Remove theme-specific styles.
	foreach ( $wp_styles->queue as $handle ) {
		if ( ! empty( $wp_styles->registered[ $handle ]->src ) ) {
			if ( strpos( $wp_styles->registered[ $handle ]->src, 'wp-content/themes' ) !== false ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}
}

