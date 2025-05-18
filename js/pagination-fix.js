/**
 * Pagination Fix for Choir Lyrics Manager
 * This code fixes the pagination issues by consistently handling page state
 * and ensuring the correct page is highlighted on both initial load and navigation.
 */

(function($) {
    'use strict';
    
    // ----------------------------------------
    // 1. FIX PAGINATION STATE MANAGEMENT
    // ----------------------------------------
    
    /**
     * Enhanced page number detection that works consistently
     * across all pagination scenarios
     * 
     * @param {jQuery} $element - The pagination link element
     * @return {number|null} - The page number or null if not found
     */
    function getPageNumber($element) {
        // First check data-page attribute
        if ($element.data('page')) {
            return parseInt($element.data('page'));
        }
        
        // Check if element has 'current' class - means we're already on this page
        if ($element.hasClass('current') || $element.hasClass('clm-current')) {
            // Get current page from container or URL
            const containerPage = $('#clm-results-container').data('current-page');
            const urlParams = new URLSearchParams(window.location.search);
            const urlPage = urlParams.get('paged');
            return parseInt(urlPage || containerPage || 1);
        }
        
        // Try to get from link text if it's a number
        const text = $element.text().trim();
        if (/^\d+$/.test(text)) {
            return parseInt(text);
        }
        
        // Check for prev/next links
        if ($element.hasClass('prev') || $element.text().includes('Previous')) {
            const currentPage = getCurrentPageFromDOMorURL();
            return Math.max(1, currentPage - 1);
        }
        
        if ($element.hasClass('next') || $element.text().includes('Next')) {
            const currentPage = getCurrentPageFromDOMorURL();
            return currentPage + 1;
        }
        
        // Try to extract from href - most reliable method
        const href = $element.attr('href');
        if (href) {
            // Check for query string format: ?paged=N or &paged=N
            const queryMatch = href.match(/[?&]paged=(\d+)/);
            if (queryMatch && queryMatch[1]) {
                return parseInt(queryMatch[1]);
            }
            
            // Check for pretty permalink format: /page/N/
            const permalinkMatch = href.match(/\/page\/(\d+)\/?/);
            if (permalinkMatch && permalinkMatch[1]) {
                return parseInt(permalinkMatch[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Get the current page number from DOM or URL
     * This ensures we always have a reliable page number
     */
    function getCurrentPageFromDOMorURL() {
        // Check URL first (highest priority)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('paged')) {
            return parseInt(urlParams.get('paged'));
        }
        
        // Check for permalink structure in URL
        const permalinkMatch = window.location.pathname.match(/\/page\/(\d+)\/?$/);
        if (permalinkMatch && permalinkMatch[1]) {
            return parseInt(permalinkMatch[1]);
        }
        
        // Check data attribute on container
        const containerPage = $('#clm-results-container').data('current-page');
        if (containerPage) {
            return parseInt(containerPage);
        }
        
        // Check for body class (WordPress adds paged-N class)
        const bodyClasses = document.body.className.split(/\s+/);
        for (let i = 0; i < bodyClasses.length; i++) {
            const match = bodyClasses[i].match(/^paged-(\d+)$/);
            if (match) {
                return parseInt(match[1]);
            }
        }
        
        // Default to page 1
        return 1;
    }
    
    // ----------------------------------------
    // 2. ENHANCED PAGINATION INITIALIZATION
    // ----------------------------------------
    
    /**
     * Initialize pagination with enhanced page detection
     * This corrects the initial selection of the current page
     */
    function initEnhancedPagination() {
        // Determine current page once on page load
        const currentPage = getCurrentPageFromDOMorURL();
        console.log('Enhanced pagination initialized with current page:', currentPage);
        
        // Store page number in data attribute for reference
        $('#clm-results-container').attr('data-current-page', currentPage);
        
        // Update page jump input field
        $('#clm-page-jump-input').val(currentPage);
        
        // CRITICAL FIX: Correctly highlight the current page in ALL pagination elements
        markCurrentPageActive(currentPage);
        
        // Bind pagination link clicks with improved handler
        bindPaginationHandlers();
    }
    
    /**
     * Mark current page as active in all pagination elements
     * This fixes the highlighting issue on initial page load
     */
    function markCurrentPageActive(pageNum) {
        if (!pageNum) pageNum = getCurrentPageFromDOMorURL();
        
        // Clear existing current markers first
        $('.clm-pagination .current, .clm-pagination .clm-current, .page-numbers.current')
            .removeClass('current clm-current');
        
        // Mark all matching page number links as current
        // Target all possible pagination selectors
        const pageSelectors = [
            '.clm-pagination a[data-page="' + pageNum + '"]',
            '.clm-pagination-wrapper a[data-page="' + pageNum + '"]',
            '.clm-page-link[data-page="' + pageNum + '"]',
            '.page-numbers:contains("' + pageNum + '")',
            '.navigation.pagination .nav-links a:contains("' + pageNum + '")'
        ];
        
        $(pageSelectors.join(', ')).each(function() {
            const $link = $(this);
            const linkNum = getPageNumber($link);
            
            // Only mark if the page number matches exactly
            if (linkNum === pageNum) {
                // Replace the link with a span for current page (WordPress standard)
                const $parent = $link.parent();
                const $currentSpan = $('<span class="page-numbers current clm-page-link clm-current">' + pageNum + '</span>');
                
                if ($parent.is('li')) {
                    $link.replaceWith($currentSpan);
                } else {
                    $link.replaceWith($currentSpan);
                }
            }
        });
        
        // Special handling for page 1 when no pagination item exists for it
        if (pageNum === 1) {
            // If we're on page 1 but there's no page 1 link, add a marker to body
            if ($('.clm-pagination .current, .clm-pagination .clm-current').length === 0) {
                $('body').addClass('clm-paged-1');
            }
        }
    }
    
    /**
     * Bind click handlers to pagination links with improved reliability
     */
    function bindPaginationHandlers() {
        // Unbind existing handlers to prevent duplicates
        $(document).off('click', '.clm-pagination a, .page-numbers, .clm-page-link');
        
        // Add new consolidated handler
        $(document).on('click', '.clm-pagination a, .page-numbers:not(.current), .clm-page-link:not(.clm-current)', function(e) {
            // Skip if this is in a shortcode container (handled separately)
            if ($(this).closest('.clm-shortcode-container').length) {
                return true;
            }
            
            // Skip if this is disabled or already current
            if ($(this).hasClass('current') || $(this).hasClass('clm-current') || 
                $(this).hasClass('disabled') || $(this).hasClass('dots')) {
                e.preventDefault();
                return false;
            }
            
            // Get page number reliably
            const page = getPageNumber($(this));
            if (!page) return true; // Let default link behavior handle it
            
            // Log for debugging
            console.log('Pagination click: navigating to page', page);
            
            // If using AJAX, prevent default and use AJAX handler
            if (!$(this).closest('[data-ajax="no"]').length) {
                e.preventDefault();
                const currentPage = getCurrentPageFromDOMorURL();
                
                // Don't reload the same page
                if (page === currentPage) {
                    return false;
                }
                
                // Perform search with new page number
                window.performSearch(page);
                return false;
            }
            
            // For non-AJAX, manually ensure the URL is correct before letting default behavior happen
            const href = $(this).attr('href');
            if (href) {
                const pageParam = new URLSearchParams(window.location.search).get('paged');
                if (pageParam && !href.includes('paged=')) {
                    // Ensure the paged parameter is included in the URL
                    e.preventDefault();
                    const separator = href.includes('?') ? '&' : '?';
                    window.location.href = href + separator + 'paged=' + page;
                    return false;
                }
            }
            
            // Let the default link behavior happen
            return true;
        });
        
        // Page jump handler with improved reliability
        $(document).off('submit', '.clm-page-jump form, #clm-page-jump-form');
        $(document).on('submit', '.clm-page-jump form, #clm-page-jump-form', function(e) {
            // Skip if this is in a shortcode container (handled separately)
            if ($(this).closest('.clm-shortcode-container').length) {
                return true;
            }
            
            // Get the input field
            const $input = $(this).find('.clm-page-jump-input, #clm-page-jump-input');
            if (!$input.length) return true;
            
            const page = parseInt($input.val());
            const maxPage = parseInt($input.attr('max') || 9999);
            const currentPage = getCurrentPageFromDOMorURL();
            
            // Validate page number
            if (!page || page < 1 || page > maxPage) {
                e.preventDefault();
                return false;
            }
            
            // Don't reload the same page
            if (page === currentPage) {
                e.preventDefault();
                return false;
            }
            
            // For AJAX mode, use AJAX
            if (!$(this).closest('[data-ajax="no"]').length) {
                e.preventDefault();
                window.performSearch(page);
                return false;
            }
            
            // For non-AJAX, let the form submit happen normally
            return true;
        });
        
        // Direct button click handler (alternative to form submit)
        $(document).off('click', '#clm-page-jump-button, .clm-go-button');
        $(document).on('click', '#clm-page-jump-button, .clm-go-button', function(e) {
            e.preventDefault();
            $(this).closest('form').submit();
        });
    }
    
    // ----------------------------------------
    // 3. ENHANCED AJAX RESPONSE HANDLING
    // ----------------------------------------
    
    /**
     * Enhance the page content update function to correctly
     * handle pagination after AJAX updates
     */
    function enhanceUpdatePageContent(originalFunction) {
        // Store the original function
        window.originalUpdatePageContent = originalFunction;
        
        // Replace with enhanced version
        window.updatePageContent = function(data) {
            // Call the original function first
            window.originalUpdatePageContent(data);
            
            // Get current page from response
            const pageNum = parseInt(data.page) || getCurrentPageFromDOMorURL();
            
            // Ensure pagination is correctly marked
            setTimeout(function() {
                markCurrentPageActive(pageNum);
            }, 100);
            
            // Update URL to reflect the current page
            if (window.history && window.history.replaceState) {
                const currentUrl = window.location.href;
                const pageParam = 'paged=' + pageNum;
                
                let newUrl;
                if (currentUrl.includes('paged=')) {
                    newUrl = currentUrl.replace(/paged=\d+/, pageParam);
                } else {
                    const separator = currentUrl.includes('?') ? '&' : '?';
                    newUrl = currentUrl + separator + pageParam;
                }
                
                // Only update if different
                if (newUrl !== currentUrl) {
                    window.history.replaceState({page: pageNum}, '', newUrl);
                }
            }
        };
    }
    
    // ----------------------------------------
    // 4. IMPROVED URL HANDLING
    // ----------------------------------------
    
    /**
     * Add page parameter to URL correctly with improved handling
     * for various permalink structures
     */
    function enhanceAddPageParam(originalFunction) {
        // Store the original function
        window.originalAddPageParam = originalFunction;
        
        // Replace with enhanced version
        window.addPageParam = function(baseUrl, pageNum) {
            // Skip if no pageNum
            if (!pageNum || pageNum <= 0) {
                return baseUrl;
            }
            
            // Special case for page 1 - remove page parameter
            if (pageNum === 1) {
                // For pretty permalinks, remove /page/N/
                if (baseUrl.includes('/page/')) {
                    return baseUrl.replace(/\/page\/\d+\/?/, '/');
                }
                
                // For query string, remove paged param
                if (baseUrl.includes('paged=')) {
                    const url = new URL(baseUrl);
                    const params = new URLSearchParams(url.search);
                    params.delete('paged');
                    
                    const newSearch = params.toString();
                    return url.origin + url.pathname + (newSearch ? '?' + newSearch : '');
                }
                
                return baseUrl;
            }
            
            // Handle pretty permalinks
            if (baseUrl.includes('/page/')) {
                return baseUrl.replace(/\/page\/\d+\/?/, '/page/' + pageNum + '/');
            }
            
            // Handle query string
            if (baseUrl.includes('paged=')) {
                return baseUrl.replace(/paged=\d+/, 'paged=' + pageNum);
            }
            
            // Add as new parameter
            const separator = baseUrl.includes('?') ? '&' : '?';
            return baseUrl + separator + 'paged=' + pageNum;
        };
    }
    
    // ----------------------------------------
    // 5. INITIALIZATION AND HOOKS
    // ----------------------------------------
    
    // Expose important functions to global scope for other scripts
    window.clmGetCurrentPage = getCurrentPageFromDOMorURL;
    window.clmMarkCurrentPage = markCurrentPageActive;
    
    // Hook into document ready
    $(document).ready(function() {
        // Wait for other scripts to initialize
        setTimeout(function() {
            // Store original functions for reference
            if (window.updatePageContent) {
                enhanceUpdatePageContent(window.updatePageContent);
            }
            
            if (window.addPageParam) {
                enhanceAddPageParam(window.addPageParam);
            }
            
            // Initialize enhanced pagination
            initEnhancedPagination();
            
            console.log('Pagination Enhancement: Initialized and applied fixes');
        }, 100);
        
        // Apply a CSS fix for page 1 highlighting in the pagination
        // This ensures page 1 is highlighted correctly when no paged parameter exists
        const cssFixForPage1 = `
            /* Force the page 1 button to look like the current page if we're on page 1 */
            body:not(.paged) .page-numbers:first-of-type:not(.prev),
            body.paged-1 .page-numbers:first-of-type:not(.prev),
            body:not(.paged) .clm-pagination-wrapper .page-numbers:first-of-type:not(.prev),
            body.paged-1 .clm-pagination-wrapper .page-numbers:first-of-type:not(.prev) {
                background: #007cba !important;
                color: white !important;
                border-color: #007cba !important;
                font-weight: 500 !important;
            }
        `;
        
        // Add the CSS fix to the page
        $('head').append('<style id="clm-pagination-fix">' + cssFixForPage1 + '</style>');
    });
    
    // Fix for history navigation
    window.addEventListener('popstate', function(event) {
        // Wait briefly to ensure DOM is updated
        setTimeout(function() {
            const currentPage = getCurrentPageFromDOMorURL();
            markCurrentPageActive(currentPage);
        }, 100);
    });
    
})(jQuery);