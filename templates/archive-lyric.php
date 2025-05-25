<?php
/**
 * Enhanced template for displaying lyric archives
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

// Items per page handling - sanitize inputs with consistent parameter name
$items_per_page = 20; // Default value
if (isset($_GET['per_page']) && is_numeric($_GET['per_page'])) {
    $items_per_page = absint($_GET['per_page']);
} elseif (isset($_GET['clm_items_per_page']) && is_numeric($_GET['clm_items_per_page'])) {
    $items_per_page = absint($_GET['clm_items_per_page']);
} else {
    $items_per_page = absint($settings->get_setting('items_per_page', 20));
}

// Validate items per page is within acceptable range
$items_per_page = in_array($items_per_page, array(10, 20, 50, 100)) ? $items_per_page : 20;

// Get and sanitize sorting parameters
$valid_orderby = array('title', 'date', 'modified');
$valid_order = array('ASC', 'DESC');

$orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], $valid_orderby) 
    ? sanitize_text_field($_GET['orderby']) 
    : 'title';
    
$order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), $valid_order) 
    ? strtoupper(sanitize_text_field($_GET['order'])) 
    : 'ASC';

// Store in globals for the filter - only do this if not already done
global $clm_query_vars;
if (!isset($clm_query_vars)) {
    $clm_query_vars = array(
        'posts_per_page' => $items_per_page,
        'orderby' => $orderby,
        'order' => $order
    );
    
    // Add a pre_get_posts filter to modify the query before it executes - only add once
    function clm_modify_lyrics_query($query) {
        global $clm_query_vars;
        
        // Only modify main query for our specific post type archive
        if ($query->is_main_query() && $query->is_post_type_archive('clm_lyric')) {
            // Apply all our custom query vars
            foreach ($clm_query_vars as $key => $value) {
                $query->set($key, $value);
            }
        }
        return $query;
    }
    add_action('pre_get_posts', 'clm_modify_lyrics_query');
}

// Get current page number
$current_page = get_query_var('paged') ? absint(get_query_var('paged')) : 1;

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
                <?php wp_nonce_field('clm_search_nonce', 'clm_search_nonce'); ?>
                <div class="clm-search-input-wrapper">
                    <label class="screen-reader-text">
                        <?php _e('Search lyrics', 'choir-lyrics-manager'); ?>
                    </label>
                    <input type="text" 
                           id="clm-search-input"
                           name="clm_search_query" 
                           class="clm-search-input" 
                           placeholder="<?php _e('Search lyrics, composers, languages...', 'choir-lyrics-manager'); ?>"
                           autocomplete="off">
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
            
            if (!is_wp_error($popular_genres) && !empty($popular_genres)) {
                foreach ($popular_genres as $genre) {
                    echo '<button class="clm-quick-filter" data-filter="genre" data-value="' . esc_attr($genre->slug) . '">' . esc_html($genre->name) . '</button>';
                }
            }
            ?>
        </div>
        
        <!-- Media Quick Filters -->
        <div class="clm-media-quick-filters">
            <span class="clm-filter-label"><?php _e('Media:', 'choir-lyrics-manager'); ?></span>
            <a href="<?php echo esc_url(add_query_arg('has_audio', '1')); ?>" class="clm-media-quick-filter <?php echo isset($_GET['has_audio']) ? 'active' : ''; ?>" data-media="audio">
                <span class="dashicons dashicons-format-audio"></span>
                <span class="filter-text"><?php _e('Audio', 'choir-lyrics-manager'); ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('has_video', '1')); ?>" class="clm-media-quick-filter <?php echo isset($_GET['has_video']) ? 'active' : ''; ?>" data-media="video">
                <span class="dashicons dashicons-format-video"></span>
                <span class="filter-text"><?php _e('Video', 'choir-lyrics-manager'); ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('has_sheet', '1')); ?>" class="clm-media-quick-filter <?php echo isset($_GET['has_sheet']) ? 'active' : ''; ?>" data-media="sheet">
                <span class="dashicons dashicons-media-document"></span>
                <span class="filter-text"><?php _e('Sheet Music', 'choir-lyrics-manager'); ?></span>
            </a>
        </div>
    </div>

    <!-- Results info and controls -->
    <div class="clm-results-info-controls">
        <div class="clm-results-info">
            <span class="clm-results-count"><?php echo esc_html($wp_query->found_posts); ?></span> 
            <?php _e('lyrics found', 'choir-lyrics-manager'); ?>
        </div>
        
        <div class="clm-view-options">
            <form id="clm-items-per-page-form" method="get" action="">
                <?php 
                // Only preserve safe GET parameters
                $safe_params = array('orderby', 'order', 'genre', 'language', 'difficulty', 'post_type');
                foreach ($_GET as $key => $value) {
                    if (in_array($key, $safe_params) && $key !== 'per_page' && $key !== 'paged') {
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
                
                // Ensure post_type is preserved
                if (!isset($_GET['post_type'])) {
                    echo '<input type="hidden" name="post_type" value="clm_lyric">';
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
            
            <a href="<?php echo esc_url(home_url('/media-browse/')); ?>" class="clm-button clm-browse-media-button">
                <span class="dashicons dashicons-media-interactive"></span>
                <?php _e('Browse by Media Type', 'choir-lyrics-manager'); ?>
            </a>
        </div>
    </div>

    <!-- Enhanced Filters (Hidden by default) -->
    <div class="clm-advanced-filters" style="display: none;">
        <form id="clm-filter-form" action="<?php echo esc_url(get_post_type_archive_link('clm_lyric')); ?>" method="get">
            <?php wp_nonce_field('clm_filter_nonce', 'clm_filter_nonce'); ?>
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
                    
                    if (!is_wp_error($genres) && !empty($genres)) :
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
                    
                    if (!is_wp_error($languages) && !empty($languages)) :
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
                    
                    if (!is_wp_error($difficulties) && !empty($difficulties)) :
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
                
                <!-- Media Type Filter -->
                <div class="clm-filter-group">
                    <fieldset>
                        <legend><?php _e('Media Type', 'choir-lyrics-manager'); ?></legend>
                        <div class="clm-checkbox-group">
                            <label>
                                <input type="checkbox" name="has_audio" value="1" <?php checked(isset($_GET['has_audio'])); ?>> 
                                <span class="dashicons dashicons-format-audio"></span>
                                <?php _e('Audio', 'choir-lyrics-manager'); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="has_video" value="1" <?php checked(isset($_GET['has_video'])); ?>> 
                                <span class="dashicons dashicons-format-video"></span>
                                <?php _e('Video', 'choir-lyrics-manager'); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="has_sheet" value="1" <?php checked(isset($_GET['has_sheet'])); ?>> 
                                <span class="dashicons dashicons-media-document"></span>
                                <?php _e('Sheet Music', 'choir-lyrics-manager'); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="has_midi" value="1" <?php checked(isset($_GET['has_midi'])); ?>> 
                                <span class="dashicons dashicons-playlist-audio"></span>
                                <?php _e('MIDI', 'choir-lyrics-manager'); ?>
                            </label>
                        </div>
                    </fieldset>
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
            <a href="#" class="clm-alpha-link" data-letter="<?php echo esc_attr($letter); ?>"><?php echo esc_html($letter); ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Loading indicator -->
    <div id="clm-loading-overlay" class="clm-loading-overlay" style="display: none;">
        <div class="clm-loading-spinner"></div>
    </div>

    <!-- Results container -->
    <div id="clm-results-container" data-current-page="<?php echo esc_attr($current_page); ?>">
        <?php if (have_posts()) : ?>
            <ul class="clm-items-list" id="clm-items-list">
                <?php 
                // Create settings instance once for use in the loop
                $clm_settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
                
                while (have_posts()) : the_post(); 
                    // Include the lyric item template
                    include(CLM_PLUGIN_DIR . 'templates/partials/lyric-item.php');
                endwhile; 
                ?>
            </ul>
        <?php else: ?>
            <div class="clm-no-results">
                <p class="clm-notice"><?php _e('No lyrics found matching your criteria.', 'choir-lyrics-manager'); ?></p>
                <p><?php _e('Try adjusting your filters or search terms.', 'choir-lyrics-manager'); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Fixed Pagination with Consistent Classes -->
        <div class="clm-pagination" data-container="archive" data-current-page="<?php echo esc_attr($current_page); ?>" data-max-pages="<?php echo esc_attr($wp_query->max_num_pages); ?>">
            <?php if ($wp_query->max_num_pages > 1) : ?>
                <div class="clm-pagination-wrapper">
                    
                    <!-- Previous Button -->
                    <?php if ($current_page > 1) : ?>
                        <a class="clm-page-link clm-prev" 
                           href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, get_pagenum_link())); ?>" 
                           data-page="<?php echo esc_attr($current_page - 1); ?>"
                           aria-label="<?php esc_attr_e('Go to previous page', 'choir-lyrics-manager'); ?>">
                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                            <span class="clm-nav-text"><?php _e('Previous', 'choir-lyrics-manager'); ?></span>
                        </a>
                    <?php else : ?>
                        <span class="clm-page-link clm-prev disabled" aria-disabled="true">
                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                            <span class="clm-nav-text"><?php _e('Previous', 'choir-lyrics-manager'); ?></span>
                        </span>
                    <?php endif; ?>
                    
                    <!-- Numbered Pages -->
                    <?php
                    // Calculate page numbers to show
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($wp_query->max_num_pages, $current_page + 2);
                    
                    // Show first page if not in range
                    if ($start_page > 1) : ?>
                        <a class="clm-page-link" 
                           href="<?php echo esc_url(add_query_arg('paged', 1, get_pagenum_link())); ?>" 
                           data-page="1"
                           aria-label="<?php esc_attr_e('Go to page 1', 'choir-lyrics-manager'); ?>">
                           1
                        </a>
                        <?php if ($start_page > 2) : ?>
                            <span class="clm-page-link clm-dots" aria-hidden="true">...</span>
                        <?php endif;
                    endif;
                    
                    // Page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) :
                        if ($i == $current_page) : ?>
                            <span class="clm-page-link clm-current current" 
                                  data-page="<?php echo esc_attr($i); ?>" 
                                  aria-current="page"
                                  aria-label="<?php echo esc_attr(sprintf(__('Current page, page %s', 'choir-lyrics-manager'), $i)); ?>">
                                  <?php echo esc_html($i); ?>
                            </span>
                        <?php else : ?>
                            <a class="clm-page-link" 
                               href="<?php echo esc_url(add_query_arg('paged', $i, get_pagenum_link())); ?>" 
                               data-page="<?php echo esc_attr($i); ?>"
                               aria-label="<?php echo esc_attr(sprintf(__('Go to page %s', 'choir-lyrics-manager'), $i)); ?>">
                               <?php echo esc_html($i); ?>
                            </a>
                        <?php endif;
                    endfor;
                    
                    // Show last page if not in range
                    if ($end_page < $wp_query->max_num_pages) :
                        if ($end_page < $wp_query->max_num_pages - 1) : ?>
                            <span class="clm-page-link clm-dots" aria-hidden="true">...</span>
                        <?php endif; ?>
                        <a class="clm-page-link" 
                           href="<?php echo esc_url(add_query_arg('paged', $wp_query->max_num_pages, get_pagenum_link())); ?>" 
                           data-page="<?php echo esc_attr($wp_query->max_num_pages); ?>"
                           aria-label="<?php echo esc_attr(sprintf(__('Go to page %s', 'choir-lyrics-manager'), $wp_query->max_num_pages)); ?>">
                           <?php echo esc_html($wp_query->max_num_pages); ?>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Next Button -->
                    <?php if ($current_page < $wp_query->max_num_pages) : ?>
                        <a class="clm-page-link clm-next" 
                           href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, get_pagenum_link())); ?>" 
                           data-page="<?php echo esc_attr($current_page + 1); ?>"
                           aria-label="<?php esc_attr_e('Go to next page', 'choir-lyrics-manager'); ?>">
                            <span class="clm-nav-text"><?php _e('Next', 'choir-lyrics-manager'); ?></span>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    <?php else : ?>
                        <span class="clm-page-link clm-next disabled" aria-disabled="true">
                            <span class="clm-nav-text"><?php _e('Next', 'choir-lyrics-manager'); ?></span>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </span>
                    <?php endif; ?>
                    
                </div><!-- End .clm-pagination-wrapper -->
            <?php endif; ?>
            
            <!-- Page Jump Form - Improved with ARIA attributes and proper IDs -->
            <?php if ($wp_query->max_num_pages > 1) : ?>
            <div class="clm-page-jump">
                <label for="clm-page-jump-input"><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
                <input type="number" 
                       id="clm-page-jump-input"
                       class="clm-page-jump-input"
                       min="1" 
                       max="<?php echo esc_attr($wp_query->max_num_pages); ?>" 
                       value="<?php echo esc_attr($current_page); ?>">
                <!-- Note the direct onclick attribute - this bypasses jQuery event binding issues -->
                <button type="button" 
                        id="clm-page-jump-button" 
                        class="clm-go-button"
                        onclick="return window.clmPageJump(this);">
                    <?php _e('Go', 'choir-lyrics-manager'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div><!-- End .clm-pagination -->
    </div>
</div>

<?php
get_footer();
?>