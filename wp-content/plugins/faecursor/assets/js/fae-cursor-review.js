/**
 * FaeCursor Review Notification Handler
 * Manages review request slide-in notification
 */

(function ($) {
  "use strict";

  const FaeReview = {
    /**
     * Initialize review notification
     */
    init: function() {
      this.showNotification();
      this.bindEvents();
    },

    /**
     * Show notification after 5 seconds
     */
    showNotification: function() {
      setTimeout(function() {
        const $notification = $('#fae-review-notification');
        if ($notification.length) {
          $notification.addClass('fae-show');
        }
      }, 5000); // 5 seconds delay
    },

    /**
     * Bind button click events
     */
    bindEvents: function() {
      const self = this;

      // X button - Permanent dismiss
      $('#fae-review-notification-dismiss').on('click', function() {
        self.sendAction('dismissed');
      });

      // Maybe Later button - Remind in 3 days
      $('#fae-review-notification-later').on('click', function() {
        self.sendAction('later');
      });

      // Leave a Review button - Mark as reviewed
      $('#fae-review-btn').on('click', function() {
        self.sendAction('reviewed');
      });
    },

    /**
     * Send review action to server
     * @param {string} action - Action type: 'reviewed', 'later', or 'dismissed'
     */
    sendAction: function(action) {
      const $notification = $('#fae-review-notification');
      const nonce = $notification.data('nonce');

      // Hide notification immediately for better UX
      $notification.removeClass('fae-show');

      // Send to server
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'fae_review_action',
          review_action: action,
          nonce: nonce
        },
        success: function(response) {
          if (response.success && response.data.message) {
            console.log('FaeCursor Review:', response.data.message);
          }
        },
        error: function(xhr, status, error) {
          console.error('FaeCursor Review Error:', error);
        }
      });
    }
  };

  // Initialize when document is ready
  $(document).ready(function () {
    FaeReview.init();
  });

})(jQuery);
