<?php
/**
 * Location-based shortcodes and menu filters for Early Bird Electricians.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------
 * 1. LOCATION DATA

 * ------------------------------------------------------------- */

function eb_get_location_data() {
	return array(
		'Minneapolis' => array(
			'city'              => 'Minneapolis',
			'services_label'    => 'Electric Service in Minneapolis',
			'address_line1'     => '5720 International Parkway',
			'address_line2'     => 'New Hope, MN 55428',
			'phone_display'     => '612-421-1300',
			'phone_tel'         => '6124211300',
			'booking_url'       => '/minneapolis/service-areas/',
			'url_prefix'        => '/minneapolis',
			'service_urls'      => array(
				'repair'         => '/minneapolis/services/electric-repair/',
				'install'        => '/minneapolis/services/electric-installation/',
				'lighting'       => '/minneapolis/services/indoor-outdoor-lighting/',
				'safety'         => '/minneapolis/services/safety-services/',
				'wiring'         => '/minneapolis/services/electric-repair/home-wiring-rewiring/',
				'panels'         => '/minneapolis/services/electric-installation/electrical-panels/',
				'carbonmonoxide' => '/minneapolis/services/safety-services/carbon-monoxide-detectors/',
				'smoke'          => '/minneapolis/services/safety-services/smoke-detectors/',
				'homesafety'     => '/minneapolis/services/safety-services/home-electrical-safety-inspection/',
			),
			'btn_label_phone'   => 'Call (612) 421-1300',
			'btn_label_booking' => 'Book in Minneapolis',
		),
		'Rochester' => array(
			'city'              => 'Rochester',
			'services_label'    => 'Electric Service in Rochester',
			'address_line1'     => '4410 19th Street NW',
			'address_line2'     => 'Rochester, MN 55901',
			'phone_display'     => '507-821-3664',
			'phone_tel'         => '5078213664',
			'booking_url'       => '/rochester/service-areas/',
			'url_prefix'        => '/rochester',
			'service_urls'      => array(
				'repair'         => '/rochester/services/electric-repair/',
				'install'        => '/rochester/services/electric-installation/',
				'lighting'       => '/rochester/services/indoor-outdoor-lighting/',
				'safety'         => '/rochester/services/safety-services/',
				'wiring'         => '/rochester/services/electric-repair/home-wiring-rewiring/',
				'panels'         => '/rochester/services/electric-installation/electrical-panels/',
				'carbonmonoxide' => '/rochester/services/safety-services/carbon-monoxide-detectors/',
				'smoke'          => '/rochester/services/safety-services/smoke-detectors/',
				'homesafety'     => '/rochester/services/safety-services/home-electrical-safety-inspection/',
			),
			'btn_label_phone'   => 'Call (507) 821-3664',
			'btn_label_booking' => 'Book in Rochester',
		),
	);
}

/* ---------------------------------------------------------------
 * 2. HELPER — detect visitor location from URL path, then cookie
 * ------------------------------------------------------------- */

function eb_get_visitor_location() {
	$locations = eb_get_location_data();
	$default   = 'Minneapolis';

	// 1. Check URL path for a location slug (best for SEO — Googlebot gets correct content).
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$path = strtolower( $_SERVER['REQUEST_URI'] );
		foreach ( $locations as $loc ) {
			if ( strpos( $path, strtolower( $loc['url_prefix'] ) . '/' ) !== false ) {
				return $loc;
			}
		}
	}

	// 2. Fall back to cookie (for global pages like homepage, blog, etc.).
	if ( isset( $_COOKIE['client_region'] ) ) {
		$region = ucfirst( strtolower( trim( $_COOKIE['client_region'] ) ) );
		if ( isset( $locations[ $region ] ) ) {
			return $locations[ $region ];
		}
	}

	return $locations[ $default ];
}

/* ---------------------------------------------------------------
 * 3. SHORTCODES — one per data field
 * ------------------------------------------------------------- */

// [eb_city]
function eb_city_shortcode() {
	$loc = eb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="city">' . esc_html( $loc['city'] ) . '</span>';
}
add_shortcode( 'eb_city', 'eb_city_shortcode' );

// [eb_services_label]
function eb_services_label_shortcode() {
	$loc = eb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="services">' . esc_html( $loc['services_label'] ) . '</span>';
}
add_shortcode( 'eb_services_label', 'eb_services_label_shortcode' );

// [eb_address]
function eb_address_shortcode() {
	$loc  = eb_get_visitor_location();
	$html = esc_html( $loc['address_line1'] ) . ' <br> <span class="address-line2">' . esc_html( $loc['address_line2'] ) . '</span>';
	return '<span class="loc-dynamic-data" data-field="address">' . wp_kses( $html, array( 'br' => array(), 'span' => array( 'class' => array() ) ) ) . '</span>';
}
add_shortcode( 'eb_address', 'eb_address_shortcode' );

// [eb_phone]
function eb_phone_shortcode() {
	$loc = eb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="phone">' . esc_html( $loc['phone_display'] ) . '</span>';
}
add_shortcode( 'eb_phone', 'eb_phone_shortcode' );

// [eb_phone_link] — raw tel: URI, no HTML wrapper
function eb_phone_link_shortcode() {
	$loc = eb_get_visitor_location();
	return 'tel:' . esc_attr( $loc['phone_tel'] );
}
add_shortcode( 'eb_phone_link', 'eb_phone_link_shortcode' );

// [csad_phone] — inline clickable phone link
function csad_phone_shortcode() {
	$loc = eb_get_visitor_location();
	return '<a href="tel:' . esc_attr( $loc['phone_tel'] ) . '" class="loc-dynamic-data" data-field="phone_inline">' . esc_html( $loc['phone_display'] ) . '</a>';
}
add_shortcode( 'csad_phone', 'csad_phone_shortcode' );

// [eb_booking_url] — raw URL, no HTML wrapper
function eb_booking_url_shortcode() {
	$loc = eb_get_visitor_location();
	return esc_url( $loc['booking_url'] );
}
add_shortcode( 'eb_booking_url', 'eb_booking_url_shortcode' );

// [eb_service_url service="repair"] — raw URL, no HTML wrapper
function eb_service_url_shortcode( $atts ) {
	$a   = shortcode_atts( array( 'service' => '' ), $atts );
	$loc = eb_get_visitor_location();
	$key = sanitize_key( $a['service'] );
	if ( isset( $loc['service_urls'][ $key ] ) ) {
		return esc_url( $loc['service_urls'][ $key ] );
	}
	return '';
}
add_shortcode( 'eb_service_url', 'eb_service_url_shortcode' );

// [eb_url_prefix] — raw path prefix, no HTML wrapper
function eb_url_prefix_shortcode() {
	$loc = eb_get_visitor_location();
	return esc_attr( $loc['url_prefix'] );
}
add_shortcode( 'eb_url_prefix', 'eb_url_prefix_shortcode' );

// [eb_btn_label_phone]
function eb_btn_label_phone_shortcode() {
	$loc = eb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="btn_label_phone">' . esc_html( $loc['btn_label_phone'] ) . '</span>';
}
add_shortcode( 'eb_btn_label_phone', 'eb_btn_label_phone_shortcode' );

// [eb_btn_label_booking]
function eb_btn_label_booking_shortcode() {
	$loc = eb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="btn_label_booking">' . esc_html( $loc['btn_label_booking'] ) . '</span>';
}
add_shortcode( 'eb_btn_label_booking', 'eb_btn_label_booking_shortcode' );

// Keep legacy shortcodes for backward compatibility
// [loc_data field="city" default="Minneapolis"]
function eb_loc_data_shortcode( $atts ) {
	$a   = shortcode_atts( array( 'field' => 'city', 'default' => '' ), $atts );
	$loc = eb_get_visitor_location();

	$field_map = array(
		'city'              => 'city',
		'services'          => 'services_label',
		'phone'             => 'phone_display',
		'btn_label_phone'   => 'btn_label_phone',
		'btn_label_booking' => 'btn_label_booking',
	);

	$key   = $a['field'];
	$value = $a['default'];

	if ( $key === 'address' ) {
		$html = esc_html( $loc['address_line1'] ) . ' <br> <span class="address-line2">' . esc_html( $loc['address_line2'] ) . '</span>';
		return '<span class="loc-dynamic-data" data-field="address">' . wp_kses( $html, array( 'br' => array(), 'span' => array( 'class' => array() ) ) ) . '</span>';
	}

	if ( isset( $field_map[ $key ] ) && isset( $loc[ $field_map[ $key ] ] ) ) {
		$value = $loc[ $field_map[ $key ] ];
	}

	return '<span class="loc-dynamic-data" data-field="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</span>';
}
add_shortcode( 'loc_data', 'eb_loc_data_shortcode' );

// [loc_button type="phone" text="Call Now"]
function eb_loc_button_shortcode( $atts ) {
	$a = shortcode_atts( array(
		'type'  => 'phone',
		'class' => 'elementor-button-link elementor-button elementor-size-sm',
		'text'  => 'Call Now',
	), $atts );

	$loc = eb_get_visitor_location();

	if ( $a['type'] === 'phone' ) {
		$href  = 'tel:' . esc_attr( $loc['phone_tel'] );
		$label = $loc['btn_label_phone'];
	} else {
		$href  = esc_url( $loc['booking_url'] );
		$label = $loc['btn_label_booking'];
	}

	return '<a href="' . $href . '" class="loc-dynamic-btn ' . esc_attr( $a['class'] ) . '" data-type="' . esc_attr( $a['type'] ) . '" role="button">
		<span class="elementor-button-content-wrapper">
			<span class="elementor-button-text">' . esc_html( $label ) . '</span>
		</span>
	</a>';
}
add_shortcode( 'loc_button', 'eb_loc_button_shortcode' );

// [eb_location_block] — address block with integrated location switcher.
function eb_location_block_shortcode() {
	$locations = eb_get_location_data();
	$current   = eb_get_visitor_location();

	$arrow = '<svg class="blueox-location-block__arrow" xmlns="http://www.w3.org/2000/svg" width="12" height="8" viewBox="0 0 12 8"><path d="M1 1.5l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	$address_html = esc_html( $current['address_line1'] ) . ' <br> <span class="address-line2">' . esc_html( $current['address_line2'] ) . '</span>';

	$html  = '<div class="blueox-location-block" id="blueox-location-block">';
	$html .= '  <div class="blueox-location-block__pin">';
	$html .= '    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>';
	$html .= '  </div>';
	$html .= '  <div class="blueox-location-block__content">';
	$html .= '    <span class="blueox-location-block__label">Selected location:</span>';
	$html .= '    <div class="blueox-location-block__switcher">';
	$html .= '      <select id="blueox-location-switcher" class="blueox-location-block__select">';
	foreach ( $locations as $key => $loc ) {
		$selected = ( $loc['city'] === $current['city'] ) ? ' selected' : '';
		$html    .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $key ) . '</option>';
	}
	$html .= '      </select>';
	$html .= '      <span class="blueox-location-block__city loc-dynamic-data" data-field="city">' . esc_html( $current['city'] ) . '</span>';
	$html .= '      ' . $arrow;
	$html .= '    </div>';
	$html .= '    <div class="blueox-location-block__address">';
	$html .= '      <span class="loc-dynamic-data" data-field="address">' . wp_kses( $address_html, array( 'br' => array(), 'span' => array( 'class' => array() ) ) ) . '</span>';
	$html .= '    </div>';
	$html .= '  </div>';
	$html .= '</div>';

	return $html;
}
add_shortcode( 'eb_location_block', 'eb_location_block_shortcode' );

/* ---------------------------------------------------------------
 * 4. MENU FILTERS — rewrite URLs and add data attributes
 * ------------------------------------------------------------- */

// Map of generic service paths to service keys (parent pages only)
function eb_get_menu_service_map() {
	return array(
		'/services/electric-repair/'        => 'repair',
		'/services/electric-installation/'  => 'install',
		'/services/indoor-outdoor-lighting/' => 'lighting',
		'/services/safety-services/'        => 'safety',
	);
}

// Get all location slugs for URL matching
function eb_get_location_slugs() {
	$locations = eb_get_location_data();
	$slugs     = array();
	foreach ( $locations as $loc ) {
		$slugs[] = trim( $loc['url_prefix'], '/' );
	}
	return $slugs; // e.g. ['minneapolis', 'rochester']
}

// Filter: rewrite menu item URLs to match visitor's location
add_filter( 'wp_nav_menu_objects', 'eb_rewrite_menu_urls', 10, 2 );

function eb_rewrite_menu_urls( $items, $args ) {
	$loc   = eb_get_visitor_location();
	$slugs = eb_get_location_slugs();

	$visitor_prefix = ltrim( $loc['url_prefix'], '/' ); // e.g. 'minneapolis'

	foreach ( $items as &$item ) {
		$url  = $item->url;
		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( ! $path ) {
			continue;
		}

		// Check if this URL contains any location slug
		foreach ( $slugs as $slug ) {
			if ( strpos( $path, '/' . $slug . '/' ) !== false ) {
				// Replace the location slug with the visitor's prefix
				$new_path  = str_replace( '/' . $slug . '/', '/' . $visitor_prefix . '/', $path );
				$item->url = str_replace( $path, $new_path, $url );

				// Add loc-dynamic-link class
				if ( ! in_array( 'loc-dynamic-link', $item->classes, true ) ) {
					$item->classes[] = 'loc-dynamic-link';
				}
				break;
			}
		}
	}

	return $items;
}

// Filter: add data-service attribute to parent service page links only
add_filter( 'nav_menu_link_attributes', 'eb_add_service_data_attr', 10, 4 );

function eb_add_service_data_attr( $atts, $item, $args, $depth ) {
	$url  = isset( $atts['href'] ) ? $atts['href'] : '';
	$path = wp_parse_url( $url, PHP_URL_PATH );

	if ( ! $path ) {
		return $atts;
	}

	$service_map = eb_get_menu_service_map();
	$slugs       = eb_get_location_slugs();

	// Strip location prefix to get the generic path
	$generic_path = $path;
	foreach ( $slugs as $slug ) {
		$generic_path = str_replace( '/' . $slug, '', $generic_path );
	}

	// Check if this generic path matches a parent service page exactly
	foreach ( $service_map as $service_path => $service_key ) {
		if ( $generic_path === $service_path ) {
			// Exact match = parent page — add data-service
			$atts['data-service'] = $service_key;

			// Ensure loc-dynamic-link class is on the <a> tag
			$existing = isset( $atts['class'] ) ? $atts['class'] : '';
			if ( strpos( $existing, 'loc-dynamic-link' ) === false ) {
				$atts['class'] = trim( $existing . ' loc-dynamic-link' );
			}
			break;
		}
		// If the path starts with the service path but has more segments,
		// it's a child page — skip data-service attribute
	}

	return $atts;
}
