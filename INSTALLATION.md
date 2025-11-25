# Installation Guide - Campaign Management System

## Prerequisites

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Divi Theme (or any theme that supports custom post types)
- Administrator access to WordPress
- FTP or file manager access (optional, for manual installation)

## Installation Methods

### Method 1: ZIP File Upload (Recommended)

1. **Create Plugin ZIP File:**
   ```bash
   cd /path/to/pcocommunicationrequest
   zip -r campaign-management-system.zip campaign-management-system/
   ```

2. **Upload to WordPress:**
   - Log in to your WordPress admin panel
   - Navigate to **Plugins → Add New**
   - Click **Upload Plugin**
   - Click **Choose File** and select `campaign-management-system.zip`
   - Click **Install Now**

3. **Activate Plugin:**
   - Click **Activate Plugin** after installation completes
   - You should see "Campaign Briefs" in your WordPress admin menu

### Method 2: FTP/SFTP Upload

1. **Access Your Server:**
   - Connect to your server via FTP/SFTP
   - Navigate to `/wp-content/plugins/`

2. **Upload Plugin Folder:**
   - Upload the entire `campaign-management-system` folder
   - Ensure all files and subfolders are transferred

3. **Activate Plugin:**
   - In WordPress admin, go to **Plugins → Installed Plugins**
   - Find "Campaign Management System"
   - Click **Activate**

### Method 3: WP-CLI (Advanced)

```bash
# Navigate to WordPress installation
cd /path/to/wordpress

# Copy plugin to plugins directory
cp -r /path/to/pcocommunicationrequest/campaign-management-system wp-content/plugins/

# Activate plugin
wp plugin activate campaign-management-system
```

## Post-Installation Configuration

### Step 1: Verify Installation

1. Check that "Campaign Briefs" appears in the WordPress admin menu
2. Navigate to **Campaign Briefs → Settings** to verify the settings page loads
3. Visit **Campaign Briefs → Dashboard** to see the overview

### Step 2: Configure Basic Settings

1. **Go to Settings → Campaign Briefs**

2. **Set Communications Coordinator Email:**
   - Enter the email address for notifications
   - This email will receive alerts when briefs are accepted or commented on

3. **Configure Notification Preferences:**
   - ☑ Notify on New Comments (recommended)
   - ☑ Notify on Status Changes (recommended)

4. **Save Changes**

### Step 3: Set Up Ministries and Service Levels

1. **Add/Edit Ministries:**
   - Go to **Campaign Briefs → Ministries**
   - Default ministries are pre-created from your form
   - Add any additional ministries needed

2. **Verify Service Levels:**
   - Go to **Campaign Briefs → Service Levels**
   - Should see: Green, Blue, Black (pre-created)
   - Do not delete these - they're required for the system

### Step 4: Configure Permalink Settings

1. **Go to Settings → Permalinks**
2. **Ensure permalink structure is set to:**
   - **Post name** (recommended): `https://metropolitanbible.church/sample-post/`
   - Or any custom structure
   - **NOT** "Plain" (won't work with shareable links)
3. **Click Save Changes** to flush rewrite rules

### Step 5: Create Test Brief

1. **Go to Campaign Briefs → Add New**
2. **Fill in basic information:**
   - Title: "Test Campaign"
   - Ministry/Department: Select a ministry
   - Service Designation: Select "Green"
3. **Save as Draft**
4. **Copy the shareable link** from the sidebar
5. **Open link in new tab** to verify brief displays correctly

## User Setup

### Communications Team Members

1. **Create or modify user accounts:**
   - Go to **Users → Add New** (or edit existing)
   - Username: team member's name
   - Email: their work email
   - **Role: Editor** (required for editing briefs)

2. **Grant access:**
   - Editors can create, edit, and manage all campaign briefs
   - They'll see the Campaign Briefs menu in admin

### Ministry Leaders (Optional)

For ministry leaders who need to comment on briefs:

**Option A: No Account Required (Recommended)**
- Ministry leaders can view briefs via shareable link
- Comments require only name and email (no login)

**Option B: WordPress Subscriber Account**
- Create user with **Subscriber** role
- They can log in to comment
- No access to admin functions

## Permissions & Capabilities

| Role | Create Briefs | Edit Briefs | View Briefs | Comment | Change Status |
|------|--------------|-------------|-------------|---------|---------------|
| Administrator | ✓ | ✓ | ✓ | ✓ | ✓ |
| Editor | ✓ | ✓ | ✓ | ✓ | ✓ |
| Subscriber | ✗ | ✗ | ✓ (via link) | ✓ | ✗ |
| Public | ✗ | ✗ | ✓ (via link) | ✓ | ✗ |

## Troubleshooting Installation

### Issue: Plugin won't activate

**Solution:**
- Check PHP version: `php -v` (must be 7.4+)
- Check WordPress version: Dashboard → Updates (must be 5.8+)
- Enable WordPress debug mode to see error messages

### Issue: "Campaign Briefs" menu not appearing

**Solution:**
- Deactivate and reactivate the plugin
- Clear browser cache
- Check that your user role is Administrator or Editor

### Issue: Shareable links show 404 error

**Solution:**
- Go to Settings → Permalinks
- Click "Save Changes" without changing anything
- This flushes the rewrite rules

### Issue: Styles not loading correctly

**Solution:**
- Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
- Clear WordPress cache if using a caching plugin
- Ensure CSS files were uploaded: `/wp-content/plugins/campaign-management-system/assets/css/`

### Issue: JavaScript features not working

**Solution:**
- Check browser console for errors (F12 → Console tab)
- Ensure JS files were uploaded: `/wp-content/plugins/campaign-management-system/assets/js/`
- Try disabling other plugins to check for conflicts

## Uninstallation

### To Remove Plugin (Keep Data)

1. Go to **Plugins → Installed Plugins**
2. Find "Campaign Management System"
3. Click **Deactivate**

Data will be preserved. Reactivating will restore full functionality.

### To Remove Plugin (Delete Data)

1. **Deactivate** plugin first
2. Click **Delete**
3. Confirm deletion

**Warning:** This will permanently delete:
- All campaign briefs
- All comments on briefs
- Plugin settings

**Not Deleted:**
- Ministries taxonomy terms (can manually delete)
- Service Levels taxonomy terms (can manually delete)

## Backup Recommendations

Before installing or updating:

1. **Backup WordPress Database:**
   - Use phpMyAdmin, WP-CLI, or backup plugin
   - Focus on tables: `wp_posts`, `wp_postmeta`, `wp_terms`, `wp_term_relationships`

2. **Backup WordPress Files:**
   - At minimum: `/wp-content/plugins/campaign-management-system/`

3. **Test on Staging Site First (if available):**
   - Install on staging site
   - Test functionality
   - Then deploy to production

## Next Steps

After installation, proceed to:

1. **Read** [README.md](README.md) for usage instructions
2. **Review** [ARCHITECTURE_PROPOSAL.md](ARCHITECTURE_PROPOSAL.md) for system overview
3. **Create your first campaign brief** following the workflow
4. **Set up Planning Center API** (Phase 2) in Settings

## Support

If you encounter issues not covered here:

1. Check the main [README.md](README.md) file
2. Review [ARCHITECTURE_PROPOSAL.md](ARCHITECTURE_PROPOSAL.md) for technical details
3. Enable WordPress debug mode to see detailed error messages
4. Contact the development team with error details

## System Requirements Checklist

Before installation, verify:

- [ ] WordPress 5.8 or higher
- [ ] PHP 7.4 or higher
- [ ] MySQL 5.6 or higher (or MariaDB 10.0+)
- [ ] HTTPS enabled (recommended for security)
- [ ] wp-content/plugins/ directory is writable
- [ ] Permalink structure is NOT set to "Plain"
- [ ] Administrator access to WordPress
- [ ] At least 10MB available disk space

## Success Indicators

You'll know installation was successful when:

1. ✓ "Campaign Briefs" menu appears in WordPress admin
2. ✓ Dashboard page loads without errors
3. ✓ You can create a test brief
4. ✓ Shareable link works when opened in new tab
5. ✓ Brief displays with correct styling (colors, tables, layout)
6. ✓ Comments can be added to brief
7. ✓ Service levels (Green/Blue/Black) are visible in dropdown

---

**Installation Date:** _________________
**Installed By:** _________________
**WordPress Version:** _________________
**Plugin Version:** 1.0.0
