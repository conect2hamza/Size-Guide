<?php
/**
 * Static rendering of one size record.
 *
 * @package SizeGuide
 *
 * @var array $sg_format Normalised format record.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $sg_format ) ) {
	return;
}

$sg_is_print = 'print' === $sg_format['section'];
?>
<article class="sg-static__card" id="size-<?php echo esc_attr( $sg_format['id'] ); ?>">
	<h5 class="sg-static__card-title"><?php echo esc_html( $sg_format['name'] ); ?></h5>

	<p class="sg-static__card-size">
		<?php echo esc_html( SizeGuide\Data_Loader::dimensions( $sg_format ) ); ?>
		<span class="sg-static__card-ratio"><?php echo esc_html( $sg_format['aspect_ratio'] ); ?></span>
	</p>

	<?php
	if ( $sg_is_print ) {
		include SIZE_GUIDE_PATH . 'templates/print-size.php';
	}
	?>

	<?php if ( $sg_format['safe_zone'] ) : ?>
		<p class="sg-static__card-meta">
			<?php
			printf(
				/* translators: %s: safe zone measurement */
				esc_html__( 'Safe zone: %s', 'size-guide' ),
				esc_html(
					SizeGuide\Data_Loader::round_unit( $sg_format['safe_zone']['top'], $sg_format['unit'] ) . ' / ' .
					SizeGuide\Data_Loader::round_unit( $sg_format['safe_zone']['right'], $sg_format['unit'] ) . ' / ' .
					SizeGuide\Data_Loader::round_unit( $sg_format['safe_zone']['bottom'], $sg_format['unit'] ) . ' / ' .
					SizeGuide\Data_Loader::round_unit( $sg_format['safe_zone']['left'], $sg_format['unit'] ) . ' ' .
					$sg_format['unit']
				)
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $sg_format['bleed'] ) : ?>
		<p class="sg-static__card-meta">
			<?php
			printf(
				/* translators: 1: bleed measurement, 2: working document size */
				esc_html__( 'Bleed: %1$s — working document %2$s', 'size-guide' ),
				esc_html( SizeGuide\Data_Loader::round_unit( $sg_format['bleed'], $sg_format['unit'] ) . ' ' . $sg_format['unit'] ),
				esc_html(
					SizeGuide\Data_Loader::round_unit( $sg_format['width'] + ( $sg_format['bleed'] * 2 ), $sg_format['unit'] ) . ' × ' .
					SizeGuide\Data_Loader::round_unit( $sg_format['height'] + ( $sg_format['bleed'] * 2 ), $sg_format['unit'] ) . ' ' .
					$sg_format['unit']
				)
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $sg_format['notes'] ) : ?>
		<p class="sg-static__card-notes"><?php echo esc_html( $sg_format['notes'] ); ?></p>
	<?php endif; ?>
</article>
