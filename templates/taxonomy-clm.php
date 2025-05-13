<?php
/**
 * Template for displaying taxonomy archives for plugin taxonomies
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get the current term
$term = get_queried_object();

get_header();
?>

<div class="clm-container clm-archive">
    <header class="clm-archive-header">
        <h1 class="clm-archive-title">
            <?php 
            if (is_tax('clm_genre')) {
                echo __('Genre: ', 'choir-lyrics-manager') . single_term_title('', false);
            } elseif (is_tax('clm_composer')) {
                echo __('Composer: ', 'choir-lyrics-manager') . single_term_title('', false);
            } elseif (is_tax('clm_language')) {
                echo __('Language: ', 'choir-lyrics-manager') . single_term_title('', false);
            } elseif (is_tax('clm_difficulty')) {
                echo __('Difficulty: ', 'choir-lyrics-manager') . single_term_title('', false);
            } elseif (is_tax('clm_collection')) {
                echo __('Collection: ', 'choir-lyrics-manager') . single_term_title('', false);
            } else {
                single_term_title();
            }
            ?>
        </h1>
        
        <?php
        // Display term description if available
        $term_description = term_description();
        if (!empty($term_description)) {
            echo '<div class="clm-term-description">' . $term_description . '</div>';
        }
        ?>
    </header>
    
    <div class="clm-filters">
        <form action="<?php echo esc_url(get_term_link($term)); ?>" method="get">
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
                <button type="submit" class="clm-button"><?php _e('Sort', 'choir-lyrics-manager'); ?></button>
            </div>
        </form>
    </div>
    
    <?php if (have_posts()) : ?>
        <ul class="clm-items-list">
            <?php while (have_posts()) : the_post(); ?>
                <li id="post-<?php the_ID(); ?>" class="clm-item <?php echo 'clm-' . get_post_type() . '-item'; ?>">
                    <h2 class="clm-item-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    
                    <div class="clm-item-meta">
                        <?php
                        // Different meta for different post types
                        if (get_post_type() === 'clm_lyric') {
                            // Show composer if available
                            $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
                            if ($composer) {
                                echo '<span class="clm-meta-composer">' . __('Composer: ', 'choir-lyrics-manager') . esc_html($composer) . '</span> | ';
                            }
                            
                            // Show language if available
                            $language = get_post_meta(get_the_ID(), '_clm_language', true);
                            if ($language) {
                                echo '<span class="clm-meta-language">' . __('Language: ', 'choir-lyrics-manager') . esc_html($language) . '</span>';
                            }
                        } elseif (get_post_type() === 'clm_album') {
                            // Show release year if available
                            $release_year = get_post_meta(get_the_ID(), '_clm_release_year', true);
                            if ($release_year) {
                                echo '<span class="clm-meta-year">' . __('Year: ', 'choir-lyrics-manager') . esc_html($release_year) . '</span> | ';
                            }
                            
                            // Show director if available
                            $director = get_post_meta(get_the_ID(), '_clm_director', true);
                            if ($director) {
                                echo '<span class="clm-meta-director">' . __('Director: ', 'choir-lyrics-manager') . esc_html($director) . '</span>';
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="clm-item-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    
                    <div class="clm-item-actions">
                        <a href="<?php the_permalink(); ?>" class="clm-button">
                            <?php
                            if (get_post_type() === 'clm_lyric') {
                                _e('View Lyric', 'choir-lyrics-manager');
                            } elseif (get_post_type() === 'clm_album') {
                                _e('View Album', 'choir-lyrics-manager');
                            } else {
                                _e('View', 'choir-lyrics-manager');
                            }
                            ?>
                        </a>
                        
                        <?php if (get_post_type() === 'clm_lyric' && is_user_logged_in()): ?>
                            <?php
                            // Show add to playlist button
                            $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
                            echo $playlists->render_playlist_dropdown(get_the_ID());
                            ?>
                        <?php endif; ?>
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
        <p class="clm-notice"><?php _e('No items found.', 'choir-lyrics-manager'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();