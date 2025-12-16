<?php
/**
 * Admin interface handler
 *
 * @package TranscriptionsSync
 */

namespace TranscriptionsSync;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 */
class Admin {

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->database = new Database();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'manage_pages_columns', array( $this, 'add_custom_columns' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'transcriptions_details',
			__( 'Transcription Details', 'transcriptions-sync' ),
			array( $this, 'render_details_meta_box' ),
			'page',
			'normal',
			'high'
		);

		add_meta_box(
			'transcriptions_sync',
			__( 'Contentful Sync', 'transcriptions-sync' ),
			array( $this, 'render_sync_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Render details meta box
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_details_meta_box( $post ) {
		// Add nonce for security.
		wp_nonce_field( 'transcriptions_save_meta', 'transcriptions_meta_nonce' );

		// Get current values.
		$composer    = get_post_meta( $post->ID, '_transcriptions_composer', true );
		$form        = get_post_meta( $post->ID, '_transcriptions_form', true );
		$iqa_rhythm  = get_post_meta( $post->ID, '_transcriptions_iqa_rhythm', true );
		$pdf_url     = get_post_meta( $post->ID, '_transcriptions_pdf_url', true );
		$about       = get_post_meta( $post->ID, '_transcriptions_about', true );
		$text        = get_post_meta( $post->ID, '_transcriptions_text', true );
		$translation = get_post_meta( $post->ID, '_transcriptions_translation', true );
		$analysis    = get_post_meta( $post->ID, '_transcriptions_analysis', true );

		?>
		<table class="form-table">
			<tr>
				<th>
					<label for="transcriptions_composer">
						<?php esc_html_e( 'Composer', 'transcriptions-sync' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="transcriptions_composer"
						name="transcriptions_composer"
						value="<?php echo esc_attr( $composer ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Name of the composer', 'transcriptions-sync' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="transcriptions_form">
						<?php esc_html_e( 'Form', 'transcriptions-sync' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="transcriptions_form"
						name="transcriptions_form"
						value="<?php echo esc_attr( $form ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Musical form (e.g., Samai, Longa, Taqsim)', 'transcriptions-sync' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="transcriptions_iqa_rhythm">
						<?php esc_html_e( 'Iqa (Rhythm)', 'transcriptions-sync' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="transcriptions_iqa_rhythm"
						name="transcriptions_iqa_rhythm"
						value="<?php echo esc_attr( $iqa_rhythm ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Rhythmic pattern or time signature', 'transcriptions-sync' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="transcriptions_pdf_url">
						<?php esc_html_e( 'PDF URL', 'transcriptions-sync' ); ?>
					</label>
				</th>
				<td>
					<input
						type="url"
						id="transcriptions_pdf_url"
						name="transcriptions_pdf_url"
						value="<?php echo esc_url( $pdf_url ); ?>"
						class="regular-text"
					/>
					<button type="button" class="button transcriptions-upload-pdf">
						<?php esc_html_e( 'Upload PDF', 'transcriptions-sync' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'URL to the PDF transcription file', 'transcriptions-sync' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="transcriptions_about">
						<?php esc_html_e( 'About', 'transcriptions-sync' ); ?>
					</label>
				</th>
				<td>
					<textarea
						id="transcriptions_about"
						name="transcriptions_about"
						rows="4"
						class="large-text"
					><?php echo esc_textarea( $about ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Optional centered text about the composition. Displayed without a heading.', 'transcriptions-sync' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="transcriptions_text">
						<?php esc_html_e( 'Text (Arabic/Syrian)', 'transcriptions-sync' ); ?>
					</label>
				</th>
				<td>
					<textarea
						id="transcriptions_text"
						name="transcriptions_text"
						rows="6"
						class="large-text"
						dir="auto"
						style="direction: rtl; unicode-bidi: plaintext;"
					><?php echo esc_textarea( $text ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Optional text content (often Arabic/Syrian). Displayed centered with RTL support.', 'transcriptions-sync' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="transcriptions_translation">
						<?php esc_html_e( 'Translation', 'transcriptions-sync' ); ?>
					</label>
				</th>
				<td>
					<textarea
						id="transcriptions_translation"
						name="transcriptions_translation"
						rows="6"
						class="large-text"
					><?php echo esc_textarea( $translation ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Optional English translation of the text. Displayed left-aligned.', 'transcriptions-sync' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="transcriptions_analysis">
						<?php esc_html_e( 'Analysis', 'transcriptions-sync' ); ?>
					</label>
				</th>
				<td>
					<textarea
						id="transcriptions_analysis"
						name="transcriptions_analysis"
						rows="8"
						class="large-text"
					><?php echo esc_textarea( $analysis ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Optional analysis section with detailed commentary. Displayed left-aligned.', 'transcriptions-sync' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render sync meta box
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_sync_meta_box( $post ) {
		$contentful_id   = get_post_meta( $post->ID, '_transcriptions_contentful_id', true );
		$last_sync       = get_post_meta( $post->ID, '_transcriptions_contentful_last_sync', true );

		?>
		<div class="transcriptions-sync-info">
			<?php if ( ! empty( $contentful_id ) ) : ?>
				<p>
					<strong><?php esc_html_e( 'Contentful ID:', 'transcriptions-sync' ); ?></strong><br>
					<code><?php echo esc_html( $contentful_id ); ?></code>
				</p>

				<?php if ( ! empty( $last_sync ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'Last Synced:', 'transcriptions-sync' ); ?></strong><br>
						<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_sync ) ) ); ?>
					</p>
				<?php endif; ?>

				<p class="description">
					<?php esc_html_e( 'This transcription is synced from Contentful. The Contentful ID is immutable.', 'transcriptions-sync' ); ?>
				</p>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'This page is not synced with Contentful.', 'transcriptions-sync' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save meta boxes
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Check if this is a page.
		if ( 'page' !== $post->post_type ) {
			return;
		}

		// Check nonce.
		if ( ! isset( $_POST['transcriptions_meta_nonce'] ) ||
			! wp_verify_nonce( $_POST['transcriptions_meta_nonce'], 'transcriptions_save_meta' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		// Save composer.
		if ( isset( $_POST['transcriptions_composer'] ) ) {
			update_post_meta(
				$post_id,
				'_transcriptions_composer',
				sanitize_text_field( $_POST['transcriptions_composer'] )
			);
		}

		// Save form.
		if ( isset( $_POST['transcriptions_form'] ) ) {
			update_post_meta(
				$post_id,
				'_transcriptions_form',
				sanitize_text_field( $_POST['transcriptions_form'] )
			);
		}

		// Save iqa_rhythm.
		if ( isset( $_POST['transcriptions_iqa_rhythm'] ) ) {
			update_post_meta(
				$post_id,
				'_transcriptions_iqa_rhythm',
				sanitize_text_field( $_POST['transcriptions_iqa_rhythm'] )
			);
		}

		// Save pdf_url.
		if ( isset( $_POST['transcriptions_pdf_url'] ) ) {
			update_post_meta(
				$post_id,
				'_transcriptions_pdf_url',
				esc_url_raw( $_POST['transcriptions_pdf_url'] )
			);
		}

		// Save about.
		if ( isset( $_POST['transcriptions_about'] ) ) {
			update_post_meta(
				$post_id,
				'_transcriptions_about',
				wp_kses_post( $_POST['transcriptions_about'] )
			);
		}

		// Save text.
		if ( isset( $_POST['transcriptions_text'] ) ) {
			update_post_meta(
				$post_id,
				'_transcriptions_text',
				wp_kses_post( $_POST['transcriptions_text'] )
			);
		}

		// Save translation.
		if ( isset( $_POST['transcriptions_translation'] ) ) {
			update_post_meta(
				$post_id,
				'_transcriptions_translation',
				wp_kses_post( $_POST['transcriptions_translation'] )
			);
		}

		// Save analysis.
		if ( isset( $_POST['transcriptions_analysis'] ) ) {
			update_post_meta(
				$post_id,
				'_transcriptions_analysis',
				wp_kses_post( $_POST['transcriptions_analysis'] )
			);
		}
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Transcriptions', 'transcriptions-sync' ),
			__( 'Transcriptions', 'transcriptions-sync' ),
			'edit_pages',
			'transcriptions-sync',
			array( $this, 'render_admin_page' ),
			'dashicons-format-aside',
			30
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		// Get all transcription pages.
		$args = array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_transcriptions_contentful_id',
					'compare' => 'EXISTS',
				),
			),
		);

		$transcriptions = get_posts( $args );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Transcriptions', 'transcriptions-sync' ); ?></h1>

			<div class="transcriptions-admin-info">
				<h2><?php esc_html_e( 'API Information', 'transcriptions-sync' ); ?></h2>
				<p>
					<strong><?php esc_html_e( 'API Endpoint:', 'transcriptions-sync' ); ?></strong><br>
					<code><?php echo esc_url( rest_url( 'transcriptions/v1/entry' ) ); ?></code>
				</p>
				<p class="description">
					<?php esc_html_e( 'Use Application Passwords for authentication. See the README for details.', 'transcriptions-sync' ); ?>
				</p>
			</div>

			<h2><?php esc_html_e( 'All Transcriptions', 'transcriptions-sync' ); ?></h2>

			<?php if ( empty( $transcriptions ) ) : ?>
				<p><?php esc_html_e( 'No transcriptions found.', 'transcriptions-sync' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'transcriptions-sync' ); ?></th>
							<th><?php esc_html_e( 'Composer', 'transcriptions-sync' ); ?></th>
							<th><?php esc_html_e( 'Maqam', 'transcriptions-sync' ); ?></th>
							<th><?php esc_html_e( 'Form', 'transcriptions-sync' ); ?></th>
							<th><?php esc_html_e( 'Contentful ID', 'transcriptions-sync' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'transcriptions-sync' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $transcriptions as $transcription ) : ?>
							<?php
							$composer      = get_post_meta( $transcription->ID, '_transcriptions_composer', true );
							$form          = get_post_meta( $transcription->ID, '_transcriptions_form', true );
							$contentful_id = get_post_meta( $transcription->ID, '_transcriptions_contentful_id', true );
							$maqam_terms   = get_the_terms( $transcription->ID, 'maqam' );
							$maqam         = '';
							if ( $maqam_terms && ! is_wp_error( $maqam_terms ) ) {
								$maqam = $maqam_terms[0]->name;
							}
							?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( get_edit_post_link( $transcription->ID ) ); ?>">
											<?php echo esc_html( $transcription->post_title ); ?>
										</a>
									</strong>
								</td>
								<td><?php echo esc_html( ! empty( $composer ) ? $composer : __( 'Unknown', 'transcriptions-sync' ) ); ?></td>
								<td><?php echo esc_html( $maqam ); ?></td>
								<td><?php echo esc_html( $form ); ?></td>
								<td><code><?php echo esc_html( $contentful_id ); ?></code></td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $transcription->ID ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'transcriptions-sync' ); ?>
									</a>
									<a href="<?php echo esc_url( get_permalink( $transcription->ID ) ); ?>" class="button button-small" target="_blank">
										<?php esc_html_e( 'View', 'transcriptions-sync' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Add custom columns to pages list
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_custom_columns( $columns ) {
		// Add after title column.
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['transcription_composer'] = __( 'Composer', 'transcriptions-sync' );
				$new_columns['transcription_maqam']    = __( 'Maqam', 'transcriptions-sync' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom columns
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_custom_columns( $column, $post_id ) {
		// Only show for transcription pages.
		$contentful_id = get_post_meta( $post_id, '_transcriptions_contentful_id', true );
		if ( empty( $contentful_id ) ) {
			return;
		}

		switch ( $column ) {
			case 'transcription_composer':
				$composer = get_post_meta( $post_id, '_transcriptions_composer', true );
				echo esc_html( ! empty( $composer ) ? $composer : __( 'Unknown', 'transcriptions-sync' ) );
				break;

			case 'transcription_maqam':
				$maqam_terms = get_the_terms( $post_id, 'maqam' );
				if ( $maqam_terms && ! is_wp_error( $maqam_terms ) ) {
					echo esc_html( $maqam_terms[0]->name );
				}
				break;
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on page edit screens and transcriptions admin page.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'toplevel_page_transcriptions-sync' ), true ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'transcriptions-sync-admin',
			TRANSCRIPTIONS_SYNC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			TRANSCRIPTIONS_SYNC_VERSION
		);

		wp_enqueue_script(
			'transcriptions-sync-admin',
			TRANSCRIPTIONS_SYNC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			TRANSCRIPTIONS_SYNC_VERSION,
			true
		);
	}
}
