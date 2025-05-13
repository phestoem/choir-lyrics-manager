<?php
/**
 * Custom Post Types for the plugin.
 *
 * Define and register all custom post types used by the plugin
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_CPT {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register custom post types used by the plugin.
     *
     * @since    1.0.0
     */
    public function register_post_types() {
        // Register Lyric CPT
        register_post_type('clm_lyric', array(
            'labels' => array(
                'name'               => _x('Lyrics', 'post type general name', 'choir-lyrics-manager'),
                'singular_name'      => _x('Lyric', 'post type singular name', 'choir-lyrics-manager'),
                'menu_name'          => _x('Lyrics', 'admin menu', 'choir-lyrics-manager'),
                'name_admin_bar'     => _x('Lyric', 'add new on admin bar', 'choir-lyrics-manager'),
                'add_new'            => _x('Add New', 'lyric', 'choir-lyrics-manager'),
                'add_new_item'       => __('Add New Lyric', 'choir-lyrics-manager'),
                'new_item'           => __('New Lyric', 'choir-lyrics-manager'),
                'edit_item'          => __('Edit Lyric', 'choir-lyrics-manager'),
                'view_item'          => __('View Lyric', 'choir-lyrics-manager'),
                'all_items'          => __('All Lyrics', 'choir-lyrics-manager'),
                'search_items'       => __('Search Lyrics', 'choir-lyrics-manager'),
                'parent_item_colon'  => __('Parent Lyrics:', 'choir-lyrics-manager'),
                'not_found'          => __('No lyrics found.', 'choir-lyrics-manager'),
                'not_found_in_trash' => __('No lyrics found in Trash.', 'choir-lyrics-manager')
            ),
            'description'         => __('Lyrics for songs and music pieces', 'choir-lyrics-manager'),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-format-audio',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'author', 'comments'),
            'has_archive'         => true,
            'rewrite'             => array('slug' => 'practice-logs'),
            'query_var'           => true,
            'show_in_rest'        => true,
        ));
        
        // Register meta boxes for post types
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        
        // Save post meta
        add_action('save_post', array($this, 'save_meta_box_data'));
    }
    
    /**
     * Register meta boxes for custom post types
     *
     * @since    1.0.0
     */
    public function register_meta_boxes() {
        // Meta box for lyric details
        add_meta_box(
            'clm_lyric_details',
            __('Lyric Details', 'choir-lyrics-manager'),
            array($this, 'render_lyric_details_meta_box'),
            'clm_lyric',
            'normal',
            'high'
        );
        
        // Meta box for album details
        add_meta_box(
            'clm_album_details',
            __('Album Details', 'choir-lyrics-manager'),
            array($this, 'render_album_details_meta_box'),
            'clm_album',
            'normal',
            'high'
        );
        
        // Meta box for practice log details
        add_meta_box(
            'clm_practice_details',
            __('Practice Details', 'choir-lyrics-manager'),
            array($this, 'render_practice_details_meta_box'),
            'clm_practice_log',
            'normal',
            'high'
        );
    }
    
    /**
     * Render lyric details meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_lyric_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('clm_lyric_details_meta_box', 'clm_lyric_details_meta_box_nonce');
        
        // Retrieve current values
        $composer = get_post_meta($post->ID, '_clm_composer', true);
        $arranger = get_post_meta($post->ID, '_clm_arranger', true);
        $year = get_post_meta($post->ID, '_clm_year', true);
        $language = get_post_meta($post->ID, '_clm_language', true);
        $difficulty = get_post_meta($post->ID, '_clm_difficulty', true);
        $performance_notes = get_post_meta($post->ID, '_clm_performance_notes', true);
        $sheet_music_id = get_post_meta($post->ID, '_clm_sheet_music_id', true);
        
        // Meta box content
        ?>
        <div class="clm-meta-box-container">
            <div class="clm-meta-field">
                <label for="clm_composer"><?php _e('Composer', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_composer" name="clm_composer" value="<?php echo esc_attr($composer); ?>" class="regular-text">
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_arranger"><?php _e('Arranger', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_arranger" name="clm_arranger" value="<?php echo esc_attr($arranger); ?>" class="regular-text">
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_year"><?php _e('Year', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_year" name="clm_year" value="<?php echo esc_attr($year); ?>" class="small-text">
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_language"><?php _e('Language', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_language" name="clm_language" value="<?php echo esc_attr($language); ?>" class="regular-text">
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_difficulty"><?php _e('Difficulty (1-5)', 'choir-lyrics-manager'); ?></label>
                <select id="clm_difficulty" name="clm_difficulty">
                    <option value=""><?php _e('Select Difficulty', 'choir-lyrics-manager'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($difficulty, $i); ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_performance_notes"><?php _e('Performance Notes', 'choir-lyrics-manager'); ?></label>
                <textarea id="clm_performance_notes" name="clm_performance_notes" rows="4" class="large-text"><?php echo esc_textarea($performance_notes); ?></textarea>
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_sheet_music_id"><?php _e('Sheet Music Attachment ID', 'choir-lyrics-manager'); ?></label>
                <input type="number" id="clm_sheet_music_id" name="clm_sheet_music_id" value="<?php echo esc_attr($sheet_music_id); ?>" class="small-text">
                <button type="button" class="button clm-upload-sheet-music"><?php _e('Upload Sheet Music', 'choir-lyrics-manager'); ?></button>
                <div id="clm-sheet-music-preview">
                    <?php if ($sheet_music_id) : ?>
                        <?php echo wp_get_attachment_link($sheet_music_id, 'thumbnail', false, true); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render album details meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_album_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('clm_album_details_meta_box', 'clm_album_details_meta_box_nonce');
        
        // Retrieve current values
        $release_year = get_post_meta($post->ID, '_clm_release_year', true);
        $director = get_post_meta($post->ID, '_clm_director', true);
        $lyrics = get_post_meta($post->ID, '_clm_lyrics', true);
        if (!is_array($lyrics)) {
            $lyrics = array();
        }
        
        // Meta box content
        ?>
        <div class="clm-meta-box-container">
            <div class="clm-meta-field">
                <label for="clm_release_year"><?php _e('Release Year', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_release_year" name="clm_release_year" value="<?php echo esc_attr($release_year); ?>" class="small-text">
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_director"><?php _e('Director/Conductor', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_director" name="clm_director" value="<?php echo esc_attr($director); ?>" class="regular-text">
            </div>
            
            <div class="clm-meta-field clm-lyrics-list">
                <label><?php _e('Lyrics in this Album', 'choir-lyrics-manager'); ?></label>
                <div class="clm-lyrics-container">
                    <?php
                    $all_lyrics = get_posts(array(
                        'post_type' => 'clm_lyric',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    
                    if ($all_lyrics) :
                    ?>
                        <select id="clm_lyrics_dropdown" class="widefat">
                            <option value=""><?php _e('Select a lyric to add', 'choir-lyrics-manager'); ?></option>
                            <?php foreach ($all_lyrics as $lyric) : ?>
                                <option value="<?php echo $lyric->ID; ?>"><?php echo esc_html($lyric->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="button clm-add-lyric"><?php _e('Add to Album', 'choir-lyrics-manager'); ?></button>
                        
                        <ul id="clm-selected-lyrics" class="clm-sortable-lyrics">
                            <?php foreach ($lyrics as $lyric_id) : ?>
                                <li data-id="<?php echo $lyric_id; ?>">
                                    <input type="hidden" name="clm_lyrics[]" value="<?php echo $lyric_id; ?>">
                                    <?php echo get_the_title($lyric_id); ?>
                                    <a href="#" class="clm-remove-lyric dashicons dashicons-no"></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p><?php _e('No lyrics found. Create some lyrics first.', 'choir-lyrics-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render practice details meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_practice_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('clm_practice_details_meta_box', 'clm_practice_details_meta_box_nonce');
        
        // Retrieve current values
        $lyric_id = get_post_meta($post->ID, '_clm_lyric_id', true);
        $practice_date = get_post_meta($post->ID, '_clm_practice_date', true);
        $duration = get_post_meta($post->ID, '_clm_duration', true);
        $confidence = get_post_meta($post->ID, '_clm_confidence', true);
        $notes = get_post_meta($post->ID, '_clm_practice_notes', true);
        
        // Meta box content
        ?>
        <div class="clm-meta-box-container">
            <div class="clm-meta-field">
                <label for="clm_lyric_id"><?php _e('Lyric', 'choir-lyrics-manager'); ?></label>
                <select id="clm_lyric_id" name="clm_lyric_id" class="widefat">
                    <option value=""><?php _e('Select a lyric', 'choir-lyrics-manager'); ?></option>
                    <?php
                    $lyrics = get_posts(array(
                        'post_type' => 'clm_lyric',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    
                    foreach ($lyrics as $lyric) :
                    ?>
                        <option value="<?php echo $lyric->ID; ?>" <?php selected($lyric_id, $lyric->ID); ?>><?php echo esc_html($lyric->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_practice_date"><?php _e('Practice Date', 'choir-lyrics-manager'); ?></label>
                <input type="date" id="clm_practice_date" name="clm_practice_date" value="<?php echo esc_attr($practice_date); ?>" class="regular-text">
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_duration"><?php _e('Duration (minutes)', 'choir-lyrics-manager'); ?></label>
                <input type="number" id="clm_duration" name="clm_duration" value="<?php echo esc_attr($duration); ?>" class="small-text" min="1" step="1">
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_confidence"><?php _e('Confidence Level (1-5)', 'choir-lyrics-manager'); ?></label>
                <select id="clm_confidence" name="clm_confidence">
                    <option value=""><?php _e('Select Confidence Level', 'choir-lyrics-manager'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($confidence, $i); ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_practice_notes"><?php _e('Practice Notes', 'choir-lyrics-manager'); ?></label>
                <textarea id="clm_practice_notes" name="clm_practice_notes" rows="4" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save meta box data
     *
     * @since    1.0.0
     * @param    int        $post_id    The post ID.
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set for lyric details
        if (isset($_POST['clm_lyric_details_meta_box_nonce'])) {
            // Verify that the nonce is valid
            if (!wp_verify_nonce($_POST['clm_lyric_details_meta_box_nonce'], 'clm_lyric_details_meta_box')) {
                return;
            }
            
            // Save lyric details
            if (isset($_POST['clm_composer'])) {
                update_post_meta($post_id, '_clm_composer', sanitize_text_field($_POST['clm_composer']));
            }
            
            if (isset($_POST['clm_arranger'])) {
                update_post_meta($post_id, '_clm_arranger', sanitize_text_field($_POST['clm_arranger']));
            }
            
            if (isset($_POST['clm_year'])) {
                update_post_meta($post_id, '_clm_year', sanitize_text_field($_POST['clm_year']));
            }
            
            if (isset($_POST['clm_language'])) {
                update_post_meta($post_id, '_clm_language', sanitize_text_field($_POST['clm_language']));
            }
            
            if (isset($_POST['clm_difficulty'])) {
                update_post_meta($post_id, '_clm_difficulty', intval($_POST['clm_difficulty']));
            }
            
            if (isset($_POST['clm_performance_notes'])) {
                update_post_meta($post_id, '_clm_performance_notes', sanitize_textarea_field($_POST['clm_performance_notes']));
            }
            
            if (isset($_POST['clm_sheet_music_id'])) {
                update_post_meta($post_id, '_clm_sheet_music_id', intval($_POST['clm_sheet_music_id']));
            }
        }
        
        // Check if our nonce is set for album details
        if (isset($_POST['clm_album_details_meta_box_nonce'])) {
            // Verify that the nonce is valid
            if (!wp_verify_nonce($_POST['clm_album_details_meta_box_nonce'], 'clm_album_details_meta_box')) {
                return;
            }
            
            // Save album details
            if (isset($_POST['clm_release_year'])) {
                update_post_meta($post_id, '_clm_release_year', sanitize_text_field($_POST['clm_release_year']));
            }
            
            if (isset($_POST['clm_director'])) {
                update_post_meta($post_id, '_clm_director', sanitize_text_field($_POST['clm_director']));
            }
            
            // Save lyrics list
            $lyrics = isset($_POST['clm_lyrics']) ? array_map('intval', $_POST['clm_lyrics']) : array();
            update_post_meta($post_id, '_clm_lyrics', $lyrics);
        }
        
        // Check if our nonce is set for practice details
        if (isset($_POST['clm_practice_details_meta_box_nonce'])) {
            // Verify that the nonce is valid
            if (!wp_verify_nonce($_POST['clm_practice_details_meta_box_nonce'], 'clm_practice_details_meta_box')) {
                return;
            }
            
            // Save practice details
            if (isset($_POST['clm_lyric_id'])) {
                update_post_meta($post_id, '_clm_lyric_id', intval($_POST['clm_lyric_id']));
            }
            
            if (isset($_POST['clm_practice_date'])) {
                update_post_meta($post_id, '_clm_practice_date', sanitize_text_field($_POST['clm_practice_date']));
            }
            
            if (isset($_POST['clm_duration'])) {
                update_post_meta($post_id, '_clm_duration', intval($_POST['clm_duration']));
            }
            
            if (isset($_POST['clm_confidence'])) {
                update_post_meta($post_id, '_clm_confidence', intval($_POST['clm_confidence']));
            }
            
            if (isset($_POST['clm_practice_notes'])) {
                update_post_meta($post_id, '_clm_practice_notes', sanitize_textarea_field($_POST['clm_practice_notes']));
            }
        }
    }
    
    /**
     * Define custom columns for lyric post type
     *
     * @since    1.0.0
     * @param    array    $columns    The default columns.
     * @return   array                Modified columns.
     */
    public function set_custom_lyric_columns($columns) {
        $columns = array(
            'cb' => $columns['cb'],
            'title' => __('Title', 'choir-lyrics-manager'),
            'composer' => __('Composer', 'choir-lyrics-manager'),
            'language' => __('Language', 'choir-lyrics-manager'),
            'difficulty' => __('Difficulty', 'choir-lyrics-manager'),
            'genres' => __('Genres', 'choir-lyrics-manager'),
            'date' => __('Date', 'choir-lyrics-manager')
        );
        
        return $columns;
    }
    
    /**
     * Display custom column content for lyric post type
     *
     * @since    1.0.0
     * @param    string    $column     The column name.
     * @param    int       $post_id    The post ID.
     */
    public function custom_lyric_column($column, $post_id) {
        switch ($column) {
            case 'composer':
                echo get_post_meta($post_id, '_clm_composer', true);
                break;
                
            case 'language':
                echo get_post_meta($post_id, '_clm_language', true);
                break;
                
            case 'difficulty':
                $difficulty = get_post_meta($post_id, '_clm_difficulty', true);
                if ($difficulty) {
                    $stars = '';
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $difficulty) {
                            $stars .= '<span class="dashicons dashicons-star-filled"></span>';
                        } else {
                            $stars .= '<span class="dashicons dashicons-star-empty"></span>';
                        }
                    }
                    echo $stars;
                }
                break;
                
            case 'genres':
                $terms = get_the_terms($post_id, 'clm_genre');
                if ($terms && !is_wp_error($terms)) {
                    $genres = array();
                    foreach ($terms as $term) {
                        $genres[] = $term->name;
                    }
                    echo implode(', ', $genres);
                }
                break;
        }
    }
}