<?php
/**
 * Planning Center API Integration
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CMS_API_Planning_Center Class
 *
 * This class handles integration with Planning Center APIs for:
 * - Fetching form submissions from People
 * - Creating tasks in Planning Center for acceptance workflow
 * - Syncing event data from Calendar
 *
 * Phase 2 implementation
 */
class CMS_API_Planning_Center {

	/**
	 * API base URL
	 *
	 * @var string
	 */
	private $api_base = 'https://api.planningcenteronline.com';

	/**
	 * Application ID
	 *
	 * @var string
	 */
	private $app_id;

	/**
	 * Secret
	 *
	 * @var string
	 */
	private $secret;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->app_id = get_option( 'cms_pc_app_id', '' );
		$this->secret = get_option( 'cms_pc_secret', '' );

		// Phase 2: Uncomment to enable.
		// add_action( 'cms_poll_planning_center', array( $this, 'poll_form_submissions' ) );
		// add_action( 'init', array( $this, 'schedule_polling' ) );
	}

	/**
	 * Test API connection
	 *
	 * @return bool|WP_Error
	 */
	public function test_connection() {
		if ( empty( $this->app_id ) || empty( $this->secret ) ) {
			return new WP_Error( 'missing_credentials', __( 'API credentials not configured', 'campaign-mgmt' ) );
		}

		$response = $this->api_request( '/people/v2/me' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Make API request to Planning Center
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $args Additional arguments.
	 * @return array|WP_Error
	 */
	private function api_request( $endpoint, $args = array() ) {
		if ( empty( $this->app_id ) || empty( $this->secret ) ) {
			return new WP_Error( 'missing_credentials', __( 'API credentials not configured', 'campaign-mgmt' ) );
		}

		$url = $this->api_base . $endpoint;

		$defaults = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->app_id . ':' . $this->secret ),
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		);

		$args = wp_parse_args( $args, $defaults );

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data ) {
			return new WP_Error( 'invalid_response', __( 'Invalid API response', 'campaign-mgmt' ) );
		}

		return $data;
	}

	/**
	 * Fetch form submissions from Planning Center People
	 *
	 * Phase 2 implementation
	 *
	 * @param string $form_id Form ID to fetch submissions from.
	 * @return array|WP_Error
	 */
	public function get_form_submissions( $form_id ) {
		$endpoint = "/people/v2/forms/{$form_id}/form_submissions";

		// Add filter for only new submissions (not yet imported).
		$endpoint .= '?filter=created_after&order=-created_at';

		return $this->api_request( $endpoint );
	}

	/**
	 * Create a brief from form submission
	 *
	 * Phase 2 implementation
	 *
	 * @param array $submission Form submission data.
	 * @return int|WP_Error Post ID or error.
	 */
	public function create_brief_from_submission( $submission ) {
		// Map form fields to brief meta fields.
		$field_map = array(
			'project_title'              => 'cms_campaign_title',
			'ministry_representative'    => 'cms_ministry_representative',
			'ministry_rep_email'         => 'cms_ministry_rep_email',
			'which_ministry'             => 'cms_ministry_department',
			'main_purpose'               => 'cms_context',
			'goals'                      => 'cms_goals',
			'scripture_references'       => 'cms_scriptures',
			'target_audience'            => 'cms_audience',
			'other_known_information'    => 'cms_key_facts',
		);

		// Create draft brief.
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'campaign_brief',
				'post_title'  => $this->get_submission_field( $submission, 'project_title' ),
				'post_status' => 'draft',
				'post_author' => 1, // Admin user.
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Save meta fields.
		foreach ( $field_map as $form_field => $meta_key ) {
			$value = $this->get_submission_field( $submission, $form_field );
			if ( ! empty( $value ) ) {
				update_post_meta( $post_id, '_' . $meta_key, $value );
			}
		}

		// Store Planning Center submission ID.
		update_post_meta( $post_id, '_cms_pc_submission_id', $submission['id'] );

		return $post_id;
	}

	/**
	 * Get field value from submission data
	 *
	 * @param array  $submission Submission data.
	 * @param string $field_key Field key.
	 * @return string
	 */
	private function get_submission_field( $submission, $field_key ) {
		// Phase 2: Implement proper field extraction from Planning Center API response.
		return '';
	}

	/**
	 * Create task in Planning Center for brief acceptance
	 *
	 * Note: Planning Center Tasks may not have a public API endpoint yet.
	 * This method attempts to create a task via available API methods.
	 *
	 * @param int    $post_id Brief post ID.
	 * @param string $assignee_email Email of person to assign task to.
	 * @return bool|WP_Error
	 */
	public function create_acceptance_task( $post_id, $assignee_email ) {
		if ( empty( $this->app_id ) || empty( $this->secret ) ) {
			$error = new WP_Error(
				'missing_credentials',
				__( 'Planning Center API credentials not configured. Task not created.', 'campaign-mgmt' )
			);
			error_log( 'CMS: ' . $error->get_error_message() );
			return $error;
		}

		$post = get_post( $post_id );
		$brief_url = get_permalink( $post_id );

		// Attempt 1: Try the Tasks API endpoint (if it exists)
		$task_data = array(
			'data' => array(
				'type'       => 'Task',
				'attributes' => array(
					'name'        => sprintf(
						__( 'Review & Accept Campaign Brief: %s', 'campaign-mgmt' ),
						$post->post_title
					),
					'description' => sprintf(
						__( "Please review and accept the campaign brief.\n\nBrief: %s\nLink: %s", 'campaign-mgmt' ),
						$post->post_title,
						$brief_url
					),
					'due_at'      => date( 'c', strtotime( '+3 days' ) ),
				),
			),
		);

		// Try Tasks API first
		$response = $this->api_post( '/tasks/v2/tasks', $task_data );

		if ( ! is_wp_error( $response ) ) {
			error_log( 'CMS: Successfully created Planning Center task via /tasks/v2/tasks' );
			return true;
		}

		// If Tasks API doesn't work, try People API
		error_log( 'CMS: Tasks API failed, trying People API: ' . $response->get_error_message() );
		$response = $this->api_post( '/people/v2/tasks', $task_data );

		if ( ! is_wp_error( $response ) ) {
			error_log( 'CMS: Successfully created Planning Center task via /people/v2/tasks' );
			return true;
		}

		// Both attempts failed - log detailed error
		$error_msg = sprintf(
			'Failed to create Planning Center task for brief "%s" (ID: %d). Error: %s. This likely means Planning Center Tasks API is not available yet. You will need to create the task manually in Planning Center.',
			$post->post_title,
			$post_id,
			$response->get_error_message()
		);
		error_log( 'CMS: ' . $error_msg );

		// Add admin notice
		update_post_meta( $post_id, '_cms_pc_task_error', $error_msg );

		return new WP_Error( 'task_creation_failed', $error_msg );
	}

	/**
	 * Make POST request to Planning Center API
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $data Data to send.
	 * @return array|WP_Error
	 */
	private function api_post( $endpoint, $data ) {
		if ( empty( $this->app_id ) || empty( $this->secret ) ) {
			return new WP_Error( 'missing_credentials', __( 'API credentials not configured', 'campaign-mgmt' ) );
		}

		$url = $this->api_base . $endpoint;

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->app_id . ':' . $this->secret ),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $data ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Log the response for debugging
		error_log( sprintf( 'CMS PC API: POST %s returned %d', $endpoint, $status_code ) );

		if ( $status_code >= 400 ) {
			$error_message = isset( $data['errors'][0]['detail'] ) ? $data['errors'][0]['detail'] : 'Unknown API error';
			return new WP_Error( 'api_error', sprintf( 'API returned %d: %s', $status_code, $error_message ) );
		}

		if ( ! $data ) {
			return new WP_Error( 'invalid_response', __( 'Invalid API response', 'campaign-mgmt' ) );
		}

		return $data;
	}

	/**
	 * Schedule polling for new form submissions
	 *
	 * Phase 2 implementation
	 */
	public function schedule_polling() {
		if ( ! wp_next_scheduled( 'cms_poll_planning_center' ) ) {
			wp_schedule_event( time(), 'hourly', 'cms_poll_planning_center' );
		}
	}

	/**
	 * Poll Planning Center for new form submissions
	 *
	 * Phase 2 implementation
	 */
	public function poll_form_submissions() {
		// Get form IDs from settings.
		$comm_form_id = get_option( 'cms_pc_comm_form_id', '' );
		$event_form_id = get_option( 'cms_pc_event_form_id', '' );

		if ( ! empty( $comm_form_id ) ) {
			$submissions = $this->get_form_submissions( $comm_form_id );
			if ( ! is_wp_error( $submissions ) ) {
				foreach ( $submissions['data'] as $submission ) {
					$this->create_brief_from_submission( $submission );
				}
			}
		}

		if ( ! empty( $event_form_id ) ) {
			$submissions = $this->get_form_submissions( $event_form_id );
			if ( ! is_wp_error( $submissions ) ) {
				foreach ( $submissions['data'] as $submission ) {
					$this->create_brief_from_submission( $submission );
				}
			}
		}
	}
}
