<?php
/**
 * Plugin Name: Family Media Manager
 * Plugin URI: https://bob490.co.uk
 * Description: Secure video download and streaming plugin for family members with email-based invitations
 * Version: 1.1.0
 * Author: Bob
 * Author URI: https://bob490.co.uk
 * License: GPL v2 or later
 * Text Domain: family-media-manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FMM_VERSION', '1.1.0');
define('FMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FMM_UPLOAD_DIR', 'family-videos');

// Include required files
require_once FMM_PLUGIN_DIR . 'includes/class-fmm-installer.php';
require_once FMM_PLUGIN_DIR . 'includes/class-fmm-user-manager.php';
require_once FMM_PLUGIN_DIR . 'includes/class-fmm-media-manager.php';
require_once FMM_PLUGIN_DIR . 'includes/class-fmm-access-control.php';
require_once FMM_PLUGIN_DIR . 'includes/class-fmm-frontend.php';
require_once FMM_PLUGIN_DIR . 'includes/class-fmm-admin.php';

// Activation hook
register_activation_hook(__FILE__, array('FMM_Installer', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('FMM_Installer', 'deactivate'));

// Initialize the plugin
function fmm_init() {
    // Initialize access control first
    FMM_Access_Control::init();
    
    // Initialize user manager
    FMM_User_Manager::init();
    
    // Initialize media manager
    FMM_Media_Manager::init();
    
    // Initialize frontend
    FMM_Frontend::init();
    
    // Initialize admin if in admin area
    if (is_admin()) {
        FMM_Admin::init();
    }
}
add_action('plugins_loaded', 'fmm_init');

// Enqueue scripts and styles
function fmm_enqueue_scripts() {
    wp_enqueue_style('fmm-frontend-style', FMM_PLUGIN_URL . 'assets/css/frontend.css', array(), FMM_VERSION);
    wp_enqueue_script('fmm-frontend-script', FMM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), FMM_VERSION, true);
    
    wp_localize_script('fmm-frontend-script', 'fmm_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fmm_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'fmm_enqueue_scripts');

// Enqueue admin scripts and styles
function fmm_enqueue_admin_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'family-media-manager') === false && strpos($hook, 'fmm-') === false) {
        return;
    }
    
    wp_enqueue_style('fmm-admin-style', FMM_PLUGIN_URL . 'assets/css/admin.css', array(), FMM_VERSION);
    wp_enqueue_script('fmm-admin-script', FMM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), FMM_VERSION, true);
    
    wp_localize_script('fmm-admin-script', 'fmm_admin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fmm_admin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'fmm_enqueue_admin_scripts');
