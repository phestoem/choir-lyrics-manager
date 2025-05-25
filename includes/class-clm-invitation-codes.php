<?php
/**
 * CLM Invitation Codes System
 * Allows registration only with valid invitation codes
 */

class CLM_Invitation_Codes {
    
    private $plugin_name;
    private $version;
    private $loader;
    private $table_name;
    
    public function __construct($plugin_name, $version, $loader = null) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = $loader;
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'clm_invitation_codes';
    }
    
    /**
     * Initialize the invitation codes system
     */
    public function init() {
        if ($this->loader) {
            $this->register_hooks_via_loader();
        } else {
            $this->register_hooks_directly();
        }
    }
    
    /**
     * Register hooks via loader
     */
    private function register_hooks_via_loader() {
        // Database creation
        $this->loader->add_action('init', $this, 'maybe_create_table');
        
        // Admin interface
        $this->loader->add_action('admin_menu', $this, 'add_admin_menu');
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_scripts');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_clm_generate_invitation_codes', $this, 'ajax_generate_codes');
        $this->loader->add_action('wp_ajax_clm_delete_invitation_code', $this, 'ajax_delete_code');
        $this->loader->add_action('wp_ajax_clm_toggle_code_status', $this, 'ajax_toggle_code_status');
        $this->loader->add_action('wp_ajax_clm_validate_invitation_code', $this, 'ajax_validate_code');
        
        // Registration integration
        $this->loader->add_action('register_form', $this, 'add_invitation_field');
        $this->loader->add_filter('registration_errors', $this, 'validate_invitation_code', 10, 3);
        $this->loader->add_action('user_register', $this, 'process_code_usage');
        
        // Settings integration
        $this->loader->add_action('admin_init', $this, 'register_settings');
    }
    
    /**
     * Create invitation codes table
     */
    public function maybe_create_table() {
        if (get_option('clm_invitation_codes_table_created')) {
            return;
        }
        
        $this->create_table();
        update_option('clm_invitation_codes_table_created', true);
    }
    
    /**
     * Create the invitation codes database table
     */
    private function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            type enum('single','multi','unlimited') DEFAULT 'single',
            max_uses int(11) DEFAULT 1,
            used_count int(11) DEFAULT 0,
            expires_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            description text,
            voice_part varchar(20) DEFAULT NULL,
            notes text,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY created_by (created_by),
            KEY expires_at (expires_at),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create usage tracking table
        $usage_table = $wpdb->prefix . 'clm_invitation_code_usage';
        $usage_sql = "CREATE TABLE {$usage_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            used_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            KEY code_id (code_id),
            KEY user_id (user_id),
            KEY used_at (used_at)
        ) $charset_collate;";
        
        dbDelta($usage_sql);
    }
    
    /**
     * Add admin menu for invitation codes
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=clm_lyric',
            __('Invitation Codes', 'choir-lyrics-manager'),
            __('Invitation Codes', 'choir-lyrics-manager'),
            'clm_manage_members',
            'clm-invitation-codes',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'clm-invitation-codes') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-util');
    }
    
    /**
     * Admin page for managing invitation codes
     */
    public function admin_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'generate':
                $this->render_generate_page();
                break;
            case 'view':
                $this->render_view_page();
                break;
            default:
                $this->render_list_page();
                break;
        }
    }
    
    /**
     * Render the main list page
     */
    private function render_list_page() {
        $codes = $this->get_all_codes();
        $stats = $this->get_usage_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Invitation Codes', 'choir-lyrics-manager'); ?>
                <a href="<?php echo admin_url('edit.php?post_type=clm_lyric&page=clm-invitation-codes&action=generate'); ?>" class="page-title-action">
                    <?php _e('Generate New Codes', 'choir-lyrics-manager'); ?>
                </a>
            </h1>
            
            <!-- Statistics Dashboard -->
            <div class="clm-invitation-stats">
                <div class="clm-stats-grid">
                    <div class="clm-stat-card">
                        <h3><?php _e('Total Codes', 'choir-lyrics-manager'); ?></h3>
                        <span class="clm-stat-number"><?php echo $stats['total_codes']; ?></span>
                    </div>
                    <div class="clm-stat-card">
                        <h3><?php _e('Active Codes', 'choir-lyrics-manager'); ?></h3>
                        <span class="clm-stat-number"><?php echo $stats['active_codes']; ?></span>
                    </div>
                    <div class="clm-stat-card">
                        <h3><?php _e('Used Codes', 'choir-lyrics-manager'); ?></h3>
                        <span class="clm-stat-number"><?php echo $stats['used_codes']; ?></span>
                    </div>
                    <div class="clm-stat-card">
                        <h3><?php _e('Total Registrations', 'choir-lyrics-manager'); ?></h3>
                        <span class="clm-stat-number"><?php echo $stats['total_uses']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Codes Table -->
            <div class="clm-codes-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Code', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Type', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Usage', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Voice Part', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Expires', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Status', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Created', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Actions', 'choir-lyrics-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($codes)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <?php _e('No invitation codes found.', 'choir-lyrics-manager'); ?>
                                <br><br>
                                <a href="<?php echo admin_url('edit.php?post_type=clm_lyric&page=clm-invitation-codes&action=generate'); ?>" class="button button-primary">
                                    <?php _e('Generate Your First Codes', 'choir-lyrics-manager'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($codes as $code): 
                                $is_expired = $code->expires_at && strtotime($code->expires_at) < time();
                                $is_used_up = $code->used_count >= $code->max_uses && $code->type !== 'unlimited';
                                $status_class = $code->is_active && !$is_expired && !$is_used_up ? 'active' : 'inactive';
                            ?>
                            <tr data-code-id="<?php echo $code->id; ?>" class="clm-code-row clm-status-<?php echo $status_class; ?>">
                                <td>
                                    <strong class="clm-code-value"><?php echo esc_html($code->code); ?></strong>
                                    <button class="clm-copy-code button-link" data-code="<?php echo esc_attr($code->code); ?>" title="<?php esc_attr_e('Copy to clipboard', 'choir-lyrics-manager'); ?>">
                                        📋
                                    </button>
                                    <?php if ($code->description): ?>
                                    <br><small><?php echo esc_html($code->description); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $type_labels = array(
                                        'single' => __('Single Use', 'choir-lyrics-manager'),
                                        'multi' => __('Multi Use', 'choir-lyrics-manager'),
                                        'unlimited' => __('Unlimited', 'choir-lyrics-manager')
                                    );
                                    echo $type_labels[$code->type];
                                    ?>
                                </td>
                                <td>
                                    <?php if ($code->type === 'unlimited'): ?>
                                        <?php echo $code->used_count; ?> / ∞
                                    <?php else: ?>
                                        <?php echo $code->used_count; ?> / <?php echo $code->max_uses; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($code->used_count > 0): ?>
                                    <br><a href="<?php echo admin_url('edit.php?post_type=clm_lyric&page=clm-invitation-codes&action=view&id=' . $code->id); ?>" class="button-link">
                                        <?php _e('View Usage', 'choir-lyrics-manager'); ?>
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($code->voice_part): ?>
                                        <span class="clm-voice-part-badge"><?php echo esc_html(ucfirst($code->voice_part)); ?></span>
                                    <?php else: ?>
                                        <span class="clm-voice-part-any"><?php _e('Any', 'choir-lyrics-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($code->expires_at): ?>
                                        <?php 
                                        $expiry = strtotime($code->expires_at);
                                        $now = time();
                                        if ($expiry < $now): ?>
                                            <span class="clm-expired"><?php _e('Expired', 'choir-lyrics-manager'); ?></span><br>
                                            <small><?php echo date_i18n(get_option('date_format'), $expiry); ?></small>
                                        <?php else: ?>
                                            <?php echo date_i18n(get_option('date_format'), $expiry); ?>
                                            <br><small><?php echo human_time_diff($now, $expiry) . ' ' . __('remaining', 'choir-lyrics-manager'); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="clm-no-expiry"><?php _e('Never', 'choir-lyrics-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="clm-status-indicator clm-status-<?php echo $status_class; ?>">
                                        <?php if ($is_expired): ?>
                                            <?php _e('Expired', 'choir-lyrics-manager'); ?>
                                        <?php elseif ($is_used_up): ?>
                                            <?php _e('Used Up', 'choir-lyrics-manager'); ?>
                                        <?php elseif ($code->is_active): ?>
                                            <?php _e('Active', 'choir-lyrics-manager'); ?>
                                        <?php else: ?>
                                            <?php _e('Disabled', 'choir-lyrics-manager'); ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($code->created_at)); ?>
                                    <br><small><?php echo get_user_by('ID', $code->created_by)->display_name; ?></small>
                                </td>
                                <td>
                                    <div class="clm-code-actions">
                                        <button class="clm-toggle-status button-link" data-code-id="<?php echo $code->id; ?>" data-current-status="<?php echo $code->is_active; ?>">
                                            <?php echo $code->is_active ? __('Disable', 'choir-lyrics-manager') : __('Enable', 'choir-lyrics-manager'); ?>
                                        </button>
                                        |
                                        <button class="clm-delete-code button-link clm-text-danger" data-code-id="<?php echo $code->id; ?>">
                                            <?php _e('Delete', 'choir-lyrics-manager'); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .clm-invitation-stats {
            margin: 20px 0;
        }
        .clm-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .clm-stat-card {
            background: white;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            text-align: center;
        }
        .clm-stat-card h3 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #666;
        }
        .clm-stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #0073aa;
        }
        .clm-copy-code {
            margin-left: 10px;
            text-decoration: none;
            cursor: pointer;
        }
        .clm-voice-part-badge {
            background: #0073aa;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .clm-voice-part-any {
            color: #666;
            font-style: italic;
        }
        .clm-expired {
            color: #dc3232;
            font-weight: bold;
        }
        .clm-no-expiry {
            color: #666;
            font-style: italic;
        }
        .clm-status-indicator.clm-status-active {
            color: #46b450;
            font-weight: bold;
        }
        .clm-status-indicator.clm-status-inactive {
            color: #dc3232;
        }
        .clm-text-danger {
            color: #dc3232;
        }
        .clm-code-actions {
            font-size: 12px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Copy code to clipboard
            $('.clm-copy-code').on('click', function() {
                var code = $(this).data('code');
                navigator.clipboard.writeText(code).then(function() {
                    alert('<?php _e('Code copied to clipboard!', 'choir-lyrics-manager'); ?>');
                });
            });
            
            // Toggle code status
            $('.clm-toggle-status').on('click', function() {
                var $button = $(this);
                var codeId = $button.data('code-id');
                var currentStatus = $button.data('current-status');
                
                $.post(ajaxurl, {
                    action: 'clm_toggle_code_status',
                    code_id: codeId,
                    nonce: '<?php echo wp_create_nonce('clm_invitation_codes'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                });
            });
            
            // Delete code
            $('.clm-delete-code').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to delete this invitation code?', 'choir-lyrics-manager'); ?>')) {
                    return;
                }
                
                var codeId = $(this).data('code-id');
                
                $.post(ajaxurl, {
                    action: 'clm_delete_invitation_code',
                    code_id: codeId,
                    nonce: '<?php echo wp_create_nonce('clm_invitation_codes'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render the code generation page
     */
    private function render_generate_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Generate Invitation Codes', 'choir-lyrics-manager'); ?>
                <a href="<?php echo admin_url('edit.php?post_type=clm_lyric&page=clm-invitation-codes'); ?>" class="page-title-action">
                    <?php _e('Back to List', 'choir-lyrics-manager'); ?>
                </a>
            </h1>
            
            <form id="clm-generate-codes-form" class="clm-generate-form">
                <?php wp_nonce_field('clm_invitation_codes', 'nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="code_type"><?php _e('Code Type', 'choir-lyrics-manager'); ?></label>
                        </th>
                        <td>
                            <select name="code_type" id="code_type" required>
                                <option value="single"><?php _e('Single Use (1 registration per code)', 'choir-lyrics-manager'); ?></option>
                                <option value="multi"><?php _e('Multi Use (limited registrations per code)', 'choir-lyrics-manager'); ?></option>
                                <option value="unlimited"><?php _e('Unlimited (unlimited registrations per code)', 'choir-lyrics-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr class="clm-max-uses-row" style="display: none;">
                        <th scope="row">
                            <label for="max_uses"><?php _e('Maximum Uses', 'choir-lyrics-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_uses" id="max_uses" min="2" max="100" value="5">
                            <p class="description"><?php _e('How many times this code can be used.', 'choir-lyrics-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="quantity"><?php _e('Number of Codes', 'choir-lyrics-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="quantity" id="quantity" min="1" max="100" value="1" required>
                            <p class="description"><?php _e('How many codes to generate.', 'choir-lyrics-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="voice_part"><?php _e('Voice Part Restriction', 'choir-lyrics-manager'); ?></label>
                        </th>
                        <td>
                            <select name="voice_part" id="voice_part">
                                <option value=""><?php _e('Any Voice Part', 'choir-lyrics-manager'); ?></option>
                                <option value="soprano"><?php _e('Soprano Only', 'choir-lyrics-manager'); ?></option>
                                <option value="alto"><?php _e('Alto Only', 'choir-lyrics-manager'); ?></option>
                                <option value="tenor"><?php _e('Tenor Only', 'choir-lyrics-manager'); ?></option>
                                <option value="bass"><?php _e('Bass Only', 'choir-lyrics-manager'); ?></option>
                            </select>
                            <p class="description"><?php _e('Restrict these codes to specific voice parts.', 'choir-lyrics-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="expires_at"><?php _e('Expiration Date', 'choir-lyrics-manager'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="expires_at" id="expires_at" min="<?php echo date('Y-m-d'); ?>">
                            <p class="description"><?php _e('Leave blank for codes that never expire.', 'choir-lyrics-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="description"><?php _e('Description', 'choir-lyrics-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="description" id="description" class="regular-text" placeholder="<?php esc_attr_e('e.g., Fall 2024 Auditions', 'choir-lyrics-manager'); ?>">
                            <p class="description"><?php _e('Optional description for your reference.', 'choir-lyrics-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="notes"><?php _e('Internal Notes', 'choir-lyrics-manager'); ?></label>
                        </th>
                        <td>
                            <textarea name="notes" id="notes" rows="3" class="large-text" placeholder="<?php esc_attr_e('Internal notes about these codes...', 'choir-lyrics-manager'); ?>"></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Generate Codes', 'choir-lyrics-manager'); ?></button>
                    <span class="clm-generating" style="display: none;">
                        <span class="spinner is-active"></span>
                        <?php _e('Generating codes...', 'choir-lyrics-manager'); ?>
                    </span>
                </p>
            </form>
            
            <div id="clm-generated-codes" style="display: none;">
                <h2><?php _e('Generated Codes', 'choir-lyrics-manager'); ?></h2>
                <div class="clm-codes-container"></div>
                <p>
                    <button id="clm-copy-all-codes" class="button"><?php _e('Copy All Codes', 'choir-lyrics-manager'); ?></button>
                    <button id="clm-download-codes" class="button"><?php _e('Download as CSV', 'choir-lyrics-manager'); ?></button>
                </p>
            </div>
        </div>
        
        <style>
        .clm-generate-form {
            background: white;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }
        .clm-codes-container {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-line;
            max-height: 300px;
            overflow-y: auto;
        }
        .clm-generating {
            margin-left: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Show/hide max uses field based on code type
            $('#code_type').on('change', function() {
                if ($(this).val() === 'multi') {
                    $('.clm-max-uses-row').show();
                } else {
                    $('.clm-max-uses-row').hide();
                }
            });
            
            // Handle form submission
            $('#clm-generate-codes-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $button = $form.find('button[type="submit"]');
                var $loading = $('.clm-generating');
                
                $button.prop('disabled', true);
                $loading.show();
                
                $.post(ajaxurl, $form.serialize() + '&action=clm_generate_invitation_codes', function(response) {
                    $button.prop('disabled', false);
                    $loading.hide();
                    
                    if (response.success) {
                        var codes = response.data.codes;
                        var codesText = codes.join('\n');
                        
                        $('.clm-codes-container').text(codesText);
                        $('#clm-generated-codes').show();
                        
                        // Store codes for copying/downloading
                        $('#clm-generated-codes').data('codes', codes);
                        
                        // Reset form
                        $form[0].reset();
                        
                        alert('<?php _e('Codes generated successfully!', 'choir-lyrics-manager'); ?>');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }).fail(function() {
                    $button.prop('disabled', false);
                    $loading.hide();
                    alert('<?php _e('Network error. Please try again.', 'choir-lyrics-manager'); ?>');
                });
            });
            
            // Copy all codes
            $('#clm-copy-all-codes').on('click', function() {
                var codes = $('#clm-generated-codes').data('codes');
                if (codes) {
                    navigator.clipboard.writeText(codes.join('\n')).then(function() {
                        alert('<?php _e('All codes copied to clipboard!', 'choir-lyrics-manager'); ?>');
                    });
                }
            });
            
            // Download codes as CSV
            $('#clm-download-codes').on('click', function() {
                var codes = $('#clm-generated-codes').data('codes');
                if (codes) {
                    var csv = 'Invitation Code\n' + codes.join('\n');
                    var blob = new Blob([csv], { type: 'text/csv' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'choir-invitation-codes-' + new Date().toISOString().split('T')[0] + '.csv';
                    a.click();
                    window.URL.revokeObjectURL(url);
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render code usage view page
     */
    private function render_view_page() {
        $code_id = intval($_GET['id']);
        $code = $this->get_code_by_id($code_id);
        
        if (!$code) {
            wp_die(__('Invalid invitation code.', 'choir-lyrics-manager'));
        }
        
        $usage_data = $this->get_code_usage($code_id);
        ?>
        <div class="wrap">
            <h1><?php printf(__('Code: %s', 'choir-lyrics-manager'), $code->code); ?>
                <a href="<?php echo admin_url('edit.php?post_type=clm_lyric&page=clm-invitation-codes'); ?>" class="page-title-action">
                    <?php _e('Back to List', 'choir-lyrics-manager'); ?>
                </a>
            </h1>
            
            <div class="clm-code-details">
                <h2><?php _e('Code Details', 'choir-lyrics-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Code', 'choir-lyrics-manager'); ?></th>
                        <td><strong><?php echo esc_html($code->code); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Type', 'choir-lyrics-manager'); ?></th>
                        <td><?php echo esc_html(ucfirst($code->type)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Usage', 'choir-lyrics-manager'); ?></th>
                        <td>
                            <?php if ($code->type === 'unlimited'): ?>
                                <?php echo $code->used_count; ?> / ∞
                            <?php else: ?>
                                <?php echo $code->used_count; ?> / <?php echo $code->max_uses; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($code->voice_part): ?>
                    <tr>
                        <th><?php _e('Voice Part', 'choir-lyrics-manager'); ?></th>
                        <td><?php echo esc_html(ucfirst($code->voice_part)); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($code->expires_at): ?>
                    <tr>
                        <th><?php _e('Expires', 'choir-lyrics-manager'); ?></th>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($code->expires_at)); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php _e('Created', 'choir-lyrics-manager'); ?></th>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($code->created_at)); ?> 
                            by <?php echo get_user_by('ID', $code->created_by)->display_name; ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if (!empty($usage_data)): ?>
            <div class="clm-usage-history">
                <h2><?php _e('Usage History', 'choir-lyrics-manager'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Email', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Registration Date', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('IP Address', 'choir-lyrics-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usage_data as $usage): 
                            $user = get_user_by('ID', $usage->user_id);
                        ?>
                        <tr>
                            <td>
                                <?php if ($user): ?>
                                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>">
                                        <?php echo esc_html($user->display_name); ?>
                                    </a>
                                <?php else: ?>
                                    <em><?php _e('User deleted', 'choir-lyrics-manager'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user ? esc_html($user->user_email) : '—'; ?></td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($usage->used_at)); ?></td>
                            <td><?php echo esc_html($usage->ip_address); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="clm-no-usage">
                <p><?php _e('This code has not been used yet.', 'choir-lyrics-manager'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Add invitation code field to registration form
     */
    public function add_invitation_field() {
        // Only show if invitation codes are required
        if (!get_option('clm_require_invitation_codes', true)) {
            return;
        }
        
        $pre_filled_code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
        ?>
        <p>
            <label for="clm_invitation_code"><?php _e('Invitation Code', 'choir-lyrics-manager'); ?> <span class="required">*</span></label>
            <input type="text" name="clm_invitation_code" id="clm_invitation_code" class="input" value="<?php echo esc_attr($pre_filled_code); ?>" required>
            <span id="clm-code-validation" class="clm-validation-message"></span>
        </p>
        
        <script>
        jQuery(document).ready(function($) {
            var validationTimeout;
            
            $('#clm_invitation_code').on('input', function() {
                var code = $(this).val().trim();
                var $validation = $('#clm-code-validation');
                
                clearTimeout(validationTimeout);
                
                if (code.length < 4) {
                    $validation.text('').removeClass('valid invalid');
                    return;
                }
                
                $validation.text('<?php _e('Checking...', 'choir-lyrics-manager'); ?>').removeClass('valid invalid');
                
                validationTimeout = setTimeout(function() {
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'clm_validate_invitation_code',
                        code: code,
                        nonce: '<?php echo wp_create_nonce('clm_validate_code'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $validation.text('✓ ' + response.data.message).addClass('valid').removeClass('invalid');
                        } else {
                            $validation.text('✗ ' + response.data.message).addClass('invalid').removeClass('valid');
                        }
                    });
                }, 500);
            });
        });
        </script>
        
        <style>
        .clm-validation-message {
            display: block;
            margin-top: 5px;
            font-size: 12px;
        }
        .clm-validation-message.valid {
            color: #46b450;
        }
        .clm-validation-message.invalid {
            color: #dc3232;
        }
        </style>
        <?php
    }
    
    /**
     * Validate invitation code during registration
     */
    public function validate_invitation_code($errors, $sanitized_user_login, $user_email) {
        if (!get_option('clm_require_invitation_codes', true)) {
            return $errors;
        }
        
        $code = isset($_POST['clm_invitation_code']) ? sanitize_text_field($_POST['clm_invitation_code']) : '';
        
        if (empty($code)) {
            $errors->add('invitation_code_required', 
                __('<strong>ERROR</strong>: Invitation code is required.', 'choir-lyrics-manager'));
            return $errors;
        }
        
        $validation = $this->validate_code($code);
        
        if (!$validation['valid']) {
            $errors->add('invitation_code_invalid', 
                __('<strong>ERROR</strong>: ', 'choir-lyrics-manager') . $validation['message']);
        }
        
        return $errors;
    }
    
    /**
     * Process code usage after successful registration
     */
    public function process_code_usage($user_id) {
        if (!get_option('clm_require_invitation_codes', true)) {
            return;
        }
        
        $code = isset($_POST['clm_invitation_code']) ? sanitize_text_field($_POST['clm_invitation_code']) : '';
        
        if ($code) {
            $this->use_code($code, $user_id);
        }
    }
    
    /**
     * AJAX: Generate invitation codes
     */
    public function ajax_generate_codes() {
        check_ajax_referer('clm_invitation_codes', 'nonce');
        
        if (!current_user_can('clm_manage_members')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $type = sanitize_text_field($_POST['code_type']);
        $quantity = intval($_POST['quantity']);
        $max_uses = $type === 'multi' ? intval($_POST['max_uses']) : ($type === 'unlimited' ? 0 : 1);
        $voice_part = sanitize_text_field($_POST['voice_part']);
        $expires_at = sanitize_text_field($_POST['expires_at']);
        $description = sanitize_text_field($_POST['description']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if ($quantity < 1 || $quantity > 100) {
            wp_send_json_error(array('message' => 'Invalid quantity'));
        }
        
        $codes = array();
        for ($i = 0; $i < $quantity; $i++) {
            $code = $this->generate_unique_code();
            if ($this->create_code($code, $type, $max_uses, $voice_part, $expires_at, $description, $notes)) {
                $codes[] = $code;
            }
        }
        
        wp_send_json_success(array(
            'codes' => $codes,
            'message' => sprintf(__('%d codes generated successfully.', 'choir-lyrics-manager'), count($codes))
        ));
    }
    
    /**
     * AJAX: Validate invitation code
     */
    public function ajax_validate_code() {
        check_ajax_referer('clm_validate_code', 'nonce');
        
        $code = sanitize_text_field($_POST['code']);
        $validation = $this->validate_code($code);
        
        if ($validation['valid']) {
            wp_send_json_success(array('message' => $validation['message']));
        } else {
            wp_send_json_error(array('message' => $validation['message']));
        }
    }
    
    /**
     * AJAX: Toggle code status
     */
    public function ajax_toggle_code_status() {
        check_ajax_referer('clm_invitation_codes', 'nonce');
        
        if (!current_user_can('clm_manage_members')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $code_id = intval($_POST['code_id']);
        
        global $wpdb;
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM {$this->table_name} WHERE id = %d",
            $code_id
        ));
        
        $new_status = $current_status ? 0 : 1;
        
        $result = $wpdb->update(
            $this->table_name,
            array('is_active' => $new_status),
            array('id' => $code_id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
    }
    
    /**
     * AJAX: Delete invitation code
     */
    public function ajax_delete_code() {
        check_ajax_referer('clm_invitation_codes', 'nonce');
        
        if (!current_user_can('clm_manage_members')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $code_id = intval($_POST['code_id']);
        
        global $wpdb;
        
        // Delete usage records first
        $usage_table = $wpdb->prefix . 'clm_invitation_code_usage';
        $wpdb->delete($usage_table, array('code_id' => $code_id), array('%d'));
        
        // Delete the code
        $result = $wpdb->delete($this->table_name, array('id' => $code_id), array('%d'));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Code deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete code'));
        }
    }
    
    /**
     * Generate a unique invitation code
     */
    private function generate_unique_code($length = 8) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max_attempts = 100;
        
        for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[wp_rand(0, strlen($characters) - 1)];
            }
            
            // Add dashes for readability
            if ($length >= 8) {
                $code = substr($code, 0, 4) . '-' . substr($code, 4);
            }
            
            // Check if code already exists
            if (!$this->code_exists($code)) {
                return $code;
            }
        }
        
        // Fallback: add timestamp if all attempts failed
        return substr($code, 0, 4) . '-' . substr(time(), -4);
    }
    
    /**
     * Check if code exists
     */
    private function code_exists($code) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE code = %s",
            $code
        ));
        
        return $count > 0;
    }
    
    /**
     * Create a new invitation code
     */
    private function create_code($code, $type, $max_uses, $voice_part, $expires_at, $description, $notes) {
        global $wpdb;
        
        $data = array(
            'code' => $code,
            'type' => $type,
            'max_uses' => $max_uses,
            'voice_part' => $voice_part ?: null,
            'expires_at' => $expires_at ? $expires_at . ' 23:59:59' : null,
            'description' => $description ?: null,
            'notes' => $notes ?: null,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        );
        
        return $wpdb->insert($this->table_name, $data);
    }
    
    /**
     * Validate an invitation code
     */
    public function validate_code($code, $voice_part = null) {
        global $wpdb;
        
        $code_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE code = %s",
            $code
        ));
        
        if (!$code_data) {
            return array(
                'valid' => false,
                'message' => __('Invalid invitation code.', 'choir-lyrics-manager')
            );
        }
        
        // Check if code is active
        if (!$code_data->is_active) {
            return array(
                'valid' => false,
                'message' => __('This invitation code has been disabled.', 'choir-lyrics-manager')
            );
        }
        
        // Check expiration
        if ($code_data->expires_at && strtotime($code_data->expires_at) < time()) {
            return array(
                'valid' => false,
                'message' => __('This invitation code has expired.', 'choir-lyrics-manager')
            );
        }
        
        // Check usage limit
        if ($code_data->type !== 'unlimited' && $code_data->used_count >= $code_data->max_uses) {
            return array(
                'valid' => false,
                'message' => __('This invitation code has reached its usage limit.', 'choir-lyrics-manager')
            );
        }
        
        // Check voice part restriction
        if ($code_data->voice_part && $voice_part && $code_data->voice_part !== $voice_part) {
            return array(
                'valid' => false,
                'message' => sprintf(__('This code is only valid for %s singers.', 'choir-lyrics-manager'), 
                                   ucfirst($code_data->voice_part))
            );
        }
        
        return array(
            'valid' => true,
            'message' => __('Valid invitation code.', 'choir-lyrics-manager'),
            'code_data' => $code_data
        );
    }
    
    /**
     * Use an invitation code
     */
    private function use_code($code, $user_id) {
        global $wpdb;
        
        // Get code data
        $code_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE code = %s",
            $code
        ));
        
        if (!$code_data) {
            return false;
        }
        
        // Increment usage count
        $wpdb->update(
            $this->table_name,
            array('used_count' => $code_data->used_count + 1),
            array('id' => $code_data->id),
            array('%d'),
            array('%d')
        );
        
        // Record usage
        $usage_table = $wpdb->prefix . 'clm_invitation_code_usage';
        $wpdb->insert(
            $usage_table,
            array(
                'code_id' => $code_data->id,
                'user_id' => $user_id,
                'used_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            )
        );
        
        return true;
    }
    
    /**
     * Get all invitation codes
     */
    private function get_all_codes() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC"
        );
    }
    
    /**
     * Get code by ID
     */
    private function get_code_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get code usage data
     */
    private function get_code_usage($code_id) {
        global $wpdb;
        
        $usage_table = $wpdb->prefix . 'clm_invitation_code_usage';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$usage_table} WHERE code_id = %d ORDER BY used_at DESC",
            $code_id
        ));
    }
    
    /**
     * Get usage statistics
     */
    private function get_usage_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_codes,
                SUM(CASE WHEN is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) THEN 1 ELSE 0 END) as active_codes,
                SUM(CASE WHEN used_count > 0 THEN 1 ELSE 0 END) as used_codes,
                SUM(used_count) as total_uses
            FROM {$this->table_name}"
        );
        
        return array(
            'total_codes' => intval($stats->total_codes),
            'active_codes' => intval($stats->active_codes),
            'used_codes' => intval($stats->used_codes),
            'total_uses' => intval($stats->total_uses)
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        add_settings_field(
            'clm_require_invitation_codes',
            __('Require Invitation Codes', 'choir-lyrics-manager'),
            array($this, 'require_codes_callback'),
            'clm_settings',
            'clm_user_management_section'
        );
        
        register_setting('clm_settings', 'clm_require_invitation_codes');
    }
    
    /**
     * Settings callback
     */
    public function require_codes_callback() {
        $value = get_option('clm_require_invitation_codes', true);
        echo '<input type="checkbox" name="clm_require_invitation_codes" value="1" ' . checked(1, $value, false) . '>';
        echo '<p class="description">' . __('Require valid invitation codes for choir registration.', 'choir-lyrics-manager') . '</p>';
    }
    
    /**
     * Fallback hook registration
     */
    private function register_hooks_directly() {
        add_action('init', array($this, 'maybe_create_table'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // ... other hooks
    }
}

