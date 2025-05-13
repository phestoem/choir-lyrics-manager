/**
 * Enhanced Public JavaScript for Choir Lyrics Manager
 *
 * @package    Choir_Lyrics_Manager
 */
function debugLog(message, data) {
    console.log('[CLM Debug] ' + message, data || '');
}
(function($) {
    'use strict';
    // At the very beginning of your file, right after 'use strict';
console.log('public.js loading started');
    var currentFilters = {};
    var currentPage = 1;
    var isLoadingPage = false;
    // Global search timer
    var searchTimer;
    var currentRequest;

// Ensure currentPage is initialized
    if (typeof currentPage === 'undefined') {
        window.currentPage = 1;
    }
    
    // Ensure currentRequest is initialized
    if (typeof currentRequest === 'undefined') {
        window.currentRequest = null;
    }

    /**
     * Initialize enhanced search functionality
     */
    function initEnhancedSearch() {
        var searchInput = $('#clm-search-input');
        var searchForm = $('#clm-ajax-search-form');
        var suggestionsBox = $('#clm-search-suggestions');
        var loadingIndicator = $('.clm-search-loading');

        // Live search as user types
        searchInput.on('input', function() {
            clearTimeout(searchTimer);
            var query = $(this).val();

            if (query.length < 2) {
                suggestionsBox.hide();
                return;
            }

            loadingIndicator.show();

            searchTimer = setTimeout(function() {
                // Cancel previous request if still pending
                if (currentRequest && currentRequest.readyState !== 4) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: clm_vars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clm_ajax_search',
                        nonce: clm_vars.nonce.search,
                        query: query
                    },
                    success: function(response) {
                        loadingIndicator.hide();
                        
                        if (response.success && response.data.suggestions) {
                            displaySearchSuggestions(response.data.suggestions);
                        }
                    },
                    error: function() {
                        loadingIndicator.hide();
                    }
                });
            }, 300); // Debounce delay
        });

        // Handle search form submission
        searchForm.on('submit', function(e) {
            e.preventDefault();
            performSearch();
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
     */
    function displaySearchSuggestions(suggestions) {
        var suggestionsBox = $('#clm-search-suggestions');
        
        if (!suggestions || suggestions.length === 0) {
            suggestionsBox.hide();
            return;
        }

        var html = '<ul class="clm-suggestions-list">';
        
        suggestions.forEach(function(item) {
            html += '<li class="clm-suggestion-item" data-id="' + item.id + '">';
            html += '<a href="' + item.url + '">';
            html += '<span class="clm-suggestion-title">' + item.title + '</span>';
            
            if (item.meta) {
                html += '<span class="clm-suggestion-meta">' + item.meta + '</span>';
            }
            
            html += '</a></li>';
        });
        
        html += '</ul>';
        
        suggestionsBox.html(html).show();
    }


function updateResults(data) {
    debugLog('updateResults called', data);
    
    // Debug what elements exist
    debugLog('Target elements exist', {
        itemsList: $('#clm-items-list').length > 0,
        itemsListClass: $('.clm-items-list').length > 0,
        pagination: $('#clm-pagination').length > 0,
        paginationClass: $('.clm-pagination').length > 0,
        resultsCount: $('.clm-results-count').length > 0
    });
    
    // Update items list
    if (data.html !== undefined) {
        var updated = false;
        
        if ($('#clm-items-list').length > 0) {
            $('#clm-items-list').html(data.html);
            updated = true;
        }
        
        if ($('.clm-items-list').length > 0) {
            $('.clm-items-list').html(data.html);
            updated = true;
        }
        
        debugLog('Items list updated', updated);
    }
    
    // Update pagination
    if (data.pagination !== undefined) {
        var paginationUpdated = false;
        
        if ($('#clm-pagination').length > 0) {
            $('#clm-pagination').html(data.pagination);
            paginationUpdated = true;
        }
        
        if ($('.clm-pagination').length > 0) {
            $('.clm-pagination').html(data.pagination);
            paginationUpdated = true;
        }
        
        debugLog('Pagination updated', paginationUpdated);
        
        // Re-initialize pagination after updating DOM
        if (paginationUpdated) {
            debugLog('Re-initializing pagination handlers');
            // Don't call initPagination() here as it would create duplicate handlers
            // The delegated event handlers will work on the new elements
        }
    }
    
    // Update results count
    if (data.total !== undefined) {
        $('.clm-results-count').text(data.total);
        debugLog('Results count updated', data.total);
    }
    
    // Update current page
    if (data.page !== undefined) {
        currentPage = parseInt(data.page);
        $('#clm-page-jump-input').val(currentPage);
        debugLog('Current page updated', currentPage);
    }
}

// Add this to check pagination structure after DOM updates
function inspectPaginationStructure() {
    debugLog('Pagination structure inspection');
    
    $('.clm-pagination').each(function(index) {
        var $pagination = $(this);
        debugLog('Pagination container ' + index, {
            id: $pagination.attr('id'),
            classes: $pagination.attr('class'),
            html: $pagination.html().substring(0, 200) + '...'
        });
        
        $pagination.find('a').each(function(linkIndex) {
            var $link = $(this);
            debugLog('Pagination link ' + linkIndex, {
                href: $link.attr('href'),
                text: $link.text(),
                classes: $link.attr('class'),
                dataPage: $link.data('page')
            });
        });
    });
}

// Add this improved URL update function
function updateURL(search, filters, page) {
    var params = new URLSearchParams();
    
    if (search) {
        params.set('s', search);
    }
    
    Object.keys(filters).forEach(function(key) {
        if (filters[key]) {
            params.set(key, filters[key]);
        }
    });
    
    if (page && page > 1) {
        params.set('paged', page);
    }

    var newURL = window.location.pathname;
    var queryString = params.toString();
    
    if (queryString) {
        newURL += '?' + queryString;
    }
    
    // Use replaceState to avoid creating browser history entries for each page
    window.history.replaceState({
        page: page,
        search: search,
        filters: filters
    }, '', newURL);
}

// Add this browser history handler
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.page) {
        currentPage = event.state.page;
        performSearch(currentPage);
    }
});
 
    
/**
 * Corrected safeScrollToTop function
 */
function safeScrollToTop() {
    // Try different containers in order of preference
    var containers = ['.clm-archive', '.clm-container', 'main', 'body'];
    var scrollTarget = null;
    
    for (var i = 0; i < containers.length; i++) {
        var element = $(containers[i]);
        if (element.length > 0) {
            scrollTarget = element;
            break;
        }
    }
    
    if (scrollTarget) {
        var scrollPosition = scrollTarget.offset().top - 100;
        // Make sure we don't scroll to negative values
        scrollPosition = Math.max(0, scrollPosition);
        
        $('html, body').animate({
            scrollTop: scrollPosition  
        }, 300);
    }
}

    /**
     * Initialize filter functionality
     */
function initFilters() {
    // Toggle advanced filters
    $('.clm-toggle-filters').on('click', function(e) {
        e.preventDefault();
        $('.clm-advanced-filters').slideToggle(300);
        $(this).toggleClass('active');
    });

    // Quick filters
    $('.clm-quick-filter').on('click', function(e) {
        e.preventDefault();
        
        $('.clm-quick-filter').removeClass('active');
        $(this).addClass('active');

        var filterType = $(this).data('filter');
        var filterValue = $(this).data('value');

        if (filterType === 'all') {
            // Reset all filters
            $('#clm-filter-form')[0].reset();
            currentFilters = {};
        } else {
            // Apply specific filter
            $('#clm-' + filterType + '-select').val(filterValue);
        }

        currentPage = 1; // Reset to first page when filtering
        performSearch();
    });

    // Apply filters button
    $('.clm-apply-filters').on('click', function(e) {
        e.preventDefault();
        currentPage = 1; // Reset to first page when filtering
        performSearch();
    });

    // Reset filters
    $('.clm-reset-filters').on('click', function(e) {
        e.preventDefault();
        $('#clm-filter-form')[0].reset();
        $('#clm-search-input').val('');
        $('.clm-quick-filter').removeClass('active');
        $('.clm-quick-filter[data-filter="all"]').addClass('active');
        $('.clm-alpha-link').removeClass('active');
        $('.clm-alpha-link[data-filter="all"]').addClass('active');
        currentFilters = {};
        currentPage = 1;
        performSearch();
    });

    // Items per page
    $('#clm-items-per-page').on('change', function() {
        currentPage = 1; // Reset to first page when changing items per page
        performSearch();
    });
}

    /**
     * Initialize alphabet navigation
     */
   function initAlphabetNav() {
    $('.clm-alpha-link').on('click', function(e) {
        e.preventDefault();
        
        $('.clm-alpha-link').removeClass('active');
        $(this).addClass('active');

        var letter = $(this).data('letter');
        
        // Reset page to 1 when changing filters
        currentPage = 1;
        
        if (letter === 'all') {
            // Remove alphabet filter
            delete currentFilters.starts_with;
        } else {
            // Apply alphabet filter
            currentFilters.starts_with = letter;
        }
        
        // Perform search with page 1
        performSearch(1);
    });
}
// Keep only one version of initPagination
function initPagination() {
    // Remove all existing pagination handlers
    $(document).off('click', '.clm-pagination a');
    $(document).off('click', '#clm-page-jump-button');
    
    // Attach new handler with namespace
    $(document).on('click.clm', '.clm-pagination a', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        
        if (isLoadingPage) {
            debugLog('Already loading, ignoring click');
            return false;
        }
        
        var $link = $(this);
        
        // Skip if current page
        if ($link.hasClass('current') || $link.find('.current').length > 0) {
            return false;
        }
        
        var page = extractPageNumber($link);
        
        if (page && page !== currentPage) {
            debugLog('Navigating to page:', page);
            loadPage(page);
        }
        
        return false;
    });
    
    // Page jump handler
    $(document).on('click.clm', '#clm-page-jump-button', function(e) {
        e.preventDefault();
        
        var page = parseInt($('#clm-page-jump-input').val());
        var maxPage = parseInt($('#clm-page-jump-input').attr('max'));
        
        if (page && page >= 1 && page <= maxPage && page !== currentPage) {
            loadPage(page);
        }
    });
}

// New function to handle page loading
// This should be your main page loading function
function loadPage(page) {
    if (isLoadingPage) {
        console.log('Already loading a page, ignoring request');
        return;
    }
    
    isLoadingPage = true;
    currentPage = page;
    
    var query = $('#clm-search-input').val();
    var filters = collectFilters();
    
    console.log('Loading page:', page);
    console.log('Filters:', filters);
    
    showLoadingOverlay();
    
    // Abort any existing request
    if (window.currentRequest && window.currentRequest.readyState !== 4) {
        window.currentRequest.abort();
    }
    
    window.currentRequest = $.ajax({
        url: clm_vars.ajaxurl,
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: {
            action: 'clm_ajax_filter',
            nonce: clm_vars.nonce.filter,
            search: query,
            filters: filters,
            page: page
        },
        success: function(response) {
            console.log('AJAX response received:', response);
            
            if (response.success) {
                console.log('Response data:', {
                    total: response.data.total,
                    page: response.data.page,
                    max_pages: response.data.max_pages,
                    has_pagination: !!response.data.pagination,
                    pagination_length: response.data.pagination ? response.data.pagination.length : 0
                });
                
                updatePageContent(response.data);
                updateURL(query, filters, page);
                safeScrollToTop();
            } else {
                console.error('Error in response:', response);
                alert('Error: ' + (response.data?.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            if (status !== 'abort') {
                alert('Error loading page. Please try again.');
            }
        },
        complete: function() {
            hideLoadingOverlay();
            isLoadingPage = false;
            
            // Final check for pagination visibility
            setTimeout(function() {
                var paginationElements = $('.clm-pagination, #clm-pagination');
                console.log('Final pagination check:', {
                    elements_found: paginationElements.length,
                    has_content: paginationElements.html() ? paginationElements.html().length : 0,
                    is_visible: paginationElements.is(':visible')
                });
            }, 100);
        }
    });
}

/**
 * / Clean version of updatePageContent
function updatePageContent(data) {
    debugLog('Updating page content:', data);
    
    // Update items
    if (data.html) {
        $('.clm-items-list, #clm-items-list').html(data.html);
    }
    
    // Update pagination
    if (data.pagination) {
        $('.clm-pagination, #clm-pagination').html(data.pagination);
        // Reinitialize playlist management if needed
        if (typeof initPlaylistManagement === 'function') {
            initPlaylistManagement();
        }
    }
    
    // Update count
    if (data.total !== undefined) {
        $('.clm-results-count').text(data.total);
    }
    
    // Update page input
    if (data.page) {
        $('#clm-page-jump-input').val(data.page);
        currentPage = parseInt(data.page);
    }
    
    debugLog('Page update complete. Current page:', currentPage);
}
*/
/**
 * Debug version of updatePageContent
 * Add this to your public.js temporarily to debug
 */

function updatePageContent(data) {
    console.log('updatePageContent called with:', data);
    console.log('Pagination HTML length:', data.pagination ? data.pagination.length : 0);
    
    // Update items
    if (data.html !== undefined) {
        $('.clm-items-list, #clm-items-list').html(data.html);
        console.log('Items updated');
    }
    
    // Update pagination - More robust selectors
    if (data.pagination !== undefined) {
        console.log('Updating pagination with HTML:', data.pagination.substring(0, 100) + '...');
        
        // Try multiple selectors
        var paginationUpdated = false;
        
        // First try ID
        if ($('#clm-pagination').length > 0) {
            console.log('Found pagination by ID');
            $('#clm-pagination').html(data.pagination);
            paginationUpdated = true;
        }
        
        // Then try class
        if ($('.clm-pagination').length > 0) {
            console.log('Found pagination by class');
            $('.clm-pagination').html(data.pagination);
            paginationUpdated = true;
        }
        
        // If no container exists, create one
        if (!paginationUpdated) {
            console.log('No pagination container found, creating one');
            var itemsList = $('.clm-items-list, #clm-items-list');
            if (itemsList.length > 0) {
                // Remove any existing pagination first
                itemsList.siblings('.clm-pagination').remove();
                // Add new pagination after items list
                itemsList.after('<div class="clm-pagination" id="clm-pagination">' + data.pagination + '</div>');
                paginationUpdated = true;
            }
        }
        
        console.log('Pagination updated:', paginationUpdated);
    } else {
        console.log('No pagination in response data');
    }
    
    // Update count
    if (data.total !== undefined) {
        $('.clm-results-count').text(data.total);
    }
    
    // Update page input
    if (data.page) {
        $('#clm-page-jump-input').val(data.page);
        currentPage = parseInt(data.page);
    }
    
    // Reinitialize playlist management if needed
    if (typeof initPlaylistManagement === 'function') {
        initPlaylistManagement();
    }
}

// Override performSearch to use the new loadPage function
function performSearch(page) {
    page = page || 1;
    currentPage = 1; // Reset to page 1 for new searches
    loadPage(page);
}

// Simplified page extraction function
function extractPageNumber($link) {
    var page = null;
    
    // Check data-page attribute first
    if ($link.data('page')) {
        return parseInt($link.data('page'));
    }
    
    var href = $link.attr('href');
    
    // Check link text for numeric pages
    var linkText = $link.text().trim();
    if (/^\d+$/.test(linkText)) {
        return parseInt(linkText);
    }
    
    // Extract from href
    var patterns = [
        /[?&]paged=(\d+)/,
        /\/page\/(\d+)/,
        /page=(\d+)/,
        /\/(\d+)\/?$/
    ];
    
    for (var i = 0; i < patterns.length; i++) {
        var match = href.match(patterns[i]);
        if (match) {
            return parseInt(match[1]);
        }
    }
    
    // Check for prev/next
    if ($link.hasClass('prev') || href.includes('prev')) {
        return Math.max(1, currentPage - 1);
    }
    
    if ($link.hasClass('next') || href.includes('next')) {
        return currentPage + 1;
    }
    
    return null;
}
    /**
     * Collect all active filters
     */
   function collectFilters() {
    var filters = {
        genre: $('#clm-genre-select').val(),
        language: $('#clm-language-select').val(),
        difficulty: $('#clm-difficulty-select').val(),
        year_from: $('input[name="year_from"]').val(),
        year_to: $('input[name="year_to"]').val(),
        orderby: $('#clm-sort-select').val(),
        order: $('select[name="order"]').val(),
        per_page: $('#clm-items-per-page').val()
    };

    // Make sure to include current filters (like alphabet)
    $.extend(filters, currentFilters);

    // Remove empty values
    Object.keys(filters).forEach(function(key) {
        if (!filters[key] || filters[key] === '') {
            delete filters[key];
        }
    });

    debugLog('Collected filters:', filters);
    return filters;
}


    /**
     * Update results display
     */
    function updateResults(data) {
        // Update items list - using class selector
        $('.clm-items-list').html(data.html);
        
        // Update pagination - using class selector
        $('.clm-pagination').html(data.pagination);
        
        // Update results count
        $('.clm-results-count').text(data.total);
        
        // Reinitialize any necessary functionality
        initPlaylistManagement();
        
        // If alphabet filter was active, we don't need to reapply it
        // since the server already filtered the results
    }



    /**
     * Update URL without page reload
     */
    function updateURL(search, filters, page) {
        var params = new URLSearchParams();
        
        if (search) {
            params.set('s', search);
        }
        
        Object.keys(filters).forEach(function(key) {
            if (filters[key]) {
                params.set(key, filters[key]);
            }
        });
        
        if (page && page > 1) {
            params.set('paged', page);
        }

        var newURL = window.location.pathname + '?' + params.toString();
        window.history.pushState({}, '', newURL);
    }

    /**
     * Get page number from URL
     */
    function getPageFromURL(url) {
    var match = url.match(/paged=(\d+)/);
    if (match) return parseInt(match[1]);
    
    // Try alternative pagination format
    match = url.match(/page\/(\d+)/);
    if (match) return parseInt(match[1]);
    
    // Try query parameter
    var urlParams = new URLSearchParams(url.split('?')[1]);
    if (urlParams.has('paged')) {
        return parseInt(urlParams.get('paged'));
    }
    
    return 1;
}
/**
 * Initialize form URL on page load
 */
function initializeFromURL() {
    var urlParams = new URLSearchParams(window.location.search);
    
    // Set page
    if (urlParams.has('paged')) {
        currentPage = parseInt(urlParams.get('paged'));
        $('#clm-page-jump-input').val(currentPage);
    }
    
    // Set search
    if (urlParams.has('s')) {
        $('#clm-search-input').val(urlParams.get('s'));
    }
    
    // Set filters
    if (urlParams.has('genre')) {
        $('#clm-genre-select').val(urlParams.get('genre'));
        currentFilters.genre = urlParams.get('genre');
    }
    if (urlParams.has('language')) {
        $('#clm-language-select').val(urlParams.get('language'));
        currentFilters.language = urlParams.get('language');
    }
    if (urlParams.has('difficulty')) {
        $('#clm-difficulty-select').val(urlParams.get('difficulty'));
        currentFilters.difficulty = urlParams.get('difficulty');
    }
    if (urlParams.has('starts_with')) {
        var letter = urlParams.get('starts_with');
        $('.clm-alpha-link[data-letter="' + letter + '"]').addClass('active');
        $('.clm-alpha-link[data-letter="all"]').removeClass('active');
        currentFilters.starts_with = letter;
    }
}

    /**
     * Show loading overlay
     */
    function showLoadingOverlay() {
        $('#clm-loading-overlay').fadeIn(200);
    }

    /**
     * Hide loading overlay
     */
    function hideLoadingOverlay() {
        $('#clm-loading-overlay').fadeOut(200);
    }

    /**
     * Update visible results count
     */
    function updateResultsCount() {
        var visibleCount = $('.clm-lyric-item:visible').length;
        $('.clm-results-count').text(visibleCount);
    }

    /**
     * Practice tracking functionality (existing)
     */
    function initPracticeTracking() {
        // Log practice button
        $('#clm-log-practice').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var lyricId = $('.clm-practice-tracker').data('lyric-id');
            var duration = $('#clm-practice-duration').val();
            var confidence = $('#clm-practice-confidence').val();
            var notes = $('#clm-practice-notes').val();
            var messageContainer = $('#clm-practice-message');
            
            // Validate inputs
            if (!duration || duration < 1) {
                messageContainer.html('<div class="clm-error">Please enter a valid duration.</div>');
                return;
            }
            
            // Disable button to prevent multiple submissions
            button.prop('disabled', true).text('Logging...');
            
            // Clear previous messages
            messageContainer.empty();
            
            // Make AJAX request to log practice
            $.ajax({
                url: clm_vars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clm_update_practice_log',
                    nonce: clm_vars.nonce.practice,
                    lyric_id: lyricId,
                    duration: duration,
                    confidence: confidence,
                    notes: notes
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        messageContainer.html('<div class="clm-success">' + clm_vars.text.practice_success + '</div>');
                        
                        // Reset form
                        $('#clm-practice-notes').val('');
                        
                        // Update stats if provided
                        if (response.data && response.data.stats) {
                            updatePracticeStats(response.data.stats);
                        }
                        
                        // Reload practice history
                        if ($('.clm-practice-history').length) {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        // Show error message
                        messageContainer.html('<div class="clm-error">' + (response.data ? response.data.message : 'Error logging practice.') + '</div>');
                    }
                },
                error: function() {
                    // Show error message
                    messageContainer.html('<div class="clm-error">Error communicating with the server.</div>');
                },
                complete: function() {
                    // Re-enable button
                    button.prop('disabled', false).text('Log Practice');
                }
            });
        });
        
        // View all history link
        $('.clm-view-all-history').on('click', function(e) {
            e.preventDefault();
            
            // Toggle showing all history vs. just recent history
            var historyContainer = $('.clm-practice-history');
            
            if (historyContainer.hasClass('showing-all')) {
                historyContainer.removeClass('showing-all');
                $(this).text('View All History');
                
                // Hide older entries
                $('.clm-practice-history-table tr').slice(5).hide();
            } else {
                historyContainer.addClass('showing-all');
                $(this).text('Show Less');
                
                // Show all entries
                $('.clm-practice-history-table tr').show();
            }
        });
    }
    
    /**
     * Update practice stats display
     */
    function updatePracticeStats(stats) {
        // Update total practice time
        if (stats.total_time !== undefined) {
            $('.clm-practice-stat:nth-child(1) .clm-stat-value').text(formatDuration(stats.total_time));
        }
        
        // Update practice sessions
        if (stats.sessions !== undefined) {
            $('.clm-practice-stat:nth-child(2) .clm-stat-value').text(stats.sessions);
        }
        
        // Update confidence level
        if (stats.confidence !== undefined) {
            var starsHtml = '';
            
            for (var i = 1; i <= 5; i++) {
                if (i <= stats.confidence) {
                    starsHtml += '<span class="dashicons dashicons-star-filled"></span>';
                } else {
                    starsHtml += '<span class="dashicons dashicons-star-empty"></span>';
                }
            }
            
            $('.clm-practice-stat:nth-child(4) .clm-stat-value').html(starsHtml);
        }
    }
    
    /**
     * Format duration in minutes to hours and minutes
     */
    function formatDuration(minutes) {
        minutes = parseInt(minutes, 10);
        
        if (minutes < 60) {
            return minutes + (minutes === 1 ? ' minute' : ' minutes');
        }
        
        var hours = Math.floor(minutes / 60);
        var mins = minutes % 60;
        
        if (mins === 0) {
            return hours + (hours === 1 ? ' hour' : ' hours');
        }
        
        return hours + (hours === 1 ? ' hour' : ' hours') + ', ' + mins + (mins === 1 ? ' minute' : ' minutes');
    }
    
    /**
     * Playlist management functionality (existing)
     */
    /**
 * Fix for the playlist functionality in public.js
 * Replace the existing initPlaylistManagement function
 */
function initPlaylistManagement() {
    // Toggle playlist dropdown
    $('.clm-playlist-dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        $(this).siblings('.clm-playlist-dropdown-menu').toggle();
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.clm-playlist-dropdown').length) {
            $('.clm-playlist-dropdown-menu').hide();
        }
    });
    
    // Add to playlist button
    $('.clm-add-to-playlist').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var playlistId = button.data('playlist-id');
        var lyricId = button.data('lyric-id');
        
        // Make AJAX request to add to playlist
        $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'clm_add_to_playlist',
                nonce: clm_vars.nonce.playlist,
                playlist_id: playlistId,
                lyric_id: lyricId
            },
            success: function(response) {
                if (response.success) {
                    button.removeClass('clm-add-to-playlist').addClass('clm-in-playlist');
                    button.text('Added');
                } else {
                    alert(response.data.message || clm_vars.text.playlist_error);
                }
            },
            error: function() {
                alert(clm_vars.text.playlist_error);
            }
        });
    });
    
    // Remove from playlist button
    $('.clm-remove-from-playlist').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(clm_vars.text.confirm_remove)) {
            return;
        }
        
        var button = $(this);
        var playlistId = button.data('playlist-id');
        var lyricId = button.data('lyric-id');
        var listItem = button.closest('.clm-playlist-item');
        
        // Make AJAX request to remove from playlist
        $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'clm_remove_from_playlist',
                nonce: clm_vars.nonce.playlist,
                playlist_id: playlistId,
                lyric_id: lyricId
            },
            success: function(response) {
                if (response.success) {
                    listItem.slideUp(300, function() {
                        $(this).remove();
                        
                        // Check if playlist is now empty
                        if ($('.clm-playlist-item').length === 0) {
                            $('.clm-playlist-items').html('<p class="clm-notice">This playlist is empty.</p>');
                        }
                    });
                } else {
                    alert(response.data.message || clm_vars.text.playlist_error);
                }
            },
            error: function() {
                alert(clm_vars.text.playlist_error);
            }
        });
    });
    
    // Create playlist button - FIX: Make this specific to each card
    $(document).off('click', '.clm-create-playlist-button');
    $(document).on('click', '.clm-create-playlist-button', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent event bubbling
        var $button = $(this);
        var $itemActions = $button.closest('.clm-item-actions');
        
        // Hide the button and show the form only for this specific item
        $button.hide();
        $itemActions.find('.clm-create-playlist-form').show();
    });
    
    // Cancel playlist creation - FIX: Make this specific to each card
    $(document).off('click', '.clm-cancel-playlist');
    $(document).on('click', '.clm-cancel-playlist', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $itemActions = $button.closest('.clm-item-actions');
        
        // Hide the form and show the button only for this specific item
        $itemActions.find('.clm-create-playlist-form').hide();
        $itemActions.find('.clm-create-playlist-button').show();
    });
    
    // Submit playlist creation
    $(document).off('click', '.clm-submit-playlist');
    $(document).on('click', '.clm-submit-playlist', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $form = $button.closest('.clm-create-playlist-form');
        var $nameField = $form.find('.clm-playlist-name');
        var name = $nameField.val();
        var description = $form.find('.clm-playlist-description').val();
        var visibility = $form.find('input[name="clm-playlist-visibility"]:checked').val();
        var lyricId = $button.data('lyric-id') || 0;
        
        // Validate name
        if (!name) {
            alert('Please enter a playlist name.');
            $nameField.focus();
            return;
        }
        
        // Disable button to prevent multiple submissions
        $button.prop('disabled', true).text('Creating...');
        
        // Make AJAX request to create playlist
        $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'clm_create_playlist',
                nonce: clm_vars.nonce.playlist,
                playlist_name: name,
                playlist_description: description,
                playlist_visibility: visibility,
                lyric_id: lyricId
            },
            success: function(response) {
                if (response.success) {
                    alert(clm_vars.text.playlist_success);
                    location.reload();
                } else {
                    alert(response.data.message || clm_vars.text.playlist_error);
                    $button.prop('disabled', false).text('Create Playlist');
                }
            },
            error: function() {
                alert(clm_vars.text.playlist_error);
                $button.prop('disabled', false).text('Create Playlist');
            }
        });
    });
}
    
    /**
     * User dashboard functionality (existing)
     */
    function initUserDashboard() {
        // Tab navigation
        $('.clm-dashboard-nav-item').on('click', function(e) {
            e.preventDefault();
            
            var tabId = $(this).data('tab');
            
            // Update active tab
            $('.clm-dashboard-nav-item').removeClass('active');
            $(this).addClass('active');
            
            // Show selected tab content
            $('.clm-dashboard-tab').hide();
            $('#' + tabId).show();
            
            // Update URL hash
            window.location.hash = tabId;
        });
        
        // Check for hash in URL and activate corresponding tab
        var hash = window.location.hash.substring(1);
        
        if (hash && $('#' + hash).length) {
            $('.clm-dashboard-nav-item[data-tab="' + hash + '"]').trigger('click');
        } else {
            // Activate first tab by default
            $('.clm-dashboard-nav-item:first').trigger('click');
        }
    }
    
   /**
 * Add debugging for browser compatibility
 * Add this at the beginning of your jQuery ready function
 */
$(document).ready(function() {
    // Browser detection for debugging
    var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
    var isEdge = /Edg/.test(navigator.userAgent);
    var isBrave = navigator.brave && navigator.brave.isBrave();
    
 // Debug: Check if clm_vars is properly loaded
    console.log('clm_vars object:', clm_vars);
    console.log('AJAX URL:', clm_vars.ajaxurl);
    console.log('Nonces:', clm_vars.nonce);

 // Get initial page from URL
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('paged')) {
        currentPage = parseInt(urlParams.get('paged')) || 1;
    }
console.log('Initializing pagination. Current page:', currentPage);
    
    // Initialize pagination
    initPagination();

    // Prevent form submission on enter in search
    $('#clm-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            performSearch();
        }
    });
// Check if all expected nonces exist
    var expectedNonces = ['practice', 'playlist', 'search', 'filter'];
    var missingNonces = [];
    
    expectedNonces.forEach(function(nonce) {
        if (!clm_vars.nonce[nonce]) {
            missingNonces.push(nonce);
        }
    });
    
    if (missingNonces.length > 0) {
        console.error('Missing nonces:', missingNonces);
    }
    console.log('Browser detection:', {
        chrome: isChrome,
        edge: isEdge,
        brave: isBrave,
        userAgent: navigator.userAgent
    });
    
    // Add error handling
    window.addEventListener('error', function(e) {
        console.error('JavaScript error:', e.message, 'at', e.filename, ':', e.lineno);
    });
    
    // Check if jQuery is properly loaded
    console.log('jQuery version:', $.fn.jquery);
    
    // Check if our functions are defined
    console.log('Functions defined:', {
        initEnhancedSearch: typeof initEnhancedSearch,
        initFilters: typeof initFilters,
        initAlphabetNav: typeof initAlphabetNav,
        initPagination: typeof initPagination,
        initPlaylistManagement: typeof initPlaylistManagement
    });
    
    // Initialize from URL parameters
    initializeFromURL();
    
    // Initialize enhanced search
    initEnhancedSearch();
    
    // Initialize filters
    initFilters();
    
    // Initialize alphabet navigation
    initAlphabetNav();
    
    // Initialize pagination
    initPagination();
    
    // Initialize original features
    initPracticeTracking();
    initPlaylistManagement();
    initUserDashboard();
});
 

/**
 * Initialize shortcode features
 * @param {string} containerId - The unique ID of the shortcode container
 */
function initShortcodeFeatures(containerId) {
    var container = $('#' + containerId);
    
    if (!container.length) return;
    
    // Initialize search functionality
    initShortcodeSearch(container);
    
    // Initialize filters
    initShortcodeFilters(container);
    
    // Initialize alphabet navigation
    initShortcodeAlphabet(container);
    
    // Initialize pagination
    initShortcodePagination(container);
}

/**
 * Initialize shortcode search
 */
function initShortcodeSearch(container) {
    var searchForm = container.find('.clm-shortcode-search-form');
    var searchInput = container.find('.clm-search-input');
    var suggestionsBox = container.find('.clm-search-suggestions');
    var loadingIndicator = container.find('.clm-search-loading');
    var searchTimer;
    
    // Handle form submission
    searchForm.on('submit', function(e) {
        e.preventDefault();
        performShortcodeSearch(container);
    });
    
    // Live search suggestions (if AJAX is enabled)
    if (container.data('ajax') === 'yes') {
        searchInput.on('input', function() {
            clearTimeout(searchTimer);
            var query = $(this).val();
            
            if (query.length < 2) {
                suggestionsBox.hide();
                return;
            }
            
            loadingIndicator.show();
            
            searchTimer = setTimeout(function() {
                $.ajax({
                    url: clm_vars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clm_ajax_search',
                        nonce: clm_vars.nonce.search,
                        query: query
                    },
                    success: function(response) {
                        loadingIndicator.hide();
                        
                        if (response.success && response.data.suggestions) {
                            displayShortcodeSuggestions(response.data.suggestions, suggestionsBox);
                        }
                    },
                    error: function() {
                        loadingIndicator.hide();
                    }
                });
            }, 300);
        });
    }
}
// Make the function available globally if needed
//if (typeof window.initShortcodeFeatures === 'undefined') {
  //  window.initShortcodeFeatures = initShortcodeFeatures;
//}
// Right after making it globally available
window.initShortcodeFeatures = initShortcodeFeatures;
//console.log('initShortcodeFeatures exposed globally:', typeof window.initShortcodeFeatures);

/**
 * Display search suggestions for shortcode
 */
function displayShortcodeSuggestions(suggestions, suggestionsBox) {
    if (!suggestions || suggestions.length === 0) {
        suggestionsBox.hide();
        return;
    }
    
    var html = '<ul class="clm-suggestions-list">';
    
    suggestions.forEach(function(item) {
        html += '<li class="clm-suggestion-item">';
        html += '<a href="' + item.url + '">';
        html += '<span class="clm-suggestion-title">' + item.title + '</span>';
        
        if (item.meta) {
            html += '<span class="clm-suggestion-meta">' + item.meta + '</span>';
        }
        
        html += '</a></li>';
    });
    
    html += '</ul>';
    
    suggestionsBox.html(html).show();
}

/**
 * Initialize shortcode filters
 */
function initShortcodeFilters(container) {
    // Toggle advanced filters
    container.find('.clm-toggle-filters').on('click', function(e) {
        e.preventDefault();
        container.find('.clm-advanced-filters').slideToggle(300);
        $(this).toggleClass('active');
    });
    
    // Quick filters
    container.find('.clm-quick-filter').on('click', function(e) {
        e.preventDefault();
        
        container.find('.clm-quick-filter').removeClass('active');
        $(this).addClass('active');
        
        var filterType = $(this).data('filter');
        var filterValue = $(this).data('value');
        
        if (filterType === 'all') {
            // Reset filters
            container.find('.clm-shortcode-filter-form')[0].reset();
        } else {
            // Apply specific filter
            container.find('select[name="' + filterType + '"]').val(filterValue);
        }
        
        performShortcodeSearch(container);
    });
    
    // Apply filters
    container.find('.clm-shortcode-filter-form').on('submit', function(e) {
        e.preventDefault();
        performShortcodeSearch(container);
    });
    
    // Reset filters
    container.find('.clm-reset-filters').on('click', function(e) {
        e.preventDefault();
        container.find('.clm-shortcode-filter-form')[0].reset();
        container.find('.clm-search-input').val('');
        container.find('.clm-quick-filter').removeClass('active');
        container.find('.clm-quick-filter[data-filter="all"]').addClass('active');
        performShortcodeSearch(container);
    });
    
    // Items per page
    container.find('.clm-items-per-page').on('change', function() {
        performShortcodeSearch(container);
    });
}

/**
 * Initialize shortcode alphabet navigation
 */
function initShortcodeAlphabet(container) {
    container.find('.clm-alpha-link').on('click', function(e) {
        e.preventDefault();
        
        container.find('.clm-alpha-link').removeClass('active');
        $(this).addClass('active');
        
        var letter = $(this).data('letter');
        
        if (letter === 'all') {
            container.find('.clm-lyric-item').show();
        } else {
            container.find('.clm-lyric-item').each(function() {
                var title = $(this).data('title');
                if (title && title.charAt(0).toUpperCase() === letter) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
        
        updateShortcodeResultsCount(container);
    });
}

/**
 * Fix for shortcode pagination
 */
function initShortcodePagination(container) {
    if (container.data('ajax') !== 'yes') {
        // If not using AJAX, pagination works with standard page reloads
        return;
    }
    
    // AJAX pagination
    container.on('click', '.clm-pagination a', function(e) {
        e.preventDefault();
        
        var url = $(this).attr('href');
        var page = getPageFromURL(url);
        
        performShortcodeSearch(container, page);
        
        // Correct scrollTop animation
        $('html, body').animate({
            scrollTop: container.offset().top - 100
        }, 300);
    });
}
/**
 * Perform shortcode search/filter
 */
function performShortcodeSearch(container, page) {
    // Check if AJAX is enabled
    if (container.data('ajax') !== 'yes') {
        // Submit form normally for page reload
        var form = container.find('.clm-shortcode-search-form, .clm-shortcode-filter-form').first();
        
        // Add page parameter if specified
        if (page) {
            $('<input>').attr({
                type: 'hidden',
                name: 'paged',
                value: page
            }).appendTo(form);
        }
        
        form.submit();
        return;
    }
    
    // Collect all parameters
    var params = {
        action: 'clm_shortcode_filter',
        nonce: clm_vars.nonce.filter,
        container_id: container.attr('id'),
        page: page || 1
    };
    
    // Add search query
    var searchQuery = container.find('.clm-search-input').val();
    if (searchQuery) {
        params.search = searchQuery;
    }
    
    // Add filters
    var filterData = container.find('.clm-shortcode-filter-form').serializeArray();
    filterData.forEach(function(item) {
        if (item.value) {
            params['filter_' + item.name] = item.value;
        }
    });
    
    // Add per page
    params.per_page = container.find('.clm-items-per-page').val();
    
    // Show loading
    container.find('.clm-loading-overlay').show();
    
    // Perform AJAX request
    $.ajax({
        url: clm_vars.ajaxurl,
        type: 'POST',
        data: params,
        success: function(response) {
            container.find('.clm-loading-overlay').hide();
            
            if (response.success) {
                // Update results
                container.find('.clm-shortcode-results').html(response.data.html);
                
                // Update results count
                container.find('.clm-results-count').text(response.data.total);
                
                // Reinitialize features
                initPlaylistManagement();
                updateShortcodeResultsCount(container);
            }
        },
        error: function() {
            container.find('.clm-loading-overlay').hide();
            alert('Error loading results. Please try again.');
        }
    });
}

/**
 * Update shortcode results count
 */
function updateShortcodeResultsCount(container) {
    var visibleCount = container.find('.clm-lyric-item:visible').length;
    container.find('.clm-results-count').text(visibleCount);
}

/**
 * Get page number from URL
 */
function getPageFromURL(url) {
    var match = url.match(/paged=(\d+)/);
    if (match) return parseInt(match[1]);
    
    match = url.match(/page\/(\d+)/);
    if (match) return parseInt(match[1]);
    
    return 1;
}
// At the very end of your file, before })(jQuery);
console.log('public.js fully loaded');
/**
 * Add this test function to your public.js to manually test pagination
 */

/**
 * Debug script for pagination - Add this to public.js or create a separate debug file
 */

// Make functions globally accessible
window.testPagination = function() {
    console.log('=== Pagination Test ===');
    
    // Check if pagination containers exist
    var containers = jQuery('.clm-pagination, #clm-pagination');
    console.log('Pagination containers found:', containers.length);
    
    containers.each(function(index) {
        var $container = jQuery(this);
        console.log('Container ' + index + ':');
        console.log('  - ID:', $container.attr('id'));
        console.log('  - Classes:', $container.attr('class'));
        console.log('  - Display:', $container.css('display'));
        console.log('  - Visibility:', $container.css('visibility'));
        console.log('  - Height:', $container.height());
        console.log('  - HTML length:', $container.html().length);
        console.log('  - Has content:', $container.html().substring(0, 100));
        console.log('  - Parent:', $container.parent().attr('class'));
    });
    
    // Check the archive template structure
    console.log('\n=== Archive Structure ===');
    var archive = jQuery('.clm-archive');
    console.log('Archive container:', archive.length > 0 ? 'Found' : 'Not found');
    
    var resultContainer = jQuery('#clm-results-container');
    console.log('Results container:', resultContainer.length > 0 ? 'Found' : 'Not found');
    
    // Look for pagination in different places
    console.log('\n=== Searching for pagination elements ===');
    var allPagination = jQuery('div[class*="pagination"], ul[class*="pagination"]');
    console.log('All pagination elements:', allPagination.length);
    
    allPagination.each(function(index) {
        console.log('Element ' + index + ':', {
            tag: this.tagName,
            classes: jQuery(this).attr('class'),
            id: jQuery(this).attr('id'),
            html: jQuery(this).html().substring(0, 50) + '...'
        });
    });
    
    // Test injection
    console.log('\n=== Testing Pagination Injection ===');
    var testHtml = '<div style="background: yellow; padding: 10px; border: 2px solid red;">TEST PAGINATION: <a href="#">1</a> <span>2</span> <a href="#">3</a></div>';
    
    // Try different containers
    if (containers.length > 0) {
        console.log('Injecting into existing pagination container...');
        containers.first().html(testHtml);
    } else {
        console.log('No pagination container found, creating one...');
        
        // Try to append after the results list
        var itemsList = jQuery('.clm-items-list');
        if (itemsList.length > 0) {
            console.log('Adding after items list...');
            itemsList.after('<div class="clm-pagination" id="test-pagination">' + testHtml + '</div>');
        } else {
            console.log('No items list found either');
        }
    }
};

// Debug AJAX response
window.debugAjaxResponse = function() {
    // Override jQuery ajax to log responses
    var originalAjax = jQuery.ajax;
    jQuery.ajax = function(options) {
        var originalSuccess = options.success;
        
        options.success = function(response) {
            if (options.url && options.url.includes('admin-ajax.php')) {
                console.log('=== AJAX Response Debug ===');
                console.log('URL:', options.url);
                console.log('Data sent:', options.data);
                console.log('Response:', response);
                
                if (response.success && response.data) {
                    console.log('Pagination HTML exists:', !!response.data.pagination);
                    console.log('Pagination length:', response.data.pagination ? response.data.pagination.length : 0);
                    console.log('Pagination preview:', response.data.pagination ? response.data.pagination.substring(0, 200) : 'None');
                }
            }
            
            if (originalSuccess) {
                originalSuccess.apply(this, arguments);
            }
        };
        
        return originalAjax.call(this, options);
    };
    
    console.log('AJAX debugging enabled');
};

/**
 * Diagnostic function to check pagination state
 * Add this to your public.js and run it in console
 */

window.checkPaginationState = function() {
    console.log('=== Pagination State Check ===');
    
    // Check for pagination containers
    var byId = $('#clm-pagination');
    var byClass = $('.clm-pagination');
    var allDivs = $('div').filter(function() {
        return this.className && this.className.includes('pagination');
    });
    
    console.log('Pagination by ID (#clm-pagination):', byId.length);
    console.log('Pagination by class (.clm-pagination):', byClass.length);
    console.log('All divs with "pagination" in class:', allDivs.length);
    
    // Check parent structure
    var itemsList = $('.clm-items-list, #clm-items-list');
    console.log('Items list found:', itemsList.length);
    
    if (itemsList.length > 0) {
        console.log('Items list parent:', itemsList.parent().attr('class'));
        console.log('Items list siblings:', itemsList.siblings().length);
        
        itemsList.siblings().each(function(index) {
            console.log('Sibling ' + index + ':', {
                tag: this.tagName,
                classes: $(this).attr('class'),
                id: $(this).attr('id'),
                html_length: $(this).html().length
            });
        });
    }
    
    // Check results container
    var resultsContainer = $('#clm-results-container');
    console.log('Results container found:', resultsContainer.length);
    
    if (resultsContainer.length > 0) {
        console.log('Results container children:', resultsContainer.children().length);
        resultsContainer.children().each(function(index) {
            console.log('Child ' + index + ':', {
                tag: this.tagName,
                classes: $(this).attr('class'),
                id: $(this).attr('id')
            });
        });
    }
    
    // Create test pagination
    console.log('\n=== Creating Test Pagination ===');
    
    if (itemsList.length > 0) {
        // Remove any existing test
        $('#test-pagination').remove();
        
        // Add test pagination
        var testHtml = '<div id="test-pagination" style="background: yellow; padding: 20px; margin: 20px 0; text-align: center; border: 3px solid red;">TEST PAGINATION VISIBLE</div>';
        itemsList.after(testHtml);
        
        console.log('Test pagination added. Can you see it?');
    }
};

// Auto-run debug on page load
jQuery(document).ready(function() {
    console.log('=== Page Load Debug ===');
    
    // Check initial state
    setTimeout(function() {
        console.log('Running initial pagination check...');
        window.testPagination();
        
        // Enable AJAX debugging
        window.debugAjaxResponse();
    }, 1000);
    
    // Monitor DOM changes
    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && node.className && node.className.includes && node.className.includes('pagination')) {
                            console.log('Pagination element added to DOM:', node);
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('DOM observer started');
    }
});

})(jQuery);