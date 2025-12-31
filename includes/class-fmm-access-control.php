<?php
/**
 * Access Control class for secure media access
 */

if (!defined('ABSPATH')) {
    exit;
}

class FMM_Access_Control {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'register_download_endpoint'));
        add_action('init', array(__CLASS__, 'register_stream_endpoint'));
        add_action('template_redirect', array(__CLASS__, 'handle_download'));
        add_action('template_redirect', array(__CLASS__, 'handle_stream'));
    }
    
    /**
     * Register download endpoint
     */
    public static function register_download_endpoint() {
        add_rewrite_rule(
            '^fmm-download/([0-9]+)/?$',
            'index.php?fmm_download=$matches[1]',
            'top'
        );
        add_rewrite_tag('%fmm_download%', '([0-9]+)');
    }
    
    /**
     * Register stream endpoint
     */
    public static function register_stream_endpoint() {
        add_rewrite_rule(
            '^fmm-stream/([0-9]+)/?$',
            'index.php?fmm_stream=$matches[1]',
            'top'
        );
        add_rewrite_tag('%fmm_stream%', '([0-9]+)');
    }
    
    /**
     * Check if user has access to media
     */
    public static function user_has_access($user_id = null, $media_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        
        // Admins always have access
        if (user_can($user, 'manage_options')) {
            return true;
        }
        
        // Check if user has family member role or capability
        if (user_can($user, 'fmm_access_media')) {
            return true;
        }
        
        // Check if user is registered through invitation
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_invites';
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = 'registered'",
            $user_id
        ));
        
        if (!$invite) {
            return false;
        }
        
        // If media_id is provided, check category permissions
        if ($media_id !== null) {
            $media = FMM_Media_Manager::get_media_by_id($media_id);
            
            if ($media && $media->category_id) {
                return FMM_Media_Manager::user_can_access_category($user_id, $media->category_id);
            }
        }
        
        return true;
    }
    
    /**
     * Handle download request
     */
    public static function handle_download() {
        $media_id = get_query_var('fmm_download');
        
        if (!$media_id) {
            return;
        }
        
        // Check access
        if (!self::user_has_access(null, $media_id)) {
            wp_die('You do not have permission to download this file.', 'Access Denied', array('response' => 403));
        }
        
        // Get media file
        $media = FMM_Media_Manager::get_media_by_id($media_id);
        
        if (!$media) {
            wp_die('File not found.', 'Not Found', array('response' => 404));
        }
        
        // Check if file exists
        if (!file_exists($media->filepath)) {
            wp_die('File not found on server.', 'Not Found', array('response' => 404));
        }
        
        // Increment download count
        FMM_Media_Manager::increment_download_count($media_id);
        
        // Serve file
        self::serve_file($media->filepath, $media->filename, 'attachment');
        
        exit;
    }
    
    /**
     * Handle stream request
     */
    public static function handle_stream() {
        $media_id = get_query_var('fmm_stream');
        
        if (!$media_id) {
            return;
        }
        
        // Check access
        if (!self::user_has_access(null, $media_id)) {
            wp_die('You do not have permission to view this file.', 'Access Denied', array('response' => 403));
        }
        
        // Get media file
        $media = FMM_Media_Manager::get_media_by_id($media_id);
        
        if (!$media) {
            wp_die('File not found.', 'Not Found', array('response' => 404));
        }
        
        // Check if file exists
        if (!file_exists($media->filepath)) {
            wp_die('File not found on server.', 'Not Found', array('response' => 404));
        }
        
        // Increment view count
        FMM_Media_Manager::increment_view_count($media_id);
        
        // Serve file for streaming
        self::serve_file($media->filepath, $media->filename, 'inline', true);
        
        exit;
    }
    
    /**
     * Serve file with proper headers
     */
    private static function serve_file($filepath, $filename, $disposition = 'attachment', $support_range = false) {
        // Clear output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Get file info
        $filesize = filesize($filepath);
        $mime_type = mime_content_type($filepath);
        
        // Set headers
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Handle range requests for streaming
        if ($support_range && isset($_SERVER['HTTP_RANGE'])) {
            self::serve_file_range($filepath, $filesize);
        } else {
            header('Content-Length: ' . $filesize);
            readfile($filepath);
        }
    }
    
    /**
     * Serve file with range support (for video streaming)
     */
    private static function serve_file_range($filepath, $filesize) {
        $range = $_SERVER['HTTP_RANGE'];
        $range = str_replace('bytes=', '', $range);
        $range = explode('-', $range);
        
        $start = intval($range[0]);
        $end = isset($range[1]) && !empty($range[1]) ? intval($range[1]) : $filesize - 1;
        
        $length = $end - $start + 1;
        
        header('HTTP/1.1 206 Partial Content');
        header('Content-Length: ' . $length);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $filesize);
        
        $file = fopen($filepath, 'rb');
        fseek($file, $start);
        
        $buffer_size = 8192;
        $bytes_sent = 0;
        
        while (!feof($file) && $bytes_sent < $length) {
            $bytes_to_read = min($buffer_size, $length - $bytes_sent);
            echo fread($file, $bytes_to_read);
            $bytes_sent += $bytes_to_read;
            flush();
        }
        
        fclose($file);
    }
    
    /**
     * Get download URL for media
     */
    public static function get_download_url($media_id) {
        return home_url('/fmm-download/' . $media_id . '/');
    }
    
    /**
     * Get stream URL for media
     */
    public static function get_stream_url($media_id) {
        return home_url('/fmm-stream/' . $media_id . '/');
    }
}
