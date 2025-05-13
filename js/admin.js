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
        var mediaUploader;
        
        // Handle the upload button click
        $('.clm-upload-media').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var fieldType = button.data('type');
            var title = button.data('title') || clm_admin_vars.text.upload_title;
            var buttonText = button.data('button') || clm_admin_vars.text.upload_button;
            var inputField = $('#clm_' + fieldType + '_id');
            var previewContainer = $('#clm-' + fieldType + '-preview');
            
            // If the media uploader exists, open it
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Create the media uploader
            mediaUploader = wp.media({
                title: title,
                button: {
                    text: buttonText
                },
                multiple: false // Set to true if you want multiple files
            });
            
            // When a file is selected
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                inputField.val(attachment.id);
                
                // Update the preview
                updateMediaPreview(fieldType, attachment, previewContainer);
                
                // Show the remove button
                $('.clm-remove-media[data-type="' + fieldType + '"]').show();
            });
            
            // Open the uploader
            mediaUploader.open();
        });
        
        // Handle the remove button click
        $('.clm-remove-media').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var fieldType = button.data('type');
            var inputField = $('#clm_' + fieldType + '_id');
            var previewContainer = $('#clm-' + fieldType + '-preview');
            
            // Clear the input field and preview
            inputField.val('');
            previewContainer.empty();
            
            // Hide the remove button
            button.hide();
        });
        
        // Handle practice track uploads
        $('.clm-add-practice-track').on('click', function(e) {
            e.preventDefault();
            
            // Get the current number of tracks
            var trackCount = $('.clm-practice-track-item').length;
            
            // Create a new track element using the template
            var template = wp.template('clm-practice-track');
            var newTrack = template({index: trackCount});
            
            // Add the new track to the container
            $('#clm-practice-tracks-container').append(newTrack);
        });
        
        // Handle practice track removal (delegated event)
        $('#clm-practice-tracks-container').on('click', '.clm-remove-practice-track', function(e) {
            e.preventDefault();
            
            if (confirm(clm_admin_vars.text.confirm_delete)) {
                $(this).closest('.clm-practice-track-item').remove();
            }
        });
        
        // Handle practice track upload button (delegated event)
        $('#clm-practice-tracks-container').on('click', '.clm-upload-practice-track', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var trackItem = button.closest('.clm-practice-track-item');
            var trackIndex = button.data('index');
            var inputField = trackItem.find('input[name^="clm_practice_tracks"][name$="[id]"]');
            var previewContainer = trackItem.find('.clm-practice-track-preview');
            
            // Create a media uploader for this track
            var trackUploader = wp.media({
                title: clm_admin_vars.text.upload_audio,
                button: {
                    text: clm_admin_vars.text.upload_button
                },
                multiple: false,
                library: {
                    type: 'audio'
                }
            });
            
            // When a file is selected
            trackUploader.on('select', function() {
                var attachment = trackUploader.state().get('selection').first().toJSON();
                inputField.val(attachment.id);
                
                // Update the preview
                updatePracticeTrackPreview(attachment, previewContainer);
            });
            
            // Open the uploader
            trackUploader.open();
        });
    }
    
    /**
     * Update media preview based on field type and attachment
     */
    function updateMediaPreview(fieldType, attachment, previewContainer) {
        previewContainer.empty();
        
        var previewItem, icon, fileExt;
        
        switch (fieldType) {
            case 'sheet_music':
                fileExt = attachment.filename.split('.').pop().toLowerCase();
                
                if (fileExt === 'pdf') {
                    icon = 'dashicons-pdf';
                } else if (fileExt === 'doc' || fileExt === 'docx') {
                    icon = 'dashicons-media-document';
                } else {
                    icon = 'dashicons-media-default';
                }
                
                previewItem = $('<div class="clm-media-item">' +
                    '<span class="dashicons ' + icon + '"></span>' +
                    '<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>' +
                    '</div>');
                break;
                
            case 'audio_file':
                previewItem = $('<div class="clm-media-item">' +
                    '<span class="dashicons dashicons-format-audio"></span>' +
                    '<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>' +
                    '<div class="clm-audio-player">' +
                    '<audio controls src="' + attachment.url + '"></audio>' +
                    '</div>' +
                    '</div>');
                break;
                
            case 'midi_file':
                previewItem = $('<div class="clm-media-item">' +
                    '<span class="dashicons dashicons-format-audio"></span>' +
                    '<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>' +
                    '</div>');
                break;
                
            default:
                previewItem = $('<div class="clm-media-item">' +
                    '<span class="dashicons dashicons-media-default"></span>' +
                    '<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>' +
                    '</div>');
        }
        
        previewContainer.append(previewItem);
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
     * Album lyrics management
     */
    function initAlbumLyrics() {
        // Make lyrics sortable
        if ($.fn.sortable) {
            $('.clm-sortable-lyrics').sortable({
                placeholder: 'clm-sortable-placeholder',
                update: function(event, ui) {
                    // Update the order of lyrics if needed
                }
            });
        }
        
        // Add lyric to album
        $('.clm-add-lyric').on('click', function(e) {
            e.preventDefault();
            
            var dropdown = $('#clm_lyrics_dropdown');
            var lyricId = dropdown.val();
            var lyricTitle = dropdown.find('option:selected').text();
            
            if (!lyricId) {
                return;
            }
            
            // Check if lyric already exists in the list
            if ($('#clm-selected-lyrics li[data-id="' + lyricId + '"]').length) {
                alert(clm_admin_vars.text.already_added);
                return;
            }
            
            // Add lyric to the list
            var lyricItem = $('<li data-id="' + lyricId + '">' +
                '<input type="hidden" name="clm_lyrics[]" value="' + lyricId + '">' +
                lyricTitle +
                '<a href="#" class="clm-remove-lyric dashicons dashicons-no"></a>' +
                '</li>');
            
            $('#clm-selected-lyrics').append(lyricItem);
            
            // Reset dropdown
            dropdown.val('');
        });
        
        // Remove lyric from album (delegated event)
        $('#clm-selected-lyrics').on('click', '.clm-remove-lyric', function(e) {
            e.preventDefault();
            
            $(this).parent().remove();
        });
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
        // Initialize media uploader
        initMediaUploader();
        
        // Initialize album lyrics management
        initAlbumLyrics();
        
        // Initialize settings page
        initSettings();
        
        // Initialize analytics
        initAnalytics();
    });

})(jQuery);