<?php
/**
 * The same print size written out in every unit.
 *
 * @package SizeGuide
 *
 * @var array $sg_format Normalised format record.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $sg_format ) ) {
	return;
}

$sg_dpi = $sg_format['dpi'] ? $sg_format['dpi'] : 300;
?>
<ul class="sg-static__units">
	<?php foreach ( array( 'mm', 'cm', 'in', 'px' ) as $sg_unit ) : ?>
		<li>
			<?php
			$sg_line = SizeGuide\Data_Loader::dimensions( $sg_format, $sg_unit, $sg_dpi );

			if ( 'px' === $sg_unit && 'px' !== $sg_format['unit'] ) {
				$sg_line .= ' @ ' . $sg_dpi . ' DPI';
			}

			echo esc_html( $sg_line );
			?>
		</li>
	<?php endforeach; ?>
</ul>
