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
				'post_status'    => array( 'draft', 'pending_acceptance', 'accepted', 'publish' ),
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

	/**
	 * Get count of briefs by status
	 *
	 * @param string $status Post status.
	 * @return int
	 */
	private function get_count_by_status( $status ) {
		// Use wp_count_posts() for more reliable counting of custom post statuses
		$counts = wp_count_posts( 'campaign_brief' );
		return isset( $counts->$status ) ? absint( $counts->$status ) : 0;
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
		if ( $pending_count > 0 ) {
			$views['pending_acceptance'] = sprintf(
				'<a href="%s">%s <span class="count">(%d)</span></a>',
				admin_url( 'edit.php?post_type=campaign_brief&post_status=pending_acceptance' ),
				__( 'Pending Acceptance', 'campaign-mgmt' ),
				$pending_count
			);
		}

		// Add accepted view.
		$accepted_count = $this->get_count_by_status( 'accepted' );
		if ( $accepted_count > 0 ) {
			$views['accepted'] = sprintf(
				'<a href="%s">%s <span class="count">(%d)</span></a>',
				admin_url( 'edit.php?post_type=campaign_brief&post_status=accepted' ),
				__( 'Accepted', 'campaign-mgmt' ),
				$accepted_count
			);
		}

		// Add archived view.
		$archived_count = $this->get_count_by_status( 'archived' );
		if ( $archived_count > 0 ) {
			$views['archived'] = sprintf(
				'<a href="%s">%s <span class="count">(%d)</span></a>',
				admin_url( 'edit.php?post_type=campaign_brief&post_status=archived' ),
				__( 'Archived', 'campaign-mgmt' ),
				$archived_count
			);
		}

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

		// Service level filter.
		$service_levels = get_terms(
			array(
				'taxonomy'   => 'service_level',
				'hide_empty' => false,
			)
		);

		if ( ! empty( $service_levels ) ) {
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

		if ( ! empty( $ministries ) ) {
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

		// Service level filter.
		if ( isset( $_GET['service_level'] ) && '' !== $_GET['service_level'] ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => 'service_level',
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $_GET['service_level'] ),
					),
				)
			);
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
