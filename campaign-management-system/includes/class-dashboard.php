<?php
/**
 * Campaign Management Dashboard
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CMS_Dashboard Class
 */
class CMS_Dashboard {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_dashboard_page' ) );
		add_filter( 'views_edit-campaign_brief', array( $this, 'add_custom_views' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_filter( 'parse_query', array( $this, 'filter_query' ) );
	}

	/**
	 * Add dashboard menu page
	 */
	public function add_dashboard_page() {
		add_submenu_page(
			'edit.php?post_type=campaign_brief',
			__( 'Campaign Dashboard', 'campaign-mgmt' ),
			__( 'Dashboard', 'campaign-mgmt' ),
			'edit_posts',
			'campaign-dashboard',
			array( $this, 'render_dashboard' )
		);
	}

	/**
	 * Render dashboard page
	 */
	public function render_dashboard() {
		// Get all briefs with different statuses.
		$draft_count = $this->get_count_by_status( 'draft' );
		$pending_count = $this->get_count_by_status( 'pending_acceptance' );
		$accepted_count = $this->get_count_by_status( 'accepted' );
		$archived_count = $this->get_count_by_status( 'archived' );

		// Get recent briefs.
		$recent_briefs = get_posts(
			array(
				'post_type'      => 'campaign_brief',
				'posts_per_page' => 10,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		// Get briefs by service level.
		$green_count = $this->get_count_by_service_level( 'Green' );
		$blue_count = $this->get_count_by_service_level( 'Blue' );
		$black_count = $this->get_count_by_service_level( 'Black' );

		// Get recent comments on campaign briefs.
		$recent_comments = $this->get_recent_comments( 10 );

		include CMS_PLUGIN_DIR . 'templates/dashboard.php';
	}

	/**
	 * Get recent comments on campaign briefs
	 *
	 * @param int $limit Number of comments to retrieve.
	 * @return array Array of comment objects.
	 */
	private function get_recent_comments( $limit = 10 ) {
		// First, get all campaign_brief post IDs
		$brief_ids = get_posts( array(
			'post_type'      => 'campaign_brief',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => array( 'publish', 'draft', 'private' ),
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

	/**
	 * Get count of briefs by workflow status
	 *
	 * @param string $status Workflow status (draft, pending_acceptance, accepted, archived).
	 * @return int
	 */
	private function get_count_by_status( $status ) {
		global $wpdb;

		// Count posts with matching workflow status meta
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type = %s
			AND p.post_status IN ('publish', 'draft', 'private')
			AND pm.meta_key = '_cms_workflow_status'
			AND pm.meta_value = %s",
			'campaign_brief',
			$status
		));

		return absint( $count );
	}

	/**
	 * Get count of briefs by service level
	 *
	 * @param string $level Service level term name.
	 * @return int
	 */
	private function get_count_by_service_level( $level ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'campaign_brief',
				'post_status'    => array( 'draft', 'pending_acceptance', 'accepted', 'publish' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'service_level',
						'field'    => 'name',
						'terms'    => $level,
					),
				),
			)
		);
		return $query->found_posts;
	}

	/**
	 * Add custom views to post list
	 *
	 * @param array $views Existing views.
	 * @return array
	 */
	public function add_custom_views( $views ) {
		// Add pending acceptance view.
		$pending_count = $this->get_count_by_status( 'pending_acceptance' );
		$current = isset( $_GET['workflow_status'] ) && 'pending_acceptance' === $_GET['workflow_status'] ? ' class="current"' : '';
		$views['pending_acceptance'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			admin_url( 'edit.php?post_type=campaign_brief&workflow_status=pending_acceptance' ),
			$current,
			__( 'Pending Acceptance', 'campaign-mgmt' ),
			$pending_count
		);

		// Add accepted view.
		$accepted_count = $this->get_count_by_status( 'accepted' );
		$current = isset( $_GET['workflow_status'] ) && 'accepted' === $_GET['workflow_status'] ? ' class="current"' : '';
		$views['accepted'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			admin_url( 'edit.php?post_type=campaign_brief&workflow_status=accepted' ),
			$current,
			__( 'Accepted', 'campaign-mgmt' ),
			$accepted_count
		);

		// Add archived view.
		$archived_count = $this->get_count_by_status( 'archived' );
		$current = isset( $_GET['workflow_status'] ) && 'archived' === $_GET['workflow_status'] ? ' class="current"' : '';
		$views['archived'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			admin_url( 'edit.php?post_type=campaign_brief&workflow_status=archived' ),
			$current,
			__( 'Archived', 'campaign-mgmt' ),
			$archived_count
		);

		// Add draft view (workflow draft, not WP draft)
		$draft_count = $this->get_count_by_status( 'draft' );
		$current = isset( $_GET['workflow_status'] ) && 'draft' === $_GET['workflow_status'] ? ' class="current"' : '';
		$views['cms_draft'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			admin_url( 'edit.php?post_type=campaign_brief&workflow_status=draft' ),
			$current,
			__( 'Brief Drafts', 'campaign-mgmt' ),
			$draft_count
		);

		return $views;
	}

	/**
	 * Add filter dropdowns to post list
	 *
	 * @param string $post_type Current post type.
	 */
	public function add_filters( $post_type ) {
		if ( 'campaign_brief' !== $post_type ) {
			return;
		}

		// Workflow status filter
		$workflow_statuses = array(
			'draft'              => __( 'Draft', 'campaign-mgmt' ),
			'pending_acceptance' => __( 'Pending Acceptance', 'campaign-mgmt' ),
			'accepted'           => __( 'Accepted', 'campaign-mgmt' ),
			'archived'           => __( 'Archived', 'campaign-mgmt' ),
		);

		$current_workflow = isset( $_GET['workflow_status'] ) ? sanitize_text_field( $_GET['workflow_status'] ) : '';
		echo '<select name="workflow_status">';
		echo '<option value="">' . esc_html__( 'All Workflow Statuses', 'campaign-mgmt' ) . '</option>';
		foreach ( $workflow_statuses as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $current_workflow, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		// Service level filter.
		$service_levels = get_terms(
			array(
				'taxonomy'   => 'service_level',
				'hide_empty' => false,
			)
		);

		if ( ! empty( $service_levels ) && ! is_wp_error( $service_levels ) ) {
			$current_level = isset( $_GET['service_level'] ) ? sanitize_text_field( $_GET['service_level'] ) : '';
			echo '<select name="service_level">';
			echo '<option value="">' . esc_html__( 'All Service Levels', 'campaign-mgmt' ) . '</option>';
			foreach ( $service_levels as $level ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $level->slug ),
					selected( $current_level, $level->slug, false ),
					esc_html( $level->name )
				);
			}
			echo '</select>';
		}

		// Ministry filter.
		$ministries = get_terms(
			array(
				'taxonomy'   => 'ministry',
				'hide_empty' => false,
			)
		);

		if ( ! empty( $ministries ) && ! is_wp_error( $ministries ) ) {
			$current_ministry = isset( $_GET['ministry'] ) ? sanitize_text_field( $_GET['ministry'] ) : '';
			echo '<select name="ministry">';
			echo '<option value="">' . esc_html__( 'All Ministries', 'campaign-mgmt' ) . '</option>';
			foreach ( $ministries as $ministry ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $ministry->slug ),
					selected( $current_ministry, $ministry->slug, false ),
					esc_html( $ministry->name )
				);
			}
			echo '</select>';
		}
	}

	/**
	 * Filter query based on custom filters
	 *
	 * @param WP_Query $query Query object.
	 */
	public function filter_query( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'campaign_brief' !== $_GET['post_type'] ) {
			return;
		}

		// Workflow status filter
		if ( isset( $_GET['workflow_status'] ) && '' !== $_GET['workflow_status'] ) {
			$meta_query = $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array();
			$meta_query[] = array(
				'key'   => '_cms_workflow_status',
				'value' => sanitize_text_field( $_GET['workflow_status'] ),
			);
			$query->set( 'meta_query', $meta_query );

			// Make sure we're not filtering by WordPress post_status when using workflow filter
			$query->set( 'post_status', array( 'publish', 'draft', 'private' ) );
		}

		// Service level filter.
		if ( isset( $_GET['service_level'] ) && '' !== $_GET['service_level'] ) {
			$tax_query = $query->get( 'tax_query' ) ? $query->get( 'tax_query' ) : array();
			$tax_query[] = array(
				'taxonomy' => 'service_level',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $_GET['service_level'] ),
			);
			$query->set( 'tax_query', $tax_query );
		}

		// Ministry filter.
		if ( isset( $_GET['ministry'] ) && '' !== $_GET['ministry'] ) {
			$tax_query = $query->get( 'tax_query' ) ? $query->get( 'tax_query' ) : array();
			$tax_query[] = array(
				'taxonomy' => 'ministry',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $_GET['ministry'] ),
			);
			$query->set( 'tax_query', $tax_query );
		}
	}
}
