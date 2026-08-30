# Size Guide

> **Every design. Every size. One place.**

A free WordPress plugin that answers the question a designer asks before starting any file:
*what size do I make this, how much safe area do I leave, what is the margin and bleed, and where do I get a ready template?*

Size Guide covers social media, web, digital ads, video and print — with safe zones, margins, bleed, DPI,
unit conversion, copy-to-clipboard dimensions and downloadable SVG/PNG templates. No external service, no
API key, no account.

- **Version:** 1.0.0
- **Requires:** WordPress 5.8+, PHP 7.4+
- **License:** GPL-2.0-or-later
- **Dependencies:** none (no React, Vue, jQuery or CSS framework)

---

## What you get

**255 size records** across 28 platforms and format groups:

| Section | Groups | Examples |
| --- | --- | --- |
| Digital | Social Media (14 platforms) | Instagram, Facebook, LinkedIn, X, YouTube, TikTok, Pinterest, Threads, Reddit, Discord, Snapchat, WhatsApp, Telegram, Twitch |
| Digital | Web | layout widths, hero images, Open Graph, favicons, app icons, email |
| Digital | Digital Ads | IAB desktop, mobile and responsive display units |
| Digital | Video | 8K to 720p, vertical, square, cinematic, lower thirds, subtitle safe areas |
| Print | Paper Sizes | ISO A0–A10, B0–B10, C0–C10, US Letter/Legal/Tabloid, ANSI C–E |
| Print | Products | business cards, flyers, brochures, posters, envelopes, stationery, books, magazines, banners, stickers, labels, menus, presentations |

Every record carries width, height, unit, aspect ratio, orientation, safe zone, margin, bleed, DPI,
file formats, maximum file size, notes, a source type and a last-updated date.

---

## Installation

1. Copy the `size-guide` folder into `wp-content/plugins/`.
2. Activate **Size Guide** in *Plugins*.
3. Put `[size_guide]` on any page.

## Shortcodes

```
[size_guide]                                  The full guide
[size_guide section="print"]                  Open on a section: digital or print
[size_guide category="social"]                Open on a group (social, web, digital-ads, video, print-sizes)
[size_guide platform="instagram"]             Open on one platform
[size_guide format="instagram-post-portrait"] Open one size straight away
[size_guide search="a4"]                      Open with a search pre-filled
[size_guide title="Our brand sizes"]          Change the heading
```

Multiple guides can appear on one page; the first one owns the URL hash.

---

## How it is built

The one rule the plugin is organised around: **separate data from presentation.**

```
data/*.json  →  Data_Loader  →  normalised dataset  →  PHP templates (static)
                                                    →  JavaScript UI (interactive)
```

Nothing about a platform is hard-coded in the frontend. Add a record to a JSON file — or import one
through the admin — and it appears in the navigation, the search index, the infographic and the
template generator with no code changes.

```
size-guide/
├── size-guide.php              Bootstrap, constants, activation
├── uninstall.php               Removes options and transients (multisite aware)
├── data/                       The dataset
│   ├── social-media.json
│   ├── web-sizes.json
│   ├── digital-ads.json
│   ├── video-sizes.json
│   └── print-sizes.json
├── includes/
│   ├── class-size-guide.php         Bootstrap, asset registration, i18n strings
│   ├── class-data-loader.php        Load, merge, normalise, index, search, convert
│   ├── class-shortcode.php          [size_guide]
│   ├── class-rest-api.php           Read-only REST endpoints
│   ├── class-admin.php              Menu, settings, import/export
│   └── class-template-generator.php Server-side SVG templates
├── templates/                  Frontend markup (themes can override via size-guide/app.php)
├── admin/                      Admin screens
└── assets/
    ├── css/  frontend.css, infographic.css, admin.css
    └── js/   converter.js, search.js, infographic.js, template-generator.js, frontend.js
```

### The JavaScript modules

| File | Responsibility |
| --- | --- |
| `converter.js` | Unit and DPI maths, aspect ratios. Everything else converts through it. |
| `search.js` | Token search with shorthand expansion and dimension matching. |
| `infographic.js` | Builds the scaled SVG diagram: bleed, trim, margin, safe zone, grid, measurements. |
| `template-generator.js` | SVG source, PNG rasterisation via canvas, print/PDF window, Blob downloads. |
| `frontend.js` | State, routing, navigation, platform grid and detail view. |

---

## Data format

A dataset file is one **group**: a section, a name and a list of platforms.

```json
{
  "schema_version": 1,
  "id": "social-media",
  "section": "digital",
  "name": "Social Media",
  "order": 10,
  "defaults": { "unit": "px", "dpi": 72, "file_formats": ["JPG", "PNG"] },
  "platforms": [
    {
      "id": "instagram",
      "name": "Instagram",
      "aliases": ["ig", "insta"],
      "color": "#e1306c",
      "categories": [
        {
          "id": "posts",
          "name": "Posts",
          "defaults": { "max_file_size": "30 MB" },
          "formats": [
            {
              "id": "instagram-post-portrait",
              "name": "Portrait Post",
              "aliases": ["4:5 post"],
              "keywords": ["feed", "vertical"],
              "width": 1080,
              "height": 1350,
              "aspect_ratio": "4:5",
              "safe_zone": 60,
              "notes": "The tallest ratio the feed accepts.",
              "source": { "type": "common-practice", "checked_date": "2026-08-29" },
              "last_updated": "2026-08"
            }
          ]
        }
      ]
    }
  ]
}
```

### Field reference

| Field | Notes |
| --- | --- |
| `width`, `height` | Required. Numbers in `unit`. |
| `unit` | `px`, `mm`, `cm` or `in`. Defaults to `px`. |
| `aspect_ratio` | Optional — derived from the dimensions when omitted. |
| `safe_zone`, `margin`, `padding` | A single number for all four sides, or `{top,right,bottom,left}`. |
| `bleed` | A single number, in the record's unit. |
| `dpi` | Defaults to 72 for pixel records and 300 for physical ones. |
| `minimum`, `maximum`, `recommended` | `{width,height}` pairs. |
| `file_formats`, `aliases`, `keywords` | Arrays, or a comma-separated string. |
| `source` | `{type, name, url, checked_date}`. Type is `official`, `recommended`, `common-practice` or `estimated`. |
| `orientation` | Derived when omitted. |

**`defaults` inheritance:** a `defaults` object on a group, platform or category is merged into every
format beneath it, nearest wins. This keeps the files short — set the source once per group rather than
once per record.

---

## Extending

### Import your own sizes

*Size Guide → Import / Export* accepts one group, a list of groups, or a full export. Imported data is
stored as an option and layered on top of the bundled JSON, so plugin updates never overwrite it.

### Add a dataset file from another plugin or theme

```php
add_filter( 'size_guide_data_files', function ( $files ) {
	$files[] = get_stylesheet_directory() . '/size-guide/brand-sizes.json';
	return $files;
} );
```

### Appearance

*Size Guide → Settings → Appearance* covers the accent colour, the colour scheme (light, dark, or
follow the visitor's system), rounded or square corners, and comfortable or compact density. A single
page can override the scheme with `[size_guide scheme="dark"]`.

The infographic reads its guide colours from CSS custom properties (`--sg-guide-bleed`, `--sg-guide-safe`,
`--sg-guide-measure` and friends), so a theme can restyle the diagram without touching JavaScript. The
artboard stays white in every scheme because it represents the design surface rather than the page, and
downloaded templates always carry the standard guide colours.

### Add search shorthand

```php
add_filter( 'size_guide_search_abbreviations', function ( $map ) {
	$map['ml'] = 'mailchimp';
	return $map;
} );
```

### Override the markup

Copy `templates/app.php` into your theme as `size-guide/app.php`.

### REST API

| Route | Returns |
| --- | --- |
| `GET /wp-json/size-guide/v1/dataset` | The whole normalised dataset (cached for an hour) |
| `GET /wp-json/size-guide/v1/search?q=ig+post` | Ranked search results |
| `GET /wp-json/size-guide/v1/format/<id>` | One size record |

### Direct template links

```
/?sg_template=print-a4&sg_type=guide
/?sg_template=print-a4&sg_type=clean
```

Server-generated SVG, so template links work without JavaScript.

---

## Templates

Two kinds, generated in the browser as SVG and downloaded via a Blob — no service is contacted:

- **Clean** — just the artboard at the right size, with bleed added where the record has it.
- **Guide** — artboard plus bleed edge, trim line, margin, safe zone, centre lines and a caption.

PNG is rasterised from the same SVG through a canvas at the record's DPI (capped at 8000 px on the long
edge so large print sizes stay within canvas limits). **Print / PDF** opens a print window with `@page`
sized to the document, which is how you get a PDF without shipping a PDF library.

---

## Performance

- Assets are registered on `wp_enqueue_scripts` but only enqueued when a shortcode actually renders, so
  pages without a guide load nothing.
- The normalised dataset is cached in a transient for a day and memoised per request.
- Search, filtering, conversion and template generation all run in the browser — no admin-ajax, no
  database queries for specifications.
- The dataset is inlined by default (about 27 KB gzipped) so the guide renders on first paint. Sites that
  would rather keep the HTML small can switch to **Fetch the dataset over REST** in the settings, which
  lets the browser cache it across pages.

---

## Accessibility

- Semantic landmarks, headings and `<dl>` specification lists.
- The search field is a proper combobox: arrow keys move through results, Enter opens, Escape closes.
- Visible focus rings on every control, `aria-current` on the active tab and platform.
- A `role="status"` live region announces result counts and copy confirmations.
- The infographic carries an `aria-label` describing the size, and a written legend so colour is never
  the only signal.
- Full specifications render as plain HTML without JavaScript, and `prefers-reduced-motion` is respected.

---

## Security

- Every admin action checks `manage_options` and a nonce.
- Settings go through an allow-list sanitiser; imports are validated as JSON and reshaped before storage.
- Output is escaped at the point of use; the inlined SVG is filtered through `wp_kses` with an explicit
  tag and attribute list.
- The plugin makes no outbound requests and collects no personal data.

---

## A note on the data

Paper sizes are standards: the ISO 216 A, B and C series and the US/ANSI sizes are exact, and are marked
`official`.

Social, web, ad and video specifications are marked `common-practice`. They reflect the sizes in
widespread use at the time of writing, but platforms change their specifications without notice, and the
bundled values have not been re-verified against each platform's live documentation. Before publishing a
site that people will rely on, check the formats that matter to you and update the record's `source.type`
to `official` with the URL you checked and the date. The dataset is built for exactly this: the
Import / Export screen and the `source` / `last_updated` fields exist so corrections do not need a code
change.

---

## Roadmap

**1.1** — more platforms and print formats, size comparison, favourites, recently viewed.
**1.2** — a Tools section: standalone unit converter, DPI and aspect-ratio calculators, and a custom
canvas generator. (Per-size unit switching and DPI conversion are already in the detail view.)
**2.0** — full admin dataset editor, visual template editor, user-created templates, more export formats.
