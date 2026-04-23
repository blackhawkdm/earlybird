<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

	wp_enqueue_script(
		'eb-location-swap',
		get_stylesheet_directory_uri() . '/inc/location-swap.js',
		[],
		HELLO_ELEMENTOR_CHILD_VERSION,
		true // load in footer
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

// Location-based shortcodes and menu filters.
require_once get_stylesheet_directory() . '/inc/shortcodes-location.php';

// ACF field group registration for location pages.
require_once get_stylesheet_directory() . '/inc/acf-location.php';

// Elementor template override — pushes the location template into the queue.
// Update EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID in this file after
// creating the Elementor template in WP Admin.
require_once get_stylesheet_directory() . '/inc/elementor-location-override.php';

// JSON-LD schema markup for location pages.
require_once get_stylesheet_directory() . '/inc/location-schema.php';

// Importer class and admin UI are never needed on the frontend.
// Load them only in admin and WP-CLI contexts.
if ( is_admin() ) {
	require_once get_stylesheet_directory() . '/inc/location-importer.php';
	require_once get_stylesheet_directory() . '/inc/admin-location-import.php';
}
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once get_stylesheet_directory() . '/inc/location-importer.php';
	require_once get_stylesheet_directory() . '/inc/cli-location-import.php';
}