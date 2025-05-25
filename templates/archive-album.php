<?php
/**
 * Template for displaying album archives
 *
 * This template is used for the main /albums/ archive page.
 * It now relies on the main WordPress query, which is modified by
 * CLM_Albums::filter_album_archive_query via the pre_get_posts hook.
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Note: $items_per_page is now handled by the pre_get_posts hook.
// The main query (have_posts(), the_post()) will respect this.

get_header();

// Get current filter values for form pre-fill
$current_collection = isset($_GET['collection']) ? sanitize_text_field($_GET['collection']) : '';
$current_year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : '';
$current_orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'title'; // Default to title
$current_order = isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : 'ASC'; // Default to ASC

?>

<div class="clm-container clm-archive clm-album-archive-page">
    <header class="clm-archive-header">
        <h1 class="clm-archive-title"><?php post_type_archive_title(); // More dynamic title ?></h1>
        <?php
        // Display archive description if it exists
        $archive_description = get_the_post_type_description();
        if ( $archive_description ) {
            echo '<div class="clm-archive-description">' . wp_kses_post( $archive_description ) . '</div>';
        }
        ?>
    </header>

    <div class="clm-filters clm-album-filters">
        <form role="search" method="get" class="clm-filter-form" action="<?php echo esc_url(get_post_type_archive_link('clm_album')); ?>">
            <input type="hidden" name="post_type" value="clm_album"> 

            <div class="clm-filter-group">
                <div class="clm-filter clm-collection-filter">
                    <label for="clm-collection-select"><?php _e('Collection:', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-collection-select" name="collection" class="clm-filter-select">
                        <option value=""><?php _e('All Collections', 'choir-lyrics-manager'); ?></option>
                        <?php
                        $collections = get_terms(array(
                            'taxonomy' => 'clm_collection', // Use the dynamic slug from CLM_Albums if possible
                            'hide_empty' => true,
                            'orderby' => 'name',
                            'order' => 'ASC',
                        ));

                        if ($collections && !is_wp_error($collections)) {
                            foreach ($collections as $collection_term) {
                                echo '<option value="' . esc_attr($collection_term->slug) . '" ' . selected($current_collection, $collection_term->slug, false) . '>' . esc_html($collection_term->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="clm-filter clm-year-filter">
                    <label for="clm-year-input"><?php _e('Year:', 'choir-lyrics-manager'); ?></label>
                    <input type="number" id="clm-year-input" name="year" value="<?php echo esc_attr($current_year); ?>" placeholder="<?php _e('Any Year', 'choir-lyrics-manager'); ?>" min="1900" max="<?php echo date('Y') + 5; ?>" class="clm-filter-input">
                </div>
            </div>

            <div class="clm-filter-group">
                <div class="clm-filter clm-sort-filter">
                    <label for="clm-sort-select"><?php _e('Sort By:', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-sort-select" name="orderby" class="clm-filter-select">
                        <option value="title" <?php selected($current_orderby, 'title'); ?>><?php _e('Title', 'choir-lyrics-manager'); ?></option>
                        <option value="date" <?php selected($current_orderby, 'date'); ?>><?php _e('Date Added', 'choir-lyrics-manager'); ?></option>
                        <option value="modified" <?php selected($current_orderby, 'modified'); ?>><?php _e('Last Modified', 'choir-lyrics-manager'); ?></option>
                        <option value="release_year_meta" <?php selected($current_orderby, 'release_year_meta'); ?>><?php _e('Release Year', 'choir-lyrics-manager'); ?></option>
                        <option value="rand" <?php selected($current_orderby, 'rand'); ?>><?php _e('Random', 'choir-lyrics-manager'); ?></option>
                    </select>
                    <select name="order" class="clm-filter-select">
                        <option value="ASC" <?php selected($current_order, 'ASC'); ?>><?php _e('Ascending', 'choir-lyrics-manager'); ?></option>
                        <option value="DESC" <?php selected($current_order, 'DESC'); ?>><?php _e('Descending', 'choir-lyrics-manager'); ?></option>
                    </select>
                </div>
            </div>

            <div class="clm-filter-actions">
                <button type="submit" class="clm-button clm-apply-filters-button"><?php _e('Filter Albums', 'choir-lyrics-manager'); ?></button>
                <a href="<?php echo esc_url(get_post_type_archive_link('clm_album')); ?>" class="clm-button-text clm-reset-filters-button"><?php _e('Reset Filters', 'choir-lyrics-manager'); ?></a>
            </div>
        </form>
    </div>

    <?php if (have_posts()) : ?>
        <div class="clm-items-list clm-album-grid"> 
            <?php while (have_posts()) : the_post(); ?>
                <?php
                $album_id = get_the_ID();
                // Fetch details using the correct meta keys
                $release_year_display = get_post_meta($album_id, '_clm_album_release_date', true); // Textual one first
                if (!$release_year_display) {
                    $release_year_display = get_post_meta($album_id, '_clm_album_release_year', true); // Fallback to numeric year
                }
                $director = get_post_meta($album_id, '_clm_director', true);
                $album_collections = get_the_terms($album_id, 'clm_collection'); // Use dynamic slug

                $lyric_ids = get_post_meta($album_id, '_clm_album_lyric_ids', true); // CORRECTED META KEY
                $lyrics_count = is_array($lyric_ids) ? count($lyric_ids) : 0;
                ?>
                <article id="album-<?php echo esc_attr($album_id); ?>" <?php post_class('clm-item clm-album-item'); ?>>
                    <div class="clm-item-card">
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>" class="clm-item-thumbnail-link">
                                <?php the_post_thumbnail('medium_large'); // Or another appropriate size ?>
                            </a>
                        <?php endif; ?>

                        <div class="clm-item-content">
                            <h2 class="clm-item-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <div class="clm-item-meta">
                                <?php if ($release_year_display) : ?>
                                    <span class="clm-meta-item clm-meta-year">
                                        <span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html($release_year_display); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($director) : ?>
                                    <span class="clm-meta-item clm-meta-director">
                                        <span class="dashicons dashicons-admin-users"></span> <?php echo esc_html($director); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($album_collections && !is_wp_error($album_collections)) : ?>
                                    <span class="clm-meta-item clm-meta-collections">
                                        <span class="dashicons dashicons-tag"></span>
                                        <?php
                                        $collection_links_array = array();
                                        foreach ($album_collections as $collection_item) {
                                            $collection_links_array[] = '<a href="' . esc_url(get_term_link($collection_item)) . '">' . esc_html($collection_item->name) . '</a>';
                                        }
                                        echo implode(', ', $collection_links_array);
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if(has_excerpt()): ?>
                                <div class="clm-item-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>

                            <div class="clm-item-stats">
                                <span class="clm-item-lyrics-count">
                                    <span class="dashicons dashicons-list-view"></span>
                                    <?php echo sprintf(_n('%d track', '%d tracks', $lyrics_count, 'choir-lyrics-manager'), $lyrics_count); ?>
                                </span>
                            </div>
                        </div> 

                        <div class="clm-item-actions">
                            <a href="<?php the_permalink(); ?>" class="clm-button clm-view-album-button"><?php _e('View Album', 'choir-lyrics-manager'); ?></a>
                            <?php
                            // Example: Add to a "listen later" queue or quick play button if applicable
                            // if (is_user_logged_in() && $lyrics_count > 0) {
                            // echo '<button type="button" class="clm-button clm-button-secondary clm-quick-play-album" data-album-id="' . $album_id . '">' . __('Quick Play', 'choir-lyrics-manager') . '</button>';
                            // }
                            ?>
                        </div>
                    </div> 
                </article>
            <?php endwhile; ?>
        </div> 

        <div class="clm-pagination">
            <?php
            // WordPress's paginate_links function will use the main query's max_num_pages
            echo paginate_links(array(
                'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
                'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                'type'      => 'list', // Outputs an unordered list
            ));
            ?>
        </div>
    <?php else: ?>
        <div class="clm-no-results">
            <p class="clm-notice"><?php _e('No albums found matching your criteria. Try adjusting your filters.', 'choir-lyrics-manager'); ?></p>
        </div>
    <?php endif; ?>
</div> 

<?php
get_footer();