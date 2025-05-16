<?php
/**
 * Event reminder email template
 *
 * @package Choir_Lyrics_Manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($email_subject); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            background: #007cba;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .event-details {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo esc_html($event->post_title); ?></h1>
            <p><?php _e('Event Reminder', 'choir-lyrics-manager'); ?></p>
        </div>
        
        <p><?php printf(__('Hello %s,', 'choir-lyrics-manager'), esc_html($user->display_name)); ?></p>
        
        <p><?php _e('This is a reminder about the upcoming event:', 'choir-lyrics-manager'); ?></p>
        
        <div class="event-details">
            <p><strong><?php _e('Date:', 'choir-lyrics-manager'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?></p>
            <p><strong><?php _e('Time:', 'choir-lyrics-manager'); ?></strong> <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($event_time))); ?></p>
            <p><strong><?php _e('Location:', 'choir-lyrics-manager'); ?></strong> <?php echo nl2br(esc_html($event_location)); ?></p>
        </div>
        
        <p><?php _e('We look forward to seeing you there!', 'choir-lyrics-manager'); ?></p>
        
        <p style="text-align: center;">
            <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" class="button">
                <?php _e('View Event Details', 'choir-lyrics-manager'); ?>
            </a>
        </p>
        
        <div class="footer">
            <p><?php printf(__('This email was sent from %s', 'choir-lyrics-manager'), get_bloginfo('name')); ?></p>
        </div>
    </div>
</body>
</html>