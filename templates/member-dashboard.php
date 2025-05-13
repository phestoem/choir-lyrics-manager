<?php
/**
 * Template for displaying member dashboard
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get shortcode attributes if this is called from a shortcode
$show_practice = isset($atts) && isset($atts['show_practice']) ? $atts['show_practice'] === 'yes' : true;
$show_playlists = isset($atts) && isset($atts['show_playlists']) ? $atts['show_playlists'] === 'yes' : true;
$show_submissions = isset($atts) && isset($atts['show_submissions']) ? $atts['show_submissions'] === 'yes' : true;

// Create needed class instances
$public = new CLM_Public('choir-lyrics-manager', CLM_VERSION);
$settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
$roles = new CLM_Roles('choir-lyrics-manager', CLM_VERSION);

get_header();
?>

<div class="clm-container clm-user-dashboard">
    <h1 class="clm-dashboard-heading"><?php _e('Member Dashboard', 'choir-lyrics-manager'); ?></h1>
    
    <div class="clm-dashboard-nav">
        <?php if ($show_practice && $settings->get_setting('enable_practice', true)): ?>
            <div class="clm-dashboard-nav-item" data-tab="clm-practice-tab"><?php _e('Practice Stats', 'choir-lyrics-manager'); ?></div>
        <?php endif; ?>
        
        <?php if ($show_playlists): ?>
            <div class="clm-dashboard-nav-item" data-tab="clm-playlists-tab"><?php _e('My Playlists', 'choir-lyrics-manager'); ?></div>
        <?php endif; ?>
        
        <?php if ($show_submissions && $roles->can_submit_lyrics()): ?>
            <div class="clm-dashboard-nav-item" data-tab="clm-submissions-tab"><?php _e('My Submissions', 'choir-lyrics-manager'); ?></div>
        <?php endif; ?>
    </div>
    
    <div class="clm-dashboard-content">
        <?php if ($show_practice && $settings->get_setting('enable_practice', true)): ?>
            <div id="clm-practice-tab" class="clm-dashboard-tab">
                <?php echo $public->get_user_practice_stats_html(); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_playlists): ?>
            <div id="clm-playlists-tab" class="clm-dashboard-tab">
                <?php echo $public->get_user_playlists_html(); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_submissions && $roles->can_submit_lyrics()): ?>
            <div id="clm-submissions-tab" class="clm-dashboard-tab">
                <?php echo $public->get_user_submissions_html(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();