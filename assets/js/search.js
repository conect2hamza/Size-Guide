/**
 * Size Guide — instant client-side search.
 *
 * Works over the flat index the data loader builds, so a query never touches
 * the server and never reloads the page.
 */
( function ( window ) {
	'use strict';

	var SizeGuide = window.SizeGuide || ( window.SizeGuide = {} );

	/**
	 * Split a query into comparable tokens.
	 *
	 * @param {string} value Raw query.
	 * @return {string[]} Tokens.
	 */
	function tokenize( value ) {
		return String( value )
			.toLowerCase()
			.replace( /[×x]\s*/g, ' x ' )
			.split( /[\s,/|]+/ )
			.filter( function ( token ) {
				return token.length > 0;
			} );
	}

	/**
	 * Pull "1080x1350" style dimension pairs out of a query.
	 *
	 * @param {string} value Raw query.
	 * @return {Array<{width:number,height:number}>} Pairs.
	 */
	function dimensionPairs( value ) {
		var pairs = [];
		var pattern = /(\d+(?:\.\d+)?)\s*[x×*by\s]+\s*(\d+(?:\.\d+)?)/gi;
		var match;

		while ( ( match = pattern.exec( value ) ) !== null ) {
			pairs.push( {
				width: parseFloat( match[ 1 ] ),
				height: parseFloat( match[ 2 ] )
			} );
		}

		return pairs;
	}

	var Search = {

		/**
		 * Build a searcher over one index.
		 *
		 * @param {Array}  index         Flat index entries.
		 * @param {Object} abbreviations Shorthand map, e.g. { ig: 'instagram' }.
		 * @return {{query: Function}} Searcher.
		 */
		create: function ( index, abbreviations ) {
			index = index || [];
			abbreviations = abbreviations || {};

			/**
			 * Expand designer shorthand so "yt thumbnail" finds the YouTube thumbnail.
			 *
			 * @param {string[]} tokens Query tokens.
			 * @return {string[]} Expanded tokens.
			 */
			function expand( tokens ) {
				var out = [];

				tokens.forEach( function ( token ) {
					var expansion = abbreviations[ token ];

					if ( expansion ) {
						out = out.concat( tokenize( expansion ) );
					} else {
						out.push( token );
					}
				} );

				return out;
			}

			/**
			 * Score one entry against the query tokens.
			 *
			 * @param {Object}   entry  Index entry.
			 * @param {string[]} tokens Expanded tokens.
			 * @param {Array}    pairs  Dimension pairs from the query.
			 * @return {number} Score, 0 when the entry does not match.
			 */
			function score( entry, tokens, pairs ) {
				var total = 0;
				var name = entry.name.toLowerCase();
				var i;

				for ( i = 0; i < tokens.length; i++ ) {
					var token = tokens[ i ];
					var position = entry.text.indexOf( token );

					if ( position === -1 ) {
						return 0;
					}

					total += 1;

					if ( name.indexOf( token ) === 0 ) {
						total += 4;
					} else if ( name.indexOf( token ) > -1 ) {
						total += 2;
					}

					if ( position === 0 ) {
						total += 2;
					}
				}

				// An exact dimension match should always win.
				for ( i = 0; i < pairs.length; i++ ) {
					if ( pairs[ i ].width === entry.width && pairs[ i ].height === entry.height ) {
						total += 25;
					}
				}

				// Short, exact names beat long ones on an otherwise equal score.
				if ( name === tokens.join( ' ' ) ) {
					total += 10;
				}

				return total;
			}

			return {
				/**
				 * Run a query.
				 *
				 * @param {string} value Raw query.
				 * @param {number} limit Maximum results.
				 * @return {Array} Matching index entries, best first.
				 */
				query: function ( value, limit ) {
					value = String( value || '' ).trim();
					limit = limit || 30;

					if ( value.length < 1 ) {
						return [];
					}

					var tokens = expand( tokenize( value ) );
					var pairs = dimensionPairs( value );

					if ( ! tokens.length ) {
						return [];
					}

					var results = [];

					index.forEach( function ( entry ) {
						var value2 = score( entry, tokens, pairs );

						if ( value2 > 0 ) {
							results.push( { entry: entry, score: value2 } );
						}
					} );

					results.sort( function ( a, b ) {
						if ( b.score !== a.score ) {
							return b.score - a.score;
						}
						return a.entry.label.length - b.entry.label.length;
					} );

					return results.slice( 0, limit ).map( function ( result ) {
						return result.entry;
					} );
				}
			};
		},

		tokenize: tokenize,
		dimensionPairs: dimensionPairs
	};

	SizeGuide.Search = Search;
}( window ) );
