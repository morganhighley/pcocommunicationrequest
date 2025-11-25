<?php
/**
 * Dashboard Template
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Campaign Dashboard', 'campaign-mgmt' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign_brief' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New Campaign', 'campaign-mgmt' ); ?>
	</a>
	<hr class="wp-header-end">

	<!-- Stats Overview -->
	<div class="cms-dashboard-stats">
		<div class="cms-stat-card cms-stat-draft">
			<div class="cms-stat-number"><?php echo esc_html( $draft_count ); ?></div>
			<div class="cms-stat-label"><?php esc_html_e( 'Drafts', 'campaign-mgmt' ); ?></div>
		</div>

		<div class="cms-stat-card cms-stat-pending">
			<div class="cms-stat-number"><?php echo esc_html( $pending_count ); ?></div>
			<div class="cms-stat-label"><?php esc_html_e( 'Pending Acceptance', 'campaign-mgmt' ); ?></div>
		</div>

		<div class="cms-stat-card cms-stat-accepted">
			<div class="cms-stat-number"><?php echo esc_html( $accepted_count ); ?></div>
			<div class="cms-stat-label"><?php esc_html_e( 'Accepted', 'campaign-mgmt' ); ?></div>
		</div>

		<div class="cms-stat-card cms-stat-archived">
			<div class="cms-stat-number"><?php echo esc_html( $archived_count ); ?></div>
			<div class="cms-stat-label"><?php esc_html_e( 'Archived', 'campaign-mgmt' ); ?></div>
		</div>
	</div>

	<!-- Service Level Breakdown -->
	<div class="cms-dashboard-section">
		<h2><?php esc_html_e( 'By Service Level', 'campaign-mgmt' ); ?></h2>
		<div class="cms-service-level-stats">
			<div class="cms-stat-card cms-stat-green">
				<div class="cms-stat-number"><?php echo esc_html( $green_count ); ?></div>
				<div class="cms-stat-label"><?php esc_html_e( 'Green Campaigns', 'campaign-mgmt' ); ?></div>
			</div>

			<div class="cms-stat-card cms-stat-blue">
				<div class="cms-stat-number"><?php echo esc_html( $blue_count ); ?></div>
				<div class="cms-stat-label"><?php esc_html_e( 'Blue Campaigns', 'campaign-mgmt' ); ?></div>
			</div>

			<div class="cms-stat-card cms-stat-black">
				<div class="cms-stat-number"><?php echo esc_html( $black_count ); ?></div>
				<div class="cms-stat-label"><?php esc_html_e( 'Black Campaigns', 'campaign-mgmt' ); ?></div>
			</div>
		</div>
	</div>

	<!-- Recent Activity -->
	<div class="cms-dashboard-section">
		<h2><?php esc_html_e( 'Recent Activity', 'campaign-mgmt' ); ?></h2>

		<?php if ( ! empty( $recent_briefs ) ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Service Level', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Ministry', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Status', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Last Modified', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'campaign-mgmt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_briefs as $brief ) : ?>
						<?php
						$service_level_terms = get_the_terms( $brief->ID, 'service_level' );
						$service_level = $service_level_terms && ! is_wp_error( $service_level_terms ) ? $service_level_terms[0]->name : '-';

						$ministry_terms = get_the_terms( $brief->ID, 'ministry' );
						$ministry = $ministry_terms && ! is_wp_error( $ministry_terms ) ? $ministry_terms[0]->name : '-';

						$workflow_status = get_post_meta( $brief->ID, '_cms_workflow_status', true );
						if ( empty( $workflow_status ) ) {
							$workflow_status = 'draft';
						}
						$status_labels = array(
							'draft'              => __( 'Draft', 'campaign-mgmt' ),
							'pending_acceptance' => __( 'Pending Acceptance', 'campaign-mgmt' ),
							'accepted'           => __( 'Accepted', 'campaign-mgmt' ),
							'archived'           => __( 'Archived', 'campaign-mgmt' ),
						);
						$status = isset( $status_labels[ $workflow_status ] ) ? $status_labels[ $workflow_status ] : ucfirst( $workflow_status );
						?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo esc_url( get_edit_post_link( $brief->ID ) ); ?>">
										<?php echo esc_html( $brief->post_title ); ?>
									</a>
								</strong>
							</td>
							<td>
								<span class="cms-badge cms-badge-<?php echo esc_attr( strtolower( $service_level ) ); ?>">
									<?php echo esc_html( $service_level ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $ministry ); ?></td>
							<td><?php echo esc_html( $status ); ?></td>
							<td><?php echo esc_html( human_time_diff( strtotime( $brief->post_modified ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $brief->ID ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'campaign-mgmt' ); ?>
								</a>
								<a href="<?php echo esc_url( get_permalink( $brief->ID ) ); ?>" class="button button-small" target="_blank">
									<?php esc_html_e( 'View', 'campaign-mgmt' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=campaign_brief' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'View All Campaigns', 'campaign-mgmt' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p><?php esc_html_e( 'No campaign briefs yet.', 'campaign-mgmt' ); ?></p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign_brief' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Create Your First Campaign', 'campaign-mgmt' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>

	<!-- Recent Messages -->
	<div class="cms-dashboard-section">
		<h2><?php esc_html_e( 'Recent Messages', 'campaign-mgmt' ); ?></h2>

		<?php if ( ! empty( $recent_messages ) ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'From', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Message', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Brief', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Received', 'campaign-mgmt' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'campaign-mgmt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_messages as $msg ) : ?>
						<tr<?php echo ! $msg->is_read ? ' style="background: #fff8e5;"' : ''; ?>>
							<td>
								<strong><?php echo esc_html( $msg->author_name ); ?></strong><br>
								<small><?php echo esc_html( $msg->author_email ); ?></small>
							</td>
							<td>
								<?php echo esc_html( wp_trim_words( $msg->message, 15, '...' ) ); ?>
								<?php if ( ! $msg->is_read ) : ?>
									<span style="background: #ffc107; color: #000; padding: 1px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">NEW</span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $msg->brief_id ) ); ?>">
									<?php echo esc_html( $msg->brief_title ); ?>
								</a>
							</td>
							<td><?php echo esc_html( human_time_diff( strtotime( $msg->created_at ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_permalink( $msg->brief_id ) ); ?>" class="button button-small" target="_blank">
									<?php esc_html_e( 'View', 'campaign-mgmt' ); ?>
								</a>
								<a href="<?php echo esc_url( get_edit_post_link( $msg->brief_id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit Brief', 'campaign-mgmt' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No messages yet.', 'campaign-mgmt' ); ?></p>
		<?php endif; ?>
	</div>

	<!-- Quick Links -->
	<div class="cms-dashboard-section">
		<h2><?php esc_html_e( 'Quick Links', 'campaign-mgmt' ); ?></h2>
		<ul>
			<li>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=campaign_brief&workflow_status=pending_acceptance' ) ); ?>">
					<?php esc_html_e( 'Briefs Pending Acceptance', 'campaign-mgmt' ); ?>
					(<?php echo esc_html( $pending_count ); ?>)
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=campaign_brief&workflow_status=accepted' ) ); ?>">
					<?php esc_html_e( 'Accepted Briefs', 'campaign-mgmt' ); ?>
					(<?php echo esc_html( $accepted_count ); ?>)
				</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=campaign_brief&workflow_status=archived' ) ); ?>">
					<?php esc_html_e( 'Archived Campaigns', 'campaign-mgmt' ); ?>
					(<?php echo esc_html( $archived_count ); ?>)
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=service_level&post_type=campaign_brief' ) ); ?>">
					<?php esc_html_e( 'Manage Service Levels', 'campaign-mgmt' ); ?>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=ministry&post_type=campaign_brief' ) ); ?>">
					<?php esc_html_e( 'Manage Ministries', 'campaign-mgmt' ); ?>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=cms-settings' ) ); ?>">
					<?php esc_html_e( 'Settings', 'campaign-mgmt' ); ?>
				</a>
			</li>
		</ul>
	</div>

	<!-- Help & Documentation -->
	<div class="cms-dashboard-section">
		<h2><?php esc_html_e( 'Help & Documentation', 'campaign-mgmt' ); ?></h2>
		<div class="cms-help-box">
			<h3><?php esc_html_e( 'Getting Started', 'campaign-mgmt' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Create a new campaign brief from a Planning Center form submission', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Fill in all required fields (Page 1-4)', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Assign a Service Designation (Green/Blue/Black)', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Change status to "Pending Acceptance" when ready', 'campaign-mgmt' ); ?></li>
				<li><?php esc_html_e( 'Copy the shareable link and send to ministry leader', 'campaign-mgmt' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Service Level Guidelines', 'campaign-mgmt' ); ?></h3>
			<ul>
				<li><strong><?php esc_html_e( 'Green:', 'campaign-mgmt' ); ?></strong> <?php esc_html_e( '8-week lead time, basic creative package', 'campaign-mgmt' ); ?></li>
				<li><strong><?php esc_html_e( 'Blue:', 'campaign-mgmt' ); ?></strong> <?php esc_html_e( '10-week lead time, everything in Green plus web strategy, photography', 'campaign-mgmt' ); ?></li>
				<li><strong><?php esc_html_e( 'Black:', 'campaign-mgmt' ); ?></strong> <?php esc_html_e( '12-week lead time, everything in Blue plus print, film, environmental design', 'campaign-mgmt' ); ?></li>
			</ul>

			<p>
				<a href="https://github.com/morganhighley/pcocommunicationrequest" target="_blank" class="button button-secondary">
					<?php esc_html_e( 'View Documentation', 'campaign-mgmt' ); ?>
				</a>
			</p>
		</div>
	</div>
</div>
