/**
 * Public JavaScript for Campaign Briefs
 *
 * @package CampaignManagementSystem
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Accept Brief Modal
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
						// Show success message
						alert(response.data.message);

						// Reload page to show acceptance status
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

		// Copy shareable link (if on admin side)
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

		/**
		 * Get current post ID from URL or body class
		 */
		function getPostId() {
			// Try to get from URL parameter
			var urlParams = new URLSearchParams(window.location.search);
			var postId = urlParams.get('post');

			// If not in URL, try to get from body class
			if (!postId) {
				var bodyClasses = $('body').attr('class').split(' ');
				for (var i = 0; i < bodyClasses.length; i++) {
					if (bodyClasses[i].indexOf('postid-') === 0) {
						postId = bodyClasses[i].replace('postid-', '');
						break;
					}
				}
			}

			return postId;
		}

		// Smooth scroll to comments
		$('a[href="#comments"]').on('click', function(e) {
			e.preventDefault();
			$('html, body').animate({
				scrollTop: $('.cms-comments-section').offset().top - 100
			}, 500);
		});
	});

})(jQuery);
