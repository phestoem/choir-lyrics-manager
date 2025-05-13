<?php
/**
 * Template for displaying lyric submission form
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get redirect URL
$redirect = isset($atts['redirect']) ? $atts['redirect'] : '';

?>

<div class="clm-submission-form">
    <h2 class="clm-heading"><?php _e('Submit a Lyric', 'choir-lyrics-manager'); ?></h2>
    
    <?php if (isset($_GET['clm_submission']) && $_GET['clm_submission'] === 'success'): ?>
        <div class="clm-success">
            <?php _e('Your lyric has been submitted successfully! It will be reviewed by a moderator.', 'choir-lyrics-manager'); ?>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="clm_submit_lyric">
        <?php wp_nonce_field('clm_submit_lyric', 'clm_submission_nonce'); ?>
        
        <?php if ($redirect): ?>
            <input type="hidden" name="clm_redirect" value="<?php echo esc_url($redirect); ?>">
        <?php endif; ?>
        
        <div class="clm-form-section">
            <h3 class="clm-form-section-title"><?php _e('Basic Information', 'choir-lyrics-manager'); ?></h3>
            
            <div class="clm-form-field">
                <label for="clm_title"><?php _e('Title', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
                <input type="text" id="clm_title" name="clm_title" required>
            </div>
            
            <div class="clm-form-field">
                <label for="clm_composer"><?php _e('Composer', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_composer" name="clm_composer">
            </div>
            
            <div class="clm-form-field">
                <label for="clm_arranger"><?php _e('Arranger', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_arranger" name="clm_arranger">
            </div>
            
            <div class="clm-form-field">
                <label for="clm_year"><?php _e('Year', 'choir-lyrics-manager'); ?></label>
                <input type="number" id="clm_year" name="clm_year" min="1000" max="<?php echo date('Y'); ?>">
            </div>
            
            <div class="clm-form-field">
                <label for="clm_language"><?php _e('Language', 'choir-lyrics-manager'); ?></label>
                <select id="clm_language" name="clm_languages">
                    <option value=""><?php _e('Select Language', 'choir-lyrics-manager'); ?></option>
                    <?php
                    $languages = get_terms(array(
                        'taxonomy' => 'clm_language',
                        'hide_empty' => false,
                    ));
                    
                    foreach ($languages as $language) {
                        echo '<option value="' . esc_attr($language->term_id) . '">' . esc_html($language->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="clm-form-field">
                <label for="clm_difficulty"><?php _e('Difficulty Level', 'choir-lyrics-manager'); ?></label>
                <select id="clm_difficulty" name="clm_difficulty_level">
                    <option value=""><?php _e('Select Difficulty', 'choir-lyrics-manager'); ?></option>
                    <?php
                    $difficulties = get_terms(array(
                        'taxonomy' => 'clm_difficulty',
                        'hide_empty' => false,
                    ));
                    
                    foreach ($difficulties as $difficulty) {
                        echo '<option value="' . esc_attr($difficulty->term_id) . '">' . esc_html($difficulty->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="clm-form-field">
                <label for="clm_genres"><?php _e('Genres', 'choir-lyrics-manager'); ?></label>
                <select id="clm_genres" name="clm_genres[]" multiple>
                    <?php
                    $genres = get_terms(array(
                        'taxonomy' => 'clm_genre',
                        'hide_empty' => false,
                    ));
                    
                    foreach ($genres as $genre) {
                        echo '<option value="' . esc_attr($genre->term_id) . '">' . esc_html($genre->name) . '</option>';
                    }
                    ?>
                </select>
                <span class="description"><?php _e('Hold Ctrl/Cmd to select multiple genres', 'choir-lyrics-manager'); ?></span>
            </div>
        </div>
        
        <div class="clm-form-section">
            <h3 class="clm-form-section-title"><?php _e('Lyric Content', 'choir-lyrics-manager'); ?></h3>
            
            <div class="clm-form-field">
                <label for="clm_content"><?php _e('Lyrics', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
                <textarea id="clm_content" name="clm_content" rows="15" required></textarea>
            </div>
            
            <div class="clm-form-field">
                <label for="clm_performance_notes"><?php _e('Performance Notes', 'choir-lyrics-manager'); ?></label>
                <textarea id="clm_performance_notes" name="clm_performance_notes" rows="5"></textarea>
            </div>
        </div>
        
        <div class="clm-form-section">
            <h3 class="clm-form-section-title"><?php _e('Media Files', 'choir-lyrics-manager'); ?></h3>
            
            <div class="clm-form-field">
                <label for="clm_sheet_music"><?php _e('Sheet Music (PDF, DOC, or image files)', 'choir-lyrics-manager'); ?></label>
                <input type="file" id="clm_sheet_music" name="clm_sheet_music" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </div>
            
            <div class="clm-form-field">
                <label for="clm_audio_file"><?php _e('Audio Recording (MP3, WAV, OGG)', 'choir-lyrics-manager'); ?></label>
                <input type="file" id="clm_audio_file" name="clm_audio_file" accept=".mp3,.wav,.ogg,.m4a">
            </div>
            
            <div class="clm-form-field">
                <label for="clm_midi_file"><?php _e('MIDI File', 'choir-lyrics-manager'); ?></label>
                <input type="file" id="clm_midi_file" name="clm_midi_file" accept=".mid,.midi">
            </div>
            
            <div class="clm-form-field">
                <label for="clm_video_embed"><?php _e('Video Embed Code', 'choir-lyrics-manager'); ?></label>
                <textarea id="clm_video_embed" name="clm_video_embed" rows="3"></textarea>
                <span class="description"><?php _e('Paste embed code from YouTube, Vimeo or other video platforms', 'choir-lyrics-manager'); ?></span>
            </div>
            
            <div class="clm-form-field clm-practice-tracks-field">
                <label><?php _e('Practice Tracks', 'choir-lyrics-manager'); ?></label>
                
                <div id="clm-practice-tracks-container">
                    <div class="clm-practice-track-input">
                        <input type="text" name="clm_practice_track_titles[]" placeholder="<?php _e('Track Title (e.g. Soprano)', 'choir-lyrics-manager'); ?>" class="medium-text">
                        <input type="file" name="clm_practice_tracks[]" accept=".mp3,.wav,.ogg,.m4a">
                        <button type="button" class="clm-remove-track button"><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                    </div>
                </div>
                
                <button type="button" id="clm-add-practice-track" class="button"><?php _e('Add Another Track', 'choir-lyrics-manager'); ?></button>
            </div>
        </div>
        
        <div class="clm-form-actions">
            <button type="submit" class="clm-button clm-button-primary"><?php _e('Submit Lyric', 'choir-lyrics-manager'); ?></button>
            <a href="<?php echo esc_url(home_url()); ?>" class="clm-button"><?php _e('Cancel', 'choir-lyrics-manager'); ?></a>
        </div>
    </form>
</div>

<script>
    // Simple JavaScript for adding/removing practice tracks
    document.addEventListener('DOMContentLoaded', function() {
        var container = document.getElementById('clm-practice-tracks-container');
        var addButton = document.getElementById('clm-add-practice-track');
        
        if (addButton && container) {
            addButton.addEventListener('click', function() {
                var trackInput = container.querySelector('.clm-practice-track-input').cloneNode(true);
                var inputs = trackInput.querySelectorAll('input');
                
                // Clear input values
                for (var i = 0; i < inputs.length; i++) {
                    inputs[i].value = '';
                }
                
                container.appendChild(trackInput);
            });
            
            // Delegate click event for remove buttons
            container.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('clm-remove-track')) {
                    // Don't remove the last track
                    if (container.querySelectorAll('.clm-practice-track-input').length > 1) {
                        e.target.closest('.clm-practice-track-input').remove();
                    } else {
                        // Just clear the inputs
                        var inputs = e.target.closest('.clm-practice-track-input').querySelectorAll('input');
                        for (var i = 0; i < inputs.length; i++) {
                            inputs[i].value = '';
                        }
                    }
                }
            });
        }
    });
</script>