<?php
/**
 * Template for Category Permissions Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Category Permissions</h1>
    <p>Manage which family members can access videos in each category.</p>
    
    <?php if (empty($users)): ?>
        <div class="notice notice-warning">
            <p>No registered family members found. <a href="<?php echo admin_url('admin.php?page=fmm-invitations'); ?>">Send an invitation</a> to get started.</p>
        </div>
    <?php elseif (empty($categories)): ?>
        <div class="notice notice-warning">
            <p>No categories found. <a href="<?php echo admin_url('admin.php?page=fmm-categories'); ?>">Create a category</a> to get started.</p>
        </div>
    <?php else: ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 200px;">Family Member</th>
                    <?php foreach ($categories as $category): ?>
                        <th style="text-align: center;">
                            <?php echo esc_html($category->name); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($user->display_name); ?></strong><br>
                            <small><?php echo esc_html($user->user_email); ?></small>
                        </td>
                        <?php foreach ($categories as $category): ?>
                            <td style="text-align: center;">
                                <?php
                                $has_permission = isset($permission_matrix[$user->ID][$category->id]);
                                ?>
                                <input 
                                    type="checkbox" 
                                    class="fmm-permission-checkbox"
                                    data-user-id="<?php echo esc_attr($user->ID); ?>"
                                    data-category-id="<?php echo esc_attr($category->id); ?>"
                                    <?php checked($has_permission); ?>
                                >
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div id="fmm-permission-message" style="display: none; margin-top: 20px;"></div>
        
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.fmm-permission-checkbox').on('change', function() {
        var checkbox = $(this);
        var userId = checkbox.data('user-id');
        var categoryId = checkbox.data('category-id');
        var isChecked = checkbox.is(':checked');
        
        var action = isChecked ? 'fmm_grant_permission' : 'fmm_revoke_permission';
        
        // Disable checkbox during request
        checkbox.prop('disabled', true);
        
        $.ajax({
            url: fmm_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: fmm_admin_ajax.nonce,
                user_id: userId,
                category_id: categoryId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Permission updated successfully', 'success');
                } else {
                    showMessage('Error: ' + response.data, 'error');
                    // Revert checkbox state on error
                    checkbox.prop('checked', !isChecked);
                }
                checkbox.prop('disabled', false);
            },
            error: function() {
                showMessage('Network error occurred', 'error');
                // Revert checkbox state on error
                checkbox.prop('checked', !isChecked);
                checkbox.prop('disabled', false);
            }
        });
    });
    
    function showMessage(message, type) {
        var messageDiv = $('#fmm-permission-message');
        messageDiv.removeClass('notice-success notice-error');
        messageDiv.addClass('notice notice-' + type);
        messageDiv.html('<p>' + message + '</p>');
        messageDiv.show();
        
        setTimeout(function() {
            messageDiv.fadeOut();
        }, 3000);
    }
});
</script>

<style>
.fmm-permission-checkbox {
    cursor: pointer;
    transform: scale(1.3);
}
#fmm-permission-message {
    padding: 10px;
    border-left: 4px solid;
}
#fmm-permission-message.notice-success {
    border-color: #46b450;
    background-color: #ecf7ed;
}
#fmm-permission-message.notice-error {
    border-color: #dc3232;
    background-color: #f9e2e2;
}
</style>
