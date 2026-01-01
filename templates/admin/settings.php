<?php
/**
 * Admin Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$allow_registration = get_option('fmm_allow_registration', '1');
$send_email = get_option('fmm_send_email_notifications', '1');
$registration_page = get_option('fmm_registration_page', '');
?>

<div class="wrap">
    <div class="fmm-admin-header">
        <h1>Settings</h1>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('fmm_settings'); ?>
        
        <div class="fmm-admin-card">
            <h2>General Settings</h2>
            
            <div class="fmm-form-group">
                <label>
                    <input type="checkbox" 
                           name="allow_registration" 
                           <?php checked($allow_registration, '1'); ?>>
                    Allow new registrations
                </label>
                <p class="description">When disabled, no new family members can register even with valid invite codes.</p>
            </div>
            
            <div class="fmm-form-group">
                <label>
                    <input type="checkbox" 
                           name="send_email" 
                           <?php checked($send_email, '1'); ?>>
                    Send automatic invitation emails
                </label>
                <p class="description">When enabled, invitation emails are sent automatically. Otherwise, you'll need to share invite codes manually.</p>
            </div>
            
            <div class="fmm-form-group">
                <label for="registration_page">Registration Page</label>
                <?php
                wp_dropdown_pages(array(
                    'name' => 'registration_page',
                    'id' => 'registration_page',
                    'selected' => $registration_page,
                    'show_option_none' => '— Select Page —',
                    'option_none_value' => '0'
                ));
                ?>
                <p class="description">
                    Select the page where you've added the <code>[fmm_registration]</code> shortcode.
                    <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>">Create a new page</a>
                </p>
            </div>
        </div>
        
        <div class="fmm-admin-card">
            <h2>Usage Instructions</h2>
            
            <h3>Shortcodes</h3>
            <p><strong>Registration Page:</strong></p>
            <code>[fmm_registration]</code>
            <p class="description">Add this to a page to display the family member registration form.</p>
            
            <p><strong>Media Library:</strong></p>
            <code>[fmm_media_library]</code>
            <p class="description">Add this to a page to display the video library for logged-in family members.</p>
            
            <h3>File Storage</h3>
            <p>Videos are stored in: <code><?php echo esc_html(wp_upload_dir()['basedir'] . '/family-videos/'); ?></code></p>
            
            <h3>Security</h3>
            <p>✓ Direct file access is blocked by .htaccess</p>
            <p>✓ All downloads and streams require authentication</p>
            <p>✓ Only invited family members can register</p>
        </div>
        
        <p class="submit">
            <button type="submit" name="fmm_save_settings" class="button button-primary">
                Save Settings
            </button>
        </p>
    </form>
</div>
