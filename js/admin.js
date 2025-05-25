/**
 * Admin JavaScript for Choir Lyrics Manager
 *
 * @package    Choir_Lyrics_Manager
 */

(function($) {
    'use strict';

    /**
     * Media uploader functionality
     */
    function initMediaUploader() {
        var mediaUploaderInstance; // Use a single instance, reconfigured each time

        // Handle the UPLOAD/SELECT button click
        $(document).on('click', '.clm-upload-media', function(e) { // Delegated for dynamically added elements
            e.preventDefault();

            var $button = $(this);
            var fieldId = $button.data('field-id'); // Get target field ID
            var previewId = $button.data('preview-id'); // Get target preview ID
            var mediaType = $button.data('media-type') || 'file'; // 'file' for any, or 'image', 'audio', 'application/pdf' etc.
            var uploaderTitle = $button.data('title') || ( (clm_admin_vars && clm_admin_vars.text && clm_admin_vars.text.upload_title) || 'Select File');
            var uploaderButtonText = $button.data('button-text') || ( (clm_admin_vars && clm_admin_vars.text && clm_admin_vars.text.upload_button) || 'Use this file');

            var $inputField = $('#' + fieldId);
            var $previewContainer = $('#' + previewId);
            var $removeButton = $('.clm-remove-media[data-field-id="' + fieldId + '"]'); // Find related remove button

            if (!$inputField.length) {
                console.error('CLM Admin: Target input field #' + fieldId + ' not found.');
                return;
            }

            // Create a new media uploader frame instance each time to allow for different library types
            mediaUploaderInstance = wp.media({
                title: uploaderTitle,
                button: {
                    text: uploaderButtonText
                },
                multiple: false,
                library: mediaType !== 'file' ? { type: mediaType } : {} // Filter library if specific type provided
            });

            mediaUploaderInstance.on('select', function() {
                var attachment = mediaUploaderInstance.state().get('selection').first().toJSON();
                $inputField.val(attachment.id);

                // Update the preview using a generic preview builder
                var previewHtml = buildAdminMediaPreviewHtml(attachment);
                if ($previewContainer.length) {
                    $previewContainer.html(previewHtml);
                } else { // Fallback if specific preview container not found, try to create one
                    $button.after('<div class="clm-media-preview" id="' + previewId + '">' + previewHtml + '</div>');
                }

                if ($removeButton.length) $removeButton.show();
            });

            mediaUploaderInstance.open();
        });

        // Handle the REMOVE button click
        $(document).on('click', '.clm-remove-media', function(e) { // Delegated
            e.preventDefault();
            var $button = $(this);
            var fieldId = $button.data('field-id');
            var previewId = $button.data('preview-id');

            $('#' + fieldId).val('');
            $('#' + previewId).empty();
            $button.hide();
        });


        // --- Practice Tracks ---
        $(document).on('click', '.clm-add-practice-track', function(e) { // Delegated
            e.preventDefault();
            var $container = $('#clm-practice-tracks-container');
            // Generate a unique index to avoid collisions if items are removed and re-added
            var newIndex = $container.find('.clm-practice-track-item').length ? (Math.max(0, ...$container.find('.clm-practice-track-item').map(function() { return parseInt($(this).find('.clm-track-id-input').attr('name').match(/\[(\d+)\]/)[1]); }).get()) + 1) : 0;


            if (typeof wp.template === 'function' && $('#tmpl-clm-practice-track-item').length) {
                var template = wp.template('clm-practice-track-item'); // ID from PHP template
                var newTrackHtml = template({ index: newIndex });
                $container.append(newTrackHtml);
            } else {
                console.error('CLM Admin: Practice track Underscore template not found.');
            }
        });

        $('#clm-practice-tracks-container').on('click', '.clm-remove-practice-track', function(e) { // Already delegated
            e.preventDefault();
            if (confirm((clm_admin_vars && clm_admin_vars.text && clm_admin_vars.text.confirm_delete) || 'Are you sure you want to remove this track?')) {
                $(this).closest('.clm-practice-track-item').remove();
            }
        });

        $('#clm-practice-tracks-container').on('click', '.clm-upload-practice-track', function(e) { // Already delegated
            e.preventDefault();
            var $button = $(this);
            var $trackItem = $button.closest('.clm-practice-track-item');
            var $inputField = $trackItem.find('.clm-track-id-input'); // Target by class
            var $previewContainer = $trackItem.find('.clm-practice-track-preview'); // Target by class
            var uploaderTitle = $button.data('title') || ((clm_admin_vars && clm_admin_vars.text && clm_admin_vars.text.upload_audio) || 'Select Audio');
            var uploaderButtonText = (clm_admin_vars && clm_admin_vars.text && clm_admin_vars.text.upload_button) || 'Use this audio';


            var trackUploader = wp.media({
                title: uploaderTitle,
                button: { text: uploaderButtonText },
                multiple: false,
                library: { type: 'audio' }
            });

            trackUploader.on('select', function() {
                var attachment = trackUploader.state().get('selection').first().toJSON();
                $inputField.val(attachment.id);
                $previewContainer.html(buildAdminMediaPreviewHtml(attachment, true)); // Use helper, true for player
            });
            trackUploader.open();
        });
    }
    
    /**
         * Helper function to build HTML for media preview in admin.
         * @param {object} attachment - The media attachment object from WordPress.
         * @param {boolean} withPlayer - Whether to include an audio player for audio types.
         * @returns {string} HTML string for the preview.
         */
    function buildAdminMediaPreviewHtml(attachment, withPlayer = false) {
        if (!attachment || !attachment.url || !attachment.filename) return '';

        var iconClass = 'dashicons-media-default';
        var type = attachment.type; // 'image', 'audio', 'video'
        var subtype = attachment.subtype; // 'jpeg', 'mp3', 'pdf', 'msword'

        if (type === 'audio') {
            iconClass = 'dashicons-format-audio';
        } else if (type === 'video') {
            iconClass = 'dashicons-format-video';
        } else if (type === 'image') {
            iconClass = 'dashicons-format-image';
        } else if (subtype === 'pdf') {
            iconClass = 'dashicons-pdf';
        } else if (subtype === 'msword' || subtype === 'vnd.openxmlformats-officedocument.wordprocessingml.document') {
            iconClass = 'dashicons-media-document';
        }

        var previewHtml = '<div class="clm-media-item">';
        previewHtml += '<span class="dashicons ' + escapeHtml(iconClass) + '"></span> ';
        previewHtml += '<a href="' + escapeHtml(attachment.url) + '" target="_blank">' + escapeHtml(attachment.filename) + '</a>';
        if (withPlayer && type === 'audio' && subtype !== 'midi' && subtype !== 'mid') {
            previewHtml += '<div class="clm-audio-player" style="margin-top:5px;"><audio controls src="' + escapeHtml(attachment.url) + '"></audio></div>';
        }
        previewHtml += '</div>';
        return previewHtml;
    }



        
    /**
     * Update practice track preview
     */
    function updatePracticeTrackPreview(attachment, previewContainer) {
        previewContainer.empty();
        
        var previewItem = $('<div class="clm-media-item">' +
            '<span class="dashicons dashicons-format-audio"></span>' +
            '<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>' +
            '<div class="clm-audio-player">' +
            '<audio controls src="' + attachment.url + '"></audio>' +
            '</div>' +
            '</div>');
        
        previewContainer.append(previewItem);
    }
    
    
    
    /**
     * Settings page functionality
     */
    function initSettings() {
        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.clm-color-picker').wpColorPicker();
        }
        
        // Toggle dependent settings
        $('.clm-toggle-setting').on('change', function() {
            var target = $(this).data('target');
            
            if ($(this).is(':checked')) {
                $('.' + target).show();
            } else {
                $('.' + target).hide();
            }
        }).trigger('change');
    }
    
    /**
     * Analytics functionality
     */
    function initAnalytics() {
        // Handle chart type change
        $('#clm-chart-type').on('change', function() {
            // Reload chart with new type
            loadChart();
        });
        
        // Handle period change
        $('#clm-chart-period').on('change', function() {
            // Reload chart with new period
            loadChart();
        });
        
        // Initial chart load
        function loadChart() {
            var chartType = $('#clm-chart-type').val();
            var period = $('#clm-chart-period').val();
            
            // Show loading indicator
            $('.clm-chart-container').addClass('loading');
            
            // Make AJAX request to get chart data
            $.ajax({
                url: clm_admin_vars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'clm_get_analytics_data',
                    nonce: clm_admin_vars.nonce,
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
                }
            });
        }
    }
    
    /**
     * Initialize everything when the document is ready
     */
    $(document).ready(function() {
        console.log("CLM Admin JS: Document Ready.");
        if (typeof clm_admin_vars === 'undefined') {
            console.error("CLM Admin JS: clm_admin_vars not defined. Some features may not work.");
            // You might not want to halt execution entirely for admin JS
        }

        initMediaUploader();
        initSettings();

        // Only initialize analytics if on the analytics page (example condition)
        if ($('#clm-analytics-dashboard').length) { // Assume your analytics dashboard has this ID
            initAnalytics();
             if (typeof loadChart === 'function') loadChart(); // Initial load if on the page
        }

        // Other admin initializations if needed
    });
})(jQuery);