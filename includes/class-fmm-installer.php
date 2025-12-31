<?php
/**
 * Installer class for Family Media Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class FMM_Installer {
    
    /**
     * Run on plugin activation
     */
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for invited users
        $table_invites = $wpdb->prefix . 'fmm_invites';
        $sql_invites = "CREATE TABLE IF NOT EXISTS $table_invites (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            invite_code varchar(64) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            invited_by bigint(20) NOT NULL,
            invited_date datetime DEFAULT CURRENT_TIMESTAMP,
            registered_date datetime DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            UNIQUE KEY invite_code (invite_code)
        ) $charset_collate;";
        
        // Table for media files
        $table_media = $wpdb->prefix . 'fmm_media';
        $sql_media = "CREATE TABLE IF NOT EXISTS $table_media (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            filepath varchar(500) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            category_id mediumint(9) DEFAULT NULL,
            filesize bigint(20) DEFAULT NULL,
            duration varchar(20) DEFAULT NULL,
            thumbnail varchar(500) DEFAULT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            uploaded_by bigint(20) NOT NULL,
            download_count int DEFAULT 0,
            view_count int DEFAULT 0,
            PRIMARY KEY  (id),
            KEY category_id (category_id)
        ) $charset_collate;";
        
        // Table for categories
        $table_categories = $wpdb->prefix . 'fmm_categories';
        $sql_categories = "CREATE TABLE IF NOT EXISTS $table_categories (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            parent_id mediumint(9) DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_invites);
        dbDelta($sql_media);
        dbDelta($sql_categories);
        
        // Create upload directory
        self::create_upload_directory();
        
        // Create default category
        self::create_default_category();
        
        // Set default options
        add_option('fmm_version', FMM_VERSION);
        add_option('fmm_allow_registration', '1');
        add_option('fmm_send_email_notifications', '1');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Run on plugin deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create upload directory
     */
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $fmm_dir = $upload_dir['basedir'] . '/' . FMM_UPLOAD_DIR;
        
        if (!file_exists($fmm_dir)) {
            wp_mkdir_p($fmm_dir);
            
            // Create .htaccess for security
            $htaccess_content = "# Deny direct access\n";
            $htaccess_content .= "Order Deny,Allow\n";
            $htaccess_content .= "Deny from all\n";
            file_put_contents($fmm_dir . '/.htaccess', $htaccess_content);
            
            // Create index.php
            file_put_contents($fmm_dir . '/index.php', '<?php // Silence is golden');
        }
        
        // Create thumbnails directory
        $thumbnails_dir = $fmm_dir . '/thumbnails';
        if (!file_exists($thumbnails_dir)) {
            wp_mkdir_p($thumbnails_dir);
            file_put_contents($thumbnails_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * Create default category
     */
    private static function create_default_category() {
        global $wpdb;
        $table_categories = $wpdb->prefix . 'fmm_categories';
        
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_categories");
        
        if ($exists == 0) {
            $wpdb->insert($table_categories, array(
                'name' => 'Uncategorized',
                'slug' => 'uncategorized',
                'description' => 'Default category for media files'
            ));
        }
    }
}
