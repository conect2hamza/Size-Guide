/**
 * Size Guide — the visual infographic.
 *
 * Draws the canvas, bleed, trim, margin and safe zone as a scaled SVG so a
 * designer can see the design area rather than read two numbers.
 */
( function ( window, document ) {
	'use strict';

	var SizeGuide = window.SizeGuide || ( window.SizeGuide = {} );
	var Convert = SizeGuide.Convert;
	var SVG_NS = 'http://www.w3.org/2000/svg';

	var COLORS = {
		bleed: '#e11d48',
		bleedFill: 'rgba(225, 29, 72, 0.06)',
		trim: '#111827',
		canvas: '#ffffff',
		margin: '#f59e0b',
		safe: '#16a34a',
		safeFill: 'rgba(22, 163, 74, 0.08)',
		grid: '#93c5fd',
		measure: '#6b7280',
		text: '#374151'
	};

	/* CSS custom property behind each colour, so the appearance settings and a
	   theme's own overrides both reach the diagram. */
	var COLOR_TOKENS = {
		canvas: '--sg-guide-canvas',
		bleed: '--sg-guide-bleed',
		bleedFill: '--sg-guide-bleed-fill',
		trim: '--sg-guide-trim',
		margin: '--sg-guide-margin',
		safe: '--sg-guide-safe',
		safeFill: '--sg-guide-safe-fill',
		grid: '--sg-guide-grid',
		measure: '--sg-guide-measure',
		text: '--sg-guide-text'
	};

	var DEFAULT_OPTIONS = {
		measurements: true,
		safeZone: true,
		margins: true,
		bleed: true,
		grid: false,
		labels: true,
		unit: null,
		dpi: null
	};

	/**
	 * Create an SVG element with attributes.
	 *
	 * @param {string} name  Tag name.
	 * @param {Object} attrs Attribute map.
	 * @return {SVGElement} Element.
	 */
	function el( name, attrs ) {
		var node = document.createElementNS( SVG_NS, name );

		Object.keys( attrs || {} ).forEach( function ( key ) {
			node.setAttribute( key, attrs[ key ] );
		} );

		return node;
	}

	/**
	 * Create an SVG text node.
	 *
	 * @param {string} content Text.
	 * @param {Object} attrs   Attribute map.
	 * @return {SVGElement} Element.
	 */
	function text( content, attrs ) {
		var node = el( 'text', attrs );
		node.textContent = content;
		return node;
	}

	var Infographic = {

		COLORS: COLORS,

		/**
		 * Resolve the diagram palette from the CSS custom properties in force
		 * on an element, falling back to the built-in colours.
		 *
		 * Downloads deliberately do not use this: a template file should carry
		 * the standard guide colours whatever the site looks like.
		 *
		 * @param {HTMLElement} element Element to read the cascade from.
		 * @return {Object} Colour map.
		 */
		resolveColors: function ( element ) {
			if ( ! element || ! window.getComputedStyle ) {
				return COLORS;
			}

			var styles = window.getComputedStyle( element );
			var resolved = {};

			Object.keys( COLORS ).forEach( function ( key ) {
				var token = COLOR_TOKENS[ key ];
				var value = token ? styles.getPropertyValue( token ).trim() : '';

				resolved[ key ] = value || COLORS[ key ];
			} );

			return resolved;
		},

		/**
		 * Work out every rectangle the diagram needs, in the record's own unit.
		 *
		 * @param {Object} format Format record.
		 * @param {Object} options Render options.
		 * @return {Object} Geometry in document coordinates.
		 */
		geometry: function ( format, options ) {
			options = options || {};

			var bleed = options.bleed === false ? 0 : ( parseFloat( format.bleed ) || 0 );
			var width = parseFloat( format.width ) || 0;
			var height = parseFloat( format.height ) || 0;
			var docWidth = width + ( bleed * 2 );
			var docHeight = height + ( bleed * 2 );

			var geometry = {
				unit: format.unit,
				bleed: bleed,
				width: width,
				height: height,
				docWidth: docWidth,
				docHeight: docHeight,
				trim: { x: bleed, y: bleed, width: width, height: height },
				margin: null,
				safe: null
			};

			if ( format.margin && false !== options.margins ) {
				geometry.margin = {
					x: bleed + format.margin.left,
					y: bleed + format.margin.top,
					width: width - format.margin.left - format.margin.right,
					height: height - format.margin.top - format.margin.bottom
				};
			}

			if ( format.safe_zone && false !== options.safeZone ) {
				geometry.safe = {
					x: bleed + format.safe_zone.left,
					y: bleed + format.safe_zone.top,
					width: width - format.safe_zone.left - format.safe_zone.right,
					height: height - format.safe_zone.top - format.safe_zone.bottom
				};
			}

			return geometry;
		},

		/**
		 * Build the infographic SVG for one format.
		 *
		 * @param {Object} format  Format record.
		 * @param {Object} options Render options.
		 * @param {Object} strings Translated labels.
		 * @return {SVGElement} The SVG node.
		 */
		build: function ( format, options, strings ) {
			options = Object.assign( {}, DEFAULT_OPTIONS, options || {} );
			strings = strings || {};

			var colors = options.colors || COLORS;
			var geo = Infographic.geometry( format, options );
			var scale = Math.max( geo.docWidth, geo.docHeight );
			var stroke = scale / 400;
			var font = scale / 30;
			var pad = options.measurements ? scale * 0.16 : scale * 0.03;

			var displayUnit = options.unit || format.unit;
			var dpi = options.dpi || format.dpi || 72;

			var svg = el( 'svg', {
				xmlns: SVG_NS,
				viewBox: [ -pad, -pad, geo.docWidth + ( pad * 2 ), geo.docHeight + ( pad * 2 ) ].join( ' ' ),
				class: 'sg-infographic__svg',
				role: 'img',
				preserveAspectRatio: 'xMidYMid meet'
			} );

			var label = format.platform_name + ' ' + format.name + ', ' +
				Convert.dimensions(
					Convert.convert( format.width, format.unit, displayUnit, dpi ),
					Convert.convert( format.height, format.unit, displayUnit, dpi ),
					displayUnit
				) + ', ' + ( strings.aspectRatio || 'Aspect ratio' ) + ' ' + format.aspect_ratio;

			svg.setAttribute( 'aria-label', label );
			svg.appendChild( text( label, { class: 'sg-visually-hidden-svg', x: -pad, y: -pad, 'font-size': 0.01, fill: 'transparent' } ) );

			// Bleed area.
			if ( geo.bleed > 0 && false !== options.bleed ) {
				svg.appendChild( el( 'rect', {
					x: 0,
					y: 0,
					width: geo.docWidth,
					height: geo.docHeight,
					fill: colors.bleedFill,
					stroke: colors.bleed,
					'stroke-width': stroke,
					'stroke-dasharray': stroke * 6
				} ) );
			}

			// Canvas / trim.
			svg.appendChild( el( 'rect', {
				x: geo.trim.x,
				y: geo.trim.y,
				width: geo.trim.width,
				height: geo.trim.height,
				fill: colors.canvas,
				stroke: colors.trim,
				'stroke-width': stroke * 1.4
			} ) );

			// Grid: thirds plus centre lines.
			if ( options.grid ) {
				var grid = el( 'g', {
					stroke: colors.grid,
					'stroke-width': stroke * 0.8,
					'stroke-dasharray': stroke * 4,
					fill: 'none'
				} );

				[ 1 / 3, 2 / 3 ].forEach( function ( fraction ) {
					grid.appendChild( el( 'line', {
						x1: geo.trim.x + ( geo.trim.width * fraction ),
						y1: geo.trim.y,
						x2: geo.trim.x + ( geo.trim.width * fraction ),
						y2: geo.trim.y + geo.trim.height
					} ) );
					grid.appendChild( el( 'line', {
						x1: geo.trim.x,
						y1: geo.trim.y + ( geo.trim.height * fraction ),
						x2: geo.trim.x + geo.trim.width,
						y2: geo.trim.y + ( geo.trim.height * fraction )
					} ) );
				} );

				svg.appendChild( grid );
			}

			// Margin.
			if ( geo.margin && geo.margin.width > 0 && geo.margin.height > 0 ) {
				svg.appendChild( el( 'rect', {
					x: geo.margin.x,
					y: geo.margin.y,
					width: geo.margin.width,
					height: geo.margin.height,
					fill: 'none',
					stroke: colors.margin,
					'stroke-width': stroke,
					'stroke-dasharray': stroke * 4
				} ) );
			}

			// Safe zone.
			if ( geo.safe && geo.safe.width > 0 && geo.safe.height > 0 ) {
				svg.appendChild( el( 'rect', {
					x: geo.safe.x,
					y: geo.safe.y,
					width: geo.safe.width,
					height: geo.safe.height,
					fill: colors.safeFill,
					stroke: colors.safe,
					'stroke-width': stroke,
					'stroke-dasharray': stroke * 3
				} ) );

				if ( options.labels ) {
					svg.appendChild( text( ( strings.safeContentArea || 'Safe content area' ).toUpperCase(), {
						x: geo.safe.x + ( geo.safe.width / 2 ),
						y: geo.safe.y + ( geo.safe.height / 2 ) + ( font / 3 ),
						'text-anchor': 'middle',
						'font-size': Math.min( font, geo.safe.width / 12 ),
						'font-family': 'inherit',
						fill: colors.safe,
						'letter-spacing': stroke
					} ) );
				}
			}

			if ( options.labels ) {
				Infographic.appendLabels( svg, geo, format, font, strings, colors );
			}

			if ( options.measurements ) {
				Infographic.appendMeasurements( svg, geo, format, {
					font: font,
					stroke: stroke,
					pad: pad,
					unit: displayUnit,
					dpi: dpi,
					colors: colors
				} );
			}

			return svg;
		},

		/**
		 * Layer name labels around the edges of the diagram.
		 *
		 * @param {SVGElement} svg     Target SVG.
		 * @param {Object}     geo     Geometry.
		 * @param {Object}     format  Format record.
		 * @param {number}     font    Base font size.
		 * @param {Object}     strings Translated labels.
		 * @param {Object}     colors  Resolved palette.
		 */
		appendLabels: function ( svg, geo, format, font, strings, colors ) {
			colors = colors || COLORS;
			var small = font * 0.62;

			// A band thinner than this cannot hold readable type, and a label
			// crammed into it just collides with the edge it describes.
			var minBand = Math.max( geo.docWidth, geo.docHeight ) * 0.05;

			if ( geo.bleed >= minBand ) {
				svg.appendChild( text( ( strings.bleed || 'Bleed' ).toUpperCase(), {
					x: geo.docWidth / 2,
					y: geo.bleed * 0.72,
					'text-anchor': 'middle',
					'font-size': Math.min( small, geo.bleed * 0.9 ),
					fill: colors.bleed
				} ) );
			}

			var gap = geo.margin ? geo.margin.y - geo.trim.y : 0;

			if ( geo.margin && gap >= minBand ) {
				svg.appendChild( text( ( strings.margin || 'Margin' ).toUpperCase(), {
					x: geo.trim.x + ( geo.trim.width / 2 ),
					y: geo.trim.y + Math.max( gap * 0.62, small ),
					'text-anchor': 'middle',
					'font-size': Math.min( small, Math.max( gap * 0.7, 1 ) ),
					fill: colors.margin
				} ) );
			}
		},

		/**
		 * Draw the width and height measurement lines.
		 *
		 * @param {SVGElement} svg    Target SVG.
		 * @param {Object}     geo    Geometry.
		 * @param {Object}     format Format record.
		 * @param {Object}     opts   Drawing options.
		 */
		appendMeasurements: function ( svg, geo, format, opts ) {
			var colors = opts.colors || COLORS;
			var group = el( 'g', {
				stroke: colors.measure,
				'stroke-width': opts.stroke,
				fill: 'none'
			} );

			var offset = opts.pad * 0.55;
			var tick = opts.pad * 0.12;

			// Horizontal measurement above the canvas.
			var y = -offset;
			group.appendChild( el( 'path', {
				d: 'M ' + geo.trim.x + ' ' + y + ' H ' + ( geo.trim.x + geo.trim.width ) +
					' M ' + geo.trim.x + ' ' + ( y - tick ) + ' V ' + ( y + tick ) +
					' M ' + ( geo.trim.x + geo.trim.width ) + ' ' + ( y - tick ) + ' V ' + ( y + tick )
			} ) );

			// Vertical measurement to the left.
			var x = -offset;
			group.appendChild( el( 'path', {
				d: 'M ' + x + ' ' + geo.trim.y + ' V ' + ( geo.trim.y + geo.trim.height ) +
					' M ' + ( x - tick ) + ' ' + geo.trim.y + ' H ' + ( x + tick ) +
					' M ' + ( x - tick ) + ' ' + ( geo.trim.y + geo.trim.height ) + ' H ' + ( x + tick )
			} ) );

			svg.appendChild( group );

			var widthLabel = Convert.number(
				Convert.round( Convert.convert( format.width, format.unit, opts.unit, opts.dpi ), opts.unit )
			) + ' ' + opts.unit;

			var heightLabel = Convert.number(
				Convert.round( Convert.convert( format.height, format.unit, opts.unit, opts.dpi ), opts.unit )
			) + ' ' + opts.unit;

			svg.appendChild( text( widthLabel, {
				x: geo.trim.x + ( geo.trim.width / 2 ),
				y: y - ( tick * 1.8 ),
				'text-anchor': 'middle',
				'font-size': opts.font * 0.8,
				fill: colors.text
			} ) );

			var heightText = text( heightLabel, {
				x: x - ( tick * 1.8 ),
				y: geo.trim.y + ( geo.trim.height / 2 ),
				'text-anchor': 'middle',
				'font-size': opts.font * 0.8,
				fill: colors.text
			} );
			heightText.setAttribute(
				'transform',
				'rotate(-90 ' + ( x - ( tick * 1.8 ) ) + ' ' + ( geo.trim.y + ( geo.trim.height / 2 ) ) + ')'
			);
			svg.appendChild( heightText );
		},

		/**
		 * Render into a container, replacing whatever was there.
		 *
		 * @param {HTMLElement} container Target element.
		 * @param {Object}      format    Format record.
		 * @param {Object}      options   Render options.
		 * @param {Object}      strings   Translated labels.
		 * @return {SVGElement} The rendered SVG.
		 */
		render: function ( container, format, options, strings ) {
			options = Object.assign( {}, options || {}, {
				colors: Infographic.resolveColors( container )
			} );

			var svg = Infographic.build( format, options, strings );

			container.textContent = '';
			container.appendChild( svg );

			return svg;
		}
	};

	SizeGuide.Infographic = Infographic;
}( window, document ) );
