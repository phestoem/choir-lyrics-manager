<?php
/**
 * Admin-specific functionality for the Albums CPT.
 *
 * @since      1.2.0
 * @package    Choir_Lyrics_Manager
 * @subpackage Choir_Lyrics_Manager/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class CLM_Album_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.2.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.2.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The slug for the Album CPT.
     *
     * @since    1.2.0
     * @access   private
     * @var      string
     */
    private $album_cpt_slug;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.2.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name = null, $version = null ) { // Made params optional for direct instantiation
        $this->plugin_name = $plugin_name ?: 'choir-lyrics-manager';
        $this->version = $version ?: CLM_VERSION; // Assuming CLM_VERSION is defined

        // Get an instance of CLM_Albums to access its CPT slug
        // This assumes CLM_Albums is already loaded or can be instantiated here.
        // A better approach might be to pass the slug or the CLM_Albums instance.
        // For now, we'll hardcode it or assume CLM_Albums is available.
        if (class_exists('CLM_Albums')) {
            $clm_albums_temp = new CLM_Albums($this->plugin_name, $this->version);
            $this->album_cpt_slug = $clm_albums_temp->album_cpt_slug;
        } else {
            $this->album_cpt_slug = 'clm_album'; // Fallback if CLM_Albums isn't loaded yet
        }


        // Hooks are usually added by the main plugin loader (Choir_Lyrics_Manager)
        // Example of how they would be added:
        // In Choir_Lyrics_Manager->define_admin_hooks():
        //   $clm_album_admin_instance = new CLM_Album_Admin($this->get_plugin_name(), $this->get_version());
        //   $this->loader->add_action('add_meta_boxes', $clm_album_admin_instance, 'add_meta_boxes');
        //   $this->loader->add_action('save_post_' . $this->album_cpt_slug, $clm_album_admin_instance, 'save_meta_data');
        //   $this->loader->add_filter('manage_' . $this->album_cpt_slug . '_posts_columns', $clm_album_admin_instance, 'add_admin_columns');
        //   $this->loader->add_action('manage_' . $this->album_cpt_slug . '_posts_custom_column', $clm_album_admin_instance, 'display_admin_columns', 10, 2);
        //   $this->loader->add_action('admin_enqueue_scripts', $clm_album_admin_instance, 'enqueue_admin_assets');
    }

    /**
     * Enqueue admin-specific JavaScript and CSS for album management.
     *
     * @since 1.2.0
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        if ( ($hook == 'post-new.php' || $hook == 'post.php') && $this->album_cpt_slug === $post_type ) {
            wp_enqueue_style(
                $this->plugin_name . '-album-admin',
                CLM_PLUGIN_URL . 'admin/css/clm-album-admin.css', // You'll need to create this CSS file
                array(),
                $this->version,
                'all'
            );

            wp_enqueue_script(
                $this->plugin_name . '-album-admin',
                CLM_PLUGIN_URL . 'admin/js/clm-album-admin.js', // You'll need to create this JS file
                array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), // wp-util for JS templating
                $this->version,
                true
            );

            // Get all lyrics for the track selector
            $lyrics_posts = get_posts(array(
                'post_type' => 'clm_lyric', // Assuming 'clm_lyric' is your lyric CPT slug
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish' // Only published lyrics
            ));

            $all_lyrics_data = array();
            foreach ($lyrics_posts as $lyric) {
                $all_lyrics_data[] = array(
                    'id' => $lyric->ID,
                    'title' => get_the_title($lyric->ID)
                );
            }

            wp_localize_script($this->plugin_name . '-album-admin', 'clmAlbumAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('clm_album_admin_nonce'),
                'all_lyrics' => $all_lyrics_data,
                'text' => array(
                    'add_track' => __('Add Track', 'choir-lyrics-manager'),
                    'remove_track' => __('Remove', 'choir-lyrics-manager'),
                    'no_lyrics_found' => __('No lyrics found.', 'choir-lyrics-manager'),
                    'search_lyrics' => __('Search lyrics...', 'choir-lyrics-manager'),
                )
            ));
        }
    }


    /**
     * Add meta boxes for the Album CPT.
     *
     * @since 1.2.0
     */
    public function add_meta_boxes() {
        add_meta_box(
            'clm_album_details_meta_box',
            __( 'Album Details', 'choir-lyrics-manager' ),
            array( $this, 'render_album_details_meta_box' ),
            $this->album_cpt_slug,
            'normal', // 'normal', 'side', 'advanced'
            'high'    // 'high', 'core', 'default', 'low'
        );

        add_meta_box(
            'clm_album_tracks_meta_box',
            __( 'Album Tracks (Lyrics)', 'choir-lyrics-manager' ),
            array( $this, 'render_album_tracks_meta_box' ),
            $this->album_cpt_slug,
            'normal',
            'high'
        );
    }

    /**
     * Render the Album Details meta box.
     *
     * @since 1.2.0
     * @param WP_Post $post The current post object.
     */
    public function render_album_details_meta_box( $post ) {
        // Add a nonce field for security
        wp_nonce_field( 'clm_save_album_meta', 'clm_album_meta_nonce' );

        $tagline = get_post_meta( $post->ID, '_clm_album_tagline', true );
        $release_date = get_post_meta( $post->ID, '_clm_album_release_date', true );
        $release_year = get_post_meta( $post->ID, '_clm_album_release_year', true );
        $director = get_post_meta( $post->ID, '_clm_director', true ); // Moved here
        ?>
        <p>
            <label for="clm_album_tagline"><?php _e( 'Tagline:', 'choir-lyrics-manager' ); ?></label><br>
            <input type="text" id="clm_album_tagline" name="clm_album_tagline" value="<?php echo esc_attr( $tagline ); ?>" class="widefat" />
        </p>
        <p>
            <label for="clm_album_release_date"><?php _e( 'Release Date (e.g., YYYY-MM-DD or Month YYYY):', 'choir-lyrics-manager' ); ?></label><br>
            <input type="text" id="clm_album_release_date" name="clm_album_release_date" value="<?php echo esc_attr( $release_date ); ?>" class="widefat" placeholder="YYYY-MM-DD or textual date"/>
            <em><small><?php _e('This is displayed as entered.', 'choir-lyrics-manager'); ?></small></em>
        </p>
         <p>
            <label for="clm_album_release_year"><?php _e( 'Release Year (YYYY - for filtering):', 'choir-lyrics-manager' ); ?></label><br>
            <input type="number" id="clm_album_release_year" name="clm_album_release_year" value="<?php echo esc_attr( $release_year ); ?>" class="small-text" placeholder="YYYY" pattern="[0-9]{4}" />
             <em><small><?php _e('Used for sorting and filtering by year.', 'choir-lyrics-manager'); ?></small></em>
        </p>
		 <p>
			<label for="clm_album_director"><?php _e('Director/Conductor:', 'choir-lyrics-manager'); ?></label><br>
			<input type="text" id="clm_album_director" name="clm_album_director" value="<?php echo esc_attr($director); ?>" class="regular-text">
		</p>
        <?php
    }

    /**
     * Render the Album Tracks (Lyrics) meta box.
     *
     * @since 1.2.0
     * @param WP_Post $post The current post object.
     */
    public function render_album_tracks_meta_box( $post ) {
        // Nonce is already in the details meta box, or add another if needed for this specific box
        // wp_nonce_field( 'clm_save_album_tracks_meta', 'clm_album_tracks_nonce' );

        $selected_lyric_ids = get_post_meta( $post->ID, '_clm_album_lyric_ids', true );
        if ( ! is_array( $selected_lyric_ids ) ) {
            $selected_lyric_ids = array();
        }
        ?>
        <div id="clm-album-tracks-container">
            <div id="clm-selected-tracks-list">
                <?php
                if ( ! empty( $selected_lyric_ids ) ) {
                    // Query to get titles for already selected lyrics, maintaining order
                    $selected_lyrics = get_posts(array(
                        'post_type' => 'clm_lyric',
                        'posts_per_page' => -1,
                        'post__in' => $selected_lyric_ids,
                        'orderby' => 'post__in', // Crucial for maintaining saved order
                        'post_status' => 'publish'
                    ));
                    foreach ( $selected_lyrics as $lyric ) {
                        ?>
                        <div class="clm-selected-track-item" data-lyric-id="<?php echo esc_attr( $lyric->ID ); ?>">
                            <span class="dashicons dashicons-menu clm-sortable-handle"></span>
                            <span class="clm-track-title"><?php echo esc_html( get_the_title( $lyric->ID ) ); ?></span>
                            <input type="hidden" name="clm_album_lyric_ids[]" value="<?php echo esc_attr( $lyric->ID ); ?>">
                            <button type="button" class="button button-small clm-remove-track"><?php _e( 'Remove', 'choir-lyrics-manager' ); ?></button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <p class="clm-no-tracks-message" <?php if ( ! empty( $selected_lyric_ids ) ) echo 'style="display:none;"'; ?>>
                <?php _e( 'No tracks added yet.', 'choir-lyrics-manager' ); ?>
            </p>

            <div id="clm-add-track-selector">
                <input type="text" id="clm-lyric-search-input" placeholder="<?php esc_attr_e( 'Search for lyrics to add...', 'choir-lyrics-manager' ); ?>" class="widefat">
                <div id="clm-lyric-search-results">
                    <!-- Search results will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- JavaScript Template for selected track item -->
        <script type="text/html" id="tmpl-clm-selected-track-item">
            <div class="clm-selected-track-item" data-lyric-id="{{ data.id }}">
                <span class="dashicons dashicons-menu clm-sortable-handle"></span>
                <span class="clm-track-title">{{ data.title }}</span>
                <input type="hidden" name="clm_album_lyric_ids[]" value="{{ data.id }}">
                <button type="button" class="button button-small clm-remove-track"><?php _e( 'Remove', 'choir-lyrics-manager' ); ?></button>
            </div>
        </script>
        <?php
    }


    /**
     * Save meta data for the Album CPT.
     *
     * @since 1.2.0
     * @param int $post_id The ID of the post being saved.
     */
    public function save_meta_data( $post_id ) {
        // Check if our nonce is set.
        if ( ! isset( $_POST['clm_album_meta_nonce'] ) ) {
            return;
        }
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['clm_album_meta_nonce'], 'clm_save_album_meta' ) ) {
            return;
        }
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        // Ensure it's our Album CPT
        if ( $this->album_cpt_slug !== get_post_type( $post_id ) ) {
            return;
        }

        // --- Save Album Details ---
        if ( isset( $_POST['clm_album_tagline'] ) ) {
            update_post_meta( $post_id, '_clm_album_tagline', sanitize_text_field( $_POST['clm_album_tagline'] ) );
        }
        if ( isset( $_POST['clm_album_release_date'] ) ) {
            update_post_meta( $post_id, '_clm_album_release_date', sanitize_text_field( $_POST['clm_album_release_date'] ) );
        }
        if ( isset( $_POST['clm_album_release_year'] ) ) {
            $year = sanitize_text_field($_POST['clm_album_release_year']);
            if (is_numeric($year) && strlen($year) === 4) {
                 update_post_meta( $post_id, '_clm_album_release_year', $year );
            } elseif (empty($year)) {
                 delete_post_meta( $post_id, '_clm_album_release_year');
            }
        }
		if ( isset( $_POST['clm_album_director'] ) ) { // MOVED SAVE LOGIC HERE
			update_post_meta( $post_id, '_clm_director', sanitize_text_field( $_POST['clm_album_director'] ) );
		}


        // --- Save Album Tracks (Lyric IDs) ---
        if ( isset( $_POST['clm_album_lyric_ids'] ) && is_array( $_POST['clm_album_lyric_ids'] ) ) {
            $lyric_ids = array_map( 'intval', $_POST['clm_album_lyric_ids'] );
            update_post_meta( $post_id, '_clm_album_lyric_ids', $lyric_ids );
        } else {
            // If no tracks are submitted, delete the meta to represent an empty list
            delete_post_meta( $post_id, '_clm_album_lyric_ids' );
        }
    }

    /**
     * Add custom columns to the Album CPT admin list table.
     *
     * @since 1.2.0
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_admin_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $title ) {
            $new_columns[$key] = $title;
            if ( $key === 'title' ) { // Add after title
                $new_columns['clm_album_cover'] = __( 'Cover', 'choir-lyrics-manager' );
                $new_columns['clm_album_tracks_count'] = __( 'Tracks', 'choir-lyrics-manager' );
                $new_columns['clm_album_release_date_col'] = __( 'Release Date', 'choir-lyrics-manager');
            }
        }
        // If 'title' wasn't found (e.g. only cb), add at the end
        if (!isset($new_columns['clm_album_cover'])) {
            $new_columns['clm_album_cover'] = __( 'Cover', 'choir-lyrics-manager' );
            $new_columns['clm_album_tracks_count'] = __( 'Tracks', 'choir-lyrics-manager' );
            $new_columns['clm_album_release_date_col'] = __( 'Release Date', 'choir-lyrics-manager');
        }

        return $new_columns;
    }

    /**
     * Display content for custom admin columns.
     *
     * @since 1.2.0
     * @param string $column_name The name of the custom column.
     * @param int    $post_id     The ID of the current post.
     */
    public function display_admin_columns( $column_name, $post_id ) {
        switch ( $column_name ) {
            case 'clm_album_cover':
                if ( has_post_thumbnail( $post_id ) ) {
                    echo get_the_post_thumbnail( $post_id, array( 60, 60 ) ); // 60x60 thumbnail
                } else {
                    echo '—'; // Em dash
                }
                break;

            case 'clm_album_tracks_count':
                $lyric_ids = get_post_meta( $post_id, '_clm_album_lyric_ids', true );
                echo ( is_array( $lyric_ids ) ? count( $lyric_ids ) : 0 );
                break;
            
            case 'clm_album_release_date_col':
                $release_date = get_post_meta( $post_id, '_clm_album_release_date', true );
                echo $release_date ? esc_html($release_date) : '—';
                break;
        }
    }
}