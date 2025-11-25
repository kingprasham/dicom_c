# How to Fix Google Drive Backup with OAuth 2.0

## Problem
Service Accounts don't have storage quota. You're getting this error:
```
Service Accounts do not have storage quota
```

## Solution: Use OAuth 2.0 Credentials

### Step 1: Create OAuth 2.0 Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project (or the same project you used for service account)
3. Navigate to **APIs & Services** → **Credentials**
4. Click **"Create Credentials"** → **"OAuth client ID"**
5. If prompted, configure the OAuth consent screen first:
   - Choose **External** (for personal Gmail) or **Internal** (for Workspace)
   - Fill in app name: "DICOM Viewer Backup"
   - Add your email as developer contact
   - Click **Save and Continue** through the screens
6. Back on Credentials page, click **"Create Credentials"** → **"OAuth client ID"** again
7. Application type: **"Desktop app"** or **"Web application"**
   - If Desktop app: Just give it a name and click Create
   - If Web application: Leave redirect URIs empty for now
8. Click **"Create"**
9. Click **"Download JSON"** button (download icon next to OAuth client)
10. Save the file (e.g., `oauth_credentials.json`)

### Step 2: Add OAuth Account to DICOM Viewer

1. Login to your DICOM Viewer admin panel
2. Go to **Hospital Configuration** or **Google Drive Backup Configuration**
3. Click **"Add Account"** button
4. Enter an account name (e.g., "My Google Drive")
5. Upload the **OAuth 2.0 JSON file** you downloaded
6. Click **"Save Account"**

✅ **The validation now accepts OAuth 2.0 credentials!**

### Step 3: Grant Permissions (TODO - Requires Additional Development)

> **IMPORTANT NOTE:** The backend `BackupManager.php` currently only supports Service Account authentication. To fully support OAuth 2.0, we need to:
>
> 1. Implement OAuth authorization flow
> 2. Handle token refresh
> 3. Store access/refresh tokens
>
> **Current workaround:** Use OAuth credentials with **domain-wide delegation** if you have Google Workspace, OR continue using the service account with a **ShredDrive** (see alternative solution below).

---

## Alternative Solution: Use Shared Drive (Google Workspace Only)

If you have Google Workspace:

1. Create a **Shared Drive** in Google Drive
2. Add your **Service Account email** to the Shared Drive
   - Give it **Content Manager** or **Manager** permissions
3. The service account can now upload to the Shared Drive
4. Update your backup folder name to match the Shared Drive name

This works because Shared Drives have their own storage quota, separate from individual accounts.

---

## Quick Fix Applied

✅ **Modified Files:**
- `admin/hospital-config.php` - Frontend now accepts OAuth 2.0
- `api/backup/manage-accounts.php` - Backend now accepts OAuth 2.0

The UI will no longer show the "Must be a service account JSON" error when you upload OAuth credentials.

**However**, to actually USE OAuth 2.0 for backups, additional backend development is needed in `BackupManager.php` to handle the OAuth flow.

---

## Recommended Next Steps

**Option A - Use Shared Drive (Immediate Solution):**
1. Get Google Workspace account
2. Create Shared Drive
3. Add your service account to it
4. Continue using service account credentials

**Option B - Implement Full OAuth Support (Requires Development):**
1. Modify `BackupManager.php` to support OAuth 2.0 flow
2. Implement authorization redirect
3. Store refresh tokens
4. Update Google Client initialization

Let me know which approach you'd like to pursue!
