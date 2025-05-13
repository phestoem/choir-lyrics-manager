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
	$items_per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : $settings->get_setting('items_per_page', 20);

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
                    <input type="text" 
                           id="clm-search-input" 
                           class="clm-search-input" 
                           placeholder="<?php _e('Search lyrics, composers, languages...', 'choir-lyrics-manager'); ?>"
                           autocomplete="off">
                    <button type="submit" class="clm-search-button">
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
    <div class="clm-results-controls">
        <div class="clm-results-info">
            <span class="clm-results-count"><?php echo absint($wp_query->found_posts); ?></span> 
            <?php _e('lyrics found', 'choir-lyrics-manager'); ?>
        </div>
        
        <div class="clm-view-options">
            <select id="clm-items-per-page" class="clm-items-per-page">
                <option value="10" <?php selected($items_per_page, 10); ?>>10 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                <option value="20" <?php selected($items_per_page, 20); ?>>20 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                <option value="50" <?php selected($items_per_page, 50); ?>>50 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                <option value="100" <?php selected($items_per_page, 100); ?>>100 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
            </select>
            
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
            
            <div class="clm-filters-grid">
                <!-- Genre Filter -->
                <div class="clm-filter-group">
                    <label for="clm-genre-select"><?php _e('Genre', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-genre-select" name="genre" class="clm-filter-select">
                        <option value=""><?php _e('All Genres', 'choir-lyrics-manager'); ?></option>
                        <?php
                        $genres = get_terms(array(
                            'taxonomy' => 'clm_genre',
                            'hide_empty' => true,
                        ));
                        
                        foreach ($genres as $genre) {
                            $selected = isset($_GET['genre']) && $_GET['genre'] == $genre->slug ? 'selected' : '';
                            echo '<option value="' . esc_attr($genre->slug) . '" ' . $selected . '>' . esc_html($genre->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Language Filter -->
                <div class="clm-filter-group">
                    <label for="clm-language-select"><?php _e('Language', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-language-select" name="language" class="clm-filter-select">
                        <option value=""><?php _e('All Languages', 'choir-lyrics-manager'); ?></option>
                        <?php
                        $languages = get_terms(array(
                            'taxonomy' => 'clm_language',
                            'hide_empty' => true,
                        ));
                        
                        foreach ($languages as $language) {
                            $selected = isset($_GET['language']) && $_GET['language'] == $language->slug ? 'selected' : '';
                            echo '<option value="' . esc_attr($language->slug) . '" ' . $selected . '>' . esc_html($language->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Difficulty Filter -->
                <div class="clm-filter-group">
                    <label for="clm-difficulty-select"><?php _e('Difficulty', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-difficulty-select" name="difficulty" class="clm-filter-select">
                        <option value=""><?php _e('All Difficulties', 'choir-lyrics-manager'); ?></option>
                        <?php
                        $difficulties = get_terms(array(
                            'taxonomy' => 'clm_difficulty',
                            'hide_empty' => true,
                        ));
                        
                        foreach ($difficulties as $difficulty) {
                            $selected = isset($_GET['difficulty']) && $_GET['difficulty'] == $difficulty->slug ? 'selected' : '';
                            echo '<option value="' . esc_attr($difficulty->slug) . '" ' . $selected . '>' . esc_html($difficulty->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Sort Options -->
                <div class="clm-filter-group">
                    <label for="clm-sort-select"><?php _e('Sort By', 'choir-lyrics-manager'); ?></label>
                    <div class="clm-sort-options">
                        <select id="clm-sort-select" name="orderby" class="clm-filter-select">
                            <option value="title" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : 'title', 'title'); ?>><?php _e('Title', 'choir-lyrics-manager'); ?></option>
                            <option value="date" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'date'); ?>><?php _e('Date Added', 'choir-lyrics-manager'); ?></option>
                            <option value="modified" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'modified'); ?>><?php _e('Last Modified', 'choir-lyrics-manager'); ?></option>
                        </select>
                        <select name="order" class="clm-filter-select">
                            <option value="ASC" <?php selected(isset($_GET['order']) ? $_GET['order'] : 'ASC', 'ASC'); ?>><?php _e('Ascending', 'choir-lyrics-manager'); ?></option>
                            <option value="DESC" <?php selected(isset($_GET['order']) ? $_GET['order'] : '', 'DESC'); ?>><?php _e('Descending', 'choir-lyrics-manager'); ?></option>
                        </select>
                    </div>
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
                                            <label><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                                            <input type="text" class="clm-playlist-name" placeholder="<?php _e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                                        </div>
                                        
                                        <div class="clm-form-field">
                                            <label><?php _e('Description (optional)', 'choir-lyrics-manager'); ?></label>
                                            <textarea class="clm-playlist-description" rows="3"></textarea>
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
        
        <!-- Enhanced Pagination with ID -->
        <div id="clm-pagination" class="clm-pagination">
            <?php
            // Initial pagination for non-AJAX loads
            if ($wp_query->max_num_pages > 1) {
                echo paginate_links(array(
                    'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
                    'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                    'type' => 'list',
                    'mid_size' => 2,
                    'end_size' => 1,
                ));
                ?>
                
                <div class="clm-page-jump">
                    <label><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
                    <input type="number" id="clm-page-jump-input" min="1" max="<?php echo $wp_query->max_num_pages; ?>" value="<?php echo get_query_var('paged') ?: 1; ?>">
                    <button id="clm-page-jump-button" class="clm-button-small"><?php _e('Go', 'choir-lyrics-manager'); ?></button>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php
get_footer();
?>