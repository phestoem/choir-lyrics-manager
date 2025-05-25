<?php
/**
 * UPDATED CLM_User_Management Class
 * Integrates with existing clm_member CPT system
 */

class CLM_User_Management {
    
    private $plugin_name;
    private $version;
    private $loader;
    private $members_class;
    
    public function __construct($plugin_name, $version, $loader = null) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = $loader;
        
        // Get access to the existing members class
        $this->members_class = new CLM_Members($plugin_name, $version);
    }
    
    /**
     * Initialize the integrated system
     */
    public function init() {
        if ($this->loader) {
            $this->register_hooks_via_loader();
        } else {
            $this->register_hooks_directly();
        }
    }
    
    /**
     * Register hooks via loader
     */
    private function register_hooks_via_loader() {
        // Core user events
        $this->loader->add_action('init', $this, 'create_custom_roles');
        $this->loader->add_action('user_register', $this, 'handle_new_user_registration');
        $this->loader->add_action('wp_login', $this, 'handle_user_login', 10, 2);
        
        // Registration form integration
        $this->loader->add_action('register_form', $this, 'add_registration_fields');
        $this->loader->add_filter('registration_errors', $this, 'validate_registration_fields', 10, 3);
        
        // Profile integration
        $this->loader->add_action('show_user_profile', $this, 'add_choir_profile_section');
        $this->loader->add_action('edit_user_profile', $this, 'add_choir_profile_section');
        $this->loader->add_action('personal_options_update', $this, 'save_choir_profile_section');
        $this->loader->add_action('edit_user_profile_update', $this, 'save_choir_profile_section');
        
        // Admin interface enhancements
        $this->loader->add_filter('manage_users_columns', $this, 'add_user_choir_columns');
        $this->loader->add_filter('manage_users_custom_column', $this, 'show_user_choir_column_content', 10, 3);
        
        // Member CPT enhancements
        $this->loader->add_action('save_post_clm_member', $this, 'sync_member_to_user', 20); // After existing save
        $this->loader->add_action('delete_post', $this, 'handle_member_deletion');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_clm_create_user_from_member', $this, 'ajax_create_user_from_member');
        $this->loader->add_action('wp_ajax_clm_update_member_user_status', $this, 'ajax_update_member_user_status');
        
        // Shortcodes
        $this->loader->add_action('init', $this, 'register_shortcodes');
    }
    
    /**
     * Create custom roles with capabilities
     */
    public function create_custom_roles() {
        if (get_option('clm_integrated_roles_created')) {
            return;
        }
        
        // Define capabilities
        $choir_member_caps = array(
            'read' => true,
            'clm_access_lyrics' => true,
            'clm_practice_tracking' => true,
            'clm_create_playlists' => true,
            'clm_view_member_area' => true,
            'edit_clm_member' => true, // Can edit their own member profile
            'read_clm_member' => true,
        );
        
        $choir_director_caps = array_merge($choir_member_caps, array(
            'clm_manage_members' => true,
            'clm_view_all_progress' => true,
            'clm_manage_playlists' => true,
            'edit_clm_members' => true,
            'edit_others_clm_members' => true,
            'publish_clm_members' => true,
            'delete_clm_members' => true,
        ));
        
        // Create roles
        add_role('choir_member', __('Choir Member', 'choir-lyrics-manager'), $choir_member_caps);
        add_role('choir_director', __('Choir Director', 'choir-lyrics-manager'), $choir_director_caps);
        
        // Add capabilities to admin
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($choir_director_caps as $cap => $granted) {
                $admin_role->add_cap($cap, $granted);
            }
            $admin_role->add_cap('clm_manage_roles', true);
            $admin_role->add_cap('clm_plugin_settings', true);
        }
        
        update_option('clm_integrated_roles_created', true);
    }
    
    /**
     * Handle new user registration - create linked member CPT
     */
    public function handle_new_user_registration($user_id) {
        $registration_source = isset($_POST['clm_registration_source']) ? 
            sanitize_text_field($_POST['clm_registration_source']) : 'default';
        
        if ($registration_source === 'choir_registration') {
            // Create linked member CPT post
            $this->create_member_post_for_user($user_id);
            
            // Set user role
            $user = new WP_User($user_id);
            $user->set_role('choir_member');
            
            // Set initial status
            $auto_approve = get_option('clm_auto_approve_members', false);
            $status = $auto_approve ? 'active' : 'pending';
            update_user_meta($user_id, 'clm_member_status', $status);
            update_user_meta($user_id, 'clm_member_since', current_time('mysql'));
            
            // Notify admins
            $this->notify_admins_new_member($user_id);
        }
    }
    
    /**
     * Create member CPT post for a user
     */
    private function create_member_post_for_user($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return false;
        
        // Check if member post already exists
        $existing_member = $this->members_class->get_member_by_user_id($user_id);
        if ($existing_member) {
            return $existing_member->ID;
        }
        
        // Create member post
        $member_data = array(
            'post_title' => $user->display_name ?: $user->user_login,
            'post_type' => 'clm_member',
            'post_status' => 'publish',
            'post_author' => 1, // Admin
        );
        
        $member_id = wp_insert_post($member_data);
        
        if ($member_id && !is_wp_error($member_id)) {
            // Link to WordPress user
            update_post_meta($member_id, '_clm_wp_user_id', $user_id);
            
            // Set basic info from registration
            update_post_meta($member_id, '_clm_member_email', $user->user_email);
            update_post_meta($member_id, '_clm_member_since', current_time('mysql'));
            
            $auto_approve = get_option('clm_auto_approve_members', false);
            $status = $auto_approve ? 'active' : 'inactive';
            update_post_meta($member_id, '_clm_member_status', $status);
            
            // Set voice part and other registration data
            if (isset($_POST['clm_voice_part'])) {
                $voice_part = sanitize_text_field($_POST['clm_voice_part']);
                // Set voice type taxonomy
                wp_set_post_terms($member_id, array($voice_part), 'clm_voice_type');
            }
            
            if (isset($_POST['clm_experience_level'])) {
                update_post_meta($member_id, '_clm_experience_level', 
                    sanitize_text_field($_POST['clm_experience_level']));
            }
            
            if (isset($_POST['clm_musical_background'])) {
                update_post_meta($member_id, '_clm_musical_background', 
                    sanitize_textarea_field($_POST['clm_musical_background']));
            }
            
            return $member_id;
        }
        
        return false;
    }
    
    /**
     * Add registration fields to WordPress registration form
     */
    public function add_registration_fields() {
        if (!$this->is_choir_registration_page()) {
            return;
        }
        ?>
        <p>
            <label for="first_name"><?php _e('First Name', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
            <input type="text" name="first_name" id="first_name" class="input" required>
        </p>
        
        <p>
            <label for="last_name"><?php _e('Last Name', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
            <input type="text" name="last_name" id="last_name" class="input" required>
        </p>
        
        <p>
            <label for="clm_voice_part"><?php _e('Voice Part', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
            <select name="clm_voice_part" id="clm_voice_part" class="input" required>
                <option value=""><?php _e('Select Voice Part', 'choir-lyrics-manager'); ?></option>
                <option value="soprano"><?php _e('Soprano', 'choir-lyrics-manager'); ?></option>
                <option value="alto"><?php _e('Alto', 'choir-lyrics-manager'); ?></option>
                <option value="tenor"><?php _e('Tenor', 'choir-lyrics-manager'); ?></option>
                <option value="bass"><?php _e('Bass', 'choir-lyrics-manager'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="clm_experience_level"><?php _e('Experience Level', 'choir-lyrics-manager'); ?></label>
            <select name="clm_experience_level" id="clm_experience_level" class="input">
                <option value="beginner"><?php _e('Beginner', 'choir-lyrics-manager'); ?></option>
                <option value="intermediate"><?php _e('Intermediate', 'choir-lyrics-manager'); ?></option>
                <option value="advanced"><?php _e('Advanced', 'choir-lyrics-manager'); ?></option>
                <option value="professional"><?php _e('Professional', 'choir-lyrics-manager'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="clm_musical_background"><?php _e('Musical Background (Optional)', 'choir-lyrics-manager'); ?></label>
            <textarea name="clm_musical_background" id="clm_musical_background" class="input" rows="3" 
                placeholder="<?php esc_attr_e('Tell us about your musical experience...', 'choir-lyrics-manager'); ?>"></textarea>
        </p>
        
        <input type="hidden" name="clm_registration_source" value="choir_registration">
        <?php
    }
    
    	/**
 * UPDATE CLM_User_Management class to integrate invitation codes
 */

// Add this method to your CLM_User_Management class:

public function validate_registration_fields($errors, $sanitized_user_login, $user_email) {
    if (!$this->is_choir_registration_page()) {
        return $errors;
    }
    
    // Existing validation...
    if (empty($_POST['first_name'])) {
        $errors->add('first_name_error', 
            __('<strong>ERROR</strong>: Please enter your first name.', 'choir-lyrics-manager'));
    }
    
    if (empty($_POST['last_name'])) {
        $errors->add('last_name_error', 
            __('<strong>ERROR</strong>: Please enter your last name.', 'choir-lyrics-manager'));
    }
    
    if (empty($_POST['clm_voice_part'])) {
        $errors->add('voice_part_error', 
            __('<strong>ERROR</strong>: Please select your voice part.', 'choir-lyrics-manager'));
    }
    
    // ADD INVITATION CODE VALIDATION:
    if (get_option('clm_require_invitation_codes', true)) {
        $invitation_code = isset($_POST['clm_invitation_code']) ? sanitize_text_field($_POST['clm_invitation_code']) : '';
        
        if (empty($invitation_code)) {
            $errors->add('invitation_code_required', 
                __('<strong>ERROR</strong>: Invitation code is required.', 'choir-lyrics-manager'));
        } else {
            // Validate with voice part
            $voice_part = isset($_POST['clm_voice_part']) ? sanitize_text_field($_POST['clm_voice_part']) : '';
            
            $invitation_system = new CLM_Invitation_Codes($this->plugin_name, $this->version);
            $validation = $invitation_system->validate_code($invitation_code, $voice_part);
            
            if (!$validation['valid']) {
                $errors->add('invitation_code_invalid', 
                    __('<strong>ERROR</strong>: ', 'choir-lyrics-manager') . $validation['message']);
            }
        }
    }
    
    return $errors;
}
    /**
     * Add choir information section to user profile
     */
    public function add_choir_profile_section($user) {
        $member_post = $this->members_class->get_member_by_user_id($user->ID);
        $member_status = get_user_meta($user->ID, 'clm_member_status', true);
        
        ?>
        <h3><?php _e('Choir Information', 'choir-lyrics-manager'); ?></h3>
        <table class="form-table">
            <?php if ($member_post): ?>
            <tr>
                <th><label><?php _e('Member Profile', 'choir-lyrics-manager'); ?></label></th>
                <td>
                    <a href="<?php echo admin_url('post.php?post=' . $member_post->ID . '&action=edit'); ?>" class="button">
                        <?php _e('Edit Full Member Profile', 'choir-lyrics-manager'); ?>
                    </a>
                    <p class="description"><?php _e('Edit detailed choir member information.', 'choir-lyrics-manager'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <th><label for="clm_member_status"><?php _e('Member Status', 'choir-lyrics-manager'); ?></label></th>
                <td>
                    <?php if (current_user_can('clm_manage_members')): ?>
                    <select name="clm_member_status" id="clm_member_status">
                        <option value="pending" <?php selected($member_status, 'pending'); ?>>
                            <?php _e('Pending Approval', 'choir-lyrics-manager'); ?>
                        </option>
                        <option value="active" <?php selected($member_status, 'active'); ?>>
                            <?php _e('Active Member', 'choir-lyrics-manager'); ?>
                        </option>
                        <option value="inactive" <?php selected($member_status, 'inactive'); ?>>
                            <?php _e('Inactive', 'choir-lyrics-manager'); ?>
                        </option>
                    </select>
                    <?php else: ?>
                        <strong><?php echo esc_html(ucfirst($member_status ?: 'pending')); ?></strong>
                    <?php endif; ?>
                </td>
            </tr>
            
            <?php if (!$member_post && current_user_can('clm_manage_members')): ?>
            <tr>
                <th><label><?php _e('Create Member Profile', 'choir-lyrics-manager'); ?></label></th>
                <td>
                    <button type="button" class="button" id="clm-create-member-profile" data-user-id="<?php echo $user->ID; ?>">
                        <?php _e('Create Member Profile', 'choir-lyrics-manager'); ?>
                    </button>
                    <p class="description"><?php _e('Create a detailed member profile for this user.', 'choir-lyrics-manager'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('#clm-create-member-profile').on('click', function() {
                var userId = $(this).data('user-id');
                $.post(ajaxurl, {
                    action: 'clm_create_user_from_member',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('clm_user_member'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save choir profile section
     */
    public function save_choir_profile_section($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        if (isset($_POST['clm_member_status'])) {
            $old_status = get_user_meta($user_id, 'clm_member_status', true);
            $new_status = sanitize_text_field($_POST['clm_member_status']);
            
            update_user_meta($user_id, 'clm_member_status', $new_status);
            
            // Sync with member CPT
            $member_post = $this->members_class->get_member_by_user_id($user_id);
            if ($member_post) {
                update_post_meta($member_post->ID, '_clm_member_status', $new_status);
            }
            
            // Handle status change notifications
            if ($old_status !== $new_status) {
                $this->handle_status_change($user_id, $old_status, $new_status);
            }
        }
    }
    
    /**
     * Add choir columns to users table
     */
    public function add_user_choir_columns($columns) {
        $columns['choir_member'] = __('Choir Member', 'choir-lyrics-manager');
        $columns['voice_part'] = __('Voice Part', 'choir-lyrics-manager');
        $columns['member_status'] = __('Status', 'choir-lyrics-manager');
        return $columns;
    }
    
    /**
     * Show choir column content
     */
    public function show_user_choir_column_content($value, $column_name, $user_id) {
        switch ($column_name) {
            case 'choir_member':
                $member_post = $this->members_class->get_member_by_user_id($user_id);
                if ($member_post) {
                    $value = '<a href="' . admin_url('post.php?post=' . $member_post->ID . '&action=edit') . '">' . 
                             __('View Profile', 'choir-lyrics-manager') . '</a>';
                } else {
                    $user = new WP_User($user_id);
                    if (in_array('choir_member', $user->roles) || in_array('choir_director', $user->roles)) {
                        $value = '<em>' . __('No profile', 'choir-lyrics-manager') . '</em>';
                    } else {
                        $value = '—';
                    }
                }
                break;
                
            case 'voice_part':
                $member_post = $this->members_class->get_member_by_user_id($user_id);
                if ($member_post) {
                    $voice_types = get_the_terms($member_post->ID, 'clm_voice_type');
                    if ($voice_types && !is_wp_error($voice_types)) {
                        $value = esc_html($voice_types[0]->name);
                    }
                }
                break;
                
            case 'member_status':
                $status = get_user_meta($user_id, 'clm_member_status', true);
                if ($status) {
                    $status_labels = array(
                        'pending' => __('Pending', 'choir-lyrics-manager'),
                        'active' => __('Active', 'choir-lyrics-manager'),
                        'inactive' => __('Inactive', 'choir-lyrics-manager'),
                    );
                    $value = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
                }
                break;
        }
        
        return $value;
    }
    
    /**
     * Sync member CPT changes to user meta
     */
    public function sync_member_to_user($post_id) {
        if (get_post_type($post_id) !== 'clm_member') {
            return;
        }
        
        $user_id = get_post_meta($post_id, '_clm_wp_user_id', true);
        if (!$user_id) {
            return;
        }
        
        // Sync status
        $member_status = get_post_meta($post_id, '_clm_member_status', true);
        if ($member_status) {
            update_user_meta($user_id, 'clm_member_status', $member_status);
        }
        
        // Sync email if different
        $member_email = get_post_meta($post_id, '_clm_member_email', true);
        if ($member_email) {
            $user = get_user_by('ID', $user_id);
            if ($user && $user->user_email !== $member_email) {
                wp_update_user(array(
                    'ID' => $user_id,
                    'user_email' => $member_email
                ));
            }
        }
    }
    
    /**
     * Check if user has choir access
     */
    public function user_has_choir_access($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check capability first
        $user = new WP_User($user_id);
        if ($user->has_cap('clm_access_lyrics')) {
            return true;
        }
        
        // Check member status
        $status = get_user_meta($user_id, 'clm_member_status', true);
        $access_level = get_option('clm_member_access_level', 'active_only');
        
        switch ($access_level) {
            case 'all_members':
                return in_array($status, array('active', 'pending', 'inactive'));
            case 'logged_in':
                return true; // Any logged-in user
            case 'active_only':
            default:
                return $status === 'active';
        }
    }
    
    /**
     * Member registration shortcode
     */
    public function member_registration_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
            'show_login_link' => 'true'
        ), $atts);
        
        if (is_user_logged_in()) {
            return '<p>' . __('You are already registered and logged in.', 'choir-lyrics-manager') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="clm-registration-form-container">
            <form method="post" action="<?php echo esc_url(site_url('wp-login.php?action=register&choir=1')); ?>" class="clm-registration-form">
                <h3><?php _e('Join Our Choir', 'choir-lyrics-manager'); ?></h3>
                
                <div class="clm-form-row">
                    <div class="clm-form-field">
                        <label for="user_login"><?php _e('Username', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
                        <input type="text" name="user_login" id="user_login" required>
                    </div>
                    
                    <div class="clm-form-field">
                        <label for="user_email"><?php _e('Email', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
                        <input type="email" name="user_email" id="user_email" required>
                    </div>
                </div>
                
                <div class="clm-form-row">
                    <div class="clm-form-field">
                        <label for="first_name"><?php _e('First Name', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
                        <input type="text" name="first_name" id="first_name" required>
                    </div>
                    
                    <div class="clm-form-field">
                        <label for="last_name"><?php _e('Last Name', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
                        <input type="text" name="last_name" id="last_name" required>
                    </div>
                </div>
                
                <div class="clm-form-row">
                    <div class="clm-form-field">
                        <label for="clm_voice_part"><?php _e('Voice Part', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
                        <select name="clm_voice_part" id="clm_voice_part" required>
                            <option value=""><?php _e('Select Voice Part', 'choir-lyrics-manager'); ?></option>
                            <option value="soprano"><?php _e('Soprano', 'choir-lyrics-manager'); ?></option>
                            <option value="alto"><?php _e('Alto', 'choir-lyrics-manager'); ?></option>
                            <option value="tenor"><?php _e('Tenor', 'choir-lyrics-manager'); ?></option>
                            <option value="bass"><?php _e('Bass', 'choir-lyrics-manager'); ?></option>
                        </select>
                    </div>
                    <?php if (get_option('clm_require_invitation_codes', true)): ?>
					<div class="clm-form-field">
						<label for="clm_invitation_code"><?php _e('Invitation Code', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
						<input type="text" name="clm_invitation_code" id="clm_invitation_code" required value="<?php echo isset($_GET['code']) ? esc_attr(sanitize_text_field($_GET['code'])) : ''; ?>">
						<span id="clm-code-validation" class="clm-validation-message"></span>
					</div>
					<?php endif; ?>
                    <div class="clm-form-field">
                        <label for="clm_experience_level"><?php _e('Experience Level', 'choir-lyrics-manager'); ?></label>
                        <select name="clm_experience_level" id="clm_experience_level">
                            <option value="beginner"><?php _e('Beginner', 'choir-lyrics-manager'); ?></option>
                            <option value="intermediate"><?php _e('Intermediate', 'choir-lyrics-manager'); ?></option>
                            <option value="advanced"><?php _e('Advanced', 'choir-lyrics-manager'); ?></option>
                            <option value="professional"><?php _e('Professional', 'choir-lyrics-manager'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="clm-form-field">
                    <label for="clm_musical_background"><?php _e('Musical Background (Optional)', 'choir-lyrics-manager'); ?></label>
                    <textarea name="clm_musical_background" id="clm_musical_background" rows="3" 
                        placeholder="<?php esc_attr_e('Tell us about your musical experience...', 'choir-lyrics-manager'); ?>"></textarea>
                </div>
                
                <div class="clm-form-field">
                    <input type="submit" value="<?php esc_attr_e('Register for Choir', 'choir-lyrics-manager'); ?>" class="button clm-button-primary">
                </div>
                
                <?php if ($atts['show_login_link'] === 'true'): ?>
                <div class="clm-form-field">
                    <p><a href="<?php echo wp_login_url(); ?>"><?php _e('Already have an account? Log in', 'choir-lyrics-manager'); ?></a></p>
                </div>
                <?php endif; ?>
                
                <input type="hidden" name="clm_registration_source" value="choir_registration">
            </form>
        </div>
        
        <style>
        .clm-registration-form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .clm-registration-form h3 {
            margin-top: 0;
            text-align: center;
        }
        .clm-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .clm-form-field {
            margin-bottom: 20px;
        }
        .clm-form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .clm-form-field input,
        .clm-form-field select,
        .clm-form-field textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .required {
            color: red;
        }
        .clm-button-primary {
            background: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        @media (max-width: 600px) {
            .clm-form-row {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Member dashboard shortcode
     */
    public function member_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="clm-access-notice">
                <p>' . __('Please log in to access your member dashboard.', 'choir-lyrics-manager') . '</p>
                <p><a href="' . wp_login_url() . '" class="button">' . __('Login', 'choir-lyrics-manager') . '</a></p>
            </div>';
        }
        
        if (!$this->user_has_choir_access()) {
            return '<div class="clm-access-notice">
                <p>' . __('Your membership is pending approval. Please contact the choir director.', 'choir-lyrics-manager') . '</p>
            </div>';
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $member_post = $this->members_class->get_member_by_user_id($user_id);
        
        ob_start();
        ?>
        <div class="clm-member-dashboard">
            <h2><?php printf(__('Welcome, %s!', 'choir-lyrics-manager'), $user->display_name); ?></h2>
            
            <?php if ($member_post): 
                $voice_types = get_the_terms($member_post->ID, 'clm_voice_type');
                $voice_part = $voice_types && !is_wp_error($voice_types) ? $voice_types[0]->name : '';
                $member_since = get_post_meta($member_post->ID, '_clm_member_since', true);
            ?>
            <div class="clm-member-info">
                <div class="clm-info-grid">
                    <?php if ($voice_part): ?>
                    <div class="clm-info-item">
                        <strong><?php _e('Voice Part:', 'choir-lyrics-manager'); ?></strong>
                        <span><?php echo esc_html($voice_part); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($member_since): ?>
                    <div class="clm-info-item">
                        <strong><?php _e('Member Since:', 'choir-lyrics-manager'); ?></strong>
                        <span><?php echo date_i18n(get_option('date_format'), strtotime($member_since)); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="clm-quick-actions">
                <h3><?php _e('Quick Actions', 'choir-lyrics-manager'); ?></h3>
                <div class="clm-action-buttons">
                    <a href="<?php echo esc_url(get_post_type_archive_link('clm_lyric')); ?>" class="button">
                        <?php _e('Browse All Lyrics', 'choir-lyrics-manager'); ?>
                    </a>
                    <?php if ($member_post): ?>
                    <a href="<?php echo admin_url('post.php?post=' . $member_post->ID . '&action=edit'); ?>" class="button">
                        <?php _e('Edit My Profile', 'choir-lyrics-manager'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .clm-member-dashboard {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .clm-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .clm-info-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .clm-info-item strong {
            display: block;
            margin-bottom: 5px;
        }
        .clm-action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .clm-access-notice {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
            margin: 20px 0;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('clm_member_registration', array($this, 'member_registration_shortcode'));
        add_shortcode('clm_member_dashboard', array($this, 'member_dashboard_shortcode'));
    }
    
    /**
     * AJAX: Create member profile for existing user
     */
    public function ajax_create_user_from_member() {
        check_ajax_referer('clm_user_member', 'nonce');
        
        if (!current_user_can('clm_manage_members')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $user_id = intval($_POST['user_id']);
        $member_id = $this->create_member_post_for_user($user_id);
        
        if ($member_id) {
            wp_send_json_success(array(
                'message' => __('Member profile created successfully.', 'choir-lyrics-manager'),
                'member_id' => $member_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create member profile.', 'choir-lyrics-manager')));
        }
    }
    
    /**
     * Handle status changes
     */
    private function handle_status_change($user_id, $old_status, $new_status) {
        // Send activation email if approved
        if ($new_status === 'active' && $old_status === 'pending') {
            $this->send_activation_email($user_id);
        }
        
        update_user_meta($user_id, 'clm_status_updated', current_time('mysql'));
    }
    
    /**
     * Send activation email
     */
    private function send_activation_email($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;
        
        $subject = __('Welcome to the Choir - Your Account is Active!', 'choir-lyrics-manager');
        $message = sprintf(
            __('Hi %s,

Your choir membership has been approved! You now have full access to all choir features.

Login here: %s

Welcome to the choir!', 'choir-lyrics-manager'),
            $user->display_name,
            wp_login_url()
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Notify admins of new registration
     */
    private function notify_admins_new_member($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;
        
        $admins = get_users(array('role' => 'administrator'));
        $directors = get_users(array('role' => 'choir_director'));
        $recipients = array_merge($admins, $directors);
        
        foreach ($recipients as $recipient) {
            $subject = __('New Choir Member Registration', 'choir-lyrics-manager');
            $message = sprintf(
                __('A new member has registered:

Name: %s
Email: %s

Please review their membership in the admin area.', 'choir-lyrics-manager'),
                $user->display_name,
                $user->user_email
            );
            
            wp_mail($recipient->user_email, $subject, $message);
        }
    }
    
    /**
     * Check if current page is choir registration
     */
    private function is_choir_registration_page() {
        return isset($_GET['choir']) || 
               isset($_POST['clm_registration_source']) || 
               strpos($_SERVER['REQUEST_URI'], 'choir-registration') !== false;
    }
	
	

}





/* 
IMPLEMENTATION STEPS FOR INTEGRATION:

1. Replace the CLM_User_Management class with this integrated version
2. Update class-choir-lyrics-manager.php to load this properly
3. The existing clm_member CPT system will be preserved and enhanced
4. WordPress users will be linked to member CPTs via _clm_wp_user_id
5. Access control uses WordPress user roles + member status

BENEFITS OF THIS APPROACH:
✅ Preserves existing member data structure
✅ Adds proper WordPress user authentication
✅ Maintains backward compatibility
✅ Enhances admin interface with user management
✅ Provides registration workflow
✅ Links users and members seamlessly
*/