<?php
/**
 * Admin Page: Tools → Import Locations
 *
 * Browser-based UI for running the location page importer. Calls the same
 * PaulBunyan_Location_Importer class as the WP-CLI command. Access is
 * restricted to users with the manage_options capability.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'paulbunyan_register_import_locations_page' );

function paulbunyan_register_import_locations_page(): void {
	add_management_page(
		'Import Locations',
		'Import Locations',
		'manage_options',
		'paulbunyan-import-locations',
		'paulbunyan_render_import_locations_page'
	);
}

function paulbunyan_render_import_locations_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.' ) );
	}

	$result = null;
	$error  = '';

	if ( isset( $_POST['paulbunyan_run_import'] ) ) {

		if ( ! check_admin_referer( 'paulbunyan_import_locations', 'paulbunyan_import_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		$upload_ok = (
			! empty( $_FILES['paulbunyan_csv']['tmp_name'] ) &&
			isset( $_FILES['paulbunyan_csv']['error'] ) &&
			UPLOAD_ERR_OK === (int) $_FILES['paulbunyan_csv']['error']
		);

		if ( ! $upload_ok ) {
			$error = 'Please upload a valid CSV file.';
		} else {
			$csv_path   = $_FILES['paulbunyan_csv']['tmp_name'];
			$dry_run    = ! empty( $_POST['paulbunyan_dry_run'] );
			$only_slug  = sanitize_title( trim( $_POST['paulbunyan_only'] ?? '' ) );
			$images_dir = sanitize_text_field( trim( $_POST['paulbunyan_images_dir'] ?? '' ) );

			$importer = new PaulBunyan_Location_Importer( [
				'file'       => $csv_path,
				'dry_run'    => $dry_run,
				'only'       => $only_slug,
				'images_dir' => $images_dir,
			] );

			$result = $importer->run();
		}
	}

	$default_images_dir = '/home/wpe-user/apps/paulbunyans/public/wp-content/uploads/location-images';

	?>
	<div class="wrap">
		<h1>Import Locations</h1>
		<p>Creates or updates Paul Bunyan city location pages from the CSV. Each page is created as a standard WordPress page with the <strong>Location Page</strong> template, all ACF fields populated, images sideloaded from the server images folder, and Rank Math meta written directly.</p>
		<p><strong>Protected slugs (never created or overwritten):</strong> <code>minneapolis</code>, <code>rochester</code></p>

		<?php if ( $error ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<form method="post" enctype="multipart/form-data" style="max-width:760px">
			<?php wp_nonce_field( 'paulbunyan_import_locations', 'paulbunyan_import_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="paulbunyan_csv">CSV File</label></th>
					<td>
						<input type="file" name="paulbunyan_csv" id="paulbunyan_csv" accept=".csv" required>
						<p class="description">
							<code>PaulBunyan_SEO_Final_Paul_Bunyan_SEO_Plan_.csv</code> — Row 2 = column headers,
							Row 3 = example row (auto-skipped), Row 4+ = data.
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="paulbunyan_images_dir">Images Directory</label></th>
					<td>
						<input
							type="text"
							name="paulbunyan_images_dir"
							id="paulbunyan_images_dir"
							class="large-text"
							value="<?php echo esc_attr( $default_images_dir ); ?>"
						>
						<p class="description">
							Server path to the folder containing
							<code>{slug}-hero.webp</code>,
							<code>{slug}-2.webp</code>, and
							<code>{slug}.webp</code>.
							Leave blank to skip image sideloading.
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="paulbunyan_only">Only Slug</label></th>
					<td>
						<input
							type="text"
							name="paulbunyan_only"
							id="paulbunyan_only"
							class="regular-text"
							placeholder="e.g. maple-grove"
						>
						<p class="description">Process only the row matching this slug. Leave blank to process all rows.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Dry Run</th>
					<td>
						<label>
							<input type="checkbox" name="paulbunyan_dry_run" value="1">
							Preview planned actions and check image files — no writes made
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Run Import', 'primary large', 'paulbunyan_run_import' ); ?>
		</form>

		<?php if ( null !== $result ) : ?>
			<?php paulbunyan_render_import_results( $result ); ?>
		<?php endif; ?>

	</div>
	<?php
}

function paulbunyan_render_import_results( array $result ): void {
	$log   = $result['log'];
	$stats = $result['stats'];

	$total_processed = array_sum( $stats );

	echo '<hr>';
	echo '<h2>Results</h2>';

	echo '<h3>Summary</h3>';
	echo '<table class="widefat fixed striped" style="max-width:320px">';
	echo '<thead><tr><th>Status</th><th style="width:70px;text-align:right">Count</th></tr></thead>';
	echo '<tbody>';
	foreach ( $stats as $label => $count ) {
		$display = esc_html( str_replace( '_', '-', $label ) );
		$style   = $count > 0 && in_array( $label, [ 'error', 'skipped_conflict', 'skipped_incomplete' ], true )
			? ' style="color:#d63638;font-weight:600"'
			: '';
		printf(
			'<tr><td%s>%s</td><td style="text-align:right">%d</td></tr>',
			$style,
			$display,
			(int) $count
		);
	}
	echo '</tbody></table>';

	if ( 0 === $total_processed ) {
		echo '<p>No rows were processed. Check that the CSV contains rows of type "Location".</p>';
		return;
	}

	echo '<h3 style="margin-top:1.5em">Row Log</h3>';
	echo '<table class="widefat fixed striped">';
	echo '<thead><tr>';
	echo '<th style="width:55px">Row</th>';
	echo '<th style="width:190px">Slug</th>';
	echo '<th style="width:165px">Status</th>';
	echo '<th>Note</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	foreach ( $log as $entry ) {
		switch ( $entry['status'] ) {
			case 'created':
			case 'would-create':
				$row_style = 'background:#edfdf0';
				break;
			case 'error':
				$row_style = 'background:#fdf0ef';
				break;
			case 'skipped-conflict':
			case 'skipped-incomplete':
			case 'warn':
				$row_style = 'background:#fdfaed';
				break;
			default:
				$row_style = '';
		}

		printf(
			'<tr%s><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>',
			$row_style ? ' style="' . esc_attr( $row_style ) . '"' : '',
			(int) $entry['row'],
			esc_html( $entry['slug'] ),
			esc_html( $entry['status'] ),
			esc_html( $entry['note'] )
		);
	}

	echo '</tbody></table>';
}
