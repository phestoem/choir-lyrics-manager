<?php
/**
 * Custom Taxonomies for the plugin.
 *
 * Define and register all custom taxonomies used by the plugin
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Taxonomies {

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
     * Register custom taxonomies used by the plugin.
     *
     * @since    1.0.0
     */
    public function register_taxonomies() {
        // Register Genre Taxonomy
        register_taxonomy('clm_genre', array('clm_lyric'), array(
            'labels' => array(
                'name'                       => _x('Genres', 'taxonomy general name', 'choir-lyrics-manager'),
                'singular_name'              => _x('Genre', 'taxonomy singular name', 'choir-lyrics-manager'),
                'search_items'               => __('Search Genres', 'choir-lyrics-manager'),
                'popular_items'              => __('Popular Genres', 'choir-lyrics-manager'),
                'all_items'                  => __('All Genres', 'choir-lyrics-manager'),
                'parent_item'                => __('Parent Genre', 'choir-lyrics-manager'),
                'parent_item_colon'          => __('Parent Genre:', 'choir-lyrics-manager'),
                'edit_item'                  => __('Edit Genre', 'choir-lyrics-manager'),
                'update_item'                => __('Update Genre', 'choir-lyrics-manager'),
                'add_new_item'               => __('Add New Genre', 'choir-lyrics-manager'),
                'new_item_name'              => __('New Genre Name', 'choir-lyrics-manager'),
                'separate_items_with_commas' => __('Separate genres with commas', 'choir-lyrics-manager'),
                'add_or_remove_items'        => __('Add or remove genres', 'choir-lyrics-manager'),
                'choose_from_most_used'      => __('Choose from the most used genres', 'choir-lyrics-manager'),
                'not_found'                  => __('No genres found.', 'choir-lyrics-manager'),
                'menu_name'                  => __('Genres', 'choir-lyrics-manager'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'genre'),
            'show_in_rest'      => true, // Enable in Gutenberg
        ));

        // Register Composer Taxonomy
        register_taxonomy('clm_composer', array('clm_lyric'), array(
            'labels' => array(
                'name'                       => _x('Composers', 'taxonomy general name', 'choir-lyrics-manager'),
                'singular_name'              => _x('Composer', 'taxonomy singular name', 'choir-lyrics-manager'),
                'search_items'               => __('Search Composers', 'choir-lyrics-manager'),
                'popular_items'              => __('Popular Composers', 'choir-lyrics-manager'),
                'all_items'                  => __('All Composers', 'choir-lyrics-manager'),
                'edit_item'                  => __('Edit Composer', 'choir-lyrics-manager'),
                'update_item'                => __('Update Composer', 'choir-lyrics-manager'),
                'add_new_item'               => __('Add New Composer', 'choir-lyrics-manager'),
                'new_item_name'              => __('New Composer Name', 'choir-lyrics-manager'),
                'separate_items_with_commas' => __('Separate composers with commas', 'choir-lyrics-manager'),
                'add_or_remove_items'        => __('Add or remove composers', 'choir-lyrics-manager'),
                'choose_from_most_used'      => __('Choose from the most used composers', 'choir-lyrics-manager'),
                'not_found'                  => __('No composers found.', 'choir-lyrics-manager'),
                'menu_name'                  => __('Composers', 'choir-lyrics-manager'),
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'composer'),
            'show_in_rest'      => true,
        ));

        // Register Language Taxonomy
        register_taxonomy('clm_language', array('clm_lyric'), array(
            'labels' => array(
                'name'                       => _x('Languages', 'taxonomy general name', 'choir-lyrics-manager'),
                'singular_name'              => _x('Language', 'taxonomy singular name', 'choir-lyrics-manager'),
                'search_items'               => __('Search Languages', 'choir-lyrics-manager'),
                'popular_items'              => __('Popular Languages', 'choir-lyrics-manager'),
                'all_items'                  => __('All Languages', 'choir-lyrics-manager'),
                'edit_item'                  => __('Edit Language', 'choir-lyrics-manager'),
                'update_item'                => __('Update Language', 'choir-lyrics-manager'),
                'add_new_item'               => __('Add New Language', 'choir-lyrics-manager'),
                'new_item_name'              => __('New Language Name', 'choir-lyrics-manager'),
                'separate_items_with_commas' => __('Separate languages with commas', 'choir-lyrics-manager'),
                'add_or_remove_items'        => __('Add or remove languages', 'choir-lyrics-manager'),
                'choose_from_most_used'      => __('Choose from the most used languages', 'choir-lyrics-manager'),
                'not_found'                  => __('No languages found.', 'choir-lyrics-manager'),
                'menu_name'                  => __('Languages', 'choir-lyrics-manager'),
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'language'),
            'show_in_rest'      => true,
        ));

        // Register Difficulty Taxonomy
        register_taxonomy('clm_difficulty', array('clm_lyric'), array(
            'labels' => array(
                'name'                       => _x('Difficulty Levels', 'taxonomy general name', 'choir-lyrics-manager'),
                'singular_name'              => _x('Difficulty Level', 'taxonomy singular name', 'choir-lyrics-manager'),
                'search_items'               => __('Search Difficulty Levels', 'choir-lyrics-manager'),
                'popular_items'              => __('Popular Difficulty Levels', 'choir-lyrics-manager'),
                'all_items'                  => __('All Difficulty Levels', 'choir-lyrics-manager'),
                'parent_item'                => __('Parent Difficulty Level', 'choir-lyrics-manager'),
                'parent_item_colon'          => __('Parent Difficulty Level:', 'choir-lyrics-manager'),
                'edit_item'                  => __('Edit Difficulty Level', 'choir-lyrics-manager'),
                'update_item'                => __('Update Difficulty Level', 'choir-lyrics-manager'),
                'add_new_item'               => __('Add New Difficulty Level', 'choir-lyrics-manager'),
                'new_item_name'              => __('New Difficulty Level Name', 'choir-lyrics-manager'),
                'not_found'                  => __('No difficulty levels found.', 'choir-lyrics-manager'),
                'menu_name'                  => __('Difficulty Levels', 'choir-lyrics-manager'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'difficulty-level'),
            'show_in_rest'      => true,
        ));
        
        // Register Collection Taxonomy (to group albums)
        register_taxonomy('clm_collection', array('clm_album'), array(
            'labels' => array(
                'name'                       => _x('Collections', 'taxonomy general name', 'choir-lyrics-manager'),
                'singular_name'              => _x('Collection', 'taxonomy singular name', 'choir-lyrics-manager'),
                'search_items'               => __('Search Collections', 'choir-lyrics-manager'),
                'popular_items'              => __('Popular Collections', 'choir-lyrics-manager'),
                'all_items'                  => __('All Collections', 'choir-lyrics-manager'),
                'parent_item'                => __('Parent Collection', 'choir-lyrics-manager'),
                'parent_item_colon'          => __('Parent Collection:', 'choir-lyrics-manager'),
                'edit_item'                  => __('Edit Collection', 'choir-lyrics-manager'),
                'update_item'                => __('Update Collection', 'choir-lyrics-manager'),
                'add_new_item'               => __('Add New Collection', 'choir-lyrics-manager'),
                'new_item_name'              => __('New Collection Name', 'choir-lyrics-manager'),
                'not_found'                  => __('No collections found.', 'choir-lyrics-manager'),
                'menu_name'                  => __('Collections', 'choir-lyrics-manager'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'collection'),
            'show_in_rest'      => true,
        ));
        
        // Hook into pre-defined terms setup
        add_action('admin_init', array($this, 'register_default_terms'));
    }
    
    /**
     * Register default taxonomy terms.
     *
     * @since    1.0.0
     */
    public function register_default_terms() {
        // Only run this on plugin activation
        if (!get_option('clm_default_terms_added')) {
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
            
            // Remember that we've added default terms
            update_option('clm_default_terms_added', true);
        }
    }
    
    /**
     * Add custom column to taxonomy admin screens
     *
     * @since    1.0.0
     * @param    array    $columns    The default columns.
     * @return   array                Modified columns.
     */
    public function add_taxonomy_columns($columns) {
        $new_columns = array(
            'cb'           => $columns['cb'],
            'name'         => $columns['name'],
            'description'  => $columns['description'],
            'slug'         => $columns['slug'],
            'items'        => $columns['posts'],
        );
        
        return $new_columns;
    }
}