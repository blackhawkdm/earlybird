<?php
/**
 * WP-CLI eval-file: Set Elementor Theme Builder conditions for the
 * EarlyBird Location Page Template.
 *
 * Finds every published page with _wp_page_template = template-location.php
 * and adds an include/singular/page/{ID} condition for each one.
 * Calls the real Elementor Pro conditions manager so _elementor_conditions meta
 * and the elementor_pro_theme_builder_conditions cache are both written
 * exactly as the UI would write them.
 *
 * Requires EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID to be set in
 * inc/elementor-location-override.php before running.
 *
 * Usage (run from Local Site Shell, from the site root):
 *
 *   wp eval-file wp-content/themes/hello-theme-child-master/inc/cli-elementor-set-conditions.php
 *
 * To append to existing conditions instead of replacing them, pass --append:
 *
 *   wp eval-file wp-content/themes/hello-theme-child-master/inc/cli-elementor-set-conditions.php -- --append
 *
 * @package HelloElementorChild
 */

// ---------------------------------------------------------------------------
// 0. Bootstrap checks
// ---------------------------------------------------------------------------

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	die( "Run this with: wp eval-file\n" );
}

$append = in_array( '--append', $GLOBALS['argv'] ?? [], true );

if ( ! defined( 'EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID' ) || 0 === EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID ) {
	WP_CLI::error( 'EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID is not set or is 0. Update inc/elementor-location-override.php with the correct template post ID first.' );
}

$template_id = EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID;

WP_CLI::log( "=== Elementor Conditions Setter ===" );
WP_CLI::log( "Target template ID : {$template_id}" );
WP_CLI::log( "Mode               : " . ( $append ? 'APPEND to existing conditions' : 'REPLACE all conditions' ) );
WP_CLI::log( '' );

// ---------------------------------------------------------------------------
// 1. Verify Elementor Pro is available
// ---------------------------------------------------------------------------

if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
	WP_CLI::error( 'ElementorPro\Modules\ThemeBuilder\Module not found. Is Elementor Pro active?' );
}

$theme_builder      = \ElementorPro\Modules\ThemeBuilder\Module::instance();
$conditions_manager = $theme_builder->get_conditions_manager();
$document           = $theme_builder->get_document( $template_id );

if ( ! $document ) {
	WP_CLI::error( "Elementor document not found for post ID {$template_id}. Verify the template exists and is published." );
}

WP_CLI::log( "Template title : " . get_the_title( $template_id ) );
WP_CLI::log( "Template type  : " . get_class( $document ) );
WP_CLI::log( '' );

// ---------------------------------------------------------------------------
// 2. Find all location pages
// ---------------------------------------------------------------------------

$pages = get_posts( [
	'post_type'              => 'page',
	'post_status'            => 'publish',
	'posts_per_page'         => -1,
	'no_found_rows'          => true,
	'update_post_term_cache' => false,
	'meta_query'             => [ [
		'key'   => '_wp_page_template',
		'value' => 'template-location.php',
	] ],
] );

if ( empty( $pages ) ) {
	WP_CLI::error( 'No published pages found with _wp_page_template = template-location.php.' );
}

WP_CLI::log( "Found " . count( $pages ) . " location page(s):" );
foreach ( $pages as $page ) {
	WP_CLI::log( sprintf( "  ID %-6d  %-30s  /%s/", $page->ID, $page->post_title, $page->post_name ) );
}
WP_CLI::log( '' );

// ---------------------------------------------------------------------------
// 3. Build the conditions array
// ---------------------------------------------------------------------------
// save_conditions() expects an array of arrays. It does:
//   unset($condition['_id']);
//   $conditions_to_save[] = rtrim(implode('/', $condition), '/');
// So ['include','singular','page','123'] → 'include/singular/page/123'.
//
// The stored flat-string format already in _elementor_conditions is:
//   ['include/singular/page/123', 'include/general', ...]
// We need to convert those back to arrays when appending.

$new_conditions = [];

if ( $append ) {
	$existing_strings = $document->get_main_meta( '_elementor_conditions' );

	if ( is_array( $existing_strings ) ) {
		foreach ( $existing_strings as $cond_string ) {
			$new_conditions[] = explode( '/', $cond_string );
		}
		WP_CLI::log( "Preserving " . count( $new_conditions ) . " existing condition(s)." );
	}
}

$existing_ids = [];
foreach ( $new_conditions as $c ) {
	if ( isset( $c[3] ) && is_numeric( $c[3] ) ) {
		$existing_ids[] = (int) $c[3];
	}
}

$added = 0;
foreach ( $pages as $page ) {
	if ( in_array( $page->ID, $existing_ids, true ) ) {
		WP_CLI::log( WP_CLI::colorize( "%ySkipping (already present): ID {$page->ID} {$page->post_title}%n" ) );
		continue;
	}

	$new_conditions[] = [ 'include', 'singular', 'page', (string) $page->ID ];
	WP_CLI::log( WP_CLI::colorize( "%gAdding: include/singular/page/{$page->ID}  ({$page->post_title})%n" ) );
	$added++;
}

if ( 0 === $added && ! $append ) {
	WP_CLI::warning( 'No new conditions to add.' );
}

WP_CLI::log( '' );
WP_CLI::log( "Total conditions to save: " . count( $new_conditions ) );
WP_CLI::log( '' );

// ---------------------------------------------------------------------------
// 4. Save via the real Elementor conditions manager
// ---------------------------------------------------------------------------
// save_conditions() does two things:
//   a) $document->update_meta('_elementor_conditions', $flat_strings)
//   b) $this->cache->regenerate()  → rewrites elementor_pro_theme_builder_conditions

$is_saved = $conditions_manager->save_conditions( $template_id, $new_conditions );

if ( ! $is_saved ) {
	WP_CLI::error( 'save_conditions() returned false. The meta update may have failed.' );
}

WP_CLI::log( "Conditions saved to _elementor_conditions meta." );
WP_CLI::log( "Cache regenerated." );
WP_CLI::log( '' );

// ---------------------------------------------------------------------------
// 5. Verify: re-read meta and cache
// ---------------------------------------------------------------------------

WP_CLI::log( "--- Verification ---" );

$saved_meta = $document->get_main_meta( '_elementor_conditions' );

WP_CLI::log( "_elementor_conditions meta on post {$template_id}:" );
if ( is_array( $saved_meta ) ) {
	foreach ( $saved_meta as $condition_str ) {
		WP_CLI::log( "  {$condition_str}" );
	}
} else {
	WP_CLI::warning( "  Meta value is not an array: " . var_export( $saved_meta, true ) );
}

WP_CLI::log( '' );

// The actual cache option name from conditions-cache.php:
//   const OPTION_NAME = 'elementor_pro_theme_builder_conditions';
// (not 'elementor_pro_conditions_cache' as older docs suggest)
$cache = get_option( 'elementor_pro_theme_builder_conditions' );

if ( empty( $cache ) ) {
	WP_CLI::warning( "Cache option 'elementor_pro_theme_builder_conditions' is empty after regeneration!" );
} else {
	WP_CLI::log( "Cache option 'elementor_pro_theme_builder_conditions':" );
	foreach ( $cache as $location => $templates ) {
		WP_CLI::log( "  Location: {$location}" );
		foreach ( $templates as $tmpl_id => $conditions ) {
			WP_CLI::log( "    Template ID {$tmpl_id} (" . count( $conditions ) . " condition(s)):" );
			foreach ( $conditions as $c ) {
				WP_CLI::log( "      {$c}" );
			}
		}
	}
}

WP_CLI::log( '' );
WP_CLI::success( "Done. Template {$template_id} now has " . count( $saved_meta ) . " condition(s)." );
