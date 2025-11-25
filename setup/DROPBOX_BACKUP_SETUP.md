# ✅ Dropbox Backup Setup - Simple & Easy!

## Why Dropbox is Better for Backups

✅ **5-Minute Setup** - No complex authentication  
✅ **2GB Free Storage** - Perfect for database backups  
✅ **Just One Token** - Copy/paste and you're done  
✅ **No OAuth Complexity** - Works immediately  

---

## Step 1: Create Dropbox App (3 minutes)

1. Go to https://www.dropbox.com/developers/apps
2. Click **"Create app"** button
3. Choose **"Scoped access"**
4. Choose **"Full Dropbox"** (or "App folder" if you prefer)
5. Name your app: **"DICOM Viewer Backup"**
6. Click **"Create app"**

---

## Step 2: Generate Access Token (1 minute)

1. On your app settings page, scroll to **"OAuth 2"** section
2. Find **"Generated access token"**
3. Click **"Generate"** button
4. **Copy the token** (it looks like: `sl.B1a2c3d4e5...`)
5. Save it somewhere safe - you'll paste it in the next step

**IMPORTANT:** This token never expires, so you only need to do this once!

---

## Step 3: Add to DICOM Viewer (1 minute)

### Using Hospital Config Page:

1. Login to DICOM Viewer admin panel
2. Go to **Hospital Configuration**
3. Scroll to **"Google Drive & Dropbox Backup"** section
4. Click **"Add Account"** button
5. Fill in the form:
   - **Account Name:** "My Dropbox Backup"
   - **Provider:** Select **"Dropbox"**
   - **Access Token:** Paste the token from Step 2
   - **Folder Name:** "/DICOM_Backups" (or your preferred folder)
6. Click **"Save Account"**

---

## Step 4: Test Backup (30 seconds)

1. Click **"Backup to All Accounts Now"** button
2. Wait a few seconds
3. You should see: **"Successful: 1, Failed: 0"**

✅ **Done!** Your backups are now going to Dropbox automatically!

---

## How to Verify

1. Open https://www.dropbox.com/
2. Navigate to Apps → DICOM Viewer Backup → DICOM_Backups
3. You should see your backup ZIP file there!

---

## Automatic Backups

The system will automatically backup every 6 hours (default). You can change this in the backup schedule settings.

---

## Troubleshooting

**Error: "Invalid token"**
- Generate a new token in Dropbox app settings
- Make sure you copied the entire token

**Error: "Permission denied"**
- Make sure you chose "Full Dropbox" access when creating the app
- Try regenerating the token

**Backup file not appearing in Dropbox**
- Check the folder name matches what you entered
- The folder is created automatically if it doesn't exist

---

## Storage Limits

- **Free Dropbox:** 2GB storage
- **Database backups:** Usually 10-100MB each
- **You can store:** 20-200 backups before reaching limit
- **Old backups:** Automatically deleted after 30 days

---

That's it! Simple, right? No complex Google Drive setup needed! 🎉
