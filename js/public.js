/**
 * Public JavaScript for Choir Lyrics Manager - Fixed Version
 *
 * @package    Choir_Lyrics_Manager
 */

(function($) {
    'use strict';

    // Global variables
    var currentPage = 1;
    var isLoading = false;
    var currentFilters = {};
    var searchTimer;
    var currentRequest;

    /**
     * Initialize everything when document is ready
     */
    $(document).ready(function() {
        console.log('CLM Public JS initializing...');
        // Add a flag to prevent multiple initializations
    if (window.clmInitialized) {
        return;
    }
    window.clmInitialized = true;
        // Check if nonces are available
        if (!clm_vars || !clm_vars.nonce) {
            console.error('CLM: Nonces not available');
            return;
        }
        
        console.log('Available nonces:', clm_vars.nonce);
        
        // Initialize all features
        initEnhancedSearch();
        initFilters();
        initAlphabetNav();
        initPagination();
        initPlaylistManagement();
        initPracticeTracking();
        initUserDashboard();
        
        // Initialize from URL parameters
        initializeFromURL();
        
        // Debug info
        console.log('CLM initialization complete');

        console.log('CLM Public JS initializing...');
    
    // Initialize current page from URL or default
    var urlParams = new URLSearchParams(window.location.search);
    currentPage = parseInt(urlParams.get('paged')) || 1;
    
    console.log('Initial current page:', currentPage);
    
    });

    /**
     * Initialize enhanced search functionality
     */
    function initEnhancedSearch() {
        var searchInput = $('#clm-search-input');
        var searchForm = $('#clm-ajax-search-form');
        var suggestionsBox = $('#clm-search-suggestions');
        var loadingIndicator = $('.clm-search-loading');

        // Handle search form submission
        searchForm.on('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            performSearch();
        });

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
                    }
                });
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
     */
    function displaySearchSuggestions(suggestions) {
        var suggestionsBox = $('#clm-search-suggestions');
        
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
 * Initialize filter functionality with proper event handling
 */
function initFilters() {
    // Flag to prevent multiple initializations
    if (window.clmFiltersInitialized) {
        return;
    }
    window.clmFiltersInitialized = true;
    
    // Toggle advanced filters
    var toggleButton = $('.clm-toggle-filters');
    var advancedFilters = $('.clm-advanced-filters');
    
    // Ensure initial state
    advancedFilters.hide();
    toggleButton.removeClass('active');
    
    // Single click handler using a more specific approach
    toggleButton.each(function() {
        var $this = $(this);
        
        // Remove all existing handlers
        $this.off('click');
        $this.unbind('click');
        
        // Add new handler
        $this.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $btn = $(this);
            var $filters = $('.clm-advanced-filters');
            
            // Check if animation is already running
            if ($filters.is(':animated')) {
                return false;
            }
            
            // Simple toggle without callback
            $filters.slideToggle(300);
            $btn.toggleClass('active');
            
            return false;
        });
    });
    
    // Initialize other filters...
    initOtherFilters();
}

function initOtherFilters() {
    // Quick filters
    $('.clm-quick-filter').off('click').on('click', function(e) {
        e.preventDefault();
        
        $('.clm-quick-filter').removeClass('active');
        $(this).addClass('active');

        var filterType = $(this).data('filter');
        var filterValue = $(this).data('value');

        if (filterType === 'all') {
            $('#clm-filter-form')[0].reset();
            currentFilters = {};
        } else {
            $('#clm-' + filterType + '-select').val(filterValue);
        }

        currentPage = 1;
        performSearch();
    });

    // Apply filters button
    $('.clm-apply-filters').off('submit click').on('click', function(e) {
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
        e.preventDefault();
        currentPage = 1;
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
            
            if (letter === 'all') {
                delete currentFilters.starts_with;
            } else {
                currentFilters.starts_with = letter;
            }
            
            currentPage = 1;
            performSearch();
        });
    }
    
    /**
     * Update practice stats display
     */
    function updatePracticeStats(stats) {
        if (stats.total_time !== undefined) {
            $('.clm-practice-stat:nth-child(1) .clm-stat-value').text(formatDuration(stats.total_time));
        }
        
        if (stats.sessions !== undefined) {
            $('.clm-practice-stat:nth-child(2) .clm-stat-value').text(stats.sessions);
        }
        
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
 * Initialize pagination
 */
function initPagination() {
    console.log('Initializing pagination handlers');
    
    // Use event delegation for both pagination containers
    $(document).off('click', '.clm-pagination a, #clm-pagination a, .page-numbers a');
    $(document).on('click', '.clm-pagination a, #clm-pagination a, .page-numbers a', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $link = $(this);
        var $liParent = $link.parent('li');
        
        console.log('=== Pagination click ===');
        console.log('Link HTML:', $link.prop('outerHTML'));
        console.log('Link href:', $link.attr('href'));
        console.log('Current page before click:', currentPage);
        
        // Skip if already loading
        if (isLoading) {
            return false;
        }
        
        // Skip if current page (check both link and parent li)
        if ($link.hasClass('current') || $liParent.hasClass('current') || 
            $link.find('.current').length > 0 || $liParent.find('.current').length > 0) {
            console.log('Already on current page');
            return false;
        }
        
        // Extract page number
        var page = extractPageNumber($link);
        console.log('Extracted page:', page);
        
        if (page && page > 0) {
            performSearch(page);
        }
        
        return false;
    });
}
    
    // Page jump handler
    $(document).on('click', '#clm-page-jump-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var page = parseInt($('#clm-page-jump-input').val());
        var maxPage = parseInt($('#clm-page-jump-input').attr('max'));
        
        console.log('Jump to page:', page, 'Max:', maxPage);
        
        if (page && page >= 1 && page <= maxPage && page !== currentPage) {
            performSearch(page);
        }
        
        return false;
    });
    
    // Items per page handler - ensure it doesn't submit form
    $('#clm-items-per-page').on('change', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Items per page changed to:', $(this).val());
        
        currentPage = 1;
        performSearch();
        
        return false;
    });



extractPageNumber
 /**
 * Perform search/filter with AJAX - Fixed
 */
function performSearch(page) {
    if (isLoading) {
        console.log('Already loading, aborting');
        return;
    }
    
    isLoading = true;
    page = page || 1;
    
    console.log('=== Performing search ===');
    console.log('Requested page:', page);
    console.log('Current page before request:', currentPage);
    
    var searchQuery = $('#clm-search-input').val() || '';
    var filters = collectFilters();
    
    showLoadingOverlay();
    
    // Cancel existing request
    if (currentRequest && currentRequest.readyState !== 4) {
        currentRequest.abort();
    }
    
    // Prepare data
    var requestData = {
        action: 'clm_ajax_filter',
        nonce: clm_vars.nonce.filter,
        search: searchQuery,
        filters: filters,
        page: page  // Make sure page is included
    };
    
    console.log('Request data:', requestData);
    
    currentRequest = $.ajax({
        url: clm_vars.ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: requestData,
        success: function(response) {
            console.log('Search response:', response);
            
            if (response.success) {
                // Update current page from the server response
                currentPage = parseInt(response.data.page) || page;
                console.log('Updated current page to:', currentPage);
                
                updatePageContent(response.data);
                updateURL(searchQuery, filters, currentPage);
                scrollToResults();
            } else {
                console.error('Search error:', response);
                if (response.data && response.data.message) {
                    alert('Error: ' + response.data.message);
                }
            }
        },
        error: function(xhr, status, error) {
            if (status !== 'abort') {
                console.error('AJAX error:', status, error);
                alert('Error loading results. Please try again.');
            }
        },
        complete: function() {
            isLoading = false;
            hideLoadingOverlay();
            console.log('Request complete. Current page:', currentPage);
        }
    });
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
        per_page: $('#clm-items-per-page').val() || 20 // Default to 20
    };

    // Merge with current filters
    $.extend(filters, currentFilters);

    // Remove empty values
    Object.keys(filters).forEach(function(key) {
        if (!filters[key] || filters[key] === '') {
            delete filters[key];
        }
    });

    console.log('Collected filters:', filters);

    return filters;
}

    /**
 * Update page content after AJAX response
 */
function updatePageContent(data) {
    console.log('Updating page content. Current page before update:', currentPage);
    
    // Update items list
    if (data.html !== undefined) {
        $('#clm-items-list, .clm-items-list').html(data.html);
    }
    
    // Update pagination
    if (data.pagination !== undefined) {
        $('#clm-pagination, .clm-pagination').html(data.pagination);
    }
    
    // Update results count
    if (data.total !== undefined) {
        $('.clm-results-count').text(data.total);
    }
    
    // Update current page from response
    if (data.page !== undefined) {
        currentPage = parseInt(data.page);
        console.log('Updated current page to:', currentPage);
        $('#clm-page-jump-input').val(currentPage);
    }
    
    // Reinitialize features that need it
    initPlaylistManagement();
}

    /**
     * Update URL with current state
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

        var newURL = window.location.pathname;
        var queryString = params.toString();
        
        if (queryString) {
            newURL += '?' + queryString;
        }
        
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
        var urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('paged')) {
            currentPage = parseInt(urlParams.get('paged'));
            $('#clm-page-jump-input').val(currentPage);
        }
        
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
     * Scroll to results section
     */
    function scrollToResults() {
        var target = $('#clm-results-container, .clm-container, .clm-archive').first();
        if (target.length) {
            var scrollTop = target.offset().top - 100;
            $('html, body').animate({
                scrollTop: Math.max(0, scrollTop)
            }, 300);
        }
    }

    /**
     * Practice tracking functionality
     */
    function initPracticeTracking() {
        $('#clm-log-practice').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var lyricId = $('.clm-practice-tracker').data('lyric-id');
            var duration = $('#clm-practice-duration').val();
            var confidence = $('#clm-practice-confidence').val();
            var notes = $('#clm-practice-notes').val();
            var messageContainer = $('#clm-practice-message');
            
            if (!duration || duration < 1) {
                messageContainer.html('<div class="clm-error">Please enter a valid duration.</div>');
                return;
            }
            
            button.prop('disabled', true).text('Logging...');
            messageContainer.empty();
            
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
                        messageContainer.html('<div class="clm-success">' + clm_vars.text.practice_success + '</div>');
                        $('#clm-practice-notes').val('');
                        
                        if (response.data && response.data.stats) {
                            updatePracticeStats(response.data.stats);
                        }
                        
                        if ($('.clm-practice-history').length) {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        messageContainer.html('<div class="clm-error">' + (response.data ? response.data.message : 'Error logging practice.') + '</div>');
                    }
                },
                error: function() {
                    messageContainer.html('<div class="clm-error">Error communicating with the server.</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Log Practice');
                }
            });
        });
    }

    /**
     * Playlist management functionality
     */
    function initPlaylistManagement() {
        // Remove old handlers first
        $(document).off('click', '.clm-create-playlist-button');
        $(document).off('click', '.clm-cancel-playlist');
        $(document).off('click', '.clm-submit-playlist');
        
        // Create playlist button - specific to each card
        $(document).on('click', '.clm-create-playlist-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $button = $(this);
            var $itemActions = $button.closest('.clm-item-actions');
            
            $button.hide();
            $itemActions.find('.clm-create-playlist-form').show();
        });
        
        // Cancel playlist creation
        $(document).on('click', '.clm-cancel-playlist', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $itemActions = $button.closest('.clm-item-actions');
            
            $itemActions.find('.clm-create-playlist-form').hide();
            $itemActions.find('.clm-create-playlist-button').show();
        });
        
        // Submit playlist creation
        $(document).on('click', '.clm-submit-playlist', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('.clm-create-playlist-form');
            var $nameField = $form.find('.clm-playlist-name');
            var name = $nameField.val();
            var description = $form.find('.clm-playlist-description').val();
            var lyricId = $button.data('lyric-id') || 0;
            
            // Find the correct radio input for this form
            var visibilityRadio = $form.find('input[type="radio"]:checked');
            var visibility = visibilityRadio.val() || 'private';
            
            if (!name) {
                alert('Please enter a playlist name.');
                $nameField.focus();
                return;
            }
            
            $button.prop('disabled', true).text('Creating...');
            
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
                        $button.prop('disabled', false).text('Create');
                    }
                },
                error: function() {
                    alert(clm_vars.text.playlist_error);
                    $button.prop('disabled', false).text('Create');
                }
            });
        });
    }

    /**
     * User dashboard functionality
     */
    function initUserDashboard() {
        $('.clm-dashboard-nav-item').on('click', function(e) {
            e.preventDefault();
            
            var tabId = $(this).data('tab');
            
            $('.clm-dashboard-nav-item').removeClass('active');
            $(this).addClass('active');
            
            $('.clm-dashboard-tab').hide();
            $('#' + tabId).show();
            
            window.location.hash = tabId;
        });
        
        var hash = window.location.hash.substring(1);
        if (hash && $('#' + hash).length) {
            $('.clm-dashboard-nav-item[data-tab="' + hash + '"]').trigger('click');
        } else {
            $('.clm-dashboard-nav-item:first').trigger('click');
        }
    }

    /**
     * Initialize shortcode features (exposed globally)
     */
    window.initShortcodeFeatures = function(containerId) {
        var container = $('#' + containerId);
        if (!container.length) return;
        
        console.log('Initializing shortcode features for:', containerId);
        
        initShortcodeSearch(container);
        initShortcodeFilters(container);
        initShortcodeAlphabet(container);
        initShortcodePagination(container);
    };

    /**
     * Initialize shortcode search
     */
    function initShortcodeSearch(container) {
        var searchForm = container.find('.clm-shortcode-search-form');
        
        searchForm.on('submit', function(e) {
            e.preventDefault();
            performShortcodeSearch(container);
        });
    }

    /**
     * Initialize shortcode filters
     */
    function initShortcodeFilters(container) {
        container.find('.clm-toggle-filters').on('click', function(e) {
            e.preventDefault();
            container.find('.clm-advanced-filters').slideToggle(300);
            $(this).toggleClass('active');
        });
        
        container.find('.clm-shortcode-filter-form').on('submit', function(e) {
            e.preventDefault();
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
     * Initialize shortcode pagination
     */
    function initShortcodePagination(container) {
        if (container.data('ajax') !== 'yes') {
            return;
        }
        
        container.on('click', '.clm-pagination a', function(e) {
            e.preventDefault();
            
            var url = $(this).attr('href');
            var page = getPageFromURL(url);
            
            performShortcodeSearch(container, page);
        });
    }

    /**
     * Perform shortcode search
     */
    function performShortcodeSearch(container, page) {
        if (container.data('ajax') !== 'yes') {
            var form = container.find('.clm-shortcode-search-form, .clm-shortcode-filter-form').first();
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
        
        var params = {
            action: 'clm_shortcode_filter',
            nonce: clm_vars.nonce.filter,
            container_id: container.attr('id'),
            page: page || 1
        };
        
        var searchQuery = container.find('.clm-search-input').val();
        if (searchQuery) {
            params.search = searchQuery;
        }
        
        container.find('.clm-loading-overlay').show();
        
        $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            data: params,
            success: function(response) {
                container.find('.clm-loading-overlay').hide();
                
                if (response.success) {
                    container.find('.clm-shortcode-results').html(response.data.html);
                    container.find('.clm-results-count').text(response.data.total);
                    
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

    // Browser history support
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.page) {
            currentPage = event.state.page;
            performSearch(currentPage);
        }
    });

})(jQuery);