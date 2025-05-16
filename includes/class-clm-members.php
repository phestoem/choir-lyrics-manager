<?php
/**
 * Members functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Members {

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
     * Register the Member custom post type
     *
     * @since    1.0.0
     */
    public function register_member_cpt() {
        $labels = array(
            'name'                  => _x('Members', 'post type general name', 'choir-lyrics-manager'),
            'singular_name'         => _x('Member', 'post type singular name', 'choir-lyrics-manager'),
            'menu_name'             => _x('Members', 'admin menu', 'choir-lyrics-manager'),
            'name_admin_bar'        => _x('Member', 'add new on admin bar', 'choir-lyrics-manager'),
            'add_new'               => _x('Add New', 'member', 'choir-lyrics-manager'),
            'add_new_item'          => __('Add New Member', 'choir-lyrics-manager'),
            'new_item'              => __('New Member', 'choir-lyrics-manager'),
            'edit_item'             => __('Edit Member', 'choir-lyrics-manager'),
            'view_item'             => __('View Member', 'choir-lyrics-manager'),
            'all_items'             => __('All Members', 'choir-lyrics-manager'),
            'search_items'          => __('Search Members', 'choir-lyrics-manager'),
            'parent_item_colon'     => __('Parent Members:', 'choir-lyrics-manager'),
            'not_found'             => __('No members found.', 'choir-lyrics-manager'),
            'not_found_in_trash'    => __('No members found in Trash.', 'choir-lyrics-manager')
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Choir members and their information', 'choir-lyrics-manager'),
            'public'                => false,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => false,
            'show_in_admin_bar'     => true,
            'menu_position'         => 26,
            'menu_icon'             => 'dashicons-groups',
            'capability_type'       => 'page',
            'hierarchical'          => false,
            'supports'              => array('title', 'thumbnail'),
            'has_archive'           => false,
            'rewrite'               => false,
            'query_var'             => false,
            'show_in_rest'          => false,
        );

        register_post_type('clm_member', $args);
    }

    /**
     * Register the Voice Type taxonomy
     *
     * @since    1.0.0
     */
    public function register_voice_type_taxonomy() {
        $labels = array(
            'name'                       => _x('Voice Types', 'taxonomy general name', 'choir-lyrics-manager'),
            'singular_name'              => _x('Voice Type', 'taxonomy singular name', 'choir-lyrics-manager'),
            'search_items'               => __('Search Voice Types', 'choir-lyrics-manager'),
            'popular_items'              => __('Popular Voice Types', 'choir-lyrics-manager'),
            'all_items'                  => __('All Voice Types', 'choir-lyrics-manager'),
            'parent_item'                => __('Parent Voice Type', 'choir-lyrics-manager'),
            'parent_item_colon'          => __('Parent Voice Type:', 'choir-lyrics-manager'),
            'edit_item'                  => __('Edit Voice Type', 'choir-lyrics-manager'),
            'update_item'                => __('Update Voice Type', 'choir-lyrics-manager'),
            'add_new_item'               => __('Add New Voice Type', 'choir-lyrics-manager'),
            'new_item_name'              => __('New Voice Type Name', 'choir-lyrics-manager'),
            'separate_items_with_commas' => __('Separate voice types with commas', 'choir-lyrics-manager'),
            'add_or_remove_items'        => __('Add or remove voice types', 'choir-lyrics-manager'),
            'choose_from_most_used'      => __('Choose from the most used voice types', 'choir-lyrics-manager'),
            'not_found'                  => __('No voice types found.', 'choir-lyrics-manager'),
            'menu_name'                  => __('Voice Types', 'choir-lyrics-manager'),
        );

        $args = array(
            'labels'                => $labels,
            'hierarchical'          => true,
            'public'                => false,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => false,
            'show_tagcloud'         => false,
            'query_var'             => false,
            'rewrite'               => false,
            'show_in_rest'          => false,
        );

        register_taxonomy('clm_voice_type', array('clm_member'), $args);
    }

    /**
     * Register meta boxes for member details
     *
     * @since    1.0.0
     */
    public function register_member_meta_boxes() {
        add_meta_box(
            'clm_member_details',
            __('Member Details', 'choir-lyrics-manager'),
            array($this, 'render_member_details_meta_box'),
            'clm_member',
            'normal',
            'high'
        );

        add_meta_box(
            'clm_member_contact',
            __('Contact Information', 'choir-lyrics-manager'),
            array($this, 'render_member_contact_meta_box'),
            'clm_member',
            'normal',
            'high'
        );

        add_meta_box(
            'clm_member_voice',
            __('Voice Information', 'choir-lyrics-manager'),
            array($this, 'render_member_voice_meta_box'),
            'clm_member',
            'normal',
            'high'
        );
    }

    /**
     * Render member details meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_member_details_meta_box($post) {
        wp_nonce_field('clm_member_details_meta_box', 'clm_member_details_meta_box_nonce');

        $wp_user_id = get_post_meta($post->ID, '_clm_wp_user_id', true);
        $member_since = get_post_meta($post->ID, '_clm_member_since', true);
        $member_status = get_post_meta($post->ID, '_clm_member_status', true);
        ?>
        <div class="clm-meta-box-container">
            <div class="clm-meta-field">
                <label for="clm_wp_user_id"><?php _e('Associated WordPress User', 'choir-lyrics-manager'); ?></label>
                <select id="clm_wp_user_id" name="clm_wp_user_id">
                    <option value=""><?php _e('No associated user', 'choir-lyrics-manager'); ?></option>
                    <?php
                    $users = get_users(array('orderby' => 'display_name'));
                    foreach ($users as $user) {
                        ?>
                        <option value="<?php echo $user->ID; ?>" <?php selected($wp_user_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Link this member to a WordPress user account.', 'choir-lyrics-manager'); ?></p>
            </div>

            <div class="clm-meta-field">
                <label for="clm_member_since"><?php _e('Member Since', 'choir-lyrics-manager'); ?></label>
                <input type="date" id="clm_member_since" name="clm_member_since" value="<?php echo esc_attr($member_since); ?>" class="regular-text">
            </div>

            <div class="clm-meta-field">
                <label for="clm_member_status"><?php _e('Member Status', 'choir-lyrics-manager'); ?></label>
                <select id="clm_member_status" name="clm_member_status">
                    <option value="active" <?php selected($member_status, 'active'); ?>><?php _e('Active', 'choir-lyrics-manager'); ?></option>
                    <option value="inactive" <?php selected($member_status, 'inactive'); ?>><?php _e('Inactive', 'choir-lyrics-manager'); ?></option>
                    <option value="on_leave" <?php selected($member_status, 'on_leave'); ?>><?php _e('On Leave', 'choir-lyrics-manager'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }

    /**
     * Render member contact meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_member_contact_meta_box($post) {
        $email = get_post_meta($post->ID, '_clm_member_email', true);
        $phone = get_post_meta($post->ID, '_clm_member_phone', true);
        $address = get_post_meta($post->ID, '_clm_member_address', true);
        $emergency_contact = get_post_meta($post->ID, '_clm_emergency_contact', true);
        $emergency_phone = get_post_meta($post->ID, '_clm_emergency_phone', true);
        ?>
        <div class="clm-meta-box-container">
            <div class="clm-meta-field">
                <label for="clm_member_email"><?php _e('Email', 'choir-lyrics-manager'); ?></label>
                <input type="email" id="clm_member_email" name="clm_member_email" value="<?php echo esc_attr($email); ?>" class="regular-text">
            </div>

            <div class="clm-meta-field">
                <label for="clm_member_phone"><?php _e('Phone', 'choir-lyrics-manager'); ?></label>
                <input type="tel" id="clm_member_phone" name="clm_member_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
            </div>

            <div class="clm-meta-field">
                <label for="clm_member_address"><?php _e('Address', 'choir-lyrics-manager'); ?></label>
                <textarea id="clm_member_address" name="clm_member_address" rows="3" class="large-text"><?php echo esc_textarea($address); ?></textarea>
            </div>

            <div class="clm-meta-field">
                <label for="clm_emergency_contact"><?php _e('Emergency Contact Name', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_emergency_contact" name="clm_emergency_contact" value="<?php echo esc_attr($emergency_contact); ?>" class="regular-text">
            </div>

            <div class="clm-meta-field">
                <label for="clm_emergency_phone"><?php _e('Emergency Contact Phone', 'choir-lyrics-manager'); ?></label>
                <input type="tel" id="clm_emergency_phone" name="clm_emergency_phone" value="<?php echo esc_attr($emergency_phone); ?>" class="regular-text">
            </div>
        </div>
        <?php
    }

    /**
     * Render member voice meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_member_voice_meta_box($post) {
        $voice_sub_type = get_post_meta($post->ID, '_clm_voice_sub_type', true);
        $voice_range_low = get_post_meta($post->ID, '_clm_voice_range_low', true);
        $voice_range_high = get_post_meta($post->ID, '_clm_voice_range_high', true);
        $sight_reading = get_post_meta($post->ID, '_clm_sight_reading', true);
        $instruments = get_post_meta($post->ID, '_clm_instruments', true);
        ?>
        <div class="clm-meta-box-container">
            <div class="clm-meta-field">
                <label for="clm_voice_sub_type"><?php _e('Voice Sub-classification', 'choir-lyrics-manager'); ?></label>
                <select id="clm_voice_sub_type" name="clm_voice_sub_type">
                    <option value=""><?php _e('Select sub-classification', 'choir-lyrics-manager'); ?></option>
                    <optgroup label="<?php _e('Soprano', 'choir-lyrics-manager'); ?>">
                        <option value="soprano1" <?php selected($voice_sub_type, 'soprano1'); ?>><?php _e('Soprano 1', 'choir-lyrics-manager'); ?></option>
                        <option value="soprano2" <?php selected($voice_sub_type, 'soprano2'); ?>><?php _e('Soprano 2', 'choir-lyrics-manager'); ?></option>
                    </optgroup>
                    <optgroup label="<?php _e('Alto', 'choir-lyrics-manager'); ?>">
                        <option value="alto1" <?php selected($voice_sub_type, 'alto1'); ?>><?php _e('Alto 1', 'choir-lyrics-manager'); ?></option>
                        <option value="alto2" <?php selected($voice_sub_type, 'alto2'); ?>><?php _e('Alto 2', 'choir-lyrics-manager'); ?></option>
                    </optgroup>
                    <optgroup label="<?php _e('Tenor', 'choir-lyrics-manager'); ?>">
                        <option value="tenor1" <?php selected($voice_sub_type, 'tenor1'); ?>><?php _e('Tenor 1', 'choir-lyrics-manager'); ?></option>
                        <option value="tenor2" <?php selected($voice_sub_type, 'tenor2'); ?>><?php _e('Tenor 2', 'choir-lyrics-manager'); ?></option>
                    </optgroup>
                    <optgroup label="<?php _e('Bass', 'choir-lyrics-manager'); ?>">
                        <option value="bass1" <?php selected($voice_sub_type, 'bass1'); ?>><?php _e('Bass 1', 'choir-lyrics-manager'); ?></option>
                        <option value="bass2" <?php selected($voice_sub_type, 'bass2'); ?>><?php _e('Bass 2', 'choir-lyrics-manager'); ?></option>
                    </optgroup>
                </select>
            </div>

            <div class="clm-meta-field">
                <label><?php _e('Voice Range', 'choir-lyrics-manager'); ?></label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="clm_voice_range_low" name="clm_voice_range_low" value="<?php echo esc_attr($voice_range_low); ?>" class="small-text" placeholder="<?php _e('Low (e.g., C3)', 'choir-lyrics-manager'); ?>">
                    <span><?php _e('to', 'choir-lyrics-manager'); ?></span>
                    <input type="text" id="clm_voice_range_high" name="clm_voice_range_high" value="<?php echo esc_attr($voice_range_high); ?>" class="small-text" placeholder="<?php _e('High (e.g., C5)', 'choir-lyrics-manager'); ?>">
                </div>
            </div>

            <div class="clm-meta-field">
                <label for="clm_sight_reading"><?php _e('Sight Reading Ability', 'choir-lyrics-manager'); ?></label>
                <select id="clm_sight_reading" name="clm_sight_reading">
                    <option value="none" <?php selected($sight_reading, 'none'); ?>><?php _e('None', 'choir-lyrics-manager'); ?></option>
                    <option value="basic" <?php selected($sight_reading, 'basic'); ?>><?php _e('Basic', 'choir-lyrics-manager'); ?></option>
                    <option value="intermediate" <?php selected($sight_reading, 'intermediate'); ?>><?php _e('Intermediate', 'choir-lyrics-manager'); ?></option>
                    <option value="advanced" <?php selected($sight_reading, 'advanced'); ?>><?php _e('Advanced', 'choir-lyrics-manager'); ?></option>
                </select>
            </div>

            <div class="clm-meta-field">
                <label for="clm_instruments"><?php _e('Instruments (comma-separated)', 'choir-lyrics-manager'); ?></label>
                <input type="text" id="clm_instruments" name="clm_instruments" value="<?php echo esc_attr($instruments); ?>" class="large-text" placeholder="<?php _e('e.g., Piano, Guitar, Violin', 'choir-lyrics-manager'); ?>">
                <p class="description"><?php _e('List any instruments this member can play.', 'choir-lyrics-manager'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Save member meta box data
     *
     * @since    1.0.0
     * @param    int        $post_id    The post ID.
     */
    public function save_member_meta($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['clm_member_details_meta_box_nonce'])) {
            return;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['clm_member_details_meta_box_nonce'], 'clm_member_details_meta_box')) {
            return;
        }

        // If this is an autosave, our form has not been submitted
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (isset($_POST['post_type']) && 'clm_member' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        }

        // Save member details
        if (isset($_POST['clm_wp_user_id'])) {
            update_post_meta($post_id, '_clm_wp_user_id', intval($_POST['clm_wp_user_id']));
        }

        if (isset($_POST['clm_member_since'])) {
            update_post_meta($post_id, '_clm_member_since', sanitize_text_field($_POST['clm_member_since']));
        }

        if (isset($_POST['clm_member_status'])) {
            update_post_meta($post_id, '_clm_member_status', sanitize_text_field($_POST['clm_member_status']));
        }

        // Save contact information
        if (isset($_POST['clm_member_email'])) {
            update_post_meta($post_id, '_clm_member_email', sanitize_email($_POST['clm_member_email']));
        }

        if (isset($_POST['clm_member_phone'])) {
            update_post_meta($post_id, '_clm_member_phone', sanitize_text_field($_POST['clm_member_phone']));
        }

        if (isset($_POST['clm_member_address'])) {
            update_post_meta($post_id, '_clm_member_address', sanitize_textarea_field($_POST['clm_member_address']));
        }

        if (isset($_POST['clm_emergency_contact'])) {
            update_post_meta($post_id, '_clm_emergency_contact', sanitize_text_field($_POST['clm_emergency_contact']));
        }

        if (isset($_POST['clm_emergency_phone'])) {
            update_post_meta($post_id, '_clm_emergency_phone', sanitize_text_field($_POST['clm_emergency_phone']));
        }

        // Save voice information
        if (isset($_POST['clm_voice_sub_type'])) {
            update_post_meta($post_id, '_clm_voice_sub_type', sanitize_text_field($_POST['clm_voice_sub_type']));
        }

        if (isset($_POST['clm_voice_range_low'])) {
            update_post_meta($post_id, '_clm_voice_range_low', sanitize_text_field($_POST['clm_voice_range_low']));
        }

        if (isset($_POST['clm_voice_range_high'])) {
            update_post_meta($post_id, '_clm_voice_range_high', sanitize_text_field($_POST['clm_voice_range_high']));
        }

        if (isset($_POST['clm_sight_reading'])) {
            update_post_meta($post_id, '_clm_sight_reading', sanitize_text_field($_POST['clm_sight_reading']));
        }

        if (isset($_POST['clm_instruments'])) {
            update_post_meta($post_id, '_clm_instruments', sanitize_text_field($_POST['clm_instruments']));
        }
    }

    /**
     * Define custom columns for members list
     *
     * @since    1.0.0
     * @param    array    $columns    The default columns.
     * @return   array                Modified columns.
     */
    public function set_custom_member_columns($columns) {
        $columns = array(
            'cb' => $columns['cb'],
            'title' => __('Name', 'choir-lyrics-manager'),
            'voice_type' => __('Voice Type', 'choir-lyrics-manager'),
            'sub_type' => __('Sub-type', 'choir-lyrics-manager'),
            'email' => __('Email', 'choir-lyrics-manager'),
            'phone' => __('Phone', 'choir-lyrics-manager'),
            'status' => __('Status', 'choir-lyrics-manager'),
            'member_since' => __('Member Since', 'choir-lyrics-manager'),
        );

        return $columns;
    }

    /**
     * Display custom column content for members
     *
     * @since    1.0.0
     * @param    string    $column     The column name.
     * @param    int       $post_id    The post ID.
     */
    public function custom_member_column($column, $post_id) {
        switch ($column) {
            case 'voice_type':
                $terms = get_the_terms($post_id, 'clm_voice_type');
                if ($terms && !is_wp_error($terms)) {
                    $voice_types = array();
                    foreach ($terms as $term) {
                        $voice_types[] = $term->name;
                    }
                    echo implode(', ', $voice_types);
                }
                break;

            case 'sub_type':
                $sub_type = get_post_meta($post_id, '_clm_voice_sub_type', true);
                if ($sub_type) {
                    $sub_types = array(
                        'soprano1' => __('Soprano 1', 'choir-lyrics-manager'),
                        'soprano2' => __('Soprano 2', 'choir-lyrics-manager'),
                        'alto1' => __('Alto 1', 'choir-lyrics-manager'),
                        'alto2' => __('Alto 2', 'choir-lyrics-manager'),
                        'tenor1' => __('Tenor 1', 'choir-lyrics-manager'),
                        'tenor2' => __('Tenor 2', 'choir-lyrics-manager'),
                        'bass1' => __('Bass 1', 'choir-lyrics-manager'),
                        'bass2' => __('Bass 2', 'choir-lyrics-manager'),
                    );
                    echo isset($sub_types[$sub_type]) ? $sub_types[$sub_type] : $sub_type;
                }
                break;

            case 'email':
                echo get_post_meta($post_id, '_clm_member_email', true);
                break;

            case 'phone':
                echo get_post_meta($post_id, '_clm_member_phone', true);
                break;

            case 'status':
                $status = get_post_meta($post_id, '_clm_member_status', true);
                $status_labels = array(
                    'active' => __('Active', 'choir-lyrics-manager'),
                    'inactive' => __('Inactive', 'choir-lyrics-manager'),
                    'on_leave' => __('On Leave', 'choir-lyrics-manager'),
                );
                $status_label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                $status_class = 'clm-status-' . $status;
                echo '<span class="' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';
                break;

            case 'member_since':
                $date = get_post_meta($post_id, '_clm_member_since', true);
                if ($date) {
                    echo date_i18n(get_option('date_format'), strtotime($date));
                }
                break;
        }
    }

    /**
     * Get member by WordPress user ID
     *
     * @since    1.0.0
     * @param    int       $user_id    The WordPress user ID.
     * @return   WP_Post|null            The member post object or null.
     */
    public function get_member_by_user_id($user_id) {
        $args = array(
            'post_type' => 'clm_member',
            'meta_key' => '_clm_wp_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'post_status' => 'any',
        );

        $members = get_posts($args);

        return !empty($members) ? $members[0] : null;
    }

    /**
     * Get active members by voice type
     *
     * @since    1.0.0
     * @param    string    $voice_type    The voice type slug.
     * @return   array                    Array of member posts.
     */
    public function get_active_members_by_voice_type($voice_type) {
        $args = array(
            'post_type' => 'clm_member',
            'post_status' => 'publish',
            'meta_key' => '_clm_member_status',
            'meta_value' => 'active',
            'tax_query' => array(
                array(
                    'taxonomy' => 'clm_voice_type',
                    'field' => 'slug',
                    'terms' => $voice_type,
                ),
            ),
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        return get_posts($args);
    }

    /**
     * Create default voice types on activation
     *
     * @since    1.0.0
     */
    public static function create_default_voice_types() {
        $voice_types = array(
            'soprano' => __('Soprano', 'choir-lyrics-manager'),
            'alto' => __('Alto', 'choir-lyrics-manager'),
            'tenor' => __('Tenor', 'choir-lyrics-manager'),
            'bass' => __('Bass', 'choir-lyrics-manager'),
        );

        foreach ($voice_types as $slug => $name) {
            if (!term_exists($slug, 'clm_voice_type')) {
                wp_insert_term($name, 'clm_voice_type', array('slug' => $slug));
            }
        }
    }

    /**
     * Add member capabilities
     *
     * @since    1.0.0
     */
    public static function add_capabilities() {
        $capabilities = array(
            'edit_clm_member',
            'read_clm_member',
            'delete_clm_member',
            'edit_clm_members',
            'edit_others_clm_members',
            'publish_clm_members',
            'read_private_clm_members',
            'delete_clm_members',
            'delete_private_clm_members',
            'delete_published_clm_members',
            'delete_others_clm_members',
            'edit_private_clm_members',
            'edit_published_clm_members',
        );

        $roles = array('administrator', 'editor');

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->add_cap($cap);
                }
            }
        }

        // Add limited capabilities for authors
        $author_role = get_role('author');
        if ($author_role) {
            $author_role->add_cap('edit_clm_member');
            $author_role->add_cap('read_clm_member');
            $author_role->add_cap('delete_clm_member');
            $author_role->add_cap('edit_clm_members');
            $author_role->add_cap('publish_clm_members');
        }
    }

    /**
     * Remove member capabilities
     *
     * @since    1.0.0
     */
    public static function remove_capabilities() {
        $capabilities = array(
            'edit_clm_member',
            'read_clm_member',
            'delete_clm_member',
            'edit_clm_members',
            'edit_others_clm_members',
            'publish_clm_members',
            'read_private_clm_members',
            'delete_clm_members',
            'delete_private_clm_members',
            'delete_published_clm_members',
            'delete_others_clm_members',
            'edit_private_clm_members',
            'edit_published_clm_members',
        );

        $roles = array('administrator', 'editor', 'author');

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }

    /**
     * Add custom CSS for member admin
     *
     * @since    1.0.0
     */
    public function add_member_admin_styles() {
        global $post_type;
        
        if ('clm_member' === $post_type) {
            ?>
            <style>
                .clm-status-active { color: #46b450; font-weight: bold; }
                .clm-status-inactive { color: #dc3232; }
                .clm-status-on_leave { color: #f56e28; }
                .column-voice_type { width: 100px; }
                .column-sub_type { width: 100px; }
                .column-status { width: 80px; }
                .column-member_since { width: 100px; }
            </style>
            <?php
        }
    }
}