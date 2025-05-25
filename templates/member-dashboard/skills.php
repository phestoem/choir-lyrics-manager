<?php
/**
 * Member Skills Dashboard Template
 * Displays the current user's skill progress and practice history.
 *
 * Expected variables in scope from the calling function (e.g., CLM_Admin::display_my_skills_page_callback):
 * - $member_post : WP_Post object for the current user's Member CPT entry.
 * - $plugin_name : string, your plugin's unique name/slug.
 * - $version     : string, your plugin's current version.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure $member_post is set and is a valid Member CPT post.
if (!isset($member_post) || !is_object($member_post) || $member_post->post_type !== 'clm_member') {
    echo '<div class="wrap clm-member-skills-dashboard"><div class="notice notice-warning is-dismissible"><p>' . __('Your associated Member profile could not be loaded. Skill data cannot be displayed.', 'choir-lyrics-manager') . '</p></div></div>';
    return; // Stop further processing if member profile is not found.
}
$member_cpt_id = $member_post->ID;

// Instantiate necessary service classes
$skills_instance = null;
if (class_exists('CLM_Skills')) {
    $skills_instance = new CLM_Skills($plugin_name, $version);
} else {
    echo '<div class="wrap clm-member-skills-dashboard"><div class="notice notice-error is-dismissible"><p>' . __('Skills system is currently unavailable.', 'choir-lyrics-manager') . '</p></div></div>';
    return;
}

$practice_instance = null;
if (class_exists('CLM_Practice')) {
    $practice_instance = new CLM_Practice($plugin_name, $version);
}

// Fetch member's skills
$member_skills_data = array();
if ($skills_instance && method_exists($skills_instance, 'get_member_skills_with_lyric_titles')) {
    $member_skills_data = $skills_instance->get_member_skills_with_lyric_titles($member_cpt_id);
}
$skill_levels_map = $skills_instance->get_skill_levels(); // Assuming this method exists and is public

// --- Filtering and Sorting Logic ---
$filter_level_req = isset($_GET['skill_level']) ? sanitize_text_field($_GET['skill_level']) : '';
if ($filter_level_req && !empty($member_skills_data) && isset($skill_levels_map[$filter_level_req])) {
    $member_skills_data = array_filter($member_skills_data, function($skill_item) use ($filter_level_req) {
        return isset($skill_item->skill_level) && $skill_item->skill_level === $filter_level_req;
    });
}

$sort_by_req = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'title'; // Default sort
if (!empty($member_skills_data)) {
    usort($member_skills_data, function($a, $b) use ($sort_by_req, $skill_levels_map) {
        switch ($sort_by_req) {
            case 'level':
                $a_val = isset($a->skill_level, $skill_levels_map[$a->skill_level]) ? $skill_levels_map[$a->skill_level]['value'] : -1;
                $b_val = isset($b->skill_level, $skill_levels_map[$b->skill_level]) ? $skill_levels_map[$b->skill_level]['value'] : -1;
                return $b_val <=> $a_val; // Sort by level value descending
            case 'practice':
                return ($b->practice_count ?? 0) <=> ($a->practice_count ?? 0); // Descending practice count
            case 'recent':
                $a_time = isset($a->last_practice_date) ? strtotime($a->last_practice_date) : 0;
                $b_time = isset($b->last_practice_date) ? strtotime($b->last_practice_date) : 0;
                return $b_time <=> $a_time; // Most recent first
            default: // 'title'
                return strcasecmp($a->lyric_title ?? '', $b->lyric_title ?? ''); // Ascending title
        }
    });
}

// Calculate skill statistics for the overview
$skill_counts_summary = array_fill_keys(array_keys($skill_levels_map), 0);
$total_practice_sessions_summary = 0;
$total_practice_time_summary = 0;

if (!empty($member_skills_data)) {
    foreach ($member_skills_data as $skill_item) {
        if (isset($skill_item->skill_level) && isset($skill_counts_summary[$skill_item->skill_level])) {
            $skill_counts_summary[$skill_item->skill_level]++;
        }
        $total_practice_sessions_summary += (isset($skill_item->practice_count) ? (int)$skill_item->practice_count : 0);
        $total_practice_time_summary += (isset($skill_item->total_practice_minutes) ? (int)$skill_item->total_practice_minutes : 0);
    }
}
?>

<div class="wrap clm-member-skills-dashboard">
    <h1><?php _e('My Skill Progress', 'choir-lyrics-manager'); ?></h1>

    <!-- Skills Overview Section -->
    <div class="clm-skills-overview">
        <h3><?php _e('Overview', 'choir-lyrics-manager'); ?></h3>
        <div class="clm-skill-stats-summary-grid">
            <?php foreach ($skill_levels_map as $level_key => $level_info): 
                // Skip 'unknown' if it has 0 count and you don't want to show it
                if ($level_key === 'unknown' && $skill_counts_summary[$level_key] === 0) continue;
            ?>
                <div class="clm-skill-stat-box" style="border-left-color: <?php echo esc_attr($level_info['color']); ?>;">
                    <span class="dashicons <?php echo esc_attr($level_info['icon']); ?>" style="color: <?php echo esc_attr($level_info['color']); ?>"></span>
                    <div class="clm-skill-stat-content">
                        <span class="clm-skill-count"><?php echo esc_html($skill_counts_summary[$level_key]); ?></span>
                        <span class="clm-skill-label"><?php echo esc_html($level_info['label']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($member_skills_data) > 0): ?>
        <div class="clm-overall-practice-summary">
            <p>
                <?php printf(__('Total songs with skill data: %d', 'choir-lyrics-manager'), count($member_skills_data)); ?>
                <span class="clm-summary-separator">|</span>
                <?php printf(__('Total practice sessions logged: %d', 'choir-lyrics-manager'), $total_practice_sessions_summary); ?>
                <span class="clm-summary-separator">|</span>
                <?php printf(__('Total practice time: %s', 'choir-lyrics-manager'), esc_html($skills_instance->format_minutes($total_practice_time_summary))); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Individual Song Skills Section -->
    <div class="clm-song-skills-section">
        <h3><?php _e('My Song Skills', 'choir-lyrics-manager'); ?></h3>
        <form method="get" class="clm-skills-filter-form">
            <input type="hidden" name="page" value="clm_my_skills_page">
            <label for="clm-skill-filter-select" class="screen-reader-text"><?php _e('Filter by Level:', 'choir-lyrics-manager'); ?></label>
            <select id="clm-skill-filter-select" name="skill_level">
                <option value=""><?php _e('All Skill Levels', 'choir-lyrics-manager'); ?></option>
                <?php foreach ($skill_levels_map as $level_key_map => $level_info_map):
                     if ($level_key_map === 'unknown' && $skill_counts_summary[$level_key_map] === 0 && empty($filter_level_req)) continue; // Hide unknown if no skills and not filtered
                ?>
                    <option value="<?php echo esc_attr($level_key_map); ?>" <?php selected($filter_level_req, $level_key_map); ?>>
                        <?php echo esc_html($level_info_map['label']); ?> (<?php echo esc_html($skill_counts_summary[$level_key_map]); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="clm-skill-sort-select" class="screen-reader-text"><?php _e('Sort by:', 'choir-lyrics-manager'); ?></label>
            <select id="clm-skill-sort-select" name="sort">
                <option value="title" <?php selected($sort_by_req, 'title'); ?>><?php _e('Song Title (A-Z)', 'choir-lyrics-manager'); ?></option>
                <option value="level" <?php selected($sort_by_req, 'level'); ?>><?php _e('Skill Level (Highest First)', 'choir-lyrics-manager'); ?></option>
                <option value="practice" <?php selected($sort_by_req, 'practice'); ?>><?php _e('Practice Count (Most First)', 'choir-lyrics-manager'); ?></option>
                <option value="recent" <?php selected($sort_by_req, 'recent'); ?>><?php _e('Recently Practiced (Newest First)', 'choir-lyrics-manager'); ?></option>
            </select>
            <?php submit_button(__('Filter / Sort', 'choir-lyrics-manager'), 'secondary small', 'clm_apply_skill_filters_button', false); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=clm_my_skills_page')); ?>" class="button button-link"><?php _e('Reset', 'choir-lyrics-manager'); ?></a>
        </form>

        <?php if (empty($member_skills_data)): ?>
            <div class="clm-no-items-message clm-no-skills">
                <span class="dashicons dashicons-edit-page"></span>
                <h4><?php _e('No Skills Recorded Yet', 'choir-lyrics-manager'); ?></h4>
                <p><?php _e('When you log practice for songs, your skill progress will appear here.', 'choir-lyrics-manager'); ?></p>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=clm_lyric')); ?>" class="button button-primary">
                    <?php _e('Browse Lyrics to Practice', 'choir-lyrics-manager'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="clm-skills-list">
                <?php foreach ($member_skills_data as $skill_item):
                    if (empty($skill_item->lyric_id) || empty($skill_item->lyric_title)) continue;
                    $level_info_display = $skills_instance->get_skill_level_info($skill_item->skill_level);
                ?>
                    <div class="clm-skill-item" data-skill-level="<?php echo esc_attr($skill_item->skill_level); ?>">
                        <div class="clm-skill-item-header">
                            <h4 class="clm-skill-lyric-title"><a href="<?php echo get_permalink($skill_item->lyric_id); ?>#clm-practice-tracker-anchor" title="<?php esc_attr_e('View or Practice this Lyric', 'choir-lyrics-manager'); ?>"><?php echo esc_html($skill_item->lyric_title); ?></a></h4>
                            <span class="clm-skill-level-badge" style="background-color: <?php echo esc_attr($level_info_display['color']); ?>;">
                                <span class="dashicons <?php echo esc_attr($level_info_display['icon']); ?>"></span>
                                <?php echo esc_html($level_info_display['label']); ?>
                            </span>
                        </div>
                        <div class="clm-skill-item-details">
                            <div class="clm-skill-progress-bar-container">
                                <div class="clm-progress-bar">
                                    <div class="clm-progress-fill" 
                                    style="width: <?php echo esc_attr($level_info_display['progress'] ?? 0); ?>%; background-color: <?php echo esc_attr($level_info_display['color']); ?>;" 
                                    title="<?php 
                                        // Prepare the parts for sprintf
                                        $progress_percentage = esc_attr($level_info_display['progress'] ?? 0);
                                        $level_label = esc_html($level_info_display['label'] ?? __('Unknown Level', 'choir-lyrics-manager'));
                                        // Get the translated string with placeholders
                                        $title_string_format = __('%s%% towards %s', 'choir-lyrics-manager');
                                        // Output the formatted and escaped string
                                        printf(esc_attr($title_string_format), $progress_percentage, $level_label); 
                                    ?>">
                                </div>                                
                            </div>
                        </div>
                            <div class="clm-skill-meta">
                                <span><span class="dashicons dashicons-controls-repeat"></span> <?php printf(_n('%s Session', '%s Sessions', $skill_item->practice_count ?? 0, 'choir-lyrics-manager'), esc_html($skill_item->practice_count ?? 0)); ?></span>
                                <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($skills_instance->format_minutes($skill_item->total_practice_minutes ?? 0)); ?></span>
                                <?php if (!empty($skill_item->last_practice_date)): ?>
                                    <span><span class="dashicons dashicons-calendar-alt"></span> <?php echo human_time_diff(strtotime($skill_item->last_practice_date)); ?> <?php _e('ago', 'choir-lyrics-manager'); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($skill_item->goal_date)): ?>
                                <div class="clm-skill-goal">
                                    <strong><span class="dashicons dashicons-flag"></span> <?php _e('Goal:', 'choir-lyrics-manager'); ?></strong>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($skill_item->goal_date)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="clm-skill-item-actions">
                             <a href="<?php echo get_permalink($skill_item->lyric_id); ?>#clm-practice-tracker-anchor" class="clm-button clm-button-small clm-button-secondary">
                                <span class="dashicons dashicons-edit-page"></span> <?php _e('Practice', 'choir-lyrics-manager'); ?>
                            </a>
                            <button type="button" class="clm-button clm-button-small clm-set-skill-goal-button" data-lyric-id="<?php echo esc_attr($skill_item->lyric_id); ?>" data-current-goal="<?php echo esc_attr($skill_item->goal_date ?? ''); ?>">
                                 <span class="dashicons dashicons-flag"></span> <?php echo (!empty($skill_item->goal_date)) ? __('Change Goal', 'choir-lyrics-manager') : __('Set Goal', 'choir-lyrics-manager'); ?>
                            </button>
                        </div>
                         <div id="clm-set-goal-form-container-<?php echo esc_attr($skill_item->lyric_id); ?>" class="clm-set-goal-form" style="display:none;">
                            <label for="clm-goal-date-input-<?php echo esc_attr($skill_item->lyric_id); ?>"><?php _e('New Goal Date:','choir-lyrics-manager'); ?></label>
                            <input type="date" class="clm-goal-date-input" id="clm-goal-date-input-<?php echo esc_attr($skill_item->lyric_id); ?>" value="<?php echo esc_attr($skill_item->goal_date ?? date('Y-m-d', strtotime('+1 month'))); ?>" min="<?php echo date('Y-m-d'); ?>">
                            <div class="clm-goal-form-actions">
                                <button type="button" class="clm-submit-new-goal clm-button clm-button-primary clm-button-small" data-lyric-id="<?php echo esc_attr($skill_item->lyric_id); ?>"><?php _e('Save Goal','choir-lyrics-manager'); ?></button>
                                <button type="button" class="clm-cancel-new-goal clm-button-text"><?php _e('Cancel','choir-lyrics-manager'); ?></button>
                            </div>
                            <div class="clm-set-goal-message" style="display:none;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="clm-recent-practice-section">
        <h3><?php _e('My Recent Practice Sessions', 'choir-lyrics-manager'); ?></h3>
        <?php
        $recent_practices = array();
        if ($practice_instance && method_exists($practice_instance, 'get_member_recent_practice_sessions')) {
            $recent_practices = $practice_instance->get_member_recent_practice_sessions($member_post->ID, 5);
        }
        if (empty($recent_practices)): ?>
            <p class="clm-no-items-message"><?php _e('No recent practice sessions recorded.', 'choir-lyrics-manager'); ?></p>
        <?php else: ?>
            <ul class="clm-practice-list">
                <?php foreach ($recent_practices as $practice_session):
                    $lyric_for_history = get_post($practice_session->lyric_id);
                    if (!$lyric_for_history) continue;
                    ?>
                    <li>
                        <a href="<?php echo get_permalink($lyric_for_history->ID); ?>#clm-practice-tracker-anchor" class="clm-practice-title"><?php echo esc_html($lyric_for_history->post_title); ?></a>
                        <span class="clm-practice-duration"><?php echo esc_html($practice_instance ? $practice_instance->format_duration($practice_session->duration ?? 0) : ($practice_session->duration ?? 0) . ' mins'); ?></span>
                        <span class="clm-practice-date"><?php echo human_time_diff(strtotime($practice_session->practice_date)); ?> <?php _e('ago', 'choir-lyrics-manager'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>