<?php
/**
 * Campaign Brief Custom Post Type
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CMS_Post_Type Class
 */
class CMS_Post_Type {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_post_status' ) );
		add_filter( 'template_include', array( $this, 'load_template' ) );
		add_filter( 'single_template', array( $this, 'single_template' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'enable_comments_on_insert' ), 10, 2 );
		add_action( 'save_post_campaign_brief', array( $this, 'ensure_comments_open' ), 10, 2 );
	}

	/**
	 * Register Campaign Brief Post Type
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Campaign Briefs', 'Post Type General Name', 'campaign-mgmt' ),
			'singular_name'         => _x( 'Campaign Brief', 'Post Type Singular Name', 'campaign-mgmt' ),
			'menu_name'             => __( 'Campaign Briefs', 'campaign-mgmt' ),
			'name_admin_bar'        => __( 'Campaign Brief', 'campaign-mgmt' ),
			'archives'              => __( 'Campaign Archives', 'campaign-mgmt' ),
			'attributes'            => __( 'Campaign Attributes', 'campaign-mgmt' ),
			'parent_item_colon'     => __( 'Parent Campaign:', 'campaign-mgmt' ),
			'all_items'             => __( 'All Campaigns', 'campaign-mgmt' ),
			'add_new_item'          => __( 'Add New Campaign Brief', 'campaign-mgmt' ),
			'add_new'               => __( 'Add New', 'campaign-mgmt' ),
			'new_item'              => __( 'New Campaign', 'campaign-mgmt' ),
			'edit_item'             => __( 'Edit Campaign Brief', 'campaign-mgmt' ),
			'update_item'           => __( 'Update Campaign', 'campaign-mgmt' ),
			'view_item'             => __( 'View Campaign Brief', 'campaign-mgmt' ),
			'view_items'            => __( 'View Campaigns', 'campaign-mgmt' ),
			'search_items'          => __( 'Search Campaigns', 'campaign-mgmt' ),
			'not_found'             => __( 'Not found', 'campaign-mgmt' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'campaign-mgmt' ),
			'featured_image'        => __( 'Campaign Image', 'campaign-mgmt' ),
			'set_featured_image'    => __( 'Set campaign image', 'campaign-mgmt' ),
			'remove_featured_image' => __( 'Remove campaign image', 'campaign-mgmt' ),
			'use_featured_image'    => __( 'Use as campaign image', 'campaign-mgmt' ),
			'insert_into_item'      => __( 'Insert into campaign', 'campaign-mgmt' ),
			'uploaded_to_this_item' => __( 'Uploaded to this campaign', 'campaign-mgmt' ),
			'items_list'            => __( 'Campaigns list', 'campaign-mgmt' ),
			'items_list_navigation' => __( 'Campaigns list navigation', 'campaign-mgmt' ),
			'filter_items_list'     => __( 'Filter campaigns list', 'campaign-mgmt' ),
		);

		$args = array(
			'label'               => __( 'Campaign Brief', 'campaign-mgmt' ),
			'description'         => __( 'Communication Campaign Briefs', 'campaign-mgmt' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'comments', 'revisions', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-megaphone',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => 'campaign-archive',
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rewrite'             => array( 'slug' => 'brief' ),
		);

		register_post_type( 'campaign_brief', $args );
	}

	/**
	 * Register Custom Taxonomies
	 */
	public function register_taxonomies() {
		// Service Designation Taxonomy (Green/Blue/Black).
		$labels = array(
			'name'              => _x( 'Service Levels', 'taxonomy general name', 'campaign-mgmt' ),
			'singular_name'     => _x( 'Service Level', 'taxonomy singular name', 'campaign-mgmt' ),
			'search_items'      => __( 'Search Service Levels', 'campaign-mgmt' ),
			'all_items'         => __( 'All Service Levels', 'campaign-mgmt' ),
			'edit_item'         => __( 'Edit Service Level', 'campaign-mgmt' ),
			'update_item'       => __( 'Update Service Level', 'campaign-mgmt' ),
			'add_new_item'      => __( 'Add New Service Level', 'campaign-mgmt' ),
			'new_item_name'     => __( 'New Service Level Name', 'campaign-mgmt' ),
			'menu_name'         => __( 'Service Levels', 'campaign-mgmt' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'service-level' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'service_level', array( 'campaign_brief' ), $args );

		// Ministry/Department Taxonomy.
		$labels = array(
			'name'              => _x( 'Ministries', 'taxonomy general name', 'campaign-mgmt' ),
			'singular_name'     => _x( 'Ministry', 'taxonomy singular name', 'campaign-mgmt' ),
			'search_items'      => __( 'Search Ministries', 'campaign-mgmt' ),
			'all_items'         => __( 'All Ministries', 'campaign-mgmt' ),
			'edit_item'         => __( 'Edit Ministry', 'campaign-mgmt' ),
			'update_item'       => __( 'Update Ministry', 'campaign-mgmt' ),
			'add_new_item'      => __( 'Add New Ministry', 'campaign-mgmt' ),
			'new_item_name'     => __( 'New Ministry Name', 'campaign-mgmt' ),
			'menu_name'         => __( 'Ministries', 'campaign-mgmt' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'ministry' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'ministry', array( 'campaign_brief' ), $args );

		// Create default service levels.
		$this->create_default_terms();
	}

	/**
	 * Create default taxonomy terms
	 */
	private function create_default_terms() {
		// Service levels.
		$service_levels = array( 'Green', 'Blue', 'Black' );
		foreach ( $service_levels as $level ) {
			if ( ! term_exists( $level, 'service_level' ) ) {
				wp_insert_term( $level, 'service_level' );
			}
		}

		// Ministries (from form).
		$ministries = array(
			'Metro Kids',
			'Metro Students',
			'Adult Family Groups & Bible Studies',
			'Men\'s Ministry',
			'Women\'s Ministry',
			'Church Leadership',
			'Son Light Preschool & Mother\'s Day Out',
			'Music & Worship',
			'Fellowship',
			'Other',
		);
		foreach ( $ministries as $ministry ) {
			if ( ! term_exists( $ministry, 'ministry' ) ) {
				wp_insert_term( $ministry, 'ministry' );
			}
		}
	}

	/**
	 * Register Custom Post Statuses
	 */
	public function register_post_status() {
		// Pending Acceptance status.
		register_post_status(
			'pending_acceptance',
			array(
				'label'                     => _x( 'Pending Acceptance', 'post status', 'campaign-mgmt' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Pending Acceptance <span class="count">(%s)</span>',
					'Pending Acceptance <span class="count">(%s)</span>',
					'campaign-mgmt'
				),
			)
		);

		// Accepted status.
		register_post_status(
			'accepted',
			array(
				'label'                     => _x( 'Accepted', 'post status', 'campaign-mgmt' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Accepted <span class="count">(%s)</span>',
					'Accepted <span class="count">(%s)</span>',
					'campaign-mgmt'
				),
			)
		);

		// Archived status.
		register_post_status(
			'archived',
			array(
				'label'                     => _x( 'Archived', 'post status', 'campaign-mgmt' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Archived <span class="count">(%s)</span>',
					'Archived <span class="count">(%s)</span>',
					'campaign-mgmt'
				),
			)
		);
	}

	/**
	 * Load custom template for campaign briefs
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function load_template( $template ) {
		if ( is_singular( 'campaign_brief' ) ) {
			$custom_template = CMS_PLUGIN_DIR . 'templates/brief-view.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		if ( is_post_type_archive( 'campaign_brief' ) ) {
			$custom_template = CMS_PLUGIN_DIR . 'templates/brief-archive.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		return $template;
	}

	/**
	 * Load single template
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function single_template( $template ) {
		global $post;

		if ( 'campaign_brief' === $post->post_type ) {
			$custom_template = CMS_PLUGIN_DIR . 'templates/brief-view.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		return $template;
	}

	/**
	 * Enable comments on new campaign briefs
	 *
	 * @param array $data Post data array.
	 * @param array $postarr Original post data.
	 * @return array Modified post data.
	 */
	public function enable_comments_on_insert( $data, $postarr ) {
		if ( 'campaign_brief' === $data['post_type'] ) {
			$data['comment_status'] = 'open';
			$data['ping_status'] = 'closed';
		}
		return $data;
	}

	/**
	 * Ensure comments are open on campaign briefs
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function ensure_comments_open( $post_id, $post ) {
		// Skip if this is an autosave or revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check if comments are closed, and open them.
		if ( 'closed' === $post->comment_status ) {
			remove_action( 'save_post_campaign_brief', array( $this, 'ensure_comments_open' ), 10 );
			wp_update_post(
				array(
					'ID'             => $post_id,
					'comment_status' => 'open',
					'ping_status'    => 'closed',
				)
			);
			add_action( 'save_post_campaign_brief', array( $this, 'ensure_comments_open' ), 10, 2 );
		}
	}
}
