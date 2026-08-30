<?php
/**
 * Admin dashboard.
 *
 * @package SizeGuide
 *
 * @var array $dataset Normalised dataset.
 * @var array $stats   Dataset totals.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap sg-admin">
	<h1><?php esc_html_e( 'Size Guide', 'size-guide' ); ?></h1>
	<p class="sg-admin__lead">
		<?php esc_html_e( 'Every design size in one place — social media, web, ads, video and print, with safe zones, margins, bleed and downloadable templates.', 'size-guide' ); ?>
	</p>

	<div class="sg-admin__stats">
		<?php
		$sg_cards = array(
			__( 'Sections', 'size-guide' )  => $stats['sections'],
			__( 'Groups', 'size-guide' )    => $stats['groups'],
			__( 'Platforms', 'size-guide' ) => $stats['platforms'],
			__( 'Sizes', 'size-guide' )     => $stats['formats'],
		);

		foreach ( $sg_cards as $sg_label => $sg_value ) :
			?>
			<div class="sg-admin__stat">
				<span class="sg-admin__stat-value"><?php echo esc_html( number_format_i18n( $sg_value ) ); ?></span>
				<span class="sg-admin__stat-label"><?php echo esc_html( $sg_label ); ?></span>
			</div>
			<?php
		endforeach;
		?>
	</div>

	<div class="sg-admin__columns">
		<div class="sg-admin__panel">
			<h2><?php esc_html_e( 'Add the guide to a page', 'size-guide' ); ?></h2>
			<p><?php esc_html_e( 'Paste one of these shortcodes into any page, post or block.', 'size-guide' ); ?></p>

			<table class="widefat striped">
				<tbody>
					<?php
					$sg_shortcodes = array(
						'[size_guide]'                          => __( 'The full guide.', 'size-guide' ),
						'[size_guide section="print"]'          => __( 'Open on the print section.', 'size-guide' ),
						'[size_guide category="social"]'        => __( 'Open on social media.', 'size-guide' ),
						'[size_guide platform="instagram"]'     => __( 'Open on one platform.', 'size-guide' ),
						'[size_guide format="instagram-post-portrait"]' => __( 'Open one size straight away.', 'size-guide' ),
						'[size_guide search="a4"]'              => __( 'Open with a search already filled in.', 'size-guide' ),
						'[size_guide title="Our brand sizes"]'  => __( 'Change the heading.', 'size-guide' ),
					);

					foreach ( $sg_shortcodes as $sg_code => $sg_note ) :
						?>
						<tr>
							<td><code><?php echo esc_html( $sg_code ); ?></code></td>
							<td><?php echo esc_html( $sg_note ); ?></td>
						</tr>
						<?php
					endforeach;
					?>
				</tbody>
			</table>
		</div>

		<div class="sg-admin__panel">
			<h2><?php esc_html_e( 'Dataset', 'size-guide' ); ?></h2>
			<ul class="sg-admin__list">
				<?php foreach ( $dataset['sections'] as $sg_section ) : ?>
					<li>
						<strong><?php echo esc_html( $sg_section['name'] ); ?></strong>
						<span>
							<?php
							$sg_names = wp_list_pluck( $sg_section['groups'], 'name' );
							echo esc_html( implode( ', ', $sg_names ) );
							?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>

			<p>
				<?php
				printf(
					/* translators: %s: REST route */
					esc_html__( 'The same data is available over REST at %s.', 'size-guide' ),
					'<code>' . esc_html( rest_url( SizeGuide\Rest_API::NAMESPACE_ROOT . '/dataset' ) ) . '</code>'
				);
				?>
			</p>

			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=size-guide&size_guide_action=flush_cache' ), 'size_guide_flush_cache' ) ); ?>">
					<?php esc_html_e( 'Clear dataset cache', 'size-guide' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=size-guide-data' ) ); ?>">
					<?php esc_html_e( 'Import / export', 'size-guide' ); ?>
				</a>
			</p>
		</div>
	</div>
</div>
