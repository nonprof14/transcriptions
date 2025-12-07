<?php
/**
 * REST API handler
 *
 * @package TranscriptionsSync
 */

namespace TranscriptionsSync;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API class
 */
class API {

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * API namespace
	 */
	const NAMESPACE = 'transcriptions/v1';

	/**
	 * Constructor
	 *
	 * @param Database $database Database instance.
	 */
	public function __construct( Database $database ) {
		$this->database = $database;
		$this->register_routes();
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// POST /wp-json/transcriptions/v1/entry - Create or update entry.
		register_rest_route(
			self::NAMESPACE,
			'/entry',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_or_update_entry' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_entry_schema(),
			)
		);

		// PUT /wp-json/transcriptions/v1/entry/{contentful_id} - Update entry.
		register_rest_route(
			self::NAMESPACE,
			'/entry/(?P<contentful_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_entry' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array_merge(
					array(
						'contentful_id' => array(
							'description' => 'Contentful ID',
							'type'        => 'string',
							'required'    => true,
						),
					),
					$this->get_entry_schema()
				),
			)
		);

		// GET /wp-json/transcriptions/v1/entry/{contentful_id} - Get entry.
		register_rest_route(
			self::NAMESPACE,
			'/entry/(?P<contentful_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_entry' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'contentful_id' => array(
						'description' => 'Contentful ID',
						'type'        => 'string',
						'required'    => true,
					),
				),
			)
		);

		// DELETE /wp-json/transcriptions/v1/entry/{contentful_id} - Delete entry.
		register_rest_route(
			self::NAMESPACE,
			'/entry/(?P<contentful_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_entry' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'contentful_id' => array(
						'description' => 'Contentful ID',
						'type'        => 'string',
						'required'    => true,
					),
				),
			)
		);
	}

	/**
	 * Get entry schema
	 *
	 * @return array Entry schema.
	 */
	private function get_entry_schema() {
		return array(
			'contentful_id' => array(
				'description'       => 'Unique Contentful identifier',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $param ) {
					return ! empty( $param );
				},
			),
			'title'         => array(
				'description'       => 'Transcription title',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $param ) {
					return ! empty( $param );
				},
			),
			'composer'      => array(
				'description'       => 'Composer name',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'maqam'         => array(
				'description'       => 'Maqam category',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'form'          => array(
				'description'       => 'Musical form',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'iqa_rhythm'    => array(
				'description'       => 'Iqa (Rhythm)',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'pdf_url'       => array(
				'description'       => 'PDF URL',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => function( $param ) {
					if ( empty( $param ) ) {
						return true;
					}
					return filter_var( $param, FILTER_VALIDATE_URL ) !== false;
				},
			),
			'about'         => array(
				'description'       => 'Optional description about the composition',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			),
			'text'          => array(
				'description'       => 'Optional text content (often Arabic/Syrian lyrics)',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			),
			'translation'   => array(
				'description'       => 'Optional translation of the text content',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			),
			'analysis'      => array(
				'description'       => 'Optional analysis section with detailed commentary',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			),
		);
	}

	/**
	 * Check API permission
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool Whether user has permission.
	 */
	public function check_permission( $request ) {
		// Check if user is authenticated and has edit_pages capability.
		return current_user_can( 'edit_pages' );
	}

	/**
	 * Create or update entry (POST endpoint)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function create_or_update_entry( $request ) {
		try {
			$data = array(
				'contentful_id' => $request->get_param( 'contentful_id' ),
				'title'         => $request->get_param( 'title' ),
				'composer'      => $request->get_param( 'composer' ),
				'maqam'         => $request->get_param( 'maqam' ),
				'form'          => $request->get_param( 'form' ),
				'iqa_rhythm'    => $request->get_param( 'iqa_rhythm' ),
				'pdf_url'       => $request->get_param( 'pdf_url' ),
				'about'         => $request->get_param( 'about' ),
				'text'          => $request->get_param( 'text' ),
				'translation'   => $request->get_param( 'translation' ),
				'analysis'      => $request->get_param( 'analysis' ),
			);

			// Try to create (will auto-update if exists).
			$result = $this->database->create_transcription( $data );

			if ( ! $result['success'] ) {
				return new \WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => $result['error'],
					),
					400
				);
			}

			// Check if it was created or updated.
			$existing = $this->database->find_by_contentful_id( $data['contentful_id'] );
			$status   = $existing ? 'updated' : 'created';

			return new \WP_REST_Response(
				array(
					'status'  => $status,
					'page_id' => $result['page_id'],
					'url'     => $result['url'],
				),
				$status === 'created' ? 201 : 200
			);

		} catch ( \Exception $e ) {
			error_log( 'Transcriptions Sync API Error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Internal server error',
				),
				500
			);
		}
	}

	/**
	 * Update entry (PUT endpoint)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function update_entry( $request ) {
		try {
			$contentful_id = $request->get_param( 'contentful_id' );

			$data = array(
				'contentful_id' => $contentful_id,
				'title'         => $request->get_param( 'title' ),
				'composer'      => $request->get_param( 'composer' ),
				'maqam'         => $request->get_param( 'maqam' ),
				'form'          => $request->get_param( 'form' ),
				'iqa_rhythm'    => $request->get_param( 'iqa_rhythm' ),
				'pdf_url'       => $request->get_param( 'pdf_url' ),
				'about'         => $request->get_param( 'about' ),
				'text'          => $request->get_param( 'text' ),
				'translation'   => $request->get_param( 'translation' ),
				'analysis'      => $request->get_param( 'analysis' ),
			);

			$result = $this->database->update_transcription( $contentful_id, $data );

			if ( ! $result['success'] ) {
				return new \WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => $result['error'],
					),
					404
				);
			}

			return new \WP_REST_Response(
				array(
					'status'  => 'updated',
					'page_id' => $result['page_id'],
					'url'     => $result['url'],
				),
				200
			);

		} catch ( \Exception $e ) {
			error_log( 'Transcriptions Sync API Error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Internal server error',
				),
				500
			);
		}
	}

	/**
	 * Get entry (GET endpoint)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function get_entry( $request ) {
		try {
			$contentful_id = $request->get_param( 'contentful_id' );

			$data = $this->database->get_transcription( $contentful_id );

			if ( ! $data ) {
				return new \WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => 'Transcription not found',
					),
					404
				);
			}

			return new \WP_REST_Response(
				array(
					'status' => 'success',
					'data'   => $data,
				),
				200
			);

		} catch ( \Exception $e ) {
			error_log( 'Transcriptions Sync API Error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Internal server error',
				),
				500
			);
		}
	}

	/**
	 * Delete entry (DELETE endpoint)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function delete_entry( $request ) {
		try {
			$contentful_id = $request->get_param( 'contentful_id' );

			$result = $this->database->delete_transcription( $contentful_id );

			if ( ! $result['success'] ) {
				return new \WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => $result['error'],
					),
					404
				);
			}

			return new \WP_REST_Response(
				array(
					'status' => 'deleted',
				),
				200
			);

		} catch ( \Exception $e ) {
			error_log( 'Transcriptions Sync API Error: ' . $e->getMessage() );

			return new \WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Internal server error',
				),
				500
			);
		}
	}
}
