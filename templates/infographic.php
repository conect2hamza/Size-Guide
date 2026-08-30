<?php
/**
 * Server-rendered infographic for one size record.
 *
 * Useful in themes and for no-JS rendering: it prints the same guide SVG the
 * download produces, inlined into the page.
 *
 * @package SizeGuide
 *
 * @var array  $sg_format Normalised format record.
 * @var string $sg_type   "clean" or "guide".
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $sg_format ) ) {
	return;
}

$sg_type = isset( $sg_type ) && 'clean' === $sg_type ? 'clean' : 'guide';
$sg_svg  = SizeGuide\Template_Generator::build_svg( $sg_format, $sg_type );

// Drop the XML declaration so the markup can be inlined in an HTML document.
$sg_svg = preg_replace( '/^<\?xml[^>]*\?>\s*/', '', $sg_svg );
?>
<figure class="sg-infographic sg-infographic--static">
	<div class="sg-infographic__canvas">
		<?php
		echo wp_kses(
			$sg_svg,
			array(
				'svg'  => array(
					'xmlns'      => true,
					'width'      => true,
					'height'     => true,
					'viewbox'    => true,
					'role'       => true,
					'aria-label' => true,
					'class'      => true,
				),
				'title' => array(),
				'desc'  => array(),
				'g'     => array(
					'id'   => true,
					'fill' => true,
				),
				'rect' => array(
					'x'                => true,
					'y'                => true,
					'width'            => true,
					'height'           => true,
					'fill'             => true,
					'stroke'           => true,
					'stroke-width'     => true,
					'stroke-dasharray' => true,
				),
				'path' => array(
					'd'                => true,
					'stroke'           => true,
					'stroke-width'     => true,
					'stroke-dasharray' => true,
					'fill'             => true,
				),
				'text' => array(
					'x'             => true,
					'y'             => true,
					'fill'          => true,
					'font-size'     => true,
					'font-family'   => true,
					'text-anchor'   => true,
					'letter-spacing' => true,
					'transform'     => true,
				),
			)
		);
		?>
	</div>
	<figcaption class="sg-infographic__caption">
		<?php
		echo esc_html(
			SizeGuide\Data_Loader::dimensions( $sg_format ) . ' · ' . $sg_format['aspect_ratio']
		);
		?>
	</figcaption>
</figure>
