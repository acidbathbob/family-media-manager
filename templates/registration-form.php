<?php
/**
 * Registration Form Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Validate invite code
$invite = null;
$error = '';

if ($invite_code) {
    $invite = FMM_User_Manager::validate_invite_code($invite_code);
    if (!$invite) {
        $error = 'Invalid or expired invitation code.';
    }
}
?>

<div class="fmm-registration-form">
    <h2>Family Member Registration</h2>
    
    <?php if ($error): ?>
        <div class="fmm-error"><?php echo esc_html($error); ?></div>
    <?php endif; ?>
    
    <?php if ($invite): ?>
        <p>You've been invited to join the family media library!</p>
        <p><strong>Email:</strong> <?php echo esc_html($invite->email); ?></p>
    <?php endif; ?>
    
    <form id="fmm-registration-form" method="post">
        <div class="form-group">
            <label for="invite_code">Invitation Code *</label>
            <input type="text" 
                   id="invite_code" 
                   name="invite_code" 
                   value="<?php echo esc_attr($invite_code); ?>" 
                   <?php echo $invite ? 'readonly' : ''; ?> 
                   required>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   value="<?php echo $invite ? esc_attr($invite->email) : ''; ?>" 
                   <?php echo $invite ? 'readonly' : ''; ?> 
                   required>
        </div>
        
        <div class="form-group">
            <label for="first_name">First Name *</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name *</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" minlength="8" required>
            <small>Minimum 8 characters</small>
        </div>
        
        <div class="form-group">
            <label for="password_confirm">Confirm Password *</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="button button-primary">Register</button>
        </div>
        
        <div id="fmm-registration-message"></div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#fmm-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        var password = $('#password').val();
        var confirm = $('#password_confirm').val();
        
        if (password !== confirm) {
            $('#fmm-registration-message').html('<div class="fmm-error">Passwords do not match</div>');
            return;
        }
        
        var formData = {
            action: 'fmm_register_user',
            email: $('#email').val(),
            password: password,
            invite_code: $('#invite_code').val(),
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val()
        };
        
        $.post(fmm_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#fmm-registration-message').html('<div class="fmm-success">Registration successful! Redirecting...</div>');
                setTimeout(function() {
                    window.location.href = response.data.redirect;
                }, 1500);
            } else {
                $('#fmm-registration-message').html('<div class="fmm-error">' + response.data + '</div>');
            }
        });
    });
});
</script>

<style>
.fmm-registration-form {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px;
}
.fmm-registration-form h2 {
    text-align: center;
    margin-bottom: 20px;
}
.fmm-registration-form .form-group {
    margin-bottom: 15px;
}
.fmm-registration-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.fmm-registration-form input[type="text"],
.fmm-registration-form input[type="email"],
.fmm-registration-form input[type="password"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.fmm-registration-form small {
    color: #666;
    font-size: 12px;
}
.fmm-error {
    background: #ffebee;
    color: #c62828;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}
.fmm-success {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 10px;
    border-radius: 4px;
    margin-top: 15px;
}
</style>
