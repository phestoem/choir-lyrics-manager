<?php
/**
 * User role and capability management for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Roles {

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
     * Add custom roles on plugin activation
     *
     * @since    1.0.0
     */
    public function add_custom_roles() {
        // Check if roles are already added
        if (get_option('clm_roles_version') == $this->version) {
            return;
        }
        
        // Add Choir Manager role
        add_role(
            'clm_manager',
            __('Choir Manager', 'choir-lyrics-manager'),
            array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'upload_files' => true,
                
                // Custom capabilities for lyrics
                'edit_clm_lyrics' => true,
                'edit_others_clm_lyrics' => true,
                'publish_clm_lyrics' => true,
                'read_private_clm_lyrics' => true,
                'delete_clm_lyrics' => true,
                'delete_others_clm_lyrics' => true,
                
                // Custom capabilities for albums
                'edit_clm_albums' => true,
                'edit_others_clm_albums' => true,
                'publish_clm_albums' => true,
                'read_private_clm_albums' => true,
                'delete_clm_albums' => true,
                'delete_others_clm_albums' => true,
                
                // Custom capabilities for practice logs
                'edit_clm_practice_logs' => true,
                'edit_others_clm_practice_logs' => true,
                'read_private_clm_practice_logs' => true,
                
                // Special capabilities
                'manage_clm_settings' => true,
                'view_clm_analytics' => true,
                'moderate_clm_submissions' => true,
            )
        );
        
        // Add Choir Contributor role
        add_role(
            'clm_contributor',
            __('Choir Contributor', 'choir-lyrics-manager'),
            array(
                'read' => true,
                'upload_files' => true,
                
                // Custom capabilities for lyrics
                'edit_clm_lyrics' => true,
                'publish_clm_lyrics' => false, // Need moderation
                'read_private_clm_lyrics' => false,
                
                // Custom capabilities for practice logs
                'edit_clm_practice_logs' => true,
                
                // Special capabilities
                'submit_clm_lyrics' => true,
            )
        );
        
        // Add Choir Member role
        add_role(
            'clm_member',
            __('Choir Member', 'choir-lyrics-manager'),
            array(
                'read' => true,
                
                // Custom capabilities for practice logs
                'edit_clm_practice_logs' => true,
                
                // Special capabilities
                'view_clm_lyrics' => true,
                'practice_clm_lyrics' => true,
            )
        );
        
        // Add custom capabilities to existing roles
        $this->add_capabilities_to_existing_roles();
        
        // Remember the version so we don't have to do this again
        update_option('clm_roles_version', $this->version);
    }
    
    /**
     * Add custom capabilities to existing WordPress roles
     *
     * @since    1.0.0
     */
    private function add_capabilities_to_existing_roles() {
        // Add capabilities to Administrator role
        $role = get_role('administrator');
        
        if ($role) {
            // Lyric capabilities
            $role->add_cap('edit_clm_lyrics');
            $role->add_cap('edit_others_clm_lyrics');
            $role->add_cap('publish_clm_lyrics');
            $role->add_cap('read_private_clm_lyrics');
            $role->add_cap('delete_clm_lyrics');
            $role->add_cap('delete_others_clm_lyrics');
            
            // Album capabilities
            $role->add_cap('edit_clm_albums');
            $role->add_cap('edit_others_clm_albums');
            $role->add_cap('publish_clm_albums');
            $role->add_cap('read_private_clm_albums');
            $role->add_cap('delete_clm_albums');
            $role->add_cap('delete_others_clm_albums');
            
            // Practice log capabilities
            $role->add_cap('edit_clm_practice_logs');
            $role->add_cap('edit_others_clm_practice_logs');
            $role->add_cap('read_private_clm_practice_logs');
            
            // Special capabilities
            $role->add_cap('manage_clm_settings');
            $role->add_cap('view_clm_analytics');
            $role->add_cap('moderate_clm_submissions');
            $role->add_cap('submit_clm_lyrics');
            $role->add_cap('view_clm_lyrics');
            $role->add_cap('practice_clm_lyrics');
        }
        
        // Add capabilities to Editor role
        $role = get_role('editor');
        
        if ($role) {
            // Lyric capabilities
            $role->add_cap('edit_clm_lyrics');
            $role->add_cap('edit_others_clm_lyrics');
            $role->add_cap('publish_clm_lyrics');
            $role->add_cap('read_private_clm_lyrics');
            $role->add_cap('delete_clm_lyrics');
            
            // Album capabilities
            $role->add_cap('edit_clm_albums');
            $role->add_cap('edit_others_clm_albums');
            $role->add_cap('publish_clm_albums');
            $role->add_cap('read_private_clm_albums');
            
            // Practice log capabilities
            $role->add_cap('edit_clm_practice_logs');
            $role->add_cap('edit_others_clm_practice_logs');
            $role->add_cap('read_private_clm_practice_logs');
            
            // Special capabilities
            $role->add_cap('view_clm_analytics');
            $role->add_cap('moderate_clm_submissions');
            $role->add_cap('submit_clm_lyrics');
            $role->add_cap('view_clm_lyrics');
            $role->add_cap('practice_clm_lyrics');
        }
        
        // Add capabilities to Author role
        $role = get_role('author');
        
        if ($role) {
            // Lyric capabilities
            $role->add_cap('edit_clm_lyrics');
            $role->add_cap('publish_clm_lyrics');
            
            // Practice log capabilities
            $role->add_cap('edit_clm_practice_logs');
            
            // Special capabilities
            $role->add_cap('submit_clm_lyrics');
            $role->add_cap('view_clm_lyrics');
            $role->add_cap('practice_clm_lyrics');
        }
        
        // Add capabilities to Contributor role
        $role = get_role('contributor');
        
        if ($role) {
            // Practice log capabilities
            $role->add_cap('edit_clm_practice_logs');
            
            // Special capabilities
            $role->add_cap('view_clm_lyrics');
            $role->add_cap('practice_clm_lyrics');
        }
        
        // Add capabilities to Subscriber role
        $role = get_role('subscriber');
        
        if ($role) {
            // Special capabilities
            $role->add_cap('view_clm_lyrics');
            $role->add_cap('practice_clm_lyrics');
        }
    }
    
    /**
     * Remove custom roles and capabilities on plugin deactivation
     *
     * @since    1.0.0
     */
    public static function remove_custom_roles() {
        // Remove custom roles
        remove_role('clm_manager');
        remove_role('clm_contributor');
        remove_role('clm_member');
        
        // Remove capabilities from existing roles
        self::remove_capabilities_from_existing_roles();
        
        // Delete the version option
        delete_option('clm_roles_version');
    }
    
    /**
     * Remove custom capabilities from existing WordPress roles
     *
     * @since    1.0.0
     */
    private static function remove_capabilities_from_existing_roles() {
        $roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            
            if (!$role) {
                continue;
            }
            
            // Lyric capabilities
            $role->remove_cap('edit_clm_lyrics');
            $role->remove_cap('edit_others_clm_lyrics');
            $role->remove_cap('publish_clm_lyrics');
            $role->remove_cap('read_private_clm_lyrics');
            $role->remove_cap('delete_clm_lyrics');
            $role->remove_cap('delete_others_clm_lyrics');
            
            // Album capabilities
            $role->remove_cap('edit_clm_albums');
            $role->remove_cap('edit_others_clm_albums');
            $role->remove_cap('publish_clm_albums');
            $role->remove_cap('read_private_clm_albums');
            $role->remove_cap('delete_clm_albums');
            $role->remove_cap('delete_others_clm_albums');
            
            // Practice log capabilities
            $role->remove_cap('edit_clm_practice_logs');
            $role->remove_cap('edit_others_clm_practice_logs');
            $role->remove_cap('read_private_clm_practice_logs');
            
            // Special capabilities
            $role->remove_cap('manage_clm_settings');
            $role->remove_cap('view_clm_analytics');
            $role->remove_cap('moderate_clm_submissions');
            $role->remove_cap('submit_clm_lyrics');
            $role->remove_cap('view_clm_lyrics');
            $role->remove_cap('practice_clm_lyrics');
        }
    }
    
    /**
     * Check if a user can submit lyrics
     *
     * @since     1.0.0
     * @param     int       $user_id    The user ID.
     * @return    boolean               Whether the user can submit lyrics.
     */
    public function can_submit_lyrics($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        
        return $user && $user->has_cap('submit_clm_lyrics');
    }
    
    /**
     * Check if a user can practice lyrics
     *
     * @since     1.0.0
     * @param     int       $user_id    The user ID.
     * @return    boolean               Whether the user can practice lyrics.
     */
    public function can_practice_lyrics($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        
        return $user && $user->has_cap('practice_clm_lyrics');
    }
    
    /**
     * Check if a user can view analytics
     *
     * @since     1.0.0
     * @param     int       $user_id    The user ID.
     * @return    boolean               Whether the user can view analytics.
     */
    public function can_view_analytics($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        
        return $user && $user->has_cap('view_clm_analytics');
    }
    
    /**
     * Check if a user can manage settings
     *
     * @since     1.0.0
     * @param     int       $user_id    The user ID.
     * @return    boolean               Whether the user can manage settings.
     */
    public function can_manage_settings($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        
        return $user && $user->has_cap('manage_clm_settings');
    }
    
    /**
     * Get all users with the ability to submit lyrics
     *
     * @since     1.0.0
     * @return    array     Array of user objects.
     */
    public function get_lyrics_submitters() {
        $args = array(
            'role__in' => array('administrator', 'editor', 'author', 'clm_manager', 'clm_contributor'),
            'fields' => array('ID', 'display_name', 'user_email'),
        );
        
        return get_users($args);
    }
    
    /**
     * Get all users with a specified role
     *
     * @since     1.0.0
     * @param     string    $role    The role name.
     * @return    array              Array of user objects.
     */
    public function get_users_by_role($role) {
        $args = array(
            'role' => $role,
            'fields' => array('ID', 'display_name', 'user_email'),
        );
        
        return get_users($args);
    }
}