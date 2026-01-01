# Family Media Manager - WordPress Plugin

A custom WordPress plugin for secure video downloads and streaming with email-based family member invitations.

## Features

- **Email-Based Invitations**: Send automatic invitation emails or generate manual invite codes for family members
- **Secure Access Control**: Only approved users can access videos
- **Video Upload & Management**: Upload videos with title, description, and categories
- **Video Streaming**: Built-in HTML5 video player with range request support
- **Secure Downloads**: Protected download links with access control
- **Thumbnail Generation**: Automatic thumbnail generation (requires ffmpeg) or placeholder fallback
- **Search & Filter**: Search videos by title, description, or filename
- **Category Organization**: Organize videos into folders/categories
- **Download/View Tracking**: Track download and view counts
- **Responsive Design**: Mobile-friendly interface

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Apache web server with mod_rewrite enabled
- Optional: ffmpeg for automatic thumbnail generation and video duration

## Installation

### Method 1: Direct Upload

1. Upload the entire `family-media-manager` folder to `/var/www/html/wp-content/plugins/`
2. Log in to your WordPress admin panel
3. Navigate to **Plugins** > **Installed Plugins**
4. Find "Family Media Manager" and click **Activate**

### Method 2: From Your Server

```bash
# Copy the plugin to WordPress plugins directory
sudo cp -r /home/bob/projects/photo-converter/family-media-manager /var/www/html/wp-content/plugins/

# Set correct permissions
sudo chown -R apache:apache /var/www/html/wp-content/plugins/family-media-manager
sudo chmod -R 755 /var/www/html/wp-content/plugins/family-media-manager

# Navigate to WordPress admin and activate the plugin
```

## Initial Setup

### 1. Activate the Plugin
After activation, the plugin will automatically:
- Create database tables for invites, media, and categories
- Create upload directory at `/wp-content/uploads/family-videos/`
- Add security .htaccess files
- Create "Family Member" user role
- Flush rewrite rules for secure URLs

### 2. Configure Settings
Go to **Family Media** > **Settings** in your WordPress admin:

- **Allow Registration**: Enable/disable new registrations
- **Send Email Notifications**: Automatically send invitation emails
- **Registration Page**: Select a page where you'll add the registration shortcode

### 3. Create Registration Page
Create a new WordPress page (e.g., "Family Registration"):
1. Go to **Pages** > **Add New**
2. Title: "Family Registration"
3. Add this shortcode to the page content:
   ```
   [fmm_registration]
   ```
4. Publish the page
5. Go back to **Family Media** > **Settings** and select this page

### 4. Create Media Library Page
Create another page for the media library (e.g., "Family Videos"):
1. Go to **Pages** > **Add New**
2. Title: "Family Videos"
3. Add this shortcode:
   ```
   [fmm_media_library]
   ```
4. Publish the page

## Usage

### Inviting Family Members

1. Go to **Family Media** > **Invitations**
2. Enter the family member's email address
3. Choose whether to send an automatic email or generate a manual invite code
4. Click **Send Invitation**

If automatic email is enabled, they'll receive an email with a registration link and code.
If manual, you'll see the invite code and registration URL to share manually.

### Uploading Videos

1. Go to **Family Media** > **Media Library**
2. Click **Upload New Video**
3. Fill in:
   - **Video File**: Select your video file (MP4, WebM, OGG, MOV, AVI)
   - **Title**: Give it a descriptive title
   - **Description**: Optional description
   - **Category**: Select or create a category
4. Click **Upload**

The video will be automatically:
- Uploaded to the secure directory
- Thumbnail generated (if ffmpeg available)
- Added to the database
- Made available to all registered family members

### Managing Categories

1. Go to **Family Media** > **Categories**
2. Click **Add New Category**
3. Enter name and description
4. Click **Create**

### Viewing Statistics

In the admin media library, you can see:
- Number of views per video
- Number of downloads per video
- Upload date and file size
- Who uploaded each video

## For Family Members

### Registration

1. Click the invitation link or visit the registration page
2. Enter the invitation code (if not in the URL)
3. Fill in:
   - Email address (must match invitation)
   - First name and last name
   - Password
4. Click **Register**
5. You'll be automatically logged in and redirected to the media library

### Accessing Videos

1. Log in to the website
2. Navigate to the Family Videos page
3. You'll see all available videos with thumbnails
4. Use the search box to find specific videos
5. Filter by category using the dropdown
6. Click on a video to:
   - **Watch**: Stream the video in your browser
   - **Download**: Download the video file

## Security Features

### Access Control
- Videos are stored in `/wp-content/uploads/family-videos/` with .htaccess protection
- Direct URL access is blocked
- All downloads/streams go through access control checks
- Only logged-in, approved family members can access content

### Invitation System
- Email-based invitations prevent unauthorized registrations
- Unique invite codes per email
- Invite codes can only be used once
- Email must match the invitation to register

### Secure URLs
- Download URLs: `https://bob490.co.uk/fmm-download/{video_id}/`
- Stream URLs: `https://bob490.co.uk/fmm-stream/{video_id}/`
- Both require authentication and authorization

## Shortcodes

### Media Library
```
[fmm_media_library]
```

Optional parameters:
- `category=""` - Show only specific category
- `columns="3"` - Number of columns (default: 3)
- `show_search="yes"` - Show search box (default: yes)

Example:
```
[fmm_media_library columns="4" show_search="yes"]
```

### Registration Form
```
[fmm_registration]
```

No parameters needed. Automatically handles invite codes from URL.

### Video Player
```
[fmm_video_player id="123"]
```

Parameters:
- `id` - The media ID (required)

## Troubleshooting

### Videos won't upload
- Check PHP upload_max_filesize and post_max_size in php.ini
- Ensure `/wp-content/uploads/family-videos/` is writable by Apache
- Check error logs: `/var/log/httpd/error_log`

### Thumbnails not generating
- Install ffmpeg: `sudo dnf install ffmpeg`
- Verify ffmpeg is in PATH: `which ffmpeg`
- Check PHP can execute shell commands: `exec()` must be enabled

### Invitation emails not sending
- Check WordPress mail configuration
- Test with a plugin like WP Mail SMTP
- Verify Fedora mail service is running

### Videos won't stream/download
- Flush rewrite rules: Go to **Settings** > **Permalinks** and click **Save**
- Check .htaccess in WordPress root has mod_rewrite rules
- Verify Apache mod_rewrite is enabled: `sudo httpd -M | grep rewrite`

### Permission issues
```bash
# Fix file permissions
sudo chown -R apache:apache /var/www/html/wp-content/uploads/family-videos/
sudo chmod -R 755 /var/www/html/wp-content/uploads/family-videos/
```

## File Structure

```
family-media-manager/
├── family-media-manager.php          # Main plugin file
├── README.md                          # This file
├── includes/
│   ├── class-fmm-installer.php       # Database & directory setup
│   ├── class-fmm-user-manager.php    # Invitation & registration
│   ├── class-fmm-media-manager.php   # Video upload & management
│   ├── class-fmm-access-control.php  # Secure download/stream
│   ├── class-fmm-frontend.php        # User-facing pages
│   └── class-fmm-admin.php           # Admin interface
├── templates/
│   ├── registration-form.php         # Registration page template
│   ├── media-library.php             # Media library display
│   ├── video-player.php              # Video player template
│   └── admin/                        # Admin page templates
│       ├── media-library.php
│       ├── invitations.php
│       ├── categories.php
│       └── settings.php
└── assets/
    ├── css/
    │   ├── frontend.css              # User-facing styles
    │   └── admin.css                 # Admin styles
    └── js/
        ├── frontend.js               # Frontend JavaScript
        └── admin.js                  # Admin JavaScript
```

## Database Tables

### wp_fmm_invites
Stores email invitations and registration status.

### wp_fmm_media
Stores video file information, metadata, and statistics.

### wp_fmm_categories
Stores video categories/folders.

## Uninstallation

To completely remove the plugin:

1. Deactivate the plugin
2. Delete the plugin files
3. Optionally, remove database tables:
```sql
DROP TABLE wp_fmm_invites;
DROP TABLE wp_fmm_media;
DROP TABLE wp_fmm_categories;
```
4. Optionally, remove uploaded videos:
```bash
sudo rm -rf /var/www/html/wp-content/uploads/family-videos/
```

## Support

For issues or questions:
- Check the Troubleshooting section above
- Review WordPress error logs
- Check Apache error logs on Fedora

## License

GPL v2 or later

## Author

Bob - https://bob490.co.uk

## Version

1.0.0
