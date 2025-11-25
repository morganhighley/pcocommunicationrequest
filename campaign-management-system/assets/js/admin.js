/**
 * Admin JavaScript for Campaign Management System
 *
 * @package CampaignManagementSystem
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Channel Plan: Add Row
		var channelRowIndex = $('.cms-channel-row').length;

		$('#cms-add-channel-row').on('click', function() {
			var newRow = '<tr class="cms-channel-row">' +
				'<td><input type="text" name="cms_channel_plan[' + channelRowIndex + '][channel]" value="" class="widefat" /></td>' +
				'<td><input type="text" name="cms_channel_plan[' + channelRowIndex + '][frequency]" value="" class="widefat" /></td>' +
				'<td><input type="text" name="cms_channel_plan[' + channelRowIndex + '][ideas]" value="" class="widefat" /></td>' +
				'<td><button type="button" class="button cms-remove-channel-row">×</button></td>' +
				'</tr>';

			$('#cms-channel-plan-rows').append(newRow);
			channelRowIndex++;
		});

		// Channel Plan: Remove Row
		$(document).on('click', '.cms-remove-channel-row', function() {
			if (confirm('Remove this channel?')) {
				$(this).closest('tr').remove();
			}
		});

		// Copy shareable link
		$('#cms-copy-link').on('click', function() {
			var $input = $('#cms-share-url');
			$input.select();
			$input[0].setSelectionRange(0, 99999); // For mobile devices

			try {
				document.execCommand('copy');
				var $btn = $(this);
				var originalText = $btn.text();
				$btn.text('Copied!').addClass('button-primary').removeClass('button-secondary');

				setTimeout(function() {
					$btn.text(originalText).removeClass('button-primary').addClass('button-secondary');
				}, 2000);
			} catch (err) {
				alert('Failed to copy. Please copy manually.');
			}
		});

		// Unlock brief
		$('#cms-unlock-brief').on('click', function() {
			if (!confirm('Unlocking this brief will require re-acceptance from the ministry leader. Continue?')) {
				return;
			}

			var $btn = $(this);
			var originalText = $btn.text();
			$btn.prop('disabled', true).text('Unlocking...');

			$.post(ajaxurl, {
				action: 'cms_unlock_brief',
				nonce: cmsAdmin.nonce,
				post_id: getPostId()
			})
			.done(function(response) {
				if (response.success) {
					alert(response.data.message);
					location.reload();
				} else {
					alert('Error: ' + response.data.message);
					$btn.prop('disabled', false).text(originalText);
				}
			})
			.fail(function() {
				alert('An error occurred. Please try again.');
				$btn.prop('disabled', false).text(originalText);
			});
		});

		// Unaccept brief (clear acceptance status)
		$('#cms-unaccept-brief').on('click', function() {
			if (!confirm('This will clear the acceptance status. The ministry leader will need to accept the brief again. Continue?')) {
				return;
			}

			var $btn = $(this);
			var originalText = $btn.text();
			$btn.prop('disabled', true).text('Clearing...');

			$.post(ajaxurl, {
				action: 'cms_unaccept_brief',
				nonce: cmsAdmin.nonce,
				post_id: getPostId()
			})
			.done(function(response) {
				if (response.success) {
					alert(response.data.message);
					location.reload();
				} else {
					alert('Error: ' + response.data.message);
					$btn.prop('disabled', false).text(originalText);
				}
			})
			.fail(function() {
				alert('An error occurred. Please try again.');
				$btn.prop('disabled', false).text(originalText);
			});
		});

		// Auto-generate slug from title
		$('#cms_campaign_title').on('blur', function() {
			var title = $(this).val();
			var $slugField = $('#cms_campaign_slug');

			// Only auto-generate if slug field is empty
			if (title && !$slugField.val()) {
				var slug = title
					.toLowerCase()
					.replace(/[^\w\s-]/g, '') // Remove special characters
					.replace(/\s+/g, '-')      // Replace spaces with hyphens
					.replace(/--+/g, '-')      // Replace multiple hyphens with single
					.trim();

				$slugField.val(slug);
			}
		});

		// Warn before leaving if form is dirty
		var formChanged = false;

		$('#post').on('change', 'input, textarea, select', function() {
			formChanged = true;
		});

		$('#publish, #save-post').on('click', function() {
			formChanged = false;
		});

		$(window).on('beforeunload', function() {
			if (formChanged) {
				return 'You have unsaved changes. Are you sure you want to leave?';
			}
		});

		// Quick status change buttons (added via PHP in workflow class)
		// Already handled by PHP-generated JavaScript

		// Enhance taxonomy selectors
		$('select[name="tax_input[service_level][]"]').on('change', function() {
			var level = $(this).val();
			var $notice = $('.cms-service-level-notice');

			if ($notice.length === 0) {
				$notice = $('<div class="cms-notice cms-service-level-notice"></div>');
				$(this).after($notice);
			}

			var messages = {
				'green': 'Green Campaign: 8-week lead time, basic creative package',
				'blue': 'Blue Campaign: 10-week lead time, includes web strategy & photography',
				'black': 'Black Campaign: 12-week lead time, full service with print & film'
			};

			if (messages[level]) {
				$notice.text(messages[level]).show();
			} else {
				$notice.hide();
			}
		});

		/**
		 * Get current post ID
		 */
		function getPostId() {
			return $('#post_ID').val() || 0;
		}

		// Initialize tooltips (if using a tooltip library)
		$('[data-tooltip]').each(function() {
			var $this = $(this);
			$this.attr('title', $this.data('tooltip'));
		});

		// Dashboard: Refresh stats periodically (every 5 minutes)
		if ($('.cms-dashboard-stats').length > 0) {
			setInterval(function() {
				// Optionally refresh dashboard stats via AJAX
				// This is a future enhancement
			}, 300000);
		}

		// Table row highlighting on hover
		$('.cms-dashboard-section table tbody tr').on('mouseenter', function() {
			$(this).css('background-color', '#f6f7f7');
		}).on('mouseleave', function() {
			$(this).css('background-color', '');
		});

		// Make table rows clickable
		$('.cms-dashboard-section table tbody tr').on('click', function(e) {
			if ($(e.target).is('button') || $(e.target).is('a')) {
				return; // Don't interfere with buttons and links
			}

			var $link = $(this).find('a').first();
			if ($link.length > 0) {
				window.location.href = $link.attr('href');
			}
		});

		// Add search/filter functionality to dashboard
		$('#cms-dashboard-search').on('keyup', function() {
			var searchText = $(this).val().toLowerCase();

			$('.cms-dashboard-section table tbody tr').each(function() {
				var rowText = $(this).text().toLowerCase();
				if (rowText.indexOf(searchText) > -1) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		});

		// Auto-save draft periodically
		if ($('#post_type').val() === 'campaign_brief') {
			// WordPress already handles auto-save, but we can add custom logic here if needed
		}
	});

})(jQuery);
