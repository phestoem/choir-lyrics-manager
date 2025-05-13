<?php
/**
 * Template for displaying search results
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="clm-container clm-archive">
    <header class="clm-archive-header">
        <h1 class="clm-archive-title">
            <?php
            printf(
                /* translators: %s: search query */
                __('Search Results for: %s', 'choir-lyrics-manager'),
                '<span>' . get_search_query() . '</span>'
            );
            ?>
        </h1>
        
        <?php
        // Display search form
        echo do_shortcode('[clm_search_form]');
        ?>
    </header>
    
    <?php if (have_posts()) : ?>
        <div class="clm-search-meta">
            <?php
            global $wp_query;
            printf(
                /* translators: %d: number of results */
                _n(
                    'Found %d result',
                    'Found %d results',
                    $wp_query->found_posts,
                    'choir-lyrics-manager'
                ),
                $wp_query->found_posts
            );
            ?>
        </div>
        
        <ul class="clm-items-list">
            <?php while (have_posts()) : the_post(); ?>
                <li id="post-<?php the_ID(); ?>" class="clm-item <?php echo 'clm-' . get_post_type() . '-item'; ?>">
                    <h2 class="clm-item-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    
                    <div class="clm-item-meta">
                        <span class="clm-meta-type">
                            <?php
                            if (get_post_type() === 'clm_lyric') {
                                _e('Lyric', 'choir-lyrics-manager');
                            } elseif (get_post_type() === 'clm_album') {
                                _e('Album', 'choir-lyrics-manager');
                            } else {
                                echo get_post_type_object(get_post_type())->labels->singular_name;
                            }
                            ?>
                        </span>
                        
                        <?php
                        // Different meta for different post types
                        if (get_post_type() === 'clm_lyric') {
                            // Show composer if available
                            $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
                            if ($composer) {
                                echo ' | <span class="clm-meta-composer">' . __('Composer: ', 'choir-lyrics-manager') . esc_html($composer) . '</span>';
                            }
                            
                            // Show language if available
                            $language = get_post_meta(get_the_ID(), '_clm_language', true);
                            if ($language) {
                                echo ' | <span class="clm-meta-language">' . __('Language: ', 'choir-lyrics-manager') . esc_html($language) . '</span>';
                            }
                        } elseif (get_post_type() === 'clm_album') {
                            // Show release year if available
                            $release_year = get_post_meta(get_the_ID(), '_clm_release_year', true);
                            if ($release_year) {
                                echo ' | <span class="clm-meta-year">' . __('Year: ', 'choir-lyrics-manager') . esc_html($release_year) . '</span>';
                            }
                            
                            // Show director if available
                            $director = get_post_meta(get_the_ID(), '_clm_director', true);
                            if ($director) {
                                echo ' | <span class="clm-meta-director">' . __('Director: ', 'choir-lyrics-manager') . esc_html($director) . '</span>';
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="clm-item-excerpt">
                        <?php 
                        // Custom excerpt with highlighted search terms
                        $excerpt = get_the_excerpt();
                        $keys = explode(' ', get_search_query());
                        $excerpt = preg_replace('/(' . implode('|', $keys) . ')/iu', '<strong class="clm-search-highlight">$0</strong>', $excerpt);
                        echo $excerpt;
                        ?>
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
        <p class="clm-notice"><?php _e('No results found. Please try a different search.', 'choir-lyrics-manager'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();