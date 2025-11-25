<?php
/**
 * Plugin Name: Campaign Management System
 * Plugin URI: https://github.com/morganhighley/pcocommunicationrequest
 * Description: Manages communication campaign requests from Planning Center with Green/Blue/Black service levels
 * Version: 1.0.0
 * Author: Metropolitan Bible Church
 * Author URI: https://metropolitanbible.church
 * License: Proprietary
 * Text Domain: campaign-mgmt
 * Domain Path: /languages
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )) {
	exit;
}

// Define plugin constants.
define( 'CMS_VERSION', '1.0.0' );
define( 'CMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Campaign Management System Class
 */
class Campaign_Management_System {

	/**
	 * The single instance of the class
	 *
	 * @var Campaign_Management_System
	 */
	protected static $instance = null;

	/**
	 * Main Campaign_Management_System Instance
	 *
	 * Ensures only one instance of Campaign_Management_System is loaded or can be loaded.
	 *
	 * @return Campaign_Management_System - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Campaign_Management_System Constructor
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files
	 */
	private function includes() {
		require_once CMS_PLUGIN_DIR . 'includes/class-post-type.php';
		require_once CMS_PLUGIN_DIR . 'includes/class-meta-boxes.php';
		require_once CMS_PLUGIN_DIR . 'includes/class-dashboard.php';
		require_once CMS_PLUGIN_DIR . 'includes/class-workflow.php';
		require_once CMS_PLUGIN_DIR . 'includes/class-settings.php';
		require_once CMS_PLUGIN_DIR . 'includes/class-api-planning-center.php';
	}

	/**
	 * Hook into actions and filters
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'public_scripts' ) );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Activation and deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize the plugin
	 */
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

		// Initialize Planning Center API (Phase 2).
		// new CMS_API_Planning_Center();
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_scripts( $hook ) {
		global $post_type;

		// Only load on our post type pages.
		if ( 'campaign_brief' === $post_type || 'campaign_brief_page_campaign-dashboard' === $hook ) {
			wp_enqueue_style(
				'cms-admin',
				CMS_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				CMS_VERSION
			);

			wp_enqueue_script(
				'cms-admin',
				CMS_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				CMS_VERSION,
				true
			);

			wp_localize_script(
				'cms-admin',
				'cmsAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'cms-admin' ),
				)
			);
		}
	}

	/**
	 * Enqueue public scripts and styles
	 */
	public function public_scripts() {
		// Only load on brief view pages.
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

			wp_localize_script(
				'cms-public',
				'cmsPublic',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'cms-public' ),
				)
			);
		}
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'campaign-mgmt',
			false,
			dirname( CMS_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Trigger init to register post type.
		$this->init();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Set default options.
		add_option( 'cms_version', CMS_VERSION );
		add_option( 'cms_installed_date', current_time( 'mysql' ) );
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

/**
 * Main instance of Campaign_Management_System
 *
 * Returns the main instance of CMS to prevent the need to use globals.
 *
 * @return Campaign_Management_System
 */
function CMS() {
	return Campaign_Management_System::instance();
}

// Initialize the plugin.
CMS();
