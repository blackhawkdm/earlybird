<?php
/**
 * Template Name: Location Page
 *
 * Assigned to all EarlyBird city location pages. The ACF field group in
 * inc/acf-location.php targets this template so its fields appear only
 * on location pages, not on standard WP pages.
 *
 * Actual page rendering is handled entirely by the Elementor Theme Builder
 * single template whose ID is set in inc/elementor-location-override.php.
 * This file only needs to exist — WordPress registers the template name
 * from the header comment above.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Ask Elementor Pro to render the matched Theme Builder 'single' location
// template. Falls back to the standard WP loop if Elementor Pro is inactive
// or no Theme Builder template is matched for this page.
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'single' ) ) {
	while ( have_posts() ) {
		the_post();
		the_content();
	}
}

get_footer();
