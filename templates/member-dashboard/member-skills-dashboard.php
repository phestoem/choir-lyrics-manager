

<?php
/**
 * Member Skills Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$member_id = get_current_user_id();
$skills = new CLM_Skills('choir-lyrics-manager', CLM_VERSION);
$member_skills = $skills->get_member_skills($member_id);
$skill_levels = $skills->get_skill_levels();
?>

<div class="clm-member-skills-dashboard">
    <h2><?php _e('My Skill Progress', 'choir-lyrics-manager'); ?></h2>
    
    <!-- Skills Overview -->
    <div class="clm-skills-overview">
        <div class="clm-skill-stats">
            <?php
            $skill_counts = array_fill_keys(array_keys($skill_levels), 0);
            
            foreach ($member_skills as $skill) {
                $skill_counts[$skill->skill_level]++;
            }
            
            foreach ($skill_levels as $level_key => $level_info):
                ?>
                <div class="clm-skill-stat-box" style="border-color: <?php echo $level_info['color']; ?>">
                    <span class="dashicons <?php echo $level_info['icon']; ?>" style="color: <?php echo $level_info['color']; ?>"></span>
                    <div class="clm-skill-stat-content">
                        <span class="clm-skill-count"><?php echo $skill_counts[$level_key]; ?></span>
                        <span class="clm-skill-label"><?php echo esc_html($info['label']); ?></span>
                    </div>
                </div>
                <?php
            endforeach;
            ?>
        </div>
    </div>
    
    <!-- Skills Grid -->
    <div class="clm-skills-grid">
        <h3><?php _e('Song Skills', 'choir-lyrics-manager'); ?></h3>
        
        <div class="clm-skills-filter">
            <select id="clm-skill-filter">
                <option value=""><?php _e('All Skills', 'choir-lyrics-manager'); ?></option>
                <?php foreach ($skill_levels as $level_key => $level_info): ?>
                    <option value="<?php echo $level_key; ?>"><?php echo $level_info['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if (empty($member_skills)): ?>
            <p class="clm-no-skills"><?php _e('No skills recorded yet. Start practicing to track your progress!', 'choir-lyrics-manager'); ?></p>
        <?php else: ?>
            <div class="clm-skills-list">
                <?php foreach ($member_skills as $skill): 
                    $lyric = get_post($skill->lyric_id);
                    if (!$lyric) continue;
                    
                    $level_info = $skill_levels[$skill->skill_level];
                    ?>
                    <div class="clm-skill-item" data-skill-level="<?php echo $skill->skill_level; ?>">
                        <div class="clm-skill-header">
                            <h4><?php echo esc_html($lyric->post_title); ?></h4>
                            <span class="clm-skill-level" style="background-color: <?php echo $level_info['color']; ?>">
                                <?php echo $level_info['label']; ?>
                            </span>
                        </div>
                        
                        <div class="clm-skill-details">
                            <div class="clm-skill-progress">
                                <div class="clm-progress-bar">
                                    <div class="clm-progress-fill" style="width: <?php echo ($level_info['value'] / 4) * 100; ?>%; background-color: <?php echo $level_info['color']; ?>"></div>
                                </div>
                            </div>
                            
                            <div class="clm-skill-meta">
                                <span><?php _e('Practice Sessions:', 'choir-lyrics-manager'); ?> <?php echo $skill->practice_count; ?></span>
                                <span><?php _e('Total Time:', 'choir-lyrics-manager'); ?> <?php echo $this->format_minutes($skill->total_practice_minutes); ?></span>
                                <?php if ($skill->last_practice_date): ?>
                                    <span><?php _e('Last Practice:', 'choir-lyrics-manager'); ?> <?php echo human_time_diff(strtotime($skill->last_practice_date)); ?> ago</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($skill->goal_date): ?>
                                <div class="clm-skill-goal">
                                    <?php _e('Goal:', 'choir-lyrics-manager'); ?> 
                                    <?php echo date_i18n(get_option('date_format'), strtotime($skill->goal_date)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="clm-skill-actions">
                                <a href="<?php echo get_permalink($lyric->ID); ?>" class="clm-button clm-button-small">
                                    <?php _e('Practice', 'choir-lyrics-manager'); ?>
                                </a>
                                <button class="clm-button clm-button-small clm-set-goal" data-skill-id="<?php echo $skill->id; ?>">
                                    <?php _e('Set Goal', 'choir-lyrics-manager'); ?>
                                </button>
                                <?php if ($skill->achievement_badges): ?>
                                    <div class="clm-achievement-badges">
                                        <?php 
                                        $badges = maybe_unserialize($skill->achievement_badges);
                                        if (is_array($badges)) {
                                            foreach ($badges as $badge) {
                                                echo '<span class="clm-badge" title="' . esc_attr($badge['description']) . '">' . 
                                                     '<span class="dashicons ' . esc_attr($badge['icon']) . '"></span>' .
                                                     '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Practice Sessions -->
    <div class="clm-recent-practice">
        <h3><?php _e('Recent Practice Sessions', 'choir-lyrics-manager'); ?></h3>
        <?php
        $recent_practices = $skills->get_recent_practice_sessions($member_id, 5);
        
        if (empty($recent_practices)): ?>
            <p><?php _e('No recent practice sessions.', 'choir-lyrics-manager'); ?></p>
        <?php else: ?>
            <ul class="clm-practice-list">
                <?php foreach ($recent_practices as $practice): 
                    $lyric = get_post($practice->lyric_id);
                    ?>
                    <li>
                        <span class="clm-practice-title"><?php echo esc_html($lyric->post_title); ?></span>
                        <span class="clm-practice-duration"><?php echo $practice->duration; ?> <?php _e('minutes', 'choir-lyrics-manager'); ?></span>
                        <span class="clm-practice-date"><?php echo human_time_diff(strtotime($practice->practice_date)); ?> ago</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
.clm-member-skills-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.clm-skills-overview {
    margin-bottom: 30px;
}

.clm-skill-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.clm-skill-stat-box {
    background: white;
    border: 2px solid;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.clm-skill-stat-box .dashicons {
    font-size: 36px;
    width: 36px;
    height: 36px;
    margin-bottom: 10px;
}

.clm-skill-stat-content {
    display: flex;
    flex-direction: column;
}

.clm-skill-count {
    font-size: 2em;
    font-weight: bold;
}

.clm-skills-filter {
    margin-bottom: 20px;
}

.clm-skills-list {
    display: grid;
    gap: 20px;
}

.clm-skill-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.clm-skill-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.clm-skill-level {
    padding: 5px 15px;
    border-radius: 20px;
    color: white;
    font-size: 0.9em;
}

.clm-progress-bar {
    width: 100%;
    height: 10px;
    background: #f0f0f0;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 15px;
}

.clm-progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.clm-skill-meta {
    display: flex;
    gap: 20px;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 15px;
}

.clm-skill-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.clm-achievement-badges {
    display: flex;
    gap: 5px;
}

.clm-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #f0f0f0;
    color: #333;
}
</style>