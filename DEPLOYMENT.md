# Deployment Instructions

## Files Ready for Transfer

✅ **ZIP file created:** `/home/bob/projects/photo-converter/family-media-manager.zip`  
✅ **Git repository initialized:** `/home/bob/projects/photo-converter/family-media-manager/`

---

## Method 1: Transfer via SSH (Quick)

### From this desktop machine, run:

```bash
# Replace with your server details
SERVER_USER="your-username"
SERVER_IP="your-server-ip"

# Transfer the ZIP file
scp /home/bob/projects/photo-converter/family-media-manager.zip \
    ${SERVER_USER}@${SERVER_IP}:/tmp/

# Or transfer the whole directory
scp -r /home/bob/projects/photo-converter/family-media-manager \
    ${SERVER_USER}@${SERVER_IP}:/tmp/
```

### On your server machine:

```bash
# If you transferred the ZIP:
cd /tmp
unzip family-media-manager.zip
cd family-media-manager
sudo bash INSTALL.sh

# If you transferred the directory:
cd /tmp/family-media-manager
sudo bash INSTALL.sh
```

---

## Method 2: Push to Git Repository

### Step 1: Create a repository on GitHub/GitLab

1. Go to GitHub.com or GitLab.com
2. Create a new **private** repository (keep it private since it's for your personal use)
3. Name it: `family-media-manager`
4. **Don't** initialize with README (we already have one)

### Step 2: Push from this desktop

```bash
cd /home/bob/projects/photo-converter/family-media-manager

# Add your remote (replace with your actual URL)
git remote add origin git@github.com:YOUR_USERNAME/family-media-manager.git
# OR for HTTPS:
# git remote add origin https://github.com/YOUR_USERNAME/family-media-manager.git

# Push to main branch
git branch -M main
git push -u origin main
```

### Step 3: Clone on your server

```bash
# SSH to your server, then:
cd /tmp
git clone git@github.com:YOUR_USERNAME/family-media-manager.git
# OR for HTTPS:
# git clone https://github.com/YOUR_USERNAME/family-media-manager.git

cd family-media-manager
sudo bash INSTALL.sh
```

---

## Installation on Server

Once files are on your server (via either method):

```bash
cd /path/to/family-media-manager
sudo bash INSTALL.sh
```

The script will:
1. ✓ Check for existing installation
2. ✓ Copy files to `/var/www/html/wp-content/plugins/`
3. ✓ Set correct permissions (apache:apache)
4. ✓ Check for ffmpeg (optional)
5. ✓ Verify Apache mod_rewrite

Then:
1. Go to your WordPress admin: `https://bob490.co.uk/wp-admin`
2. Navigate to **Plugins** → **Installed Plugins**
3. Find "Family Media Manager"
4. Click **Activate**

---

## Quick Commands Summary

### SSH Transfer:
```bash
# Transfer ZIP
scp /home/bob/projects/photo-converter/family-media-manager.zip user@server:/tmp/

# Or transfer directory
scp -r /home/bob/projects/photo-converter/family-media-manager user@server:/tmp/
```

### Git Setup (one-time):
```bash
cd /home/bob/projects/photo-converter/family-media-manager
git remote add origin YOUR_GIT_URL
git push -u origin main
```

### Git Clone on Server:
```bash
git clone YOUR_GIT_URL /tmp/family-media-manager
cd /tmp/family-media-manager
sudo bash INSTALL.sh
```

---

## Future Updates

When you make changes to the plugin:

### Update via Git:
```bash
# On desktop (after making changes)
cd /home/bob/projects/photo-converter/family-media-manager
git add .
git commit -m "Description of changes"
git push

# On server
cd /tmp/family-media-manager
git pull
sudo bash INSTALL.sh
```

### Update via SCP:
```bash
# Re-create ZIP with changes
cd /home/bob/projects/photo-converter
zip -r family-media-manager.zip family-media-manager/

# Transfer and install
scp family-media-manager.zip user@server:/tmp/
# Then SSH to server and reinstall
```

---

## File Locations

**Desktop (source):**
- Plugin files: `/home/bob/projects/photo-converter/family-media-manager/`
- ZIP file: `/home/bob/projects/photo-converter/family-media-manager.zip`

**Server (after installation):**
- Plugin files: `/var/www/html/wp-content/plugins/family-media-manager/`
- Video storage: `/var/www/html/wp-content/uploads/family-videos/`

---

## Troubleshooting

### SSH Connection Issues:
```bash
# Test SSH connection first
ssh user@server-ip

# If key authentication fails, use password:
scp -o PreferredAuthentications=password family-media-manager.zip user@server:/tmp/
```

### Git Authentication:
```bash
# For SSH keys (recommended):
ssh-keygen -t ed25519 -C "your_email@example.com"
# Add public key to GitHub/GitLab settings

# For HTTPS, you'll be prompted for username/password or token
```

### Permission Issues on Server:
```bash
# Fix permissions if needed
sudo chown -R apache:apache /var/www/html/wp-content/plugins/family-media-manager
sudo chmod -R 755 /var/www/html/wp-content/plugins/family-media-manager
```

---

**Ready to transfer!** Choose your preferred method above.
