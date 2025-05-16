<?php
/**
 * Admin functionality additions for Members and Skills
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Admin_Members {

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
     * Add admin menu items for members
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Add submenu under Members for Skills Management
        add_submenu_page(
            'edit.php?post_type=clm_member',
            __('Skills Management', 'choir-lyrics-manager'),
            __('Skills Management', 'choir-lyrics-manager'),
            'manage_options',
            'clm-skills-management',
            array($this, 'render_skills_management_page')
        );

        // Add submenu for Member Reports
        add_submenu_page(
            'edit.php?post_type=clm_member',
            __('Member Reports', 'choir-lyrics-manager'),
            __('Reports', 'choir-lyrics-manager'),
            'manage_options',
            'clm-member-reports',
            array($this, 'render_member_reports_page')
        );

        // Add submenu for Practice Assignments
        add_submenu_page(
            'edit.php?post_type=clm_member',
            __('Practice Assignments', 'choir-lyrics-manager'),
            __('Assignments', 'choir-lyrics-manager'),
            'manage_options',
            'clm-practice-assignments',
            array($this, 'render_practice_assignments_page')
        );
    }

    /**
     * Render skills management page
     *
     * @since    1.0.0
     */
    public function render_skills_management_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        $skills_instance = new CLM_Skills($this->plugin_name, $this->version);
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['member_id']) && isset($_GET['lyric_id'])) {
            $member_id = intval($_GET['member_id']);
            $lyric_id = intval($_GET['lyric_id']);
            
            if ($_GET['action'] === 'edit' && isset($_POST['submit'])) {
                // Handle skill update
                $skill_data = array(
                    'skill_level' => sanitize_text_field($_POST['skill_level']),
                    'teacher_notes' => sanitize_textarea_field($_POST['teacher_notes']),
                    'assessed_by' => get_current_user_id(),
                );
                
                $skills_instance->update_member_skill($member_id, $lyric_id, $skill_data);
                echo '<div class="notice notice-success"><p>' . __('Skill updated successfully.', 'choir-lyrics-manager') . '</p></div>';
            }
        }
        
        // Get filter parameters
        $selected_member = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;
        $selected_lyric = isset($_GET['lyric_id']) ? intval($_GET['lyric_id']) : 0;
        $selected_level = isset($_GET['skill_level']) ? sanitize_text_field($_GET['skill_level']) : '';
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Filters -->
            <form method="get" action="">
                <input type="hidden" name="post_type" value="clm_member">
                <input type="hidden" name="page" value="clm-skills-management">
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Filter by Member', 'choir-lyrics-manager'); ?></th>
                        <td>
                            <select name="member_id">
                                <option value=""><?php _e('All Members', 'choir-lyrics-manager'); ?></option>
                                <?php
                                $members = get_posts(array(
                                    'post_type' => 'clm_member',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC',
                                ));
                                
                                foreach ($members as $member) {
                                    ?>
                                    <option value="<?php echo $member->ID; ?>" <?php selected($selected_member, $member->ID); ?>>
                                        <?php echo esc_html($member->post_title); ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Filter by Song', 'choir-lyrics-manager'); ?></th>
                        <td>
                            <select name="lyric_id">
                                <option value=""><?php _e('All Songs', 'choir-lyrics-manager'); ?></option>
                                <?php
                                $lyrics = get_posts(array(
                                    'post_type' => 'clm_lyric',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC',
                                ));
                                
                                foreach ($lyrics as $lyric) {
                                    ?>
                                    <option value="<?php echo $lyric->ID; ?>" <?php selected($selected_lyric, $lyric->ID); ?>>
                                        <?php echo esc_html($lyric->post_title); ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Filter by Skill Level', 'choir-lyrics-manager'); ?></th>
                        <td>
                            <select name="skill_level">
                                <option value=""><?php _e('All Levels', 'choir-lyrics-manager'); ?></option>
                                <?php
                                $skill_levels = $skills_instance->get_skill_levels();
                                foreach ($skill_levels as $level => $info) {
                                    ?>
                                    <option value="<?php echo $level; ?>" <?php selected($selected_level, $level); ?>>
                                        <?php echo esc_html($info['label']); ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Filter', 'choir-lyrics-manager'), 'secondary', 'filter', false); ?>
            </form>
            
            <!-- Skills Table -->
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'clm_member_skills';
            
            $where_clauses = array('1=1');
            if ($selected_member) {
                $where_clauses[] = $wpdb->prepare('s.member_id = %d', $selected_member);
            }
            if ($selected_lyric) {
                $where_clauses[] = $wpdb->prepare('s.lyric_id = %d', $selected_lyric);
            }
            if ($selected_level) {
                $where_clauses[] = $wpdb->prepare('s.skill_level = %s', $selected_level);
            }
            
            $where_sql = implode(' AND ', $where_clauses);
            
            $skills = $wpdb->get_results("
                SELECT s.*, 
                       m.post_title as member_name,
                       l.post_title as lyric_title
                FROM $table_name s
                LEFT JOIN {$wpdb->posts} m ON s.member_id = m.ID
                LEFT JOIN {$wpdb->posts} l ON s.lyric_id = l.ID
                WHERE $where_sql
                ORDER BY m.post_title, l.post_title
            ");
            
            if ($skills) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Member', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Song', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Skill Level', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Practice Count', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Last Practice', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Performances', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Actions', 'choir-lyrics-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($skills as $skill) {
                            $level_info = $skill_levels[$skill->skill_level];
                            ?>
                            <tr>
                                <td><?php echo esc_html($skill->member_name); ?></td>
                                <td><?php echo esc_html($skill->lyric_title); ?></td>
                                <td>
                                    <span style="color: <?php echo $level_info['color']; ?>;">
                                        <span class="dashicons <?php echo $level_info['icon']; ?>"></span>
                                        <?php echo esc_html($level_info['label']); ?>
                                    </span>
                                </td>
                                <td><?php echo intval($skill->practice_count); ?></td>
                                <td>
                                    <?php 
                                    if ($skill->last_practice_date) {
                                        echo human_time_diff(strtotime($skill->last_practice_date), current_time('timestamp')) . ' ' . __('ago', 'choir-lyrics-manager');
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php echo intval($skill->performance_count); ?></td>
                                <td>
                                    <a href="?post_type=clm_member&page=clm-skills-management&action=edit&member_id=<?php echo $skill->member_id; ?>&lyric_id=<?php echo $skill->lyric_id; ?>" class="button button-small">
                                        <?php _e('Edit', 'choir-lyrics-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                ?>
                <p><?php _e('No skills found matching your criteria.', 'choir-lyrics-manager'); ?></p>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render member reports page
     *
     * @since    1.0.0
     */
    public function render_member_reports_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $skills_instance = new CLM_Skills($this->plugin_name, $this->version);
        $stats = $skills_instance->get_skill_statistics();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="clm-admin-stats-grid">
                <!-- Overall Statistics -->
                <div class="clm-admin-stat-box">
                    <h3><?php _e('Skill Distribution', 'choir-lyrics-manager'); ?></h3>
                    <table class="widefat">
                        <?php
                        $skill_levels = $skills_instance->get_skill_levels();
                        foreach ($skill_levels as $level => $info) {
                            $count = isset($stats['skill_distribution'][$level]) ? $stats['skill_distribution'][$level] : 0;
                            ?>
                            <tr>
                                <td>
                                    <span style="color: <?php echo $info['color']; ?>;">
                                        <span class="dashicons <?php echo $info['icon']; ?>"></span>
                                        <?php echo esc_html($info['label']); ?>
                                    </span>
                                </td>
                                <td><?php echo $count; ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </div>
                
                <!-- Top Practiced Songs -->
                <div class="clm-admin-stat-box">
                    <h3><?php _e('Most Practiced Songs', 'choir-lyrics-manager'); ?></h3>
                    <table class="widefat">
                        <?php
                        if (!empty($stats['most_practiced'])) {
                            foreach ($stats['most_practiced'] as $song) {
                                $lyric = get_post($song->lyric_id);
                                if ($lyric) {
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($lyric->post_title); ?></td>
                                        <td><?php echo sprintf(__('%d sessions', 'choir-lyrics-manager'), $song->total_practice); ?></td>
                                        <td><?php echo sprintf(__('%d members', 'choir-lyrics-manager'), $song->member_count); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                        } else {
                            ?>
                            <tr><td colspan="3"><?php _e('No practice data available.', 'choir-lyrics-manager'); ?></td></tr>
                            <?php
                        }
                        ?>
                    </table>
                </div>
                
                <!-- Recent Achievements -->
                <div class="clm-admin-stat-box">
                    <h3><?php _e('Recent Achievements', 'choir-lyrics-manager'); ?></h3>
                    <table class="widefat">
                        <?php
                        if (!empty($stats['recent_achievements'])) {
                            foreach ($stats['recent_achievements'] as $achievement) {
                                $member = get_post($achievement->member_id);
                                $lyric = get_post($achievement->lyric_id);
                                if ($member && $lyric) {
                                    $level_info = $skill_levels[$achievement->skill_level];
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($member->post_title); ?></td>
                                        <td><?php echo esc_html($lyric->post_title); ?></td>
                                        <td>
                                            <span style="color: <?php echo $level_info['color']; ?>;">
                                                <?php echo esc_html($level_info['label']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                        } else {
                            ?>
                            <tr><td colspan="3"><?php _e('No recent achievements.', 'choir-lyrics-manager'); ?></td></tr>
                            <?php
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
        
        <style>
        .clm-admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .clm-admin-stat-box {
            background: white;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        
        .clm-admin-stat-box h3 {
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .clm-admin-stat-box table {
            margin: 0;
        }
        </style>
        <?php
    }

    /**
     * Render practice assignments page
     *
     * @since    1.0.0
     */
    public function render_practice_assignments_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Practice assignments feature coming soon!', 'choir-lyrics-manager'); ?></p>
            </div>
            
            <p><?php _e('This feature will allow you to:', 'choir-lyrics-manager'); ?></p>
            <ul>
                <li><?php _e('Assign specific songs to members or voice groups', 'choir-lyrics-manager'); ?></li>
                <li><?php _e('Set practice goals and deadlines', 'choir-lyrics-manager'); ?></li>
                <li><?php _e('Track assignment completion', 'choir-lyrics-manager'); ?></li>
                <li><?php _e('Send practice reminders', 'choir-lyrics-manager'); ?></li>
            </ul>
        </div>
        <?php
    }
}