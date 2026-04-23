<?php
/**
 * EarlyBird Location Pages — JSON-LD Schema Markup
 *
 * Outputs an Electrician JSON-LD block in <head> for every page using
 * template-location.php. Suppresses Rank Math's own LocalBusiness output
 * on these pages to prevent duplicate schema.
 *
 * Schema type is hardcoded as "Electrician" for all EarlyBird location pages.
 *
 * Fields used:
 *   - name         ← ACF h1 field
 *   - url          ← page permalink
 *   - telephone    ← site default (update EARLYBIRD_SCHEMA_PHONE below)
 *   - areaServed   ← ACF h1 field (city context)
 *   - review[]     ← ACF testimonial_{1-3}_quote / testimonial_{1-3}_author
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site-wide default phone number for EarlyBird Electricians.
 * Update this to the correct phone number.
 */
if ( ! defined( 'EARLYBIRD_SCHEMA_PHONE' ) ) {
	define( 'EARLYBIRD_SCHEMA_PHONE', '' ); // ← UPDATE: e.g. '+1-555-867-5309'
}

add_action( 'wp_head', 'earlybird_output_location_schema', 20 );

/**
 * Output the JSON-LD block. Runs only on location pages.
 */
function earlybird_output_location_schema(): void {
	if ( ! is_singular() ) {
		return;
	}

	global $post;
	if ( empty( $post ) ) {
		return;
	}

	$template = get_post_meta( $post->ID, '_wp_page_template', true );
	if ( 'template-location.php' !== $template ) {
		return;
	}

	if ( ! function_exists( 'get_field' ) ) {
		return;
	}

	$h1       = get_field( 'h1', $post->ID );
	$name     = $h1 ?: get_the_title( $post->ID );
	$url      = get_permalink( $post->ID );
	$phone    = EARLYBIRD_SCHEMA_PHONE;

	$schema = [
		'@context'    => 'https://schema.org',
		'@type'       => 'Electrician',
		'name'        => $name,
		'url'         => $url,
		'areaServed'  => $name,
	];

	if ( $phone ) {
		$schema['telephone'] = $phone;
	}

	// Build reviews from flat testimonial fields.
	$reviews = [];
	for ( $i = 1; $i <= 3; $i++ ) {
		$quote  = get_field( "testimonial_{$i}_quote",  $post->ID );
		$author = get_field( "testimonial_{$i}_author", $post->ID );

		if ( empty( $quote ) ) {
			continue;
		}

		$review = [
			'@type'      => 'Review',
			'reviewBody' => $quote,
		];

		if ( $author ) {
			$review['author'] = [
				'@type' => 'Person',
				'name'  => $author,
			];
		}

		$reviews[] = $review;
	}

	if ( $reviews ) {
		$schema['review'] = $reviews;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}

/**
 * Suppress Rank Math's own LocalBusiness JSON-LD on location pages to prevent
 * duplicate schema output.
 *
 * @param array $data  Rank Math's JSON-LD data array.
 * @return array
 */
add_filter( 'rank_math/json_ld', 'earlybird_suppress_rankmath_localbusiness_on_location_pages', 10 );

function earlybird_suppress_rankmath_localbusiness_on_location_pages( array $data ): array {
	if ( ! is_singular() ) {
		return $data;
	}

	global $post;
	if ( empty( $post ) ) {
		return $data;
	}

	$template = get_post_meta( $post->ID, '_wp_page_template', true );
	if ( 'template-location.php' !== $template ) {
		return $data;
	}

	// Remove any LocalBusiness-type entries Rank Math would output.
	foreach ( $data as $key => $block ) {
		if (
			isset( $block['@type'] ) &&
			in_array( $block['@type'], [ 'LocalBusiness', 'Electrician', 'HomeAndConstructionBusiness' ], true )
		) {
			unset( $data[ $key ] );
		}
	}

	return $data;
}
