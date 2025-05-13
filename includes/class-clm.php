<?php
class CLM {
    public function __construct() {
        // Initialize CPTs, taxonomies, hooks
        add_action('init', [$this, 'init_plugin']);
    }

    public function init_plugin() {
        // You can optionally initiate global logic here
    }
}
