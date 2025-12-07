<?php
/**
 * Single transcription template
 *
 * @package TranscriptionsSync
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'transcription-single' ); ?>>
				<div class="entry-content transcription-content">
					<?php
					// Get transcription data.
					$composer   = get_post_meta( get_the_ID(), '_transcriptions_composer', true );
					$iqa_rhythm = get_post_meta( get_the_ID(), '_transcriptions_iqa_rhythm', true );
					$pdf_url    = get_post_meta( get_the_ID(), '_transcriptions_pdf_url', true );
					$about      = get_post_meta( get_the_ID(), '_transcriptions_about', true );
					$text       = get_post_meta( get_the_ID(), '_transcriptions_text', true );
					$analysis   = get_post_meta( get_the_ID(), '_transcriptions_analysis', true );

					// Get Maqam.
					$maqam_terms = get_the_terms( get_the_ID(), 'maqam' );
					$maqam       = '';
					if ( $maqam_terms && ! is_wp_error( $maqam_terms ) ) {
						$maqam = $maqam_terms[0]->name;
					}
					?>

					<div class="transcription-single-container">
						<!-- Title - Centered -->
						<?php the_title( '<h1 class="transcription-title">', '</h1>' ); ?>

						<!-- Composer - Centered below title -->
						<?php if ( ! empty( $composer ) ) : ?>
							<h2 class="transcription-composer"><?php echo esc_html( $composer ); ?></h2>
						<?php endif; ?>

						<!-- About Section (Optional - No heading) -->
						<?php if ( ! empty( $about ) ) : ?>
							<div class="transcription-about">
								<?php echo wp_kses_post( wpautop( $about ) ); ?>
							</div>
						<?php endif; ?>

						<!-- Maqam Section -->
						<?php if ( ! empty( $maqam ) ) : ?>
							<div class="transcription-section">
								<h3 class="transcription-section-heading"><?php esc_html_e( 'Maqam', 'transcriptions-sync' ); ?></h3>
								<div class="section-separator"></div>
								<p class="section-value"><?php echo esc_html( $maqam ); ?></p>
							</div>
						<?php endif; ?>

						<!-- Iqa (Rhythm) Section -->
						<?php if ( ! empty( $iqa_rhythm ) ) : ?>
							<div class="transcription-section">
								<h3 class="transcription-section-heading"><?php esc_html_e( 'Iqa (Rhythm)', 'transcriptions-sync' ); ?></h3>
								<div class="section-separator iqa"></div>
								<p class="section-value"><?php echo esc_html( $iqa_rhythm ); ?></p>
							</div>
						<?php endif; ?>

						<!-- Transcription Section -->
						<?php if ( ! empty( $pdf_url ) ) : ?>
							<div class="transcription-section transcription-pdf-section">
								<h3 class="transcription-section-heading"><?php esc_html_e( 'Transcription', 'transcriptions-sync' ); ?></h3>
								<div class="section-separator"></div>

								<!-- PDF.js Viewer -->
								<div class="transcription-pdf-viewer" data-pdf-url="<?php echo esc_url( $pdf_url ); ?>">
									<!-- PDF Canvas -->
									<div class="pdf-canvas-wrapper">
										<canvas id="pdf-canvas"></canvas>
										<div class="pdf-loading"><?php esc_html_e( 'Loading PDF...', 'transcriptions-sync' ); ?></div>
									</div>

									<!-- Navigation Controls -->
									<div class="pdf-controls" id="pdf-controls">
										<button class="pdf-nav-btn" id="pdf-prev" disabled>
											← <?php esc_html_e( 'Previous', 'transcriptions-sync' ); ?>
										</button>
										<button class="pdf-nav-btn" id="pdf-next">
											<?php esc_html_e( 'Next', 'transcriptions-sync' ); ?> →
										</button>
									</div>

									<!-- Download Button -->
									<div class="pdf-download-section">
										<a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener" class="pdf-download-btn">
											<?php esc_html_e( 'Download PDF', 'transcriptions-sync' ); ?>
										</a>
									</div>
								</div>
								<!-- PDF.js scripts are enqueued via class-renderer.php -->
							</div>
						<?php endif; ?>

						<!-- Text Section (Optional - With heading, supports RTL/Arabic) -->
						<?php if ( ! empty( $text ) ) : ?>
							<div class="transcription-section">
								<h3 class="transcription-section-heading"><?php esc_html_e( 'Text', 'transcriptions-sync' ); ?></h3>
								<div class="section-separator"></div>
								<div class="section-text-content">
									<?php echo wp_kses_post( wpautop( $text ) ); ?>
								</div>
							</div>
						<?php endif; ?>

						<!-- Analysis Section (Optional - With heading, left-aligned) -->
						<?php if ( ! empty( $analysis ) ) : ?>
							<div class="transcription-section">
								<h3 class="transcription-section-heading"><?php esc_html_e( 'Analysis', 'transcriptions-sync' ); ?></h3>
								<div class="section-separator"></div>
								<div class="section-analysis-content">
									<?php echo wp_kses_post( wpautop( $analysis ) ); ?>
								</div>
							</div>
						<?php endif; ?>

						<?php
						// Display any additional content from the page editor.
						the_content();
						?>
					</div>
				</div>

			</article>

		<?php endwhile; ?>

	</main>
</div>

<?php
get_sidebar();
get_footer();
