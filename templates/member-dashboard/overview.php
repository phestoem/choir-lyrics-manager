<?php
/**
 * Member Dashboard - Overview Tab
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get skills instance
$skills_instance = new CLM_Skills('choir-lyrics-manager', CLM_VERSION);
$member_skills = $skills_instance->get_member_skills($member->ID);

// Get practice instance
$practice_instance = new CLM_Practice('choir-lyrics-manager', CLM_VERSION);
$practice_stats = $practice_instance->get_user_practice_stats($user_id);

// Get skill level counts
$skill_counts = array('novice' => 0, 'learning' => 0, 'proficient' => 0, 'master' => 0);
foreach ($member_skills as $skill) {
    if (isset($skill_counts[$skill->skill_level])) {
        $skill_counts[$skill->skill_level]++;
    }
}

// Get recent activity
$recent_skills = array_slice($member_skills, 0, 5);

// Get upcoming performances (if events module is enabled)
$upcoming_performances = array(); // This would be populated from events module
?>

<div class="clm-dashboard-overview">
    <div class="clm-overview-stats">
        <div class="clm-stat-box">
            <span class="dashicons dashicons-music"></span>
            <div class="clm-stat-content">
                <div class="clm-stat-value"><?php echo count($member_skills); ?></div>
                <div class="clm-stat-label"><?php _e('Songs Practiced', 'choir-lyrics-manager'); ?></div>
            </div>
        </div>
        
        <div class="clm-stat-box">
            <span class="dashicons dashicons-clock"></span>
            <div class="clm-stat-content">
                <div class="clm-stat-value"><?php echo number_format($practice_stats['total_time']); ?></div>
                <div class="clm-stat-label"><?php _e('Minutes Practiced', 'choir-lyrics-manager'); ?></div>
            </div>
        </div>
        
        <div class="clm-stat-box">
            <span class="dashicons dashicons-awards"></span>
            <div class="clm-stat-content">
                <div class="clm-stat-value"><?php echo $skill_counts['master']; ?></div>
                <div class="clm-stat-label"><?php _e('Songs Mastered', 'choir-lyrics-manager'); ?></div>
            </div>
        </div>
        
        <div class="clm-stat-box">
            <span class="dashicons dashicons-star-filled"></span>
            <div class="clm-stat-content">
                <div class="clm-stat-value"><?php echo $skill_counts['proficient']; ?></div>
                <div class="clm-stat-label"><?php _e('Songs Proficient', 'choir-lyrics-manager'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="clm-overview-sections">
        <div class="clm-overview-section">
            <h2><?php _e('Recent Activity', 'choir-lyrics-manager'); ?></h2>
            
            <?php if ($recent_skills): ?>
                <ul class="clm-activity-list">
                    <?php 
                    $skill_levels = $skills_instance->get_skill_levels();
                    foreach ($recent_skills as $skill): 
                        $level_info = $skill_levels[$skill->skill_level];
                        ?>
                        <li class="clm-activity-item">
                            <div class="clm-activity-icon" style="color: <?php echo $level_info['color']; ?>">
                                <span class="dashicons <?php echo $level_info['icon']; ?>"></span>
                            </div>
                            <div class="clm-activity-content">
                                <div class="clm-activity-title">
                                    <a href="<?php echo get_permalink($skill->lyric_id); ?>">
                                        <?php echo esc_html($skill->lyric_title); ?>
                                    </a>
                                </div>
                                <div class="clm-activity-meta">
                                    <?php 
                                    echo sprintf(
                                        __('%s - %d practice sessions', 'choir-lyrics-manager'),
                                        $level_info['label'],
                                        $skill->practice_count
                                    );
                                    
                                    if ($skill->last_practice_date) {
                                        echo ' • ' . sprintf(
                                            __('Last practiced %s', 'choir-lyrics-manager'),
                                            human_time_diff(strtotime($skill->last_practice_date), current_time('timestamp')) . ' ' . __('ago', 'choir-lyrics-manager')
                                        );
                                    }
                                    ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <p class="clm-view-all">
                    <a href="?tab=skills" class="clm-button clm-button-secondary">
                        <?php _e('View All Skills', 'choir-lyrics-manager'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p class="clm-no-activity"><?php _e('No activity yet. Start practicing some songs!', 'choir-lyrics-manager'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="clm-overview-section">
            <h2><?php _e('Upcoming', 'choir-lyrics-manager'); ?></h2>
            
            <?php if ($upcoming_performances): ?>
                <ul class="clm-upcoming-list">
                    <?php foreach ($upcoming_performances as $event): ?>
                        <li class="clm-upcoming-item">
                            <div class="clm-upcoming-date">
                                <?php echo date_i18n('M j', strtotime($event->event_date)); ?>
                            </div>
                            <div class="clm-upcoming-content">
                                <div class="clm-upcoming-title"><?php echo esc_html($event->post_title); ?></div>
                                <div class="clm-upcoming-meta">
                                    <?php echo esc_html($event->event_time); ?>
                                    <?php if ($event->event_location): ?>
                                        • <?php echo esc_html($event->event_location); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="clm-no-upcoming"><?php _e('No upcoming events scheduled.', 'choir-lyrics-manager'); ?></p>
            <?php endif; ?>
            
            <h3><?php _e('Practice Recommendations', 'choir-lyrics-manager'); ?></h3>
            <?php
            // Get least practiced songs
            $recommendations = $practice_instance->get_least_practiced_lyrics($user_id, 3);
            
            if ($recommendations): ?>
                <ul class="clm-recommendations-list">
                    <?php foreach ($recommendations as $item): ?>
                        <li class="clm-recommendation-item">
                            <a href="<?php echo get_permalink($item['lyric']->ID); ?>">
                                <?php echo esc_html($item['lyric']->post_title); ?>
                            </a>
                            <div class="clm-recommendation-reason">
                                <?php
                                if ($item['practice_time'] === 0) {
                                    _e('Not practiced yet', 'choir-lyrics-manager');
                                } elseif ($item['confidence'] <= 2) {
                                    _e('Low confidence level', 'choir-lyrics-manager');
                                } else {
                                    _e('Needs more practice', 'choir-lyrics-manager');
                                }
                                ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <p class="clm-view-all">
                    <a href="?tab=practice" class="clm-button clm-button-secondary">
                        <?php _e('Practice Now', 'choir-lyrics-manager'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p><?php _e('Keep up the good work!', 'choir-lyrics-manager'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Overview specific styles */
.clm-overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.clm-stat-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
}

.clm-stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.clm-stat-box .dashicons {
    font-size: 36px;
    width: 36px;
    height: 36px;
    color: #007cba;
}

.clm-stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #333;
}

.clm-stat-label {
    font-size: 14px;
    color: #666;
}

.clm-overview-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 40px;
}

.clm-overview-section h2 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.clm-activity-list,
.clm-upcoming-list,
.clm-recommendations-list {
    list-style: none;
    margin: 0 0 20px;
    padding: 0;
}

.clm-activity-item,
.clm-upcoming-item,
.clm-recommendation-item {
    display: flex;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.clm-activity-item:last-child,
.clm-upcoming-item:last-child,
.clm-recommendation-item:last-child {
    border-bottom: none;
}

.clm-activity-icon {
    font-size: 24px;
}

.clm-activity-content {
    flex: 1;
}

.clm-activity-title a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
}

.clm-activity-title a:hover {
    color: #007cba;
}

.clm-activity-meta,
.clm-upcoming-meta,
.clm-recommendation-reason {
    font-size: 13px;
    color: #666;
    margin-top: 3px;
}

.clm-upcoming-date {
    background: #007cba;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 14px;
    text-align: center;
    min-width: 50px;
}

.clm-view-all {
    margin-top: 15px;
    text-align: center;
}

.clm-no-activity,
.clm-no-upcoming {
    color: #666;
    font-style: italic;
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .clm-overview-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .clm-overview-sections {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .clm-overview-stats {
        grid-template-columns: 1fr;
    }
}
</style>