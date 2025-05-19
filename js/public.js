/**
 * Enhanced Public JavaScript for Choir Lyrics Manager
 * 
 * This file handles all front-end interactive functionality for the plugin including:
 * - AJAX search and filtering
 * - Pagination
 * - Practice tracking
 * - Playlist management
 * - Alphabet navigation
 * - Skill management
 * - User dashboard tabs
 * - Shortcode functionality
 * 
 * @package    Choir_Lyrics_Manager
 * @version    1.2.0
 */

(function($) {
    'use strict';

    
    // Global variables
    let currentPage = 1;
    let isLoading = false;
    let currentFilters = {};
    let searchTimer;
    let currentRequest;
    let pendingRequests = [];
    let archiveUrl = '';  // Store archive URL

    
   
    window.initShortcodeFeatures = function(containerId) {
        const container = $('#' + containerId);
        if (!container.length) return;
        
        // CRITICAL: Prevent reinitializing the same container
        if (container.data('initialized')) {
            console.log('Container already initialized, skipping:', containerId);
            return;
        }
        
        console.log('Initializing shortcode features for container:', containerId);
        
        // Mark the container as initialized
        container.data('initialized', true);
        
        // Initialize default data attributes for state management
        if (!container.attr('data-per-page')) {
            container.attr('data-per-page', '20');
        }
        
        // Set default ordering if not already present
        if (!container.attr('data-orderby')) {
            // Try to get from orderby select, otherwise use default
            const sortSelect = container.find('select[name="orderby"]');
            container.attr('data-orderby', sortSelect.length ? sortSelect.val() : 'title');
        }
        
        if (!container.attr('data-order')) {
            // Try to get from order select, otherwise use default
            const orderSelect = container.find('select[name="order"]');
            container.attr('data-order', orderSelect.length ? orderSelect.val() : 'ASC');
        }
        
        // Initialize components
        initShortcodeSearch(container);
        initShortcodeFilters(container);
        initShortcodeAlphabet(container);
        initShortcodePagination(container);
        
        // Log current state
        console.log('Shortcode container initialized with:', {
            'per_page': container.attr('data-per-page'),
            'orderby': container.attr('data-orderby'),
            'order': container.attr('data-order')
        });
    };


 /**
     * Initialize all features
     */
 function initializeFeatures() {
    // Initialize features based on page content
    if ($('.clm-member-skills-dashboard').length) {
        initSkillManagement();
    }
    
    initEnhancedSearch();
    initFilters();
    initAlphabetNav();
    initPagination();
    initPlaylistManagement();
    initPracticeTracking();
    initUserDashboard();
    initializeFromURL();
    
    // Initialize any shortcode containers
    $('.clm-shortcode-container').each(function() {
        const containerId = $(this).attr('id');
        if (containerId) {
            initShortcodeFeatures(containerId);
        }
    });
}


 
    /**
     * Abort pending AJAX requests
     */
    function abortPendingRequests() {
        pendingRequests.forEach(function(req) {
            if (req && req.readyState !== 4) {
                req.abort();
            }
        });
        
        pendingRequests = [];
    }

    /**
     * Centralized AJAX request handler with improved error handling
     * 
     * @param {string} action - The AJAX action to perform
     * @param {Object} data - Data to send with the request
     * @param {Function} successCallback - Function to call on success
     * @param {Function} errorCallback - Function to call on error
     * @return {jqXHR|null} - The jQuery XHR object or null if failed
     */
    function clmAjaxRequest(action, data, successCallback, errorCallback) {
        // Abort any ongoing request
        if (currentRequest && currentRequest.readyState !== 4) {
            currentRequest.abort();
        }

        // Create the full data object
        const ajaxData = {
            action: 'clm_' + action
        };
        
        // Make sure we have clm_vars
        if (typeof clm_vars === 'undefined') {
            console.error('CLM: Required clm_vars not found');
            if (errorCallback) {
                errorCallback({}, 'error', 'CLM variables not found');
            }
            return null;
        }
        
        // Add nonce if available
        if (clm_vars.nonce && clm_vars.nonce[action]) {
            ajaxData.nonce = clm_vars.nonce[action];
        } else if (clm_vars.nonce && clm_vars.nonce.filter) {
            // Fallback to filter nonce
            ajaxData.nonce = clm_vars.nonce.filter;
        }
        
        // Merge data into ajaxData
        for (const key in data) {
            if (Object.prototype.hasOwnProperty.call(data, key)) {
                ajaxData[key] = data[key];
            }
        }

        console.log('CLM: Sending AJAX request for action: ' + action, ajaxData);

        const request = $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: ajaxData,
            success: function(response) {
                console.log('CLM: AJAX response for action: ' + action, response);
                
                if (successCallback) successCallback(response);
            },
            error: function(xhr, status, error) {
                console.error('CLM: AJAX error for action: ' + action, status, error);
                
                // Only proceed with error handling if not an abort
                if (status !== 'abort') {
                    if (errorCallback) {
                        errorCallback(xhr, status, error);
                    } else {
                        showNotification('Error: ' + (error || 'Request failed'), 'error');
                    }
                }
            }
        });

        pendingRequests.push(request);
        return request;
    }

    /**
     * Initialize enhanced search functionality
     */
    function initEnhancedSearch() {
        const searchInput = $('#clm-search-input');
        const searchForm = $('#clm-ajax-search-form');
        const suggestionsBox = $('#clm-search-suggestions');
        const loadingIndicator = $('.clm-search-loading');

        // Skip initialization if elements don't exist
        if (!searchForm.length) return;

        // Handle search form submission
        searchForm.on('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            performSearch();
        });

        // Live search as user types
        searchInput.on('input', function() {
            clearTimeout(searchTimer);
            const query = $(this).val();

            if (query.length < 2) {
                suggestionsBox.hide();
                return;
            }

            loadingIndicator.show();

            searchTimer = setTimeout(function() {
                abortPendingRequests();

                currentRequest = clmAjaxRequest('ajax_search', { query: query }, 
                    function(response) {
                        loadingIndicator.hide();
                        if (response.success && response.data.suggestions) {
                            displaySearchSuggestions(suggestionsBox, response.data.suggestions);
                        }
                    },
                    function() {
                        loadingIndicator.hide();
                        // Silent failure for search suggestions
                    }
                );
            }, 300);
        });

        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.clm-search-wrapper').length) {
                suggestionsBox.hide();
            }
        });
    }

    /**
     * Display search suggestions
     * 
     * @param {jQuery} suggestionsBox - The suggestions container element
     * @param {Array} suggestions - Array of suggestion items
     */
    function displaySearchSuggestions(suggestionsBox, suggestions) {
        if (!suggestions || suggestions.length === 0) {
            suggestionsBox.hide();
            return;
        }

        let html = '<ul class="clm-suggestions-list">';
        suggestions.forEach(function(item) {
            html += '<li class="clm-suggestion-item">';
            html += '<a href="' + escapeHtml(item.url) + '">';
            html += '<span class="clm-suggestion-title">' + escapeHtml(item.title) + '</span>';
            if (item.meta) {
                html += '<span class="clm-suggestion-meta">' + escapeHtml(item.meta) + '</span>';
            }
            html += '</a></li>';
        });
        html += '</ul>';
        
        suggestionsBox.html(html).show();
    }

    /**
     * Initialize filter functionality
     */
    function initFilters() {
        // Toggle advanced filters
        $('.clm-toggle-filters').each(function() {
            $(this).off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if ($('.clm-advanced-filters').is(':animated')) return;
                
                $('.clm-advanced-filters').slideToggle(300);
                $(this).toggleClass('active');
            });
        });
        
        // Quick filters
        $('.clm-quick-filter').off('click').on('click', function(e) {
            e.preventDefault();
            
            $('.clm-quick-filter').removeClass('active');
            $(this).addClass('active');

            const filterType = $(this).data('filter');
            const filterValue = $(this).data('value');

            if (filterType === 'all') {
                $('#clm-filter-form')[0].reset();
                currentFilters = {};
            } else if (filterType && filterValue) {
                // Set the filter in the select element
                $('#clm-' + filterType + '-select').val(filterValue);
                
                // Update currentFilters
                currentFilters[filterType] = filterValue;
            }

            currentPage = 1;
            performSearch();
        });

        // Apply filters button
        $('.clm-apply-filters').off('click').on('click', function(e) {
            e.preventDefault();
            currentPage = 1;
            performSearch();
        });

        // Reset filters
        $('.clm-reset-filters').off('click').on('click', function(e) {
            e.preventDefault();
            $('#clm-filter-form')[0].reset();
            $('#clm-search-input').val('');
            $('.clm-quick-filter').removeClass('active');
            $('.clm-quick-filter[data-filter="all"]').addClass('active');
            $('.clm-alpha-link').removeClass('active');
            $('.clm-alpha-link[data-letter="all"]').addClass('active');
            currentFilters = {};
            currentPage = 1;
            performSearch();
        });

        // Items per page
        $('#clm-items-per-page').off('change').on('change', function(e) {
            currentPage = 1;
            performSearch();
        });
    }

    /**
     * Initialize alphabet navigation for the Choir Lyrics Manager
     */
    // Update in public.js - initAlphabetNav function
function initAlphabetNav() {
    console.log('Initializing alphabet navigation');
    
    // Use specific selector to avoid conflicts with other elements
    $('.clm-alphabet-nav .clm-alpha-link').off('click').on('click', function(e) {
        e.preventDefault();
        
        const $link = $(this);
        const letter = $link.data('letter');
        
        console.log('Alphabet link clicked: ' + letter);
        
        // Skip if already active
        if ($link.hasClass('active')) {
            console.log('Link already active, skipping');
            return;
        }
        
        // Update UI
        $('.clm-alpha-link').removeClass('active');
        $link.addClass('active');
        
        // Update filter
        if (letter === 'all') {
            delete currentFilters.starts_with;
            console.log('Removed starts_with filter (all selected)');
        } else {
            currentFilters.starts_with = letter;
            console.log('Set starts_with filter to: ' + letter);
        }
        
        // Reset to first page and perform search
        currentPage = 1;
        console.log('Performing search with alphabet filter');
        performSearch();
    });
}

            /**
         * Show loading overlay during AJAX requests
         */
        function showLoadingOverlay() {
            $('#clm-loading-overlay').fadeIn(200);
        }

        /**
         * Hide loading overlay after AJAX requests
         */
        function hideLoadingOverlay() {
            $('#clm-loading-overlay').fadeOut(200);
        }


/**
 * Fallback alphabet filter using client-side filtering
 * This will be used if the server-side filtering fails
 */
function clientSideAlphabetFilter(letter) {
    debugLog('Performing client-side alphabet filtering for letter: ' + letter);
    
    const $items = $('.clm-lyric-item');
    
    if (letter === 'all') {
        // Show all items
        $items.show();
        debugLog('Showing all items (' + $items.length + ')');
        
        // Update count
        $('.clm-results-count').text($items.length);
        return;
    }
    
    // Count shown items
    let shownCount = 0;
    
    // Filter items by first letter
    $items.each(function() {
        const $item = $(this);
        const title = $item.data('title') || $item.find('.clm-item-title').text();
        
        if (title && title.trim().charAt(0).toUpperCase() === letter.toUpperCase()) {
            $item.show();
            shownCount++;
            debugLog('Showing item: ' + title);
        } else {
            $item.hide();
        }
    });
    
    // Update count
    $('.clm-results-count').text(shownCount);
    debugLog('Updated count: ' + shownCount + ' items shown');
    
    // Show no results message if needed
    if (shownCount === 0) {
        if ($('.clm-no-results').length === 0) {
            const noResultsHtml = '<div class="clm-no-results">' +
                '<p class="clm-notice">No lyrics found starting with ' + letter + '.</p>' +
                '<p>Try selecting a different letter.</p>' +
                '</div>';
            
            $('#clm-items-list').html(noResultsHtml);
            debugLog('Added no results message');
        }
    }
}



   /**
 * Initialize pagination with unified handling for all pagination elements
 */
function initPagination() {
    // Use event delegation for all pagination links with a single handler
    $(document).off('click', '.clm-pagination a, .clm-pagination-wrapper a, .page-numbers a')
        .on('click', '.clm-pagination a, .clm-pagination-wrapper a, .page-numbers a', function(e) {
            // Skip if this is in a shortcode container (handled separately)
            if ($(this).closest('.clm-shortcode-container').length) {
                return true;
            }
            
            // Skip if this is the current page, disabled, or we're already loading
            if ($(this).hasClass('current') || $(this).hasClass('clm-current') || 
                $(this).hasClass('disabled') || isLoading) {
                e.preventDefault();
                return false;
            }
            
            // Skip if this is an alphabet filter link
            if ($(this).hasClass('clm-alpha-link')) {
                return true;
            }
            
            // Get page number from link
            const page = getPageNumber($(this));
            if (!page) return true; // Let the default link behavior happen if no page number found
            
            // If using AJAX, prevent default and use our AJAX handler
            if (!$(this).closest('[data-ajax="no"]').length) {
                e.preventDefault();
                performSearch(page);
                return false;
            }
            
            // Otherwise, let the link work normally
            return true;
        });
    
    // Page jump form handler - unified approach
    $(document).off('click', '.clm-page-jump-button, .clm-go-button')
        .on('click', '.clm-page-jump-button, .clm-go-button', function(e) {
            // Skip if this is in a shortcode container (handled separately)
            if ($(this).closest('.clm-shortcode-container').length) {
                return true;
            }
            
            e.preventDefault();
            
            // Look for input in multiple possible locations
            const $input = $(this).siblings('.clm-page-jump-input, #clm-page-jump-input');
            if (!$input.length) return;
            
            const page = parseInt($input.val());
            const maxPage = parseInt($input.attr('max') || 9999);
            
            if (!page || page < 1 || page > maxPage || page === currentPage) return;
            
            // If using AJAX, use our AJAX handler
            if (!$(this).closest('[data-ajax="no"]').length) {
                performSearch(page);
            } else {
                // Otherwise, submit the form normally
                $(this).closest('form').submit();
            }
        });
}
 
    /**
     * Get page number from a pagination link or element
     * Improved to handle multiple pagination formats
     * 
     * @param {jQuery} $element - The pagination link or element
     * @return {number|null} - The page number or null if not found
     */
    function getPageNumber($element) {
        console.log('Getting page number from:', $element.prop('tagName'), 
        'class:', $element.attr('class'), 
        'text:', $element.text().trim());

        // First check data-page attribute
        if ($element.data('page')) {
        console.log('Found page from data attribute:', $element.data('page'));
        return parseInt($element.data('page'));
        }

        // Try to get from link text if it's a number
        const text = $element.text().trim();
        if (/^\d+$/.test(text)) {
        console.log('Found page from text content:', text);
        return parseInt(text);
        }

        // Check for prev/next links
        if ($element.hasClass('prev') || $element.text().includes('Previous')) {
        const prevPage = Math.max(1, currentPage - 1);
        console.log('Previous link detected, page:', prevPage);
        return prevPage;
        }

        if ($element.hasClass('next') || $element.text().includes('Next')) {
        const nextPage = currentPage + 1;
        console.log('Next link detected, page:', nextPage);
        return nextPage;
        }
                
        // Try to extract from href
        const href = $element.attr('href');
        if (href) {
            // Check for query string format: ?paged=N
            const queryMatch = href.match(/[\?&]paged=(\d+)/);
            if (queryMatch && queryMatch[1]) {
                console.log('Found page from URL query:', queryMatch[1]);
                return parseInt(queryMatch[1]);
            }
            
            // Check for pretty permalink format: /page/N/
            const permalinkMatch = href.match(/\/page\/(\d+)\/?/);
            if (permalinkMatch && permalinkMatch[1]) {
                console.log('Found page from permalink:', permalinkMatch[1]);
                return parseInt(permalinkMatch[1]);
            }
            
            // Check for hash format: #page-N
            const hashMatch = href.match(/#page-(\d+)/);
            if (hashMatch && hashMatch[1]) {
                console.log('Found page from hash:', hashMatch[1]);
                return parseInt(hashMatch[1]);
            }
        }
        
        console.log('Could not determine page number');
        return null;
    }


    // Update in public.js - add window.clmPageJump function
    window.clmPageJump = function(button) {
        console.log('Page jump button clicked');
        
        const $button = $(button);
        const $container = $button.closest('.clm-pagination, .clm-shortcode-pagination');
        const $input = $container.find('#clm-page-jump-input, .clm-page-jump-input');
        
        if (!$input.length) {
            console.log('Page jump input not found');
            return false;
        }
        
        const page = parseInt($input.val());
        const maxPage = parseInt($input.attr('max') || 9999);
        
        console.log('Jump to page:', page, 'max:', maxPage);
        
        if (!page || page < 1 || page > maxPage || page === currentPage) {
            console.log('Invalid page number');
            return false;
        }
        
        // Perform search with the new page
        performSearch(page);
        return false;
    };

    /**
     * Add page parameter to URL correctly
     * This function handles both pretty permalinks and query string formats
     * 
     * @param {string} baseUrl - The base URL to add page parameter to
     * @param {number} pageNum - The page number to add
     * @return {string} - The URL with page parameter added
     */
    function addPageParam(baseUrl, pageNum) {
        // First check if baseUrl has /page/N/ format (pretty permalinks)
        const prettyPermalink = baseUrl.match(/(.+?)\/page\/\d+\/?$/);
        if (prettyPermalink) {
            return prettyPermalink[1] + '/page/' + pageNum + '/';
        }
        
        // Check if the URL already has a query string
        if (baseUrl.indexOf('?') >= 0) {
            // URL already has query parameters
            const urlParts = baseUrl.split('?');
            const urlParams = new URLSearchParams(urlParts[1]);
            
            // Replace or add paged parameter
            urlParams.set('paged', pageNum);
            
            return urlParts[0] + '?' + urlParams.toString();
        } else {
            // No query parameters yet
            return baseUrl + '?paged=' + pageNum;
        }
    }

 /**
 * Perform search with all current filters - Enhanced version
 * 
 * @param {number} page - Page number to retrieve
 * @return {jqXHR|null} - The jQuery XHR object or null
 */
 function performSearch(page) {
    // If already loading, exit
    if (isLoading) {
        console.log('Already loading, skipping duplicate search request');
        return null;
    }
    
    // Set loading state
    isLoading = true;
    
    // Use provided page or current page
    page = page || currentPage || 1;
    console.log('Performing search for page: ' + page);
    
    // Get search query from input
    const searchQuery = $('#clm-search-input').val() || '';
    
    // Collect all filters
    const filters = collectFilters();
    console.log('Current filters:', filters);
    console.log('Current starts_with:', currentFilters.starts_with);
    
    // Double-check that starts_with from currentFilters is included
    if (currentFilters.starts_with && !filters.starts_with) {
        console.log('Adding starts_with to filters: ' + currentFilters.starts_with);
        filters.starts_with = currentFilters.starts_with;
    }
    
    // Show loading overlay    
    if (typeof showLoadingOverlay === 'function') {
        showLoadingOverlay();
    } else {
        $('#clm-loading-overlay').show();
    }
    
    // Abort any pending requests
    abortPendingRequests();

    console.log('Sending AJAX request for main archive filtering');

    // Make the AJAX request
    currentRequest = clmAjaxRequest('ajax_filter', {
        search: searchQuery,
        filters: filters,
        page: page
    }, function(response) {
        isLoading = false;
        
        console.log('AJAX response received for main archive search:', response);
        
        if (typeof hideLoadingOverlay === 'function') {
            hideLoadingOverlay();
        } else {
            $('#clm-loading-overlay').hide();
        }
        
        if (response.success) {
            // Update current page
            currentPage = parseInt(response.data.page) || page;
            
            console.log('Updating main archive content with response data');
            
            // Update page content
            updatePageContent(response.data);
            
            // Update URL with current state
            updateURL(searchQuery, filters, currentPage);
            
            // Scroll to results if function exists
            if (typeof scrollToResults === 'function') {
                scrollToResults();
            }
        } else {
            console.error('Search error:', response);
            if (typeof showNotification === 'function') {
                showNotification('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'), 'error');
            }
        }
    }, function(xhr, status, error) {
        isLoading = false;
        console.error('AJAX error:', status, error);
        
        if (typeof hideLoadingOverlay === 'function') {
            hideLoadingOverlay();
        } else {
            $('#clm-loading-overlay').hide();
        }
        
        if (typeof showNotification === 'function') {
            showNotification('Error connecting to server. Please try again.', 'error');
        }
    });
    
    return currentRequest;
}
// Debug flag for alphabet filtering
const DEBUG_ALPHABET = true;

function debugLog(message, data) {
    if (!DEBUG_ALPHABET) return;
    
    if (data !== undefined) {
        console.log('ALPHABET DEBUG: ' + message, data);
    } else {
        console.log('ALPHABET DEBUG: ' + message);
    }
}

/**
 * Scroll to the results container after search
 */
function scrollToResults() {
    const $resultsContainer = $('#clm-results-container');
    if ($resultsContainer.length) {
        $('html, body').animate({
            scrollTop: Math.max(0, $resultsContainer.offset().top - 100)
        }, 300);
    }
}

    /**
     * Collect all active filters
     * 
     * @return {Object} - Object containing all filter values
     */
    function collectFilters() {
        const filters = {
            genre: $('#clm-genre-select').val(),
            language: $('#clm-language-select').val(),
            difficulty: $('#clm-difficulty-select').val(),
            orderby: $('#clm-sort-select').val(),
            order: $('#clm-order-select').val(),
            per_page: $('#clm-items-per-page').val() || 20
        };
    
        // Merge with currentFilters (includes starts_with)
        $.extend(filters, currentFilters);
    
        // Remove empty values
        Object.keys(filters).forEach(function(key) {
            if (!filters[key] || filters[key] === '') {
                delete filters[key];
            }
        });
    
        // Ensure starts_with is properly included (double-check)
        if (currentFilters.starts_with && currentFilters.starts_with !== 'all') {
            filters.starts_with = currentFilters.starts_with;
        }
    
        return filters;
    }


   
    /**
     * Update page content after AJAX response
     * Enhanced to ensure pagination links are properly initialized
     * 
     * @param {Object} data - Response data to update page with
     */
    function updatePageContent(data) {
        if (data.html !== undefined) {
            $('#clm-items-list, .clm-items-list').html(data.html);
        }
        
        if (data.pagination !== undefined) {
            $('.clm-pagination').html(data.pagination);
            
            // Make sure all pagination links have data-page attributes
            $('.clm-pagination a, .clm-pagination-wrapper a').each(function() {
                const $link = $(this);
                const page = getPageNumber($link);
                if (page && !$link.data('page')) {
                    $link.attr('data-page', page);
                }
            });
            
            // Ensure current page link has the correct class
            $('.clm-pagination .page-numbers, .clm-pagination-wrapper .page-numbers').each(function() {
                const $span = $(this);
                const text = $span.text().trim();
                if (text === currentPage.toString() && !$span.hasClass('current') && !$span.hasClass('clm-current')) {
                    $span.addClass('clm-current');
                }
            });
        }
        
        if (data.total !== undefined) {
            $('.clm-results-count').text(data.total);
        }
        
        if (data.page !== undefined) {
            currentPage = parseInt(data.page);
            $('.clm-page-jump-input, #clm-page-jump-input').val(currentPage);
            
            // Update data attribute on container
            $('#clm-results-container').attr('data-current-page', currentPage);
        }
        
        // Reinitialize playlist management for new content
        initPlaylistManagement();
    }

    /**
     * Update URL with current state
     * 
     * @param {string} search - Search query
     * @param {Object} filters - Filter settings
     * @param {number} page - Current page number
     */
    function updateURL(search, filters, page) {
        const params = new URLSearchParams();
        
        if (search) params.set('s', search);
        
        Object.keys(filters).forEach(function(key) {
            if (filters[key]) params.set(key, filters[key]);
        });
        
        if (page && page > 1) params.set('paged', page);

        // Use archiveUrl as base if available, otherwise current path
        const baseUrl = archiveUrl || window.location.pathname;
        let newURL = baseUrl;
        const queryString = params.toString();
        
        if (queryString) newURL += (newURL.indexOf('?') >= 0 ? '&' : '?') + queryString;
        
        window.history.replaceState({
            page: page,
            search: search,
            filters: filters
        }, '', newURL);
    }

    /**
     * Initialize from URL parameters
     */
    function initializeFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Get current page from URL or data attribute
        if (urlParams.has('paged')) {
            currentPage = parseInt(urlParams.get('paged'));
        } else {
            const dataPage = $('#clm-results-container').data('current-page');
            if (dataPage) {
                currentPage = parseInt(dataPage);
            }
        }
        
        // Update page jump input
        $('#clm-page-jump-input').val(currentPage);
        
        // Set search input from URL
        if (urlParams.has('s')) {
            $('#clm-search-input').val(urlParams.get('s'));
        }
        
        // Set filters from URL
        ['genre', 'language', 'difficulty'].forEach(function(filter) {
            if (urlParams.has(filter)) {
                $('#clm-' + filter + '-select').val(urlParams.get(filter));
                currentFilters[filter] = urlParams.get(filter);
            }
        });
        
        // Set alphabet filter from URL
        if (urlParams.has('starts_with')) {
            const letter = urlParams.get('starts_with');
            $('.clm-alpha-link[data-letter="' + letter + '"]').addClass('active');
            $('.clm-alpha-link[data-letter="all"]').removeClass('active');
            currentFilters.starts_with = letter;
        }
    }

    /**
     * Practice tracking functionality
     */
    function initPracticeTracking() {
        $('#clm-log-practice').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const lyricId = $('.clm-practice-tracker').data('lyric-id');
            const duration = $('#clm-practice-duration').val();
            const confidence = $('#clm-practice-confidence').val();
            const notes = $('#clm-practice-notes').val();
            const messageContainer = $('#clm-practice-message');
            
            if (!duration || duration < 1) {
                messageContainer.html('<div class="clm-error">Please enter a valid duration.</div>');
                return;
            }
            
            button.prop('disabled', true).text('Logging...');
            messageContainer.empty();
            
            clmAjaxRequest('update_practice_log', {
                lyric_id: lyricId,
                duration: duration,
                confidence: confidence,
                notes: notes
            }, function(response) {
                if (response.success) {
                    messageContainer.html('<div class="clm-success">' + clm_vars.text.practice_success + '</div>');
                    $('#clm-practice-notes').val('');
                    
                    // Update practice stats if available
                    if (response.data && response.data.stats) updatePracticeStats(response.data.stats);
                    if (response.data && response.data.skill) updateSkillDisplay(response.data.skill);
                    
                    if ($('.clm-practice-history').length) {
                        setTimeout(function() { location.reload(); }, 1500);
                    }
                } else {
                    messageContainer.html('<div class="clm-error">' + ((response.data && response.data.message) || 'Error logging practice.') + '</div>');
                }
            }).always(function() {
                button.prop('disabled', false).text('Log Practice');
            });
        });
    }

    /**
     * Update skill display after practice
     * 
     * @param {Object} skill - Skill data to update display with
     */
    function updateSkillDisplay(skill) {
        if (!skill) return;
        
        const skillLevels = {
            'novice': { label: 'Novice', color: '#dc3545', icon: 'dashicons-warning', value: 1 },
            'learning': { label: 'Learning', color: '#ffc107', icon: 'dashicons-lightbulb', value: 2 },
            'proficient': { label: 'Proficient', color: '#17a2b8', icon: 'dashicons-yes', value: 3 },
            'master': { label: 'Master', color: '#28a745', icon: 'dashicons-star-filled', value: 4 }
        };
        
        const levelInfo = skillLevels[skill.skill_level];
        if (!levelInfo) return;
        
        $('.clm-skill-badge').css('background-color', levelInfo.color)
            .html('<span class="dashicons ' + levelInfo.icon + '"></span> ' + levelInfo.label);
        
        $('.clm-skill-stats p:first').text('Practice Sessions: ' + skill.practice_count);
        
        const progressWidth = (levelInfo.value / 4) * 100;
        $('.clm-progress-fill').css({
            'width': progressWidth + '%',
            'background-color': levelInfo.color
        });
    }

    /**
     * Update practice stats display
     * 
     * @param {Object} stats - Practice stats to display
     */
    function updatePracticeStats(stats) {
        if (stats && stats.total_time !== undefined) {
            $('.clm-practice-stat:nth-child(1) .clm-stat-value').text(formatDuration(stats.total_time));
        }
        
        if (stats && stats.sessions !== undefined) {
            $('.clm-practice-stat:nth-child(2) .clm-stat-value').text(stats.sessions);
        }
        
        if (stats && stats.confidence !== undefined) {
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                starsHtml += i <= stats.confidence ? 
                    '<span class="dashicons dashicons-star-filled"></span>' : 
                    '<span class="dashicons dashicons-star-empty"></span>';
            }
            $('.clm-practice-stat:nth-child(4) .clm-stat-value').html(starsHtml);
        }
    }

    /**
     * Format duration in minutes to a readable string
     * 
     * @param {number} minutes - Duration in minutes
     * @return {string} - Formatted duration string
     */
    function formatDuration(minutes) {
        minutes = parseInt(minutes, 10);
        if (minutes < 60) return minutes + (minutes === 1 ? ' minute' : ' minutes');
        
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        
        if (mins === 0) return hours + (hours === 1 ? ' hour' : ' hours');
        return hours + (hours === 1 ? ' hour' : ' hours') + ', ' + mins + (mins === 1 ? ' minute' : ' minutes');
    }

    /**
     * Initialize playlist management functionality
     */
    function initPlaylistManagement() {
        // Use event delegation for better performance with dynamic content
        $(document).off('click', '.clm-create-playlist-button, .clm-cancel-playlist, .clm-submit-playlist')
            .on('click', '.clm-create-playlist-button', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                $(this).hide().closest('.clm-item-actions')
                    .find('.clm-create-playlist-form').show();
            })
            .on('click', '.clm-cancel-playlist', function(e) {
                e.preventDefault();
                $(this).closest('.clm-item-actions')
                    .find('.clm-create-playlist-form').hide()
                    .end().find('.clm-create-playlist-button').show();
            })
            .on('click', '.clm-submit-playlist', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $form = $button.closest('.clm-create-playlist-form');
                const name = $form.find('.clm-playlist-name').val();
                
                if (!name) {
                    showNotification('Please enter a playlist name.', 'error');
                    $form.find('.clm-playlist-name').focus();
                    return;
                }
                
                $button.prop('disabled', true).text('Creating...');
                
                clmAjaxRequest('create_playlist', {
                    playlist_name: name,
                    playlist_description: $form.find('.clm-playlist-description').val(),
                    playlist_visibility: $form.find('input[type="radio"]:checked').val() || 'private',
                    lyric_id: $button.data('lyric-id') || 0
                }, function(response) {
                    if (response.success) {
                        showNotification(clm_vars.text.playlist_success, 'success');
                        location.reload();
                    } else {
                        showNotification((response.data && response.data.message) || clm_vars.text.playlist_error, 'error');
                    }
                }).always(function() {
                    $button.prop('disabled', false).text('Create');
                });
            });
    }

    /**
     * Initialize user dashboard tabs
     */
    function initUserDashboard() {
        $('.clm-dashboard-nav-item').on('click', function(e) {
            e.preventDefault();
            
            const tabId = $(this).data('tab');
            
            $('.clm-dashboard-nav-item').removeClass('active');
            $(this).addClass('active');
            
            $('.clm-dashboard-tab').hide();
            $('#' + tabId).show();
            
            window.location.hash = tabId;
        });
        
        // Check for hash in URL to activate the correct tab
        const hash = window.location.hash.substring(1);
        if (hash && $('#' + hash).length) {
            $('.clm-dashboard-nav-item[data-tab="' + hash + '"]').trigger('click');
        } else {
            $('.clm-dashboard-nav-item:first').trigger('click');
        }
    }

    /**
     * Initialize skill management functionality
     */
    function initSkillManagement() {
        // Set up skill goal buttons
        $(document).on('click', '.clm-set-goal', function(e) {
            e.preventDefault();
            
            const skillId = $(this).data('skill-id');
            const today = new Date().toISOString().split('T')[0];
            
            const modalHtml = '<div class="clm-modal-overlay">' +
                '<div class="clm-modal">' +
                '<h3>' + escapeHtml(clm_vars.text.set_goal_title || 'Set Practice Goal') + '</h3>' +
                '<p>' + escapeHtml(clm_vars.text.set_goal_description || 'Choose a target date for mastering this piece:') + '</p>' +
                '<input type="date" id="clm-goal-date" class="clm-goal-date-input" min="' + today + '">' +
                '<div class="clm-modal-actions">' +
                '<button class="clm-button clm-button-primary clm-confirm-goal">' + escapeHtml(clm_vars.text.confirm || 'Set Goal') + '</button>' +
                '<button class="clm-button clm-cancel-goal">' + escapeHtml(clm_vars.text.cancel || 'Cancel') + '</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            $('body').append(modalHtml);
            
            $('.clm-confirm-goal').on('click', function() {
                const goalDate = $('#clm-goal-date').val();
                
                if (goalDate) {
                    setSkillGoal(skillId, goalDate);
                } else {
                    showNotification(clm_vars.text.please_select_date || 'Please select a date', 'error');
                }
            });
            
            $('.clm-cancel-goal, .clm-modal-overlay').on('click', function(e) {
                if (e.target === this) $('.clm-modal-overlay').remove();
            });
        });
    }

    /**
     * Set skill goal for a given skill
     * 
     * @param {number} skillId - ID of the skill to set goal for
     * @param {string} goalDate - Target date for the goal
     */
    function setSkillGoal(skillId, goalDate) {
        if (!isValidDate(goalDate)) {
            showNotification('Please select a valid date', 'error');
            return;
        }

        $('.clm-confirm-goal').prop('disabled', true).text(clm_vars.text.saving || 'Saving...');
        
        clmAjaxRequest('set_skill_goal', {
            skill_id: skillId,
            goal_date: goalDate
        }, function(response) {
            if (response.success) {
                $('.clm-modal-overlay').remove();
                updateSkillGoalDisplay(skillId, goalDate);
                showNotification(clm_vars.text.goal_set_success || 'Goal set successfully!', 'success');
            } else {
                showNotification((response.data && response.data.message) || 'Error setting goal', 'error');
            }
        }).always(function() {
            $('.clm-confirm-goal').prop('disabled', false).text(clm_vars.text.confirm || 'Set Goal');
        });
    }

    /**
     * Update the skill goal display in the UI
     * 
     * @param {number} skillId - ID of the skill
     * @param {string} goalDate - Goal date to display
     */
    function updateSkillGoalDisplay(skillId, goalDate) {
        const skillItem = $('.clm-set-goal[data-skill-id="' + skillId + '"]').closest('.clm-skill-item');
        const goalHtml = '<div class="clm-skill-goal">' +
            escapeHtml(clm_vars.text.goal || 'Goal') + ': ' + escapeHtml(formatDate(goalDate)) +
            '</div>';
        
        skillItem.find('.clm-skill-goal').remove().end()
            .find('.clm-skill-actions').before(goalHtml);
    }

    /**
     * Initialize shortcode features
     * 
     * @param {string} containerId - ID of the shortcode container
     */
    window.initShortcodeFeatures = function(containerId) {
        const container = $('#' + containerId);
        if (!container.length) return;
        
        console.log('Initializing shortcode features for container:', containerId);
        
        // Initialize default data attributes for state management
        if (!container.attr('data-per-page')) {
            container.attr('data-per-page', '20');
        }
        
        // Set default ordering if not already present
        if (!container.attr('data-orderby')) {
            // Try to get from orderby select, otherwise use default
            const sortSelect = container.find('select[name="orderby"]');
            container.attr('data-orderby', sortSelect.length ? sortSelect.val() : 'title');
        }
        
        if (!container.attr('data-order')) {
            // Try to get from order select, otherwise use default
            const orderSelect = container.find('select[name="order"]');
            container.attr('data-order', orderSelect.length ? orderSelect.val() : 'ASC');
        }
        
        // Initialize components
        initShortcodeSearch(container);
        initShortcodeFilters(container);
        initShortcodeAlphabet(container);
        initShortcodePagination(container);
        
        // Log current state
        console.log('Shortcode container initialized with:', {
            'per_page': container.attr('data-per-page'),
            'orderby': container.attr('data-orderby'),
            'order': container.attr('data-order')
        });
    };

    /**
     * Initialize search functionality for shortcodes
     * 
     * @param {jQuery} container - The shortcode container
     */
    function initShortcodeSearch(container) {
        const searchForm = container.find('.clm-shortcode-search-form');
        const searchInput = container.find('.clm-search-input');
        
        if (!searchForm.length) return;

        // Handle search form submission
        searchForm.off('submit').on('submit', function(e) {
            e.preventDefault();
            
            // Reset to page 1 and perform search
            performShortcodeSearch(container, 1);
        });
    }

    /**
     * Initialize filters for shortcodes
     * 
     * @param {jQuery} container - The shortcode container
     */
    function initShortcodeFilters(container) {
        // Toggle advanced filters
        container.find('.clm-toggle-filters').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (container.find('.clm-advanced-filters').is(':animated')) return;
            
            container.find('.clm-advanced-filters').slideToggle(300);
            $(this).toggleClass('active');
        });
        
        // Apply filters button
        container.find('.clm-apply-filters').off('click').on('click', function(e) {
            e.preventDefault();
            performShortcodeSearch(container, 1);
        });

        // Reset filters
        container.find('.clm-reset-filters').off('click').on('click', function(e) {
            e.preventDefault();
            
            // Reset all form elements
            container.find('.clm-shortcode-filter-form')[0].reset();
            container.find('.clm-search-input').val('');
            
            // Reset filter classes
            container.find('.clm-quick-filter').removeClass('active');
            container.find('.clm-quick-filter[data-filter="all"]').addClass('active');
            
            container.find('.clm-alpha-link').removeClass('active');
            container.find('.clm-alpha-link[data-letter="all"]').addClass('active');
            
            // Reset to page 1 and perform fresh search
            performShortcodeSearch(container, 1);
        });
        
        // Items per page selector
        container.find('.clm-items-per-page').off('change').on('change', function() {
            const perPage = parseInt($(this).val());
            if (perPage) {
                // Update the container data attribute
                container.attr('data-per-page', perPage);
                
                // Perform search with page 1
                performShortcodeSearch(container, 1);
            }
        });
        
        // Sort controls
        container.find('select[name="orderby"], select[name="order"]').off('change').on('change', function() {
            const orderby = container.find('select[name="orderby"]').val();
            const order = container.find('select[name="order"]').val();
            
            // Update container data attributes
            if (orderby) container.attr('data-orderby', orderby);
            if (order) container.attr('data-order', order);
            
            // Perform search with current settings
            performShortcodeSearch(container, 1);
        });
    }

    /**
     * Initialize alphabet navigation for shortcodes
     * 
     * @param {jQuery} container - The shortcode container
     */
    function initShortcodeAlphabet(container) {
        console.log('Initializing alphabet filter for container', container.attr('id'));
        
        // Alphabet navigation
        container.find('.clm-alpha-link').off('click').on('click', function(e) {
            e.preventDefault();
            
            // Update UI
            container.find('.clm-alpha-link').removeClass('active');
            $(this).addClass('active');
            
            const letter = $(this).data('letter');
            console.log('Alphabet filter selected letter:', letter);
            
            if (container.data('ajax') === 'yes') {
                try {
                    // Store the letter in a data attribute for the search
                    container.attr('data-selected-letter', letter === 'all' ? '' : letter);
                    
                    // Perform search with page 1
                    performShortcodeSearch(container, 1);
                } catch (error) {
                    console.error('Error in alphabet filter:', error);
                    alert('An error occurred with the filter. Please try again.');
                }
            } else {
                // Use client-side filtering for non-AJAX mode
                if (letter === 'all') {
                    container.find('.clm-lyric-item').show();
                } else {
                    container.find('.clm-lyric-item').each(function() {
                        const title = $(this).data('title');
                        $(this).toggle(title && title.charAt(0).toUpperCase() === letter);
                    });
                }
            }
        });
    }

    /**
 * Initialize pagination for shortcode containers
 * 
 * @param {jQuery} container - The shortcode container
 */
    function initShortcodePagination(container) {
        if (container.data('ajax') !== 'yes') return;
        
        console.log('Setting up pagination for container:', container.attr('id'));
        
        // Clean up any existing event handlers to prevent duplicates
        container.off('click', '.clm-pagination a, .page-numbers a, .clm-page-link, .clm-shortcode-pagination a');
        
        // Use event delegation for pagination links
        container.on('click', '.clm-pagination a, .page-numbers a, .clm-page-link, .clm-shortcode-pagination a', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $link = $(this);
            console.log('Pagination link clicked:', $link.attr('class'), $link.attr('href'));
            
            // Skip if disabled or already on current page
            if ($link.hasClass('disabled') || $link.hasClass('clm-current') || 
                $link.hasClass('current') || $link.parent().hasClass('current')) {
                console.log('Skipping click on disabled/current link');
                return false;
            }
            
            // Skip if this is an alphabet filter
            if ($link.hasClass('clm-alpha-link')) {
                console.log('Alphabet link clicked - passing through');
                return true;
            }
            
            // Get the target page number
            const page = getPageNumber($link);
            if (page) {
                console.log('Navigating to page:', page);
                performShortcodeSearch(container, page);
            } else {
                console.warn('Could not determine page number from link:', $link.attr('href'));
            }
            
            return false;
        });
        
        // Clean up existing page jump handlers
        container.off('click', '.clm-page-jump-button, .clm-go-button');
        
        // Page jump handler
        container.on('click', '.clm-page-jump-button, .clm-go-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Page jump button clicked');
            
            // Find the closest input within the form or container
            const $input = $(this).closest('form').find('.clm-page-jump-input');
            if (!$input.length) {
                console.warn('Page jump input not found');
                return;
            }
            
            const page = parseInt($input.val());
            const maxPage = parseInt($input.attr('max') || 9999);
            
            console.log('Page jump requested to page:', page, 'max:', maxPage);
            
            if (page && page > 0 && page <= maxPage) {
                performShortcodeSearch(container, page);
            } else {
                console.warn('Invalid page number for jump:', page, 'max:', maxPage);
            }
        });
        
        // Log that we set up the pagination handlers
        console.log('Pagination handlers initialized for container:', container.attr('id'));
    }

/**
 * Perform search with shortcode-specific parameters
 * 
 * @param {jQuery} container - The shortcode container
 * @param {number} page - Page number to load
 */
function performShortcodeSearch(container, page) {
    console.log('Starting shortcode search for page:', page);
    
    // For non-AJAX mode, submit the form
    if (container.data('ajax') !== 'yes') {
        const form = container.find('.clm-shortcode-search-form, .clm-shortcode-filter-form').first();
        if (page) {
            // Replace any existing paged input to avoid duplicates
            form.find('input[name="paged"]').remove();
            
            $('<input>').attr({
                type: 'hidden',
                name: 'paged',
                value: page
            }).appendTo(form);
        }
        form.submit();
        return;
    }
    
    // Show loading indicator
    container.find('.clm-loading-overlay').show();
    
    // Get the nonce and other parameters
    const nonce = clm_vars.nonce.filter;
    const itemsPerPage = parseInt(container.attr('data-per-page')) || 20;
    const orderBy = container.attr('data-orderby') || 'title';
    const order = container.attr('data-order') || 'ASC';
    
    // Create the request data
    const requestData = {
        container_id: container.attr('id'),
        page: parseInt(page) || 1,
        search: container.find('.clm-search-input').val() || '',
        per_page: itemsPerPage,
        orderby: orderBy,
        order: order,
        nonce: nonce
    };
    
    // Store current page in container
    container.attr('data-current-page', requestData.page);
    
    // Add any active filters
    container.find('.clm-shortcode-filter-form').find('select, input[type="text"]').each(function() {
        const $input = $(this);
        if ($input.val()) {
            requestData[$input.attr('name')] = $input.val();
        }
    });
    
    // Add alphabet filter if active
    const selectedLetter = container.attr('data-selected-letter');
    if (selectedLetter) {
        requestData.starts_with = selectedLetter;
    }
    
    console.log('Performing shortcode search with data:', requestData);
    
    // Abort any pending requests
    if (container.data('current-request') && container.data('current-request').readyState !== 4) {
        console.log('Aborting previous request');
        container.data('current-request').abort();
    }
    
    // Make the AJAX request with error handling
    try {
        const request = clmAjaxRequest('shortcode_filter', requestData, 
            function(response) {
                container.find('.clm-loading-overlay').hide();
                
                if (response.success) {
                    console.log('Search successful, updating content');
                    
                    // Update the results content
                    if (response.data.html !== undefined) {
                        container.find('.clm-shortcode-results').html(response.data.html);
                    }
                    
                    // Update count if available
                    if (container.find('.clm-results-count').length && response.data.total !== undefined) {
                        container.find('.clm-results-count').text(response.data.total);
                    }
                    
                    // Update pagination if available
                    if (response.data.pagination) {
                        let paginationContainer = container.find('.clm-shortcode-pagination');
                        if (paginationContainer.length === 0) {
                            container.find('.clm-shortcode-results').after('<div class="clm-shortcode-pagination"></div>');
                            paginationContainer = container.find('.clm-shortcode-pagination');
                        }
                        
                        paginationContainer.html(response.data.pagination);
                        paginationContainer.show();
                        
                        // Initialize pagination for the new content
                        setTimeout(function() {
                            initShortcodePagination(container);
                        }, 100);
                    }
                } else {
                    console.error('Shortcode filter failed:', response.data);
                    showNotification(response.data && response.data.message ? response.data.message : 'Error loading results', 'error');
                }
            }, 
            function(xhr, status, error) {
                container.find('.clm-loading-overlay').hide();
                
                console.error('Shortcode search error:', status, error);
                
                if (status !== 'abort') {
                    try {
                        // Try to parse the error response
                        let errorMessage = 'Error connecting to server. Please try again.';
                        
                        if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response && response.data && response.data.message) {
                                    errorMessage = response.data.message;
                                }
                            } catch (e) {
                                // If we can't parse the JSON, use the status text
                                errorMessage = 'Server error: ' + (xhr.statusText || 'Unknown error');
                            }
                        }
                        
                        showNotification(errorMessage, 'error');
                    } catch (e) {
                        // Ultimate fallback
                        showNotification('An unexpected error occurred. Please try again.', 'error');
                    }
                }
            }
        );
        
        // Store current request in container data
        container.data('current-request', request);
        
    } catch (error) {
        container.find('.clm-loading-overlay').hide();
        console.error('Error in AJAX request setup:', error);
        showNotification('Failed to start search. Please try again.', 'error');
    }
}




    /**
     * Update results count for client-side filtering
     * 
     * @param {jQuery} container - The shortcode container
     */
    function updateShortcodeResultsCount(container) {
        const visibleCount = container.find('.clm-lyric-item:visible').length;
        container.find('.clm-results-count').text(visibleCount);
    }

    /**
     * Format the date for display
     * 
     * @param {string} dateString - Date string in ISO format
     * @return {string} - Formatted date
     */
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString(undefined, { 
            year: 'numeric', month: 'long', day: 'numeric' 
        });
    }

    /**
     * Check if a date string is valid
     * 
     * @param {string} dateString - Date string to check
     * @return {boolean} - Whether date is valid
     */
    function isValidDate(dateString) {
        const regEx = /^\d{4}-\d{2}-\d{2}$/;
        if (!dateString.match(regEx)) return false;
        const d = new Date(dateString);
        return d instanceof Date && !isNaN(d) && d.toISOString().slice(0,10) === dateString;
    }

    /**
     * Utility function to escape HTML
     * 
     * @param {string} text - Text to escape
     * @return {string} - Escaped text
     */
    function escapeHtml(text) {
        if (!text) return '';
        
        const map = {
            '&': '&amp;', 
            '<': '&lt;', 
            '>': '&gt;',
            '"': '&quot;', 
            "'": '&#039;'
        };
        
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Show notification message
     * 
     * @param {string} message - Message to display
     * @param {string} type - Notification type (success, error, info, warning)
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        const $notification = $('<div class="clm-notification clm-notification-' + type + '">' + 
            escapeHtml(message) + '</div>').appendTo('body');
        
        $notification.fadeIn(300);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

/**
 * Comprehensive Pagination Fix for Choir Lyrics Manager
 * 
 * This single solution replaces all previous pagination fixes.
 * It addresses:
 * - Multiple highlighted current pages
 * - Non-working links
 * - Structure preservation (UL/LI)
 * - Consistent styling
 * - Proper event handling
 * - CSS conflicts with themes
 * 
 * IMPORTANT: This should replace ALL existing pagination fix code.
 */

(function($) {
    'use strict';
    
    // Configuration - enable for detailed logging during development
    const DEBUG = true;
    
    // Flag to track initialization
    let initialized = false;
    
    // Track all fixed containers to avoid duplicating work
    const fixedContainers = new Set();
    
    /**
     * Log message to console if debug is enabled
     */
    function log() {
        if (!DEBUG) return;
        const args = Array.from(arguments);
        args[0] = 'CLM PAGINATION: ' + args[0];
        console.log.apply(console, args);
    }
    
    /**
     * Main initialization function
     */
    function initialize(trigger) {
        if (!$) {
            console.error('CLM PAGINATION: jQuery not available');
            return;
        }
        
        log(`Initializing pagination fix (trigger: ${trigger})`);
        
        // Remove any existing global functions to prevent conflicts
        if (window.clmPageJump) {
            log('Removing existing clmPageJump function');
            delete window.clmPageJump;
        }
        
        if (window.directPageJump) {
            log('Removing existing directPageJump function');
            delete window.directPageJump;
        }
        
        // Define our global page jump function that works consistently
        window.clmPageJump = function(button) {
            return handlePageJump($(button));
        };
        
        // Install our unified styles
        installStyles();
        
        // Process all pagination containers
        fixAllPagination();
        
        // Mark as initialized
        initialized = true;
        log('Initialization completed');
    }
    
    /**
     * Fix all pagination containers on the page
     */
    function fixAllPagination() {
        // Find all pagination containers
        const containers = $('.clm-pagination, .clm-shortcode-pagination');
        log(`Found ${containers.length} pagination containers`);
        
        // Process each container
        containers.each(function(index) {
            const $container = $(this);
            const containerId = $container.attr('id') || `pagination-${index}`;
            
            // Skip if already fixed in this session
            if (fixedContainers.has(containerId)) {
                log(`Container ${containerId} already fixed, skipping`);
                return;
            }
            
            // Fix the container
            fixPaginationContainer($container, containerId);
            
            // Mark as fixed
            fixedContainers.add(containerId);
        });
    }
    
    /**
     * Fix a specific pagination container
     */
    function fixPaginationContainer($container, containerId) {
        log(`Fixing pagination container: ${containerId}`);
        
        // Get pagination state
        const currentPage = getCurrentPage($container);
        const maxPages = getMaxPages($container);
        log(`Container state: page ${currentPage} of ${maxPages}`);
        
        // Remove any existing click handlers to prevent duplicates
        $container.find('a').off('.clmpagination');
        
        // Determine wrapper element and structure
        const $wrapper = getOrCreateWrapper($container);
        const isUL = $wrapper.is('ul');
        
        // Rebuild pagination elements
        rebuildPagination($wrapper, currentPage, maxPages, isUL);
        
        // Fix or create page jump component
        createOrUpdatePageJump($container, currentPage, maxPages);
        
        // Apply event handlers
        attachEventHandlers($container);
        
        log(`Container ${containerId} fixed successfully`);
    }
    
    /**
     * Get or create pagination wrapper
     */
    function getOrCreateWrapper($container) {
        // Look for existing wrapper
        let $wrapper = $container.find('.clm-pagination-wrapper, .page-numbers').first();
        
        if (!$wrapper.length) {
            log('Creating new pagination wrapper');
            $wrapper = $('<div class="clm-pagination-wrapper"></div>');
            $container.prepend($wrapper);
        } else {
            log(`Using existing wrapper: ${$wrapper.prop('tagName')}`);
            // Clear existing content to rebuild
            $wrapper.empty();
        }
        
        return $wrapper;
    }
    
    /**
     * Rebuild pagination elements
     */
    function rebuildPagination($wrapper, currentPage, maxPages, isUL) {
        log(`Rebuilding pagination: page ${currentPage} of ${maxPages}`);
        $wrapper.empty(); // Clear existing content
        
        const elements = [];
        
        // Previous button
        if (currentPage > 1) {
            elements.push(createPaginationItem('a', 'clm-page-link clm-prev', 'Previous', 
                buildPageUrl(currentPage - 1), currentPage - 1, isUL));
        } else {
            elements.push(createPaginationItem('span', 'clm-page-link clm-prev disabled', 'Previous', 
                null, null, isUL));
        }
        
        // First page
        if (currentPage === 1) {
            elements.push(createPaginationItem('span', 'clm-page-link clm-current current', '1', 
                null, 1, isUL));
        } else {
            elements.push(createPaginationItem('a', 'clm-page-link', '1', 
                buildPageUrl(1), 1, isUL));
        }
        
        // Dots if needed
        if (currentPage > 4) {
            elements.push(createPaginationItem('span', 'clm-page-link clm-dots dots', '...', 
                null, null, isUL));
        }
        
        // Pages around current
        const startPage = Math.max(2, currentPage - 2);
        const endPage = Math.min(maxPages - 1, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                elements.push(createPaginationItem('span', 'clm-page-link clm-current current', i.toString(), 
                    null, i, isUL));
            } else {
                elements.push(createPaginationItem('a', 'clm-page-link', i.toString(), 
                    buildPageUrl(i), i, isUL));
            }
        }
        
        // Dots if needed
        if (currentPage < maxPages - 3 && maxPages > 5) {
            elements.push(createPaginationItem('span', 'clm-page-link clm-dots dots', '...', 
                null, null, isUL));
        }
        
        // Last page if not already shown and if there's more than one page
        if (maxPages > 1 && endPage < maxPages) {
            if (currentPage === maxPages) {
                elements.push(createPaginationItem('span', 'clm-page-link clm-current current', maxPages.toString(), 
                    null, maxPages, isUL));
            } else {
                elements.push(createPaginationItem('a', 'clm-page-link', maxPages.toString(), 
                    buildPageUrl(maxPages), maxPages, isUL));
            }
        }
        
        // Next button
        if (currentPage < maxPages) {
            elements.push(createPaginationItem('a', 'clm-page-link clm-next', 'Next', 
                buildPageUrl(currentPage + 1), currentPage + 1, isUL));
        } else {
            elements.push(createPaginationItem('span', 'clm-page-link clm-next disabled', 'Next', 
                null, null, isUL));
        }
        
        // Add elements to wrapper
        elements.forEach(function(el) {
            $wrapper.append(el);
        });
        
        // Force re-application of styles
        setTimeout(function() {
            forceStyleRefresh($wrapper);
        }, 10);
    }
    
    /**
     * Force a style refresh on pagination elements
     */
    function forceStyleRefresh($wrapper) {
        // Force update of all current page indicators
        $wrapper.find('.current, .clm-current').each(function() {
            const $current = $(this);
            // Ensure it has updated styling
            $current.css({
                'background-color': '#007cba',
                'color': 'white',
                'border-color': '#007cba',
                'pointer-events': 'none'
            });
        });
        
        // Force update on all links to ensure clickability
        $wrapper.find('a').each(function() {
            const $link = $(this);
            // Ensure it's clickable
            $link.css({
                'pointer-events': 'auto',
                'cursor': 'pointer'
            });
        });
    }
    
    /**
     * Create pagination item (link or span)
     */
    function createPaginationItem(tagName, className, text, href, page, isUL) {
        let content;
        
        // Create element
        if (tagName === 'a') {
            content = $('<a></a>')
                .addClass(className)
                .attr('href', href)
                .text(text)
                .css('pointer-events', 'auto') // Force clickability
                .css('cursor', 'pointer');
            
            if (page) {
                content.attr('data-page', page);
            }
        } else {
            content = $('<span></span>')
                .addClass(className)
                .text(text);
            
            if (page) {
                content.attr('data-page', page);
            }
            
            // If this is a current page, ensure it has proper styling
            if (className.includes('current')) {
                content.css({
                    'background-color': '#007cba',
                    'color': 'white',
                    'border-color': '#007cba',
                    'pointer-events': 'none'
                });
            }
        }
        
        // Special formatting for prev/next
        if (text === 'Previous') {
            content.html('<span class="dashicons dashicons-arrow-left-alt2"></span> <span class="clm-nav-text">Previous</span>');
        } else if (text === 'Next') {
            content.html('<span class="clm-nav-text">Next</span> <span class="dashicons dashicons-arrow-right-alt2"></span>');
        }
        
        // If parent is UL, wrap in LI
        if (isUL) {
            return $('<li></li>').append(content);
        } else {
            return content;
        }
    }
    
    /**
     * Create or update page jump form
     */
    function createOrUpdatePageJump($container, currentPage, maxPages) {
        let $pageJump = $container.find('.clm-page-jump');
        const jumpId = 'clm-jump-' + Math.random().toString(36).substr(2, 5);
        
        if (!$pageJump.length) {
            // Create page jump
            $pageJump = $(
                '<div class="clm-page-jump">' +
                '<label for="' + jumpId + '">Jump to page:</label>' +
                '<input type="number" id="' + jumpId + '" class="clm-page-jump-input" ' +
                'min="1" max="' + maxPages + '" value="' + currentPage + '">' +
                '<button type="button" class="clm-go-button" onclick="return window.clmPageJump(this);">Go</button>' +
                '</div>'
            );
            $container.append($pageJump);
        } else {
            // Update existing page jump
            const $input = $pageJump.find('input');
            if ($input.length) {
                $input.attr({
                    'value': currentPage,
                    'max': maxPages
                });
            }
            
            // Ensure button has our handler
            const $button = $pageJump.find('button');
            if ($button.length) {
                $button.attr('onclick', 'return window.clmPageJump(this);');
            }
        }
    }
    
    /**
     * Attach event handlers to pagination
     */
    function attachEventHandlers($container) {
        // Get all clickable links
        const $links = $container.find('a.clm-page-link, a.page-numbers');
        
        $links.off('.clmpagination').on('click.clmpagination', function(e) {
            // Get page number
            const page = getPageNumberFromElement($(this));
            if (!page) return true;
            
            // Log the click for debugging
            log(`Pagination link clicked: ${$(this).text().trim()} to page ${page}`);
            
            // Check if we're in AJAX mode
            const isAjax = isAjaxMode($container);
            if (isAjax) {
                log(`Using AJAX navigation for page ${page}`);
                e.preventDefault();
                navigateAjax($container, page);
                return false;
            }
            
            // Otherwise use standard navigation
            log(`Using standard navigation to ${$(this).attr('href')}`);
            return true;
        });
        
        // Forcibly ensure clickability
        $links.each(function() {
            $(this).css({
                'pointer-events': 'auto',
                'cursor': 'pointer'
            });
        });
    }
    
    /**
     * Check if container is in AJAX mode
     */
    function isAjaxMode($container) {
        // Check if in shortcode container with AJAX enabled
        const $shortcodeContainer = $container.closest('.clm-shortcode-container');
        if ($shortcodeContainer.length && $shortcodeContainer.data('ajax') === 'yes') {
            return true;
        }
        
        // Check if main site AJAX is available
        if (typeof window.performSearch === 'function' && !$container.closest('[data-ajax="no"]').length) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Navigate using AJAX
     */
    function navigateAjax($container, page) {
        // Check if in shortcode container
        const $shortcodeContainer = $container.closest('.clm-shortcode-container');
        if ($shortcodeContainer.length && typeof window.performShortcodeSearch === 'function') {
            log(`Using performShortcodeSearch with page ${page}`);
            window.performShortcodeSearch($shortcodeContainer, page);
            return;
        }
        
        // Otherwise use main search
        if (typeof window.performSearch === 'function') {
            log(`Using performSearch with page ${page}`);
            window.performSearch(page);
            return;
        }
        
        // Fallback if AJAX functions not available
        log(`AJAX functions not available, navigating to ${buildPageUrl(page)}`);
        window.location.href = buildPageUrl(page);
    }
    
    /**
     * Handle page jump button click
     */
    function handlePageJump($button) {
        log('Page jump button clicked');
        
        // Get the page number
        const $container = $button.closest('.clm-pagination, .clm-shortcode-pagination');
        const $input = $button.closest('.clm-page-jump').find('input');
        
        if (!$input.length) {
            log('Error: No input found');
            return false;
        }
        
        const page = parseInt($input.val());
        const maxPage = parseInt($input.attr('max') || 1);
        
        if (!page || isNaN(page) || page < 1 || page > maxPage) {
            log(`Error: Invalid page number: ${page}`);
            return false;
        }
        
        // If AJAX mode, use it
        if (isAjaxMode($container)) {
            navigateAjax($container, page);
            return false;
        }
        
        // Otherwise use standard navigation
        window.location.href = buildPageUrl(page);
        return false;
    }
    
    /**
     * Get current page
     */
    function getCurrentPage($container) {
        // Check URL first - most reliable source
        const urlParams = new URLSearchParams(window.location.search);
        let page = parseInt(urlParams.get('paged'));
        
        // Try data attribute if URL param not found
        if (!page) {
            page = parseInt($container.data('current-page'));
        }
        
        // Try data attribute on results container
        if (!page) {
            const $resultsContainer = $('#clm-results-container');
            if ($resultsContainer.length) {
                page = parseInt($resultsContainer.data('current-page'));
            }
        }
        
        // Check if we're in a shortcode container
        if (!page) {
            const $shortcodeContainer = $container.closest('.clm-shortcode-container');
            if ($shortcodeContainer.length) {
                page = parseInt($shortcodeContainer.data('current-page'));
            }
        }
        
        // Default to 1
        return page || 1;
    }
    
    /**
     * Get max pages
     */
    function getMaxPages($container) {
        // Try data attribute
        let maxPages = parseInt($container.data('max-pages'));
        
        // Try from pagination wrapper
        if (!maxPages) {
            const $wrapper = $container.find('.clm-pagination-wrapper, .page-numbers');
            if ($wrapper.length) {
                maxPages = parseInt($wrapper.data('max-pages'));
            }
        }
        
        // Try to extract from links
        if (!maxPages) {
            const $links = $container.find('a, span').not('.dots, .clm-dots, .prev, .next, .clm-prev, .clm-next');
            if ($links.length) {
                $links.each(function() {
                    const page = getPageNumberFromElement($(this));
                    if (page && (!maxPages || page > maxPages)) {
                        maxPages = page;
                    }
                });
            }
        }
        
        // Default to 1 if not found
        return maxPages || 1;
    }
    
    /**
     * Get page number from element
     */
    function getPageNumberFromElement($element) {
        // Try data attribute
        if ($element.data('page')) {
            return parseInt($element.data('page'));
        }
        
        // Try text content
        const text = $element.text().trim();
        if (/^\d+$/.test(text)) {
            return parseInt(text);
        }
        
        // Check for prev/next
        if ($element.hasClass('prev') || $element.hasClass('clm-prev') || 
            $element.text().toLowerCase().includes('previous')) {
            const currentPage = getCurrentPage($element.closest('.clm-pagination, .clm-shortcode-pagination'));
            return Math.max(1, currentPage - 1);
        }
        
        if ($element.hasClass('next') || $element.hasClass('clm-next') || 
            $element.text().toLowerCase().includes('next')) {
            const currentPage = getCurrentPage($element.closest('.clm-pagination, .clm-shortcode-pagination'));
            return currentPage + 1;
        }
        
        // Try to extract from URL
        const href = $element.attr('href');
        if (href) {
            // Check for query string format
            const queryMatch = href.match(/[\?&]paged=(\d+)/);
            if (queryMatch && queryMatch[1]) {
                return parseInt(queryMatch[1]);
            }
            
            // Check for pretty permalink format
            const permalinkMatch = href.match(/\/page\/(\d+)\/?/);
            if (permalinkMatch && permalinkMatch[1]) {
                return parseInt(permalinkMatch[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Build URL for a page
     */
    function buildPageUrl(page) {
        const url = window.location.pathname;
        const params = new URLSearchParams(window.location.search);
        
        // Set the page parameter
        params.set('paged', page);
        
        return url + '?' + params.toString();
    }
    
    /**
     * Install pagination styles
     */
    function installStyles() {
        // Skip if already installed
        if ($('#clm-pagination-styles').length) {
            return;
        }
        
        const css = `
        /* CLM Pagination Styles */
        .clm-pagination-wrapper,
        ul.page-numbers,
        .page-numbers {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px !important;
            margin: 0 0 15px 0 !important;
            padding: 0 !important;
            list-style: none !important;
        }
        
        ul.page-numbers li {
            margin: 0 !important;
            padding: 0 !important;
            display: inline-block !important;
        }
        
        /* Reset any existing pagination styling to prevent conflicts */
        .clm-pagination a,
        .clm-pagination span,
        .clm-shortcode-pagination a,
        .clm-shortcode-pagination span,
        .page-numbers a,
        .page-numbers span,
        ul.page-numbers a,
        ul.page-numbers span {
            pointer-events: auto !important; /* Critical for clickability */
            background: none !important;
            color: inherit !important;
            border: none !important;
        }
        
        /* Then apply our clean styles */
        .clm-page-link,
        a.page-numbers,
        span.page-numbers:not(.dots),
        .clm-pagination-wrapper a,
        .clm-pagination-wrapper span:not(.dots),
        .clm-pagination a:not(.dots),
        .clm-pagination span:not(.dots),
        .clm-shortcode-pagination a:not(.dots),
        .clm-shortcode-pagination span:not(.dots),
        ul.page-numbers a,
        ul.page-numbers span:not(.dots) {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 40px !important;
            height: 40px !important;
            padding: 5px 10px !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            background: white !important;
            color: #333 !important;
            text-decoration: none !important;
            font-size: 14px !important;
            transition: all 0.2s !important;
            margin: 0 !important;
            cursor: pointer !important;
        }
        
        /* Clear "current" styling from all elements */
        .clm-pagination a,
        .clm-pagination span,
        .clm-shortcode-pagination a,
        .clm-shortcode-pagination span,
        ul.page-numbers a,
        ul.page-numbers span,
        .page-numbers a,
        .page-numbers span {
            background-color: white !important;
            color: #333 !important;
            border-color: #ddd !important;
            font-weight: normal !important;
            pointer-events: auto !important;
        }
        
        /* Then apply current styling only to specific elements */
        .clm-page-link.clm-current,
        span.page-numbers.current,
        .clm-pagination span.current,
        .clm-shortcode-pagination span.current,
        ul.page-numbers span.current {
            background-color: #007cba !important;
            color: white !important;
            border-color: #007cba !important;
            font-weight: 600 !important;
            pointer-events: none !important;
        }
        
        /* FIX: Remove body-class based highlighting that causes multiple current pages */
        body:not([class*="paged-"]) .page-numbers a:first-of-type,
        body.paged-1 .page-numbers a:first-of-type,
        body:not([class*="paged-"]) .clm-pagination-wrapper a:first-of-type,
        body.paged-1 .clm-pagination-wrapper a:first-of-type {
            background-color: white !important; 
            color: #333 !important;
            border-color: #ddd !important;
            font-weight: normal !important;
            pointer-events: auto !important;
        }
        
        .clm-page-link:hover:not(.disabled):not(.clm-current):not(.current):not(.clm-dots):not(.dots),
        a.page-numbers:hover:not(.current):not(.dots),
        .clm-pagination a:hover:not(.current):not(.dots),
        .clm-shortcode-pagination a:hover:not(.current):not(.dots),
        ul.page-numbers a:hover {
            border-color: #007cba !important;
            background: #f8f9fa !important;
            color: #007cba !important;
        }
        
        .clm-page-link.disabled,
        .page-numbers.disabled,
        span.disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
        }
        
        .clm-page-link.clm-dots,
        span.page-numbers.dots,
        .dots {
            border: none !important;
            background: transparent !important;
            cursor: default !important;
            pointer-events: none !important;
        }
        
        .clm-page-jump {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-top: 15px !important;
            gap: 8px !important;
        }
        
        .clm-page-jump-input {
            width: 60px !important;
            height: 36px !important;
            padding: 5px 10px !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            text-align: center !important;
        }
        
        .clm-go-button {
            height: 36px !important;
            padding: 0 16px !important;
            background-color: #007cba !important;
            color: white !important;
            border: none !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            cursor: pointer !important;
        }
        
        .clm-go-button:hover {
            background-color: #006ba1 !important;
        }
        
        /* Responsive styling */
        @media (max-width: 768px) {
            .clm-page-link, 
            .page-numbers a,
            .page-numbers span:not(.dots) {
                min-width: 36px !important;
                height: 36px !important;
                padding: 5px 8px !important;
                font-size: 13px !important;
            }
        }
        
        @media (max-width: 480px) {
            .clm-pagination-wrapper,
            ul.page-numbers {
                gap: 3px !important;
            }
            
            .clm-page-link, 
            .page-numbers a,
            .page-numbers span:not(.dots) {
                min-width: 32px !important;
                height: 32px !important;
                padding: 4px 7px !important;
                font-size: 12px !important;
            }
        }
        `;
        
        $('<style id="clm-pagination-styles">' + css + '</style>').appendTo('head');
        log('Pagination styles installed');
    }
    
    // Initialize only once jQuery is ready
    $(document).ready(function() {
        // Initialize once on document ready
        if (!initialized) {
            initialize('document.ready');
            
            // Also run a second time after a delay to catch AJAX-loaded content
            setTimeout(function() {
                fixAllPagination();
            }, 500);
        }
    });
    
    // Add AJAX complete handler
    $(document).ajaxComplete(function() {
        // Don't initialize again, just fix pagination
        setTimeout(function() {
            fixAllPagination();
        }, 150);
    });
    
    // Run once on load as well
    $(window).on('load', function() {
        if (!initialized) {
            initialize('window.load');
        } else {
            // If already initialized, just fix pagination
            setTimeout(function() {
                fixAllPagination();
            }, 300);
        }
    });
    
    // Export debugging tools
    window.clmPaginationDebug = {
        fixNow: fixAllPagination,
        getState: function() {
            $('.clm-pagination, .clm-shortcode-pagination').each(function(index) {
                const $container = $(this);
                log(`Pagination #${index}:`, {
                    id: $container.attr('id'),
                    class: $container.attr('class'),
                    currentPage: getCurrentPage($container),
                    maxPages: getMaxPages($container),
                    links: $container.find('a').length,
                    isFixed: fixedContainers.has($container.attr('id') || `pagination-${index}`)
                });
            });
        },
        reset: function() {
            fixedContainers.clear();
            fixAllPagination();
        }
    };

})(jQuery);


Window.checkForPaginationConflict = function() {
    console.log('----- Checking for Pagination Conflict -----');
    
    // Check if pagination has overridden any alphabet handlers
    const alphabetEvents = $._data($('.clm-alpha-link')[0], 'events') || {};
    console.log('Alphabet link events:', alphabetEvents);
    
    // Check if any pagination handlers are capturing clicks that should go to alphabet
    const paginationEvents = $._data($('.clm-pagination a')[0], 'events') || {};
    console.log('Pagination link events:', paginationEvents);
    
    // Check if there's a delegated handler on document that might be interfering
    const docEvents = $._data(document, 'events') || {};
    console.log('Document delegated events:', docEvents);
    
    // Check if the pagination fix added any conflicting CSS
    const paginationFixStyle = $('#clm-essential-pagination-css');
    console.log('Pagination fix style exists:', paginationFixStyle.length > 0);
}


})(jQuery);