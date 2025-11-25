# Enable PHP ZIP Extension - Quick Guide

## Automated Method (Recommended)

**Run this script as Administrator:**
```
setup/enable_zip_extension.bat
```

This will:
1. ✓ Find your php.ini file
2. ✓ Create a backup
3. ✓ Enable the ZIP extension
4. ✓ Show you what to do next

## Manual Method

If the script doesn't work, follow these steps:

### Step 1: Open php.ini

1. Open: `C:\xampp\php\php.ini` in a text editor (Notepad++)
2. Or use XAMPP Control Panel → Apache Config → php.ini

### Step 2: Find and Enable ZIP Extension

Search for: `;extension=zip`

Change it to: `extension=zip` (remove the semicolon)

If you can't find it, add this line anywhere in the extensions section:
```
extension=zip
```

### Step 3: Save the File

Save and close php.ini

### Step 4: Restart Apache

1. Open XAMPP Control Panel
2. Click **Stop** next to Apache
3. Wait 3-5 seconds
4. Click **Start** next to Apache

### Step 5: Verify

Test the backup again - it should now work!

## Troubleshooting

**If it still doesn't work:**

1. Check if the extension file exists: `C:\xampp\php\ext\php_zip.dll`
2. Make sure you edited the correct php.ini (XAMPP might have multiple)
3. Verify Apache restarted successfully (check XAMPP logs)

**To verify ZIP is enabled:**
Create a file `test_zip.php`:
```php
<?php
if (class_exists('ZipArchive')) {
    echo "✓ ZIP extension is enabled!";
} else {
    echo "✗ ZIP extension is NOT enabled";
}
?>
```

Run it: `php test_zip.php`
