<?php
class CLM_Practice_Logger {
    public static function log_practice($user_id, $lyric_id, $status) {
        $logs = get_user_meta($user_id, 'clm_practice_log', true) ?: [];
        $logs[] = [
            'lyric_id' => $lyric_id,
            'status' => $status,
            'timestamp' => current_time('mysql'),
        ];
        update_user_meta($user_id, 'clm_practice_log', $logs);
    }
}
