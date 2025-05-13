<?php
/**
 * Admin dashboard view for the plugin
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap clm-admin-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="clm-dashboard-summary">
        <div class="clm-summary-box">
            <div class="clm-summary-icon">
                <span class="dashicons dashicons-format-audio"></span>
            </div>
            <div class="clm-summary-content">
                <div class="clm-summary-value"><?php echo esc_html($lyrics_count); ?></div>
                <div class="clm-summary-label"><?php _e('Lyrics', 'choir-lyrics-manager'); ?></div>
            </div>
        </div>
        
        <div class="clm-summary-box">
            <div class="clm-summary-icon">
                <span class="dashicons dashicons-album"></span>
            </div>
            <div class="clm-summary-content">
                <div class="clm-summary-value"><?php echo esc_html($albums_count); ?></div>
                <div class="clm-summary-label"><?php _e('Albums', 'choir-lyrics-manager'); ?></div>
            </div>
        </div>
        
        <div class="clm-summary-box">
            <div class="clm-summary-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="clm-summary-content">
                <div class="clm-summary-value"><?php echo esc_html($practice_logs_count); ?></div>
                <div class="clm-summary-label"><?php _e('Practice Logs', 'choir-lyrics-manager'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="clm-dashboard-cards">
        <div class="clm-dashboard-card">
            <div class="clm-card-header">
                <h2><?php _e('Recent Lyrics', 'choir-lyrics-manager'); ?></h2>
                <a href="<?php echo admin_url('edit.php?post_type=clm_lyric'); ?>" class="button"><?php _e('View All', 'choir-lyrics-manager'); ?></a>
            </div>
            
            <?php if (!empty($recent_lyrics)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Author', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Date', 'choir-lyrics-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_lyrics as $lyric) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($lyric->ID); ?>"><?php echo esc_html($lyric->post_title); ?></a>
                                    <div class="row-actions">
                                        <span class="edit"><a href="<?php echo get_edit_post_link($lyric->ID); ?>"><?php _e('Edit', 'choir-lyrics-manager'); ?></a> | </span>
                                        <span class="view"><a href="<?php echo get_permalink($lyric->ID); ?>"><?php _e('View', 'choir-lyrics-manager'); ?></a></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html(get_the_author_meta('display_name', $lyric->post_author)); ?></td>
                                <td><?php echo get_the_date('', $lyric->ID); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="clm-notice"><?php _e('No lyrics found.', 'choir-lyrics-manager'); ?></p>
            <?php endif; ?>
            
            <div class="clm-card-footer">
                <a href="<?php echo admin_url('post-new.php?post_type=clm_lyric'); ?>" class="button button-primary"><?php _e('Add New Lyric', 'choir-lyrics-manager'); ?></a>
            </div>
        </div>
        
        <div class="clm-dashboard-card">
            <div class="clm-card-header">
                <h2><?php _e('Recent Practice', 'choir-lyrics-manager'); ?></h2>
                <a href="<?php echo admin_url('edit.php?post_type=clm_practice_log'); ?>" class="button"><?php _e('View All', 'choir-lyrics-manager'); ?></a>
            </div>
            
            <?php if (!empty($recent_practice)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Lyric', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Duration', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Date', 'choir-lyrics-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_practice as $practice) : 
                            $lyric_id = get_post_meta($practice->ID, '_clm_lyric_id', true);
                            $lyric_title = get_the_title($lyric_id);
                            $duration = get_post_meta($practice->ID, '_clm_duration', true);
                        ?>
                            <tr>
                                <td><?php echo esc_html(get_the_author_meta('display_name', $practice->post_author)); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($lyric_id); ?>"><?php echo esc_html($lyric_title); ?></a>
                                </td>
                                <td><?php echo esc_html($duration) . ' ' . _n('minute', 'minutes', $duration, 'choir-lyrics-manager'); ?></td>
                                <td><?php echo get_the_date('', $practice->ID); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="clm-notice"><?php _e('No practice logs found.', 'choir-lyrics-manager'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="clm-dashboard-quick-links">
        <h2><?php _e('Quick Links', 'choir-lyrics-manager'); ?></h2>
        <div class="clm-quick-links-grid">
            <a href="<?php echo admin_url('post-new.php?post_type=clm_lyric'); ?>" class="clm-quick-link">
                <span class="dashicons dashicons-format-audio"></span>
                <span class="clm-quick-link-label"><?php _e('Add New Lyric', 'choir-lyrics-manager'); ?></span>
            </a>
            
            <a href="<?php echo admin_url('post-new.php?post_type=clm_album'); ?>" class="clm-quick-link">
                <span class="dashicons dashicons-album"></span>
                <span class="clm-quick-link-label"><?php _e('Add New Album', 'choir-lyrics-manager'); ?></span>
            </a>
            
            <a href="<?php echo admin_url('edit-tags.php?taxonomy=clm_genre&post_type=clm_lyric'); ?>" class="clm-quick-link">
                <span class="dashicons dashicons-tag"></span>
                <span class="clm-quick-link-label"><?php _e('Manage Genres', 'choir-lyrics-manager'); ?></span>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=clm-analytics'); ?>" class="clm-quick-link">
                <span class="dashicons dashicons-chart-bar"></span>
                <span class="clm-quick-link-label"><?php _e('View Analytics', 'choir-lyrics-manager'); ?></span>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=clm-settings'); ?>" class="clm-quick-link">
                <span class="dashicons dashicons-admin-settings"></span>
                <span class="clm-quick-link-label"><?php _e('Plugin Settings', 'choir-lyrics-manager'); ?></span>
            </a>
        </div>
    </div>
</div>

<style>
/* Additional styles for the dashboard */
.clm-dashboard-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

.clm-dashboard-card {
    flex: 1 1 calc(50% - 20px);
    min-width: 300px;
    background-color: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.clm-card-header {
    padding: 15px;
    border-bottom: 1px solid #e5e5e5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clm-card-header h2 {
    margin: 0;
    font-size: 16px;
}

.clm-card-footer {
    padding: 15px;
    border-top: 1px solid #e5e5e5;
    text-align: right;
}

.clm-quick-links-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 15px;
}

.clm-quick-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
    background-color: #fff;
    text-align: center;
    text-decoration: none;
    color: #555;
    min-width: 100px;
    flex: 1 1 calc(20% - 15px);
}

.clm-quick-link:hover {
    background-color: #f5f5f5;
    color: #555;
}

.clm-quick-link .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
    margin-bottom: 8px;
    color: #3498db;
}

.clm-quick-link-label {
    font-weight: 600;
}

@media screen and (max-width: 782px) {
    .clm-quick-link {
        flex: 1 1 calc(33.33% - 15px);
    }
}
</style>