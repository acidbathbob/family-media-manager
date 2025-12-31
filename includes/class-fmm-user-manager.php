<?php
/**
 * User Manager class for handling invitations and registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class FMM_User_Manager {
    
    public static function init() {
        add_action('wp_ajax_fmm_send_invitation', array(__CLASS__, 'ajax_send_invitation'));
        add_action('wp_ajax_fmm_resend_invitation', array(__CLASS__, 'ajax_resend_invitation'));
        add_action('wp_ajax_nopriv_fmm_register_user', array(__CLASS__, 'ajax_register_user'));
        add_shortcode('fmm_registration', array(__CLASS__, 'registration_shortcode'));
    }
    
    /**
     * Send invitation to email
     */
    public static function send_invitation($email, $send_email = true) {
        global $wpdb;
        
        // Validate email
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Invalid email address');
        }
        
        // Check if already invited
        $table = $wpdb->prefix . 'fmm_invites';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            if ($existing->status === 'registered') {
                return new WP_Error('already_registered', 'This email is already registered');
            }
            return new WP_Error('already_invited', 'This email has already been invited');
        }
        
        // Generate unique invite code
        $invite_code = self::generate_invite_code();
        
        // Insert invitation
        $inserted = $wpdb->insert($table, array(
            'email' => $email,
            'invite_code' => $invite_code,
            'status' => 'pending',
            'invited_by' => get_current_user_id()
        ));
        
        if (!$inserted) {
            return new WP_Error('db_error', 'Failed to create invitation');
        }
        
        // Send email if requested
        if ($send_email && get_option('fmm_send_email_notifications', '1') === '1') {
            self::send_invitation_email($email, $invite_code);
        }
        
        return array(
            'success' => true,
            'invite_code' => $invite_code,
            'registration_url' => self::get_registration_url($invite_code)
        );
    }
    
    /**
     * Generate unique invite code
     */
    private static function generate_invite_code() {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Get registration URL
     */
    public static function get_registration_url($invite_code) {
        $registration_page = get_option('fmm_registration_page');
        if ($registration_page) {
            $url = get_permalink($registration_page);
        } else {
            $url = home_url('/family-registration/');
        }
        return add_query_arg('invite', $invite_code, $url);
    }
    
    /**
     * Send invitation email
     */
    private static function send_invitation_email($email, $invite_code) {
        $site_name = get_bloginfo('name');
        $registration_url = self::get_registration_url($invite_code);
        
        $subject = sprintf('[%s] You\'re invited to join our family media library', $site_name);
        
        $message = sprintf(
            "Hello,\n\n" .
            "You've been invited to join the %s family media library!\n\n" .
            "Click the link below to create your account:\n%s\n\n" .
            "Or use this invitation code: %s\n\n" .
            "This invitation is exclusively for you and should not be shared.\n\n" .
            "Best regards,\n%s",
            $site_name,
            $registration_url,
            $invite_code,
            $site_name
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * AJAX handler for sending invitation
     */
    public static function ajax_send_invitation() {
        check_ajax_referer('fmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        $send_email = isset($_POST['send_email']) ? (bool)$_POST['send_email'] : true;
        
        $result = self::send_invitation($email, $send_email);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * AJAX handler for resending invitation
     */
    public static function ajax_resend_invitation() {
        check_ajax_referer('fmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $invite_id = intval($_POST['invite_id']);
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_invites';
        
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $invite_id
        ));
        
        if (!$invite) {
            wp_send_json_error('Invitation not found');
            return;
        }
        
        self::send_invitation_email($invite->email, $invite->invite_code);
        wp_send_json_success('Invitation email resent');
    }
    
    /**
     * Validate invite code
     */
    public static function validate_invite_code($invite_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_invites';
        
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE invite_code = %s AND status = 'pending'",
            $invite_code
        ));
        
        return $invite ? $invite : false;
    }
    
    /**
     * Register user with invite code
     */
    public static function register_user($email, $password, $invite_code, $first_name = '', $last_name = '') {
        // Validate invite
        $invite = self::validate_invite_code($invite_code);
        if (!$invite) {
            return new WP_Error('invalid_invite', 'Invalid or expired invitation code');
        }
        
        // Verify email matches
        if ($invite->email !== $email) {
            return new WP_Error('email_mismatch', 'Email does not match invitation');
        }
        
        // Check if user already exists
        if (email_exists($email)) {
            return new WP_Error('email_exists', 'An account with this email already exists');
        }
        
        // Create WordPress user
        $username = sanitize_user(current(explode('@', $email)));
        
        // Make username unique if needed
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name) ?: $username,
            'role' => 'subscriber'
        ));
        
        // Add custom role for family member
        $user = new WP_User($user_id);
        $user->add_role('fmm_family_member');
        
        // Update invitation status
        global $wpdb;
        $table = $wpdb->prefix . 'fmm_invites';
        $wpdb->update($table, 
            array(
                'status' => 'registered',
                'registered_date' => current_time('mysql'),
                'user_id' => $user_id
            ),
            array('id' => $invite->id)
        );
        
        return $user_id;
    }
    
    /**
     * AJAX handler for user registration
     */
    public static function ajax_register_user() {
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $invite_code = sanitize_text_field($_POST['invite_code']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        
        $result = self::register_user($email, $password, $invite_code, $first_name, $last_name);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            // Auto login
            wp_set_current_user($result);
            wp_set_auth_cookie($result);
            
            wp_send_json_success(array(
                'redirect' => home_url('/family-media/')
            ));
        }
    }
    
    /**
     * Registration form shortcode
     */
    public static function registration_shortcode($atts) {
        $invite_code = isset($_GET['invite']) ? sanitize_text_field($_GET['invite']) : '';
        
        ob_start();
        include FMM_PLUGIN_DIR . 'templates/registration-form.php';
        return ob_get_clean();
    }
    
    /**
     * Create custom user role
     */
    public static function create_family_member_role() {
        add_role('fmm_family_member', 'Family Member', array(
            'read' => true,
            'fmm_access_media' => true
        ));
    }
}

// Create role on init
add_action('init', array('FMM_User_Manager', 'create_family_member_role'));
