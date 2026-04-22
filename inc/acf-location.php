<?php
/**
 * ACF Field Group: Location Page Fields
 *
 * Registered via PHP so the field group lives in git rather than as a
 * JSON export or database record. Fields appear only on pages that use
 * the "Location Page" page template (template-location.php).
 *
 * SEO fields (meta title, meta description, OG fields, focus keyword)
 * are NOT registered here — Rank Math Pro manages those natively in its
 * own meta box. The importer writes to Rank Math's post meta keys directly.
 *
 * Field key prefix: field_pbloc_
 *
 * Tabs and fields (in order):
 *   Hero        — h1 (req), hero_subheadline, hero_image, hero_alt
 *   Intro       — intro_paragraph
 *   Body        — section_{1-4}_h2 / section_{1-4}_body (fixed pairs, not repeater)
 *   FAQ         — faq_{1-3}_question / faq_{1-3}_answer (fixed named fields)
 *   Testimonials— testimonial_{1-3}_quote / testimonial_{1-3}_author (fixed named fields)
 *   Images      — image_2, image_2_alt, image_3, image_3_alt
 *   CTA         — primary_cta_text
 *   Schema      — schema_type (select)
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'paulbunyan_register_location_field_group' );

function paulbunyan_register_location_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'                   => 'group_pbloc_location',
		'title'                 => 'Location Page Fields',
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,

		// Show only on pages using the Location Page template.
		'location' => array(
			array(
				array(
					'param'    => 'page_template',
					'operator' => '==',
					'value'    => 'template-location.php',
				),
			),
		),

		'fields' => array(

			// ----------------------------------------------------------------
			// TAB: Hero
			// ----------------------------------------------------------------
			array(
				'key'   => 'field_pbloc_tab_hero',
				'label' => 'Hero',
				'name'  => '',
				'type'  => 'tab',
			),
			array(
				'key'          => 'field_pbloc_h1',
				'label'        => 'H1 Heading',
				'name'         => 'h1',
				'type'         => 'text',
				'required'     => 1,
				'instructions' => 'Required. Pulled into the page as the H1 via Elementor Dynamic Tag.',
				'wrapper'      => array( 'width' => '100' ),
			),
			array(
				'key'          => 'field_pbloc_hero_subheadline',
				'label'        => 'Hero Subheadline',
				'name'         => 'hero_subheadline',
				'type'         => 'textarea',
				'rows'         => 3,
				'new_lines'    => 'br',
				'wrapper'      => array( 'width' => '100' ),
			),
			array(
				'key'           => 'field_pbloc_hero_image',
				'label'         => 'Hero Image',
				'name'          => 'hero_image',
				'type'          => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'all',
				'instructions'  => 'Populated by the importer from {slug}-hero.webp.',
				'wrapper'       => array( 'width' => '50' ),
			),
			array(
				'key'      => 'field_pbloc_hero_alt',
				'label'    => 'Hero Image Alt Text',
				'name'     => 'hero_alt',
				'type'     => 'text',
				'instructions' => 'Also written to the WP attachment alt text field by the importer.',
				'wrapper'  => array( 'width' => '50' ),
			),

			// ----------------------------------------------------------------
			// TAB: Intro
			// ----------------------------------------------------------------
			array(
				'key'   => 'field_pbloc_tab_intro',
				'label' => 'Intro',
				'name'  => '',
				'type'  => 'tab',
			),
			array(
				'key'          => 'field_pbloc_intro_paragraph',
				'label'        => 'Intro Paragraph',
				'name'         => 'intro_paragraph',
				'type'         => 'wysiwyg',
				'toolbar'      => 'full',
				'media_upload' => 0,
			),

			// ----------------------------------------------------------------
			// TAB: Body Sections
			// ----------------------------------------------------------------
			// Fixed named pairs rather than a repeater so Elementor Pro
			// Dynamic Tags can bind directly to each field by name.
			array(
				'key'   => 'field_pbloc_tab_body',
				'label' => 'Body Sections',
				'name'  => '',
				'type'  => 'tab',
			),

			// Section 1 (required)
			array(
				'key'      => 'field_pbloc_section_1_h2',
				'label'    => 'Section 1 Heading',
				'name'     => 'section_1_h2',
				'type'     => 'text',
				'required' => 1,
				'wrapper'  => array( 'width' => '100' ),
			),
			array(
				'key'          => 'field_pbloc_section_1_body',
				'label'        => 'Section 1 Body',
				'name'         => 'section_1_body',
				'type'         => 'wysiwyg',
				'toolbar'      => 'full',
				'media_upload' => 0,
				'required'     => 1,
			),

			// Section 2 (required)
			array(
				'key'      => 'field_pbloc_section_2_h2',
				'label'    => 'Section 2 Heading',
				'name'     => 'section_2_h2',
				'type'     => 'text',
				'required' => 1,
				'wrapper'  => array( 'width' => '100' ),
			),
			array(
				'key'          => 'field_pbloc_section_2_body',
				'label'        => 'Section 2 Body',
				'name'         => 'section_2_body',
				'type'         => 'wysiwyg',
				'toolbar'      => 'full',
				'media_upload' => 0,
				'required'     => 1,
			),

			// Section 3 (required)
			array(
				'key'      => 'field_pbloc_section_3_h2',
				'label'    => 'Section 3 Heading',
				'name'     => 'section_3_h2',
				'type'     => 'text',
				'required' => 1,
				'wrapper'  => array( 'width' => '100' ),
			),
			array(
				'key'          => 'field_pbloc_section_3_body',
				'label'        => 'Section 3 Body',
				'name'         => 'section_3_body',
				'type'         => 'wysiwyg',
				'toolbar'      => 'full',
				'media_upload' => 0,
				'required'     => 1,
			),

			// Section 4 (optional)
			array(
				'key'      => 'field_pbloc_section_4_h2',
				'label'    => 'Section 4 Heading',
				'name'     => 'section_4_h2',
				'type'     => 'text',
				'wrapper'  => array( 'width' => '100' ),
			),
			array(
				'key'          => 'field_pbloc_section_4_body',
				'label'        => 'Section 4 Body',
				'name'         => 'section_4_body',
				'type'         => 'wysiwyg',
				'toolbar'      => 'full',
				'media_upload' => 0,
			),

			// ----------------------------------------------------------------
			// TAB: FAQ
			// ----------------------------------------------------------------
			array(
				'key'   => 'field_pbloc_tab_faq',
				'label' => 'FAQ',
				'name'  => '',
				'type'  => 'tab',
			),

			// FAQ 1
			array(
				'key'     => 'field_pbloc_faq_1_question',
				'label'   => 'FAQ 1 Question',
				'name'    => 'faq_1_question',
				'type'    => 'text',
				'wrapper' => array( 'width' => '100' ),
			),
			array(
				'key'      => 'field_pbloc_faq_1_answer',
				'label'    => 'FAQ 1 Answer',
				'name'     => 'faq_1_answer',
				'type'     => 'textarea',
				'rows'     => 4,
				'new_lines' => 'br',
				'wrapper'  => array( 'width' => '100' ),
			),

			// FAQ 2
			array(
				'key'     => 'field_pbloc_faq_2_question',
				'label'   => 'FAQ 2 Question',
				'name'    => 'faq_2_question',
				'type'    => 'text',
				'wrapper' => array( 'width' => '100' ),
			),
			array(
				'key'      => 'field_pbloc_faq_2_answer',
				'label'    => 'FAQ 2 Answer',
				'name'     => 'faq_2_answer',
				'type'     => 'textarea',
				'rows'     => 4,
				'new_lines' => 'br',
				'wrapper'  => array( 'width' => '100' ),
			),

			// FAQ 3
			array(
				'key'     => 'field_pbloc_faq_3_question',
				'label'   => 'FAQ 3 Question',
				'name'    => 'faq_3_question',
				'type'    => 'text',
				'wrapper' => array( 'width' => '100' ),
			),
			array(
				'key'      => 'field_pbloc_faq_3_answer',
				'label'    => 'FAQ 3 Answer',
				'name'     => 'faq_3_answer',
				'type'     => 'textarea',
				'rows'     => 4,
				'new_lines' => 'br',
				'wrapper'  => array( 'width' => '100' ),
			),

			// ----------------------------------------------------------------
			// TAB: Testimonials
			// ----------------------------------------------------------------
			array(
				'key'   => 'field_pbloc_tab_testimonials',
				'label' => 'Testimonials',
				'name'  => '',
				'type'  => 'tab',
			),

			// Testimonial 1
			array(
				'key'      => 'field_pbloc_testimonial_1_quote',
				'label'    => 'Testimonial 1 Quote',
				'name'     => 'testimonial_1_quote',
				'type'     => 'textarea',
				'rows'     => 4,
				'new_lines' => 'br',
				'wrapper'  => array( 'width' => '60' ),
			),
			array(
				'key'     => 'field_pbloc_testimonial_1_author',
				'label'   => 'Testimonial 1 Author',
				'name'    => 'testimonial_1_author',
				'type'    => 'text',
				'wrapper' => array( 'width' => '40' ),
			),

			// Testimonial 2
			array(
				'key'      => 'field_pbloc_testimonial_2_quote',
				'label'    => 'Testimonial 2 Quote',
				'name'     => 'testimonial_2_quote',
				'type'     => 'textarea',
				'rows'     => 4,
				'new_lines' => 'br',
				'wrapper'  => array( 'width' => '60' ),
			),
			array(
				'key'     => 'field_pbloc_testimonial_2_author',
				'label'   => 'Testimonial 2 Author',
				'name'    => 'testimonial_2_author',
				'type'    => 'text',
				'wrapper' => array( 'width' => '40' ),
			),

			// Testimonial 3
			array(
				'key'      => 'field_pbloc_testimonial_3_quote',
				'label'    => 'Testimonial 3 Quote',
				'name'     => 'testimonial_3_quote',
				'type'     => 'textarea',
				'rows'     => 4,
				'new_lines' => 'br',
				'wrapper'  => array( 'width' => '60' ),
			),
			array(
				'key'     => 'field_pbloc_testimonial_3_author',
				'label'   => 'Testimonial 3 Author',
				'name'    => 'testimonial_3_author',
				'type'    => 'text',
				'wrapper' => array( 'width' => '40' ),
			),

			// ----------------------------------------------------------------
			// TAB: Images
			// ----------------------------------------------------------------
			array(
				'key'   => 'field_pbloc_tab_images',
				'label' => 'Images',
				'name'  => '',
				'type'  => 'tab',
			),
			array(
				'key'           => 'field_pbloc_image_2',
				'label'         => 'Image 2',
				'name'          => 'image_2',
				'type'          => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'all',
				'instructions'  => 'Populated by the importer from {slug}-2.webp.',
				'wrapper'       => array( 'width' => '50' ),
			),
			array(
				'key'      => 'field_pbloc_image_2_alt',
				'label'    => 'Image 2 Alt Text',
				'name'     => 'image_2_alt',
				'type'     => 'text',
				'wrapper'  => array( 'width' => '50' ),
			),
			array(
				'key'           => 'field_pbloc_image_3',
				'label'         => 'Image 3',
				'name'          => 'image_3',
				'type'          => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'all',
				'instructions'  => 'Populated by the importer from {slug}.webp (Images 3AN folder).',
				'wrapper'       => array( 'width' => '50' ),
			),
			array(
				'key'      => 'field_pbloc_image_3_alt',
				'label'    => 'Image 3 Alt Text',
				'name'     => 'image_3_alt',
				'type'     => 'text',
				'wrapper'  => array( 'width' => '50' ),
			),

			// ----------------------------------------------------------------
			// TAB: CTA
			// ----------------------------------------------------------------
			array(
				'key'   => 'field_pbloc_tab_cta',
				'label' => 'CTA',
				'name'  => '',
				'type'  => 'tab',
			),
			array(
				'key'     => 'field_pbloc_primary_cta_text',
				'label'   => 'Primary CTA Text',
				'name'    => 'primary_cta_text',
				'type'    => 'text',
				'wrapper' => array( 'width' => '100' ),
			),

			// ----------------------------------------------------------------
			// TAB: Schema
			// ----------------------------------------------------------------
			array(
				'key'   => 'field_pbloc_tab_schema',
				'label' => 'Schema',
				'name'  => '',
				'type'  => 'tab',
			),
			array(
				'key'           => 'field_pbloc_schema_type',
				'label'         => 'Schema Type',
				'name'          => 'schema_type',
				'type'          => 'select',
				'choices'       => array(
					'LocalBusiness' => 'LocalBusiness',
					'Plumber'       => 'Plumber',
				),
				'default_value' => 'Plumber',
				'allow_null'    => 0,
				'multiple'      => 0,
				'ui'            => 1,
				'ajax'          => 0,
				'instructions'  => 'Used by the JSON-LD schema block.',
				'wrapper'       => array( 'width' => '40' ),
			),

		), // end fields
	) );
}
