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
		add_action( 'wp_ajax_cms_test_pc_connection', array( $this, 'test_pc_connection' ) );
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
			'cms_pc_token_description',
			__( 'Token Description', 'campaign-mgmt' ),
			array( $this, 'pc_token_description_callback' ),
			'cms-settings',
			'cms_api_section'
		);

		add_settings_field(
			'cms_pc_app_id',
			__( 'Application ID (from token)', 'campaign-mgmt' ),
			array( $this, 'pc_app_id_callback' ),
			'cms-settings',
			'cms_api_section'
		);

		add_settings_field(
			'cms_pc_secret',
			__( 'Secret (from token)', 'campaign-mgmt' ),
			array( $this, 'pc_secret_callback' ),
			'cms-settings',
			'cms_api_section'
		);

		register_setting( 'cms_settings_group', 'cms_pc_token_description', 'sanitize_text_field' );
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
		?>
		<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0;">
			<h3 style="margin-top: 0;"><?php esc_html_e( '⚠️ Planning Center Tasks Integration - Future Upgrade', 'campaign-mgmt' ); ?></h3>
			<p><strong><?php esc_html_e( 'This feature is currently disabled pending the release of Planning Center\'s Tasks API.', 'campaign-mgmt' ); ?></strong></p>
			<p><?php esc_html_e( 'Once Planning Center releases their Tasks API endpoint, this integration will be enabled in a future upgrade. The integration will automatically create tasks in Planning Center when campaign briefs are accepted.', 'campaign-mgmt' ); ?></p>
			<p><?php esc_html_e( 'For now, you can configure the API credentials below in preparation for when the feature becomes available.', 'campaign-mgmt' ); ?></p>
		</div>
		<div style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 15px; margin: 15px 0;">
			<h4 style="margin-top: 0;"><?php esc_html_e( 'Setup Instructions', 'campaign-mgmt' ); ?></h4>
			<ol>
				<li><?php esc_html_e( 'Go to Planning Center → Personal Settings → Developer', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Click "New Personal Access Token"', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Enter a description (e.g., "Campaign Brief System")', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Select API versions (use defaults)', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Click Submit - you will receive an Application ID and Secret', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Copy those credentials and paste below', 'campaign-mgmt' ); ?></li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Token description callback
	 */
	public function pc_token_description_callback() {
		$value = get_option( 'cms_pc_token_description', '' );
		?>
		<input type="text" name="cms_pc_token_description" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="e.g., Campaign Brief System" />
		<p class="description"><?php esc_html_e( 'Same description you used when creating the token in Planning Center', 'campaign-mgmt' ); ?></p>
		<?php
	}

	/**
	 * PC app ID callback
	 */
	public function pc_app_id_callback() {
		$value = get_option( 'cms_pc_app_id', '' );
		?>
		<input type="text" name="cms_pc_app_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="e.g., 1234567890abcdef" />
		<p class="description"><?php esc_html_e( 'Application ID from Planning Center (shown after creating Personal Access Token)', 'campaign-mgmt' ); ?></p>
		<?php
	}

	/**
	 * PC secret callback
	 */
	public function pc_secret_callback() {
		$value = get_option( 'cms_pc_secret', '' );
		?>
		<input type="password" name="cms_pc_secret" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="••••••••••••••••" />
		<p class="description"><?php esc_html_e( 'Secret from Planning Center (shown after creating Personal Access Token - keep this secure!)', 'campaign-mgmt' ); ?></p>
		<p style="margin-top: 15px;">
			<button type="button" id="cms-test-pc-connection" class="button button-secondary">
				<?php esc_html_e( 'Test Connection', 'campaign-mgmt' ); ?>
			</button>
			<span id="cms-pc-test-result" style="margin-left: 10px;"></span>
		</p>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#cms-test-pc-connection').on('click', function() {
				var button = $(this);
				var resultSpan = $('#cms-pc-test-result');

				button.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'campaign-mgmt' ); ?>');
				resultSpan.html('');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'cms_test_pc_connection',
						nonce: '<?php echo wp_create_nonce( 'cms-admin' ); ?>'
					},
					success: function(response) {
						button.prop('disabled', false).text('<?php esc_html_e( 'Test Connection', 'campaign-mgmt' ); ?>');
						if (response.success) {
							resultSpan.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
						} else {
							resultSpan.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
						}
					},
					error: function() {
						button.prop('disabled', false).text('<?php esc_html_e( 'Test Connection', 'campaign-mgmt' ); ?>');
						resultSpan.html('<span style="color: red;">✗ <?php esc_html_e( 'Connection test failed', 'campaign-mgmt' ); ?></span>');
					}
				});
			});
		});
		</script>
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

	/**
	 * Test Planning Center connection via AJAX
	 */
	public function test_pc_connection() {
		check_ajax_referer( 'cms-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'campaign-mgmt' ) ) );
		}

		require_once CMS_PLUGIN_DIR . 'includes/class-api-planning-center.php';
		$api = new CMS_API_Planning_Center();
		$result = $api->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						__( 'Connection failed: %s', 'campaign-mgmt' ),
						$result->get_error_message()
					),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Connection successful! Planning Center API is configured correctly.', 'campaign-mgmt' ),
			)
		);
	}
}
