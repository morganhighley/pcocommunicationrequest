# Claude Code: Custom Messaging System for Campaign Briefs

## Overview

Replace the broken WordPress comments system with a completely custom messaging solution:
- **Custom database table** (`wp_cms_messages`) for storing messages
- **JavaScript-rendered chat UI** with inline styles (theme-proof)
- **Dedicated AJAX endpoints** for posting/retrieving messages
- **Admin dashboard integration** showing recent messages
- **Email notifications** to communications coordinator
- **Edit page meta box** showing messages for each brief

---

## Files to Create/Modify

### New Files:
1. `includes/class-messages.php` - Message handling class (DB, AJAX, emails)

### Modify:
2. `campaign-management-system.php` - Include new class, add activation hook for table creation
3. `templates/brief-view.php` - Add container div for JS-rendered chat
4. `assets/js/public.js` - Complete chat UI rendering and functionality
5. `includes/class-dashboard.php` - Update to show messages from custom table
6. `includes/class-meta-boxes.php` - Add messages meta box to edit screen

### Remove/Ignore:
- `templates/comments.php` - No longer needed (can delete or ignore)

---

## 1. Create `includes/class-messages.php`

Create this new file:

```php
<?php
/**
 * Campaign Brief Messaging System
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CMS_Messages Class
 * 
 * Handles custom messaging system with dedicated database table
 */
class CMS_Messages {

    /**
     * Database table name (without prefix)
     */
    const TABLE_NAME = 'cms_messages';

    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers for logged-out and logged-in users
        add_action( 'wp_ajax_cms_post_message', array( $this, 'ajax_post_message' ) );
        add_action( 'wp_ajax_nopriv_cms_post_message', array( $this, 'ajax_post_message' ) );
        add_action( 'wp_ajax_cms_get_messages', array( $this, 'ajax_get_messages' ) );
        add_action( 'wp_ajax_nopriv_cms_get_messages', array( $this, 'ajax_get_messages' ) );
        add_action( 'wp_ajax_cms_mark_messages_read', array( $this, 'ajax_mark_messages_read' ) );
    }

    /**
     * Get the full table name with prefix
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Create the messages database table
     * Called on plugin activation
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            brief_id bigint(20) unsigned NOT NULL,
            author_name varchar(255) NOT NULL,
            author_email varchar(255) NOT NULL,
            message longtext NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            is_internal tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY brief_id (brief_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Store the database version
        update_option( 'cms_messages_db_version', '1.0' );
    }

    /**
     * AJAX handler: Post a new message
     */
    public function ajax_post_message() {
        // Verify nonce
        if ( ! check_ajax_referer( 'cms_messages_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed. Please refresh the page and try again.' ) );
        }

        // Get and sanitize input
        $brief_id = isset( $_POST['brief_id'] ) ? absint( $_POST['brief_id'] ) : 0;
        $author_name = isset( $_POST['author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) ) : '';
        $author_email = isset( $_POST['author_email'] ) ? sanitize_email( wp_unslash( $_POST['author_email'] ) ) : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        // Validate required fields
        if ( ! $brief_id ) {
            wp_send_json_error( array( 'message' => 'Invalid brief.' ) );
        }

        if ( empty( $author_name ) ) {
            wp_send_json_error( array( 'message' => 'Please enter your name.' ) );
        }

        if ( empty( $author_email ) || ! is_email( $author_email ) ) {
            wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ) );
        }

        if ( empty( $message ) ) {
            wp_send_json_error( array( 'message' => 'Please enter a message.' ) );
        }

        // Verify the brief exists and is a campaign_brief
        $brief = get_post( $brief_id );
        if ( ! $brief || 'campaign_brief' !== $brief->post_type ) {
            wp_send_json_error( array( 'message' => 'Brief not found.' ) );
        }

        // Insert the message
        $message_id = $this->insert_message( $brief_id, $author_name, $author_email, $message );

        if ( ! $message_id ) {
            wp_send_json_error( array( 'message' => 'Failed to save message. Please try again.' ) );
        }

        // Send email notification
        $this->send_notification_email( $brief_id, $author_name, $author_email, $message );

        // Return success with the new message data
        wp_send_json_success( array(
            'message' => 'Message sent successfully!',
            'data' => array(
                'id' => $message_id,
                'author_name' => $author_name,
                'author_email' => $author_email,
                'message' => nl2br( esc_html( $message ) ),
                'created_at' => current_time( 'mysql' ),
                'time_ago' => 'Just now',
            ),
        ) );
    }

    /**
     * AJAX handler: Get messages for a brief
     */
    public function ajax_get_messages() {
        // Verify nonce
        if ( ! check_ajax_referer( 'cms_messages_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $brief_id = isset( $_POST['brief_id'] ) ? absint( $_POST['brief_id'] ) : 0;

        if ( ! $brief_id ) {
            wp_send_json_error( array( 'message' => 'Invalid brief.' ) );
        }

        $messages = $this->get_messages_for_brief( $brief_id );
        
        // Format messages for display
        $formatted = array();
        foreach ( $messages as $msg ) {
            $formatted[] = array(
                'id' => $msg->id,
                'author_name' => esc_html( $msg->author_name ),
                'author_email' => $msg->author_email,
                'message' => nl2br( esc_html( $msg->message ) ),
                'created_at' => $msg->created_at,
                'time_ago' => human_time_diff( strtotime( $msg->created_at ), current_time( 'timestamp' ) ) . ' ago',
                'is_internal' => (bool) $msg->is_internal,
            );
        }

        wp_send_json_success( array( 'messages' => $formatted ) );
    }

    /**
     * AJAX handler: Mark messages as read (admin only)
     */
    public function ajax_mark_messages_read() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        check_ajax_referer( 'cms-admin', 'nonce' );

        $brief_id = isset( $_POST['brief_id'] ) ? absint( $_POST['brief_id'] ) : 0;

        if ( $brief_id ) {
            $this->mark_brief_messages_read( $brief_id );
        }

        wp_send_json_success();
    }

    /**
     * Insert a new message
     *
     * @param int    $brief_id     Brief post ID.
     * @param string $author_name  Author's name.
     * @param string $author_email Author's email.
     * @param string $message      Message content.
     * @param bool   $is_internal  Whether this is an internal note.
     * @return int|false Message ID on success, false on failure.
     */
    public function insert_message( $brief_id, $author_name, $author_email, $message, $is_internal = false ) {
        global $wpdb;

        $result = $wpdb->insert(
            self::get_table_name(),
            array(
                'brief_id'     => $brief_id,
                'author_name'  => $author_name,
                'author_email' => $author_email,
                'message'      => $message,
                'is_internal'  => $is_internal ? 1 : 0,
                'created_at'   => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%d', '%s' )
        );

        if ( $result ) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get messages for a specific brief
     *
     * @param int  $brief_id       Brief post ID.
     * @param bool $include_internal Include internal notes (admin only).
     * @return array Array of message objects.
     */
    public function get_messages_for_brief( $brief_id, $include_internal = false ) {
        global $wpdb;

        $table = self::get_table_name();
        
        if ( $include_internal || current_user_can( 'edit_posts' ) ) {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE brief_id = %d ORDER BY created_at ASC",
                $brief_id
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE brief_id = %d AND is_internal = 0 ORDER BY created_at ASC",
                $brief_id
            );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get recent messages across all briefs (for dashboard)
     *
     * @param int $limit Number of messages to retrieve.
     * @return array Array of message objects with brief info.
     */
    public function get_recent_messages( $limit = 10 ) {
        global $wpdb;

        $table = self::get_table_name();

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT m.*, p.post_title as brief_title 
             FROM {$table} m
             INNER JOIN {$wpdb->posts} p ON m.brief_id = p.ID
             WHERE p.post_type = 'campaign_brief'
             ORDER BY m.created_at DESC
             LIMIT %d",
            $limit
        ) );
    }

    /**
     * Get unread message count for a brief
     *
     * @param int $brief_id Brief post ID.
     * @return int Unread count.
     */
    public function get_unread_count( $brief_id ) {
        global $wpdb;

        $table = self::get_table_name();

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE brief_id = %d AND is_read = 0",
            $brief_id
        ) );
    }

    /**
     * Get total unread messages across all briefs
     *
     * @return int Total unread count.
     */
    public function get_total_unread_count() {
        global $wpdb;

        $table = self::get_table_name();

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE is_read = 0"
        );
    }

    /**
     * Mark all messages for a brief as read
     *
     * @param int $brief_id Brief post ID.
     */
    public function mark_brief_messages_read( $brief_id ) {
        global $wpdb;

        $wpdb->update(
            self::get_table_name(),
            array( 'is_read' => 1 ),
            array( 'brief_id' => $brief_id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    /**
     * Get message count for a brief
     *
     * @param int $brief_id Brief post ID.
     * @return int Message count.
     */
    public function get_message_count( $brief_id ) {
        global $wpdb;

        $table = self::get_table_name();

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE brief_id = %d",
            $brief_id
        ) );
    }

    /**
     * Delete all messages for a brief (called when brief is deleted)
     *
     * @param int $brief_id Brief post ID.
     */
    public function delete_brief_messages( $brief_id ) {
        global $wpdb;

        $wpdb->delete(
            self::get_table_name(),
            array( 'brief_id' => $brief_id ),
            array( '%d' )
        );
    }

    /**
     * Send email notification for new message
     *
     * @param int    $brief_id     Brief post ID.
     * @param string $author_name  Message author name.
     * @param string $author_email Message author email.
     * @param string $message      Message content.
     */
    private function send_notification_email( $brief_id, $author_name, $author_email, $message ) {
        // Check if notifications are enabled
        $notify = get_option( 'cms_notify_on_comment', true );
        if ( ! $notify ) {
            return;
        }

        // Get coordinator email
        $to = get_option( 'cms_coordinator_email' );
        if ( empty( $to ) ) {
            $to = get_option( 'admin_email' );
        }

        if ( empty( $to ) ) {
            error_log( 'CMS Messages: No recipient email configured for notifications.' );
            return;
        }

        // Get brief info
        $brief = get_post( $brief_id );
        if ( ! $brief ) {
            return;
        }

        $brief_url = get_permalink( $brief_id );
        $admin_url = admin_url( 'post.php?post=' . $brief_id . '&action=edit' );

        // Build email
        $subject = sprintf( '[Campaign Brief] New message on: %s', $brief->post_title );

        $body = "A new message was posted on a campaign brief.\n\n";
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $body .= "BRIEF: " . $brief->post_title . "\n";
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $body .= "FROM: " . $author_name . "\n";
        $body .= "EMAIL: " . $author_email . "\n";
        $body .= "DATE: " . date( 'F j, Y \a\t g:i a' ) . "\n\n";
        $body .= "MESSAGE:\n";
        $body .= "────────────────────────────────────────────────\n";
        $body .= $message . "\n";
        $body .= "────────────────────────────────────────────────\n\n";
        $body .= "LINKS:\n";
        $body .= "• View Brief: " . $brief_url . "\n";
        $body .= "• Edit Brief: " . $admin_url . "\n\n";
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $body .= "Campaign Management System\n";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $author_name . ' <' . $author_email . '>',
        );

        $sent = wp_mail( $to, $subject, $body, $headers );

        if ( ! $sent ) {
            error_log( 'CMS Messages: Failed to send notification email to ' . $to );
        }
    }
}
```

---

## 2. Update `campaign-management-system.php`

Add the new class include and activation hook. 

**Add to the `includes()` method (around line 64-70):**

```php
private function includes() {
    require_once CMS_PLUGIN_DIR . 'includes/class-post-type.php';
    require_once CMS_PLUGIN_DIR . 'includes/class-meta-boxes.php';
    require_once CMS_PLUGIN_DIR . 'includes/class-dashboard.php';
    require_once CMS_PLUGIN_DIR . 'includes/class-workflow.php';
    require_once CMS_PLUGIN_DIR . 'includes/class-settings.php';
    require_once CMS_PLUGIN_DIR . 'includes/class-api-planning-center.php';
    require_once CMS_PLUGIN_DIR . 'includes/class-messages.php'; // ADD THIS LINE
}
```

**Update the `init()` method to initialize the messages class:**

```php
public function init() {
    // Initialize post type.
    new CMS_Post_Type();

    // Initialize meta boxes.
    new CMS_Meta_Boxes();

    // Initialize dashboard.
    new CMS_Dashboard();

    // Initialize workflow.
    new CMS_Workflow();

    // Initialize settings.
    new CMS_Settings();

    // Initialize messaging system.
    new CMS_Messages(); // ADD THIS LINE
}
```

**Update the `activate()` method to create the messages table:**

```php
public function activate() {
    // Trigger init to register post type.
    $this->init();

    // Create messages database table
    CMS_Messages::create_table(); // ADD THIS LINE

    // Flush rewrite rules twice to ensure they take effect.
    flush_rewrite_rules();
    delete_option( 'rewrite_rules' );

    // Set default options.
    add_option( 'cms_version', CMS_VERSION );
    add_option( 'cms_installed_date', current_time( 'mysql' ) );
    
    // Set default notification options
    if ( get_option( 'cms_notify_on_comment' ) === false ) {
        add_option( 'cms_notify_on_comment', 1 );
    }

    // Add admin notice to manually flush permalinks.
    set_transient( 'cms_flush_permalink_notice', true, 300 );
}
```

**Update `public_scripts()` to pass the messages nonce:**

```php
public function public_scripts() {
    if ( is_singular( 'campaign_brief' ) || is_page( 'campaign-archive' ) ) {
        wp_enqueue_style(
            'cms-public',
            CMS_PLUGIN_URL . 'assets/css/public.css',
            array(),
            CMS_VERSION
        );

        wp_enqueue_script(
            'cms-public',
            CMS_PLUGIN_URL . 'assets/js/public.js',
            array( 'jquery' ),
            CMS_VERSION,
            true
        );

        // Get current post ID
        $post_id = get_the_ID();
        
        // Get existing messages for initial render
        $messages_handler = new CMS_Messages();
        $messages = $messages_handler->get_messages_for_brief( $post_id );
        
        // Format messages for JS
        $formatted_messages = array();
        foreach ( $messages as $msg ) {
            $formatted_messages[] = array(
                'id' => $msg->id,
                'author_name' => esc_html( $msg->author_name ),
                'author_email' => $msg->author_email,
                'message' => nl2br( esc_html( $msg->message ) ),
                'created_at' => $msg->created_at,
                'time_ago' => human_time_diff( strtotime( $msg->created_at ), current_time( 'timestamp' ) ) . ' ago',
            );
        }

        wp_localize_script(
            'cms-public',
            'cmsPublic',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'cms-public' ),
                'messagesNonce' => wp_create_nonce( 'cms_messages_nonce' ),
                'briefId' => $post_id,
                'messages' => $formatted_messages,
            )
        );
    }
}
```

**Add hook to delete messages when brief is deleted. Add to `init_hooks()` method:**

```php
private function init_hooks() {
    add_action( 'init', array( $this, 'init' ), 0 );
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'public_scripts' ) );
    add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
    add_action( 'admin_notices', array( $this, 'activation_notice' ) );
    
    // Delete messages when brief is deleted
    add_action( 'before_delete_post', array( $this, 'cleanup_brief_messages' ) ); // ADD THIS

    register_activation_hook( __FILE__, array( $this, 'activate' ) );
    register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
}
```

**Add the cleanup method:**

```php
/**
 * Clean up messages when a brief is deleted
 *
 * @param int $post_id Post ID being deleted.
 */
public function cleanup_brief_messages( $post_id ) {
    $post = get_post( $post_id );
    if ( $post && 'campaign_brief' === $post->post_type ) {
        $messages = new CMS_Messages();
        $messages->delete_brief_messages( $post_id );
    }
}
```

---

## 3. Update `templates/brief-view.php`

Replace the comments section (around line 333-353) with a simple container div:

```php
<!-- MESSAGES SECTION (no-print) -->
<div class="cms-comments-section no-print">
    <div id="cms-chat-container">
        <!-- Chat UI is rendered by JavaScript -->
        <noscript>
            <p style="padding: 20px; background: #f8f9fa; text-align: center;">
                <?php esc_html_e( 'JavaScript is required to view and post messages.', 'campaign-mgmt' ); ?>
            </p>
        </noscript>
    </div>
</div>
```

Remove any old comment success message code (the `$_GET['comment_success']` check).

---

## 4. Replace `assets/js/public.js`

Replace the entire file with this JavaScript that renders the complete chat UI with inline styles:

```javascript
/**
 * Campaign Brief Public JavaScript
 * Includes JS-rendered chat system with inline styles
 *
 * @package CampaignManagementSystem
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // ============================================
        // ACCEPT BRIEF MODAL
        // ============================================
        
        var modal = $('#cms-accept-modal');
        var btn = $('#cms-accept-brief-btn');
        var closeButtons = $('.cms-modal-close');

        btn.on('click', function() {
            modal.fadeIn();
        });

        closeButtons.on('click', function() {
            modal.fadeOut();
        });

        $(window).on('click', function(event) {
            if (event.target == modal[0]) {
                modal.fadeOut();
            }
        });

        $('#cms-accept-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Accepting...');

            var formData = {
                action: 'cms_accept_brief',
                nonce: cmsPublic.nonce,
                post_id: cmsPublic.briefId,
                acceptor_name: $('#acceptor_name').val(),
                acceptor_email: $('#acceptor_email').val()
            };

            $.post(cmsPublic.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    alert('An error occurred. Please try again.');
                    $submitBtn.prop('disabled', false).text(originalText);
                });
        });

        // ============================================
        // CHAT SYSTEM - JS RENDERED WITH INLINE STYLES
        // ============================================

        var ChatSystem = {
            container: null,
            messages: [],
            
            // All styles defined here - completely independent of theme
            styles: {
                wrapper: [
                    'font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    'background: #ffffff',
                    'border-radius: 12px',
                    'box-shadow: 0 2px 12px rgba(0,0,0,0.1)',
                    'overflow: hidden',
                    'margin-top: 30px'
                ].join(';'),
                
                header: [
                    'background: linear-gradient(135deg, #0073aa 0%, #005a87 100%)',
                    'color: #ffffff',
                    'padding: 20px 24px'
                ].join(';'),
                
                headerTitle: [
                    'margin: 0 0 6px 0',
                    'font-size: 20px',
                    'font-weight: 600',
                    'display: flex',
                    'align-items: center',
                    'gap: 10px'
                ].join(';'),
                
                headerSubtitle: [
                    'margin: 0',
                    'font-size: 14px',
                    'opacity: 0.9'
                ].join(';'),
                
                badge: [
                    'background: rgba(255,255,255,0.2)',
                    'padding: 2px 10px',
                    'border-radius: 12px',
                    'font-size: 14px'
                ].join(';'),
                
                messagesContainer: [
                    'max-height: 450px',
                    'overflow-y: auto',
                    'padding: 24px',
                    'background: #f8f9fa',
                    'min-height: 120px'
                ].join(';'),
                
                emptyState: [
                    'text-align: center',
                    'padding: 40px 20px',
                    'color: #6c757d'
                ].join(';'),
                
                emptyIcon: [
                    'font-size: 48px',
                    'margin-bottom: 12px',
                    'opacity: 0.5'
                ].join(';'),
                
                message: [
                    'display: flex',
                    'gap: 12px',
                    'margin-bottom: 20px'
                ].join(';'),
                
                avatar: [
                    'width: 40px',
                    'height: 40px',
                    'border-radius: 50%',
                    'background: #dee2e6',
                    'display: flex',
                    'align-items: center',
                    'justify-content: center',
                    'font-weight: 600',
                    'color: #495057',
                    'font-size: 16px',
                    'flex-shrink: 0'
                ].join(';'),
                
                bubble: [
                    'flex: 1',
                    'background: #ffffff',
                    'border-radius: 12px',
                    'padding: 12px 16px',
                    'box-shadow: 0 1px 3px rgba(0,0,0,0.08)'
                ].join(';'),
                
                meta: [
                    'display: flex',
                    'align-items: center',
                    'gap: 10px',
                    'margin-bottom: 6px',
                    'flex-wrap: wrap'
                ].join(';'),
                
                authorName: [
                    'font-weight: 600',
                    'color: #333',
                    'font-size: 14px'
                ].join(';'),
                
                time: [
                    'font-size: 12px',
                    'color: #6c757d'
                ].join(';'),
                
                content: [
                    'color: #495057',
                    'font-size: 15px',
                    'line-height: 1.5'
                ].join(';'),
                
                inputWrapper: [
                    'padding: 20px 24px',
                    'background: #ffffff',
                    'border-top: 1px solid #e9ecef'
                ].join(';'),
                
                inputRow: [
                    'display: flex',
                    'gap: 12px',
                    'margin-bottom: 12px',
                    'flex-wrap: wrap'
                ].join(';'),
                
                inputField: [
                    'flex: 1',
                    'min-width: 200px'
                ].join(';'),
                
                input: [
                    'width: 100%',
                    'padding: 12px 16px',
                    'border: 2px solid #e9ecef',
                    'border-radius: 8px',
                    'font-size: 15px',
                    'font-family: inherit',
                    'box-sizing: border-box',
                    'transition: border-color 0.2s'
                ].join(';'),
                
                textarea: [
                    'width: 100%',
                    'padding: 12px 16px',
                    'border: 2px solid #e9ecef',
                    'border-radius: 8px',
                    'font-size: 15px',
                    'font-family: inherit',
                    'box-sizing: border-box',
                    'min-height: 80px',
                    'resize: vertical',
                    'transition: border-color 0.2s'
                ].join(';'),
                
                messageRow: [
                    'display: flex',
                    'gap: 12px',
                    'align-items: flex-end'
                ].join(';'),
                
                sendBtn: [
                    'padding: 12px 28px',
                    'background: linear-gradient(135deg, #0073aa 0%, #005a87 100%)',
                    'color: #ffffff',
                    'border: none',
                    'border-radius: 8px',
                    'font-size: 15px',
                    'font-weight: 600',
                    'cursor: pointer',
                    'transition: transform 0.2s, box-shadow 0.2s',
                    'white-space: nowrap'
                ].join(';'),
                
                sendBtnHover: [
                    'transform: translateY(-1px)',
                    'box-shadow: 0 4px 12px rgba(0,115,170,0.3)'
                ].join(';'),
                
                sendBtnDisabled: [
                    'opacity: 0.6',
                    'cursor: not-allowed'
                ].join(';'),
                
                privacy: [
                    'margin: 12px 0 0 0',
                    'font-size: 12px',
                    'color: #6c757d'
                ].join(';'),
                
                response: [
                    'padding: 12px 16px',
                    'border-radius: 6px',
                    'margin-bottom: 12px',
                    'display: none'
                ].join(';'),
                
                responseSuccess: [
                    'background: #d4edda',
                    'color: #155724'
                ].join(';'),
                
                responseError: [
                    'background: #f8d7da',
                    'color: #721c24'
                ].join(';')
            },

            init: function() {
                this.container = $('#cms-chat-container');
                if (!this.container.length) return;
                
                this.messages = cmsPublic.messages || [];
                this.render();
                this.bindEvents();
            },

            render: function() {
                var self = this;
                var messageCount = this.messages.length;
                
                var html = '<div id="cms-chat-wrapper" style="' + this.styles.wrapper + '">';
                
                // Header
                html += '<div style="' + this.styles.header + '">';
                html += '<h3 style="' + this.styles.headerTitle + '">';
                html += '<span>💬</span> Feedback & Discussion';
                if (messageCount > 0) {
                    html += '<span style="' + this.styles.badge + '">' + messageCount + '</span>';
                }
                html += '</h3>';
                html += '<p style="' + this.styles.headerSubtitle + '">Discuss this campaign brief with the communications team.</p>';
                html += '</div>';
                
                // Messages
                html += '<div id="cms-chat-messages" style="' + this.styles.messagesContainer + '">';
                if (messageCount === 0) {
                    html += '<div id="cms-empty-state" style="' + this.styles.emptyState + '">';
                    html += '<div style="' + this.styles.emptyIcon + '">💭</div>';
                    html += '<p style="margin:0">No messages yet. Start the conversation!</p>';
                    html += '</div>';
                } else {
                    this.messages.forEach(function(msg) {
                        html += self.renderMessage(msg);
                    });
                }
                html += '</div>';
                
                // Response area
                html += '<div id="cms-chat-response" style="' + this.styles.response + '"></div>';
                
                // Input form
                html += '<div style="' + this.styles.inputWrapper + '">';
                html += '<form id="cms-chat-form">';
                
                // Name and email row
                html += '<div style="' + this.styles.inputRow + '">';
                html += '<div style="' + this.styles.inputField + '">';
                html += '<input type="text" id="cms-msg-name" placeholder="Your Name *" required style="' + this.styles.input + '">';
                html += '</div>';
                html += '<div style="' + this.styles.inputField + '">';
                html += '<input type="email" id="cms-msg-email" placeholder="Your Email *" required style="' + this.styles.input + '">';
                html += '</div>';
                html += '</div>';
                
                // Message row
                html += '<div style="' + this.styles.messageRow + '">';
                html += '<div style="flex:1">';
                html += '<textarea id="cms-msg-content" placeholder="Type your message..." required style="' + this.styles.textarea + '"></textarea>';
                html += '</div>';
                html += '<button type="submit" id="cms-send-btn" style="' + this.styles.sendBtn + '">Send ➤</button>';
                html += '</div>';
                
                html += '</form>';
                html += '<p style="' + this.styles.privacy + '">Your email will not be published. Messages are visible to the communications team.</p>';
                html += '</div>';
                
                html += '</div>';
                
                this.container.html(html);
                this.scrollToBottom();
            },

            renderMessage: function(msg) {
                var initial = msg.author_name.charAt(0).toUpperCase();
                
                var html = '<div class="cms-chat-msg" style="' + this.styles.message + '">';
                html += '<div style="' + this.styles.avatar + '">' + initial + '</div>';
                html += '<div style="' + this.styles.bubble + '">';
                html += '<div style="' + this.styles.meta + '">';
                html += '<span style="' + this.styles.authorName + '">' + msg.author_name + '</span>';
                html += '<span style="' + this.styles.time + '">' + msg.time_ago + '</span>';
                html += '</div>';
                html += '<div style="' + this.styles.content + '">' + msg.message + '</div>';
                html += '</div>';
                html += '</div>';
                
                return html;
            },

            bindEvents: function() {
                var self = this;
                
                // Form submission
                $(document).on('submit', '#cms-chat-form', function(e) {
                    e.preventDefault();
                    self.submitMessage();
                });
                
                // Input focus styling
                $(document).on('focus', '#cms-chat-form input, #cms-chat-form textarea', function() {
                    $(this).css('border-color', '#0073aa');
                    $(this).css('box-shadow', '0 0 0 3px rgba(0,115,170,0.15)');
                });
                
                $(document).on('blur', '#cms-chat-form input, #cms-chat-form textarea', function() {
                    $(this).css('border-color', '#e9ecef');
                    $(this).css('box-shadow', 'none');
                });
                
                // Send button hover
                $(document).on('mouseenter', '#cms-send-btn', function() {
                    if (!$(this).prop('disabled')) {
                        $(this).css('transform', 'translateY(-1px)');
                        $(this).css('box-shadow', '0 4px 12px rgba(0,115,170,0.3)');
                    }
                });
                
                $(document).on('mouseleave', '#cms-send-btn', function() {
                    $(this).css('transform', 'none');
                    $(this).css('box-shadow', 'none');
                });
            },

            submitMessage: function() {
                var self = this;
                var $btn = $('#cms-send-btn');
                var $response = $('#cms-chat-response');
                
                var name = $('#cms-msg-name').val().trim();
                var email = $('#cms-msg-email').val().trim();
                var message = $('#cms-msg-content').val().trim();
                
                if (!name || !email || !message) {
                    this.showResponse('error', 'Please fill in all fields.');
                    return;
                }
                
                // Disable button
                $btn.prop('disabled', true).text('Sending...');
                $btn.css('opacity', '0.6').css('cursor', 'not-allowed');
                
                $.ajax({
                    url: cmsPublic.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'cms_post_message',
                        nonce: cmsPublic.messagesNonce,
                        brief_id: cmsPublic.briefId,
                        author_name: name,
                        author_email: email,
                        message: message
                    },
                    success: function(response) {
                        if (response.success) {
                            // Add message to display
                            self.addMessage(response.data.data);
                            
                            // Clear message field only
                            $('#cms-msg-content').val('');
                            
                            // Show success
                            self.showResponse('success', '✓ Message sent!');
                            
                            // Update count
                            self.updateCount();
                            
                            // Hide response after delay
                            setTimeout(function() {
                                $response.fadeOut();
                            }, 3000);
                        } else {
                            self.showResponse('error', response.data.message || 'Failed to send message.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Message send error:', status, error);
                        self.showResponse('error', 'An error occurred. Please try again.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Send ➤');
                        $btn.css('opacity', '1').css('cursor', 'pointer');
                    }
                });
            },

            addMessage: function(msg) {
                // Remove empty state if present
                $('#cms-empty-state').remove();
                
                // Add message
                var html = this.renderMessage(msg);
                $('#cms-chat-messages').append(html);
                
                // Scroll to bottom
                this.scrollToBottom();
                
                // Add to array
                this.messages.push(msg);
            },

            showResponse: function(type, message) {
                var $response = $('#cms-chat-response');
                var bgColor = type === 'success' ? '#d4edda' : '#f8d7da';
                var textColor = type === 'success' ? '#155724' : '#721c24';
                
                $response.css({
                    'background': bgColor,
                    'color': textColor,
                    'display': 'block',
                    'padding': '12px 24px',
                    'margin': '0'
                }).text(message);
            },

            updateCount: function() {
                var $badge = $('#cms-chat-wrapper').find('h3 span:last');
                var count = this.messages.length;
                
                if ($badge.length && $badge.text().match(/^\d+$/)) {
                    $badge.text(count);
                } else {
                    $('#cms-chat-wrapper h3').append('<span style="' + this.styles.badge + '">' + count + '</span>');
                }
            },

            scrollToBottom: function() {
                var $container = $('#cms-chat-messages');
                if ($container.length) {
                    $container.scrollTop($container[0].scrollHeight);
                }
            }
        };

        // Initialize chat system
        if (typeof cmsPublic !== 'undefined' && cmsPublic.briefId) {
            ChatSystem.init();
        }

        // ============================================
        // COPY LINK FUNCTIONALITY
        // ============================================
        
        $('#cms-copy-link').on('click', function() {
            var $input = $('#cms-share-url');
            $input.select();
            document.execCommand('copy');
            
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.text('Copied!');
            
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        });

    });

})(jQuery);
```

---

## 5. Update `includes/class-dashboard.php`

Update the `get_recent_comments()` method to use the new messages table. Replace it with:

```php
/**
 * Get recent messages on campaign briefs
 *
 * @param int $limit Number of messages to retrieve.
 * @return array Array of message objects.
 */
private function get_recent_messages( $limit = 10 ) {
    $messages_handler = new CMS_Messages();
    return $messages_handler->get_recent_messages( $limit );
}
```

Also update the `render_dashboard()` method to call this new method:

```php
// Change this line (around line 69):
// $recent_comments = $this->get_recent_comments( 10 );
// To:
$recent_messages = $this->get_recent_messages( 10 );
```

Then update `templates/dashboard.php` to use messages instead of comments. Replace the "Recent Comments" section (around lines 144-192):

```php
<!-- Recent Messages -->
<div class="cms-dashboard-section">
    <h2><?php esc_html_e( 'Recent Messages', 'campaign-mgmt' ); ?></h2>

    <?php if ( ! empty( $recent_messages ) ) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'From', 'campaign-mgmt' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'campaign-mgmt' ); ?></th>
                    <th><?php esc_html_e( 'Brief', 'campaign-mgmt' ); ?></th>
                    <th><?php esc_html_e( 'Received', 'campaign-mgmt' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'campaign-mgmt' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $recent_messages as $msg ) : ?>
                    <tr<?php echo ! $msg->is_read ? ' style="background: #fff8e5;"' : ''; ?>>
                        <td>
                            <strong><?php echo esc_html( $msg->author_name ); ?></strong><br>
                            <small><?php echo esc_html( $msg->author_email ); ?></small>
                        </td>
                        <td>
                            <?php echo esc_html( wp_trim_words( $msg->message, 15, '...' ) ); ?>
                            <?php if ( ! $msg->is_read ) : ?>
                                <span style="background: #ffc107; color: #000; padding: 1px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">NEW</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( get_edit_post_link( $msg->brief_id ) ); ?>">
                                <?php echo esc_html( $msg->brief_title ); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html( human_time_diff( strtotime( $msg->created_at ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( get_permalink( $msg->brief_id ) ); ?>" class="button button-small" target="_blank">
                                <?php esc_html_e( 'View', 'campaign-mgmt' ); ?>
                            </a>
                            <a href="<?php echo esc_url( get_edit_post_link( $msg->brief_id ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Edit Brief', 'campaign-mgmt' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php esc_html_e( 'No messages yet.', 'campaign-mgmt' ); ?></p>
    <?php endif; ?>
</div>
```

---

## 6. Update `includes/class-meta-boxes.php`

Add a messages meta box to the edit screen. In `add_meta_boxes()` method, add:

```php
// Brief Messages
add_meta_box(
    'cms_brief_messages',
    __( 'Messages & Feedback', 'campaign-mgmt' ),
    array( $this, 'render_messages_meta_box' ),
    'campaign_brief',
    'normal',
    'default'
);
```

Add the render method:

```php
/**
 * Render messages meta box
 *
 * @param WP_Post $post Current post object.
 */
public function render_messages_meta_box( $post ) {
    $messages_handler = new CMS_Messages();
    $messages = $messages_handler->get_messages_for_brief( $post->ID, true );
    $unread_count = $messages_handler->get_unread_count( $post->ID );
    
    // Mark as read when viewing in admin
    if ( $unread_count > 0 ) {
        $messages_handler->mark_brief_messages_read( $post->ID );
    }
    ?>
    <div class="cms-messages-metabox">
        <?php if ( empty( $messages ) ) : ?>
            <p style="color: #666; font-style: italic;">
                <?php esc_html_e( 'No messages yet.', 'campaign-mgmt' ); ?>
            </p>
        <?php else : ?>
            <p>
                <strong><?php printf( _n( '%d Message', '%d Messages', count( $messages ), 'campaign-mgmt' ), count( $messages ) ); ?></strong>
                <?php if ( $unread_count > 0 ) : ?>
                    <span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 10px;">
                        <?php printf( _n( '%d new', '%d new', $unread_count, 'campaign-mgmt' ), $unread_count ); ?>
                    </span>
                <?php endif; ?>
            </p>
            
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                <?php foreach ( $messages as $msg ) : ?>
                    <div style="padding: 15px; border-bottom: 1px solid #e5e5e5; background: #fff;">
                        <div style="margin-bottom: 8px;">
                            <strong style="color: #333;"><?php echo esc_html( $msg->author_name ); ?></strong>
                            <span style="color: #666; margin-left: 8px;"><?php echo esc_html( $msg->author_email ); ?></span>
                            <span style="color: #999; float: right; font-size: 12px;">
                                <?php echo esc_html( date( 'M j, Y g:i a', strtotime( $msg->created_at ) ) ); ?>
                            </span>
                        </div>
                        <div style="color: #444; line-height: 1.5;">
                            <?php echo wp_kses_post( wpautop( esc_html( $msg->message ) ) ); ?>
                        </div>
                        <?php if ( $msg->is_internal ) : ?>
                            <span style="background: #e0e0e0; color: #666; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-top: 8px; display: inline-block;">
                                <?php esc_html_e( 'Internal Note', 'campaign-mgmt' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 15px;">
            <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" class="button button-secondary" target="_blank">
                <?php esc_html_e( 'View Brief & Reply', 'campaign-mgmt' ); ?>
            </a>
        </p>
    </div>
    <?php
}
```

---

## 7. Manual Database Table Creation (Fallback)

If the table isn't created on activation, you can manually run this SQL in phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS `wp_cms_messages` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `brief_id` bigint(20) unsigned NOT NULL,
    `author_name` varchar(255) NOT NULL,
    `author_email` varchar(255) NOT NULL,
    `message` longtext NOT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `is_internal` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `brief_id` (`brief_id`),
    KEY `is_read` (`is_read`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

(Replace `wp_` with your actual table prefix if different)

---

## Testing Checklist

1. **Database Table:**
   - Deactivate and reactivate the plugin
   - Check that `wp_cms_messages` table exists in the database
   - If not, run the SQL manually

2. **Chat UI:**
   - View a campaign brief on the front-end
   - Chat interface should render with proper styling
   - All fields should be full-width and properly sized
   - Should look correct on mobile (fields stack)

3. **Posting Messages:**
   - Fill in name, email, message
   - Click Send
   - Message should appear immediately in the chat
   - Form should clear (message field only)
   - Success message should appear

4. **Email Notifications:**
   - Configure coordinator email in Settings → Campaign Briefs
   - Post a message
   - Email should be received

5. **Admin Dashboard:**
   - Go to Campaign Briefs → Dashboard
   - Recent Messages section should show new messages
   - Unread messages should be highlighted

6. **Edit Screen:**
   - Edit a brief
   - Messages & Feedback meta box should show all messages
   - Messages should be marked as read after viewing

7. **Data Persistence:**
   - Refresh the page - messages should still be there
   - Check database table for entries

---

## Debug Tips

**If messages don't post:**
1. Open browser Developer Tools → Network tab
2. Submit a message and look for the AJAX request
3. Check the response for errors
4. Check browser Console for JavaScript errors

**If table doesn't create:**
1. Check WordPress error log
2. Try running the SQL manually in phpMyAdmin
3. Check that the user has CREATE TABLE privileges

**If emails don't send:**
1. Check Settings → Campaign Briefs for coordinator email
2. Check WordPress error log for "CMS Messages" entries
3. Test if WordPress can send any emails (password reset)
