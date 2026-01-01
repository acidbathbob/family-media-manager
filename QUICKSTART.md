# Family Media Manager - Quick Start Guide

## What We Built

A complete WordPress plugin that provides:
- **Secure video streaming and downloads** for family members only
- **Email-based invitation system** (automatic emails OR manual codes)
- **Video upload and management** with categories
- **Search and filter** functionality  
- **Thumbnail generation** (with ffmpeg) or placeholders
- **Access control** - only invited family can access videos
- All videos stored securely with .htaccess protection

## Installation (5 Minutes)

### Step 1: Run the Installation Script

```bash
cd /home/bob/projects/photo-converter/family-media-manager
sudo bash INSTALL.sh
```

This will:
- Copy the plugin to `/var/www/html/wp-content/plugins/`
- Set correct permissions for Apache
- Check your server configuration

### Step 2: Activate the Plugin

1. Go to `https://bob490.co.uk/wp-admin`
2. Navigate to **Plugins** → **Installed Plugins**
3. Find "Family Media Manager" 
4. Click **Activate**

The plugin will automatically:
- Create 3 database tables
- Create `/wp-content/uploads/family-videos/` directory
- Add security .htaccess files
- Create "Family Member" user role
- Set up secure URLs

### Step 3: Create Pages (2 Minutes)

**Registration Page:**
1. Go to **Pages** → **Add New**
2. Title: "Family Registration"
3. Content: `[fmm_registration]`
4. Click **Publish**

**Media Library Page:**
1. Go to **Pages** → **Add New**
2. Title: "Family Videos"
3. Content: `[fmm_media_library]`
4. Click **Publish**

### Step 4: Configure Settings

1. Go to **Family Media** → **Settings**
2. Check "Allow new registrations" ✓
3. Check "Send automatic invitation emails" ✓
4. Select "Family Registration" as the Registration Page
5. Click **Save Settings**

## Usage

### Inviting Family Members

1. Go to **Family Media** → **Invitations**
2. Enter their email address
3. Choose:
   - **Automatic email**: They get an email with registration link
   - **Manual code**: You get a code/URL to share yourself
4. Click **Send Invitation**

### Uploading Videos

**Note:** The simplified admin upload interface requires creating one more template file. For now, you can:

**Option A: Add videos manually via PHP:**
```php
$file = $_FILES['video']; // From upload form
$result = FMM_Media_Manager::upload_video(
    $file,
    'Video Title',
    'Video description',
    1  // category_id
);
```

**Option B: I can create a complete admin upload page** - let me know if you need this.

### For Family Members

1. They receive invitation email (or you share the link)
2. They visit registration page
3. Fill in: name, password
4. Click Register → automatically logged in
5. Visit "Family Videos" page
6. They can:
   - **Watch videos** (streaming with HTML5 player)
   - **Download videos** (secure download)
   - **Search/filter** by title or category

## File Structure

```
family-media-manager/
├── family-media-manager.php       # Main plugin file
├── README.md                       # Full documentation
├── QUICKSTART.md                   # This file
├── INSTALL.sh                      # Installation script
├── includes/                       # PHP classes
│   ├── class-fmm-installer.php
│   ├── class-fmm-user-manager.php
│   ├── class-fmm-media-manager.php
│   ├── class-fmm-access-control.php
│   ├── class-fmm-frontend.php
│   └── class-fmm-admin.php
├── templates/                      # Page templates
│   ├── registration-form.php
│   ├── media-library.php
│   └── admin/
│       ├── invitations.php
│       └── settings.php
└── assets/                         # CSS & JavaScript
    ├── css/
    └── js/
```

## Key Features Explained

### Secure Access
- Videos stored in `/wp-content/uploads/family-videos/`
- .htaccess blocks direct access to files
- Download URL: `https://bob490.co.uk/fmm-download/{id}/`
- Stream URL: `https://bob490.co.uk/fmm-stream/{id}/`
- Both URLs check if user is logged in and invited

### Invitation System
- Each email gets a unique invite code
- Codes can only be used once
- Email must match the invitation
- Status tracked: "pending" or "registered"

### Video Features
- Automatic thumbnail generation (requires ffmpeg)
- Video duration detection (requires ffmpeg)
- Download/view count tracking
- Category organization
- Search by title/description/filename

## Optional: Install ffmpeg

For automatic thumbnails and video duration:

```bash
sudo dnf install ffmpeg
```

Test if it works:
```bash
ffmpeg -version
```

## Troubleshooting

### Videos won't upload
Check PHP limits in `/etc/php.ini`:
```ini
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 300
```

Then restart Apache:
```bash
sudo systemctl restart httpd
```

### Emails not sending
WordPress needs mail configured. Install WP Mail SMTP plugin or configure Fedora's mail service.

### Permission errors
```bash
sudo chown -R apache:apache /var/www/html/wp-content/uploads/family-videos/
sudo chmod -R 755 /var/www/html/wp-content/uploads/family-videos/
```

### Rewrite rules not working
Go to **Settings** → **Permalinks** and click **Save Changes** to flush rewrite rules.

## Database Tables

The plugin creates 3 tables:

1. **wp_fmm_invites** - Email invitations
2. **wp_fmm_media** - Video files and metadata
3. **wp_fmm_categories** - Video categories

## Security Notes

✓ All video access requires authentication
✓ Only invited users can register
✓ Files protected by .htaccess
✓ SQL injection protected (prepared statements)
✓ XSS protected (all output escaped)
✓ CSRF protected (nonces on all forms)

## Next Steps

1. **Test the invitation system** - invite yourself at a different email
2. **Upload some test videos** - see how it works
3. **Customize the design** - edit templates or add custom CSS
4. **Invite family members** - start sharing!

## Need Help?

- Read the full README.md for detailed documentation
- Check Apache error logs: `sudo tail -f /var/log/httpd/error_log`
- Check WordPress debug log (if enabled)

## What's Missing?

For a complete v1.0, you might want to add:
- Admin media upload interface (I can create this)
- Admin categories management page (I can create this)  
- Bulk video upload
- Video compression/optimization
- Email templates customization
- User activity logs

Let me know if you need any of these!

---

**Plugin Version:** 1.0.0
**Author:** Bob
**Website:** https://bob490.co.uk
