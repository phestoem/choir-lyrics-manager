/**
 * Pagination Fix for Choir Lyrics Manager
 * 
 * This code ensures all pagination elements display their numbers correctly
 * on initial page load and after navigation.
 */

(function($) {
    'use strict';

    // Run pagination fix on document ready and after any AJAX requests
    $(document).ready(function() {
        fixPaginationLinks();
        
        // Also run after AJAX loads
        $(document).ajaxComplete(function() {
            setTimeout(fixPaginationLinks, 100);
        });
    });

    // Add a window load event for any dynamically created elements
    $(window).on('load', function() {
        // Run multiple times with delays to catch late DOM updates
        fixPaginationLinks();
        setTimeout(fixPaginationLinks, 500);
        setTimeout(fixPaginationLinks, 1500);
    });

    /**
     * Fix all pagination links to ensure page numbers are visible
     * and data-page attributes are correctly set
     */
    function fixPaginationLinks() {
        console.log('Running pagination fix...');
        
        // Process all pagination containers
        $('.clm-pagination, .clm-shortcode-pagination, .page-numbers, .clm-pagination-wrapper').each(function() {
            const $container = $(this);
            
            // Check if container has already been fixed this session
            if ($container.data('pagination-fixed')) {
                return;
            }
            
            // Get current page from various possible sources
            let currentPage = parseInt($container.data('current-page') || 1);
            
            // Try to get current page from container or parent
            if (!currentPage || isNaN(currentPage)) {
                const closestContainer = $container.closest('[data-current-page]');
                if (closestContainer.length) {
                    currentPage = parseInt(closestContainer.data('current-page')) || 1;
                }
            }

            // Try to get max pages from container
            let maxPages = parseInt($container.data('max-pages') || 1);
            if (!maxPages || isNaN(maxPages)) {
                maxPages = parseInt($container.closest('[data-max-pages]').data('max-pages')) || 8;
            }
            
            // Fix links and spans in pagination
            $container.find('a, span').not('.clm-dots, .dots, label span, .dashicons').each(function() {
                const $element = $(this);
                
                // Skip if element is just a container or has proper content
                if ($element.find('> .dashicons').length || $element.children().length > 1) {
                    return;
                }
                
                // Skip if already has text content
                if ($element.text().trim()) {
                    return;
                }
                
                // Determine what page this element represents
                let pageNum = null;
                
                // For elements with data-page attribute
                if ($element.data('page')) {
                    pageNum = parseInt($element.data('page'));
                } 
                // For links with href containing page information
                else if ($element.is('a') && $element.attr('href')) {
                    const href = $element.attr('href');
                    if (href.includes('paged=')) {
                        const match = href.match(/paged=(\d+)/);
                        if (match && match[1]) {
                            pageNum = parseInt(match[1]);
                        }
                    } else if (href.includes('/page/')) {
                        const match = href.match(/\/page\/(\d+)/);
                        if (match && match[1]) {
                            pageNum = parseInt(match[1]);
                        }
                    }
                }
                
                // Set data-page attribute if found
                if (pageNum) {
                    $element.attr('data-page', pageNum);
                    
                    // Add text content if empty
                    if (!$element.text().trim()) {
                        $element.text(pageNum);
                    }
                }
                
                // Handle prev/next buttons that might be empty
                if (!$element.text().trim()) {
                    if ($element.hasClass('prev') || $element.hasClass('clm-prev')) {
                        $element.html('<span class="dashicons dashicons-arrow-left-alt2"></span> <span class="clm-nav-text">Previous</span>');
                    } else if ($element.hasClass('next') || $element.hasClass('clm-next')) {
                        $element.html('<span class="clm-nav-text">Next</span> <span class="dashicons dashicons-arrow-right-alt2"></span>');
                    }
                }
                
                // For current page that might not have content
                if (($element.hasClass('current') || $element.hasClass('clm-current')) && !$element.text().trim()) {
                    $element.text(currentPage);
                }
            });
            
            // Fix page jump input if it exists
            const $pageJump = $container.find('.clm-page-jump');
            if ($pageJump.length) {
                const $input = $pageJump.find('.clm-page-jump-input, #clm-page-jump-input');
                if ($input.length && (!$input.val() || isNaN($input.val()))) {
                    $input.val(currentPage);
                }
                
                // Make sure max attribute is set
                if ($input.length && (!$input.attr('max') || isNaN($input.attr('max')))) {
                    $input.attr('max', maxPages);
                }
            }
            
            // Mark as fixed to avoid rechecking
            $container.data('pagination-fixed', true);
        });
        
        // Reinitialize page jump functionality
        window.clmPageJump = function(button) {
            const container = $(button).closest('.clm-pagination, .clm-shortcode-pagination');
            const input = container.find('.clm-page-jump-input, #clm-page-jump-input');
            
            if (!input.length) return false;
            
            const page = parseInt(input.val());
            const maxPage = parseInt(input.attr('max') || 9999);
            
            if (!page || page < 1 || page > maxPage) return false;
            
            // Find the appropriate page link to click, or create URL
            const pageLink = container.find('a[data-page="' + page + '"]');
            
            if (pageLink.length) {
                // Trigger existing link
                pageLink[0].click();
            } else {
                // Construct URL for the page
                let baseUrl = window.location.pathname;
                const params = new URLSearchParams(window.location.search);
                params.set('paged', page);
                
                window.location.href = baseUrl + '?' + params.toString();
            }
            
            return false;
        };
    }
    
    // Run immediately
    fixPaginationLinks();
    
})(jQuery);