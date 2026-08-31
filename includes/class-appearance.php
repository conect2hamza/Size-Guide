<?php
/**
 * Appearance: the design tokens behind the frontend, and the CSS they produce.
 *
 * Every visual decision the guide makes reads a CSS custom property. This class
 * owns the list of those properties, their defaults, validation, presets, and
 * the stylesheet generated from a saved configuration.
 *
 * @package SizeGuide
 */

namespace SizeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Design token storage and CSS generation.
 */
class Appearance {

	const OPTION = 'size_guide_appearance';

	/**
	 * Colour tokens defined once per scheme.
	 *
	 * @return array<string,string> Token key to label.
	 */
	public static function scheme_colors() {
		return array(
			'background'      => __( 'Background', 'size-guide' ),
			'surface'         => __( 'Card surface', 'size-guide' ),
			'surface_alt'     => __( 'Sunken surface', 'size-guide' ),
			'text'            => __( 'Text', 'size-guide' ),
			'muted'           => __( 'Secondary text', 'size-guide' ),
			'border'          => __( 'Borders', 'size-guide' ),
			'accent'          => __( 'Accent', 'size-guide' ),
			'accent_contrast' => __( 'Text on accent', 'size-guide' ),
		);
	}

	/**
	 * Diagram colours.
	 *
	 * These are shared by both schemes on purpose: the artboard inside a diagram
	 * is always white, so a second set tuned for a dark page would be drawn on a
	 * light surface and disappear.
	 *
	 * @return array<string,string> Token key to label.
	 */
	public static function guide_colors() {
		return array(
			'guide_canvas' => __( 'Artboard', 'size-guide' ),
			'guide_trim'   => __( 'Trim / canvas edge', 'size-guide' ),
			'guide_bleed'  => __( 'Bleed', 'size-guide' ),
			'guide_margin' => __( 'Margin', 'size-guide' ),
			'guide_safe'   => __( 'Safe zone', 'size-guide' ),
			'guide_grid'   => __( 'Grid & centre lines', 'size-guide' ),
		);
	}

	/**
	 * Font stacks offered in the picker.
	 *
	 * @return array<string,array{label:string,stack:string}>
	 */
	public static function font_stacks() {
		return array(
			'inherit' => array(
				'label' => __( 'Inherit from the theme', 'size-guide' ),
				'stack' => 'inherit',
			),
			'system'  => array(
				'label' => __( 'System UI', 'size-guide' ),
				'stack' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
			),
			'grotesk' => array(
				'label' => __( 'Neo-grotesque', 'size-guide' ),
				'stack' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
			),
			'geometric' => array(
				'label' => __( 'Geometric', 'size-guide' ),
				'stack' => 'Avenir, "Avenir Next", Montserrat, Corbel, "URW Gothic", sans-serif',
			),
			'serif'   => array(
				'label' => __( 'Serif', 'size-guide' ),
				'stack' => 'Georgia, Cambria, "Times New Roman", Times, serif',
			),
			'mono'    => array(
				'label' => __( 'Monospace', 'size-guide' ),
				'stack' => 'ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace',
			),
			'custom'  => array(
				'label' => __( 'Custom stack', 'size-guide' ),
				'stack' => '',
			),
		);
	}

	/**
	 * Shadow presets, keyed by strength.
	 *
	 * @return array<string,array{label:string,value:string,hover:string}>
	 */
	public static function shadows() {
		return array(
			'none'   => array(
				'label' => __( 'None', 'size-guide' ),
				'value' => 'none',
				'hover' => 'none',
			),
			'soft'   => array(
				'label' => __( 'Soft', 'size-guide' ),
				'value' => '0 1px 2px rgba( 15, 23, 42, 0.05 )',
				'hover' => '0 4px 14px rgba( 15, 23, 42, 0.09 )',
			),
			'medium' => array(
				'label' => __( 'Medium', 'size-guide' ),
				'value' => '0 1px 3px rgba( 15, 23, 42, 0.09 ), 0 6px 16px rgba( 15, 23, 42, 0.06 )',
				'hover' => '0 6px 22px rgba( 15, 23, 42, 0.14 )',
			),
			'strong' => array(
				'label' => __( 'Strong', 'size-guide' ),
				'value' => '0 2px 6px rgba( 15, 23, 42, 0.12 ), 0 12px 32px rgba( 15, 23, 42, 0.1 )',
				'hover' => '0 10px 36px rgba( 15, 23, 42, 0.2 )',
			),
		);
	}

	/**
	 * Density presets: the spacing rhythm everything else is derived from.
	 *
	 * @return array<string,array{label:string,gap:int,pad:int}>
	 */
	public static function densities() {
		return array(
			'compact'     => array(
				'label' => __( 'Compact', 'size-guide' ),
				'gap'   => 10,
				'pad'   => 9,
			),
			'comfortable' => array(
				'label' => __( 'Comfortable', 'size-guide' ),
				'gap'   => 16,
				'pad'   => 14,
			),
			'roomy'       => array(
				'label' => __( 'Roomy', 'size-guide' ),
				'gap'   => 22,
				'pad'   => 20,
			),
		);
	}

	/**
	 * Numeric controls: key => [label, min, max, step, unit].
	 *
	 * @return array<string,array>
	 */
	public static function numbers() {
		return array(
			'font_size'      => array( __( 'Base text size', 'size-guide' ), 12, 20, 1, 'px' ),
			'heading_weight' => array( __( 'Heading weight', 'size-guide' ), 400, 800, 100, '' ),
			'radius'         => array( __( 'Corner radius', 'size-guide' ), 0, 28, 1, 'px' ),
			'border_width'   => array( __( 'Border width', 'size-guide' ), 0, 3, 1, 'px' ),
			'card_min'       => array( __( 'Minimum card width', 'size-guide' ), 110, 260, 5, 'px' ),
			'sidebar_width'  => array( __( 'Sidebar width', 'size-guide' ), 150, 340, 10, 'px' ),
			'max_width'      => array( __( 'Maximum width (0 = full width)', 'size-guide' ), 0, 1800, 20, 'px' ),
		);
	}

	/**
	 * The default configuration.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'scheme'         => 'light',
			'light'          => array(
				'background'      => '#ffffff',
				'surface'         => '#ffffff',
				'surface_alt'     => '#f6f8fb',
				'text'            => '#0f172a',
				'muted'           => '#64748b',
				'border'          => '#e3e8ef',
				'accent'          => '#2563eb',
				'accent_contrast' => '#ffffff',
			),
			'dark'           => array(
				'background'      => '#0b1120',
				'surface'         => '#111a2e',
				'surface_alt'     => '#0e1626',
				'text'            => '#e6ebf4',
				'muted'           => '#93a1b8',
				'border'          => '#26334c',
				'accent'          => '#5b9bff',
				'accent_contrast' => '#08111f',
			),
			'guides'         => array(
				'guide_canvas' => '#ffffff',
				'guide_trim'   => '#0f172a',
				'guide_bleed'  => '#e11d48',
				'guide_margin' => '#f59e0b',
				'guide_safe'   => '#16a34a',
				'guide_grid'   => '#93c5fd',
			),
			'transparent_bg' => 1,
			'font_family'    => 'inherit',
			'font_custom'    => '',
			'font_size'      => 16,
			'heading_weight' => 650,
			'radius'         => 12,
			'border_width'   => 1,
			'shadow'         => 'soft',
			'density'        => 'comfortable',
			'card_min'       => 150,
			'sidebar_width'  => 220,
			'max_width'      => 0,
			'animations'     => 1,
		);
	}

	/**
	 * Named starting points. Each is a partial merged over the defaults.
	 *
	 * @return array<string,array{label:string,values:array}>
	 */
	public static function presets() {
		return array(
			'default'   => array(
				'label'  => __( 'Default', 'size-guide' ),
				'values' => array(),
			),
			'minimal'   => array(
				'label'  => __( 'Minimal', 'size-guide' ),
				'values' => array(
					'light'  => array(
						'surface_alt' => '#fafafa',
						'text'        => '#111111',
						'muted'       => '#6b6b6b',
						'border'      => '#e4e4e4',
						'accent'      => '#111111',
					),
					'radius' => 0,
					'shadow' => 'none',
				),
			),
			'studio'    => array(
				'label'  => __( 'Studio (dark)', 'size-guide' ),
				'values' => array(
					'scheme'         => 'dark',
					'dark'           => array(
						'background'      => '#0a0a0c',
						'surface'         => '#151519',
						'surface_alt'     => '#101014',
						'text'            => '#ededf0',
						'muted'           => '#9a9aa5',
						'border'          => '#2a2a31',
						'accent'          => '#a78bfa',
						'accent_contrast' => '#12101c',
					),
					'transparent_bg' => 0,
					'radius'         => 14,
					'shadow'         => 'medium',
				),
			),
			'editorial' => array(
				'label'  => __( 'Editorial', 'size-guide' ),
				'values' => array(
					'light'       => array(
						'background'  => '#fbfaf7',
						'surface'     => '#ffffff',
						'surface_alt' => '#f4f1ea',
						'text'        => '#1c1a17',
						'muted'       => '#6f6960',
						'border'      => '#e0dad0',
						'accent'      => '#7c3f1d',
					),
					'font_family'    => 'serif',
					'transparent_bg' => 0,
					'radius'         => 2,
					'shadow'         => 'none',
					'density'        => 'roomy',
				),
			),
			'vivid'     => array(
				'label'  => __( 'Vivid', 'size-guide' ),
				'values' => array(
					'light'  => array(
						'surface_alt' => '#f0fdf9',
						'border'      => '#d4e9e2',
						'accent'      => '#0d9488',
					),
					'radius' => 20,
					'shadow' => 'medium',
				),
			),
		);
	}

	/**
	 * The saved configuration, merged over the defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved    = get_option( self::OPTION, array() );
		$defaults = self::defaults();

		if ( ! is_array( $saved ) ) {
			return $defaults;
		}

		$merged = array_merge( $defaults, $saved );

		// Nested colour groups merge key by key so a partial save keeps the rest.
		foreach ( array( 'light', 'dark', 'guides' ) as $group ) {
			$merged[ $group ] = array_merge(
				$defaults[ $group ],
				isset( $saved[ $group ] ) && is_array( $saved[ $group ] ) ? $saved[ $group ] : array()
			);
		}

		return $merged;
	}

	/**
	 * Validate a submitted configuration.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$clean    = array();

		// A preset fills anything the form did not send.
		if ( ! empty( $input['preset'] ) ) {
			$presets = self::presets();
			$key     = sanitize_key( $input['preset'] );

			if ( isset( $presets[ $key ] ) ) {
				$input = self::merge_preset( $input, $presets[ $key ]['values'] );
			}
		}

		$schemes          = array( 'light', 'dark', 'auto' );
		$clean['scheme']  = in_array( $input['scheme'] ?? '', $schemes, true ) ? $input['scheme'] : $defaults['scheme'];

		foreach ( array( 'light', 'dark' ) as $group ) {
			$clean[ $group ] = array();

			foreach ( array_keys( self::scheme_colors() ) as $token ) {
				$value                    = isset( $input[ $group ][ $token ] ) ? sanitize_hex_color( $input[ $group ][ $token ] ) : '';
				$clean[ $group ][ $token ] = $value ? $value : $defaults[ $group ][ $token ];
			}
		}

		$clean['guides'] = array();
		foreach ( array_keys( self::guide_colors() ) as $token ) {
			$value                     = isset( $input['guides'][ $token ] ) ? sanitize_hex_color( $input['guides'][ $token ] ) : '';
			$clean['guides'][ $token ] = $value ? $value : $defaults['guides'][ $token ];
		}

		$fonts               = self::font_stacks();
		$clean['font_family'] = isset( $fonts[ $input['font_family'] ?? '' ] ) ? $input['font_family'] : $defaults['font_family'];

		// A custom stack is a font-family value, not markup.
		$custom               = isset( $input['font_custom'] ) ? wp_strip_all_tags( (string) $input['font_custom'] ) : '';
		$custom               = preg_replace( '/[^A-Za-z0-9 ,"\'\-_]/', '', $custom );
		$clean['font_custom'] = trim( mb_substr( $custom, 0, 200 ) );

		foreach ( self::numbers() as $key => $spec ) {
			list( , $min, $max ) = $spec;
			$value               = isset( $input[ $key ] ) ? (int) $input[ $key ] : $defaults[ $key ];
			$clean[ $key ]       = max( $min, min( $max, $value ) );
		}

		$shadows          = self::shadows();
		$clean['shadow']  = isset( $shadows[ $input['shadow'] ?? '' ] ) ? $input['shadow'] : $defaults['shadow'];

		$densities        = self::densities();
		$clean['density'] = isset( $densities[ $input['density'] ?? '' ] ) ? $input['density'] : $defaults['density'];

		$clean['transparent_bg'] = empty( $input['transparent_bg'] ) ? 0 : 1;
		$clean['animations']     = empty( $input['animations'] ) ? 0 : 1;

		Data_Loader::flush_cache();

		return $clean;
	}

	/**
	 * Merge a preset's values under whatever the form submitted.
	 *
	 * @param array $input  Submitted values.
	 * @param array $values Preset values.
	 * @return array
	 */
	protected static function merge_preset( array $input, array $values ) {
		foreach ( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				$input[ $key ] = array_merge( isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array(), $value );
			} else {
				$input[ $key ] = $value;
			}
		}

		return $input;
	}

	/**
	 * Resolve the font stack for a configuration.
	 *
	 * @param array $config Configuration.
	 * @return string
	 */
	public static function font_stack( array $config ) {
		if ( 'custom' === $config['font_family'] && '' !== $config['font_custom'] ) {
			return $config['font_custom'];
		}

		$stacks = self::font_stacks();

		return $stacks[ $config['font_family'] ]['stack'] ?? 'inherit';
	}

	/**
	 * The custom properties for one colour scheme.
	 *
	 * @param array $config Configuration.
	 * @param string $group "light" or "dark".
	 * @return array<string,string>
	 */
	public static function scheme_properties( array $config, $group ) {
		$colors = $config[ $group ];

		$props = array(
			'--sg-surface'         => $colors['surface'],
			'--sg-surface-alt'     => $colors['surface_alt'],
			'--sg-text'            => $colors['text'],
			'--sg-muted'           => $colors['muted'],
			'--sg-border'          => $colors['border'],
			'--sg-accent'          => $colors['accent'],
			'--sg-accent-contrast' => $colors['accent_contrast'],
			'--sg-accent-soft'     => self::mix( $colors['accent'], $colors['surface'], 0.12 ),
			'--sg-accent-ring'     => self::mix( $colors['accent'], $colors['surface'], 0.4 ),
		);

		$props['--sg-bg'] = $config['transparent_bg'] ? 'transparent' : $colors['background'];

		return $props;
	}

	/**
	 * Every custom property that does not depend on the scheme.
	 *
	 * @param array $config Configuration.
	 * @return array<string,string>
	 */
	public static function base_properties( array $config ) {
		$densities = self::densities();
		$density   = $densities[ $config['density'] ];
		$shadows   = self::shadows();
		$shadow    = $shadows[ $config['shadow'] ];

		$props = array(
			'--sg-font'           => self::font_stack( $config ),
			'--sg-font-size'      => $config['font_size'] . 'px',
			'--sg-heading-weight' => (string) $config['heading_weight'],
			'--sg-radius'         => $config['radius'] . 'px',
			'--sg-radius-sm'      => max( 0, round( $config['radius'] * 0.55 ) ) . 'px',
			'--sg-radius-pill'    => $config['radius'] > 0 ? '999px' : '0',
			'--sg-border-width'   => $config['border_width'] . 'px',
			'--sg-gap'            => $density['gap'] . 'px',
			'--sg-pad'            => $density['pad'] . 'px',
			'--sg-card-min'       => $config['card_min'] . 'px',
			'--sg-sidebar-w'      => $config['sidebar_width'] . 'px',
			'--sg-max-w'          => $config['max_width'] > 0 ? $config['max_width'] . 'px' : 'none',
			'--sg-shadow'         => $shadow['value'],
			'--sg-shadow-hover'   => $shadow['hover'],
			'--sg-motion'         => $config['animations'] ? '0.16s' : '0s',
		);

		foreach ( self::guide_colors() as $token => $label ) {
			$props[ '--sg-' . str_replace( '_', '-', $token ) ] = $config['guides'][ $token ];
		}

		$props['--sg-guide-bleed-fill'] = self::rgba( $config['guides']['guide_bleed'], 0.08 );
		$props['--sg-guide-safe-fill']  = self::rgba( $config['guides']['guide_safe'], 0.1 );

		return $props;
	}

	/**
	 * Build the stylesheet for the saved configuration.
	 *
	 * @param array|null $config Configuration, or null to read the saved one.
	 * @return string
	 */
	public static function css( $config = null ) {
		$config = is_array( $config ) ? $config : self::get();

		$base  = array_merge( self::base_properties( $config ), self::scheme_properties( $config, 'light' ) );
		$dark  = self::scheme_properties( $config, 'dark' );
		$css   = '.sg-app{' . self::declarations( $base ) . '}';
		$css  .= '.sg-app--dark{' . self::declarations( $dark ) . '}';
		$css  .= '@media(prefers-color-scheme:dark){.sg-app--auto{' . self::declarations( $dark ) . '}}';

		return $css;
	}

	/**
	 * Turn a property map into a declaration list.
	 *
	 * @param array<string,string> $props Properties.
	 * @return string
	 */
	protected static function declarations( array $props ) {
		$out = '';

		foreach ( $props as $name => $value ) {
			$out .= $name . ':' . $value . ';';
		}

		return $out;
	}

	/**
	 * The body classes an app root needs for a configuration.
	 *
	 * @param array  $config Configuration.
	 * @param string $scheme Optional per-instance scheme override.
	 * @return string
	 */
	public static function classes( array $config, $scheme = '' ) {
		$scheme  = in_array( $scheme, array( 'light', 'dark', 'auto' ), true ) ? $scheme : $config['scheme'];
		$classes = array();

		if ( 'light' !== $scheme ) {
			$classes[] = 'sg-app--' . $scheme;
		}

		if ( ! $config['animations'] ) {
			$classes[] = 'sg-app--still';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Parse a hex colour into RGB components.
	 *
	 * @param string $hex Hex colour.
	 * @return array{0:int,1:int,2:int}
	 */
	protected static function rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return array( 0, 0, 0 );
		}

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * A translucent version of a colour.
	 *
	 * @param string $hex   Hex colour.
	 * @param float  $alpha Alpha 0-1.
	 * @return string
	 */
	public static function rgba( $hex, $alpha ) {
		list( $r, $g, $b ) = self::rgb( $hex );

		return sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, round( $alpha, 3 ) );
	}

	/**
	 * Blend two colours, so tints stay in key with the palette they came from.
	 *
	 * @param string $hex    Colour to mix in.
	 * @param string $onto   Colour mixed onto.
	 * @param float  $amount Share of the first colour, 0-1.
	 * @return string
	 */
	public static function mix( $hex, $onto, $amount ) {
		list( $r1, $g1, $b1 ) = self::rgb( $hex );
		list( $r2, $g2, $b2 ) = self::rgb( $onto );

		$amount = max( 0, min( 1, (float) $amount ) );

		return sprintf(
			'#%02x%02x%02x',
			(int) round( ( $r1 * $amount ) + ( $r2 * ( 1 - $amount ) ) ),
			(int) round( ( $g1 * $amount ) + ( $g2 * ( 1 - $amount ) ) ),
			(int) round( ( $b1 * $amount ) + ( $b2 * ( 1 - $amount ) ) )
		);
	}
}
