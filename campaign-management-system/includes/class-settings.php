<?php
/**
 * Plugin Settings
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CMS_Settings Class
 */
class CMS_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Campaign Brief Settings', 'campaign-mgmt' ),
			__( 'Campaign Briefs', 'campaign-mgmt' ),
			'manage_options',
			'cms-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		// General settings section.
		add_settings_section(
			'cms_general_section',
			__( 'General Settings', 'campaign-mgmt' ),
			array( $this, 'general_section_callback' ),
			'cms-settings'
		);

		add_settings_field(
			'cms_coordinator_email',
			__( 'Communications Coordinator Email', 'campaign-mgmt' ),
			array( $this, 'coordinator_email_callback' ),
			'cms-settings',
			'cms_general_section'
		);

		register_setting( 'cms_settings_group', 'cms_coordinator_email', 'sanitize_email' );

		// Planning Center API section (Phase 2).
		add_settings_section(
			'cms_api_section',
			__( 'Planning Center API (Phase 2)', 'campaign-mgmt' ),
			array( $this, 'api_section_callback' ),
			'cms-settings'
		);

		add_settings_field(
			'cms_pc_app_id',
			__( 'Application ID', 'campaign-mgmt' ),
			array( $this, 'pc_app_id_callback' ),
			'cms-settings',
			'cms_api_section'
		);

		add_settings_field(
			'cms_pc_secret',
			__( 'Secret', 'campaign-mgmt' ),
			array( $this, 'pc_secret_callback' ),
			'cms-settings',
			'cms_api_section'
		);

		register_setting( 'cms_settings_group', 'cms_pc_app_id', 'sanitize_text_field' );
		register_setting( 'cms_settings_group', 'cms_pc_secret', 'sanitize_text_field' );

		// Notification settings.
		add_settings_section(
			'cms_notification_section',
			__( 'Notification Settings', 'campaign-mgmt' ),
			array( $this, 'notification_section_callback' ),
			'cms-settings'
		);

		add_settings_field(
			'cms_notify_on_comment',
			__( 'Notify on New Comments', 'campaign-mgmt' ),
			array( $this, 'notify_on_comment_callback' ),
			'cms-settings',
			'cms_notification_section'
		);

		add_settings_field(
			'cms_notify_on_status_change',
			__( 'Notify on Status Changes', 'campaign-mgmt' ),
			array( $this, 'notify_on_status_change_callback' ),
			'cms-settings',
			'cms_notification_section'
		);

		register_setting( 'cms_settings_group', 'cms_notify_on_comment', 'absint' );
		register_setting( 'cms_settings_group', 'cms_notify_on_status_change', 'absint' );
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'cms_settings_group' );
				do_settings_sections( 'cms-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * General section callback
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Configure basic settings for the Campaign Management System.', 'campaign-mgmt' ) . '</p>';
	}

	/**
	 * Coordinator email callback
	 */
	public function coordinator_email_callback() {
		$value = get_option( 'cms_coordinator_email', get_option( 'admin_email' ) );
		?>
		<input type="email" name="cms_coordinator_email" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Email address for notifications (e.g., when briefs are accepted)', 'campaign-mgmt' ); ?></p>
		<?php
	}

	/**
	 * API section callback
	 */
	public function api_section_callback() {
		echo '<p>' . esc_html__( 'Connect to Planning Center to automatically import form submissions. Get your credentials from Planning Center → Personal Settings → Developer.', 'campaign-mgmt' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Note: This feature will be available in Phase 2. For now, manually create briefs from form data.', 'campaign-mgmt' ) . '</strong></p>';
	}

	/**
	 * PC app ID callback
	 */
	public function pc_app_id_callback() {
		$value = get_option( 'cms_pc_app_id', '' );
		?>
		<input type="text" name="cms_pc_app_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Your Planning Center Application ID', 'campaign-mgmt' ); ?></p>
		<?php
	}

	/**
	 * PC secret callback
	 */
	public function pc_secret_callback() {
		$value = get_option( 'cms_pc_secret', '' );
		?>
		<input type="password" name="cms_pc_secret" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Your Planning Center Secret (keep this secure!)', 'campaign-mgmt' ); ?></p>
		<?php
	}

	/**
	 * Notification section callback
	 */
	public function notification_section_callback() {
		echo '<p>' . esc_html__( 'Configure email notifications for the communications team.', 'campaign-mgmt' ) . '</p>';
	}

	/**
	 * Notify on comment callback
	 */
	public function notify_on_comment_callback() {
		$value = get_option( 'cms_notify_on_comment', 1 );
		?>
		<label>
			<input type="checkbox" name="cms_notify_on_comment" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Send email notification when someone comments on a brief', 'campaign-mgmt' ); ?>
		</label>
		<?php
	}

	/**
	 * Notify on status change callback
	 */
	public function notify_on_status_change_callback() {
		$value = get_option( 'cms_notify_on_status_change', 1 );
		?>
		<label>
			<input type="checkbox" name="cms_notify_on_status_change" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Send email notification when brief status changes', 'campaign-mgmt' ); ?>
		</label>
		<?php
	}
}
