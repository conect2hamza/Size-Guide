<?php
/**
 * Read-only REST endpoints for the dataset.
 *
 * @package SizeGuide
 */

namespace SizeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the dataset over the WordPress REST API.
 */
class Rest_API {

	const NAMESPACE_ROOT = 'size-guide/v1';

	/**
	 * Singleton instance.
	 *
	 * @var Rest_API|null
	 */
	protected static $instance = null;

	/**
	 * Get the shared instance.
	 *
	 * @return Rest_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_ROOT,
			'/dataset',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dataset' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE_ROOT,
			'/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit' => array(
						'type'              => 'integer',
						'default'           => 25,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_ROOT,
			'/format/(?P<id>[a-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_format' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * GET /dataset
	 *
	 * @return \WP_REST_Response
	 */
	public function get_dataset() {
		$response = rest_ensure_response( Data_Loader::get_dataset() );

		// The dataset only changes when the files or the imported data change,
		// so let browsers and proxies hold on to it.
		$response->header( 'Cache-Control', 'public, max-age=3600' );

		return $response;
	}

	/**
	 * GET /search?q=
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function search( $request ) {
		$results = Data_Loader::search( $request->get_param( 'q' ), $request->get_param( 'limit' ) );

		return rest_ensure_response(
			array(
				'query'   => $request->get_param( 'q' ),
				'count'   => count( $results ),
				'results' => $results,
			)
		);
	}

	/**
	 * GET /format/{id}
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_format( $request ) {
		$format = Data_Loader::get_format( $request->get_param( 'id' ) );

		if ( ! $format ) {
			return new \WP_Error(
				'size_guide_format_not_found',
				__( 'No size record matched that id.', 'size-guide' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $format );
	}
}
