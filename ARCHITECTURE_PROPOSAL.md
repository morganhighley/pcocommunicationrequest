# Campaign Management System - Architecture Proposal

## Executive Summary

This proposal outlines a WordPress-based campaign management system that integrates with Planning Center to streamline the communication request workflow for Metropolitan Bible Church. The system prioritizes simplicity while meeting all requirements.

## Answers to Key Questions

### 1. Where should campaign briefs live?

**Recommendation: WordPress Custom Post Type**

**Rationale:**
- No Google account requirement (addresses key constraint)
- Single source of truth with real-time updates
- Built-in WordPress user authentication
- Easy to control access and permissions
- Can embed in Divi using code blocks
- Supports custom fields for all brief data
- Built-in revision history

**Data Structure:**
- Custom Post Type: `campaign_brief`
- Custom fields for all brief sections
- WordPress categories for campaign levels (Green/Blue/Black)
- WordPress post status for workflow (Draft/Pending/Accepted/Archived)

### 2. How to authenticate viewers without requiring Google accounts?

**Recommendation: Hybrid Authentication Approach**

**Option A (Preferred): Public View with Planning Center Authentication**
- Briefs are viewable via shareable link (no login required for viewing)
- Comments require WordPress account OR Planning Center authentication
- Communications team uses WordPress accounts
- Ministry leaders can comment using their Planning Center credentials

**Option B (Fallback): WordPress User Accounts**
- Create WordPress subscriber accounts for ministry leaders
- Simple username/password (can be shared per ministry if needed)
- No requirement for personal emails

**Implementation:**
- Use WordPress nonce-protected links for sharing
- Brief URLs: `metropolitanbible.church/brief/[unique-id]`
- Comments via WordPress native system or integration with Planning Center Tasks

### 3. Simplest way to get form submissions from Planning Center?

**Recommendation: Three-Phase Approach**

**Phase 1 (Week 1 - MVP): Manual Entry**
- Communications team manually creates briefs from form data
- Planning Center Workflows email form submissions to team
- Team copies data into WordPress brief creation form
- **Advantage:** Works immediately, no API setup delays

**Phase 2 (Week 2-3): API Polling**
- WordPress cron job polls Planning Center API every 15 minutes
- Fetches new form submissions
- Creates draft briefs with pre-populated data
- **Advantage:** Semi-automated, reliable

**Phase 3 (Future): Webhook Integration**
- Planning Center webhook triggers on form submission
- Instantly creates draft brief in WordPress
- **Advantage:** Real-time automation
- **Note:** May require Planning Center webhook support verification

### 4. How to handle comment/acceptance workflow?

**Recommendation: Dual-Track Workflow**

**For Comments:**
- WordPress native commenting system on brief pages
- Comments require name/email (not anonymous)
- Communications team can reply inline
- Email notifications on new comments

**For Acceptance:**
- **Option A:** Planning Center Tasks (Recommended)
  - Task created: "Review & Accept Campaign Brief: [Title]"
  - Task assigned to ministry leader
  - Task description includes brief link
  - Task completion = acceptance
  - Visible to staff and campaign leaders

- **Option B:** WordPress-based acceptance
  - "Accept Brief" button on brief page
  - Requires email confirmation
  - Logs acceptance date/time/person

**Status Workflow:**
1. **Draft** - Communications team working on brief
2. **Pending Acceptance** - Sent to ministry leader for review
3. **Accepted** - Leader approved (brief locked)
4. **Unlocked** - Edited after acceptance (requires re-acceptance)
5. **Archived** - Campaign completed

### 5. Minimal first version deployable this week?

**MVP Feature Set (Deployable in 3-5 days):**

**Week 1 Deliverables:**
1. WordPress custom post type for campaign briefs
2. Brief creation form with all template fields
3. Public brief viewing page (shareable link)
4. Simple status dropdown (Draft/Pending/Accepted/Archived)
5. Communications team dashboard showing all briefs
6. Basic WordPress commenting on briefs
7. Manual brief creation from form data

**What's NOT in MVP:**
- Planning Center API integration (manual entry instead)
- Automated acceptance workflow (manual status change)
- File uploads (can add in phase 2)
- Advanced permissions (use WordPress roles)

## Technical Architecture

### System Components

```
┌─────────────────────┐
│  Planning Center    │
│  Forms (People &    │
│  Calendar)          │
└──────────┬──────────┘
           │
           │ Manual Copy (Phase 1)
           │ API Poll (Phase 2)
           │ Webhook (Phase 3)
           ▼
┌─────────────────────┐
│  WordPress          │
│  (Divi Theme)       │
│                     │
│  ┌───────────────┐  │
│  │ Custom Post   │  │
│  │ Type:         │  │
│  │ campaign_brief│  │
│  └───────────────┘  │
│                     │
│  ┌───────────────┐  │
│  │ Dashboard     │  │
│  │ (Comms Team)  │  │
│  └───────────────┘  │
│                     │
│  ┌───────────────┐  │
│  │ Brief View    │  │
│  │ (Public Link) │  │
│  └───────────────┘  │
└─────────────────────┘
           │
           │ Task Creation
           ▼
┌─────────────────────┐
│  Planning Center    │
│  Tasks              │
│  (Acceptance)       │
└─────────────────────┘
```

### Data Model

**WordPress Post Fields:**
- `post_type`: `campaign_brief`
- `post_title`: Campaign Title
- `post_status`: draft | pending | publish | archived
- `post_content`: Additional Content (Page 5)

**Custom Meta Fields:**
```
// Page 1: Campaign Brief
meta_ministry_department
meta_ministry_representative
meta_ministry_rep_email
meta_communications_coordinator
meta_service_designation (green|blue|black)
meta_campaign_title
meta_campaign_tagline
meta_campaign_slug
meta_event_dates
meta_promotion_dates
meta_file_path
meta_livestream_location

// Page 2: Messaging Strategy
meta_context
meta_audience
meta_single_persuasive_idea
meta_key_facts
meta_preroll_copy
meta_approved_additional_copy
meta_goals

// Page 3: Creative Direction
meta_scriptures
meta_emotion_energy
meta_styles_elements
meta_visual_inspiration

// Page 4: Channel Plan
meta_channel_plan (JSON array)

// Workflow
meta_acceptance_status
meta_accepted_by
meta_accepted_date
meta_is_locked
meta_pc_form_id (source form submission ID)
meta_pc_task_id (acceptance task ID)
```

### File Structure

```
wp-content/
├── themes/
│   └── divi-child/
│       ├── functions.php (hooks and filters)
│       ├── campaign-brief/
│       │   ├── post-type.php
│       │   ├── meta-boxes.php
│       │   ├── dashboard.php
│       │   ├── templates/
│       │   │   ├── brief-view.php
│       │   │   ├── brief-edit.php
│       │   │   └── dashboard.php
│       │   ├── api/
│       │   │   ├── planning-center.php
│       │   │   └── webhooks.php
│       │   └── assets/
│       │       ├── css/
│       │       └── js/
```

## Planning Center Integration

### API Credentials Setup

**Required:**
- Planning Center Personal Access Token
- Scopes needed: `people`, `calendar` (read-only)

**API Endpoints to Use:**
- Form submissions: `GET /people/v2/forms/{id}/form_submissions`
- Calendar events: `GET /calendar/v2/event_instances`
- Tasks creation: `POST /people/v2/lists/{id}/tasks`

### Form Field Mapping

**Communication Request Form → Brief Fields:**
```
Planning Center Field              → WordPress Meta Field
─────────────────────────────────────────────────────────
First name, last name, email       → meta_ministry_representative
Ministry representative email      → meta_ministry_rep_email
Which ministry?                    → meta_ministry_department
Project Title                      → meta_campaign_title
Project Due Date                   → meta_event_dates
Main purpose                       → meta_context
Goals                              → meta_goals
Scripture references               → meta_scriptures
Target audience checkboxes         → meta_audience
Communication Type checkboxes      → (helps determine service level)
Additional thoughts                → meta_key_facts
```

**Event Request Form → Brief Fields:**
```
Planning Center Field              → WordPress Meta Field
─────────────────────────────────────────────────────────
Event name                         → meta_campaign_title
Main contact                       → meta_ministry_representative
Start/end dates                    → meta_event_dates
Ministries involved                → meta_ministry_department
Description                        → meta_context
Estimated attendance               → (helps determine service level)
```

## WordPress Pages Structure

### Required Pages

**1. Communications Dashboard** (`/campaign-dashboard`)
- Only visible to communications team (Administrator/Editor roles)
- Lists all briefs with filters:
  - Status (Draft/Pending/Accepted/Archived)
  - Service Level (Green/Blue/Black)
  - Ministry
  - Date range
- Quick actions: Edit, Change Status, Archive, Duplicate
- New Brief button

**2. Brief Creation/Edit** (`/wp-admin/post.php?post_type=campaign_brief`)
- Custom admin interface
- Tabbed sections matching template pages
- Auto-save functionality
- Preview button
- Status change dropdown
- Generate shareable link button

**3. Brief View** (`/brief/[unique-id]`)
- Public-facing view
- Matches PDF template visual style
- Color-coded by service designation
- Print-friendly
- Comment section at bottom
- "Accept Brief" button for ministry leaders

**4. Brief Archive** (`/campaign-archive`)
- Searchable archive of completed campaigns
- Filters by year, ministry, service level
- Read-only access

## Implementation Phases

### Phase 1: MVP (Days 1-5)

**Day 1-2:**
- Set up WordPress child theme
- Create custom post type and meta fields
- Build basic brief creation form

**Day 3:**
- Create brief viewing template
- Implement shareable link system
- Style to match PDF templates

**Day 4:**
- Build communications dashboard
- Add status workflow
- Enable WordPress commenting

**Day 5:**
- Testing and refinements
- Documentation
- Training materials

**Deliverable:** Fully functional manual system

### Phase 2: API Integration (Week 2)

- Set up Planning Center API credentials
- Build form submission fetcher
- Create auto-population of brief fields
- Implement Planning Center Tasks creation
- Add email notifications

**Deliverable:** Semi-automated system with API polling

### Phase 3: Automation & Enhancement (Week 3-4)

- Implement webhooks (if available)
- Add file upload support
- Build brief duplication feature
- Create reporting/analytics
- Add advanced search
- Optimize mobile experience

**Deliverable:** Fully automated system

### Phase 4: Future Enhancements

- Planning Center Calendar integration (show events in dashboard)
- Automatic slug generation from title
- Channel plan templates by service level
- Asset library integration
- Multi-ministry collaboration features
- Campaign performance tracking

## Security Considerations

### Access Control

**WordPress Roles:**
- **Administrator:** Full access (IT staff)
- **Editor:** Communications team (can create/edit/publish all briefs)
- **Subscriber:** Ministry leaders (can comment on briefs)
- **Public:** Can view briefs via shared link (no account needed)

### Data Protection

- Shareable links use cryptographic nonces (expire after 30 days)
- Brief URLs are non-guessable (UUID-based)
- Form uploads stored in protected directory
- API credentials stored in wp-config.php (not in database)
- All API calls use HTTPS

### Permissions Matrix

| Action | Admin | Editor | Subscriber | Public |
|--------|-------|--------|------------|--------|
| Create brief | ✓ | ✓ | ✗ | ✗ |
| Edit brief | ✓ | ✓ | ✗ | ✗ |
| View brief | ✓ | ✓ | ✓ | ✓ (with link) |
| Comment | ✓ | ✓ | ✓ | ✓ (with name/email) |
| Change status | ✓ | ✓ | ✗ | ✗ |
| Archive | ✓ | ✓ | ✗ | ✗ |
| Accept brief | ✗ | ✗ | ✓ | ✓ (ministry leader) |

## Cost Analysis

### Development Time

- **Phase 1 (MVP):** 40-50 hours
- **Phase 2 (API):** 20-30 hours
- **Phase 3 (Automation):** 20-30 hours
- **Total:** 80-110 hours

### Ongoing Costs

- **WordPress Hosting:** Already covered (Dreamhost)
- **Planning Center API:** Included with existing subscription
- **Maintenance:** ~2-4 hours/month

### No Additional Software Costs

All components use existing infrastructure or free/open-source tools.

## Risk Mitigation

### Potential Risks & Solutions

**Risk:** Planning Center API rate limits
- **Solution:** Implement caching, use polling instead of frequent requests
- **Mitigation:** Start with manual entry in Phase 1

**Risk:** Ministry leaders forget shareable links
- **Solution:** Email notification with link on every status change
- **Mitigation:** Add "Request Link" form on archive page

**Risk:** Comments become unmanageable
- **Solution:** Email notifications to communications team
- **Mitigation:** Use Planning Center Tasks for formal acceptance

**Risk:** Brief fields too complex for WordPress admin
- **Solution:** Custom UI with clear labeling and help text
- **Mitigation:** Training documentation with screenshots

**Risk:** System too complex to maintain
- **Solution:** Comprehensive documentation and code comments
- **Mitigation:** Use WordPress best practices, avoid custom frameworks

## Success Metrics

### Key Performance Indicators

**Efficiency:**
- Time to create brief: < 10 minutes (vs. current manual process)
- Time from request to assignment: < 24 hours
- Time to ministry leader acceptance: < 3 days

**Adoption:**
- % of requests processed through system: > 95% by month 3
- Communications team satisfaction: > 4/5
- Ministry leader satisfaction: > 4/5

**Quality:**
- Briefs requiring revision: < 20%
- Missed campaigns due to process breakdown: 0

## Next Steps

1. **Approval of this proposal**
2. **Set up development environment** (WordPress staging site)
3. **Create child theme** (Divi)
4. **Begin Phase 1 development**
5. **Schedule training session** (for communications team)

## Questions for Clarification

1. **Communications Coordinator Name:** Laura Murray - is this the only coordinator, or are there others?
2. **WordPress Admin Access:** Do you have admin credentials for the WordPress site?
3. **Planning Center Access:** Do you have a developer account for API access?
4. **Dreamhost Access:** Do you need help setting up a staging environment?
5. **Current Process Pain Points:** What's the biggest frustration with the current manual process?

## Recommendation

**Start with Phase 1 (MVP) immediately.** This provides immediate value while we work on automation. The manual entry in Phase 1 is still a massive improvement over the current process because:
- Briefs are centralized and searchable
- Shareable links are automatic
- Status tracking is built-in
- Templates are standardized
- Version history is preserved

Once the team is comfortable with the system, we layer in API automation in Phases 2-3.

---

**Prepared by:** Claude Code
**Date:** 2025-11-25
**Project:** Metropolitan Bible Church Campaign Management System
