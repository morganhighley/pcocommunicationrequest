<?php
/**
 * Custom Comments Template for Campaign Briefs
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Don't load if not a campaign brief or comments are closed.
if ( 'campaign_brief' !== get_post_type() || ! ( comments_open() || get_comments_number() ) ) {
	return;
}
?>

<div id="comments" class="cms-comments-wrapper">
	<?php if ( have_comments() ) : ?>
		<h3 class="cms-comments-title">
			<?php
			$comment_count = get_comments_number();
			printf(
				_n( '%s Comment', '%s Comments', $comment_count, 'campaign-mgmt' ),
				number_format_i18n( $comment_count )
			);
			?>
		</h3>

		<ol class="cms-comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 50,
					'callback'    => 'cms_custom_comment_display',
				)
			);
			?>
		</ol>

		<?php
		the_comments_navigation();
		?>
	<?php endif; ?>

	<?php if ( comments_open() ) : ?>
		<div class="cms-comment-form-wrapper">
			<h3><?php esc_html_e( 'Leave Your Feedback', 'campaign-mgmt' ); ?></h3>
			<p class="cms-comment-notes">
				<?php esc_html_e( 'Your email address will not be published. All fields are required.', 'campaign-mgmt' ); ?>
			</p>

			<form id="cms-comment-form" class="cms-comment-form" method="post">
				<div class="cms-form-field">
					<label for="cms_comment_author"><?php esc_html_e( 'Your Name', 'campaign-mgmt' ); ?> <span class="required">*</span></label>
					<input type="text" id="cms_comment_author" name="author" required maxlength="245" />
				</div>

				<div class="cms-form-field">
					<label for="cms_comment_email"><?php esc_html_e( 'Your Email', 'campaign-mgmt' ); ?> <span class="required">*</span></label>
					<input type="email" id="cms_comment_email" name="email" required maxlength="100" />
				</div>

				<div class="cms-form-field">
					<label for="cms_comment_content"><?php esc_html_e( 'Your Comment', 'campaign-mgmt' ); ?> <span class="required">*</span></label>
					<textarea id="cms_comment_content" name="comment" rows="8" required maxlength="65525"></textarea>
				</div>

				<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( get_the_ID() ); ?>" />
				<?php wp_nonce_field( 'cms_submit_comment', 'cms_comment_nonce' ); ?>

				<div class="cms-form-actions">
					<button type="submit" class="cms-button cms-button-primary" id="cms-submit-comment">
						<?php esc_html_e( 'Submit Comment', 'campaign-mgmt' ); ?>
					</button>
				</div>

				<div id="cms-comment-response" style="display:none;"></div>
			</form>
		</div>
	<?php else : ?>
		<p class="cms-comments-closed"><?php esc_html_e( 'Comments are closed.', 'campaign-mgmt' ); ?></p>
	<?php endif; ?>
</div>

<?php
/**
 * Custom comment display callback
 *
 * @param WP_Comment $comment Comment object.
 * @param array      $args    Comment arguments.
 * @param int        $depth   Comment depth.
 */
function cms_custom_comment_display( $comment, $args, $depth ) {
	?>
	<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
		<article class="cms-comment-body">
			<div class="cms-comment-author">
				<?php echo get_avatar( $comment, 50 ); ?>
				<div class="cms-comment-meta">
					<strong><?php echo get_comment_author_link( $comment ); ?></strong>
					<time datetime="<?php comment_time( 'c' ); ?>">
						<?php
						printf(
							__( '%s ago', 'campaign-mgmt' ),
							human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) )
						);
						?>
					</time>
				</div>
			</div>
			<div class="cms-comment-content">
				<?php comment_text(); ?>
			</div>
		</article>
	<?php
}
