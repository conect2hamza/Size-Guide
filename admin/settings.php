<?php
/**
 * Settings screen.
 *
 * @package SizeGuide
 *
 * @var array $dataset Normalised dataset.
 * @var array $stats   Dataset totals.
 */

defined( 'ABSPATH' ) || exit;

$sg_settings = SizeGuide\Size_Guide::get_settings();
$sg_option   = SizeGuide\Size_Guide::SETTINGS_OPTION;
?>
<div class="wrap sg-admin">
	<h1><?php esc_html_e( 'Size Guide Settings', 'size-guide' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'size_guide_settings_group' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="sg_default_section"><?php esc_html_e( 'Opening section', 'size-guide' ); ?></label>
					</th>
					<td>
						<select name="<?php echo esc_attr( $sg_option ); ?>[default_section]" id="sg_default_section">
							<?php
							$sg_sections = array(
								'digital' => __( 'Digital', 'size-guide' ),
								'print'   => __( 'Print', 'size-guide' ),
							);

							foreach ( $sg_sections as $sg_key => $sg_label ) :
								?>
								<option value="<?php echo esc_attr( $sg_key ); ?>" <?php selected( $sg_settings['default_section'], $sg_key ); ?>>
									<?php echo esc_html( $sg_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Which tab the guide opens on, unless the shortcode says otherwise.', 'size-guide' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="sg_default_unit"><?php esc_html_e( 'Default unit', 'size-guide' ); ?></label>
					</th>
					<td>
						<select name="<?php echo esc_attr( $sg_option ); ?>[default_unit]" id="sg_default_unit">
							<?php foreach ( array( 'px', 'mm', 'cm', 'in' ) as $sg_unit ) : ?>
								<option value="<?php echo esc_attr( $sg_unit ); ?>" <?php selected( $sg_settings['default_unit'], $sg_unit ); ?>>
									<?php echo esc_html( strtoupper( $sg_unit ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Print sizes always open in millimetres; this covers everything else.', 'size-guide' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="sg_default_dpi"><?php esc_html_e( 'Default DPI', 'size-guide' ); ?></label>
					</th>
					<td>
						<input type="number" min="1" max="2400" step="1" class="small-text"
							name="<?php echo esc_attr( $sg_option ); ?>[default_dpi]" id="sg_default_dpi"
							value="<?php echo esc_attr( $sg_settings['default_dpi'] ); ?>">
						<p class="description"><?php esc_html_e( 'Used when converting between pixels and physical units.', 'size-guide' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Frontend', 'size-guide' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Frontend options', 'size-guide' ); ?></legend>

							<label>
								<input type="checkbox" name="<?php echo esc_attr( $sg_option ); ?>[show_sources]" value="1" <?php checked( $sg_settings['show_sources'], 1 ); ?>>
								<?php esc_html_e( 'Show where each specification came from and when it was last checked', 'size-guide' ); ?>
							</label><br>

							<label>
								<input type="checkbox" name="<?php echo esc_attr( $sg_option ); ?>[enable_download]" value="1" <?php checked( $sg_settings['enable_download'], 1 ); ?>>
								<?php esc_html_e( 'Allow template downloads (SVG, PNG and print)', 'size-guide' ); ?>
							</label><br>

							<label>
								<input type="checkbox" name="<?php echo esc_attr( $sg_option ); ?>[load_via_rest]" value="1" <?php checked( $sg_settings['load_via_rest'], 1 ); ?>>
								<?php esc_html_e( 'Fetch the dataset over REST instead of inlining it', 'size-guide' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Inlining renders the guide on first paint; fetching keeps the page HTML small and lets the browser cache the dataset across pages. Either way no external service is contacted.', 'size-guide' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>

			</tbody>
		</table>

		<p class="description">
			<?php
			printf(
				/* translators: %s: link to the appearance screen */
				esc_html__( 'Colours, typography, spacing and shape live on the %s screen.', 'size-guide' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=size-guide-appearance' ) ) . '">' .
					esc_html__( 'Appearance', 'size-guide' ) . '</a>'
			);
			?>
		</p>

		<?php submit_button(); ?>
	</form>
</div>
