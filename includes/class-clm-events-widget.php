<?php
/**
 * Events Widget
 *
 * @package    Choir_Lyrics_Manager
 */

class CLM_Events_Widget extends WP_Widget {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            'clm_events_widget',
            __('Choir Events', 'choir-lyrics-manager'),
            array(
                'description' => __('Display upcoming choir events', 'choir-lyrics-manager'),
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * Output the widget content.
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $number = (!empty($instance['number'])) ? absint($instance['number']) : 5;
        $show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : true;
        $show_venue = isset($instance['show_venue']) ? (bool) $instance['show_venue'] : true;

        $query_args = array(
            'post_type' => 'clm_event',
            'posts_per_page' => $number,
            'post_status' => 'publish',
            'meta_key' => '_clm_event_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_clm_event_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
            ),
        );

        $events = new WP_Query($query_args);

        if ($events->have_posts()) {
            echo '<ul class="clm-events-widget">';

            while ($events->have_posts()) {
                $events->the_post();
                $event_date = get_post_meta(get_the_ID(), '_clm_event_date', true);
                $event_time = get_post_meta(get_the_ID(), '_clm_event_time', true);
                ?>
                <li class="clm-event-widget-item">
                    <a href="<?php the_permalink(); ?>" class="clm-event-widget-title">
                        <?php the_title(); ?>
                    </a>
                    
                    <?php if ($show_date && $event_date): ?>
                        <div class="clm-event-widget-date">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>
                            <?php if ($event_time): ?>
                                @ <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($event_time))); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($show_venue): ?>
                        <?php $venues = get_the_terms(get_the_ID(), 'clm_venue'); ?>
                        <?php if ($venues && !is_wp_error($venues)): ?>
                            <div class="clm-event-widget-venue">
                                <?php echo esc_html($venues[0]->name); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </li>
                <?php
            }

            echo '</ul>';

            // View all events link
            if (!empty($instance['show_all_link'])) {
                $events_page = get_post_type_archive_link('clm_event');
                echo '<p class="clm-view-all-events"><a href="' . esc_url($events_page) . '">' . __('View all events', 'choir-lyrics-manager') . '</a></p>';
            }

            wp_reset_postdata();
        } else {
            echo '<p>' . __('No upcoming events.', 'choir-lyrics-manager') . '</p>';
        }

        echo $args['after_widget'];
    }

    /**
     * Output the widget form.
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Upcoming Events', 'choir-lyrics-manager');
        $number = !empty($instance['number']) ? absint($instance['number']) : 5;
        $show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : true;
        $show_venue = isset($instance['show_venue']) ? (bool) $instance['show_venue'] : true;
        $show_all_link = isset($instance['show_all_link']) ? (bool) $instance['show_all_link'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'choir-lyrics-manager'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number')); ?>"><?php _e('Number of events to show:', 'choir-lyrics-manager'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number')); ?>" name="<?php echo esc_attr($this->get_field_name('number')); ?>" type="number" step="1" min="1" value="<?php echo esc_attr($number); ?>" size="3">
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_date); ?> id="<?php echo esc_attr($this->get_field_id('show_date')); ?>" name="<?php echo esc_attr($this->get_field_name('show_date')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>"><?php _e('Display event date?', 'choir-lyrics-manager'); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_venue); ?> id="<?php echo esc_attr($this->get_field_id('show_venue')); ?>" name="<?php echo esc_attr($this->get_field_name('show_venue')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_venue')); ?>"><?php _e('Display venue?', 'choir-lyrics-manager'); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_all_link); ?> id="<?php echo esc_attr($this->get_field_id('show_all_link')); ?>" name="<?php echo esc_attr($this->get_field_name('show_all_link')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_all_link')); ?>"><?php _e('Display "View all events" link?', 'choir-lyrics-manager'); ?></label>
        </p>
        <?php
    }

    /**
     * Update the widget settings.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['number'] = (!empty($new_instance['number'])) ? absint($new_instance['number']) : 5;
        $instance['show_date'] = isset($new_instance['show_date']) ? (bool) $new_instance['show_date'] : false;
        $instance['show_venue'] = isset($new_instance['show_venue']) ? (bool) $new_instance['show_venue'] : false;
        $instance['show_all_link'] = isset($new_instance['show_all_link']) ? (bool) $new_instance['show_all_link'] : false;

        return $instance;
    }
}