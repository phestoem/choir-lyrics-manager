/**
 * Choir Lyrics Manager - Public JavaScript
 * Optimized and professionally organized
 * 
 * @package    Choir_Lyrics_Manager
 * @version    1.2.0
 * @author     CLM Development Team
 */

(function($) {
    'use strict';

    // ========================================================================
    // GLOBAL VARIABLES & CONFIGURATION
    // ========================================================================

    let currentPage = 1;
    let isLoading = false;
    let currentFilters = {};
    let searchTimer;
    let currentRequest;
    let pendingRequests = [];
    let archiveUrl = (clm_vars && clm_vars.archive_url) ? clm_vars.archive_url : '';

    // Configuration constants
    const CONFIG = {
        SEARCH_DEBOUNCE_DELAY: 500,
        NOTIFICATION_DURATION: 3500,
        NOTIFICATION_FADE_DURATION: 300,
        MIN_SEARCH_LENGTH: 2,
        DEFAULT_PER_PAGE: 20
    };

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    /**
     * Safely escape HTML to prevent XSS attacks
     * @param {string} text - Text to escape
     * @returns {string} - Escaped text
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') text = String(text);
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Show notification message to user
     * @param {string} message - Message to display
     * @param {string} type - Notification type (info, success, error, warning)
     */
    function showNotification(message, type) {
        console.log('DEBUG: showNotification called:', message, type);

        type = type || 'info';
        const safeMessage = (typeof message === 'string') ? 
            escapeHtml(message) : 
            ((clm_vars && clm_vars.text && clm_vars.text.default_notification) || 'Notification');

        const $notification = $('<div class="clm-notification clm-notification-' + escapeHtml(type) + '">' +
            safeMessage + '</div>');

        console.log('DEBUG: Notification element created:', $notification[0]);

        $notification.appendTo('body').fadeIn(CONFIG.NOTIFICATION_FADE_DURATION);

        console.log('DEBUG: Notification appended and fadeIn initiated.');

        setTimeout(function() {
            $notification.fadeOut(CONFIG.NOTIFICATION_FADE_DURATION, function() {
                $(this).remove();
            });
        }, CONFIG.NOTIFICATION_DURATION);
    }

    /**
     * Format duration in minutes to human-readable string
     * @param {number} minutes - Duration in minutes
     * @returns {string} - Formatted duration string
     */
    function formatDuration(minutes) {
        minutes = parseInt(minutes, 10);
        if (isNaN(minutes) || minutes < 0) minutes = 0;

        const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};
        const S_0_MINUTES = texts.duration_0_minutes || '0 minutes';
        const S_MINUTE = texts.duration_minute || ' minute';
        const S_MINUTES = texts.duration_minutes || ' minutes';
        const S_HOUR = texts.duration_hour || ' hour';
        const S_HOURS = texts.duration_hours || ' hours';

        if (minutes === 0) return S_0_MINUTES;
        if (minutes < 60) return minutes + (minutes === 1 ? S_MINUTE : S_MINUTES);

        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const hourText = hours + (hours === 1 ? S_HOUR : S_HOURS);

        if (mins === 0) return hourText;
        return hourText + ', ' + mins + (mins === 1 ? S_MINUTE : S_MINUTES);
    }

    /**
     * Validate date string in YYYY-MM-DD format
     * @param {string} dateString - Date string to validate
     * @returns {boolean} - True if valid date
     */
    function isValidDate(dateString) {
        if (!dateString) return false;
        const regEx = /^\d{4}-\d{2}-\d{2}$/;
        if (!dateString.match(regEx)) return false;
        const d = new Date(dateString + 'T00:00:00Z');
        const dNum = d.getTime();
        if (!dNum && dNum !== 0) return false;
        return d.toISOString().slice(0,10) === dateString;
    }

    /**
     * Format date string for display
     * @param {string} dateString - Date string in YYYY-MM-DD format
     * @returns {string} - Formatted date string
     */
    function formatDateForDisplay(dateString) {
        if (!dateString || !isValidDate(dateString)) return '';
        const date = new Date(dateString + 'T00:00:00Z');
        return date.toLocaleDateString(navigator.language || 'en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }

    /**
     * Abort pending AJAX requests to prevent conflicts
     */
    function abortPendingRequests() {
        pendingRequests.forEach(function(req) {
            if (req && req.readyState !== 4) {
                req.abort();
            }
        });
        pendingRequests = [];
    }

    // ========================================================================
    // CENTRALIZED AJAX REQUEST HANDLER
    // ========================================================================

    /**
     * Centralized AJAX request handler with automatic nonce management
     * @param {string} shortAction - Action name (without 'clm_' prefix)
     * @param {Object} data - Data to send
     * @param {Function} successCallback - Success callback function
     * @param {Function} errorCallback - Error callback function
     * @returns {jqXHR|null} - jQuery XHR object or null
     */
    function clmAjaxRequest(shortAction, data, successCallback, errorCallback) {
        const ajaxData = $.extend({}, data, {
            action: 'clm_' + shortAction
        });

        if (typeof clm_vars === 'undefined' || !clm_vars.ajaxurl) {
            console.error('CLM Error: clm_vars.ajaxurl is not defined.');
            if (errorCallback) errorCallback(null, 'config_error', 'AJAX URL not defined');
            return null;
        }

        // Intelligent nonce selection
        if (clm_vars.nonce) {
            if (clm_vars.nonce[shortAction]) {
                ajaxData.nonce = clm_vars.nonce[shortAction];
            } else if (clm_vars.nonce.ajax_nonce) {
                ajaxData.nonce = clm_vars.nonce.ajax_nonce;
            } else if (clm_vars.nonce.filter) {
                ajaxData.nonce = clm_vars.nonce.filter;
            }
        }

        if (!ajaxData.nonce) {
            console.warn('CLM Warning: Nonce not found for action "' + shortAction + '". Request may fail.');
        }

        console.log('CLM AJAX Request for "clm_' + shortAction + '":', ajaxData);

        const request = $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: ajaxData,
            success: function(response) {
                console.log('CLM AJAX Success for "clm_' + shortAction + '":', response);
                if (successCallback) successCallback(response);
            },
            error: function(xhr, status, error) {
                console.error('CLM AJAX Error for "clm_' + shortAction + '":', status, error, xhr.responseText);
                if (status !== 'abort') {
                    if (errorCallback) {
                        errorCallback(xhr, status, error);
                    } else {
                        showNotification('Error: Request failed (' + status + ')', 'error');
                    }
                }
            }
        });

        pendingRequests.push(request);
        return request;
    }

    // ========================================================================
    // SEARCH & FILTERING FUNCTIONS
    // ========================================================================

    /**
     * Initialize enhanced search functionality
     */
    function initEnhancedSearch() {
        console.log('CORE_CLM: initEnhancedSearch() called.');
        const $searchForm = $('#clm-ajax-search-form');
        if (!$searchForm.length) return;

        const $searchInput = $searchForm.find('#clm-search-input');
        const $suggestionsBox = $searchForm.find('#clm-search-suggestions');
        const $loadingIndicator = $searchForm.find('.clm-search-loading');

        // Search form submission
        $searchForm.off('submit.clm').on('submit.clm', function(e) {
            e.preventDefault();
            currentPage = 1;
            performSearchAndFilter();
        });

        // Search input with debouncing
        $searchInput.off('input.clm').on('input.clm', function() {
            clearTimeout(searchTimer);
            const query = $(this).val().trim();

            if (query.length < CONFIG.MIN_SEARCH_LENGTH) {
                if ($suggestionsBox.length) $suggestionsBox.hide().empty();
                if (query.length === 0 && !Object.keys(currentFilters).length) {
                    // Could trigger search here if needed
                }
                return;
            }

            if ($loadingIndicator.length) $loadingIndicator.show();

            searchTimer = setTimeout(function() {
                clmAjaxRequest('ajax_search', { query: query },
                    function(response) {
                        if ($loadingIndicator.length) $loadingIndicator.hide();
                        if (response.success && response.data && response.data.suggestions && $suggestionsBox.length) {
                            displaySearchSuggestions($suggestionsBox, response.data.suggestions);
                        } else {
                            if ($suggestionsBox.length) $suggestionsBox.hide().empty();
                        }
                    },
                    function() {
                        if ($loadingIndicator.length) $loadingIndicator.hide();
                    }
                );
            }, CONFIG.SEARCH_DEBOUNCE_DELAY);
        });

        // Hide suggestions on outside click
        $(document).off('click.clm_suggestions').on('click.clm_suggestions', function(e) {
            if ($suggestionsBox.length && !$(e.target).closest('.clm-search-wrapper').length) {
                $suggestionsBox.hide();
            }
        });
    }

    /**
     * Display search suggestions (placeholder for implementation)
     * @param {jQuery} $suggestionsBox - Suggestions container
     * @param {Array} suggestions - Array of suggestion items
     */
    function displaySearchSuggestions($suggestionsBox, suggestions) {
        // Implementation would depend on suggestion structure
        console.log('Displaying search suggestions:', suggestions);
        // Add your suggestion display logic here
    }

    /**
     * Collect all active filters from form elements
     * @returns {Object} - Object containing all filter values
     */
    function collectFilters() {
        const filters = {
            genre: $('#clm-genre-select').val(),
            language: $('#clm-language-select').val(),
            difficulty: $('#clm-difficulty-select').val(),
            orderby: $('#clm-sort-select').val(),
            order: $('#clm-order-select').val(),
            per_page: $('#clm-items-per-page').val() || CONFIG.DEFAULT_PER_PAGE
        };

        // Add media type filters
        const mediaTypes = ['audio', 'video', 'sheet', 'midi'];
        mediaTypes.forEach(function(type) {
            if ($('input[name="has_' + type + '"]').is(':checked')) {
                filters['has_' + type] = 1;
            }
        });

        // Merge with currentFilters (includes starts_with)
        $.extend(filters, currentFilters);

        // Remove empty values
        Object.keys(filters).forEach(function(key) {
            if (!filters[key] || filters[key] === '') {
                delete filters[key];
            }
        });

        // Ensure starts_with is properly included
        if (currentFilters.starts_with && currentFilters.starts_with !== 'all') {
            filters.starts_with = currentFilters.starts_with;
        }

        return filters;
    }

    /**
     * Collect archive-specific filters
     * @returns {Object} - Updated currentFilters object
     */
    function collectArchiveFilters() {
        currentFilters = {};
        const $filterForm = $('#clm-filter-form');
        
        if ($filterForm.length) {
            $filterForm.find('select, input[type="checkbox"]:checked, input[type="radio"]:checked').each(function() {
                const name = $(this).attr('name');
                const value = $(this).val();
                if (name && value) {
                    if ($(this).is(':checkbox')) {
                        if (!currentFilters[name]) currentFilters[name] = [];
                        currentFilters[name].push(value);
                    } else {
                        currentFilters[name] = value;
                    }
                }
            });
        }

        // Include specific controls
        const specificControls = [
            { selector: '#clm-items-per-page', key: 'per_page' },
            { selector: '#clm-sort-select', key: 'orderby' },
            { selector: '#clm-order-select', key: 'order' }
        ];

        specificControls.forEach(function(control) {
            const $element = $(control.selector);
            if ($element.length) {
                currentFilters[control.key] = $element.val();
            }
        });

        // Keep starts_with if set by alphabet nav
        const activeAlpha = $('.clm-alphabet-nav .clm-alpha-link.active').data('letter');
        if (activeAlpha && activeAlpha !== 'all') {
            currentFilters.starts_with = activeAlpha;
        } else {
            delete currentFilters.starts_with;
        }

        console.log('Collected Filters:', currentFilters);
        return currentFilters;
    }

    /**
     * Perform search with all current filters
     * @param {number} page - Page number to retrieve
     * @returns {jqXHR|null} - The jQuery XHR object or null
     */
    function performSearch(page) {
        if (isLoading) {
            console.log('Already loading, skipping duplicate search request');
            return null;
        }

        isLoading = true;
        page = page || currentPage || 1;
        console.log('Performing search for page: ' + page);

        const searchQuery = $('#clm-search-input').val() || '';
        const filters = collectFilters();
        
        console.log('Current filters:', filters);
        console.log('Current starts_with:', currentFilters.starts_with);

        if (currentFilters.starts_with && !filters.starts_with) {
            console.log('Adding starts_with to filters: ' + currentFilters.starts_with);
            filters.starts_with = currentFilters.starts_with;
        }

        // Show loading state
        if (typeof showLoadingOverlay === 'function') {
            showLoadingOverlay();
        } else {
            $('#clm-loading-overlay').show();
        }

        abortPendingRequests();

        console.log('Sending AJAX request for main archive filtering');

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
                currentPage = parseInt(response.data.page) || page;
                console.log('Updating main archive content with response data');
                updatePageContent(response.data);
                updateURL(searchQuery, filters, currentPage);
                
                if (typeof scrollToResults === 'function') {
                    scrollToResults();
                }
            } else {
                console.error('Search error:', response);
                if (typeof showNotification === 'function') {
                    showNotification('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'), 'error');
                }
            }
        }, 
        function(xhr, status, error) {
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

    /**
     * Legacy function name - calls performSearch for compatibility
     * @param {number} page - Page number
     */
    function performSearchAndFilter(page) {
        page = page || currentPage || 1;
        console.log('ARCHIVE_ACTION: Performing Search/Filter for page', page);

        const searchQuery = $('#clm-search-input').val() ? $('#clm-search-input').val().trim() : '';
        collectArchiveFilters();

        $('#clm-loading-overlay').fadeIn(100);
        $('.clm-items-list').css('opacity', 0.5);

        clmAjaxRequest('ajax_filter', {
            search: searchQuery,
            filters: currentFilters,
            page: page
        }, function(response) {
            $('#clm-loading-overlay').fadeOut(200);
            $('.clm-items-list').css('opacity', 1);
            if (response.success && response.data) {
                updateArchivePageContent(response.data);
                currentPage = parseInt(response.data.page) || page;
                updateArchiveURL(searchQuery, currentFilters, currentPage);
                if (typeof scrollToResults === 'function') {
                    scrollToResults('#clm-results-container');
                }
            } else {
                showNotification((response.data && response.data.message) || 'Error loading results.', 'error');
            }
        }, function() {
            $('#clm-loading-overlay').fadeOut(200);
            $('.clm-items-list').css('opacity', 1);
            showNotification('Request failed. Please try again.', 'error');
        });
    }

    // ========================================================================
    // PRACTICE TRACKING FUNCTIONS
    // ========================================================================

    /**
     * Initialize practice tracking functionality
     */
    function initPracticeTracking() {
        console.log('CORE_CLM: initPracticeTracking() called.');
        
        $(document).off('click.clm_practice', '#clm-submit-practice-log')
                   .on('click.clm_practice', '#clm-submit-practice-log', function(e) {
            e.preventDefault();
            console.log('PRACTICE: "Log Practice" button clicked!');

            const $button = $(this);
            const $widget = $button.closest('.clm-practice-tracker');
            
            if (!$widget.length) {
                console.error('PRACTICE: .clm-practice-tracker widget not found.');
                return;
            }

            const practiceData = {
                lyricId: $widget.data('lyric-id'),
                duration: $widget.find('#clm-practice-duration').val(),
                confidence: $widget.find('#clm-practice-confidence').val(),
                notes: $widget.find('#clm-practice-notes').val()
            };

            const $messageContainer = $widget.find('#clm-practice-log-message');

            console.log('PRACTICE: Data:', practiceData);

            // Validation
            const validation = validatePracticeData(practiceData, $messageContainer);
            if (!validation.isValid) return;

            // Submit practice log
            submitPracticeLog($button, $widget, practiceData, $messageContainer);
        });
    }

    /**
     * Validate practice data before submission
     * @param {Object} data - Practice data to validate
     * @param {jQuery} $messageContainer - Message container element
     * @returns {Object} - Validation result with isValid boolean
     */
    function validatePracticeData(data, $messageContainer) {
        const errors = [];

        if (!data.lyricId) {
            errors.push('Lyric ID missing.');
        }
        if (!data.duration || parseInt(data.duration) < 1) {
            errors.push('Please enter a valid duration.');
        }
        if (!data.confidence) {
            errors.push('Please select a confidence level.');
        }

        if (errors.length > 0) {
            const errorMessage = errors.join(' ');
            if ($messageContainer.length) {
                $messageContainer.html('<div class="clm-error">' + escapeHtml(errorMessage) + '</div>').show();
            }
            return { isValid: false, errors: errors };
        }

        return { isValid: true };
    }

    /**
     * Submit practice log via AJAX
     * @param {jQuery} $button - Submit button element
     * @param {jQuery} $widget - Widget container element
     * @param {Object} data - Practice data
     * @param {jQuery} $messageContainer - Message container element
     */
    function submitPracticeLog($button, $widget, data, $messageContainer) {
        const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};
        const loggingText = texts.practice_logging || 'Logging...';
        const originalButtonText = texts.log_session || 'Log Session';

        $button.prop('disabled', true).text(loggingText);
        if ($messageContainer.length) $messageContainer.empty().hide();

        clmAjaxRequest('log_lyric_practice', {
            lyric_id: parseInt(data.lyricId),
            duration: parseInt(data.duration),
            confidence: parseInt(data.confidence),
            notes: data.notes
        }, function(response) {
            $button.prop('disabled', false).text(originalButtonText);
            
            if (response.success) {
                const successMsg = (response.data && response.data.message) || 
                                 (texts.practice_log_success || 'Practice logged!');
                
                if ($messageContainer.length) {
                    $messageContainer.html('<div class="clm-success">' + escapeHtml(successMsg) + '</div>').show();
                }
                
                $widget.find('#clm-practice-notes').val('');

                // Update displays if data available
                if (response.data && response.data.skill && typeof updateSkillDisplay === 'function') {
                    updateSkillDisplay(response.data.skill, $widget.data('lyric-id'));
                }
                if (response.data && response.data.stats && typeof updatePracticeStatsDisplay === 'function') {
                    updatePracticeStatsDisplay(response.data.stats, $widget.data('lyric-id'));
                }
            } else {
                const errorMsg = (response.data && response.data.message) || 
                               (texts.practice_log_error || 'Error.');
                if ($messageContainer.length) {
                    $messageContainer.html('<div class="clm-error">' + escapeHtml(errorMsg) + '</div>').show();
                }
            }
        }, function(xhr, status, error) {
            $button.prop('disabled', false).text(originalButtonText);
            const errorMsg = (texts.practice_log_error_ajax || 'Request failed.') + ' (' + escapeHtml(status) + ')';
            if ($messageContainer.length) {
                $messageContainer.html('<div class="clm-error">' + errorMsg + '</div>').show();
            }
        });
    }

    /**
     * Update skill display widget
     * @param {Object} skillData - Skill data from server
     * @param {string|number} lyricId - Lyric ID for targeting
     */
    function updateSkillDisplay(skillData, lyricId) {
        console.log('UI_UPDATE: updateSkillDisplay for lyric ' + lyricId, skillData);
        
        let $skillWidget = $('.clm-skill-status-widget[data-lyric-id="' + lyricId + '"]');
        if (!$skillWidget.length) {
            $skillWidget = $('.clm-practice-tracker[data-lyric-id="' + lyricId + '"]').find('.clm-current-skill-info');
            if (!$skillWidget.length) {
                console.warn("UI_UPDATE: Skill display area not found for lyric " + lyricId);
                return;
            }
        }

        const skillLevels = (clm_vars && clm_vars.skill_levels_js) ? clm_vars.skill_levels_js : {
            'unknown':    { label: 'Unknown',    color: '#95a5a6', icon: 'dashicons-editor-help',  value: 0, progress: 0 },
            'novice':     { label: 'Novice',     color: '#e74c3c', icon: 'dashicons-warning',        value: 1, progress: 20 },
            'learning':   { label: 'Learning',   color: '#f39c12', icon: 'dashicons-lightbulb',      value: 2, progress: 40 },
            'proficient': { label: 'Proficient', color: '#3498db', icon: 'dashicons-yes-alt',        value: 3, progress: 70 },
            'mastered':   { label: 'Mastered',   color: '#2ecc71', icon: 'dashicons-star-filled',    value: 4, progress: 100 }
        };

        const levelInfo = skillLevels[skillData.skill_level] || skillLevels['unknown'];

        // Update skill badge
        $skillWidget.find('.clm-skill-badge')
            .css('background-color', levelInfo.color)
            .html('<span class="dashicons ' + escapeHtml(levelInfo.icon) + '"></span> ' + escapeHtml(levelInfo.label));

        // Update skill details
        updateSkillDetails($skillWidget, skillData);
        
        // Update goal date display
        updateGoalDateDisplay($skillWidget, skillData);
    }

    /**
     * Update skill details section
     * @param {jQuery} $skillWidget - Skill widget element
     * @param {Object} skillData - Skill data
     */
    function updateSkillDetails($skillWidget, skillData) {
        const $detailsDiv = $skillWidget.find('.clm-skill-details-for-widget');
        if (!$detailsDiv.length) {
            console.warn("UI_UPDATE: .clm-skill-details-for-widget not found");
            return;
        }

        const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};

        if (skillData.practice_count && parseInt(skillData.practice_count) > 0) {
            const sessionCount = parseInt(skillData.practice_count);
            const sessionsText = sessionCount === 1 ? 
                                (texts.one_session || '1 session') : 
                                sessionCount + (texts.many_sessions || ' sessions');
            const totalTimeText = formatDuration(skillData.total_practice_minutes || 0);
            
            $detailsDiv.html(
                '<span>' + escapeHtml(sessionsText) + '</span>' +
                ' | <span>' + escapeHtml(totalTimeText) + ' ' + (texts.total_suffix || 'total') + '</span>'
            );
        } else {
            $detailsDiv.html('<span>' + (texts.no_practice_yet || 'No practice logged for this skill yet.') + '</span>');
        }
    }

    /**
     * Update goal date display
     * @param {jQuery} $skillWidget - Skill widget element
     * @param {Object} skillData - Skill data
     */
    function updateGoalDateDisplay($skillWidget, skillData) {
        const $goalDateDisplay = $skillWidget.find('.clm-skill-goal-date-display');
        const $setGoalButton = $skillWidget.find('.clm-set-skill-goal-button');
        const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};

        if (skillData.goal_date && $goalDateDisplay.length) {
            $skillWidget.find('.clm-skill-goal-date-container').show();
            $goalDateDisplay.text(skillData.goal_date);
            if ($setGoalButton.length) {
                $setGoalButton.text(texts.change_goal_button || 'Change Goal')
                             .data('current-goal', skillData.raw_goal_date || '');
            }
        } else {
            if ($goalDateDisplay.length) $skillWidget.find('.clm-skill-goal-date-container').hide();
            if ($setGoalButton.length) {
                $setGoalButton.text(texts.set_goal_button || 'Set Goal')
                             .data('current-goal', '');
            }
        }
    }

    /**
     * Update practice statistics display
     * @param {Object} statsData - Statistics data from server
     * @param {string|number} lyricId - Lyric ID for targeting
     */
    function updatePracticeStatsDisplay(statsData, lyricId) {
        console.log('UI_UPDATE: updatePracticeStatsDisplay for lyric ' + lyricId, statsData);
        
        const $practiceWidget = $('.clm-practice-tracker[data-lyric-id="' + lyricId + '"]');
        if (!$practiceWidget.length) {
            console.warn("UI_UPDATE: Practice stats display area not found for lyric " + lyricId);
            return;
        }

        // Update individual stats
        const statUpdates = [
            { key: 'total_time_minutes', selector: '.clm-stat-value.total-time', formatter: formatDuration },
            { key: 'sessions', selector: '.clm-stat-value.sessions-count', formatter: null },
            { key: 'last_practice_date_display', selector: '.clm-stat-value.last-practice-date', formatter: null }
        ];

        statUpdates.forEach(function(stat) {
            if (statsData[stat.key] !== undefined) {
                const value = stat.formatter ? stat.formatter(statsData[stat.key]) : statsData[stat.key];
                $practiceWidget.find(stat.selector).text(value);
            }
        });

        // Update confidence stars
        if (statsData.confidence !== undefined) {
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                starsHtml += i <= statsData.confidence ?
                    '<span class="dashicons dashicons-star-filled"></span>' :
                    '<span class="dashicons dashicons-star-empty"></span>';
            }
            $practiceWidget.find('.clm-stat-value.confidence-stars').html(starsHtml);
        }
    }

    // ========================================================================
    // SKILL GOAL MANAGEMENT FUNCTIONS
    // ========================================================================

    /**
     * Initialize skill goal management functionality
     */
    function initSkillManagement() {
        console.log('CORE_CLM: initSkillGoalManagement() called.');

        // Show/Hide Goal Form
        $(document).off('click.clm_skill', '.clm-set-skill-goal-button')
                   .on('click.clm_skill', '.clm-set-skill-goal-button', function(e) {
            e.preventDefault();
            const $button = $(this);
            const lyricId = $button.data('lyric-id');
            const $formContainer = $('#clm-set-goal-form-container-' + lyricId);
            
            if ($formContainer.length) {
                $formContainer.slideToggle();
                const currentGoal = $button.data('current-goal');
                const defaultDate = currentGoal || new Date().toISOString().split('T')[0];
                $formContainer.find('#clm-goal-date-input-' + lyricId).val(defaultDate);
            } else {
                console.error("Goal form container not found for lyric " + lyricId);
            }
        });

        // Cancel Goal Form
        $(document).off('click.clm_skill', '.clm-cancel-new-goal')
                   .on('click.clm_skill', '.clm-cancel-new-goal', function(e) {
            e.preventDefault();
            $(this).closest('[id^="clm-set-goal-form-container-"]').slideUp();
        });

        // Submit New Goal
        $(document).off('click.clm_skill', '.clm-submit-new-goal')
                   .on('click.clm_skill', '.clm-submit-new-goal', function(e) {
            e.preventDefault();
            handleGoalSubmission($(this));
        });
    }

    /**
     * Handle goal submission
     * @param {jQuery} $button - Submit button element
     */
    function handleGoalSubmission($button) {
        const lyricId = $button.data('lyric-id');
        const $formContainer = $button.closest('[id^="clm-set-goal-form-container-"]');
        const goalDate = $formContainer.find('#clm-goal-date-input-' + lyricId).val();
        const $messageDiv = $formContainer.find('.clm-set-goal-message');
        const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};

        console.log('SKILL_GOAL: Submit goal for lyric ' + lyricId + ', Date: ' + goalDate);

        if (!goalDate || !isValidDate(goalDate)) {
            if ($messageDiv.length) {
                $messageDiv.text(texts.please_select_date || 'Please select a valid date.')
                          .addClass('error').show();
            }
            return;
        }

        if ($messageDiv.length) $messageDiv.hide().removeClass('error success');

        const savingText = texts.skill_goal_saving || 'Saving Goal...';
        const originalButtonText = texts.save_goal_button || 'Save Goal';
        
        $button.prop('disabled', true).text(savingText);

        clmAjaxRequest('set_lyric_skill_goal', {
            lyric_id: lyricId,
            goal_date: goalDate
        }, function(response) {
            $button.prop('disabled', false).text(originalButtonText);
            
            if (response.success) {
                const successMsg = (response.data && response.data.message) || 
                                 (texts.skill_goal_success || 'Goal set!');
                
                if ($messageDiv.length) {
                    $messageDiv.text(successMsg).addClass('success').show();
                }

                // Update skill display
                updateSkillDisplayAfterGoalSet(lyricId, response.data);

                setTimeout(function() {
                    $formContainer.slideUp();
                    if ($messageDiv.length) $messageDiv.hide();
                }, 2000);
            } else {
                const errorMsg = (response.data && response.data.message) || 
                               (texts.skill_goal_error || 'Error.');
                if ($messageDiv.length) {
                    $messageDiv.text(errorMsg).addClass('error').show();
                }
            }
        }, function(xhr, status, error) {
            $button.prop('disabled', false).text(originalButtonText);
            const errorMsg = (texts.skill_goal_error_ajax || 'Request failed.') + ' (' + escapeHtml(status) + ')';
            if ($messageDiv.length) {
                $messageDiv.text(errorMsg).addClass('error').show();
            }
        });
    }

    /**
     * Update skill display after goal is set
     * @param {string|number} lyricId - Lyric ID
     * @param {Object} responseData - Response data from server
     */
    function updateSkillDisplayAfterGoalSet(lyricId, responseData) {
        const $skillWidget = $('.clm-skill-status-widget[data-lyric-id="' + lyricId + '"]');
        const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};
        
        if ($skillWidget.length && responseData && responseData.goal_date) {
            const formattedDate = formatDateForDisplay(responseData.goal_date);
            $skillWidget.find('.clm-skill-goal-date-display').text(formattedDate).show();
            $skillWidget.find('.clm-set-skill-goal-button')
                       .text(texts.change_goal || 'Change Goal')
                       .data('current-goal', responseData.goal_date);
        }
    }

    // ========================================================================
    // PLAYLIST MANAGEMENT FUNCTIONS
    // ========================================================================

    /**
     * Initialize playlist management functionality
     */
    function initPlaylistManagement() {
        console.log('CORE_CLM: initPlaylistManagement() called.');
        // Basic placeholder - specific functionality handled by initPlaylistDropdowns

        $(document).on('click', '.clm-remove-from-playlist-button', function(e) {
            e.preventDefault();
            const $button = $(this);
    
            if ($button.prop('disabled') || $button.hasClass('clm-processing')) {
                return; // Prevent multiple clicks
            }
    
            const playlistId = $button.data('playlist-id');
            const lyricId = $button.data('lyric-id');
            const $trackItem = $button.closest('.clm-playlist-track-item'); // The <li> or <div> containing the track
    
            const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};
            const confirmMsg = texts.playlist_confirm_remove_lyric || 'Are you sure you want to remove this lyric from the playlist?';
            const removingText = texts.playlist_removing_lyric || 'Removing...';
            const originalButtonContent = $button.html(); // Store original HTML content (icon + text if any)
    
            if (!playlistId || !lyricId) {
                console.error('CLM Playlist: Missing playlistId or lyricId for remove action.');
                showNotification(texts.playlist_data_missing || 'Essential data missing for this action.', 'error');
                return;
            }
    
            if (!confirm(confirmMsg)) {
                return;
            }
    
            $button.addClass('clm-processing').prop('disabled', true).html(removingText); // Use .html() if button contains icon + text
    
            clmAjaxRequest(
                'remove_from_playlist', // This is the shortAction
                {
                    playlist_id: playlistId,
                    lyric_id: lyricId
                    // Nonce will be added by clmAjaxRequest from clm_vars.nonce.remove_from_playlist
                },
                function(response) { // Success callback of clmAjaxRequest
                    if (response.success) {
                        showNotification((response.data && response.data.message) || (texts.playlist_remove_success || 'Lyric removed successfully!'), 'success');
                        if ($trackItem.length) {
                            $trackItem.fadeOut(400, function() {
                                $(this).remove();
                                // Optional: Update a track count display if you have one
                                var $playlistDisplay = $('.clm-single-playlist-display[data-playlist-id="' + playlistId + '"]');
                                if ($playlistDisplay.length) {
                                    var $countSpan = $playlistDisplay.find('.clm-playlist-track-count-number'); // You'd need this element
                                    if ($countSpan.length) {
                                        var currentCount = parseInt($countSpan.text()) || 0;
                                        $countSpan.text(Math.max(0, currentCount - 1));
                                    }
                                }
                            });
                        } else {
                            // If not removing from a visible list, maybe just disable the button permanently or reload
                            $button.html((texts.playlist_removed_feedback || '✓ Removed')).removeClass('clm-processing').addClass('clm-success-feedback');
                            // $button will remain disabled as it's already removed.
                        }
                    } else {
                        $button.prop('disabled', false).html(originalButtonContent).removeClass('clm-processing');
                        showNotification((response.data && response.data.message) || (texts.playlist_error_generic || 'Could not remove lyric.'), 'error');
                    }
                },
                function(xhr, status, error) { // Error callback of clmAjaxRequest
                    $button.prop('disabled', false).html(originalButtonContent).removeClass('clm-processing');
                    // showNotification is handled by clmAjaxRequest's default error path if this is null
                }
            );
        });


        $(document).on('click', '.clm-delete-entire-playlist-button', function(e) {
            e.preventDefault();
            const $button = $(this);
            if ($button.prop('disabled')) return;
        
            const playlistId = $button.data('playlist-id');
            const $playlistItem = $button.closest('.clm-my-playlist-item'); // The <li> or <div> for this playlist
        
            const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};
            const confirmMsg = texts.playlist_confirm_delete_list || 'Are you sure you want to permanently delete this playlist? This cannot be undone.';
            const deletingText = texts.playlist_deleting || 'Deleting...';
            const originalButtonHTML = $button.html();
        
            if (!playlistId) {
                showNotification(texts.playlist_data_missing || 'Playlist ID missing.', 'error');
                return;
            }
        
            if (!confirm(confirmMsg)) {
                return;
            }
        
            $button.prop('disabled', true).html(deletingText);
        
            clmAjaxRequest('delete_user_playlist', // shortAction
                { playlist_id: playlistId },
                function(response) { // Success callback
                    if (response.success) {
                        showNotification((response.data && response.data.message) || (texts.playlist_delete_success || 'Playlist deleted!'), 'success');
                        if ($playlistItem.length) {
                            $playlistItem.fadeOut(400, function() { $(this).remove(); });
                        }
                        // Potentially update a "total playlists" counter if displayed
                    } else {
                        $button.prop('disabled', false).html(originalButtonHTML);
                        showNotification((response.data && response.data.message) || (texts.playlist_error_generic || 'Could not delete playlist.'), 'error');
                    }
                },
                function() { // AJAX error callback
                    $button.prop('disabled', false).html(originalButtonHTML);
                }
            );
        });
        
        // Also update the "Create New Playlist" button toggle for this context
        $(document).on('click', '.clm-create-new-playlist-toggle-button', function(e){
            e.preventDefault();
            $(this).siblings('.clm-create-playlist-form-area').slideToggle();
        });

    }
    /**
     * Smart positioning for playlist dropdowns
     * Automatically detects column position and adjusts dropdown alignment
     */
    function applySmartDropdownPositioning() {
        $('.clm-playlist-dropdown:visible').each(function() {
            const $dropdown = $(this);
            const $wrapper = $dropdown.closest('.clm-playlist-wrapper');
            const $container = $wrapper.closest('.clm-items-list, .clm-media-browser, .clm-lyrics-list');
            
            // Reset positioning classes
            $dropdown.removeClass('clm-align-left clm-align-right clm-first-column clm-center-column');
            
            if ($container.length) {
                const containerRect = $container[0].getBoundingClientRect();
                const wrapperRect = $wrapper[0].getBoundingClientRect();
                const dropdownWidth = 320; // Match CSS min-width
                
                // Calculate relative position within container
                const relativeLeft = wrapperRect.left - containerRect.left;
                const relativeRight = containerRect.right - wrapperRect.right;
                const containerWidth = containerRect.width;
                
                // Detect if we're in a grid layout
                const isGridLayout = $container.hasClass('clm-items-list') || 
                                    $container.hasClass('clm-media-browser') ||
                                    window.getComputedStyle($container[0]).display === 'grid';
                
                if (isGridLayout) {
                    // Grid layout logic
                    const columnPosition = relativeLeft / containerWidth;
                    
                    if (columnPosition < 0.33) {
                        // First column - align to left
                        $dropdown.addClass('clm-first-column clm-align-left');
                    } else if (columnPosition > 0.66) {
                        // Last column - align to right
                        $dropdown.addClass('clm-align-right');
                    } else {
                        // Middle column - center
                        $dropdown.addClass('clm-center-column');
                    }
                } else {
                    // Non-grid layout - check if dropdown would go off-screen
                    if (relativeLeft < dropdownWidth / 2) {
                        $dropdown.addClass('clm-align-left');
                    } else if (relativeRight < dropdownWidth / 2) {
                        $dropdown.addClass('clm-align-right');
                    }
                }
            }
            
            // Mobile override - always center on small screens
            if (window.innerWidth <= 768) {
                $dropdown.removeClass('clm-align-left clm-align-right clm-first-column');
            }
        });
    }

    function initMyPlaylistsShortcode() { // Call this from initializeFeatures if .clm-my-playlists-list exists
        console.log('CORE_CLM: initMyPlaylistsShortcode() called.');
    
        // Toggle "Create New Playlist" form visibility for the shortcode
        $(document).on('click', '.clm-create-new-playlist-from-shortcode-button', function(e) {
            e.preventDefault();
            // Find the form related to this button, perhaps by a shared parent or data attribute
            var $button = $(this);
            var $formArea = $button.siblings('.clm-create-playlist-form-area'); // Or a more robust selector
            if ($formArea.length) {
                $formArea.slideToggle();
            } else {
                // If the form isn't initially there and should be loaded/shown differently
                // For example, if get_new_playlist_form_html was not called when list was empty
                // This indicates that we need to ensure the form HTML is always available (even if hidden)
                // or load it dynamically.
                // For now, assume it's toggling an existing hidden form.
                console.warn("Create playlist form area not found for shortcode button.");
            }
        });
    
        // Handle submission of the "Create New Playlist" form from the shortcode
        $(document).on('click', '.clm-submit-new-playlist-from-shortcode', function(e) {
            e.preventDefault();
            var $button = $(this);
            if ($button.prop('disabled')) return;
    
            var contextPrefix = $button.data('context-id-prefix');
            var $formArea = $button.closest('.clm-create-playlist-form-area');
            var $nameInput = $formArea.find('#' + contextPrefix + '-name');
            var $descInput = $formArea.find('#' + contextPrefix + '-desc');
            var $messageDiv = $formArea.find('.clm-playlist-creation-message');
    
            var playlistName = $nameInput.val().trim();
            var playlistDesc = $descInput.val().trim();
            var originalButtonText = $button.text();
    
            const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};
            const nameRequiredMsg = texts.playlist_name_required || 'Please enter a playlist name.';
            const creatingText = texts.playlist_creating || 'Creating...';
    
            if (!playlistName) {
                $nameInput.css('border-color', 'red').focus();
                $messageDiv.text(nameRequiredMsg).removeClass('success').addClass('error').show();
                return;
            }
            $nameInput.css('border-color', '');
            $messageDiv.hide().empty();
            $button.prop('disabled', true).text(creatingText);
    
            clmAjaxRequest('create_playlist', {
                playlist_name: playlistName,
                playlist_description: playlistDesc,
                // lyric_id is not sent from this form, PHP handler should not require it
                // visibility can be defaulted in PHP or added as a form field here
            }, function(response) {
                if (response.success) {
                    showNotification((response.data && response.data.message) || 'Playlist created!', 'success');
                    $nameInput.val('');
                    $descInput.val('');
                    $formArea.slideUp();
                    // BEST UX: Reload the list of playlists via AJAX or reload the page
                    // For simplicity now, we can just inform the user.
                    // location.reload(); // Or target a specific part of the page to refresh
                    $messageDiv.text('Success! Reloading list...').removeClass('error').addClass('success').show();
                     setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    var errorMsg = (response.data && response.data.message) || 'Error creating playlist.';
                    $messageDiv.text(errorMsg).removeClass('success').addClass('error').show();
                }
            }, function() { /* AJAX error */ }
            ).always(function() {
                $button.prop('disabled', false).text(originalButtonText);
            });
        });
    }




    /**
     * Initialize playlist dropdown functionality
     */
    function initPlaylistDropdowns() {
        // Toggle playlist dropdown
        $(document).off('click.clm_playlist', '.clm-playlist-button')
                   .on('click.clm_playlist', '.clm-playlist-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $dropdown = $(this).siblings('.clm-playlist-dropdown');
            
            // Close other dropdowns
            $('.clm-playlist-dropdown').not($dropdown).hide();
            
            if ($dropdown.is(':visible')) {
                $dropdown.hide();
            } else {
                $dropdown.show();
                // Apply smart positioning after showing
                setTimeout(() => applySmartDropdownPositioning(), 10);
            }
        });
        
        // Close dropdown when clicking elsewhere
        $(document).off('click.clm_playlist_outside')
                   .on('click.clm_playlist_outside', function(e) {
            if (!$(e.target).closest('.clm-playlist-wrapper').length) {
                $('.clm-playlist-dropdown').hide();
            }
        });
        
        // Reposition dropdowns on window resize
        $(window).off('resize.clm_playlist').on('resize.clm_playlist', function() {
            if ($('.clm-playlist-dropdown:visible').length) {
                applySmartDropdownPositioning();
            }
        });
        
        // Add to existing playlist functionality (keep existing code)
        $(document).off('click.clm_playlist', '.clm-add-to-playlist')
                   .on('click.clm_playlist', '.clm-add-to-playlist', function(e) {
            e.preventDefault();
            handleAddToPlaylist($(this));
        });
        
        // Create new playlist functionality (keep existing code)
        $(document).off('click.clm_playlist', '.clm-create-and-add')
                   .on('click.clm_playlist', '.clm-create-and-add', function(e) {
            e.preventDefault();
            handleCreateAndAdd($(this));
        });
    }

    /**
     * Handle adding item to existing playlist
     * @param {jQuery} $link - Add to playlist link element
     */
    function handleAddToPlaylist($link) {
        if ($link.hasClass('clm-processing') || $link.hasClass('clm-feedback-active')) {
            return;
        }

        const playlistId = $link.data('playlist-id');
        const lyricId = $link.data('lyric-id');
        const originalLinkText = $link.text();
        const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};

        if (!playlistId || !lyricId) {
            console.error('Playlist or Lyric ID missing for .clm-add-to-playlist');
            showNotification(texts.playlist_data_missing || 'Data missing for playlist action.', 'error');
            return;
        }

        // Set loading state
        $link.addClass('clm-processing')
             .text(texts.playlist_adding || 'Adding...')
             .css('opacity', 0.7);

        clmAjaxRequest('add_to_playlist', {
            playlist_id: playlistId,
            lyric_id: lyricId
        }, function(response) {
            $link.removeClass('clm-processing').css('opacity', 1);

            if (response.success) {
                handleAddToPlaylistSuccess($link, originalLinkText, texts);
            } else {
                handleAddToPlaylistError($link, originalLinkText, response, texts);
            }
        }, function(xhr, status, error) {
            $link.removeClass('clm-processing').css('opacity', 1);
            handleAddToPlaylistNetworkError($link, originalLinkText, status, texts);
        });
    }

    /**
     * Handle successful add to playlist
     * @param {jQuery} $link - Link element
     * @param {string} originalText - Original link text
     * @param {Object} texts - Localized text object
     */
    function handleAddToPlaylistSuccess($link, originalText, texts) {
        $link.text((clm_vars && clm_vars.text && clm_vars.text.playlist_added_feedback) || '✓ Added')
             .removeClass('clm-error-feedback clm-notice-feedback')
             .addClass('clm-success-feedback clm-feedback-active');

        setTimeout(function() {
            $link.text(originalText).removeClass('clm-success-feedback clm-feedback-active');
        }, 2500);

        setTimeout(function() {
            $link.closest('.clm-playlist-dropdown').hide();
        }, 1500);
    }

    /**
     * Handle add to playlist error
     * @param {jQuery} $link - Link element
     * @param {string} originalText - Original link text
     * @param {Object} response - Server response
     * @param {Object} texts - Localized text object
     */
    function handleAddToPlaylistError($link, originalText, response, texts) {
        let feedbackText = texts.playlist_error_generic || 'Error';
        let feedbackClass = 'clm-error-feedback';

        if (response.data && response.data.message) {
            showNotification(response.data.message, 'error');
            if (response.data.message.toLowerCase().includes('already in')) {
                feedbackText = texts.playlist_already_in || 'Already in';
                feedbackClass = 'clm-notice-feedback';
            }
        } else {
            showNotification(feedbackText, 'error');
        }

        $link.text(feedbackText)
             .removeClass('clm-success-feedback')
             .addClass(feedbackClass + ' clm-feedback-active');

        setTimeout(function() {
            $link.text(originalText).removeClass(feedbackClass + ' clm-feedback-active');
        }, 3000);
    }

    /**
     * Handle add to playlist network error
     * @param {jQuery} $link - Link element
     * @param {string} originalText - Original link text
     * @param {string} status - Error status
     * @param {Object} texts - Localized text object
     */
    function handleAddToPlaylistNetworkError($link, originalText, status, texts) {
        $link.text(texts.playlist_error_connection || 'Connection Error')
             .removeClass('clm-success-feedback clm-notice-feedback')
             .addClass('clm-error-feedback clm-feedback-active');

        showNotification(texts.playlist_error_connection_long || 'Could not connect to server. Please try again.', 'error');

        setTimeout(function() {
            $link.text(originalText).removeClass('clm-error-feedback clm-feedback-active');
        }, 3000);
    }

    /**
     * Handle creating new playlist and adding item
     * @param {jQuery} $button - Create and add button element
     */
    function handleCreateAndAdd($button) {
        if ($button.prop('disabled')) {
            return;
        }

        const $formContainer = $button.closest('.clm-create-new-playlist');
        const $inputField = $formContainer.find('.clm-new-playlist-name');
        const playlistName = $inputField.val().trim();
        const lyricId = $button.data('lyric-id');
        const originalButtonText = $button.text();
        const texts = (clm_vars && clm_vars.text) ? clm_vars.text : {};

        if (!playlistName) {
            $inputField.css('border-color', 'red').focus();
            showNotification(texts.playlist_name_required || 'Please enter a playlist name.', 'error');
            return;
        }

        $inputField.css('border-color', '');
        $button.prop('disabled', true).text(texts.playlist_creating || 'Creating...');

        clmAjaxRequest('create_playlist', {
            playlist_name: playlistName,
            lyric_id: lyricId
        }, function(response) {
            if (response.success) {
                handleCreatePlaylistSuccess($button, $inputField, originalButtonText, response, texts);
            } else {
                handleCreatePlaylistError($button, $inputField, originalButtonText, response, texts);
            }
        }, function(xhr, status, error) {
            handleCreatePlaylistNetworkError($button, $inputField, originalButtonText, status, texts);
        });
    }

    /**
     * Handle successful playlist creation
     * @param {jQuery} $button - Button element
     * @param {jQuery} $inputField - Input field element
     * @param {string} originalText - Original button text
     * @param {Object} response - Server response
     * @param {Object} texts - Localized text object
     */
    function handleCreatePlaylistSuccess($button, $inputField, originalText, response, texts) {
        $button.text(texts.playlist_created_added_feedback || '✓ Created & Added')
               .removeClass('clm-error-feedback');
        
        showNotification((response.data && response.data.message) || 
                        (texts.playlist_create_success || 'Playlist created!'), 'success');
        
        $inputField.val('');

        setTimeout(function() {
            $button.prop('disabled', false).text(originalText);
            $button.closest('.clm-playlist-dropdown').hide();
        }, 2000);
    }

    /**
     * Handle playlist creation error
     * @param {jQuery} $button - Button element
     * @param {jQuery} $inputField - Input field element
     * @param {string} originalText - Original button text
     * @param {Object} response - Server response
     * @param {Object} texts - Localized text object
     */
    function handleCreatePlaylistError($button, $inputField, originalText, response, texts) {
        $button.prop('disabled', false).text(originalText).addClass('clm-error-feedback');
        
        const errorMessage = (response.data && response.data.message) ? 
                            response.data.message : 
                            (texts.playlist_error_generic || 'Error creating playlist.');
        
        showNotification(errorMessage, 'error');

        if (errorMessage.toLowerCase().includes('name')) {
            $inputField.css('border-color', 'red').focus();
        }
    }

    /**
     * Handle playlist creation network error
     * @param {jQuery} $button - Button element
     * @param {jQuery} $inputField - Input field element
     * @param {string} originalText - Original button text
     * @param {string} status - Error status
     * @param {Object} texts - Localized text object
     */
    function handleCreatePlaylistNetworkError($button, $inputField, originalText, status, texts) {
        $button.prop('disabled', false).text(originalText).addClass('clm-error-feedback');
        
        const errorMessage = (texts.playlist_error_connection_long || 'Connection error. Please try again.') + 
                            (status ? ' (' + status + ')' : '');
        
        showNotification(errorMessage, 'error');
    }

    // ========================================================================
    // UI & DISPLAY FUNCTIONS
    // ========================================================================

   /**
 * Enhanced initDetailToggles function with Chrome Android compatibility
 * Add this to your public.js file, replacing the existing initDetailToggles function
 */
function initDetailToggles() {
    console.log('CORE_CLM: initDetailToggles() called.');
    
    // Remove any existing event listeners to prevent duplicates
    $(document).off('click.clm_toggles touchend.clm_toggles', '.clm-toggle-button');
    
    // Chrome Android compatibility: Use both click and touchend events
    $(document).on('click.clm_toggles touchend.clm_toggles', '.clm-toggle-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Prevent double-firing on devices that support both touch and click
        if (e.type === 'touchend' && $(this).data('touched')) {
            return;
        } else if (e.type === 'click' && $(this).data('touched')) {
            $(this).removeData('touched');
            return;
        } else if (e.type === 'touchend') {
            $(this).data('touched', true);
            // Clear the flag after a short delay
            setTimeout(() => {
                $(this).removeData('touched');
            }, 300);
        }
        
        const $button = $(this);
        const targetSelector = $button.data('target');
        const $targetElement = $(targetSelector);
        
        console.log('Toggle button clicked/touched:', targetSelector);
        
        if ($targetElement.length) {
            const isCurrentlyVisible = $targetElement.is(':visible');
            const willBeVisible = !isCurrentlyVisible;
            
            // Update ARIA attribute immediately for accessibility
            $button.attr('aria-expanded', willBeVisible.toString());
            
            // Toggle the target element
            $targetElement.slideToggle(300, function() {
                // Double-check visibility after animation
                const finallyVisible = $(this).is(':visible');
                console.log('Toggle completed, final visibility:', finallyVisible);
                
                // Update button text and icon state
                updateToggleButtonState($button, finallyVisible);
            });
        } else {
            console.warn('Target element not found:', targetSelector);
        }
    });
    
    // Additional Chrome Android fix: Handle touch events separately
    $(document).on('touchstart.clm_toggles', '.clm-toggle-button', function(e) {
        // Add visual feedback for touch start
        $(this).addClass('clm-button-touching');
    });
    
    $(document).on('touchcancel.clm_toggles touchleave.clm_toggles', '.clm-toggle-button', function(e) {
        // Remove visual feedback
        $(this).removeClass('clm-button-touching');
    });
}

/**
 * Update toggle button state based on visibility
 * @param {jQuery} $button - Toggle button element
 * @param {boolean} isVisible - Whether target element is visible
 */
function updateToggleButtonState($button, isVisible) {
    $button.attr('aria-expanded', isVisible);
    
    const $showText = $button.find('.clm-toggle-text-show');
    const $hideText = $button.find('.clm-toggle-text-hide');
    const $icon = $button.find('.clm-toggle-icon');

    if (isVisible) {
        $showText.hide();
        $hideText.show();
        $icon.addClass('clm-rotated');
    } else {
        $showText.show();
        $hideText.hide();
        $icon.removeClass('clm-rotated');
    }
    
    // Remove any lingering touch classes
    $button.removeClass('clm-button-touching');
}

// Optional: Add debugging function for Chrome issues
function debugToggleButtons() {
    console.log('CLM Toggle Buttons Debug:');
    $('.clm-toggle-button').each(function(index) {
        const $button = $(this);
        const target = $button.data('target');
        const $target = $(target);
        
        console.log(`Button ${index + 1}:`, {
            button: $button[0],
            target: target,
            targetExists: $target.length > 0,
            targetVisible: $target.is(':visible'),
            ariaExpanded: $button.attr('aria-expanded')
        });
    });
}

// Call debug function in console if needed: debugToggleButtons();

    /**
     * Update toggle button state based on visibility
     * @param {jQuery} $button - Toggle button element
     * @param {boolean} isVisible - Whether target element is visible
     */
    function updateToggleButtonState($button, isVisible) {
        $button.attr('aria-expanded', isVisible);
        
        const $showText = $button.find('.clm-toggle-text-show');
        const $hideText = $button.find('.clm-toggle-text-hide');
        const $icon = $button.find('.clm-toggle-icon');

        if (isVisible) {
            $showText.hide();
            $hideText.show();
            $icon.addClass('clm-rotated');
        } else {
            $showText.show();
            $hideText.hide();
            $icon.removeClass('clm-rotated');
        }
    }


        /**
     * Enhanced function to detect mobile viewport
     * Useful for dropdown positioning decisions
     */
    function isMobileViewport() {
        return window.innerWidth <= 768 || 
            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    /**
     * Force dropdown positioning update
     * Call this function if you dynamically update the grid layout
     */
    function updateDropdownPositioning() {
        if ($('.clm-playlist-dropdown:visible').length) {
            applySmartDropdownPositioning();
        }
    }

    /**
     * Enhanced updatePageContent function
     * Add this to your existing updatePageContent function or replace it
     */
    function updatePageContent(data) {
        if (data.html !== undefined) {
            $('#clm-items-list, .clm-items-list').html(data.html);
        }
        
        if (data.pagination !== undefined) {
            $('.clm-pagination').html(data.pagination);
            initializePaginationLinks();
        }
        
        if (data.total !== undefined) {
            $('.clm-results-count').text(data.total);
        }
        
        if (data.page !== undefined) {
            currentPage = parseInt(data.page);
            $('.clm-page-jump-input, #clm-page-jump-input').val(currentPage);
            $('#clm-results-container').attr('data-current-page', currentPage);
        }
        
        // Reinitialize features for new content
        initPlaylistManagement();
        
        // Important: Reinitialize dropdown positioning for new content
        setTimeout(() => {
            initPlaylistDropdowns();
            // Close any open dropdowns after content update
            $('.clm-playlist-dropdown').hide();
        }, 100);
    }

    /**
     * Initialize pagination links with proper data attributes
     */
    function initializePaginationLinks() {
        $('.clm-pagination a, .clm-pagination-wrapper a').each(function() {
            const $link = $(this);
            const page = getPageNumber($link);
            if (page && !$link.data('page')) {
                $link.attr('data-page', page);
            }
        });
        
        $('.clm-pagination .page-numbers, .clm-pagination-wrapper .page-numbers').each(function() {
            const $span = $(this);
            const text = $span.text().trim();
            if (text === currentPage.toString() && !$span.hasClass('current') && !$span.hasClass('clm-current')) {
                $span.addClass('clm-current');
            }
        });
    }

    /**
     * Update archive page content (legacy function name)
     * @param {Object} data - Response data
     */
    function updateArchivePageContent(data) {
        console.log('UI_UPDATE: Updating archive page content.');
        updatePageContent(data);
    }

    // ========================================================================
    // PAGINATION FUNCTIONS
    // ========================================================================

    /**
     * Get page number from a pagination link or element
     * @param {jQuery} $element - The pagination link or element
     * @returns {number|null} - The page number or null if not found
     */
    function getPageNumber($element) {
        console.log('Getting page number from:', $element.prop('tagName'), 
                   'class:', $element.attr('class'), 
                   'text:', $element.text().trim());

        // Check data-page attribute first
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
            const patterns = [
                /[\?&]paged=(\d+)/,      // Query string format
                /\/page\/(\d+)\/?/,     // Pretty permalink format
                /#page-(\d+)/           // Hash format
            ];

            for (let pattern of patterns) {
                const match = href.match(pattern);
                if (match && match[1]) {
                    console.log('Found page from URL pattern:', match[1]);
                    return parseInt(match[1]);
                }
            }
        }
        
        console.log('Could not determine page number');
        return null;
    }

    /**
     * Initialize pagination for shortcode containers
     * @param {jQuery} container - The shortcode container
     */
    function initShortcodePagination(container) {
        if (container.data('ajax') !== 'yes') return;
        
        console.log('Setting up pagination for container:', container.attr('id'));
        
        // Clean up existing handlers
        container.off('click.clm_shortcode_pagination');
        
        // Use event delegation for pagination links
        container.on('click.clm_shortcode_pagination', 
                    '.clm-pagination a, .page-numbers a, .clm-page-link, .clm-shortcode-pagination a', 
                    function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $link = $(this);
            console.log('Pagination link clicked:', $link.attr('class'), $link.attr('href'));
            
            // Skip if disabled, current, or alphabet filter
            if ($link.hasClass('disabled') || $link.hasClass('clm-current') || 
                $link.hasClass('current') || $link.parent().hasClass('current') ||
                $link.hasClass('clm-alpha-link')) {
                console.log('Skipping click on disabled/current/alphabet link');
                return false;
            }
            
            const page = getPageNumber($link);
            if (page) {
                console.log('Navigating to page:', page);
                if (typeof performShortcodeSearch === 'function') {
                    performShortcodeSearch(container, page);
                }
            } else {
                console.warn('Could not determine page number from link:', $link.attr('href'));
            }
            
            return false;
        });
        
        // Page jump handler
        container.on('click.clm_shortcode_pagination', '.clm-page-jump-button, .clm-go-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Page jump button clicked');
            
            const $input = $(this).closest('form').find('.clm-page-jump-input');
            if (!$input.length) {
                console.warn('Page jump input not found');
                return;
            }
            
            const page = parseInt($input.val());
            const maxPage = parseInt($input.attr('max') || 9999);
            
            console.log('Page jump requested to page:', page, 'max:', maxPage);
            
            if (page && page > 0 && page <= maxPage && typeof performShortcodeSearch === 'function') {
                performShortcodeSearch(container, page);
            } else {
                console.warn('Invalid page number for jump:', page, 'max:', maxPage);
            }
        });
        
        console.log('Pagination handlers initialized for container:', container.attr('id'));
    }

    /**
     * Initialize basic archive pagination
     */
    function initArchivePagination() {
        console.log('CORE_CLM: initArchivePagination() called.');
        
        $(document).off('click.clm_archive_pagination', '.clm-pagination a:not(.current)')
                   .on('click.clm_archive_pagination', '.clm-pagination a:not(.current)', function(e) {
            e.preventDefault();
            
            if ($(this).closest('.clm-shortcode-pagination').length) return;

            const page = extractPageFromHref($(this).attr('href')) || $(this).text();
            if (page && !isNaN(parseInt(page))) {
                performSearchAndFilter(parseInt(page));
            }
        });
    }

    /**
     * Extract page number from href attribute
     * @param {string} href - The href attribute value
     * @returns {number|null} - Extracted page number or null
     */
    function extractPageFromHref(href) {
        if (!href) return null;
        
        const match = href.match(/paged=(\d+)/);
        return match ? parseInt(match[1]) : null;
    }

    // ========================================================================
    // FILTER & NAVIGATION FUNCTIONS
    // ========================================================================

    /**
     * Initialize filter functionality
     */
    function initFilters() {
        console.log('CORE_CLM: initFilters() called.');
        const $filterForm = $('#clm-filter-form');
        if (!$filterForm.length) return;

        // Toggle advanced filters
        $('.clm-toggle-filters').off('click.clm_filters').on('click.clm_filters', function(e) {
            e.preventDefault();
            $('.clm-advanced-filters').slideToggle();
            $(this).toggleClass('active');
        });

        // Quick filters
        $('.clm-quick-filter').off('click.clm_filters').on('click.clm_filters', function(e) {
            e.preventDefault();
            // Implementation would depend on your quick filter structure
        });

        // Apply filters
        $filterForm.find('.clm-apply-filters').off('click.clm_filters').on('click.clm_filters', function(e) {
            e.preventDefault();
            collectArchiveFilters();
            performSearchAndFilter(1);
        });

        // Reset filters
        $filterForm.find('.clm-reset-filters').off('click.clm_filters').on('click.clm_filters', function(e) {
            e.preventDefault();
            resetAllFilters($filterForm);
        });

        // Items per page and sorting changes
        $('#clm-items-per-page, #clm-sort-select, #clm-order-select')
            .off('change.clm_filters').on('change.clm_filters', function() {
            collectArchiveFilters();
            performSearchAndFilter(1);
        });
    }

    /**
     * Reset all filters to default state
     * @param {jQuery} $filterForm - Filter form element
     */
    function resetAllFilters($filterForm) {
        $filterForm[0].reset();
        $('#clm-search-input').val('');
        $('.clm-quick-filter.active, .clm-alpha-link.active').removeClass('active');
        $('.clm-quick-filter[data-filter="all"], .clm-alpha-link[data-letter="all"]').addClass('active');
        currentFilters = {};
        performSearchAndFilter(1);
    }

    /**
     * Initialize alphabet navigation
     */
    function initAlphabetNav() {
        console.log('Initializing alphabet navigation');
        
        $('.clm-alphabet-nav .clm-alpha-link').off('click.clm_alpha').on('click.clm_alpha', function(e) {
            e.preventDefault();
            
            const $link = $(this);
            const letter = $link.data('letter');
            
            console.log('Alphabet link clicked: ' + letter);
            
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
     * Update URL with current state
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
     * Update archive URL (legacy function name)
     * @param {string} search - Search query
     * @param {Object} filters - Filter settings
     * @param {number} page - Current page number
     */
    function updateArchiveURL(search, filters, page) {
        const params = new URLSearchParams();
        
        if (search) params.set('s', search);
        
        Object.keys(filters).forEach(function(key) {
            if (filters[key]) params.set(key, filters[key]);
        });
        
        if (page && page > 1) params.set('paged', page);

        const newURL = window.location.pathname + '?' + params.toString();
        window.history.pushState({}, '', newURL);
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
        const filterKeys = ['genre', 'language', 'difficulty'];
        filterKeys.forEach(function(filter) {
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
     * Debug function for testing dropdown positioning
     * Call this in console: debugDropdownPositioning()
     */
    function debugDropdownPositioning() {
        console.log('CLM Dropdown Positioning Debug:');
        $('.clm-playlist-wrapper').each(function(index) {
            const $wrapper = $(this);
            const $dropdown = $wrapper.find('.clm-playlist-dropdown');
            const $container = $wrapper.closest('.clm-items-list, .clm-media-browser, .clm-lyrics-list');
            
            if ($container.length) {
                const containerRect = $container[0].getBoundingClientRect();
                const wrapperRect = $wrapper[0].getBoundingClientRect();
                const relativeLeft = wrapperRect.left - containerRect.left;
                const containerWidth = containerRect.width;
                const columnPosition = relativeLeft / containerWidth;
                
                console.log(`Dropdown ${index + 1}:`, {
                    relativeLeft: relativeLeft,
                    containerWidth: containerWidth,
                    columnPosition: columnPosition.toFixed(2),
                    classes: $dropdown.attr('class'),
                    isVisible: $dropdown.is(':visible')
                });
            }
        });
    }


    // ========================================================================
    // MAIN INITIALIZATION
    // ========================================================================

    /**
     * Initialize all features
     */
    function initializeFeatures() {
        console.log('CORE_CLM: initializeFeatures() function has been CALLED.');

        // Core features
        initPracticeTracking();
        initSkillManagement();
        initPlaylistManagement();
        initPlaylistDropdowns();
        initDetailToggles();

        // Archive/Browse page features
        const hasArchiveElements = $('#clm-ajax-search-form').length || $('#clm-filter-form').length;
        if (hasArchiveElements) {
            console.log('CORE_CLM: Archive page elements detected, initializing archive features.');
            initEnhancedSearch();
            initFilters();
            initAlphabetNav();
            initArchivePagination();
            initializeFromURL();
        }

        if ($('.clm-my-playlists-list').length) { // Check if the shortcode's output container exists
            initMyPlaylistsShortcode();
        }
          // Initialize smart dropdown positioning on page load
            setTimeout(() => applySmartDropdownPositioning(), 500);
    }

    // ========================================================================
    // DOCUMENT READY INITIALIZATION
    // ========================================================================

    $(document).ready(function() {
        console.log('CORE_CLM: Document is ready. Calling initializeFeatures().');
        
        // Validate clm_vars availability
        if (typeof clm_vars === 'undefined') {
            console.error("CLM FATAL: clm_vars object not found. Plugin's public JS will likely not work.");
            $('body').prepend(
                '<div style="background:red;color:white;padding:10px;text-align:center;font-weight:bold;' +
                'position:fixed;top:0;left:0;width:100%;z-index:99999;">' +
                'CLM Plugin Error: JavaScript variables missing. Check console.' +
                '</div>'
            );
            return;
        }
        
        initializeFeatures();
    });

})(jQuery);