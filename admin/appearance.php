<?php
/**
 * Appearance screen: every design token, with a live preview of the real app.
 *
 * @package SizeGuide
 *
 * @var array $dataset Normalised dataset.
 * @var array $stats   Dataset totals.
 */

defined( 'ABSPATH' ) || exit;

use SizeGuide\Appearance;

$sg_config = Appearance::get();
$sg_option = Appearance::OPTION;
$sg_fonts  = Appearance::font_stacks();

/**
 * Print one colour control.
 *
 * @param string $name  Field name.
 * @param string $token Token key.
 * @param string $label Field label.
 * @param string $value Current value.
 */
function size_guide_color_field( $name, $token, $label, $value ) {
	$id = 'sg-color-' . sanitize_key( $name . '-' . $token );
	?>
	<div class="sg-ap__color">
		<input type="color" id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $token ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			data-sg-token="<?php echo esc_attr( $token ); ?>">
		<label for="<?php echo esc_attr( $id ); ?>">
			<span class="sg-ap__color-name"><?php echo esc_html( $label ); ?></span>
			<code><?php echo esc_html( $value ); ?></code>
		</label>
	</div>
	<?php
}
?>
<div class="wrap sg-admin sg-ap">
	<h1><?php esc_html_e( 'Appearance', 'size-guide' ); ?></h1>
	<p class="sg-admin__lead">
		<?php esc_html_e( 'Every colour, measurement and typeface the guide uses. The preview is the real front-end running on your dataset, and it updates as you change a control — nothing is saved until you press Save.', 'size-guide' ); ?>
	</p>

	<form method="post" action="options.php" id="sg-appearance-form">
		<?php settings_fields( 'size_guide_appearance_group' ); ?>

		<div class="sg-ap__layout">

			<div class="sg-ap__controls">

				<section class="sg-ap__panel">
					<h2><?php esc_html_e( 'Start from a preset', 'size-guide' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Fills every control below. Adjust anything afterwards.', 'size-guide' ); ?></p>
					<div class="sg-ap__presets">
						<?php foreach ( Appearance::presets() as $sg_key => $sg_preset ) : ?>
							<button type="button" class="button sg-ap__preset" data-sg-preset="<?php echo esc_attr( $sg_key ); ?>">
								<?php echo esc_html( $sg_preset['label'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
					<script type="application/json" id="sg-ap-presets">
						<?php
						$sg_resolved = array();
						foreach ( Appearance::presets() as $sg_key => $sg_preset ) {
							$sg_resolved[ $sg_key ] = Appearance::sanitize(
								array_merge( $sg_config, $sg_preset['values'], array( 'preset' => $sg_key ) )
							);
						}
						echo wp_json_encode( $sg_resolved );
						?>
					</script>
				</section>

				<section class="sg-ap__panel">
					<h2><?php esc_html_e( 'Colour scheme', 'size-guide' ); ?></h2>
					<div class="sg-ap__segmented" role="radiogroup" aria-label="<?php esc_attr_e( 'Colour scheme', 'size-guide' ); ?>">
						<?php
						$sg_schemes = array(
							'light' => __( 'Light', 'size-guide' ),
							'dark'  => __( 'Dark', 'size-guide' ),
							'auto'  => __( 'Auto', 'size-guide' ),
						);

						foreach ( $sg_schemes as $sg_key => $sg_label ) :
							?>
							<label>
								<input type="radio" name="<?php echo esc_attr( $sg_option ); ?>[scheme]"
									value="<?php echo esc_attr( $sg_key ); ?>"
									<?php checked( $sg_config['scheme'], $sg_key ); ?> data-sg-scheme>
								<span><?php echo esc_html( $sg_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="description"><?php esc_html_e( '“Auto” follows each visitor’s own system setting.', 'size-guide' ); ?></p>

					<p class="sg-ap__check">
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $sg_option ); ?>[transparent_bg]" value="1"
								<?php checked( $sg_config['transparent_bg'], 1 ); ?> data-sg-transparent>
							<?php esc_html_e( 'Let the page background show through', 'size-guide' ); ?>
						</label>
					</p>
				</section>

				<section class="sg-ap__panel">
					<h2><?php esc_html_e( 'Light palette', 'size-guide' ); ?></h2>
					<div class="sg-ap__colors" data-sg-group="light">
						<?php
						foreach ( Appearance::scheme_colors() as $sg_token => $sg_label ) {
							size_guide_color_field(
								$sg_option . '[light]',
								$sg_token,
								$sg_label,
								$sg_config['light'][ $sg_token ]
							);
						}
						?>
					</div>
				</section>

				<section class="sg-ap__panel">
					<h2><?php esc_html_e( 'Dark palette', 'size-guide' ); ?></h2>
					<div class="sg-ap__colors" data-sg-group="dark">
						<?php
						foreach ( Appearance::scheme_colors() as $sg_token => $sg_label ) {
							size_guide_color_field(
								$sg_option . '[dark]',
								$sg_token,
								$sg_label,
								$sg_config['dark'][ $sg_token ]
							);
						}
						?>
					</div>
				</section>

				<section class="sg-ap__panel">
					<h2><?php esc_html_e( 'Diagram colours', 'size-guide' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'One set for both schemes: the artboard inside a diagram is always the design surface, so a second dark set would be drawn on a light background and vanish.', 'size-guide' ); ?>
					</p>
					<div class="sg-ap__colors" data-sg-group="guides">
						<?php
						foreach ( Appearance::guide_colors() as $sg_token => $sg_label ) {
							size_guide_color_field(
								$sg_option . '[guides]',
								$sg_token,
								$sg_label,
								$sg_config['guides'][ $sg_token ]
							);
						}
						?>
					</div>
				</section>

				<section class="sg-ap__panel">
					<h2><?php esc_html_e( 'Typography', 'size-guide' ); ?></h2>

					<p>
						<label class="sg-ap__label" for="sg-font-family"><?php esc_html_e( 'Typeface', 'size-guide' ); ?></label>
						<select id="sg-font-family" name="<?php echo esc_attr( $sg_option ); ?>[font_family]" data-sg-font>
							<?php foreach ( $sg_fonts as $sg_key => $sg_font ) : ?>
								<option value="<?php echo esc_attr( $sg_key ); ?>"
									data-sg-stack="<?php echo esc_attr( $sg_font['stack'] ); ?>"
									<?php selected( $sg_config['font_family'], $sg_key ); ?>>
									<?php echo esc_html( $sg_font['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>

					<p class="sg-ap__custom-font" <?php echo 'custom' === $sg_config['font_family'] ? '' : 'hidden'; ?>>
						<label class="sg-ap__label" for="sg-font-custom"><?php esc_html_e( 'Custom font stack', 'size-guide' ); ?></label>
						<input type="text" class="regular-text" id="sg-font-custom"
							name="<?php echo esc_attr( $sg_option ); ?>[font_custom]"
							value="<?php echo esc_attr( $sg_config['font_custom'] ); ?>"
							placeholder="&quot;Inter&quot;, system-ui, sans-serif" data-sg-font-custom>
						<span class="description"><?php esc_html_e( 'The font must already be loaded by your theme.', 'size-guide' ); ?></span>
					</p>

					<?php
					$sg_type_numbers = array( 'font_size', 'heading_weight' );
					foreach ( Appearance::numbers() as $sg_key => $sg_spec ) :
						if ( ! in_array( $sg_key, $sg_type_numbers, true ) ) {
							continue;
						}
						list( $sg_label, $sg_min, $sg_max, $sg_step, $sg_unit ) = $sg_spec;
						?>
						<p class="sg-ap__range">
							<label class="sg-ap__label" for="sg-num-<?php echo esc_attr( $sg_key ); ?>">
								<?php echo esc_html( $sg_label ); ?>
								<output><?php echo esc_html( $sg_config[ $sg_key ] . $sg_unit ); ?></output>
							</label>
							<input type="range" id="sg-num-<?php echo esc_attr( $sg_key ); ?>"
								name="<?php echo esc_attr( $sg_option ); ?>[<?php echo esc_attr( $sg_key ); ?>]"
								min="<?php echo esc_attr( $sg_min ); ?>" max="<?php echo esc_attr( $sg_max ); ?>"
								step="<?php echo esc_attr( $sg_step ); ?>" value="<?php echo esc_attr( $sg_config[ $sg_key ] ); ?>"
								data-sg-number="<?php echo esc_attr( $sg_key ); ?>" data-sg-unit="<?php echo esc_attr( $sg_unit ); ?>">
						</p>
					<?php endforeach; ?>
				</section>

				<section class="sg-ap__panel">
					<h2><?php esc_html_e( 'Shape &amp; space', 'size-guide' ); ?></h2>

					<?php
					foreach ( Appearance::numbers() as $sg_key => $sg_spec ) :
						if ( in_array( $sg_key, $sg_type_numbers, true ) ) {
							continue;
						}
						list( $sg_label, $sg_min, $sg_max, $sg_step, $sg_unit ) = $sg_spec;
						?>
						<p class="sg-ap__range">
							<label class="sg-ap__label" for="sg-num-<?php echo esc_attr( $sg_key ); ?>">
								<?php echo esc_html( $sg_label ); ?>
								<output><?php echo esc_html( $sg_config[ $sg_key ] . $sg_unit ); ?></output>
							</label>
							<input type="range" id="sg-num-<?php echo esc_attr( $sg_key ); ?>"
								name="<?php echo esc_attr( $sg_option ); ?>[<?php echo esc_attr( $sg_key ); ?>]"
								min="<?php echo esc_attr( $sg_min ); ?>" max="<?php echo esc_attr( $sg_max ); ?>"
								step="<?php echo esc_attr( $sg_step ); ?>" value="<?php echo esc_attr( $sg_config[ $sg_key ] ); ?>"
								data-sg-number="<?php echo esc_attr( $sg_key ); ?>" data-sg-unit="<?php echo esc_attr( $sg_unit ); ?>">
						</p>
					<?php endforeach; ?>

					<p>
						<label class="sg-ap__label" for="sg-density"><?php esc_html_e( 'Density', 'size-guide' ); ?></label>
						<select id="sg-density" name="<?php echo esc_attr( $sg_option ); ?>[density]" data-sg-density>
							<?php foreach ( Appearance::densities() as $sg_key => $sg_density ) : ?>
								<option value="<?php echo esc_attr( $sg_key ); ?>"
									data-sg-gap="<?php echo esc_attr( $sg_density['gap'] ); ?>"
									data-sg-pad="<?php echo esc_attr( $sg_density['pad'] ); ?>"
									<?php selected( $sg_config['density'], $sg_key ); ?>>
									<?php echo esc_html( $sg_density['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label class="sg-ap__label" for="sg-shadow"><?php esc_html_e( 'Shadow', 'size-guide' ); ?></label>
						<select id="sg-shadow" name="<?php echo esc_attr( $sg_option ); ?>[shadow]" data-sg-shadow>
							<?php foreach ( Appearance::shadows() as $sg_key => $sg_shadow ) : ?>
								<option value="<?php echo esc_attr( $sg_key ); ?>"
									data-sg-value="<?php echo esc_attr( $sg_shadow['value'] ); ?>"
									data-sg-hover="<?php echo esc_attr( $sg_shadow['hover'] ); ?>"
									<?php selected( $sg_config['shadow'], $sg_key ); ?>>
									<?php echo esc_html( $sg_shadow['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>

					<p class="sg-ap__check">
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $sg_option ); ?>[animations]" value="1"
								<?php checked( $sg_config['animations'], 1 ); ?> data-sg-animations>
							<?php esc_html_e( 'Animate hovers and transitions', 'size-guide' ); ?>
						</label>
						<span class="description"><?php esc_html_e( 'Visitors who ask for reduced motion never see them either way.', 'size-guide' ); ?></span>
					</p>
				</section>

				<?php submit_button( __( 'Save appearance', 'size-guide' ) ); ?>
			</div>

			<div class="sg-ap__preview-wrap">
				<div class="sg-ap__preview-bar">
					<span class="sg-ap__live"><?php esc_html_e( 'Live preview', 'size-guide' ); ?></span>
					<span class="sg-ap__hint"><?php esc_html_e( 'Unsaved', 'size-guide' ); ?></span>
				</div>
				<div class="sg-ap__preview" id="sg-appearance-preview">
					<?php
					// The real front-end, rendered with the saved configuration; the
					// script overrides its custom properties as controls change.
					echo do_shortcode( '[size_guide platform="instagram" format="instagram-post-portrait"]' );
					?>
				</div>
			</div>
		</div>
	</form>
</div>
