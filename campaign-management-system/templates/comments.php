<?php
/**
 * Chat-Style Feedback Template for Campaign Briefs
 *
 * @package CampaignManagementSystem
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Don't load if not a campaign brief
if ( 'campaign_brief' !== get_post_type() ) {
    return;
}

// Get existing comments for this brief
$comments = get_comments( array(
    'post_id' => get_the_ID(),
    'status'  => 'approve',
    'orderby' => 'comment_date',
    'order'   => 'ASC', // Oldest first, like a chat
));

$comment_count = count( $comments );
?>

<div id="cms-chat" class="cms-chat-wrapper">
    <div class="cms-chat-header">
        <h3 class="cms-chat-title">
            <span class="cms-chat-icon">💬</span>
            <?php esc_html_e( 'Feedback & Discussion', 'campaign-mgmt' ); ?>
            <?php if ( $comment_count > 0 ) : ?>
                <span class="cms-chat-count"><?php echo esc_html( $comment_count ); ?></span>
            <?php endif; ?>
        </h3>
        <p class="cms-chat-subtitle">
            <?php esc_html_e( 'Use this space to discuss the campaign brief with the communications team.', 'campaign-mgmt' ); ?>
        </p>
    </div>

    <!-- Chat Messages Container -->
    <div class="cms-chat-messages" id="cms-chat-messages">
        <?php if ( empty( $comments ) ) : ?>
            <div class="cms-chat-empty">
                <div class="cms-chat-empty-icon">💭</div>
                <p><?php esc_html_e( 'No messages yet. Start the conversation!', 'campaign-mgmt' ); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ( $comments as $comment ) : ?>
                <div class="cms-chat-message" id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>">
                    <div class="cms-chat-avatar">
                        <?php echo get_avatar( $comment->comment_author_email, 40 ); ?>
                    </div>
                    <div class="cms-chat-bubble">
                        <div class="cms-chat-meta">
                            <span class="cms-chat-author"><?php echo esc_html( $comment->comment_author ); ?></span>
                            <span class="cms-chat-time" title="<?php echo esc_attr( date( 'F j, Y \a\t g:i a', strtotime( $comment->comment_date ) ) ); ?>">
                                <?php echo esc_html( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) ); ?> ago
                            </span>
                        </div>
                        <div class="cms-chat-content">
                            <?php echo wp_kses_post( wpautop( $comment->comment_content ) ); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Success/Error Messages -->
    <div id="cms-chat-response" class="cms-chat-response" style="display: none;"></div>

    <!-- Chat Input Form -->
    <?php if ( comments_open() ) : ?>
        <div class="cms-chat-input-wrapper">
            <form id="cms-chat-form" class="cms-chat-form">
                <div class="cms-chat-input-row">
                    <div class="cms-chat-input-field cms-chat-input-name">
                        <label for="cms_chat_author" class="screen-reader-text"><?php esc_html_e( 'Your Name', 'campaign-mgmt' ); ?></label>
                        <input
                            type="text"
                            id="cms_chat_author"
                            name="author"
                            placeholder="<?php esc_attr_e( 'Your Name *', 'campaign-mgmt' ); ?>"
                            required
                            maxlength="245"
                        />
                    </div>
                    <div class="cms-chat-input-field cms-chat-input-email">
                        <label for="cms_chat_email" class="screen-reader-text"><?php esc_html_e( 'Your Email', 'campaign-mgmt' ); ?></label>
                        <input
                            type="email"
                            id="cms_chat_email"
                            name="email"
                            placeholder="<?php esc_attr_e( 'Your Email *', 'campaign-mgmt' ); ?>"
                            required
                            maxlength="100"
                        />
                    </div>
                </div>
                <div class="cms-chat-input-row cms-chat-message-row">
                    <div class="cms-chat-input-field cms-chat-input-message">
                        <label for="cms_chat_content" class="screen-reader-text"><?php esc_html_e( 'Your Message', 'campaign-mgmt' ); ?></label>
                        <textarea
                            id="cms_chat_content"
                            name="comment"
                            placeholder="<?php esc_attr_e( 'Type your message...', 'campaign-mgmt' ); ?>"
                            required
                            maxlength="65525"
                            rows="3"
                        ></textarea>
                    </div>
                    <button type="submit" class="cms-chat-send-btn" id="cms-chat-submit" title="<?php esc_attr_e( 'Send Message', 'campaign-mgmt' ); ?>">
                        <span class="cms-chat-send-icon">➤</span>
                        <span class="cms-chat-send-text"><?php esc_html_e( 'Send', 'campaign-mgmt' ); ?></span>
                    </button>
                </div>

                <input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( get_the_ID() ); ?>" />
                <?php wp_nonce_field( 'cms_submit_comment', 'cms_comment_nonce' ); ?>
            </form>
            <p class="cms-chat-privacy">
                <?php esc_html_e( 'Your email will not be published. Messages are visible to the communications team.', 'campaign-mgmt' ); ?>
            </p>
        </div>
    <?php else : ?>
        <div class="cms-chat-closed">
            <p><?php esc_html_e( 'Discussion is closed for this brief.', 'campaign-mgmt' ); ?></p>
        </div>
    <?php endif; ?>
</div>
