/**
 * Public JavaScript for Choir Lyrics Manager - Optimized Version
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
    var pendingRequests = [];
    var archiveUrl = '';  // Store archive URL

    // Ensure dependencies are available
    if (typeof jQuery === 'undefined') {
        console.error('CLM: jQuery is required but not loaded');
        return;
    }

    if (typeof clm_vars === 'undefined') {
        console.error('CLM: Required variables not loaded');
        return;
    }

    /**
     * Initialize everything when document is ready
     */
    $(document).ready(function() {
        console.log('CLM Public JS initializing...');
        
        // Prevent multiple initializations
        if (window.clmInitialized) {
            return;
        }
        window.clmInitialized = true;

        // Get the archive URL if we're on an archive page
        if ($('.clm-archive').length) {
            // Store current page URL without query parameters
            archiveUrl = window.location.href.split('?')[0];
            console.log('Archive URL detected:', archiveUrl);
        }

        // Check if nonces are available - FIXED NONCE HANDLING
        if (!clm_vars || !clm_vars.nonce) {
            console.error('CLM: Nonces not available in clm_vars');
            
            // Let's check what's actually in clm_vars for debugging
            if (clm_vars) {
                console.log('CLM: Available clm_vars:', clm_vars);
            }
            
            // Temporary fallback for missing nonces
            if (!clm_vars.nonce) {
                clm_vars.nonce = {};
            }
            
            // Continue with initialization - we'll handle missing nonces in the request functions
        } else {
            console.log('CLM: Nonces loaded successfully');
        }
        
        // Initialize current page from URL or default
        var urlParams = new URLSearchParams(window.location.search);
        currentPage = parseInt(urlParams.get('paged')) || 1;
        console.log('Initial current page:', currentPage);

        // Initialize features
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
        console.log('CLM initialization complete');
    });

    /**
     * Abort all pending AJAX requests
     */
    function abortPendingRequests() {
        pendingRequests.forEach(function(req) {
            if (req.readyState !== 4) req.abort();
        });
        pendingRequests = [];
    }

    /**
     * Centralized AJAX request handler - Fixed for compatibility and improved nonce handling
     */
    function clmAjaxRequest(action, data, successCallback, errorCallback) {
        if (currentRequest && currentRequest.readyState !== 4) {
            currentRequest.abort();
        }

        // Create the full data object without spread operator
        var ajaxData = {
            action: 'clm_' + action
        };
        
        // FIXED: Better nonce handling - check if nonce exists for this action
        if (clm_vars.nonce && clm_vars.nonce[action]) {
            ajaxData.nonce = clm_vars.nonce[action];
        } else {
            // Log missing nonce but continue
            console.warn('CLM: Missing nonce for action: ' + action);
            
            // If we have any nonce, try to use it
            if (clm_vars.nonce) {
                // Try to find a similar nonce name
                for (var key in clm_vars.nonce) {
                    if (key.includes(action) || action.includes(key)) {
                        console.log('CLM: Using similar nonce "' + key + '" for action "' + action + '"');
                        ajaxData.nonce = clm_vars.nonce[key];
                        break;
                    }
                }
                
                // Last resort: use first available nonce
                if (!ajaxData.nonce && Object.keys(clm_vars.nonce).length > 0) {
                    var firstKey = Object.keys(clm_vars.nonce)[0];
                    console.log('CLM: Using fallback nonce "' + firstKey + '" for action "' + action + '"');
                    ajaxData.nonce = clm_vars.nonce[firstKey];
                }
            }
        }
        
        // Merge data into ajaxData
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                ajaxData[key] = data[key];
            }
        }

        console.log('CLM: Sending AJAX request for action: ' + action, ajaxData);

        var request = $.ajax({
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
                abortPendingRequests();

                currentRequest = clmAjaxRequest('ajax_search', { query: query }, 
                    function(response) {
                        loadingIndicator.hide();
                        if (response.success && response.data.suggestions) {
                            displaySearchSuggestions(response.data.suggestions);
                        }
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
        if (window.clmFiltersInitialized) return;
        window.clmFiltersInitialized = true;
        
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
            e.preventDefault();
            currentPage = 1;
            performSearch();
        });
    }

 /**
 * Initialize alphabet navigation for the Choir Lyrics Manager
 */
function initAlphabetNav() {
    // First remove any existing event handlers to prevent duplicates
    $('.clm-alpha-link').off('click');
    
    // Add new click handler
    $('.clm-alpha-link').on('click', function(e) {
        e.preventDefault();
        
        // Update UI - highlight the active letter
        $('.clm-alpha-link').removeClass('active');
        $(this).addClass('active');

        // Get the selected letter
        var letter = $(this).data('letter');
        
        console.log('Alphabet filter: Selected letter:', letter);
        
        // Update the current filters
        if (letter === 'all') {
            delete currentFilters.starts_with;
        } else {
            currentFilters.starts_with = letter;
        }
        
        // Log for debugging
        console.log('Current filters after alpha selection:', currentFilters);
        
        // Reset to first page and perform search
        currentPage = 1;
        
        // Show loading state
        showLoadingOverlay();
        
        // Perform the AJAX request
        performSearch();
    });
}
 /**
     * Initialize pagination - FIXED VERSION FOR ARCHIVE URL
     */
 function initPagination() {
    // Fixed event delegation for pagination links - exclude shortcode containers
        $(document).off('click', '.clm-pagination a, #clm-pagination a, .page-numbers a')
        .on('click', '.clm-pagination:not([data-container="shortcode"]) a, #clm-pagination a, .page-numbers a', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (isLoading) return false;
            
            var $link = $(this);
            if ($link.hasClass('current')) { return false; }
            
            var page = extractPageNumber($link);
            if (!page || page < 1) return false;
            
            // FIXED: Check if we should use AJAX or regular page navigation
            if (archiveUrl && $('.clm-ajax-filter-disabled').length) {
                // Use standard navigation with the correct archive URL
                window.location.href = addPageParam(archiveUrl, page);
            } else {
                // Use AJAX
                performSearch(page);
            }
            
            return false;
        });

    // Page jump handler
    $(document).on('click', '#clm-page-jump-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var page = parseInt($('#clm-page-jump-input').val());
        var maxPage = parseInt($('#clm-page-jump-input').attr('max'));
        
        if (!page || page < 1 || page > maxPage || page === currentPage) return;
            
        // FIXED: Check if we should use AJAX or regular page navigation
        if (archiveUrl && $('.clm-ajax-filter-disabled').length) {
            // Use standard navigation with the correct archive URL
            window.location.href = addPageParam(archiveUrl, page);
        } else {
            // Use AJAX
            performSearch(page);
        }
    });
}

 /**
     * Add page parameter to URL correctly
     * This function handles both pretty permalinks and query string formats
     */
 function addPageParam(baseUrl, pageNum) {
    // First check if baseUrl has /page/N/ format (pretty permalinks)
    var prettyPermalink = baseUrl.match(/(.+?)\/page\/\d+\/?$/);
    if (prettyPermalink) {
        return prettyPermalink[1] + '/page/' + pageNum + '/';
    }
    
    // Check if the URL already has a query string
    if (baseUrl.indexOf('?') >= 0) {
        // URL already has query parameters
        var urlParts = baseUrl.split('?');
        var urlParams = new URLSearchParams(urlParts[1]);
        
        // Replace or add paged parameter
        urlParams.set('paged', pageNum);
        
        return urlParts[0] + '?' + urlParams.toString();
    } else {
        // No query parameters yet
        return baseUrl + '?paged=' + pageNum;
    }
}

 /**
 * Perform search with all current filters
 */
function performSearch(page) {
    // If already loading, exit
    if (isLoading) return;
    
    // Set loading state
    isLoading = true;
    page = page || 1;
    
    // Get search query from input
    var searchQuery = $('#clm-search-input').val() || '';
    
    // Collect all filters
    var filters = collectFilters();
    
    // Log request for debugging
    console.log('Performing search with:', {
        search: searchQuery,
        filters: filters,
        page: page
    });
    
    // Show loading overlay
    showLoadingOverlay();
    
    // Abort any pending requests
    abortPendingRequests();

    // Make the AJAX request
    currentRequest = $.ajax({
        url: clm_vars.ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'clm_ajax_filter',
            nonce: clm_vars.nonce.filter,
            search: searchQuery,
            filters: filters,
            page: page
        },
        success: function(response) {
            console.log('Search response:', response);
            
            if (response.success) {
                // Update current page
                currentPage = parseInt(response.data.page) || page;
                
                // Update page content
                updatePageContent(response.data);
                
                // Update URL with current state
                updateURL(searchQuery, filters, currentPage);
                
                // Scroll to results
                scrollToResults();
            } else {
                console.error('Search error:', response);
                showNotification('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('CLM: AJAX error for action: ' + action, status, error);
            
            // Only show errors for non-abort situations
            if (status !== 'abort') {
                if (errorCallback) {
                    errorCallback(xhr, status, error);
                } else {
                    showNotification('Error: ' + (error || 'Request failed'), 'error');
                }
            }
        }
    });
    
    // Add to pending requests
    pendingRequests.push(currentRequest);
    
    return currentRequest;
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

    // Debug log
    console.log('Collected filters for request:', filters);

    return filters;
}

    /**
     * Update page content after AJAX response
     */
    function updatePageContent(data) {
        if (data.html !== undefined) {
            $('#clm-items-list, .clm-items-list').html(data.html);
        }
        
        if (data.pagination !== undefined) {
            $('#clm-pagination, .clm-pagination').html(data.pagination);
        }
        
        if (data.total !== undefined) {
            $('.clm-results-count').text(data.total);
        }
        
        if (data.page !== undefined) {
            currentPage = parseInt(data.page);
            $('#clm-page-jump-input').val(currentPage);
        }
        
        initPlaylistManagement();
    }

 /**
     * Update URL with current state - FIXED FOR ARCHIVE URL
     */
 function updateURL(search, filters, page) {
    var params = new URLSearchParams();
    
    if (search) params.set('s', search);
    
    Object.keys(filters).forEach(function(key) {
        if (filters[key]) params.set(key, filters[key]);
    });
    
    if (page && page > 1) params.set('paged', page);

    // Use archiveUrl as base if available, otherwise current path
    var baseUrl = archiveUrl || window.location.pathname;
    var newURL = baseUrl;
    var queryString = params.toString();
    
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
            
            clmAjaxRequest('update_practice_log', {
                lyric_id: lyricId,
                duration: duration,
                confidence: confidence,
                notes: notes
            }, function(response) {
                if (response.success) {
                    messageContainer.html('<div class="clm-success">' + clm_vars.text.practice_success + '</div>');
                    $('#clm-practice-notes').val('');
                    
                    // Fixed: replaced optional chaining
                    if (response.data && response.data.stats) updatePracticeStats(response.data.stats);
                    if (response.data && response.data.skill) updateSkillDisplay(response.data.skill);
                    
                    if ($('.clm-practice-history').length) {
                        setTimeout(location.reload, 1500);
                    }
                } else {
                    // Fixed: replaced optional chaining
                    messageContainer.html('<div class="clm-error">' + ((response.data && response.data.message) || 'Error logging practice.') + '</div>');
                }
            }).always(function() {
                button.prop('disabled', false).text('Log Practice');
            });
        });
    }

    /**
     * Update skill display
     */
    function updateSkillDisplay(skill) {
        if (!skill) return;
        
        var skillLevels = {
            'novice': { label: 'Novice', color: '#dc3545', icon: 'dashicons-warning', value: 1 },
            'learning': { label: 'Learning', color: '#ffc107', icon: 'dashicons-lightbulb', value: 2 },
            'proficient': { label: 'Proficient', color: '#17a2b8', icon: 'dashicons-yes', value: 3 },
            'master': { label: 'Master', color: '#28a745', icon: 'dashicons-star-filled', value: 4 }
        };
        
        var levelInfo = skillLevels[skill.skill_level];
        if (!levelInfo) return;
        
        $('.clm-skill-badge').css('background-color', levelInfo.color)
            .html('<span class="dashicons ' + levelInfo.icon + '"></span> ' + levelInfo.label);
        
        $('.clm-skill-stats p:first').text('Practice Sessions: ' + skill.practice_count);
        
        var progressWidth = (levelInfo.value / 4) * 100;
        $('.clm-progress-fill').css({
            'width': progressWidth + '%',
            'background-color': levelInfo.color
        });
    }

    /**
     * Playlist management functionality
     */
    function initPlaylistManagement() {
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
                
                var $button = $(this);
                var $form = $button.closest('.clm-create-playlist-form');
                var name = $form.find('.clm-playlist-name').val();
                
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
     * Initialize user dashboard
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
     * Initialize shortcode features
     */
    window.initShortcodeFeatures = function(containerId) {
        var container = $('#' + containerId);
        if (!container.length) return;
        
        initShortcodeSearch(container);
        initShortcodeFilters(container);
        initShortcodeAlphabet(container);
        initShortcodePagination(container);
        initItemsPerPageSelector(container); // Add this line
    };

    function initShortcodeSearch(container) {
        container.find('.clm-shortcode-search-form').on('submit', function(e) {
            e.preventDefault();
            performShortcodeSearch(container);
        });
    }

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
                    $(this).toggle(title && title.charAt(0).toUpperCase() === letter);
                });
            }
            
            updateShortcodeResultsCount(container);
        });
    }
// Initialize items per page selector
function initItemsPerPageSelector(container) {
    // Find the items per page selector in the container
    var selector = container.find('.clm-items-per-page');
    if (!selector.length) return;
    
    // Remove any existing event handlers
    selector.off('change');
    
    // Add new event handler
    selector.on('change', function() {
        var perPage = parseInt($(this).val());
        if (perPage) {
            console.log('Items per page changed to:', perPage);
            
            // FIXED: Update the container data attribute
            container.attr('data-per-page', perPage);
            
            // Reset to page 1 and perform search
            performShortcodeSearch(container, 1);
        }
    });
    
    // Set initial value from container if available
    var initialPerPage = container.attr('data-per-page');
    if (initialPerPage) {
        selector.val(initialPerPage);
    }
}
    function initShortcodePagination(container) {
        if (container.data('ajax') !== 'yes') return;
    
    // Use event delegation for pagination
    container.off('click', '.clm-pagination a, .clm-page-jump-button')
        .on('click', '.clm-pagination a', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            var $link = $(this);
            var page;
            
            // First check for data-page attribute
            if ($link.attr('data-page')) {
                page = parseInt($link.attr('data-page'));
                console.log('Found page in data-page attribute:', page);
            } else {
                // Fallback to URL parsing
                page = getPageFromURL($link.attr('href'));
            }
            
            if (!page || page < 1) {
                console.log('Invalid page number:', page);
                return false;
            }
            
            console.log('Clicked shortcode pagination link for page:', page);
            performShortcodeSearch(container, page);
            return false;
        })
        .on('click', '.clm-page-jump-button', function(e) {
            e.preventDefault();
            
            var page = parseInt($(this).siblings('.clm-page-jump-input').val());
            var maxPage = parseInt($(this).siblings('.clm-page-jump-input').attr('max'));
            
            if (page && page > 0 && page <= maxPage) {
                console.log('Jumping to page:', page);
                performShortcodeSearch(container, page);
            }
        });
        
    // Debug pagination links for this container
    console.log('Pagination links in container:', container.attr('id'));
    container.find('.clm-pagination a').each(function(i) {
        var $link = $(this);
        console.log('Link #' + i + ':', {
            'text': $link.text().trim(),
            'href': $link.attr('href'),
            'data-page': $link.attr('data-page'),
            'class': $link.attr('class')
        });
    });
    }
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
        
        container.find('.clm-loading-overlay').show();
        
        // Get the nonce from the container or from clm_vars
        var nonce = container.data('nonce') || clm_vars.nonce.filter;
        
        // Get current items per page from data attribute or default to 20
        var itemsPerPage = parseInt(container.attr('data-per-page')) || 20;
        var requestData = {
            container_id: container.attr('id'),
            page: page || 1,
            search: container.find('.clm-search-input').val(),
            per_page: itemsPerPage  // Add this line
        };
        
        console.log('Performing shortcode search with:', requestData);
        
        clmAjaxRequest('shortcode_filter', requestData, function(response) {
            if (response.success) {
                // Update the results content
                container.find('.clm-shortcode-results').html(response.data.html);
                 // Update per_page if provided
                if (response.data.per_page) {
                    container.attr('data-per-page', response.data.per_page);
                }
                // Update the count
                if (container.find('.clm-results-count').length) {
                    container.find('.clm-results-count').text(response.data.total);
                }
                
                // Update nonce if provided
                if (response.data.new_nonce) {
                    container.attr('data-nonce', response.data.new_nonce);
                    if (clm_vars.nonce) {
                        clm_vars.nonce.filter = response.data.new_nonce;
                        clm_vars.nonce.shortcode_filter = response.data.new_nonce;
                    }
                    console.log('Updated nonce for filter operations:', response.data.new_nonce);
                }
                
                // Update pagination - THIS IS THE KEY PART
                if (response.data.pagination) {
                    // Check if pagination container exists
                    var paginationContainer = container.find('.clm-shortcode-pagination');
                    
                    // If not, create it after the results
                    if (paginationContainer.length === 0) {
                        container.find('.clm-shortcode-results').after('<div class="clm-shortcode-pagination"></div>');
                        paginationContainer = container.find('.clm-shortcode-pagination');
                    }
                    
                    // Update with new pagination HTML
                    paginationContainer.html(response.data.pagination);
                    
                    // Make sure it's visible
                    paginationContainer.show();
                    
                    // Re-initialize pagination events
                    initShortcodePagination(container);
                }
                
                // Re-initialize other components if needed
                if ($('body').hasClass('logged-in')) {
                    initPlaylistManagement();
                }
                // Re-initialize per page selector
                initItemsPerPageSelector(container);
                
                // Update any counts
                updateShortcodeResultsCount(container);
                
                // Scroll to results (optional)
                if (page > 1) {
                    $('html, body').animate({
                        scrollTop: Math.max(0, container.offset().top - 100)
                    }, 300);
                }
            } else {
                console.error('Shortcode filter failed:', response.data);
                if (response.data && response.data.message) {
                    showNotification(response.data.message, 'error');
                }
            }
            // Call this after updating content in performShortcodeSearch
            debugPaginationElements();
            container.find('.clm-loading-overlay').hide();
        });
    }

    function updateShortcodeResultsCount(container) {
        container.find('.clm-results-count').text(
            container.find('.clm-lyric-item:visible').length
        );
    }

    /**
     * Skill goal management
     */
    $(document).on('click', '.clm-set-goal', function(e) {
        e.preventDefault();
        
        var skillId = $(this).data('skill-id');
        var today = new Date().toISOString().split('T')[0];
        
        var modalHtml = '<div class="clm-modal-overlay">' +
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
            var goalDate = $('#clm-goal-date').val();
            
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

    function updateSkillGoalDisplay(skillId, goalDate) {
        var skillItem = $('.clm-set-goal[data-skill-id="' + skillId + '"]').closest('.clm-skill-item');
        var goalHtml = '<div class="clm-skill-goal">' +
            escapeHtml(clm_vars.text.goal || 'Goal') + ': ' + escapeHtml(formatDate(goalDate)) +
            '</div>';
        
        skillItem.find('.clm-skill-goal').remove().end()
            .find('.clm-skill-actions').before(goalHtml);
    }

    /**
     * Utility functions
     */
    function showLoadingOverlay() {
        $('#clm-loading-overlay').fadeIn(200);
    }

    function hideLoadingOverlay() {
        $('#clm-loading-overlay').fadeOut(200);
    }

    function scrollToResults() {
        var target = $('#clm-results-container, .clm-container, .clm-archive').first();
        if (target.length) {
            $('html, body').animate({
                scrollTop: Math.max(0, target.offset().top - 100)
            }, 300);
        }
    }

    function updatePracticeStats(stats) {
        if (stats && stats.total_time !== undefined) {
            $('.clm-practice-stat:nth-child(1) .clm-stat-value').text(formatDuration(stats.total_time));
        }
        
        if (stats && stats.sessions !== undefined) {
            $('.clm-practice-stat:nth-child(2) .clm-stat-value').text(stats.sessions);
        }
        
        if (stats && stats.confidence !== undefined) {
            var starsHtml = '';
            for (var i = 1; i <= 5; i++) {
                starsHtml += i <= stats.confidence ? 
                    '<span class="dashicons dashicons-star-filled"></span>' : 
                    '<span class="dashicons dashicons-star-empty"></span>';
            }
            $('.clm-practice-stat:nth-child(4) .clm-stat-value').html(starsHtml);
        }
    }

    function formatDuration(minutes) {
        minutes = parseInt(minutes, 10);
        if (minutes < 60) return minutes + (minutes === 1 ? ' minute' : ' minutes');
        
        var hours = Math.floor(minutes / 60);
        var mins = minutes % 60;
        
        if (mins === 0) return hours + (hours === 1 ? ' hour' : ' hours');
        return hours + (hours === 1 ? ' hour' : ' hours') + ', ' + mins + (mins === 1 ? ' minute' : ' minutes');
    }

     /**
     * Extract page number from link
     */
     function extractPageNumber($link) {
        // Try to get page from data attribute
        var page = $link.data('page');
        if (page) return parseInt(page);
        
        // Try to get from link text if it's a number
        var text = $link.text().trim();
        if (/^\d+$/.test(text)) return parseInt(text);
        
        // Try to extract from href
        var href = $link.attr('href');
        if (href) {
            // Check for query string format: ?paged=N
            var queryMatch = href.match(/[\?&]paged=(\d+)/);
            if (queryMatch) return parseInt(queryMatch[1]);
            
            // Check for pretty permalink format: /page/N/
            var permalinkMatch = href.match(/\/page\/(\d+)\/?/);
            if (permalinkMatch) return parseInt(permalinkMatch[1]);
        }
        
        // Check for prev/next buttons
        if ($link.hasClass('prev') || text.includes('Previous')) return Math.max(1, currentPage - 1);
        if ($link.hasClass('next') || text.includes('Next')) return currentPage + 1;
        
        return null;
    }

    function getPageFromURL(url) {
        console.log('Getting page from URL:', url);
        
        // Don't try to use URL as jQuery selector
        // This was causing: Uncaught Error: Syntax error, unrecognized expression
        
        // If the url parameter is actually a DOM element or jQuery object
        if (url && url.jquery || (typeof url === 'object' && url.nodeType)) {
            var $element = $(url);
            if ($element.data('page')) {
                console.log('Found page in element data attribute:', $element.data('page'));
                return parseInt($element.data('page'));
            }
        }
        
        // Check for data-page attribute on DOM element
        if (typeof url === 'object' && url.dataset && url.dataset.page) {
            console.log('Found page in element dataset:', url.dataset.page);
            return parseInt(url.dataset.page);
        }
        
        // If url is a string (which it should be normally)
        if (typeof url === 'string') {
            // For query string format
            var match = url.match(/paged=(\d+)/);
            if (match) {
                console.log('Found page in query string:', match[1]);
                return parseInt(match[1]);
            }
            
            // For pretty permalink format
            match = url.match(/page\/(\d+)/);
            if (match) {
                console.log('Found page in pretty permalink:', match[1]);
                return parseInt(match[1]);
            }
        }
        
        console.log('No page found, defaulting to 1');
        return 1;
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString(undefined, { 
            year: 'numeric', month: 'long', day: 'numeric' 
        });
    }

    function isValidDate(dateString) {
        var regEx = /^\d{4}-\d{2}-\d{2}$/;
        if (!dateString.match(regEx)) return false;
        var d = new Date(dateString);
        return d instanceof Date && !isNaN(d) && d.toISOString().slice(0,10) === dateString;
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;', '<': '&lt;', '>': '&gt;',
            '"': '&quot;', "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="clm-notification clm-notification-' + type + '">' + 
            escapeHtml(message) + '</div>').appendTo('body');
        
        $notification.fadeIn(300);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    function animateSkillProgress() {
        $('.clm-progress-fill').each(function() {
            var $this = $(this);
            var width = $this.css('width');
            $this.css('width', '0');
            setTimeout(function() { $this.css('width', width); }, 100);
        });
    }

    function initAchievementTooltips() {
        $('.clm-badge').each(function() {
            var title = $(this).attr('title');
            if (!title) return;
            
            $(this).on({
                mouseenter: function(e) {
                    $('<div class="clm-tooltip">' + escapeHtml(title) + '</div>')
                        .appendTo('body')
                        .css({
                            top: e.pageY - 30,
                            left: e.pageX - ($('.clm-tooltip').width() / 2)
                        })
                        .fadeIn(200);
                },
                mousemove: function(e) {
                    $('.clm-tooltip').css({
                        top: e.pageY - 30,
                        left: e.pageX - ($('.clm-tooltip').width() / 2)
                    });
                },
                mouseleave: function() {
                    $('.clm-tooltip').remove();
                }
            });
        });
    }
// Add this function to your public.js
function debugPaginationElements() {
    console.log('Pagination containers in document:', $('.clm-pagination').length);
    console.log('Shortcode pagination containers:', $('.clm-shortcode-pagination').length);
    
    $('.clm-shortcode-container').each(function(index) {
        var container = $(this);
        console.log('Shortcode container #' + index + ' id:', container.attr('id'));
        console.log('-- Items per page:', container.data('per-page'));
        console.log('Shortcode container #' + index + ' id:', container.attr('id'));
        console.log('-- Pagination inside:', container.find('.clm-pagination').length);
        console.log('-- Pagination visible:', container.find('.clm-pagination').is(':visible'));
        console.log('-- Shortcode pagination inside:', container.find('.clm-shortcode-pagination').length);
        console.log('-- Shortcode pagination visible:', container.find('.clm-shortcode-pagination').is(':visible'));
    });

    console.log('Checking pagination links:');
    $('.clm-pagination a').each(function(i) {
        var $link = $(this);
        console.log('Link #' + i + ':', {
            'text': $link.text().trim(),
            'href': $link.attr('href'),
            'data-page': $link.data('page'),
            'class': $link.attr('class')
        });
    });
}
    // Browser history support
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.page) {
            currentPage = event.state.page;
            performSearch(currentPage);
        }
    });

})(jQuery);