<?php
/**
 * The Size Guide app shell.
 *
 * Rendered by the [size_guide] shortcode. JavaScript takes over the marked
 * regions; the static block underneath keeps the content readable and
 * crawlable when scripts are unavailable.
 *
 * @package SizeGuide
 *
 * @var array $dataset Normalised dataset.
 * @var array $atts    Sanitised shortcode attributes.
 */

defined( 'ABSPATH' ) || exit;

$sg_uid   = wp_unique_id( 'sg-' );
$sg_title = '' !== $atts['title'] ? $atts['title'] : __( 'Size Guide', 'size-guide' );
?>
<div class="sg-app" data-sg-app
	data-section="<?php echo esc_attr( $atts['section'] ); ?>"
	data-platform="<?php echo esc_attr( $atts['platform'] ); ?>"
	data-format="<?php echo esc_attr( $atts['format'] ); ?>"
	data-group="<?php echo esc_attr( $atts['category'] ); ?>">

	<header class="sg-app__header">
		<div class="sg-app__titles">
			<h2 class="sg-app__title"><?php echo esc_html( $sg_title ); ?></h2>
			<p class="sg-app__subtitle"><?php esc_html_e( 'Design sizes &amp; specifications', 'size-guide' ); ?></p>
		</div>

		<div class="sg-search">
			<label class="sg-visually-hidden" for="<?php echo esc_attr( $sg_uid ); ?>-search">
				<?php esc_html_e( 'Search design sizes', 'size-guide' ); ?>
			</label>
			<input
				type="search"
				class="sg-search__input"
				id="<?php echo esc_attr( $sg_uid ); ?>-search"
				data-sg-role="search-input"
				placeholder="<?php esc_attr_e( 'Search any design size…', 'size-guide' ); ?>"
				autocomplete="off"
				role="combobox"
				aria-expanded="false"
				aria-autocomplete="list"
				aria-controls="<?php echo esc_attr( $sg_uid ); ?>-results"
				value="<?php echo esc_attr( $atts['search'] ); ?>">
			<button type="button" class="sg-search__clear" data-sg-role="search-clear" hidden>
				<span aria-hidden="true">&times;</span>
				<span class="sg-visually-hidden"><?php esc_html_e( 'Clear search', 'size-guide' ); ?></span>
			</button>
			<div class="sg-search__results" id="<?php echo esc_attr( $sg_uid ); ?>-results" data-sg-role="search-results" role="listbox" hidden></div>
		</div>
	</header>

	<p class="sg-visually-hidden" data-sg-role="status" role="status" aria-live="polite"></p>

	<nav class="sg-sections" data-sg-role="sections" aria-label="<?php esc_attr_e( 'Size Guide sections', 'size-guide' ); ?>"></nav>

	<div class="sg-layout">
		<aside class="sg-sidebar" data-sg-role="sidebar" aria-label="<?php esc_attr_e( 'Platforms', 'size-guide' ); ?>"></aside>
		<div class="sg-content" data-sg-role="content"></div>
	</div>

	<div class="sg-static">
		<?php
		$sg_static_platform = null;

		if ( '' !== $atts['platform'] ) {
			foreach ( $dataset['sections'] as $sg_section ) {
				foreach ( $sg_section['groups'] as $sg_group ) {
					foreach ( $sg_group['platforms'] as $sg_candidate ) {
						if ( $sg_candidate['id'] === $atts['platform'] ) {
							$sg_static_platform = $sg_candidate;
						}
					}
				}
			}
		}

		if ( $sg_static_platform ) {
			$sg_platform = $sg_static_platform;
			include SIZE_GUIDE_PATH . 'templates/platform.php';
		} else {
			foreach ( $dataset['sections'] as $sg_section ) :
				?>
				<section class="sg-static__section">
					<h3 class="sg-static__heading"><?php echo esc_html( $sg_section['name'] ); ?></h3>
					<?php foreach ( $sg_section['groups'] as $sg_group ) : ?>
						<?php foreach ( $sg_group['platforms'] as $sg_platform_item ) : ?>
							<h4 class="sg-static__platform"><?php echo esc_html( $sg_platform_item['name'] ); ?></h4>
							<ul class="sg-static__list">
								<?php foreach ( $sg_platform_item['categories'] as $sg_category ) : ?>
									<?php foreach ( $sg_category['formats'] as $sg_format ) : ?>
										<li>
											<strong><?php echo esc_html( $sg_format['name'] ); ?></strong>
											<span><?php echo esc_html( SizeGuide\Data_Loader::dimensions( $sg_format ) ); ?></span>
											<span><?php echo esc_html( $sg_format['aspect_ratio'] ); ?></span>
										</li>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</ul>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</section>
				<?php
			endforeach;
		}
		?>
	</div>
</div>
