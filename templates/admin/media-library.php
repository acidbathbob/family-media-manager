<?php
/**
 * Enhanced Admin Media Library Page with Server File Browser
 * Replace the existing templates/admin/media-library.php with this file
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle server file import
if (isset($_POST['import_server_files']) && isset($_POST['server_files'])) {
    check_admin_referer('fmm_import_server_files');
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($_POST['server_files'] as $filepath) {
        $filepath = sanitize_text_field($filepath);
        
        if (!file_exists($filepath)) {
            continue;
        }
        
        $filename = basename($filepath);
        
        // Check if already in database
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE filename = %s",
            $filename
        ));
        
        if ($exists > 0) {
            $skipped++;
            continue;
        }
        
        // Import the file
        $upload_dir = wp_upload_dir();
        $thumbnails_dir = $upload_dir['basedir'] . '/family-videos/thumbnails';
        
        // Generate thumbnail
        $path_parts = pathinfo($filename);
        $thumbnail_filename = $path_parts['filename'] . '.jpg';
        $thumbnail_path = $thumbnails_dir . '/' . $thumbnail_filename;
        
        if (!file_exists($thumbnail_path)) {
            // Try ffmpeg first
            $ffmpeg_cmd = sprintf(
                'ffmpeg -i %s -ss 00:00:01.000 -vframes 1 -vf scale=320:180 %s 2>&1',
                escapeshellarg($filepath),
                escapeshellarg($thumbnail_path)
            );
            @exec($ffmpeg_cmd, $output, $return_var);
            
            // Create placeholder if ffmpeg failed
            if (!file_exists($thumbnail_path)) {
                $image = imagecreatetruecolor(320, 180);
                $bg = imagecolorallocate($image, 45, 45, 45);
                $text_color = imagecolorallocate($image, 200, 200, 200);
                imagefill($image, 0, 0, $bg);
                imagestring($image, 5, 130, 85, 'VIDEO', $text_color);
                imagejpeg($image, $thumbnail_path, 80);
                imagedestroy($image);
            }
        }
        
        // Get video info
        $filesize = filesize($filepath);
        $title = ucwords(str_replace(array('-', '_'), ' ', $path_parts['filename']));
        
        // Get duration
        $duration = null;
        $ffprobe_cmd = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($filepath)
        );
        @exec($ffprobe_cmd, $duration_output, $return_var);
        if ($return_var === 0 && isset($duration_output[0]) && is_numeric($duration_output[0])) {
            $seconds = (int)$duration_output[0];
            $duration = gmdate('H:i:s', $seconds);
        }
        
        // Insert into database
        $inserted = $wpdb->insert($table, array(
            'filename' => $filename,
            'filepath' => $filepath,
            'title' => $title,
            'description' => '',
            'category_id' => null,
            'filesize' => $filesize,
            'duration' => $duration,
            'thumbnail' => $thumbnail_path,
            'uploaded_by' => get_current_user_id(),
            'upload_date' => current_time('mysql')
        ));
        
        if ($inserted) {
            $imported++;
        }
    }
    
    echo '<div class="notice notice-success"><p>✅ Imported ' . $imported . ' videos. Skipped ' . $skipped . ' duplicates.</p></div>';
}
?>

<div class="wrap">
    <div class="fmm-admin-header">
        <h1>Media Library</h1>
    </div>
    
    <!-- Server File Browser -->
    <div class="fmm-admin-card" style="background: #f0f8ff; border-left: 4px solid #0073aa;">
        <h2>🔍 Import from Server</h2>
        <p>Browse and import existing video files from your server into the media library.</p>
        
        <?php
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/family-videos';
        $search_query = isset($_GET['server_search']) ? sanitize_text_field($_GET['server_search']) : '';
        
        // Scan for videos on server
        $server_videos = array();
        if (is_dir($base_dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            $video_extensions = array('mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv');
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, $video_extensions)) {
                        // Skip thumbnails directory
                        if (strpos($file->getPathname(), '/thumbnails/') === false) {
                            $filename = $file->getFilename();
                            
                            // Apply search filter
                            if ($search_query === '' || stripos($filename, $search_query) !== false) {
                                $server_videos[] = array(
                                    'path' => $file->getPathname(),
                                    'filename' => $filename,
                                    'size' => $file->getSize(),
                                    'modified' => $file->getMTime()
                                );
                            }
                        }
                    }
                }
            }
            
            // Sort by modified date (newest first)
            usort($server_videos, function($a, $b) {
                return $b['modified'] - $a['modified'];
            });
        }
        
        // Check which are already in database
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_media';
        $existing_files = $wpdb->get_col("SELECT filename FROM $table");
        ?>
        
        <form method="get" style="margin-bottom: 15px;">
            <input type="hidden" name="page" value="fmm-media-library">
            <input type="text" 
                   name="server_search" 
                   placeholder="Search server files..." 
                   value="<?php echo esc_attr($search_query); ?>"
                   style="width: 300px; padding: 6px 12px;">
            <button type="submit" class="button">Search</button>
            <?php if ($search_query): ?>
                <a href="?page=fmm-media-library" class="button">Clear</a>
            <?php endif; ?>
        </form>
        
        <?php if (empty($server_videos)): ?>
            <p>No video files found on server<?php echo $search_query ? ' matching "' . esc_html($search_query) . '"' : ''; ?>.</p>
        <?php else: ?>
            <form method="post">
                <?php wp_nonce_field('fmm_import_server_files'); ?>
                
                <p><strong>Found <?php echo count($server_videos); ?> video<?php echo count($server_videos) != 1 ? 's' : ''; ?> on server</strong></p>
                
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: white;">
                    <?php foreach ($server_videos as $video): ?>
                        <?php 
                        $already_imported = in_array($video['filename'], $existing_files);
                        ?>
                        <div style="padding: 10px; margin: 5px 0; background: <?php echo $already_imported ? '#fff3cd' : '#f8f9fa'; ?>; border-radius: 4px; display: flex; align-items: center; gap: 10px;">
                            <?php if (!$already_imported): ?>
                                <input type="checkbox" 
                                       name="server_files[]" 
                                       value="<?php echo esc_attr($video['path']); ?>"
                                       id="file_<?php echo md5($video['path']); ?>">
                            <?php else: ?>
                                <span style="width: 20px; text-align: center;">✓</span>
                            <?php endif; ?>
                            
                            <label for="file_<?php echo md5($video['path']); ?>" style="flex: 1; margin: 0;">
                                <strong><?php echo esc_html($video['filename']); ?></strong><br>
                                <small style="color: #666;">
                                    <?php echo size_format($video['size']); ?> • 
                                    Modified: <?php echo date('Y-m-d H:i', $video['modified']); ?>
                                    <?php if ($already_imported): ?>
                                        • <span style="color: #856404;">Already in library</span>
                                    <?php endif; ?>
                                </small>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 15px;">
                    <button type="button" onclick="jQuery('input[name=\'server_files[]\']').prop('checked', true);" class="button">Select All</button>
                    <button type="button" onclick="jQuery('input[name=\'server_files[]\']').prop('checked', false);" class="button">Deselect All</button>
                    <button type="submit" name="import_server_files" class="button button-primary" style="margin-left: 10px;">
                        📥 Import Selected Videos
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Regular Upload Form -->
    <div class="fmm-admin-card">
        <h2>Upload New Video</h2>
        <form id="fmm-upload-video-form" enctype="multipart/form-data">
            <div class="fmm-form-group">
                <label for="video_file">Video File *</label>
                <input type="file" id="video_file" name="video" accept="video/*" required>
                <p class="description">Accepted formats: MP4, WebM, OGG, MOV, AVI</p>
            </div>
            
            <div class="fmm-form-group">
                <label for="video_title">Title *</label>
                <input type="text" id="video_title" name="title" required>
            </div>
            
            <div class="fmm-form-group">
                <label for="video_description">Description</label>
                <textarea id="video_description" name="description" rows="3"></textarea>
            </div>
            
            <div class="fmm-form-group">
                <label for="video_category">Category</label>
                <select id="video_category" name="category_id">
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->id); ?>">
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="button button-primary">Upload Video</button>
            <span id="upload-progress" style="margin-left: 10px;"></span>
        </form>
    </div>
    
    <!-- Video Library -->
    <div class="fmm-admin-card">
        <h2>Import Existing Videos from Server</h2>
        <p class="description">Select videos that are already on the server but not yet in the media library.</p>
        <form id="fmm-import-videos-form">
            <?php
            // Get videos from filesystem
            $video_dir = wp_upload_dir()['basedir'] . '/family-videos/';
            $filesystem_files = glob($video_dir . '*.{mp4,mov,avi,webm,ogg}', GLOB_BRACE);
            
            // Get videos already in database
            global $wpdb;
            $db_files = $wpdb->get_col("SELECT filename FROM {$wpdb->prefix}fmm_media");
            
            // Find videos not yet imported
            $unimported = array();
            foreach ($filesystem_files as $file) {
                $filename = basename($file);
                if (!in_array($filename, $db_files)) {
                    $unimported[] = array(
                        'filename' => $filename,
                        'filepath' => $file,
                        'size' => filesize($file)
                    );
                }
            }
            
            if (empty($unimported)): ?>
                <p><em>All videos from the server are already in the media library.</em></p>
            <?php else: ?>
                <table class="widefat" style="margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all-videos"></th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unimported as $video): ?>
                            <tr>
                                <td><input type="checkbox" name="import_videos[]" value="<?php echo esc_attr($video['filename']); ?>"></td>
                                <td><?php echo esc_html($video['filename']); ?></td>
                                <td><?php echo FMM_Frontend::format_filesize($video['size']); ?></td>
                                <td>
                                    <select name="category_<?php echo esc_attr($video['filename']); ?>">
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="button button-primary">Import Selected Videos</button>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="fmm-admin-card">
        <h2>Import Existing Videos from Server</h2>
        <p class="description">Select videos that are already on the server but not yet in the media library.</p>
        <form id="fmm-import-videos-form">
            <?php
            // Get videos from filesystem
            $video_dir = wp_upload_dir()['basedir'] . '/family-videos/';
            $filesystem_files = glob($video_dir . '*.{mp4,mov,avi,webm,ogg}', GLOB_BRACE);
            
            // Get videos already in database
            global $wpdb;
            $db_files = $wpdb->get_col("SELECT filename FROM {$wpdb->prefix}fmm_media");
            
            // Find videos not yet imported
            $unimported = array();
            foreach ($filesystem_files as $file) {
                $filename = basename($file);
                if (!in_array($filename, $db_files)) {
                    $unimported[] = array(
                        'filename' => $filename,
                        'filepath' => $file,
                        'size' => filesize($file)
                    );
                }
            }
            
            if (empty($unimported)): ?>
                <p><em>All videos from the server are already in the media library.</em></p>
            <?php else: ?>
                <table class="widefat" style="margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all-videos"></th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unimported as $video): ?>
                            <tr>
                                <td><input type="checkbox" name="import_videos[]" value="<?php echo esc_attr($video['filename']); ?>"></td>
                                <td><?php echo esc_html($video['filename']); ?></td>
                                <td><?php echo FMM_Frontend::format_filesize($video['size']); ?></td>
                                <td>
                                    <select name="category_<?php echo esc_attr($video['filename']); ?>">
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="button button-primary">Import Selected Videos</button>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="fmm-admin-card">
        <h2>All Videos (<?php echo count($media); ?>)</h2>
        
        <?php if (empty($media)): ?>
            <p>No videos in library yet. Import from server or upload new videos above.</p>
        <?php else: ?>
            <table class="fmm-admin-table widefat">
                <thead>
                    <tr>
                        <th style="width: 80px;">Thumbnail</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>File Size</th>
                        <th>Duration</th>
                        <th>Views</th>
                        <th>Downloads</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($media as $video): ?>
                        <tr>
                            <td>
                                <?php if ($video->thumbnail && file_exists($video->thumbnail)): ?>
                                    <?php 
                                    $upload_dir = wp_upload_dir();
                                    $thumb_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $video->thumbnail);
                                    ?>
                                    <img src="<?php echo esc_url($thumb_url); ?>" 
                                         style="width: 80px; height: 45px; object-fit: cover; border-radius: 4px;"
                                         alt="<?php echo esc_attr($video->title); ?>">
                                <?php else: ?>
                                    <div style="width: 80px; height: 45px; background: #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #666;">
                                        NO THUMB
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($video->title); ?></strong>
                                <?php if ($video->description): ?>
                                    <br><small><?php echo esc_html(wp_trim_words($video->description, 10)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($video->category_id) {
                                    $cat = array_filter($categories, function($c) use ($video) {
                                        return $c->id == $video->category_id;
                                    });
                                    $cat = reset($cat);
                                    echo $cat ? esc_html($cat->name) : '—';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td><?php echo FMM_Frontend::format_filesize($video->filesize); ?></td>
                            <td><?php echo $video->duration ? esc_html($video->duration) : '—'; ?></td>
                            <td><?php echo intval($video->view_count); ?></td>
                            <td><?php echo intval($video->download_count); ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($video->upload_date))); ?></td>
                            <td>
                                <select class="fmm-change-category" data-media-id="<?php echo esc_attr($video->id); ?>" style="max-width: 150px; margin-bottom: 5px;">
                                    <option value="">— Set Category —</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo esc_attr($cat->id); ?>" 
                                                <?php selected($video->category_id, $cat->id); ?>>
                                            <?php echo esc_html($cat->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <br>
                                <a href="<?php echo esc_url(FMM_Access_Control::get_stream_url($video->id)); ?>" 
                                   target="_blank" 
                                   class="button button-small">
                                    Preview
                                </a>
                                <button class="button button-small fmm-delete-video" 
                                        data-media-id="<?php echo esc_attr($video->id); ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#fmm-upload-video-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'fmm_upload_video');
        formData.append('nonce', fmm_admin_ajax.nonce);
        
        $('#upload-progress').html('<span style="color: #0073aa;">Uploading...</span>');
        $('button[type="submit"]').prop('disabled', true);
        
        $.ajax({
            url: fmm_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('#upload-progress').html('<span style="color: #0073aa;">Uploading: ' + Math.round(percentComplete) + '%</span>');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    $('#upload-progress').html('<span style="color: #2e7d32;">✓ Upload complete!</span>');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $('#upload-progress').html('<span style="color: #c62828;">✗ Error: ' + response.data + '</span>');
                    $('button[type="submit"]').prop('disabled', false);
                }
            },
            error: function() {
                $('#upload-progress').html('<span style="color: #c62828;">✗ Upload failed</span>');
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    });
    
    // Handle select all checkbox
    $('#select-all-videos').on('change', function() {
        $('input[name="import_videos[]"]').prop('checked', $(this).is(':checked'));
    });
    
    // Handle import form
    $('#fmm-import-videos-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'fmm_import_videos'});
        formData.push({name: 'nonce', value: fmm_admin_ajax.nonce});
        
        $.post(fmm_admin_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Videos imported successfully!\n\n' + response.data.message);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Handle select all checkbox
    $('#select-all-videos').on('change', function() {
        $('input[name="import_videos[]"]').prop('checked', $(this).is(':checked'));
    });
    
    // Handle import form
    $('#fmm-import-videos-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'fmm_import_videos'});
        formData.push({name: 'nonce', value: fmm_admin_ajax.nonce});
        
        $.post(fmm_admin_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Videos imported successfully!\n\n' + response.data.message);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Handle category change
    $('.fmm-change-category').on('change', function() {
        var mediaId = $(this).data('media-id');
        var categoryId = $(this).val();
        
        $.post(fmm_admin_ajax.ajax_url, {
            action: 'fmm_update_video',
            nonce: fmm_admin_ajax.nonce,
            media_id: mediaId,
            category_id: categoryId
        }, function(response) {
            if (response.success) {
                alert('Category updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
</script>
