<?php
/**
 * Removes Size Guide data when the plugin is deleted.
 *
 * @package SizeGuide
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$size_guide_options = array(
	'size_guide_settings',
	'size_guide_custom_dataset',
	'size_guide_appearance',
);

foreach ( $size_guide_options as $size_guide_option ) {
	delete_option( $size_guide_option );
	delete_site_option( $size_guide_option );
}

delete_transient( 'size_guide_dataset_v1' );

// Multisite: clean each site in the network.
if ( is_multisite() ) {
	$size_guide_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $size_guide_sites as $size_guide_site_id ) {
		switch_to_blog( $size_guide_site_id );

		foreach ( $size_guide_options as $size_guide_option ) {
			delete_option( $size_guide_option );
		}
		delete_transient( 'size_guide_dataset_v1' );

		restore_current_blog();
	}
}
