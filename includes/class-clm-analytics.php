<?php
/**
 * Analytics functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Analytics {

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
     * Get analytics data via AJAX
     *
     * @since    1.0.0
     */
    public function get_analytics_data() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view analytics.', 'choir-lyrics-manager')));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clm_analytics_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
        }
        
        // Check if user can view analytics
        if (!$this->can_view_analytics()) {
            wp_send_json_error(array('message' => __('You do not have permission to view analytics.', 'choir-lyrics-manager')));
        }
        
        $data_type = isset($_POST['data_type']) ? sanitize_key($_POST['data_type']) : 'overview';
        $period = isset($_POST['period']) ? sanitize_key($_POST['period']) : 'month';
        
        $data = $this->get_data_for_chart($data_type, $period);
        
        wp_send_json_success($data);
    }

    /**
     * Check if current user can view analytics
     *
     * @since     1.0.0
     * @return    boolean    Whether the user can view analytics.
     */
    private function can_view_analytics() {
        $allowed_roles = $this->get_analytics_roles();
        
        foreach ($allowed_roles as $role) {
            if (current_user_can($role)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get roles allowed to view analytics
     *
     * @since     1.0.0
     * @return    array    Array of role slugs.
     */
    private function get_analytics_roles() {
        $default_roles = array('administrator', 'editor', 'clm_manager');
        $option_roles = get_option('clm_analytics_roles', array());
        
        return !empty($option_roles) ? $option_roles : $default_roles;
    }
    
    /**
     * Get data for chart
     *
     * @since     1.0.0
     * @param     string    $data_type    Type of data to get.
     * @param     string    $period       Time period.
     * @return    array                   Data for chart.
     */
    private function get_data_for_chart($data_type, $period) {
        switch ($data_type) {
            case 'practice_time':
                return $this->get_practice_time_data($period);
                break;
                
            case 'genre_distribution':
                return $this->get_genre_distribution_data();
                break;
                
            case 'difficulty_distribution':
                return $this->get_difficulty_distribution_data();
                break;
                
            case 'language_distribution':
                return $this->get_language_distribution_data();
                break;
                
            case 'most_practiced':
                return $this->get_most_practiced_data();
                break;
                
            case 'user_activity':
                return $this->get_user_activity_data($period);
                break;
                
            case 'submission_trends':
                return $this->get_submission_trends_data($period);
                break;
                
            case 'overview':
            default:
                return $this->get_overview_data();
                break;
        }
    }
    
    /**
     * Get overview data
     *
     * @since     1.0.0
     * @return    array    Overview data.
     */
    private function get_overview_data() {
        // Get total counts
        $post_counts = wp_count_posts('clm_lyric');
		$lyrics_count = isset($post_counts->publish) ? $post_counts->publish : 0;
		$post_counts = wp_count_posts('clm_album');
		$albums_count = isset($post_counts->publish) ? $post_counts->publish : 0;

		$post_counts = wp_count_posts('clm_practice_log');
		$practice_logs_count = isset($post_counts->publish) ? $post_counts->publish : 0;       
        // Get user counts
        $user_query = new WP_User_Query(array(
            'meta_key' => 'clm_total_practice_time',
            'meta_compare' => 'EXISTS',
        ));
        $active_users_count = $user_query->get_total();
        
        // Get total practice time across all users
        global $wpdb;
        $total_practice_time = $wpdb->get_var(
            "SELECT SUM(meta_value) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = 'clm_total_practice_time'"
        );
        
        // Get recent lyrics
        $recent_lyrics = get_posts(array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        $recent_lyrics_data = array();
        foreach ($recent_lyrics as $lyric) {
            $recent_lyrics_data[] = array(
                'id' => $lyric->ID,
                'title' => $lyric->post_title,
                'date' => get_the_date('Y-m-d', $lyric->ID),
                'author' => get_the_author_meta('display_name', $lyric->post_author),
                'url' => get_permalink($lyric->ID),
            );
        }
        
        // Get recent practice logs
        $recent_logs = get_posts(array(
            'post_type' => 'clm_practice_log',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        $recent_logs_data = array();
        foreach ($recent_logs as $log) {
            $lyric_id = get_post_meta($log->ID, '_clm_lyric_id', true);
            $lyric_title = get_the_title($lyric_id);
            
            $recent_logs_data[] = array(
                'id' => $log->ID,
                'date' => get_the_date('Y-m-d', $log->ID),
                'user' => get_the_author_meta('display_name', $log->post_author),
                'lyric_id' => $lyric_id,
                'lyric_title' => $lyric_title,
                'duration' => get_post_meta($log->ID, '_clm_duration', true),
            );
        }
        
        return array(
            'lyrics_count' => $lyrics_count,
            'albums_count' => $albums_count,
            'practice_logs_count' => $practice_logs_count,
            'active_users_count' => $active_users_count,
            'total_practice_time' => intval($total_practice_time),
            'recent_lyrics' => $recent_lyrics_data,
            'recent_logs' => $recent_logs_data,
        );
    }
    
    /**
     * Get practice time data
     *
     * @since     1.0.0
     * @param     string    $period    Time period.
     * @return    array                Practice time data.
     */
    private function get_practice_time_data($period) {
        global $wpdb;
        
        $date_format = '%Y-%m-%d';
        $group_by = 'date';
        $limit = 30;
        
        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
                
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
                
            case 'year':
                $start_date = date('Y-m-d', strtotime('-1 year'));
                $date_format = '%Y-%m';
                $group_by = 'month';
                $limit = 12;
                break;
                
            case 'all':
                $start_date = date('Y-m-d', strtotime('-5 years')); // Arbitrary far back date
                $date_format = '%Y-%m';
                $group_by = 'month';
                $limit = 60; // Up to 5 years of data
                break;
                
            default:
                $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        
        // Get practice logs grouped by date
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    DATE_FORMAT(pm.meta_value, %s) as $group_by,
                    SUM(pm2.meta_value) as duration
                FROM 
                    {$wpdb->posts} p
                    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_clm_practice_date'
                    JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_clm_duration'
                WHERE 
                    p.post_type = 'clm_practice_log'
                    AND p.post_status = 'publish'
                    AND pm.meta_value >= %s
                GROUP BY 
                    $group_by
                ORDER BY 
                    $group_by ASC
                LIMIT %d",
                $date_format,
                $start_date,
                $limit
            )
        );
        
        $data = array();
        
        // Fill in all dates/months in the range
        $current = new DateTime($start_date);
        $end = new DateTime();
        $interval = new DateInterval('P1D');
        
        if ($group_by === 'month') {
            $interval = new DateInterval('P1M');
        }
        
        while ($current <= $end) {
            $period_key = $current->format($group_by === 'date' ? 'Y-m-d' : 'Y-m');
            $data[$period_key] = 0;
            $current->add($interval);
        }
        
        // Add actual data
        foreach ($results as $row) {
            $data[$row->$group_by] = intval($row->duration);
        }
        
        $chart_data = array(
            'labels' => array_keys($data),
            'datasets' => array(
                array(
                    'label' => __('Practice Time (minutes)', 'choir-lyrics-manager'),
                    'data' => array_values($data),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                )
            )
        );
        
        return array(
            'type' => 'bar',
            'data' => $chart_data,
            'options' => array(
                'scales' => array(
                    'yAxes' => array(
                        array(
                            'ticks' => array(
                                'beginAtZero' => true
                            )
                        )
                    )
                )
            )
        );
    }
    
    /**
     * Get genre distribution data
     *
     * @since     1.0.0
     * @return    array    Genre distribution data.
     */
    private function get_genre_distribution_data() {
        $genres = get_terms(array(
            'taxonomy' => 'clm_genre',
            'hide_empty' => true,
        ));
        
        $genre_data = array();
        $colors = array(
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
        );
        
        $labels = array();
        $values = array();
        $background_colors = array();
        
        foreach ($genres as $index => $genre) {
            $labels[] = $genre->name;
            $values[] = $genre->count;
            $background_colors[] = $colors[$index % count($colors)];
        }
        
        $chart_data = array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $values,
                    'backgroundColor' => $background_colors,
                )
            )
        );
        
        return array(
            'type' => 'pie',
            'data' => $chart_data,
            'options' => array(
                'responsive' => true,
                'legend' => array(
                    'position' => 'right',
                ),
                'title' => array(
                    'display' => true,
                    'text' => __('Lyrics by Genre', 'choir-lyrics-manager')
                )
            )
        );
    }
    
    /**
     * Get difficulty distribution data
     *
     * @since     1.0.0
     * @return    array    Difficulty distribution data.
     */
    private function get_difficulty_distribution_data() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT 
                pm.meta_value as difficulty,
                COUNT(*) as count
            FROM 
                {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_clm_difficulty'
            WHERE 
                p.post_type = 'clm_lyric'
                AND p.post_status = 'publish'
                AND pm.meta_value BETWEEN 1 AND 5
            GROUP BY 
                pm.meta_value
            ORDER BY 
                pm.meta_value ASC"
        );
        
        $difficulty_labels = array(
            1 => __('Beginner', 'choir-lyrics-manager'),
            2 => __('Easy', 'choir-lyrics-manager'),
            3 => __('Intermediate', 'choir-lyrics-manager'),
            4 => __('Advanced', 'choir-lyrics-manager'),
            5 => __('Expert', 'choir-lyrics-manager'),
        );
        
        $labels = array();
        $values = array();
        
        foreach (range(1, 5) as $level) {
            $labels[] = $difficulty_labels[$level];
            $values[] = 0;
        }
        
        foreach ($results as $row) {
            $index = intval($row->difficulty) - 1;
            if (isset($values[$index])) {
                $values[$index] = intval($row->count);
            }
        }
        
        $chart_data = array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Number of Lyrics', 'choir-lyrics-manager'),
                    'data' => $values,
                    'backgroundColor' => array(
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                    ),
                    'borderWidth' => 1
                )
            )
        );
        
        return array(
            'type' => 'bar',
            'data' => $chart_data,
            'options' => array(
                'scales' => array(
                    'yAxes' => array(
                        array(
                            'ticks' => array(
                                'beginAtZero' => true
                            )
                        )
                    )
                ),
                'title' => array(
                    'display' => true,
                    'text' => __('Lyrics by Difficulty Level', 'choir-lyrics-manager')
                )
            )
        );
    }
    
    /**
     * Get language distribution data
     *
     * @since     1.0.0
     * @return    array    Language distribution data.
     */
    private function get_language_distribution_data() {
        $languages = get_terms(array(
            'taxonomy' => 'clm_language',
            'hide_empty' => true,
        ));
        
        $labels = array();
        $values = array();
        
        foreach ($languages as $language) {
            $labels[] = $language->name;
            $values[] = $language->count;
        }
        
        $chart_data = array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Number of Lyrics', 'choir-lyrics-manager'),
                    'data' => $values,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                )
            )
        );
        
        return array(
            'type' => 'horizontalBar',
            'data' => $chart_data,
            'options' => array(
                'scales' => array(
                    'xAxes' => array(
                        array(
                            'ticks' => array(
                                'beginAtZero' => true
                            )
                        )
                    )
                ),
                'title' => array(
                    'display' => true,
                    'text' => __('Lyrics by Language', 'choir-lyrics-manager')
                )
            )
        );
    }
    
    /**
     * Get most practiced lyrics data
     *
     * @since     1.0.0
     * @return    array    Most practiced lyrics data.
     */
    private function get_most_practiced_data() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT 
                pm.meta_value as lyric_id,
                SUM(pm2.meta_value) as total_duration,
                COUNT(*) as practice_sessions
            FROM 
                {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_clm_lyric_id'
                JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_clm_duration'
            WHERE 
                p.post_type = 'clm_practice_log'
                AND p.post_status = 'publish'
            GROUP BY 
                pm.meta_value
            ORDER BY 
                total_duration DESC
            LIMIT 10"
        );
        
        $labels = array();
        $duration_values = array();
        $sessions_values = array();
        
        foreach ($results as $row) {
            $lyric_title = get_the_title($row->lyric_id);
            
            if (!$lyric_title) {
                continue; // Skip if lyric doesn't exist
            }
            
            $labels[] = $lyric_title;
            $duration_values[] = intval($row->total_duration);
            $sessions_values[] = intval($row->practice_sessions);
        }
        
        $chart_data = array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Total Practice Time (minutes)', 'choir-lyrics-manager'),
                    'data' => $duration_values,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y-axis-1'
                ),
                array(
                    'label' => __('Practice Sessions', 'choir-lyrics-manager'),
                    'data' => $sessions_values,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.8)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y-axis-2'
                )
            )
        );
        
        return array(
            'type' => 'bar',
            'data' => $chart_data,
            'options' => array(
                'scales' => array(
                    'yAxes' => array(
                        array(
                            'id' => 'y-axis-1',
                            'position' => 'left',
                            'ticks' => array(
                                'beginAtZero' => true
                            ),
                            'scaleLabel' => array(
                                'display' => true,
                                'labelString' => __('Practice Time (minutes)', 'choir-lyrics-manager')
                            )
                        ),
                        array(
                            'id' => 'y-axis-2',
                            'position' => 'right',
                            'ticks' => array(
                                'beginAtZero' => true
                            ),
                            'scaleLabel' => array(
                                'display' => true,
                                'labelString' => __('Practice Sessions', 'choir-lyrics-manager')
                            )
                        )
                    )
                ),
                'title' => array(
                    'display' => true,
                    'text' => __('Most Practiced Lyrics', 'choir-lyrics-manager')
                )
            )
        );
    }
    
    /**
     * Get user activity data
     *
     * @since     1.0.0
     * @param     string    $period    Time period.
     * @return    array                User activity data.
     */
    private function get_user_activity_data($period) {
        global $wpdb;
        
        $date_format = '%Y-%m-%d';
        $group_by = 'date';
        $limit = 30;
        
        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
                
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
                
            case 'year':
                $start_date = date('Y-m-d', strtotime('-1 year'));
                $date_format = '%Y-%m';
                $group_by = 'month';
                $limit = 12;
                break;
                
            case 'all':
                $start_date = date('Y-m-d', strtotime('-5 years')); // Arbitrary far back date
                $date_format = '%Y-%m';
                $group_by = 'month';
                $limit = 60; // Up to 5 years of data
                break;
                
            default:
                $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        
        // Get active users by date
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    DATE_FORMAT(p.post_date, %s) as $group_by,
                    COUNT(DISTINCT p.post_author) as active_users
                FROM 
                    {$wpdb->posts} p
                WHERE 
                    p.post_type = 'clm_practice_log'
                    AND p.post_status = 'publish'
                    AND p.post_date >= %s
                GROUP BY 
                    $group_by
                ORDER BY 
                    $group_by ASC
                LIMIT %d",
                $date_format,
                $start_date,
                $limit
            )
        );
        
        $data = array();
        
        // Fill in all dates/months in the range
        $current = new DateTime($start_date);
        $end = new DateTime();
        $interval = new DateInterval('P1D');
        
        if ($group_by === 'month') {
            $interval = new DateInterval('P1M');
        }
        
        while ($current <= $end) {
            $period_key = $current->format($group_by === 'date' ? 'Y-m-d' : 'Y-m');
            $data[$period_key] = 0;
            $current->add($interval);
        }
        
        // Add actual data
        foreach ($results as $row) {
            $data[$row->$group_by] = intval($row->active_users);
        }
        
        $chart_data = array(
            'labels' => array_keys($data),
            'datasets' => array(
                array(
                    'label' => __('Active Users', 'choir-lyrics-manager'),
                    'data' => array_values($data),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                    'fill' => true,
                )
            )
        );
        
        return array(
            'type' => 'line',
            'data' => $chart_data,
            'options' => array(
                'scales' => array(
                    'yAxes' => array(
                        array(
                            'ticks' => array(
                                'beginAtZero' => true,
                                'stepSize' => 1
                            )
                        )
                    )
                ),
                'title' => array(
                    'display' => true,
                    'text' => __('Active Users Over Time', 'choir-lyrics-manager')
                )
            )
        );
    }
    
    /**
     * Get submission trends data
     *
     * @since     1.0.0
     * @param     string    $period    Time period.
     * @return    array                Submission trends data.
     */
    private function get_submission_trends_data($period) {
        global $wpdb;
        
        $date_format = '%Y-%m-%d';
        $group_by = 'date';
        $limit = 30;
        
        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
                
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
                
            case 'year':
                $start_date = date('Y-m-d', strtotime('-1 year'));
                $date_format = '%Y-%m';
                $group_by = 'month';
                $limit = 12;
                break;
                
            case 'all':
                $start_date = date('Y-m-d', strtotime('-5 years')); // Arbitrary far back date
                $date_format = '%Y-%m';
                $group_by = 'month';
                $limit = 60; // Up to 5 years of data
                break;
                
            default:
                $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        
        // Get submitted lyrics by date
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    DATE_FORMAT(p.post_date, %s) as $group_by,
                    COUNT(*) as submissions
                FROM 
                    {$wpdb->posts} p
                WHERE 
                    p.post_type = 'clm_lyric'
                    AND p.post_status IN ('publish', 'pending')
                    AND p.post_date >= %s
                GROUP BY 
                    $group_by
                ORDER BY 
                    $group_by ASC
                LIMIT %d",
                $date_format,
                $start_date,
                $limit
            )
        );
        
        $data = array();
        
        // Fill in all dates/months in the range
        $current = new DateTime($start_date);
        $end = new DateTime();
        $interval = new DateInterval('P1D');
        
        if ($group_by === 'month') {
            $interval = new DateInterval('P1M');
        }
        
        while ($current <= $end) {
            $period_key = $current->format($group_by === 'date' ? 'Y-m-d' : 'Y-m');
            $data[$period_key] = 0;
            $current->add($interval);
        }
        
        // Add actual data
        foreach ($results as $row) {
            $data[$row->$group_by] = intval($row->submissions);
        }
        
        $chart_data = array(
            'labels' => array_keys($data),
            'datasets' => array(
                array(
                    'label' => __('Lyric Submissions', 'choir-lyrics-manager'),
                    'data' => array_values($data),
                    'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                    'borderColor' => 'rgba(153, 102, 255, 1)',
                    'borderWidth' => 1
                )
            )
        );
        
        return array(
            'type' => 'bar',
            'data' => $chart_data,
            'options' => array(
                'scales' => array(
                    'yAxes' => array(
                        array(
                            'ticks' => array(
                                'beginAtZero' => true,
                                'stepSize' => 1
                            )
                        )
                    )
                ),
                'title' => array(
                    'display' => true,
                    'text' => __('Lyric Submissions Over Time', 'choir-lyrics-manager')
                )
            )
        );
    }
    
    /**
     * Render analytics dashboard
     *
     * @since     1.0.0
     * @return    string    HTML for the analytics dashboard.
     */
    public function render_analytics_dashboard() {
        if (!$this->can_view_analytics()) {
            return '<div class="clm-error">' . __('You do not have permission to view analytics.', 'choir-lyrics-manager') . '</div>';
        }
        
        $overview_data = $this->get_overview_data();
        
        ob_start();
        ?>
        <div class="clm-analytics-dashboard">
            <h2><?php _e('Choir Lyrics Analytics', 'choir-lyrics-manager'); ?></h2>
            
            <div class="clm-dashboard-summary">
                <div class="clm-summary-box">
                    <div class="clm-summary-icon">
                        <span class="dashicons dashicons-format-audio"></span>
                    </div>
                    <div class="clm-summary-content">
                        <div class="clm-summary-value"><?php echo $overview_data['lyrics_count']; ?></div>
                        <div class="clm-summary-label"><?php _e('Lyrics', 'choir-lyrics-manager'); ?></div>
                    </div>
                </div>
                
                <div class="clm-summary-box">
                    <div class="clm-summary-icon">
                        <span class="dashicons dashicons-album"></span>
                    </div>
                    <div class="clm-summary-content">
                        <div class="clm-summary-value"><?php echo $overview_data['albums_count']; ?></div>
                        <div class="clm-summary-label"><?php _e('Albums', 'choir-lyrics-manager'); ?></div>
                    </div>
                </div>
                
                <div class="clm-summary-box">
                    <div class="clm-summary-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="clm-summary-content">
                        <div class="clm-summary-value"><?php echo $overview_data['practice_logs_count']; ?></div>
                        <div class="clm-summary-label"><?php _e('Practice Logs', 'choir-lyrics-manager'); ?></div>
                    </div>
                </div>
                
                <div class="clm-summary-box">
                    <div class="clm-summary-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="clm-summary-content">
                        <div class="clm-summary-value"><?php echo $overview_data['active_users_count']; ?></div>
                        <div class="clm-summary-label"><?php _e('Active Users', 'choir-lyrics-manager'); ?></div>
                    </div>
                </div>
                
                <div class="clm-summary-box">
                    <div class="clm-summary-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="clm-summary-content">
                        <div class="clm-summary-value"><?php echo $this->format_duration($overview_data['total_practice_time']); ?></div>
                        <div class="clm-summary-label"><?php _e('Total Practice Time', 'choir-lyrics-manager'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="clm-chart-container-wrap">
                <div class="clm-chart-filters">
                    <div class="clm-filter-group">
                        <label for="clm-chart-type"><?php _e('Chart Type:', 'choir-lyrics-manager'); ?></label>
                        <select id="clm-chart-type">
                            <option value="practice_time"><?php _e('Practice Time', 'choir-lyrics-manager'); ?></option>
                            <option value="genre_distribution"><?php _e('Genre Distribution', 'choir-lyrics-manager'); ?></option>
                            <option value="difficulty_distribution"><?php _e('Difficulty Distribution', 'choir-lyrics-manager'); ?></option>
                            <option value="language_distribution"><?php _e('Language Distribution', 'choir-lyrics-manager'); ?></option>
                            <option value="most_practiced"><?php _e('Most Practiced Lyrics', 'choir-lyrics-manager'); ?></option>
                            <option value="user_activity"><?php _e('User Activity', 'choir-lyrics-manager'); ?></option>
                            <option value="submission_trends"><?php _e('Submission Trends', 'choir-lyrics-manager'); ?></option>
                        </select>
                    </div>
                    
                    <div class="clm-filter-group">
                        <label for="clm-chart-period"><?php _e('Time Period:', 'choir-lyrics-manager'); ?></label>
                        <select id="clm-chart-period">
                            <option value="week"><?php _e('Last 7 Days', 'choir-lyrics-manager'); ?></option>
                            <option value="month" selected><?php _e('Last 30 Days', 'choir-lyrics-manager'); ?></option>
                            <option value="year"><?php _e('Last Year', 'choir-lyrics-manager'); ?></option>
                            <option value="all"><?php _e('All Time', 'choir-lyrics-manager'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="clm-chart-container">
                    <canvas id="clm-analytics-chart"></canvas>
                </div>
            </div>
            
            <div class="clm-dashboard-tables">
                <div class="clm-dashboard-table clm-recent-lyrics">
                    <h3><?php _e('Recent Lyrics', 'choir-lyrics-manager'); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'choir-lyrics-manager'); ?></th>
                                <th><?php _e('Author', 'choir-lyrics-manager'); ?></th>
                                <th><?php _e('Date', 'choir-lyrics-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overview_data['recent_lyrics'] as $lyric) : ?>
                                <tr>
                                    <td><a href="<?php echo $lyric['url']; ?>"><?php echo esc_html($lyric['title']); ?></a></td>
                                    <td><?php echo esc_html($lyric['author']); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($lyric['date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="clm-dashboard-table clm-recent-logs">
                    <h3><?php _e('Recent Practice Logs', 'choir-lyrics-manager'); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('User', 'choir-lyrics-manager'); ?></th>
                                <th><?php _e('Lyric', 'choir-lyrics-manager'); ?></th>
                                <th><?php _e('Duration', 'choir-lyrics-manager'); ?></th>
                                <th><?php _e('Date', 'choir-lyrics-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overview_data['recent_logs'] as $log) : ?>
                                <tr>
                                    <td><?php echo esc_html($log['user']); ?></td>
                                    <td><a href="<?php echo get_permalink($log['lyric_id']); ?>"><?php echo esc_html($log['lyric_title']); ?></a></td>
                                    <td><?php echo sprintf(_n('%d minute', '%d minutes', $log['duration'], 'choir-lyrics-manager'), $log['duration']); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($log['date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php wp_nonce_field('clm_analytics_nonce', 'clm_analytics_nonce'); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Format duration in minutes to hours and minutes
     *
     * @since     1.0.0
     * @param     int       $minutes    Duration in minutes.
     * @return    string                Formatted duration.
     */
    private function format_duration($minutes) {
        $minutes = intval($minutes);
        
        if ($minutes < 60) {
            return sprintf(_n('%d minute', '%d minutes', $minutes, 'choir-lyrics-manager'), $minutes);
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($mins === 0) {
            return sprintf(_n('%d hour', '%d hours', $hours, 'choir-lyrics-manager'), $hours);
        }
        
        return sprintf(
            _n('%d hour', '%d hours', $hours, 'choir-lyrics-manager') . ', ' . _n('%d minute', '%d minutes', $mins, 'choir-lyrics-manager'),
            $hours,
            $mins
        );
    }
}