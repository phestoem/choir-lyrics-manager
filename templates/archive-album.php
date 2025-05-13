<?php
/**
 * Template for displaying album archives
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get settings
$settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
$items_per_page = $settings->get_setting('items_per_page', 10);

get_header();
?>

<div class="clm-container clm-archive">
    <header class="clm-archive-header">
        <h1 class="clm-archive-title"><?php _e('Albums', 'choir-lyrics-manager'); ?></h1>
    </header>
    
    <div class="clm-filters">
        <form action="<?php echo esc_url(get_post_type_archive_link('clm_album')); ?>" method="get">
            <input type="hidden" name="post_type" value="clm_album">
            
            <div class="clm-filter clm-collection-filter">
                <label for="clm-collection-select"><?php _e('Collection', 'choir-lyrics-manager'); ?></label>
                <select id="clm-collection-select" name="collection">
                    <option value=""><?php _e('All Collections', 'choir-lyrics-manager'); ?></option>
                    <?php
                    $collections = get_terms(array(
                        'taxonomy' => 'clm_collection',
                        'hide_empty' => true,
                    ));
                    
                    foreach ($collections as $collection) {
                        $selected = isset($_GET['collection']) && $_GET['collection'] == $collection->slug ? 'selected' : '';
                        echo '<option value="' . esc_attr($collection->slug) . '" ' . $selected . '>' . esc_html($collection->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="clm-filter clm-year-filter">
                <label for="clm-year-input"><?php _e('Year', 'choir-lyrics-manager'); ?></label>
                <input type="number" id="clm-year-input" name="year" value="<?php echo isset($_GET['year']) ? esc_attr($_GET['year']) : ''; ?>" placeholder="<?php _e('Any Year', 'choir-lyrics-manager'); ?>" min="1900" max="<?php echo date('Y'); ?>">
            </div>
            
            <div class="clm-filter clm-sort-filter">
                <label for="clm-sort-select"><?php _e('Sort By', 'choir-lyrics-manager'); ?></label>
                <select id="clm-sort-select" name="orderby">
                    <option value="title" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : 'title', 'title'); ?>><?php _e('Title', 'choir-lyrics-manager'); ?></option>
                    <option value="date" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'date'); ?>><?php _e('Date Added', 'choir-lyrics-manager'); ?></option>
                    <option value="modified" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'modified'); ?>><?php _e('Last Modified', 'choir-lyrics-manager'); ?></option>
                </select>
                <select name="order">
                    <option value="ASC" <?php selected(isset($_GET['order']) ? $_GET['order'] : 'ASC', 'ASC'); ?>><?php _e('Ascending', 'choir-lyrics-manager'); ?></option>
                    <option value="DESC" <?php selected(isset($_GET['order']) ? $_GET['order'] : '', 'DESC'); ?>><?php _e('Descending', 'choir-lyrics-manager'); ?></option>
                </select>
            </div>
            
            <div class="clm-filter clm-filter-submit">
                <button type="submit" class="clm-button"><?php _e('Filter', 'choir-lyrics-manager'); ?></button>
                <a href="<?php echo esc_url(get_post_type_archive_link('clm_album')); ?>" class="clm-button-text"><?php _e('Reset Filters', 'choir-lyrics-manager'); ?></a>
            </div>
        </form>
    </div>
    
    <?php if (have_posts()) : ?>
        <ul class="clm-items-list">
            <?php while (have_posts()) : the_post(); ?>
                <li id="album-<?php the_ID(); ?>" class="clm-item clm-album-item">
                    <h2 class="clm-item-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    
                    <div class="clm-item-meta">
                        <?php
                        // Show release year if available
                        $release_year = get_post_meta(get_the_ID(), '_clm_release_year', true);
                        if ($release_year) {
                            echo '<span class="clm-meta-year">' . __('Year: ', 'choir-lyrics-manager') . esc_html($release_year) . '</span> | ';
                        }
                        
                        // Show director if available
                        $director = get_post_meta(get_the_ID(), '_clm_director', true);
                        if ($director) {
                            echo '<span class="clm-meta-director">' . __('Director: ', 'choir-lyrics-manager') . esc_html($director) . '</span> | ';
                        }
                        
                        // Show collections
                        $collections = get_the_terms(get_the_ID(), 'clm_collection');
                        if ($collections && !is_wp_error($collections)) {
                            $collection_names = array();
                            foreach ($collections as $collection) {
                                $collection_names[] = '<a href="' . esc_url(get_term_link($collection)) . '">' . esc_html($collection->name) . '</a>';
                            }
                            echo '<span class="clm-meta-collections">' . __('Collections: ', 'choir-lyrics-manager') . implode(', ', $collection_names) . '</span>';
                        }
                        ?>
                    </div>
                    
                    <div class="clm-item-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    
                    <?php
                    // Show number of lyrics in album
                    $lyrics_ids = get_post_meta(get_the_ID(), '_clm_lyrics', true);
                    $lyrics_count = is_array($lyrics_ids) ? count($lyrics_ids) : 0;
                    ?>
                    <div class="clm-item-stats">
                        <span class="clm-item-lyrics-count"><?php echo sprintf(_n('%d lyric', '%d lyrics', $lyrics_count, 'choir-lyrics-manager'), $lyrics_count); ?></span>
                    </div>
                    
                    <div class="clm-item-actions">
                        <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Album', 'choir-lyrics-manager'); ?></a>
                        
                        <?php 
                        // Add playlist shortcode button
                        if (is_user_logged_in()) {
                            echo '<a href="' . esc_url(get_permalink()) . '" class="clm-button clm-button-small">' . __('Play', 'choir-lyrics-manager') . '</a>';
                        }
                        ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
        
        <div class="clm-pagination">
            <?php
            echo paginate_links(array(
                'prev_text' => '&laquo; ' . __('Previous', 'choir-lyrics-manager'),
                'next_text' => __('Next', 'choir-lyrics-manager') . ' &raquo;',
            ));
            ?>
        </div>
    <?php else: ?>
        <p class="clm-notice"><?php _e('No albums found.', 'choir-lyrics-manager'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();