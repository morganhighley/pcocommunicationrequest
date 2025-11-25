# Planning Center Integration & Workflows

## Overview

This document explains what happens in Planning Center at each stage of the campaign brief workflow and how the systems interact.

## Current Setup (Phase 1 - Manual)

### When a Form is Submitted in Planning Center

**Communication Request Form (People):**
1. Ministry leader fills out form in Planning Center People
2. Form submission is saved in Planning Center
3. Planning Center sends email notification to designated recipients
4. **YOU (Communications Team):** Receive notification email
5. **YOU DO:** Log into Planning Center and review the form submission
6. **YOU DO:** Manually copy data from Planning Center to WordPress
7. **YOU DO:** Create new Campaign Brief in WordPress with copied data

**Event Request Form (Calendar):**
1. Ministry leader submits event in Planning Center Calendar
2. Event appears in calendar
3. Planning Center sends notification
4. **YOU DO:** Review event details in Planning Center
5. **YOU DO:** Manually create Campaign Brief in WordPress

**Nothing automatic happens yet** - this is Phase 1 manual workflow.

## What Happens When Brief is Approved (Phase 1 - Manual)

**In WordPress:**
1. Ministry leader reviews brief via shareable link
2. Leader clicks "Accept Brief"
3. Brief status changes to "Accepted"
4. Brief is locked (read-only)
5. Email sent to communications team

**In Planning Center:**
Currently, **nothing automatic happens** in Planning Center.

**What YOU Can Do Manually:**
1. Create a Task in Planning Center Tasks
2. Assign task to ministry leader
3. Mark task complete when brief is accepted
4. Add notes about the campaign

This keeps Planning Center updated but requires manual work.

## Future Setup (Phase 2 - API Integration)

### When Form is Submitted

**With API Integration Active:**
1. Form submitted in Planning Center (same as before)
2. **WordPress polls Planning Center API** every hour
3. **WordPress finds** new form submissions
4. **WordPress automatically creates** draft Campaign Brief
5. **WordPress pre-populates** all fields from form data
6. Communications team receives notification
7. **YOU DO:** Review auto-created brief, add details, assign service level
8. **YOU DO:** Send for acceptance

**Benefits:**
- No more manual data entry
- Reduced errors from copy/paste
- Faster brief creation
- Form data captured immediately

### When Brief is Approved (Phase 2)

**With API Integration:**
1. Ministry leader accepts brief (same as Phase 1)
2. **WordPress automatically creates** Planning Center Task
3. Task appears in Planning Center Tasks
4. Task assigned to ministry representative
5. Task description includes brief link and details
6. Task marked as complete automatically
7. Ministry leader and staff can see task in Planning Center

**Benefits:**
- Automatic tracking in Planning Center
- Visibility for staff who don't use WordPress
- Integration with existing Planning Center workflows
- Task history preserved

## Planning Center Forms Integration Details

### Communication Request Form

**Fields That Map to Campaign Brief:**

| Planning Center Field | Brief Field | How It's Used |
|-----------------------|-------------|---------------|
| Project Title | Campaign Title | Auto-populated |
| Ministry Representative | Ministry Representative | Auto-populated |
| Ministry Rep Email | Ministry Rep Email | Auto-populated |
| Which ministry? | Ministry Department | Auto-populated |
| Project Due Date | Event Dates | Auto-populated |
| Main purpose | Context/History | Auto-populated |
| Goals | Goals | Auto-populated |
| Scripture references | Scriptures | Auto-populated |
| Who to communicate with | Target Audience | Auto-populated |
| Additional details | Key Facts | Auto-populated |

**Fields NOT Automatically Mapped:**
- Communications Coordinator (set by team)
- Service Designation (determined by team)
- Tagline (created by team)
- Slug (generated from title)
- Promotion dates (calculated by team)
- Channel Plan (created by team)
- Creative Direction (developed by team)

### Event Request Form

**Fields That Map to Campaign Brief:**

| Planning Center Field | Brief Field | How It's Used |
|-----------------------|-------------|---------------|
| Event Name | Campaign Title | Auto-populated |
| Main Contact | Ministry Representative | Auto-populated |
| Start/End Dates | Event Dates | Auto-populated |
| Ministries Involved | Ministry Department | Auto-populated |
| Description | Context/History | Auto-populated |
| Estimated Attendance | (Helps determine service level) | Reference only |

## Planning Center Tasks Integration (Phase 2)

### Task Creation

**When brief status changes to "Pending Acceptance":**
1. WordPress calls Planning Center API
2. Creates new Task in Planning Center
3. Task details:
   - **Title:** "Review & Accept Campaign Brief: [Campaign Name]"
   - **Description:** Brief summary + shareable link
   - **Assigned to:** Ministry representative (matched by email)
   - **Due date:** 3 days from creation
   - **List:** "Communication Campaign Briefs" (auto-created)

### Task Completion

**When ministry leader accepts brief:**
1. Brief status changes to "Accepted" in WordPress
2. WordPress calls Planning Center API
3. Updates task status to "Complete"
4. Adds completion note with acceptor name and date

### Task Visibility

**Who can see the tasks:**
- Organization Administrators
- People with access to the Tasks product
- The assigned person (ministry representative)
- Anyone with shared access to the task list

**Benefits:**
- Non-staff leaders can see their tasks without WordPress access
- Staff can track campaign progress in familiar Planning Center interface
- Integration with existing task management workflows

## API Authentication (Phase 2)

### Personal Access Token Method (Recommended)

**Setup Process:**
1. Go to Planning Center → Personal Settings → Developer
2. Click "New Personal Access Token"
3. Fill in:
   - Description: "Campaign Brief System"
   - Versions: Use defaults (no changes needed)
4. Click Submit
5. **Copy the Application ID and Secret shown**
6. Paste into WordPress Settings → Campaign Briefs

**Security:**
- Token associated with your account
- Token has same permissions as your account
- Can be revoked anytime in Planning Center
- WordPress stores credentials securely (not in database)

**Permissions Needed:**
- People (read-only) - to fetch form submissions
- Calendar (read-only) - to fetch event details
- Tasks (read-write) - to create/update tasks

### What the API Does

**Read Operations (Planning Center → WordPress):**
- Fetch new form submissions from People
- Fetch event details from Calendar
- Check for updates to existing forms/events

**Write Operations (WordPress → Planning Center):**
- Create tasks for brief acceptance
- Update task status when brief accepted
- Add notes/comments to tasks

**NOT Done:**
- Does not modify form submissions
- Does not change event details
- Does not delete anything in Planning Center
- Does not add people or permissions

## Workflow Diagrams

### Phase 1 (Current) - Manual Workflow

```
Planning Center Form Submission
           ↓
    Email to Comms Team
           ↓
Team Views Form in Planning Center
           ↓
Team Manually Copies Data
           ↓
Team Creates Brief in WordPress
           ↓
  Team Completes All Fields
           ↓
  Team Sends Shareable Link
           ↓
  Leader Reviews & Accepts
           ↓
    Brief Locked in WordPress
           ↓
(Optional) Team Manually Creates
    Task in Planning Center
```

### Phase 2 (Future) - API Automated Workflow

```
Planning Center Form Submission
           ↓
WordPress Polls API (every hour)
           ↓
WordPress Auto-Creates Draft Brief
           ↓
WordPress Pre-Populates Fields
           ↓
    Email to Comms Team
           ↓
Team Reviews Auto-Created Brief
           ↓
   Team Adds Final Details
           ↓
 Team Changes Status to "Pending"
           ↓
WordPress Auto-Creates PC Task
           ↓
  Leader Sees Task in Planning Center
           ↓
Leader Clicks Link in Task
           ↓
   Leader Reviews & Accepts
           ↓
WordPress Auto-Completes PC Task
           ↓
       Brief Locked
           ↓
    Campaign Executes
```

## Data Flow

### Phase 1 Data Flow
```
Planning Center (Form Data)
    ↓ [Manual Copy]
WordPress (Campaign Brief)
    ↓ [Manual Share]
Ministry Leader (Email Link)
    ↓ [Manual Task Creation]
Planning Center (Task) [Optional]
```

### Phase 2 Data Flow
```
Planning Center (Form Data)
    ↓ [API: Automated Poll]
WordPress (Auto-Created Brief)
    ↓ [API: Auto Task Creation]
Planning Center (Task Created)
    ↓ [Email Notification]
Ministry Leader (Task + Link)
    ↓ [Accept via Link]
WordPress (Brief Accepted)
    ↓ [API: Task Update]
Planning Center (Task Completed)
```

## Recommendations

### For Phase 1 (Now)
1. **Set up email forwarding** from Planning Center forms to communications team
2. **Create a checklist** for manual brief creation to ensure consistency
3. **Bookmark Planning Center forms** for quick access
4. **Optionally create Planning Center Tasks** manually for accountability

### For Phase 2 (Future)
1. **Set up Personal Access Token** when ready to automate
2. **Test on a few briefs** before full deployment
3. **Monitor API usage** to ensure within limits
4. **Create custom Planning Center Task list** for campaign briefs
5. **Train team** on new automated workflow

## Troubleshooting

### Form Submissions Not Appearing in WordPress (Phase 2)

**Check:**
1. API credentials are correct in Settings
2. WordPress cron is running (hourly job)
3. Planning Center form IDs are configured
4. Check WordPress error logs

### Tasks Not Creating in Planning Center (Phase 2)

**Check:**
1. API token has Tasks permission
2. Ministry representative email matches Planning Center
3. Task list exists or can be auto-created
4. Check Planning Center API rate limits

### Ministry Leaders Can't See Tasks

**Check:**
1. Leader has access to Planning Center
2. Leader has access to Tasks product
3. Task is assigned to correct person
4. Task list is visible to appropriate people

## Support & Resources

- **Planning Center API Documentation:** https://developer.planning.center/docs/
- **Planning Center Tasks:** https://planning.center/tasks
- **Plugin Settings:** WordPress → Settings → Campaign Briefs

---

**Questions?** Contact the communications team or IT administrator.
