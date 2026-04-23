<?php
/**
 * EarlyBird Location Importer — Core Class
 *
 * Shared logic used by both the WP-CLI command (cli-location-import.php)
 * and the Admin UI (admin-location-import.php). Instantiate the class,
 * pass args, call run(), read back the log and stats arrays.
 *
 * Creates/updates standard WordPress pages (post_type = 'page') with:
 *   - 'Location Page' page template (_wp_page_template)
 *   - All ACF fields written via update_field()
 *   - Images sideloaded from a local --images-dir folder
 *   - Rank Math meta keys written directly via update_post_meta()
 *
 * Column positions are detected automatically by reading the CSV header row
 * (row 2). The class constants define the EarlyBird fallback positions.
 *
 * EarlyBird CSV: 57 columns. A "CTA Image" column at AG (col 32) does not
 * exist in Blue Ox or Paul Bunyan — this shifts all image columns right by 1
 * starting at AG. Dynamic header detection handles this automatically.
 *
 * Image notes:
 *   - hero_image: {slug}-hero.webp
 *   - image_2:    {slug}-2.webp
 *   - image_3:    {slug}.webp  (no suffix — just the slug)
 *   - All three images live in the single --images-dir folder.
 *   - Fallback image attachment IDs must be set via the FALLBACK_*_ID constants
 *     after uploading a fallback image to the media library.
 *
 * Idempotent: keyed on page slug. Re-running produces zero changes when
 * content has not changed. Images are cached by attachment ID in post meta
 * so they are never uploaded twice.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EarlyBird_Location_Importer {

	// -------------------------------------------------------------------------
	// Fallback column indices (0-based) — EarlyBird CSV layout (57 columns).
	// Image columns are +1 vs Paul Bunyan due to CTA Image column at AG (32).
	// -------------------------------------------------------------------------

	const COL_PAGE_TYPE  = 0;  // A  — filter: keep "Location" rows only
	const COL_TITLE      = 2;  // C  — Location Name → WP post title
	const COL_SLUG       = 3;  // D  — URL Slug (extract last path segment)
	const COL_FOCUS_KW   = 4;  // E  — Primary Keyword   → rank_math_focus_keyword
	const COL_KEYWORDS   = 5;  // F  — Secondary Keywords → rank_math_keywords
	const COL_RANK_TITLE = 10; // K  — Title Tag           → rank_math_title
	const COL_RANK_DESC  = 11; // L  — Meta Description    → rank_math_description
	const COL_H1         = 13; // N  — H1 Heading (required; missing = placeholder)
	const COL_HERO_SUB   = 14; // O  — Hero Subheadline
	const COL_INTRO      = 15; // P  — Intro Paragraph
	const COL_S1_H2      = 16; // Q  — Section 1 Heading (required)
	const COL_S1_BODY    = 17; // R  — Section 1 Body    (required)
	const COL_S2_H2      = 18; // S  — Section 2 Heading (required)
	const COL_S2_BODY    = 19; // T  — Section 2 Body    (required)
	const COL_S3_H2      = 20; // U  — Section 3 Heading (required)
	const COL_S3_BODY    = 21; // V  — Section 3 Body    (required)
	const COL_S4_H2      = 22; // W  — Section 4 Heading (optional)
	const COL_S4_BODY    = 23; // X  — Section 4 Body    (optional)
	const COL_FAQ1_Q     = 24; // Y  — FAQ Q1
	const COL_FAQ1_A     = 25; // Z  — FAQ A1
	const COL_FAQ2_Q     = 26; // AA — FAQ Q2
	const COL_FAQ2_A     = 27; // AB — FAQ A2
	const COL_FAQ3_Q     = 28; // AC — FAQ Q3
	const COL_FAQ3_A     = 29; // AD — FAQ A3
	const COL_CTA_TEXT   = 30; // AE — Primary CTA Text
	const COL_PHONE      = 33; // AH — Phone # Display → phone_display ACF field
	// AG=32 is CTA Image (new vs Blue Ox) — shifts all subsequent image cols +1
	const COL_HERO_IMG   = 34; // AI — Hero Image (shifted from AH/33 in Paul Bunyan)
	const COL_HERO_ALT   = 35; // AJ — Hero Image Alt Text
	const COL_IMG2       = 36; // AK — Image 2
	const COL_IMG2_ALT   = 37; // AL — Image 2 Alt Text
	const COL_IMG3_ALT   = 39; // AN — Image 3 Alt Text
	// AM=38 is Image 3 URL — empty for all EarlyBird rows; image_3 uses {slug}.webp
	const COL_OG_TITLE   = 43; // AR — OG Title        → rank_math_og_title
	const COL_OG_DESC    = 44; // AS — OG Description  → rank_math_og_description
	const COL_REVIEW1    = 45; // AT — Review 1
	const COL_REVIEW2    = 46; // AU — Review 2
	const COL_REVIEW3    = 47; // AV — Review 3

	/** Schema type is always Electrician for EarlyBird — not read from CSV. */
	const SCHEMA_TYPE = 'Electrician';

	/**
	 * Slugs that already exist as standard WP pages that must not be touched.
	 * Add any protected slugs here before running the importer.
	 * Check WP Admin for existing pages that conflict with location slugs.
	 */
	const PROTECTED_SLUGS = [];

	/** Page template filename (relative to child theme root). */
	const PAGE_TEMPLATE = 'template-location.php';

	/** Minimum column padding applied before processing each data row. */
	const MIN_COLS = 57;

	/**
	 * Fallback attachment IDs used when a location's own image file is missing.
	 * Set these to the attachment IDs of fallback images uploaded to the media
	 * library. 0 means no fallback — the field will be skipped on missing images.
	 *
	 * To find an attachment ID: WP Admin → Media Library → open the image →
	 * read the post= number from the URL.
	 */
	const FALLBACK_HERO_ID   = 14967; // fallback hero image
	const FALLBACK_IMAGE2_ID = 14966; // fallback image 2
	const FALLBACK_IMAGE3_ID = 14965; // fallback image 3

	// -------------------------------------------------------------------------
	// Instance state
	// -------------------------------------------------------------------------

	/** @var string */
	private $csv_file;

	/** @var string */
	private $images_dir;

	/** @var bool */
	private $dry_run;

	/** @var string */
	private $only_slug;

	/**
	 * Active column index map. Seeded from class constants in __construct()
	 * and overridden by detect_columns() after the CSV header row is read.
	 *
	 * @var array<string,int>
	 */
	private $col = [];

	/** @var array */
	private $log = [];

	/** @var array */
	private $stats = [
		'created'             => 0,
		'updated'             => 0,
		'skipped_placeholder' => 0,
		'skipped_conflict'    => 0,
		'skipped_incomplete'  => 0,
		'error'               => 0,
	];

	/**
	 * @param array $args {
	 *   @type string $file       Absolute path to the CSV file.
	 *   @type string $images_dir Path to local images folder.
	 *   @type bool   $dry_run    If true, no writes are made.
	 *   @type string $only       If set, only process the row with this slug.
	 * }
	 */
	public function __construct( array $args ) {
		$this->csv_file   = $args['file']       ?? '';
		$this->images_dir = rtrim( $args['images_dir'] ?? '', '/\\' );
		$this->dry_run    = ! empty( $args['dry_run'] );
		$this->only_slug  = sanitize_title( $args['only'] ?? '' );

		// Seed column map from constants. detect_columns() will override these
		// once the actual CSV header row is parsed in run().
		$this->col = [
			'COL_PAGE_TYPE'  => self::COL_PAGE_TYPE,
			'COL_TITLE'      => self::COL_TITLE,
			'COL_SLUG'       => self::COL_SLUG,
			'COL_FOCUS_KW'   => self::COL_FOCUS_KW,
			'COL_KEYWORDS'   => self::COL_KEYWORDS,
			'COL_RANK_TITLE' => self::COL_RANK_TITLE,
			'COL_RANK_DESC'  => self::COL_RANK_DESC,
			'COL_H1'         => self::COL_H1,
			'COL_HERO_SUB'   => self::COL_HERO_SUB,
			'COL_INTRO'      => self::COL_INTRO,
			'COL_S1_H2'      => self::COL_S1_H2,
			'COL_S1_BODY'    => self::COL_S1_BODY,
			'COL_S2_H2'      => self::COL_S2_H2,
			'COL_S2_BODY'    => self::COL_S2_BODY,
			'COL_S3_H2'      => self::COL_S3_H2,
			'COL_S3_BODY'    => self::COL_S3_BODY,
			'COL_S4_H2'      => self::COL_S4_H2,
			'COL_S4_BODY'    => self::COL_S4_BODY,
			'COL_FAQ1_Q'     => self::COL_FAQ1_Q,
			'COL_FAQ1_A'     => self::COL_FAQ1_A,
			'COL_FAQ2_Q'     => self::COL_FAQ2_Q,
			'COL_FAQ2_A'     => self::COL_FAQ2_A,
			'COL_FAQ3_Q'     => self::COL_FAQ3_Q,
			'COL_FAQ3_A'     => self::COL_FAQ3_A,
			'COL_CTA_TEXT'   => self::COL_CTA_TEXT,
			'COL_PHONE'      => self::COL_PHONE,
			'COL_HERO_ALT'   => self::COL_HERO_ALT,
			'COL_IMG2_ALT'   => self::COL_IMG2_ALT,
			'COL_IMG3_ALT'   => self::COL_IMG3_ALT,
			'COL_OG_TITLE'   => self::COL_OG_TITLE,
			'COL_OG_DESC'    => self::COL_OG_DESC,
			'COL_REVIEW1'    => self::COL_REVIEW1,
			'COL_REVIEW2'    => self::COL_REVIEW2,
			'COL_REVIEW3'    => self::COL_REVIEW3,
		];
	}

	// -------------------------------------------------------------------------
	// Public entry point
	// -------------------------------------------------------------------------

	/**
	 * Run the import. Returns log and stats.
	 *
	 * @return array{ log: array, stats: array }
	 */
	public function run(): array {
		if ( empty( $this->csv_file ) || ! file_exists( $this->csv_file ) ) {
			$this->log_result( 0, '', 'error', 'CSV file not found: ' . $this->csv_file );
			return $this->result();
		}

		$fh = fopen( $this->csv_file, 'r' );
		if ( ! $fh ) {
			$this->log_result( 0, '', 'error', 'Cannot open CSV: ' . $this->csv_file );
			return $this->result();
		}

		// Strip UTF-8 BOM if present (common in Excel-exported CSVs).
		$bom = fread( $fh, 3 );
		if ( $bom !== "\xEF\xBB\xBF" ) {
			rewind( $fh );
		}

		$row_num = 0;
		while ( ( $cols = fgetcsv( $fh, 0, ',' ) ) !== false ) {
			$row_num++;

			// Row 2 is the column header row — detect column positions from it.
			if ( $row_num === 2 ) {
				$this->detect_columns( $cols );
			}

			// Skip first 3 rows: group headers, column headers, example row.
			if ( $row_num <= 3 ) {
				continue;
			}

			$this->process_row( $cols, $row_num );
		}

		fclose( $fh );
		return $this->result();
	}

	// -------------------------------------------------------------------------
	// Column detection
	// -------------------------------------------------------------------------

	/**
	 * Read the CSV header row and override $this->col with detected indices.
	 *
	 * Uses case-insensitive substring matching. If a header is not found the
	 * constant fallback seeded in __construct() is preserved.
	 *
	 * Special case: "Location Specific Review 1" appears twice in the CSV.
	 *   First occurrence  → COL_REVIEW1
	 *   Second occurrence → COL_REVIEW3
	 *
	 * @param array $header_row  Row 2 of the CSV (0-based column indices).
	 */
	private function detect_columns( array $header_row ): void {
		$patterns = [
			'COL_PAGE_TYPE'  => 'page type',
			'COL_TITLE'      => 'location name',
			'COL_SLUG'       => 'url slug',
			'COL_FOCUS_KW'   => 'primary keyword',
			'COL_KEYWORDS'   => 'secondary keyword',
			'COL_RANK_TITLE' => 'title tag',
			'COL_RANK_DESC'  => 'meta description',
			'COL_H1'         => 'h1 heading',
			'COL_HERO_SUB'   => 'hero subheadline',
			'COL_INTRO'      => 'intro paragraph',
			'COL_S1_H2'      => 'section 1 heading',
			'COL_S1_BODY'    => 'section 1 body',
			'COL_S2_H2'      => 'section 2 heading',
			'COL_S2_BODY'    => 'section 2 body',
			'COL_S3_H2'      => 'section 3 heading',
			'COL_S3_BODY'    => 'section 3 body',
			'COL_S4_H2'      => 'section 4 heading',
			'COL_S4_BODY'    => 'section 4 body',
			'COL_FAQ1_Q'     => 'faq q1',
			'COL_FAQ1_A'     => 'faq a1',
			'COL_FAQ2_Q'     => 'faq q2',
			'COL_FAQ2_A'     => 'faq a2',
			'COL_FAQ3_Q'     => 'faq q3',
			'COL_FAQ3_A'     => 'faq a3',
			'COL_CTA_TEXT'   => 'primary cta',
			'COL_PHONE'      => 'phone',
			'COL_HERO_ALT'   => 'hero image alt',
			'COL_IMG2_ALT'   => 'image 2 alt',
			'COL_IMG3_ALT'   => 'image 3 alt',
			'COL_OG_TITLE'   => 'og title',
			'COL_OG_DESC'    => 'og description',
		];

		// First pass: all uniquely-named headers.
		foreach ( $header_row as $idx => $cell ) {
			$lower = strtolower( trim( $cell ) );
			foreach ( $patterns as $key => $needle ) {
				if ( str_contains( $lower, $needle ) ) {
					$this->col[ $key ] = $idx;
					unset( $patterns[ $key ] );
				}
			}
		}

		// Second pass: "Location Specific Review 1" appears twice in the CSV.
		// First occurrence  → COL_REVIEW1
		// Second occurrence → COL_REVIEW3
		$review1_hits = 0;
		foreach ( $header_row as $idx => $cell ) {
			$lower = strtolower( trim( $cell ) );
			if ( str_contains( $lower, 'location specific review 1' ) ) {
				if ( 0 === $review1_hits ) {
					$this->col['COL_REVIEW1'] = $idx;
				} else {
					$this->col['COL_REVIEW3'] = $idx;
				}
				$review1_hits++;
			} elseif ( str_contains( $lower, 'location specific review 2' ) ) {
				$this->col['COL_REVIEW2'] = $idx;
			}
		}
	}

	// -------------------------------------------------------------------------
	// Row processing
	// -------------------------------------------------------------------------

	private function process_row( array $cols, int $row_num ): void {
		// Pad to the greater of MIN_COLS or one beyond the highest detected index.
		$cols = array_pad( $cols, max( self::MIN_COLS, max( $this->col ) + 1 ), '' );

		$c = $this->col;

		// Skip rows that are not of type "Location".
		if ( 'Location' !== trim( $cols[ $c['COL_PAGE_TYPE'] ] ) ) {
			return;
		}

		$title    = trim( $cols[ $c['COL_TITLE'] ] );
		$slug_raw = trim( $cols[ $c['COL_SLUG'] ] );

		// Extract the last path segment from the URL in the slug column.
		// e.g. "https://earlybirdelectricians.com/saint-paul/" → "saint-paul"
		$parts = array_values( array_filter( explode( '/', $slug_raw ) ) );
		$slug  = sanitize_title( end( $parts ) ?: $slug_raw );

		if ( empty( $slug ) || empty( $title ) ) {
			$this->log_result( $row_num, $slug ?: '?', 'skipped-incomplete', 'Missing title or slug' );
			$this->stats['skipped_incomplete']++;
			return;
		}

		// Honour --only filter.
		if ( $this->only_slug && $slug !== $this->only_slug ) {
			return;
		}

		// Protected slugs must never be created or overwritten.
		if ( in_array( $slug, self::PROTECTED_SLUGS, true ) ) {
			$this->log_result( $row_num, $slug, 'skipped-conflict', 'Protected slug — existing WP page must not be overwritten' );
			$this->stats['skipped_conflict']++;
			return;
		}

		$h1 = trim( $cols[ $c['COL_H1'] ] );

		// Empty H1 means the row is a placeholder awaiting copywriting.
		if ( empty( $h1 ) ) {
			$this->log_result( $row_num, $slug, 'skipped-placeholder', 'No H1 — awaiting content' );
			$this->stats['skipped_placeholder']++;
			return;
		}

		// Validate required content fields.
		$missing = [];
		foreach ( [
			'Section 1 heading' => $c['COL_S1_H2'],
			'Section 1 body'    => $c['COL_S1_BODY'],
			'Section 2 heading' => $c['COL_S2_H2'],
			'Section 2 body'    => $c['COL_S2_BODY'],
			'Section 3 heading' => $c['COL_S3_H2'],
			'Section 3 body'    => $c['COL_S3_BODY'],
		] as $label => $idx ) {
			if ( empty( trim( $cols[ $idx ] ) ) ) {
				$missing[] = $label;
			}
		}
		if ( $missing ) {
			$this->log_result( $row_num, $slug, 'skipped-incomplete', 'Missing required fields: ' . implode( ', ', $missing ) );
			$this->stats['skipped_incomplete']++;
			return;
		}

		// Check whether a page with this slug already exists.
		$existing = get_posts( [
			'post_type'              => 'page',
			'name'                   => $slug,
			'post_status'            => [ 'publish', 'draft', 'pending' ],
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

		$is_update = ! empty( $existing );
		$post_id   = $is_update ? (int) $existing[0] : 0;

		// --- Dry run: report and check images, make no writes ---
		if ( $this->dry_run ) {
			$action = $is_update ? 'would-update' : 'would-create';
			$this->log_result( $row_num, $slug, $action, $title );
			$this->check_images_exist( $slug, $row_num );
			return;
		}

		// --- Create or update the WP page ---
		$post_data = [
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_status' => 'publish',
			'post_type'   => 'page',
		];

		if ( $is_update ) {
			$post_data['ID'] = $post_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			$this->log_result( $row_num, $slug, 'error', $result->get_error_message() );
			$this->stats['error']++;
			return;
		}

		$post_id = (int) $result;

		// Assign the Location Page template.
		update_post_meta( $post_id, '_wp_page_template', self::PAGE_TEMPLATE );

		// ACF content fields.
		if ( function_exists( 'update_field' ) ) {
			$this->update_acf_fields( $post_id, $cols, $slug );
		}

		// Rank Math SEO meta keys.
		$this->update_rank_math_meta( $post_id, $cols );

		$status = $is_update ? 'updated' : 'created';
		$this->log_result( $row_num, $slug, $status, $title );
		$this->stats[ $status ]++;
	}

	// -------------------------------------------------------------------------
	// ACF fields
	// -------------------------------------------------------------------------

	private function update_acf_fields( int $post_id, array $cols, string $slug ): void {
		$c = $this->col;

		// --- Hero tab ---
		update_field( 'h1',               trim( $cols[ $c['COL_H1'] ] ),       $post_id );
		update_field( 'hero_subheadline', trim( $cols[ $c['COL_HERO_SUB'] ] ), $post_id );
		update_field( 'hero_alt',         trim( $cols[ $c['COL_HERO_ALT'] ] ), $post_id );

		// --- Intro tab ---
		update_field( 'intro_paragraph', trim( $cols[ $c['COL_INTRO'] ] ), $post_id );

		// --- Body sections tab ---
		update_field( 'section_1_h2',   trim( $cols[ $c['COL_S1_H2'] ] ),   $post_id );
		update_field( 'section_1_body', trim( $cols[ $c['COL_S1_BODY'] ] ), $post_id );
		update_field( 'section_2_h2',   trim( $cols[ $c['COL_S2_H2'] ] ),   $post_id );
		update_field( 'section_2_body', trim( $cols[ $c['COL_S2_BODY'] ] ), $post_id );
		update_field( 'section_3_h2',   trim( $cols[ $c['COL_S3_H2'] ] ),   $post_id );
		update_field( 'section_3_body', trim( $cols[ $c['COL_S3_BODY'] ] ), $post_id );
		update_field( 'section_4_h2',   trim( $cols[ $c['COL_S4_H2'] ] ),   $post_id );
		update_field( 'section_4_body', trim( $cols[ $c['COL_S4_BODY'] ] ), $post_id );

		// --- CTA tab ---
		update_field( 'primary_cta_text', trim( $cols[ $c['COL_CTA_TEXT'] ] ), $post_id );
		$phone_value = trim( $cols[ $c['COL_PHONE'] ] );
		update_field( 'phone_display', $phone_value,                                 $post_id );
		update_field( 'phone_tel',     'tel:' . preg_replace( '/[^0-9]/', '', $phone_value ), $post_id );

		// --- FAQ fields (fixed named, not repeater) ---
		update_field( 'faq_1_question', trim( $cols[ $c['COL_FAQ1_Q'] ] ), $post_id );
		update_field( 'faq_1_answer',   trim( $cols[ $c['COL_FAQ1_A'] ] ), $post_id );
		update_field( 'faq_2_question', trim( $cols[ $c['COL_FAQ2_Q'] ] ), $post_id );
		update_field( 'faq_2_answer',   trim( $cols[ $c['COL_FAQ2_A'] ] ), $post_id );
		update_field( 'faq_3_question', trim( $cols[ $c['COL_FAQ3_Q'] ] ), $post_id );
		update_field( 'faq_3_answer',   trim( $cols[ $c['COL_FAQ3_A'] ] ), $post_id );

		// --- Testimonial fields (fixed named, not repeater) ---
		$t1 = $this->parse_testimonial( trim( $cols[ $c['COL_REVIEW1'] ] ) );
		$t2 = $this->parse_testimonial( trim( $cols[ $c['COL_REVIEW2'] ] ) );
		$t3 = $this->parse_testimonial( trim( $cols[ $c['COL_REVIEW3'] ] ) );
		update_field( 'testimonial_1_quote',  $t1['quote'],  $post_id );
		update_field( 'testimonial_1_author', $t1['author'], $post_id );
		update_field( 'testimonial_2_quote',  $t2['quote'],  $post_id );
		update_field( 'testimonial_2_author', $t2['author'], $post_id );
		update_field( 'testimonial_3_quote',  $t3['quote'],  $post_id );
		update_field( 'testimonial_3_author', $t3['author'], $post_id );

		// --- Image fields ---
		$this->update_image_fields( $post_id, $slug, $cols );
	}

	// -------------------------------------------------------------------------
	// Image fields
	// -------------------------------------------------------------------------

	private function update_image_fields( int $post_id, string $slug, array $cols ): void {
		if ( empty( $this->images_dir ) ) {
			return;
		}

		$c = $this->col;

	$images = [
		'hero_image' => [
			'filename'    => "{$slug}-hero.webp",
			'alt'         => trim( $cols[ $c['COL_HERO_ALT'] ] ),
			'alt_field'   => 'hero_alt',
			'cache_key'   => "_eb_img_hero_{$slug}",
			'fallback_id' => self::FALLBACK_HERO_ID,
		],
		'image_2' => [
			'filename'    => "{$slug}-2.webp",
			'alt'         => trim( $cols[ $c['COL_IMG2_ALT'] ] ),
			'alt_field'   => 'image_2_alt',
			'cache_key'   => "_eb_img_2_{$slug}",
			'fallback_id' => self::FALLBACK_IMAGE2_ID,
		],
		'image_3' => [
			'filename'    => "{$slug}.webp",
			'alt'         => trim( $cols[ $c['COL_IMG3_ALT'] ] ),
			'alt_field'   => 'image_3_alt',
			'cache_key'   => "_eb_img_3_{$slug}",
			'fallback_id' => self::FALLBACK_IMAGE3_ID,
		],
	];

		foreach ( $images as $acf_field => $image ) {
			$file_path = $this->images_dir . '/' . $image['filename'];
			$result    = $this->sideload_image( $file_path, $post_id, $image['alt'], $image['cache_key'] );

			if ( is_wp_error( $result ) ) {
				if ( 'file_not_found' === $result->get_error_code() && $image['fallback_id'] > 0 ) {
					$this->log_result( 0, $slug, 'warn', "Image not found ({$image['filename']}), using fallback ID {$image['fallback_id']}" );
					$result = $image['fallback_id'];
				} else {
					$this->log_result( 0, $slug, 'warn', "Image skipped ({$image['filename']}): " . $result->get_error_message() );
					continue;
				}
			}

			if ( $result > 0 ) {
				update_field( $acf_field,         $result,       $post_id );
				update_field( $image['alt_field'], $image['alt'], $post_id );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Image sideloading
	// -------------------------------------------------------------------------

	/**
	 * Sideload a local image file into the WP media library.
	 *
	 * Uses a post meta cache key so re-running the importer never re-uploads
	 * an image that is already in the library for this post.
	 *
	 * @return int|WP_Error  Attachment ID on success, WP_Error on failure.
	 */
	private function sideload_image( string $file_path, int $post_id, string $alt, string $cache_key ) {
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', "File not found: {$file_path}" );
		}

		// Return cached attachment ID to skip re-uploading on re-runs.
		$cached_id = (int) get_post_meta( $post_id, $cache_key, true );
		if ( $cached_id > 0 && get_post( $cached_id ) ) {
			update_post_meta( $cached_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
			return $cached_id;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// Copy to a temp file so WP does not delete the original from --images-dir.
		$tmp = wp_tempnam( basename( $file_path ) );
		if ( ! copy( $file_path, $tmp ) ) {
			return new WP_Error( 'copy_failed', "Could not copy to temp location: {$file_path}" );
		}

		$file_array = [
			'name'     => basename( $file_path ),
			'tmp_name' => $tmp,
		];

		$attachment_id = media_handle_sideload( $file_array, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			return $attachment_id;
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		update_post_meta( $post_id, $cache_key, $attachment_id );

		return $attachment_id;
	}

	// -------------------------------------------------------------------------
	// Rank Math meta
	// -------------------------------------------------------------------------

	private function update_rank_math_meta( int $post_id, array $cols ): void {
		$c = $this->col;

		$meta_map = [
			'rank_math_title'          => $c['COL_RANK_TITLE'],
			'rank_math_description'    => $c['COL_RANK_DESC'],
			'rank_math_focus_keyword'  => $c['COL_FOCUS_KW'],
			'rank_math_keywords'       => $c['COL_KEYWORDS'],
			'rank_math_og_title'       => $c['COL_OG_TITLE'],
			'rank_math_og_description' => $c['COL_OG_DESC'],
		];

		foreach ( $meta_map as $meta_key => $col_idx ) {
			$value = trim( $cols[ $col_idx ] );
			if ( $value !== '' ) {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Parse a testimonial string into a quote and an author.
	 *
	 * @return array{ quote: string, author: string }
	 */
	private function parse_testimonial( string $text ): array {
		$text = trim( $text );
		if ( $text === '' ) {
			return [ 'quote' => '', 'author' => '' ];
		}

		$text = preg_replace( '/[\x{0080}-\x{009F}]/u', ' ', $text ) ?? $text;
		$text = preg_replace( '/   +/', '  ', $text ) ?? $text;
		$text = trim( $text );
		if ( $text === '' ) {
			return [ 'quote' => '', 'author' => '' ];
		}

		$original = $text;

		$text = preg_replace( '/^[\x{22}\x{201C}]/u', '', $text ) ?? $original;
		$text = trim( $text );
		if ( $text === '' ) {
			$text = $original;
		}

		$text = preg_replace( '/[\x{22}\x{201D}](\s{2,})/u', '$1', $text ) ?? $text;
		$text = trim( $text );
		if ( $text === '' ) {
			$text = $original;
		}

		foreach ( [ ' — ', ' – ', "\n— ", "\n– ", ' -- ', ' - ', "\n- " ] as $sep ) {
			$pos = mb_strrpos( $text, $sep );
			if ( $pos !== false ) {
				$quote  = trim( mb_substr( $text, 0, $pos ) );
				$author = trim( mb_substr( $text, $pos + mb_strlen( $sep ) ) );
				if ( $quote !== '' && $author !== '' ) {
					return [ 'quote' => $quote, 'author' => $author ];
				}
			}
		}

		if ( preg_match_all( '/\s{2,}/', $text, $space_matches, PREG_OFFSET_CAPTURE ) ) {
			foreach ( array_reverse( $space_matches[0] ) as [ $run, $byte_pos ] ) {
				$candidate_quote  = trim( substr( $text, 0, $byte_pos ) );
				$candidate_author = trim( substr( $text, $byte_pos + strlen( $run ) ) );
				if (
					$candidate_quote !== ''
					&& $candidate_author !== ''
					&& str_contains( $candidate_author, ',' )
					&& mb_strlen( $candidate_author ) <= 40
				) {
					return [ 'quote' => $candidate_quote, 'author' => $candidate_author ];
				}
			}
		}

		return [ 'quote' => $text, 'author' => '' ];
	}

	/**
	 * In dry-run mode, report any missing image files for a given slug.
	 */
	private function check_images_exist( string $slug, int $row_num ): void {
		if ( empty( $this->images_dir ) ) {
			return;
		}

	$image_fallbacks = [
		"{$slug}-hero.webp" => self::FALLBACK_HERO_ID,
		"{$slug}-2.webp"    => self::FALLBACK_IMAGE2_ID,
		"{$slug}.webp"      => self::FALLBACK_IMAGE3_ID,
	];

		foreach ( $image_fallbacks as $filename => $fallback_id ) {
			$path = $this->images_dir . '/' . $filename;
			if ( ! file_exists( $path ) ) {
				$note = $fallback_id > 0
					? "Image not found: {$filename} (will use fallback ID {$fallback_id})"
					: "Image not found: {$filename} (no fallback — field will be skipped)";
				$this->log_result( $row_num, $slug, 'warn', $note );
			}
		}
	}

	private function log_result( int $row_num, string $slug, string $status, string $note ): void {
		$this->log[] = [
			'row'    => $row_num,
			'slug'   => $slug,
			'status' => $status,
			'note'   => $note,
		];
	}

	private function result(): array {
		return [
			'log'   => $this->log,
			'stats' => $this->stats,
		];
	}
}
