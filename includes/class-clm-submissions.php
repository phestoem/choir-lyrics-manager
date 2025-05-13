<?php
/**
 * Submissions functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Submissions {

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
     * Process lyric submission from frontend form
     *
     * @since    1.0.0
     */
    public function process_lyric_submission() {
        // Check nonce for security
        if (!isset($_POST['clm_submission_nonce']) || !wp_verify_nonce($_POST['clm_submission_nonce'], 'clm_submit_lyric')) {
            wp_die(__('Security check failed. Please try again.', 'choir-lyrics-manager'), __('Error', 'choir-lyrics-manager'));
        }
        
        // Check if submission is allowed
        if (!$this->can_submit_lyric()) {
            wp_die(__('You do not have permission to submit lyrics.', 'choir-lyrics-manager'), __('Error', 'choir-lyrics-manager'));
        }
        
        // Validate required fields
        if (empty($_POST['clm_title']) || empty($_POST['clm_content'])) {
            wp_die(__('Title and lyrics content are required.', 'choir-lyrics-manager'), __('Error', 'choir-lyrics-manager'));
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'    => sanitize_text_field($_POST['clm_title']),
            'post_content'  => wp_kses_post($_POST['clm_content']),
            'post_status'   => $this->get_submission_status(),
            'post_type'     => 'clm_lyric',
            'post_author'   => get_current_user_id(),
        );
        
        // Insert the post into the database
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_die($post_id->get_error_message(), __('Error', 'choir-lyrics-manager'));
        }
        
        // Process meta fields
        $meta_fields = array(
            '_clm_composer' => 'clm_composer',
            '_clm_arranger' => 'clm_arranger',
            '_clm_year' => 'clm_year',
            '_clm_language' => 'clm_language',
            '_clm_difficulty' => 'clm_difficulty',
            '_clm_performance_notes' => 'clm_performance_notes',
        );
        
        foreach ($meta_fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
            }
        }
        
        // Process taxonomies
        $taxonomies = array(
            'clm_genre' => 'clm_genres',
            'clm_composer' => 'clm_composers',
            'clm_language' => 'clm_languages',
            'clm_difficulty' => 'clm_difficulty_level',
        );
        
        foreach ($taxonomies as $taxonomy => $post_key) {
            if (isset($_POST[$post_key]) && !empty($_POST[$post_key])) {
                $terms = is_array($_POST[$post_key]) ? $_POST[$post_key] : array($_POST[$post_key]);
                wp_set_object_terms($post_id, array_map('intval', $terms), $taxonomy);
            }
        }
        
        // Process file uploads
        $this->handle_file_uploads($post_id);
        
        // Send notifications
        $this->send_submission_notifications($post_id);
        
        // Redirect after submission
        $redirect_url = isset($_POST['clm_redirect']) ? esc_url_raw($_POST['clm_redirect']) : home_url();
        wp_redirect(add_query_arg('clm_submission', 'success', $redirect_url));
        exit;
    }
    
    /**
     * Check if current user can submit a lyric
     *
     * @since     1.0.0
     * @return    boolean    Whether the user can submit.
     */
    private function can_submit_lyric() {
        $submission_roles = $this->get_submission_roles();
        
        // Check if user is logged in and has appropriate role
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        foreach ($submission_roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get allowed submission roles
     *
     * @since     1.0.0
     * @return    array    Array of role slugs.
     */
    private function get_submission_roles() {
        $default_roles = array('administrator', 'editor', 'author', 'clm_manager', 'clm_contributor');
        $option_roles = get_option('clm_submission_roles', array());
        
        return !empty($option_roles) ? $option_roles : $default_roles;
    }
    
    /**
     * Get post status for new submissions
     *
     * @since     1.0.0
     * @return    string    The post status to use.
     */
    private function get_submission_status() {
        $user = wp_get_current_user();
        $auto_approve_roles = array('administrator', 'editor', 'clm_manager');
        
        // Check if user's role allows auto-approval
        foreach ($auto_approve_roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                return 'publish';
            }
        }
        
        // Otherwise submission requires moderation
        return 'pending';
    }
    
    /**
     * Handle file uploads for submission
     *
     * @since    1.0.0
     * @param    int      $post_id    The post ID.
     */
    private function handle_file_uploads($post_id) {
        // Check if files were uploaded
        if (empty($_FILES)) {
            return;
        }
        
        // Setup upload directory
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Handle sheet music upload
        if (!empty($_FILES['clm_sheet_music']['name'])) {
            $uploaded_file = $this->upload_file('clm_sheet_music', $post_id);
            
            if (!is_wp_error($uploaded_file) && !empty($uploaded_file['attachment_id'])) {
                update_post_meta($post_id, '_clm_sheet_music_id', $uploaded_file['attachment_id']);
            }
        }
        
        // Handle audio file upload
        if (!empty($_FILES['clm_audio_file']['name'])) {
            $uploaded_file = $this->upload_file('clm_audio_file', $post_id);
            
            if (!is_wp_error($uploaded_file) && !empty($uploaded_file['attachment_id'])) {
                update_post_meta($post_id, '_clm_audio_file_id', $uploaded_file['attachment_id']);
            }
        }
        
        // Handle MIDI file upload
        if (!empty($_FILES['clm_midi_file']['name'])) {
            $uploaded_file = $this->upload_file('clm_midi_file', $post_id);
            
            if (!is_wp_error($uploaded_file) && !empty($uploaded_file['attachment_id'])) {
                update_post_meta($post_id, '_clm_midi_file_id', $uploaded_file['attachment_id']);
            }
        }
        
        // Handle practice track uploads
        if (!empty($_FILES['clm_practice_tracks']['name']) && is_array($_FILES['clm_practice_tracks']['name'])) {
            $practice_tracks = array();
            
            for ($i = 0; $i < count($_FILES['clm_practice_tracks']['name']); $i++) {
                if (empty($_FILES['clm_practice_tracks']['name'][$i])) {
                    continue;
                }
                
                // Create a temporary file array for this specific file
                $file = array(
                    'name' => $_FILES['clm_practice_tracks']['name'][$i],
                    'type' => $_FILES['clm_practice_tracks']['type'][$i],
                    'tmp_name' => $_FILES['clm_practice_tracks']['tmp_name'][$i],
                    'error' => $_FILES['clm_practice_tracks']['error'][$i],
                    'size' => $_FILES['clm_practice_tracks']['size'][$i]
                );
                
                $uploaded_file = $this->upload_file_from_array($file, $post_id);
                
                if (!is_wp_error($uploaded_file) && !empty($uploaded_file['attachment_id'])) {
                    $track_title = isset($_POST['clm_practice_track_titles'][$i]) ? 
                                   sanitize_text_field($_POST['clm_practice_track_titles'][$i]) : 
                                   __('Practice Track', 'choir-lyrics-manager') . ' ' . ($i + 1);
                    
                    $practice_tracks[] = array(
                        'id' => $uploaded_file['attachment_id'],
                        'title' => $track_title
                    );
                }
            }
            
            if (!empty($practice_tracks)) {
                update_post_meta($post_id, '_clm_practice_tracks', $practice_tracks);
            }
        }
    }
    
    /**
     * Upload a file from form submission
     *
     * @since     1.0.0
     * @param     string    $file_key    The file key in $_FILES.
     * @param     int       $post_id     The post ID to attach to.
     * @return    array|WP_Error         Upload results or error.
     */
    private function upload_file($file_key, $post_id) {
        if (empty($_FILES[$file_key]['name'])) {
            return new WP_Error('empty_file', __('No file uploaded', 'choir-lyrics-manager'));
        }
        
        // Check file type
        $file_type = wp_check_filetype($_FILES[$file_key]['name']);
        
        // Set up allowed file types
        $allowed_types = $this->get_allowed_file_types($file_key);
        
        if (!in_array($file_type['ext'], $allowed_types)) {
            return new WP_Error(
                'invalid_file_type', 
                sprintf(
                    __('Invalid file type. Allowed types: %s', 'choir-lyrics-manager'),
                    implode(', ', $allowed_types)
                )
            );
        }
        
        // Prepare upload args
        $upload_args = array(
            'test_form' => false,
        );
        
        // Upload the file
        $uploaded_file = wp_handle_upload($_FILES[$file_key], $upload_args);
        
        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }
        
        // Insert as attachment
        $attachment = array(
            'guid' => $uploaded_file['url'],
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($uploaded_file['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file'], $post_id);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Generate attachment metadata
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        return array(
            'url' => $uploaded_file['url'],
            'file' => $uploaded_file['file'],
            'type' => $uploaded_file['type'],
            'attachment_id' => $attachment_id
        );
    }
    
    /**
     * Upload a file from custom array
     *
     * @since     1.0.0
     * @param     array     $file        The file array.
     * @param     int       $post_id     The post ID to attach to.
     * @return    array|WP_Error         Upload results or error.
     */
    private function upload_file_from_array($file, $post_id) {
        if (empty($file['name'])) {
            return new WP_Error('empty_file', __('No file uploaded', 'choir-lyrics-manager'));
        }
        
        // Check file type
        $file_type = wp_check_filetype($file['name']);
        
        // Set up allowed file types for practice tracks
        $allowed_types = array('mp3', 'wav', 'ogg', 'm4a', 'mid', 'midi');
        
        if (!in_array($file_type['ext'], $allowed_types)) {
            return new WP_Error(
                'invalid_file_type', 
                sprintf(
                    __('Invalid file type. Allowed types: %s', 'choir-lyrics-manager'),
                    implode(', ', $allowed_types)
                )
            );
        }
        
        // Prepare upload args
        $upload_args = array(
            'test_form' => false,
        );
        
        // Create a temporary file array in the $_FILES format
        $_FILES['temp_file'] = $file;
        
        // Upload the file
        $uploaded_file = wp_handle_upload($_FILES['temp_file'], $upload_args);
        
        // Clean up
        unset($_FILES['temp_file']);
        
        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }
        
        // Insert as attachment
        $attachment = array(
            'guid' => $uploaded_file['url'],
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($uploaded_file['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file'], $post_id);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Generate attachment metadata
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        return array(
            'url' => $uploaded_file['url'],
            'file' => $uploaded_file['file'],
            'type' => $uploaded_file['type'],
            'attachment_id' => $attachment_id
        );
    }
    
    /**
     * Get allowed file types for a specific input
     *
     * @since     1.0.0
     * @param     string    $file_key    The file input key.
     * @return    array                  Array of allowed extensions.
     */
    private function get_allowed_file_types($file_key) {
        $allowed_types = array();
        
        switch ($file_key) {
            case 'clm_sheet_music':
                $allowed_types = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
                break;
                
            case 'clm_audio_file':
            case 'clm_practice_tracks':
                $allowed_types = array('mp3', 'wav', 'ogg', 'm4a');
                break;
                
            case 'clm_midi_file':
                $allowed_types = array('mid', 'midi');
                break;
                
            default:
                $allowed_types = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'mp3', 'wav', 'ogg', 'm4a', 'mid', 'midi');
        }
        
        return $allowed_types;
    }
    
    /**
     * Send notifications about the new submission
     *
     * @since    1.0.0
     * @param    int      $post_id    The post ID.
     */
    private function send_submission_notifications($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return;
        }
        
        $post_status = get_post_status($post_id);
        $post_title = get_the_title($post_id);
        $post_url = get_edit_post_link($post_id, '');
        $submitter = get_userdata($post->post_author);
        
        // Admin notification for pending submissions
        if ($post_status === 'pending') {
            $admin_email = get_option('admin_email');
            
            $subject = sprintf(
                __('[%s] New Lyric Submission: %s', 'choir-lyrics-manager'),
                get_bloginfo('name'),
                $post_title
            );
            
            $message = sprintf(
                __("A new lyric has been submitted and is pending review.\n\nTitle: %s\nSubmitter: %s\n\nPlease review it here: %s", 'choir-lyrics-manager'),
                $post_title,
                $submitter->display_name,
                $post_url
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        // Submitter notification
        $submitter_email = $submitter->user_email;
        
        if ($post_status === 'pending') {
            $subject = sprintf(
                __('[%s] Your Lyric Submission Is Pending Review', 'choir-lyrics-manager'),
                get_bloginfo('name')
            );
            
            $message = sprintf(
                __("Thank you for submitting a lyric.\n\nYour submission '%s' is now pending review by a moderator. You will be notified when it is approved.\n\nThank you!", 'choir-lyrics-manager'),
                $post_title
            );
        } else {
            $subject = sprintf(
                __('[%s] Your Lyric Submission Has Been Published', 'choir-lyrics-manager'),
                get_bloginfo('name')
            );
            
            $message = sprintf(
                __("Thank you for submitting a lyric.\n\nYour submission '%s' has been published and is now available on the site.\n\nView it here: %s\n\nThank you!", 'choir-lyrics-manager'),
                $post_title,
                get_permalink($post_id)
            );
        }
        
        wp_mail($submitter_email, $subject, $message);
    }
    
    /**
     * Render submission form
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             The submission form HTML.
     */
    public function render_submission_form($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
        ), $atts);
        
        // Check if user can submit
        if (!$this->can_submit_lyric()) {
            return '<div class="clm-error">' . __('You must be logged in with appropriate permissions to submit lyrics.', 'choir-lyrics-manager') . '</div>';
        }
        
        // Get form template
        ob_start();
        include CLM_PLUGIN_DIR . 'templates/lyric-submission-form.php';
        return ob_get_clean();
    }
}