<?php
/**
 * Settings functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Settings
{

    private $plugin_name;
    private $version;
    private $options_key = 'clm_settings'; // The key for get_option()
    private $settings_page_slug = 'clm_settings'; // The slug for the settings page menu

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        // The init hook for register_settings is added by Choir_Lyrics_Manager class
    }

    /**
     * Register settings
     *
     * @since    1.0.0
     */
    public function register_settings()
    {
        // Register the single option that will store all settings as an array
        register_setting(
            $this->options_key . '_group',   // Option group, e.g., 'clm_settings_group'
            $this->options_key,              // Option name, e.g., 'clm_settings'
            array($this, 'sanitize_settings') // Sanitization callback for the entire array
        );

        // --- General Section ---
        add_settings_section(
            'clm_general_section',
            __('General Settings', 'choir-lyrics-manager'),
            array($this, 'render_section_description_callback'), // Generic callback
            $this->settings_page_slug // Page slug this section appears on
        );
        add_settings_field('archive_title', __('Lyrics Archive Title', 'choir-lyrics-manager'), array($this, 'archive_title_callback'), $this->settings_page_slug, 'clm_general_section');
        add_settings_field('items_per_page', __('Lyrics Per Page', 'choir-lyrics-manager'), array($this, 'items_per_page_callback'), $this->settings_page_slug, 'clm_general_section');
        add_settings_field('show_difficulty', __('Show Difficulty', 'choir-lyrics-manager'), array($this, 'show_difficulty_callback'), $this->settings_page_slug, 'clm_general_section');
        add_settings_field('show_composer', __('Show Composer', 'choir-lyrics-manager'), array($this, 'show_composer_callback'), $this->settings_page_slug, 'clm_general_section');

        // ADDED: Playlist Display Page setting to General Section
        add_settings_field(
            'clm_playlist_view_page_id', // Unique ID for the field
            __('Playlist Display Page', 'choir-lyrics-manager'),
            array($this, 'render_playlist_view_page_dropdown_field'), // Callback to render the field
            $this->settings_page_slug,      // Page slug
            'clm_general_section'           // Section ID it belongs to
        );

        // --- Roles Section ---
        add_settings_section('clm_roles_section', __('Role Settings', 'choir-lyrics-manager'), array($this, 'render_section_description_callback'), $this->settings_page_slug);
        add_settings_field('submission_roles', __('Who Can Submit Lyrics', 'choir-lyrics-manager'), array($this, 'submission_roles_callback'), $this->settings_page_slug, 'clm_roles_section');
        add_settings_field('analytics_roles', __('Who Can View Analytics', 'choir-lyrics-manager'), array($this, 'analytics_roles_callback'), $this->settings_page_slug, 'clm_roles_section');

        // --- Practice Section ---
        add_settings_section('clm_practice_section', __('Practice Settings', 'choir-lyrics-manager'), array($this, 'render_section_description_callback'), $this->settings_page_slug);
        add_settings_field('enable_practice', __('Enable Practice Tracking', 'choir-lyrics-manager'), array($this, 'enable_practice_callback'), $this->settings_page_slug, 'clm_practice_section');
        add_settings_field('practice_notification', __('Practice Reminders', 'choir-lyrics-manager'), array($this, 'practice_notification_callback'), $this->settings_page_slug, 'clm_practice_section');

        // --- Appearance Section ---
        add_settings_section('clm_appearance_section', __('Appearance Settings', 'choir-lyrics-manager'), array($this, 'render_section_description_callback'), $this->settings_page_slug);
        add_settings_field('primary_color', __('Primary Color', 'choir-lyrics-manager'), array($this, 'primary_color_callback'), $this->settings_page_slug, 'clm_appearance_section');
        add_settings_field('secondary_color', __('Secondary Color', 'choir-lyrics-manager'), array($this, 'secondary_color_callback'), $this->settings_page_slug, 'clm_appearance_section');
        add_settings_field('font_size', __('Base Font Size', 'choir-lyrics-manager'), array($this, 'font_size_callback'), $this->settings_page_slug, 'clm_appearance_section');
    }

    /**
     * Generic callback for rendering section descriptions.
     */
    public function render_section_description_callback($args)
    {
        switch ($args['id']) {
            case 'clm_general_section':
                echo '<p>' . __('Configure general settings for the Choir Lyrics Manager plugin.', 'choir-lyrics-manager') . '</p>';
                break;
            case 'clm_roles_section':
                echo '<p>' . __('Configure user roles and permissions.', 'choir-lyrics-manager') . '</p>';
                break;
            case 'clm_practice_section':
                echo '<p>' . __('Configure practice tracking settings.', 'choir-lyrics-manager') . '</p>';
                break;
            case 'clm_appearance_section':
                echo '<p>' . __('Customize the appearance of the plugin.', 'choir-lyrics-manager') . '</p>';
                break;
        }
    }


    // Callback function in CLM_Settings
    public function render_playlist_view_page_dropdown_field()
    {
        $options = get_option($this->options_key); // Get the main settings array
        $selected_page_id = isset($options['clm_playlist_view_page_id']) ? $options['clm_playlist_view_page_id'] : 0;

        wp_dropdown_pages(array(
            'name' => esc_attr($this->options_key . '[clm_playlist_view_page_id]'), // Save as part of the main options array
            'id' => 'clm_playlist_view_page_id_field', // Unique ID for label_for
            'selected' => $selected_page_id,
            'show_option_none' => __('— Select a Page —', 'choir-lyrics-manager'),
            'option_none_value' => '0', // Value for "Select a Page"
        ));
        echo '<p class="description">' .
            sprintf(
                wp_kses_post(__('Select the page where you have placed the %s shortcode. This page will display individual playlists.', 'choir-lyrics-manager')),
                '<code>[clm_view_playlist_tracks]</code>'
            ) . '</p>';
    }


    public function sanitize_settings($input)
    {
        $sanitized_input = array();
        $options = get_option($this->options_key); // Get existing options to merge/preserve

        // Sanitize General settings
        $sanitized_input['archive_title'] = isset($input['archive_title']) ? sanitize_text_field($input['archive_title']) : ($options['archive_title'] ?? '');
        $sanitized_input['items_per_page'] = isset($input['items_per_page']) ? absint($input['items_per_page']) : ($options['items_per_page'] ?? 10);
        $sanitized_input['show_difficulty'] = isset($input['show_difficulty']) ? 1 : 0; // Checkbox
        $sanitized_input['show_composer'] = isset($input['show_composer']) ? 1 : 0;   // Checkbox

        // Sanitize Playlist View Page ID
        $sanitized_input['clm_playlist_view_page_id'] = isset($input['clm_playlist_view_page_id']) ? absint($input['clm_playlist_view_page_id']) : ($options['clm_playlist_view_page_id'] ?? 0);

        // Sanitize Roles settings
        $sanitized_input['submission_roles'] = isset($input['submission_roles']) && is_array($input['submission_roles']) ? array_map('sanitize_key', $input['submission_roles']) : ($options['submission_roles'] ?? array());
        $sanitized_input['analytics_roles'] = isset($input['analytics_roles']) && is_array($input['analytics_roles']) ? array_map('sanitize_key', $input['analytics_roles']) : ($options['analytics_roles'] ?? array());

        // Sanitize Practice settings
        $sanitized_input['enable_practice'] = isset($input['enable_practice']) ? 1 : 0; // Checkbox
        $sanitized_input['practice_notification'] = isset($input['practice_notification']) ? absint($input['practice_notification']) : ($options['practice_notification'] ?? 7);

        // Sanitize Appearance settings
        $sanitized_input['primary_color'] = isset($input['primary_color']) ? sanitize_hex_color($input['primary_color']) : ($options['primary_color'] ?? '#3498db');
        $sanitized_input['secondary_color'] = isset($input['secondary_color']) ? sanitize_hex_color($input['secondary_color']) : ($options['secondary_color'] ?? '#2ecc71');
        $sanitized_input['font_size'] = isset($input['font_size']) ? absint($input['font_size']) : ($options['font_size'] ?? 16);

        return $sanitized_input;
    }

    /**
     * General section callback
     *
     * @since    1.0.0
     */
    public function general_section_callback()
    {
        echo '<p>' . __('Configure general settings for the Choir Lyrics Manager plugin.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Roles section callback
     *
     * @since    1.0.0
     */
    public function roles_section_callback()
    {
        echo '<p>' . __('Configure user roles and permissions.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Practice section callback
     *
     * @since    1.0.0
     */
    public function practice_section_callback()
    {
        echo '<p>' . __('Configure practice tracking settings.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Appearance section callback
     *
     * @since    1.0.0
     */
    public function appearance_section_callback()
    {
        echo '<p>' . __('Customize the appearance of the plugin.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Archive title field callback
     *
     * @since    1.0.0
     */
    public function archive_title_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['archive_title']) ? $options['archive_title'] : __('Choir Lyrics', 'choir-lyrics-manager');

        echo '<input type="text" id="archive_title" name="clm_settings[archive_title]" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . __('Title for the lyrics archive page.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Items per page field callback
     *
     * @since    1.0.0
     */
    public function items_per_page_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['items_per_page']) ? $options['items_per_page'] : 10;

        echo '<input type="number" id="items_per_page" name="clm_settings[items_per_page]" value="' . esc_attr($value) . '" class="small-text" min="1" max="100">';
        echo '<p class="description">' . __('Number of lyrics to display per page in archives and search results.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Show difficulty field callback
     *
     * @since    1.0.0
     */
    public function show_difficulty_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['show_difficulty']) ? $options['show_difficulty'] : true;

        echo '<input type="checkbox" id="show_difficulty" name="clm_settings[show_difficulty]" value="1" ' . checked(1, $value, false) . '>';
        echo '<label for="show_difficulty">' . __('Show difficulty level on lyric pages', 'choir-lyrics-manager') . '</label>';
    }

    /**
     * Show composer field callback
     *
     * @since    1.0.0
     */
    public function show_composer_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['show_composer']) ? $options['show_composer'] : true;

        echo '<input type="checkbox" id="show_composer" name="clm_settings[show_composer]" value="1" ' . checked(1, $value, false) . '>';
        echo '<label for="show_composer">' . __('Show composer on lyric pages', 'choir-lyrics-manager') . '</label>';
    }

    /**
     * Submission roles field callback
     *
     * @since    1.0.0
     */
    public function submission_roles_callback()
    {
        $options = get_option('clm_settings');
        $selected_roles = isset($options['submission_roles']) ? $options['submission_roles'] : array('administrator', 'editor', 'author', 'clm_manager', 'clm_contributor');

        $roles = $this->get_all_roles();

        echo '<fieldset>';

        foreach ($roles as $role_key => $role_name) {
            $checked = in_array($role_key, $selected_roles) ? 'checked="checked"' : '';

            echo '<label>';
            echo '<input type="checkbox" name="clm_settings[submission_roles][]" value="' . esc_attr($role_key) . '" ' . $checked . '>';
            echo esc_html($role_name);
            echo '</label><br>';
        }

        echo '</fieldset>';
        echo '<p class="description">' . __('Select which user roles can submit lyrics.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Analytics roles field callback
     *
     * @since    1.0.0
     */
    public function analytics_roles_callback()
    {
        $options = get_option('clm_settings');
        $selected_roles = isset($options['analytics_roles']) ? $options['analytics_roles'] : array('administrator', 'editor', 'clm_manager');

        $roles = $this->get_all_roles();

        echo '<fieldset>';

        foreach ($roles as $role_key => $role_name) {
            $checked = in_array($role_key, $selected_roles) ? 'checked="checked"' : '';

            echo '<label>';
            echo '<input type="checkbox" name="clm_settings[analytics_roles][]" value="' . esc_attr($role_key) . '" ' . $checked . '>';
            echo esc_html($role_name);
            echo '</label><br>';
        }

        echo '</fieldset>';
        echo '<p class="description">' . __('Select which user roles can view analytics.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Enable practice field callback
     *
     * @since    1.0.0
     */
    public function enable_practice_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['enable_practice']) ? $options['enable_practice'] : true;

        echo '<input type="checkbox" id="enable_practice" name="clm_settings[enable_practice]" value="1" ' . checked(1, $value, false) . '>';
        echo '<label for="enable_practice">' . __('Enable practice tracking features', 'choir-lyrics-manager') . '</label>';
    }

    /**
     * Practice notification field callback
     *
     * @since    1.0.0
     */
    public function practice_notification_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['practice_notification']) ? $options['practice_notification'] : 7;

        $intervals = array(
            0 => __('Disabled', 'choir-lyrics-manager'),
            3 => __('Every 3 days', 'choir-lyrics-manager'),
            7 => __('Weekly', 'choir-lyrics-manager'),
            14 => __('Bi-weekly', 'choir-lyrics-manager'),
            30 => __('Monthly', 'choir-lyrics-manager'),
        );

        echo '<select id="practice_notification" name="clm_settings[practice_notification]">';

        foreach ($intervals as $interval => $label) {
            echo '<option value="' . esc_attr($interval) . '" ' . selected($value, $interval, false) . '>' . esc_html($label) . '</option>';
        }

        echo '</select>';
        echo '<p class="description">' . __('Send practice reminder emails to users who haven\'t practiced recently.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Primary color field callback
     *
     * @since    1.0.0
     */
    public function primary_color_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['primary_color']) ? $options['primary_color'] : '#3498db';

        echo '<input type="color" id="primary_color" name="clm_settings[primary_color]" value="' . esc_attr($value) . '">';
        echo '<p class="description">' . __('Select primary color for plugin elements.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Secondary color field callback
     *
     * @since    1.0.0
     */
    public function secondary_color_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['secondary_color']) ? $options['secondary_color'] : '#2ecc71';

        echo '<input type="color" id="secondary_color" name="clm_settings[secondary_color]" value="' . esc_attr($value) . '">';
        echo '<p class="description">' . __('Select secondary color for plugin elements.', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Font size field callback
     *
     * @since    1.0.0
     */
    public function font_size_callback()
    {
        $options = get_option('clm_settings');
        $value = isset($options['font_size']) ? $options['font_size'] : 16;

        echo '<input type="number" id="font_size" name="clm_settings[font_size]" value="' . esc_attr($value) . '" class="small-text" min="12" max="24">';
        echo '<span class="description">px</span>';
        echo '<p class="description">' . __('Base font size for plugin elements (in pixels).', 'choir-lyrics-manager') . '</p>';
    }

    /**
     * Get all WordPress roles
     *
     * @since     1.0.0
     * @return    array    Array of role keys and names.
     */
    private function get_all_roles()
    {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $roles = array();

        foreach ($wp_roles->roles as $key => $role) {
            $roles[$key] = translate_user_role($role['name']);
        }

        return $roles;
    }

    /**
     * Generate CSS from settings
     *
     * @since     1.0.0
     * @return    string    CSS code.
     */
    public function get_custom_css()
    {
        $options = get_option('clm_settings');

        $primary_color = isset($options['primary_color']) ? $options['primary_color'] : '#3498db';
        $secondary_color = isset($options['secondary_color']) ? $options['secondary_color'] : '#2ecc71';
        $font_size = isset($options['font_size']) ? $options['font_size'] : 16;

        $css = "
        .clm-container {
            font-size: {$font_size}px;
        }
        
        .clm-button,
        .clm-pagination .current,
        .clm-tabs .active,
        .clm-practice-form button,
        .clm-playlist-dropdown-toggle,
        .clm-add-to-playlist {
            background-color: {$primary_color};
            border-color: {$primary_color};
        }
        
        .clm-button:hover,
        .clm-pagination a:hover,
        .clm-practice-form button:hover,
        .clm-playlist-dropdown-toggle:hover,
        .clm-add-to-playlist:hover {
            background-color: " . $this->adjust_brightness($primary_color, -20) . ";
            border-color: " . $this->adjust_brightness($primary_color, -20) . ";
        }
        
        .clm-heading,
        .clm-link,
        .clm-lyric-meta .clm-meta-label,
        .clm-practice-stat .clm-stat-label,
        .clm-playlist-name {
            color: {$primary_color};
        }
        
        .clm-practice-tracker,
        .clm-practice-suggestions,
        .clm-analytics-dashboard .clm-summary-box {
            border-color: {$primary_color};
        }
        
        .clm-practice-form button,
        .clm-suggestion-action,
        .clm-create-playlist-button,
        .clm-submit-playlist {
            background-color: {$secondary_color};
            border-color: {$secondary_color};
        }
        
        .clm-practice-form button:hover,
        .clm-suggestion-action:hover,
        .clm-create-playlist-button:hover,
        .clm-submit-playlist:hover {
            background-color: " . $this->adjust_brightness($secondary_color, -20) . ";
            border-color: " . $this->adjust_brightness($secondary_color, -20) . ";
        }
        
        .clm-confidence-stars .dashicons-star-filled {
            color: {$secondary_color};
        }
        ";

        return $css;
    }

    /**
     * Adjust color brightness
     *
     * @since     1.0.0
     * @param     string    $hex     Hex color code.
     * @param     int       $steps   Steps to adjust (negative for darker, positive for lighter).
     * @return    string             Adjusted hex color.
     */
    private function adjust_brightness($hex, $steps)
    {
        // Remove # if present
        $hex = ltrim($hex, '#');

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Adjust brightness
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        // Convert back to hex
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Get a specific setting
     *
     * @since     1.0.0
     * @param     string    $key          The setting key.
     * @param     mixed     $default      Default value if setting doesn't exist.
     * @return    mixed                   The setting value.
     */
    public function get_setting($key, $default = null)
    {
        $options = get_option($this->options_key); // Use the class property
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * Render settings page
     *
     * @since    1.0.0
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options'))
            return;
        ?>
        <div class="wrap clm-settings-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->options_key . '_group'); // Use the option group name
                do_settings_sections($this->settings_page_slug); // Use the page slug for settings page
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}