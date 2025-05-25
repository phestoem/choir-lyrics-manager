<?php
/**
 * Custom Post Types for the Choir Lyrics Manager plugin.
 *
 * Define and register all custom post types used by the plugin
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 * @author     CLM Development Team
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
     * @param    string    $version    The version of this plugin.
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
        $this->register_lyric_post_type();
        $this->register_practice_log_post_type();
        $this->register_playlist_post_type();

        // Register meta boxes and admin customizations
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'), 10, 2);
        
        // Admin columns for lyrics
        add_filter('manage_clm_lyric_posts_columns', array($this, 'set_custom_lyric_columns'));
        add_action('manage_clm_lyric_posts_custom_column', array($this, 'custom_lyric_column'), 10, 2);
        
        // Admin columns for practice logs
        add_filter('manage_clm_practice_log_posts_columns', array($this, 'set_custom_practice_log_columns'));
        add_action('manage_clm_practice_log_posts_custom_column', array($this, 'custom_practice_log_column'), 10, 2);

         // Optional: Admin columns for playlists
         add_filter('manage_clm_playlist_posts_columns', array($this, 'set_custom_playlist_columns'));
         add_action('manage_clm_playlist_posts_custom_column', array($this, 'custom_playlist_column'), 10, 2);
    }

     // Optional: Custom Columns for Playlists
     public function set_custom_playlist_columns($columns) {
        unset($columns['date']); // Remove default date
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => __('Playlist Title', 'choir-lyrics-manager'),
            'author' => __('Owner', 'choir-lyrics-manager'),
            'clm_track_count' => __('Tracks', 'choir-lyrics-manager'),
            'clm_visibility' => __('Visibility', 'choir-lyrics-manager'),
            'date' => __('Created Date', 'choir-lyrics-manager'),
        );
        return $new_columns;
    }

    public function custom_playlist_column($column, $post_id) {
        switch ($column) {
            case 'clm_track_count':
                $lyric_ids = get_post_meta($post_id, '_clm_playlist_lyrics', true);
                echo is_array($lyric_ids) ? count($lyric_ids) : 0;
                break;
            case 'clm_visibility':
                $visibility = get_post_meta($post_id, '_clm_playlist_visibility', true);
                echo esc_html(ucfirst($visibility ?: 'private'));
                break;
        }
    }

    /**
     * Register the Playlist custom post type.
     *
     * @since    1.2.3 (or current version)
     */
    private function register_playlist_post_type() {
        $labels = array(
            'name'                  => _x('Playlists', 'Post type general name', 'choir-lyrics-manager'),
            'singular_name'         => _x('Playlist', 'Post type singular name', 'choir-lyrics-manager'),
            'menu_name'             => _x('Playlists', 'Admin Menu text', 'choir-lyrics-manager'),
            'name_admin_bar'        => _x('Playlist', 'Add New on Toolbar', 'choir-lyrics-manager'),
            'add_new'               => __('Add New Playlist', 'choir-lyrics-manager'), // More specific
            'add_new_item'          => __('Add New Playlist', 'choir-lyrics-manager'),
            'new_item'              => __('New Playlist', 'choir-lyrics-manager'),
            'edit_item'             => __('Edit Playlist', 'choir-lyrics-manager'),
            'view_item'             => __('View Playlist', 'choir-lyrics-manager'),
            'all_items'             => __('All Playlists', 'choir-lyrics-manager'),
            'search_items'          => __('Search Playlists', 'choir-lyrics-manager'),
            'not_found'             => __('No playlists found.', 'choir-lyrics-manager'),
            'not_found_in_trash'    => __('No playlists found in Trash.', 'choir-lyrics-manager'),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('User-created playlists of lyrics.', 'choir-lyrics-manager'),
            'public'             => false, // Usually not public unless you build a frontend for them
            'publicly_queryable' => current_user_can('manage_options'), // Only queryable by admins, or based on specific logic
            'show_ui'            => true,
            'show_in_menu'       => 'clm_dashboard', // << MAKE IT SUBMENU OF YOUR MAIN PLUGIN PAGE
            'capability_type'    => 'post', // Consider 'clm_playlist' for custom capabilities
            'map_meta_cap'       => true,
            'hierarchical'       => false,
            'supports'           => array('title', 'author', 'editor' /* for description */, 'custom-fields'),
            'has_archive'        => false, // No public archive page by default
            'rewrite'            => false, // No public permalinks by default
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-list-view', // Or 'dashicons-format-audio', 'dashicons-playlist-audio'
        );

        register_post_type('clm_playlist', $args);
    }


    /**
     * Register the Lyric custom post type.
     *
     * @since    1.0.0
     */
    private function register_lyric_post_type() {
        $labels = array(
            'name'                  => _x('Lyrics', 'Post type general name', 'choir-lyrics-manager'),
            'singular_name'         => _x('Lyric', 'Post type singular name', 'choir-lyrics-manager'),
            'menu_name'             => _x('Lyrics', 'Admin Menu text', 'choir-lyrics-manager'),
            'name_admin_bar'        => _x('Lyric', 'Add New on Toolbar', 'choir-lyrics-manager'),
            'add_new'               => __('Add New', 'choir-lyrics-manager'),
            'add_new_item'          => __('Add New Lyric', 'choir-lyrics-manager'),
            'new_item'              => __('New Lyric', 'choir-lyrics-manager'),
            'edit_item'             => __('Edit Lyric', 'choir-lyrics-manager'),
            'view_item'             => __('View Lyric', 'choir-lyrics-manager'),
            'all_items'             => __('All Lyrics', 'choir-lyrics-manager'),
            'search_items'          => __('Search Lyrics', 'choir-lyrics-manager'),
            'parent_item_colon'     => __('Parent Lyrics:', 'choir-lyrics-manager'),
            'not_found'             => __('No lyrics found.', 'choir-lyrics-manager'),
            'not_found_in_trash'    => __('No lyrics found in Trash.', 'choir-lyrics-manager'),
            'featured_image'        => _x('Featured Image', 'Overrides the "Featured Image" phrase for this post type. Added in 4.3', 'choir-lyrics-manager'),
            'set_featured_image'    => _x('Set featured image', 'Overrides the "Set featured image" phrase for this post type. Added in 4.3', 'choir-lyrics-manager'),
            'remove_featured_image' => _x('Remove featured image', 'Overrides the "Remove featured image" phrase for this post type. Added in 4.3', 'choir-lyrics-manager'),
            'use_featured_image'    => _x('Use as featured image', 'Overrides the "Use as featured image" phrase for this post type. Added in 4.3', 'choir-lyrics-manager'),
            'archives'              => _x('Lyric archives', 'The post type archive label used in nav menus. Default "Post Archives". Added in 4.4', 'choir-lyrics-manager'),
            'insert_into_item'      => _x('Insert into lyric', 'Overrides the "Insert into post"/"Insert into page" phrase (used when inserting media into a post). Added in 4.4', 'choir-lyrics-manager'),
            'uploaded_to_this_item' => _x('Uploaded to this lyric', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase (used when viewing media attached to a post). Added in 4.4', 'choir-lyrics-manager'),
            'filter_items_list'     => _x('Filter lyrics list', 'Screen reader text for the filter links heading on the post type listing screen. Default "Filter posts list"/"Filter pages list". Added in 4.4', 'choir-lyrics-manager'),
            'items_list_navigation' => _x('Lyrics list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default "Posts list navigation"/"Pages list navigation". Added in 4.4', 'choir-lyrics-manager'),
            'items_list'            => _x('Lyrics list', 'Screen reader text for the items list heading on the post type listing screen. Default "Posts list"/"Pages list". Added in 4.4', 'choir-lyrics-manager'),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Lyrics for songs and music pieces', 'choir-lyrics-manager'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'clm_dashboard',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-format-audio',
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions'),
            'has_archive'        => true,
            'rewrite'            => array(
                'slug'       => 'lyrics',
                'with_front' => false,
                'feeds'      => true,
                'pages'      => true,
            ),
            'query_var'          => true,
            'show_in_rest'       => true,
            'rest_base'          => 'lyrics',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        register_post_type('clm_lyric', $args);
    }

    /**
     * Register the Practice Log custom post type.
     *
     * @since    1.0.0
     */
    private function register_practice_log_post_type() {
        $labels = array(
            'name'                  => _x('Practice Logs', 'Post type general name', 'choir-lyrics-manager'),
            'singular_name'         => _x('Practice Log', 'Post type singular name', 'choir-lyrics-manager'),
            'menu_name'             => _x('Practice Logs', 'Admin Menu text', 'choir-lyrics-manager'),
            'name_admin_bar'        => _x('Practice Log', 'Add New on Toolbar', 'choir-lyrics-manager'),
            'add_new'               => __('Add New', 'choir-lyrics-manager'),
            'add_new_item'          => __('Add New Practice Log', 'choir-lyrics-manager'),
            'new_item'              => __('New Practice Log', 'choir-lyrics-manager'),
            'edit_item'             => __('Edit Practice Log', 'choir-lyrics-manager'),
            'view_item'             => __('View Practice Log', 'choir-lyrics-manager'),
            'all_items'             => __('All Practice Logs', 'choir-lyrics-manager'),
            'search_items'          => __('Search Practice Logs', 'choir-lyrics-manager'),
            'not_found'             => __('No practice logs found.', 'choir-lyrics-manager'),
            'not_found_in_trash'    => __('No practice logs found in Trash.', 'choir-lyrics-manager'),
            'filter_items_list'     => _x('Filter practice logs list', 'Screen reader text for the filter links heading on the post type listing screen. Added in 4.4', 'choir-lyrics-manager'),
            'items_list_navigation' => _x('Practice logs list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Added in 4.4', 'choir-lyrics-manager'),
            'items_list'            => _x('Practice logs list', 'Screen reader text for the items list heading on the post type listing screen. Added in 4.4', 'choir-lyrics-manager'),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Individual practice session records for lyrics.', 'choir-lyrics-manager'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'clm_dashboard',
            'show_in_nav_menus'  => false,
            'show_in_admin_bar'  => false,
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'hierarchical'       => false,
            'supports'           => array('title', 'author', 'custom-fields'),
            'has_archive'        => false,
            'rewrite'            => false,
            'query_var'          => false,
            'show_in_rest'       => true,
            'rest_base'          => 'practice-logs',
        );

        register_post_type('clm_practice_log', $args);
    }

    /**
     * Register meta boxes for custom post types.
     *
     * @since    1.0.0
     */
    public function register_meta_boxes() {
        // Meta box for lyric details
        add_meta_box(
            'clm_lyric_details_mb',
            __('Lyric Details', 'choir-lyrics-manager'),
            array($this, 'render_lyric_details_meta_box'),
            'clm_lyric',
            'normal',
            'high'
        );

        // Meta box for practice log details
        add_meta_box(
            'clm_practice_log_details_mb',
            __('Practice Log Details', 'choir-lyrics-manager'),
            array($this, 'render_practice_log_details_meta_box'),
            'clm_practice_log',
            'normal',
            'high'
        );

        // Meta box for playlist details (e.g., visibility if not using post status)
        add_meta_box(
            'clm_playlist_details_mb',
            __('Playlist Details', 'choir-lyrics-manager'),
            array($this, 'render_playlist_details_meta_box'),
            'clm_playlist',
            'side', // Or 'normal'
            'default'
        );
    }


    public function render_playlist_details_meta_box($post) {
        wp_nonce_field('clm_save_playlist_details_nonce', 'clm_playlist_details_nonce_field'); // Corrected nonce names
        $visibility = get_post_meta($post->ID, '_clm_playlist_visibility', true) ?: 'private';
        $lyrics_count = 0;
        $lyric_ids = get_post_meta($post->ID, '_clm_playlist_lyrics', true);
        if (is_array($lyric_ids)) {
            $lyrics_count = count($lyric_ids);
        }
        ?>
        <p>
            <label for="clm_playlist_visibility_field"><?php _e('Visibility:', 'choir-lyrics-manager'); ?></label><br>
            <select name="clm_playlist_visibility_field" id="clm_playlist_visibility_field">
                <option value="private" <?php selected($visibility, 'private'); ?>><?php _e('Private (only me)', 'choir-lyrics-manager'); ?></option>
                <option value="public" <?php selected($visibility, 'public'); ?>><?php _e('Public (viewable with link - future feature)', 'choir-lyrics-manager'); ?></option>
            </select>
        </p>
        <p>
            <?php printf(_n('%d track in this playlist.', '%d tracks in this playlist.', $lyrics_count, 'choir-lyrics-manager'), $lyrics_count); ?>
        </p>
        <p class="description">
            <?php _e('Manage actual tracks by editing the playlist on the frontend dashboard or via lyric pages.', 'choir-lyrics-manager'); ?>
        </p>
        <?php
    }

    /**
     * Render lyric details meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_lyric_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('clm_save_lyric_details', 'clm_lyric_details_nonce');

        // Get current values
        $composer = get_post_meta($post->ID, '_clm_composer', true);
        $arranger = get_post_meta($post->ID, '_clm_arranger', true);
        $year = get_post_meta($post->ID, '_clm_year', true);
        $performance_notes = get_post_meta($post->ID, '_clm_performance_notes', true);
        
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="clm_composer"><?php _e('Composer', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="clm_composer" 
                               name="clm_composer" 
                               value="<?php echo esc_attr($composer); ?>" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e('Enter composer name', 'choir-lyrics-manager'); ?>">
                        <p class="description"><?php _e('The composer of this piece.', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="clm_arranger"><?php _e('Arranger', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="clm_arranger" 
                               name="clm_arranger" 
                               value="<?php echo esc_attr($arranger); ?>" 
                               class="regular-text"
                               placeholder="<?php esc_attr_e('Enter arranger name', 'choir-lyrics-manager'); ?>">
                        <p class="description"><?php _e('The arranger of this piece (if different from composer).', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="clm_year"><?php _e('Year', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="clm_year" 
                               name="clm_year" 
                               value="<?php echo esc_attr($year); ?>" 
                               class="small-text" 
                               min="1000" 
                               max="<?php echo date('Y') + 10; ?>"
                               placeholder="<?php echo date('Y'); ?>">
                        <p class="description"><?php _e('The year this piece was composed or arranged.', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="clm_performance_notes"><?php _e('Performance Notes', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <textarea id="clm_performance_notes" 
                                  name="clm_performance_notes" 
                                  rows="4" 
                                  class="large-text"
                                  placeholder="<?php esc_attr_e('Add any performance notes, tempo markings, or special instructions...', 'choir-lyrics-manager'); ?>"><?php echo esc_textarea($performance_notes); ?></textarea>
                        <p class="description"><?php _e('Special notes for performing this piece (tempo, dynamics, etc.).', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render practice log details meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_practice_log_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('clm_save_practice_log_details', 'clm_practice_log_details_nonce');

        // Get current values
        $member_id = get_post_meta($post->ID, '_clm_member_id', true);
        $lyric_id = get_post_meta($post->ID, '_clm_lyric_id', true);
        $practice_date = get_post_meta($post->ID, '_clm_practice_date', true);
        $duration = get_post_meta($post->ID, '_clm_duration_minutes', true);
        $confidence = get_post_meta($post->ID, '_clm_confidence_rating', true);
        $notes = get_post_meta($post->ID, '_clm_practice_notes', true);

        // Set default date if empty
        if (empty($practice_date)) {
            $practice_date = date('Y-m-d');
        }
        
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="clm_log_member_id"><?php _e('Member', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <?php $this->render_posts_dropdown('clm_member', 'clm_log_member_id', $member_id, __('Select Member', 'choir-lyrics-manager')); ?>
                        <p class="description"><?php _e('The choir member who practiced.', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="clm_log_lyric_id"><?php _e('Lyric Practiced', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <?php $this->render_posts_dropdown('clm_lyric', 'clm_log_lyric_id', $lyric_id, __('Select Lyric', 'choir-lyrics-manager')); ?>
                        <p class="description"><?php _e('The lyric that was practiced.', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="clm_log_practice_date"><?php _e('Practice Date', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <input type="date" 
                               id="clm_log_practice_date" 
                               name="clm_log_practice_date" 
                               value="<?php echo esc_attr($practice_date); ?>" 
                               class="regular-text" 
                               max="<?php echo date('Y-m-d'); ?>">
                        <p class="description"><?php _e('The date when this practice session occurred.', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="clm_log_duration_minutes"><?php _e('Duration (minutes)', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="clm_log_duration_minutes" 
                               name="clm_log_duration_minutes" 
                               value="<?php echo esc_attr($duration); ?>" 
                               class="small-text" 
                               min="1" 
                               max="480" 
                               step="1"
                               placeholder="30">
                        <span class="description"><?php _e('minutes', 'choir-lyrics-manager'); ?></span>
                        <p class="description"><?php _e('How long was this practice session?', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="clm_log_confidence_rating"><?php _e('Confidence Rating', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <select id="clm_log_confidence_rating" name="clm_log_confidence_rating">
                            <option value=""><?php _e('Select confidence level', 'choir-lyrics-manager'); ?></option>
                            <option value="1" <?php selected($confidence, '1'); ?>><?php _e('1 - Just started learning', 'choir-lyrics-manager'); ?></option>
                            <option value="2" <?php selected($confidence, '2'); ?>><?php _e('2 - Getting familiar', 'choir-lyrics-manager'); ?></option>
                            <option value="3" <?php selected($confidence, '3'); ?>><?php _e('3 - Comfortable', 'choir-lyrics-manager'); ?></option>
                            <option value="4" <?php selected($confidence, '4'); ?>><?php _e('4 - Very confident', 'choir-lyrics-manager'); ?></option>
                            <option value="5" <?php selected($confidence, '5'); ?>><?php _e('5 - Performance ready', 'choir-lyrics-manager'); ?></option>
                        </select>
                        <p class="description"><?php _e('Rate your confidence level after this practice session.', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="clm_log_practice_notes_field"><?php _e('Session Notes', 'choir-lyrics-manager'); ?></label>
                    </th>
                    <td>
                        <textarea id="clm_log_practice_notes_field" 
                                  name="clm_log_practice_notes_field" 
                                  rows="4" 
                                  class="large-text"
                                  placeholder="<?php esc_attr_e('Add notes about this practice session (challenges, breakthroughs, areas to focus on next time, etc.)', 'choir-lyrics-manager'); ?>"><?php echo esc_textarea($notes); ?></textarea>
                        <p class="description"><?php _e('Optional notes about this practice session.', 'choir-lyrics-manager'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render a dropdown for selecting posts of a specific post type.
     *
     * @since    1.0.0
     * @param    string    $post_type        The post type to query.
     * @param    string    $name            The name attribute for the select element.
     * @param    mixed     $selected        The currently selected value.
     * @param    string    $default_text    Default option text.
     * @param    array     $args            Additional arguments for get_posts().
     */
    private function render_posts_dropdown($post_type, $name, $selected = '', $default_text = '', $args = array()) {
        $default_args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => array(),
            'suppress_filters' => false,
        );

        $args = wp_parse_args($args, $default_args);
        $posts = get_posts($args);

        printf('<select id="%s" name="%s" class="regular-text">', 
               esc_attr(str_replace(array('[', ']'), array('_', ''), $name)), 
               esc_attr($name)
        );

        if ($default_text) {
            printf('<option value="">%s</option>', esc_html($default_text));
        }

        if (!empty($posts)) {
            foreach ($posts as $post_item) {
                printf('<option value="%d" %s>%s</option>',
                       $post_item->ID,
                       selected($selected, $post_item->ID, false),
                       esc_html($post_item->post_title)
                );
            }
        } else {
            printf('<option value="" disabled>%s</option>', 
                   sprintf(__('No %s found', 'choir-lyrics-manager'), $post_type)
            );
        }

        echo '</select>';
    }

    /**
     * Save meta box data.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function save_meta_box_data($post_id, $post) {
       // Security checks at the top
       if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
       if (wp_is_post_revision($post_id)) return;
       if (!current_user_can('edit_post', $post_id)) return;

        $current_post_type = $post->post_type; // Use $post->post_type for reliability


        if ($current_post_type === 'clm_lyric' && isset($_POST['clm_lyric_details_nonce']) && wp_verify_nonce($_POST['clm_lyric_details_nonce'], 'clm_save_lyric_details')) {
            $this->save_lyric_meta_data($post_id);
        }

        if ($current_post_type === 'clm_practice_log' && isset($_POST['clm_practice_log_details_nonce']) && wp_verify_nonce($_POST['clm_practice_log_details_nonce'], 'clm_save_practice_log_details')) {
            $this->save_practice_log_meta_data($post_id);
        }

        if ($current_post_type === 'clm_playlist' && isset($_POST['clm_playlist_details_nonce_field']) && wp_verify_nonce($_POST['clm_playlist_details_nonce_field'], 'clm_save_playlist_details_nonce')) { // Corrected nonce name
            if (isset($_POST['clm_playlist_visibility_field'])) { // Corrected field name
                update_post_meta($post_id, '_clm_playlist_visibility', sanitize_text_field($_POST['clm_playlist_visibility_field']));
            }
        }    

    }

    /**
     * Save lyric meta data.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    private function save_lyric_meta_data($post_id) {
        $fields_to_save = array(
            '_clm_composer' => array(
                'post_key' => 'clm_composer',
                'sanitize' => 'sanitize_text_field'
            ),
            '_clm_arranger' => array(
                'post_key' => 'clm_arranger',
                'sanitize' => 'sanitize_text_field'
            ),
            '_clm_year' => array(
                'post_key' => 'clm_year',
                'sanitize' => 'absint'
            ),
            '_clm_performance_notes' => array(
                'post_key' => 'clm_performance_notes',
                'sanitize' => 'sanitize_textarea_field'
            ),
        );

        foreach ($fields_to_save as $meta_key => $field_config) {
            if (isset($_POST[$field_config['post_key']])) {
                $value = $_POST[$field_config['post_key']];
                
                // Apply sanitization
                if (function_exists($field_config['sanitize'])) {
                    $value = call_user_func($field_config['sanitize'], $value);
                } else {
                    $value = sanitize_text_field($value);
                }

                // Special handling for year
                if ($meta_key === '_clm_year' && !empty($value)) {
                    $current_year = (int) date('Y');
                    if ($value < 1000 || $value > ($current_year + 10)) {
                        continue; // Skip invalid years
                    }
                }

                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }

    /**
     * Save practice log meta data.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    private function save_practice_log_meta_data($post_id) {
        $fields_to_save = array(
            '_clm_member_id' => array(
                'post_key' => 'clm_log_member_id',
                'sanitize' => 'absint'
            ),
            '_clm_lyric_id' => array(
                'post_key' => 'clm_log_lyric_id',
                'sanitize' => 'absint'
            ),
            '_clm_practice_date' => array(
                'post_key' => 'clm_log_practice_date',
                'sanitize' => 'sanitize_text_field'
            ),
            '_clm_duration_minutes' => array(
                'post_key' => 'clm_log_duration_minutes',
                'sanitize' => 'absint'
            ),
            '_clm_confidence_rating' => array(
                'post_key' => 'clm_log_confidence_rating',
                'sanitize' => 'absint'
            ),
            '_clm_practice_notes' => array(
                'post_key' => 'clm_log_practice_notes_field',
                'sanitize' => 'sanitize_textarea_field'
            ),
        );

        foreach ($fields_to_save as $meta_key => $field_config) {
            if (isset($_POST[$field_config['post_key']])) {
                $value = $_POST[$field_config['post_key']];
                
                // Apply sanitization
                if (function_exists($field_config['sanitize'])) {
                    $value = call_user_func($field_config['sanitize'], $value);
                } else {
                    $value = sanitize_text_field($value);
                }

                // Special validation
                if ($meta_key === '_clm_confidence_rating' && !empty($value)) {
                    $value = max(1, min(5, $value)); // Ensure 1-5 range
                }

                if ($meta_key === '_clm_duration_minutes' && !empty($value)) {
                    $value = max(1, min(480, $value)); // Max 8 hours
                }

                if ($meta_key === '_clm_practice_date' && !empty($value)) {
                    // Validate date format
                    $date = DateTime::createFromFormat('Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        continue; // Skip invalid dates
                    }
                }

                update_post_meta($post_id, $meta_key, $value);
            }
        }

        // Auto-generate title if empty
        if (empty(get_the_title($post_id)) || get_the_title($post_id) === 'Auto Draft') {
            $member_id = get_post_meta($post_id, '_clm_member_id', true);
            $lyric_id = get_post_meta($post_id, '_clm_lyric_id', true);
            $practice_date = get_post_meta($post_id, '_clm_practice_date', true);

            $title_parts = array();

            if ($member_id) {
                $member = get_post($member_id);
                if ($member) {
                    $title_parts[] = $member->post_title;
                }
            }

            if ($lyric_id) {
                $lyric = get_post($lyric_id);
                if ($lyric) {
                    $title_parts[] = $lyric->post_title;
                }
            }

            if ($practice_date) {
                $title_parts[] = date('M j, Y', strtotime($practice_date));
            }

            if (!empty($title_parts)) {
                $title = implode(' - ', $title_parts);
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => $title
                ));
            }
        }
    }

    /**
     * Set custom columns for lyric post type.
     *
     * @since    1.0.0
     * @param    array    $columns    Existing columns.
     * @return   array    Modified columns.
     */
    public function set_custom_lyric_columns($columns) {
        $new_columns = array(
            'cb'                    => $columns['cb'],
            'title'                 => __('Title', 'choir-lyrics-manager'),
            'clm_composer_col'      => __('Composer', 'choir-lyrics-manager'),
            'clm_year_col'          => __('Year', 'choir-lyrics-manager'),
            'clm_genres_col'        => __('Genres', 'choir-lyrics-manager'),
            'clm_collections_col'   => __('Collections', 'choir-lyrics-manager'),
            'clm_media_col'         => __('Media', 'choir-lyrics-manager'),
            'date'                  => __('Date', 'choir-lyrics-manager')
        );
        return $new_columns;
    }

    /**
     * Display custom column content for lyric post type.
     *
     * @since    1.0.0
     * @param    string    $column     Column name.
     * @param    int       $post_id    Post ID.
     */
    public function custom_lyric_column($column, $post_id) {
        switch ($column) {
            case 'clm_composer_col':
                $composer = get_post_meta($post_id, '_clm_composer', true);
                echo $composer ? esc_html($composer) : '—';
                break;

            case 'clm_year_col':
                $year = get_post_meta($post_id, '_clm_year', true);
                echo $year ? esc_html($year) : '—';
                break;

            case 'clm_genres_col':
                $terms = get_the_terms($post_id, 'clm_genre');
                if ($terms && !is_wp_error($terms)) {
                    $genre_links = array();
                    foreach ($terms as $term) {
                        $genre_links[] = sprintf('<a href="%s">%s</a>',
                            esc_url(add_query_arg(array('clm_genre' => $term->slug), 'edit.php?post_type=clm_lyric')),
                            esc_html($term->name)
                        );
                    }
                    echo implode(', ', $genre_links);
                } else {
                    echo '—';
                }
                break;

            case 'clm_collections_col':
                $terms = get_the_terms($post_id, 'clm_collection');
                if ($terms && !is_wp_error($terms)) {
                    $collection_links = array();
                    foreach ($terms as $term) {
                        $collection_links[] = sprintf('<a href="%s">%s</a>',
                            esc_url(add_query_arg(array('clm_collection' => $term->slug), 'edit.php?post_type=clm_lyric')),
                            esc_html($term->name)
                        );
                    }
                    echo implode(', ', $collection_links);
                } else {
                    echo '—';
                }
                break;

            case 'clm_media_col':
                $media_indicators = array();
                
                if (get_post_meta($post_id, '_clm_audio_file_id', true)) {
                    $media_indicators[] = '<span class="dashicons dashicons-format-audio" title="' . esc_attr__('Audio', 'choir-lyrics-manager') . '"></span>';
                }
                
                if (get_post_meta($post_id, '_clm_video_embed', true)) {
                    $media_indicators[] = '<span class="dashicons dashicons-format-video" title="' . esc_attr__('Video', 'choir-lyrics-manager') . '"></span>';
                }
                
                if (get_post_meta($post_id, '_clm_sheet_music_id', true)) {
                    $media_indicators[] = '<span class="dashicons dashicons-media-document" title="' . esc_attr__('Sheet Music', 'choir-lyrics-manager') . '"></span>';
                }
                
                if (get_post_meta($post_id, '_clm_midi_file_id', true)) {
                    $media_indicators[] = '<span class="dashicons dashicons-playlist-audio" title="' . esc_attr__('MIDI', 'choir-lyrics-manager') . '"></span>';
                }

                echo !empty($media_indicators) ? implode(' ', $media_indicators) : '—';
                break;
        }
    }

    /**
     * Set custom columns for practice log post type.
     *
     * @since    1.0.0
     * @param    array    $columns    Existing columns.
     * @return   array    Modified columns.
     */
    public function set_custom_practice_log_columns($columns) {
        $new_columns = array(
            'cb'                    => $columns['cb'],
            'title'                 => __('Practice Session', 'choir-lyrics-manager'),
            'clm_member_col'        => __('Member', 'choir-lyrics-manager'),
            'clm_lyric_col'         => __('Lyric', 'choir-lyrics-manager'),
            'clm_practice_date_col' => __('Practice Date', 'choir-lyrics-manager'),
            'clm_duration_col'      => __('Duration', 'choir-lyrics-manager'),
            'clm_confidence_col'    => __('Confidence', 'choir-lyrics-manager'),
            'date'                  => __('Logged', 'choir-lyrics-manager')
        );
        return $new_columns;
    }

    /**
     * Display custom column content for practice log post type.
     *
     * @since    1.0.0
     * @param    string    $column     Column name.
     * @param    int       $post_id    Post ID.
     */
    public function custom_practice_log_column($column, $post_id) {
        switch ($column) {
            case 'clm_member_col':
                $member_id = get_post_meta($post_id, '_clm_member_id', true);
                if ($member_id) {
                    $member = get_post($member_id);
                    if ($member) {
                        printf('<a href="%s">%s</a>',
                            esc_url(get_edit_post_link($member_id)),
                            esc_html($member->post_title)
                        );
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'clm_lyric_col':
                $lyric_id = get_post_meta($post_id, '_clm_lyric_id', true);
                if ($lyric_id) {
                    $lyric = get_post($lyric_id);
                    if ($lyric) {
                        printf('<a href="%s">%s</a>',
                            esc_url(get_edit_post_link($lyric_id)),
                            esc_html($lyric->post_title)
                        );
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'clm_practice_date_col':
                $practice_date = get_post_meta($post_id, '_clm_practice_date', true);
                if ($practice_date) {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($practice_date)));
                } else {
                    echo '—';
                }
                break;

            case 'clm_duration_col':
                $duration = get_post_meta($post_id, '_clm_duration_minutes', true);
                if ($duration) {
                    printf(_n('%d minute', '%d minutes', $duration, 'choir-lyrics-manager'), $duration);
                } else {
                    echo '—';
                }
                break;

            case 'clm_confidence_col':
                $confidence = get_post_meta($post_id, '_clm_confidence_rating', true);
                if ($confidence) {
                    $stars = str_repeat('★', $confidence) . str_repeat('☆', 5 - $confidence);
                    printf('<span title="%s">%s</span>',
                        esc_attr(sprintf(__('%d out of 5 stars', 'choir-lyrics-manager'), $confidence)),
                        $stars
                    );
                } else {
                    echo '—';
                }
                break;
        }
    }
}