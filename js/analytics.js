/**
 * Analytics JavaScript for Choir Lyrics Manager
 *
 * @package    Choir_Lyrics_Manager
 */

(function($) {
    'use strict';

    // Store chart instance to destroy when creating a new one
    var analyticsChart;

    /**
     * Initialize analytics functionality
     */
    function initAnalytics() {
        // Initial chart load
        loadChart();

        // Handle chart type change
        $('#clm-chart-type').on('change', function() {
            loadChart();
        });
        
        // Handle period change
        $('#clm-chart-period').on('change', function() {
            loadChart();
        });
    }

    /**
     * Load chart data via AJAX
     */
    function loadChart() {
        var chartType = $('#clm-chart-type').val();
        var period = $('#clm-chart-period').val();
        
        // Show loading indicator
        $('.clm-chart-container').addClass('loading');
        
        // Make AJAX request to get chart data
        $.ajax({
            url: clm_analytics_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'clm_get_analytics_data',
                nonce: clm_analytics_vars.nonce,
                data_type: chartType,
                period: period
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Remove loading indicator
                    $('.clm-chart-container').removeClass('loading');
                    
                    // Render the chart
                    renderChart(response.data);
                }
            },
            error: function() {
                // Remove loading indicator
                $('.clm-chart-container').removeClass('loading');
                
                // Show error message
                $('.clm-chart-container').html('<div class="clm-error">Error loading chart data.</div>');
            }
        });
    }

    /**
     * Render chart with provided data
     */
    function renderChart(chartData) {
        var ctx = document.getElementById('clm-analytics-chart').getContext('2d');
        
        // Destroy existing chart if any
        if (analyticsChart) {
            analyticsChart.destroy();
        }
        
        // Create new chart
        analyticsChart = new Chart(ctx, {
            type: chartData.type,
            data: chartData.data,
            options: chartData.options
        });
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
     * Initialize everything when the document is ready
     */
    $(document).ready(function() {
        initAnalytics();
        
        // Format duration values
        $('.clm-summary-value').each(function() {
            var value = $(this).text();
            
            // Check if it's a duration value
            if (value && !isNaN(value) && $(this).siblings('.clm-summary-label').text().indexOf('Time') !== -1) {
                $(this).text(formatDuration(value));
            }
        });
    });

})(jQuery);