<?php
/**
 * WordPress admin screens.
 *
 * @package SizeGuide
 */

namespace SizeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Size Guide admin menu, settings and import/export tools.
 */
class Admin {

	const MENU_SLUG  = 'size-guide';
	const CAPABILITY = 'manage_options';

	/**
	 * Singleton instance.
	 *
	 * @var Admin|null
	 */
	protected static $instance = null;

	/**
	 * Notices queued for the current request.
	 *
	 * @var array<int,array{type:string,message:string}>
	 */
	protected $notices = array();

	/**
	 * Get the shared instance.
	 *
	 * @return Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . SIZE_GUIDE_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Add the menu and its sub-pages.
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Size Guide', 'size-guide' ),
			__( 'Size Guide', 'size-guide' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-editor-expand',
			58
		);

		$pages = array(
			self::MENU_SLUG               => array( __( 'Dashboard', 'size-guide' ), 'render_dashboard' ),
			self::MENU_SLUG . '-platforms' => array( __( 'Platforms', 'size-guide' ), 'render_platforms' ),
			self::MENU_SLUG . '-sizes'    => array( __( 'Sizes', 'size-guide' ), 'render_sizes' ),
			self::MENU_SLUG . '-data'     => array( __( 'Import / Export', 'size-guide' ), 'render_data' ),
			self::MENU_SLUG . '-settings' => array( __( 'Settings', 'size-guide' ), 'render_settings' ),
		);

		foreach ( $pages as $slug => $page ) {
			add_submenu_page(
				self::MENU_SLUG,
				$page[0],
				$page[0],
				self::CAPABILITY,
				$slug,
				array( $this, $page[1] )
			);
		}
	}

	/**
	 * Admin styles, loaded only on Size Guide screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'size-guide-admin',
			SIZE_GUIDE_URL . 'assets/css/admin.css',
			array(),
			SIZE_GUIDE_VERSION
		);
	}

	/**
	 * Quick link from the plugins list.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '-settings' ) ),
			esc_html__( 'Settings', 'size-guide' )
		);

		array_unshift( $links, $settings );

		return $links;
	}

	/**
	 * Register the settings group and its sanitiser.
	 */
	public function register_settings() {
		register_setting(
			'size_guide_settings_group',
			Size_Guide::SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => Size_Guide::default_settings(),
			)
		);
	}

	/**
	 * Sanitise submitted settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = Size_Guide::default_settings();
		$input    = is_array( $input ) ? $input : array();
		$clean    = array();

		$sections                   = array( 'digital', 'print' );
		$clean['default_section']   = in_array( $input['default_section'] ?? '', $sections, true )
			? $input['default_section']
			: $defaults['default_section'];

		$units                 = array( 'px', 'mm', 'cm', 'in' );
		$clean['default_unit'] = in_array( $input['default_unit'] ?? '', $units, true )
			? $input['default_unit']
			: $defaults['default_unit'];

		$dpi                  = isset( $input['default_dpi'] ) ? absint( $input['default_dpi'] ) : 0;
		$clean['default_dpi'] = ( $dpi >= 1 && $dpi <= 2400 ) ? $dpi : $defaults['default_dpi'];

		$clean['show_sources']    = empty( $input['show_sources'] ) ? 0 : 1;
		$clean['enable_download'] = empty( $input['enable_download'] ) ? 0 : 1;
		$clean['load_via_rest']   = empty( $input['load_via_rest'] ) ? 0 : 1;

		$color                 = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '';
		$clean['accent_color'] = $color ? $color : $defaults['accent_color'];

		$schemes               = array( 'light', 'dark', 'auto' );
		$clean['color_scheme'] = in_array( $input['color_scheme'] ?? '', $schemes, true )
			? $input['color_scheme']
			: $defaults['color_scheme'];

		$corners               = array( 'rounded', 'square' );
		$clean['corner_style'] = in_array( $input['corner_style'] ?? '', $corners, true )
			? $input['corner_style']
			: $defaults['corner_style'];

		$densities        = array( 'comfortable', 'compact' );
		$clean['density'] = in_array( $input['density'] ?? '', $densities, true )
			? $input['density']
			: $defaults['density'];

		Data_Loader::flush_cache();

		return $clean;
	}

	/**
	 * Handle the import, export and cache actions.
	 */
	public function handle_actions() {
		if ( empty( $_POST['size_guide_action'] ) && empty( $_GET['size_guide_action'] ) ) {
			return;
		}

		$action = isset( $_POST['size_guide_action'] )
			? sanitize_key( wp_unslash( $_POST['size_guide_action'] ) )
			: sanitize_key( wp_unslash( $_GET['size_guide_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce checked below.

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Size Guide data.', 'size-guide' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'size_guide_' . $action );

		switch ( $action ) {
			case 'export':
				$this->do_export();
				break;
			case 'import':
				$this->do_import();
				break;
			case 'reset_custom':
				delete_option( Data_Loader::CUSTOM_OPTION );
				Data_Loader::flush_cache();
				$this->redirect_with_notice( 'reset' );
				break;
			case 'flush_cache':
				Data_Loader::flush_cache();
				$this->redirect_with_notice( 'flushed' );
				break;
		}
	}

	/**
	 * Stream the full dataset as a JSON download.
	 */
	protected function do_export() {
		$scope = isset( $_POST['export_scope'] ) ? sanitize_key( wp_unslash( $_POST['export_scope'] ) ) : 'all';

		if ( 'custom' === $scope ) {
			$payload = get_option( Data_Loader::CUSTOM_OPTION, array() );
		} else {
			$payload = Data_Loader::get_dataset();
		}

		$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="size-guide-' . $scope . '-' . gmdate( 'Ymd' ) . '.json"' );

		echo $json; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- JSON download.
		exit;
	}

	/**
	 * Validate and store an uploaded or pasted dataset.
	 */
	protected function do_import() {
		$raw = '';

		if ( ! empty( $_FILES['size_guide_file']['tmp_name'] ) ) {
			$tmp = sanitize_text_field( wp_unslash( $_FILES['size_guide_file']['tmp_name'] ) );
			if ( is_uploaded_file( $tmp ) ) {
				$contents = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the just-uploaded temp file.
				$raw      = is_string( $contents ) ? $contents : '';
			}
		}

		if ( '' === $raw && ! empty( $_POST['size_guide_json'] ) ) {
			$raw = wp_unslash( $_POST['size_guide_json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as JSON below.
		}

		if ( '' === trim( (string) $raw ) ) {
			$this->redirect_with_notice( 'import_empty' );
		}

		$decoded = json_decode( (string) $raw, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			$this->redirect_with_notice( 'import_invalid' );
		}

		$groups = $this->extract_groups( $decoded );

		if ( ! $groups ) {
			$this->redirect_with_notice( 'import_shape' );
		}

		$mode     = isset( $_POST['import_mode'] ) ? sanitize_key( wp_unslash( $_POST['import_mode'] ) ) : 'replace';
		$existing = get_option( Data_Loader::CUSTOM_OPTION, array() );
		$existing = is_array( $existing ) ? $existing : array();

		if ( 'merge' === $mode && $existing ) {
			if ( isset( $existing['platforms'] ) ) {
				$existing = array( $existing );
			}
			$groups = array_merge( array_values( $existing ), $groups );
		}

		update_option( Data_Loader::CUSTOM_OPTION, $groups, false );
		Data_Loader::flush_cache();

		$this->redirect_with_notice( 'imported', count( $groups ) );
	}

	/**
	 * Pull importable groups out of a decoded payload.
	 *
	 * Accepts a single group, a list of groups, or a full dataset export.
	 *
	 * @param array $decoded Decoded JSON.
	 * @return array
	 */
	protected function extract_groups( array $decoded ) {
		// A full export: sections -> groups.
		if ( isset( $decoded['sections'] ) && is_array( $decoded['sections'] ) ) {
			$groups = array();
			foreach ( $decoded['sections'] as $section ) {
				if ( empty( $section['groups'] ) || ! is_array( $section['groups'] ) ) {
					continue;
				}
				foreach ( $section['groups'] as $group ) {
					if ( is_array( $group ) && ! empty( $group['platforms'] ) ) {
						$group['section'] = $section['id'] ?? 'digital';
						$groups[]         = $group;
					}
				}
			}
			return $groups;
		}

		// One group.
		if ( isset( $decoded['platforms'] ) && is_array( $decoded['platforms'] ) ) {
			return array( $decoded );
		}

		// A list of groups.
		$groups = array();
		foreach ( $decoded as $item ) {
			if ( is_array( $item ) && ! empty( $item['platforms'] ) ) {
				$groups[] = $item;
			}
		}

		return $groups;
	}

	/**
	 * Redirect back to the data screen carrying a notice code.
	 *
	 * @param string   $code  Notice code.
	 * @param int|null $count Optional count.
	 */
	protected function redirect_with_notice( $code, $count = null ) {
		$args = array(
			'page'       => self::MENU_SLUG . '-data',
			'sg_notice'  => $code,
		);

		if ( null !== $count ) {
			$args['sg_count'] = (int) $count;
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Print the notice matching ?sg_notice=.
	 */
	public function print_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only notice code.
		if ( empty( $_GET['sg_notice'] ) ) {
			return;
		}

		$code  = sanitize_key( wp_unslash( $_GET['sg_notice'] ) );
		$count = isset( $_GET['sg_count'] ) ? absint( $_GET['sg_count'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$messages = array(
			'imported'       => array(
				'success',
				sprintf(
					/* translators: %d: number of imported groups */
					_n( 'Imported %d dataset group.', 'Imported %d dataset groups.', max( 1, $count ), 'size-guide' ),
					$count
				),
			),
			'import_empty'   => array( 'error', __( 'Nothing to import — choose a file or paste JSON.', 'size-guide' ) ),
			'import_invalid' => array( 'error', __( 'That file is not valid JSON.', 'size-guide' ) ),
			'import_shape'   => array( 'error', __( 'That JSON does not contain any dataset groups with a "platforms" list.', 'size-guide' ) ),
			'reset'          => array( 'success', __( 'Imported data removed. Only the bundled dataset is active.', 'size-guide' ) ),
			'flushed'        => array( 'success', __( 'Dataset cache cleared.', 'size-guide' ) ),
		);

		if ( ! isset( $messages[ $code ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $messages[ $code ][0] ),
			esc_html( $messages[ $code ][1] )
		);
	}

	/**
	 * Dataset totals for the dashboard.
	 *
	 * @return array<string,int>
	 */
	public static function get_stats() {
		$dataset   = Data_Loader::get_dataset();
		$platforms = 0;
		$formats   = 0;
		$groups    = 0;

		foreach ( $dataset['sections'] as $section ) {
			foreach ( $section['groups'] as $group ) {
				++$groups;
				foreach ( $group['platforms'] as $platform ) {
					++$platforms;
					foreach ( $platform['categories'] as $category ) {
						$formats += count( $category['formats'] );
					}
				}
			}
		}

		return array(
			'sections'  => count( $dataset['sections'] ),
			'groups'    => $groups,
			'platforms' => $platforms,
			'formats'   => $formats,
		);
	}

	/**
	 * Render a page template.
	 *
	 * @param string $file File name inside /admin.
	 */
	protected function render_page( $file ) {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'size-guide' ), '', array( 'response' => 403 ) );
		}

		$dataset = Data_Loader::get_dataset();
		$stats   = self::get_stats();

		$this->print_notice();

		include SIZE_GUIDE_PATH . 'admin/' . $file;
	}

	/**
	 * Dashboard screen.
	 */
	public function render_dashboard() {
		$this->render_page( 'dashboard.php' );
	}

	/**
	 * Platforms screen.
	 */
	public function render_platforms() {
		$this->render_page( 'platforms.php' );
	}

	/**
	 * Sizes screen.
	 */
	public function render_sizes() {
		$this->render_page( 'sizes.php' );
	}

	/**
	 * Import / export screen.
	 */
	public function render_data() {
		$this->render_page( 'data.php' );
	}

	/**
	 * Settings screen.
	 */
	public function render_settings() {
		$this->render_page( 'settings.php' );
	}
}
