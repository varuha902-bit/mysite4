/**
 * Front-end Admin Notices JavaScript
 * 
 * Minimal, efficient JavaScript for handling admin notices on the front-end.
 * Only loads for users with appropriate capabilities.
 */

jQuery(function($) {
	'use strict';

	// Initialize front-end admin notices
	window.EVGE = window.EVGE || {};
	window.EVGE.FrontendNotices = window.EVGE.FrontendNotices || {};

	/**
	 * Front-end Admin Notices Handler
	 */
	window.EVGE.FrontendNotices.Handler = {
		/**
		 * Initialize the notice handler
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Handle dismiss button clicks
			$(document).on('click', '.evge-frontend-notice-dismiss', this.handleDismiss.bind(this));
		},

		/**
		 * Handle notice dismiss
		 * 
		 * @param {Event} e Click event
		 */
		handleDismiss: function(e) {
			e.preventDefault();
			
			var $notice = $(e.target).closest('.evge-frontend-notice');
			var noticeId = $notice.data('notice-id');
			
			if (!noticeId) {
				console.warn('EVGE Frontend Notices: No notice ID found');
				return;
			}

			// Add dismissing class for animation
			$notice.addClass('evge-dismissing');
			
			// Disable the button to prevent double-clicks
			var $button = $(e.target).closest('.evge-frontend-notice-dismiss');
			$button.prop('disabled', true);
			
			// Send AJAX request to dismiss the notice
			this.dismissNotice(noticeId, $notice);
		},

		/**
		 * Send AJAX request to dismiss notice
		 * 
		 * @param {string} noticeId The notice ID to dismiss
		 * @param {jQuery} $notice The notice element
		 */
		dismissNotice: function(noticeId, $notice) {
			$.ajax({
				url: evgeFrontendNotices.ajaxUrl,
				type: 'POST',
				data: {
					action: 'evge_frontend_notice_dismiss',
					notice_id: noticeId,
					nonce: evgeFrontendNotices.nonce
				},
				success: function(response) {
					if (response.success) {
						// Remove the notice after animation completes
						setTimeout(function() {
							$notice.remove();
							
							// If no more notices, remove the container
							if ($('.evge-frontend-notice').length === 0) {
								$('#evge-frontend-admin-notices').remove();
							}
						}, 300); // Match animation duration
					} else {
						// Re-enable button and remove dismissing class on error
						$notice.removeClass('evge-dismissing');
						$notice.find('.evge-frontend-notice-dismiss').prop('disabled', false);
						console.error('EVGE Frontend Notices: Failed to dismiss notice', response);
					}
				},
				error: function(xhr, status, error) {
					// Re-enable button and remove dismissing class on error
					$notice.removeClass('evge-dismissing');
					$notice.find('.evge-frontend-notice-dismiss').prop('disabled', false);
					console.error('EVGE Frontend Notices: AJAX error dismissing notice', error);
				}
			});
		}
	};

	// Initialize the handler when DOM is ready
	$(document).ready(function() {
		// Only initialize if notices exist
		if ($('#evge-frontend-admin-notices').length > 0) {
			window.EVGE.FrontendNotices.Handler.init();
		}
	});

}); 