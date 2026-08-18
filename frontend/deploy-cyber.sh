#!/bin/bash

# =========================
# CONFIG
# =========================
FTP_HOST="ftp.npms.pro"
FTP_USER="admin_npms"
FTP_PASS="admin_npms"
REMOTE_PATH="/valo.npms.pro/"
DOMAIN="valo.npms.pro"

BUILD_DIR=".next"
EXPORT_DIR="out"

echo "=============================="
echo "🚀 Deploying Next.js to CyberPanel"
echo "Domain: $DOMAIN"
echo "=============================="

# =========================
# CLEAN & INSTALL
# =========================
echo "🧹 Cleaning old build..."
rm -rf .next out node_modules package-lock.json

echo "📦 Installing dependencies..."
npm install || exit 1

# =========================
# BUILD
# =========================
echo "🏗️ Building Next.js..."
npm run build || exit 1

# =========================
# EXPORT STATIC SITE
# =========================
echo "📤 Exporting static build..."
npx next export || exit 1

# =========================
# CREATE .htaccess
# =========================
echo "📝 Creating .htaccess..."
cat <<EOF > out/.htaccess
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove trailing slash
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)/$ /\$1 [R=301,L]

# Next.js routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [L]
EOF

# =========================
# FTP UPLOAD
# =========================
echo "📡 Uploading files via FTP..."

lftp -u "$FTP_USER","$FTP_PASS" $FTP_HOST <<EOF
set ftp:ssl-allow no
mirror -R --delete --verbose $EXPORT_DIR $REMOTE_PATH
bye
EOF

# =========================
# DONE
# =========================
echo "=============================="
echo "✅ DEPLOY COMPLETE!"
echo "🌍 Visit: https://$DOMAIN"
echo "=============================="
