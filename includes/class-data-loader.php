<?php
/**
 * Dataset loading, normalisation and search indexing.
 *
 * The frontend never hard-codes platform information: every specification is
 * read from the JSON files in /data (plus any imported custom dataset) and
 * normalised into one predictable shape.
 *
 * @package SizeGuide
 */

namespace SizeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Loads, merges and normalises the size dataset.
 */
class Data_Loader {

	const CACHE_KEY     = 'size_guide_dataset_v1';
	const CUSTOM_OPTION = 'size_guide_custom_dataset';
	const SCHEMA        = 1;

	/**
	 * Runtime memo so a single request parses the data once.
	 *
	 * @var array|null
	 */
	protected static $memo = null;

	/**
	 * Full normalised dataset.
	 *
	 * @return array {
	 *     @type int   $schema_version Dataset schema version.
	 *     @type array $sections       List of sections, each with groups.
	 *     @type array $index          Flat searchable list of every format.
	 * }
	 */
	public static function get_dataset() {
		if ( null !== self::$memo ) {
			return self::$memo;
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && isset( $cached['schema_version'] ) && self::SCHEMA === $cached['schema_version'] ) {
			self::$memo = $cached;
			return self::$memo;
		}

		$groups = array();

		foreach ( self::data_files() as $file ) {
			$group = self::read_json_file( $file );
			if ( $group ) {
				$groups[] = $group;
			}
		}

		foreach ( self::custom_groups() as $group ) {
			$groups[] = $group;
		}

		$dataset = self::normalise( $groups );

		set_transient( self::CACHE_KEY, $dataset, DAY_IN_SECONDS );
		self::$memo = $dataset;

		return $dataset;
	}

	/**
	 * Bundled dataset files, sorted for a stable order.
	 *
	 * @return string[] Absolute paths.
	 */
	protected static function data_files() {
		$files = glob( SIZE_GUIDE_PATH . 'data/*.json' );
		if ( ! is_array( $files ) ) {
			return array();
		}
		sort( $files );

		/**
		 * Filter the dataset files that get loaded.
		 *
		 * @param string[] $files Absolute paths to JSON files.
		 */
		return (array) apply_filters( 'size_guide_data_files', $files );
	}

	/**
	 * Read and decode one dataset file.
	 *
	 * @param string $file Absolute path.
	 * @return array|null
	 */
	protected static function read_json_file( $file ) {
		if ( ! is_readable( $file ) ) {
			return null;
		}

		$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local bundled data file.
		if ( false === $raw ) {
			return null;
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		return $decoded;
	}

	/**
	 * Groups imported through the admin screen.
	 *
	 * @return array
	 */
	protected static function custom_groups() {
		$custom = get_option( self::CUSTOM_OPTION, array() );

		if ( ! is_array( $custom ) || empty( $custom ) ) {
			return array();
		}

		// A custom dataset may be a single group or a list of groups.
		if ( isset( $custom['platforms'] ) ) {
			return array( $custom );
		}

		return array_values( array_filter( $custom, 'is_array' ) );
	}

	/**
	 * Normalise raw groups into the shape the UI consumes.
	 *
	 * @param array $groups Raw decoded groups.
	 * @return array
	 */
	protected static function normalise( array $groups ) {
		$sections = array();
		$index    = array();

		usort(
			$groups,
			static function ( $a, $b ) {
				$oa = isset( $a['order'] ) ? (int) $a['order'] : 100;
				$ob = isset( $b['order'] ) ? (int) $b['order'] : 100;
				return $oa <=> $ob;
			}
		);

		foreach ( $groups as $group ) {
			$section_id = isset( $group['section'] ) ? sanitize_key( $group['section'] ) : 'digital';
			$group_id   = isset( $group['id'] ) ? sanitize_key( $group['id'] ) : $section_id;

			if ( ! isset( $sections[ $section_id ] ) ) {
				$sections[ $section_id ] = array(
					'id'     => $section_id,
					'name'   => self::section_name( $section_id ),
					'groups' => array(),
				);
			}

			$normal_group = array(
				'id'        => $group_id,
				'name'      => isset( $group['name'] ) ? (string) $group['name'] : ucwords( str_replace( '-', ' ', $group_id ) ),
				'section'   => $section_id,
				'platforms' => array(),
			);

			$group_defaults = isset( $group['defaults'] ) && is_array( $group['defaults'] ) ? $group['defaults'] : array();
			$platforms      = isset( $group['platforms'] ) && is_array( $group['platforms'] ) ? $group['platforms'] : array();

			foreach ( $platforms as $platform ) {
				$normal_platform = self::normalise_platform( $platform, $section_id, $group_id, $index, $group_defaults );
				if ( $normal_platform ) {
					$normal_group['platforms'][] = $normal_platform;
				}
			}

			if ( $normal_group['platforms'] ) {
				$sections[ $section_id ]['groups'][] = $normal_group;
			}
		}

		return array(
			'schema_version' => self::SCHEMA,
			'generated'      => gmdate( 'c' ),
			'sections'       => array_values( $sections ),
			'index'          => $index,
		);
	}

	/**
	 * Normalise one platform and push its formats onto the flat index.
	 *
	 * @param array  $platform   Raw platform.
	 * @param string $section_id Owning section.
	 * @param string $group_id   Owning group.
	 * @param array  $index      Flat index, by reference.
	 * @param array  $defaults   Field defaults inherited from the group.
	 * @return array|null
	 */
	protected static function normalise_platform( $platform, $section_id, $group_id, array &$index, array $defaults = array() ) {
		if ( ! is_array( $platform ) || empty( $platform['id'] ) ) {
			return null;
		}

		$platform_id = sanitize_key( $platform['id'] );
		$result      = array(
			'id'         => $platform_id,
			'name'       => isset( $platform['name'] ) ? (string) $platform['name'] : ucwords( str_replace( '-', ' ', $platform_id ) ),
			'aliases'    => self::string_list( $platform['aliases'] ?? array() ),
			'color'      => isset( $platform['color'] ) ? (string) $platform['color'] : '',
			'section'    => $section_id,
			'group'      => $group_id,
			'categories' => array(),
		);

		if ( isset( $platform['defaults'] ) && is_array( $platform['defaults'] ) ) {
			$defaults = array_merge( $defaults, $platform['defaults'] );
		}

		$categories = isset( $platform['categories'] ) && is_array( $platform['categories'] ) ? $platform['categories'] : array();

		foreach ( $categories as $category ) {
			if ( ! is_array( $category ) || empty( $category['id'] ) ) {
				continue;
			}

			$category_id = sanitize_key( $category['id'] );
			$normal_cat  = array(
				'id'      => $category_id,
				'name'    => isset( $category['name'] ) ? (string) $category['name'] : ucwords( str_replace( '-', ' ', $category_id ) ),
				'formats' => array(),
			);

			$cat_defaults = isset( $category['defaults'] ) && is_array( $category['defaults'] )
				? array_merge( $defaults, $category['defaults'] )
				: $defaults;

			$formats = isset( $category['formats'] ) && is_array( $category['formats'] ) ? $category['formats'] : array();

			foreach ( $formats as $format ) {
				$normal_format = self::normalise_format( $format, $result, $normal_cat, $cat_defaults );
				if ( ! $normal_format ) {
					continue;
				}
				$normal_cat['formats'][] = $normal_format;
				$index[]                 = self::index_entry( $normal_format );
			}

			if ( $normal_cat['formats'] ) {
				$result['categories'][] = $normal_cat;
			}
		}

		return $result['categories'] ? $result : null;
	}

	/**
	 * Normalise a single size record, filling in every documented field.
	 *
	 * @param array $format   Raw format.
	 * @param array $platform Normalised platform.
	 * @param array $category Normalised category.
	 * @param array $defaults Inherited field defaults.
	 * @return array|null
	 */
	protected static function normalise_format( $format, array $platform, array $category, array $defaults = array() ) {
		if ( ! is_array( $format ) || empty( $format['name'] ) ) {
			return null;
		}

		if ( $defaults ) {
			$format = array_merge( $defaults, $format );
		}

		$width  = isset( $format['width'] ) ? (float) $format['width'] : 0;
		$height = isset( $format['height'] ) ? (float) $format['height'] : 0;

		if ( $width <= 0 || $height <= 0 ) {
			return null;
		}

		$unit = isset( $format['unit'] ) ? strtolower( (string) $format['unit'] ) : 'px';
		if ( ! in_array( $unit, array( 'px', 'mm', 'cm', 'in' ), true ) ) {
			$unit = 'px';
		}

		$id = ! empty( $format['id'] )
			? sanitize_key( $format['id'] )
			: sanitize_key( $platform['id'] . '-' . $category['id'] . '-' . $format['name'] );

		$dpi = isset( $format['dpi'] ) ? (int) $format['dpi'] : ( 'px' === $unit ? 72 : 300 );

		return array(
			'id'            => $id,
			'name'          => (string) $format['name'],
			'platform'      => $platform['id'],
			'platform_name' => $platform['name'],
			'category'      => $category['id'],
			'category_name' => $category['name'],
			'section'       => $platform['section'],
			'group'         => $platform['group'],
			'width'         => self::round_dimension( $width ),
			'height'        => self::round_dimension( $height ),
			'unit'          => $unit,
			'aspect_ratio'  => isset( $format['aspect_ratio'] ) && '' !== $format['aspect_ratio']
				? (string) $format['aspect_ratio']
				: self::aspect_ratio( $width, $height ),
			'orientation'   => isset( $format['orientation'] ) ? (string) $format['orientation'] : self::orientation( $width, $height ),
			'recommended'   => self::size_pair( $format['recommended'] ?? null ),
			'minimum'       => self::size_pair( $format['minimum'] ?? null ),
			'maximum'       => self::size_pair( $format['maximum'] ?? null ),
			'safe_zone'     => self::box( $format['safe_zone'] ?? null ),
			'margin'        => self::box( $format['margin'] ?? null ),
			'padding'       => self::box( $format['padding'] ?? null ),
			'bleed'         => isset( $format['bleed'] ) ? (float) $format['bleed'] : 0,
			'dpi'           => $dpi > 0 ? $dpi : 72,
			'file_formats'  => self::string_list( $format['file_formats'] ?? ( $format['formats'] ?? array() ) ),
			'max_file_size' => isset( $format['max_file_size'] ) ? (string) $format['max_file_size'] : '',
			'notes'         => isset( $format['notes'] ) ? (string) $format['notes'] : '',
			'aliases'       => self::string_list( $format['aliases'] ?? array() ),
			'keywords'      => self::string_list( $format['keywords'] ?? array() ),
			'source'        => self::source( $format['source'] ?? null ),
			'last_updated'  => isset( $format['last_updated'] ) ? (string) $format['last_updated'] : '',
		);
	}

	/**
	 * Build a compact search-index entry for one format.
	 *
	 * @param array $format Normalised format.
	 * @return array
	 */
	protected static function index_entry( array $format ) {
		$haystack = array(
			$format['name'],
			$format['platform_name'],
			$format['category_name'],
			$format['aspect_ratio'],
			$format['width'] . 'x' . $format['height'],
			$format['width'] . ' ' . $format['height'],
		);

		$haystack = array_merge( $haystack, $format['aliases'], $format['keywords'] );

		return array(
			'id'       => $format['id'],
			'name'     => $format['name'],
			'platform' => $format['platform'],
			'label'    => $format['platform_name'] . ' — ' . $format['name'],
			'section'  => $format['section'],
			'category' => $format['category'],
			'width'    => $format['width'],
			'height'   => $format['height'],
			'unit'     => $format['unit'],
			'ratio'    => $format['aspect_ratio'],
			'text'     => strtolower( implode( ' ', array_filter( $haystack ) ) ),
		);
	}

	/**
	 * Human section label.
	 *
	 * @param string $section_id Section key.
	 * @return string
	 */
	protected static function section_name( $section_id ) {
		$names = array(
			'digital' => __( 'Digital', 'size-guide' ),
			'print'   => __( 'Print', 'size-guide' ),
			'tools'   => __( 'Tools', 'size-guide' ),
		);

		return $names[ $section_id ] ?? ucwords( str_replace( '-', ' ', $section_id ) );
	}

	/**
	 * Coerce a value into a list of non-empty strings.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]
	 */
	protected static function string_list( $value ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/\s*,\s*/', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $item ) {
			if ( is_scalar( $item ) ) {
				$item = trim( (string) $item );
				if ( '' !== $item ) {
					$out[] = $item;
				}
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * Normalise a four-sided box (safe zone, margin, padding).
	 *
	 * Accepts a number (applied to all sides) or an object with sides.
	 *
	 * @param mixed $value Raw value.
	 * @return array|null
	 */
	protected static function box( $value ) {
		if ( is_numeric( $value ) ) {
			$value = array(
				'top'    => $value,
				'right'  => $value,
				'bottom' => $value,
				'left'   => $value,
			);
		}

		if ( ! is_array( $value ) ) {
			return null;
		}

		$box = array(
			'top'    => isset( $value['top'] ) ? (float) $value['top'] : 0,
			'right'  => isset( $value['right'] ) ? (float) $value['right'] : 0,
			'bottom' => isset( $value['bottom'] ) ? (float) $value['bottom'] : 0,
			'left'   => isset( $value['left'] ) ? (float) $value['left'] : 0,
		);

		$total = $box['top'] + $box['right'] + $box['bottom'] + $box['left'];

		return $total > 0 ? $box : null;
	}

	/**
	 * Normalise a width/height pair.
	 *
	 * @param mixed $value Raw value.
	 * @return array|null
	 */
	protected static function size_pair( $value ) {
		if ( ! is_array( $value ) || empty( $value['width'] ) || empty( $value['height'] ) ) {
			return null;
		}

		return array(
			'width'  => self::round_dimension( (float) $value['width'] ),
			'height' => self::round_dimension( (float) $value['height'] ),
		);
	}

	/**
	 * Normalise source metadata.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	protected static function source( $value ) {
		$types = array( 'official', 'recommended', 'common-practice', 'estimated' );

		if ( is_string( $value ) ) {
			$value = array( 'type' => $value );
		}
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$type = isset( $value['type'] ) ? sanitize_key( str_replace( ' ', '-', $value['type'] ) ) : 'common-practice';
		if ( ! in_array( $type, $types, true ) ) {
			$type = 'common-practice';
		}

		return array(
			'type'         => $type,
			'name'         => isset( $value['name'] ) ? (string) $value['name'] : '',
			'url'          => isset( $value['url'] ) ? esc_url_raw( (string) $value['url'] ) : '',
			'checked_date' => isset( $value['checked_date'] ) ? (string) $value['checked_date'] : '',
		);
	}

	/**
	 * Trim pointless decimals from a dimension.
	 *
	 * @param float $value Dimension.
	 * @return int|float
	 */
	protected static function round_dimension( $value ) {
		$rounded = round( $value, 2 );
		return ( floor( $rounded ) === $rounded ) ? (int) $rounded : $rounded;
	}

	/**
	 * Derive a simplified aspect ratio such as "4:5".
	 *
	 * @param float $width  Width.
	 * @param float $height Height.
	 * @return string
	 */
	public static function aspect_ratio( $width, $height ) {
		if ( $width <= 0 || $height <= 0 ) {
			return '';
		}

		$w = (int) round( $width * 100 );
		$h = (int) round( $height * 100 );

		$a = $w;
		$b = $h;
		while ( $b ) {
			$t = $b;
			$b = $a % $b;
			$a = $t;
		}
		$divisor = max( 1, $a );

		$rw = $w / $divisor;
		$rh = $h / $divisor;

		// Fall back to a decimal ratio when the reduced pair is unreadable,
		// keeping the "1" on the short side the way designers write it.
		if ( $rw > 20 || $rh > 20 ) {
			return ( $width >= $height )
				? round( $width / $height, 2 ) . ':1'
				: '1:' . round( $height / $width, 2 );
		}

		return $rw . ':' . $rh;
	}

	/**
	 * Physical units per inch. Pixels depend on DPI and are handled separately.
	 *
	 * @var array<string,float>
	 */
	const PER_INCH = array(
		'in' => 1.0,
		'mm' => 25.4,
		'cm' => 2.54,
	);

	/**
	 * Convert a measurement between units.
	 *
	 * @param float  $value Value.
	 * @param string $from  Source unit.
	 * @param string $to    Target unit.
	 * @param int    $dpi   Pixels per inch, used whenever px is involved.
	 * @return float
	 */
	public static function convert( $value, $from, $to, $dpi = 72 ) {
		$value = (float) $value;
		$dpi   = (int) $dpi > 0 ? (int) $dpi : 72;

		if ( $from === $to ) {
			return $value;
		}

		$inches = ( 'px' === $from )
			? $value / $dpi
			: $value / ( self::PER_INCH[ $from ] ?? 1 );

		return ( 'px' === $to )
			? $inches * $dpi
			: $inches * ( self::PER_INCH[ $to ] ?? 1 );
	}

	/**
	 * Round a measurement the way that unit is normally written.
	 *
	 * @param float  $value Value.
	 * @param string $unit  Unit key.
	 * @return int|float
	 */
	public static function round_unit( $value, $unit ) {
		if ( 'px' === $unit ) {
			return (int) round( $value );
		}

		$precision = ( 'mm' === $unit ) ? 1 : 2;
		$rounded   = round( (float) $value, $precision );

		return ( floor( $rounded ) === $rounded ) ? (int) $rounded : $rounded;
	}

	/**
	 * Format a size for display, converting it if needed.
	 *
	 * @param array  $format Normalised format.
	 * @param string $unit   Target unit.
	 * @param int    $dpi    Pixels per inch.
	 * @return string
	 */
	public static function dimensions( array $format, $unit = null, $dpi = null ) {
		$unit = $unit ? $unit : $format['unit'];
		$dpi  = $dpi ? $dpi : $format['dpi'];

		$width  = self::round_unit( self::convert( $format['width'], $format['unit'], $unit, $dpi ), $unit );
		$height = self::round_unit( self::convert( $format['height'], $format['unit'], $unit, $dpi ), $unit );

		return $width . ' × ' . $height . ' ' . $unit;
	}

	/**
	 * Portrait, landscape or square.
	 *
	 * @param float $width  Width.
	 * @param float $height Height.
	 * @return string
	 */
	protected static function orientation( $width, $height ) {
		if ( abs( $width - $height ) < 0.01 ) {
			return 'square';
		}
		return $width > $height ? 'landscape' : 'portrait';
	}

	/**
	 * Look one format up by id.
	 *
	 * @param string $format_id Format id.
	 * @return array|null
	 */
	public static function get_format( $format_id ) {
		$format_id = sanitize_key( $format_id );
		$dataset   = self::get_dataset();

		foreach ( $dataset['sections'] as $section ) {
			foreach ( $section['groups'] as $group ) {
				foreach ( $group['platforms'] as $platform ) {
					foreach ( $platform['categories'] as $category ) {
						foreach ( $category['formats'] as $format ) {
							if ( $format['id'] === $format_id ) {
								return $format;
							}
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * All formats as a flat list.
	 *
	 * @return array
	 */
	public static function get_formats() {
		$dataset = self::get_dataset();
		$formats = array();

		foreach ( $dataset['sections'] as $section ) {
			foreach ( $section['groups'] as $group ) {
				foreach ( $group['platforms'] as $platform ) {
					foreach ( $platform['categories'] as $category ) {
						foreach ( $category['formats'] as $format ) {
							$formats[] = $format;
						}
					}
				}
			}
		}

		return $formats;
	}

	/**
	 * Server-side search, mirroring the browser search for no-JS and REST use.
	 *
	 * @param string $query Search term.
	 * @param int    $limit Maximum results.
	 * @return array
	 */
	public static function search( $query, $limit = 25 ) {
		$query = strtolower( trim( (string) $query ) );
		if ( '' === $query ) {
			return array();
		}

		$query   = self::expand_abbreviations( $query );
		$terms   = array_filter( preg_split( '/\s+/', $query ) );
		$dataset = self::get_dataset();
		$results = array();

		foreach ( $dataset['index'] as $entry ) {
			$score = 0;

			foreach ( $terms as $term ) {
				$pos = strpos( $entry['text'], $term );
				if ( false === $pos ) {
					$score = 0;
					break;
				}
				$score += ( 0 === $pos ) ? 3 : 1;
			}

			if ( $score > 0 ) {
				$entry['score'] = $score;
				$results[]      = $entry;
			}
		}

		usort(
			$results,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return array_slice( $results, 0, max( 1, (int) $limit ) );
	}

	/**
	 * Expand common designer shorthand ("ig post" -> "instagram post").
	 *
	 * @param string $query Lowercased query.
	 * @return string
	 */
	public static function expand_abbreviations( $query ) {
		$map = self::abbreviations();

		return preg_replace_callback(
			'/\b[a-z0-9]+\b/',
			static function ( $matches ) use ( $map ) {
				return $map[ $matches[0] ] ?? $matches[0];
			},
			$query
		);
	}

	/**
	 * Shorthand map shared with the browser search.
	 *
	 * @return array<string,string>
	 */
	public static function abbreviations() {
		$map = array(
			'ig'   => 'instagram',
			'insta' => 'instagram',
			'fb'   => 'facebook',
			'yt'   => 'youtube',
			'li'   => 'linkedin',
			'tt'   => 'tiktok',
			'x'    => 'twitter',
			'pin'  => 'pinterest',
			'wa'   => 'whatsapp',
			'tg'   => 'telegram',
			'pfp'  => 'profile picture',
			'dp'   => 'profile picture',
			'avi'  => 'profile picture',
			'thumb' => 'thumbnail',
			'bc'   => 'business card',
			'og'   => 'open graph',
		);

		/**
		 * Filter the search shorthand map.
		 *
		 * @param array<string,string> $map Shorthand to expansion.
		 */
		return (array) apply_filters( 'size_guide_search_abbreviations', $map );
	}

	/**
	 * Drop the cached dataset.
	 */
	public static function flush_cache() {
		self::$memo = null;
		delete_transient( self::CACHE_KEY );
	}
}
