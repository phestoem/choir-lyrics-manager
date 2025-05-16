/**
 * Events JavaScript for Choir Lyrics Manager
 *
 * @package Choir_Lyrics_Manager
 */

(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        // Initialize event features
        initEventCalendar();
        initEventFilters();
        initEventShare();
        initEventAdmin();
    });

    /**
     * Initialize event calendar (placeholder for future implementation)
     */
    function initEventCalendar() {
        var $calendar = $('.clm-event-calendar');
        
        if ($calendar.length) {
            // This would integrate with a calendar library like FullCalendar
            console.log('Event calendar placeholder initialized');
        }
    }

    /**
     * Initialize event filters
     */
    function initEventFilters() {
        // Auto-submit filters on change
        $('.clm-event-filters select').on('change', function() {
            $(this).closest('form').submit();
        });
    }

    /**
     * Initialize event share functionality
     */
    function initEventShare() {
        $('.clm-share-button').on('click', function(e) {
            e.preventDefault();
            
            var url = $(this).attr('href');
            var title = 'Share Event';
            var width = 600;
            var height = 400;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;
            
            window.open(url, title, 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top);
        });
    }

    /**
     * Initialize admin features for events
     */
    function initEventAdmin() {
        if (!$('body').hasClass('wp-admin')) {
            return;
        }
        
        // Date picker for event date field
        if ($.datepicker) {
            $('#clm_event_date').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                minDate: new Date()
            });
        }
        
        // Time picker enhancement
        $('#clm_event_time, #clm_event_end_time').attr('step', '900'); // 15-minute intervals
        
        // Setlist drag and drop is handled in the main admin JS
        
        // Venue autocomplete
        var venues = [];
        $('#clm_event_location').autocomplete({
            source: function(request, response) {
                // This would fetch venues from the database
                response(venues);
            }
        });
    }

    /**
     * Handle print program functionality
     */
    window.clmPrintProgram = function(eventId) {
        window.print();
    };

    /**
     * Handle export to calendar
     */
    window.clmExportToCalendar = function(eventId, format) {
        var baseUrl = clm_events_vars.ajax_url;
        var url = baseUrl + '?action=clm_export_event&event_id=' + eventId + '&format=' + format;
        
        window.open(url, '_blank');
    };

})(jQuery);