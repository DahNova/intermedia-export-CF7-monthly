#!/bin/bash

# Build script for CF7 Monthly Export plugin
# Creates a production-ready ZIP file for WordPress installation

set -e

echo "======================================"
echo "CF7 Monthly Export - Build Script"
echo "======================================"
echo ""

# Configuration
PLUGIN_SLUG="cf7-monthly-export"
VERSION=$(grep "Version:" cf7-monthly-export.php | awk '{print $3}')
BUILD_DIR="build"
DIST_DIR="dist"
PLUGIN_DIR="$BUILD_DIR/$PLUGIN_SLUG"

echo "Plugin: $PLUGIN_SLUG"
echo "Version: $VERSION"
echo ""

# Clean previous builds
echo "→ Cleaning previous builds..."
rm -rf "$BUILD_DIR"
rm -rf "$DIST_DIR"
mkdir -p "$BUILD_DIR"
mkdir -p "$DIST_DIR"

# Create plugin directory structure
echo "→ Creating plugin directory structure..."
mkdir -p "$PLUGIN_DIR"

# Copy plugin files
echo "→ Copying plugin files..."
cp -r includes "$PLUGIN_DIR/"
cp -r admin "$PLUGIN_DIR/"
cp cf7-monthly-export.php "$PLUGIN_DIR/"
cp composer.json "$PLUGIN_DIR/"
cp README.md "$PLUGIN_DIR/"
cp .gitignore "$PLUGIN_DIR/"

# Install Composer dependencies (production only)
echo "→ Installing Composer dependencies..."
cd "$PLUGIN_DIR"
composer install --no-dev --optimize-autoloader --no-interaction
cd ../..

# Remove unnecessary files
echo "→ Removing development files..."
rm -rf "$PLUGIN_DIR/vendor/bin"
rm -rf "$PLUGIN_DIR/vendor/*/*/tests"
rm -rf "$PLUGIN_DIR/vendor/*/*/test"
rm -rf "$PLUGIN_DIR/vendor/*/*/.git"
find "$PLUGIN_DIR/vendor" -name "*.md" -not -name "README.md" -delete
find "$PLUGIN_DIR/vendor" -name ".gitignore" -delete
find "$PLUGIN_DIR/vendor" -name ".gitattributes" -delete
find "$PLUGIN_DIR/vendor" -name "composer.json" -delete
find "$PLUGIN_DIR/vendor" -name "composer.lock" -delete

# Create ZIP file
echo "→ Creating ZIP file..."
cd "$BUILD_DIR"
zip -r "../$DIST_DIR/$PLUGIN_SLUG-$VERSION.zip" "$PLUGIN_SLUG" -q
cd ..

# Get file size
FILE_SIZE=$(du -h "$DIST_DIR/$PLUGIN_SLUG-$VERSION.zip" | cut -f1)

echo ""
echo "======================================"
echo "✓ Build completed successfully!"
echo "======================================"
echo ""
echo "Package: $DIST_DIR/$PLUGIN_SLUG-$VERSION.zip"
echo "Size: $FILE_SIZE"
echo ""
echo "Installation:"
echo "1. Go to WordPress Admin → Plugins → Add New"
echo "2. Click 'Upload Plugin'"
echo "3. Choose the ZIP file: $PLUGIN_SLUG-$VERSION.zip"
echo "4. Click 'Install Now'"
echo "5. Activate the plugin"
echo ""
