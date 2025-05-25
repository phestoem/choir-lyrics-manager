jQuery(document).ready(function($) {
    'use strict';

    // Make selected tracks sortable
    if (typeof $.fn.sortable !== 'undefined') {
        $('#clm-selected-tracks-list').sortable({
            handle: '.clm-sortable-handle',
            placeholder: 'clm-track-sortable-placeholder',
            axis: 'y',
            update: function() {
                // Optional: could trigger an AJAX save for order immediately,
                // but usually order is saved with the post.
            }
        });
    }

    // Template for a selected track item (from the <script type="text/html"> block)
    var selectedTrackTemplate = wp.template('clm-selected-track-item');

    // Handle lyric search and adding
    var lyricSearchRequest = null;
    $('#clm-lyric-search-input').on('keyup input', function() {
        var searchTerm = $(this).val().toLowerCase();
        var $resultsContainer = $('#clm-lyric-search-results');
        $resultsContainer.html('').hide(); // Clear previous results

        if (searchTerm.length < 2) {
            return;
        }

        var availableLyrics = clmAlbumAdmin.all_lyrics.filter(function(lyric) {
            // Check if already selected
            if ($('#clm-selected-tracks-list .clm-selected-track-item[data-lyric-id="' + lyric.id + '"]').length > 0) {
                return false;
            }
            return lyric.title.toLowerCase().includes(searchTerm);
        });

        if (availableLyrics.length > 0) {
            var $ul = $('<ul></ul>');
            availableLyrics.forEach(function(lyric) {
                $('<li></li>')
                    .text(lyric.title)
                    .data('lyric', lyric)
                    .on('click', function() {
                        var lyricData = $(this).data('lyric');
                        var newItemHtml = selectedTrackTemplate(lyricData);
                        $('#clm-selected-tracks-list').append(newItemHtml);
                        $('#clm-lyric-search-input').val('');
                        $resultsContainer.html('').hide();
                        $('#clm-selected-tracks-list').sortable('refresh');
                        $('.clm-no-tracks-message').hide();
                    })
                    .appendTo($ul);
            });
            $resultsContainer.append($ul).show();
        } else {
            $resultsContainer.html('<p>' + clmAlbumAdmin.text.no_lyrics_found + '</p>').show();
        }
    });

    // Hide search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#clm-add-track-selector').length) {
            $('#clm-lyric-search-results').hide();
        }
    });

    // Handle removing a track
    $('#clm-album-tracks-container').on('click', '.clm-remove-track', function() {
        $(this).closest('.clm-selected-track-item').remove();
        if ($('#clm-selected-tracks-list .clm-selected-track-item').length === 0) {
            $('.clm-no-tracks-message').show();
        }
    });
});