<?php
/**
 * Platforms overview.
 *
 * @package SizeGuide
 *
 * @var array $dataset Normalised dataset.
 * @var array $stats   Dataset totals.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap sg-admin">
	<h1><?php esc_html_e( 'Platforms', 'size-guide' ); ?></h1>
	<p class="sg-admin__lead">
		<?php esc_html_e( 'Everything the guide knows about, read from the JSON dataset. Add or change platforms on the Import / Export screen.', 'size-guide' ); ?>
	</p>

	<?php foreach ( $dataset['sections'] as $sg_section ) : ?>
		<h2><?php echo esc_html( $sg_section['name'] ); ?></h2>

		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Platform', 'size-guide' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Group', 'size-guide' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Categories', 'size-guide' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Sizes', 'size-guide' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Shortcode', 'size-guide' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sg_section['groups'] as $sg_group ) : ?>
					<?php foreach ( $sg_group['platforms'] as $sg_platform ) : ?>
						<?php
						$sg_count = 0;
						foreach ( $sg_platform['categories'] as $sg_category ) {
							$sg_count += count( $sg_category['formats'] );
						}
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $sg_platform['name'] ); ?></strong>
								<code><?php echo esc_html( $sg_platform['id'] ); ?></code>
							</td>
							<td><?php echo esc_html( $sg_group['name'] ); ?></td>
							<td><?php echo esc_html( implode( ', ', wp_list_pluck( $sg_platform['categories'], 'name' ) ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $sg_count ) ); ?></td>
							<td><code>[size_guide platform="<?php echo esc_attr( $sg_platform['id'] ); ?>"]</code></td>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
</div>
