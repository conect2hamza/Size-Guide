<?php
/**
 * The [size_guide] shortcode and matching block-editor friendly renderer.
 *
 * @package SizeGuide
 */

namespace SizeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the Size Guide app.
 */
class Shortcode {

	/**
	 * Singleton instance.
	 *
	 * @var Shortcode|null
	 */
	protected static $instance = null;

	/**
	 * Get the shared instance.
	 *
	 * @return Shortcode
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		add_shortcode( 'size_guide', array( $this, 'render' ) );
	}

	/**
	 * Render the app shell.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'section'  => '',
				'category' => '',
				'platform' => '',
				'format'   => '',
				'search'   => '',
				'title'    => '',
			),
			$atts,
			'size_guide'
		);

		$atts = array_map( 'sanitize_text_field', $atts );

		// "social" is a friendly alias for the social-media group.
		if ( 'social' === $atts['category'] ) {
			$atts['category'] = 'social-media';
		}

		size_guide()->enqueue_frontend( $atts );

		$dataset = Data_Loader::get_dataset();

		ob_start();
		$template = locate_template( 'size-guide/app.php' );
		if ( ! $template ) {
			$template = SIZE_GUIDE_PATH . 'templates/app.php';
		}
		include $template;

		return (string) ob_get_clean();
	}
}
