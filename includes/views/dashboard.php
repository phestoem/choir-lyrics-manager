<?php
// Basic dashboard view
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p><?php _e('Welcome to the Choir Lyrics Manager Dashboard!', 'choir-lyrics-manager'); ?></p>
    
    <div class="card">
        <h2><?php _e('Quick Start Guide', 'choir-lyrics-manager'); ?></h2>
        <p><?php _e('Add lyrics, create albums, and track practice sessions with this plugin.', 'choir-lyrics-manager'); ?></p>
        <p><a href="<?php echo admin_url('post-new.php?post_type=clm_lyric'); ?>" class="button button-primary"><?php _e('Add New Lyric', 'choir-lyrics-manager'); ?></a></p>
    </div>
</div>