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
