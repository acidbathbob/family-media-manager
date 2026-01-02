# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

Family Media Manager is a WordPress plugin for secure video sharing with family members via email-based invitations. Built for deployment on Fedora Server with Apache/WordPress, it provides secure video storage with .htaccess protection, automatic thumbnail generation via ffmpeg, and role-based access control.

## Architecture

### Core Components

**Plugin Entry Point**: `family-media-manager.php`
- Defines constants (FMM_VERSION, FMM_PLUGIN_DIR, FMM_UPLOAD_DIR)
- Loads all class files from `includes/`
- Registers activation/deactivation hooks
- Initializes components via `fmm_init()` hook on `plugins_loaded`
- Enqueues CSS/JS assets with WordPress localization for AJAX endpoints

**Class Architecture** (all in `includes/`):
- `FMM_Installer`: Database schema creation (wp_fmm_invites, wp_fmm_media, wp_fmm_categories), upload directory setup with .htaccess protection
- `FMM_User_Manager`: Invitation system with unique codes, email-based registration, invite validation
- `FMM_Media_Manager`: Video upload handling, thumbnail generation (ffmpeg or fallback), media CRUD operations
- `FMM_Access_Control`: Custom rewrite endpoints (`/fmm-download/{id}/`, `/fmm-stream/{id}/`), access control checks, range request support for streaming
- `FMM_Frontend`: Shortcode handlers (`[fmm_media_library]`, `[fmm_registration]`, `[fmm_video_player]`)
- `FMM_Admin`: WordPress admin menu pages, AJAX handlers for admin operations

### Data Flow

**Registration Flow**:
1. Admin creates invitation via `FMM_User_Manager::send_invitation()`
2. Unique code stored in wp_fmm_invites with pending status
3. Email sent with registration URL containing invite code
4. User registers via `[fmm_registration]` shortcode → validates email matches invite
5. WordPress user created, invite status updated to 'registered'

**Video Upload Flow**:
1. Admin uploads via WordPress admin (templates/admin/media-library.php)
2. File stored in `/wp-content/uploads/family-videos/` with unique filename
3. ffmpeg generates thumbnail (or placeholder fallback) in `thumbnails/` subdirectory
4. Metadata (title, description, category, filesize) stored in wp_fmm_media

**Access Control**:
- All video files protected by .htaccess (deny direct access)
- Download/stream URLs use custom rewrite rules handled by `FMM_Access_Control`
- Access checks: user must be logged in AND (admin OR has fmm_access_media capability OR registered via invitation)
- Downloads use `Content-Disposition: attachment`, streams use `inline` with range request support

### Security Model

- Videos stored outside WordPress media library
- Direct file access blocked via .htaccess
- All requests route through access control checks
- Invitation codes are unique 32-char hex strings (bin2hex(random_bytes(16)))
- Email must match invitation to complete registration

## Development Commands

### Testing Plugin Locally

```bash
# Install to local WordPress (requires sudo)
sudo bash INSTALL.sh

# The script will:
# - Copy files to /var/www/html/wp-content/plugins/family-media-manager/
# - Set apache:apache ownership
# - Check for ffmpeg and mod_rewrite
```

### Making Changes

```bash
# After editing plugin files in this directory, reinstall to WordPress
sudo bash INSTALL.sh

# Or manually copy specific file(s) for quick iteration
sudo cp includes/class-fmm-media-manager.php /var/www/html/wp-content/plugins/family-media-manager/includes/
sudo chown apache:apache /var/www/html/wp-content/plugins/family-media-manager/includes/class-fmm-media-manager.php
```

### Database Operations

The plugin automatically creates tables on activation. To manually inspect or reset:

```bash
# Connect to WordPress database
mysql -u root -p wordpress_db_name

# View tables
SHOW TABLES LIKE 'wp_fmm_%';

# Inspect invitations
SELECT * FROM wp_fmm_invites;

# Inspect media
SELECT id, title, filename, category_id, upload_date FROM wp_fmm_media;

# Reset plugin (WARNING: deletes all data)
DROP TABLE wp_fmm_invites, wp_fmm_media, wp_fmm_categories;
```

### Checking WordPress Integration

```bash
# View WordPress error log
sudo tail -f /var/log/httpd/error_log

# Check plugin is activated
wp plugin list --path=/var/www/html

# Flush rewrite rules after changing endpoints
wp rewrite flush --path=/var/www/html

# Test ffmpeg availability
which ffmpeg
ffmpeg -version
```

## Deployment Workflow

### Preparing for Deployment

```bash
# Create deployment ZIP
cd /home/bob/projects/photo-converter
zip -r family-media-manager.zip family-media-manager/

# Commit changes to git
cd family-media-manager
git add .
git commit -m "Description of changes"
git push
```

### Deploying to Production Server

**Via Git** (preferred for updates):
```bash
# On server
cd /tmp/family-media-manager
git pull
sudo bash INSTALL.sh
```

**Via SCP** (for initial setup):
```bash
# From development machine
scp -r /home/bob/projects/photo-converter/family-media-manager user@server:/tmp/

# On server
cd /tmp/family-media-manager
sudo bash INSTALL.sh
```

After installation, activate via WordPress admin: Plugins → Installed Plugins → Family Media Manager → Activate

## File Locations

**Development**: `/home/bob/projects/photo-converter/family-media-manager/`
**Production Plugin**: `/var/www/html/wp-content/plugins/family-media-manager/`
**Video Storage**: `/var/www/html/wp-content/uploads/family-videos/`
**Thumbnails**: `/var/www/html/wp-content/uploads/family-videos/thumbnails/`

## WordPress-Specific Patterns

- All AJAX actions use `wp_ajax_` prefixed hooks (admin) or `wp_ajax_nopriv_` (frontend)
- Nonce verification required: `fmm_admin_nonce` (admin), `fmm_nonce` (frontend)
- Rewrite rules registered in `FMM_Access_Control::init()` on 'init' hook
- Always call `flush_rewrite_rules()` after activation or endpoint changes
- Use `wp_upload_dir()` to get upload paths (respects WordPress multisite)
- Capabilities: Admins use `manage_options`, family members need `fmm_access_media`

## Common Development Tasks

### Adding New Video Format

1. Edit `FMM_Media_Manager::upload_video()` → update `$allowed_types` array
2. Test upload via WordPress admin
3. Verify browser can stream (check MIME type support)

### Adding New Access Control Rules

1. Edit `FMM_Access_Control::user_has_access()` to add conditions
2. Test both download and stream endpoints
3. Verify .htaccess still blocks direct file access

### Modifying Database Schema

1. Edit table creation SQL in `FMM_Installer::activate()`
2. Version bump in `family-media-manager.php` (FMM_VERSION constant)
3. Add migration logic for existing installations (check version in options table)
4. Test by deactivating/reactivating plugin

### Debugging AJAX Issues

1. Check browser console for JavaScript errors
2. Check response in Network tab (look for `admin-ajax.php`)
3. Verify nonce is being sent: `fmm_admin_ajax.nonce` or `fmm_ajax.nonce`
4. Check Apache error log for PHP errors: `sudo tail -f /var/log/httpd/error_log`

## Dependencies

**Server Requirements**:
- Fedora Linux with Apache (httpd)
- WordPress 5.0+ with PHP 7.4+
- MySQL 5.6+
- mod_rewrite enabled (`sudo dnf install mod_rewrite && sudo systemctl restart httpd`)

**Optional but Recommended**:
- ffmpeg for thumbnail generation (`sudo dnf install ffmpeg`)
- WP Mail SMTP plugin for reliable invitation emails

## Template System

Templates are in `templates/` directory:

**Frontend Templates**:
- `registration-form.php`: Invitation code validation and user registration
- `media-library.php`: Video grid display with search/filter
- `video-player.php`: HTML5 video player with download link

**Admin Templates**:
- `admin/media-library.php`: Video upload and management interface
- `admin/invitations.php`: Send and track invitations
- `admin/categories.php`: Category CRUD operations
- `admin/settings.php`: Plugin configuration options

Templates are loaded via `include` with variables passed from controller methods. Use `ob_start()` and `ob_get_clean()` for shortcode rendering.

## Shortcode Usage

After plugin activation, create WordPress pages with these shortcodes:

```
[fmm_registration]
// Registration form with invite code validation

[fmm_media_library columns="3" show_search="yes"]
// Video grid display (columns: 2-4, show_search: yes/no)

[fmm_video_player id="123"]
// Single video player (id required)
```

Configure registration page URL in: Family Media → Settings → Registration Page
