<?php
/**
 * Template for displaying a single playlist
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Initialize playlists
$playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);

// Get playlist data
$playlist_data = $playlists->get_playlist(get_the_ID());

// If playlist not found or not accessible
if (!$playlist_data) {
    get_header();
    ?>
    <div class="clm-playlist-not-found">
        <h1><?php _e('Playlist Not Found', 'choir-lyrics-manager'); ?></h1>
        <p><?php _e('The requested playlist does not exist or you do not have permission to view it.', 'choir-lyrics-manager'); ?></p>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="clm-button"><?php _e('Back to Home', 'choir-lyrics-manager'); ?></a>
    </div>
    <?php
    get_footer();
    return;
}

// Extract data
$playlist = $playlist_data['playlist'];
$lyrics = $playlist_data['lyrics'];
$can_edit = $playlist_data['can_edit'];

get_header();
?>

<div class="clm-single-playlist-container">
    <div class="clm-playlist-header">
        <div class="clm-playlist-title-wrapper">
            <h1 class="clm-playlist-title"><?php echo get_the_title(); ?></h1>
            
            <div class="clm-playlist-meta">
                <span class="clm-playlist-author">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php 
                    $author_id = $playlist->post_author;
                    $author_display = get_the_author_meta('display_name', $author_id);
                    printf(__('Created by %s', 'choir-lyrics-manager'), esc_html($author_display)); 
                    ?>
                </span>
                
                <span class="clm-playlist-date">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo get_the_date(); ?>
                </span>
                
                <span class="clm-playlist-count">
                    <span class="dashicons dashicons-playlist-audio"></span>
                    <?php echo sprintf(_n('%s song', '%s songs', count($lyrics), 'choir-lyrics-manager'), number_format_i18n(count($lyrics))); ?>
                </span>
                
                <span class="clm-playlist-visibility <?php echo esc_attr($playlist_data['visibility']); ?>">
                    <span class="dashicons dashicons-<?php echo $playlist_data['visibility'] === 'public' ? 'visibility' : 'hidden'; ?>"></span>
                    <?php echo $playlist_data['visibility'] === 'public' ? __('Public', 'choir-lyrics-manager') : __('Private', 'choir-lyrics-manager'); ?>
                </span>
            </div>
        </div>
        
        <?php if ($can_edit): ?>
            <div class="clm-playlist-actions">
                <a href="<?php echo esc_url(home_url('/member-dashboard/playlists/')); ?>" class="clm-button">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to My Playlists', 'choir-lyrics-manager'); ?>
                </a>
                <button id="clm-edit-playlist-btn" class="clm-button">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Edit Playlist', 'choir-lyrics-manager'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($playlist->post_content)): ?>
        <div class="clm-playlist-description">
            <?php echo wpautop($playlist->post_content); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($lyrics)): ?>
        <div class="clm-playlist-empty">
            <p><?php _e('This playlist is empty.', 'choir-lyrics-manager'); ?></p>
            <?php if ($can_edit): ?>
                <a href="<?php echo esc_url(home_url('/lyrics/')); ?>" class="clm-button clm-primary-button">
                    <?php _e('Browse Lyrics to Add', 'choir-lyrics-manager'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="clm-playlist-contents">
            <h2 class="clm-playlist-songs-title"><?php _e('Songs in this Playlist', 'choir-lyrics-manager'); ?></h2>
            
            <div class="clm-playlist-controls">
                <button id="clm-play-all" class="clm-button clm-primary-button">
                    <span class="dashicons dashicons-controls-play"></span>
                    <?php _e('Play All', 'choir-lyrics-manager'); ?>
                </button>
                
                <?php if ($can_edit): ?>
                    <button id="clm-reorder-playlist" class="clm-button">
                        <span class="dashicons dashicons-sort"></span>
                        <?php _e('Reorder', 'choir-lyrics-manager'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <ul id="clm-playlist-items" class="clm-playlist-items" <?php echo $can_edit ? 'data-playlist-id="' . esc_attr($playlist->ID) . '"' : ''; ?>>
                <?php foreach ($lyrics as $index => $lyric): 
                    $composer = get_post_meta($lyric->ID, '_clm_composer', true);
                    $audio_id = get_post_meta($lyric->ID, '_clm_audio_file_id', true);
                    $has_audio = !empty($audio_id);
                ?>
                    <li class="clm-playlist-item" data-lyric-id="<?php echo esc_attr($lyric->ID); ?>">
                        <div class="clm-playlist-item-number"><?php echo esc_html($index + 1); ?></div>
                        
                        <div class="clm-playlist-item-details">
                            <h3 class="clm-playlist-item-title">
                                <a href="<?php echo esc_url(get_permalink($lyric->ID)); ?>"><?php echo esc_html($lyric->post_title); ?></a>
                            </h3>
                            
                            <?php if ($composer): ?>
                                <div class="clm-playlist-item-composer"><?php echo esc_html($composer); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($has_audio): 
                            $audio_url = wp_get_attachment_url($audio_id);
                        ?>
                            <div class="clm-playlist-item-controls">
                                <button class="clm-play-button" data-audio-url="<?php echo esc_url($audio_url); ?>">
                                    <span class="dashicons dashicons-controls-play"></span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($can_edit): ?>
                            <div class="clm-playlist-item-actions">
                                <?php if ($has_audio): ?>
                                    <button class="clm-playlist-item-play" data-audio-url="<?php echo esc_url($audio_url); ?>">
                                        <span class="dashicons dashicons-controls-play"></span>
                                    </button>
                                <?php endif; ?>
                                
                                <button class="clm-remove-from-playlist" data-lyric-id="<?php echo esc_attr($lyric->ID); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                                
                                <?php if ($can_edit): ?>
                                    <div class="clm-reorder-handle" style="display:none;">
                                        <span class="dashicons dashicons-move"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Audio Player (Fixed at bottom) -->
        <div id="clm-audio-player" class="clm-audio-player" style="display:none;">
            <div class="clm-audio-player-inner">
                <div class="clm-now-playing">
                    <div class="clm-now-playing-title"></div>
                    <div class="clm-now-playing-composer"></div>
                </div>
                
                <div class="clm-player-controls">
                    <button id="clm-player-prev" class="clm-player-control">
                        <span class="dashicons dashicons-controls-skipback"></span>
                    </button>
                    <button id="clm-player-play" class="clm-player-control">
                        <span class="dashicons dashicons-controls-play"></span>
                    </button>
                    <button id="clm-player-next" class="clm-player-control">
                        <span class="dashicons dashicons-controls-skipforward"></span>
                    </button>
                </div>
                
                <div class="clm-player-progress">
                    <div class="clm-progress-bar">
                        <div class="clm-progress-current"></div>
                    </div>
                    <div class="clm-player-times">
                        <span class="clm-current-time">0:00</span>
                        <span class="clm-duration">0:00</span>
                    </div>
                </div>
                
                <div class="clm-player-volume">
                    <button id="clm-player-mute" class="clm-player-control">
                        <span class="dashicons dashicons-volume-medium"></span>
                    </button>
                    <input type="range" id="clm-volume-slider" min="0" max="100" value="80">
                </div>
                
                <button id="clm-player-close" class="clm-player-control">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <audio id="clm-audio-element"></audio>
        </div>
    <?php endif; ?>

    <?php if ($can_edit): ?>
        <!-- Edit Playlist Modal -->
        <div id="clm-edit-playlist-modal" class="clm-modal" style="display:none;">
            <div class="clm-modal-content">
                <span class="clm-modal-close">&times;</span>
                <h3><?php _e('Edit Playlist', 'choir-lyrics-manager'); ?></h3>
                
 <form id="clm-edit-playlist-form">
                    <?php wp_nonce_field('clm_playlist_nonce', 'clm_playlist_nonce'); ?>
                    <input type="hidden" name="playlist_id" value="<?php echo esc_attr($playlist->ID); ?>">
                    
                    <div class="clm-form-field">
                        <label for="playlist_name"><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                        <input type="text" id="playlist_name" name="playlist_name" class="clm-input" value="<?php echo esc_attr($playlist->post_title); ?>" required>
                    </div>
                    
                    <div class="clm-form-field">
                        <label for="playlist_description"><?php _e('Description', 'choir-lyrics-manager'); ?></label>
                        <textarea id="playlist_description" name="playlist_description" class="clm-textarea" rows="4"><?php echo esc_textarea($playlist->post_content); ?></textarea>
                    </div>
                    
                    <div class="clm-form-field">
                        <label><?php _e('Visibility', 'choir-lyrics-manager'); ?></label>
                        <div class="clm-radio-group">
                            <label>
                                <input type="radio" name="playlist_visibility" value="private" <?php checked($playlist_data['visibility'], 'private'); ?>> 
                                <?php _e('Private', 'choir-lyrics-manager'); ?>
                            </label>
                            <label>
                                <input type="radio" name="playlist_visibility" value="public" <?php checked($playlist_data['visibility'], 'public'); ?>> 
                                <?php _e('Public', 'choir-lyrics-manager'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="clm-form-actions">
                        <button type="submit" class="clm-button clm-primary-button"><?php _e('Save Changes', 'choir-lyrics-manager'); ?></button>
                        <button type="button" class="clm-button clm-modal-cancel"><?php _e('Cancel', 'choir-lyrics-manager'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Remove Lyric Confirmation Modal -->
        <div id="clm-remove-lyric-modal" class="clm-modal" style="display:none;">
            <div class="clm-modal-content">
                <span class="clm-modal-close">&times;</span>
                <h3><?php _e('Remove from Playlist', 'choir-lyrics-manager'); ?></h3>
                
                <p><?php _e('Are you sure you want to remove this lyric from the playlist?', 'choir-lyrics-manager'); ?></p>
                
                <input type="hidden" id="clm-remove-lyric-id">
                
                <div class="clm-form-actions">
                    <button id="clm-confirm-remove-lyric" class="clm-button clm-danger-button"><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                    <button class="clm-button clm-modal-cancel"><?php _e('Cancel', 'choir-lyrics-manager'); ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Nonce for AJAX requests
    const nonce = '<?php echo wp_create_nonce('clm_playlist_nonce'); ?>';
    const playlistId = <?php echo json_encode($playlist->ID); ?>;
    
    // Audio player functionality
    const audioElement = document.getElementById('clm-audio-element');
    const audioPlayer = document.getElementById('clm-audio-player');
    const playButton = document.getElementById('clm-player-play');
    const prevButton = document.getElementById('clm-player-prev');
    const nextButton = document.getElementById('clm-player-next');
    const muteButton = document.getElementById('clm-player-mute');
    const closeButton = document.getElementById('clm-player-close');
    const volumeSlider = document.getElementById('clm-volume-slider');
    const progressBar = document.querySelector('.clm-progress-current');
    const currentTimeDisplay = document.querySelector('.clm-current-time');
    const durationDisplay = document.querySelector('.clm-duration');
    
    // Playlist data
    let currentTrackIndex = -1;
    const playlistItems = [];
    
    // Collect playlist items
    $('.clm-playlist-item').each(function() {
        const $item = $(this);
        const lyricId = $item.data('lyric-id');
        const title = $item.find('.clm-playlist-item-title a').text();
        const composer = $item.find('.clm-playlist-item-composer').text();
        const audioUrl = $item.find('.clm-play-button, .clm-playlist-item-play').data('audio-url');
        
        if (audioUrl) {
            playlistItems.push({
                lyricId: lyricId,
                title: title,
                composer: composer,
                audioUrl: audioUrl,
                element: $item[0]
            });
        }
    });
    
    // Audio player UI functions
    function updatePlayerInfo(item) {
        $('.clm-now-playing-title').text(item.title);
        $('.clm-now-playing-composer').text(item.composer);
        
        // Highlight playing item
        $('.clm-playlist-item').removeClass('playing');
        $(item.element).addClass('playing');
    }
    
    function playTrack(index) {
        if (index >= 0 && index < playlistItems.length) {
            currentTrackIndex = index;
            const item = playlistItems[index];
            
            audioElement.src = item.audioUrl;
            audioElement.play().then(() => {
                playButton.querySelector('.dashicons').classList.remove('dashicons-controls-play');
                playButton.querySelector('.dashicons').classList.add('dashicons-controls-pause');
                updatePlayerInfo(item);
                $(audioPlayer).fadeIn(300);
            }).catch(error => {
                console.error('Error playing audio:', error);
            });
        }
    }
    
    function togglePlay() {
        if (audioElement.paused) {
            audioElement.play();
            playButton.querySelector('.dashicons').classList.remove('dashicons-controls-play');
            playButton.querySelector('.dashicons').classList.add('dashicons-controls-pause');
        } else {
            audioElement.pause();
            playButton.querySelector('.dashicons').classList.remove('dashicons-controls-pause');
            playButton.querySelector('.dashicons').classList.add('dashicons-controls-play');
        }
    }
    
    // Play all button
    $('#clm-play-all').on('click', function() {
        if (playlistItems.length > 0) {
            playTrack(0);
        }
    });
    
    // Play individual track
    $('.clm-play-button, .clm-playlist-item-play').on('click', function() {
        const audioUrl = $(this).data('audio-url');
        const item = playlistItems.findIndex(item => item.audioUrl === audioUrl);
        if (item !== -1) {
            playTrack(item);
        }
    });
    
    // Player controls
    playButton.addEventListener('click', togglePlay);
    
    prevButton.addEventListener('click', function() {
        if (currentTrackIndex > 0) {
            playTrack(currentTrackIndex - 1);
        } else if (playlistItems.length > 0) {
            // Wrap to end of playlist
            playTrack(playlistItems.length - 1);
        }
    });
    
    nextButton.addEventListener('click', function() {
        if (currentTrackIndex < playlistItems.length - 1) {
            playTrack(currentTrackIndex + 1);
        } else if (playlistItems.length > 0) {
            // Wrap to beginning of playlist
            playTrack(0);
        }
    });
    
    closeButton.addEventListener('click', function() {
        audioElement.pause();
        $(audioPlayer).fadeOut(300);
    });
    
    muteButton.addEventListener('click', function() {
        audioElement.muted = !audioElement.muted;
        if (audioElement.muted) {
            muteButton.querySelector('.dashicons').classList.remove('dashicons-volume-medium');
            muteButton.querySelector('.dashicons').classList.add('dashicons-volume-off');
        } else {
            muteButton.querySelector('.dashicons').classList.remove('dashicons-volume-off');
            muteButton.querySelector('.dashicons').classList.add('dashicons-volume-medium');
        }
    });
    
    volumeSlider.addEventListener('input', function() {
        audioElement.volume = this.value / 100;
        if (this.value === '0') {
            muteButton.querySelector('.dashicons').classList.remove('dashicons-volume-medium');
            muteButton.querySelector('.dashicons').classList.add('dashicons-volume-off');
        } else {
            muteButton.querySelector('.dashicons').classList.remove('dashicons-volume-off');
            muteButton.querySelector('.dashicons').classList.add('dashicons-volume-medium');
        }
    });
    
    // Update progress bar and time display
    audioElement.addEventListener('timeupdate', function() {
        const duration = audioElement.duration;
        const currentTime = audioElement.currentTime;
        
        if (duration) {
            // Update progress bar
            const progressPercent = (currentTime / duration) * 100;
            progressBar.style.width = progressPercent + '%';
            
            // Update time displays
            currentTimeDisplay.textContent = formatTime(currentTime);
            durationDisplay.textContent = formatTime(duration);
        }
    });
    
    // Format time in MM:SS
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return minutes + ':' + (secs < 10 ? '0' : '') + secs;
    }
    
    // Play next track when current one ends
    audioElement.addEventListener('ended', function() {
        if (currentTrackIndex < playlistItems.length - 1) {
            playTrack(currentTrackIndex + 1);
        } else {
            // Stop at end of playlist
            playButton.querySelector('.dashicons').classList.remove('dashicons-controls-pause');
            playButton.querySelector('.dashicons').classList.add('dashicons-controls-play');
        }
    });
    
    // Progress bar seeking
    $('.clm-progress-bar').on('click', function(e) {
        const progressBar = $(this);
        const clickPosition = e.pageX - progressBar.offset().left;
        const width = progressBar.width();
        const duration = audioElement.duration;
        
        const seekTime = (clickPosition / width) * duration;
        audioElement.currentTime = seekTime;
    });
    
    <?php if ($can_edit): ?>
    // Edit playlist modal
    $('#clm-edit-playlist-btn').on('click', function() {
        $('#clm-edit-playlist-modal').show();
    });
    
    // Edit playlist form submission
    $('#clm-edit-playlist-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'clm_update_playlist',
            nonce: nonce,
            playlist_id: playlistId,
            playlist_name: $('#playlist_name').val(),
            playlist_description: $('#playlist_description').val(),
            playlist_visibility: $('input[name="playlist_visibility"]:checked').val()
        };
        
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('<?php echo esc_js(__('Saving...', 'choir-lyrics-manager')); ?>');
        
        $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error updating playlist.', 'choir-lyrics-manager')); ?>');
                    submitButton.prop('disabled', false).text('<?php echo esc_js(__('Save Changes', 'choir-lyrics-manager')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error connecting to server.', 'choir-lyrics-manager')); ?>');
                submitButton.prop('disabled', false).text('<?php echo esc_js(__('Save Changes', 'choir-lyrics-manager')); ?>');
            }
        });
    });
    
    // Remove lyric from playlist
    $('.clm-remove-from-playlist').on('click', function() {
        const lyricId = $(this).data('lyric-id');
        $('#clm-remove-lyric-id').val(lyricId);
        $('#clm-remove-lyric-modal').show();
    });
    
    $('#clm-confirm-remove-lyric').on('click', function() {
        const lyricId = $('#clm-remove-lyric-id').val();
        
        $(this).prop('disabled', true).text('<?php echo esc_js(__('Removing...', 'choir-lyrics-manager')); ?>');
        
        $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'clm_remove_from_playlist',
                nonce: nonce,
                playlist_id: playlistId,
                lyric_id: lyricId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error removing lyric from playlist.', 'choir-lyrics-manager')); ?>');
                    $('#clm-confirm-remove-lyric').prop('disabled', false).text('<?php echo esc_js(__('Remove', 'choir-lyrics-manager')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error connecting to server.', 'choir-lyrics-manager')); ?>');
                $('#clm-confirm-remove-lyric').prop('disabled', false).text('<?php echo esc_js(__('Remove', 'choir-lyrics-manager')); ?>');
            }
        });
    });
    
    // Initialize Sortable for reordering
    if (typeof $.fn.sortable !== 'undefined') {
        const $playlistItems = $('#clm-playlist-items');
        
        $('#clm-reorder-playlist').on('click', function() {
            const isReordering = $(this).hasClass('active');
            
            if (isReordering) {
                // Stop reordering
                $(this).removeClass('active').html('<span class="dashicons dashicons-sort"></span> <?php echo esc_js(__('Reorder', 'choir-lyrics-manager')); ?>');
                $('.clm-reorder-handle').hide();
                $playlistItems.sortable('destroy');
                
                // Get the new order
                const newOrder = [];
                $playlistItems.find('.clm-playlist-item').each(function() {
                    newOrder.push($(this).data('lyric-id'));
                });
                
                // Update order via AJAX
                $.ajax({
                    url: clm_vars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clm_reorder_playlist',
                        nonce: nonce,
                        playlist_id: playlistId,
                        lyric_order: newOrder
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update item numbers
                            $playlistItems.find('.clm-playlist-item').each(function(index) {
                                $(this).find('.clm-playlist-item-number').text(index + 1);
                            });
                        } else {
                            alert(response.data.message || '<?php echo esc_js(__('Error updating playlist order.', 'choir-lyrics-manager')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Error connecting to server.', 'choir-lyrics-manager')); ?>');
                    }
                });
            } else {
                // Start reordering
                $(this).addClass('active').html('<span class="dashicons dashicons-saved"></span> <?php echo esc_js(__('Save Order', 'choir-lyrics-manager')); ?>');
                $('.clm-reorder-handle').show();
                
                $playlistItems.sortable({
                    handle: '.clm-reorder-handle',
                    placeholder: 'clm-playlist-item-placeholder',
                    start: function(e, ui) {
                        ui.placeholder.height(ui.item.outerHeight());
                    }
                });
            }
        });
    }
    <?php endif; ?>
    
    // Modal close buttons
    $('.clm-modal-close, .clm-modal-cancel').on('click', function() {
        $(this).closest('.clm-modal').hide();
    });
    
    // Close modal if clicked outside content
    $('.clm-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script>

<style>
/* Single Playlist Template Styles */
.clm-single-playlist-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.clm-playlist-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.clm-playlist-title {
    font-size: 2em;
    margin: 0 0 15px;
    color: #333;
}

.clm-playlist-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    color: #666;
    font-size: 0.9em;
}

.clm-playlist-meta > span {
    display: flex;
    align-items: center;
}

.clm-playlist-meta .dashicons {
    margin-right: 5px;
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.clm-playlist-visibility {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.85em;
}

.clm-playlist-visibility.public {
    background-color: #e3f7e8;
    color: #2e7d32;
}

.clm-playlist-visibility.private {
    background-color: #f5f5f5;
    color: #666;
}

.clm-playlist-actions {
    display: flex;
    gap: 10px;
}

.clm-playlist-description {
    background-color: #f9f9f9;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.clm-playlist-empty {
    text-align: center;
    padding: 50px 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.clm-playlist-songs-title {
    margin-bottom: 20px;
    font-size: 1.5em;
}

.clm-playlist-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.clm-playlist-items {
    list-style: none;
    margin: 0;
    padding: 0;
}

.clm-playlist-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    background-color: #fff;
    transition: background-color 0.2s ease;
}

.clm-playlist-item:hover {
    background-color: #f9f9f9;
}

.clm-playlist-item.playing {
    background-color: #e8f0fe;
}

.clm-playlist-item-placeholder {
    background-color: #f0f0f0;
    border: 2px dashed #ccc;
}

.clm-playlist-item-number {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
    border-radius: 50%;
    margin-right: 15px;
    font-weight: 500;
    flex-shrink: 0;
}

.clm-playlist-item-details {
    flex: 1;
    min-width: 0;
}

.clm-playlist-item-title {
    margin: 0 0 5px;
    font-size: 1.1em;
}

.clm-playlist-item-title a {
    color: #333;
    text-decoration: none;
}

.clm-playlist-item-title a:hover {
    color: #1a73e8;
}

.clm-playlist-item-composer {
    color: #666;
    font-size: 0.9em;
}

.clm-playlist-item-controls {
    margin-right: 15px;
}

.clm-play-button,
.clm-playlist-item-play {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #1a73e8;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.clm-play-button:hover,
.clm-playlist-item-play:hover {
    background-color: #1765cc;
}

.clm-playlist-item-actions {
    display: flex;
    gap: 10px;
}

.clm-remove-from-playlist {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #f5f5f5;
    color: #d32f2f;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #ddd;
    cursor: pointer;
    transition: all 0.2s ease;
}

.clm-remove-from-playlist:hover {
    background-color: #ffebee;
    border-color: #ffcdd2;
}

.clm-reorder-handle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #f5f5f5;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #ddd;
    cursor: grab;
}

.clm-reorder-handle:active {
    cursor: grabbing;
}

/* Audio Player Styles */
.clm-audio-player {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #1a73e8;
    color: white;
    padding: 10px 0;
    z-index: 1000;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.clm-audio-player-inner {
    display: flex;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.clm-now-playing {
    flex: 1;
    min-width: 0;
    margin-right: 20px;
}

.clm-now-playing-title {
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.clm-now-playing-composer {
    font-size: 0.85em;
    opacity: 0.8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.clm-player-controls {
    display: flex;
    gap: 10px;
    margin: 0 20px;
}

.clm-player-control {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: rgba(255,255,255,0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.clm-player-control:hover {
    background-color: rgba(255,255,255,0.3);
}

.clm-player-progress {
    flex: 2;
    margin: 0 20px;
}

.clm-progress-bar {
    width: 100%;
    height: 4px;
    background-color: rgba(255,255,255,0.2);
    border-radius: 2px;
    position: relative;
    cursor: pointer;
}

.clm-progress-current {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background-color: white;
    border-radius: 2px;
    width: 0%;
}

.clm-player-times {
    display: flex;
    justify-content: space-between;
    font-size: 0.8em;
    margin-top: 5px;
}

.clm-player-volume {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: 20px;
}

#clm-volume-slider {
    width: 80px;
}

#clm-player-close {
    margin-left: 20px;
}

/* Form & Modal Styles - same as in playlists.php */
.clm-button {
    padding: 8px 16px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: #f5f5f5;
    color: #333;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.clm-button .dashicons {
    margin-right: 5px;
}

.clm-primary-button {
    background-color: #1a73e8;
    color: white;
    border-color: #1a73e8;
}

.clm-primary-button:hover {
    background-color: #1765cc;
    border-color: #1765cc;
}

.clm-danger-button {
    background-color: #d32f2f;
    color: white;
    border-color: #d32f2f;
}

.clm-danger-button:hover {
    background-color: #b71c1c;
    border-color: #b71c1c;
}

/* Modal Styles - same as in playlists.php */
.clm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.clm-modal-content {
    background-color: #fff;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    padding: 20px;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}

.clm-modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 20px;
    cursor: pointer;
    color: #666;
}

.clm-form-field {
    margin-bottom: 15px;
}

.clm-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.clm-input, 
.clm-textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.clm-radio-group {
    display: flex;
    gap: 15px;
}

.clm-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .clm-playlist-header {
        flex-direction: column;
    }
    
    .clm-playlist-actions {
        margin-top: 15px;
    }
    
    .clm-audio-player-inner {
        flex-direction: column;
        padding: 10px 15px;
    }
    
    .clm-now-playing {
        margin-right: 0;
        margin-bottom: 10px;
        text-align: center;
    }
    
    .clm-player-controls {
        margin: 10px 0;
    }
    
    .clm-player-progress {
        width: 100%;
        margin: 10px 0;
    }
    
    .clm-player-volume {
        margin-left: 0;
        margin-top: 10px;
    }
    
    #clm-player-close {
        position: absolute;
        top: 10px;
        right: 10px;
        margin: 0;
    }
}
</style>

<?php get_footer(); ?>