<?php
/**
 * Playlists functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Playlists {

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
     * Create a new playlist
     *
     * @since    1.0.0
     */
    public function create_playlist() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to create playlists.', 'choir-lyrics-manager')));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clm_playlist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
        }
        
        // Check if name is provided
        if (empty($_POST['playlist_name'])) {
            wp_send_json_error(array('message' => __('Playlist name is required.', 'choir-lyrics-manager')));
        }
        
        $user_id = get_current_user_id();
        $playlist_name = sanitize_text_field($_POST['playlist_name']);
        $playlist_description = isset($_POST['playlist_description']) ? sanitize_textarea_field($_POST['playlist_description']) : '';
        
        // Create new playlist post
        $playlist_data = array(
            'post_title'    => $playlist_name,
            'post_content'  => $playlist_description,
            'post_status'   => 'publish',
            'post_author'   => $user_id,
            'post_type'     => 'clm_playlist',
        );
        
        $playlist_id = wp_insert_post($playlist_data);
        
        if (is_wp_error($playlist_id)) {
            wp_send_json_error(array('message' => $playlist_id->get_error_message()));
        }
        
        // Set playlist visibility
        $visibility = isset($_POST['playlist_visibility']) ? sanitize_text_field($_POST['playlist_visibility']) : 'private';
        update_post_meta($playlist_id, '_clm_playlist_visibility', $visibility);
        
        // Add initial lyric if provided
        if (!empty($_POST['lyric_id'])) {
            $lyric_id = intval($_POST['lyric_id']);
            $this->add_lyric_to_playlist($playlist_id, $lyric_id);
        }
        
        wp_send_json_success(array(
            'playlist_id' => $playlist_id,
            'playlist_name' => $playlist_name,
            'message' => __('Playlist created successfully.', 'choir-lyrics-manager')
        ));
    }

    /**
     * Add a lyric to a playlist
     *
     * @since    1.0.0
     */
    public function add_to_playlist() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to add to playlists.', 'choir-lyrics-manager')));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clm_playlist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
        }
        
        // Check if playlist and lyric IDs are provided
        if (empty($_POST['playlist_id']) || empty($_POST['lyric_id'])) {
            wp_send_json_error(array('message' => __('Both playlist and lyric are required.', 'choir-lyrics-manager')));
        }
        
        $playlist_id = intval($_POST['playlist_id']);
        $lyric_id = intval($_POST['lyric_id']);
        
        // Check if user has permission to modify this playlist
        if (!$this->can_modify_playlist($playlist_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to modify this playlist.', 'choir-lyrics-manager')));
        }
        
        // Add lyric to playlist
        $result = $this->add_lyric_to_playlist($playlist_id, $lyric_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Lyric added to playlist successfully.', 'choir-lyrics-manager')
        ));
    }

    /**
     * Remove a lyric from a playlist
     *
     * @since    1.0.0
     */
    public function remove_from_playlist() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to modify playlists.', 'choir-lyrics-manager')));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clm_playlist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
        }
        
        // Check if playlist and lyric IDs are provided
        if (empty($_POST['playlist_id']) || empty($_POST['lyric_id'])) {
            wp_send_json_error(array('message' => __('Both playlist and lyric are required.', 'choir-lyrics-manager')));
        }
        
        $playlist_id = intval($_POST['playlist_id']);
        $lyric_id = intval($_POST['lyric_id']);
        
        // Check if user has permission to modify this playlist
        if (!$this->can_modify_playlist($playlist_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to modify this playlist.', 'choir-lyrics-manager')));
        }
        
        // Remove lyric from playlist
        $result = $this->remove_lyric_from_playlist($playlist_id, $lyric_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Lyric removed from playlist successfully.', 'choir-lyrics-manager')
        ));
    }

    /**
     * Check if current user can modify a playlist
     *
     * @since     1.0.0
     * @param     int       $playlist_id    The playlist ID.
     * @return    boolean                   Whether the user can modify the playlist.
     */
    private function can_modify_playlist($playlist_id) {
        // Administrators can modify any playlist
        if (current_user_can('administrator')) {
            return true;
        }
        
        $playlist = get_post($playlist_id);
        
        // Check if playlist exists
        if (!$playlist || $playlist->post_type !== 'clm_playlist') {
            return false;
        }
        
        // Users can only modify their own playlists
        return $playlist->post_author == get_current_user_id();
    }

    /**
     * Add a lyric to a playlist
     *
     * @since     1.0.0
     * @param     int       $playlist_id    The playlist ID.
     * @param     int       $lyric_id       The lyric ID.
     * @return    boolean|WP_Error         Success or error.
     */
    private function add_lyric_to_playlist($playlist_id, $lyric_id) {
        // Get current lyrics in playlist
        $lyrics = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
        
        if (!is_array($lyrics)) {
            $lyrics = array();
        }
        
        // Check if lyric already in playlist
        if (in_array($lyric_id, $lyrics)) {
            return new WP_Error('already_in_playlist', __('This lyric is already in the playlist.', 'choir-lyrics-manager'));
        }
        
        // Add lyric to playlist
        $lyrics[] = $lyric_id;
        
        // Update playlist meta
        update_post_meta($playlist_id, '_clm_playlist_lyrics', $lyrics);
        
        // Add to user's recent lyrics if not already there
        $this->add_to_recent_lyrics($lyric_id);
        
        return true;
    }

    /**
     * Remove a lyric from a playlist
     *
     * @since     1.0.0
     * @param     int       $playlist_id    The playlist ID.
     * @param     int       $lyric_id       The lyric ID.
     * @return    boolean|WP_Error         Success or error.
     */
    private function remove_lyric_from_playlist($playlist_id, $lyric_id) {
        // Get current lyrics in playlist
        $lyrics = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
        
        if (!is_array($lyrics)) {
            return new WP_Error('empty_playlist', __('The playlist is empty.', 'choir-lyrics-manager'));
        }
        
        // Find lyric in playlist
        $key = array_search($lyric_id, $lyrics);
        
        if ($key === false) {
            return new WP_Error('not_in_playlist', __('This lyric is not in the playlist.', 'choir-lyrics-manager'));
        }
        
        // Remove lyric from playlist
        unset($lyrics[$key]);
        
        // Reindex array
        $lyrics = array_values($lyrics);
        
        // Update playlist meta
        update_post_meta($playlist_id, '_clm_playlist_lyrics', $lyrics);
        
        return true;
    }

    /**
     * Get user's playlists
     *
     * @since     1.0.0
     * @param     int       $user_id    The user ID, defaults to current user.
     * @return    array                 Array of playlist objects.
     */
    public function get_user_playlists($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array();
        }
        
        $args = array(
            'post_type'      => 'clm_playlist',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );
        
        $playlists = get_posts($args);
        
        return $playlists;
    }

    /**
     * Get public playlists
     *
     * @since     1.0.0
     * @return    array     Array of playlist objects.
     */
    public function get_public_playlists() {
        $args = array(
            'post_type'      => 'clm_playlist',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_clm_playlist_visibility',
                    'value'   => 'public',
                    'compare' => '=',
                ),
            ),
        );
        
        $playlists = get_posts($args);
        
        return $playlists;
    }

    /**
     * Get lyrics in a playlist
     *
     * @since     1.0.0
     * @param     int       $playlist_id    The playlist ID.
     * @return    array                     Array of lyric objects.
     */
    public function get_playlist_lyrics($playlist_id) {
        $lyrics_ids = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
        
        if (!is_array($lyrics_ids) || empty($lyrics_ids)) {
            return array();
        }
        
        $args = array(
            'post_type'      => 'clm_lyric',
            'posts_per_page' => -1,
            'post__in'       => $lyrics_ids,
            'orderby'        => 'post__in', // Preserve the order from the meta field
        );
        
        $lyrics = get_posts($args);
        
        return $lyrics;
    }
    
    /**
     * Add a lyric to user's recent lyrics
     *
     * @since     1.0.0
     * @param     int       $lyric_id    The lyric ID.
     */
    private function add_to_recent_lyrics($lyric_id) {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $recent_lyrics = get_user_meta($user_id, 'clm_recent_lyrics', true);
        
        if (!is_array($recent_lyrics)) {
            $recent_lyrics = array();
        }
        
        // Remove if already exists (to put it at the front)
        $key = array_search($lyric_id, $recent_lyrics);
        if ($key !== false) {
            unset($recent_lyrics[$key]);
        }
        
        // Add to the beginning
        array_unshift($recent_lyrics, $lyric_id);
        
        // Limit to 10 recent lyrics
        $recent_lyrics = array_slice($recent_lyrics, 0, 10);
        
        // Update user meta
        update_user_meta($user_id, 'clm_recent_lyrics', $recent_lyrics);
    }
    
    /**
     * Get user's recent lyrics
     *
     * @since     1.0.0
     * @param     int       $user_id    The user ID, defaults to current user.
     * @param     int       $limit      Number of lyrics to return, defaults to 5.
     * @return    array                 Array of lyric objects.
     */
    public function get_recent_lyrics($user_id = 0, $limit = 5) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array();
        }
        
        $recent_lyrics_ids = get_user_meta($user_id, 'clm_recent_lyrics', true);
        
        if (!is_array($recent_lyrics_ids) || empty($recent_lyrics_ids)) {
            return array();
        }
        
        // Limit the number of IDs
        $recent_lyrics_ids = array_slice($recent_lyrics_ids, 0, $limit);
        
        $args = array(
            'post_type'      => 'clm_lyric',
            'posts_per_page' => $limit,
            'post__in'       => $recent_lyrics_ids,
            'orderby'        => 'post__in', // Preserve the order from the meta field
        );
        
        $lyrics = get_posts($args);
        
        return $lyrics;
    }
    
    /**
     * Render playlist dropdown for frontend
     *
     * @since     1.0.0
     * @param     int       $lyric_id    The current lyric ID.
     * @return    string                 HTML for the playlist dropdown.
     */
    public function render_playlist_dropdown($lyric_id) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $user_id = get_current_user_id();
        $playlists = $this->get_user_playlists($user_id);
        
        if (empty($playlists)) {
            return sprintf(
                '<div class="clm-playlist-dropdown">
                    <button class="clm-create-playlist-button" data-lyric-id="%d">%s</button>
                    <div class="clm-create-playlist-form" style="display:none;">
                        <input type="text" class="clm-playlist-name" placeholder="%s">
                        <button class="clm-submit-playlist" data-lyric-id="%d">%s</button>
                        <button class="clm-cancel-playlist">%s</button>
                    </div>
                </div>',
                $lyric_id,
                __('Create Playlist', 'choir-lyrics-manager'),
                __('Playlist Name', 'choir-lyrics-manager'),
                $lyric_id,
                __('Create', 'choir-lyrics-manager'),
                __('Cancel', 'choir-lyrics-manager')
            );
        }
        
        $output = '<div class="clm-playlist-dropdown">';
        $output .= '<button class="clm-playlist-dropdown-toggle">' . __('Add to Playlist', 'choir-lyrics-manager') . '</button>';
        $output .= '<div class="clm-playlist-dropdown-menu" style="display:none;">';
        
        foreach ($playlists as $playlist) {
            $playlist_id = $playlist->ID;
            $playlist_lyrics = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
            
            if (!is_array($playlist_lyrics)) {
                $playlist_lyrics = array();
            }
            
            $in_playlist = in_array($lyric_id, $playlist_lyrics);
            $button_class = $in_playlist ? 'clm-in-playlist' : 'clm-add-to-playlist';
            $button_text = $in_playlist ? __('Added', 'choir-lyrics-manager') : __('Add', 'choir-lyrics-manager');
            
            $output .= sprintf(
                '<div class="clm-playlist-item">
                    <span class="clm-playlist-name">%s</span>
                    <button class="%s" data-playlist-id="%d" data-lyric-id="%d">%s</button>
                </div>',
                esc_html($playlist->post_title),
                $button_class,
                $playlist_id,
                $lyric_id,
                $button_text
            );
        }
        
        $output .= '<div class="clm-playlist-divider"></div>';
        $output .= sprintf(
            '<div class="clm-create-playlist">
                <button class="clm-create-playlist-button" data-lyric-id="%d">%s</button>
                <div class="clm-create-playlist-form" style="display:none;">
                    <input type="text" class="clm-playlist-name" placeholder="%s">
                    <button class="clm-submit-playlist" data-lyric-id="%d">%s</button>
                    <button class="clm-cancel-playlist">%s</button>
                </div>
            </div>',
            $lyric_id,
            __('Create New Playlist', 'choir-lyrics-manager'),
            __('Playlist Name', 'choir-lyrics-manager'),
            $lyric_id,
            __('Create', 'choir-lyrics-manager'),
            __('Cancel', 'choir-lyrics-manager')
        );
        
        $output .= '</div>'; // End dropdown menu
        $output .= '</div>'; // End dropdown
        
        return $output;
    }
    
    /**
     * Render playlist shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             HTML for the playlist.
     */
    public function render_playlist_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_media' => 'yes',
            'show_actions' => 'yes',
        ), $atts);
        
        $playlist_id = intval($atts['id']);
        
        if (!$playlist_id) {
            return '<p class="clm-error">' . __('Playlist ID is required.', 'choir-lyrics-manager') . '</p>';
        }
        
        $playlist = get_post($playlist_id);
        
        if (!$playlist || $playlist->post_type !== 'clm_playlist') {
            return '<p class="clm-error">' . __('Playlist not found.', 'choir-lyrics-manager') . '</p>';
        }
        
        // Check playlist visibility
        $visibility = get_post_meta($playlist_id, '_clm_playlist_visibility', true);
        
        if ($visibility === 'private' && $playlist->post_author != get_current_user_id() && !current_user_can('administrator')) {
            return '<p class="clm-error">' . __('This playlist is private.', 'choir-lyrics-manager') . '</p>';
        }
        
        $lyrics = $this->get_playlist_lyrics($playlist_id);
        
        if (empty($lyrics)) {
            return '<p class="clm-notice">' . __('This playlist is empty.', 'choir-lyrics-manager') . '</p>';
        }
        
        $show_media = $atts['show_media'] === 'yes';
        $show_actions = $atts['show_actions'] === 'yes';
        
        $output = '<div class="clm-playlist" data-playlist-id="' . $playlist_id . '">';
        $output .= '<h3 class="clm-playlist-title">' . esc_html($playlist->post_title) . '</h3>';
        
        if (!empty($playlist->post_content)) {
            $output .= '<div class="clm-playlist-description">' . apply_filters('the_content', $playlist->post_content) . '</div>';
        }
        
        $output .= '<ul class="clm-playlist-items">';
        
        foreach ($lyrics as $lyric) {
            $output .= '<li class="clm-playlist-item" data-lyric-id="' . $lyric->ID . '">';
            $output .= '<div class="clm-playlist-item-title">';
            $output .= '<a href="' . get_permalink($lyric->ID) . '">' . esc_html($lyric->post_title) . '</a>';
            
            // Show composer if available
            $composer = get_post_meta($lyric->ID, '_clm_composer', true);
            if ($composer) {
                $output .= '<span class="clm-playlist-item-composer"> ' . __('by', 'choir-lyrics-manager') . ' ' . esc_html($composer) . '</span>';
            }
            
            $output .= '</div>';
            
            if ($show_media) {
                // Show audio player if available
                $audio_id = get_post_meta($lyric->ID, '_clm_audio_file_id', true);
                if ($audio_id) {
                    $audio_url = wp_get_attachment_url($audio_id);
                    $output .= '<div class="clm-playlist-item-audio">';
                    $output .= wp_audio_shortcode(array('src' => $audio_url));
                    $output .= '</div>';
                }
            }
            
            if ($show_actions && $this->can_modify_playlist($playlist_id)) {
                $output .= '<div class="clm-playlist-item-actions">';
                $output .= '<button class="clm-remove-from-playlist" data-playlist-id="' . $playlist_id . '" data-lyric-id="' . $lyric->ID . '">';
                $output .= __('Remove', 'choir-lyrics-manager');
                $output .= '</button>';
                $output .= '</div>';
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
}