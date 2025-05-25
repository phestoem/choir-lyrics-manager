<?php
/**
 * Playlists functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Playlists
{

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
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Create a new playlist
     *
     * @since    1.0.0
     */
    public function create_playlist()
    {
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

        $existing_playlist_args = array(
            'post_type' => 'clm_playlist', // Your playlist CPT slug
            'post_status' => 'publish',    // Or any status you want to check against
            'author' => $user_id,
            'title' => $playlist_name,     // Check for exact title match
            'posts_per_page' => 1,         // We only need to know if one exists
            'fields' => 'ids',             // Only need the ID
        );
        $existing_playlists = get_posts($existing_playlist_args);

        if (!empty($existing_playlists)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('You already have a playlist named "%s". Please choose a different name.', 'choir-lyrics-manager'),
                    esc_html($playlist_name)
                )
            ));
            return;
        }

        // Create new playlist post
        $playlist_data = array(
            'post_title' => $playlist_name,
            'post_content' => $playlist_description,
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_type' => 'clm_playlist',
        );

        $playlist_id = wp_insert_post($playlist_data, true);

        if (is_wp_error($playlist_id)) {
            error_log('CLM Create Playlist Error: ' . $playlist_id->get_error_message());
            wp_send_json_error(array('message' => __('Failed to create playlist: ', 'choir-lyrics-manager') . $playlist_id->get_error_message()));
            return;
        }

        // Set playlist visibility
        // $visibility = isset($_POST['playlist_visibility']) ? sanitize_text_field($_POST['playlist_visibility']) : 'private';
        // update_post_meta($playlist_id, '_clm_playlist_visibility', $visibility);
        // Set playlist visibility (if you have this feature)
        $visibility = isset($_POST['playlist_visibility']) && in_array($_POST['playlist_visibility'], array('public', 'private'))
            ? sanitize_text_field($_POST['playlist_visibility'])
            : 'private'; // Default to private
        update_post_meta($playlist_id, '_clm_playlist_visibility', $visibility);

        // Add initial lyric if provided
        $lyric_id_to_add = isset($_POST['lyric_id']) ? intval($_POST['lyric_id']) : 0;
        // ...
        if ($lyric_id_to_add > 0 && get_post_type($lyric_id_to_add) === 'clm_lyric') {
            $this->add_lyric_to_playlist_internal($playlist_id, $lyric_id_to_add); // Use $lyric_id_to_add
        }

        wp_send_json_success(array(
            'message' => __('Playlist created successfully.', 'choir-lyrics-manager'),
            'playlist_id' => $playlist_id,
            'playlist_name' => $playlist_name,
            // Optionally send back the updated list of playlists for the JS to refresh the dropdown
            // 'user_playlists' => $this->get_user_playlists_for_js($user_id) // A helper to format playlists for JS
        ));
    }


    /**
     * Internal helper to add a lyric to a playlist.
     * Does not perform nonce or permission checks, assumes they are done by caller.
     *
     * @param int $playlist_id
     * @param int $lyric_id
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function add_lyric_to_playlist_internal($playlist_id, $lyric_id)
    {
        $lyrics = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
        if (!is_array($lyrics)) {
            $lyrics = array();
        }

        if (in_array($lyric_id, $lyrics)) {
            // This internal function might not need to return an error for this,
            // as the public-facing add_to_playlist AJAX handler would check this.
            // For create_playlist, it's unlikely to be a duplicate immediately.
            return true; // Lyric already there, consider it a success for this internal call.
        }

        $lyrics[] = $lyric_id;
        $updated = update_post_meta($playlist_id, '_clm_playlist_lyrics', $lyrics);

        if (!$updated) {
            // update_post_meta can return false if value is unchanged or on error
            // If it was a new meta, it returns meta_id. If updating existing, true/false.
            // Check if the lyric is now actually in the meta to be sure.
            $check_lyrics = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
            if (!in_array($lyric_id, $check_lyrics)) {
                return new WP_Error('meta_update_failed', __('Could not add lyric to playlist meta.', 'choir-lyrics-manager'));
            }
        }
        return true;
    }


    /**
     * Add a lyric to a playlist
     *
     * @since    1.0.0
     */
    public function add_to_playlist()
    {
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

        error_log("--- CLM_ADD_TO_PLAYLIST_START --- Playlist: {$playlist_id}, Lyric: {$lyric_id}");

        if (!$this->can_modify_playlist($playlist_id)) {
            error_log("CLM_ADD_TO_PLAYLIST_ERROR: Permission denied for playlist {$playlist_id}.");
            wp_send_json_error(array('message' => __('You do not have permission to modify this playlist.', 'choir-lyrics-manager')));
            return;
        }

        $current_lyrics_meta = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
        if (!is_array($current_lyrics_meta)) {
            $current_lyrics_meta = array();
        }
        error_log("CLM_ADD_TO_PLAYLIST_DEBUG: Raw meta _clm_playlist_lyrics for playlist {$playlist_id}: " . print_r($current_lyrics_meta, true));

        $current_lyrics_integers = array_map('intval', $current_lyrics_meta);
        error_log("CLM_ADD_TO_PLAYLIST_DEBUG: Integer lyrics in meta for playlist {$playlist_id}: " . print_r($current_lyrics_integers, true));

        // The crucial check
        $is_already_in_list = in_array($lyric_id, $current_lyrics_integers, true); // Using STRICT comparison now (integer vs integer)

        // THIS IS THE MOST IMPORTANT LOG TO SEE NOW
        error_log("CLM_ADD_TO_PLAYLIST_DEBUG: Value of \$lyric_id: {$lyric_id} (type: " . gettype($lyric_id) . ")");
        error_log("CLM_ADD_TO_PLAYLIST_DEBUG: Value of \$current_lyrics_integers: " . print_r($current_lyrics_integers, true));
        error_log("CLM_ADD_TO_PLAYLIST_DEBUG: Result of in_array(\$lyric_id, \$current_lyrics_integers, true): " . ($is_already_in_list ? 'TRUE' : 'FALSE'));


        if ($is_already_in_list) {
            error_log("CLM_ADD_TO_PLAYLIST_INFO: Lyric {$lyric_id} is already in playlist {$playlist_id}. Sending error.");
            wp_send_json_error(array('message' => __('This lyric is already in the playlist.', 'choir-lyrics-manager')));
            return;
        }

        // If not already in the list, proceed to add
        error_log("CLM_ADD_TO_PLAYLIST_INFO: Lyric {$lyric_id} NOT in playlist {$playlist_id}. Proceeding to add.");
        $result = $this->add_lyric_to_playlist_internal($playlist_id, $lyric_id);

        if (is_wp_error($result)) {
            error_log("CLM_ADD_TO_PLAYLIST_ERROR: add_lyric_to_playlist_internal failed. Error: " . $result->get_error_message() . " Data: " . print_r($result->get_error_data(), true));
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            error_log("CLM_ADD_TO_PLAYLIST_SUCCESS: Lyric {$lyric_id} should now be added to playlist {$playlist_id}.");
            wp_send_json_success(array('message' => __('Lyric added to playlist successfully.', 'choir-lyrics-manager')));
        }


    }

    /**
     * Remove a lyric from a playlist
     *
     * @since    1.0.0
     */
    public function remove_from_playlist()
    {
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
    private function can_modify_playlist($playlist_id)
    {
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
    private function add_lyric_to_playlist($playlist_id, $lyric_id)
    {
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
    private function remove_lyric_from_playlist($playlist_id, $lyric_id)
    {
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
     * Render the [clm_my_playlists] shortcode.
     * Displays a list of playlists for the currently logged-in user.
     */
    public function render_my_playlists_shortcode($atts)
    {
        if (!is_user_logged_in()) {
            return '<p class="clm-notice">' . __('Please log in to view your playlists.', 'choir-lyrics-manager') . '</p>';
        }

        $atts = shortcode_atts(array(
            'orderby' => 'title',
            'order' => 'ASC',
            'show_count' => 'yes',
        ), $atts);

        $user_id = get_current_user_id();
        // Fetch playlists for the current user
        $user_playlists = $this->get_user_playlists($user_id, array(
            'orderby' => sanitize_key($atts['orderby']),
            'order' => strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC',
        ));

        // --- Robustly Get the URL of your "View Playlist" page ---
        $view_playlist_page_id = 0; // Default to 0
        $clm_settings_option = get_option('clm_settings'); // Get the entire settings array

        if (is_array($clm_settings_option) && isset($clm_settings_option['clm_playlist_view_page_id'])) {
            $view_playlist_page_id = intval($clm_settings_option['clm_playlist_view_page_id']);
        }

        $base_view_playlist_url = ($view_playlist_page_id > 0) ? get_permalink($view_playlist_page_id) : '';

        // Debugging logs
        error_log("CLM_MyPlaylistsShortcode: Value of clm_settings option: " . print_r($clm_settings_option, true));
        error_log("CLM_MyPlaylistsShortcode: Extracted Playlist Page ID: " . $view_playlist_page_id);
        error_log("CLM_MyPlaylistsShortcode: Generated Base View Playlist URL: " . $base_view_playlist_url);
        // --- End Get URL ---

        ob_start();
        ?>
        <div class="clm-my-playlists-container">
            <h3 class="clm-section-title"><?php _e('My Playlists', 'choir-lyrics-manager'); ?></h3>

            <?php if (empty($user_playlists)): ?>
                <p class="clm-notice"><?php _e('You have not created any playlists yet.', 'choir-lyrics-manager'); ?></p>
            <?php else: ?>
                <ul class="clm-my-playlists-list">
                    <?php foreach ($user_playlists as $playlist_post):
                        $playlist_id = $playlist_post->ID;
                        $playlist_title = get_the_title($playlist_id);
                        $admin_edit_url = get_edit_post_link($playlist_id);
                        $frontend_view_url = '';

                        if ($base_view_playlist_url) {
                            $frontend_view_url = add_query_arg('playlist_to_view', $playlist_id, $base_view_playlist_url);
                        }

                        $lyrics_in_playlist = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
                        $count = is_array($lyrics_in_playlist) ? count($lyrics_in_playlist) : 0;
                        ?>
                        <li class="clm-my-playlist-item" data-playlist-id="<?php echo esc_attr($playlist_id); ?>">
                            <div class="clm-playlist-info">
                                <?php if ($frontend_view_url): ?>
                                    <a href="<?php echo esc_url($frontend_view_url); ?>"
                                        class="clm-playlist-title-link"><?php echo esc_html($playlist_title); ?></a>
                                <?php else: ?>
                                    <span class="clm-playlist-title-text"><?php echo esc_html($playlist_title); ?></span>
                                    <?php if (current_user_can('manage_options') && empty($base_view_playlist_url)): ?>
                                        <small
                                            style="display:block; color: #c00; font-style:italic;"><?php _e('(Admin: "Playlist Display Page" not set in CLM Settings. Frontend view links are disabled.)', 'choir-lyrics-manager'); ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($atts['show_count'] === 'yes'): ?>
                                    <span
                                        class="clm-playlist-track-count">(<?php printf(_n('%d track', '%d tracks', $count, 'choir-lyrics-manager'), $count); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="clm-playlist-actions">
                                <?php if ($frontend_view_url): ?>
                                    <a href="<?php echo esc_url($frontend_view_url); ?>"
                                        class="clm-button clm-button-small clm-view-playlist-tracks-button"><?php _e('View Tracks', 'choir-lyrics-manager'); ?></a>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($admin_edit_url); ?>" target="_blank"
                                    class="clm-button clm-button-small clm-button-secondary"><?php _e('Edit Name/Desc.', 'choir-lyrics-manager'); /* Clarified button text */ ?></a>
                                <button type="button"
                                    class="clm-delete-entire-playlist-button clm-button clm-button-small clm-button-danger"
                                    data-playlist-id="<?php echo esc_attr($playlist_id); ?>">
                                    <span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'choir-lyrics-manager'); ?>
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="clm-my-playlists-actions">
                <button type="button" class="clm-create-new-playlist-toggle-button clm-button clm-button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create New Playlist', 'choir-lyrics-manager'); ?>
                </button>
                <?php echo $this->get_new_playlist_form_html('my_playlists_page_new_form'); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }


    // Helper for the View Playlist page shortcode
    public function render_single_playlist_from_url_shortcode($atts)
    {
        $playlist_id = isset($_GET['playlist_to_view']) ? intval($_GET['playlist_to_view']) : 0;

        if (!$playlist_id && isset($atts['id'])) { // Fallback to id attribute if query param not present
            $playlist_id = intval($atts['id']);
        }

        if (!$playlist_id) {
            return '<p class="clm-error">' . __('No playlist ID specified.', 'choir-lyrics-manager') . '</p>';
        }
        // Now call the existing single playlist renderer
        return $this->render_playlist_shortcode(array_merge($atts, array('id' => $playlist_id)));
    }

    /**
     * Helper to get HTML for a new playlist form (can be used in multiple places)
     */
    private function get_new_playlist_form_html($context_id_prefix = 'new_playlist')
    {
        ob_start();
        ?>
        <div id="<?php echo esc_attr($context_id_prefix); ?>-create-form" class="clm-create-playlist-form-area"
            style="display:none; margin-top:15px; padding:15px; border:1px solid #eee;">
            <h4><?php _e('Create a New Playlist', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-form-field">
                <label
                    for="<?php echo esc_attr($context_id_prefix); ?>-name"><?php _e('Playlist Name:', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="<?php echo esc_attr($context_id_prefix); ?>-name" class="clm-new-playlist-name-input"
                    placeholder="<?php esc_attr_e('Enter playlist name', 'choir-lyrics-manager'); ?>">
            </div>
            <div class="clm-form-field">
                <label
                    for="<?php echo esc_attr($context_id_prefix); ?>-desc"><?php _e('Description (optional):', 'choir-lyrics-manager'); ?></label>
                <textarea id="<?php echo esc_attr($context_id_prefix); ?>-desc" class="clm-new-playlist-desc-input"
                    rows="3"></textarea>
            </div>
            <button type="button" class="clm-button clm-submit-new-playlist-from-shortcode"
                data-context-id-prefix="<?php echo esc_attr($context_id_prefix); ?>"><?php _e('Create Playlist', 'choir-lyrics-manager'); ?></button>
            <div class="clm-playlist-creation-message" style="margin-top:10px;"></div>
        </div>
        <?php
        return ob_get_clean();
    }



    /**
     * Modified get_user_playlists to accept WP_Query args
     */
    public function get_user_playlists($user_id = 0, $extra_args = array())
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id)
            return array();

        $default_args = array(
            'post_type' => 'clm_playlist', // Your playlist CPT slug
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish', // Or 'any' if you want drafts too
        );
        $args = wp_parse_args($extra_args, $default_args);

        return get_posts($args);
    }

    /**
     * Get public playlists
     *
     * @since     1.0.0
     * @return    array     Array of playlist objects.
     */
    public function get_public_playlists()
    {
        $args = array(
            'post_type' => 'clm_playlist',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_clm_playlist_visibility',
                    'value' => 'public',
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
    public function get_playlist_lyrics($playlist_id)
    {
        $lyrics_ids = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);

        if (!is_array($lyrics_ids) || empty($lyrics_ids)) {
            return array();
        }

        $args = array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => -1,
            'post__in' => $lyrics_ids,
            'orderby' => 'post__in', // Preserve the order from the meta field
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
    private function add_to_recent_lyrics($lyric_id)
    {
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
    public function get_recent_lyrics($user_id = 0, $limit = 5)
    {
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
            'post_type' => 'clm_lyric',
            'posts_per_page' => $limit,
            'post__in' => $recent_lyrics_ids,
            'orderby' => 'post__in', // Preserve the order from the meta field
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
    public function render_playlist_dropdown($lyric_id)
    {
        if (!is_user_logged_in()) {
            return ''; // Don't show for non-logged-in users
        }

        $user_playlists = $this->get_user_playlists(get_current_user_id()); // Assuming this method fetches user's playlists

        ob_start();
        ?>
        <div class="clm-playlist-wrapper">
            <button type="button" class="clm-button clm-playlist-button" aria-haspopup="true" aria-expanded="false">
                <span class="dashicons dashicons-playlist-audio"></span>
                <?php _e('Add to Playlist', 'choir-lyrics-manager'); ?>
            </button>

            <div class="clm-playlist-dropdown" style="display: none;">
                <?php if (!empty($user_playlists)): ?>
                    <div class="clm-existing-playlists">
                        <h4><?php _e('Your Playlists', 'choir-lyrics-manager'); ?></h4>
                        <ul>
                            <?php foreach ($user_playlists as $playlist): ?>
                                <li>

                                    <a href="#" class="clm-add-to-playlist" data-playlist-id="<?php echo esc_attr($playlist->ID); ?>"
                                        data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
                                        <?php echo esc_html($playlist->post_title); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="clm-create-new-playlist">
                    <h4><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></h4>
                    <div class="clm-form-field">
                        <input type="text" class="clm-new-playlist-name"
                            placeholder="<?php esc_attr_e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                    </div>

                    <button type="button" class="clm-button clm-button-small clm-create-and-add"
                        data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
                        <?php _e('Create & Add', 'choir-lyrics-manager'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render playlist shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             HTML for the playlist.
     */
    public function render_playlist_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0,
            // 'show_media' => 'yes', // Already in your version
            // 'show_actions' => 'yes', // Already in your version
        ), $atts);

        $playlist_id = intval($atts['id']);
        if (!$playlist_id) /* error */
            ;

        $playlist_data = $this->get_playlist_with_lyrics($playlist_id); // New helper

        if (!$playlist_data) {
            return '<p class="clm-error">' . __('Playlist not found or you do not have permission to view it.', 'choir-lyrics-manager') . '</p>';
        }

        // Pass data to a template file
        ob_start();
        // Make variables available to the template
        extract(array(
            'playlist_post' => $playlist_data['playlist'],
            'lyrics_in_playlist' => $playlist_data['lyrics'],
            'can_edit_playlist' => $playlist_data['can_edit'], // From can_modify_playlist()
            'playlist_id' => $playlist_id, // Pass id for convenience
            'shortcode_atts' => $atts      // Pass original shortcode attributes
        ));

        $template_path = CLM_PLUGIN_DIR . 'templates/shortcode/playlist-display.php'; // Dedicated template
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<!-- Playlist display template not found -->';
        }
        return ob_get_clean();
    }

    /**
     * Helper to get a single playlist with its lyrics and edit permissions.
     */
    public function get_playlist_with_lyrics($playlist_id)
    {
        $playlist_post = get_post($playlist_id);

        if (!$playlist_post || $playlist_post->post_type !== 'clm_playlist') { // 'clm_playlist' is your CPT slug
            return null;
        }

        // Visibility check (example)
        $visibility = get_post_meta($playlist_id, '_clm_playlist_visibility', true);
        if ($visibility === 'private' && $playlist_post->post_author != get_current_user_id() && !current_user_can('manage_options')) {
            return null; // Cannot view private playlist if not owner or admin
        }

        $lyric_ids = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);
        $lyrics_data = array();
        if (is_array($lyric_ids) && !empty($lyric_ids)) {
            $args = array(
                'post_type' => 'clm_lyric',
                'post__in' => $lyric_ids,
                'orderby' => 'post__in',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            );
            $lyric_posts = get_posts($args);
            foreach ($lyric_posts as $lp) {
                $lyrics_data[] = $lp; // Store full WP_Post objects
            }
        }

        return array(
            'playlist' => $playlist_post,
            'lyrics' => $lyrics_data,
            'can_edit' => $this->can_modify_playlist($playlist_id) // Your existing permission check
        );
    }
    /**
     * Update a playlist's details
     *
     * @since    1.0.0
     */
    public function update_playlist()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to update playlists.', 'choir-lyrics-manager')));
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clm_playlist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
        }

        // Check if playlist ID is provided
        if (empty($_POST['playlist_id'])) {
            wp_send_json_error(array('message' => __('Playlist ID is required.', 'choir-lyrics-manager')));
        }

        $playlist_id = intval($_POST['playlist_id']);

        // Check if user has permission to modify this playlist
        if (!$this->can_modify_playlist($playlist_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to modify this playlist.', 'choir-lyrics-manager')));
        }

        // Update playlist data
        $update_data = array(
            'ID' => $playlist_id,
        );

        if (!empty($_POST['playlist_name'])) {
            $update_data['post_title'] = sanitize_text_field($_POST['playlist_name']);
        }

        if (isset($_POST['playlist_description'])) {
            $update_data['post_content'] = sanitize_textarea_field($_POST['playlist_description']);
        }

        $playlist_id = wp_update_post($update_data);

        if (is_wp_error($playlist_id)) {
            wp_send_json_error(array('message' => $playlist_id->get_error_message()));
        }

        // Update visibility if provided
        if (isset($_POST['playlist_visibility'])) {
            $visibility = sanitize_text_field($_POST['playlist_visibility']);
            update_post_meta($playlist_id, '_clm_playlist_visibility', $visibility);
        }

        wp_send_json_success(array(
            'message' => __('Playlist updated successfully.', 'choir-lyrics-manager')
        ));
    }

    /**
     * Reorder lyrics in a playlist
     *
     * @since    1.0.0
     */
    public function reorder_playlist()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to modify playlists.', 'choir-lyrics-manager')));
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clm_playlist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
        }

        // Check if playlist ID and order are provided
        if (empty($_POST['playlist_id']) || empty($_POST['lyric_order']) || !is_array($_POST['lyric_order'])) {
            wp_send_json_error(array('message' => __('Playlist ID and lyric order are required.', 'choir-lyrics-manager')));
        }

        $playlist_id = intval($_POST['playlist_id']);
        $lyric_order = array_map('intval', $_POST['lyric_order']);

        // Check if user has permission to modify this playlist
        if (!$this->can_modify_playlist($playlist_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to modify this playlist.', 'choir-lyrics-manager')));
        }

        // Get current lyrics to verify all IDs exist
        $current_lyrics = get_post_meta($playlist_id, '_clm_playlist_lyrics', true);

        if (!is_array($current_lyrics)) {
            $current_lyrics = array();
        }

        // Verify all lyrics in the new order exist in the current playlist
        if (count(array_diff($lyric_order, $current_lyrics)) > 0 || count($lyric_order) !== count($current_lyrics)) {
            wp_send_json_error(array('message' => __('Invalid lyric order.', 'choir-lyrics-manager')));
        }

        // Update the playlist with the new order
        update_post_meta($playlist_id, '_clm_playlist_lyrics', $lyric_order);

        wp_send_json_success(array(
            'message' => __('Playlist order updated successfully.', 'choir-lyrics-manager')
        ));
    }

    /**
     * Get a single playlist with its lyrics
     *
     * @since     1.0.0
     * @param     int       $playlist_id    The playlist ID.
     * @return    array                     Array with playlist and lyrics data.
     */
    public function get_playlist($playlist_id)
    {
        $playlist = get_post($playlist_id);

        if (!$playlist || $playlist->post_type !== 'clm_playlist') {
            return null;
        }

        // Check visibility
        $visibility = get_post_meta($playlist_id, '_clm_playlist_visibility', true);
        $current_user_id = get_current_user_id();

        if ($visibility === 'private' && $playlist->post_author != $current_user_id && !current_user_can('administrator')) {
            return null;
        }

        $lyrics = $this->get_playlist_lyrics($playlist_id);

        return array(
            'playlist' => $playlist,
            'lyrics' => $lyrics,
            'visibility' => $visibility,
            'can_edit' => $this->can_modify_playlist($playlist_id)
        );
    }

    /**
     * Delete a playlist
     *
     * @since    1.0.0
     */
    public function ajax_delete_user_playlist()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Login required.', 'choir-lyrics-manager')));
            return;
        }
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'clm_playlist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
            return;
        }
        if (empty($_POST['playlist_id'])) {
            wp_send_json_error(array('message' => __('Playlist ID missing.', 'choir-lyrics-manager')));
            return;
        }

        $playlist_id = intval($_POST['playlist_id']);

        if (!$this->can_modify_playlist($playlist_id)) { // can_modify_playlist implies ownership or admin
            wp_send_json_error(array('message' => __('Permission denied to delete this playlist.', 'choir-lyrics-manager')));
            return;
        }

        $deleted_post = wp_delete_post($playlist_id, true); // true = force delete, bypass trash

        if ($deleted_post === false || is_wp_error($deleted_post)) {
            wp_send_json_error(array('message' => __('Failed to delete playlist.', 'choir-lyrics-manager')));
        } else {
            wp_send_json_success(array('message' => __('Playlist deleted successfully.', 'choir-lyrics-manager'), 'playlist_id' => $playlist_id));
        }
    }

    /**
     * Share a playlist
     *
     * @since    1.0.0
     */
    public function share_playlist()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to share playlists.', 'choir-lyrics-manager')));
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clm_playlist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
        }

        // Check if playlist ID is provided
        if (empty($_POST['playlist_id'])) {
            wp_send_json_error(array('message' => __('Playlist ID is required.', 'choir-lyrics-manager')));
        }

        $playlist_id = intval($_POST['playlist_id']);

        // Check if user has permission to modify this playlist
        if (!$this->can_modify_playlist($playlist_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to share this playlist.', 'choir-lyrics-manager')));
        }

        // Set playlist to public
        update_post_meta($playlist_id, '_clm_playlist_visibility', 'public');

        // Generate a unique share URL
        $share_url = get_permalink($playlist_id);

        wp_send_json_success(array(
            'message' => __('Playlist shared successfully.', 'choir-lyrics-manager'),
            'share_url' => $share_url
        ));
    }



}
