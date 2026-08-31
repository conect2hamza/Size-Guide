=== Size Guide ===
Contributors: sizeguide
Tags: design, image sizes, social media, print, templates
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Design size reference for social media, web, ads, video and print — with safe zones, margins, bleed, DPI and downloadable SVG templates.

== Description ==

Size Guide answers the question a designer asks before starting any file: what size do I make this, how
much safe area do I leave, what is the margin and bleed, and where do I get a ready template?

It ships with 255 size records across 28 platforms and format groups, and shows each one as a scaled
visual diagram rather than two numbers.

**Included**

* Social media — Instagram, Facebook, LinkedIn, X, YouTube, TikTok, Pinterest, Threads, Reddit, Discord, Snapchat, WhatsApp, Telegram, Twitch
* Web — layout widths, hero images, Open Graph, favicons, app icons, email
* Digital ads — IAB desktop, mobile and responsive display units
* Video — 8K to 720p, vertical, square, cinematic, lower thirds, subtitle safe areas
* Print — ISO A, B and C series, US and ANSI sizes, business cards, flyers, brochures, posters, envelopes, stationery, books, magazines, banners, stickers, labels, menus, presentations

**Features**

* Visual infographic with togglable bleed, trim, margin, safe zone, grid, measurements and labels
* Instant search that understands shorthand ("ig post", "yt thumbnail") and dimensions ("1080x1350")
* Unit switching between px, mm, cm and inches, with DPI conversion
* Copy dimensions to the clipboard in three notations
* Download clean and guide templates as SVG or PNG, or print to PDF
* Responsive, keyboard accessible, and readable without JavaScript
* A full appearance editor: every colour, typeface, radius and spacing value, with a live preview and five presets

**No external services.** Everything is generated from local data in the browser. No API key, no account,
no tracking, no personal data.

== Installation ==

1. Upload the `size-guide` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu.
3. Add `[size_guide]` to any page or post.

== Frequently Asked Questions ==

= How do I show only one platform? =

Use `[size_guide platform="instagram"]`. You can also pass `section`, `category`, `format`, `search`,
`scheme` and `title`.

= Can I add my own sizes? =

Yes. Size Guide → Import / Export accepts JSON, and imported data is layered on top of the bundled
dataset so plugin updates never overwrite it. You can also register extra dataset files with the
`size_guide_data_files` filter.

= Are the sizes guaranteed to be current? =

Paper sizes follow ISO 216 and the US/ANSI standards and are exact. Social, web, ad and video sizes are
marked "common practice": they reflect widely used specifications, but platforms change without notice
and the bundled values have not been re-verified against each platform's live documentation. Every record
shows its source type and last-updated date, and you can correct any of them through the import screen.

= How much can I restyle it? =

All of it. Size Guide → Appearance exposes 35 design tokens — separate light and dark palettes,
diagram colours, typeface, base size, heading weight, radius, border width, card and sidebar widths,
density, shadow and motion — with a live preview of the real front-end. Five presets give you a
starting point, and a theme can override any token in CSS.

= Does it slow down my site? =

Assets load only on pages that actually contain the shortcode. Search, conversion and template generation
all run in the browser, and the dataset is cached. If you would rather keep the page HTML small, the
settings offer an option to fetch the dataset over REST instead of inlining it.

= How do I get a PDF template? =

Use the Print / PDF button and choose "Save as PDF" in the print dialog. The page size is set to the
document's real dimensions, bleed included.

== Screenshots ==

1. A social media size with its infographic, specifications and templates.
2. A print size showing bleed, trim and safe zone with conversions in every unit.
3. The admin dashboard and dataset overview.
4. The settings screen, including the appearance options.

== Changelog ==

= 1.0.0 =
* First release: digital and print datasets, search, infographic system, safe zone, margin, bleed, DPI,
  unit conversion, copy dimensions, SVG and PNG templates, appearance settings, responsive UI,
  shortcode, REST API and WordPress admin with JSON import/export.

== Upgrade Notice ==

= 1.0.0 =
First release.
