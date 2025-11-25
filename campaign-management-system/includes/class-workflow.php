<?php
/**
 * Campaign Brief Workflow Management
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CMS_Workflow Class
 */
class CMS_Workflow {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_cms_accept_brief', array( $this, 'accept_brief' ) );
		add_action( 'wp_ajax_nopriv_cms_accept_brief', array( $this, 'accept_brief' ) );
		add_action( 'wp_ajax_cms_unlock_brief', array( $this, 'unlock_brief' ) );
		add_action( 'wp_ajax_cms_unaccept_brief', array( $this, 'unaccept_brief' ) );
		add_action( 'wp_ajax_cms_submit_comment', array( $this, 'submit_comment' ) );
		add_action( 'wp_ajax_nopriv_cms_submit_comment', array( $this, 'submit_comment' ) );
		add_action( 'admin_footer-post.php', array( $this, 'add_quick_status_change' ) );
		add_action( 'save_post_campaign_brief', array( $this, 'check_lock_status' ), 20, 2 );
		add_filter( 'comment_form_default_fields', array( $this, 'remove_comment_website_field' ) );
		add_filter( 'comment_form_defaults', array( $this, 'customize_comment_form' ) );
		add_filter( 'pre_comment_approved', array( $this, 'auto_approve_comments' ), 10, 2 );
		add_filter( 'comment_post_redirect', array( $this, 'redirect_after_comment' ), 10, 2 );
	}

	/**
	 * Accept brief via AJAX
	 */
	public function accept_brief() {
		check_ajax_referer( 'cms-public', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$acceptor_name = isset( $_POST['acceptor_name'] ) ? sanitize_text_field( $_POST['acceptor_name'] ) : '';
		$acceptor_email = isset( $_POST['acceptor_email'] ) ? sanitize_email( $_POST['acceptor_email'] ) : '';

		if ( ! $post_id || ! $acceptor_name || ! $acceptor_email ) {
			wp_send_json_error( array( 'message' => __( 'Missing required fields', 'campaign-mgmt' ) ) );
		}

		// Update post status to accepted.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'accepted',
			)
		);

		// Update meta fields.
		update_post_meta( $post_id, '_cms_acceptance_status', 'accepted' );
		update_post_meta( $post_id, '_cms_accepted_by', $acceptor_name . ' (' . $acceptor_email . ')' );
		update_post_meta( $post_id, '_cms_accepted_date', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_cms_is_locked', 1 );

		// Send notification to communications team.
		$this->send_acceptance_notification( $post_id, $acceptor_name, $acceptor_email );

		// Try to create Planning Center task.
		$this->create_planning_center_task( $post_id, $acceptor_email );

		wp_send_json_success(
			array(
				'message' => __( 'Brief accepted successfully!', 'campaign-mgmt' ),
			)
		);
	}

	/**
	 * Unlock brief via AJAX
	 */
	public function unlock_brief() {
		check_ajax_referer( 'cms-admin', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'campaign-mgmt' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'campaign-mgmt' ) ) );
		}

		// Unlock the brief.
		update_post_meta( $post_id, '_cms_is_locked', 0 );

		// Clear acceptance metadata so it can be re-accepted after editing.
		delete_post_meta( $post_id, '_cms_acceptance_status' );
		delete_post_meta( $post_id, '_cms_accepted_by' );
		delete_post_meta( $post_id, '_cms_accepted_date' );

		// Change status back to pending_acceptance so ministry leader can re-review.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'pending_acceptance',
			)
		);

		wp_send_json_success(
			array(
				'message' => __( 'Brief unlocked and acceptance cleared. Status changed to Pending Acceptance. You can now make changes and have the brief re-accepted.', 'campaign-mgmt' ),
			)
		);
	}

	/**
	 * Manually clear acceptance status via AJAX
	 */
	public function unaccept_brief() {
		check_ajax_referer( 'cms-admin', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'campaign-mgmt' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'campaign-mgmt' ) ) );
		}

		// Clear acceptance metadata.
		delete_post_meta( $post_id, '_cms_acceptance_status' );
		delete_post_meta( $post_id, '_cms_accepted_by' );
		delete_post_meta( $post_id, '_cms_accepted_date' );

		// Change status back to pending_acceptance so it appears in the correct dashboard view.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'pending_acceptance',
			)
		);

		wp_send_json_success(
			array(
				'message' => __( 'Acceptance status cleared. Brief status changed to Pending Acceptance.', 'campaign-mgmt' ),
			)
		);
	}

	/**
	 * Send acceptance notification email
	 *
	 * @param int    $post_id Post ID.
	 * @param string $acceptor_name Name of person who accepted.
	 * @param string $acceptor_email Email of person who accepted.
	 */
	private function send_acceptance_notification( $post_id, $acceptor_name, $acceptor_email ) {
		$post = get_post( $post_id );
		$brief_url = get_permalink( $post_id );

		// Get communications coordinator email.
		$coordinator_email = get_option( 'cms_coordinator_email', get_option( 'admin_email' ) );

		$subject = sprintf(
			__( 'Campaign Brief Accepted: %s', 'campaign-mgmt' ),
			$post->post_title
		);

		$message = sprintf(
			__( 'The campaign brief "%s" has been accepted by %s (%s) on %s.', 'campaign-mgmt' ),
			$post->post_title,
			$acceptor_name,
			$acceptor_email,
			date( 'F j, Y \a\t g:i a' )
		);

		$message .= "\n\n" . sprintf( __( 'View brief: %s', 'campaign-mgmt' ), $brief_url );

		wp_mail( $coordinator_email, $subject, $message );
	}

	/**
	 * Add quick status change buttons to edit screen
	 */
	public function add_quick_status_change() {
		global $post;

		if ( ! $post || 'campaign_brief' !== $post->post_type ) {
			return;
		}

		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Add status change buttons below publish box
			var statusButtons = '<div id="cms-status-buttons" style="margin: 10px 0;">';
			statusButtons += '<p><strong><?php esc_html_e( 'Quick Status Change:', 'campaign-mgmt' ); ?></strong></p>';
			statusButtons += '<button type="button" class="button button-secondary cms-status-btn" data-status="draft"><?php esc_html_e( 'Mark as Draft', 'campaign-mgmt' ); ?></button> ';
			statusButtons += '<button type="button" class="button button-secondary cms-status-btn" data-status="pending_acceptance"><?php esc_html_e( 'Send for Acceptance', 'campaign-mgmt' ); ?></button> ';
			statusButtons += '<button type="button" class="button button-secondary cms-status-btn" data-status="archived"><?php esc_html_e( 'Archive', 'campaign-mgmt' ); ?></button>';
			statusButtons += '</div>';

			$('#submitdiv .inside').append(statusButtons);

			// Handle status button clicks
			$('.cms-status-btn').on('click', function() {
				var status = $(this).data('status');
				$('#post_status').val(status);
				$('#publish').click();
			});
		});
		</script>
		<?php
	}

	/**
	 * Check if brief should be locked after save
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function check_lock_status( $post_id, $post ) {
		// If status is accepted and not already locked, lock it.
		if ( 'accepted' === $post->post_status ) {
			$is_locked = get_post_meta( $post_id, '_cms_is_locked', true );
			if ( ! $is_locked ) {
				update_post_meta( $post_id, '_cms_is_locked', 1 );
			}
		}

		// If brief was locked and now edited, unlock it and change status.
		$is_locked = get_post_meta( $post_id, '_cms_is_locked', true );
		if ( $is_locked && 'accepted' !== $post->post_status ) {
			// Brief was edited after being locked, so it needs re-acceptance.
			if ( isset( $_POST['cms_meta_box_nonce'] ) ) {
				// This is an edit from the admin, not just a status change.
				update_post_meta( $post_id, '_cms_is_locked', 0 );
			}
		}
	}

	/**
	 * Remove website field from comment form
	 *
	 * @param array $fields Comment form fields.
	 * @return array Modified fields.
	 */
	public function remove_comment_website_field( $fields ) {
		if ( is_singular( 'campaign_brief' ) ) {
			unset( $fields['url'] );
		}
		return $fields;
	}

	/**
	 * Customize comment form for campaign briefs
	 *
	 * @param array $defaults Comment form defaults.
	 * @return array Modified defaults.
	 */
	public function customize_comment_form( $defaults ) {
		if ( is_singular( 'campaign_brief' ) ) {
			$defaults['title_reply'] = __( 'Leave Your Feedback', 'campaign-mgmt' );
			$defaults['comment_notes_before'] = '<p class="comment-notes">' . __( 'Your email address will not be published.', 'campaign-mgmt' ) . '</p>';
			$defaults['label_submit'] = __( 'Submit Comment', 'campaign-mgmt' );
		}
		return $defaults;
	}

	/**
	 * Auto-approve comments on campaign briefs
	 *
	 * @param int|string|WP_Error $approved The approval status.
	 * @param array               $commentdata Comment data.
	 * @return int|string|WP_Error Modified approval status.
	 */
	public function auto_approve_comments( $approved, $commentdata ) {
		if ( isset( $commentdata['comment_post_ID'] ) ) {
			$post = get_post( $commentdata['comment_post_ID'] );
			if ( $post && 'campaign_brief' === $post->post_type ) {
				// Auto-approve comments on campaign briefs
				return 1;
			}
		}
		return $approved;
	}

	/**
	 * Redirect after comment is posted
	 *
	 * @param string     $location The redirect location.
	 * @param WP_Comment $comment The comment object.
	 * @return string Modified redirect location.
	 */
	public function redirect_after_comment( $location, $comment ) {
		$post = get_post( $comment->comment_post_ID );
		if ( $post && 'campaign_brief' === $post->post_type ) {
			// Redirect to the brief with a success message
			$location = add_query_arg( 'comment_success', '1', get_permalink( $post->ID ) . '#comments' );
		}
		return $location;
	}

	/**
	 * Create Planning Center task for accepted brief
	 *
	 * NOTE: Planning Center Tasks API integration is disabled pending API release.
	 * This feature will be available in a future upgrade once Planning Center
	 * releases their Tasks API endpoint.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $assignee_email Email of person to assign task to.
	 */
	private function create_planning_center_task( $post_id, $assignee_email ) {
		// Planning Center Tasks API integration is currently disabled.
		// This feature is planned for a future upgrade when the API is available.
		error_log( 'CMS: Planning Center task creation disabled - awaiting API release. This will be a future upgrade.' );
		update_post_meta( $post_id, '_cms_pc_task_status', 'pending_api_release' );
		return;
	}

	/**
	 * Submit comment via AJAX
	 */
	public function submit_comment() {
		// Verify nonce.
		check_ajax_referer( 'cms_submit_comment', 'nonce' );

		// Sanitize and validate input.
		$post_id = isset( $_POST['comment_post_ID'] ) ? absint( $_POST['comment_post_ID'] ) : 0;
		$author = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$content = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '';

		// Validate required fields.
		if ( ! $post_id || ! $author || ! $email || ! $content ) {
			wp_send_json_error( array( 'message' => __( 'All fields are required.', 'campaign-mgmt' ) ) );
		}

		// Validate email.
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'campaign-mgmt' ) ) );
		}

		// Check if post exists and is a campaign brief.
		$post = get_post( $post_id );
		if ( ! $post || 'campaign_brief' !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post.', 'campaign-mgmt' ) ) );
		}

		// Check if comments are open.
		if ( ! comments_open( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Comments are closed for this brief.', 'campaign-mgmt' ) ) );
		}

		// Prepare comment data.
		$comment_data = array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $author,
			'comment_author_email' => $email,
			'comment_content'      => $content,
			'comment_type'         => 'comment',
			'comment_parent'       => 0,
			'user_id'              => get_current_user_id(),
			'comment_author_IP'    => $_SERVER['REMOTE_ADDR'],
			'comment_agent'        => $_SERVER['HTTP_USER_AGENT'],
			'comment_date'         => current_time( 'mysql' ),
			'comment_approved'     => 1, // Auto-approve for campaign briefs.
		);

		// Insert comment.
		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id || is_wp_error( $comment_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to submit comment. Please try again.', 'campaign-mgmt' ) ) );
		}

		// Send email notification about the new comment.
		$this->send_comment_notification( $post_id, $comment_id, $author, $email, $content );

		// Send success response.
		wp_send_json_success(
			array(
				'message'    => __( 'Comment submitted successfully!', 'campaign-mgmt' ),
				'comment_id' => $comment_id,
			)
		);
	}

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
}
