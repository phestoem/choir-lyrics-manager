<?php
/**
 * Template for displaying custom taxonomy archives (e.g., Collections, Genres, etc.)
 *
 * This template is used when viewing an archive page for a term in
 * clm_collection, clm_genre, etc.
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

$current_term = get_queried_object();
$current_orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'title'; // Default to title
$current_order = isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : 'ASC'; // Default to ASC

get_header();
?>

<div class="clm-container clm-archive clm-taxonomy-archive-page">
    <header class="clm-archive-header">
        <h1 class="clm-archive-title">
            <?php
            $taxonomy_title_prefix = '';
            if ($current_term && isset($current_term->taxonomy)) {
                switch ($current_term->taxonomy) {
                    case 'clm_genre':
                        $taxonomy_title_prefix = __('Genre: ', 'choir-lyrics-manager');
                        break;
                    // case 'clm_composer_tax': // If composer was a taxonomy
                    //     $taxonomy_title_prefix = __('Composer: ', 'choir-lyrics-manager');
                    //     break;
                    case 'clm_language':
                        $taxonomy_title_prefix = __('Language: ', 'choir-lyrics-manager');
                        break;
                    case 'clm_difficulty':
                        $taxonomy_title_prefix = __('Difficulty: ', 'choir-lyrics-manager');
                        break;
                    case 'clm_collection':
                        $taxonomy_title_prefix = __('Collection: ', 'choir-lyrics-manager');
                        break;
                }
            }
            echo esc_html($taxonomy_title_prefix) . single_term_title('', false);
            ?>
        </h1>

        <?php
        $term_description = term_description();
        if (!empty($term_description)) {
            echo '<div class="clm-term-description">' . wp_kses_post($term_description) . '</div>';
        }
        ?>
    </header>

    <div class="clm-filters clm-taxonomy-filters">
        <form role="search" method="get" class="clm-filter-form" action="<?php echo esc_url(get_term_link($current_term)); ?>">
            <?php // Hidden fields for other active filters if this form is part of a larger filter system ?>
            <div class="clm-filter-group">
                <div class="clm-filter clm-sort-filter">
                    <label for="clm-sort-select"><?php _e('Sort By:', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-sort-select" name="orderby" class="clm-filter-select">
                        <option value="title" <?php selected($current_orderby, 'title'); ?>><?php _e('Title', 'choir-lyrics-manager'); ?></option>
                        <option value="date" <?php selected($current_orderby, 'date'); ?>><?php _e('Date Added', 'choir-lyrics-manager'); ?></option>
                        <option value="modified" <?php selected($current_orderby, 'modified'); ?>><?php _e('Last Modified', 'choir-lyrics-manager'); ?></option>
                        <?php if (is_tax('clm_collection') || is_tax('clm_genre')) : // Example: only show for certain tax where lyrics are primary ?>
                            <option value="lyric_composer_meta" <?php selected($current_orderby, 'lyric_composer_meta'); ?>><?php _e('Composer (Lyrics)', 'choir-lyrics-manager'); ?></option>
                        <?php endif; ?>
                         <option value="rand" <?php selected($current_orderby, 'rand'); ?>><?php _e('Random', 'choir-lyrics-manager'); ?></option>
                    </select>
                    <select name="order" class="clm-filter-select">
                        <option value="ASC" <?php selected($current_order, 'ASC'); ?>><?php _e('Ascending', 'choir-lyrics-manager'); ?></option>
                        <option value="DESC" <?php selected($current_order, 'DESC'); ?>><?php _e('Descending', 'choir-lyrics-manager'); ?></option>
                    </select>
                </div>
            </div>
            <div class="clm-filter-actions">
                <button type="submit" class="clm-button clm-apply-filters-button"><?php _e('Sort Items', 'choir-lyrics-manager'); ?></button>
            </div>
        </form>
    </div>

    <?php if (have_posts()) : ?>
        <div class="clm-items-list clm-taxonomy-items-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php
                $post_id = get_the_ID();
                $post_type = get_post_type();
                ?>
                <article id="post-<?php echo esc_attr($post_id); ?>" <?php post_class('clm-item clm-' . esc_attr($post_type) . '-item'); ?>>
                    <div class="clm-item-card">
                        <?php if (has_post_thumbnail() && ($post_type === 'clm_album' || $post_type === 'clm_lyric')) : // Show thumbnail for albums and lyrics ?>
                            <a href="<?php the_permalink(); ?>" class="clm-item-thumbnail-link">
                                <?php the_post_thumbnail('medium'); ?>
                            </a>
                        <?php endif; ?>

                        <div class="clm-item-content">
                            <h2 class="clm-item-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <div class="clm-item-meta">
                                <?php if ($post_type === 'clm_lyric') : ?>
                                    <?php
                                    $composer = get_post_meta($post_id, '_clm_composer', true);
                                    if ($composer) {
                                        echo '<span class="clm-meta-item"><span class="dashicons dashicons-businessman"></span> ' . esc_html($composer) . '</span>';
                                    }
                                    $lang_terms = get_the_terms($post_id, 'clm_language');
                                    if ($lang_terms && !is_wp_error($lang_terms)) {
                                        $lang_names = array_map(function($term) { return esc_html($term->name); }, $lang_terms);
                                        echo '<span class="clm-meta-item"><span class="dashicons dashicons-translation"></span> ' . implode(', ', $lang_names) . '</span>';
                                    }
                                    ?>
                                <?php elseif ($post_type === 'clm_album') : ?>
                                    <?php
                                    $release_year_display = get_post_meta($post_id, '_clm_album_release_date', true);
                                    if (!$release_year_display) {
                                        $release_year_display = get_post_meta($post_id, '_clm_album_release_year', true);
                                    }
                                    if ($release_year_display) {
                                        echo '<span class="clm-meta-item"><span class="dashicons dashicons-calendar-alt"></span> ' . esc_html($release_year_display) . '</span>';
                                    }
                                    $director = get_post_meta($post_id, '_clm_director', true);
                                    if ($director) {
                                        echo '<span class="clm-meta-item"><span class="dashicons dashicons-admin-users"></span> ' . esc_html($director) . '</span>';
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>

                            <?php if(has_excerpt()): ?>
                                <div class="clm-item-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>
                        </div> {/* .clm-item-content */}

                        <div class="clm-item-actions">
                            <a href="<?php the_permalink(); ?>" class="clm-button">
                                <?php
                                if ($post_type === 'clm_lyric') {
                                    _e('View Lyric', 'choir-lyrics-manager');
                                } elseif ($post_type === 'clm_album') {
                                    _e('View Album', 'choir-lyrics-manager');
                                } else {
                                    _e('View Item', 'choir-lyrics-manager');
                                }
                                ?>
                            </a>
                            <?php if ($post_type === 'clm_lyric' && is_user_logged_in() && class_exists('CLM_Playlists')): ?>
                                <?php
                                $playlists_manager = new CLM_Playlists('choir-lyrics-manager', defined('CLM_VERSION') ? CLM_VERSION : '1.0.0');
                                echo $playlists_manager->render_playlist_dropdown($post_id);
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </article> 
            <?php endwhile; ?>
        </div> 

        <div class="clm-pagination">
            <?php
            echo paginate_links(array(
                'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
                'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                'type'      => 'list',
            ));
            ?>
        </div>
    <?php else: ?>
        <div class="clm-no-results">
            <p class="clm-notice"><?php _e('No items found in this category.', 'choir-lyrics-manager'); ?></p>
        </div>
    <?php endif; ?>
</div> 

<?php
get_footer();