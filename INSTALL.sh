#!/bin/bash

# Family Media Manager - Installation Script for Fedora Server
# This script installs the plugin to WordPress

echo "================================"
echo "Family Media Manager - Installer"
echo "================================"
echo ""

# Configuration
PLUGIN_SOURCE="/home/bob/projects/photo-converter/family-media-manager"
WP_PLUGINS_DIR="/var/www/html/wp-content/plugins"
PLUGIN_NAME="family-media-manager"
WP_USER="apache"
WP_GROUP="apache"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "ERROR: Please run as root or with sudo"
    echo "Usage: sudo bash INSTALL.sh"
    exit 1
fi

# Check if WordPress is installed
if [ ! -d "$WP_PLUGINS_DIR" ]; then
    echo "ERROR: WordPress plugins directory not found at $WP_PLUGINS_DIR"
    echo "Please verify your WordPress installation path"
    exit 1
fi

echo "Step 1: Checking for existing installation..."
if [ -d "$WP_PLUGINS_DIR/$PLUGIN_NAME" ]; then
    echo "WARNING: Plugin already exists. Do you want to overwrite it? (y/n)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        echo "Removing existing installation..."
        rm -rf "$WP_PLUGINS_DIR/$PLUGIN_NAME"
    else
        echo "Installation cancelled"
        exit 0
    fi
fi

echo ""
echo "Step 2: Copying plugin files..."
cp -r "$PLUGIN_SOURCE" "$WP_PLUGINS_DIR/"
echo "✓ Files copied"

echo ""
echo "Step 3: Setting correct permissions..."
chown -R $WP_USER:$WP_GROUP "$WP_PLUGINS_DIR/$PLUGIN_NAME"
chmod -R 755 "$WP_PLUGINS_DIR/$PLUGIN_NAME"
echo "✓ Permissions set"

echo ""
echo "Step 4: Checking for ffmpeg (optional but recommended)..."
if command -v ffmpeg &> /dev/null; then
    echo "✓ ffmpeg is installed"
else
    echo "⚠ ffmpeg not found. Install it for automatic thumbnail generation:"
    echo "  sudo dnf install ffmpeg"
fi

echo ""
echo "Step 5: Checking Apache configuration..."
if httpd -M 2>&1 | grep -q "rewrite_module"; then
    echo "✓ mod_rewrite is enabled"
else
    echo "⚠ mod_rewrite may not be enabled. Enable it with:"
    echo "  sudo dnf install mod_rewrite"
    echo "  sudo systemctl restart httpd"
fi

echo ""
echo "================================"
echo "Installation Complete!"
echo "================================"
echo ""
echo "Next steps:"
echo "1. Go to your WordPress admin panel"
echo "2. Navigate to Plugins > Installed Plugins"
echo "3. Find 'Family Media Manager' and click Activate"
echo "4. Go to Family Media > Settings to configure"
echo "5. Create two pages with these shortcodes:"
echo "   - Registration page: [fmm_registration]"
echo "   - Media library page: [fmm_media_library]"
echo ""
echo "For detailed setup instructions, see README.md"
echo ""
