<?php
/**
 * Markup and styling for the standalone demo page.
 *
 * Returns the page content. build-demo.php either wraps it in a document or
 * writes it as-is for publishing.
 *
 * @package SizeGuide
 *
 * @var string $sg_css        The plugin's frontend CSS.
 * @var string $sg_js         The plugin's frontend JavaScript.
 * @var string $sg_payload    The localised data payload as JSON.
 * @var string $sg_app_markup Output of the plugin's app template.
 * @var array  $sg_stats      Dataset totals.
 */

defined( 'ABSPATH' ) || exit;

$sg_template = <<<'HTMLTEMPLATE'
<title>Size Guide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap">

<style>
/* ===========================================================================
   Demo page chrome. The plugin's own stylesheet follows further down and is
   scoped to .sg-app, so the two never collide.
   ======================================================================== */

:root {
	--ground: #e9ebf0;
	--surface: #ffffff;
	--surface-2: #f2f4f8;
	--ink: #131720;
	--muted: #5c6472;
	--rule: #c9ced9;
	--rule-strong: #a6aebd;
	--blueprint: #2f5bd0;
	--live: #16794a;
	--sans: "IBM Plex Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
	--display: "Archivo", "IBM Plex Sans", ui-sans-serif, system-ui, sans-serif;
	--mono: "IBM Plex Mono", ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
}

@media ( prefers-color-scheme: dark ) {

	:root:not( [data-theme="light"] ) {
		--ground: #10131a;
		--surface: #171b24;
		--surface-2: #1d222d;
		--ink: #e6e9f0;
		--muted: #909aab;
		--rule: #2b3140;
		--rule-strong: #3f4759;
		--blueprint: #7d9df7;
		--live: #4ade80;
	}
}

:root[data-theme="dark"] {
	--ground: #10131a;
	--surface: #171b24;
	--surface-2: #1d222d;
	--ink: #e6e9f0;
	--muted: #909aab;
	--rule: #2b3140;
	--rule-strong: #3f4759;
	--blueprint: #7d9df7;
	--live: #4ade80;
}

*,
*::before,
*::after {
	box-sizing: border-box;
}

body {
	background: var( --ground );
	color: var( --ink );
	font-family: var( --sans );
	font-size: 15px;
	line-height: 1.6;
	margin: 0;
	-webkit-font-smoothing: antialiased;
}

.sheet {
	margin-inline: auto;
	max-width: 1200px;
	padding: 0 20px 80px;
}

a {
	color: var( --blueprint );
	text-underline-offset: 3px;
}

:where( a, button, summary ):focus-visible {
	outline: 2px solid var( --blueprint );
	outline-offset: 3px;
}

/* Masthead ---------------------------------------------------------------- */

.masthead {
	align-items: end;
	display: flex;
	flex-wrap: wrap;
	gap: 20px 48px;
	justify-content: space-between;
	padding-top: 52px;
}

.eyebrow {
	color: var( --muted );
	font-family: var( --mono );
	font-size: 0.7rem;
	letter-spacing: 0.14em;
	margin: 0;
	text-transform: uppercase;
}

h1 {
	font-family: var( --display );
	font-size: clamp( 2.1rem, 1.4rem + 2.6vw, 3.1rem );
	font-weight: 700;
	letter-spacing: -0.025em;
	line-height: 1.02;
	margin: 0.18em 0 0;
	text-wrap: balance;
}

.thesis {
	color: var( --muted );
	margin: 0;
	max-width: 46ch;
	text-wrap: pretty;
}

/* A ruler edge, because the subject is measurement --------------------------
   Short ticks every 8px, long ticks every 40px. */

.ruler {
	background-image:
		repeating-linear-gradient( to right, var( --rule-strong ) 0 1px, transparent 1px 40px ),
		repeating-linear-gradient( to right, var( --rule ) 0 1px, transparent 1px 8px );
	background-position: bottom left;
	background-repeat: repeat-x;
	background-size: 100% 13px, 100% 6px;
	border-bottom: 1px solid var( --rule-strong );
	height: 13px;
	margin-top: 34px;
}

/* Facts ------------------------------------------------------------------- */

.facts {
	background: var( --rule );
	display: grid;
	gap: 1px;
	grid-template-columns: repeat( auto-fit, minmax( 148px, 1fr ) );
	margin: 0 0 44px;
}

.fact {
	background: var( --ground );
	padding: 16px 4px 18px 0;
}

.fact__n {
	display: block;
	font-family: var( --display );
	font-size: 1.7rem;
	font-variant-numeric: tabular-nums;
	line-height: 1;
}

.fact__l {
	color: var( --muted );
	display: block;
	font-family: var( --mono );
	font-size: 0.66rem;
	letter-spacing: 0.12em;
	margin-top: 7px;
	text-transform: uppercase;
}

/* The stage ---------------------------------------------------------------- */

.stage {
	margin-bottom: 60px;
}

.stage__bar {
	align-items: baseline;
	color: var( --muted );
	display: flex;
	flex-wrap: wrap;
	font-family: var( --mono );
	font-size: 0.68rem;
	gap: 8px 20px;
	justify-content: space-between;
	letter-spacing: 0.12em;
	padding-bottom: 11px;
	text-transform: uppercase;
}

.live {
	align-items: center;
	color: var( --ink );
	display: inline-flex;
	gap: 8px;
}

.live::before {
	background: var( --live );
	border-radius: 50%;
	content: "";
	height: 7px;
	width: 7px;
}

/* The plugin renders light, the way it does on a light WordPress theme, so the
   artboard keeps its own surface in both page themes. */

.artboard {
	background: #ffffff;
	border: 1px solid var( --rule-strong );
	color-scheme: light;
	padding: clamp( 16px, 2.4vw, 32px );
	position: relative;
}

.tick {
	height: 14px;
	pointer-events: none;
	position: absolute;
	width: 14px;
}

.tick--tl {
	border-left: 1px solid var( --rule-strong );
	border-top: 1px solid var( --rule-strong );
	left: -1px;
	top: -1px;
	transform: translate( -9px, -9px );
}

.tick--tr {
	border-right: 1px solid var( --rule-strong );
	border-top: 1px solid var( --rule-strong );
	right: -1px;
	top: -1px;
	transform: translate( 9px, -9px );
}

.tick--bl {
	border-bottom: 1px solid var( --rule-strong );
	border-left: 1px solid var( --rule-strong );
	bottom: -1px;
	left: -1px;
	transform: translate( -9px, 9px );
}

.tick--br {
	border-bottom: 1px solid var( --rule-strong );
	border-right: 1px solid var( --rule-strong );
	bottom: -1px;
	right: -1px;
	transform: translate( 9px, 9px );
}

/* Notes -------------------------------------------------------------------- */

.notes {
	display: grid;
	gap: 34px 40px;
	grid-template-columns: repeat( auto-fit, minmax( 270px, 1fr ) );
}

.note {
	border-top: 1px solid var( --rule-strong );
	padding-top: 14px;
}

.note h2 {
	color: var( --muted );
	font-family: var( --mono );
	font-size: 0.68rem;
	font-weight: 500;
	letter-spacing: 0.12em;
	margin: 0 0 12px;
	text-transform: uppercase;
}

.note p {
	margin: 0 0 11px;
	max-width: 60ch;
}

.note p:last-child {
	margin-bottom: 0;
}

.steps {
	counter-reset: step;
	list-style: none;
	margin: 0;
	padding: 0;
}

.steps li {
	counter-increment: step;
	margin-bottom: 11px;
	padding-left: 30px;
	position: relative;
}

.steps li::before {
	color: var( --blueprint );
	content: counter( step, decimal-leading-zero );
	font-family: var( --mono );
	font-size: 0.72rem;
	left: 0;
	position: absolute;
	top: 0.15em;
}

.steps li:last-child {
	margin-bottom: 0;
}

kbd {
	background: var( --surface-2 );
	border: 1px solid var( --rule );
	font-family: var( --mono );
	font-size: 0.82em;
	padding: 1px 6px;
	white-space: nowrap;
}

.stack {
	list-style: none;
	margin: 0;
	padding: 0;
}

.stack li {
	border-bottom: 1px dotted var( --rule );
	display: flex;
	gap: 16px;
	justify-content: space-between;
	padding: 7px 0;
}

.stack li:last-child {
	border-bottom: 0;
}

.stack span:last-child {
	color: var( --muted );
	font-family: var( --mono );
	font-size: 0.76rem;
	font-variant-numeric: tabular-nums;
	text-align: right;
	white-space: nowrap;
}

pre {
	background: var( --surface-2 );
	border: 1px solid var( --rule );
	font-family: var( --mono );
	font-size: 0.78rem;
	line-height: 1.75;
	margin: 0;
	overflow-x: auto;
	padding: 12px 14px;
}

.caveat {
	border-top-color: var( --blueprint );
	border-top-width: 2px;
}

footer {
	border-top: 1px solid var( --rule );
	color: var( --muted );
	display: flex;
	flex-wrap: wrap;
	font-family: var( --mono );
	font-size: 0.68rem;
	gap: 8px 24px;
	justify-content: space-between;
	letter-spacing: 0.08em;
	margin-top: 56px;
	padding-top: 16px;
	text-transform: uppercase;
}

/* Download preview --------------------------------------------------------- */

.demo-dlg {
	background: var( --surface );
	border: 1px solid var( --rule-strong );
	color: var( --ink );
	max-width: min( 720px, 92vw );
	padding: 0;
	width: 100%;
}

.demo-dlg::backdrop {
	background: rgba( 8, 11, 18, 0.62 );
}

.demo-dlg__head {
	align-items: center;
	border-bottom: 1px solid var( --rule );
	display: flex;
	gap: 16px;
	justify-content: space-between;
	padding: 12px 16px;
}

.demo-dlg__name {
	font-family: var( --mono );
	font-size: 0.78rem;
	overflow-wrap: anywhere;
}

.demo-dlg__close {
	background: none;
	border: 1px solid var( --rule );
	color: inherit;
	cursor: pointer;
	font: inherit;
	font-size: 0.8rem;
	padding: 4px 12px;
}

.demo-dlg__close:hover {
	border-color: var( --blueprint );
	color: var( --blueprint );
}

.demo-dlg__body {
	padding: 16px;
}

.demo-dlg__note {
	color: var( --muted );
	font-size: 0.85rem;
	margin: 0 0 14px;
}

.demo-preview {
	background:
		linear-gradient( 45deg, #eceef1 25%, transparent 25% 75%, #eceef1 75% ) 0 0 / 16px 16px,
		linear-gradient( 45deg, #eceef1 25%, transparent 25% 75%, #eceef1 75% ) 8px 8px / 16px 16px,
		#ffffff;
	border: 1px solid var( --rule );
	display: flex;
	justify-content: center;
	padding: 14px;
}

.demo-preview svg,
.demo-preview img {
	display: block;
	height: auto;
	max-height: 56vh;
	width: auto;
	max-width: 100%;
}

@media ( max-width: 640px ) {

	.masthead {
		padding-top: 36px;
	}

	.artboard {
		padding: 14px;
	}
}
HTMLTEMPLATE;

$sg_template .= "\n/* =========================================================================\n"
	. "   The plugin's own stylesheet, inlined unchanged from assets/css/.\n"
	. "   ====================================================================== */\n"
	. $sg_css
	. "\n</style>\n";

$sg_body = <<<'HTMLBODY'

<div class="sheet">

	<header class="masthead">
		<div>
			<p class="eyebrow">WordPress plugin &middot; v1.0.0 &middot; GPL-2.0-or-later</p>
			<h1>Size Guide</h1>
		</div>
		<p class="thesis">
			Every design size, with the safe zone, margin and bleed that go with it &mdash;
			and a template you can open in your editor.
		</p>
	</header>

	<div class="ruler" aria-hidden="true"></div>

	<dl class="facts">
		<div class="fact">
			<dd class="fact__n">{{FORMATS}}</dd>
			<dt class="fact__l">Size records</dt>
		</div>
		<div class="fact">
			<dd class="fact__n">{{PLATFORMS}}</dd>
			<dt class="fact__l">Platforms &amp; groups</dt>
		</div>
		<div class="fact">
			<dd class="fact__n">{{FILES}}</dd>
			<dt class="fact__l">JSON datasets</dt>
		</div>
		<div class="fact">
			<dd class="fact__n">0</dd>
			<dt class="fact__l">Dependencies</dt>
		</div>
	</dl>

	<section class="stage">
		<div class="stage__bar">
			<span class="live">Live &mdash; the plugin's own frontend</span>
			<span>Unmodified CSS, JS and dataset</span>
		</div>

		<div class="artboard">
			<span class="tick tick--tl" aria-hidden="true"></span>
			<span class="tick tick--tr" aria-hidden="true"></span>
			<span class="tick tick--bl" aria-hidden="true"></span>
			<span class="tick tick--br" aria-hidden="true"></span>
{{APP}}
		</div>
	</section>

	<section class="notes">

		<div class="note">
			<h2>Try this</h2>
			<ol class="steps">
				<li>Search <kbd>yt thumbnail</kbd> &mdash; shorthand resolves to the platform.</li>
				<li>Search <kbd>1080x1350</kbd> &mdash; dimensions match too.</li>
				<li>Open <kbd>Print</kbd> &rarr; <kbd>Paper Sizes</kbd> &rarr; <kbd>A4</kbd>, then switch the unit to <kbd>IN</kbd>.</li>
				<li>Toggle <kbd>Bleed</kbd> and <kbd>Safe zone</kbd> under the diagram.</li>
				<li>Press <kbd>Guide SVG</kbd> &mdash; the generated file opens here in a preview.</li>
				<li>Open <kbd>Tools</kbd> for the DPI and ratio calculators.</li>
			</ol>
		</div>

		<div class="note">
			<h2>What's inside</h2>
			<ul class="stack">
				<li><span>Social media</span> <span>14 platforms</span></li>
				<li><span>Web, ads, video</span> <span>3 groups</span></li>
				<li><span>ISO A / B / C series</span> <span>33 sizes</span></li>
				<li><span>US &amp; ANSI paper</span> <span>8 sizes</span></li>
				<li><span>Print trade formats</span> <span>10 groups</span></li>
			</ul>
			<p style="margin-top:12px">
				Every record carries width, height, unit, ratio, orientation, safe zone,
				margin, bleed, DPI, file formats, notes, a source type and a date.
			</p>
		</div>

		<div class="note">
			<h2>How it's built</h2>
			<p>
				Data is separated from presentation. Specifications live in JSON, one loader
				normalises them, and that feeds the navigation, search index, infographic and
				template generator. Nothing about a platform is hard-coded in the frontend.
			</p>
			<p>
				Vanilla JavaScript across five modules &mdash; no React, Vue, jQuery or CSS
				framework. Templates generate in the browser; nothing is sent anywhere.
			</p>
		</div>

		<div class="note">
			<h2>Using it</h2>
			<pre>[size_guide]
[size_guide section="print"]
[size_guide platform="instagram"]
[size_guide format="instagram-post-portrait"]
[size_guide search="a4"]
[size_guide title="Our brand sizes"]
[size_guide scheme="dark"]</pre>
			<p style="margin-top:11px">
				Assets load only on pages that contain the shortcode. Sizes are also readable
				over REST at <code>/wp-json/size-guide/v1/dataset</code>.
			</p>
		</div>

		<div class="note caveat">
			<h2>About the data</h2>
			<p>
				Paper sizes follow ISO&nbsp;216 and the US/ANSI standards and are exact. They
				are marked <strong>official</strong>.
			</p>
			<p>
				Social, web, ad and video sizes are marked <strong>common practice</strong>:
				they reflect widely used specifications, but platforms change without notice
				and these values have not been re-verified against each platform's live
				documentation. Check the formats that matter to you before relying on them.
			</p>
			<p>
				Every record shows its source type and last-updated date, and the admin's
				JSON import lets you correct any of them without touching code.
			</p>
		</div>

		<div class="note">
			<h2>Appearance</h2>
			<p>
				Settings cover the accent colour, a light / dark / follow-the-system colour
				scheme, rounded or square corners, and comfortable or compact density. A single
				page can override the scheme with <code>scheme="dark"</code>.
			</p>
			<p>
				The diagram reads its guide colours from CSS, so it follows the scheme too &mdash;
				except the artboard, which stays white because it stands for the design surface.
				Downloaded templates always keep the standard guide colours.
			</p>
		</div>

		<div class="note">
			<h2>In this preview</h2>
			<p>
				This page embeds the plugin's real frontend &mdash; same CSS, same JavaScript,
				same dataset &mdash; so everything behaves as it does in WordPress.
			</p>
			<p>
				The template buttons generate a real file. A framed page can't write to disk on
				its own, so the save goes through a confirmation prompt here; in WordPress the
				click saves straight away. Print opens a sized print window there, which pop-up
				blocking prevents in a preview.
			</p>
		</div>

	</section>

	<footer>
		<span>Size Guide 1.0.0</span>
		<span>{{FORMATS}} sizes &middot; no external services &middot; no personal data</span>
	</footer>
</div>

<dialog class="demo-dlg" id="demo-dlg" aria-labelledby="demo-dlg-name">
	<div class="demo-dlg__head">
		<span class="demo-dlg__name" id="demo-dlg-name">Template</span>
		<button type="button" class="demo-dlg__close" id="demo-dlg-close">Close</button>
	</div>
	<div class="demo-dlg__body" id="demo-dlg-body"></div>
</dialog>

HTMLBODY;

$sg_scripts = "\n<script>\nwindow.SizeGuideData = " . $sg_payload . ";\n</script>\n"
	. "<script>\n/* The plugin's own JavaScript, inlined unchanged from assets/js/. */\n"
	. $sg_js . "\n</script>\n";

$sg_scripts .= <<<'HTMLSHIM'
<script>
/**
 * Demo adaptation for the Artifact viewer.
 *
 * A framed page cannot save a file itself, so the plugin's final hand-off to
 * the browser is routed through the viewer's downloads capability, which asks
 * the reader to confirm. Everything up to that point — the SVG and the canvas
 * rasterisation — is the plugin's own untouched code.
 *
 * Served anywhere else (the standalone demo, a normal web server) none of this
 * runs and the plugin downloads the file directly, exactly as it does in
 * WordPress.
 */
( function () {
	'use strict';

	var Templates = window.SizeGuide && window.SizeGuide.Templates;
	var dialog = document.getElementById( 'demo-dlg' );

	if ( ! Templates || ! dialog || ! dialog.showModal ) {
		return;
	}

	if ( ! window.claude || typeof window.claude.use !== 'function' ) {
		return;
	}

	var nameEl = document.getElementById( 'demo-dlg-name' );
	var bodyEl = document.getElementById( 'demo-dlg-body' );

	// Resolves later, and null whenever this view cannot save.
	var ready = window.claude.use( 'downloads' ).catch( function () {
		return null;
	} );

	/**
	 * Open the fallback panel.
	 *
	 * @param {string}      title   Heading.
	 * @param {string}      note    Explanation.
	 * @param {HTMLElement} content Optional preview.
	 */
	function panel( title, note, content ) {
		nameEl.textContent = title;
		bodyEl.textContent = '';

		var paragraph = document.createElement( 'p' );
		paragraph.className = 'demo-dlg__note';
		paragraph.textContent = note;
		bodyEl.appendChild( paragraph );

		if ( content ) {
			bodyEl.appendChild( content );
		}

		dialog.showModal();
	}

	/**
	 * Show the generated file when it cannot be saved.
	 *
	 * @param {Blob}   blob     Generated file.
	 * @param {string} filename Name the save would have used.
	 * @param {string} note     Explanation.
	 */
	function preview( blob, filename, note ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'demo-preview';

		if ( blob.type.indexOf( 'svg' ) > -1 && blob.text ) {
			blob.text().then( function ( source ) {
				wrap.innerHTML = source.replace( /^<\?xml[^>]*\?>\s*/, '' );
				panel( filename, note, wrap );
			} );
			return;
		}

		var image = document.createElement( 'img' );
		image.alt = filename;
		image.src = window.URL.createObjectURL( blob );
		wrap.appendChild( image );

		panel( filename, note, wrap );
	}

	Templates.download = function ( blob, filename ) {
		ready.then( function ( downloads ) {
			if ( ! downloads ) {
				preview( blob, filename, 'Generated in your browser. Saving is not available in this view, so here is the file.' );
				return;
			}

			downloads.save( { filename: filename, data: blob } ).catch( function ( error ) {
				var code = error && error.code;

				if ( 'declined' === code ) {
					return;
				}

				if ( 'rate_limited' === code ) {
					panel( filename, 'A save prompt is already open — finish that one first.', null );
					return;
				}

				preview( blob, filename, 'Generated in your browser, but this view could not save it.' );
			} );
		} );
	};

	Templates.print = function ( format ) {
		panel(
			'Print / PDF',
			'In WordPress this opens a print window with the page size set to the document\u2019s real ' +
				'dimensions \u2014 ' + format.width + ' \u00d7 ' + format.height + ' ' + format.unit +
				( format.bleed ? ' plus ' + format.bleed + ' ' + format.unit + ' bleed' : '' ) +
				' \u2014 so \u201cSave as PDF\u201d produces a correctly sized file. Pop-up windows are blocked in this preview.',
			null
		);
	};

	document.getElementById( 'demo-dlg-close' ).addEventListener( 'click', function () {
		dialog.close();
	} );

	dialog.addEventListener( 'click', function ( event ) {
		if ( event.target === dialog ) {
			dialog.close();
		}
	} );
}() );
</script>
HTMLSHIM;

$sg_page = $sg_template . $sg_body . $sg_scripts;

return strtr(
	$sg_page,
	array(
		'{{APP}}'       => $sg_app_markup,
		'{{FORMATS}}'   => number_format( $sg_stats['formats'] ),
		'{{PLATFORMS}}' => number_format( $sg_stats['platforms'] ),
		'{{FILES}}'     => number_format( $sg_stats['files'] ),
	)
);
