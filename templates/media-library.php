<?php
/**
 * Media Library Frontend Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$categories = FMM_Media_Manager::get_categories();
$selected_category = isset($_GET['fmm_category']) ? intval($_GET['fmm_category']) : null;
$search = isset($_GET['fmm_search']) ? sanitize_text_field($_GET['fmm_search']) : '';

$media = FMM_Media_Manager::get_media(array(
    'category_id' => $selected_category,
    'search' => $search
));
?>

<div class="fmm-media-library">
    
    <?php if ($atts['show_search'] === 'yes'): ?>
    <div class="fmm-filters">
        <form method="get" class="fmm-filter-form">
            <input type="text" 
                   name="fmm_search" 
                   placeholder="Search videos..." 
                   value="<?php echo esc_attr($search); ?>">
            
            <select name="fmm_category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->id); ?>" 
                            <?php selected($selected_category, $cat->id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="button">Search</button>
            
            <?php if ($search || $selected_category): ?>
                <a href="<?php echo esc_url(remove_query_arg(array('fmm_search', 'fmm_category'))); ?>" 
                   class="button">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="fmm-media-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        <?php if (empty($media)): ?>
            <p>No videos found.</p>
        <?php else: ?>
            <?php foreach ($media as $video): ?>
                <div class="fmm-media-item">
                    <div class="fmm-media-thumbnail">
                        <img src="<?php echo esc_url(FMM_Frontend::get_thumbnail_url($video->thumbnail)); ?>" 
                             alt="<?php echo esc_attr($video->title); ?>">
                        <div class="fmm-media-overlay">
                            <span class="fmm-play-icon">▶</span>
                        </div>
                    </div>
                    
                    <div class="fmm-media-info">
                        <h3><?php echo esc_html($video->title); ?></h3>
                        
                        <?php if ($video->description): ?>
                            <p class="fmm-description"><?php echo esc_html(wp_trim_words($video->description, 15)); ?></p>
                        <?php endif; ?>
                        
                        <div class="fmm-meta">
                            <?php if ($video->duration): ?>
                                <span class="fmm-duration">⏱ <?php echo esc_html($video->duration); ?></span>
                            <?php endif; ?>
                            <span class="fmm-size">📦 <?php echo FMM_Frontend::format_filesize($video->filesize); ?></span>
                        </div>
                        
                        <div class="fmm-actions">
                            <a href="<?php echo esc_url(FMM_Access_Control::get_stream_url($video->id)); ?>" 
                               class="button fmm-watch-btn" 
                               data-video-id="<?php echo esc_attr($video->id); ?>">
                                Watch
                            </a>
                            <a href="<?php echo esc_url(FMM_Access_Control::get_download_url($video->id)); ?>" 
                               class="button button-secondary" 
                               download>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Video Modal -->
<div id="fmm-video-modal" class="fmm-modal" style="display:none;">
    <div class="fmm-modal-content">
        <span class="fmm-modal-close">&times;</span>
        <div id="fmm-modal-video"></div>
    </div>
</div>

<style>
.fmm-media-library {
    padding: 20px 0;
}
.fmm-filters {
    margin-bottom: 30px;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 8px;
}
.fmm-filter-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.fmm-filter-form input[type="text"] {
    flex: 1;
    min-width: 200px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.fmm-filter-form select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.fmm-media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.fmm-media-grid[data-columns="2"] {
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
}
.fmm-media-grid[data-columns="4"] {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
}
.fmm-media-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.fmm-media-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.fmm-media-thumbnail {
    position: relative;
    padding-top: 56.25%; /* 16:9 aspect ratio */
    overflow: hidden;
    background: #000;
}
.fmm-media-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.fmm-media-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}
.fmm-media-item:hover .fmm-media-overlay {
    opacity: 1;
}
.fmm-play-icon {
    font-size: 48px;
    color: #fff;
}
.fmm-media-info {
    padding: 15px;
}
.fmm-media-info h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}
.fmm-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}
.fmm-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #888;
}
.fmm-actions {
    display: flex;
    gap: 10px;
}
.fmm-actions .button {
    flex: 1;
    text-align: center;
}
.fmm-modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
}
.fmm-modal-content {
    position: relative;
    margin: 5% auto;
    width: 90%;
    max-width: 1200px;
}
.fmm-modal-close {
    position: absolute;
    right: -40px;
    top: 0;
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10000;
}
#fmm-modal-video video {
    width: 100%;
    max-height: 80vh;
    background: #000;
}
@media (max-width: 768px) {
    .fmm-media-grid {
        grid-template-columns: 1fr;
    }
    .fmm-modal-content {
        width: 95%;
        margin: 10% auto;
    }
    .fmm-modal-close {
        right: 10px;
        top: 10px;
    }
    #fmm-modal-video video {
        max-height: 70vh;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.fmm-watch-btn').on('click', function(e) {
        e.preventDefault();
        var videoUrl = $(this).attr('href');
        var videoId = $(this).data('video-id');
        
        // Create video element with better mobile support
        $('#fmm-modal-video').html(
            '<video controls playsinline preload="metadata" style="width: 100%; max-height: 80vh;">' +
            '<source src="' + videoUrl + '" type="video/mp4">' +
            'Your browser does not support the video tag.' +
            '</video>'
        );
        
        $('#fmm-video-modal').fadeIn(function() {
            // Add fullscreen button on mobile
            if (window.innerWidth <= 768) {
                var fullscreenBtn = $('<button class="fmm-fullscreen-btn" style="position: absolute; top: 10px; left: 10px; z-index: 10001; background: rgba(0,0,0,0.7); color: white; border: none; padding: 10px 15px; border-radius: 5px; font-size: 16px;">⛶ Fullscreen</button>');
                $('#fmm-modal-video').prepend(fullscreenBtn);
                
                fullscreenBtn.on('click', function() {
                    var video = $('#fmm-modal-video video')[0];
                    if (video) {
                        if (video.requestFullscreen) {
                            video.requestFullscreen();
                        } else if (video.webkitRequestFullscreen) {
                            video.webkitRequestFullscreen();
                        } else if (video.webkitEnterFullscreen) {
                            video.webkitEnterFullscreen(); // iOS
                        }
                    }
                });
            }
            
            // Try to play after modal is shown (better for mobile)
            var video = $('#fmm-modal-video video')[0];
            if (video) {
                // On mobile, go fullscreen automatically when video starts
                if (window.innerWidth <= 768) {
                    video.addEventListener('play', function() {
                        if (video.webkitEnterFullscreen) {
                            video.webkitEnterFullscreen(); // iOS
                        } else if (video.requestFullscreen) {
                            video.requestFullscreen();
                        } else if (video.webkitRequestFullscreen) {
                            video.webkitRequestFullscreen();
                        }
                    }, { once: true });
                }
                
                video.play().catch(function(error) {
                    console.log('Auto-play prevented:', error);
                });
            }
        });
    });
    
    $('.fmm-modal-close, .fmm-modal').on('click', function(e) {
        if (e.target === this) {
            var video = $('#fmm-modal-video video')[0];
            if (video) {
                video.pause();
            }
            $('#fmm-video-modal').fadeOut();
            $('#fmm-modal-video').html('');
        }
    });
    
    // Close on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.fmm-modal-close').click();
        }
    });
});
</script>
