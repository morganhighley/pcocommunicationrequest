# Claude Code: Campaign Management System Plugin Bug Fixes

## Overview
The Campaign Management System WordPress plugin has several bugs that need to be fixed. Please address each of the following issues:

---

## Issue 1: "Pending Acceptance" Count Always Shows "0"

### Problem
The dashboard shows "0" for pending acceptance briefs, even when there are submitted briefs with status `pending_acceptance` or briefs that have been unlocked and reset.

### Root Cause
In `includes/class-dashboard.php`, the `get_recent_comments()` method (lines 80-91) uses a `post_type` parameter with `get_comments()`, but WordPress's `get_comments()` function doesn't support filtering by post type directly. This parameter is ignored.

Additionally, the `get_count_by_status()` method may have issues with how WordPress handles custom post statuses in `WP_Query`.

### Files to Modify
- `includes/class-dashboard.php`

### Required Fix
1. In `get_count_by_status()` method, replace the `WP_Query` approach with a direct query that's more reliable for custom post statuses:

```php
private function get_count_by_status( $status ) {
    global $wpdb;
    
    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
        'campaign_brief',
        $status
    ));
    
    return absint( $count );
}
```

Alternatively, use `wp_count_posts()` and access the custom status dynamically:

```php
private function get_count_by_status( $status ) {
    $counts = wp_count_posts( 'campaign_brief' );
    return isset( $counts->$status ) ? absint( $counts->$status ) : 0;
}
```

---

## Issue 2: Comment Section Formatting Issues

### Problem
The comment form on the public brief view has terrible formatting:
- Labels are inline with inputs instead of stacked above them
- Input fields are too narrow/small
- Overall spacing and alignment is broken
- The layout doesn't match the professional styling intended

### Root Cause
The CSS in `assets/css/public.css` doesn't have sufficient specificity to override WordPress theme default styles. Additionally, some CSS properties may be missing or incorrect.

### Files to Modify
- `assets/css/public.css`
- `templates/comments.php`

### Required Fixes

1. **Add CSS specificity overrides** - Update `assets/css/public.css` to add more specific selectors and `!important` declarations where needed to ensure the plugin's styles take precedence over theme styles:

```css
/* Enhanced Comment Form Styles with Higher Specificity */
#cms-brief-view .cms-comments-wrapper,
.cms-brief-container .cms-comments-wrapper {
    margin-top: 30px !important;
    padding: 0 !important;
}

#cms-brief-view .cms-comment-form-wrapper,
.cms-brief-container .cms-comment-form-wrapper {
    margin-top: 40px !important;
    padding: 35px !important;
    background: #fff !important;
    border: 2px solid #0073aa !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(0, 115, 170, 0.1) !important;
}

#cms-brief-view .cms-comment-form-wrapper h3,
.cms-brief-container .cms-comment-form-wrapper h3 {
    margin-top: 0 !important;
    margin-bottom: 10px !important;
    color: #0073aa !important;
    font-size: 22px !important;
    font-weight: 700 !important;
}

#cms-brief-view .cms-form-field,
.cms-brief-container .cms-form-field {
    margin-bottom: 20px !important;
    display: block !important;
    width: 100% !important;
}

#cms-brief-view .cms-form-field label,
.cms-brief-container .cms-form-field label {
    display: block !important;
    font-weight: 600 !important;
    margin-bottom: 8px !important;
    color: #333 !important;
    font-size: 15px !important;
}

#cms-brief-view .cms-form-field input[type="text"],
#cms-brief-view .cms-form-field input[type="email"],
#cms-brief-view .cms-form-field textarea,
.cms-brief-container .cms-form-field input[type="text"],
.cms-brief-container .cms-form-field input[type="email"],
.cms-brief-container .cms-form-field textarea {
    width: 100% !important;
    max-width: 100% !important;
    padding: 14px 16px !important;
    border: 2px solid #ddd !important;
    border-radius: 6px !important;
    font-size: 15px !important;
    font-family: inherit !important;
    box-sizing: border-box !important;
    background-color: #fff !important;
    line-height: 1.5 !important;
    display: block !important;
}

#cms-brief-view .cms-form-field input[type="text"]:focus,
#cms-brief-view .cms-form-field input[type="email"]:focus,
#cms-brief-view .cms-form-field textarea:focus,
.cms-brief-container .cms-form-field input[type="text"]:focus,
.cms-brief-container .cms-form-field input[type="email"]:focus,
.cms-brief-container .cms-form-field textarea:focus {
    outline: none !important;
    border-color: #0073aa !important;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1) !important;
}

#cms-brief-view .cms-form-field textarea,
.cms-brief-container .cms-form-field textarea {
    min-height: 150px !important;
    resize: vertical !important;
}

#cms-brief-view .cms-form-actions,
.cms-brief-container .cms-form-actions {
    margin-top: 20px !important;
}

#cms-brief-view .cms-button.cms-button-primary,
.cms-brief-container .cms-button.cms-button-primary {
    display: inline-block !important;
    padding: 14px 32px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    background: #0073aa !important;
    color: #fff !important;
    border: none !important;
    border-radius: 4px !important;
    cursor: pointer !important;
}

#cms-brief-view .cms-button.cms-button-primary:hover,
.cms-brief-container .cms-button.cms-button-primary:hover {
    background: #005a87 !important;
}

/* Reset any theme form styles */
#cms-brief-view .cms-comment-form,
.cms-brief-container .cms-comment-form {
    display: block !important;
    width: 100% !important;
}

#cms-brief-view .cms-comment-form p,
.cms-brief-container .cms-comment-form p {
    margin: 0 0 20px 0 !important;
}
```

2. **Update `templates/comments.php`** - Ensure the form structure uses proper block-level elements and doesn't rely on grid that might conflict with themes. Replace the form structure around lines 56-82:

```php
<form id="cms-comment-form" class="cms-comment-form" method="post">
    <div class="cms-form-field">
        <label for="cms_comment_author"><?php esc_html_e( 'Your Name', 'campaign-mgmt' ); ?> <span class="required">*</span></label>
        <input type="text" id="cms_comment_author" name="author" required maxlength="245" />
    </div>

    <div class="cms-form-field">
        <label for="cms_comment_email"><?php esc_html_e( 'Your Email', 'campaign-mgmt' ); ?> <span class="required">*</span></label>
        <input type="email" id="cms_comment_email" name="email" required maxlength="100" />
    </div>

    <div class="cms-form-field">
        <label for="cms_comment_content"><?php esc_html_e( 'Your Comment', 'campaign-mgmt' ); ?> <span class="required">*</span></label>
        <textarea id="cms_comment_content" name="comment" rows="8" required maxlength="65525"></textarea>
    </div>

    <input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( get_the_ID() ); ?>" />
    <?php wp_nonce_field( 'cms_submit_comment', 'cms_comment_nonce' ); ?>

    <div class="cms-form-actions">
        <button type="submit" class="cms-button cms-button-primary" id="cms-submit-comment">
            <?php esc_html_e( 'Submit Comment', 'campaign-mgmt' ); ?>
        </button>
    </div>

    <div id="cms-comment-response" style="display:none;"></div>
</form>
```

Also remove the CSS grid property from `.cms-comment-form` and use standard block layout instead.

---

## Issue 3: Comments Not Showing in Dashboard or Brief Edit Page

### Problem
Submitted comments are not appearing in:
1. The plugin's dashboard "Recent Comments" section
2. The associated brief's edit page

### Root Cause
In `includes/class-dashboard.php`, the `get_recent_comments()` method (lines 80-91) uses a `post_type` parameter with `get_comments()`, but **WordPress's `get_comments()` function doesn't support filtering by post type directly**. This parameter is being ignored, which means the query might be returning comments from other post types or failing to properly filter.

### Files to Modify
- `includes/class-dashboard.php`

### Required Fix
Replace the `get_recent_comments()` method with a proper implementation that first gets campaign_brief post IDs, then retrieves comments for those posts:

```php
/**
 * Get recent comments on campaign briefs
 *
 * @param int $limit Number of comments to retrieve.
 * @return array Array of comment objects.
 */
private function get_recent_comments( $limit = 10 ) {
    global $wpdb;
    
    // Get comments that belong to campaign_brief posts
    $comments = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.* FROM {$wpdb->comments} c
        INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
        WHERE p.post_type = %s
        AND c.comment_approved = '1'
        ORDER BY c.comment_date DESC
        LIMIT %d",
        'campaign_brief',
        $limit
    ));
    
    // Convert to WP_Comment objects for compatibility
    if ( ! empty( $comments ) ) {
        $comments = array_map( function( $comment ) {
            return new WP_Comment( $comment );
        }, $comments );
    }
    
    return $comments ? $comments : array();
}
```

Alternatively, use a two-step approach with WordPress functions:

```php
private function get_recent_comments( $limit = 10 ) {
    // First, get all campaign_brief post IDs
    $brief_ids = get_posts( array(
        'post_type'      => 'campaign_brief',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post_status'    => array( 'publish', 'draft', 'pending_acceptance', 'accepted', 'archived' ),
    ));
    
    if ( empty( $brief_ids ) ) {
        return array();
    }
    
    // Then get comments for those posts
    $comments = get_comments( array(
        'post__in'  => $brief_ids,
        'status'    => 'approve',
        'number'    => $limit,
        'orderby'   => 'comment_date',
        'order'     => 'DESC',
    ));
    
    return $comments;
}
```

---

## Issue 4: Comments Not Being Emailed to Communications Coordinator

### Problem
When comments are submitted on briefs, email notifications are not being sent to the communications coordinator email address configured in the plugin settings.

### Root Causes
1. The `cms_notify_on_comment` option might default to empty/false instead of 1 on fresh installs
2. The email functionality should be verified/debugged
3. There's no logging to help diagnose email failures

### Files to Modify
- `includes/class-workflow.php`
- `includes/class-settings.php`

### Required Fixes

1. **Update `class-settings.php`** - Ensure the notification option has a proper default value. In the `notify_on_comment_callback()` method, the default should be guaranteed:

```php
public function notify_on_comment_callback() {
    // Ensure default is set if option doesn't exist
    $value = get_option( 'cms_notify_on_comment' );
    if ( $value === false ) {
        update_option( 'cms_notify_on_comment', 1 );
        $value = 1;
    }
    ?>
    <label>
        <input type="checkbox" name="cms_notify_on_comment" value="1" <?php checked( $value, 1 ); ?> />
        <?php esc_html_e( 'Send email notification when someone comments on a brief', 'campaign-mgmt' ); ?>
    </label>
    <?php
}
```

2. **Update `class-workflow.php`** - Improve the `send_comment_notification()` method with better error handling and logging:

```php
/**
 * Send notification email when a comment is posted
 *
 * @param int    $post_id Post ID.
 * @param int    $comment_id Comment ID.
 * @param string $author Comment author name.
 * @param string $author_email Comment author email.
 * @param string $content Comment content.
 */
private function send_comment_notification( $post_id, $comment_id, $author, $author_email, $content ) {
    // Get the post.
    $post = get_post( $post_id );
    if ( ! $post ) {
        error_log( 'CMS: Cannot send comment notification - post not found: ' . $post_id );
        return;
    }

    // Get coordinator email.
    $coordinator_email = get_option( 'cms_coordinator_email' );
    if ( empty( $coordinator_email ) ) {
        $coordinator_email = get_option( 'admin_email' );
    }
    
    if ( empty( $coordinator_email ) ) {
        error_log( 'CMS: Cannot send comment notification - no coordinator email configured' );
        return;
    }

    // Check if comment notifications are enabled (default to enabled if not set).
    $notify_on_comment = get_option( 'cms_notify_on_comment' );
    if ( $notify_on_comment === false ) {
        // Option not set, default to enabled
        $notify_on_comment = 1;
    }
    
    if ( ! $notify_on_comment ) {
        error_log( 'CMS: Comment notifications disabled in settings' );
        return;
    }

    // Prepare email.
    $subject = sprintf(
        __( 'New Comment on Campaign Brief: %s', 'campaign-mgmt' ),
        $post->post_title
    );

    $brief_url = get_permalink( $post_id );
    $comment_url = $brief_url . '#comment-' . $comment_id;
    $admin_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

    $message = sprintf(
        __( 'A new comment has been posted on the campaign brief "%s".', 'campaign-mgmt' ),
        $post->post_title
    ) . "\n\n";

    $message .= __( 'Author:', 'campaign-mgmt' ) . ' ' . $author . ' (' . $author_email . ')' . "\n\n";
    $message .= __( 'Comment:', 'campaign-mgmt' ) . "\n" . $content . "\n\n";
    $message .= __( 'View comment:', 'campaign-mgmt' ) . ' ' . $comment_url . "\n";
    $message .= __( 'Edit brief:', 'campaign-mgmt' ) . ' ' . $admin_url . "\n";
    $message .= __( 'View brief:', 'campaign-mgmt' ) . ' ' . $brief_url . "\n\n";
    $message .= '---' . "\n";
    $message .= sprintf( __( 'Posted on %s', 'campaign-mgmt' ), date( 'F j, Y \a\t g:i a' ) );

    // Send email with error logging.
    $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
    $sent = wp_mail( $coordinator_email, $subject, $message, $headers );
    
    if ( ! $sent ) {
        error_log( 'CMS: Failed to send comment notification email to: ' . $coordinator_email );
    } else {
        error_log( 'CMS: Comment notification sent successfully to: ' . $coordinator_email );
    }
}
```

3. **Add default options on plugin activation** - In `campaign-management-system.php`, update the `activate()` method to set default notification options:

```php
public function activate() {
    // Trigger init to register post type.
    $this->init();

    // Flush rewrite rules twice to ensure they take effect.
    flush_rewrite_rules();
    delete_option( 'rewrite_rules' );

    // Set default options.
    add_option( 'cms_version', CMS_VERSION );
    add_option( 'cms_installed_date', current_time( 'mysql' ) );
    
    // Set default notification options (only if they don't exist)
    if ( get_option( 'cms_notify_on_comment' ) === false ) {
        add_option( 'cms_notify_on_comment', 1 );
    }
    if ( get_option( 'cms_notify_on_status_change' ) === false ) {
        add_option( 'cms_notify_on_status_change', 1 );
    }

    // Add admin notice to manually flush permalinks.
    set_transient( 'cms_flush_permalink_notice', true, 300 );
}
```

---

## Issue 5: Add Comments Meta Box to Brief Edit Page (Enhancement)

### Problem
There's no way to view comments on a brief from within the WordPress admin edit screen. Comments should be visible in a meta box on the brief edit page.

### Files to Modify
- `includes/class-meta-boxes.php`

### Required Fix
Add a new meta box in the `add_meta_boxes()` method to display comments:

```php
// In add_meta_boxes() method, add:
add_meta_box(
    'cms_brief_comments',
    __( 'Brief Comments & Feedback', 'campaign-mgmt' ),
    array( $this, 'render_comments_meta_box' ),
    'campaign_brief',
    'normal',
    'default'
);
```

Then add the render method:

```php
/**
 * Render comments meta box
 *
 * @param WP_Post $post Current post object.
 */
public function render_comments_meta_box( $post ) {
    $comments = get_comments( array(
        'post_id' => $post->ID,
        'status'  => 'approve',
        'orderby' => 'comment_date',
        'order'   => 'DESC',
    ));
    
    if ( empty( $comments ) ) {
        echo '<p>' . esc_html__( 'No comments yet.', 'campaign-mgmt' ) . '</p>';
        echo '<p><a href="' . esc_url( get_permalink( $post->ID ) . '#comments' ) . '" target="_blank" class="button button-secondary">' . esc_html__( 'View Brief & Add Comment', 'campaign-mgmt' ) . '</a></p>';
        return;
    }
    
    echo '<div class="cms-admin-comments">';
    echo '<p><strong>' . sprintf( _n( '%s Comment', '%s Comments', count( $comments ), 'campaign-mgmt' ), count( $comments ) ) . '</strong></p>';
    
    foreach ( $comments as $comment ) {
        ?>
        <div class="cms-admin-comment" style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-left: 4px solid #0073aa;">
            <div class="comment-meta" style="margin-bottom: 10px;">
                <strong><?php echo esc_html( $comment->comment_author ); ?></strong>
                <span style="color: #666; margin-left: 10px;">
                    (<?php echo esc_html( $comment->comment_author_email ); ?>)
                </span>
                <br>
                <small style="color: #999;">
                    <?php echo esc_html( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) . ' ago' ); ?>
                    (<?php echo esc_html( date( 'F j, Y \a\t g:i a', strtotime( $comment->comment_date ) ) ); ?>)
                </small>
            </div>
            <div class="comment-content">
                <?php echo wp_kses_post( wpautop( $comment->comment_content ) ); ?>
            </div>
            <div class="comment-actions" style="margin-top: 10px;">
                <a href="<?php echo esc_url( admin_url( 'comment.php?action=editcomment&c=' . $comment->comment_ID ) ); ?>" class="button button-small">
                    <?php esc_html_e( 'Edit', 'campaign-mgmt' ); ?>
                </a>
                <a href="<?php echo esc_url( get_permalink( $post->ID ) . '#comment-' . $comment->comment_ID ); ?>" class="button button-small" target="_blank">
                    <?php esc_html_e( 'View on Brief', 'campaign-mgmt' ); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    echo '<p><a href="' . esc_url( get_permalink( $post->ID ) . '#comments' ) . '" target="_blank" class="button button-primary">' . esc_html__( 'View All Comments on Brief', 'campaign-mgmt' ) . '</a></p>';
    echo '</div>';
}
```

---

## Testing Checklist

After implementing these fixes, test the following:

1. **Pending Acceptance Count:**
   - Create a new campaign brief
   - Change its status to "pending_acceptance" using the quick status buttons
   - Verify the dashboard shows the correct count (should be 1 or more)
   - Unlock an accepted brief and verify it shows in pending count

2. **Comment Form Styling:**
   - View a campaign brief on the front-end
   - Verify labels appear above input fields
   - Verify input fields span full width of the form container
   - Verify textarea is appropriately sized
   - Verify the submit button is styled correctly

3. **Comments in Dashboard:**
   - Submit a comment on a campaign brief
   - Go to Campaign Briefs → Dashboard
   - Verify the comment appears in the "Recent Comments" section

4. **Comments in Edit Page:**
   - Edit a campaign brief that has comments
   - Verify the "Brief Comments & Feedback" meta box appears
   - Verify all comments are displayed with author, date, and content

5. **Email Notifications:**
   - Configure a coordinator email in Settings → Campaign Briefs
   - Enable "Notify on New Comments"
   - Submit a comment on a campaign brief
   - Verify an email is received at the coordinator email address
   - Check the WordPress debug log for any email-related error messages

---

## Summary of Files to Modify

1. `includes/class-dashboard.php` - Fix pending count and comments retrieval
2. `includes/class-workflow.php` - Improve email notification with logging
3. `includes/class-settings.php` - Ensure proper defaults for notification settings
4. `includes/class-meta-boxes.php` - Add comments meta box to edit page
5. `assets/css/public.css` - Fix comment form styling with higher specificity
6. `templates/comments.php` - Ensure proper form structure
7. `campaign-management-system.php` - Set default options on activation
