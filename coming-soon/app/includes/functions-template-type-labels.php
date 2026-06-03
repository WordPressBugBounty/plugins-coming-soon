<?php
/**
 * Template-type and content-type label map.
 *
 * @package SeedProd
 * @subpackage SeedProd/app/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'seedprod_lite_get_template_type_labels' ) ) {
	/**
	 * Return the template-type and content-type label map.
	 *
	 * @since 6.21.0
	 *
	 * @return array<string,string>
	 */
	function seedprod_lite_get_template_type_labels() {
		static $labels = null;

		if ( null !== $labels ) {
			return $labels;
		}

		$labels = array(
			'header'              => __( 'Header', 'coming-soon' ),
			'footer'              => __( 'Footer', 'coming-soon' ),
			'part'                => __( 'Template Part', 'coming-soon' ),
			'sidebar'             => __( 'Sidebar', 'coming-soon' ),
			'section'             => __( 'Section', 'coming-soon' ),
			'single'              => __( 'Single', 'coming-soon' ),
			'archive'             => __( 'Archive Page', 'coming-soon' ),
			'single_post'         => __( 'Single Post', 'coming-soon' ),
			'single_page'         => __( 'Single Page', 'coming-soon' ),
			'single_product'      => __( 'Single Product', 'coming-soon' ),
			'archive_product'     => __( 'Product Archive', 'coming-soon' ),
			'woocommerce_single'  => __( 'Single Product', 'coming-soon' ),
			'woocommerce_archive' => __( 'Product Archive', 'coming-soon' ),
			'search'              => __( 'Search Results', 'coming-soon' ),
			'author'              => __( 'Author Page', 'coming-soon' ),
			'404'                 => __( '404 Page', 'coming-soon' ),
			'home'                => __( 'Home Page', 'coming-soon' ),
			'blog'                => __( 'Blog Page', 'coming-soon' ),
			'css'                 => __( 'Global CSS', 'coming-soon' ),
			'lp'                  => __( 'Landing Page', 'coming-soon' ),
			'cs'                  => __( 'Coming Soon', 'coming-soon' ),
			'mm'                  => __( 'Maintenance', 'coming-soon' ),
			'p404'                => __( '404 Page', 'coming-soon' ),
			'loginp'              => __( 'Login Page', 'coming-soon' ),
			'page'                => __( 'Page', 'coming-soon' ),
			'post'                => __( 'Post', 'coming-soon' ),
		);

		$filtered = apply_filters( 'seedprod_lite_template_type_labels', $labels );

		if ( is_array( $filtered ) ) {
			$labels = $filtered;
		}

		return $labels;
	}
}

if ( ! function_exists( 'seedprod_lite_resolve_effective_template_type' ) ) {
	/**
	 * Derive a specific template-type slug from theme-template conditions
	 * when the stored type is the generic 'page'. Non-'page' types are
	 * returned unchanged.
	 *
	 * @since 6.21.0
	 *
	 * @param string             $type       Stored template type meta.
	 * @param string|array|object $conditions Conditions JSON, array, or stdClass.
	 * @return string Effective type slug for the canonical label map.
	 */
	function seedprod_lite_resolve_effective_template_type( $type, $conditions ) {
		if ( 'page' !== $type ) {
			return $type;
		}

		if ( is_string( $conditions ) ) {
			$conditions = json_decode( $conditions );
		}

		if ( ! is_array( $conditions ) && ! is_object( $conditions ) ) {
			return $type;
		}

		$map = array(
			'is_single'            => 'single_post',
			'is_singular'          => 'single',
			'is_page'              => 'single_page',
			'is_archive'           => 'archive',
			'is_date'              => 'archive',
			'is_category'          => 'archive',
			'is_tag'               => 'archive',
			'is_tax'               => 'archive',
			'is_post_type_archive' => 'archive',
			'is_home'              => 'blog',
			'is_front_page'        => 'home',
			'is_search'            => 'search',
			'is_404'               => '404',
			'is_author'            => 'author',
			'is_product'           => 'single_product',
			'is_shop'              => 'archive_product',
			'is_product_category'  => 'archive_product',
			'is_product_tag'       => 'archive_product',
		);

		foreach ( (array) $conditions as $condition ) {
			$mode = is_object( $condition ) ? ( isset( $condition->condition ) ? $condition->condition : 'include' ) : ( isset( $condition['condition'] ) ? $condition['condition'] : 'include' );
			if ( 'exclude' === $mode ) {
				continue;
			}
			$tag = is_object( $condition ) ? ( isset( $condition->type ) ? $condition->type : '' ) : ( isset( $condition['type'] ) ? $condition['type'] : '' );
			if ( empty( $tag ) ) {
				continue;
			}
			$tag = strtolower( preg_replace( '/\(.*$/', '', $tag ) );
			if ( isset( $map[ $tag ] ) ) {
				return $map[ $tag ];
			}
		}

		return $type;
	}
}
