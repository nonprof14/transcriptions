<?php
/**
 * Database operations handler
 *
 * @package TranscriptionsSync
 */

namespace TranscriptionsSync;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database class
 */
class Database {

	/**
	 * Meta key prefix
	 */
	const META_PREFIX = '_transcriptions_';

	/**
	 * Find page by Contentful ID
	 *
	 * @param string $contentful_id Contentful ID.
	 * @return int|null Page ID or null if not found.
	 */
	public function find_by_contentful_id( $contentful_id ) {
		$args = array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'     => self::META_PREFIX . 'contentful_id',
					'value'   => sanitize_text_field( $contentful_id ),
					'compare' => '=',
				),
			),
			'fields'         => 'ids',
		);

		$posts = get_posts( $args );

		return ! empty( $posts ) ? $posts[0] : null;
	}

	/**
	 * Create transcription page
	 *
	 * @param array $data Transcription data.
	 * @return array Result with page_id and url or error.
	 */
	public function create_transcription( $data ) {
		// Validate required fields.
		$required_fields = array( 'contentful_id', 'title' );
		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return array(
					'success' => false,
					'error'   => sprintf( 'Missing required field: %s', $field ),
				);
			}
		}

		// Check if page already exists.
		$existing_page_id = $this->find_by_contentful_id( $data['contentful_id'] );
		if ( $existing_page_id ) {
			// Update existing page instead.
			return $this->update_transcription( $data['contentful_id'], $data );
		}

		// Create page.
		$page_data = array(
			'post_title'   => sanitize_text_field( $data['title'] ),
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '', // Content will be rendered via template.
		);

		$page_id = wp_insert_post( $page_data, true );

		if ( is_wp_error( $page_id ) ) {
			return array(
				'success' => false,
				'error'   => $page_id->get_error_message(),
			);
		}

		// Save meta data.
		$this->save_meta_data( $page_id, $data );

		// Assign Maqam taxonomy.
		if ( ! empty( $data['maqam'] ) ) {
			$this->assign_maqam( $page_id, $data['maqam'] );
		}

		return array(
			'success' => true,
			'page_id' => $page_id,
			'url'     => get_permalink( $page_id ),
		);
	}

	/**
	 * Update transcription page
	 *
	 * @param string $contentful_id Contentful ID.
	 * @param array  $data Transcription data.
	 * @return array Result with page_id and url or error.
	 */
	public function update_transcription( $contentful_id, $data ) {
		$page_id = $this->find_by_contentful_id( $contentful_id );

		if ( ! $page_id ) {
			return array(
				'success' => false,
				'error'   => 'Transcription not found',
			);
		}

		// Update page title if provided.
		if ( ! empty( $data['title'] ) ) {
			$page_data = array(
				'ID'         => $page_id,
				'post_title' => sanitize_text_field( $data['title'] ),
			);

			$result = wp_update_post( $page_data, true );

			if ( is_wp_error( $result ) ) {
				return array(
					'success' => false,
					'error'   => $result->get_error_message(),
				);
			}
		}

		// Update meta data.
		$this->save_meta_data( $page_id, $data );

		// Update Maqam taxonomy.
		if ( ! empty( $data['maqam'] ) ) {
			$this->assign_maqam( $page_id, $data['maqam'] );
		}

		return array(
			'success' => true,
			'page_id' => $page_id,
			'url'     => get_permalink( $page_id ),
		);
	}

	/**
	 * Get transcription data
	 *
	 * @param string $contentful_id Contentful ID.
	 * @return array|null Transcription data or null if not found.
	 */
	public function get_transcription( $contentful_id ) {
		$page_id = $this->find_by_contentful_id( $contentful_id );

		if ( ! $page_id ) {
			return null;
		}

		$page = get_post( $page_id );

		if ( ! $page ) {
			return null;
		}

		// Get maqam terms.
		$maqam_terms = get_the_terms( $page_id, 'maqam' );
		$maqam       = '';
		if ( $maqam_terms && ! is_wp_error( $maqam_terms ) ) {
			$maqam = $maqam_terms[0]->name;
		}

		return array(
			'page_id'               => $page_id,
			'title'                 => $page->post_title,
			'url'                   => get_permalink( $page_id ),
			'composer'              => get_post_meta( $page_id, self::META_PREFIX . 'composer', true ),
			'maqam'                 => $maqam,
			'form'                  => get_post_meta( $page_id, self::META_PREFIX . 'form', true ),
			'iqa_rhythm'            => get_post_meta( $page_id, self::META_PREFIX . 'iqa_rhythm', true ),
			'pdf_url'               => get_post_meta( $page_id, self::META_PREFIX . 'pdf_url', true ),
			'about'                 => get_post_meta( $page_id, self::META_PREFIX . 'about', true ),
			'text'                  => get_post_meta( $page_id, self::META_PREFIX . 'text', true ),
			'translation'           => get_post_meta( $page_id, self::META_PREFIX . 'translation', true ),
			'analysis'              => get_post_meta( $page_id, self::META_PREFIX . 'analysis', true ),
			'contentful_id'         => get_post_meta( $page_id, self::META_PREFIX . 'contentful_id', true ),
			'contentful_last_sync'  => get_post_meta( $page_id, self::META_PREFIX . 'contentful_last_sync', true ),
		);
	}

	/**
	 * Delete transcription page
	 *
	 * @param string $contentful_id Contentful ID.
	 * @return array Result with success status or error.
	 */
	public function delete_transcription( $contentful_id ) {
		$page_id = $this->find_by_contentful_id( $contentful_id );

		if ( ! $page_id ) {
			return array(
				'success' => false,
				'error'   => 'Transcription not found',
			);
		}

		$result = wp_delete_post( $page_id, true );

		if ( ! $result ) {
			return array(
				'success' => false,
				'error'   => 'Failed to delete transcription',
			);
		}

		return array(
			'success' => true,
		);
	}

	/**
	 * Get all transcriptions grouped by Maqam
	 *
	 * @return array Transcriptions grouped by Maqam.
	 */
	public function get_all_transcriptions_grouped() {
		// Get all Maqam terms.
		$maqam_terms = get_terms(
			array(
				'taxonomy'   => 'maqam',
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $maqam_terms ) || empty( $maqam_terms ) ) {
			return array();
		}

		$grouped = array();

		foreach ( $maqam_terms as $term ) {
			// Get pages for this Maqam.
			$args = array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'tax_query'      => array(
					array(
						'taxonomy' => 'maqam',
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					),
				),
				'meta_query'     => array(
					array(
						'key'     => self::META_PREFIX . 'contentful_id',
						'compare' => 'EXISTS',
					),
				),
			);

			$pages = get_posts( $args );

			if ( ! empty( $pages ) ) {
				$transcriptions = array();

				foreach ( $pages as $page ) {
					$transcriptions[] = array(
						'id'       => $page->ID,
						'title'    => $page->post_title,
						'url'      => get_permalink( $page->ID ),
						'composer' => get_post_meta( $page->ID, self::META_PREFIX . 'composer', true ),
						'form'     => get_post_meta( $page->ID, self::META_PREFIX . 'form', true ),
						'maqam'    => $term->name,
					);
				}

				$grouped[ $term->name ] = $transcriptions;
			}
		}

		return $grouped;
	}

	/**
	 * Get transcriptions grouped by Composer (sorted by last name)
	 *
	 * @return array Transcriptions grouped by composer, sorted by last name.
	 */
	public function get_transcriptions_by_composer() {
		// Get all transcription pages.
		$args = array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => self::META_PREFIX . 'contentful_id',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => self::META_PREFIX . 'composer',
					'compare' => 'EXISTS',
				),
			),
		);

		$pages = get_posts( $args );

		if ( empty( $pages ) ) {
			return array();
		}

		// Build data with last name for sorting.
		$composers_data = array();

		foreach ( $pages as $page ) {
			$composer = get_post_meta( $page->ID, self::META_PREFIX . 'composer', true );

			if ( empty( $composer ) ) {
				continue;
			}

			$last_name = $this->get_last_name( $composer );

			// Get Maqam for this entry.
			$maqam_terms = get_the_terms( $page->ID, 'maqam' );
			$maqam       = '';
			if ( $maqam_terms && ! is_wp_error( $maqam_terms ) ) {
				$maqam = $maqam_terms[0]->name;
			}

			if ( ! isset( $composers_data[ $composer ] ) ) {
				$composers_data[ $composer ] = array(
					'last_name'      => $last_name,
					'transcriptions' => array(),
				);
			}

			$composers_data[ $composer ]['transcriptions'][] = array(
				'id'       => $page->ID,
				'title'    => $page->post_title,
				'url'      => get_permalink( $page->ID ),
				'composer' => $composer,
				'form'     => get_post_meta( $page->ID, self::META_PREFIX . 'form', true ),
				'maqam'    => $maqam,
			);
		}

		// Sort composers by last name.
		uasort( $composers_data, function( $a, $b ) {
			return strcasecmp( $a['last_name'], $b['last_name'] );
		});

		// Build final grouped array with composer name as key.
		$grouped = array();
		foreach ( $composers_data as $composer_name => $data ) {
			$grouped[ $composer_name ] = $data['transcriptions'];
		}

		return $grouped;
	}

	/**
	 * Extract last name from full name
	 *
	 * @param string $full_name Full name string.
	 * @return string Last name (last word in the name).
	 */
	private function get_last_name( $full_name ) {
		if ( empty( $full_name ) ) {
			return '';
		}

		$parts = explode( ' ', trim( $full_name ) );
		return end( $parts );
	}

	/**
	 * Get transcriptions grouped by Form
	 *
	 * @return array Transcriptions grouped by form type.
	 */
	public function get_transcriptions_by_form() {
		// Get all transcription pages.
		$args = array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => self::META_PREFIX . 'contentful_id',
					'compare' => 'EXISTS',
				),
			),
		);

		$pages = get_posts( $args );

		if ( empty( $pages ) ) {
			return array();
		}

		$grouped = array();

		foreach ( $pages as $page ) {
			$form = get_post_meta( $page->ID, self::META_PREFIX . 'form', true );

			if ( empty( $form ) ) {
				$form = __( 'Uncategorized', 'transcriptions-sync' );
			}

			// Get Maqam for this entry.
			$maqam_terms = get_the_terms( $page->ID, 'maqam' );
			$maqam       = '';
			if ( $maqam_terms && ! is_wp_error( $maqam_terms ) ) {
				$maqam = $maqam_terms[0]->name;
			}

			if ( ! isset( $grouped[ $form ] ) ) {
				$grouped[ $form ] = array();
			}

			$grouped[ $form ][] = array(
				'id'       => $page->ID,
				'title'    => $page->post_title,
				'url'      => get_permalink( $page->ID ),
				'composer' => get_post_meta( $page->ID, self::META_PREFIX . 'composer', true ),
				'form'     => $form,
				'maqam'    => $maqam,
			);
		}

		// Sort groups alphabetically by form name.
		ksort( $grouped );

		return $grouped;
	}

	/**
	 * Save meta data for transcription
	 *
	 * @param int   $page_id Page ID.
	 * @param array $data Transcription data.
	 */
	private function save_meta_data( $page_id, $data ) {
		// Fields that use sanitize_text_field.
		$text_fields = array(
			'contentful_id',
			'composer',
			'form',
			'iqa_rhythm',
			'pdf_url',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				update_post_meta(
					$page_id,
					self::META_PREFIX . $field,
					sanitize_text_field( $data[ $field ] )
				);
			}
		}

		// Fields that use wp_kses_post (allow HTML, preserve line breaks).
		$html_fields = array(
			'about',
			'text',
			'translation',
			'analysis',
		);

		foreach ( $html_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				update_post_meta(
					$page_id,
					self::META_PREFIX . $field,
					wp_kses_post( $data[ $field ] )
				);
			}
		}

		// Update last sync timestamp.
		update_post_meta(
			$page_id,
			self::META_PREFIX . 'contentful_last_sync',
			current_time( 'mysql' )
		);
	}

	/**
	 * Assign Maqam taxonomy to page
	 *
	 * @param int    $page_id Page ID.
	 * @param string $maqam_name Maqam name.
	 */
	private function assign_maqam( $page_id, $maqam_name ) {
		$maqam_name = sanitize_text_field( $maqam_name );

		// Check if term exists, create if not.
		$term = get_term_by( 'name', $maqam_name, 'maqam' );

		if ( ! $term ) {
			$term = wp_insert_term( $maqam_name, 'maqam' );
			if ( is_wp_error( $term ) ) {
				error_log( 'Failed to create Maqam term: ' . $term->get_error_message() );
				return;
			}
			$term_id = $term['term_id'];
		} else {
			$term_id = $term->term_id;
		}

		// Assign term to page.
		wp_set_object_terms( $page_id, array( $term_id ), 'maqam', false );
	}

	/**
	 * Get meta field value
	 *
	 * @param int    $page_id Page ID.
	 * @param string $field Field name (without prefix).
	 * @return mixed Field value.
	 */
	public function get_meta( $page_id, $field ) {
		return get_post_meta( $page_id, self::META_PREFIX . $field, true );
	}
}
