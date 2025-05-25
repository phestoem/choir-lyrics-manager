<?php
/**
 * Album functionality for the Choir Lyrics Manager plugin.
 *
 * @since      1.2.0
 * @package    Choir_Lyrics_Manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class CLM_Albums {

    private $plugin_name;
    private $version;
    public $album_cpt_slug;
    public $collection_taxonomy_slug;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->album_cpt_slug = 'clm_album';
        $this->collection_taxonomy_slug = 'clm_collection';

        // Hooks will be added by the main plugin loader (Choir_Lyrics_Manager)
        // or CLM_Public for template filters and shortcodes.
        // For example:
        // In Choir_Lyrics_Manager->define_public_hooks():
        //   $this->loader->add_action('init', $clm_albums_instance, 'register_post_type_and_taxonomy');
        //   $this->loader->add_filter('single_template', $clm_albums_instance, 'load_single_album_template');
        //   $this->loader->add_filter('archive_template', $clm_albums_instance, 'load_archive_album_template');
        //   $this->loader->add_filter('taxonomy_template', $clm_albums_instance, 'load_taxonomy_collection_template');

        // Shortcodes are typically registered in CLM_Public, which can then call methods of this class.
        // add_shortcode('clm_album', array($this, 'album_shortcode_output'));
        // add_shortcode('clm_albums', array($this, 'albums_shortcode_output'));
    }

    /**
     * Register the Album Custom Post Type and Collection Taxonomy.
     */
    public function register_post_type_and_taxonomy() {
        // Register Album CPT
        $album_labels = array(
            'name'                  => _x( 'Albums', 'Post Type General Name', 'choir-lyrics-manager' ),
            'singular_name'         => _x( 'Album', 'Post Type Singular Name', 'choir-lyrics-manager' ),
            'menu_name'             => __( 'Albums', 'choir-lyrics-manager' ),
            'name_admin_bar'        => __( 'Album', 'choir-lyrics-manager' ),
            'archives'              => __( 'Album Archives', 'choir-lyrics-manager' ),
            'attributes'            => __( 'Album Attributes', 'choir-lyrics-manager' ),
            'parent_item_colon'     => __( 'Parent Album:', 'choir-lyrics-manager' ),
            'all_items'             => __( 'All Albums', 'choir-lyrics-manager' ),
            'add_new_item'          => __( 'Add New Album', 'choir-lyrics-manager' ),
            'add_new'               => __( 'Add New', 'choir-lyrics-manager' ),
            'new_item'              => __( 'New Album', 'choir-lyrics-manager' ),
            'edit_item'             => __( 'Edit Album', 'choir-lyrics-manager' ),
            'update_item'           => __( 'Update Album', 'choir-lyrics-manager' ),
            'view_item'             => __( 'View Album', 'choir-lyrics-manager' ),
            'view_items'            => __( 'View Albums', 'choir-lyrics-manager' ),
            'search_items'          => __( 'Search Album', 'choir-lyrics-manager' ),
            'not_found'             => __( 'No albums found.', 'choir-lyrics-manager' ),
            'not_found_in_trash'    => __( 'No albums found in Trash.', 'choir-lyrics-manager' ),
            'featured_image'        => __( 'Album Cover', 'choir-lyrics-manager' ),
            'set_featured_image'    => __( 'Set album cover', 'choir-lyrics-manager' ),
            'remove_featured_image' => __( 'Remove album cover', 'choir-lyrics-manager' ),
            'use_featured_image'    => __( 'Use as album cover', 'choir-lyrics-manager' ),
            'insert_into_item'      => __( 'Insert into album', 'choir-lyrics-manager' ),
            'uploaded_to_this_item' => __( 'Uploaded to this album', 'choir-lyrics-manager' ),
            'items_list'            => __( 'Albums list', 'choir-lyrics-manager' ),
            'items_list_navigation' => __( 'Albums list navigation', 'choir-lyrics-manager' ),
            'filter_items_list'     => __( 'Filter albums list', 'choir-lyrics-manager' ),
        );
        $album_args = array(
            'label'                 => __( 'Album', 'choir-lyrics-manager' ),
            'description'           => __( 'Represents a collection of choir lyrics/songs.', 'choir-lyrics-manager' ),
            'labels'                => $album_labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'custom-fields', 'revisions' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'clm_dashboard', // Assumes 'clm_dashboard' is a registered top-level menu slug
            'menu_icon'             => 'dashicons-album',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array( 'slug' => 'albums' ),
        );
        register_post_type( $this->album_cpt_slug, $album_args );

        // Register Collection Taxonomy
        if ( ! taxonomy_exists( $this->collection_taxonomy_slug ) ) {
            $collection_labels = array(
                'name'                       => _x( 'Collections', 'Taxonomy General Name', 'choir-lyrics-manager' ),
                'singular_name'              => _x( 'Collection', 'Taxonomy Singular Name', 'choir-lyrics-manager' ),
                'search_items'               => __( 'Search Collections', 'choir-lyrics-manager' ),
                'popular_items'              => __( 'Popular Collections', 'choir-lyrics-manager' ),
                'all_items'                  => __( 'All Collections', 'choir-lyrics-manager' ),
                'parent_item'                => __( 'Parent Collection', 'choir-lyrics-manager' ),
                'parent_item_colon'          => __( 'Parent Collection:', 'choir-lyrics-manager' ),
                'edit_item'                  => __( 'Edit Collection', 'choir-lyrics-manager' ),
                'update_item'                => __( 'Update Collection', 'choir-lyrics-manager' ),
                'add_new_item'               => __( 'Add New Collection', 'choir-lyrics-manager' ),
                'new_item_name'              => __( 'New Collection Name', 'choir-lyrics-manager' ),
                'separate_items_with_commas' => __( 'Separate collections with commas', 'choir-lyrics-manager' ),
                'add_or_remove_items'        => __( 'Add or remove collections', 'choir-lyrics-manager' ),
                'choose_from_most_used'      => __( 'Choose from the most used collections', 'choir-lyrics-manager' ),
                'not_found'                  => __( 'No collections found.', 'choir-lyrics-manager' ),
                'menu_name'                  => __( 'Collections', 'choir-lyrics-manager' ),
            );
            $collection_args = array(
                'hierarchical'      => true, // Can be true (like categories) or false (like tags)
                'labels'            => $collection_labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array( 'slug' => 'collection' ),
                'show_in_rest'      => true,
            );
            register_taxonomy( $this->collection_taxonomy_slug, array( 'clm_lyric', $this->album_cpt_slug ), $collection_args );
        } else {
            register_taxonomy_for_object_type( $this->collection_taxonomy_slug, $this->album_cpt_slug );
        }
         add_post_type_support('clm_album', 'excerpt');
    }


    /**
     * Load custom template for single album.
     * Looks for single-clm_album.php in theme, then single-album.php in theme, then plugin's template.
     */
    public function load_single_album_template( $template ) {
        global $post;

        if ( is_singular( $this->album_cpt_slug ) && $post->post_type === $this->album_cpt_slug ) {
            $theme_template_specific = locate_template( array( 'single-' . $this->album_cpt_slug . '.php' ) );
            $theme_template_generic = locate_template( array( 'single-album.php' ) );
            
            if ( $theme_template_specific ) {
                return $theme_template_specific;
            } elseif ( $theme_template_generic ) {
                return $theme_template_generic;
            }

            $plugin_template = CLM_PLUGIN_DIR . 'templates/single-album.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        return $template;
    }

    /**
     * Load custom template for album archive.
     * Looks for archive-clm_album.php in theme, then archive-album.php in theme, then plugin's template.
     */
    public function load_archive_album_template( $template ) {
        if ( is_post_type_archive( $this->album_cpt_slug ) ) {
            $theme_template_specific = locate_template( array( 'archive-' . $this->album_cpt_slug . '.php' ) );
            $theme_template_generic = locate_template( array( 'archive-album.php' ) );

            if ( $theme_template_specific ) {
                return $theme_template_specific;
            } elseif ( $theme_template_generic ) {
                return $theme_template_generic;
            }
            
            $plugin_template = CLM_PLUGIN_DIR . 'templates/archive-album.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        return $template;
    }
    
    /**
     * Load custom template for clm_collection taxonomy archive.
     */
    public function load_taxonomy_collection_template( $template ) {
        if ( is_tax( $this->collection_taxonomy_slug ) ) {
            $theme_template = locate_template( array( 'taxonomy-' . $this->collection_taxonomy_slug . '.php', 'taxonomy-collection.php' ) );
            
            if ( $theme_template ) {
                return $theme_template;
            }
            
            $plugin_template = CLM_PLUGIN_DIR . 'templates/taxonomy-collection.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        return $template;
    }


    /**
     * Shortcode to display a single album.
     */
    public function album_shortcode_output( $atts ) {
        $atts = shortcode_atts( array(
            'id' => 0,
            // Add other attributes from your design if needed: show_details, show_media, show_description
        ), $atts );

        $album_id = intval( $atts['id'] );

        if ( ! $album_id || get_post_type( $album_id ) !== $this->album_cpt_slug ) {
            return '<p class="clm-error">' . __( 'Invalid Album ID.', 'choir-lyrics-manager' ) . '</p>';
        }

        $album_post = get_post( $album_id );
        if ( ! $album_post || $album_post->post_status !== 'publish' ) {
            return '<p class="clm-error">' . __( 'Album not found or not published.', 'choir-lyrics-manager' ) . '</p>';
        }

       // Prepare data for the template
		$template_data = array(
			'album_id' => $album_id,
			'album_post' => $album_post, // Contains title, content (description)
			'atts' => $atts,
			'lyrics' => array(), // Populated from _clm_album_lyric_ids
			// ADD any other meta fields needed by the template directly here
			'tagline' => get_post_meta( $album_id, '_clm_album_tagline', true ),
			'release_date_display' => get_post_meta( $album_id, '_clm_album_release_date', true ),
			'director' => get_post_meta( $album_id, '_clm_director', true ), // Add this if it's needed in the shortcode
		);

        $lyric_ids = get_post_meta( $album_id, '_clm_album_lyric_ids', true );
        if ( ! empty( $lyric_ids ) && is_array( $lyric_ids ) ) {
            $track_args = array(
                'post_type' => 'clm_lyric', // Assuming 'clm_lyric' is your lyric CPT slug
                'post__in' => $lyric_ids,
                'orderby' => 'post__in',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            );
            $tracks_query = new WP_Query( $track_args );
            if ( $tracks_query->have_posts() ) {
                while ( $tracks_query->have_posts() ) {
                    $tracks_query->the_post();
                    // Store relevant lyric data for the template
                    $template_data['lyrics'][] = array(
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'permalink' => get_permalink(),
                        // Add any other lyric data needed by the template
                    );
                }
                wp_reset_postdata();
            }
        }
        
        // Pass data to a template file for rendering
        // This allows for easier customization and cleaner code
        ob_start();
        // Make $template_data available to the included file
        extract($template_data); 
        
        $template_path = CLM_PLUGIN_DIR . 'templates/shortcode/album.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback or error message if template is missing
            echo '<p class="clm-error">' . __('Album shortcode template missing.', 'choir-lyrics-manager') . '</p>';
            // You could output the basic structure here as a fallback from my previous version
             echo '<div class="clm-single-album" id="clm-album-' . esc_attr( $album_id ) . '">';
             echo '<h2 class="clm-album-title">' . esc_html( get_the_title( $album_id ) ) . '</h2>';
             // ... etc.
             echo '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Shortcode to display a list of albums.
     */
    public function albums_shortcode_output( $atts ) {
        $atts = shortcode_atts( array(
            'collection'   => '',
            'year'         => '', // Expects a year for _clm_release_year meta
            'limit'        => 10,
            'orderby'      => 'date', // Changed from 'title' in your design
            'order'        => 'DESC', // Changed from 'ASC' in your design
            'show_image'   => 'yes',
            'columns'      => 3,
            // 'show_filters' => 'no', // From your design, can be added
        ), $atts );

        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

        $args = array(
            'post_type'      => $this->album_cpt_slug,
            'posts_per_page' => intval( $atts['limit'] ),
            'paged'          => $paged,
            'post_status'    => 'publish',
        );

        // Order and Orderby
        $valid_orderby = array('date', 'title', 'modified', 'rand', 'menu_order', 'release_year');
        $args['orderby'] = in_array($atts['orderby'], $valid_orderby) ? $atts['orderby'] : 'date';
        $args['order'] = strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        if ($args['orderby'] === 'release_year') {
            $args['meta_key'] = '_clm_album_release_year'; // Assuming this meta key
            $args['orderby'] = 'meta_value_num';
        }
        
        // Tax query for collection
        if ( ! empty( $atts['collection'] ) ) {
            $args['tax_query'][] = array(
                'taxonomy' => $this->collection_taxonomy_slug,
                'field'    => 'slug',
                'terms'    => array_map( 'sanitize_text_field', explode( ',', $atts['collection'] ) ),
            );
        }

        // Meta query for year
        if ( ! empty( $atts['year'] ) ) {
            $args['meta_query'][] = array(
                'key'     => '_clm_album_release_year', // Assuming this meta key stores just the year
                'value'   => sanitize_text_field( $atts['year'] ),
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }
        if (isset($args['tax_query']) && count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
        if (isset($args['meta_query']) && count($args['meta_query']) > 1) {
             $args['meta_query']['relation'] = 'AND';
        }


        $albums_query = new WP_Query( $args );

        // Prepare data for the template
        $template_data = array(
            'albums_query' => $albums_query,
            'atts' => $atts,
            'paged' => $paged,
        );

        ob_start();
        extract($template_data); // Make variables available to the template
        
        $template_path = CLM_PLUGIN_DIR . 'templates/shortcode/albums.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback or error message
            echo '<p class="clm-error">' . __('Albums shortcode template missing.', 'choir-lyrics-manager') . '</p>';
            // Basic fallback from my previous version
            if ( $albums_query->have_posts() ) :
                echo '<div class="clm-albums-list">';
                while ( $albums_query->have_posts() ) : $albums_query->the_post();
                    echo '<div class="clm-album-item">';
                    echo '<a href="' . get_permalink() . '" class="clm-album-link">';
                    if ( has_post_thumbnail() ) {
                        echo '<div class="clm-album-item-cover">' . get_the_post_thumbnail(get_the_ID(), 'medium') . '</div>';
                    }
                    echo '<h3 class="clm-album-item-title">' . get_the_title() . '</h3>';
                    echo '</a></div>';
                endwhile;
                echo '</div>';
                // Pagination
            else :
                echo '<p class="clm-no-albums-found">' . __( 'No albums found.', 'choir-lyrics-manager' ) . '</p>';
            endif;
            wp_reset_postdata();
        }
        return ob_get_clean();
    }
	
	
	// Inside class CLM_Albums (includes/class-clm-albums.php)

    /**
     * Modify the main query for album archives to handle custom filtering and ordering.
     *
     * @since 1.2.0
     * @param WP_Query $query The WP_Query instance (passed by reference).
     */
    public function filter_album_archive_query( $query ) {
        if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( $this->album_cpt_slug ) ) {

            // Items per page from settings
            // Ensure CLM_Settings class is available or settings are loaded differently
            if (class_exists('CLM_Settings')) {
                $settings_instance = new CLM_Settings($this->plugin_name, $this->version); // Or get a shared instance
                $items_per_page = $settings_instance->get_setting('items_per_page', get_option('posts_per_page'));
                $query->set( 'posts_per_page', intval($items_per_page) );
            }


            $tax_query = $query->get('tax_query') ?: array();
            $meta_query = $query->get('meta_query') ?: array();

            // Filter by Collection
            if ( ! empty( $_GET['collection'] ) ) {
                $tax_query[] = array(
                    'taxonomy' => $this->collection_taxonomy_slug,
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $_GET['collection'] ),
                );
            }

            // Filter by Year
            if ( ! empty( $_GET['year'] ) ) {
                $year_filter = intval( $_GET['year'] );
                if ( $year_filter >= 1900 && $year_filter <= date('Y') + 5 ) { // Allow a bit of future for upcoming
                    $meta_query[] = array(
                        'key'     => '_clm_album_release_year',
                        'value'   => $year_filter,
                        'compare' => '=',
                        'type'    => 'NUMERIC',
                    );
                }
            }

            if (!empty($tax_query)) {
                if (count($tax_query) > 1 && !isset($tax_query['relation'])) {
                    $tax_query['relation'] = 'AND';
                }
                $query->set( 'tax_query', $tax_query );
            }
            if (!empty($meta_query)) {
                 if (count($meta_query) > 1 && !isset($meta_query['relation'])) {
                    $meta_query['relation'] = 'AND';
                }
                $query->set( 'meta_query', $meta_query );
            }


            // Handle Orderby
            if ( ! empty( $_GET['orderby'] ) ) {
                $orderby_param = sanitize_key( $_GET['orderby'] );
                $valid_orderby_options = array('title', 'date', 'modified', 'rand'); // Standard WP orderby

                if ( in_array($orderby_param, $valid_orderby_options) ) {
                    $query->set( 'orderby', $orderby_param );
                } elseif ( $orderby_param === 'release_year_meta' ) { // Custom orderby for meta
                    $query->set( 'meta_key', '_clm_album_release_year' );
                    $query->set( 'orderby', 'meta_value_num' );
                }
                // Add more custom orderby options if needed
            } else {
                 // Default order if not specified by GET
                 $query->set( 'orderby', 'title'); // Default to title
                 $query->set( 'order', 'ASC');
            }


            // Handle Order (ASC/DESC)
            if ( ! empty( $_GET['order'] ) ) {
                $order_param = strtoupper( sanitize_key( $_GET['order'] ) );
                if ( in_array( $order_param, array( 'ASC', 'DESC' ) ) ) {
                    $query->set( 'order', $order_param );
                }
            }
        }
    }
	
	// Extend or create a new pre_get_posts handler
public function clm_modify_archive_queries( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {

        // Handling for Album CPT Archive (as before)
        if ( $query->is_post_type_archive( $this->album_cpt_slug ) ) {
            // ... (existing logic for album archive: items_per_page, collection filter, year filter, album orderby) ...
            // Example: Add sorting by release_year_meta if that's a specific case for album archive
            if (isset($_GET['orderby']) && $_GET['orderby'] === 'release_year_meta') {
                $query->set( 'meta_key', '_clm_album_release_year' );
                $query->set( 'orderby', 'meta_value_num' );
            }
        }

        // Handling for Custom Taxonomy Archives
        if ( $query->is_tax(array('clm_genre', 'clm_language', 'clm_difficulty', $this->collection_taxonomy_slug)) ) {
            // Items per page from settings
            if (class_exists('CLM_Settings')) {
                $settings_instance = new CLM_Settings($this->plugin_name, $this->version);
                $items_per_page = $settings_instance->get_setting('items_per_page', get_option('posts_per_page'));
                $query->set( 'posts_per_page', intval($items_per_page) );
            }

            // Handle Orderby for taxonomy archives
            if ( ! empty( $_GET['orderby'] ) ) {
                $orderby_param = sanitize_key( $_GET['orderby'] );
                $valid_orderby_options = array('title', 'date', 'modified', 'rand');

                if ( in_array($orderby_param, $valid_orderby_options) ) {
                    $query->set( 'orderby', $orderby_param );
                } elseif ( $orderby_param === 'lyric_composer_meta' ) {
                    // Only apply if querying lyrics primarily (e.g., not an album collection view if that means something different)
                    // This assumes results are primarily lyrics when sorting by composer
                    $query->set( 'meta_key', '_clm_composer' );
                    $query->set( 'orderby', 'meta_value' ); // For text-based meta value
                }
            } else {
                 // Default order if not specified by GET
                 $query->set( 'orderby', 'title');
                 $query->set( 'order', 'ASC');
            }

            // Handle Order (ASC/DESC)
            if ( ! empty( $_GET['order'] ) ) {
                $order_param = strtoupper( sanitize_key( $_GET['order'] ) );
                if ( in_array( $order_param, array( 'ASC', 'DESC' ) ) ) {
                    $query->set( 'order', $order_param );
                }
            }
        }
    }
}

// And ensure it's hooked in Choir_Lyrics_Manager:
// $this->loader->add_action('pre_get_posts', $clm_albums_instance, 'clm_modify_archive_queries');
// (or if you make clm_modify_archive_queries a method of CLM_Public)
// $this->loader->add_action('pre_get_posts', $plugin_public, 'clm_modify_archive_queries');
}