<?php
/**
 * Template Name: Media Browser
 * Efficient version with optimized queries
 * 
 * @package    Choir_Lyrics_Manager
 */

get_header();

// Get the active media type
$media_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;

// Define media types and their properties
$media_types = array(
    'audio' => array(
        'title' => __('Lyrics with Audio', 'choir-lyrics-manager'),
        'description' => __('Browse all lyrics that have audio recordings available.', 'choir-lyrics-manager'),
        'icon' => 'dashicons-format-audio',
        'color' => '#3498db'
    ),
    'video' => array(
        'title' => __('Lyrics with Video', 'choir-lyrics-manager'),
        'description' => __('Browse all lyrics that have video recordings available.', 'choir-lyrics-manager'),
        'icon' => 'dashicons-format-video',
        'color' => '#e74c3c'
    ),
    'sheet' => array(
        'title' => __('Lyrics with Sheet Music', 'choir-lyrics-manager'),
        'description' => __('Browse all lyrics that have sheet music available.', 'choir-lyrics-manager'),
        'icon' => 'dashicons-media-document',
        'color' => '#27ae60'
    ),
    'midi' => array(
        'title' => __('Lyrics with MIDI Files', 'choir-lyrics-manager'),
        'description' => __('Browse all lyrics that have MIDI files available.', 'choir-lyrics-manager'),
        'icon' => 'dashicons-playlist-audio',
        'color' => '#9b59b6'
    ),
    'all' => array(
        'title' => __('All Media Types', 'choir-lyrics-manager'),
        'description' => __('Browse all lyrics with any type of media attached.', 'choir-lyrics-manager'),
        'icon' => 'dashicons-media-interactive',
        'color' => '#333'
    )
);

// Set page title and description
$page_title = $media_types[$media_type]['title'];
$page_description = $media_types[$media_type]['description'];
$page_icon = $media_types[$media_type]['icon'];
$page_color = $media_types[$media_type]['color'];

/**
 * Efficient approach: Get posts with specific media type using optimized queries
 */
function clm_get_posts_with_media($media_type, $page = 1, $per_page = 20) {
    global $wpdb;
    
    $offset = ($page - 1) * $per_page;
    
    if ($media_type === 'all') {
        // For "all", use a simpler approach - get posts that have any media meta key
        $sql = "
            SELECT DISTINCT p.ID, p.post_title, p.post_date, p.post_content, p.post_excerpt
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'clm_lyric' 
            AND p.post_status = 'publish'
            AND (
                (pm.meta_key = '_clm_audio_file_id' AND pm.meta_value != '' AND pm.meta_value != '0') OR
                (pm.meta_key = '_clm_video_embed' AND pm.meta_value != '') OR
                (pm.meta_key = '_clm_sheet_music_id' AND pm.meta_value != '' AND pm.meta_value != '0') OR
                (pm.meta_key = '_clm_midi_file_id' AND pm.meta_value != '' AND pm.meta_value != '0') OR
                (pm.meta_key = '_clm_practice_tracks')
            )
            ORDER BY p.post_title ASC
            LIMIT %d OFFSET %d
        ";
        
        $count_sql = "
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'clm_lyric' 
            AND p.post_status = 'publish'
            AND (
                (pm.meta_key = '_clm_audio_file_id' AND pm.meta_value != '' AND pm.meta_value != '0') OR
                (pm.meta_key = '_clm_video_embed' AND pm.meta_value != '') OR
                (pm.meta_key = '_clm_sheet_music_id' AND pm.meta_value != '' AND pm.meta_value != '0') OR
                (pm.meta_key = '_clm_midi_file_id' AND pm.meta_value != '' AND pm.meta_value != '0') OR
                (pm.meta_key = '_clm_practice_tracks')
            )
        ";
    } else {
        // For specific media types, use targeted queries
        $meta_key = '';
        $extra_condition = '';
        
        switch ($media_type) {
            case 'audio':
                $meta_key = '_clm_audio_file_id';
                $extra_condition = "AND pm.meta_value != '' AND pm.meta_value != '0'";
                break;
            case 'video':
                $meta_key = '_clm_video_embed';
                $extra_condition = "AND pm.meta_value != ''";
                break;
            case 'sheet':
                $meta_key = '_clm_sheet_music_id';
                $extra_condition = "AND pm.meta_value != '' AND pm.meta_value != '0'";
                break;
            case 'midi':
                $meta_key = '_clm_midi_file_id';
                $extra_condition = "AND pm.meta_value != '' AND pm.meta_value != '0'";
                break;
        }
        
        if ($meta_key) {
            $sql = "
                SELECT p.ID, p.post_title, p.post_date, p.post_content, p.post_excerpt
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'clm_lyric' 
                AND p.post_status = 'publish'
                AND pm.meta_key = %s
                {$extra_condition}
                ORDER BY p.post_title ASC
                LIMIT %d OFFSET %d
            ";
            
            $count_sql = "
                SELECT COUNT(p.ID)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'clm_lyric' 
                AND p.post_status = 'publish'
                AND pm.meta_key = %s
                {$extra_condition}
            ";
        }
    }
    
    // Execute queries
    if ($media_type === 'all') {
        $posts = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
        $total_posts = $wpdb->get_var($count_sql);
    } else if (!empty($meta_key)) {
        $posts = $wpdb->get_results($wpdb->prepare($sql, $meta_key, $per_page, $offset));
        $total_posts = $wpdb->get_var($wpdb->prepare($count_sql, $meta_key));
    } else {
        $posts = array();
        $total_posts = 0;
    }
    
    // Convert to WP_Post objects
    $wp_posts = array();
    foreach ($posts as $post) {
        $wp_posts[] = new WP_Post($post);
    }
    
    return array(
        'posts' => $wp_posts,
        'total' => intval($total_posts),
        'pages' => ceil($total_posts / $per_page)
    );
}

// Get the posts efficiently
$start_time = microtime(true);
$result = clm_get_posts_with_media($media_type, $current_page, 20);
$query_time = microtime(true) - $start_time;

// Debug logging
if (defined('WP_DEBUG') && WP_DEBUG) {
    //error_log(sprintf('Efficient Media Browser [%s]: %.4f seconds, %d posts found', 
        // $media_type, $query_time, $result['total']));
}

// Create a mock WP_Query object for compatibility
$query = new WP_Query();
$query->posts = $result['posts'];
$query->found_posts = $result['total'];
$query->max_num_pages = $result['pages'];
$query->post_count = count($result['posts']);
$query->current_post = -1;

?>

<div class="clm-container clm-archive clm-media-browser">
    <header class="clm-archive-header">
        <h1 class="clm-archive-title">
            <span class="dashicons <?php echo esc_attr($page_icon); ?>" style="color: <?php echo esc_attr($page_color); ?>;"></span>
            <?php echo esc_html($page_title); ?>
        </h1>
        <p class="clm-archive-description"><?php echo esc_html($page_description); ?></p>
    </header>

    <!-- Media Type Navigation -->
    <div class="clm-media-navigation">
        <div class="clm-media-nav-wrapper">
            <?php foreach ($media_types as $type => $properties) : ?>
                <a href="<?php echo esc_url(add_query_arg('type', $type)); ?>" 
                   class="clm-media-nav-item <?php echo $media_type === $type ? 'active' : ''; ?>"
                   style="<?php echo $media_type === $type ? 'background-color:' . esc_attr($properties['color']) . '; color: white;' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr($properties['icon']); ?>"></span>
                    <span class="media-nav-text"><?php echo esc_html($properties['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Performance Info (debug mode only) -->
    <?php if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) : ?>
    <div class="clm-debug-info" style="background: #e8f5e8; padding: 10px; margin-bottom: 20px; border-radius: 4px; font-size: 12px;">
        <strong>⚡ Performance:</strong> Query executed in <?php echo number_format($query_time * 1000, 2); ?>ms | 
        Posts found: <?php echo $result['total']; ?> | 
        Media type: <?php echo esc_html($media_type); ?>
    </div>
    <?php endif; ?>

    <!-- Results container -->
    <div id="clm-results-container" data-current-page="<?php echo esc_attr($current_page); ?>">
        <?php if ($query->have_posts()) : ?>
            <div class="clm-results-count">
                <?php 
                printf(
                    _n('%s lyric found', '%s lyrics found', $query->found_posts, 'choir-lyrics-manager'), 
                    '<span class="count">' . number_format_i18n($query->found_posts) . '</span>'
                ); 
                ?>
            </div>
            
            <ul class="clm-items-list" id="clm-items-list">
                <?php
                // Create settings instance
                $clm_settings = null;
                if (class_exists('CLM_Settings')) {
                    try {
                        $clm_settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
                    } catch (Exception $e) {
                        error_log('CLM Settings Error: ' . $e->getMessage());
                    }
                }
                
                // Set up global post data for the loop
                global $post;
                foreach ($query->posts as $post) : 
                    setup_postdata($post);
                    
                    // Check if template exists
                    $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/partials/lyric-item.php';
                    if (file_exists($template_path)) {
                        include($template_path);
                    } else {
                        // Fallback template
                        $lyric_id = get_the_ID();
                        
                        // Get media info for this post
                        $audio_id = get_post_meta($lyric_id, '_clm_audio_file_id', true);
                        $video_embed = get_post_meta($lyric_id, '_clm_video_embed', true);
                        $sheet_id = get_post_meta($lyric_id, '_clm_sheet_music_id', true);
                        $midi_id = get_post_meta($lyric_id, '_clm_midi_file_id', true);
                        $practice_tracks = get_post_meta($lyric_id, '_clm_practice_tracks', true);
                        
                        $has_audio = (!empty($audio_id) && $audio_id !== '0') || !empty($practice_tracks);
                        $has_video = !empty($video_embed);
                        $has_sheet = !empty($sheet_id) && $sheet_id !== '0';
                        $has_midi = !empty($midi_id) && $midi_id !== '0';
                        ?>
                        <li id="lyric-<?php echo esc_attr($lyric_id); ?>" class="clm-item clm-lyric-item">
                            <div class="clm-item-card">
                                <h2 class="clm-item-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    
                                    <?php if ($has_audio || $has_video || $has_sheet || $has_midi): ?>
                                    <div class="clm-attachment-icons">
                                        <?php if ($has_audio): ?>
                                        <span class="clm-attachment-icon" title="Audio Available" style="background-color: #f0f7ff; border: 1px solid #d0e3ff; color: #3498db;">🎵</span>
                                        <?php endif; ?>
                                        <?php if ($has_video): ?>
                                        <span class="clm-attachment-icon" title="Video Available" style="background-color: #fff0f0; border: 1px solid #ffd0d0; color: #e74c3c;">🎬</span>
                                        <?php endif; ?>
                                        <?php if ($has_sheet): ?>
                                        <span class="clm-attachment-icon" title="Sheet Music Available" style="background-color: #f0fff5; border: 1px solid #d0ffd0; color: #27ae60;">📄</span>
                                        <?php endif; ?>
                                        <?php if ($has_midi): ?>
                                        <span class="clm-attachment-icon" title="MIDI Available" style="background-color: #f5f0ff; border: 1px solid #d0d0ff; color: #9b59b6;">🎹</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </h2>
                                
                                <div class="clm-item-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                                
                                <div class="clm-item-actions">
                                    <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
                                </div>
                            </div>
                        </li>
                        <?php
                    }
                endforeach;
                wp_reset_postdata();
                ?>
            </ul>
            
            <!-- Pagination -->
            <?php if ($query->max_num_pages > 1) : ?>
            <div class="clm-pagination">
                <ul class="page-numbers">
                    <?php
                    // Previous link
                    if ($current_page > 1) {
                        $prev_url = add_query_arg('paged', $current_page - 1);
                        echo '<li><a href="' . esc_url($prev_url) . '" class="prev page-numbers">&laquo; Previous</a></li>';
                    }
                    
                    // Page numbers
                    for ($i = 1; $i <= $query->max_num_pages; $i++) {
                        if ($i == $current_page) {
                            echo '<li><span class="page-numbers current">' . $i . '</span></li>';
                        } else {
                            $page_url = add_query_arg('paged', $i);
                            echo '<li><a href="' . esc_url($page_url) . '" class="page-numbers">' . $i . '</a></li>';
                        }
                    }
                    
                    // Next link
                    if ($current_page < $query->max_num_pages) {
                        $next_url = add_query_arg('paged', $current_page + 1);
                        echo '<li><a href="' . esc_url($next_url) . '" class="next page-numbers">Next &raquo;</a></li>';
                    }
                    ?>
                </ul>
            </div>
            <?php endif; ?>
            
        <?php else : ?>
            <div class="clm-no-results">
                <div style="text-align: center; padding: 40px 20px;">
                    <span class="dashicons <?php echo esc_attr($page_icon); ?>" style="font-size: 48px; color: <?php echo esc_attr($page_color); ?>; margin-bottom: 20px; display: block;"></span>
                    <h2><?php _e('No Lyrics Found', 'choir-lyrics-manager'); ?></h2>
                    <p class="clm-notice"><?php _e('No lyrics with this media type were found.', 'choir-lyrics-manager'); ?></p>
                    <p><?php _e('This means there are currently no lyrics in your database that have this type of media attached.', 'choir-lyrics-manager'); ?></p>
                    
                    <?php if (current_user_can('edit_posts')) : ?>
                    <p style="margin-top: 20px;">
                        <a href="<?php echo admin_url('edit.php?post_type=clm_lyric'); ?>" class="clm-button">
                            <?php _e('Manage Lyrics', 'choir-lyrics-manager'); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Quick fix for attachment icons */
.clm-attachment-icon {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 24px !important;
    height: 24px !important;
    margin-right: 5px !important;
    border-radius: 50% !important;
    font-size: 14px !important;
}

.clm-attachment-icons {
    display: inline-flex !important;
    align-items: center !important;
    margin-left: 10px !important;
}
</style>

<?php get_footer(); ?>