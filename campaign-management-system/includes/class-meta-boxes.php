<?php
/**
 * Campaign Brief Meta Boxes
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CMS_Meta_Boxes Class
 */
class CMS_Meta_Boxes {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_campaign_brief', array( $this, 'save_meta_boxes' ), 10, 2 );
		add_filter( 'manage_campaign_brief_posts_columns', array( $this, 'custom_columns' ) );
		add_action( 'manage_campaign_brief_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		// Remove default taxonomy metabox (we have our own custom one).
		remove_meta_box( 'service_leveldiv', 'campaign_brief', 'side' );
		remove_meta_box( 'tagsdiv-service_level', 'campaign_brief', 'side' );

		// Page 1: Campaign Brief (Top Sheet).
		add_meta_box(
			'cms_campaign_info',
			__( 'Page 1: Campaign Brief', 'campaign-mgmt' ),
			array( $this, 'render_campaign_info' ),
			'campaign_brief',
			'normal',
			'high'
		);

		// Page 2: Messaging Strategy.
		add_meta_box(
			'cms_messaging_strategy',
			__( 'Page 2: Messaging Strategy', 'campaign-mgmt' ),
			array( $this, 'render_messaging_strategy' ),
			'campaign_brief',
			'normal',
			'default'
		);

		// Page 3: Creative Direction.
		add_meta_box(
			'cms_creative_direction',
			__( 'Page 3: Inspiration for Concepting', 'campaign-mgmt' ),
			array( $this, 'render_creative_direction' ),
			'campaign_brief',
			'normal',
			'default'
		);

		// Page 4: Channel Plan.
		add_meta_box(
			'cms_channel_plan',
			__( 'Page 4: Channel Plan', 'campaign-mgmt' ),
			array( $this, 'render_channel_plan' ),
			'campaign_brief',
			'normal',
			'default'
		);

		// Workflow status.
		add_meta_box(
			'cms_workflow',
			__( 'Brief Status & Workflow', 'campaign-mgmt' ),
			array( $this, 'render_workflow' ),
			'campaign_brief',
			'side',
			'high'
		);

		// Shareable link.
		add_meta_box(
			'cms_share',
			__( 'Share Brief', 'campaign-mgmt' ),
			array( $this, 'render_share' ),
			'campaign_brief',
			'side',
			'default'
		);

		// Service Level selector.
		add_meta_box(
			'cms_service_level',
			__( 'Service Level', 'campaign-mgmt' ),
			array( $this, 'render_service_level' ),
			'campaign_brief',
			'side',
			'high'
		);

		// Comments meta box.
		add_meta_box(
			'cms_brief_comments',
			__( 'Brief Comments & Feedback', 'campaign-mgmt' ),
			array( $this, 'render_comments_meta_box' ),
			'campaign_brief',
			'normal',
			'default'
		);
	}

	/**
	 * Render Page 1: Campaign Brief
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_campaign_info( $post ) {
		wp_nonce_field( 'cms_meta_box', 'cms_meta_box_nonce' );

		$ministry_department = get_post_meta( $post->ID, '_cms_ministry_department', true );
		$ministry_representative = get_post_meta( $post->ID, '_cms_ministry_representative', true );
		$ministry_rep_email = get_post_meta( $post->ID, '_cms_ministry_rep_email', true );
		$communications_coordinator = get_post_meta( $post->ID, '_cms_communications_coordinator', true );
		$campaign_title = get_post_meta( $post->ID, '_cms_campaign_title', true );
		$campaign_tagline = get_post_meta( $post->ID, '_cms_campaign_tagline', true );
		$campaign_slug = get_post_meta( $post->ID, '_cms_campaign_slug', true );
		$event_dates = get_post_meta( $post->ID, '_cms_event_dates', true );
		$event_start_datetime = get_post_meta( $post->ID, '_cms_event_start_datetime', true );
		$event_end_datetime = get_post_meta( $post->ID, '_cms_event_end_datetime', true );
		$promotion_dates = get_post_meta( $post->ID, '_cms_promotion_dates', true );
		$file_path = get_post_meta( $post->ID, '_cms_file_path', true );
		$livestream_location = get_post_meta( $post->ID, '_cms_livestream_location', true );
		?>

		<div class="cms-meta-box-grid">
			<div class="cms-field">
				<label for="cms_ministry_department"><?php esc_html_e( 'Ministry/Department', 'campaign-mgmt' ); ?></label>
				<input type="text" id="cms_ministry_department" name="cms_ministry_department" value="<?php echo esc_attr( $ministry_department ); ?>" class="widefat" />
				<p class="description"><?php esc_html_e( 'e.g., Metro Kids, Women\'s Ministry', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_ministry_representative"><?php esc_html_e( 'Ministry Representative', 'campaign-mgmt' ); ?></label>
				<input type="text" id="cms_ministry_representative" name="cms_ministry_representative" value="<?php echo esc_attr( $ministry_representative ); ?>" class="widefat" />
			</div>

			<div class="cms-field">
				<label for="cms_ministry_rep_email"><?php esc_html_e( 'Ministry Rep Email', 'campaign-mgmt' ); ?></label>
				<input type="email" id="cms_ministry_rep_email" name="cms_ministry_rep_email" value="<?php echo esc_attr( $ministry_rep_email ); ?>" class="widefat" />
			</div>

			<div class="cms-field">
				<label for="cms_communications_coordinator"><?php esc_html_e( 'Communications Coordinator', 'campaign-mgmt' ); ?></label>
				<input type="text" id="cms_communications_coordinator" name="cms_communications_coordinator" value="<?php echo esc_attr( $communications_coordinator ); ?>" class="widefat" placeholder="Laura Murray" />
			</div>

			<div class="cms-field">
				<label for="cms_campaign_title"><?php esc_html_e( 'Title', 'campaign-mgmt' ); ?></label>
				<input type="text" id="cms_campaign_title" name="cms_campaign_title" value="<?php echo esc_attr( $campaign_title ); ?>" class="widefat" />
			</div>

			<div class="cms-field">
				<label for="cms_campaign_tagline"><?php esc_html_e( 'Tagline', 'campaign-mgmt' ); ?></label>
				<input type="text" id="cms_campaign_tagline" name="cms_campaign_tagline" value="<?php echo esc_attr( $campaign_tagline ); ?>" class="widefat" />
			</div>

			<div class="cms-field">
				<label for="cms_campaign_slug"><?php esc_html_e( 'Call to action and /slug', 'campaign-mgmt' ); ?></label>
				<div class="cms-slug-wrapper">
					<span class="cms-slug-prefix">metropolitanbible.church/</span>
					<input type="text" id="cms_campaign_slug" name="cms_campaign_slug" value="<?php echo esc_attr( $campaign_slug ); ?>" />
				</div>
			</div>

			<div class="cms-field">
				<label for="cms_event_start_datetime"><?php esc_html_e( 'Event Start Date & Time', 'campaign-mgmt' ); ?></label>
				<input type="datetime-local" id="cms_event_start_datetime" name="cms_event_start_datetime" value="<?php echo esc_attr( $event_start_datetime ); ?>" class="widefat" />
			</div>

			<div class="cms-field">
				<label for="cms_event_end_datetime"><?php esc_html_e( 'Event End Date & Time', 'campaign-mgmt' ); ?></label>
				<input type="datetime-local" id="cms_event_end_datetime" name="cms_event_end_datetime" value="<?php echo esc_attr( $event_end_datetime ); ?>" class="widefat" />
				<p class="description"><?php esc_html_e( 'Select start and end dates/times for the event', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_promotion_start_date"><?php esc_html_e( 'Promotion start date', 'campaign-mgmt' ); ?></label>
				<input type="date" id="cms_promotion_start_date" name="cms_promotion_start_date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_cms_promotion_start_date', true ) ); ?>" class="widefat" />
			</div>

			<div class="cms-field">
				<label for="cms_promotion_end_date"><?php esc_html_e( 'Promotion end date', 'campaign-mgmt' ); ?></label>
				<input type="date" id="cms_promotion_end_date" name="cms_promotion_end_date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_cms_promotion_end_date', true ) ); ?>" class="widefat" />
			</div>

			<div class="cms-field">
				<label for="cms_file_path"><?php esc_html_e( 'File path', 'campaign-mgmt' ); ?></label>
				<input type="text" id="cms_file_path" name="cms_file_path" value="<?php echo esc_attr( $file_path ); ?>" class="widefat" />
				<p class="description"><?php esc_html_e( 'Google Drive or server path to campaign assets', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_livestream_location"><?php esc_html_e( 'Live stream? (if so, where?)', 'campaign-mgmt' ); ?></label>
				<input type="text" id="cms_livestream_location" name="cms_livestream_location" value="<?php echo esc_attr( $livestream_location ); ?>" class="widefat" />
				<p class="description"><?php esc_html_e( 'e.g., YouTube, Facebook Live, Church Website', 'campaign-mgmt' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Page 2: Messaging Strategy
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_messaging_strategy( $post ) {
		$context = get_post_meta( $post->ID, '_cms_context', true );
		$audience = get_post_meta( $post->ID, '_cms_audience', true );
		$single_persuasive_idea = get_post_meta( $post->ID, '_cms_single_persuasive_idea', true );
		$key_facts = get_post_meta( $post->ID, '_cms_key_facts', true );
		$preroll_copy = get_post_meta( $post->ID, '_cms_preroll_copy', true );
		$approved_additional_copy = get_post_meta( $post->ID, '_cms_approved_additional_copy', true );
		$goals = get_post_meta( $post->ID, '_cms_goals', true );
		?>

		<div class="cms-meta-box-grid">
			<div class="cms-field">
				<label for="cms_context"><?php esc_html_e( 'Context / History', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_context" name="cms_context" rows="4" class="widefat"><?php echo esc_textarea( $context ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Background information about this campaign', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_audience"><?php esc_html_e( 'Target Audience(s)', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_audience" name="cms_audience" rows="3" class="widefat"><?php echo esc_textarea( $audience ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Who are we trying to reach?', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_single_persuasive_idea"><?php esc_html_e( 'Single persuasive idea', 'campaign-mgmt' ); ?></label>
				<input type="text" id="cms_single_persuasive_idea" name="cms_single_persuasive_idea" value="<?php echo esc_attr( $single_persuasive_idea ); ?>" class="widefat" />
				<p class="description"><?php esc_html_e( 'One-sentence purpose/reason for this campaign', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_key_facts"><?php esc_html_e( 'Key facts', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_key_facts" name="cms_key_facts" rows="4" class="widefat"><?php echo esc_textarea( $key_facts ); ?></textarea>
				<p class="description"><?php esc_html_e( 'What is this event? What does it accomplish? Any uniqueness?', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_preroll_copy"><?php esc_html_e( 'Pre-roll copy', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_preroll_copy" name="cms_preroll_copy" rows="3" class="widefat"><?php echo esc_textarea( $preroll_copy ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Event Name - Brief description. Learn more at URL/slug.', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_approved_additional_copy"><?php esc_html_e( 'Approved additional copy', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_approved_additional_copy" name="cms_approved_additional_copy" rows="5" class="widefat"><?php echo esc_textarea( $approved_additional_copy ); ?></textarea>
				<p class="description"><?php esc_html_e( 'More details. How it works. Schedule.', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_goals"><?php esc_html_e( 'Goals', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_goals" name="cms_goals" rows="4" class="widefat"><?php echo esc_textarea( $goals ); ?></textarea>
				<p class="description"><?php esc_html_e( 'What would need to happen for this event to be considered successful?', 'campaign-mgmt' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Page 3: Creative Direction
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_creative_direction( $post ) {
		$scriptures = get_post_meta( $post->ID, '_cms_scriptures', true );
		$emotion_energy = get_post_meta( $post->ID, '_cms_emotion_energy', true );
		$styles_elements = get_post_meta( $post->ID, '_cms_styles_elements', true );
		$visual_inspiration = get_post_meta( $post->ID, '_cms_visual_inspiration', true );
		?>

		<div class="cms-meta-box-grid">
			<div class="cms-field">
				<label for="cms_scriptures"><?php esc_html_e( 'Scriptures', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_scriptures" name="cms_scriptures" rows="3" class="widefat"><?php echo esc_textarea( $scriptures ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Scripture references used as a guide for this project', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_emotion_energy"><?php esc_html_e( 'Emotion or energy', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_emotion_energy" name="cms_emotion_energy" rows="3" class="widefat"><?php echo esc_textarea( $emotion_energy ); ?></textarea>
				<p class="description"><?php esc_html_e( 'What feeling should this campaign convey?', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_styles_elements"><?php esc_html_e( 'Styles or graphic elements', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_styles_elements" name="cms_styles_elements" rows="3" class="widefat"><?php echo esc_textarea( $styles_elements ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Design direction, colors, typography, etc.', 'campaign-mgmt' ); ?></p>
			</div>

			<div class="cms-field">
				<label for="cms_visual_inspiration"><?php esc_html_e( 'Visuals (links or descriptions)', 'campaign-mgmt' ); ?></label>
				<textarea id="cms_visual_inspiration" name="cms_visual_inspiration" rows="6" class="widefat"><?php echo esc_textarea( $visual_inspiration ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Paste links to images, videos, or describe concepts that could serve as inspiration', 'campaign-mgmt' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Page 4: Channel Plan
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_channel_plan( $post ) {
		$channel_plan = get_post_meta( $post->ID, '_cms_channel_plan', true );

		// Default channel plan structure.
		if ( empty( $channel_plan ) ) {
			$channel_plan = array(
				array( 'channel' => 'metropolitanbible.church/[slug]', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Bulletin - Print', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Bulletin - Digital', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Service Pre-roll', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Magnify News', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Metropolitan Facebook', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Metropolitan Instagram', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Family Group Announcements', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Homepage Takeover', 'frequency' => '', 'ideas' => '' ),
				array( 'channel' => 'Church Text/CCA Push Notification', 'frequency' => '', 'ideas' => '' ),
			);
		} else {
			$channel_plan = json_decode( $channel_plan, true );
		}
		?>

		<p class="description"><?php esc_html_e( 'Plan which channels to use for promoting this campaign. Add or remove rows as needed.', 'campaign-mgmt' ); ?></p>

		<div id="cms-channel-plan-wrapper">
			<table class="cms-channel-plan-table">
				<thead>
					<tr>
						<th style="width: 35%;"><?php esc_html_e( 'Channel', 'campaign-mgmt' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Frequency', 'campaign-mgmt' ); ?></th>
						<th style="width: 40%;"><?php esc_html_e( 'Ideas', 'campaign-mgmt' ); ?></th>
						<th style="width: 5%;"></th>
					</tr>
				</thead>
				<tbody id="cms-channel-plan-rows">
					<?php foreach ( $channel_plan as $index => $row ) : ?>
						<tr class="cms-channel-row">
							<td>
								<input type="text" name="cms_channel_plan[<?php echo esc_attr( $index ); ?>][channel]" value="<?php echo esc_attr( $row['channel'] ); ?>" class="widefat" />
							</td>
							<td>
								<input type="text" name="cms_channel_plan[<?php echo esc_attr( $index ); ?>][frequency]" value="<?php echo esc_attr( $row['frequency'] ); ?>" class="widefat" />
							</td>
							<td>
								<input type="text" name="cms_channel_plan[<?php echo esc_attr( $index ); ?>][ideas]" value="<?php echo esc_attr( $row['ideas'] ); ?>" class="widefat" />
							</td>
							<td>
								<button type="button" class="button cms-remove-channel-row">×</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<button type="button" class="button button-secondary" id="cms-add-channel-row">
				<?php esc_html_e( '+ Add Channel', 'campaign-mgmt' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render Workflow meta box
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_workflow( $post ) {
		$workflow_status = get_post_meta( $post->ID, '_cms_workflow_status', true );
		if ( empty( $workflow_status ) ) {
			$workflow_status = 'draft';
		}

		$acceptance_status = get_post_meta( $post->ID, '_cms_acceptance_status', true );
		$accepted_by = get_post_meta( $post->ID, '_cms_accepted_by', true );
		$accepted_date = get_post_meta( $post->ID, '_cms_accepted_date', true );
		$is_locked = get_post_meta( $post->ID, '_cms_is_locked', true );

		$workflow_labels = array(
			'draft'              => __( 'Draft', 'campaign-mgmt' ),
			'pending_acceptance' => __( 'Pending Acceptance', 'campaign-mgmt' ),
			'accepted'           => __( 'Accepted', 'campaign-mgmt' ),
			'archived'           => __( 'Archived', 'campaign-mgmt' ),
		);
		?>
		<div class="cms-workflow-status">
			<p>
				<strong><?php esc_html_e( 'Workflow Status:', 'campaign-mgmt' ); ?></strong><br>
				<select name="cms_workflow_status" id="cms_workflow_status_select" style="width: 100%;">
					<?php foreach ( $workflow_labels as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $workflow_status, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php if ( $accepted_by ) : ?>
				<p class="cms-accepted-info" style="background: #d4edda; padding: 10px; border-radius: 4px;">
					<strong><?php esc_html_e( '✓ Accepted by:', 'campaign-mgmt' ); ?></strong><br>
					<?php echo esc_html( $accepted_by ); ?><br>
					<small><?php echo esc_html( date( 'F j, Y \a\t g:i a', strtotime( $accepted_date ) ) ); ?></small>
				</p>
			<?php endif; ?>

			<?php if ( $is_locked ) : ?>
				<p class="cms-locked-notice" style="background: #fff3cd; padding: 10px; border-radius: 4px;">
					<strong><?php esc_html_e( '🔒 Brief is locked', 'campaign-mgmt' ); ?></strong><br>
					<small><?php esc_html_e( 'This brief has been accepted and is locked for editing.', 'campaign-mgmt' ); ?></small>
				</p>
				<p>
					<button type="button" class="button button-secondary" id="cms-unlock-brief">
						<?php esc_html_e( 'Unlock Brief', 'campaign-mgmt' ); ?>
					</button>
				</p>
			<?php endif; ?>

			<?php if ( $accepted_by && ! $is_locked ) : ?>
				<p>
					<button type="button" class="button button-secondary" id="cms-unaccept-brief">
						<?php esc_html_e( 'Clear Acceptance', 'campaign-mgmt' ); ?>
					</button>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Service Level meta box
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_service_level( $post ) {
		$terms = get_the_terms( $post->ID, 'service_level' );
		$current_level = $terms && ! is_wp_error( $terms ) ? $terms[0]->term_id : '';

		$service_levels = get_terms(
			array(
				'taxonomy'   => 'service_level',
				'hide_empty' => false,
			)
		);
		?>

		<div class="cms-service-level-selector">
			<p><strong><?php esc_html_e( 'Select campaign service level:', 'campaign-mgmt' ); ?></strong></p>

			<?php if ( ! empty( $service_levels ) && ! is_wp_error( $service_levels ) ) : ?>
				<?php foreach ( $service_levels as $level ) : ?>
					<label style="display: block; margin: 8px 0;">
						<input type="radio"
							name="cms_service_level_id"
							value="<?php echo esc_attr( $level->term_id ); ?>"
							<?php checked( $current_level, $level->term_id ); ?>
						/>
						<strong><?php echo esc_html( $level->name ); ?></strong>
						<br>
						<small style="margin-left: 20px; color: #646970;">
							<?php
							if ( 'Green' === $level->name ) {
								esc_html_e( '8-week lead time • Basic creative package', 'campaign-mgmt' );
							} elseif ( 'Blue' === $level->name ) {
								esc_html_e( '10-week lead time • Includes web & photography', 'campaign-mgmt' );
							} elseif ( 'Black' === $level->name ) {
								esc_html_e( '12-week lead time • Full service with print & film', 'campaign-mgmt' );
							}
							?>
						</small>
					</label>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'No service levels found. Please add them in Campaign Briefs → Service Levels.', 'campaign-mgmt' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Share meta box
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_share( $post ) {
		// Check if post has been saved (has an ID > 0)
		if ( ! $post->ID || 0 === $post->ID ) {
			?>
			<p class="description"><?php esc_html_e( 'Save draft first to generate shareable link.', 'campaign-mgmt' ); ?></p>
			<?php
			return;
		}

		$share_url = get_permalink( $post->ID );
		?>

		<div class="cms-share-box">
			<p><strong><?php esc_html_e( 'Shareable Link:', 'campaign-mgmt' ); ?></strong></p>
			<input type="text" value="<?php echo esc_url( $share_url ); ?>" readonly class="widefat" id="cms-share-url" />
			<p>
				<button type="button" class="button button-primary" id="cms-copy-link">
					<?php esc_html_e( 'Copy Link', 'campaign-mgmt' ); ?>
				</button>
			</p>
			<p class="description"><?php esc_html_e( 'Anyone with this link can view the brief. Comments require a name/email.', 'campaign-mgmt' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render comments meta box
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_comments_meta_box( $post ) {
		$comments = get_comments( array(
			'post_id' => $post->ID,
			'status'  => 'approve',
			'orderby' => 'comment_date',
			'order'   => 'DESC',
		));

		if ( empty( $comments ) ) {
			echo '<p>' . esc_html__( 'No comments yet.', 'campaign-mgmt' ) . '</p>';
			echo '<p><a href="' . esc_url( get_permalink( $post->ID ) . '#comments' ) . '" target="_blank" class="button button-secondary">' . esc_html__( 'View Brief & Add Comment', 'campaign-mgmt' ) . '</a></p>';
			return;
		}

		echo '<div class="cms-admin-comments">';
		echo '<p><strong>' . sprintf( _n( '%s Comment', '%s Comments', count( $comments ), 'campaign-mgmt' ), count( $comments ) ) . '</strong></p>';

		foreach ( $comments as $comment ) {
			?>
			<div class="cms-admin-comment" style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-left: 4px solid #0073aa;">
				<div class="comment-meta" style="margin-bottom: 10px;">
					<strong><?php echo esc_html( $comment->comment_author ); ?></strong>
					<span style="color: #666; margin-left: 10px;">
						(<?php echo esc_html( $comment->comment_author_email ); ?>)
					</span>
					<br>
					<small style="color: #999;">
						<?php echo esc_html( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) . ' ago' ); ?>
						(<?php echo esc_html( date( 'F j, Y \a\t g:i a', strtotime( $comment->comment_date ) ) ); ?>)
					</small>
				</div>
				<div class="comment-content">
					<?php echo wp_kses_post( wpautop( $comment->comment_content ) ); ?>
				</div>
				<div class="comment-actions" style="margin-top: 10px;">
					<a href="<?php echo esc_url( admin_url( 'comment.php?action=editcomment&c=' . $comment->comment_ID ) ); ?>" class="button button-small">
						<?php esc_html_e( 'Edit', 'campaign-mgmt' ); ?>
					</a>
					<a href="<?php echo esc_url( get_permalink( $post->ID ) . '#comment-' . $comment->comment_ID ); ?>" class="button button-small" target="_blank">
						<?php esc_html_e( 'View on Brief', 'campaign-mgmt' ); ?>
					</a>
				</div>
			</div>
			<?php
		}

		echo '<p><a href="' . esc_url( get_permalink( $post->ID ) . '#comments' ) . '" target="_blank" class="button button-primary">' . esc_html__( 'View All Comments on Brief', 'campaign-mgmt' ) . '</a></p>';
		echo '</div>';
	}

	/**
	 * Save meta box data
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Check nonce.
		if ( ! isset( $_POST['cms_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['cms_meta_box_nonce'], 'cms_meta_box' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save Page 1 fields.
		$page1_fields = array(
			'cms_ministry_department',
			'cms_ministry_representative',
			'cms_ministry_rep_email',
			'cms_communications_coordinator',
			'cms_campaign_title',
			'cms_campaign_tagline',
			'cms_campaign_slug',
			'cms_event_dates',
			'cms_event_start_datetime',
			'cms_event_end_datetime',
			'cms_promotion_dates',
			'cms_promotion_start_date',
			'cms_promotion_end_date',
			'cms_file_path',
			'cms_livestream_location',
		);

		foreach ( $page1_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}

		// Save Page 2 fields.
		$page2_fields = array(
			'cms_context',
			'cms_audience',
			'cms_single_persuasive_idea',
			'cms_key_facts',
			'cms_preroll_copy',
			'cms_approved_additional_copy',
			'cms_goals',
		);

		foreach ( $page2_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( $_POST[ $field ] ) );
			}
		}

		// Save Page 3 fields.
		$page3_fields = array(
			'cms_scriptures',
			'cms_emotion_energy',
			'cms_styles_elements',
			'cms_visual_inspiration',
		);

		foreach ( $page3_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( $_POST[ $field ] ) );
			}
		}

		// Save Page 4: Channel Plan.
		if ( isset( $_POST['cms_channel_plan'] ) && is_array( $_POST['cms_channel_plan'] ) ) {
			$channel_plan = array();
			foreach ( $_POST['cms_channel_plan'] as $row ) {
				$channel_plan[] = array(
					'channel'   => sanitize_text_field( $row['channel'] ),
					'frequency' => sanitize_text_field( $row['frequency'] ),
					'ideas'     => sanitize_text_field( $row['ideas'] ),
				);
			}
			update_post_meta( $post_id, '_cms_channel_plan', wp_json_encode( $channel_plan ) );
		}

		// Save workflow status
		if ( isset( $_POST['cms_workflow_status'] ) ) {
			$allowed_statuses = array( 'draft', 'pending_acceptance', 'accepted', 'archived' );
			$new_status = sanitize_text_field( $_POST['cms_workflow_status'] );
			if ( in_array( $new_status, $allowed_statuses, true ) ) {
				update_post_meta( $post_id, '_cms_workflow_status', $new_status );
			}
		}

		// Save workflow fields.
		if ( isset( $_POST['cms_is_locked'] ) ) {
			update_post_meta( $post_id, '_cms_is_locked', absint( $_POST['cms_is_locked'] ) );
		}

		// Save service level taxonomy.
		if ( isset( $_POST['cms_service_level_id'] ) && ! empty( $_POST['cms_service_level_id'] ) ) {
			$term_id = absint( $_POST['cms_service_level_id'] );
			// First, remove all existing service level terms to prevent duplicates
			wp_set_object_terms( $post_id, array(), 'service_level', false );
			// Then set the single selected term
			wp_set_object_terms( $post_id, $term_id, 'service_level', false );
		}
	}

	/**
	 * Add custom columns to admin list
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function custom_columns( $columns ) {
		$new_columns = array();

		$new_columns['cb'] = $columns['cb'];
		$new_columns['title'] = $columns['title'];
		$new_columns['service_level'] = __( 'Service Level', 'campaign-mgmt' );
		$new_columns['ministry'] = __( 'Ministry', 'campaign-mgmt' );
		$new_columns['ministry_rep'] = __( 'Ministry Rep', 'campaign-mgmt' );
		$new_columns['event_date'] = __( 'Event Date', 'campaign-mgmt' );
		$new_columns['status'] = __( 'Status', 'campaign-mgmt' );
		$new_columns['date'] = $columns['date'];

		return $new_columns;
	}

	/**
	 * Display custom column content
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function custom_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'service_level':
				$terms = get_the_terms( $post_id, 'service_level' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$level = $terms[0]->name;
					$color_class = 'cms-badge cms-badge-' . strtolower( $level );
					echo '<span class="' . esc_attr( $color_class ) . '">' . esc_html( $level ) . '</span>';
				} else {
					echo '—';
				}
				break;

			case 'ministry':
				$terms = get_the_terms( $post_id, 'ministry' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					echo esc_html( $terms[0]->name );
				} else {
					echo '—';
				}
				break;

			case 'ministry_rep':
				$rep = get_post_meta( $post_id, '_cms_ministry_representative', true );
				echo $rep ? esc_html( $rep ) : '—';
				break;

			case 'event_date':
				$event_start = get_post_meta( $post_id, '_cms_event_start_datetime', true );
				$event_dates_old = get_post_meta( $post_id, '_cms_event_dates', true );
				if ( $event_start ) {
					echo esc_html( date( 'F j, Y', strtotime( $event_start ) ) );
				} elseif ( $event_dates_old ) {
					echo esc_html( $event_dates_old );
				} else {
					echo '—';
				}
				break;

			case 'status':
				$workflow_status = get_post_meta( $post_id, '_cms_workflow_status', true );
				if ( empty( $workflow_status ) ) {
					$workflow_status = 'draft';
				}
				$status_labels = array(
					'draft'              => __( 'Draft', 'campaign-mgmt' ),
					'pending_acceptance' => __( 'Pending Acceptance', 'campaign-mgmt' ),
					'accepted'           => __( 'Accepted', 'campaign-mgmt' ),
					'archived'           => __( 'Archived', 'campaign-mgmt' ),
				);
				$status_label = isset( $status_labels[ $workflow_status ] ) ? $status_labels[ $workflow_status ] : ucfirst( $workflow_status );

				// Add color coding
				$status_colors = array(
					'draft'              => '#6c757d',
					'pending_acceptance' => '#ffc107',
					'accepted'           => '#28a745',
					'archived'           => '#17a2b8',
				);
				$color = isset( $status_colors[ $workflow_status ] ) ? $status_colors[ $workflow_status ] : '#6c757d';

				echo '<span style="background: ' . esc_attr( $color ) . '; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html( $status_label ) . '</span>';
				break;
		}
	}
}
