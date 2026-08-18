#!/bin/bash

# =========================
# FTP CONFIG
# =========================
FTP_HOST="ftp.npms.pro"
FTP_USER="admin_npms"
FTP_PASS="admin_npms"
REMOTE_PATH="/valo.npms.pro"

echo "🚀 Uploading Next.js source to VPS..."
echo "Host: $FTP_HOST"
echo "Path: $REMOTE_PATH"

# =========================
# FTP UPLOAD (NO node_modules)
# =========================
lftp -u "$FTP_USER","$FTP_PASS" $FTP_HOST <<EOF
set ftp:ssl-allow no
mirror -R \
  --exclude node_modules/ \
  --exclude .next/ \
  --exclude .git/ \
  --exclude .DS_Store \
  --exclude "*.log" \
  ./ $REMOTE_PATH
bye
EOF

echo "✅ SOURCE UPLOAD COMPLETE"

