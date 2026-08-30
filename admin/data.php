<?php
/**
 * Import and export the dataset.
 *
 * @package SizeGuide
 *
 * @var array $dataset Normalised dataset.
 * @var array $stats   Dataset totals.
 */

defined( 'ABSPATH' ) || exit;

$sg_custom      = get_option( SizeGuide\Data_Loader::CUSTOM_OPTION, array() );
$sg_has_custom  = ! empty( $sg_custom );
$sg_custom_size = $sg_has_custom ? count( isset( $sg_custom['platforms'] ) ? array( $sg_custom ) : $sg_custom ) : 0;
?>
<div class="wrap sg-admin">
	<h1><?php esc_html_e( 'Import / Export', 'size-guide' ); ?></h1>
	<p class="sg-admin__lead">
		<?php esc_html_e( 'Move thousands of specifications at once instead of editing fields one by one. Imported groups are stored in the database and merged on top of the bundled JSON files.', 'size-guide' ); ?>
	</p>

	<div class="sg-admin__columns">
		<div class="sg-admin__panel">
			<h2><?php esc_html_e( 'Import', 'size-guide' ); ?></h2>

			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=size-guide-data' ) ); ?>">
				<?php wp_nonce_field( 'size_guide_import' ); ?>
				<input type="hidden" name="size_guide_action" value="import">

				<p>
					<label for="size_guide_file"><strong><?php esc_html_e( 'JSON file', 'size-guide' ); ?></strong></label><br>
					<input type="file" name="size_guide_file" id="size_guide_file" accept="application/json,.json">
				</p>

				<p>
					<label for="size_guide_json"><strong><?php esc_html_e( '…or paste JSON', 'size-guide' ); ?></strong></label><br>
					<textarea name="size_guide_json" id="size_guide_json" rows="10" class="large-text code"
						placeholder='{"id":"my-sizes","section":"digital","name":"My Sizes","platforms":[]}'></textarea>
				</p>

				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Import mode', 'size-guide' ); ?></legend>
					<p>
						<label>
							<input type="radio" name="import_mode" value="replace" checked>
							<?php esc_html_e( 'Replace everything previously imported', 'size-guide' ); ?>
						</label><br>
						<label>
							<input type="radio" name="import_mode" value="merge">
							<?php esc_html_e( 'Add to what is already imported', 'size-guide' ); ?>
						</label>
					</p>
				</fieldset>

				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Import dataset', 'size-guide' ); ?></button>
				</p>
			</form>

			<h3><?php esc_html_e( 'Accepted shapes', 'size-guide' ); ?></h3>
			<p><?php esc_html_e( 'One group, a list of groups, or a full export from this screen. Each group needs an id, a section ("digital" or "print"), a name and a list of platforms.', 'size-guide' ); ?></p>

			<pre class="sg-admin__code">{
  "id": "my-sizes",
  "section": "digital",
  "name": "My Sizes",
  "defaults": { "unit": "px", "dpi": 72 },
  "platforms": [
    {
      "id": "my-platform",
      "name": "My Platform",
      "categories": [
        {
          "id": "posts",
          "name": "Posts",
          "formats": [
            {
              "id": "my-platform-post",
              "name": "Post",
              "width": 1080,
              "height": 1080,
              "safe_zone": 60,
              "source": { "type": "official", "checked_date": "2026-08-29" },
              "last_updated": "2026-08"
            }
          ]
        }
      ]
    }
  ]
}</pre>
		</div>

		<div class="sg-admin__panel">
			<h2><?php esc_html_e( 'Export', 'size-guide' ); ?></h2>
			<p><?php esc_html_e( 'Download the dataset as JSON — useful as a backup, or as a starting point for your own edits.', 'size-guide' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=size-guide-data' ) ); ?>">
				<?php wp_nonce_field( 'size_guide_export' ); ?>
				<input type="hidden" name="size_guide_action" value="export">

				<p>
					<label>
						<input type="radio" name="export_scope" value="all" checked>
						<?php
						printf(
							/* translators: %s: number of sizes */
							esc_html__( 'Everything (%s sizes)', 'size-guide' ),
							esc_html( number_format_i18n( $stats['formats'] ) )
						);
						?>
					</label><br>
					<label>
						<input type="radio" name="export_scope" value="custom" <?php disabled( ! $sg_has_custom ); ?>>
						<?php esc_html_e( 'Only what I imported', 'size-guide' ); ?>
					</label>
				</p>

				<p>
					<button type="submit" class="button"><?php esc_html_e( 'Export JSON', 'size-guide' ); ?></button>
				</p>
			</form>

			<h2><?php esc_html_e( 'Imported data', 'size-guide' ); ?></h2>

			<?php if ( $sg_has_custom ) : ?>
				<p>
					<?php
					printf(
						/* translators: %d: number of imported groups */
						esc_html( _n( '%d group is currently layered on top of the bundled dataset.', '%d groups are currently layered on top of the bundled dataset.', $sg_custom_size, 'size-guide' ) ),
						(int) $sg_custom_size
					);
					?>
				</p>

				<p>
					<a class="button button-link-delete"
						href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=size-guide-data&size_guide_action=reset_custom' ), 'size_guide_reset_custom' ) ); ?>"
						onclick="return confirm('<?php echo esc_js( __( 'Remove all imported size data? The bundled dataset stays in place.', 'size-guide' ) ); ?>');">
						<?php esc_html_e( 'Remove imported data', 'size-guide' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Nothing imported yet — the frontend is running on the bundled JSON files.', 'size-guide' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
