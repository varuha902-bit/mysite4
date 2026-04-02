jQuery(document).ready(function ($) {
    // Intercept the form submission
    $('#post').on('submit', function(e) {
        // Create a mock options object similar to what the block editor uses
        const options = {
            isAutosave: false,
            preview: false,
            publish: false
        };

        // Check for recurrence changes
        const hasRecurrenceChanges = window.evgeCheckRecurrenceChanges(options);
        console.log('Recurrence changes detected:', hasRecurrenceChanges);

        if (!hasRecurrenceChanges) {
            // No recurrence changes, proceed with normal save
            return true;
        }

        // Prevent form submission
        e.preventDefault();

        // Show the modal
        if ($('.evge-modal-placeholder').length) {
            $('body').append($('.evge-modal-backdrop'));
            $('body').append($('#evge-modal'));
            // Replace existing modal content
            $('.evge-modal-placeholder').replaceWith($('.evge-recurrence-modal-content'));
        } else {
            $('.evge-modal-evge-queue-processing-modal-content').remove();
            $('.evge-recurrence-modal-content').show();
            $('body').addClass('evge-modal-is-open');
        }

        // Set up modal close handlers
        $('.evge-modal-backdrop, .evge-action-modal-close, .evge-modal-close').on('click', function () {
            $('body').removeClass('evge-modal-is-open');
            // Trigger custom event for admin modal close
            $(document).trigger('evge_admin_modal_closed');
        });

        // Show the modal
        $('.evge-modal-placeholder').html($('.evge-queue-processing-modal-content').html());
        $('.evge-modal')
            .addClass('evge-narrow-max-width-modal evge-centered-modal evge-recurrence-modal')
            .removeClass('evge-medium-max-width-modal');
        $('body').addClass('evge-modal-is-open');

        // Handle continue button click
        $('.evge-continue-save').on('click', function () {
            $('body').removeClass('evge-modal-is-open');
            // Remove the submit handler to prevent infinite loop
            $('#post').off('submit');
            // Submit the form
            $('#post').submit();
        });

        // Handle cancel button click
        $('.evge-cancel-save, .evge-modal-close').on('click', function () {
            $('body').removeClass('evge-modal-is-open');
        });

        return false;
    });
});
