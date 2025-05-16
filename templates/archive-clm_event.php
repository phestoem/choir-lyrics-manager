<?php
/**
 * The template for displaying event archive pages
 *
 * @package Choir_Lyrics_Manager
 */

get_header();
?>

<div class="clm-events-archive">
    <header class="clm-archive-header">
        <h1 class="clm-archive-title"><?php _e('Choir Events', 'choir-lyrics-manager'); ?></h1>
        
        <div class="clm-event-filters">
            <form method="get" action="<?php echo esc_url(get_post_type_archive_link('clm_event')); ?>">
                <div class="clm-filter-group">
                    <label for="event-type"><?php _e('Event Type:', 'choir-lyrics-manager'); ?></label>
                    <select name="event_type" id="event-type">
                        <option value=""><?php _e('All Types', 'choir-lyrics-manager'); ?></option>
                        <?php
                        $event_types = get_terms(array(
                            'taxonomy' => 'clm_event_type',
                            'hide_empty' => true,
                        ));
                        
                        foreach ($event_types as $type) {
                            $selected = (isset($_GET['event_type']) && $_GET['event_type'] == $type->slug) ? 'selected' : '';
                            echo '<option value="' . esc_attr($type->slug) . '" ' . $selected . '>' . esc_html($type->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="clm-filter-group">
                    <label for="event-venue"><?php _e('Venue:', 'choir-lyrics-manager'); ?></label>
                    <select name="venue" id="event-venue">
                        <option value=""><?php _e('All Venues', 'choir-lyrics-manager'); ?></option>
                        <?php
                        $venues = get_terms(array(
                            'taxonomy' => 'clm_venue',
                            'hide_empty' => true,
                        ));
                        
                        foreach ($venues as $venue) {
                            $selected = (isset($_GET['venue']) && $_GET['venue'] == $venue->slug) ? 'selected' : '';
                            echo '<option value="' . esc_attr($venue->slug) . '" ' . $selected . '>' . esc_html($venue->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="clm-filter-group">
                    <label for="event-period"><?php _e('Period:', 'choir-lyrics-manager'); ?></label>
                    <select name="period" id="event-period">
                        <option value=""><?php _e('All Events', 'choir-lyrics-manager'); ?></option>
                        <option value="upcoming" <?php selected(isset($_GET['period']) ? $_GET['period'] : '', 'upcoming'); ?>><?php _e('Upcoming Events', 'choir-lyrics-manager'); ?></option>
                        <option value="past" <?php selected(isset($_GET['period']) ? $_GET['period'] : '', 'past'); ?>><?php _e('Past Events', 'choir-lyrics-manager'); ?></option>
                    </select>
                </div>
                
                <button type="submit" class="clm-button"><?php _e('Filter', 'choir-lyrics-manager'); ?></button>
                <a href="<?php echo esc_url(get_post_type_archive_link('clm_event')); ?>" class="clm-button-text"><?php _e('Clear Filters', 'choir-lyrics-manager'); ?></a>
            </form>
        </div>
    </header>

    <?php if (have_posts()) : ?>
        <div class="clm-events-list">
            <?php
            while (have_posts()) :
                the_post();
                $event_date = get_post_meta(get_the_ID(), '_clm_event_date', true
				$event_date = get_post_meta(get_the_ID(), '_clm_event_date', true);
               $event_time = get_post_meta(get_the_ID(), '_clm_event_time', true);
               $event_location = get_post_meta(get_the_ID(), '_clm_event_location', true);
               $ticket_price = get_post_meta(get_the_ID(), '_clm_event_ticket_price', true);
               $ticket_link = get_post_meta(get_the_ID(), '_clm_event_ticket_link', true);
               ?>
               
               <article class="clm-event-item">
                   <div class="clm-event-date-box">
                       <?php if ($event_date): ?>
                           <div class="clm-event-month"><?php echo date('M', strtotime($event_date)); ?></div>
                           <div class="clm-event-day"><?php echo date('d', strtotime($event_date)); ?></div>
                           <div class="clm-event-year"><?php echo date('Y', strtotime($event_date)); ?></div>
                       <?php endif; ?>
                   </div>
                   
                   <div class="clm-event-info">
                       <h2 class="clm-event-title">
                           <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                       </h2>
                       
                       <div class="clm-event-meta">
                           <?php if ($event_time): ?>
                               <span class="clm-event-time">
                                   <span class="dashicons dashicons-clock"></span>
                                   <?php echo esc_html(date('g:i A', strtotime($event_time))); ?>
                               </span>
                           <?php endif; ?>
                           
                           <?php if ($event_location): ?>
                               <span class="clm-event-location">
                                   <span class="dashicons dashicons-location"></span>
                                   <?php echo esc_html($event_location); ?>
                               </span>
                           <?php endif; ?>
                           
                           <?php
                           $event_types = get_the_terms(get_the_ID(), 'clm_event_type');
                           if ($event_types && !is_wp_error($event_types)):
                           ?>
                               <span class="clm-event-type">
                                   <span class="dashicons dashicons-category"></span>
                                   <?php echo esc_html($event_types[0]->name); ?>
                               </span>
                           <?php endif; ?>
                       </div>
                       
                       <div class="clm-event-excerpt">
                           <?php the_excerpt(); ?>
                       </div>
                       
                       <div class="clm-event-actions">
                           <a href="<?php the_permalink(); ?>" class="clm-button">
                               <?php _e('View Details', 'choir-lyrics-manager'); ?>
                           </a>
                           
                           <?php if ($ticket_link): ?>
                               <a href="<?php echo esc_url($ticket_link); ?>" class="clm-button clm-button-primary" target="_blank">
                                   <?php _e('Get Tickets', 'choir-lyrics-manager'); ?>
                               </a>
                           <?php endif; ?>
                       </div>
                   </div>
                   
                   <?php if (has_post_thumbnail()): ?>
                       <div class="clm-event-thumbnail">
                           <?php the_post_thumbnail('medium'); ?>
                       </div>
                   <?php endif; ?>
               </article>
               
           <?php endwhile; ?>
       </div>
       
       <div class="clm-pagination">
           <?php
           echo paginate_links(array(
               'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
               'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
           ));
           ?>
       </div>
       
   <?php else: ?>
       <div class="clm-no-events">
           <p><?php _e('No events found.', 'choir-lyrics-manager'); ?></p>
       </div>
   <?php endif; ?>
</div>

<?php
get_footer();
?>