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
		add_action( 'admin_footer-post.php', array( $this, 'add_quick_status_change' ) );
		add_action( 'save_post_campaign_brief', array( $this, 'check_lock_status' ), 20, 2 );
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

		// Change status back to draft.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		wp_send_json_success(
			array(
				'message' => __( 'Brief unlocked. You can now make changes.', 'campaign-mgmt' ),
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
}
