/**
 * Size Guide — unit, DPI and aspect-ratio maths.
 *
 * Every other module goes through here so a millimetre means the same thing
 * everywhere in the plugin.
 */
( function ( window ) {
	'use strict';

	var SizeGuide = window.SizeGuide || ( window.SizeGuide = {} );

	/* Physical units per inch. Pixels depend on DPI, so they are handled separately. */
	var PER_INCH = {
		in: 1,
		mm: 25.4,
		cm: 2.54
	};

	var Convert = {

		UNITS: [ 'px', 'mm', 'cm', 'in' ],

		/**
		 * Is this a physical (printable) unit?
		 *
		 * @param {string} unit Unit key.
		 * @return {boolean} True for mm, cm and in.
		 */
		isPhysical: function ( unit ) {
			return Object.prototype.hasOwnProperty.call( PER_INCH, unit );
		},

		/**
		 * Convert a value to inches.
		 *
		 * @param {number} value Value.
		 * @param {string} unit  Source unit.
		 * @param {number} dpi   Pixels per inch, used when unit is px.
		 * @return {number} Inches.
		 */
		toInches: function ( value, unit, dpi ) {
			value = parseFloat( value ) || 0;

			if ( 'px' === unit ) {
				return value / ( parseFloat( dpi ) || 72 );
			}

			return value / ( PER_INCH[ unit ] || 1 );
		},

		/**
		 * Convert between any two supported units.
		 *
		 * @param {number} value Value.
		 * @param {string} from  Source unit.
		 * @param {string} to    Target unit.
		 * @param {number} dpi   Pixels per inch.
		 * @return {number} Converted value.
		 */
		convert: function ( value, from, to, dpi ) {
			if ( from === to ) {
				return parseFloat( value ) || 0;
			}

			var inches = Convert.toInches( value, from, dpi );

			if ( 'px' === to ) {
				return inches * ( parseFloat( dpi ) || 72 );
			}

			return inches * ( PER_INCH[ to ] || 1 );
		},

		/**
		 * Pixels for a physical measurement at a given DPI.
		 *
		 * @param {number} value Value.
		 * @param {string} unit  Physical unit.
		 * @param {number} dpi   Pixels per inch.
		 * @return {number} Whole pixels.
		 */
		toPixels: function ( value, unit, dpi ) {
			return Math.round( Convert.convert( value, unit, 'px', dpi ) );
		},

		/**
		 * Round a value the way that unit is normally written.
		 *
		 * @param {number} value Value.
		 * @param {string} unit  Unit key.
		 * @return {number} Rounded value.
		 */
		round: function ( value, unit ) {
			value = parseFloat( value ) || 0;

			if ( 'px' === unit ) {
				return Math.round( value );
			}
			if ( 'in' === unit ) {
				return Math.round( value * 100 ) / 100;
			}
			if ( 'cm' === unit ) {
				return Math.round( value * 100 ) / 100;
			}

			return Math.round( value * 10 ) / 10;
		},

		/**
		 * Format one measurement for display.
		 *
		 * @param {number} value Value.
		 * @param {string} unit  Unit key.
		 * @return {string} e.g. "210 mm".
		 */
		format: function ( value, unit ) {
			return Convert.number( Convert.round( value, unit ) ) + ' ' + unit;
		},

		/**
		 * Trim trailing zeros from a number.
		 *
		 * @param {number} value Value.
		 * @return {string} Clean number as text.
		 */
		number: function ( value ) {
			var rounded = Math.round( ( parseFloat( value ) || 0 ) * 1000 ) / 1000;
			return String( rounded );
		},

		/**
		 * Format a width x height pair.
		 *
		 * @param {number} width  Width.
		 * @param {number} height Height.
		 * @param {string} unit   Unit key.
		 * @param {string} sep    Separator, defaults to a multiplication sign.
		 * @return {string} e.g. "1080 × 1350 px".
		 */
		dimensions: function ( width, height, unit, sep ) {
			sep = sep || ' × ';
			return Convert.number( Convert.round( width, unit ) ) + sep +
				Convert.number( Convert.round( height, unit ) ) + ' ' + unit;
		},

		/**
		 * Convert a whole size record into another unit.
		 *
		 * @param {Object} format Format record.
		 * @param {string} unit   Target unit.
		 * @param {number} dpi    Pixels per inch.
		 * @return {{width:number,height:number,unit:string}} Converted size.
		 */
		formatSize: function ( format, unit, dpi ) {
			dpi = dpi || format.dpi || 72;

			return {
				width: Convert.convert( format.width, format.unit, unit, dpi ),
				height: Convert.convert( format.height, format.unit, unit, dpi ),
				unit: unit
			};
		},

		/**
		 * Greatest common divisor.
		 *
		 * @param {number} a First number.
		 * @param {number} b Second number.
		 * @return {number} GCD.
		 */
		gcd: function ( a, b ) {
			a = Math.abs( a );
			b = Math.abs( b );

			while ( b ) {
				var t = b;
				b = a % b;
				a = t;
			}

			return a || 1;
		},

		/**
		 * Simplified aspect ratio, falling back to a decimal form when the
		 * reduced pair would be unreadable (for example 1.91:1).
		 *
		 * @param {number} width  Width.
		 * @param {number} height Height.
		 * @return {string} Ratio string.
		 */
		ratio: function ( width, height ) {
			width = parseFloat( width ) || 0;
			height = parseFloat( height ) || 0;

			if ( width <= 0 || height <= 0 ) {
				return '';
			}

			var w = Math.round( width * 100 );
			var h = Math.round( height * 100 );
			var divisor = Convert.gcd( w, h );
			var rw = w / divisor;
			var rh = h / divisor;

			if ( rw > 20 || rh > 20 ) {
				return width >= height
					? ( Math.round( ( width / height ) * 100 ) / 100 ) + ':1'
					: '1:' + ( Math.round( ( height / width ) * 100 ) / 100 );
			}

			return rw + ':' + rh;
		},

		/**
		 * Ratio as a decimal, for layout maths.
		 *
		 * @param {number} width  Width.
		 * @param {number} height Height.
		 * @return {number} width / height.
		 */
		ratioValue: function ( width, height ) {
			height = parseFloat( height ) || 1;
			return ( parseFloat( width ) || 0 ) / height;
		},

		/**
		 * Portrait, landscape or square.
		 *
		 * @param {number} width  Width.
		 * @param {number} height Height.
		 * @return {string} Orientation key.
		 */
		orientation: function ( width, height ) {
			if ( Math.abs( width - height ) < 0.01 ) {
				return 'square';
			}
			return width > height ? 'landscape' : 'portrait';
		},

		/**
		 * Every unit for one size, ready to list in the UI.
		 *
		 * @param {Object} format Format record.
		 * @param {number} dpi    Pixels per inch for the pixel row.
		 * @return {Array<{unit:string,label:string,width:number,height:number}>} Rows.
		 */
		allUnits: function ( format, dpi ) {
			dpi = dpi || format.dpi || 72;

			return Convert.UNITS.map( function ( unit ) {
				var size = Convert.formatSize( format, unit, dpi );
				var label = Convert.dimensions( size.width, size.height, unit );

				if ( 'px' === unit && 'px' !== format.unit ) {
					label += ' @ ' + dpi + ' DPI';
				}

				return {
					unit: unit,
					label: label,
					width: Convert.round( size.width, unit ),
					height: Convert.round( size.height, unit )
				};
			} );
		},

		/**
		 * Scale a four-sided box into another unit.
		 *
		 * @param {Object|null} box  Box with top/right/bottom/left.
		 * @param {string}      from Source unit.
		 * @param {string}      to   Target unit.
		 * @param {number}      dpi  Pixels per inch.
		 * @return {Object|null} Converted box.
		 */
		convertBox: function ( box, from, to, dpi ) {
			if ( ! box ) {
				return null;
			}

			return {
				top: Convert.convert( box.top, from, to, dpi ),
				right: Convert.convert( box.right, from, to, dpi ),
				bottom: Convert.convert( box.bottom, from, to, dpi ),
				left: Convert.convert( box.left, from, to, dpi )
			};
		}
	};

	SizeGuide.Convert = Convert;
}( window ) );
