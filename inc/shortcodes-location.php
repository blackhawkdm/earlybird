<?php
/**
 * Location-based shortcodes and menu filters for Paul Bunyan Plumbing.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------
 * 1. LOCATION DATA
 * ------------------------------------------------------------- */

function pb_get_location_data() {
	return array(
		'Minneapolis' => array(
			'city'              => 'Minneapolis',
			'services_label'    => 'Expert Plumber in Minneapolis',
			'address_line1'     => '5720 International Parkway',
			'address_line2'     => 'New Hope, MN 55428',
			'phone_display'     => '612-340-1444',
			'phone_tel'         => '6123401444',
			'booking_url'       => '/minneapolis/service-areas/',
			'url_prefix'        => '/minneapolis',
			'service_urls'      => array(
				'water_heaters'  => '/minneapolis/services/water-heaters/',
				'drain_sewer'    => '/minneapolis/services/drains-sewers/',
				'plumbing'       => '/minneapolis/services/plumbing/',
				'water_quality'  => '/minneapolis/services/water-quality/',
				'leak_detection' => '/minneapolis/services/other-services/leak-repair/',
			),
			'btn_label_phone'   => '(612) 340-1444',
			'btn_label_booking' => 'Book in Minneapolis',
		),
		'Rochester' => array(
			'city'              => 'Rochester',
			'services_label'    => 'Expert Plumber in Rochester',
			'address_line1'     => '4410 19th Street NW',
			'address_line2'     => 'Rochester, MN 55901',
			'phone_display'     => '507-821-3664',
			'phone_tel'         => '5078213664',
			'booking_url'       => '/rochester/service-areas/',
			'url_prefix'        => '/rochester',
			'service_urls'      => array(
				'water_heaters'  => '/rochester/services/water-heaters/',
				'drain_sewer'    => '/rochester/services/drains-sewers/',
				'plumbing'       => '/rochester/services/plumbing/',
				'water_quality'  => '/rochester/services/water-quality/',
				'leak_detection' => '/rochester/services/other-services/leak-repair/',
			),
			'btn_label_phone'   => '(507) 821-3664',
			'btn_label_booking' => 'Book in Rochester',
		),
	);
}

/* ---------------------------------------------------------------
 * 2. HELPER — detect visitor location from URL path, then cookie
 * ------------------------------------------------------------- */

function pb_get_visitor_location() {
	$locations = pb_get_location_data();
	$default   = 'Rochester';

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

// [pb_city]
function pb_city_shortcode() {
	$loc = pb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="city">' . esc_html( $loc['city'] ) . '</span>';
}
add_shortcode( 'pb_city', 'pb_city_shortcode' );

// [pb_services_label]
function pb_services_label_shortcode() {
	$loc = pb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="services">' . esc_html( $loc['services_label'] ) . '</span>';
}
add_shortcode( 'pb_services_label', 'pb_services_label_shortcode' );

// [pb_address]
function pb_address_shortcode() {
	$loc  = pb_get_visitor_location();
	$html = esc_html( $loc['address_line1'] ) . ' <br> <span class="address-line2">' . esc_html( $loc['address_line2'] ) . '</span>';
	return '<span class="loc-dynamic-data" data-field="address">' . wp_kses( $html, array( 'br' => array(), 'span' => array( 'class' => array() ) ) ) . '</span>';
}
add_shortcode( 'pb_address', 'pb_address_shortcode' );

// [pb_phone]
function pb_phone_shortcode() {
	$loc = pb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="phone">' . esc_html( $loc['phone_display'] ) . '</span>';
}
add_shortcode( 'pb_phone', 'pb_phone_shortcode' );

// [pb_phone_link] — raw tel: URI
function pb_phone_link_shortcode() {
	$loc = pb_get_visitor_location();
	return 'tel:' . esc_attr( $loc['phone_tel'] );
}
add_shortcode( 'pb_phone_link', 'pb_phone_link_shortcode' );

// [pb_inline_phone] — inline clickable phone link
function pb_inline_phone_shortcode() {
	$loc = pb_get_visitor_location();
	return '<a href="tel:' . esc_attr( $loc['phone_tel'] ) . '" class="loc-dynamic-data" data-field="phone_inline">' . esc_html( $loc['phone_display'] ) . '</a>';
}
add_shortcode( 'pb_inline_phone', 'pb_inline_phone_shortcode' );

// [csad_phone] — inline clickable phone link (legacy shortcode)
function csad_phone_shortcode() {
	$loc = pb_get_visitor_location();
	return '<a href="tel:' . esc_attr( $loc['phone_tel'] ) . '" class="loc-dynamic-data" data-field="phone_inline">' . esc_html( $loc['phone_display'] ) . '</a>';
}
add_shortcode( 'csad_phone', 'csad_phone_shortcode' );

// [pb_booking_url] — raw URL
function pb_booking_url_shortcode() {
	$loc = pb_get_visitor_location();
	return esc_url( $loc['booking_url'] );
}
add_shortcode( 'pb_booking_url', 'pb_booking_url_shortcode' );

// [pb_service_url service="water_heaters"] — raw URL
function pb_service_url_shortcode( $atts ) {
	$a   = shortcode_atts( array( 'service' => '' ), $atts );
	$loc = pb_get_visitor_location();
	$key = sanitize_key( $a['service'] );
	if ( isset( $loc['service_urls'][ $key ] ) ) {
		return esc_url( $loc['service_urls'][ $key ] );
	}
	return '';
}
add_shortcode( 'pb_service_url', 'pb_service_url_shortcode' );

// [pb_url_prefix] — raw path prefix
function pb_url_prefix_shortcode() {
	$loc = pb_get_visitor_location();
	return esc_attr( $loc['url_prefix'] );
}
add_shortcode( 'pb_url_prefix', 'pb_url_prefix_shortcode' );

// [pb_btn_label_phone]
function pb_btn_label_phone_shortcode() {
	$loc = pb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="btn_label_phone">' . esc_html( $loc['btn_label_phone'] ) . '</span>';
}
add_shortcode( 'pb_btn_label_phone', 'pb_btn_label_phone_shortcode' );

// [pb_btn_label_booking]
function pb_btn_label_booking_shortcode() {
	$loc = pb_get_visitor_location();
	return '<span class="loc-dynamic-data" data-field="btn_label_booking">' . esc_html( $loc['btn_label_booking'] ) . '</span>';
}
add_shortcode( 'pb_btn_label_booking', 'pb_btn_label_booking_shortcode' );

// [loc_data field="city" default="Minneapolis"] — legacy/generic shortcode
function pb_loc_data_shortcode( $atts ) {
	$a   = shortcode_atts( array( 'field' => 'city', 'default' => '' ), $atts );
	$loc = pb_get_visitor_location();

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
add_shortcode( 'loc_data', 'pb_loc_data_shortcode' );

// [loc_button type="phone" text="Call Now"]
function pb_loc_button_shortcode( $atts ) {
	$a = shortcode_atts( array(
		'type'  => 'phone',
		'class' => 'elementor-button-link elementor-button elementor-size-sm',
		'text'  => 'Call Now',
	), $atts );

	$loc = pb_get_visitor_location();

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
add_shortcode( 'loc_button', 'pb_loc_button_shortcode' );

// [pb_location_block] — address block with integrated location switcher.
function pb_location_block_shortcode() {
	$locations = pb_get_location_data();
	$current   = pb_get_visitor_location();

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
add_shortcode( 'pb_location_block', 'pb_location_block_shortcode' );

/* ---------------------------------------------------------------
 * 4. MENU FILTERS — rewrite URLs and add data attributes
 * ------------------------------------------------------------- */

function pb_get_menu_service_map() {
	return array(
		'/services/water-heaters/'   => 'water_heaters',
		'/services/drains-sewers/'   => 'drain_sewer',
		'/services/plumbing/'        => 'plumbing',
		'/services/water-quality/'   => 'water_quality',
	);
}

function pb_get_location_slugs() {
	$locations = pb_get_location_data();
	$slugs     = array();
	foreach ( $locations as $loc ) {
		$slugs[] = trim( $loc['url_prefix'], '/' );
	}
	return $slugs;
}

add_filter( 'wp_nav_menu_objects', 'pb_rewrite_menu_urls', 10, 2 );

function pb_rewrite_menu_urls( $items, $args ) {
	$loc   = pb_get_visitor_location();
	$slugs = pb_get_location_slugs();

	$visitor_prefix = ltrim( $loc['url_prefix'], '/' );

	foreach ( $items as &$item ) {
		$url  = $item->url;
		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( ! $path ) {
			continue;
		}

		foreach ( $slugs as $slug ) {
			if ( strpos( $path, '/' . $slug . '/' ) !== false ) {
				$new_path  = str_replace( '/' . $slug . '/', '/' . $visitor_prefix . '/', $path );
				$item->url = str_replace( $path, $new_path, $url );

				if ( ! in_array( 'loc-dynamic-link', $item->classes, true ) ) {
					$item->classes[] = 'loc-dynamic-link';
				}
				break;
			}
		}
	}

	return $items;
}

add_filter( 'nav_menu_link_attributes', 'pb_add_service_data_attr', 10, 4 );

function pb_add_service_data_attr( $atts, $item, $args, $depth ) {
	$url  = isset( $atts['href'] ) ? $atts['href'] : '';
	$path = wp_parse_url( $url, PHP_URL_PATH );

	if ( ! $path ) {
		return $atts;
	}

	$service_map = pb_get_menu_service_map();
	$slugs       = pb_get_location_slugs();

	$generic_path = $path;
	foreach ( $slugs as $slug ) {
		$generic_path = str_replace( '/' . $slug, '', $generic_path );
	}

	foreach ( $service_map as $service_path => $service_key ) {
		if ( $generic_path === $service_path ) {
			$atts['data-service'] = $service_key;

			$existing = isset( $atts['class'] ) ? $atts['class'] : '';
			if ( strpos( $existing, 'loc-dynamic-link' ) === false ) {
				$atts['class'] = trim( $existing . ' loc-dynamic-link' );
			}
			break;
		}
	}

	return $atts;
}
