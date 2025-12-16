<?php
/**
 * List view template for transcriptions
 *
 * @package TranscriptionsSync
 * @var array  $grouped_transcriptions Transcriptions grouped by field
 * @var string $groupby Current grouping type (maqam, composer, or form)
 * @var string $groupby_label Label for current grouping
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="transcriptions-list-container">
	<!-- Desktop Table View -->
	<div class="transcriptions-table-view">
		<!-- Header Row -->
		<div class="transcriptions-header">
			<div class="transcriptions-header-col maqam-col">
				<button class="header-filter-btn active" data-filter="maqam" aria-label="Filter by Maqam">
					<?php esc_html_e( 'MAQAM', 'transcriptions-sync' ); ?>
				</button>
			</div>
			<div class="transcriptions-header-col composer-col">
				<button class="header-filter-btn" data-filter="composer" aria-label="Filter by Composer">
					<?php esc_html_e( 'COMPOSER', 'transcriptions-sync' ); ?>
				</button>
			</div>
			<div class="transcriptions-header-col form-col">
				<button class="header-filter-btn" data-filter="form" aria-label="Filter by Form">
					<?php esc_html_e( 'FORM', 'transcriptions-sync' ); ?>
				</button>
			</div>
		</div>

		<!-- Content -->
		<div class="transcriptions-content">
			<?php foreach ( $grouped_transcriptions as $group_name => $transcriptions ) : ?>
				<div class="transcriptions-maqam-section">
					<!-- Maqam Category Heading -->
					<h3 class="transcriptions-maqam-name"><?php echo esc_html( $group_name ); ?></h3>

					<!-- Transcription Entries -->
					<div class="transcriptions-entries">
						<?php foreach ( $transcriptions as $transcription ) :
							$display_composer = ! empty( $transcription['composer'] ) ? $transcription['composer'] : __( 'Unknown', 'transcriptions-sync' );
						?>
							<div class="transcription-entry"
								data-title="<?php echo esc_attr( $transcription['title'] ); ?>"
								data-composer="<?php echo esc_attr( $display_composer ); ?>"
								data-maqam="<?php echo esc_attr( $transcription['maqam'] ); ?>"
								data-form="<?php echo esc_attr( $transcription['form'] ); ?>"
								data-url="<?php echo esc_url( $transcription['url'] ); ?>">
								<a href="<?php echo esc_url( $transcription['url'] ); ?>" class="transcription-link">
									<?php echo esc_html( $transcription['title'] ); ?> - <?php echo esc_html( $display_composer ); ?>
								</a>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Mobile View -->
	<div class="transcriptions-mobile-view">
		<!-- Mobile Header with Toggle -->
		<div class="mobile-filter-header">
			<button class="mobile-filter-toggle" aria-expanded="false" aria-label="Toggle filters">
				<span class="filter-label">Maqam</span>
				<span class="toggle-icon">+</span>
			</button>

			<!-- Mobile Filter Dropdown (hidden by default) -->
			<div class="mobile-filter-dropdown">
				<div class="filter-option" data-filter="composer">Composer</div>
				<div class="filter-option" data-filter="form">Form</div>
			</div>
		</div>

		<!-- Mobile Content -->
		<div class="transcriptions-mobile-content">
			<?php foreach ( $grouped_transcriptions as $group_name => $transcriptions ) : ?>
				<div class="transcriptions-maqam-section">
					<!-- Maqam Category Heading -->
					<h3 class="transcriptions-maqam-name"><?php echo esc_html( $group_name ); ?></h3>

					<!-- Transcription Entries -->
					<div class="transcriptions-entries">
						<?php foreach ( $transcriptions as $transcription ) :
							$display_composer = ! empty( $transcription['composer'] ) ? $transcription['composer'] : __( 'Unknown', 'transcriptions-sync' );
						?>
							<div class="transcription-entry"
								data-title="<?php echo esc_attr( $transcription['title'] ); ?>"
								data-composer="<?php echo esc_attr( $display_composer ); ?>"
								data-maqam="<?php echo esc_attr( $transcription['maqam'] ); ?>"
								data-form="<?php echo esc_attr( $transcription['form'] ); ?>"
								data-url="<?php echo esc_url( $transcription['url'] ); ?>">
								<a href="<?php echo esc_url( $transcription['url'] ); ?>" class="transcription-link">
									<?php echo esc_html( $transcription['title'] ); ?> - <?php echo esc_html( $display_composer ); ?>
								</a>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
