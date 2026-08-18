# CyberPanel Deployment Guide

This project is configured for easy deployment to CyberPanel using the `deploy-cyber.sh` script.

## Pre-requisites
1. **Local Machine**: You must have `npm`, `zip`, and `curl` installed.
2. **CyberPanel**:
   - Ensure the domain `valo.npms.pro` is created.
   - Go to **Websites** > **List Websites** > **Manage** (for valo.npms.pro).
   - Click on **Setup Node.js App**.
   - Set **Application root** to `/home/npms.pro/valo.npms.pro`.
   - Set **Application Startup file** to `server.js`.

## How to Deploy
1. Open your terminal in the project root.
2. Run the deployment script:
   ```bash
   ./deploy-cyber.sh
   ```
3. The script will:
   - Build the Next.js project.
   - Zip the essential files.
   - Upload them to the server via FTP.
   - Trigger a remote extraction script (`extract.php`) to unzip everything automatically.

## Post-Deployment
After the script finishes, go to CyberPanel and **Restart** your Node.js application.

---
**Note**: The script uses FTP credentials provided in your request. If they change, update the variables at the top of `deploy-cyber.sh`.
