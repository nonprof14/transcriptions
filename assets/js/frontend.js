/**
 * Transcriptions Sync - Frontend JavaScript
 * Handles filtering and reorganization of transcriptions list
 *
 * @package TranscriptionsSync
 */

(function() {
	'use strict';

	// Store all transcriptions data
	let transcriptionsData = [];
	let currentFilter = 'maqam';

	/**
	 * Initialize the transcriptions list functionality
	 */
	function init() {
		// Extract transcriptions data from the DOM
		extractTranscriptionsData();

		// Setup desktop filter buttons
		setupDesktopFilters();

		// Setup mobile filter dropdown
		setupMobileFilters();

		// Setup mobile toggle
		setupMobileToggle();

		// Setup AJAX navigation override
		setupAjaxNavigationOverride();
	}

	/**
	 * Extract all transcription data from the DOM on page load
	 */
	function extractTranscriptionsData() {
		// Only extract from desktop view to avoid duplicates
		// (mobile view contains the same transcriptions)
		const desktopContainer = document.querySelector('.transcriptions-table-view');
		if (!desktopContainer) return;

		const entries = desktopContainer.querySelectorAll('.transcription-entry[data-maqam]');

		entries.forEach(function(entry) {
			const data = {
				title: entry.getAttribute('data-title'),
				composer: entry.getAttribute('data-composer'),
				maqam: entry.getAttribute('data-maqam'),
				form: entry.getAttribute('data-form'),
				url: entry.getAttribute('data-url')
			};

			transcriptionsData.push(data);
		});
	}

	/**
	 * Setup desktop filter button functionality
	 */
	function setupDesktopFilters() {
		const filterButtons = document.querySelectorAll('.header-filter-btn');

		filterButtons.forEach(function(button) {
			button.addEventListener('click', function() {
				const filter = this.getAttribute('data-filter');

				// Update active state
				filterButtons.forEach(function(btn) {
					btn.classList.remove('active');
				});
				this.classList.add('active');

				// Apply filter
				currentFilter = filter;
				applyFilter(filter, 'desktop');
			});
		});
	}

	/**
	 * Setup mobile filter dropdown functionality
	 */
	function setupMobileFilters() {
		const filterOptions = document.querySelectorAll('.filter-option');
		const toggle = document.querySelector('.mobile-filter-toggle');
		const filterLabel = toggle ? toggle.querySelector('.filter-label') : null;
		const dropdown = document.querySelector('.mobile-filter-dropdown');
		const toggleIcon = toggle ? toggle.querySelector('.toggle-icon') : null;

		filterOptions.forEach(function(option) {
			option.addEventListener('click', function() {
				const filter = this.getAttribute('data-filter');

				// Update filter label
				if (filterLabel) {
					filterLabel.textContent = capitalizeFirst(filter);
				}

				// Update dropdown options to show the other two filters
				updateMobileDropdownOptions(filter);

				// Close dropdown
				if (dropdown) {
					dropdown.classList.remove('active');
				}
				if (toggle) {
					toggle.setAttribute('aria-expanded', 'false');
				}
				if (toggleIcon) {
					toggleIcon.textContent = '+';
				}

				// Apply filter
				currentFilter = filter;
				applyFilter(filter, 'mobile');
			});
		});
	}

	/**
	 * Update mobile dropdown options to show alternatives
	 *
	 * @param {string} selectedFilter - Currently selected filter
	 */
	function updateMobileDropdownOptions(selectedFilter) {
		const dropdown = document.querySelector('.mobile-filter-dropdown');
		if (!dropdown) return;

		const allFilters = ['maqam', 'composer', 'form'];
		const otherFilters = allFilters.filter(function(f) {
			return f !== selectedFilter;
		});

		// Clear and rebuild dropdown options
		dropdown.innerHTML = '';

		otherFilters.forEach(function(filter) {
			const option = document.createElement('div');
			option.className = 'filter-option';
			option.setAttribute('data-filter', filter);
			option.textContent = capitalizeFirst(filter);

			option.addEventListener('click', function() {
				const filterValue = this.getAttribute('data-filter');
				const filterLabel = document.querySelector('.filter-label');
				const toggle = document.querySelector('.mobile-filter-toggle');
				const toggleIcon = toggle ? toggle.querySelector('.toggle-icon') : null;

				// Update filter label
				if (filterLabel) {
					filterLabel.textContent = capitalizeFirst(filterValue);
				}

				// Update dropdown options
				updateMobileDropdownOptions(filterValue);

				// Close dropdown
				dropdown.classList.remove('active');
				if (toggle) {
					toggle.setAttribute('aria-expanded', 'false');
				}
				if (toggleIcon) {
					toggleIcon.textContent = '+';
				}

				// Apply filter
				currentFilter = filterValue;
				applyFilter(filterValue, 'mobile');
			});

			dropdown.appendChild(option);
		});
	}

	/**
	 * Setup mobile toggle functionality
	 */
	function setupMobileToggle() {
		const toggle = document.querySelector('.mobile-filter-toggle');
		const dropdown = document.querySelector('.mobile-filter-dropdown');

		if (toggle && dropdown) {
			toggle.addEventListener('click', function() {
				const isExpanded = this.getAttribute('aria-expanded') === 'true';
				const icon = this.querySelector('.toggle-icon');

				if (isExpanded) {
					this.setAttribute('aria-expanded', 'false');
					dropdown.classList.remove('active');
					if (icon) icon.textContent = '+';
				} else {
					this.setAttribute('aria-expanded', 'true');
					dropdown.classList.add('active');
					if (icon) icon.textContent = '−';
				}
			});
		}
	}

	/**
	 * Apply filter and reorganize transcriptions
	 *
	 * @param {string} filterType - Type of filter (maqam, composer, form)
	 * @param {string} viewType - View type (desktop or mobile)
	 */
	function applyFilter(filterType, viewType) {
		// Group data by the selected filter
		const groupedData = groupByField(transcriptionsData, filterType);

		// Render the grouped data
		renderTranscriptions(groupedData, filterType, viewType);
	}

	/**
	 * Extract last name from full name
	 *
	 * @param {string} fullName - Full name string
	 * @return {string} Last name (last word in the name)
	 */
	function getLastName(fullName) {
		if (!fullName) return '';
		const parts = fullName.trim().split(' ');
		return parts[parts.length - 1];
	}

	/**
	 * Group transcriptions by a specific field
	 *
	 * @param {Array} data - Array of transcription objects
	 * @param {string} field - Field to group by (maqam, composer, form)
	 * @return {Object} Grouped data
	 */
	function groupByField(data, field) {
		const grouped = {};

		data.forEach(function(item) {
			const key = item[field] || 'Unknown';

			if (!grouped[key]) {
				grouped[key] = [];
			}

			grouped[key].push(item);
		});

		// Sort the groups
		let sortedKeys;

		if (field === 'composer') {
			// Sort composers by last name
			sortedKeys = Object.keys(grouped).sort(function(a, b) {
				const lastNameA = getLastName(a).toLowerCase();
				const lastNameB = getLastName(b).toLowerCase();
				return lastNameA.localeCompare(lastNameB);
			});
		} else {
			// Sort other fields alphabetically
			sortedKeys = Object.keys(grouped).sort();
		}

		const sortedGrouped = {};
		sortedKeys.forEach(function(key) {
			sortedGrouped[key] = grouped[key];
		});

		return sortedGrouped;
	}

	/**
	 * Render transcriptions in the DOM
	 *
	 * @param {Object} groupedData - Grouped transcription data
	 * @param {string} filterType - Type of filter applied
	 * @param {string} viewType - View type (desktop or mobile)
	 */
	function renderTranscriptions(groupedData, filterType, viewType) {
		let contentContainer;

		if (viewType === 'desktop') {
			contentContainer = document.querySelector('.transcriptions-content');
		} else {
			contentContainer = document.querySelector('.transcriptions-mobile-content');
		}

		if (!contentContainer) return;

		// Clear existing content
		contentContainer.innerHTML = '';

		// Render each group
		Object.keys(groupedData).forEach(function(groupName) {
			const transcriptions = groupedData[groupName];

			// Create section
			const section = document.createElement('div');
			section.className = 'transcriptions-maqam-section';

			// Create heading
			const heading = document.createElement('h3');
			heading.className = 'transcriptions-maqam-name';
			heading.textContent = groupName;
			section.appendChild(heading);

			// Create entries container
			const entriesContainer = document.createElement('div');
			entriesContainer.className = 'transcriptions-entries';

			// Add each transcription entry
			transcriptions.forEach(function(transcription) {
				const entry = document.createElement('div');
				entry.className = 'transcription-entry';
				entry.setAttribute('data-title', transcription.title);
				entry.setAttribute('data-composer', transcription.composer);
				entry.setAttribute('data-maqam', transcription.maqam);
				entry.setAttribute('data-form', transcription.form);
				entry.setAttribute('data-url', transcription.url);

				const link = document.createElement('a');
				link.href = transcription.url;
				link.className = 'transcription-link';
				link.textContent = transcription.title + ' - ' + transcription.composer;

				entry.appendChild(link);
				entriesContainer.appendChild(entry);
			});

			section.appendChild(entriesContainer);
			contentContainer.appendChild(section);
		});

		// Re-apply AJAX navigation override to newly rendered links
		setupAjaxNavigationOverride();
	}

	/**
	 * Capitalize first letter of a string
	 *
	 * @param {string} str - String to capitalize
	 * @return {string} Capitalized string
	 */
	function capitalizeFirst(str) {
		return str.charAt(0).toUpperCase() + str.slice(1);
	}

	/**
	 * Override AJAX navigation for transcription links
	 * Forces full page reload to avoid PDF.js CDN CORS issues
	 */
	function setupAjaxNavigationOverride() {
		// Wait for transcription links to be rendered
		setTimeout(function() {
			const transcriptionLinks = document.querySelectorAll(
				'.transcription-entry a, ' +
				'.transcription-link, ' +
				'a[href*="/transcriptions/"]'
			);

			transcriptionLinks.forEach(function(link) {
				// Remove existing listener to prevent duplicates
				link.removeEventListener('click', forceFullPageLoad);
				// Add new listener
				link.addEventListener('click', forceFullPageLoad);
			});
		}, 100);
	}

	/**
	 * Force full page load handler
	 */
	function forceFullPageLoad(e) {
		// Prevent any AJAX navigation
		e.preventDefault();
		e.stopImmediatePropagation();

		// Force full page reload
		const href = this.href;
		setTimeout(function() {
			window.location.href = href;
		}, 0);

		return false;
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
