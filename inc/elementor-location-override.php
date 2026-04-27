<?php
/**
 * Elementor Theme Builder — Location Page Template Override
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * Elementor Pro stores display conditions in the _elementor_conditions post
 * meta on each template. Every time a template is saved in the editor,
 * Elementor overwrites that meta with only the conditions shown in the editor
 * UI — discarding anything written externally. This file bypasses that
 * entirely.
 *
 * HOW IT ACTUALLY WORKS
 * ---------------------
 * We hook into template_redirect (which fires before any template is loaded)
 * and push the template ID directly into the 'single' location queue. When
 * template-location.php later calls elementor_theme_do_location('single'),
 * the queue is already populated and the template renders correctly.
 *
 * This approach survives any number of template saves/edits — there are no
 * database writes and no dependency on the conditions cache.
 *
 * MAINTENANCE
 * -----------
 * After creating the Elementor template in WP Admin, update the constant
 * below with the correct post ID for this environment. The ID appears in
 * the URL when editing the template:
 *   .../wp-admin/post.php?post=XXXX&action=elementor
 *
 * Steps: WP Admin → Templates → Theme Builder → open "Location Page Template"
 *        → read post=XXXX from the URL → update the constant below.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Post ID of the "Location Page Template" entry in the Elementor library. */
define( 'EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID', 15228 );

add_action( 'template_redirect', 'earlybird_queue_location_template', 20 );

/**
 * Push the location template into Elementor's 'single' location queue for any
 * page using template-location.php. Runs before the template file is loaded so
 * it's in place when elementor_theme_do_location('single') fires.
 */
function earlybird_queue_location_template(): void {
	// Only act on singular pages — skip admin, feeds, REST, etc.
	if ( ! is_singular() ) {
		return;
	}

	// Bail if the template ID hasn't been set yet.
	if ( 0 === EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID ) {
		return;
	}

	global $post;
	if ( empty( $post ) ) {
		return;
	}

	// Check _wp_page_template directly — reliable before WP's template
	// hierarchy runs and without an extra DB query (already in meta cache).
	$template = get_post_meta( $post->ID, '_wp_page_template', true );
	if ( 'template-location.php' !== $template ) {
		return;
	}

	if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
		return;
	}

	$locations_manager = \ElementorPro\Modules\ThemeBuilder\Module::instance()
		->get_locations_manager();

	$locations_manager->add_doc_to_location( 'single', EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID );
}
