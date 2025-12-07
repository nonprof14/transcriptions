/**
 * Transcriptions Sync - Admin JavaScript
 *
 * @package TranscriptionsSync
 */

(function($) {
	'use strict';

	/**
	 * Initialize when DOM is ready
	 */
	$(document).ready(function() {
		initMediaUploader();
	});

	/**
	 * Initialize WordPress Media Uploader for PDF field
	 */
	function initMediaUploader() {
		var mediaUploader;
		var $uploadButton = $('.transcriptions-upload-pdf');
		var $pdfUrlField = $('#transcriptions_pdf_url');

		// Check if elements exist
		if (!$uploadButton.length || !$pdfUrlField.length) {
			return;
		}

		$uploadButton.on('click', function(e) {
			e.preventDefault();

			// If the uploader object has already been created, reopen the dialog
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}

			// Create the media uploader
			mediaUploader = wp.media({
				title: 'Select PDF Transcription',
				button: {
					text: 'Use this PDF'
				},
				library: {
					type: 'application/pdf'
				},
				multiple: false
			});

			// When a file is selected, run a callback
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();

				// Set the PDF URL in the field
				$pdfUrlField.val(attachment.url);

				// Show a preview link if desired
				showPdfPreview(attachment);
			});

			// Open the uploader dialog
			mediaUploader.open();
		});
	}

	/**
	 * Show PDF preview/info
	 *
	 * @param {Object} attachment - WordPress media attachment object
	 */
	function showPdfPreview(attachment) {
		var $pdfUrlField = $('#transcriptions_pdf_url');
		var $existingPreview = $('.transcriptions-pdf-preview');

		// Remove existing preview
		if ($existingPreview.length) {
			$existingPreview.remove();
		}

		// Create preview element
		var previewHtml = '<div class="transcriptions-pdf-preview" style="margin-top: 10px;">' +
			'<p style="margin: 0;">' +
			'<strong>Selected:</strong> ' + escapeHtml(attachment.filename) + '<br>' +
			'<a href="' + escapeHtml(attachment.url) + '" target="_blank" rel="noopener">View PDF</a>' +
			' | ' +
			'<a href="#" class="transcriptions-remove-pdf">Remove</a>' +
			'</p>' +
			'</div>';

		// Insert after the URL field
		$pdfUrlField.closest('td').append(previewHtml);

		// Handle remove click
		$('.transcriptions-remove-pdf').on('click', function(e) {
			e.preventDefault();
			$pdfUrlField.val('');
			$('.transcriptions-pdf-preview').remove();
		});
	}

	/**
	 * Escape HTML to prevent XSS
	 *
	 * @param {string} text - Text to escape
	 * @return {string} Escaped text
	 */
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	/**
	 * Show existing PDF preview on page load if URL exists
	 */
	function showExistingPdfPreview() {
		var $pdfUrlField = $('#transcriptions_pdf_url');

		if (!$pdfUrlField.length) {
			return;
		}

		var pdfUrl = $pdfUrlField.val();

		if (pdfUrl) {
			var filename = pdfUrl.split('/').pop();

			var attachment = {
				url: pdfUrl,
				filename: filename
			};

			showPdfPreview(attachment);
		}
	}

	// Show existing preview on load
	$(window).on('load', function() {
		showExistingPdfPreview();
	});

})(jQuery);
