<?php
/**
 * Template for displaying search form
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get shortcode attributes if this is called from a shortcode
$placeholder = isset($atts) && isset($atts['placeholder']) ? $atts['placeholder'] : __('Search lyrics...', 'choir-lyrics-manager');
$button_text = isset($atts) && isset($atts['button_text']) ? $atts['button_text'] : __('Search', 'choir-lyrics-manager');
$show_filters = isset($atts) && isset($atts['show_filters']) ? $atts['show_filters'] === 'yes' : true;

?>

<div class="clm-search-form">
    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <input type="hidden" name="post_type" value="clm_lyric">
        
        <div class="clm-search-input-wrap">
            <input type="search" class="clm-search-input" placeholder="<?php echo esc_attr($placeholder); ?>" value="<?php echo get_search_query(); ?>" name="s" />
            <button type="submit" class="clm-search-button"><?php echo esc_html($button_text); ?></button>
        </div>
        
        <?php if ($show_filters): ?>
            <div class="clm-search-filters-toggle">
                <a href="#" class="clm-toggle-filters"><?php _e('Show Filters', 'choir-lyrics-manager'); ?></a>
            </div>
            
            <div class="clm-search-filters" style="display: none;">
                <div class="clm-search-filter clm-genre-filter">
                    <label for="clm-search-genre"><?php _e('Genre', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-search-genre" name="genre">
                        <option value=""><?php _e('Any Genre', 'choir-lyrics-manager'); ?></option>
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
                
                <div class="clm-search-filter clm-language-filter">
                    <label for="clm-search-language"><?php _e('Language', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-search-language" name="language">
                        <option value=""><?php _e('Any Language', 'choir-lyrics-manager'); ?></option>
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
                
                <div class="clm-search-filter clm-difficulty-filter">
                    <label for="clm-search-difficulty"><?php _e('Difficulty', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-search-difficulty" name="difficulty">
                        <option value=""><?php _e('Any Difficulty', 'choir-lyrics-manager'); ?></option>
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
                
                <div class="clm-search-filter clm-composer-filter">
                    <label for="clm-search-composer"><?php _e('Composer', 'choir-lyrics-manager'); ?></label>
                    <select id="clm-search-composer" name="composer">
                        <option value=""><?php _e('Any Composer', 'choir-lyrics-manager'); ?></option>
                        <?php
                        $composers = get_terms(array(
                            'taxonomy' => 'clm_composer',
                            'hide_empty' => true,
                        ));
                        
                        foreach ($composers as $composer) {
                            $selected = isset($_GET['composer']) && $_GET['composer'] == $composer->slug ? 'selected' : '';
                            echo '<option value="' . esc_attr($composer->slug) . '" ' . $selected . '>' . esc_html($composer->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="clm-search-filter clm-filter-actions">
                    <button type="submit" class="clm-button clm-button-small"><?php _e('Apply Filters', 'choir-lyrics-manager'); ?></button>
                    <a href="<?php echo esc_url(get_post_type_archive_link('clm_lyric')); ?>" class="clm-reset-filters clm-button-text"><?php _e('Reset', 'choir-lyrics-manager'); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($show_filters): ?>
<script>
    // Simple JavaScript for toggling search filters
    document.addEventListener('DOMContentLoaded', function() {
        var toggleButton = document.querySelector('.clm-toggle-filters');
        var filtersContainer = document.querySelector('.clm-search-filters');
        
        if (toggleButton && filtersContainer) {
            // Show filters if they were used
            <?php if (isset($_GET['genre']) || isset($_GET['language']) || isset($_GET['difficulty']) || isset($_GET['composer'])): ?>
                filtersContainer.style.display = 'flex';
                toggleButton.textContent = '<?php _e('Hide Filters', 'choir-lyrics-manager'); ?>';
            <?php endif; ?>
            
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (filtersContainer.style.display === 'none' || filtersContainer.style.display === '') {
                    filtersContainer.style.display = 'flex';
                    toggleButton.textContent = '<?php _e('Hide Filters', 'choir-lyrics-manager'); ?>';
                } else {
                    filtersContainer.style.display = 'none';
                    toggleButton.textContent = '<?php _e('Show Filters', 'choir-lyrics-manager'); ?>';
                }
            });
        }
    });
</script>
<?php endif; ?>