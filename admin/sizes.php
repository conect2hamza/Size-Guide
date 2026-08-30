<?php
/**
 * Browse every size record.
 *
 * @package SizeGuide
 *
 * @var array $dataset Normalised dataset.
 * @var array $stats   Dataset totals.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters on an admin listing.
$sg_filter_platform = isset( $_GET['sg_platform'] ) ? sanitize_key( wp_unslash( $_GET['sg_platform'] ) ) : '';
$sg_filter_search   = isset( $_GET['sg_search'] ) ? sanitize_text_field( wp_unslash( $_GET['sg_search'] ) ) : '';
$sg_paged           = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$sg_per_page = 50;
$sg_formats  = SizeGuide\Data_Loader::get_formats();
$sg_platforms = array();

foreach ( $sg_formats as $sg_format ) {
	$sg_platforms[ $sg_format['platform'] ] = $sg_format['platform_name'];
}
ksort( $sg_platforms );

if ( $sg_filter_platform ) {
	$sg_formats = array_values(
		array_filter(
			$sg_formats,
			static function ( $format ) use ( $sg_filter_platform ) {
				return $format['platform'] === $sg_filter_platform;
			}
		)
	);
}

if ( '' !== $sg_filter_search ) {
	$sg_needle  = strtolower( $sg_filter_search );
	$sg_formats = array_values(
		array_filter(
			$sg_formats,
			static function ( $format ) use ( $sg_needle ) {
				$haystack = strtolower(
					$format['name'] . ' ' . $format['platform_name'] . ' ' . $format['category_name'] . ' ' .
					implode( ' ', $format['aliases'] ) . ' ' . $format['width'] . 'x' . $format['height']
				);
				return false !== strpos( $haystack, $sg_needle );
			}
		)
	);
}

$sg_total  = count( $sg_formats );
$sg_pages  = max( 1, (int) ceil( $sg_total / $sg_per_page ) );
$sg_paged  = min( $sg_paged, $sg_pages );
$sg_slice  = array_slice( $sg_formats, ( $sg_paged - 1 ) * $sg_per_page, $sg_per_page );
?>
<div class="wrap sg-admin">
	<h1><?php esc_html_e( 'Sizes', 'size-guide' ); ?></h1>
	<p class="sg-admin__lead">
		<?php
		printf(
			/* translators: %s: number of size records */
			esc_html__( '%s size records are available to the frontend. Records live in the JSON dataset — use Import / Export to change them.', 'size-guide' ),
			esc_html( number_format_i18n( $stats['formats'] ) )
		);
		?>
	</p>

	<form method="get" class="sg-admin__filters">
		<input type="hidden" name="page" value="size-guide-sizes">

		<label class="screen-reader-text" for="sg_platform"><?php esc_html_e( 'Platform', 'size-guide' ); ?></label>
		<select name="sg_platform" id="sg_platform">
			<option value=""><?php esc_html_e( 'All platforms', 'size-guide' ); ?></option>
			<?php foreach ( $sg_platforms as $sg_id => $sg_name ) : ?>
				<option value="<?php echo esc_attr( $sg_id ); ?>" <?php selected( $sg_filter_platform, $sg_id ); ?>>
					<?php echo esc_html( $sg_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label class="screen-reader-text" for="sg_search"><?php esc_html_e( 'Search sizes', 'size-guide' ); ?></label>
		<input type="search" name="sg_search" id="sg_search" value="<?php echo esc_attr( $sg_filter_search ); ?>"
			placeholder="<?php esc_attr_e( 'Search sizes…', 'size-guide' ); ?>">

		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'size-guide' ); ?></button>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Name', 'size-guide' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Platform', 'size-guide' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Category', 'size-guide' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Size', 'size-guide' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Ratio', 'size-guide' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Safe zone', 'size-guide' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Bleed', 'size-guide' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Source', 'size-guide' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $sg_slice ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No sizes matched.', 'size-guide' ); ?></td></tr>
			<?php endif; ?>

			<?php foreach ( $sg_slice as $sg_row ) : ?>
				<tr>
					<td>
						<strong><?php echo esc_html( $sg_row['name'] ); ?></strong><br>
						<code><?php echo esc_html( $sg_row['id'] ); ?></code>
					</td>
					<td><?php echo esc_html( $sg_row['platform_name'] ); ?></td>
					<td><?php echo esc_html( $sg_row['category_name'] ); ?></td>
					<td><?php echo esc_html( SizeGuide\Data_Loader::dimensions( $sg_row ) ); ?></td>
					<td><?php echo esc_html( $sg_row['aspect_ratio'] ); ?></td>
					<td>
						<?php
						echo $sg_row['safe_zone']
							? esc_html( SizeGuide\Data_Loader::round_unit( $sg_row['safe_zone']['top'], $sg_row['unit'] ) . ' ' . $sg_row['unit'] )
							: '—';
						?>
					</td>
					<td>
						<?php
						echo $sg_row['bleed']
							? esc_html( SizeGuide\Data_Loader::round_unit( $sg_row['bleed'], $sg_row['unit'] ) . ' ' . $sg_row['unit'] )
							: '—';
						?>
					</td>
					<td><?php echo esc_html( $sg_row['source']['type'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $sg_pages > 1 ) : ?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $sg_paged,
							'total'     => $sg_pages,
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
						)
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
