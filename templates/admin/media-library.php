<?php
/**
 * Admin Media Library Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <div class="fmm-admin-header">
        <h1>Media Library</h1>
    </div>
    
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
    
    <div class="fmm-admin-card">
        <h2>All Videos (<?php echo count($media); ?>)</h2>
        
        <?php if (empty($media)): ?>
            <p>No videos uploaded yet.</p>
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
                                        VIDEO
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
});
</script>
