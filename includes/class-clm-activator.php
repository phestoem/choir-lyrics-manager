<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Activator {

    /**
     * Activate the plugin.
     *
     * Creates custom database tables and sets up initial plugin settings.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create or update database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Add default terms
        self::add_default_terms();
        
        // Schedule events
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
		
		// Add default event types
		$default_event_types = array(
			'concert' => __('Concert', 'choir-lyrics-manager'),
			'competition' => __('Competition', 'choir-lyrics-manager'),
			'rehearsal' => __('Rehearsal', 'choir-lyrics-manager'),
			'workshop' => __('Workshop', 'choir-lyrics-manager'),
			'recording' => __('Recording Session', 'choir-lyrics-manager'),
			'tour' => __('Tour', 'choir-lyrics-manager'),
		);

		foreach ($default_event_types as $slug => $name) {
			if (!term_exists($slug, 'clm_event_type')) {
				wp_insert_term($name, 'clm_event_type', array('slug' => $slug));
			}
		}
		
		// Update database version
         update_option('clm_db_version', '1.1.0'); // Increment version
		
		// Add capabilities for events
		$events = new CLM_Events('choir-lyrics-manager', CLM_VERSION);
		$events->add_capabilities();
		
		// Add capabilities for members
		CLM_Members::add_capabilities();
		
		// Create default voice types
		CLM_Members::create_default_voice_types();
		
		// Create skills tracking table
		CLM_Skills::create_skills_table();
		
		// Run database updates
        self::update_database();
    }
    
    /**
     * Create custom database tables.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for tracking practice sessions (beyond post meta)
        $table_name = $wpdb->prefix . 'clm_practice_stats';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            lyric_id bigint(20) NOT NULL,
            practice_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            duration int(11) NOT NULL,
            confidence tinyint(4) NOT NULL,
            notes text,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY lyric_id (lyric_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create member skills table
        CLM_Skills::create_skills_table();
        
        // Update DB version
        update_option('clm_db_version', CLM_VERSION);
    }
    
    /**
     * Set default options.
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        // Default general settings
        $default_settings = array(
            'archive_title' => __('Choir Lyrics', 'choir-lyrics-manager'),
            'items_per_page' => 10,
            'show_difficulty' => true,
            'show_composer' => true,
            'submission_roles' => array('administrator', 'editor', 'author', 'clm_manager', 'clm_contributor'),
            'analytics_roles' => array('administrator', 'editor', 'clm_manager'),
            'enable_practice' => true,
            'practice_notification' => 7,
            'primary_color' => '#3498db',
            'secondary_color' => '#2ecc71',
            'font_size' => 16,
            'enable_skills_tracking' => true,
            'enable_member_profiles' => true,
        );
        
        // Only add if not already set
        if (!get_option('clm_settings')) {
            add_option('clm_settings', $default_settings);
        }
    }
    
    /**
     * Add default taxonomy terms.
     *
     * @since    1.0.0
     */
    private static function add_default_terms() {
        // Only run once
        if (get_option('clm_default_terms_added')) {
            return;
        }
        
        // Make sure the taxonomies are registered
        if (!taxonomy_exists('clm_genre') || !taxonomy_exists('clm_difficulty')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-clm-taxonomies.php';
            $taxonomies = new CLM_Taxonomies('choir-lyrics-manager', CLM_VERSION);
            $taxonomies->register_taxonomies();
        }
        
        // Default difficulty levels
        wp_insert_term('Beginner (1)', 'clm_difficulty', array('slug' => 'beginner'));
        wp_insert_term('Easy (2)', 'clm_difficulty', array('slug' => 'easy'));
        wp_insert_term('Intermediate (3)', 'clm_difficulty', array('slug' => 'intermediate'));
        wp_insert_term('Advanced (4)', 'clm_difficulty', array('slug' => 'advanced'));
        wp_insert_term('Expert (5)', 'clm_difficulty', array('slug' => 'expert'));
        
        // Default genres
        wp_insert_term('Classical', 'clm_genre', array('slug' => 'classical'));
        wp_insert_term('Folk', 'clm_genre', array('slug' => 'folk'));
        wp_insert_term('Gospel', 'clm_genre', array('slug' => 'gospel'));
        wp_insert_term('Contemporary', 'clm_genre', array('slug' => 'contemporary'));
        wp_insert_term('Sacred', 'clm_genre', array('slug' => 'sacred'));
        wp_insert_term('Secular', 'clm_genre', array('slug' => 'secular'));
        wp_insert_term('A Cappella', 'clm_genre', array('slug' => 'a-cappella'));
        
        // Default collections
        wp_insert_term('Concerts', 'clm_collection', array('slug' => 'concerts'));
        wp_insert_term('Competitions', 'clm_collection', array('slug' => 'competitions'));
        wp_insert_term('Recordings', 'clm_collection', array('slug' => 'recordings'));
        wp_insert_term('Practice Material', 'clm_collection', array('slug' => 'practice-material'));
        
        // Default languages
        $languages = array(
            'English', 'Spanish', 'French', 'German', 'Italian', 
            'Latin', 'Russian', 'Hebrew', 'Japanese', 'Korean', 
            'Chinese', 'Portuguese', 'Swahili', 'Arabic'
        );
        
        foreach ($languages as $language) {
            wp_insert_term($language, 'clm_language', array('slug' => sanitize_title($language)));
        }
        
        // Mark as done
        update_option('clm_default_terms_added', true);
    }
    
    /**
     * Schedule recurring events.
     *
     * @since    1.0.0
     */
    private static function schedule_events() {
        // Schedule daily analytics update
        if (!wp_next_scheduled('clm_daily_analytics_update')) {
            wp_schedule_event(time(), 'daily', 'clm_daily_analytics_update');
        }
        
        // Schedule weekly practice reminder
        if (!wp_next_scheduled('clm_weekly_practice_reminder')) {
            wp_schedule_event(time(), 'weekly', 'clm_weekly_practice_reminder');
        }
    }
	
	// Add a new method for database updates
	private static function update_database() {
		$current_db_version = get_option('clm_db_version', '1.0.0');
		
		if (version_compare($current_db_version, '1.1.0', '<')) {
			// Update skills table
			CLM_Skills::create_skills_table();
			update_option('clm_db_version', '1.1.0');
		}
	}
}