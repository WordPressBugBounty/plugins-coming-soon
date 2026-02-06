<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename maintained for compatibility.
/**
 * Skin class.
 *
 * @since 6.0.0
 *
 * @package SeedProd
 * @subpackage  Upgrader Skin
 * @author  Chris Christoff
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SeedProd Upgrader Skin (PHP 5.3+ compatible)
 *
 * Custom skin for WordPress plugin upgrader to handle silent installations.
 *
 * @since 6.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 */
class SeedProd_Skin extends WP_Upgrader_Skin {


	/**
	 * Primary class constructor.
	 *
	 * @since 6.0.0
	 *
	 * @param array $args Empty array of args (we will use defaults).
	 */
	public function __construct( $args = array() ) {
		parent::__construct();
	}

	/**
	 * Set the upgrader object and store it as a property in the parent class.
	 *
	 * @since 6.0.0
	 *
	 * @param object $upgrader The upgrader object (passed by reference).
	 */
	public function set_upgrader( &$upgrader ) {
		if ( is_object( $upgrader ) ) {
			$this->upgrader =& $upgrader;
		}
	}

	/**
	 * Set the upgrader result and store it as a property in the parent class.
	 *
	 * @since 6.0.0
	 *
	 * @param object $result The result of the install process.
	 */
	public function set_result( $result ) {
		$this->result = $result;
	}

	/**
	 * Empty out the header of its HTML content and only check to see if it has
	 * been performed or not.
	 *
	 * @since 6.0.0
	 */
	public function header() {
	}

	/**
	 * Empty out the footer of its HTML contents.
	 *
	 * @since 6.0.0
	 */
	public function footer() {
	}

	/**
	 * Instead of outputting HTML for errors, json_encode the errors and send them
	 * back to the Ajax script for processing.
	 *
	 * @since 6.0.0
	 *
	 * @param array $errors Array of errors with the install process.
	 */
	public function error( $errors ) {
		if ( ! empty( $errors ) ) {
			echo wp_json_encode( array( 'error' => esc_html__( 'There was an error installing the addon. Please try again.', 'coming-soon' ) ) );
			die;
		}
	}

	/**
	 * Empty out the feedback method to prevent outputting HTML strings as the install
	 * is progressing.
	 *
	 * @since 6.0.0
	 *
	 * @param string $string The feedback string.
	 * @param mixed  ...$args Optional additional arguments.
	 */
	public function feedback( $string, ...$args ) {
	}
}
