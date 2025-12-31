<?php
/**
 * Admin Categories Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <div class="fmm-admin-header">
        <h1>Video Categories</h1>
    </div>
    
    <div class="fmm-admin-card">
        <h2>Add New Category</h2>
        <form id="fmm-create-category-form">
            <div class="fmm-form-group">
                <label for="category_name">Category Name *</label>
                <input type="text" id="category_name" name="name" required>
            </div>
            
            <div class="fmm-form-group">
                <label for="category_description">Description</label>
                <textarea id="category_description" name="description" rows="3"></textarea>
            </div>
            
            <button type="submit" class="button button-primary">Create Category</button>
        </form>
    </div>
    
    <div class="fmm-admin-card">
        <h2>All Categories (<?php echo count($categories); ?>)</h2>
        
        <?php if (empty($categories)): ?>
            <p>No categories created yet.</p>
        <?php else: ?>
            <table class="fmm-admin-table widefat">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Video Count</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        // Count videos in this category
                        global $wpdb;
                        $media_table = $wpdb->prefix . 'fmm_media';
                        $video_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $media_table WHERE category_id = %d",
                            $cat->id
                        ));
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($cat->name); ?></strong></td>
                            <td><code><?php echo esc_html($cat->slug); ?></code></td>
                            <td><?php echo esc_html($cat->description ?: '—'); ?></td>
                            <td><?php echo intval($video_count); ?> videos</td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($cat->created_date))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#fmm-create-category-form').on('submit', function(e) {
        e.preventDefault();
        
        var name = $('#category_name').val();
        var description = $('#category_description').val();
        
        $.post(fmm_admin_ajax.ajax_url, {
            action: 'fmm_create_category',
            nonce: fmm_admin_ajax.nonce,
            name: name,
            description: description
        }, function(response) {
            if (response.success) {
                alert('Category created successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
</script>
