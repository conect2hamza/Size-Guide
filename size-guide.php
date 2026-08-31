<?php
/**
 * Plugin Name:       Size Guide
 * Plugin URI:        https://github.com/conect2hamza/size-guide
 * Description:       A free design size reference & template toolkit. Social media, web, ad, video and print dimensions with safe zones, margins, bleed, DPI, unit conversion and downloadable SVG/PNG templates.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Size Guide
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       size-guide
 * Domain Path:       /languages
 *
 * @package SizeGuide
 */

defined( 'ABSPATH' ) || exit;

define( 'SIZE_GUIDE_VERSION', '1.0.0' );
define( 'SIZE_GUIDE_FILE', __FILE__ );
define( 'SIZE_GUIDE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIZE_GUIDE_URL', plugin_dir_url( __FILE__ ) );
define( 'SIZE_GUIDE_BASENAME', plugin_basename( __FILE__ ) );

require_once SIZE_GUIDE_PATH . 'includes/class-data-loader.php';
require_once SIZE_GUIDE_PATH . 'includes/class-appearance.php';
require_once SIZE_GUIDE_PATH . 'includes/class-template-generator.php';
require_once SIZE_GUIDE_PATH . 'includes/class-shortcode.php';
require_once SIZE_GUIDE_PATH . 'includes/class-rest-api.php';
require_once SIZE_GUIDE_PATH . 'includes/class-admin.php';
require_once SIZE_GUIDE_PATH . 'includes/class-size-guide.php';

/**
 * Main plugin instance.
 *
 * @return SizeGuide\Size_Guide
 */
function size_guide() {
	return SizeGuide\Size_Guide::instance();
}

size_guide()->boot();

/**
 * Activation: flush the dataset cache so a fresh install reads current files.
 */
function size_guide_activate() {
	SizeGuide\Data_Loader::flush_cache();
	add_option( 'size_guide_settings', SizeGuide\Size_Guide::default_settings() );
}
register_activation_hook( __FILE__, 'size_guide_activate' );

/**
 * Deactivation: drop cached data, keep user settings.
 */
function size_guide_deactivate() {
	SizeGuide\Data_Loader::flush_cache();
}
register_deactivation_hook( __FILE__, 'size_guide_deactivate' );
