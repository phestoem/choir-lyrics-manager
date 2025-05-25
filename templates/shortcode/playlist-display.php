<?php
/**
 * Template for displaying a single playlist via [clm_playlist] shortcode.
 *
 * Expects:
 * - $playlist_post (WP_Post object for the playlist)
 * - $lyrics_in_playlist (array of WP_Post objects for lyrics)
 * - $can_edit_playlist (boolean)
 * - $playlist_id (int)
 * - $shortcode_atts (array)
 */
if (!defined('ABSPATH')) exit;

if (!$playlist_post) return;

$show_actions = isset($shortcode_atts['show_actions']) ? ($shortcode_atts['show_actions'] === 'yes') : true;
?>
<div class="clm-single-playlist-display" data-playlist-id="<?php echo esc_attr($playlist_id); ?>">
    <h3 class="clm-playlist-title"><?php echo esc_html($playlist_post->post_title); ?></h3>

    <?php if (!empty($playlist_post->post_content)): ?>
        <div class="clm-playlist-description">
            <?php echo wpautop(wp_kses_post($playlist_post->post_content)); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($lyrics_in_playlist)): ?>
        <ul class="clm-playlist-track-list">
            <?php foreach ($lyrics_in_playlist as $lyric_item): ?>
                <li class="clm-playlist-track-item" data-lyric-id="<?php echo esc_attr($lyric_item->ID); ?>">
                    <a href="<?php echo get_permalink($lyric_item->ID); ?>" class="clm-track-title">
                        <?php echo esc_html($lyric_item->post_title); ?>
                    </a>
                    <?php if ($show_actions && $can_edit_playlist): ?>
                        <button type="button" 
                                class="clm-remove-from-playlist-button clm-button-text clm-button-small" 
                                data-playlist-id="<?php echo esc_attr($playlist_id); ?>"
                                data-lyric-id="<?php echo esc_attr($lyric_item->ID); ?>"
                                title="<?php esc_attr_e('Remove this lyric from playlist', 'choir-lyrics-manager'); ?>">
                            <span class="dashicons dashicons-no-alt"></span> <?php // _e('Remove', 'choir-lyrics-manager'); ?>
                        </button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="clm-notice"><?php _e('This playlist is currently empty.', 'choir-lyrics-manager'); ?></p>
    <?php endif; ?>
</div>