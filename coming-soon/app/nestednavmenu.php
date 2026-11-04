<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'DOING_AJAX' ) ) {
}
/**
 * Ajax call to fetch selected WordPress inside seedprod builder.
 */
function seedprod_lite_get_nested_navmenu() {
	if ( check_ajax_referer( 'seedprod_nonce' ) ) {
		if ( ! current_user_can( apply_filters( 'seedprod_navmenu_capability', 'edit_others_posts' ) ) ) {
			wp_send_json_error();
		}
		$navmenu_name = isset( $_REQUEST['navmenu_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['navmenu_name'] ) ) : '';
		$divider      = isset( $_REQUEST['divider'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['divider'] ) ) : '';
		$layout       = isset( $_REQUEST['layout'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['layout'] ) ) : '';

		$walker_divider = true;
		if ( '' === $divider || 'v' === $layout ) {
			$walker_divider = false;
		}

		if ( true === $walker_divider ) {

			$args = array(
				'menu'            => $navmenu_name,
				'container_class' => 'nav-menu-bar',
				'menu_class'      => 'seedprod-menu-list',
				'walker'          => new SeedProd_Lite_Menu_Walker( $divider ),
			);

		} else {
			$args = array(
				'menu'            => $navmenu_name,
				'container_class' => 'nav-menu-bar',
				'menu_class'      => 'seedprod-menu-list',
			);
		}
		wp_nav_menu( $args );

		wp_die();

	}
}

/**
 * SeedProd Menu Walker Class for adding menu list divider
 */
class SeedProd_Lite_Menu_Walker extends Walker_Nav_Menu {
	/**
	 * Separators value.
	 *
	 * @var string
	 */
	private $separators;

	/**
	 * Create class instance.
	 *
	 * @param string $separators Separators passed to class.
	 */
	public function __construct( $separators = '' ) {
		$this->separators = $separators;
		add_filter( 'wp_nav_menu_items', 'seedprod_lite_remove_last_divider' );
	}

	/**
	 * Add/List separators.
	 *
	 * @param string  $output Output string.
	 * @param string  $item   Item string.
	 * @param integer $depth  Depth integer.
	 * @param array   $args   Args array.
	 * @return void
	 */
	public function end_el( &$output, $item, $depth = 0, $args = array() ) {
		$output .= '</li>';

		if ( 0 === $depth ) {
			if ( '' !== $this->separators ) {
				$output .= "<li class='separator menu-item'>" . esc_html( $this->separators ) . '</li>';
			}
		}
	}
}

/**
 * Shortcode to fetch select WordPress menu
 */
add_shortcode( 'seedprodnestedmenuwidget', 'seedprod_lite_wordpress_menuwidget' );

/**
 * WordPress Menu Widget.
 *
 * @param array $atts Shortcode attributes.
 * @return string $content
 */
function seedprod_lite_wordpress_menuwidget( $atts ) {

	$menu_atts = shortcode_atts(
		array(
			'menu'        => '',
			'menudivider' => '',
			'layout'      => 'h',
		),
		$atts
	);

	$navmenu_name = '';
	if ( isset( $menu_atts['menu'] ) ) {
		$navmenu_name = $menu_atts['menu'];
	}
	$navmenu_seperator = '';
	if ( isset( $menu_atts['menudivider'] ) ) {
		$navmenu_seperator = sanitize_text_field( $menu_atts['menudivider'] );
	}
	$layout = '';
	if ( isset( $menu_atts['layout'] ) ) {
		$layout = $menu_atts['layout'];
	}

	$walker_divider = true;
	if ( '' === $navmenu_seperator || 'v' === $layout ) {
		$walker_divider = false;
	}

	if ( true === $walker_divider ) {
		$args = array(
			'menu'            => $navmenu_name,
			'container_class' => 'nav-menu-bar',
			'menu_class'      => 'seedprod-menu-list',
			'walker'          => new SeedProd_Lite_Menu_Walker( $navmenu_seperator ),
		);
	} else {
		$args = array(
			'menu'            => $navmenu_name,
			'container_class' => 'nav-menu-bar',
			'menu_class'      => 'seedprod-menu-list',
		);
	}

	ob_start();
	wp_nav_menu( $args );
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}

/**
 * Remove last divider.
 *
 * @param string $items Items string.
 * @return string $items
 */
function seedprod_lite_remove_last_divider( $items ) {

	$substring = "<li class='separator menu-item'>|</li>";

	if ( substr( $items, -strlen( $substring ) ) === $substring ) {
		$items = substr( $items, 0, strlen( $items ) - strlen( $substring ) );
	}
	return $items;
}



/**
 * Shortcode to fetch  selected WordPress widget inside Seedprod builder
 */
add_shortcode( 'seedprodwpwidget', 'seedprod_lite_wordpress_widget' );

/**
 * WordPress Widget.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string $content
 */
function seedprod_lite_wordpress_widget( $atts ) {
	$atts = (array) $atts;

	// Get the widget name.
	$widget_name = isset( $atts[0] ) ? $atts[0] : '';

	// Remove widget name from array.
	unset( $atts[0] );

	// convert string bool.
	foreach ( $atts as $k => $v ) {
		if ( 'true' === $v ) {
			$atts[ $k ] = true; }
		if ( 'false' === $v ) {
			$atts[ $k ] = false; }
		// $atts[$k] = ($v === 'true')? true: false;
	}

	global $wp_widget_factory;

	$content = '';

	// Check if the widget class exists, else use the updated widget class name.
	if ( ! isset( $wp_widget_factory->widgets[ $widget_name ] ) && isset( $atts['class'] ) ) {
		// Replace - with \.
		$widget_name = str_replace( '-', '\\', $atts['class'] );
	}
	if ( ! isset( $wp_widget_factory->widgets[ $widget_name ] ) ) {
		return $content;
	}

	$widget = $wp_widget_factory->widgets[ $widget_name ];
	if ( ! $widget instanceof WP_Widget ) {
		return $content;
	}

	// The base WP_Widget::update() returns its input unchanged, so it cannot sanitize.
	$update = new ReflectionMethod( $widget, 'update' );
	if ( 'WP_Widget' === $update->getDeclaringClass()->getName() ) {
		/**
		 * Filters whether [seedprodwpwidget] renders a widget class that does not override update().
		 *
		 * The class must also be in the seedprod_widget_shortcode_allowed_classes list. Its update() does not
		 * sanitize, so the class must sanitize its settings and escape its own output. The shortcode filters
		 * each string value that holds a tag with wp_kses_post().
		 *
		 * @param boolean $allow       True to render the widget. Default false.
		 * @param string  $widget_name Widget class name.
		 */
		if ( ! apply_filters( 'seedprod_widget_shortcode_allow_base_update', false, $widget_name ) ) {
			return $content;
		}
	}

	$core_classes = array( 'WP_Widget_Pages', 'WP_Widget_Calendar', 'WP_Widget_Archives', 'WP_Widget_Media_Audio', 'WP_Widget_Media_Image', 'WP_Widget_Media_Gallery', 'WP_Widget_Media_Video', 'WP_Widget_Meta', 'WP_Widget_Search', 'WP_Widget_Text', 'WP_Widget_Categories', 'WP_Widget_Recent_Posts', 'WP_Widget_Recent_Comments', 'WP_Widget_RSS', 'WP_Widget_Tag_Cloud', 'WP_Nav_Menu_Widget', 'WP_Widget_Custom_HTML', 'WP_Widget_Block' );
	$is_core      = in_array( $widget_name, $core_classes, true );
	// Only WooCommerce's own WC_Widget classes. An extension widget needs the filter below.
	$is_wc_widget = false;
	if ( defined( 'WC_ABSPATH' ) && class_exists( 'WC_Widget' ) && $widget instanceof WC_Widget ) {
		$widget_file  = realpath( ( new ReflectionClass( $widget ) )->getFileName() );
		$wc_dir       = realpath( WC_ABSPATH );
		$is_wc_widget = $widget_file && $wc_dir && 0 === strpos( $widget_file, $wc_dir . DIRECTORY_SEPARATOR );
	}
	// WooCommerce brand widgets. A missing setting gets the default that their widget() uses, as a string
	// for stripslashes().
	$brand_defaults = array(
		'WC_Widget_Brand_Description' => array( 'title' => '' ),
		'WC_Widget_Brand_Nav'         => array(
			'title'        => '',
			'display_type' => 'list',
		),
		'WC_Widget_Brand_Thumbnails'  => array(
			'title'      => '',
			'columns'    => '1',
			'exclude'    => '',
			'orderby'    => 'name',
			'hide_empty' => '0',
			'number'     => '',
		),
	);
	$is_woo         = $is_wc_widget || isset( $brand_defaults[ $widget_name ] );

	/**
	 * Filters the extra widget classes that [seedprodwpwidget] renders.
	 *
	 * Core widgets and WooCommerce's own widgets render by default. For an added class, the shortcode runs its
	 * update() without unfiltered_html and filters each string value that holds a tag with wp_kses_post(). The
	 * class must still sanitize its own settings in update() and escape its own output in widget(), because
	 * the settings come from post content.
	 *
	 * @param string[] $classes Extra widget class names. Default empty.
	 */
	if ( ! $is_core && ! $is_woo && ! in_array( $widget_name, (array) apply_filters( 'seedprod_widget_shortcode_allowed_classes', array() ), true ) ) {
		return $content;
	}

	// An equal URL in the old instance stops the RSS widget update() from fetching the feed.
	$old_instance = ( 'WP_Widget_RSS' === $widget_name && isset( $atts['url'] ) ) ? array( 'url' => $atts['url'] ) : array();

	// Empty values for keys that core widgets read without a check. Other widgets keep their own defaults.
	if ( $is_core ) {
		$atts += array_fill_keys( array( 'title', 'sortby', 'exclude', 'taxonomy', 'items', 'number', 'url', 'text', 'filter', 'content' ), '' );
	}

	// WC_Widget::update() reads every setting key, so use the widget's own defaults for missing keys.
	if ( isset( $brand_defaults[ $widget_name ] ) ) {
		$atts += $brand_defaults[ $widget_name ];
	}
	if ( $is_wc_widget && isset( $widget->settings ) && is_array( $widget->settings ) ) {
		foreach ( $widget->settings as $key => $setting ) {
			if ( ! array_key_exists( $key, $atts ) && isset( $setting['std'] ) ) {
				$atts[ $key ] = $setting['std'];
			}
		}
	}

	// Only core widgets take the true and false values as booleans. Other update() methods can need strings.
	if ( ! $is_core ) {
		foreach ( $atts as $k => $v ) {
			if ( is_bool( $v ) ) {
				$atts[ $k ] = $v ? '1' : '';
			} elseif ( is_scalar( $v ) && ! is_string( $v ) ) {
				$atts[ $k ] = (string) $v;
			}
		}
	}

	// Widget settings come from post content, so the widget's own update() sanitizes them as for a user without unfiltered_html.
	$deny_unfiltered_html = static function ( $caps, $cap ) {
		return 'unfiltered_html' === $cap ? array( 'do_not_allow' ) : $caps;
	};
	add_filter( 'map_meta_cap', $deny_unfiltered_html, PHP_INT_MAX, 2 );
	try {
		$instance = $widget->update( $atts, $old_instance );
	} catch ( Exception $e ) {
		$instance = false;
	} catch ( Throwable $e ) {
		$instance = false;
	} finally {
		remove_filter( 'map_meta_cap', $deny_unfiltered_html, PHP_INT_MAX );
	}

	if ( ! is_array( $instance ) ) {
		return $content;
	}

	// Defence in depth for a class that is not a core widget: filter each string value that holds a tag with wp_kses_post().
	if ( ! $is_core ) {
		$instance = map_deep(
			$instance,
			static function ( $value ) {
				return ( is_string( $value ) && false !== strpos( $value, '<' ) ) ? wp_kses_post( $value ) : $value;
			}
		);
	}

	ob_start();
	the_widget( $widget_name, $instance );
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}
