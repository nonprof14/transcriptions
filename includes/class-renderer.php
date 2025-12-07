<?php
/**
 * Renderer for shortcodes and templates
 *
 * @package TranscriptionsSync
 */

namespace TranscriptionsSync;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderer class
 */
class Renderer {

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
		add_shortcode( 'transcriptions_list', array( $this, 'render_transcriptions_list' ) );
		add_filter( 'template_include', array( $this, 'load_transcription_template' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_pdf_scripts' ) );
	}

	/**
	 * Render transcriptions list shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function render_transcriptions_list( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'groupby' => isset( $_GET['groupby'] ) ? sanitize_text_field( $_GET['groupby'] ) : 'maqam',
			),
			$atts,
			'transcriptions_list'
		);

		$groupby = $atts['groupby'];

		// Get transcriptions grouped by the specified field.
		switch ( $groupby ) {
			case 'composer':
				$grouped_transcriptions = $this->database->get_transcriptions_by_composer();
				$groupby_label          = __( 'Composer', 'transcriptions-sync' );
				break;

			case 'form':
				$grouped_transcriptions = $this->database->get_transcriptions_by_form();
				$groupby_label          = __( 'Form', 'transcriptions-sync' );
				break;

			case 'maqam':
			default:
				$grouped_transcriptions = $this->database->get_all_transcriptions_grouped();
				$groupby_label          = __( 'Maqam', 'transcriptions-sync' );
				$groupby                = 'maqam';
				break;
		}

		if ( empty( $grouped_transcriptions ) ) {
			return '<p class="transcriptions-empty">' . esc_html__( 'No transcriptions found.', 'transcriptions-sync' ) . '</p>';
		}

		// Start output buffering.
		ob_start();

		// Load template.
		$template_path = TRANSCRIPTIONS_SYNC_PLUGIN_DIR . 'templates/list-view.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback inline rendering.
			$this->render_list_fallback( $grouped_transcriptions, $groupby );
		}

		return ob_get_clean();
	}

	/**
	 * Fallback rendering for list view
	 *
	 * @param array  $grouped_transcriptions Grouped transcriptions data.
	 * @param string $groupby Grouping type.
	 */
	private function render_list_fallback( $grouped_transcriptions, $groupby = 'maqam' ) {
		?>
		<div class="transcriptions-list-container">
			<div class="transcriptions-table">
				<div class="transcriptions-header">
					<div class="transcriptions-col">Maqam</div>
					<div class="transcriptions-col">Composer</div>
					<div class="transcriptions-col">Form</div>
				</div>

				<?php foreach ( $grouped_transcriptions as $maqam => $transcriptions ) : ?>
					<div class="transcriptions-maqam-group">
						<h2 class="transcriptions-maqam-heading"><?php echo esc_html( $maqam ); ?></h2>

						<?php foreach ( $transcriptions as $transcription ) : ?>
							<div class="transcriptions-row">
								<div class="transcriptions-col transcriptions-title">
									<a href="<?php echo esc_url( $transcription['url'] ); ?>">
										<?php echo esc_html( $transcription['title'] ); ?>
									</a>
								</div>
								<div class="transcriptions-col transcriptions-composer">
									<a href="<?php echo esc_url( $transcription['url'] ); ?>">
										<?php echo esc_html( $transcription['composer'] ); ?>
									</a>
								</div>
								<div class="transcriptions-col transcriptions-form">
									<?php echo esc_html( $transcription['form'] ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Load custom template for transcription pages
	 *
	 * @param string $template Template path.
	 * @return string Modified template path.
	 */
	public function load_transcription_template( $template ) {
		// Check if this is a page with transcription data.
		if ( is_page() ) {
			global $post;

			$contentful_id = get_post_meta( $post->ID, '_transcriptions_contentful_id', true );

			if ( ! empty( $contentful_id ) ) {
				$custom_template = TRANSCRIPTIONS_SYNC_PLUGIN_DIR . 'templates/single-transcription.php';

				if ( file_exists( $custom_template ) ) {
					return $custom_template;
				}
			}
		}

		return $template;
	}

	/**
	 * Check if current page is a transcription
	 *
	 * @param int $page_id Page ID.
	 * @return bool Whether page is a transcription.
	 */
	public function is_transcription_page( $page_id = null ) {
		if ( null === $page_id ) {
			$page_id = get_the_ID();
		}

		if ( ! $page_id ) {
			return false;
		}

		$contentful_id = get_post_meta( $page_id, '_transcriptions_contentful_id', true );

		return ! empty( $contentful_id );
	}

	/**
	 * Enqueue PDF.js scripts on transcription pages
	 */
	public function enqueue_pdf_scripts() {
		// Only enqueue on transcription pages.
		if ( ! $this->is_transcription_page() ) {
			return;
		}

		// PDF.js library from CDN.
		wp_enqueue_script(
			'pdfjs-lib',
			'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
			array(),
			'3.11.174',
			true
		);

		// Custom PDF handler with AJAX navigation support.
		wp_enqueue_script(
			'transcriptions-pdf-handler',
			plugin_dir_url( __FILE__ ) . '../assets/js/pdf-handler.js',
			array( 'pdfjs-lib' ),
			'1.0.0',
			true
		);

		// Pass plugin URL to JavaScript for worker path.
		wp_localize_script(
			'transcriptions-pdf-handler',
			'transcriptionsData',
			array(
				'pluginUrl' => plugin_dir_url( __FILE__ ) . '..',
			)
		);
	}
}
