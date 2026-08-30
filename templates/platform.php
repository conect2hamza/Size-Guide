<?php
/**
 * Static rendering of one platform and its formats.
 *
 * @package SizeGuide
 *
 * @var array $sg_platform Normalised platform record.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $sg_platform ) ) {
	return;
}
?>
<section class="sg-static__section">
	<h3 class="sg-static__heading"><?php echo esc_html( $sg_platform['name'] ); ?></h3>

	<?php foreach ( $sg_platform['categories'] as $sg_category ) : ?>
		<h4 class="sg-static__platform"><?php echo esc_html( $sg_category['name'] ); ?></h4>

		<div class="sg-static__cards">
			<?php
			foreach ( $sg_category['formats'] as $sg_format ) {
				include SIZE_GUIDE_PATH . 'templates/format-card.php';
			}
			?>
		</div>
	<?php endforeach; ?>
</section>
