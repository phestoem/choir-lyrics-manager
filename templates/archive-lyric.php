<?php
/**
 * Enhanced template for displaying lyric archives - Fixed for items per page AND order preservation
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get settings
$settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
$archive_title = $settings->get_setting('archive_title', __('Choir Lyrics', 'choir-lyrics-manager'));

// Items per page handling
$items_per_page = null;
if (isset($_GET['per_page'])) {
    $items_per_page = intval($_GET['per_page']);
} elseif (isset($_GET['clm_items_per_page'])) {
    $items_per_page = intval($_GET['clm_items_per_page']);
}

// If no value found in request, fall back to settings or default
if (empty($items_per_page)) {
    $items_per_page = $settings->get_setting('items_per_page', 20);
}

// Get sorting parameters
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'title';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC';

// Store in globals for the filter
global $clm_query_vars;
$clm_query_vars = array(
    'posts_per_page' => $items_per_page,
    'orderby' => $orderby,
    'order' => $order
);

// Add a pre_get_posts filter to modify the query before it executes
function clm_modify_lyrics_query($query) {
    global $clm_query_vars;
    
    // Only modify main query for our specific post type archive
    if ($query->is_main_query() && $query->is_post_type_archive('clm_lyric')) {
        // Apply all our custom query vars
        foreach ($clm_query_vars as $key => $value) {
            $query->set($key, $value);
        }
        
        // For debugging
        error_log('CLM: Modified query with: ' . print_r($clm_query_vars, true));
    }
    return $query;
}
add_action('pre_get_posts', 'clm_modify_lyrics_query');

get_header();
?>

<div class="clm-container clm-archive">
    <header class="clm-archive-header">
        <h1 class="clm-archive-title"><?php echo esc_html($archive_title); ?></h1>
    </header>

    <!-- Enhanced Search Section -->
    <div class="clm-search-section">
        <div class="clm-search-wrapper">
            <form id="clm-ajax-search-form" class="clm-ajax-search-form">
                <div class="clm-search-input-wrapper">
                    <label class="screen-reader-text">
                        <?php _e('Search lyrics', 'choir-lyrics-manager'); ?>
                        <input type="text" 
                               id="clm-search-input"
                               name="clm_search_query" 
                               class="clm-search-input" 
                               placeholder="<?php _e('Search lyrics, composers, languages...', 'choir-lyrics-manager'); ?>"
                               autocomplete="off">
                    </label>
                    <button type="submit" class="clm-search-button" aria-label="<?php _e('Search', 'choir-lyrics-manager'); ?>">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                    <div class="clm-search-loading" style="display: none;">
                        <span class="dashicons dashicons-update-alt spinning"></span>
                    </div>
                </div>
                <div id="clm-search-suggestions" class="clm-search-suggestions" style="display: none;"></div>
            </form>
        </div>
        
        <!-- Quick filters -->
        <div class="clm-quick-filters">
            <button class="clm-quick-filter active" data-filter="all"><?php _e('All', 'choir-lyrics-manager'); ?></button>
            <?php
            $popular_genres = get_terms(array(
                'taxonomy' => 'clm_genre',
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 5,
                'hide_empty' => true,
            ));
            
            foreach ($popular_genres as $genre) {
                echo '<button class="clm-quick-filter" data-filter="genre" data-value="' . esc_attr($genre->slug) . '">' . esc_html($genre->name) . '</button>';
            }
            ?>
        </div>
    </div>

    <!-- Results info and controls -->
    <div class="clm-results-info-controls">
        <div class="clm-results-info">
            <span class="clm-results-count"><?php echo $wp_query->found_posts; ?></span> 
            <?php _e('lyrics found', 'choir-lyrics-manager'); ?>
        </div>
        
        <div class="clm-view-options">
            <form id="clm-items-per-page-form" method="get" action="">
                <?php 
                // FIXED: Preserve ALL existing GET parameters - especially orderby and order
                foreach ($_GET as $key => $value) {
                    if ($key !== 'per_page' && $key !== 'paged') {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                
                // Explicitly add ordering parameters if they're not in GET but we have defaults
                if (!isset($_GET['orderby']) && $orderby != 'title') {
                    echo '<input type="hidden" name="orderby" value="' . esc_attr($orderby) . '">';
                }
                if (!isset($_GET['order']) && $order != 'ASC') {
                    echo '<input type="hidden" name="order" value="' . esc_attr($order) . '">';
                }
                ?>
                <label>
                    <?php _e('Items per page', 'choir-lyrics-manager'); ?>
                    <select id="clm-items-per-page" class="clm-items-per-page" name="per_page" onchange="this.form.submit()">
                        <option value="10" <?php selected($items_per_page, 10); ?>>10</option>
                        <option value="20" <?php selected($items_per_page, 20); ?>>20</option>
                        <option value="50" <?php selected($items_per_page, 50); ?>>50</option>
                        <option value="100" <?php selected($items_per_page, 100); ?>>100</option>
                    </select>
                </label>
            </form>
            
            <button class="clm-toggle-filters">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Advanced Filters', 'choir-lyrics-manager'); ?>
            </button>
        </div>
    </div>

    <!-- Enhanced Filters (Hidden by default) -->
    <div class="clm-advanced-filters" style="display: none;">
        <form id="clm-filter-form" action="<?php echo esc_url(get_post_type_archive_link('clm_lyric')); ?>" method="get">
            <input type="hidden" name="post_type" value="clm_lyric">
            <input type="hidden" name="per_page" value="<?php echo esc_attr($items_per_page); ?>">
            
            <div class="clm-filters-grid">
                <!-- Genre Filter -->
                <div class="clm-filter-group">
                    <?php
                    $genres = get_terms(array(
                        'taxonomy' => 'clm_genre',
                        'hide_empty' => true,
                    ));
                    
                    if ($genres && !is_wp_error($genres)) :
                    ?>
                        <label for="clm-genre-select"><?php _e('Genre', 'choir-lyrics-manager'); ?></label>
                        <select id="clm-genre-select" name="genre" class="clm-filter-select">
                            <option value=""><?php _e('All Genres', 'choir-lyrics-manager'); ?></option>
                            <?php
                            foreach ($genres as $genre) {
                                $selected = isset($_GET['genre']) && $_GET['genre'] == $genre->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($genre->slug) . '" ' . $selected . '>' . esc_html($genre->name) . '</option>';
                            }
                            ?>
                        </select>
                    <?php else : ?>
                        <p><?php _e('No genres available', 'choir-lyrics-manager'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Language Filter -->
                <div class="clm-filter-group">
                    <?php
                    $languages = get_terms(array(
                        'taxonomy' => 'clm_language',
                        'hide_empty' => true,
                    ));
                    
                    if ($languages && !is_wp_error($languages)) :
                    ?>
                        <label for="clm-language-select"><?php _e('Language', 'choir-lyrics-manager'); ?></label>
                        <select id="clm-language-select" name="language" class="clm-filter-select">
                            <option value=""><?php _e('All Languages', 'choir-lyrics-manager'); ?></option>
                            <?php
                            foreach ($languages as $language) {
                                $selected = isset($_GET['language']) && $_GET['language'] == $language->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($language->slug) . '" ' . $selected . '>' . esc_html($language->name) . '</option>';
                            }
                            ?>
                        </select>
                    <?php else : ?>
                        <p><?php _e('No languages available', 'choir-lyrics-manager'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Difficulty Filter -->
                <div class="clm-filter-group">
                    <?php
                    $difficulties = get_terms(array(
                        'taxonomy' => 'clm_difficulty',
                        'hide_empty' => true,
                    ));
                    
                    if ($difficulties && !is_wp_error($difficulties)) :
                    ?>
                        <label for="clm-difficulty-select"><?php _e('Difficulty', 'choir-lyrics-manager'); ?></label>
                        <select id="clm-difficulty-select" name="difficulty" class="clm-filter-select">
                            <option value=""><?php _e('All Difficulties', 'choir-lyrics-manager'); ?></option>
                            <?php
                            foreach ($difficulties as $difficulty) {
                                $selected = isset($_GET['difficulty']) && $_GET['difficulty'] == $difficulty->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($difficulty->slug) . '" ' . $selected . '>' . esc_html($difficulty->name) . '</option>';
                            }
                            ?>
                        </select>
                    <?php else : ?>
                        <p><?php _e('No difficulty levels available', 'choir-lyrics-manager'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Sort Options -->
                <div class="clm-filter-group">
                    <fieldset>
                        <legend><?php _e('Sort Options', 'choir-lyrics-manager'); ?></legend>
                        <div class="clm-sort-options">
                            <div>
                                <label for="clm-sort-select"><?php _e('Sort By', 'choir-lyrics-manager'); ?></label>
                                <select id="clm-sort-select" name="orderby" class="clm-filter-select">
                                    <option value="title" <?php selected($orderby, 'title'); ?>><?php _e('Title', 'choir-lyrics-manager'); ?></option>
                                    <option value="date" <?php selected($orderby, 'date'); ?>><?php _e('Date Added', 'choir-lyrics-manager'); ?></option>
                                    <option value="modified" <?php selected($orderby, 'modified'); ?>><?php _e('Last Modified', 'choir-lyrics-manager'); ?></option>
                                </select>
                            </div>
                            <div>
                                <label for="clm-order-select"><?php _e('Order', 'choir-lyrics-manager'); ?></label>
                                <select id="clm-order-select" name="order" class="clm-filter-select">
                                    <option value="ASC" <?php selected($order, 'ASC'); ?>><?php _e('Ascending', 'choir-lyrics-manager'); ?></option>
                                    <option value="DESC" <?php selected($order, 'DESC'); ?>><?php _e('Descending', 'choir-lyrics-manager'); ?></option>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
            
            <div class="clm-filter-actions">
                <button type="submit" class="clm-button clm-apply-filters"><?php _e('Apply Filters', 'choir-lyrics-manager'); ?></button>
                <a href="<?php echo esc_url(get_post_type_archive_link('clm_lyric')); ?>" class="clm-button-text clm-reset-filters"><?php _e('Reset All', 'choir-lyrics-manager'); ?></a>
            </div>
        </form>
    </div>

    <!-- Alphabet Navigation -->
    <div class="clm-alphabet-nav">
        <a href="#" class="clm-alpha-link active" data-letter="all"><?php _e('All', 'choir-lyrics-manager'); ?></a>
        <?php foreach (range('A', 'Z') as $letter): ?>
            <a href="#" class="clm-alpha-link" data-letter="<?php echo $letter; ?>"><?php echo $letter; ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Loading indicator -->
    <div id="clm-loading-overlay" class="clm-loading-overlay" style="display: none;">
        <div class="clm-loading-spinner"></div>
    </div>

    <!-- Debug info (if needed) -->
    <?php if (WP_DEBUG): ?>
    <div class="clm-debug-info" style="margin: 20px 0; padding: 10px; background: #f8f8f8; border: 1px solid #ddd;">
        <h3>Query Info</h3>
        <p>Posts per page: <?php echo $items_per_page; ?></p>
        <p>Order by: <?php echo $orderby; ?></p>
        <p>Order: <?php echo $order; ?></p>
        <p>WP Query posts_per_page: <?php echo $wp_query->get('posts_per_page'); ?></p>
        <p>WP Query orderby: <?php echo $wp_query->get('orderby'); ?></p>
        <p>WP Query order: <?php echo $wp_query->get('order'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Results container -->
    <div id="clm-results-container">
        <?php if (have_posts()) : ?>
            <ul class="clm-items-list" id="clm-items-list">
                <?php while (have_posts()) : the_post(); ?>
                    <li id="lyric-<?php the_ID(); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
                        <div class="clm-item-card">
                            <h2 class="clm-item-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            
                            <div class="clm-item-meta">
                                <?php
                                $meta_items = array();
                                
                                // Show composer if available
                                $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
                                if ($composer) {
                                    $meta_items[] = '<span class="clm-meta-composer"><strong>' . __('Composer:', 'choir-lyrics-manager') . '</strong> ' . esc_html($composer) . '</span>';
                                }
                                
                                // Show language if available
                                $language = get_post_meta(get_the_ID(), '_clm_language', true);
                                if ($language) {
                                    $meta_items[] = '<span class="clm-meta-language"><strong>' . __('Language:', 'choir-lyrics-manager') . '</strong> ' . esc_html($language) . '</span>';
                                }
                                
                                // Show difficulty if available and enabled
                                if ($settings->get_setting('show_difficulty', true)) {
                                    $difficulty = get_post_meta(get_the_ID(), '_clm_difficulty', true);
                                    if ($difficulty) {
                                        $stars = '';
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $difficulty) {
                                                $stars .= '<span class="dashicons dashicons-star-filled"></span>';
                                            } else {
                                                $stars .= '<span class="dashicons dashicons-star-empty"></span>';
                                            }
                                        }
                                        $meta_items[] = '<span class="clm-meta-difficulty"><strong>' . __('Difficulty:', 'choir-lyrics-manager') . '</strong> ' . $stars . '</span>';
                                    }
                                }
                                
                                echo implode(' <span class="clm-meta-separator">•</span> ', $meta_items);
                                ?>
                            </div>
                            
                            <div class="clm-item-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            
                            <div class="clm-item-actions">
                                <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
                                
                                <?php if (is_user_logged_in()): ?>
                                    <button class="clm-button clm-create-playlist-button" data-lyric-id="<?php the_ID(); ?>">
                                        <?php _e('Create Playlist', 'choir-lyrics-manager'); ?>
                                    </button>
                                    
                                    <div class="clm-create-playlist-form" style="display:none;" data-lyric-id="<?php the_ID(); ?>">
                                        <h4><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></h4>
                                        
                                        <div class="clm-form-field">
                                            <label for="clm-playlist-name-<?php the_ID(); ?>"><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                                            <input type="text" 
                                                   id="clm-playlist-name-<?php the_ID(); ?>"
                                                   name="clm_playlist_name"
                                                   class="clm-playlist-name" 
                                                   placeholder="<?php _e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                                        </div>
                                        
                                        <div class="clm-form-field">
                                            <label for="clm-playlist-description-<?php the_ID(); ?>"><?php _e('Description (optional)', 'choir-lyrics-manager'); ?></label>
                                            <textarea id="clm-playlist-description-<?php the_ID(); ?>"
                                                      name="clm_playlist_description"
                                                      class="clm-playlist-description" 
                                                      rows="3"></textarea>
                                        </div>
                                        
                                        <div class="clm-form-field">
                                            <label><?php _e('Visibility', 'choir-lyrics-manager'); ?></label>
                                            <div class="clm-radio-group">
                                                <label><input type="radio" name="clm-playlist-visibility-<?php the_ID(); ?>" value="private" checked> <?php _e('Private', 'choir-lyrics-manager'); ?></label>
                                                <label><input type="radio" name="clm-playlist-visibility-<?php the_ID(); ?>" value="public"> <?php _e('Public', 'choir-lyrics-manager'); ?></label>
                                            </div>
                                        </div>
                                        
                                        <div class="clm-form-actions">
                                            <button class="clm-submit-playlist clm-button clm-button-primary" data-lyric-id="<?php the_ID(); ?>">
                                                <?php _e('Create', 'choir-lyrics-manager'); ?>
                                            </button>
                                            <button class="clm-cancel-playlist clm-button">
                                                <?php _e('Cancel', 'choir-lyrics-manager'); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="clm-no-results">
                <p class="clm-notice"><?php _e('No lyrics found matching your criteria.', 'choir-lyrics-manager'); ?></p>
                <p><?php _e('Try adjusting your filters or search terms.', 'choir-lyrics-manager'); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Fixed Pagination -->
        <div class="clm-pagination" data-container="archive">
            <?php
            // Initial pagination for non-AJAX loads
            $current_page = max(1, get_query_var('paged') ?: 1);
            if ($wp_query->max_num_pages > 1) {
                echo paginate_links(array(
                    'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
                    'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                    'type' => 'list',
                    'mid_size' => 2,
                    'end_size' => 1,
                    'current' => $current_page,
                    'total' => $wp_query->max_num_pages,
                    'format' => '?paged=%#%',
                    'add_args' => array_filter($_GET, function($key) {
                        return !in_array($key, ['paged', 'page']);
                    }, ARRAY_FILTER_USE_KEY),
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999)))
                ));
                ?>
                
                <div class="clm-page-jump">
                    <label for="clm-page-jump-input"><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
                    <input type="number" 
                           id="clm-page-jump-input"
                           name="clm_page_jump" 
                           min="1" 
                           max="<?php echo $wp_query->max_num_pages; ?>" 
                           value="<?php echo $current_page; ?>">
                    <button id="clm-page-jump-button" class="clm-button-small"><?php _e('Go', 'choir-lyrics-manager'); ?></button>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php
// Generate nonce for AJAX operations
wp_localize_script('clm-public', 'clm_vars', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => array(
        'search' => wp_create_nonce('clm_search_nonce'),
        'filter' => wp_create_nonce('clm_filter_nonce'),
        'playlist' => wp_create_nonce('clm_playlist_nonce'),
        'practice' => wp_create_nonce('clm_practice_nonce'),
        'skills' => wp_create_nonce('clm_skills_nonce'),
    ),
    'text' => array(
        'loading' => __('Loading...', 'choir-lyrics-manager'),
        'error' => __('An error occurred. Please try again.', 'choir-lyrics-manager'),
        'playlist_success' => __('Playlist created successfully!', 'choir-lyrics-manager'),
        'playlist_error' => __('Error creating playlist.', 'choir-lyrics-manager'),
        'practice_success' => __('Practice session logged successfully!', 'choir-lyrics-manager'),
    ),
    // Add current query settings for JavaScript
    'query' => array(
        'items_per_page' => $items_per_page,
        'orderby' => $orderby,
        'order' => $order
    ),
));

get_footer();
?>