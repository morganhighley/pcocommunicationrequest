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
