<?php
/**
 * Template Part: Lyric Item for Shortcode
 * 
 * @package Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined("ABSPATH")) {
    exit;
}

if ($query->have_posts()) : ?>
    <ul class="clm-items-list">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <li id="lyric-<?php the_ID(); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
                <div class="clm-item-card">
                    <h2 class="clm-item-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    
                    <div class="clm-item-meta">
                        <?php
                        $meta_items = array();
                        
                        // Show composer if available
                        $composer = get_post_meta(get_the_ID(), "_clm_composer", true);
                        if ($composer) {
                            $meta_items[] = '<span class="clm-meta-composer"><strong>' . __("Composer:", "choir-lyrics-manager") . '</strong> ' . esc_html($composer) . '</span>';
                        }
                        
                        // Show language if available
                        $language = get_post_meta(get_the_ID(), "_clm_language", true);
                        if ($language) {
                            $meta_items[] = '<span class="clm-meta-language"><strong>' . __("Language:", "choir-lyrics-manager") . '</strong> ' . esc_html($language) . '</span>';
                        }
                        
                        echo implode(' <span class="clm-meta-separator">•</span> ', $meta_items);
                        ?>
                    </div>
                    
                    <div class="clm-item-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    
                    <div class="clm-item-actions">
                        <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e("View Lyric", "choir-lyrics-manager"); ?></a>
                    </div>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else : ?>
    <div class="clm-no-results">
        <p class="clm-notice"><?php _e("No lyrics found matching your criteria.", "choir-lyrics-manager"); ?></p>
    </div>
<?php endif;

wp_reset_postdata();