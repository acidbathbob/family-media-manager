<?php
/**
 * Admin class for WordPress backend
 */

if (!defined('ABSPATH')) {
    exit;
}

class FMM_Admin {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('wp_ajax_fmm_create_category', array(__CLASS__, 'ajax_create_category'));
    }
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            'Family Media Manager',
            'Family Media',
            'manage_options',
            'family-media-manager',
            array(__CLASS__, 'render_media_page'),
            'dashicons-video-alt3',
            30
        );
        
        add_submenu_page(
            'family-media-manager',
            'Media Library',
            'Media Library',
            'manage_options',
            'family-media-manager',
            array(__CLASS__, 'render_media_page')
        );
        
        add_submenu_page(
            'family-media-manager',
            'Invitations',
            'Invitations',
            'manage_options',
            'fmm-invitations',
            array(__CLASS__, 'render_invitations_page')
        );
        
        add_submenu_page(
            'family-media-manager',
            'Categories',
            'Categories',
            'manage_options',
            'fmm-categories',
            array(__CLASS__, 'render_categories_page')
        );
        
        add_submenu_page(
            'family-media-manager',
            'Settings',
            'Settings',
            'manage_options',
            'fmm-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }
    
    /**
     * Render media page
     */
    public static function render_media_page() {
        $categories = FMM_Media_Manager::get_categories();
        $media = FMM_Media_Manager::get_media();
        
        include FMM_PLUGIN_DIR . 'templates/admin/media-library.php';
    }
    
    /**
     * Render invitations page
     */
    public static function render_invitations_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_invites';
        $invites = $wpdb->get_results("SELECT * FROM $table ORDER BY invited_date DESC");
        
        include FMM_PLUGIN_DIR . 'templates/admin/invitations.php';
    }
    
    /**
     * Render categories page
     */
    public static function render_categories_page() {
        $categories = FMM_Media_Manager::get_categories();
        
        include FMM_PLUGIN_DIR . 'templates/admin/categories.php';
    }
    
    /**
     * Render settings page
     */
    public static function render_settings_page() {
        // Handle form submission
        if (isset($_POST['fmm_save_settings'])) {
            check_admin_referer('fmm_settings');
            
            update_option('fmm_allow_registration', isset($_POST['allow_registration']) ? '1' : '0');
            update_option('fmm_send_email_notifications', isset($_POST['send_email']) ? '1' : '0');
            update_option('fmm_registration_page', intval($_POST['registration_page']));
            
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        include FMM_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * AJAX handler for creating category
     */
    public static function ajax_create_category() {
        check_ajax_referer('fmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        
        $result = FMM_Media_Manager::create_category($name, $description);
        
        if ($result) {
            wp_send_json_success(array('category_id' => $result));
        } else {
            wp_send_json_error('Failed to create category');
        }
    }
}
