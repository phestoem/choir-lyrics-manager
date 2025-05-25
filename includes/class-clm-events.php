<?php
/**
 * Events/Performance Management functionality
 *
 * @package    Choir_Lyrics_Manager
 * @subpackage Choir_Lyrics_Manager/includes
 */

class CLM_Events {

    /**
     * The ID of this plugin.
     *
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the Event post type.
     */
    public function register_event_post_type() {
        $labels = array(
            'name'                  => _x('Events', 'Post Type General Name', 'choir-lyrics-manager'),
            'singular_name'         => _x('Event', 'Post Type Singular Name', 'choir-lyrics-manager'),
            'menu_name'             => __('Events', 'choir-lyrics-manager'),
            'name_admin_bar'        => __('Event', 'choir-lyrics-manager'),
            'archives'              => __('Event Archives', 'choir-lyrics-manager'),
            'attributes'            => __('Event Attributes', 'choir-lyrics-manager'),
            'parent_item_colon'     => __('Parent Event:', 'choir-lyrics-manager'),
            'all_items'             => __('All Events', 'choir-lyrics-manager'),
            'add_new_item'          => __('Add New Event', 'choir-lyrics-manager'),
            'add_new'               => __('Add New', 'choir-lyrics-manager'),
            'new_item'              => __('New Event', 'choir-lyrics-manager'),
            'edit_item'             => __('Edit Event', 'choir-lyrics-manager'),
            'update_item'           => __('Update Event', 'choir-lyrics-manager'),
            'view_item'             => __('View Event', 'choir-lyrics-manager'),
            'view_items'            => __('View Events', 'choir-lyrics-manager'),
            'search_items'          => __('Search Event', 'choir-lyrics-manager'),
            'not_found'             => __('Not found', 'choir-lyrics-manager'),
            'not_found_in_trash'    => __('Not found in Trash', 'choir-lyrics-manager'),
            'featured_image'        => __('Event Image', 'choir-lyrics-manager'),
            'set_featured_image'    => __('Set event image', 'choir-lyrics-manager'),
            'remove_featured_image' => __('Remove event image', 'choir-lyrics-manager'),
            'use_featured_image'    => __('Use as event image', 'choir-lyrics-manager'),
            'insert_into_item'      => __('Insert into event', 'choir-lyrics-manager'),
            'uploaded_to_this_item' => __('Uploaded to this event', 'choir-lyrics-manager'),
            'items_list'            => __('Events list', 'choir-lyrics-manager'),
            'items_list_navigation' => __('Events list navigation', 'choir-lyrics-manager'),
            'filter_items_list'     => __('Filter events list', 'choir-lyrics-manager'),
        );
        
        $args = array(
            'label'                 => __('Event', 'choir-lyrics-manager'),
            'description'           => __('Choir events and performances', 'choir-lyrics-manager'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'revisions'),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'clm_dashboard',
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-calendar-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'rewrite'               => array('slug' => 'choir-events'),
        );
        
        register_post_type('clm_event', $args);
    }

    /**
     * Register Event Type taxonomy.
     */
    public function register_event_taxonomies() {
        // Event Type taxonomy
        $labels = array(
            'name'                       => _x('Event Types', 'Taxonomy General Name', 'choir-lyrics-manager'),
            'singular_name'              => _x('Event Type', 'Taxonomy Singular Name', 'choir-lyrics-manager'),
            'menu_name'                  => __('Event Types', 'choir-lyrics-manager'),
            'all_items'                  => __('All Event Types', 'choir-lyrics-manager'),
            'parent_item'                => __('Parent Event Type', 'choir-lyrics-manager'),
            'parent_item_colon'          => __('Parent Event Type:', 'choir-lyrics-manager'),
            'new_item_name'              => __('New Event Type Name', 'choir-lyrics-manager'),
            'add_new_item'               => __('Add New Event Type', 'choir-lyrics-manager'),
            'edit_item'                  => __('Edit Event Type', 'choir-lyrics-manager'),
            'update_item'                => __('Update Event Type', 'choir-lyrics-manager'),
            'view_item'                  => __('View Event Type', 'choir-lyrics-manager'),
            'separate_items_with_commas' => __('Separate event types with commas', 'choir-lyrics-manager'),
            'add_or_remove_items'        => __('Add or remove event types', 'choir-lyrics-manager'),
            'choose_from_most_used'      => __('Choose from the most used', 'choir-lyrics-manager'),
            'popular_items'              => __('Popular Event Types', 'choir-lyrics-manager'),
            'search_items'               => __('Search Event Types', 'choir-lyrics-manager'),
            'not_found'                  => __('Not Found', 'choir-lyrics-manager'),
            'no_terms'                   => __('No event types', 'choir-lyrics-manager'),
            'items_list'                 => __('Event types list', 'choir-lyrics-manager'),
            'items_list_navigation'      => __('Event types list navigation', 'choir-lyrics-manager'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'rewrite'                    => array('slug' => 'event-type'),
        );
        
        register_taxonomy('clm_event_type', array('clm_event'), $args);

        // Venue taxonomy
        $labels = array(
            'name'                       => _x('Venues', 'Taxonomy General Name', 'choir-lyrics-manager'),
            'singular_name'              => _x('Venue', 'Taxonomy Singular Name', 'choir-lyrics-manager'),
            'menu_name'                  => __('Venues', 'choir-lyrics-manager'),
            'all_items'                  => __('All Venues', 'choir-lyrics-manager'),
            'new_item_name'              => __('New Venue Name', 'choir-lyrics-manager'),
            'add_new_item'               => __('Add New Venue', 'choir-lyrics-manager'),
            'edit_item'                  => __('Edit Venue', 'choir-lyrics-manager'),
            'update_item'                => __('Update Venue', 'choir-lyrics-manager'),
            'view_item'                  => __('View Venue', 'choir-lyrics-manager'),
            'search_items'               => __('Search Venues', 'choir-lyrics-manager'),
            'not_found'                  => __('Not Found', 'choir-lyrics-manager'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'rewrite'                    => array('slug' => 'venue'),
        );
        
        register_taxonomy('clm_venue', array('clm_event'), $args);
    }

    /**
     * Add meta boxes to event post type.
     */
    public function add_event_meta_boxes() {
        add_meta_box(
            'clm_event_details',
            __('Event Details', 'choir-lyrics-manager'),
            array($this, 'render_event_details_meta_box'),
            'clm_event',
            'normal',
            'high'
        );

        add_meta_box(
            'clm_event_setlist',
            __('Event Setlist', 'choir-lyrics-manager'),
            array($this, 'render_event_setlist_meta_box'),
            'clm_event',
            'normal',
            'high'
        );

        add_meta_box(
            'clm_event_attendance',
            __('Attendance', 'choir-lyrics-manager'),
            array($this, 'render_event_attendance_meta_box'),
            'clm_event',
            'side',
            'default'
        );
    }


// Add this method to check if a feature is enabled
public function is_feature_enabled($feature) {
    $settings = get_option('clm_settings');
    $display_mode = isset($settings['event_display_mode']) ? $settings['event_display_mode'] : 'comprehensive';
    
    // For comprehensive mode, all features are enabled
    if ($display_mode === 'comprehensive') {
        return true;
    }
    
    // For simple mode, only location is enabled
    if ($display_mode === 'simple') {
        return $feature === 'location';
    }
    
    // For standard mode, check specific features
    if ($display_mode === 'standard') {
        $standard_features = array('setlist', 'location', 'tickets');
        return in_array($feature, $standard_features);
    }
    
    // For custom mode, check the settings
    if ($display_mode === 'custom') {
        return isset($settings['event_features'][$feature]) && $settings['event_features'][$feature] === 'on';
    }
    
    return false;
}

// Add this method to check if a field is enabled
public function is_field_enabled($field) {
    $settings = get_option('clm_settings');
    
    if (!isset($settings['event_fields'][$field]['enabled'])) {
        return true; // Default to enabled if not set
    }
    
    return $settings['event_fields'][$field]['enabled'] === 'on';
}

// Add this method to check if a field is public
public function is_field_public($field) {
    $settings = get_option('clm_settings');
    
    if (!isset($settings['event_fields'][$field]['public'])) {
        return true; // Default to public if not set
    }
    
    return $settings['event_fields'][$field]['public'] === 'on';
}


    /**
     * Render event details meta box.
     */
    public function render_event_details_meta_box($post) {
        wp_nonce_field('clm_save_event_details', 'clm_event_details_nonce');
        
        $event_date = get_post_meta($post->ID, '_clm_event_date', true);
        $event_time = get_post_meta($post->ID, '_clm_event_time', true);
        $event_end_time = get_post_meta($post->ID, '_clm_event_end_time', true);
        $event_location = get_post_meta($post->ID, '_clm_event_location', true);
        $event_director = get_post_meta($post->ID, '_clm_event_director', true);
        $event_accompanist = get_post_meta($post->ID, '_clm_event_accompanist', true);
        $ticket_price = get_post_meta($post->ID, '_clm_event_ticket_price', true);
        $ticket_link = get_post_meta($post->ID, '_clm_event_ticket_link', true);
        ?>
        
        <div class="clm-meta-box-container">
            <div class="clm-meta-field">
                <label for="clm_event_date"><?php _e('Event Date', 'choir-lyrics-manager'); ?></label>
                <input type="date" id="clm_event_date" name="clm_event_date" value="<?php echo esc_attr($event_date); ?>" required />
            </div>
            
            <div class="clm-meta-field-group">
                <div class="clm-meta-field">
                    <label for="clm_event_time"><?php _e('Start Time', 'choir-lyrics-manager'); ?></label>
                    <input type="time" id="clm_event_time" name="clm_event_time" value="<?php echo esc_attr($event_time); ?>" />
                </div>
                
                <div class="clm-meta-field">
                    <label for="clm_event_end_time"><?php _e('End Time', 'choir-lyrics-manager'); ?></label>
                    <input type="time" id="clm_event_end_time" name="clm_event_end_time" value="<?php echo esc_attr($event_end_time); ?>" />
                </div>
            </div>
            
            <div class="clm-meta-field">
                <label for="clm_event_location"><?php _e('Location/Address', 'choir-lyrics-manager'); ?></label>
                <textarea id="clm_event_location" name="clm_event_location" rows="3"><?php echo esc_textarea($event_location); ?></textarea>
            </div>
            
            <div class="clm-meta-field-group">
                <div class="clm-meta-field">
                    <label for="clm_event_director"><?php _e('Director/Conductor', 'choir-lyrics-manager'); ?></label>
                    <input type="text" id="clm_event_director" name="clm_event_director" value="<?php echo esc_attr($event_director); ?>" />
                </div>
                
                <div class="clm-meta-field">
                    <label for="clm_event_accompanist"><?php _e('Accompanist', 'choir-lyrics-manager'); ?></label>
                    <input type="text" id="clm_event_accompanist" name="clm_event_accompanist" value="<?php echo esc_attr($event_accompanist); ?>" />
                </div>
            </div>
            
            <div class="clm-meta-field-group">
                <div class="clm-meta-field">
                    <label for="clm_event_ticket_price"><?php _e('Ticket Price', 'choir-lyrics-manager'); ?></label>
                    <input type="text" id="clm_event_ticket_price" name="clm_event_ticket_price" value="<?php echo esc_attr($ticket_price); ?>" placeholder="<?php _e('e.g., $10, Free', 'choir-lyrics-manager'); ?>" />
                </div>
                
                <div class="clm-meta-field">
                    <label for="clm_event_ticket_link"><?php _e('Ticket Link', 'choir-lyrics-manager'); ?></label>
                    <input type="url" id="clm_event_ticket_link" name="clm_event_ticket_link" value="<?php echo esc_url($ticket_link); ?>" placeholder="https://" />
                </div>
            </div>
        </div>
        
        <style>
        .clm-meta-field-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .clm-meta-field {
            margin-bottom: 15px;
        }
        .clm-meta-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .clm-meta-field input[type="text"],
        .clm-meta-field input[type="date"],
        .clm-meta-field input[type="time"],
        .clm-meta-field input[type="url"],
        .clm-meta-field textarea {
            width: 100%;
        }
        </style>
        <?php
    }

    /**
     * Render event setlist meta box.
     */
    public function render_event_setlist_meta_box($post) {
        wp_nonce_field('clm_save_event_setlist', 'clm_event_setlist_nonce');
        
        $setlist = get_post_meta($post->ID, '_clm_event_setlist', true);
        if (!is_array($setlist)) {
            $setlist = array();
        }
        
        // Get all available lyrics
        $lyrics = get_posts(array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        ?>
        
        <div class="clm-setlist-container">
            <div class="clm-setlist-available">
                <h4><?php _e('Available Lyrics', 'choir-lyrics-manager'); ?></h4>
                <input type="text" id="clm-lyrics-search" placeholder="<?php _e('Search lyrics...', 'choir-lyrics-manager'); ?>" />
                <ul id="clm-available-lyrics">
                    <?php foreach ($lyrics as $lyric): ?>
                        <?php if (!in_array($lyric->ID, $setlist)): ?>
                            <li data-lyric-id="<?php echo $lyric->ID; ?>" data-title="<?php echo esc_attr(strtolower($lyric->post_title)); ?>">
                                <span class="lyric-title"><?php echo esc_html($lyric->post_title); ?></span>
                                <?php
                                $composer = get_post_meta($lyric->ID, '_clm_composer', true);
                                if ($composer) {
                                    echo '<span class="lyric-composer"> - ' . esc_html($composer) . '</span>';
                                }
                                ?>
                                <button type="button" class="clm-add-to-setlist button button-small"><?php _e('Add', 'choir-lyrics-manager'); ?></button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="clm-setlist-selected">
                <h4><?php _e('Event Setlist', 'choir-lyrics-manager'); ?></h4>
                <ul id="clm-event-setlist" class="clm-sortable">
                    <?php foreach ($setlist as $index => $lyric_id): ?>
                        <?php $lyric = get_post($lyric_id); ?>
                        <?php if ($lyric): ?>
                            <li data-lyric-id="<?php echo $lyric_id; ?>">
                                <span class="clm-handle">☰</span>
                                <span class="lyric-number"><?php echo $index + 1; ?>.</span>
                                <span class="lyric-title"><?php echo esc_html($lyric->post_title); ?></span>
                                <input type="hidden" name="clm_event_setlist[]" value="<?php echo $lyric_id; ?>" />
                                <button type="button" class="clm-remove-from-setlist button button-small"><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <p class="description"><?php _e('Drag and drop to reorder the setlist.', 'choir-lyrics-manager'); ?></p>
            </div>
        </div>
        
        <style>
        .clm-setlist-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .clm-setlist-available,
        .clm-setlist-selected {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .clm-setlist-available ul,
        .clm-setlist-selected ul {
            max-height: 400px;
            overflow-y: auto;
            margin: 10px 0;
            padding: 0;
        }
        .clm-setlist-available li,
        .clm-setlist-selected li {
            padding: 8px;
            margin: 5px 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .clm-setlist-selected li {
            cursor: move;
        }
        .clm-handle {
            margin-right: 10px;
            cursor: grab;
        }
        .lyric-number {
            margin-right: 10px;
            font-weight: bold;
        }
        .lyric-title {
            flex-grow: 1;
        }
        .lyric-composer {
            color: #666;
            font-style: italic;
        }
        #clm-lyrics-search {
            width: 100%;
            margin-bottom: 10px;
        }
        .clm-sortable-placeholder {
            height: 40px;
            background: #f0f0f0;
            border: 2px dashed #ccc;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Make setlist sortable
            $('#clm-event-setlist').sortable({
                handle: '.clm-handle',
                placeholder: 'clm-sortable-placeholder',
                update: function(event, ui) {
                    // Update numbering
                    $('#clm-event-setlist li').each(function(index) {
                        $(this).find('.lyric-number').text((index + 1) + '.');
                    });
                }
            });
            
            // Search functionality
            $('#clm-lyrics-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('#clm-available-lyrics li').each(function() {
                    var title = $(this).data('title');
                    if (title.indexOf(searchTerm) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Add to setlist
            $(document).on('click', '.clm-add-to-setlist', function() {
                var $li = $(this).closest('li');
                var lyricId = $li.data('lyric-id');
                var title = $li.find('.lyric-title').text();
                var composer = $li.find('.lyric-composer').text();
                
                var newIndex = $('#clm-event-setlist li').length + 1;
                var $newLi = $('<li data-lyric-id="' + lyricId + '">' +
                    '<span class="clm-handle">☰</span>' +
                    '<span class="lyric-number">' + newIndex + '.</span>' +
                    '<span class="lyric-title">' + title + '</span>' +
                    '<span class="lyric-composer">' + composer + '</span>' +
                    '<input type="hidden" name="clm_event_setlist[]" value="' + lyricId + '" />' +
                    '<button type="button" class="clm-remove-from-setlist button button-small">Remove</button>' +
                    '</li>');
                
                $('#clm-event-setlist').append($newLi);
                $li.hide();
            });
            
            // Remove from setlist
            $(document).on('click', '.clm-remove-from-setlist', function() {
                var $li = $(this).closest('li');
                var lyricId = $li.data('lyric-id');
                
                // Show in available list
                $('#clm-available-lyrics li[data-lyric-id="' + lyricId + '"]').show();
                
                // Remove from setlist
                $li.remove();
                
                // Update numbering
                $('#clm-event-setlist li').each(function(index) {
                    $(this).find('.lyric-number').text((index + 1) + '.');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render event attendance meta box.
     */
    public function render_event_attendance_meta_box($post) {
        wp_nonce_field('clm_save_event_attendance', 'clm_event_attendance_nonce');
        
        $attendance = get_post_meta($post->ID, '_clm_event_attendance', true);
        if (!is_array($attendance)) {
            $attendance = array();
        }
        
        // Get all users with relevant roles
        $users = get_users(array(
            'role__in' => array('administrator', 'editor', 'author', 'contributor', 'clm_choir_member')
        ));
        ?>
        
        <div class="clm-attendance-container">
            <p>
                <label>
                    <input type="checkbox" id="clm-select-all-attendance" />
                    <?php _e('Select All', 'choir-lyrics-manager'); ?>
                </label>
            </p>
            
            <div class="clm-attendance-list">
                <?php foreach ($users as $user): ?>
                    <label>
                        <input type="checkbox" name="clm_event_attendance[]" value="<?php echo $user->ID; ?>" 
                               <?php checked(in_array($user->ID, $attendance)); ?> />
                        <?php echo esc_html($user->display_name); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <p class="clm-attendance-count">
                <strong><?php _e('Total Attending:', 'choir-lyrics-manager'); ?></strong> 
                <span id="clm-attendance-count"><?php echo count($attendance); ?></span>
            </p>
        </div>
        
        <style>
        .clm-attendance-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
        }
        .clm-attendance-list label {
            display: block;
            margin: 5px 0;
        }
        .clm-attendance-count {
            text-align: center;
            margin-top: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Select all functionality
            $('#clm-select-all-attendance').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('.clm-attendance-list input[type="checkbox"]').prop('checked', isChecked);
                updateAttendanceCount();
            });
            
            // Update count on individual checkbox change
            $('.clm-attendance-list input[type="checkbox"]').on('change', function() {
                updateAttendanceCount();
            });
            
            function updateAttendanceCount() {
                var count = $('.clm-attendance-list input[type="checkbox"]:checked').length;
                $('#clm-attendance-count').text(count);
            }
        });
        </script>
        <?php
    }

    /**
     * Save event meta data.
     */
    public function save_event_meta($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['clm_event_details_nonce']) || 
            !wp_verify_nonce($_POST['clm_event_details_nonce'], 'clm_save_event_details')) {
            return;
        }

        // Check if the current user has permission to edit the post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save event details
        $fields = array(
            'clm_event_date' => '_clm_event_date',
            'clm_event_time' => '_clm_event_time',
            'clm_event_end_time' => '_clm_event_end_time',
            'clm_event_location' => '_clm_event_location',
            'clm_event_director' => '_clm_event_director',
            'clm_event_accompanist' => '_clm_event_accompanist',
            'clm_event_ticket_price' => '_clm_event_ticket_price',
            'clm_event_ticket_link' => '_clm_event_ticket_link',
        );

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }

        // Save setlist
        if (isset($_POST['clm_event_setlist']) && 
            isset($_POST['clm_event_setlist_nonce']) && 
            wp_verify_nonce($_POST['clm_event_setlist_nonce'], 'clm_save_event_setlist')) {
            
            $setlist = array_map('intval', $_POST['clm_event_setlist']);
            update_post_meta($post_id, '_clm_event_setlist', $setlist);
        }

        // Save attendance
        if (isset($_POST['clm_event_attendance']) && 
            isset($_POST['clm_event_attendance_nonce']) && 
            wp_verify_nonce($_POST['clm_event_attendance_nonce'], 'clm_save_event_attendance')) {
            
            $attendance = array_map('intval', $_POST['clm_event_attendance']);
            update_post_meta($post_id, '_clm_event_attendance', $attendance);
        }
    }

    /**
     * Add custom columns to events list.
     */
    public function add_event_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            if ($key == 'date') {
                $new_columns['event_date'] = __('Event Date', 'choir-lyrics-manager');
                $new_columns['venue'] = __('Venue', 'choir-lyrics-manager');
                $new_columns['attendance'] = __('Attendance', 'choir-lyrics-manager');
            }
            $new_columns[$key] = $value;
        }
        
        return $new_columns;
    }

    /**
     * Display custom column content.
     */
    public function display_event_columns($column, $post_id) {
        switch ($column) {
            case 'event_date':
                $event_date = get_post_meta($post_id, '_clm_event_date', true);
                $event_time = get_post_meta($post_id, '_clm_event_time', true);
                
                if ($event_date) {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date)));
                    if ($event_time) {
                        echo ' @ ' . esc_html(date_i18n(get_option('time_format'), strtotime($event_time)));
                    }
                }
                break;
                
            case 'venue':
                $venues = get_the_terms($post_id, 'clm_venue');
                if ($venues && !is_wp_error($venues)) {
                    $venue_names = array();
                    foreach ($venues as $venue) {
                        $venue_names[] = $venue->name;
                    }
                    echo esc_html(implode(', ', $venue_names));
                }
                break;
				
			case 'attendance':
               $attendance = get_post_meta($post_id, '_clm_event_attendance', true);
               if (is_array($attendance)) {
                   echo count($attendance);
               } else {
                   echo '0';
               }
               break;
       }
   }

   /**
    * Make event columns sortable.
    */
   public function make_event_columns_sortable($columns) {
       $columns['event_date'] = 'event_date';
       $columns['attendance'] = 'attendance';
       return $columns;
   }

   /**
    * Handle event column sorting.
    */
   public function handle_event_column_sorting($query) {
       if (!is_admin() || !$query->is_main_query()) {
           return;
       }

       if ('clm_event' !== $query->get('post_type')) {
           return;
       }

       $orderby = $query->get('orderby');

       if ('event_date' == $orderby) {
           $query->set('meta_key', '_clm_event_date');
           $query->set('orderby', 'meta_value');
       }

       if ('attendance' == $orderby) {
           $query->set('meta_key', '_clm_event_attendance');
           $query->set('orderby', 'meta_value_num');
       }
   }

   /**
    * Register event shortcodes.
    */
   public function register_shortcodes() {
       add_shortcode('clm_events', array($this, 'events_shortcode'));
       add_shortcode('clm_event', array($this, 'single_event_shortcode'));
       add_shortcode('clm_event_calendar', array($this, 'event_calendar_shortcode'));
       add_shortcode('clm_event_setlist', array($this, 'event_setlist_shortcode'));
   }

   /**
    * Events list shortcode.
    */
   public function events_shortcode($atts) {
       $atts = shortcode_atts(array(
           'type' => '',
           'venue' => '',
           'upcoming' => 'yes',
           'limit' => 10,
           'show_past' => 'no',
           'orderby' => 'event_date',
           'order' => 'ASC',
       ), $atts);

       $args = array(
           'post_type' => 'clm_event',
           'posts_per_page' => intval($atts['limit']),
           'post_status' => 'publish',
       );

       // Filter by event type
       if (!empty($atts['type'])) {
           $args['tax_query'][] = array(
               'taxonomy' => 'clm_event_type',
               'field' => 'slug',
               'terms' => $atts['type'],
           );
       }

       // Filter by venue
       if (!empty($atts['venue'])) {
           $args['tax_query'][] = array(
               'taxonomy' => 'clm_venue',
               'field' => 'slug',
               'terms' => $atts['venue'],
           );
       }

       // Filter upcoming/past events
       if ($atts['upcoming'] === 'yes' && $atts['show_past'] === 'no') {
           $args['meta_query'][] = array(
               'key' => '_clm_event_date',
               'value' => date('Y-m-d'),
               'compare' => '>=',
               'type' => 'DATE',
           );
       } elseif ($atts['upcoming'] === 'no' && $atts['show_past'] === 'yes') {
           $args['meta_query'][] = array(
               'key' => '_clm_event_date',
               'value' => date('Y-m-d'),
               'compare' => '<',
               'type' => 'DATE',
           );
       }

       // Ordering
       if ($atts['orderby'] === 'event_date') {
           $args['meta_key'] = '_clm_event_date';
           $args['orderby'] = 'meta_value';
           $args['order'] = $atts['order'];
       } else {
           $args['orderby'] = $atts['orderby'];
           $args['order'] = $atts['order'];
       }

       $events = new WP_Query($args);

       ob_start();
       
       if ($events->have_posts()) {
           echo '<div class="clm-events-list">';
           
           while ($events->have_posts()) {
               $events->the_post();
               $event_id = get_the_ID();
               
               $event_date = get_post_meta($event_id, '_clm_event_date', true);
               $event_time = get_post_meta($event_id, '_clm_event_time', true);
               $event_location = get_post_meta($event_id, '_clm_event_location', true);
               $ticket_price = get_post_meta($event_id, '_clm_event_ticket_price', true);
               $ticket_link = get_post_meta($event_id, '_clm_event_ticket_link', true);
               
               ?>
               <div class="clm-event-item">
                   <div class="clm-event-date">
                       <?php if ($event_date): ?>
                           <div class="clm-event-day"><?php echo date('d', strtotime($event_date)); ?></div>
                           <div class="clm-event-month"><?php echo date('M', strtotime($event_date)); ?></div>
                       <?php endif; ?>
                   </div>
                   
                   <div class="clm-event-details">
                       <h3 class="clm-event-title">
                           <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                       </h3>
                       
                       <?php if ($event_time || $event_location): ?>
                           <div class="clm-event-meta">
                               <?php if ($event_time): ?>
                                   <span class="clm-event-time">
                                       <span class="dashicons dashicons-clock"></span>
                                       <?php echo esc_html(date('g:i A', strtotime($event_time))); ?>
                                   </span>
                               <?php endif; ?>
                               
                               <?php if ($event_location): ?>
                                   <span class="clm-event-location">
                                       <span class="dashicons dashicons-location"></span>
                                       <?php echo esc_html($event_location); ?>
                                   </span>
                               <?php endif; ?>
                           </div>
                       <?php endif; ?>
                       
                       <div class="clm-event-excerpt">
                           <?php the_excerpt(); ?>
                       </div>
                       
                       <?php if ($ticket_price || $ticket_link): ?>
                           <div class="clm-event-tickets">
                               <?php if ($ticket_price): ?>
                                   <span class="clm-event-price"><?php echo esc_html($ticket_price); ?></span>
                               <?php endif; ?>
                               
                               <?php if ($ticket_link): ?>
                                   <a href="<?php echo esc_url($ticket_link); ?>" class="clm-button clm-button-primary" target="_blank">
                                       <?php _e('Get Tickets', 'choir-lyrics-manager'); ?>
                                   </a>
                               <?php endif; ?>
                           </div>
                       <?php endif; ?>
                   </div>
               </div>
               <?php
           }
           
           echo '</div>';
           
           wp_reset_postdata();
       } else {
           echo '<p class="clm-no-events">' . __('No events found.', 'choir-lyrics-manager') . '</p>';
       }
       
       return ob_get_clean();
   }

   /**
    * Single event shortcode.
    */
   public function single_event_shortcode($atts) {
       $atts = shortcode_atts(array(
           'id' => 0,
           'show_setlist' => 'yes',
           'show_details' => 'yes',
       ), $atts);

       if (empty($atts['id'])) {
           return '';
       }

       $event = get_post($atts['id']);
       
       if (!$event || $event->post_type !== 'clm_event') {
           return '';
       }

       ob_start();
       ?>
       <div class="clm-single-event">
           <h2 class="clm-event-title"><?php echo esc_html($event->post_title); ?></h2>
           
           <?php if ($atts['show_details'] === 'yes'): ?>
               <?php
               $event_date = get_post_meta($event->ID, '_clm_event_date', true);
               $event_time = get_post_meta($event->ID, '_clm_event_time', true);
               $event_end_time = get_post_meta($event->ID, '_clm_event_end_time', true);
               $event_location = get_post_meta($event->ID, '_clm_event_location', true);
               $event_director = get_post_meta($event->ID, '_clm_event_director', true);
               $event_accompanist = get_post_meta($event->ID, '_clm_event_accompanist', true);
               $ticket_price = get_post_meta($event->ID, '_clm_event_ticket_price', true);
               $ticket_link = get_post_meta($event->ID, '_clm_event_ticket_link', true);
               ?>
               
               <div class="clm-event-details">
                   <?php if ($event_date): ?>
                       <div class="clm-event-detail">
                           <span class="clm-detail-label"><?php _e('Date:', 'choir-lyrics-manager'); ?></span>
                           <span class="clm-detail-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?></span>
                       </div>
                   <?php endif; ?>
                   
                   <?php if ($event_time): ?>
                       <div class="clm-event-detail">
                           <span class="clm-detail-label"><?php _e('Time:', 'choir-lyrics-manager'); ?></span>
                           <span class="clm-detail-value">
                               <?php 
                               echo esc_html(date_i18n(get_option('time_format'), strtotime($event_time)));
                               if ($event_end_time) {
                                   echo ' - ' . esc_html(date_i18n(get_option('time_format'), strtotime($event_end_time)));
                               }
                               ?>
                           </span>
                       </div>
                   <?php endif; ?>
                   
                   <?php if ($event_location): ?>
                       <div class="clm-event-detail">
                           <span class="clm-detail-label"><?php _e('Location:', 'choir-lyrics-manager'); ?></span>
                           <span class="clm-detail-value"><?php echo nl2br(esc_html($event_location)); ?></span>
                       </div>
                   <?php endif; ?>
                   
                   <?php if ($event_director): ?>
                       <div class="clm-event-detail">
                           <span class="clm-detail-label"><?php _e('Director:', 'choir-lyrics-manager'); ?></span>
                           <span class="clm-detail-value"><?php echo esc_html($event_director); ?></span>
                       </div>
                   <?php endif; ?>
                   
                   <?php if ($event_accompanist): ?>
                       <div class="clm-event-detail">
                           <span class="clm-detail-label"><?php _e('Accompanist:', 'choir-lyrics-manager'); ?></span>
                           <span class="clm-detail-value"><?php echo esc_html($event_accompanist); ?></span>
                       </div>
                   <?php endif; ?>
                   
                   <?php if ($ticket_price || $ticket_link): ?>
                       <div class="clm-event-tickets">
                           <?php if ($ticket_price): ?>
                               <span class="clm-event-price"><?php echo esc_html($ticket_price); ?></span>
                           <?php endif; ?>
                           
                           <?php if ($ticket_link): ?>
                               <a href="<?php echo esc_url($ticket_link); ?>" class="clm-button clm-button-primary" target="_blank">
                                   <?php _e('Get Tickets', 'choir-lyrics-manager'); ?>
                               </a>
                           <?php endif; ?>
                       </div>
                   <?php endif; ?>
               </div>
           <?php endif; ?>
           
           <div class="clm-event-content">
               <?php echo apply_filters('the_content', $event->post_content); ?>
           </div>
           
           <?php if ($atts['show_setlist'] === 'yes'): ?>
               <?php echo $this->event_setlist_shortcode(array('id' => $event->ID)); ?>
           <?php endif; ?>
       </div>
       <?php
       
       return ob_get_clean();
   }

   /**
    * Event setlist shortcode.
    */
   public function event_setlist_shortcode($atts) {
       $atts = shortcode_atts(array(
           'id' => 0,
           'show_links' => 'yes',
           'show_details' => 'yes',
       ), $atts);

       if (empty($atts['id'])) {
           return '';
       }

       $setlist = get_post_meta($atts['id'], '_clm_event_setlist', true);
       
       if (!is_array($setlist) || empty($setlist)) {
           return '';
       }

       ob_start();
       ?>
       <div class="clm-event-setlist">
           <h3><?php _e('Program', 'choir-lyrics-manager'); ?></h3>
           <ol class="clm-setlist">
               <?php foreach ($setlist as $lyric_id): ?>
                   <?php $lyric = get_post($lyric_id); ?>
                   <?php if ($lyric): ?>
                       <li class="clm-setlist-item">
                           <?php if ($atts['show_links'] === 'yes'): ?>
                               <a href="<?php echo get_permalink($lyric->ID); ?>" class="clm-setlist-title">
                                   <?php echo esc_html($lyric->post_title); ?>
                               </a>
                           <?php else: ?>
                               <span class="clm-setlist-title"><?php echo esc_html($lyric->post_title); ?></span>
                           <?php endif; ?>
                           
                           <?php if ($atts['show_details'] === 'yes'): ?>
                               <?php
                               $composer = get_post_meta($lyric->ID, '_clm_composer', true);
                               $arranger = get_post_meta($lyric->ID, '_clm_arranger', true);
                               ?>
                               <?php if ($composer || $arranger): ?>
                                   <span class="clm-setlist-meta">
                                       <?php if ($composer): ?>
                                           <span class="clm-composer"><?php echo esc_html($composer); ?></span>
                                       <?php endif; ?>
                                       <?php if ($composer && $arranger): ?>
                                           <span class="clm-separator">/</span>
                                       <?php endif; ?>
                                       <?php if ($arranger): ?>
                                           <span class="clm-arranger"><?php _e('arr.', 'choir-lyrics-manager'); ?> <?php echo esc_html($arranger); ?></span>
                                       <?php endif; ?>
                                   </span>
                               <?php endif; ?>
                           <?php endif; ?>
                       </li>
                   <?php endif; ?>
               <?php endforeach; ?>
           </ol>
       </div>
       <?php
       
       return ob_get_clean();
   }

   /**
    * Event calendar shortcode.
    */
   public function event_calendar_shortcode($atts) {
       $atts = shortcode_atts(array(
           'type' => '',
           'venue' => '',
       ), $atts);

       // This would require a more complex implementation with a JavaScript calendar library
       // For now, return a placeholder
       return '<div class="clm-event-calendar" data-type="' . esc_attr($atts['type']) . '" data-venue="' . esc_attr($atts['venue']) . '">' . 
              __('Calendar view coming soon', 'choir-lyrics-manager') . 
              '</div>';
   }

   /**
    * Add event capabilities.
    */
   public function add_capabilities() {
       $roles = array('administrator', 'editor');
       
       foreach ($roles as $role_name) {
           $role = get_role($role_name);
           if ($role) {
               $role->add_cap('edit_clm_event');
               $role->add_cap('edit_clm_events');
               $role->add_cap('edit_others_clm_events');
               $role->add_cap('publish_clm_events');
               $role->add_cap('read_clm_event');
               $role->add_cap('read_private_clm_events');
               $role->add_cap('delete_clm_event');
               $role->add_cap('delete_clm_events');
               $role->add_cap('delete_others_clm_events');
               $role->add_cap('delete_published_clm_events');
           }
       }
   }

   /**
    * Remove event capabilities on deactivation.
    */
   public function remove_capabilities() {
       $roles = array('administrator', 'editor');
       
       foreach ($roles as $role_name) {
           $role = get_role($role_name);
           if ($role) {
               $role->remove_cap('edit_clm_event');
               $role->remove_cap('edit_clm_events');
               $role->remove_cap('edit_others_clm_events');
               $role->remove_cap('publish_clm_events');
               $role->remove_cap('read_clm_event');
               $role->remove_cap('read_private_clm_events');
               $role->remove_cap('delete_clm_event');
               $role->remove_cap('delete_clm_events');
               $role->remove_cap('delete_others_clm_events');
               $role->remove_cap('delete_published_clm_events');
           }
       }
   }
}