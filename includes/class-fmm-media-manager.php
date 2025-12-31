<?php
/**
 * Media Manager class for handling video files
 */

if (!defined('ABSPATH')) {
    exit;
}

class FMM_Media_Manager {
    
    public static function init() {
        add_action('wp_ajax_fmm_upload_video', array(__CLASS__, 'ajax_upload_video'));
        add_action('wp_ajax_fmm_delete_video', array(__CLASS__, 'ajax_delete_video'));
        add_action('wp_ajax_fmm_update_video', array(__CLASS__, 'ajax_update_video'));
        add_action('wp_ajax_fmm_search_videos', array(__CLASS__, 'ajax_search_videos'));
        add_action('wp_ajax_nopriv_fmm_search_videos', array(__CLASS__, 'ajax_search_videos'));
    }
    
    /**
     * Get all media files
     */
    public static function get_media($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        
        $defaults = array(
            'category_id' => null,
            'search' => '',
            'orderby' => 'upload_date',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $prepare_args = array();
        
        if ($args['category_id']) {
            $where[] = 'category_id = %d';
            $prepare_args[] = $args['category_id'];
        }
        
        if ($args['search']) {
            $where[] = '(title LIKE %s OR description LIKE %s OR filename LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $prepare_args[] = $search_term;
            $prepare_args[] = $search_term;
            $prepare_args[] = $search_term;
        }
        
        $where_sql = implode(' AND ', $where);
        $order_sql = sprintf('%s %s', sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']), '');
        
        $limit_sql = '';
        if ($args['limit'] > 0) {
            $limit_sql = $wpdb->prepare('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }
        
        $query = "SELECT * FROM $table WHERE $where_sql ORDER BY $order_sql $limit_sql";
        
        if (!empty($prepare_args)) {
            $query = $wpdb->prepare($query, $prepare_args);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get single media file
     */
    public static function get_media_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Upload video file
     */
    public static function upload_video($file, $title, $description = '', $category_id = null) {
        // Validate file type
        $allowed_types = array('video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo');
        
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_type', 'Invalid file type. Only video files are allowed.');
        }
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        $fmm_dir = $upload_dir['basedir'] . '/' . FMM_UPLOAD_DIR;
        
        // Generate unique filename
        $filename = sanitize_file_name($file['name']);
        $file_path = $fmm_dir . '/' . $filename;
        
        // Make filename unique if needed
        $counter = 1;
        $path_parts = pathinfo($filename);
        while (file_exists($file_path)) {
            $filename = $path_parts['filename'] . '-' . $counter . '.' . $path_parts['extension'];
            $file_path = $fmm_dir . '/' . $filename;
            $counter++;
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return new WP_Error('upload_failed', 'Failed to upload file');
        }
        
        // Generate thumbnail
        $thumbnail = self::generate_thumbnail($file_path, $filename);
        
        // Get file info
        $filesize = filesize($file_path);
        $duration = self::get_video_duration($file_path);
        
        // Insert into database
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        
        $inserted = $wpdb->insert($table, array(
            'filename' => $filename,
            'filepath' => $file_path,
            'title' => $title,
            'description' => $description,
            'category_id' => $category_id,
            'filesize' => $filesize,
            'duration' => $duration,
            'thumbnail' => $thumbnail,
            'uploaded_by' => get_current_user_id()
        ));
        
        if (!$inserted) {
            unlink($file_path);
            return new WP_Error('db_error', 'Failed to save file information');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Generate video thumbnail
     */
    private static function generate_thumbnail($video_path, $filename) {
        $upload_dir = wp_upload_dir();
        $thumbnails_dir = $upload_dir['basedir'] . '/' . FMM_UPLOAD_DIR . '/thumbnails';
        
        $path_parts = pathinfo($filename);
        $thumbnail_filename = $path_parts['filename'] . '.jpg';
        $thumbnail_path = $thumbnails_dir . '/' . $thumbnail_filename;
        
        // Try using ffmpeg if available
        if (function_exists('exec')) {
            $ffmpeg_cmd = sprintf(
                'ffmpeg -i %s -ss 00:00:01.000 -vframes 1 %s 2>&1',
                escapeshellarg($video_path),
                escapeshellarg($thumbnail_path)
            );
            
            @exec($ffmpeg_cmd, $output, $return_var);
            
            if ($return_var === 0 && file_exists($thumbnail_path)) {
                return $thumbnail_path;
            }
        }
        
        // Fallback: create a placeholder thumbnail
        self::create_placeholder_thumbnail($thumbnail_path);
        
        return $thumbnail_path;
    }
    
    /**
     * Create placeholder thumbnail
     */
    private static function create_placeholder_thumbnail($thumbnail_path) {
        $width = 320;
        $height = 180;
        $image = imagecreatetruecolor($width, $height);
        
        $bg_color = imagecolorallocate($image, 45, 45, 45);
        $text_color = imagecolorallocate($image, 200, 200, 200);
        
        imagefill($image, 0, 0, $bg_color);
        
        $text = 'VIDEO';
        imagestring($image, 5, ($width / 2) - 25, ($height / 2) - 10, $text, $text_color);
        
        imagejpeg($image, $thumbnail_path, 80);
        imagedestroy($image);
    }
    
    /**
     * Get video duration
     */
    private static function get_video_duration($video_path) {
        if (function_exists('exec')) {
            $ffmpeg_cmd = sprintf(
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
                escapeshellarg($video_path)
            );
            
            @exec($ffmpeg_cmd, $output, $return_var);
            
            if ($return_var === 0 && isset($output[0]) && is_numeric($output[0])) {
                $seconds = (int)$output[0];
                return gmdate('H:i:s', $seconds);
            }
        }
        
        return null;
    }
    
    /**
     * Delete video
     */
    public static function delete_video($id) {
        $media = self::get_media_by_id($id);
        
        if (!$media) {
            return new WP_Error('not_found', 'Media file not found');
        }
        
        // Delete files
        if (file_exists($media->filepath)) {
            unlink($media->filepath);
        }
        
        if ($media->thumbnail && file_exists($media->thumbnail)) {
            unlink($media->thumbnail);
        }
        
        // Delete from database
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        
        $deleted = $wpdb->delete($table, array('id' => $id));
        
        return $deleted ? true : new WP_Error('delete_failed', 'Failed to delete media');
    }
    
    /**
     * Update video information
     */
    public static function update_video($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        
        $allowed_fields = array('title', 'description', 'category_id');
        $update_data = array();
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No valid data to update');
        }
        
        $updated = $wpdb->update($table, $update_data, array('id' => $id));
        
        return $updated !== false;
    }
    
    /**
     * Increment download count
     */
    public static function increment_download_count($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET download_count = download_count + 1 WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Increment view count
     */
    public static function increment_view_count($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET view_count = view_count + 1 WHERE id = %d",
            $id
        ));
    }
    
    /**
     * AJAX handler for uploading video
     */
    public static function ajax_upload_video() {
        check_ajax_referer('fmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        if (!isset($_FILES['video'])) {
            wp_send_json_error('No file uploaded');
            return;
        }
        
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
        
        $result = self::upload_video($_FILES['video'], $title, $description, $category_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('media_id' => $result));
        }
    }
    
    /**
     * AJAX handler for deleting video
     */
    public static function ajax_delete_video() {
        check_ajax_referer('fmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $id = intval($_POST['media_id']);
        $result = self::delete_video($id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Video deleted');
        }
    }
    
    /**
     * AJAX handler for updating video
     */
    public static function ajax_update_video() {
        check_ajax_referer('fmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $id = intval($_POST['media_id']);
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'category_id' => isset($_POST['category_id']) ? intval($_POST['category_id']) : null
        );
        
        $result = self::update_video($id, $data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Video updated');
        }
    }
    
    /**
     * AJAX handler for searching videos
     */
    public static function ajax_search_videos() {
        $search = sanitize_text_field($_POST['search']);
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
        
        $videos = self::get_media(array(
            'search' => $search,
            'category_id' => $category_id
        ));
        
        wp_send_json_success($videos);
    }
    
    /**
     * Get all categories
     */
    public static function get_categories() {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_categories';
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
    }
    
    /**
     * Create category
     */
    public static function create_category($name, $description = '', $parent_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_categories';
        
        $slug = sanitize_title($name);
        
        $inserted = $wpdb->insert($table, array(
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'parent_id' => $parent_id
        ));
        
        return $inserted ? $wpdb->insert_id : false;
    }
}
