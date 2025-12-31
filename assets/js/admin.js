/* Family Media Manager - Admin JavaScript */

jQuery(document).ready(function($) {
    // Handle invitation form submission
    $('#fmm-send-invitation-form').on('submit', function(e) {
        e.preventDefault();
        
        var email = $('#invite_email').val();
        var sendEmail = $('#send_email').is(':checked');
        
        $.post(fmm_admin_ajax.ajax_url, {
            action: 'fmm_send_invitation',
            nonce: fmm_admin_ajax.nonce,
            email: email,
            send_email: sendEmail
        }, function(response) {
            if (response.success) {
                alert('Invitation sent successfully!\n\nInvite Code: ' + response.data.invite_code + '\n\nRegistration URL:\n' + response.data.registration_url);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Handle video upload
    $('#fmm-upload-video-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'fmm_upload_video');
        formData.append('nonce', fmm_admin_ajax.nonce);
        
        $.ajax({
            url: fmm_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Video uploaded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Handle delete video
    $('.fmm-delete-video').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this video?')) {
            return;
        }
        
        var mediaId = $(this).data('media-id');
        
        $.post(fmm_admin_ajax.ajax_url, {
            action: 'fmm_delete_video',
            nonce: fmm_admin_ajax.nonce,
            media_id: mediaId
        }, function(response) {
            if (response.success) {
                alert('Video deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Handle resend invitation
    $('.fmm-resend-invitation').on('click', function(e) {
        e.preventDefault();
        
        var inviteId = $(this).data('invite-id');
        
        $.post(fmm_admin_ajax.ajax_url, {
            action: 'fmm_resend_invitation',
            nonce: fmm_admin_ajax.nonce,
            invite_id: inviteId
        }, function(response) {
            if (response.success) {
                alert('Invitation email resent successfully!');
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
