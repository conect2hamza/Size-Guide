<?php
/**
 * Plugin bootstrap.
 *
 * @package SizeGuide
 */

namespace SizeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's pieces together.
 */
class Size_Guide {

	const SETTINGS_OPTION = 'size_guide_settings';

	/**
	 * Singleton instance.
	 *
	 * @var Size_Guide|null
	 */
	protected static $instance = null;

	/**
	 * Whether the frontend assets have been requested for this request.
	 *
	 * @var bool
	 */
	protected $assets_needed = false;

	/**
	 * Get the shared instance.
	 *
	 * @return Size_Guide
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
	public function boot() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );

		Shortcode::instance()->register();
		Rest_API::instance()->register();
		Template_Generator::instance()->register();

		if ( is_admin() ) {
			Admin::instance()->register();
		}
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'size-guide', false, dirname( SIZE_GUIDE_BASENAME ) . '/languages' );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'default_section' => 'digital',
			'default_unit'    => 'px',
			'default_dpi'     => 300,
			'show_sources'    => 1,
			'enable_download' => 1,
			'load_via_rest'   => 0,
			'accent_color'    => '#2563eb',
			'color_scheme'    => 'light',
			'corner_style'    => 'rounded',
			'density'         => 'comfortable',
		);
	}

	/**
	 * Merged settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::SETTINGS_OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::default_settings() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = self::get_settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Register (but do not enqueue) the frontend assets.
	 *
	 * Assets are only enqueued once a shortcode or block actually renders, so
	 * pages without a Size Guide stay untouched.
	 */
	public function register_frontend_assets() {
		wp_register_style(
			'size-guide-frontend',
			SIZE_GUIDE_URL . 'assets/css/frontend.css',
			array(),
			SIZE_GUIDE_VERSION
		);

		wp_register_style(
			'size-guide-infographic',
			SIZE_GUIDE_URL . 'assets/css/infographic.css',
			array( 'size-guide-frontend' ),
			SIZE_GUIDE_VERSION
		);

		$scripts = array(
			'size-guide-converter'          => array( 'converter.js', array() ),
			'size-guide-search'             => array( 'search.js', array() ),
			'size-guide-infographic'        => array( 'infographic.js', array( 'size-guide-converter' ) ),
			'size-guide-template-generator' => array( 'template-generator.js', array( 'size-guide-converter', 'size-guide-infographic' ) ),
			'size-guide-frontend'           => array(
				'frontend.js',
				array( 'size-guide-converter', 'size-guide-search', 'size-guide-infographic', 'size-guide-template-generator' ),
			),
		);

		foreach ( $scripts as $handle => $script ) {
			list( $file, $deps ) = $script;
			wp_register_script(
				$handle,
				SIZE_GUIDE_URL . 'assets/js/' . $file,
				$deps,
				SIZE_GUIDE_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue the frontend bundle and hand the dataset to JavaScript.
	 *
	 * @param array $atts Shortcode attributes used for the initial view.
	 */
	public function enqueue_frontend( array $atts = array() ) {
		wp_enqueue_style( 'size-guide-frontend' );
		wp_enqueue_style( 'size-guide-infographic' );
		wp_enqueue_script( 'size-guide-frontend' );

		if ( $this->assets_needed ) {
			return;
		}
		$this->assets_needed = true;

		$settings = self::get_settings();

		// Scoped to .sg-app: the stylesheet declares --sg-accent on that same
		// element, so a :root rule here would never win.
		wp_add_inline_style(
			'size-guide-frontend',
			'.sg-app{--sg-accent:' . esc_attr( $settings['accent_color'] ) . ';}'
		);

		// The dataset is inlined by default so the guide works on first paint.
		// Sites that would rather keep the HTML small can fetch it over REST,
		// where the browser can cache it across pages.
		$inline = empty( $settings['load_via_rest'] );

		wp_localize_script(
			'size-guide-frontend',
			'SizeGuideData',
			array(
				'dataset'       => $inline ? Data_Loader::get_dataset() : null,
				'abbreviations' => Data_Loader::abbreviations(),
				'settings'      => array(
					'defaultSection' => $settings['default_section'],
					'defaultUnit'    => $settings['default_unit'],
					'defaultDpi'     => (int) $settings['default_dpi'],
					'showSources'    => (bool) $settings['show_sources'],
					'enableDownload' => (bool) $settings['enable_download'],
				),
				'initial'       => $atts,
				'restUrl'       => esc_url_raw( rest_url( Rest_API::NAMESPACE_ROOT ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'i18n'          => self::strings(),
			)
		);
	}

	/**
	 * Appearance classes for the app root.
	 *
	 * @param array $overrides Per-shortcode overrides.
	 * @return string Space separated class list.
	 */
	public static function appearance_classes( array $overrides = array() ) {
		$settings = self::get_settings();
		$classes  = array();

		$scheme = ! empty( $overrides['scheme'] ) ? $overrides['scheme'] : $settings['color_scheme'];
		if ( in_array( $scheme, array( 'dark', 'auto' ), true ) ) {
			$classes[] = 'sg-app--' . $scheme;
		}

		if ( 'square' === $settings['corner_style'] ) {
			$classes[] = 'sg-app--square';
		}

		if ( 'compact' === $settings['density'] ) {
			$classes[] = 'sg-app--compact';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Translatable strings used by the JavaScript UI.
	 *
	 * @return array<string,string>
	 */
	public static function strings() {
		return array(
			'search'            => __( 'Search any design size…', 'size-guide' ),
			'noResults'         => __( 'No sizes matched that search.', 'size-guide' ),
			'results'           => __( 'Results', 'size-guide' ),
			'platforms'         => __( 'Platforms', 'size-guide' ),
			'formats'           => __( 'Formats', 'size-guide' ),
			'copy'              => __( 'Copy size', 'size-guide' ),
			'copyPlain'         => _x( 'Plain', 'copy the size as 1080x1350', 'size-guide' ),
			'copyWithRatio'     => _x( 'With ratio', 'copy the size followed by its aspect ratio', 'size-guide' ),
			'copied'            => __( 'Copied', 'size-guide' ),
			'copyFailed'        => __( 'Copy failed — select the text manually.', 'size-guide' ),
			'downloadSvg'       => __( 'Clean SVG', 'size-guide' ),
			'downloadGuide'     => __( 'Guide SVG', 'size-guide' ),
			'downloadPng'       => __( 'PNG', 'size-guide' ),
			'print'             => __( 'Print / PDF', 'size-guide' ),
			'safeZone'          => __( 'Safe zone', 'size-guide' ),
			'margin'            => __( 'Margin', 'size-guide' ),
			'bleed'             => __( 'Bleed', 'size-guide' ),
			'trim'              => __( 'Trim', 'size-guide' ),
			'canvas'            => __( 'Canvas', 'size-guide' ),
			'grid'              => __( 'Grid', 'size-guide' ),
			'padding'           => __( 'Padding', 'size-guide' ),
			'templates'         => __( 'Templates', 'size-guide' ),
			'legend'            => __( 'Guide colours', 'size-guide' ),
			'labels'            => __( 'Labels', 'size-guide' ),
			'measurements'      => __( 'Measurements', 'size-guide' ),
			'safeContentArea'   => __( 'Safe content area', 'size-guide' ),
			'aspectRatio'       => __( 'Aspect ratio', 'size-guide' ),
			'orientation'       => __( 'Orientation', 'size-guide' ),
			'dpi'               => __( 'DPI', 'size-guide' ),
			'fileFormats'       => __( 'File formats', 'size-guide' ),
			'maxFileSize'       => __( 'Max file size', 'size-guide' ),
			'minimum'           => __( 'Minimum', 'size-guide' ),
			'maximum'           => __( 'Maximum', 'size-guide' ),
			'recommended'       => __( 'Recommended', 'size-guide' ),
			'notes'             => __( 'Notes', 'size-guide' ),
			'source'            => __( 'Source', 'size-guide' ),
			'lastUpdated'       => __( 'Last updated', 'size-guide' ),
			'verified'          => __( 'Verified', 'size-guide' ),
			'workingDocument'   => __( 'Working document', 'size-guide' ),
			'finalSize'         => __( 'Final size', 'size-guide' ),
			'width'             => __( 'Width', 'size-guide' ),
			'height'            => __( 'Height', 'size-guide' ),
			'unit'              => __( 'Unit', 'size-guide' ),
			'back'              => __( 'Back', 'size-guide' ),
			'official'          => __( 'Official', 'size-guide' ),
			'commonPractice'    => __( 'Common practice', 'size-guide' ),
			'estimated'         => __( 'Estimated', 'size-guide' ),
		);
	}
}
