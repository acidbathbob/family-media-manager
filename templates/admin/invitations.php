<?php
/**
 * Admin Invitations Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <div class="fmm-admin-header">
        <h1>Family Member Invitations</h1>
    </div>
    
    <div class="fmm-admin-card">
        <h2>Send New Invitation</h2>
        <form id="fmm-send-invitation-form">
            <div class="fmm-form-group">
                <label for="invite_email">Email Address *</label>
                <input type="email" id="invite_email" name="email" required>
            </div>
            
            <div class="fmm-form-group">
                <label>
                    <input type="checkbox" id="send_email" name="send_email" checked>
                    Send automatic invitation email
                </label>
                <p class="description">If unchecked, you'll receive an invite code to share manually.</p>
            </div>
            
            <button type="submit" class="button button-primary">Send Invitation</button>
        </form>
    </div>
    
    <div class="fmm-admin-card">
        <h2>Invitations List</h2>
        
        <?php if (empty($invites)): ?>
            <p>No invitations sent yet.</p>
        <?php else: ?>
            <table class="fmm-admin-table widefat">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Invite Code</th>
                        <th>Status</th>
                        <th>Invited Date</th>
                        <th>Registered Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invites as $invite): ?>
                        <tr>
                            <td><?php echo esc_html($invite->email); ?></td>
                            <td><code><?php echo esc_html($invite->invite_code); ?></code></td>
                            <td>
                                <span class="fmm-status-badge fmm-status-<?php echo esc_attr($invite->status); ?>">
                                    <?php echo esc_html(ucfirst($invite->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($invite->invited_date))); ?></td>
                            <td>
                                <?php echo $invite->registered_date ? esc_html(date('M j, Y', strtotime($invite->registered_date))) : '—'; ?>
                            </td>
                            <td>
                                <?php if ($invite->status === 'pending'): ?>
                                    <button class="button button-small fmm-resend-invitation" 
                                            data-invite-id="<?php echo esc_attr($invite->id); ?>">
                                        Resend Email
                                    </button>
                                    <a href="<?php echo esc_url(FMM_User_Manager::get_registration_url($invite->invite_code)); ?>" 
                                       target="_blank" 
                                       class="button button-small">
                                        View Registration Link
                                    </a>
                                <?php else: ?>
                                    <?php if ($invite->user_id): ?>
                                        <a href="<?php echo esc_url(get_edit_user_link($invite->user_id)); ?>" 
                                           class="button button-small">
                                            View User
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
