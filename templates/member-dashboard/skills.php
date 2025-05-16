<?php
/**
 * Member Dashboard - Skills Tab
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
$skill_levels = $skills_instance->get_skill_levels();

// Filter skills by level if requested
$filter_level = isset($_GET['skill_level']) ? sanitize_text_field($_GET['skill_level']) : '';
if ($filter_level && array_key_exists($filter_level, $skill_levels)) {
    $member_skills = array_filter($member_skills, function($skill) use ($filter_level) {
        return $skill->skill_level === $filter_level;
    });
}

// Sort skills
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'title';
usort($member_skills, function($a, $b) use ($sort_by) {
    switch ($sort_by) {
        case 'level':
            $level_order = array('master' => 4, 'proficient' => 3, 'learning' => 2, 'novice' => 1);
            return $level_order[$b->skill_level] - $level_order[$a->skill_level];
            
        case 'practice':
            return $b->practice_count - $a->practice_count;
            
        case 'recent':
            return strtotime($b->last_practice_date) - strtotime($a->last_practice_date);
            
        default:
            return strcasecmp($a->lyric_title, $b->lyric_title);
    }
});

// Skill statistics
$skill_stats = array('novice' => 0, 'learning' => 0, 'proficient' => 0, 'master' => 0);
$total_practice_sessions = 0;
foreach ($member_skills as $skill) {
    if (isset($skill_stats[$skill->skill_level])) {
        $skill_stats[$skill->skill_level]++;
    }
    $total_practice_sessions += $skill->practice_count;
}
?>

<div class="clm-skills-tab">
    <div class="clm-skills-header">
        <h2><?php _e('My Skills Overview', 'choir-lyrics-manager'); ?></h2>
        
        <div class="clm-skills-summary">
            <div class="clm-summary-cards">
                <?php foreach ($skill_levels as $level => $info): ?>
                    <div class="clm-summary-card" style="border-color: <?php echo $info['color']; ?>">
                        <div class="clm-summary-icon" style="color: <?php echo $info['color']; ?>">
                            <span class="dashicons <?php echo $info['icon']; ?>"></span>
                        </div>
                        <div class="clm-summary-count"><?php echo $skill_stats[$level]; ?></div>
                        <div class="clm-summary-label"><?php echo $info['label']; ?></div>
                        <div class="clm-summary-desc"><?php echo $info['description']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="clm-skills-progress">
                <div class="clm-progress-bar">
                    <?php 
                    $total_skills = count($member_skills);
                    if ($total_skills > 0):
                        foreach ($skill_levels as $level => $info):
                            $percentage = ($skill_stats[$level] / $total_skills) * 100;
                            if ($percentage > 0):
                            ?>
                            <div class="clm-progress-segment" 
                                 style="width: <?php echo $percentage; ?>%; background-color: <?php echo $info['color']; ?>" 
                                 title="<?php echo $info['label'] . ': ' . $skill_stats[$level]; ?>">
                            </div>
                            <?php
                            endif;
                        endforeach;
                    endif;
                    ?>
                </div>
                <div class="clm-progress-labels">
                    <span><?php echo sprintf(__('%d Total Songs', 'choir-lyrics-manager'), $total_skills); ?></span>
                    <span><?php echo sprintf(__('%d Practice Sessions', 'choir-lyrics-manager'), $total_practice_sessions); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="clm-skills-filters">
        <div class="clm-filter-group">
            <label><?php _e('Filter by:', 'choir-lyrics-manager'); ?></label>
            <select id="clm-skill-filter" onchange="filterSkills(this.value)">
                <option value=""><?php _e('All Levels', 'choir-lyrics-manager'); ?></option>
                <?php foreach ($skill_levels as $level => $info): ?>
                    <option value="<?php echo $level; ?>" <?php selected($filter_level, $level); ?>>
                        <?php echo $info['label']; ?> (<?php echo $skill_stats[$level]; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="clm-filter-group">
            <label><?php _e('Sort by:', 'choir-lyrics-manager'); ?></label>
            <select id="clm-skill-sort" onchange="sortSkills(this.value)">
                <option value="title" <?php selected($sort_by, 'title'); ?>><?php _e('Song Title', 'choir-lyrics-manager'); ?></option>
                <option value="level" <?php selected($sort_by, 'level'); ?>><?php _e('Skill Level', 'choir-lyrics-manager'); ?></option>
                <option value="practice" <?php selected($sort_by, 'practice'); ?>><?php _e('Practice Count', 'choir-lyrics-manager'); ?></option>
                <option value="recent" <?php selected($sort_by, 'recent'); ?>><?php _e('Recently Practiced', 'choir-lyrics-manager'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="clm-skills-list">
        <?php if (empty($member_skills)): ?>
            <div class="clm-no-skills">
                <span class="dashicons dashicons-music"></span>
                <h3><?php _e('No skills recorded yet', 'choir-lyrics-manager'); ?></h3>
                <p><?php _e('Start practicing some songs to track your progress!', 'choir-lyrics-manager'); ?></p>
                <a href="<?php echo get_post_type_archive_link('clm_lyric'); ?>" class="clm-button">
                    <?php _e('Browse Songs', 'choir-lyrics-manager'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="clm-skills-grid">
                <?php foreach ($member_skills as $skill): 
                    $level_info = $skill_levels[$skill->skill_level];
                    ?>
                    <div class="clm-skill-card" data-level="<?php echo $skill->skill_level; ?>">
                        <div class="clm-skill-header">
                            <h3 class="clm-skill-title">
                                <a href="<?php echo get_permalink($skill->lyric_id); ?>">
                                    <?php echo esc_html($skill->lyric_title); ?>
                                </a>
                            </h3>
                            <div class="clm-skill-level" style="background-color: <?php echo $level_info['color']; ?>">
                                <span class="dashicons <?php echo $level_info['icon']; ?>"></span>
                                <?php echo $level_info['label']; ?>
                            </div>
                        </div>
                        
                        <div class="clm-skill-stats">
                            <div class="clm-stat">
                                <span class="dashicons dashicons-chart-line"></span>
                                <?php echo sprintf(__('%d practice sessions', 'choir-lyrics-manager'), $skill->practice_count); ?>
                            </div>
                            
                            <?php if ($skill->last_practice_date): ?>
                                <div class="clm-stat">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo sprintf(
                                        __('Last: %s', 'choir-lyrics-manager'),
                                        human_time_diff(strtotime($skill->last_practice_date), current_time('timestamp')) . ' ' . __('ago', 'choir-lyrics-manager')
                                    ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($skill->performance_count > 0): ?>
                                <div class="clm-stat">
                                    <span class="dashicons dashicons-microphone"></span>
                                    <?php echo sprintf(__('%d performances', 'choir-lyrics-manager'), $skill->performance_count); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="clm-skill-progress">
                            <div class="clm-progress-timeline">
                                <?php foreach ($skill_levels as $level => $info): ?>
                                    <div class="clm-timeline-step <?php echo $skill->skill_level === $level ? 'current' : ''; ?> <?php echo array_search($level, array_keys($skill_levels)) < array_search($skill->skill_level, array_keys($skill_levels)) ? 'completed' : ''; ?>">
                                        <span class="dashicons <?php echo $info['icon']; ?>"></span>
                                        <span class="clm-step-label"><?php echo $info['label']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="clm-skill-actions">
                            <a href="<?php echo get_permalink($skill->lyric_id); ?>" class="clm-button clm-button-small">
                                <?php _e('Practice', 'choir-lyrics-manager'); ?>
                            </a>
                            <button class="clm-button clm-button-small clm-button-secondary clm-view-details" data-skill-id="<?php echo $skill->id; ?>">
                                <?php _e('Details', 'choir-lyrics-manager'); ?>
                            </button>
                        </div>
                        
                        <?php if ($skill->teacher_notes): ?>
                            <div class="clm-teacher-notes">
                                <strong><?php _e('Teacher Notes:', 'choir-lyrics-manager'); ?></strong>
                                <p><?php echo esc_html($skill->teacher_notes); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Skills tab specific styles */
.clm-skills-header {
    margin-bottom: 30px;
}

.clm-skills-summary {
    margin-top: 20px;
}

.clm-summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.clm-summary-card {
    text-align: center;
    padding: 20px;
    border: 2px solid;
    border-radius: 8px;
    background: white;
}

.clm-summary-icon {
    font-size: 36px;
    margin-bottom: 10px;
}

.clm-summary-count {
    font-size: 32px;
    font-weight: bold;
    color: #333;
}

.clm-summary-label {
    font-size: 16px;
    font-weight: 500;
    color: #333;
}

.clm-summary-desc {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.clm-skills-progress {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.clm-progress-bar {
    height: 30px;
    background: #f0f0f0;
    border-radius: 15px;
    overflow: hidden;
    display: flex;
    margin-bottom: 10px;
}

.clm-progress-segment {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.clm-progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #666;
}

.clm-skills-filters {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.clm-filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.clm-filter-group label {
    font-weight: 500;
}

.clm-filter-group select {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.clm-skills-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.clm-skill-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}

.clm-skill-card:hover {
    border-color: #007cba;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.clm-skill-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.clm-skill-title {
    margin: 0;
    font-size: 18px;
}

.clm-skill-title a {
    text-decoration: none;
    color: #333;
}

.clm-skill-title a:hover {
    color: #007cba;
}

.clm-skill-level {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 20px;
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.clm-skill-level .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.clm-skill-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.clm-stat {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
}

.clm-stat .dashicons {
    color: #007cba;
}

.clm-skill-progress {
    margin-bottom: 15px;
}

.clm-progress-timeline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
}

.clm-timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    opacity: 0.4;
    position: relative;
}

.clm-timeline-step.completed,
.clm-timeline-step.current {
    opacity: 1;
}

.clm-timeline-step .dashicons {
    font-size: 24px;
    color: #999;
}

.clm-timeline-step.completed .dashicons {
    color: #46b450;
}

.clm-timeline-step.current .dashicons {
    color: #007cba;
}

.clm-step-label {
    font-size: 11px;
    text-align: center;
}

.clm-skill-actions {
    display: flex;
    gap: 10px;
}

.clm-button-small {
    padding: 5px 15px;
    font-size: 14px;
}

.clm-teacher-notes {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 14px;
}

.clm-teacher-notes p {
    margin: 5px 0 0;
}

.clm-no-skills {
    text-align: center;
    padding: 60px 20px;
}

.clm-no-skills .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.clm-no-skills h3 {
    color: #666;
    margin-bottom: 10px;
}

.clm-no-skills p {
    color: #999;
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .clm-skills-filters {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .clm-skills-grid {
        grid-template-columns: 1fr;
    }
    
    .clm-summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .clm-summary-cards {
        grid-template-columns: 1fr;
    }
    
    .clm-skill-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .clm-skill-actions {
        flex-direction: column;
    }
    
    .clm-button-small {
        width: 100%;
    }
}
</style>

<script>
function filterSkills(level) {
    const urlParams = new URLSearchParams(window.location.search);
    if (level) {
        urlParams.set('skill_level', level);
    } else {
        urlParams.delete('skill_level');
    }
    urlParams.set('tab', 'skills');
    window.location.search = urlParams.toString();
}

function sortSkills(sortBy) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('sort', sortBy);
    urlParams.set('tab', 'skills');
    window.location.search = urlParams.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle skill details modal
    const detailButtons = document.querySelectorAll('.clm-view-details');
    detailButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Implement modal or expand details here
            alert('Details functionality coming soon!');
        });
    });
});
</script>