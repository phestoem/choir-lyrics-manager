<?php
class CLM_Public {
    public function __construct() {
        add_shortcode('clm_lyric', [$this, 'render_lyric']);
    }

    public function render_lyric($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $lyric = get_post($atts['id']);
        if (!$lyric) return '';

        ob_start();
        echo '<h2>' . esc_html($lyric->post_title) . '</h2>';
        echo apply_filters('the_content', $lyric->post_content);
        return ob_get_clean();
    }
}
new CLM_Public();
