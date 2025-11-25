/**
 * Campaign Brief Public JavaScript
 * Includes JS-rendered chat system with inline styles
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

        btn.on('click', function() {
            modal.fadeIn();
        });

        closeButtons.on('click', function() {
            modal.fadeOut();
        });

        $(window).on('click', function(event) {
            if (event.target == modal[0]) {
                modal.fadeOut();
            }
        });

        $('#cms-accept-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Accepting...');

            var formData = {
                action: 'cms_accept_brief',
                nonce: cmsPublic.nonce,
                post_id: cmsPublic.briefId,
                acceptor_name: $('#acceptor_name').val(),
                acceptor_email: $('#acceptor_email').val()
            };

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
        // CHAT SYSTEM - JS RENDERED WITH INLINE STYLES
        // ============================================

        var ChatSystem = {
            container: null,
            messages: [],

            // All styles defined here - completely independent of theme
            styles: {
                wrapper: [
                    'font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    'background: #ffffff',
                    'border-radius: 12px',
                    'box-shadow: 0 2px 12px rgba(0,0,0,0.1)',
                    'overflow: hidden',
                    'margin-top: 30px'
                ].join(';'),

                header: [
                    'background: linear-gradient(135deg, #0073aa 0%, #005a87 100%)',
                    'color: #ffffff',
                    'padding: 20px 24px'
                ].join(';'),

                headerTitle: [
                    'margin: 0 0 6px 0',
                    'font-size: 20px',
                    'font-weight: 600',
                    'display: flex',
                    'align-items: center',
                    'gap: 10px'
                ].join(';'),

                headerSubtitle: [
                    'margin: 0',
                    'font-size: 14px',
                    'opacity: 0.9'
                ].join(';'),

                badge: [
                    'background: rgba(255,255,255,0.2)',
                    'padding: 2px 10px',
                    'border-radius: 12px',
                    'font-size: 14px'
                ].join(';'),

                messagesContainer: [
                    'max-height: 450px',
                    'overflow-y: auto',
                    'padding: 24px',
                    'background: #f8f9fa',
                    'min-height: 120px'
                ].join(';'),

                emptyState: [
                    'text-align: center',
                    'padding: 40px 20px',
                    'color: #6c757d'
                ].join(';'),

                emptyIcon: [
                    'font-size: 48px',
                    'margin-bottom: 12px',
                    'opacity: 0.5'
                ].join(';'),

                message: [
                    'display: flex',
                    'gap: 12px',
                    'margin-bottom: 20px'
                ].join(';'),

                avatar: [
                    'width: 40px',
                    'height: 40px',
                    'border-radius: 50%',
                    'background: #dee2e6',
                    'display: flex',
                    'align-items: center',
                    'justify-content: center',
                    'font-weight: 600',
                    'color: #495057',
                    'font-size: 16px',
                    'flex-shrink: 0'
                ].join(';'),

                bubble: [
                    'flex: 1',
                    'background: #ffffff',
                    'border-radius: 12px',
                    'padding: 12px 16px',
                    'box-shadow: 0 1px 3px rgba(0,0,0,0.08)'
                ].join(';'),

                meta: [
                    'display: flex',
                    'align-items: center',
                    'gap: 10px',
                    'margin-bottom: 6px',
                    'flex-wrap: wrap'
                ].join(';'),

                authorName: [
                    'font-weight: 600',
                    'color: #333',
                    'font-size: 14px'
                ].join(';'),

                time: [
                    'font-size: 12px',
                    'color: #6c757d'
                ].join(';'),

                content: [
                    'color: #495057',
                    'font-size: 15px',
                    'line-height: 1.5'
                ].join(';'),

                inputWrapper: [
                    'padding: 20px 24px',
                    'background: #ffffff',
                    'border-top: 1px solid #e9ecef'
                ].join(';'),

                inputRow: [
                    'display: flex',
                    'gap: 12px',
                    'margin-bottom: 12px',
                    'flex-wrap: wrap'
                ].join(';'),

                inputField: [
                    'flex: 1',
                    'min-width: 200px'
                ].join(';'),

                input: [
                    'width: 100%',
                    'padding: 12px 16px',
                    'border: 2px solid #e9ecef',
                    'border-radius: 8px',
                    'font-size: 15px',
                    'font-family: inherit',
                    'box-sizing: border-box',
                    'transition: border-color 0.2s'
                ].join(';'),

                textarea: [
                    'width: 100%',
                    'padding: 12px 16px',
                    'border: 2px solid #e9ecef',
                    'border-radius: 8px',
                    'font-size: 15px',
                    'font-family: inherit',
                    'box-sizing: border-box',
                    'min-height: 80px',
                    'resize: vertical',
                    'transition: border-color 0.2s'
                ].join(';'),

                messageRow: [
                    'display: flex',
                    'gap: 12px',
                    'align-items: flex-end'
                ].join(';'),

                sendBtn: [
                    'padding: 12px 28px',
                    'background: linear-gradient(135deg, #0073aa 0%, #005a87 100%)',
                    'color: #ffffff',
                    'border: none',
                    'border-radius: 8px',
                    'font-size: 15px',
                    'font-weight: 600',
                    'cursor: pointer',
                    'transition: transform 0.2s, box-shadow 0.2s',
                    'white-space: nowrap'
                ].join(';'),

                sendBtnHover: [
                    'transform: translateY(-1px)',
                    'box-shadow: 0 4px 12px rgba(0,115,170,0.3)'
                ].join(';'),

                sendBtnDisabled: [
                    'opacity: 0.6',
                    'cursor: not-allowed'
                ].join(';'),

                privacy: [
                    'margin: 12px 0 0 0',
                    'font-size: 12px',
                    'color: #6c757d'
                ].join(';'),

                response: [
                    'padding: 12px 16px',
                    'border-radius: 6px',
                    'margin-bottom: 12px',
                    'display: none'
                ].join(';'),

                responseSuccess: [
                    'background: #d4edda',
                    'color: #155724'
                ].join(';'),

                responseError: [
                    'background: #f8d7da',
                    'color: #721c24'
                ].join(';')
            },

            init: function() {
                this.container = $('#cms-chat-container');
                if (!this.container.length) return;

                this.messages = cmsPublic.messages || [];
                this.render();
                this.bindEvents();
            },

            render: function() {
                var self = this;
                var messageCount = this.messages.length;

                var html = '<div id="cms-chat-wrapper" style="' + this.styles.wrapper + '">';

                // Header
                html += '<div style="' + this.styles.header + '">';
                html += '<h3 style="' + this.styles.headerTitle + '">';
                html += '<span>💬</span> Feedback & Discussion';
                if (messageCount > 0) {
                    html += '<span style="' + this.styles.badge + '">' + messageCount + '</span>';
                }
                html += '</h3>';
                html += '<p style="' + this.styles.headerSubtitle + '">Discuss this campaign brief with the communications team.</p>';
                html += '</div>';

                // Messages
                html += '<div id="cms-chat-messages" style="' + this.styles.messagesContainer + '">';
                if (messageCount === 0) {
                    html += '<div id="cms-empty-state" style="' + this.styles.emptyState + '">';
                    html += '<div style="' + this.styles.emptyIcon + '">💭</div>';
                    html += '<p style="margin:0">No messages yet. Start the conversation!</p>';
                    html += '</div>';
                } else {
                    this.messages.forEach(function(msg) {
                        html += self.renderMessage(msg);
                    });
                }
                html += '</div>';

                // Response area
                html += '<div id="cms-chat-response" style="' + this.styles.response + '"></div>';

                // Input form
                html += '<div style="' + this.styles.inputWrapper + '">';
                html += '<form id="cms-chat-form">';

                // Name and email row
                html += '<div style="' + this.styles.inputRow + '">';
                html += '<div style="' + this.styles.inputField + '">';
                html += '<input type="text" id="cms-msg-name" placeholder="Your Name *" required style="' + this.styles.input + '">';
                html += '</div>';
                html += '<div style="' + this.styles.inputField + '">';
                html += '<input type="email" id="cms-msg-email" placeholder="Your Email *" required style="' + this.styles.input + '">';
                html += '</div>';
                html += '</div>';

                // Message row
                html += '<div style="' + this.styles.messageRow + '">';
                html += '<div style="flex:1">';
                html += '<textarea id="cms-msg-content" placeholder="Type your message..." required style="' + this.styles.textarea + '"></textarea>';
                html += '</div>';
                html += '<button type="submit" id="cms-send-btn" style="' + this.styles.sendBtn + '">Send ➤</button>';
                html += '</div>';

                html += '</form>';
                html += '<p style="' + this.styles.privacy + '">Your email will not be published. Messages are visible to the communications team.</p>';
                html += '</div>';

                html += '</div>';

                this.container.html(html);
                this.scrollToBottom();
            },

            renderMessage: function(msg) {
                var initial = msg.author_name.charAt(0).toUpperCase();

                var html = '<div class="cms-chat-msg" style="' + this.styles.message + '">';
                html += '<div style="' + this.styles.avatar + '">' + initial + '</div>';
                html += '<div style="' + this.styles.bubble + '">';
                html += '<div style="' + this.styles.meta + '">';
                html += '<span style="' + this.styles.authorName + '">' + msg.author_name + '</span>';
                html += '<span style="' + this.styles.time + '">' + msg.time_ago + '</span>';
                html += '</div>';
                html += '<div style="' + this.styles.content + '">' + msg.message + '</div>';
                html += '</div>';
                html += '</div>';

                return html;
            },

            bindEvents: function() {
                var self = this;

                // Form submission
                $(document).on('submit', '#cms-chat-form', function(e) {
                    e.preventDefault();
                    self.submitMessage();
                });

                // Input focus styling
                $(document).on('focus', '#cms-chat-form input, #cms-chat-form textarea', function() {
                    $(this).css('border-color', '#0073aa');
                    $(this).css('box-shadow', '0 0 0 3px rgba(0,115,170,0.15)');
                });

                $(document).on('blur', '#cms-chat-form input, #cms-chat-form textarea', function() {
                    $(this).css('border-color', '#e9ecef');
                    $(this).css('box-shadow', 'none');
                });

                // Send button hover
                $(document).on('mouseenter', '#cms-send-btn', function() {
                    if (!$(this).prop('disabled')) {
                        $(this).css('transform', 'translateY(-1px)');
                        $(this).css('box-shadow', '0 4px 12px rgba(0,115,170,0.3)');
                    }
                });

                $(document).on('mouseleave', '#cms-send-btn', function() {
                    $(this).css('transform', 'none');
                    $(this).css('box-shadow', 'none');
                });
            },

            submitMessage: function() {
                var self = this;
                var $btn = $('#cms-send-btn');
                var $response = $('#cms-chat-response');

                var name = $('#cms-msg-name').val().trim();
                var email = $('#cms-msg-email').val().trim();
                var message = $('#cms-msg-content').val().trim();

                if (!name || !email || !message) {
                    this.showResponse('error', 'Please fill in all fields.');
                    return;
                }

                // Disable button
                $btn.prop('disabled', true).text('Sending...');
                $btn.css('opacity', '0.6').css('cursor', 'not-allowed');

                $.ajax({
                    url: cmsPublic.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'cms_post_message',
                        nonce: cmsPublic.messagesNonce,
                        brief_id: cmsPublic.briefId,
                        author_name: name,
                        author_email: email,
                        message: message
                    },
                    success: function(response) {
                        if (response.success) {
                            // Add message to display
                            self.addMessage(response.data.data);

                            // Clear message field only
                            $('#cms-msg-content').val('');

                            // Show success
                            self.showResponse('success', '✓ Message sent!');

                            // Update count
                            self.updateCount();

                            // Hide response after delay
                            setTimeout(function() {
                                $response.fadeOut();
                            }, 3000);
                        } else {
                            self.showResponse('error', response.data.message || 'Failed to send message.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Message send error:', status, error);
                        self.showResponse('error', 'An error occurred. Please try again.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Send ➤');
                        $btn.css('opacity', '1').css('cursor', 'pointer');
                    }
                });
            },

            addMessage: function(msg) {
                // Remove empty state if present
                $('#cms-empty-state').remove();

                // Add message
                var html = this.renderMessage(msg);
                $('#cms-chat-messages').append(html);

                // Scroll to bottom
                this.scrollToBottom();

                // Add to array
                this.messages.push(msg);
            },

            showResponse: function(type, message) {
                var $response = $('#cms-chat-response');
                var bgColor = type === 'success' ? '#d4edda' : '#f8d7da';
                var textColor = type === 'success' ? '#155724' : '#721c24';

                $response.css({
                    'background': bgColor,
                    'color': textColor,
                    'display': 'block',
                    'padding': '12px 24px',
                    'margin': '0'
                }).text(message);
            },

            updateCount: function() {
                var $badge = $('#cms-chat-wrapper').find('h3 span:last');
                var count = this.messages.length;

                if ($badge.length && $badge.text().match(/^\d+$/)) {
                    $badge.text(count);
                } else {
                    $('#cms-chat-wrapper h3').append('<span style="' + this.styles.badge + '">' + count + '</span>');
                }
            },

            scrollToBottom: function() {
                var $container = $('#cms-chat-messages');
                if ($container.length) {
                    $container.scrollTop($container[0].scrollHeight);
                }
            }
        };

        // Initialize chat system
        if (typeof cmsPublic !== 'undefined' && cmsPublic.briefId) {
            ChatSystem.init();
        }

        // ============================================
        // COPY LINK FUNCTIONALITY
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

    });

})(jQuery);
