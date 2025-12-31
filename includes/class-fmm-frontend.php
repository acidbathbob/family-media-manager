<?php
/**
 * Frontend class for user-facing pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class FMM_Frontend {
    
    public static function init() {
        add_shortcode('fmm_media_library', array(__CLASS__, 'media_library_shortcode'));
        add_shortcode('fmm_video_player', array(__CLASS__, 'video_player_shortcode'));
    }
    
    /**
     * Media library shortcode
     */
    public static function media_library_shortcode($atts) {
        // Check access
        if (!FMM_Access_Control::user_has_access()) {
            return '<div class="fmm-error">You do not have permission to access this content. Please <a href="' . wp_login_url(get_permalink()) . '">log in</a>.</div>';
        }
        
        $atts = shortcode_atts(array(
            'category' => '',
            'columns' => '3',
            'show_search' => 'yes'
        ), $atts);
        
        ob_start();
        include FMM_PLUGIN_DIR . 'templates/media-library.php';
        return ob_get_clean();
    }
    
    /**
     * Video player shortcode
     */
    public static function video_player_shortcode($atts) {
        // Check access
        if (!FMM_Access_Control::user_has_access()) {
            return '<div class="fmm-error">You do not have permission to access this content.</div>';
        }
        
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        $media_id = intval($atts['id']);
        
        if (!$media_id) {
            return '<div class="fmm-error">Invalid video ID</div>';
        }
        
        $media = FMM_Media_Manager::get_media_by_id($media_id);
        
        if (!$media) {
            return '<div class="fmm-error">Video not found</div>';
        }
        
        ob_start();
        include FMM_PLUGIN_DIR . 'templates/video-player.php';
        return ob_get_clean();
    }
    
    /**
     * Format file size
     */
    public static function format_filesize($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get thumbnail URL
     */
    public static function get_thumbnail_url($thumbnail_path) {
        if (!$thumbnail_path || !file_exists($thumbnail_path)) {
            return FMM_PLUGIN_URL . 'assets/images/video-placeholder.png';
        }
        
        $upload_dir = wp_upload_dir();
        $thumbnail_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $thumbnail_path);
        
        return $thumbnail_url;
    }
}
