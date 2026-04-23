<?php
/**
 * WP-CLI Command: wp earlybird import-locations
 *
 * Thin wrapper around EarlyBird_Location_Importer. This file is loaded only
 * when WP_CLI is defined (see the conditional require_once in functions.php).
 *
 * Usage examples:
 *   wp earlybird import-locations --file=cities.csv --dry-run
 *   wp earlybird import-locations --file=cities.csv --only=saint-paul \
 *       --images-dir="/path/to/Images"
 *   wp earlybird import-locations --file=cities.csv \
 *       --images-dir="/path/to/Images"
 *
 * Run from Local's Site Shell, not the regular Mac Terminal.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EarlyBird_Import_Locations_Command {

	/**
	 * Import EarlyBird city location pages from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Absolute path to the CSV file.
	 *
	 * [--dry-run]
	 * : Preview planned actions and check image files. No writes are made.
	 *
	 * [--only=<slug>]
	 * : Process only the row whose city slug matches this value.
	 *
	 * [--images-dir=<path>]
	 * : Absolute path to the local folder containing {slug}-hero.webp and
	 *   {slug}-2.webp. Required for image sideloading.
	 *
	 * ## EXAMPLES
	 *
	 *   # Dry run — no writes, reports planned actions and checks images
	 *   wp earlybird import-locations --file=cities.csv --dry-run \
	 *       --images-dir="/path/to/Images"
	 *
	 *   # Pilot: import a single city
	 *   wp earlybird import-locations --file=cities.csv --only=saint-paul \
	 *       --images-dir="/path/to/Images"
	 *
	 *   # Full import
	 *   wp earlybird import-locations --file=cities.csv \
	 *       --images-dir="/path/to/Images"
	 *
	 * @when after_wp_load
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$file = \WP_CLI\Utils\get_flag_value( $assoc_args, 'file', '' );

		if ( empty( $file ) ) {
			\WP_CLI::error( 'Missing required --file argument. Usage: wp earlybird import-locations --file=cities.csv' );
		}

		if ( ! file_exists( $file ) ) {
			\WP_CLI::error( "CSV file not found: {$file}" );
		}

		$dry_run    = array_key_exists( 'dry-run', $assoc_args );
		$only_slug  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'only', '' );
		$images_dir = \WP_CLI\Utils\get_flag_value( $assoc_args, 'images-dir', '' );

		if ( $dry_run ) {
			\WP_CLI::log( '--- DRY RUN — no changes will be made ---' );
		}

		if ( $only_slug ) {
			\WP_CLI::log( "Filtering to slug: {$only_slug}" );
		}

		\WP_CLI::log( "Reading: {$file}" );

		$importer = new EarlyBird_Location_Importer( [
			'file'       => $file,
			'dry_run'    => $dry_run,
			'only'       => $only_slug,
			'images_dir' => $images_dir,
		] );

		$result = $importer->run();
		$log    = $result['log'];
		$stats  = $result['stats'];

		\WP_CLI::log( '' );

		// Output each log entry with colour coding.
		foreach ( $log as $entry ) {
			$line = sprintf(
				'[Row %3d]  %-28s  %-22s  %s',
				$entry['row'],
				$entry['slug'],
				$entry['status'],
				$entry['note']
			);

			switch ( $entry['status'] ) {
				case 'error':
					\WP_CLI::warning( $line );
					break;
				case 'skipped-conflict':
				case 'skipped-incomplete':
				case 'warn':
					\WP_CLI::log( \WP_CLI::colorize( '%y' . $line . '%n' ) );
					break;
				case 'created':
				case 'would-create':
					\WP_CLI::log( \WP_CLI::colorize( '%g' . $line . '%n' ) );
					break;
				default:
					\WP_CLI::log( $line );
			}
		}

		// Summary table.
		\WP_CLI::log( '' );
		\WP_CLI::log( '--- Summary ---' );

		$summary_rows = [];
		foreach ( $stats as $label => $count ) {
			$summary_rows[] = [
				'Status' => str_replace( '_', '-', $label ),
				'Count'  => $count,
			];
		}

		\WP_CLI\Utils\format_items( 'table', $summary_rows, [ 'Status', 'Count' ] );

		if ( $stats['error'] > 0 ) {
			\WP_CLI::warning( "{$stats['error']} error(s) encountered. Review the log above." );
		} else {
			\WP_CLI::success( $dry_run ? 'Dry run complete — no changes made.' : 'Import complete.' );
		}
	}
}

\WP_CLI::add_command( 'earlybird import-locations', 'EarlyBird_Import_Locations_Command' );
