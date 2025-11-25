<?php
/**
 * Template for displaying campaign brief (public view)
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	// Get all meta data.
	$ministry_department = get_post_meta( get_the_ID(), '_cms_ministry_department', true );
	$ministry_representative = get_post_meta( get_the_ID(), '_cms_ministry_representative', true );
	$ministry_rep_email = get_post_meta( get_the_ID(), '_cms_ministry_rep_email', true );
	$communications_coordinator = get_post_meta( get_the_ID(), '_cms_communications_coordinator', true );
	$campaign_title = get_post_meta( get_the_ID(), '_cms_campaign_title', true );
	$campaign_tagline = get_post_meta( get_the_ID(), '_cms_campaign_tagline', true );
	$campaign_slug = get_post_meta( get_the_ID(), '_cms_campaign_slug', true );
	$event_dates = get_post_meta( get_the_ID(), '_cms_event_dates', true );
	$event_start_datetime = get_post_meta( get_the_ID(), '_cms_event_start_datetime', true );
	$event_end_datetime = get_post_meta( get_the_ID(), '_cms_event_end_datetime', true );
	$promotion_dates = get_post_meta( get_the_ID(), '_cms_promotion_dates', true );
	$promotion_start_date = get_post_meta( get_the_ID(), '_cms_promotion_start_date', true );
	$promotion_end_date = get_post_meta( get_the_ID(), '_cms_promotion_end_date', true );

	// Format event datetime for display
	if ( $event_start_datetime && $event_end_datetime ) {
		$event_dates_display = date( 'F j, Y \a\t g:i a', strtotime( $event_start_datetime ) ) . ' - ' . date( 'F j, Y \a\t g:i a', strtotime( $event_end_datetime ) );
	} elseif ( $event_start_datetime ) {
		$event_dates_display = date( 'F j, Y \a\t g:i a', strtotime( $event_start_datetime ) );
	} elseif ( $event_dates ) {
		$event_dates_display = $event_dates;
	} else {
		$event_dates_display = '';
	}

	// Format promotion dates for display
	if ( $promotion_start_date && $promotion_end_date ) {
		$promotion_dates_display = date( 'F j', strtotime( $promotion_start_date ) ) . ' - ' . date( 'F j, Y', strtotime( $promotion_end_date ) );
	} elseif ( $promotion_start_date ) {
		$promotion_dates_display = date( 'F j, Y', strtotime( $promotion_start_date ) ) . ' and ongoing';
	} elseif ( $promotion_dates ) {
		$promotion_dates_display = $promotion_dates;
	} else {
		$promotion_dates_display = '';
	}

	$file_path = get_post_meta( get_the_ID(), '_cms_file_path', true );
	$livestream_location = get_post_meta( get_the_ID(), '_cms_livestream_location', true );

	// Page 2 fields.
	$context = get_post_meta( get_the_ID(), '_cms_context', true );
	$audience = get_post_meta( get_the_ID(), '_cms_audience', true );
	$single_persuasive_idea = get_post_meta( get_the_ID(), '_cms_single_persuasive_idea', true );
	$key_facts = get_post_meta( get_the_ID(), '_cms_key_facts', true );
	$preroll_copy = get_post_meta( get_the_ID(), '_cms_preroll_copy', true );
	$approved_additional_copy = get_post_meta( get_the_ID(), '_cms_approved_additional_copy', true );
	$goals = get_post_meta( get_the_ID(), '_cms_goals', true );

	// Page 3 fields.
	$scriptures = get_post_meta( get_the_ID(), '_cms_scriptures', true );
	$emotion_energy = get_post_meta( get_the_ID(), '_cms_emotion_energy', true );
	$styles_elements = get_post_meta( get_the_ID(), '_cms_styles_elements', true );
	$visual_inspiration = get_post_meta( get_the_ID(), '_cms_visual_inspiration', true );

	// Page 4 fields.
	$channel_plan = get_post_meta( get_the_ID(), '_cms_channel_plan', true );
	if ( $channel_plan ) {
		$channel_plan = json_decode( $channel_plan, true );
	}

	// Get service level.
	$service_level_terms = get_the_terms( get_the_ID(), 'service_level' );
	$service_level = $service_level_terms && ! is_wp_error( $service_level_terms ) ? $service_level_terms[0]->name : '';
	$service_level_class = 'service-' . strtolower( $service_level );

	// Workflow status.
	$acceptance_status = get_post_meta( get_the_ID(), '_cms_acceptance_status', true );
	$accepted_by = get_post_meta( get_the_ID(), '_cms_accepted_by', true );
	$accepted_date = get_post_meta( get_the_ID(), '_cms_accepted_date', true );
	$is_locked = get_post_meta( get_the_ID(), '_cms_is_locked', true );

	?>

	<div id="cms-brief-view" class="cms-brief-container <?php echo esc_attr( $service_level_class ); ?>">

		<!-- Print Button -->
		<div class="cms-brief-actions no-print">
			<button onclick="window.print()" class="cms-button cms-button-secondary">
				<?php esc_html_e( 'Print Brief', 'campaign-mgmt' ); ?>
			</button>
			<?php if ( ! $is_locked && ! $accepted_by ) : ?>
				<button id="cms-accept-brief-btn" class="cms-button cms-button-primary">
					<?php esc_html_e( 'Accept This Brief', 'campaign-mgmt' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<!-- Acceptance Status Banner -->
		<?php if ( $accepted_by ) : ?>
			<div class="cms-acceptance-banner <?php echo esc_attr( $service_level_class ); ?> no-print">
				<strong><?php esc_html_e( '✓ Accepted', 'campaign-mgmt' ); ?></strong> by <?php echo esc_html( $accepted_by ); ?>
				on <?php echo esc_html( date( 'F j, Y', strtotime( $accepted_date ) ) ); ?>
			</div>
		<?php endif; ?>

		<!-- PAGE 1: CAMPAIGN BRIEF -->
		<div class="cms-brief-page cms-page-1">
			<h1 class="cms-brief-header">
				<?php echo esc_html( $campaign_title ? $campaign_title : get_the_title() ); ?>
				<?php if ( $ministry_department ) : ?>
					<span class="cms-ministry-label">// <?php echo esc_html( $ministry_department ); ?></span>
				<?php endif; ?>
			</h1>

			<h2 class="cms-section-title"><?php esc_html_e( 'CAMPAIGN BRIEF', 'campaign-mgmt' ); ?></h2>

			<table class="cms-info-table">
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Ministry/Department', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $ministry_department ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Ministry Representative', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $ministry_representative ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Communications Coordinator', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $communications_coordinator ); ?></td>
				</tr>
				<tr class="<?php echo esc_attr( $service_level_class ); ?>">
					<td class="cms-label"><?php esc_html_e( 'Service Designation', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><strong><?php echo esc_html( $service_level ); ?></strong></td>
				</tr>
			</table>

			<table class="cms-info-table">
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Title', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $campaign_title ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Tagline', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $campaign_tagline ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Call to action and /slug', 'campaign-mgmt' ); ?></td>
					<td class="cms-value">
						<?php if ( $campaign_slug ) : ?>
							metropolitanbible.church/<?php echo esc_html( $campaign_slug ); ?>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<table class="cms-info-table">
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Date(s) of event', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $event_dates_display ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Dates for promotion', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $promotion_dates_display ); ?></td>
				</tr>
			</table>

			<table class="cms-info-table">
				<tr>
					<td class="cms-label"><?php esc_html_e( 'File path', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $file_path ); ?></td>
				</tr>
				<?php if ( $livestream_location ) : ?>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Live stream?', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo esc_html( $livestream_location ); ?></td>
				</tr>
				<?php endif; ?>
			</table>

			<div class="cms-page-number">1</div>
		</div>

		<!-- PAGE 2: MESSAGING STRATEGY -->
		<div class="cms-brief-page cms-page-2">
			<h1 class="cms-brief-header">
				<?php echo esc_html( $campaign_title ? $campaign_title : get_the_title() ); ?>
				<?php if ( $ministry_department ) : ?>
					<span class="cms-ministry-label">// <?php echo esc_html( $ministry_department ); ?></span>
				<?php endif; ?>
			</h1>

			<h2 class="cms-section-title"><?php esc_html_e( 'MESSAGING STRATEGY', 'campaign-mgmt' ); ?></h2>

			<table class="cms-content-table">
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Context / History', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $context ) ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Target Audience(s)', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $audience ) ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Single persuasive idea', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $single_persuasive_idea ) ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Key facts', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $key_facts ) ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Pre-roll copy', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $preroll_copy ) ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Approved additional copy', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $approved_additional_copy ) ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Goals', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $goals ) ); ?></td>
				</tr>
			</table>

			<div class="cms-page-number">2</div>
		</div>

		<!-- PAGE 3: INSPIRATION FOR CONCEPTING -->
		<div class="cms-brief-page cms-page-3">
			<h1 class="cms-brief-header">
				<?php echo esc_html( $campaign_title ? $campaign_title : get_the_title() ); ?>
				<?php if ( $ministry_department ) : ?>
					<span class="cms-ministry-label">// <?php echo esc_html( $ministry_department ); ?></span>
				<?php endif; ?>
			</h1>

			<h2 class="cms-section-title"><?php esc_html_e( 'INSPIRATION FOR CONCEPTING', 'campaign-mgmt' ); ?></h2>

			<table class="cms-content-table">
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Scriptures', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $scriptures ) ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Emotion or energy', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $emotion_energy ) ); ?></td>
				</tr>
				<tr>
					<td class="cms-label"><?php esc_html_e( 'Styles or graphic elements', 'campaign-mgmt' ); ?></td>
					<td class="cms-value"><?php echo nl2br( esc_html( $styles_elements ) ); ?></td>
				</tr>
			</table>

			<h3 class="cms-subsection-title"><?php esc_html_e( 'VISUALS', 'campaign-mgmt' ); ?></h3>
			<p class="cms-description">
				<?php esc_html_e( 'Paste or link to images, videos, or any concepts or ideas that could serve as inspiration for this campaign.', 'campaign-mgmt' ); ?>
			</p>
			<div class="cms-visual-content">
				<?php echo nl2br( make_clickable( esc_html( $visual_inspiration ) ) ); ?>
			</div>

			<div class="cms-page-number">3</div>
		</div>

		<!-- PAGE 4: CHANNEL PLAN -->
		<div class="cms-brief-page cms-page-4">
			<h1 class="cms-brief-header">
				<?php echo esc_html( $campaign_title ? $campaign_title : get_the_title() ); ?>
				<?php if ( $ministry_department ) : ?>
					<span class="cms-ministry-label">// <?php echo esc_html( $ministry_department ); ?></span>
				<?php endif; ?>
			</h1>

			<h2 class="cms-section-title"><?php esc_html_e( 'CHANNEL PLAN', 'campaign-mgmt' ); ?></h2>

			<?php if ( $channel_plan && is_array( $channel_plan ) ) : ?>
				<table class="cms-channel-table <?php echo esc_attr( $service_level_class ); ?>">
					<thead>
						<tr>
							<th style="width: 35%;"><?php esc_html_e( 'Channel', 'campaign-mgmt' ); ?></th>
							<th style="width: 25%;"><?php esc_html_e( 'Frequency', 'campaign-mgmt' ); ?></th>
							<th style="width: 40%;"><?php esc_html_e( 'Ideas', 'campaign-mgmt' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr class="promo-period-row">
							<td colspan="3" class="promo-period">
								<strong><?php esc_html_e( 'Promo Period:', 'campaign-mgmt' ); ?></strong>
								<?php echo esc_html( $promotion_dates_display ); ?>
							</td>
						</tr>
						<?php foreach ( $channel_plan as $row ) : ?>
							<?php if ( ! empty( $row['channel'] ) ) : ?>
								<tr>
									<td><?php echo esc_html( $row['channel'] ); ?></td>
									<td><?php echo esc_html( $row['frequency'] ); ?></td>
									<td><?php echo esc_html( $row['ideas'] ); ?></td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<div class="cms-page-number">4</div>
		</div>

		<!-- PAGE 5: ADDITIONAL CONTENT -->
		<div class="cms-brief-page cms-page-5">
			<h1 class="cms-brief-header">
				<?php echo esc_html( $campaign_title ? $campaign_title : get_the_title() ); ?>
				<?php if ( $ministry_department ) : ?>
					<span class="cms-ministry-label">// <?php echo esc_html( $ministry_department ); ?></span>
				<?php endif; ?>
			</h1>

			<h2 class="cms-section-title"><?php esc_html_e( 'ADDITIONAL CONTENT', 'campaign-mgmt' ); ?></h2>

			<div class="cms-additional-content">
				<?php the_content(); ?>
			</div>

			<div class="cms-page-number">5</div>
		</div>

		<!-- COMMENTS SECTION (no-print) -->
		<div class="cms-comments-section no-print">
			<h2><?php esc_html_e( 'Feedback & Comments', 'campaign-mgmt' ); ?></h2>
			<?php if ( isset( $_GET['comment_success'] ) && '1' === $_GET['comment_success'] ) : ?>
				<div class="cms-comment-success" style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #155724;">
					<strong><?php esc_html_e( '✓ Comment submitted successfully!', 'campaign-mgmt' ); ?></strong>
					<?php esc_html_e( 'Your feedback has been added to this brief.', 'campaign-mgmt' ); ?>
				</div>
			<?php endif; ?>
			<?php
			if ( comments_open() || get_comments_number() ) {
				// Use custom comments template.
				$custom_comments = CMS_PLUGIN_DIR . 'templates/comments.php';
				if ( file_exists( $custom_comments ) ) {
					include $custom_comments;
				} else {
					comments_template();
				}
			}
			?>
		</div>

	</div>

	<!-- Accept Brief Modal -->
	<?php if ( ! $is_locked && ! $accepted_by ) : ?>
		<div id="cms-accept-modal" class="cms-modal" style="display:none;">
			<div class="cms-modal-content">
				<span class="cms-modal-close">&times;</span>
				<h2><?php esc_html_e( 'Accept Campaign Brief', 'campaign-mgmt' ); ?></h2>
				<p><?php esc_html_e( 'By accepting this brief, you confirm that you have reviewed all details and approve the campaign plan.', 'campaign-mgmt' ); ?></p>
				<form id="cms-accept-form">
					<p>
						<label for="acceptor_name"><?php esc_html_e( 'Your Name', 'campaign-mgmt' ); ?>*</label>
						<input type="text" id="acceptor_name" name="acceptor_name" required class="widefat" />
					</p>
					<p>
						<label for="acceptor_email"><?php esc_html_e( 'Your Email', 'campaign-mgmt' ); ?>*</label>
						<input type="email" id="acceptor_email" name="acceptor_email" required class="widefat" />
					</p>
					<p>
						<button type="submit" class="cms-button cms-button-primary">
							<?php esc_html_e( 'Accept Brief', 'campaign-mgmt' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>
	<?php endif; ?>

	<?php
endwhile;

get_footer();
