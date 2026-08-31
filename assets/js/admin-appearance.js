/**
 * Size Guide — the Appearance screen.
 *
 * Drives the live preview by writing the same custom properties the saved
 * stylesheet would produce, so what you see is what the front-end renders.
 */
( function ( window, document ) {
	'use strict';

	var form = document.getElementById( 'sg-appearance-form' );
	var preview = document.getElementById( 'sg-appearance-preview' );

	if ( ! form || ! preview ) {
		return;
	}

	var app = preview.querySelector( '.sg-app' );
	var hint = document.querySelector( '.sg-ap__hint' );

	if ( ! app ) {
		return;
	}

	/**
	 * Parse a hex colour.
	 *
	 * @param {string} hex Hex colour.
	 * @return {number[]} RGB components.
	 */
	function rgb( hex ) {
		var value = String( hex || '' ).replace( '#', '' );

		if ( 3 === value.length ) {
			value = value[ 0 ] + value[ 0 ] + value[ 1 ] + value[ 1 ] + value[ 2 ] + value[ 2 ];
		}

		if ( 6 !== value.length ) {
			return [ 0, 0, 0 ];
		}

		return [
			parseInt( value.slice( 0, 2 ), 16 ),
			parseInt( value.slice( 2, 4 ), 16 ),
			parseInt( value.slice( 4, 6 ), 16 )
		];
	}

	/**
	 * Blend two colours — mirrors Appearance::mix() in PHP.
	 *
	 * @param {string} hex    Colour mixed in.
	 * @param {string} onto   Colour mixed onto.
	 * @param {number} amount Share of the first colour.
	 * @return {string} Hex colour.
	 */
	function mix( hex, onto, amount ) {
		var a = rgb( hex );
		var b = rgb( onto );

		return '#' + [ 0, 1, 2 ].map( function ( i ) {
			var channel = Math.round( ( a[ i ] * amount ) + ( b[ i ] * ( 1 - amount ) ) );
			return ( '0' + channel.toString( 16 ) ).slice( -2 );
		} ).join( '' );
	}

	/**
	 * A translucent colour — mirrors Appearance::rgba() in PHP.
	 *
	 * @param {string} hex   Hex colour.
	 * @param {number} alpha Alpha.
	 * @return {string} rgba() string.
	 */
	function rgba( hex, alpha ) {
		var parts = rgb( hex );
		return 'rgba(' + parts[ 0 ] + ',' + parts[ 1 ] + ',' + parts[ 2 ] + ',' + alpha + ')';
	}

	/**
	 * Read one control's value out of the form.
	 *
	 * @param {string} selector Attribute selector.
	 * @return {HTMLElement|null} Control.
	 */
	function control( selector ) {
		return form.querySelector( selector );
	}

	/**
	 * Collect the colour fields of one group.
	 *
	 * @param {string} group "light", "dark" or "guides".
	 * @return {Object} Token to hex.
	 */
	function colors( group ) {
		var out = {};

		form.querySelectorAll( '[data-sg-group="' + group + '"] input[type="color"]' ).forEach( function ( input ) {
			out[ input.getAttribute( 'data-sg-token' ) ] = input.value;
		} );

		return out;
	}

	/**
	 * Which palette the preview should show.
	 *
	 * @return {string} "light" or "dark".
	 */
	function activeScheme() {
		var checked = form.querySelector( '[data-sg-scheme]:checked' );
		var scheme = checked ? checked.value : 'light';

		if ( 'auto' === scheme ) {
			return window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches
				? 'dark'
				: 'light';
		}

		return scheme;
	}

	/**
	 * Push every current value onto the preview.
	 */
	function apply() {
		var scheme = activeScheme();
		var palette = colors( scheme );
		var guides = colors( 'guides' );
		var style = app.style;

		style.setProperty( '--sg-surface', palette.surface );
		style.setProperty( '--sg-surface-alt', palette.surface_alt );
		style.setProperty( '--sg-text', palette.text );
		style.setProperty( '--sg-muted', palette.muted );
		style.setProperty( '--sg-border', palette.border );
		style.setProperty( '--sg-accent', palette.accent );
		style.setProperty( '--sg-accent-contrast', palette.accent_contrast );
		style.setProperty( '--sg-accent-soft', mix( palette.accent, palette.surface, 0.12 ) );
		style.setProperty( '--sg-accent-ring', mix( palette.accent, palette.surface, 0.4 ) );

		var transparent = control( '[data-sg-transparent]' );
		style.setProperty( '--sg-bg', transparent && transparent.checked ? 'transparent' : palette.background );

		Object.keys( guides ).forEach( function ( token ) {
			style.setProperty( '--sg-' + token.replace( /_/g, '-' ), guides[ token ] );
		} );
		style.setProperty( '--sg-guide-bleed-fill', rgba( guides.guide_bleed, 0.08 ) );
		style.setProperty( '--sg-guide-safe-fill', rgba( guides.guide_safe, 0.1 ) );

		// Numbers.
		form.querySelectorAll( '[data-sg-number]' ).forEach( function ( input ) {
			var key = input.getAttribute( 'data-sg-number' );
			var unit = input.getAttribute( 'data-sg-unit' ) || '';
			var value = input.value;
			var output = input.parentNode.querySelector( 'output' );

			if ( output ) {
				output.textContent = value + unit;
			}

			switch ( key ) {
				case 'font_size':
					style.setProperty( '--sg-font-size', value + 'px' );
					break;
				case 'heading_weight':
					style.setProperty( '--sg-heading-weight', value );
					break;
				case 'radius':
					style.setProperty( '--sg-radius', value + 'px' );
					style.setProperty( '--sg-radius-sm', Math.round( value * 0.55 ) + 'px' );
					style.setProperty( '--sg-radius-pill', value > 0 ? '999px' : '0' );
					break;
				case 'border_width':
					style.setProperty( '--sg-border-width', value + 'px' );
					break;
				case 'card_min':
					style.setProperty( '--sg-card-min', value + 'px' );
					break;
				case 'sidebar_width':
					style.setProperty( '--sg-sidebar-w', value + 'px' );
					break;
				case 'max_width':
					style.setProperty( '--sg-max-w', value > 0 ? value + 'px' : 'none' );
					break;
			}
		} );

		// Typeface.
		var font = control( '[data-sg-font]' );
		var custom = control( '[data-sg-font-custom]' );
		var wrap = document.querySelector( '.sg-ap__custom-font' );

		if ( font ) {
			var isCustom = 'custom' === font.value;
			var stack = isCustom
				? ( custom && custom.value ? custom.value : 'inherit' )
				: font.options[ font.selectedIndex ].getAttribute( 'data-sg-stack' );

			if ( wrap ) {
				wrap.hidden = ! isCustom;
			}

			style.setProperty( '--sg-font', stack || 'inherit' );
		}

		// Density.
		var density = control( '[data-sg-density]' );
		if ( density ) {
			var option = density.options[ density.selectedIndex ];
			style.setProperty( '--sg-gap', option.getAttribute( 'data-sg-gap' ) + 'px' );
			style.setProperty( '--sg-pad', option.getAttribute( 'data-sg-pad' ) + 'px' );
		}

		// Shadow.
		var shadow = control( '[data-sg-shadow]' );
		if ( shadow ) {
			var chosen = shadow.options[ shadow.selectedIndex ];
			style.setProperty( '--sg-shadow', chosen.getAttribute( 'data-sg-value' ) );
			style.setProperty( '--sg-shadow-hover', chosen.getAttribute( 'data-sg-hover' ) );
		}

		// Motion.
		var animations = control( '[data-sg-animations]' );
		app.classList.toggle( 'sg-app--still', !! animations && ! animations.checked );

		// Keep the swatch captions honest.
		form.querySelectorAll( 'input[type="color"]' ).forEach( function ( input ) {
			var code = input.parentNode.querySelector( 'code' );
			if ( code ) {
				code.textContent = input.value;
			}
		} );

		if ( hint ) {
			hint.textContent = hint.getAttribute( 'data-sg-dirty' ) || hint.textContent;
		}
	}

	/**
	 * Load a preset into every control.
	 *
	 * @param {Object} values Sanitised configuration.
	 */
	function loadPreset( values ) {
		[ 'light', 'dark', 'guides' ].forEach( function ( group ) {
			form.querySelectorAll( '[data-sg-group="' + group + '"] input[type="color"]' ).forEach( function ( input ) {
				var token = input.getAttribute( 'data-sg-token' );
				if ( values[ group ] && values[ group ][ token ] ) {
					input.value = values[ group ][ token ];
				}
			} );
		} );

		form.querySelectorAll( '[data-sg-number]' ).forEach( function ( input ) {
			var key = input.getAttribute( 'data-sg-number' );
			if ( undefined !== values[ key ] ) {
				input.value = values[ key ];
			}
		} );

		var map = [
			[ '[data-sg-font]', 'font_family' ],
			[ '[data-sg-font-custom]', 'font_custom' ],
			[ '[data-sg-density]', 'density' ],
			[ '[data-sg-shadow]', 'shadow' ]
		];

		map.forEach( function ( pair ) {
			var node = control( pair[ 0 ] );
			if ( node && undefined !== values[ pair[ 1 ] ] ) {
				node.value = values[ pair[ 1 ] ];
			}
		} );

		[ [ '[data-sg-transparent]', 'transparent_bg' ], [ '[data-sg-animations]', 'animations' ] ].forEach( function ( pair ) {
			var node = control( pair[ 0 ] );
			if ( node ) {
				node.checked = !! values[ pair[ 1 ] ];
			}
		} );

		var scheme = form.querySelector( '[data-sg-scheme][value="' + values.scheme + '"]' );
		if ( scheme ) {
			scheme.checked = true;
		}

		apply();
	}

	var presetData = {};
	var presetNode = document.getElementById( 'sg-ap-presets' );

	if ( presetNode ) {
		try {
			presetData = JSON.parse( presetNode.textContent );
		} catch ( error ) {
			presetData = {};
		}
	}

	form.querySelectorAll( '[data-sg-preset]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var values = presetData[ button.getAttribute( 'data-sg-preset' ) ];

			if ( values ) {
				loadPreset( values );
			}
		} );
	} );

	form.addEventListener( 'input', apply );
	form.addEventListener( 'change', apply );

	apply();
}( window, document ) );
