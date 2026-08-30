/**
 * Size Guide — frontend application.
 *
 * Renders the whole UI from the dataset handed over by PHP. Nothing about a
 * platform or a format is hard-coded here: add a record to the JSON and it
 * appears in the navigation, the search and the infographic.
 */
( function ( window, document ) {
	'use strict';

	var SizeGuide = window.SizeGuide || ( window.SizeGuide = {} );
	var Convert = SizeGuide.Convert;
	var Infographic = SizeGuide.Infographic;
	var Templates = SizeGuide.Templates;

	var DPI_PRESETS = [ 72, 96, 150, 300, 600 ];

	/**
	 * Small DOM builder.
	 *
	 * @param {string} tag      Tag name.
	 * @param {Object} attrs    Attributes; "class", "text", "html" and on* handlers are special-cased.
	 * @param {Array}  children Child nodes.
	 * @return {HTMLElement} Element.
	 */
	function h( tag, attrs, children ) {
		var node = document.createElement( tag );

		Object.keys( attrs || {} ).forEach( function ( key ) {
			var value = attrs[ key ];

			if ( value === null || value === undefined || value === false ) {
				return;
			}

			if ( 'text' === key ) {
				node.textContent = value;
			} else if ( 'html' === key ) {
				node.innerHTML = value;
			} else if ( key.indexOf( 'on' ) === 0 && typeof value === 'function' ) {
				node.addEventListener( key.slice( 2 ).toLowerCase(), value );
			} else if ( true === value ) {
				node.setAttribute( key, '' );
			} else {
				node.setAttribute( key, value );
			}
		} );

		( children || [] ).forEach( function ( child ) {
			if ( child === null || child === undefined || false === child ) {
				return;
			}
			node.appendChild( typeof child === 'string' ? document.createTextNode( child ) : child );
		} );

		return node;
	}

	/**
	 * Copy text to the clipboard, with a fallback for older browsers.
	 *
	 * @param {string} value Text to copy.
	 * @return {Promise<boolean>} Resolves with whether the copy worked.
	 */
	function copyText( value ) {
		if ( window.navigator.clipboard && window.navigator.clipboard.writeText ) {
			return window.navigator.clipboard.writeText( value ).then( function () {
				return true;
			} ).catch( function () {
				return legacyCopy( value );
			} );
		}

		return Promise.resolve( legacyCopy( value ) );
	}

	/**
	 * execCommand copy fallback.
	 *
	 * @param {string} value Text to copy.
	 * @return {boolean} Success.
	 */
	function legacyCopy( value ) {
		var field = document.createElement( 'textarea' );
		field.value = value;
		field.setAttribute( 'readonly', '' );
		field.style.position = 'absolute';
		field.style.left = '-9999px';

		document.body.appendChild( field );
		field.select();

		var ok = false;
		try {
			ok = document.execCommand( 'copy' );
		} catch ( error ) {
			ok = false;
		}

		document.body.removeChild( field );

		return ok;
	}

	/**
	 * One Size Guide instance.
	 *
	 * @param {HTMLElement} root Root element rendered by PHP.
	 * @param {Object}      data Localised data.
	 * @constructor
	 */
	function App( root, data ) {
		this.root = root;
		this.dataset = data.dataset || { sections: [], index: [] };
		this.strings = data.i18n || {};
		this.settings = data.settings || {};
		// Several guides can share one page, so each instance takes its opening
		// view from its own element rather than the first shortcode's attributes.
		this.initial = Object.assign( {}, data.initial || {}, {
			section: root.getAttribute( 'data-section' ) || '',
			platform: root.getAttribute( 'data-platform' ) || '',
			format: root.getAttribute( 'data-format' ) || '',
			category: root.getAttribute( 'data-group' ) || ''
		} );
		this.search = SizeGuide.Search.create( this.dataset.index, data.abbreviations || {} );

		this.nodes = {
			sections: root.querySelector( '[data-sg-role="sections"]' ),
			sidebar: root.querySelector( '[data-sg-role="sidebar"]' ),
			content: root.querySelector( '[data-sg-role="content"]' ),
			searchInput: root.querySelector( '[data-sg-role="search-input"]' ),
			searchClear: root.querySelector( '[data-sg-role="search-clear"]' ),
			searchResults: root.querySelector( '[data-sg-role="search-results"]' ),
			status: root.querySelector( '[data-sg-role="status"]' )
		};

		this.state = {
			section: '',
			platform: '',
			format: '',
			query: '',
			unit: this.settings.defaultUnit || 'px',
			dpi: this.settings.defaultDpi || 300,
			// A manual unit or DPI choice sticks while you stay in one section:
			// carrying 72 DPI from a social size over to A4 would be misleading.
			unitSection: '',
			dpiSection: '',
			toggles: {
				measurements: true,
				safeZone: true,
				margins: true,
				bleed: true,
				grid: false,
				labels: true
			}
		};

		this.ownsHash = ! document.querySelector( '.sg-app[data-sg-hash-owner]' );
		if ( this.ownsHash ) {
			root.setAttribute( 'data-sg-hash-owner', '1' );
		}

		this.init();
	}

	App.prototype = {

		/**
		 * First render and event wiring.
		 */
		init: function () {
			var self = this;

			this.applyInitialState();
			this.bindSearch();

			if ( this.ownsHash ) {
				window.addEventListener( 'hashchange', function () {
					self.readHash();
				} );
				this.readHash( true );
			}

			this.render();
		},

		/**
		 * Apply the shortcode attributes as the starting view.
		 */
		applyInitialState: function () {
			var initial = this.initial;
			var sections = this.dataset.sections;

			this.state.section = this.findSectionId( initial.section ) ||
				this.findSectionId( this.settings.defaultSection ) ||
				( sections[ 0 ] ? sections[ 0 ].id : '' );

			if ( initial.format ) {
				var format = this.getFormat( initial.format );
				if ( format ) {
					this.state.section = format.section;
					this.state.platform = format.platform;
					this.state.format = format.id;
				}
			}

			if ( ! this.state.platform && initial.platform ) {
				var platform = this.getPlatform( initial.platform );
				if ( platform ) {
					this.state.section = platform.section;
					this.state.platform = platform.id;
				}
			}

			if ( ! this.state.platform && initial.category ) {
				var group = this.getGroup( initial.category );
				if ( group && group.platforms.length ) {
					this.state.section = group.section;
					this.state.group = group.id;
					this.state.platform = group.platforms[ 0 ].id;
				}
			}

			if ( ! this.state.platform ) {
				var first = this.firstPlatformIn( this.state.section );
				this.state.platform = first ? first.id : '';
			}

			if ( initial.search ) {
				this.state.query = initial.search;
				if ( this.nodes.searchInput ) {
					this.nodes.searchInput.value = initial.search;
				}
			}

			this.syncUnitToPlatform();
		},

		/* ----------------------------------------------------------------
		 * Dataset lookups
		 * ------------------------------------------------------------- */

		/**
		 * Iterate every platform in the dataset.
		 *
		 * @param {Function} callback Receives (platform, group, section).
		 */
		eachPlatform: function ( callback ) {
			this.dataset.sections.forEach( function ( section ) {
				section.groups.forEach( function ( group ) {
					group.platforms.forEach( function ( platform ) {
						callback( platform, group, section );
					} );
				} );
			} );
		},

		/**
		 * Find a section id, tolerating a missing or unknown value.
		 *
		 * @param {string} id Candidate id.
		 * @return {string} Section id or an empty string.
		 */
		findSectionId: function ( id ) {
			if ( ! id ) {
				return '';
			}

			var found = '';
			this.dataset.sections.forEach( function ( section ) {
				if ( section.id === id ) {
					found = section.id;
				}
			} );

			return found;
		},

		/**
		 * Get a section object.
		 *
		 * @param {string} id Section id.
		 * @return {Object|null} Section.
		 */
		getSection: function ( id ) {
			var found = null;
			this.dataset.sections.forEach( function ( section ) {
				if ( section.id === id ) {
					found = section;
				}
			} );
			return found;
		},

		/**
		 * Get a group by id.
		 *
		 * @param {string} id Group id.
		 * @return {Object|null} Group with its section id attached.
		 */
		getGroup: function ( id ) {
			var found = null;

			this.dataset.sections.forEach( function ( section ) {
				section.groups.forEach( function ( group ) {
					if ( group.id === id ) {
						found = group;
					}
				} );
			} );

			return found;
		},

		/**
		 * Get a platform by id.
		 *
		 * @param {string} id Platform id.
		 * @return {Object|null} Platform.
		 */
		getPlatform: function ( id ) {
			var found = null;

			this.eachPlatform( function ( platform ) {
				if ( platform.id === id ) {
					found = platform;
				}
			} );

			return found;
		},

		/**
		 * Get a format by id.
		 *
		 * @param {string} id Format id.
		 * @return {Object|null} Format.
		 */
		getFormat: function ( id ) {
			var found = null;

			this.eachPlatform( function ( platform ) {
				platform.categories.forEach( function ( category ) {
					category.formats.forEach( function ( format ) {
						if ( format.id === id ) {
							found = format;
						}
					} );
				} );
			} );

			return found;
		},

		/**
		 * First platform inside a section.
		 *
		 * @param {string} sectionId Section id.
		 * @return {Object|null} Platform.
		 */
		firstPlatformIn: function ( sectionId ) {
			var section = this.getSection( sectionId );

			if ( ! section ) {
				return null;
			}

			for ( var i = 0; i < section.groups.length; i++ ) {
				if ( section.groups[ i ].platforms.length ) {
					return section.groups[ i ].platforms[ 0 ];
				}
			}

			return null;
		},

		/**
		 * Default the display unit to something sensible for the current section.
		 *
		 * A unit the visitor picked by hand is kept while they stay in that
		 * section, and dropped once they move to another one.
		 */
		syncUnitToPlatform: function () {
			if ( this.state.unitSection === this.state.section ) {
				return;
			}

			this.state.unitSection = '';
			this.state.unit = 'print' === this.state.section ? 'mm' : ( this.settings.defaultUnit || 'px' );
		},

		/**
		 * Pick the DPI a record should be converted at.
		 *
		 * A record given in millimetres carries its intended print resolution,
		 * so that wins. A record given in pixels only gets converted to a
		 * physical unit when someone is heading to print, so the site's default
		 * DPI is the more useful basis there.
		 *
		 * Like the unit, a hand-picked DPI survives while the visitor stays in
		 * the same section.
		 *
		 * @param {Object} format Format record.
		 */
		syncDpiToFormat: function ( format ) {
			if ( this.state.dpiSection === format.section ) {
				return;
			}

			this.state.dpiSection = '';
			this.state.dpi = ( 'px' === format.unit )
				? ( this.settings.defaultDpi || format.dpi || 72 )
				: ( format.dpi || this.settings.defaultDpi || 300 );
		},

		/* ----------------------------------------------------------------
		 * Routing
		 * ------------------------------------------------------------- */

		/**
		 * Read the state out of the location hash.
		 *
		 * @param {boolean} silent Skip the re-render (used during init).
		 */
		readHash: function ( silent ) {
			var match = /#sg=([^&]+)/.exec( window.location.hash || '' );

			if ( ! match ) {
				return;
			}

			var parts = decodeURIComponent( match[ 1 ] ).split( '/' );
			var section = this.findSectionId( parts[ 0 ] );

			if ( section ) {
				this.state.section = section;
				this.syncUnitToPlatform();
			}

			if ( parts[ 1 ] ) {
				var platform = this.getPlatform( parts[ 1 ] );
				if ( platform ) {
					this.state.section = platform.section;
					this.state.platform = platform.id;
				}
			}

			this.state.format = '';
			if ( parts[ 2 ] ) {
				var format = this.getFormat( parts[ 2 ] );
				if ( format ) {
					this.state.section = format.section;
					this.state.platform = format.platform;
					this.state.format = format.id;
				}
			}

			if ( ! silent ) {
				this.render();
			}
		},

		/**
		 * Push the current state into the location hash.
		 */
		writeHash: function () {
			if ( ! this.ownsHash ) {
				return;
			}

			var parts = [ this.state.section ];

			if ( this.state.platform ) {
				parts.push( this.state.platform );
			}
			if ( this.state.format ) {
				parts.push( this.state.format );
			}

			var hash = '#sg=' + parts.join( '/' );

			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', hash );
			} else {
				window.location.hash = hash;
			}
		},

		/**
		 * Change state and re-render.
		 *
		 * @param {Object} changes Partial state.
		 */
		go: function ( changes ) {
			Object.assign( this.state, changes );
			this.syncUnitToPlatform();
			this.writeHash();
			this.render();
		},

		/**
		 * Announce something to screen readers.
		 *
		 * @param {string} message Message.
		 */
		announce: function ( message ) {
			if ( this.nodes.status ) {
				this.nodes.status.textContent = message;
			}
		},

		/* ----------------------------------------------------------------
		 * Search
		 * ------------------------------------------------------------- */

		/**
		 * Wire the search field.
		 */
		bindSearch: function () {
			var self = this;
			var input = this.nodes.searchInput;

			if ( ! input ) {
				return;
			}

			input.addEventListener( 'input', function () {
				self.state.query = input.value;
				self.renderSearchResults();
			} );

			input.addEventListener( 'keydown', function ( event ) {
				if ( 'Escape' === event.key ) {
					input.value = '';
					self.state.query = '';
					self.renderSearchResults();
					return;
				}

				if ( 'ArrowDown' === event.key ) {
					var first = self.nodes.searchResults.querySelector( '.sg-result' );
					if ( first ) {
						event.preventDefault();
						first.focus();
					}
				}
			} );

			if ( this.nodes.searchClear ) {
				this.nodes.searchClear.addEventListener( 'click', function () {
					input.value = '';
					self.state.query = '';
					self.renderSearchResults();
					input.focus();
				} );
			}

			document.addEventListener( 'click', function ( event ) {
				if ( ! self.root.contains( event.target ) ) {
					self.hideSearchResults();
				}
			} );

			if ( this.state.query ) {
				this.renderSearchResults();
			}
		},

		/**
		 * Hide the results dropdown.
		 */
		hideSearchResults: function () {
			if ( this.nodes.searchResults ) {
				this.nodes.searchResults.hidden = true;
			}
			if ( this.nodes.searchInput ) {
				this.nodes.searchInput.setAttribute( 'aria-expanded', 'false' );
			}
		},

		/**
		 * Render the live results dropdown.
		 */
		renderSearchResults: function () {
			var self = this;
			var box = this.nodes.searchResults;

			if ( ! box ) {
				return;
			}

			var query = this.state.query.trim();

			if ( this.nodes.searchClear ) {
				this.nodes.searchClear.hidden = query.length === 0;
			}

			if ( query.length < 1 ) {
				this.hideSearchResults();
				box.textContent = '';
				return;
			}

			var results = this.search.query( query, 12 );

			box.textContent = '';
			box.hidden = false;
			this.nodes.searchInput.setAttribute( 'aria-expanded', 'true' );

			if ( ! results.length ) {
				box.appendChild( h( 'p', { class: 'sg-result__empty', text: this.strings.noResults || 'No sizes matched that search.' } ) );
				this.announce( this.strings.noResults || 'No results' );
				return;
			}

			results.forEach( function ( entry ) {
				var button = h( 'button', {
					type: 'button',
					class: 'sg-result',
					role: 'option',
					onclick: function () {
						self.openFormat( entry.id );
					},
					onkeydown: function ( event ) {
						self.moveResultFocus( event );
					}
				}, [
					h( 'span', { class: 'sg-result__label', text: entry.label } ),
					h( 'span', { class: 'sg-result__size', text: Convert.dimensions( entry.width, entry.height, entry.unit ) } )
				] );

				box.appendChild( button );
			} );

			this.announce( results.length + ' ' + ( this.strings.results || 'results' ) );
		},

		/**
		 * Arrow-key navigation inside the results list.
		 *
		 * @param {KeyboardEvent} event Key event.
		 */
		moveResultFocus: function ( event ) {
			var items = Array.prototype.slice.call( this.nodes.searchResults.querySelectorAll( '.sg-result' ) );
			var index = items.indexOf( event.currentTarget );

			if ( 'ArrowDown' === event.key && items[ index + 1 ] ) {
				event.preventDefault();
				items[ index + 1 ].focus();
			} else if ( 'ArrowUp' === event.key ) {
				event.preventDefault();
				if ( items[ index - 1 ] ) {
					items[ index - 1 ].focus();
				} else {
					this.nodes.searchInput.focus();
				}
			} else if ( 'Escape' === event.key ) {
				this.hideSearchResults();
				this.nodes.searchInput.focus();
			}
		},

		/**
		 * Open one format from anywhere.
		 *
		 * @param {string} formatId Format id.
		 */
		openFormat: function ( formatId ) {
			var format = this.getFormat( formatId );

			if ( ! format ) {
				return;
			}

			this.hideSearchResults();
			this.go( {
				section: format.section,
				platform: format.platform,
				format: format.id
			} );

			var heading = this.nodes.content.querySelector( '.sg-detail__title' );
			if ( heading ) {
				heading.focus();
			}
		},

		/* ----------------------------------------------------------------
		 * Rendering
		 * ------------------------------------------------------------- */

		/**
		 * Render everything.
		 */
		render: function () {
			this.renderSections();
			this.renderSidebar();
			this.renderContent();
		},

		/**
		 * Section tabs (Digital / Print / Tools).
		 */
		renderSections: function () {
			var self = this;
			var nav = this.nodes.sections;

			if ( ! nav ) {
				return;
			}

			nav.textContent = '';

			var tabs = this.dataset.sections.map( function ( section ) {
				return { id: section.id, name: section.name };
			} );

			if ( this.settings.showTools ) {
				tabs.push( { id: 'tools', name: this.strings.tools || 'Tools' } );
			}

			tabs.forEach( function ( tab ) {
				var active = self.state.section === tab.id;

				nav.appendChild( h( 'button', {
					type: 'button',
					class: 'sg-tab' + ( active ? ' is-active' : '' ),
					'aria-current': active ? 'true' : null,
					text: tab.name,
					onclick: function () {
						var platform = self.firstPlatformIn( tab.id );
						self.go( {
							section: tab.id,
							platform: platform ? platform.id : '',
							format: ''
						} );
					}
				} ) );
			} );
		},

		/**
		 * Platform sidebar for the active section.
		 */
		renderSidebar: function () {
			var self = this;
			var sidebar = this.nodes.sidebar;

			if ( ! sidebar ) {
				return;
			}

			sidebar.textContent = '';

			var section = this.getSection( this.state.section );

			if ( ! section ) {
				sidebar.hidden = true;
				return;
			}

			sidebar.hidden = false;

			section.groups.forEach( function ( group ) {
				var list = h( 'ul', { class: 'sg-sidebar__list' } );

				group.platforms.forEach( function ( platform ) {
					var active = self.state.platform === platform.id;

					list.appendChild( h( 'li', {}, [
						h( 'button', {
							type: 'button',
							class: 'sg-sidebar__item' + ( active ? ' is-active' : '' ),
							'aria-current': active ? 'true' : null,
							onclick: function () {
								self.go( { platform: platform.id, format: '' } );
							}
						}, [
							platform.color ? h( 'span', { class: 'sg-dot', style: 'background:' + platform.color, 'aria-hidden': 'true' } ) : null,
							h( 'span', { text: platform.name } )
						] )
					] ) );
				} );

				sidebar.appendChild( h( 'div', { class: 'sg-sidebar__group' }, [
					h( 'h3', { class: 'sg-sidebar__heading', text: group.name } ),
					list
				] ) );
			} );
		},

		/**
		 * Main panel: tools, a format detail, or the platform's format grid.
		 */
		renderContent: function () {
			var content = this.nodes.content;

			if ( ! content ) {
				return;
			}

			content.textContent = '';

			if ( 'tools' === this.state.section ) {
				content.appendChild( this.buildTools() );
				return;
			}

			if ( this.state.format ) {
				var format = this.getFormat( this.state.format );
				if ( format ) {
					content.appendChild( this.buildDetail( format ) );
					return;
				}
			}

			var platform = this.getPlatform( this.state.platform );

			if ( ! platform ) {
				content.appendChild( h( 'p', { class: 'sg-empty', text: this.strings.noResults || '' } ) );
				return;
			}

			content.appendChild( this.buildPlatform( platform ) );
		}
	};


	/* --------------------------------------------------------------------
	 * Views
	 * ----------------------------------------------------------------- */

	Object.assign( App.prototype, {

		/**
		 * Label for a source type.
		 *
		 * @param {string} type Source type key.
		 * @return {string} Human label.
		 */
		sourceLabel: function ( type ) {
			var map = {
				official: this.strings.official || 'Official',
				recommended: this.strings.recommended || 'Recommended',
				'common-practice': this.strings.commonPractice || 'Common practice',
				estimated: this.strings.estimated || 'Estimated'
			};

			return map[ type ] || type;
		},

		/**
		 * Format one dimension pair in the current display unit.
		 *
		 * @param {Object} format Format record.
		 * @param {string} sep    Separator.
		 * @return {string} e.g. "1080 × 1350 px".
		 */
		sizeLabel: function ( format, sep ) {
			var size = Convert.formatSize( format, this.state.unit, this.state.dpi );
			return Convert.dimensions( size.width, size.height, this.state.unit, sep );
		},

		/**
		 * The platform view: every category and its format cards.
		 *
		 * @param {Object} platform Platform record.
		 * @return {HTMLElement} View.
		 */
		buildPlatform: function ( platform ) {
			var self = this;
			var wrapper = h( 'div', { class: 'sg-platform' } );

			wrapper.appendChild( h( 'header', { class: 'sg-platform__header' }, [
				h( 'h3', { class: 'sg-platform__title', text: platform.name } ),
				h( 'p', {
					class: 'sg-platform__meta',
					text: platform.categories.reduce( function ( total, category ) {
						return total + category.formats.length;
					}, 0 ) + ' ' + ( this.strings.formats || 'formats' ).toLowerCase()
				} )
			] ) );

			platform.categories.forEach( function ( category ) {
				var grid = h( 'div', { class: 'sg-grid' } );

				category.formats.forEach( function ( format ) {
					grid.appendChild( self.buildCard( format ) );
				} );

				wrapper.appendChild( h( 'section', { class: 'sg-category' }, [
					h( 'h4', { class: 'sg-category__title', text: category.name } ),
					grid
				] ) );
			} );

			return wrapper;
		},

		/**
		 * One format card.
		 *
		 * @param {Object} format Format record.
		 * @return {HTMLElement} Card.
		 */
		buildCard: function ( format ) {
			var self = this;
			var ratio = Convert.ratioValue( format.width, format.height );

			// Keep the preview inside a fixed box whatever the ratio.
			var previewWidth = ratio >= 1 ? 100 : ratio * 100;
			var previewHeight = ratio >= 1 ? ( 100 / ratio ) : 100;

			var preview = h( 'span', {
				class: 'sg-card__shape',
				style: 'width:' + previewWidth + '%;padding-top:' + ( previewHeight * ( previewWidth / 100 ) ) + '%',
				'aria-hidden': 'true'
			} );

			return h( 'button', {
				type: 'button',
				class: 'sg-card',
				'data-orientation': format.orientation,
				onclick: function () {
					self.go( { format: format.id } );
					var title = self.nodes.content.querySelector( '.sg-detail__title' );
					if ( title ) {
						title.focus();
					}
				}
			}, [
				h( 'span', { class: 'sg-card__preview' }, [ preview ] ),
				h( 'span', { class: 'sg-card__name', text: format.name } ),
				h( 'span', { class: 'sg-card__size', text: this.sizeLabel( format ) } ),
				h( 'span', { class: 'sg-card__ratio', text: format.aspect_ratio } )
			] );
		},

		/**
		 * The detail view for one format.
		 *
		 * @param {Object} format Format record.
		 * @return {HTMLElement} View.
		 */
		buildDetail: function ( format ) {
			var self = this;

			this.syncDpiToFormat( format );

			var wrapper = h( 'div', { class: 'sg-detail' } );

			wrapper.appendChild( h( 'button', {
				type: 'button',
				class: 'sg-back',
				onclick: function () {
					self.go( { format: '' } );
				}
			}, [ h( 'span', { 'aria-hidden': 'true', text: '←' } ), ' ' + ( this.strings.back || 'Back' ) ] ) );

			var badges = h( 'p', { class: 'sg-badges' } );

			if ( this.settings.showSources && format.source && format.source.type ) {
				badges.appendChild( h( 'span', {
					class: 'sg-badge sg-badge--' + format.source.type,
					text: this.sourceLabel( format.source.type )
				} ) );
			}

			if ( format.last_updated ) {
				badges.appendChild( h( 'span', {
					class: 'sg-badge sg-badge--muted',
					text: ( this.strings.lastUpdated || 'Updated' ) + ' ' + format.last_updated
				} ) );
			}

			badges.appendChild( h( 'span', { class: 'sg-badge sg-badge--muted', text: format.orientation } ) );

			wrapper.appendChild( h( 'header', { class: 'sg-detail__header' }, [
				h( 'p', { class: 'sg-detail__eyebrow', text: format.platform_name + ' · ' + format.category_name } ),
				h( 'h3', { class: 'sg-detail__title', tabindex: '-1', text: format.name } ),
				badges
			] ) );

			var figure = h( 'figure', { class: 'sg-infographic' } );
			var canvas = h( 'div', { class: 'sg-infographic__canvas' } );

			figure.appendChild( canvas );
			figure.appendChild( h( 'figcaption', {
				class: 'sg-infographic__caption',
				text: this.sizeLabel( format ) + ' · ' + ( this.strings.aspectRatio || 'Ratio' ) + ' ' + format.aspect_ratio
			} ) );
			figure.appendChild( this.buildLegend( format ) );

			var body = h( 'div', { class: 'sg-detail__body' }, [
				h( 'div', { class: 'sg-detail__visual' }, [ figure, this.buildToggles( format, canvas ) ] ),
				h( 'div', { class: 'sg-detail__specs' }, [
					this.buildSizeBlock( format, canvas, figure ),
					this.buildSpecList( format ),
					this.buildDownloads( format )
				] )
			] );

			wrapper.appendChild( body );

			// Draw once the node is in the document so the SVG can size itself.
			window.setTimeout( function () {
				self.drawInfographic( canvas, format );
			}, 0 );

			return wrapper;
		},

		/**
		 * Draw or redraw the infographic for a format.
		 *
		 * @param {HTMLElement} canvas Container.
		 * @param {Object}      format Format record.
		 */
		drawInfographic: function ( canvas, format ) {
			Infographic.render(
				canvas,
				format,
				Object.assign( {}, this.state.toggles, {
					unit: this.state.unit,
					dpi: this.state.dpi
				} ),
				this.strings
			);
		},

		/**
		 * A written key for the diagram, so colour is never the only signal.
		 *
		 * @param {Object} format Format record.
		 * @return {HTMLElement} Legend list.
		 */
		buildLegend: function ( format ) {
			var items = [ { key: 'trim', label: this.strings.canvas || 'Canvas' } ];

			if ( format.bleed ) {
				items.unshift( { key: 'bleed', label: this.strings.bleed || 'Bleed' } );
			}
			if ( format.margin ) {
				items.push( { key: 'margin', label: this.strings.margin || 'Margin' } );
			}
			if ( format.safe_zone ) {
				items.push( { key: 'safe', label: this.strings.safeZone || 'Safe zone' } );
			}

			var list = h( 'ul', { class: 'sg-legend', 'aria-label': this.strings.legend || 'Guide colours' } );

			items.forEach( function ( item ) {
				list.appendChild( h( 'li', {}, [
					h( 'span', { class: 'sg-legend--' + item.key, 'aria-hidden': 'true' } ),
					h( 'span', { text: item.label } )
				] ) );
			} );

			return list;
		},

		/**
		 * The infographic layer toggles.
		 *
		 * @param {Object}      format Format record.
		 * @param {HTMLElement} canvas Infographic container.
		 * @return {HTMLElement} Toggle list.
		 */
		buildToggles: function ( format, canvas ) {
			var self = this;
			var options = [
				{ key: 'measurements', label: this.strings.measurements || 'Measurements' },
				{ key: 'safeZone', label: this.strings.safeZone || 'Safe zone' },
				{ key: 'margins', label: this.strings.margin || 'Margins' },
				{ key: 'bleed', label: this.strings.bleed || 'Bleed' },
				{ key: 'grid', label: this.strings.grid || 'Grid' },
				{ key: 'labels', label: this.strings.labels || 'Labels' }
			];

			var list = h( 'div', { class: 'sg-toggles', role: 'group', 'aria-label': this.strings.measurements || 'Guides' } );

			options.forEach( function ( option ) {
				var disabled = ( 'safeZone' === option.key && ! format.safe_zone ) ||
					( 'margins' === option.key && ! format.margin ) ||
					( 'bleed' === option.key && ! format.bleed );

				var input = h( 'input', {
					type: 'checkbox',
					class: 'sg-toggle__input',
					checked: self.state.toggles[ option.key ] ? true : null,
					disabled: disabled ? true : null,
					onchange: function ( event ) {
						self.state.toggles[ option.key ] = event.target.checked;
						self.drawInfographic( canvas, format );
					}
				} );

				list.appendChild( h( 'label', {
					class: 'sg-toggle' + ( disabled ? ' is-disabled' : '' )
				}, [ input, h( 'span', { text: option.label } ) ] ) );
			} );

			return list;
		},

		/**
		 * Dimensions, unit switcher, DPI selector and the copy buttons.
		 *
		 * @param {Object}      format Format record.
		 * @param {HTMLElement} canvas Infographic container.
		 * @param {HTMLElement} figure Infographic figure, for the caption.
		 * @return {HTMLElement} Block.
		 */
		buildSizeBlock: function ( format, canvas, figure ) {
			var self = this;
			var block = h( 'div', { class: 'sg-size' } );
			var value = h( 'p', { class: 'sg-size__value', text: this.sizeLabel( format ) } );

			block.appendChild( value );

			/**
			 * Refresh everything that depends on the unit or DPI.
			 */
			function refresh() {
				value.textContent = self.sizeLabel( format );

				var caption = figure.querySelector( '.sg-infographic__caption' );
				if ( caption ) {
					caption.textContent = self.sizeLabel( format ) + ' · ' +
						( self.strings.aspectRatio || 'Ratio' ) + ' ' + format.aspect_ratio;
				}

				self.drawInfographic( canvas, format );

				var rows = block.querySelector( '[data-sg-role="unit-rows"]' );
				if ( rows ) {
					rows.textContent = '';
					self.buildUnitRows( format ).forEach( function ( row ) {
						rows.appendChild( row );
					} );
				}
			}

			var units = h( 'div', { class: 'sg-units', role: 'group', 'aria-label': this.strings.unit || 'Unit' } );

			Convert.UNITS.forEach( function ( unit ) {
				var button = h( 'button', {
					type: 'button',
					class: 'sg-unit' + ( self.state.unit === unit ? ' is-active' : '' ),
					'aria-pressed': self.state.unit === unit ? 'true' : 'false',
					text: unit.toUpperCase(),
					onclick: function () {
						self.state.unit = unit;
						self.state.unitSection = self.state.section;

						Array.prototype.forEach.call( units.children, function ( child ) {
							child.classList.remove( 'is-active' );
							child.setAttribute( 'aria-pressed', 'false' );
						} );
						button.classList.add( 'is-active' );
						button.setAttribute( 'aria-pressed', 'true' );

						refresh();
					}
				} );

				units.appendChild( button );
			} );

			var dpiId = 'sg-dpi-' + format.id;
			var dpiSelect = h( 'select', {
				class: 'sg-select',
				id: dpiId,
				onchange: function ( event ) {
					self.state.dpi = parseInt( event.target.value, 10 ) || 300;
					self.state.dpiSection = self.state.section;
					refresh();
				}
			} );

			SizeGuide.helpers.DPI_PRESETS.forEach( function ( dpi ) {
				dpiSelect.appendChild( h( 'option', {
					value: dpi,
					selected: self.state.dpi === dpi ? true : null,
					text: dpi + ' DPI'
				} ) );
			} );

			block.appendChild( h( 'div', { class: 'sg-size__controls' }, [
				units,
				h( 'span', { class: 'sg-size__dpi' }, [
					h( 'label', { class: 'sg-label', for: dpiId, text: this.strings.dpi || 'DPI' } ),
					dpiSelect
				] )
			] ) );

			var rows = h( 'ul', { class: 'sg-unit-rows', 'data-sg-role': 'unit-rows' } );
			this.buildUnitRows( format ).forEach( function ( row ) {
				rows.appendChild( row );
			} );
			block.appendChild( rows );

			block.appendChild( this.buildCopyRow( format ) );

			return block;
		},

		/**
		 * The "same size in every unit" rows.
		 *
		 * @param {Object} format Format record.
		 * @return {HTMLElement[]} Rows.
		 */
		buildUnitRows: function ( format ) {
			return Convert.allUnits( format, this.state.dpi ).map( function ( row ) {
				return h( 'li', { class: 'sg-unit-row' }, [
					h( 'span', { class: 'sg-unit-row__unit', text: row.unit.toUpperCase() } ),
					h( 'span', { class: 'sg-unit-row__value', text: row.label } )
				] );
			} );
		},

		/**
		 * Copy buttons for the three common dimension notations.
		 *
		 * @param {Object} format Format record.
		 * @return {HTMLElement} Row.
		 */
		buildCopyRow: function ( format ) {
			var self = this;
			var row = h( 'div', { class: 'sg-copy' } );

			var variants = [
				{ label: this.strings.copy || 'Copy size', value: function () {
					return self.sizeLabel( format );
				} },
				{ label: this.strings.copyPlain || 'Plain', value: function () {
					var size = Convert.formatSize( format, self.state.unit, self.state.dpi );
					return Convert.number( Convert.round( size.width, self.state.unit ) ) + 'x' +
						Convert.number( Convert.round( size.height, self.state.unit ) );
				} },
				{ label: this.strings.copyWithRatio || 'With ratio', value: function () {
					return self.sizeLabel( format ) + ' | ' + format.aspect_ratio;
				} }
			];

			variants.forEach( function ( variant, position ) {
				var button = h( 'button', {
					type: 'button',
					class: 'sg-button' + ( 0 === position ? ' sg-button--primary' : '' ),
					text: variant.label,
					onclick: function () {
						var value = variant.value();

						copyText( value ).then( function ( ok ) {
							var original = button.textContent;

							button.textContent = ok
								? ( self.strings.copied || 'Copied' )
								: ( self.strings.copyFailed || 'Copy failed' );
							button.classList.add( ok ? 'is-copied' : 'is-error' );

							self.announce( ok ? ( self.strings.copied || 'Copied' ) + ': ' + value : ( self.strings.copyFailed || '' ) );

							window.setTimeout( function () {
								button.textContent = original;
								button.classList.remove( 'is-copied', 'is-error' );
							}, 1600 );
						} );
					}
				} );

				row.appendChild( button );
			} );

			return row;
		},

		/**
		 * The full specification list.
		 *
		 * @param {Object} format Format record.
		 * @return {HTMLElement} Definition list.
		 */
		buildSpecList: function ( format ) {
			var self = this;
			var list = h( 'dl', { class: 'sg-specs' } );

			/**
			 * Add one row when there is something to show.
			 *
			 * @param {string}             label Row label.
			 * @param {string|HTMLElement} value Row value.
			 */
			function row( label, value ) {
				if ( ! value ) {
					return;
				}

				list.appendChild( h( 'div', { class: 'sg-specs__row' }, [
					h( 'dt', { text: label } ),
					h( 'dd', {}, [ typeof value === 'string' ? document.createTextNode( value ) : value ] )
				] ) );
			}

			/**
			 * Describe a four-sided box in the display unit.
			 *
			 * @param {Object|null} box Box.
			 * @return {string} Description.
			 */
			function boxLabel( box ) {
				if ( ! box ) {
					return '';
				}

				var converted = Convert.convertBox( box, format.unit, self.state.unit, self.state.dpi );
				var sides = [ converted.top, converted.right, converted.bottom, converted.left ].map( function ( side ) {
					return Convert.number( Convert.round( side, self.state.unit ) );
				} );

				var uniform = sides.every( function ( side ) {
					return side === sides[ 0 ];
				} );

				return ( uniform ? sides[ 0 ] : sides.join( ' / ' ) ) + ' ' + self.state.unit +
					( uniform ? '' : ' (' + [ 'top', 'right', 'bottom', 'left' ].join( ' / ' ) + ')' );
			}

			row( this.strings.aspectRatio || 'Aspect ratio', format.aspect_ratio );
			row( this.strings.orientation || 'Orientation', format.orientation );
			row( this.strings.safeZone || 'Safe zone', boxLabel( format.safe_zone ) );
			row( this.strings.margin || 'Margin', boxLabel( format.margin ) );
			row( this.strings.padding || 'Padding', boxLabel( format.padding ) );

			if ( format.bleed ) {
				var bleedUnit = Convert.convert( format.bleed, format.unit, this.state.unit, this.state.dpi );
				var working = {
					width: format.width + ( format.bleed * 2 ),
					height: format.height + ( format.bleed * 2 ),
					unit: format.unit,
					dpi: format.dpi
				};

				row(
					this.strings.bleed || 'Bleed',
					Convert.format( bleedUnit, this.state.unit ) + ' — ' +
					( this.strings.workingDocument || 'working document' ) + ' ' +
					this.sizeLabel( working )
				);
			}

			row( this.strings.dpi || 'DPI', String( format.dpi ) );

			if ( format.recommended ) {
				row( this.strings.recommended || 'Recommended', Convert.dimensions( format.recommended.width, format.recommended.height, format.unit ) );
			}
			if ( format.minimum ) {
				row( this.strings.minimum || 'Minimum', Convert.dimensions( format.minimum.width, format.minimum.height, format.unit ) );
			}
			if ( format.maximum ) {
				row( this.strings.maximum || 'Maximum', Convert.dimensions( format.maximum.width, format.maximum.height, format.unit ) );
			}

			row( this.strings.fileFormats || 'File formats', format.file_formats.join( ', ' ) );
			row( this.strings.maxFileSize || 'Max file size', format.max_file_size );
			row( this.strings.notes || 'Notes', format.notes );

			if ( this.settings.showSources && format.source && ( format.source.name || format.source.type ) ) {
				var sourceText = this.sourceLabel( format.source.type ) +
					( format.source.name ? ' — ' + format.source.name : '' ) +
					( format.source.checked_date ? ' (' + format.source.checked_date + ')' : '' );

				var sourceValue = format.source.url
					? h( 'a', { href: format.source.url, rel: 'noopener nofollow', target: '_blank', text: sourceText } )
					: sourceText;

				row( this.strings.source || 'Source', sourceValue );
			}

			return list;
		},

		/**
		 * Template download buttons.
		 *
		 * @param {Object} format Format record.
		 * @return {HTMLElement|null} Row, or null when downloads are disabled.
		 */
		buildDownloads: function ( format ) {
			var self = this;

			if ( ! this.settings.enableDownload ) {
				return h( 'div', { class: 'sg-downloads', hidden: true } );
			}

			var row = h( 'div', { class: 'sg-downloads' } );

			row.appendChild( h( 'h4', { class: 'sg-downloads__title', text: this.strings.templates || 'Templates' } ) );

			var buttons = h( 'div', { class: 'sg-downloads__row' } );

			buttons.appendChild( h( 'button', {
				type: 'button',
				class: 'sg-button',
				text: this.strings.downloadSvg || 'Clean SVG',
				onclick: function () {
					Templates.downloadSvg( format, 'clean', self.strings );
				}
			} ) );

			buttons.appendChild( h( 'button', {
				type: 'button',
				class: 'sg-button sg-button--primary',
				text: this.strings.downloadGuide || 'Guide SVG',
				onclick: function () {
					Templates.downloadSvg( format, 'guide', self.strings );
				}
			} ) );

			buttons.appendChild( h( 'button', {
				type: 'button',
				class: 'sg-button',
				text: this.strings.downloadPng || 'PNG',
				onclick: function () {
					Templates.downloadPng( format, 'guide', self.strings, function () {
						self.announce( self.strings.copyFailed || '' );
					} );
				}
			} ) );

			buttons.appendChild( h( 'button', {
				type: 'button',
				class: 'sg-button',
				text: this.strings.print || 'Print / PDF',
				onclick: function () {
					Templates.print( format, 'guide', self.strings );
				}
			} ) );

			row.appendChild( buttons );

			return row;
		}
	} );


	/* --------------------------------------------------------------------
	 * Tools
	 * ----------------------------------------------------------------- */

	Object.assign( App.prototype, {

		/**
		 * A labelled field.
		 *
		 * @param {string}      label   Label text.
		 * @param {HTMLElement} control Input or select.
		 * @return {HTMLElement} Field.
		 */
		field: function ( label, control ) {
			var id = control.id || ( 'sg-field-' + Math.random().toString( 36 ).slice( 2, 8 ) );
			control.id = id;

			return h( 'div', { class: 'sg-field' }, [
				h( 'label', { class: 'sg-label', for: id, text: label } ),
				control
			] );
		},

		/**
		 * A number input.
		 *
		 * @param {number}   value    Initial value.
		 * @param {Function} onChange Change handler.
		 * @param {Object}   attrs    Extra attributes.
		 * @return {HTMLElement} Input.
		 */
		numberInput: function ( value, onChange, attrs ) {
			return h( 'input', Object.assign( {
				type: 'number',
				class: 'sg-input',
				value: value,
				min: '0',
				step: 'any',
				oninput: onChange
			}, attrs || {} ) );
		},

		/**
		 * A unit select.
		 *
		 * @param {string}   selected Selected unit.
		 * @param {Function} onChange Change handler.
		 * @return {HTMLElement} Select.
		 */
		unitSelect: function ( selected, onChange ) {
			var select = h( 'select', { class: 'sg-select', onchange: onChange } );

			Convert.UNITS.forEach( function ( unit ) {
				select.appendChild( h( 'option', {
					value: unit,
					selected: unit === selected ? true : null,
					text: unit.toUpperCase()
				} ) );
			} );

			return select;
		},

		/**
		 * A DPI select with a custom option.
		 *
		 * @param {number}   selected Selected DPI.
		 * @param {Function} onChange Change handler.
		 * @return {HTMLElement} Select.
		 */
		dpiSelect: function ( selected, onChange ) {
			var select = h( 'select', { class: 'sg-select', onchange: onChange } );

			SizeGuide.helpers.DPI_PRESETS.forEach( function ( dpi ) {
				select.appendChild( h( 'option', {
					value: dpi,
					selected: dpi === selected ? true : null,
					text: dpi + ' DPI'
				} ) );
			} );

			return select;
		},

		/**
		 * The Tools panel.
		 *
		 * @return {HTMLElement} View.
		 */
		buildTools: function () {
			return h( 'div', { class: 'sg-tools' }, [
				this.buildUnitConverter(),
				this.buildDpiCalculator(),
				this.buildRatioCalculator(),
				this.buildCustomCanvas()
			] );
		},

		/**
		 * Unit converter: one measurement in every unit.
		 *
		 * @return {HTMLElement} Tool card.
		 */
		buildUnitConverter: function () {
			var self = this;
			var state = { width: 1080, height: 1350, unit: 'px', dpi: this.state.dpi };
			var output = h( 'ul', { class: 'sg-unit-rows' } );

			/**
			 * Recalculate the output rows.
			 */
			function update() {
				var pseudo = {
					width: state.width,
					height: state.height,
					unit: state.unit,
					dpi: state.dpi
				};

				output.textContent = '';

				Convert.allUnits( pseudo, state.dpi ).forEach( function ( row ) {
					output.appendChild( h( 'li', { class: 'sg-unit-row' }, [
						h( 'span', { class: 'sg-unit-row__unit', text: row.unit.toUpperCase() } ),
						h( 'span', { class: 'sg-unit-row__value', text: row.label } )
					] ) );
				} );

				output.appendChild( h( 'li', { class: 'sg-unit-row' }, [
					h( 'span', { class: 'sg-unit-row__unit', text: 'RATIO' } ),
					h( 'span', { class: 'sg-unit-row__value', text: Convert.ratio( state.width, state.height ) } )
				] ) );
			}

			var fields = h( 'div', { class: 'sg-fields' }, [
				this.field( this.strings.width || 'Width', this.numberInput( state.width, function ( event ) {
					state.width = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( this.strings.height || 'Height', this.numberInput( state.height, function ( event ) {
					state.height = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( this.strings.unit || 'Unit', this.unitSelect( state.unit, function ( event ) {
					state.unit = event.target.value;
					update();
				} ) ),
				this.field( this.strings.dpi || 'DPI', this.dpiSelect( state.dpi, function ( event ) {
					state.dpi = parseInt( event.target.value, 10 ) || 300;
					update();
				} ) )
			] );

			update();

			return h( 'section', { class: 'sg-tool' }, [
				h( 'h3', { class: 'sg-tool__title', text: this.strings.unitConverter || 'Unit converter' } ),
				fields,
				output
			] );
		},

		/**
		 * DPI calculator: physical size to pixels and back.
		 *
		 * @return {HTMLElement} Tool card.
		 */
		buildDpiCalculator: function () {
			var self = this;
			var state = { width: 210, height: 297, unit: 'mm', dpi: 300 };
			var result = h( 'p', { class: 'sg-tool__result' } );
			var formula = h( 'p', { class: 'sg-tool__note' } );

			/**
			 * Recalculate the pixel size.
			 */
			function update() {
				var pxWidth = Convert.toPixels( state.width, state.unit, state.dpi );
				var pxHeight = Convert.toPixels( state.height, state.unit, state.dpi );

				result.textContent = pxWidth + ' × ' + pxHeight + ' px';

				var inches = Convert.toInches( state.width, state.unit, state.dpi );
				formula.textContent = Convert.number( Math.round( inches * 100 ) / 100 ) + ' in × ' +
					state.dpi + ' DPI = ' + pxWidth + ' px';
			}

			var fields = h( 'div', { class: 'sg-fields' }, [
				this.field( this.strings.width || 'Width', this.numberInput( state.width, function ( event ) {
					state.width = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( this.strings.height || 'Height', this.numberInput( state.height, function ( event ) {
					state.height = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( this.strings.unit || 'Unit', this.unitSelect( state.unit, function ( event ) {
					state.unit = event.target.value;
					update();
				} ) ),
				this.field( this.strings.dpi || 'DPI', this.dpiSelect( state.dpi, function ( event ) {
					state.dpi = parseInt( event.target.value, 10 ) || 300;
					update();
				} ) )
			] );

			update();

			return h( 'section', { class: 'sg-tool' }, [
				h( 'h3', { class: 'sg-tool__title', text: this.strings.dpiCalculator || 'DPI calculator' } ),
				fields,
				result,
				formula
			] );
		},

		/**
		 * Aspect ratio calculator with a scale-to-width helper.
		 *
		 * @return {HTMLElement} Tool card.
		 */
		buildRatioCalculator: function () {
			var state = { width: 1920, height: 1080, target: 1080 };
			var result = h( 'p', { class: 'sg-tool__result' } );
			var scaled = h( 'p', { class: 'sg-tool__note' } );

			/**
			 * Recalculate the ratio and the scaled height.
			 */
			function update() {
				result.textContent = Convert.ratio( state.width, state.height ) +
					'  (' + ( Math.round( Convert.ratioValue( state.width, state.height ) * 1000 ) / 1000 ) + ':1)';

				var height = state.height && state.width
					? Math.round( state.target * ( state.height / state.width ) )
					: 0;

				scaled.textContent = state.target + ' × ' + height + ' px';
			}

			var fields = h( 'div', { class: 'sg-fields' }, [
				this.field( this.strings.width || 'Width', this.numberInput( state.width, function ( event ) {
					state.width = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( this.strings.height || 'Height', this.numberInput( state.height, function ( event ) {
					state.height = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( 'Scale to width', this.numberInput( state.target, function ( event ) {
					state.target = parseFloat( event.target.value ) || 0;
					update();
				} ) )
			] );

			update();

			return h( 'section', { class: 'sg-tool' }, [
				h( 'h3', { class: 'sg-tool__title', text: this.strings.ratioCalculator || 'Aspect ratio calculator' } ),
				fields,
				result,
				scaled
			] );
		},

		/**
		 * Custom canvas: build a one-off size and download a template for it.
		 *
		 * @return {HTMLElement} Tool card.
		 */
		buildCustomCanvas: function () {
			var self = this;
			var state = { width: 1080, height: 1350, unit: 'px', dpi: 72, bleed: 0, safe: 60 };
			var preview = h( 'div', { class: 'sg-infographic__canvas' } );

			/**
			 * Build a format record from the current inputs.
			 *
			 * @return {Object} Format-shaped object.
			 */
			function toFormat() {
				var safe = parseFloat( state.safe ) || 0;

				return {
					id: 'custom-' + Convert.number( state.width ) + 'x' + Convert.number( state.height ),
					name: 'Custom canvas',
					platform_name: 'Size Guide',
					category_name: 'Custom',
					width: parseFloat( state.width ) || 1,
					height: parseFloat( state.height ) || 1,
					unit: state.unit,
					dpi: parseInt( state.dpi, 10 ) || 72,
					bleed: parseFloat( state.bleed ) || 0,
					aspect_ratio: Convert.ratio( state.width, state.height ),
					orientation: Convert.orientation( state.width, state.height ),
					safe_zone: safe > 0 ? { top: safe, right: safe, bottom: safe, left: safe } : null,
					margin: null,
					padding: null,
					file_formats: [],
					recommended: null,
					minimum: null,
					maximum: null,
					source: { type: 'estimated', name: '', url: '', checked_date: '' },
					notes: '',
					max_file_size: '',
					last_updated: ''
				};
			}

			/**
			 * Redraw the preview.
			 */
			function update() {
				Infographic.render( preview, toFormat(), Object.assign( {}, self.state.toggles, {
					unit: state.unit,
					dpi: parseInt( state.dpi, 10 ) || 72
				} ), self.strings );
			}

			var fields = h( 'div', { class: 'sg-fields' }, [
				this.field( this.strings.width || 'Width', this.numberInput( state.width, function ( event ) {
					state.width = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( this.strings.height || 'Height', this.numberInput( state.height, function ( event ) {
					state.height = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( this.strings.unit || 'Unit', this.unitSelect( state.unit, function ( event ) {
					state.unit = event.target.value;
					update();
				} ) ),
				this.field( this.strings.dpi || 'DPI', this.dpiSelect( state.dpi, function ( event ) {
					state.dpi = parseInt( event.target.value, 10 ) || 72;
					update();
				} ) ),
				this.field( this.strings.bleed || 'Bleed', this.numberInput( state.bleed, function ( event ) {
					state.bleed = parseFloat( event.target.value ) || 0;
					update();
				} ) ),
				this.field( this.strings.safeZone || 'Safe zone', this.numberInput( state.safe, function ( event ) {
					state.safe = parseFloat( event.target.value ) || 0;
					update();
				} ) )
			] );

			var actions = h( 'div', { class: 'sg-downloads__row' }, [
				h( 'button', {
					type: 'button',
					class: 'sg-button',
					text: this.strings.downloadSvg || 'Clean SVG',
					onclick: function () {
						Templates.downloadSvg( toFormat(), 'clean', self.strings );
					}
				} ),
				h( 'button', {
					type: 'button',
					class: 'sg-button sg-button--primary',
					text: this.strings.downloadGuide || 'Guide SVG',
					onclick: function () {
						Templates.downloadSvg( toFormat(), 'guide', self.strings );
					}
				} ),
				h( 'button', {
					type: 'button',
					class: 'sg-button',
					text: this.strings.downloadPng || 'PNG',
					onclick: function () {
						Templates.downloadPng( toFormat(), 'guide', self.strings );
					}
				} )
			] );

			window.setTimeout( update, 0 );

			return h( 'section', { class: 'sg-tool sg-tool--wide' }, [
				h( 'h3', { class: 'sg-tool__title', text: 'Custom canvas' } ),
				fields,
				h( 'figure', { class: 'sg-infographic' }, [ preview ] ),
				actions
			] );
		}
	} );

	SizeGuide.App = App;
	SizeGuide.helpers = { h: h, copyText: copyText, DPI_PRESETS: DPI_PRESETS };

	/**
	 * Start every Size Guide on the page.
	 *
	 * @param {Object} data Localised data, with the dataset resolved.
	 */
	function start( data ) {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-sg-app]' ), function ( root ) {
			if ( root.getAttribute( 'data-sg-ready' ) ) {
				return;
			}

			root.setAttribute( 'data-sg-ready', '1' );
			root.classList.add( 'sg-app--enhanced' );

			/* eslint-disable no-new */
			new App( root, data );
			/* eslint-enable no-new */
		} );
	}

	/**
	 * Boot: use the inlined dataset, or fetch it when the site prefers REST.
	 *
	 * The static markup rendered by PHP stays visible until the data is ready,
	 * so the page is never blank and never breaks if the request fails.
	 */
	function boot() {
		var data = window.SizeGuideData;

		if ( ! data ) {
			return;
		}

		if ( data.dataset && data.dataset.sections ) {
			start( data );
			return;
		}

		if ( ! data.restUrl || ! window.fetch ) {
			return;
		}

		window.fetch( data.restUrl + '/dataset', { credentials: 'same-origin' } )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Size Guide: dataset request failed' );
				}
				return response.json();
			} )
			.then( function ( dataset ) {
				data.dataset = dataset;
				start( data );
			} )
			.catch( function () {
				// Leave the static markup in place; it already lists every size.
			} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}( window, document ) );
