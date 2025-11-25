# Campaign Management System for Metropolitan Bible Church

A WordPress-based system for managing communication campaign requests from Planning Center, generating campaign briefs, and collaborating with ministry leaders.

## Quick Start

### Installation

1. **Upload Plugin to WordPress:**
   ```
   zip -r campaign-management-system.zip campaign-management-system/
   ```
   Upload via WordPress Admin → Plugins → Add New → Upload Plugin

2. **Activate Plugin:**
   Navigate to Plugins and activate "Campaign Management System"

3. **Configure Settings:**
   - Go to Settings → Campaign Briefs
   - Add Planning Center API credentials (optional for Phase 1)
   - Set communications team email

4. **Create User Accounts:**
   - Create WordPress Editor accounts for communications team members
   - Create Subscriber accounts for ministry leaders who need to comment

### Usage

#### For Communications Team

**Creating a New Brief:**
1. Navigate to Campaign Briefs → Add New
2. Fill in brief details from Planning Center form submission
3. Select Service Designation (Green/Blue/Black)
4. Save as Draft
5. When ready, change status to "Pending Acceptance"
6. Copy shareable link and send to ministry leader

**Dashboard:**
- View all briefs at Campaign Briefs → Dashboard
- Filter by status, service level, or ministry
- Quick status changes and archiving

#### For Ministry Leaders

**Reviewing a Brief:**
1. Open shareable link received from communications team
2. Review all sections of the brief
3. Add comments with feedback
4. Click "Accept Brief" when approved

**Requesting Changes:**
1. Add comments explaining needed changes
2. Communications team will make updates
3. You'll be notified when updated

## Features

### Phase 1 (MVP) - Available Now
- ✅ Campaign brief creation and editing
- ✅ Three service levels (Green/Blue/Black)
- ✅ Shareable brief links (no login required for viewing)
- ✅ WordPress commenting system
- ✅ Status workflow (Draft → Pending → Accepted → Archived)
- ✅ Communications team dashboard
- ✅ PDF-style viewing template
- ✅ Brief locking after acceptance
- ✅ Search and filter archived briefs

### Phase 2 (Coming Soon)
- ⏳ Planning Center form auto-import
- ⏳ Email notifications
- ⏳ Planning Center Tasks integration
- ⏳ File upload support
- ⏳ Automatic brief pre-population

### Phase 3 (Future)
- 📋 Webhook automation
- 📋 Advanced reporting
- 📋 Channel plan templates
- 📋 Asset library integration

## System Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Divi Theme (for embedding in pages)
- Planning Center account (for API integration in Phase 2)

## File Structure

```
campaign-management-system/
├── campaign-management-system.php    # Main plugin file
├── includes/
│   ├── class-post-type.php          # Custom post type registration
│   ├── class-meta-boxes.php         # Custom fields
│   ├── class-dashboard.php          # Admin dashboard
│   ├── class-api-planning-center.php # PC integration
│   ├── class-workflow.php           # Status management
│   └── class-settings.php           # Plugin settings
├── templates/
│   ├── brief-view.php               # Public brief display
│   ├── brief-edit.php               # Admin edit interface
│   └── dashboard.php                # Communications dashboard
├── assets/
│   ├── css/
│   │   ├── admin.css               # Admin styles
│   │   └── public.css              # Brief viewing styles
│   └── js/
│       ├── admin.js                # Admin functionality
│       └── public.js               # Public interactions
└── README.md                        # This file
```

## Configuration

### Planning Center API (Phase 2)

1. **Get API Credentials:**
   - Log in to Planning Center
   - Go to Personal Settings → Developer
   - Create new Personal Access Token
   - Copy Application ID and Secret

2. **Add to WordPress:**
   - Settings → Campaign Briefs → API Settings
   - Paste credentials
   - Test connection

### Email Notifications

Configure in Settings → Campaign Briefs → Notifications:
- Communications team email
- Auto-notify on new comments
- Auto-notify on status changes

## Customization

### Modifying Templates

Templates can be overridden in your theme:
```
divi-child/campaign-management-system/brief-view.php
```

### Adding Custom Fields

Edit `includes/class-meta-boxes.php` and add fields to the appropriate section.

### Changing Service Level Colors

Edit `assets/css/public.css`:
```css
.service-green { background-color: #custom; }
.service-blue { background-color: #custom; }
.service-black { background-color: #custom; }
```

## Workflow

```
┌─────────────────┐
│ Planning Center │
│ Form Submission │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Comms Team      │
│ Creates Brief   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Status: Draft   │
│ (Team edits)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Status: Pending │
│ (Send to leader)│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Leader Reviews  │
│ & Comments      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Leader Accepts  │
│ (Brief locked)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Campaign Runs   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Status: Archived│
│ (Searchable)    │
└─────────────────┘
```

## Support

### Documentation
- Full documentation: [Link to docs]
- Video tutorials: [Link to videos]
- FAQ: [Link to FAQ]

### Troubleshooting

**Briefs not saving:**
- Check WordPress memory limit (should be at least 64MB)
- Disable other plugins to identify conflicts

**Shareable links not working:**
- Check permalink settings (should be "Post name")
- Flush permalinks: Settings → Permalinks → Save

**API connection failing:**
- Verify credentials in Settings → Campaign Briefs
- Check Planning Center API status
- Review error logs in Settings → Campaign Briefs → Logs

### Getting Help

- GitHub Issues: https://github.com/morganhighley/pcocommunicationrequest/issues
- Email: [Your support email]

## Development

### Local Development Setup

```bash
# Clone repository
git clone https://github.com/morganhighley/pcocommunicationrequest.git

# Navigate to WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins/

# Create symbolic link
ln -s /path/to/pcocommunicationrequest/campaign-management-system campaign-management-system
```

### Running Tests

```bash
# Install dependencies
composer install

# Run PHPUnit tests
./vendor/bin/phpunit

# Run code standards check
./vendor/bin/phpcs
```

## Changelog

### Version 1.0.0 (2025-11-25)
- Initial MVP release
- Custom post type for campaign briefs
- Three service levels
- Status workflow
- Shareable links
- Commenting system
- Communications dashboard

## License

Proprietary - Metropolitan Bible Church

## Credits

- **System Design:** Adapted from Watermark Community Church's Master Calendar process (CLC25)
- **Development:** Claude Code
- **Organization:** Metropolitan Bible Church

## Roadmap

- **v1.1:** Planning Center API integration
- **v1.2:** Email notifications and Planning Center Tasks
- **v1.3:** File uploads and asset management
- **v1.4:** Advanced reporting and analytics
- **v2.0:** Full automation with webhooks
