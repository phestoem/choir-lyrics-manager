<?php
/**
 * The template for displaying single event pages
 *
 * @package Choir_Lyrics_Manager
 */

get_header();

while (have_posts()) :
    the_post();
    
    $event_date = get_post_meta(get_the_ID(), '_clm_event_date', true);
    $event_time = get_post_meta(get_the_ID(), '_clm_event_time', true);
    $event_end_time = get_post_meta(get_the_ID(), '_clm_event_end_time', true);
    $event_location = get_post_meta(get_the_ID(), '_clm_event_location', true);
    $event_director = get_post_meta(get_the_ID(), '_clm_event_director', true);
    $event_accompanist = get_post_meta(get_the_ID(), '_clm_event_accompanist', true);
    $ticket_price = get_post_meta(get_the_ID(), '_clm_event_ticket_price', true);
    $ticket_link = get_post_meta(get_the_ID(), '_clm_event_ticket_link', true);
    $setlist = get_post_meta(get_the_ID(), '_clm_event_setlist', true);
?>

<article class="clm-single-event">
    <header class="clm-event-header">
        <h1 class="clm-event-title"><?php the_title(); ?></h1>
        
        <div class="clm-event-meta">
            <?php if ($event_date): ?>
                <div class="clm-event-date">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($event_time): ?>
                <div class="clm-event-time">
                    <span class="dashicons dashicons-clock"></span>
                    <?php 
                    echo esc_html(date_i18n(get_option('time_format'), strtotime($event_time)));
                    if ($event_end_time) {
                        echo ' - ' . esc_html(date_i18n(get_option('time_format'), strtotime($event_end_time)));
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($event_location): ?>
                <div class="clm-event-location">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo nl2br(esc_html($event_location)); ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <?php if (has_post_thumbnail()): ?>
        <div class="clm-event-featured-image">
            <?php the_post_thumbnail('large'); ?>
        </div>
    <?php endif; ?>
    
    <div class="clm-event-content-wrapper">
        <div class="clm-event-main">
            <div class="clm-event-description">
                <?php the_content(); ?>
            </div>
            
            <?php if (!empty($setlist)): ?>
                <div class="clm-event-program">
                    <h2><?php _e('Program', 'choir-lyrics-manager'); ?></h2>
                    <ol class="clm-setlist">
                        <?php foreach ($setlist as $lyric_id): ?>
                            <?php $lyric = get_post($lyric_id); ?>
                            <?php if ($lyric): ?>
                                <li class="clm-setlist-item">
                                    <a href="<?php echo get_permalink($lyric->ID); ?>" class="clm-setlist-title">
                                        <?php echo esc_html($lyric->post_title); ?>
                                    </a>
                                    <?php
                                    $composer = get_post_meta($lyric->ID, '_clm_composer', true);
                                    $arranger = get_post_meta($lyric->ID, '_clm_arranger', true);
                                    $duration = get_post_meta($lyric->ID, '_clm_duration', true);
                                    ?>
                                    <div class="clm-setlist-meta">
                                        <?php if ($composer): ?>
                                            <span class="clm-composer"><?php echo esc_html($composer); ?></span>
                                        <?php endif; ?>
                                        <?php if ($arranger): ?>
                                            <span class="clm-arranger"><?php _e('arr.', 'choir-lyrics-manager'); ?> <?php echo esc_html($arranger); ?></span>
                                        <?php endif; ?>
                                        <?php if ($duration): ?>
                                            <span class="clm-duration">(<?php echo esc_html($duration); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
        
        <aside class="clm-event-sidebar">
            <div class="clm-event-details-box">
                <h3><?php _e('Event Details', 'choir-lyrics-manager'); ?></h3>
                
                <?php if ($event_director): ?>
                    <div class="clm-event-detail">
                        <span class="clm-detail-label"><?php _e('Director:', 'choir-lyrics-manager'); ?></span>
                        <span class="clm-detail-value"><?php echo esc_html($event_director); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($event_accompanist): ?>
                    <div class="clm-event-detail">
                        <span class="clm-detail-label"><?php _e('Accompanist:', 'choir-lyrics-manager'); ?></span>
                        <span class="clm-detail-value"><?php echo esc_html($event_accompanist); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php
                $event_types = get_the_terms(get_the_ID(), 'clm_event_type');
                if ($event_types && !is_wp_error($event_types)):
                ?>
                    <div class="clm-event-detail">
                        <span class="clm-detail-label"><?php _e('Event Type:', 'choir-lyrics-manager'); ?></span>
                        <span class="clm-detail-value">
                            <?php
                            $type_names = array();
                            foreach ($event_types as $type) {
                                $type_names[] = $type->name;
                            }
                            echo esc_html(implode(', ', $type_names));
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php
                $venues = get_the_terms(get_the_ID(), 'clm_venue');
                if ($venues && !is_wp_error($venues)):
                ?>
                    <div class="clm-event-detail">
                        <span class="clm-detail-label"><?php _e('Venue:', 'choir-lyrics-manager'); ?></span>
                        <span class="clm-detail-value">
                            <?php
                            $venue_names = array();
                            foreach ($venues as $venue) {
                                $venue_names[] = $venue->name;
                            }
                            echo esc_html(implode(', ', $venue_names));
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($ticket_price || $ticket_link): ?>
                <div class="clm-event-tickets-box">
                    <h3><?php _e('Tickets', 'choir-lyrics-manager'); ?></h3>
                    
                    <?php if ($ticket_price): ?>
                        <div class="clm-ticket-price">
                            <span class="clm-price-label"><?php _e('Price:', 'choir-lyrics-manager'); ?></span>
                            <span class="clm-price-value"><?php echo esc_html($ticket_price); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket_link): ?>
                        <a href="<?php echo esc_url($ticket_link); ?>" class="clm-button clm-button-primary clm-button-block" target="_blank">
                            <?php _e('Get Tickets', 'choir-lyrics-manager'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="clm-event-share">
                <h3><?php _e('Share Event', 'choir-lyrics-manager'); ?></h3>
                <div class="clm-share-buttons">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                       class="clm-share-button clm-share-facebook" target="_blank">
                        <span class="dashicons dashicons-facebook"></span>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                       class="clm-share-button clm-share-twitter" target="_blank">
                        <span class="dashicons dashicons-twitter"></span>
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode(get_the_title()); ?>&body=<?php echo urlencode(get_permalink()); ?>" 
                       class="clm-share-button clm-share-email">
                        <span class="dashicons dashicons-email"></span>
                    </a>
                </div>
            </div>
        </aside>
    </div>
    
    <nav class="clm-event-navigation">
        <div class="clm-prev-event">
            <?php previous_post_link('%link', '<span class="dashicons dashicons-arrow-left-alt2"></span> %title'); ?>
        </div>
        <div class="clm-next-event">
            <?php next_post_link('%link', '%title <span class="dashicons dashicons-arrow-right-alt2"></span>'); ?>
        </div>
    </nav>
</article>

<?php endwhile; ?>

<?php
get_footer();
?>