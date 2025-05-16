/**
 * Admin JavaScript for Skills Management in Choir Lyrics Manager
 *
 * @package    Choir_Lyrics_Manager
 */

(function($) {
    'use strict';

    // Initialize skills functionality on document ready
    $(document).ready(function() {
        initSkillsManagement();
    });

    /**
     * Initialize skills management functionality
     */
    function initSkillsManagement() {
        // Handle skill level updates
        $('.clm-update-skill').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var member_id = $button.data('member-id');
            var lyric_id = $button.data('lyric-id');
            var skill_level = $button.data('skill-level');
            
            $button.prop('disabled', true).text('Updating...');
            
            $.ajax({
                url: clm_skills.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'clm_update_skill',
                    nonce: clm_skills.nonce,
                    member_id: member_id,
                    lyric_id: lyric_id,
                    skill_level: skill_level
                },
                success: function(response) {
                    if (response.success) {
                        // Update UI to reflect new skill level
                        updateSkillLevelDisplay(member_id, lyric_id, skill_level);
                        
                        // Show success message
                        showNotification('Skill level updated successfully.', 'success');
                    } else {
                        showNotification(response.data.message || 'Error updating skill level.', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Update');
                }
            });
        });
        
        // Practice logging
        $('.clm-log-practice-button').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var lyric_id = $button.data('lyric-id');
            
            $button.prop('disabled', true).text('Logging...');
            
            $.ajax({
                url: clm_skills.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'clm_log_practice',
                    nonce: clm_skills.nonce,
                    lyric_id: lyric_id
                },
                success: function(response) {
                    if (response.success) {
                        // Update practice count and last practice date
                        if (response.data && response.data.skill) {
                            updatePracticeDisplay(response.data.skill);
                        }
                        
                        showNotification('Practice logged successfully.', 'success');
                    } else {
                        showNotification(response.data.message || 'Error logging practice.', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Log Practice');
                }
            });
        });
        
        // Handle goal setting
        $('.clm-set-goal').on('click', function(e) {
            e.preventDefault();
            
            var skillId = $(this).data('skill-id');
            var today = new Date().toISOString().split('T')[0];
            
            var $modal = $('<div class="clm-modal-overlay">' +
                '<div class="clm-modal">' +
                '<h3>Set Practice Goal</h3>' +
                '<p>Choose a target date for mastering this piece:</p>' +
                '<input type="date" id="clm-goal-date" class="clm-goal-date-input" min="' + today + '">' +
                '<div class="clm-modal-actions">' +
                '<button class="clm-button clm-button-primary clm-confirm-goal">Set Goal</button>' +
                '<button class="clm-button clm-cancel-goal">Cancel</button>' +
                '</div>' +
                '</div>' +
                '</div>');
            
            $('body').append($modal);
            
            $('.clm-confirm-goal').on('click', function() {
                var goalDate = $('#clm-goal-date').val();
                
                if (goalDate) {
                    setSkillGoal(skillId, goalDate);
                } else {
                    showNotification('Please select a date', 'error');
                }
            });
            
            $('.clm-cancel-goal, .clm-modal-overlay').on('click', function(e) {
                if (e.target === this) $('.clm-modal-overlay').remove();
            });
        });
    }
    
    /**
     * Set a skill goal
     */
    function setSkillGoal(skillId, goalDate) {
        $('.clm-confirm-goal').prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: clm_skills.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'clm_set_skill_goal',
                nonce: clm_skills.nonce,
                skill_id: skillId,
                goal_date: goalDate
            },
            success: function(response) {
                if (response.success) {
                    $('.clm-modal-overlay').remove();
                    updateSkillGoalDisplay(skillId, goalDate);
                    showNotification('Goal set successfully!', 'success');
                } else {
                    showNotification(response.data.message || 'Error setting goal', 'error');
                }
            },
            error: function() {
                showNotification('Server error. Please try again.', 'error');
            },
            complete: function() {
                $('.clm-confirm-goal').prop('disabled', false).text('Set Goal');
            }
        });
    }
    
    /**
     * Update UI to reflect new skill level
     */
    function updateSkillLevelDisplay(memberId, lyricId, skillLevel) {
        var $row = $('.clm-skill-row[data-member-id="' + memberId + '"][data-lyric-id="' + lyricId + '"]');
        
        // Update skill level cell
        $row.find('.clm-skill-level').attr('data-level', skillLevel);
        
        // Update skill level text and style
        switch (skillLevel) {
            case 'novice':
                $row.find('.clm-skill-level').html('<span class="dashicons dashicons-warning" style="color: #dc3545;"></span> Novice');
                break;
            case 'learning':
                $row.find('.clm-skill-level').html('<span class="dashicons dashicons-lightbulb" style="color: #ffc107;"></span> Learning');
                break;
            case 'proficient':
                $row.find('.clm-skill-level').html('<span class="dashicons dashicons-yes" style="color: #17a2b8;"></span> Proficient');
                break;
            case 'master':
                $row.find('.clm-skill-level').html('<span class="dashicons dashicons-star-filled" style="color: #28a745;"></span> Master');
                break;
        }
    }
    
    /**
     * Update practice display
     */
    function updatePracticeDisplay(skill) {
        if (!skill) return;
        
        var $row = $('.clm-skill-row[data-member-id="' + skill.member_id + '"][data-lyric-id="' + skill.lyric_id + '"]');
        
        // Update practice count
        $row.find('.clm-practice-count').text(skill.practice_count);
        
        // Update last practice date
        var now = new Date();
        var practiceDate = new Date(skill.last_practice_date);
        var diffMs = now - practiceDate;
        var diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        
        var timeAgo = diffDays + ' days ago';
        if (diffDays === 0) {
            timeAgo = 'Today';
        } else if (diffDays === 1) {
            timeAgo = 'Yesterday';
        }
        
        $row.find('.clm-last-practice').text(timeAgo);
    }
    
    /**
     * Update skill goal display
     */
    function updateSkillGoalDisplay(skillId, goalDate) {
        var date = new Date(goalDate);
        var formattedDate = date.toLocaleDateString();
        
        var $skillItem = $('.clm-set-goal[data-skill-id="' + skillId + '"]').closest('.clm-skill-item');
        
        // Remove existing goal display if any
        $skillItem.find('.clm-skill-goal').remove();
        
        // Add new goal display
        var goalHtml = '<div class="clm-skill-goal">' +
            'Goal: ' + formattedDate +
            '</div>';
        
        $skillItem.find('.clm-skill-actions').before(goalHtml);
    }
    
    /**
     * Show notification message
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="clm-notification clm-notification-' + type + '">' + 
            message + '</div>').appendTo('body');
        
        $notification.fadeIn(300);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);