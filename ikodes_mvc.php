<?php
/*
Plugin Name: ikodes Core Module
Plugin URI: http://ikodes.net/
Description: ikdoes Core Module For Wordpress & Woocommerce. Powered by WooCommerce and ikodes Technology
Author: ikodes Technology
Version: 1.0.1
Text Domain: ikodes Core modules
Domain Path: /core/languages
Author URI: https://github.com/ikodes
*/

if (!defined('iKVC_PLUGIN_PATH')) {
    define('iKVC_PLUGIN_PATH', dirname(__FILE__).'/');
}

// Load public functionality
require_once iKVC_PLUGIN_PATH.'core/loaders/kvc_public_loader.php';
$public_loader = new KvcPublicLoader();

if (is_admin()) {

    // Load admin functionality
    require_once iKVC_PLUGIN_PATH.'core/loaders/kvc_admin_loader.php';
    $admin_loader = new KvcAdminLoader();
    
    add_action('wp_loaded', array($public_loader,'load_rewrite_rules'));
    add_action('admin_init', array($admin_loader, 'admin_init'));
    add_action('admin_menu', array($admin_loader, 'add_menu_pages'));
    add_action('admin_menu', array($admin_loader, 'add_settings_pages'));
    add_action('plugins_loaded', array($admin_loader, 'add_admin_ajax_routes'));
    wp_kvc_load_global_functionality($admin_loader);

}  else {

    // filters for public urls
    add_filter('rewrite_rules_array', array($public_loader, 'add_rewrite_rules'));
    add_filter('query_vars', array($public_loader, 'add_query_vars'));
    add_action('template_redirect', array($public_loader, 'template_redirect'));
    wp_kvc_load_global_functionality($public_loader);
}

// Load global functionality
function wp_kvc_load_global_functionality(&$loader) { //public or admin, depending on context
    add_action('init', array($loader, 'init'));
    add_action('widgets_init', array($loader, 'register_widgets'));
    add_filter('post_type_link', array($loader, 'filter_post_link'), 10, 2);
    add_action('plugins_loaded', 'wpkvc_load_plugin_textdomain' );
}

function wpkvc_load_plugin_textdomain() {
    load_plugin_textdomain( 'wpkvc', FALSE, basename( dirname( __FILE__ ) ) . '/' . 'core/languages' );
}
