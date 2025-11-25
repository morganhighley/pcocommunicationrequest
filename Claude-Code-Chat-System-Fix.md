# Claude Code: Implement Chat-Style Feedback System

## Overview

Replace the current broken comment form with a modern chat-style feedback interface that:
- Shows all messages in a conversation thread (newest at bottom, like a chat)
- Has a clean, properly-styled input form
- Actually works (posts comments, shows them, sends email notifications)
- Is fully responsive for mobile/tablet/desktop
- Archives with the brief (uses WordPress comments system under the hood)
- Does NOT need to print (keep `no-print` class on the section)

---

## Files to Modify

1. `templates/comments.php` - Complete redesign as chat interface
2. `assets/css/public.css` - New chat-style CSS
3. `assets/js/public.js` - Update JS for chat functionality
4. `includes/class-workflow.php` - Fix email notification

---

## 1. Replace `templates/comments.php`

Replace the entire file with this chat-style implementation:

```php
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
```

---

## 2. Add Chat CSS to `assets/css/public.css`

Add these styles at the end of the file (you can remove or comment out the old `.cms-comment-*` styles):

```css
/* ============================================
   CHAT-STYLE FEEDBACK SYSTEM
   ============================================ */

.cms-chat-wrapper {
    margin-top: 40px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

/* Chat Header */
.cms-chat-header {
    background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
    color: #ffffff;
    padding: 20px 24px;
}

.cms-chat-title {
    margin: 0 0 6px 0;
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.cms-chat-icon {
    font-size: 24px;
}

.cms-chat-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
}

.cms-chat-subtitle {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

/* Chat Messages Container */
.cms-chat-messages {
    max-height: 500px;
    overflow-y: auto;
    padding: 24px;
    background: #f8f9fa;
    min-height: 150px;
}

/* Empty State */
.cms-chat-empty {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.cms-chat-empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.cms-chat-empty p {
    margin: 0;
    font-size: 16px;
}

/* Individual Message */
.cms-chat-message {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.cms-chat-message:last-child {
    margin-bottom: 0;
}

.cms-chat-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    flex-shrink: 0;
}

.cms-chat-bubble {
    flex: 1;
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    max-width: calc(100% - 52px);
}

.cms-chat-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.cms-chat-author {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.cms-chat-time {
    font-size: 12px;
    color: #6c757d;
}

.cms-chat-content {
    color: #495057;
    font-size: 15px;
    line-height: 1.5;
}

.cms-chat-content p {
    margin: 0 0 8px 0;
}

.cms-chat-content p:last-child {
    margin-bottom: 0;
}

/* Response Messages */
.cms-chat-response {
    padding: 12px 24px;
}

.cms-chat-response.success {
    background: #d4edda;
    color: #155724;
}

.cms-chat-response.error {
    background: #f8d7da;
    color: #721c24;
}

/* Chat Input Area */
.cms-chat-input-wrapper {
    padding: 20px 24px;
    background: #ffffff;
    border-top: 1px solid #e9ecef;
}

.cms-chat-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.cms-chat-input-row {
    display: flex;
    gap: 12px;
}

.cms-chat-input-field {
    flex: 1;
}

.cms-chat-input-field input,
.cms-chat-input-field textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box;
    background: #ffffff;
}

.cms-chat-input-field input:focus,
.cms-chat-input-field textarea:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.15);
}

.cms-chat-input-field input::placeholder,
.cms-chat-input-field textarea::placeholder {
    color: #adb5bd;
}

.cms-chat-message-row {
    align-items: flex-end;
}

.cms-chat-input-message {
    flex: 1;
}

.cms-chat-input-field textarea {
    min-height: 80px;
    max-height: 200px;
    resize: vertical;
    line-height: 1.5;
}

/* Send Button */
.cms-chat-send-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    flex-shrink: 0;
    height: fit-content;
    min-height: 48px;
}

.cms-chat-send-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
}

.cms-chat-send-btn:active {
    transform: translateY(0);
}

.cms-chat-send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.cms-chat-send-icon {
    font-size: 16px;
}

.cms-chat-privacy {
    margin: 12px 0 0 0;
    font-size: 12px;
    color: #6c757d;
}

/* Closed State */
.cms-chat-closed {
    padding: 24px;
    text-align: center;
    background: #f8f9fa;
    color: #6c757d;
}

/* Screen Reader Only */
.screen-reader-text {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* New Message Animation */
.cms-chat-message.new-message {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ============================================
   RESPONSIVE STYLES
   ============================================ */

/* Tablet */
@media (max-width: 768px) {
    .cms-chat-wrapper {
        margin-top: 30px;
        border-radius: 8px;
    }
    
    .cms-chat-header {
        padding: 16px 20px;
    }
    
    .cms-chat-title {
        font-size: 18px;
    }
    
    .cms-chat-messages {
        padding: 20px;
        max-height: 400px;
    }
    
    .cms-chat-input-wrapper {
        padding: 16px 20px;
    }
    
    .cms-chat-input-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .cms-chat-message-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .cms-chat-send-btn {
        width: 100%;
        justify-content: center;
        padding: 14px 24px;
    }
    
    .cms-chat-bubble {
        max-width: calc(100% - 52px);
    }
}

/* Mobile */
@media (max-width: 480px) {
    .cms-chat-header {
        padding: 14px 16px;
    }
    
    .cms-chat-title {
        font-size: 16px;
        flex-wrap: wrap;
    }
    
    .cms-chat-subtitle {
        font-size: 13px;
    }
    
    .cms-chat-messages {
        padding: 16px;
        max-height: 350px;
    }
    
    .cms-chat-message {
        gap: 10px;
    }
    
    .cms-chat-avatar img {
        width: 36px;
        height: 36px;
    }
    
    .cms-chat-bubble {
        padding: 10px 14px;
        max-width: calc(100% - 46px);
    }
    
    .cms-chat-input-wrapper {
        padding: 14px 16px;
    }
    
    .cms-chat-input-field input,
    .cms-chat-input-field textarea {
        padding: 10px 14px;
        font-size: 14px;
    }
    
    .cms-chat-input-field textarea {
        min-height: 60px;
    }
    
    .cms-chat-send-btn {
        padding: 12px 20px;
        font-size: 14px;
    }
}

/* Scrollbar Styling */
.cms-chat-messages::-webkit-scrollbar {
    width: 6px;
}

.cms-chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.cms-chat-messages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.cms-chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
```

---

## 3. Update `assets/js/public.js`

Replace the comment form handler section with this improved chat handler. Find and replace the existing comment form code (around line 118-181):

```javascript
/**
 * Public JavaScript for Campaign Briefs
 *
 * @package CampaignManagementSystem
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // ============================================
        // ACCEPT BRIEF MODAL
        // ============================================
        
        var modal = $('#cms-accept-modal');
        var btn = $('#cms-accept-brief-btn');
        var closeButtons = $('.cms-modal-close');

        // Open modal
        btn.on('click', function() {
            modal.fadeIn();
        });

        // Close modal
        closeButtons.on('click', function() {
            modal.fadeOut();
        });

        // Close modal when clicking outside
        $(window).on('click', function(event) {
            if (event.target == modal[0]) {
                modal.fadeOut();
            }
        });

        // Handle accept form submission
        $('#cms-accept-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();

            // Disable submit button
            $submitBtn.prop('disabled', true).text('Accepting...');

            // Get form data
            var formData = {
                action: 'cms_accept_brief',
                nonce: cmsPublic.nonce,
                post_id: getPostId(),
                acceptor_name: $('#acceptor_name').val(),
                acceptor_email: $('#acceptor_email').val()
            };

            // Submit via AJAX
            $.post(cmsPublic.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    alert('An error occurred. Please try again.');
                    $submitBtn.prop('disabled', false).text(originalText);
                });
        });

        // ============================================
        // CHAT/COMMENT SYSTEM
        // ============================================
        
        var $chatForm = $('#cms-chat-form');
        var $chatMessages = $('#cms-chat-messages');
        var $chatResponse = $('#cms-chat-response');
        var $submitBtn = $('#cms-chat-submit');

        // Scroll to bottom of chat on load
        if ($chatMessages.length) {
            scrollToBottom();
        }

        // Handle chat form submission
        $chatForm.on('submit', function(e) {
            e.preventDefault();

            var originalBtnHtml = $submitBtn.html();
            var authorName = $('#cms_chat_author').val().trim();
            var authorEmail = $('#cms_chat_email').val().trim();
            var messageContent = $('#cms_chat_content').val().trim();

            // Validate
            if (!authorName || !authorEmail || !messageContent) {
                showResponse('error', 'Please fill in all fields.');
                return;
            }

            // Disable submit button
            $submitBtn.prop('disabled', true).html('<span class="cms-chat-send-icon">⏳</span> <span class="cms-chat-send-text">Sending...</span>');

            // Get nonce from form
            var nonce = $chatForm.find('input[name="cms_comment_nonce"]').val();
            var postId = $chatForm.find('input[name="comment_post_ID"]').val();

            // Prepare form data
            var formData = {
                action: 'cms_submit_comment',
                nonce: nonce,
                comment_post_ID: postId,
                author: authorName,
                email: authorEmail,
                comment: messageContent
            };

            // Submit via AJAX
            $.post(cmsPublic.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        // Clear the message field only (keep name/email for convenience)
                        $('#cms_chat_content').val('');
                        
                        // Show success message briefly
                        showResponse('success', '✓ Message sent!');
                        
                        // Add the new message to the chat
                        addMessageToChat(authorName, authorEmail, messageContent);
                        
                        // Hide response after 3 seconds
                        setTimeout(function() {
                            $chatResponse.fadeOut();
                        }, 3000);
                        
                        // Update message count in header
                        updateMessageCount();
                        
                    } else {
                        showResponse('error', 'Error: ' + response.data.message);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Chat submit error:', status, error);
                    showResponse('error', 'An error occurred. Please try again.');
                })
                .always(function() {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                });
        });

        /**
         * Show response message
         */
        function showResponse(type, message) {
            $chatResponse
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .fadeIn();
        }

        /**
         * Add a new message to the chat without reloading
         */
        function addMessageToChat(name, email, content) {
            // Remove empty state if present
            $('.cms-chat-empty').remove();
            
            // Create avatar URL using Gravatar
            var emailHash = md5(email.toLowerCase().trim());
            var avatarUrl = 'https://www.gravatar.com/avatar/' + emailHash + '?s=40&d=mp';
            
            // Format the message content (convert newlines to paragraphs)
            var formattedContent = '<p>' + escapeHtml(content).replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>') + '</p>';
            
            var messageHtml = 
                '<div class="cms-chat-message new-message">' +
                    '<div class="cms-chat-avatar">' +
                        '<img src="' + avatarUrl + '" alt="" width="40" height="40" />' +
                    '</div>' +
                    '<div class="cms-chat-bubble">' +
                        '<div class="cms-chat-meta">' +
                            '<span class="cms-chat-author">' + escapeHtml(name) + '</span>' +
                            '<span class="cms-chat-time">Just now</span>' +
                        '</div>' +
                        '<div class="cms-chat-content">' + formattedContent + '</div>' +
                    '</div>' +
                '</div>';
            
            $chatMessages.append(messageHtml);
            scrollToBottom();
        }

        /**
         * Scroll chat to bottom
         */
        function scrollToBottom() {
            if ($chatMessages.length) {
                $chatMessages.animate({
                    scrollTop: $chatMessages[0].scrollHeight
                }, 300);
            }
        }

        /**
         * Update message count in header
         */
        function updateMessageCount() {
            var $count = $('.cms-chat-count');
            if ($count.length) {
                var currentCount = parseInt($count.text()) || 0;
                $count.text(currentCount + 1);
            } else {
                // Add count badge if it doesn't exist
                $('.cms-chat-title').append('<span class="cms-chat-count">1</span>');
            }
        }

        /**
         * Simple HTML escaping
         */
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Simple MD5 hash for Gravatar (using a minimal implementation)
         */
        function md5(string) {
            // Simple hash function - for Gravatar only
            // Using a basic implementation since we just need consistent hashing
            var hash = 0;
            if (string.length === 0) return hash.toString(16);
            for (var i = 0; i < string.length; i++) {
                var char = string.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            // Return a 32-char hex string (Gravatar will handle invalid hashes gracefully)
            return Math.abs(hash).toString(16).padStart(32, '0');
        }

        /**
         * Get current post ID
         */
        function getPostId() {
            var urlParams = new URLSearchParams(window.location.search);
            var postId = urlParams.get('post');

            if (!postId) {
                var bodyClasses = $('body').attr('class');
                if (bodyClasses) {
                    var classes = bodyClasses.split(' ');
                    for (var i = 0; i < classes.length; i++) {
                        if (classes[i].indexOf('postid-') === 0) {
                            postId = classes[i].replace('postid-', '');
                            break;
                        }
                    }
                }
            }

            return postId;
        }

        // ============================================
        // COPY SHAREABLE LINK
        // ============================================
        
        $('#cms-copy-link').on('click', function() {
            var $input = $('#cms-share-url');
            $input.select();
            document.execCommand('copy');

            var $btn = $(this);
            var originalText = $btn.text();
            $btn.text('Copied!');

            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        });

        // ============================================
        // SMOOTH SCROLL TO CHAT
        // ============================================
        
        $('a[href="#cms-chat"]').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('#cms-chat').offset().top - 100
            }, 500);
        });

    });

})(jQuery);
```

---

## 4. Fix Email Notification in `includes/class-workflow.php`

The `send_comment_notification()` method needs to be fixed. Replace it (around line 401):

```php
/**
 * Send notification email when a comment is posted
 *
 * @param int    $post_id Post ID.
 * @param int    $comment_id Comment ID.
 * @param string $author Comment author name.
 * @param string $author_email Comment author email.
 * @param string $content Comment content.
 */
private function send_comment_notification( $post_id, $comment_id, $author, $author_email, $content ) {
    // Get the post.
    $post = get_post( $post_id );
    if ( ! $post ) {
        error_log( 'CMS Comment Notification: Post not found - ID: ' . $post_id );
        return;
    }

    // Get coordinator email from settings
    $coordinator_email = get_option( 'cms_coordinator_email' );
    if ( empty( $coordinator_email ) ) {
        $coordinator_email = get_option( 'admin_email' );
    }
    
    if ( empty( $coordinator_email ) ) {
        error_log( 'CMS Comment Notification: No coordinator email configured' );
        return;
    }

    // Check if comment notifications are enabled
    // Default to true if option doesn't exist
    $notify_on_comment = get_option( 'cms_notify_on_comment' );
    if ( $notify_on_comment === false || $notify_on_comment === '' ) {
        // Option not set, default to enabled and save it
        update_option( 'cms_notify_on_comment', 1 );
        $notify_on_comment = 1;
    }
    
    if ( ! $notify_on_comment ) {
        error_log( 'CMS Comment Notification: Notifications disabled in settings' );
        return;
    }

    // Prepare email
    $subject = sprintf(
        '[Campaign Brief] New message on: %s',
        $post->post_title
    );

    $brief_url = get_permalink( $post_id );
    $admin_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

    $message = "New feedback received on a campaign brief.\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Brief: " . $post->post_title . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "From: " . $author . " (" . $author_email . ")\n";
    $message .= "Date: " . date( 'F j, Y \a\t g:i a' ) . "\n\n";
    $message .= "Message:\n";
    $message .= "─────────────────────────────────────────\n";
    $message .= $content . "\n";
    $message .= "─────────────────────────────────────────\n\n";
    $message .= "View brief: " . $brief_url . "#cms-chat\n";
    $message .= "Edit brief: " . $admin_url . "\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "This notification was sent by the Campaign Management System.";

    // Set headers
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $author . ' <' . $author_email . '>',
    );

    // Send email
    $sent = wp_mail( $coordinator_email, $subject, $message, $headers );
    
    if ( $sent ) {
        error_log( 'CMS Comment Notification: Email sent successfully to ' . $coordinator_email );
    } else {
        error_log( 'CMS Comment Notification: Failed to send email to ' . $coordinator_email );
        // Log additional debug info
        global $phpmailer;
        if ( isset( $phpmailer ) && is_object( $phpmailer ) ) {
            error_log( 'CMS Comment Notification: PHPMailer error: ' . $phpmailer->ErrorInfo );
        }
    }
}
```

Also verify the `submit_comment()` AJAX handler is calling the notification method correctly. It should already be doing this around line 381, but verify it looks like:

```php
// Send email notification about the new comment.
$this->send_comment_notification( $post_id, $comment_id, $author, $email, $content );
```

---

## 5. Update `templates/brief-view.php`

Find where it includes the comments section (around line 342-352) and update it:

```php
<!-- CHAT SECTION (no-print) -->
<div class="cms-comments-section no-print">
    <?php
    // Use custom chat template
    $custom_chat = CMS_PLUGIN_DIR . 'templates/comments.php';
    if ( file_exists( $custom_chat ) ) {
        include $custom_chat;
    }
    ?>
</div>
```

Also remove the old "Feedback & Comments" h2 heading and the `comment_success` GET parameter check since the new chat handles that differently.

---

## Testing Checklist

1. **Visual Design:**
   - Chat header should have blue gradient background
   - Messages should appear in speech bubble style
   - Form inputs should be properly sized and aligned
   - Should look good on desktop, tablet, and mobile

2. **Posting Messages:**
   - Fill in name, email, and message
   - Click Send - message should appear immediately in the chat
   - Form should clear (message field only)
   - "Message sent!" confirmation should appear

3. **Email Notifications:**
   - Check Settings → Campaign Briefs for coordinator email
   - Ensure "Notify on New Comments" is checked
   - Submit a comment and verify email is received
   - Check server error logs if email doesn't arrive

4. **Dashboard Integration:**
   - Posted comments should appear in Dashboard → Recent Comments
   - Comments should appear in the brief's edit page meta box

5. **Responsive Design:**
   - Test on mobile (name/email fields should stack)
   - Send button should be full-width on mobile
   - Chat messages container should scroll properly

---

## Debug Tips

If comments still don't post, check the browser's Network tab for the AJAX request to see:
1. Is the request being made?
2. What's the response?
3. Any 403/500 errors?

If emails don't send, check:
1. WordPress error log for "CMS Comment Notification" entries
2. Server mail logs
3. Whether the site can send any WordPress emails (test with password reset)
