/**
 * Transcriptions Sync - PDF Handler
 * Handles PDF.js initialization with AJAX navigation support
 *
 * @package TranscriptionsSync
 */

(function() {
	'use strict';

	// PDF state variables
	let pdfDoc = null;
	let pageNum = 1;
	let pageRendering = false;
	let pageNumPending = null;
	let currentPdfUrl = null;

	/**
	 * Initialize PDF viewer
	 * Called on page load and AJAX page transitions
	 */
	function initPDF() {
		// Check if PDF viewer exists on this page
		const pdfViewer = document.querySelector('.transcription-pdf-viewer');
		if (!pdfViewer) {
			return;
		}

		// Check if already initialized (prevent double initialization)
		if (pdfViewer.dataset.initialized === 'true') {
			return;
		}

		// Check if PDF.js is loaded
		if (typeof pdfjsLib === 'undefined') {
			console.error('PDF.js library not loaded');
			return;
		}

		// Get PDF URL
		const pdfUrl = pdfViewer.dataset.pdfUrl;
		if (!pdfUrl) {
			console.error('No PDF URL provided');
			const loadingDiv = document.querySelector('.pdf-loading');
			if (loadingDiv) {
				loadingDiv.textContent = 'PDF URL not found.';
			}
			return;
		}

		console.log('Initializing PDF viewer for:', pdfUrl);

		// Use CDN worker (works on full page load without CORS issues)
		pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

		// Mark as initialized
		pdfViewer.dataset.initialized = 'true';

		// Reset state if loading a different PDF
		if (currentPdfUrl !== pdfUrl) {
			resetPDFState();
			currentPdfUrl = pdfUrl;
		}

		// Get elements
		const canvas = document.getElementById('pdf-canvas');
		const loadingDiv = document.querySelector('.pdf-loading');

		// Verify elements exist
		if (!canvas || !loadingDiv) {
			console.error('Required PDF elements not found');
			return;
		}

		// Clear canvas
		const ctx = canvas.getContext('2d');
		ctx.clearRect(0, 0, canvas.width, canvas.height);

		// Load PDF
		loadPDF(pdfUrl, canvas, ctx, loadingDiv);

		// Setup navigation controls
		setupNavigation();

		// Setup responsive resize
		setupResize();
	}

	/**
	 * Reset PDF state
	 */
	function resetPDFState() {
		pdfDoc = null;
		pageNum = 1;
		pageRendering = false;
		pageNumPending = null;
	}

	/**
	 * Load PDF document
	 */
	function loadPDF(url, canvas, ctx, loadingDiv) {
		const loadingTask = pdfjsLib.getDocument({
			url: url,
			withCredentials: false, // Don't send cookies (avoids CORS issues)
			isEvalSupported: false, // Disable eval for CSP compliance
		});

		loadingTask.promise.then(function(pdf) {
			console.log('PDF loaded successfully, pages:', pdf.numPages);
			pdfDoc = pdf;
			loadingDiv.style.display = 'none';

			// Hide controls if single page
			if (pdf.numPages === 1) {
				const controls = document.getElementById('pdf-controls');
				if (controls) {
					controls.style.display = 'none';
				}
			}

			// Render first page
			renderPage(pageNum, canvas, ctx);
		}).catch(function(error) {
			console.error('Error loading PDF:', error);
			
			// Show error with fallback download link
			const pdfViewer = document.querySelector('.transcription-pdf-viewer');
			if (pdfViewer) {
				const errorHTML = '<div class="pdf-error" style="text-align: center; padding: 40px 20px; background: #f8f8f8; border-radius: 8px; margin: 20px 0;">' +
					'<p style="color: #d32f2f; font-weight: 500; margin-bottom: 15px;">Unable to display PDF viewer.</p>' +
					'<p style="color: #666; margin-bottom: 20px;">The PDF viewer encountered an error.</p>' +
					'<a href="' + url + '" target="_blank" rel="noopener" class="pdf-download-btn" style="display: inline-block; padding: 12px 24px; background: #2c5282; color: white; text-decoration: none; border-radius: 4px;">Download PDF</a>' +
					'</div>';
				
				const canvasWrapper = pdfViewer.querySelector('.pdf-canvas-wrapper');
				if (canvasWrapper) {
					canvasWrapper.innerHTML = errorHTML;
				}
			}
		});
	}

	/**
	 * Render a page with high DPI
	 */
	function renderPage(num, canvas, ctx) {
		if (!pdfDoc) {
			console.error('PDF document not loaded');
			return;
		}

		if (!canvas) {
			canvas = document.getElementById('pdf-canvas');
		}
		if (!ctx) {
			ctx = canvas.getContext('2d');
		}

		pageRendering = true;
		pdfDoc.getPage(num).then(function(page) {
			// Get container width
			const container = document.querySelector('.pdf-canvas-wrapper');
			const containerWidth = container ? container.offsetWidth : 800;

			// Use device pixel ratio for high DPI displays
			const pixelRatio = window.devicePixelRatio || 1;

			// Calculate scale to fit width with quality multiplier
			const viewport = page.getViewport({ scale: 1 });
			const baseScale = containerWidth / viewport.width;

			// Multiply by pixel ratio and quality factor (1.5) for crisp rendering
			const outputScale = pixelRatio * 1.5;
			const finalScale = baseScale * outputScale;

			const scaledViewport = page.getViewport({ scale: finalScale });

			// Set canvas internal resolution (high DPI)
			canvas.height = scaledViewport.height;
			canvas.width = scaledViewport.width;

			// Set canvas display size (CSS pixels)
			canvas.style.width = containerWidth + 'px';
			canvas.style.height = (scaledViewport.height / outputScale) + 'px';

			// Render page
			const renderContext = {
				canvasContext: ctx,
				viewport: scaledViewport
			};

			const renderTask = page.render(renderContext);
			renderTask.promise.then(function() {
				pageRendering = false;
				if (pageNumPending !== null) {
					renderPage(pageNumPending, canvas, ctx);
					pageNumPending = null;
				}
			});
		});

		// Update buttons
		updateNavigationButtons();
	}

	/**
	 * Queue page rendering
	 */
	function queueRenderPage(num) {
		if (pageRendering) {
			pageNumPending = num;
		} else {
			const canvas = document.getElementById('pdf-canvas');
			const ctx = canvas ? canvas.getContext('2d') : null;
			renderPage(num, canvas, ctx);
		}
	}

	/**
	 * Update navigation button states
	 */
	function updateNavigationButtons() {
		const prevBtn = document.getElementById('pdf-prev');
		const nextBtn = document.getElementById('pdf-next');

		if (prevBtn) {
			prevBtn.disabled = (pageNum <= 1);
		}
		if (nextBtn && pdfDoc) {
			nextBtn.disabled = (pageNum >= pdfDoc.numPages);
		}
	}

	/**
	 * Setup navigation controls
	 */
	function setupNavigation() {
		const prevBtn = document.getElementById('pdf-prev');
		const nextBtn = document.getElementById('pdf-next');

		// Remove old listeners by cloning buttons
		if (prevBtn) {
			const newPrevBtn = prevBtn.cloneNode(true);
			prevBtn.parentNode.replaceChild(newPrevBtn, prevBtn);
			
			newPrevBtn.addEventListener('click', function() {
				if (pageNum <= 1) return;
				pageNum--;
				queueRenderPage(pageNum);
				scrollToViewer();
			});
		}

		if (nextBtn) {
			const newNextBtn = nextBtn.cloneNode(true);
			nextBtn.parentNode.replaceChild(newNextBtn, nextBtn);
			
			newNextBtn.addEventListener('click', function() {
				if (!pdfDoc || pageNum >= pdfDoc.numPages) return;
				pageNum++;
				queueRenderPage(pageNum);
				scrollToViewer();
			});
		}
	}

	/**
	 * Scroll to top of PDF viewer
	 */
	function scrollToViewer() {
		const viewer = document.querySelector('.transcription-pdf-viewer');
		if (viewer) {
			viewer.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	}

	/**
	 * Setup responsive resize
	 */
	function setupResize() {
		let resizeTimeout;
		
		// Remove old listener (if any) and add new one
		const handleResize = function() {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(function() {
				if (pdfDoc) {
					const canvas = document.getElementById('pdf-canvas');
					const ctx = canvas ? canvas.getContext('2d') : null;
					renderPage(pageNum, canvas, ctx);
				}
			}, 300);
		};

		// Remove existing resize listener (clean up)
		window.removeEventListener('resize', handleResize);
		window.addEventListener('resize', handleResize);
	}

	/**
	 * MutationObserver to detect AJAX page loads
	 * This ensures PDF initializes even when the page loads via AJAX
	 */
	function setupMutationObserver() {
		const observer = new MutationObserver(function(mutations) {
			const pdfViewer = document.querySelector('.transcription-pdf-viewer');
			if (pdfViewer && pdfViewer.dataset.initialized !== 'true') {
				console.log('PDF viewer detected via MutationObserver, initializing...');
				initPDF();
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	// Initialize on various page load events to handle AJAX navigation

	// Standard page load
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initPDF);
	} else {
		// DOM already loaded
		initPDF();
	}

	// AJAX page transitions - common event names
	document.addEventListener('page:load', initPDF); // Turbolinks
	document.addEventListener('turbolinks:load', initPDF); // Turbolinks 5
	document.addEventListener('swup:contentReplaced', initPDF); // Swup
	window.addEventListener('load', initPDF); // Fallback

	// Setup MutationObserver as universal fallback
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', setupMutationObserver);
	} else {
		setupMutationObserver();
	}

})();
