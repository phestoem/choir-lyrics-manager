<?php
/**
 * Member dashboard - Playlists tab
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Initialize playlists
$playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);

// Get user playlists
$user_playlists = $playlists->get_user_playlists();

// Get nonce
$nonce = wp_create_nonce('clm_playlist_nonce');
?>

<div class="clm-member-playlists-dashboard">
    <div class="clm-dashboard-header">
        <h2><?php _e('My Playlists', 'choir-lyrics-manager'); ?></h2>
        <button id="clm-create-playlist-btn" class="clm-button clm-primary-button">
            <span class="dashicons dashicons-plus"></span> <?php _e('Create New Playlist', 'choir-lyrics-manager'); ?>
        </button>
    </div>
    
    <!-- Create Playlist Form (Hidden by default) -->
    <div id="clm-create-playlist-form" style="display:none;" class="clm-form-wrapper">
        <h3><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></h3>
        
        <div class="clm-form-field">
            <label for="clm-new-playlist-name"><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
            <input type="text" id="clm-new-playlist-name" class="clm-input" required>
        </div>
        
        <div class="clm-form-field">
            <label for="clm-new-playlist-description"><?php _e('Description (optional)', 'choir-lyrics-manager'); ?></label>
            <textarea id="clm-new-playlist-description" class="clm-textarea" rows="3"></textarea>
        </div>
        
        <div class="clm-form-field">
            <label><?php _e('Visibility', 'choir-lyrics-manager'); ?></label>
            <div class="clm-radio-group">
                <label>
                    <input type="radio" name="clm-new-playlist-visibility" value="private" checked> 
                    <?php _e('Private', 'choir-lyrics-manager'); ?>
                </label>
                <label>
                    <input type="radio" name="clm-new-playlist-visibility" value="public"> 
                    <?php _e('Public', 'choir-lyrics-manager'); ?>
                </label>
            </div>
        </div>
        
        <div class="clm-form-actions">
            <button id="clm-submit-new-playlist" class="clm-button clm-primary-button">
                <?php _e('Create Playlist', 'choir-lyrics-manager'); ?>
            </button>
            <button id="clm-cancel-new-playlist" class="clm-button">
                <?php _e('Cancel', 'choir-lyrics-manager'); ?>
            </button>
        </div>
    </div>
    
    <!-- Playlists Grid -->
    <?php if (!empty($user_playlists)): ?>
        <div class="clm-playlists-grid">
            <?php foreach ($user_playlists as $playlist): 
                $playlist_id = $playlist->ID;
                $visibility = get_post_meta($playlist_id, '_clm_playlist_visibility', true);
                $lyrics_count = count($playlists->get_playlist_lyrics($playlist_id));
                $is_public = $visibility === 'public';
            ?>
                <div class="clm-playlist-card" data-playlist-id="<?php echo esc_attr($playlist_id); ?>">
                    <div class="clm-playlist-card-header">
                        <h3 class="clm-playlist-title"><?php echo esc_html($playlist->post_title); ?></h3>
                        <div class="clm-playlist-badges">
                            <span class="clm-playlist-visibility-badge <?php echo $is_public ? 'public' : 'private'; ?>">
                                <span class="dashicons <?php echo $is_public ? 'dashicons-visibility' : 'dashicons-hidden'; ?>"></span>
                                <?php echo $is_public ? esc_html__('Public', 'choir-lyrics-manager') : esc_html__('Private', 'choir-lyrics-manager'); ?>
                            </span>
                            <span class="clm-playlist-count-badge">
                                <span class="dashicons dashicons-playlist-audio"></span>
                                <?php echo sprintf(_n('%s song', '%s songs', $lyrics_count, 'choir-lyrics-manager'), number_format_i18n($lyrics_count)); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($playlist->post_content)): ?>
                        <div class="clm-playlist-description">
                            <?php echo wp_kses_post(wpautop($playlist->post_content)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="clm-playlist-card-actions">
                        <a href="<?php echo esc_url(get_permalink($playlist_id)); ?>" class="clm-button clm-view-playlist">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('View', 'choir-lyrics-manager'); ?>
                        </a>
                        <button class="clm-button clm-edit-playlist" data-playlist-id="<?php echo esc_attr($playlist_id); ?>">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Edit', 'choir-lyrics-manager'); ?>
                        </button>
                        <button class="clm-button clm-share-playlist" data-playlist-id="<?php echo esc_attr($playlist_id); ?>">
                            <span class="dashicons dashicons-share"></span>
                            <?php _e('Share', 'choir-lyrics-manager'); ?>
                        </button>
                        <button class="clm-button clm-delete-playlist" data-playlist-id="<?php echo esc_attr($playlist_id); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Delete', 'choir-lyrics-manager'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="clm-empty-state">
            <div class="clm-empty-icon">
                <span class="dashicons dashicons-playlist-audio"></span>
            </div>
            <h3><?php _e('No Playlists Yet', 'choir-lyrics-manager'); ?></h3>
            <p><?php _e('Create your first playlist to organize your favorite choir lyrics.', 'choir-lyrics-manager'); ?></p>
            <button id="clm-create-first-playlist" class="clm-button clm-primary-button">
                <?php _e('Create Your First Playlist', 'choir-lyrics-manager'); ?>
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Edit Playlist Modal (Hidden by default) -->
    <div id="clm-edit-playlist-modal" class="clm-modal" style="display:none;">
        <div class="clm-modal-content">
            <span class="clm-modal-close">&times;</span>
            <h3><?php _e('Edit Playlist', 'choir-lyrics-manager'); ?></h3>
            
            <input type="hidden" id="clm-edit-playlist-id">
            
            <div class="clm-form-field">
                <label for="clm-edit-playlist-name"><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm-edit-playlist-name" class="clm-input" required>
            </div>
            
            <div class="clm-form-field">
                <label for="clm-edit-playlist-description"><?php _e('Description', 'choir-lyrics-manager'); ?></label>
                <textarea id="clm-edit-playlist-description" class="clm-textarea" rows="3"></textarea>
            </div>
            
            <div class="clm-form-field">
                <label><?php _e('Visibility', 'choir-lyrics-manager'); ?></label>
                <div class="clm-radio-group">
                    <label>
                        <input type="radio" name="clm-edit-playlist-visibility" value="private"> 
                        <?php _e('Private', 'choir-lyrics-manager'); ?>
                    </label>
                    <label>
                        <input type="radio" name="clm-edit-playlist-visibility" value="public"> 
                        <?php _e('Public', 'choir-lyrics-manager'); ?>
                    </label>
                </div>
            </div>
            
            <div class="clm-form-actions">
                <button id="clm-save-playlist-changes" class="clm-button clm-primary-button">
                    <?php _e('Save Changes', 'choir-lyrics-manager'); ?>
                </button>
                <button class="clm-button clm-modal-cancel">
                    <?php _e('Cancel', 'choir-lyrics-manager'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Share Playlist Modal (Hidden by default) -->
    <div id="clm-share-playlist-modal" class="clm-modal" style="display:none;">
        <div class="clm-modal-content">
            <span class="clm-modal-close">&times;</span>
            <h3><?php _e('Share Playlist', 'choir-lyrics-manager'); ?></h3>
            
            <p><?php _e('Share this playlist with others:', 'choir-lyrics-manager'); ?></p>
            
            <div id="clm-share-playlist-private-warning">
                <p class="clm-warning">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('This playlist is currently private. Making it public will allow anyone with the link to view it.', 'choir-lyrics-manager'); ?>
                </p>
                <button id="clm-make-playlist-public" class="clm-button clm-primary-button">
                    <?php _e('Make Public & Generate Link', 'choir-lyrics-manager'); ?>
                </button>
            </div>
            
            <div id="clm-share-playlist-public-options" style="display:none;">
                <div class="clm-form-field">
                    <label for="clm-share-playlist-url"><?php _e('Share Link', 'choir-lyrics-manager'); ?></label>
                    <div class="clm-copy-url-field">
                        <input type="text" id="clm-share-playlist-url" class="clm-input" readonly>
                        <button id="clm-copy-playlist-url" class="clm-button">
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>
                    </div>
                </div>
                
                <div class="clm-social-share-buttons">
                    <a href="#" id="clm-share-facebook" class="clm-social-share-btn facebook">
                        <span class="dashicons dashicons-facebook"></span> Facebook
                    </a>
                    <a href="#" id="clm-share-twitter" class="clm-social-share-btn twitter">
                        <span class="dashicons dashicons-twitter"></span> Twitter
                    </a>
                    <a href="#" id="clm-share-email" class="clm-social-share-btn email">
                        <span class="dashicons dashicons-email"></span> Email
                    </a>
                </div>
            </div>
            
            <div class="clm-form-actions">
                <button class="clm-button clm-modal-cancel">
                    <?php _e('Close', 'choir-lyrics-manager'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="clm-delete-playlist-modal" class="clm-modal" style="display:none;">
        <div class="clm-modal-content">
            <span class="clm-modal-close">&times;</span>
            <h3><?php _e('Delete Playlist', 'choir-lyrics-manager'); ?></h3>
            
            <p class="clm-warning">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('Are you sure you want to delete this playlist? This action cannot be undone.', 'choir-lyrics-manager'); ?>
            </p>
            
            <div class="clm-form-actions">
                <button id="clm-confirm-delete-playlist" class="clm-button clm-danger-button">
                    <?php _e('Delete Permanently', 'choir-lyrics-manager'); ?>
                </button>
                <button class="clm-button clm-modal-cancel">
                    <?php _e('Cancel', 'choir-lyrics-manager'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Nonce for AJAX requests
    const nonce = '<?php echo $nonce; ?>';
    
    // Create Playlist Form Toggle
    $('#clm-create-playlist-btn, #clm-create-first-playlist').on('click', function() {
        $('#clm-create-playlist-form').slideDown();
    });
    
    $('#clm-cancel-new-playlist').on('click', function() {
        $('#clm-create-playlist-form').slideUp();
    });
    
    // Create New Playlist
    $('#clm-submit-new-playlist').on('click', function() {
        const name = $('#clm-new-playlist-name').val();
        const description = $('#clm-new-playlist-description').val();
        const visibility = $('input[name="clm-new-playlist-visibility"]:checked').val();
        
        if (!name) {
            alert('<?php echo esc_js(__('Please enter a playlist name.', 'choir-lyrics-manager')); ?>');
            return;
        }
        
        $(this).prop('disabled', true).text('<?php echo esc_js(__('Creating...', 'choir-lyrics-manager')); ?>');
        
        $.ajax({
            url: clm_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'clm_create_playlist',
                nonce: nonce,
                playlist_name: name,
                playlist_description: description,
                playlist_visibility: visibility
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error creating playlist.', 'choir-lyrics-manager')); ?>');
                    $('#clm-submit-new-playlist').prop('disabled', false).text('<?php echo esc_js(__('Create Playlist', 'choir-lyrics-manager')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error connecting to server.', 'choir-lyrics-manager')); ?>');
                $('#clm-submit-new-playlist').prop('disabled', false).text('<?php echo esc_js(__('Create Playlist', 'choir-lyrics-manager')); ?>');
            }
        });
    });
    
    // Edit Playlist
    $('.clm-edit-playlist').on('click', function() {
        const playlistId = $(this).data('playlist-id');
        const playlistCard = $(this).closest('.clm-playlist-card');
        const title = playlistCard.find('.clm-playlist-title').text().trim();
        const description = playlistCard.find('.clm-playlist-description').text().trim();
        const isPublic = playlistCard.find('.clm-playlist-visibility-badge').hasClass('public');
        
        // Populate the edit form
        $('#clm-edit-playlist-id').val(playlistId);
        $('#clm-edit-playlist-name').val(title);
        $('#clm-edit-playlist-description').val(description);
        $(`input[name="clm-edit-playlist-visibility"][value="${isPublic ? 'public' : 'private'}"]`).prop('checked', true);
        
        // Show the modal
        $('#clm-edit-playlist-modal').show();
    });
    
    // Share Playlist
    $('.clm-share-playlist').on('click', function() {
        const playlistId = $(this).data('playlist-id');
        const playlistCard = $(this).closest('.clm-playlist-card');
        const isPublic = playlistCard.find('.clm-playlist-visibility-badge').hasClass('public');
        
        // Store the playlist ID for use in share functionality
        $('#clm-share-playlist-modal').data('playlist-id', playlistId);
        
        // Show appropriate options based on visibility
        if (isPublic) {
            $('#clm-share-playlist-private-warning').hide();
            $('#clm-share-playlist-public-options').show();
            
            // Populate the share URL
            const shareUrl = '<?php echo esc_url(site_url('/')); ?>?p=' + playlistId;
            $('#clm-share-playlist-url').val(shareUrl);
            
            // Set up share links
            const title = playlistCard.find('.clm-playlist-title').text().trim();
            const encodedUrl = encodeURIComponent(shareUrl);
            const encodedTitle = encodeURIComponent(title);
            
            $('#clm-share-facebook').attr('href', `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`);
            $('#clm-share-twitter').attr('href', `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`);
            $('#clm-share-email').attr('href', `mailto:?subject=${encodedTitle}&body=${encodedUrl}`);
        } else {
            $('#clm-share-playlist-private-warning').show();
            $('#clm-share-playlist-public-options').hide();
        }
        
        // Show the modal
        $('#clm-share-playlist-modal').show();
    });
    
    // Make Playlist Public & Generate Share Link (continued)
$('#clm-make-playlist-public').on('click', function() {
    const playlistId = $('#clm-share-playlist-modal').data('playlist-id');
    
    $(this).prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'choir-lyrics-manager')); ?>');
    
    $.ajax({
        url: clm_vars.ajaxurl,
        type: 'POST',
        data: {
            action: 'clm_share_playlist',
            nonce: nonce,
            playlist_id: playlistId
        },
        success: function(response) {
            if (response.success) {
                // Hide the warning and show sharing options
                $('#clm-share-playlist-private-warning').hide();
                $('#clm-share-playlist-public-options').show();
                
                // Update the share URL
                $('#clm-share-playlist-url').val(response.data.share_url);
                
                // Set up share links
                const shareUrl = response.data.share_url;
                const title = $('.clm-playlist-card[data-playlist-id="'+playlistId+'"] .clm-playlist-title').text().trim();
                const encodedUrl = encodeURIComponent(shareUrl);
                const encodedTitle = encodeURIComponent(title);
                
                $('#clm-share-facebook').attr('href', `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`);
                $('#clm-share-twitter').attr('href', `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`);
                $('#clm-share-email').attr('href', `mailto:?subject=${encodedTitle}&body=${encodedUrl}`);
                
                // Update the visibility badge in the UI without refreshing
                $('.clm-playlist-card[data-playlist-id="'+playlistId+'"] .clm-playlist-visibility-badge')
                    .removeClass('private')
                    .addClass('public')
                    .html('<span class="dashicons dashicons-visibility"></span> <?php echo esc_js(__('Public', 'choir-lyrics-manager')); ?>');
            } else {
                alert(response.data.message || '<?php echo esc_js(__('Error sharing playlist.', 'choir-lyrics-manager')); ?>');
            }
            
            $('#clm-make-playlist-public').prop('disabled', false).text('<?php echo esc_js(__('Make Public & Generate Link', 'choir-lyrics-manager')); ?>');
        },
        error: function() {
            alert('<?php echo esc_js(__('Error connecting to server.', 'choir-lyrics-manager')); ?>');
            $('#clm-make-playlist-public').prop('disabled', false).text('<?php echo esc_js(__('Make Public & Generate Link', 'choir-lyrics-manager')); ?>');
        }
    });
});

// Copy Share URL to clipboard
$('#clm-copy-playlist-url').on('click', function() {
    const urlField = document.getElementById('clm-share-playlist-url');
    urlField.select();
    document.execCommand('copy');
    
    // Visual feedback
    $(this).html('<span class="dashicons dashicons-yes"></span>');
    setTimeout(() => {
        $(this).html('<span class="dashicons dashicons-clipboard"></span>');
    }, 2000);
});

// Save Playlist Changes
$('#clm-save-playlist-changes').on('click', function() {
    const playlistId = $('#clm-edit-playlist-id').val();
    const name = $('#clm-edit-playlist-name').val();
    const description = $('#clm-edit-playlist-description').val();
    const visibility = $('input[name="clm-edit-playlist-visibility"]:checked').val();
    
    if (!name) {
        alert('<?php echo esc_js(__('Please enter a playlist name.', 'choir-lyrics-manager')); ?>');
        return;
    }
    
    $(this).prop('disabled', true).text('<?php echo esc_js(__('Saving...', 'choir-lyrics-manager')); ?>');
    
    $.ajax({
        url: clm_vars.ajaxurl,
        type: 'POST',
        data: {
            action: 'clm_update_playlist',
            nonce: nonce,
            playlist_id: playlistId,
            playlist_name: name,
            playlist_description: description,
            playlist_visibility: visibility
        },
        success: function(response) {
            if (response.success) {
                // Update the UI
                const playlistCard = $('.clm-playlist-card[data-playlist-id="'+playlistId+'"]');
                playlistCard.find('.clm-playlist-title').text(name);
                
                if (description) {
                    if (playlistCard.find('.clm-playlist-description').length) {
                        playlistCard.find('.clm-playlist-description').html('<p>' + description + '</p>');
                    } else {
                        playlistCard.find('.clm-playlist-card-header').after('<div class="clm-playlist-description"><p>' + description + '</p></div>');
                    }
                } else {
                    playlistCard.find('.clm-playlist-description').remove();
                }
                
                // Update visibility badge
                const isPublic = visibility === 'public';
                playlistCard.find('.clm-playlist-visibility-badge')
                    .removeClass('public private')
                    .addClass(isPublic ? 'public' : 'private')
                    .html('<span class="dashicons dashicons-' + (isPublic ? 'visibility' : 'hidden') + '"></span> ' + 
                          (isPublic ? '<?php echo esc_js(__('Public', 'choir-lyrics-manager')); ?>' : '<?php echo esc_js(__('Private', 'choir-lyrics-manager')); ?>'));
                
                // Close the modal
                $('#clm-edit-playlist-modal').hide();
            } else {
                alert(response.data.message || '<?php echo esc_js(__('Error updating playlist.', 'choir-lyrics-manager')); ?>');
            }
            
            $('#clm-save-playlist-changes').prop('disabled', false).text('<?php echo esc_js(__('Save Changes', 'choir-lyrics-manager')); ?>');
        },
        error: function() {
            alert('<?php echo esc_js(__('Error connecting to server.', 'choir-lyrics-manager')); ?>');
            $('#clm-save-playlist-changes').prop('disabled', false).text('<?php echo esc_js(__('Save Changes', 'choir-lyrics-manager')); ?>');
        }
    });
});

// Delete Playlist
$('.clm-delete-playlist').on('click', function() {
    const playlistId = $(this).data('playlist-id');
    $('#clm-delete-playlist-modal').data('playlist-id', playlistId).show();
});

// Confirm Delete Playlist
$('#clm-confirm-delete-playlist').on('click', function() {
    const playlistId = $('#clm-delete-playlist-modal').data('playlist-id');
    
    $(this).prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'choir-lyrics-manager')); ?>');
    
    $.ajax({
        url: clm_vars.ajaxurl,
        type: 'POST',
        data: {
            action: 'clm_delete_playlist',
            nonce: nonce,
            playlist_id: playlistId
        },
        success: function(response) {
            if (response.success) {
                // Remove the playlist from the UI
                $('.clm-playlist-card[data-playlist-id="'+playlistId+'"]').fadeOut(300, function() {
                    $(this).remove();
                    
                    // If no playlists left, show empty state
                    if ($('.clm-playlist-card').length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                });
                
                // Close the modal
                $('#clm-delete-playlist-modal').hide();
            } else {
                alert(response.data.message || '<?php echo esc_js(__('Error deleting playlist.', 'choir-lyrics-manager')); ?>');
                $('#clm-confirm-delete-playlist').prop('disabled', false).text('<?php echo esc_js(__('Delete Permanently', 'choir-lyrics-manager')); ?>');
            }
        },
        error: function() {
            alert('<?php echo esc_js(__('Error connecting to server.', 'choir-lyrics-manager')); ?>');
            $('#clm-confirm-delete-playlist').prop('disabled', false).text('<?php echo esc_js(__('Delete Permanently', 'choir-lyrics-manager')); ?>');
        }
    });
});

// Modal Close Buttons
$('.clm-modal-close, .clm-modal-cancel').on('click', function() {
    $(this).closest('.clm-modal').hide();
});

// Close modal if clicked outside content
$('.clm-modal').on('click', function(e) {
    if (e.target === this) {
        $(this).hide();
    }
});

// Open links in new tab
$('.clm-social-share-btn').on('click', function(e) {
    e.preventDefault();
    window.open($(this).attr('href'), '_blank', 'width=600,height=400');
});
});
</script>

<style>
/* Playlists Dashboard Styles */
.clm-member-playlists-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.clm-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.clm-playlists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.clm-playlist-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.clm-playlist-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.clm-playlist-card-header {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.clm-playlist-title {
    margin: 0 0 10px;
    font-size: 1.2em;
    color: #333;
}

.clm-playlist-badges {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.clm-playlist-visibility-badge,
.clm-playlist-count-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: 500;
}

.clm-playlist-visibility-badge.public {
    background-color: #e3f7e8;
    color: #2e7d32;
}

.clm-playlist-visibility-badge.private {
    background-color: #f5f5f5;
    color: #666;
}

.clm-playlist-count-badge {
    background-color: #e8f0fe;
    color: #1a73e8;
}

.clm-playlist-badges .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    margin-right: 5px;
}

.clm-playlist-description {
    padding: 15px;
    color: #666;
    font-size: 0.9em;
    border-bottom: 1px solid #f0f0f0;
}

.clm-playlist-description p {
    margin: 0 0 10px;
}

.clm-playlist-description p:last-child {
    margin-bottom: 0;
}

.clm-playlist-card-actions {
    display: flex;
    padding: 15px;
    gap: 8px;
    flex-wrap: wrap;
}

.clm-playlist-card-actions .clm-button {
    flex: 1;
    min-width: 0;
    padding: 8px;
    font-size: 0.85em;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: #f8f8f8;
    color: #555;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.clm-playlist-card-actions .clm-button:hover {
    background: #f0f0f0;
    border-color: #ccc;
}

.clm-playlist-card-actions .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    margin-right: 5px;
}

.clm-delete-playlist {
    color: #d32f2f !important;
}

.clm-delete-playlist:hover {
    background: #ffebee !important;
    border-color: #ffcdd2 !important;
}

/* Form Styles */
.clm-form-wrapper {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    border: 1px solid #eee;
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

.clm-button {
    padding: 8px 16px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: #f5f5f5;
    color: #333;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
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

/* Modal Styles */
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

.clm-warning {
    background-color: #fff3e0;
    color: #e65100;
    padding: 15px;
    border-radius: 4px;
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
}

.clm-warning .dashicons {
    color: #ff6d00;
    margin-right: 10px;
    font-size: 18px;
}

/* Share Modal Styles */
.clm-copy-url-field {
    display: flex;
}

.clm-copy-url-field input {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.clm-copy-url-field button {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left: none;
}

.clm-social-share-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.clm-social-share-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border-radius: 4px;
    color: white;
    text-decoration: none;
    font-size: 14px;
}

.clm-social-share-btn .dashicons {
    margin-right: 5px;
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.clm-social-share-btn.facebook {
    background-color: #1877f2;
}

.clm-social-share-btn.twitter {
    background-color: #1da1f2;
}

.clm-social-share-btn.email {
    background-color: #757575;
}

/* Empty State Styles */
.clm-empty-state {
    text-align: center;
    padding: 40px 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    border: 1px dashed #ddd;
}

.clm-empty-icon {
    font-size: 48px;
    color: #bdbdbd;
    margin-bottom: 20px;
}

.clm-empty-icon .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}

.clm-empty-state h3 {
    margin-top: 0;
    color: #424242;
}

.clm-empty-state p {
    color: #757575;
    margin-bottom: 20px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .clm-dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .clm-playlists-grid {
        grid-template-columns: 1fr;
    }
    
    .clm-playlist-card-actions {
        flex-wrap: wrap;
    }
    
    .clm-playlist-card-actions .clm-button {
        flex: 0 0 calc(50% - 5px);
    }
}
</style>