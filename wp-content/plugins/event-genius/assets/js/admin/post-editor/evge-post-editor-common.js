jQuery(document).ready(function ($) {
    /**
     * Checks if there are any recurrence changes that need special handling
     * @param {Object} options - The save options
     * @returns {boolean} - Returns true if special handling is needed, false otherwise
     */
    window.evgeCheckRecurrenceChanges = function () {
        // Check for recurrence changes
        const originalRecurrenceType = $('.evge-original-recurrence-type').text();
        const originalRecurrenceEndDate = $('.evge-original-recurrence-end-date').text();

        const currentRecurrenceType = $('#evge_recurrence_type').val();
        const currentRecurrenceEndDate = $('#evge_recurrence_end_date').val();

        if (originalRecurrenceType === 'none') {
            return false;
        }

        if (currentRecurrenceType === 'none' && originalRecurrenceType === 'none') {
            return false;
        }

        // if either value is empty, as in empty string or null, continue with save
        if (currentRecurrenceType === '' || originalRecurrenceType === '') {
            return false;
        }

        if (currentRecurrenceType !== originalRecurrenceType
            || currentRecurrenceEndDate !== originalRecurrenceEndDate) {
            $('.evge-recurring-warning-registration-text').show();
            return true;
        } else {
            $('.evge-recurring-warning-registration-text').hide();
            return false;
        }
    };

    /**
     * Shows the queue processing modal and handles its functionality
     * @param {Object} response - The AJAX response containing queue processing data
     */
    window.evgeShowQueueProcessingModal = function(response) {
        if (response.data.pending_tasks > 0) {
            // Append the modal HTML to the body if it doesn't exist yet
            if ($('.evge-modal-content').length) {
                $('.evge-recurrence-modal-content').hide();
                // create a div and add the modal html to it
                var $modalDiv = $('<div class="evge-modal-evge-queue-processing-modal-content"></div>');
                $modalDiv.append(response.data.modal_html);
                $('.evge-modal-content').append($modalDiv);
            } else {
                console.error('Modal not found');
            }
            // Show the modal
            $('.evge-modal')
                .addClass('evge-narrow-max-width-modal evge-centered-modal evge-recurrence-modal')
                .removeClass('evge-medium-max-width-modal');
            $('body').addClass('evge-modal-is-open');

            // Set up close button handlers
            $('.evge-modal-backdrop, .evge-action-modal-close, .evge-modal-close').on('click', function () {
                $('body').removeClass('evge-modal-is-open');
            });
            $('.evge-total-count')
                .text(response.data.total_events)
                .attr('data-total-events', response.data.total_events);
            $('.evge-processed-count').text(response.data.processed_events || 0);

            const progress = response.data.total_events > 0
                ? ((response.data.processed_events || 0) / response.data.total_events) * 100
                : 0;
            $('.evge-progress-bar').css('width', progress + '%');

            if (typeof window.evgeProcessQueueBatch === 'function') {
                if (response.data.is_creation) {
                    $('.evge-undo-recurrence-create').show();
                } else {
                    $('.evge-undo-recurrence-create').hide();
                }
                window.evgeProcessQueueBatch(1, response.data.estimated_batches || 30, response.data.is_creation, response.data.series_id);
            }
        } else {
            $('body').removeClass('evge-modal-is-open');
        }
    };

    // Initialize venue and organizer tools
    window.evgeInitVenueOrganizerTools = function () {
        // Handle quick create buttons
        $('.evge-create-new:not(.evge-initialized)').on('click', function (event) {
            event.preventDefault();
            $('.evge-' + $(this).attr('data-post-type') + '-quick-create')
                .slideDown()
                .append('<input type="hidden" name="evge_quick_create_' + $(this).attr('data-post-type') + '" value="1">');
        }).addClass('evge-initialized');

        $('.evge-undo-create:not(.evge-initialized)').on('click', function (event) {
            event.preventDefault();
            $('.evge-' + $(this).attr('data-post-type') + '-quick-create')
                .slideUp()
                .find('input[name="evge_quick_create_' + $(this).attr('data-post-type') + '"]')
                .remove();
        }).addClass('evge-initialized');

        // Handle add item functionality
        $('.evge-add-item-link:not(.evge-initialized)').on('click', function (e) {
            e.preventDefault();
            let $context = $(this).siblings('.evge-multi-select-list');
            let $newItem = $context.find('.evge-item-list .evge-single-wrap').first().clone();

            // Remove primary item class and create new button, add remove button
            $newItem.removeClass('evge-primary-item')
                .find('.evge-create-new, .evge-undo-create').remove()
                .end()
                .append('<a href="#" class="evge-item-remove">' + evgeIcon.close + '</a>');

            $newItem.appendTo($context.find('.evge-item-list'));
            evgeInitRemove($newItem);
        }).addClass('evge-initialized');

        // Initialize remove buttons
        $('.evge-item-remove:not(.evge-initialized)').each(function () {
            evgeInitRemove($(this).closest('.evge-single-wrap'));
            $(this).addClass('evge-initialized');
        });
    };
    // Initial initialization
    window.evgeInitVenueOrganizerTools();


    if ($('.evge-modal-placeholder').length && $('.evge-recurrence-modal-content').length) {
        $('body').append($('.evge-modal-backdrop'));
        $('body').append($('#evge-modal'));
        // Replace existing modal content
        $('.evge-modal-placeholder').replaceWith($('.evge-recurrence-modal-content'));
    }

    function evgeApplyMinMaxEndDate() {
        let startTimeVal = $('#evge_start_date').val();
        let endTimeVal = $('#evge_end_date').val();
        let startTime = new Date(startTimeVal).getTime();
        let endTime = new Date(endTimeVal).getTime();
        if (startTime > endTime) {
            $('#evge_end_date').val(startTimeVal);
        }
        $('#evge_end_date').attr('min', startTimeVal);
    } evgeApplyMinMaxEndDate();
    $('#evge_start_date').on('change', function () {
        evgeApplyMinMaxEndDate();
    });

    // Function to limit recurrence end date to max 1 year from start date
    function evgeLimitRecurrenceEndDate() {
        // Get the start date value
        let startDateVal = $('#evge_start_date').val();
        if (!startDateVal) return;

        // Handle both datetime-local and date input types
        let startDate;
        if (startDateVal.includes('T')) {
            startDate = new Date(startDateVal);
        } else {
            const timeComponent = $('#evge_start_date').attr('data-time') || '00:00';
            startDate = new Date(startDateVal + 'T' + timeComponent);
        }

        // Calculate max date (1 year from start date)
        let maxDate = new Date(startDate);
        maxDate.setFullYear(maxDate.getFullYear() + 1);

        // Format the dates for the input
        let startDateStr = startDate.toISOString().split('T')[0];
        let maxDateStr = maxDate.toISOString().split('T')[0];

        // Set the min and max attributes on the recurrence end date input
        $('#evge_recurrence_end_date').attr('min', startDateStr);
        $('#evge_recurrence_end_date').attr('max', maxDateStr);

        // If current value is outside the valid range, adjust it
        let currentEndDateVal = $('#evge_recurrence_end_date').val();
        if (currentEndDateVal) {
            let currentEndDate = new Date(currentEndDateVal);
            if (currentEndDate < startDate) {
                $('#evge_recurrence_end_date').val(startDateStr);
            } else if (currentEndDate > maxDate) {
                $('#evge_recurrence_end_date').val(maxDateStr);
            }
        } else {
            // If no end date is set, default to the start date
            $('#evge_recurrence_end_date').val(startDateStr);
        }
    }

    // Run on page load
    evgeLimitRecurrenceEndDate();

    // Run whenever start date changes
    $('#evge_start_date').on('change', function () {
        evgeLimitRecurrenceEndDate();
    });

    $('.evge-selected-timezone-wrap a').on('click', function (event) {
        event.preventDefault();
        $('.evge-event-timezone-wrap').slideToggle();
        $(this).hide();
    });

    // Make function globally available so it can be called from evge-settings.js
    window.evgeUpdateStartEndDateTimeInputs = function(allDay) {
        if (!$('#evge_start_date').length) {
            return;
        }
        let startDateTimeVal = $('#evge_start_date').val();
        let endDateTimeVal = $('#evge_end_date').val();
        let startDateVal = startDateTimeVal.indexOf('T') > -1 ? startDateTimeVal.split('T')[0] : startDateTimeVal;
        let endDateVal = endDateTimeVal.indexOf('T') > -1 ? endDateTimeVal.split('T')[0] : endDateTimeVal;
        let startTimeVal = startDateTimeVal.indexOf('T') > -1 ? startDateTimeVal.split('T')[1] : '00:00';
        let endTimeVal = endDateTimeVal.indexOf('T') > -1 ? endDateTimeVal.split('T')[1] : '00:00';
        if ($('#evge_start_date').val().indexOf('T') === -1) {
            if (typeof $('#evge_start_date').attr('data-time') !== 'undefined') {
                startDateTimeVal = $('#evge_start_date').val() + 'T' + $('#evge_start_date').attr('data-time');
                startTimeVal = $('#evge_start_date').attr('data-time');
            } else {
                startDateTimeVal = $('#evge_start_date').val() + 'T00:00';
            }
        }
        if ($('#evge_end_date').val().indexOf('T') === -1) {
            if (typeof $('#evge_end_date').attr('data-time') !== 'undefined') {
                endDateTimeVal = $('#evge_end_date').val() + 'T' + $('#evge_end_date').attr('data-time');
                endTimeVal = $('#evge_end_date').attr('data-time');
            } else {
                endDateTimeVal = $('#evge_end_date').val() + 'T00:00';
            }
        }
        if (allDay) {
            $('#evge_start_date').attr('type', 'date').val(startDateVal).attr('data-time', startTimeVal);
            $('#evge_end_date').attr('type', 'date').val(endDateVal).attr('data-time', endTimeVal);
        } else {
            $('#evge_start_date').attr('type', 'datetime-local').val(startDateTimeVal);
            $('#evge_end_date').attr('type', 'datetime-local').val(endDateTimeVal);
        }
    };
    
    // Initialize on page load
    if (typeof window.evgeUpdateStartEndDateTimeInputs === 'function') {
        window.evgeUpdateStartEndDateTimeInputs($('#evge-single-setting-all-day .evge-settings-toggle').hasClass('evge-input-toggle--enabled'));
    }

    $('#evge_unlimited_capacity').on('change', function () {
        if ($(this).is(':checked')) {
            $('#evge-capacity').prop('disabled', true);
        } else {
            $('#evge-capacity').prop('disabled', false);
        }
    });

    function evgeProcessTimelineSelection() {
        let openType = $('#evge_open_type').val();
        let closeType = $('#evge_close_type').val();

        $('.evge-open-type-sub').each(function () {
            if ($(this).attr('data-type') === openType) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        $('.evge-close-type-sub').each(function () {
            if ($(this).attr('data-type') === closeType) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

    } evgeProcessTimelineSelection();

    $('#evge_open_type, #evge_close_type').on('change', function () {
        evgeProcessTimelineSelection();
    });

    function evgeInitRegistrationTypes($context) {

        $context.find('.evge-registration-type-price-type select').on('change', function () {
            if ($(this).val() === 'custom') {
                $context.find('.evge-registration-type-price').slideDown();
            } else {
                $context.find('.evge-registration-type-price').slideUp();
            }
        });
        $context.find('.evge-registration-type-capacity-type select').on('change', function () {
            if ($(this).val() === 'custom') {
                $context.find('.evge-registration-type-capacity').slideDown();
            } else {
                $context.find('.evge-registration-type-capacity').slideUp();
            }
        });
    }
    $('.evge-registration-type').each(function () {
        evgeInitRegistrationTypes($(this));
    });


    // Initialize remove functionality
    function evgeInitRemove($item) {
        $item.find('.evge-item-remove:not(.evge-initialized)').on('click', function (e) {
            e.preventDefault();
            $(this).closest('.evge-single-wrap').remove();
            evgeToggleAvailableOptions();
        }).addClass('evge-initialized');
    }

    // Toggle available options
    function evgeToggleAvailableOptions() {
        $('.evge-multi-select-list').each(function () {
            let $context = $(this);
            let $items = $context.find('.evge-single-wrap').not('.evge-primary-item');

            if ($items.length === 0) {
                $items.find('.evge-item-remove').hide();
            } else {
                $items.find('.evge-item-remove').show();
            }
        });
    }

    $('input[name="evge_set_registration_default"]').on('change', function () {
        if ($(this).is(':checked')) {
            console.log('Global setting should be updated to disable registration by default');
        }
    });

    $('.evge-capacity-toggle .evge-settings-toggle').on('click', function () {
        const $toggle = $(this);
        const $input = $('#evge_capacity');

        // Wait for the toggle to update its state
        setTimeout(function () {
            if ($toggle.hasClass('evge-input-toggle--enabled')) {
                $input.prop('disabled', true);
            } else {
                $input.prop('disabled', false);
            }
        }, 50);
    });

    // Function to toggle registration sections visibility
    function toggleRegistrationSections() {
        const $toggle = $('.evge-registration-toggle .evge-settings-toggle');
        if ($toggle.hasClass('evge-input-toggle--enabled')) {
            // Show all registration sections
            $('.evge-registration-section').show();
        } else {
            // Hide all registration sections except the general one
            $('.evge-registration-section').hide();
            $('#evge-registration-general').show();
        }
    } toggleRegistrationSections();

    // Handle registration toggle to show/hide registration sections
    $('.evge-registration-toggle .evge-settings-toggle').on('click', function () {
        // Wait for the toggle to update its state
        setTimeout(toggleRegistrationSections, 50);
    });

    /**
     * Handle payment toggle state changes
     * This function is called after the toggle state has been updated by evge-settings.js
     */
    function evgeHandlePaymentToggle($toggle) {
        var $wrap = $toggle.closest('.evge-payment-enabled-wrap');
        var $toggleContainer = $toggle.closest('.evge-toggle-setting');
        var $message = $wrap.find('.evge-payment-gateway-message');
        var hasConfiguredGateway = $wrap.attr('data-has-configured-gateway') === 'true';
        
        // Wait for toggle state to update (after the class change in the main handler from evge-settings.js)
        setTimeout(function() {
            // Check the current state after update
            var isEnabled = $toggle.hasClass('evge-input-toggle--enabled');
            
            if (isEnabled && !hasConfiguredGateway) {
                // Add yellow warning class
                $toggleContainer.addClass('evge-gateway-needs-configuration');
                // Show message
                if ($message.length) {
                    $message.show();
                }
            } else {
                // Remove yellow warning class
                $toggleContainer.removeClass('evge-gateway-needs-configuration');
                // Hide message
                if ($message.length) {
                    $message.hide();
                }
            }
        }, 50);
    }

    // Handle payment toggle changes - listen after evge-settings.js handles the toggle
    $('body').on('click', '.evge-payment-enabled-wrap .evge-settings-toggle', function() {
        var $toggle = $(this);
        // Let evge-settings.js handle the toggle state change, then handle payment-specific logic
        evgeHandlePaymentToggle($toggle);
    });

    // Image upload handling
    $('.evge-upload-image').on('click', function (e) {
        e.preventDefault();

        const $button = $(this);
        const $wrap = $button.closest('.evge-image-upload-wrap');
        const $preview = $wrap.find('.evge-image-preview');
        const $input = $wrap.find('input[type="hidden"]');
        const $removeButton = $wrap.find('.evge-remove-image');

        // Create a new media frame
        const frame = wp.media({
            title: 'Select or Upload Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        // When an image is selected in the media frame...
        frame.on('select', function () {
            // Get media attachment details from the frame state
            const attachment = frame.state().get('selection').first().toJSON();

            // Get the image URL, falling back to full size if thumbnail isn't available
            const imageUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

            // Set the image preview
            $preview.html(`<img src="${imageUrl}" alt="">`);

            // Update the form value
            $input.val(attachment.id);

            // Show remove button
            $removeButton.show();
        });

        // Finally, open the modal
        frame.open();
    });

    // Remove image
    $('.evge-remove-image').on('click', function (e) {
        e.preventDefault();

        const $button = $(this);
        const $wrap = $button.closest('.evge-image-upload-wrap');
        const $preview = $wrap.find('.evge-image-preview');
        const $input = $wrap.find('input[type="hidden"]');

        // Clear the preview and input
        $preview.empty();
        $input.val('');

        // Hide remove button
        $button.hide();
    });

    const recurrenceSelect = $('#evge_recurrence_type');
    const endDateDiv = $('#evge-recurrence-end-date');
    const startDateInput = $('#evge_start_date');

    function updateRecurrenceText() {
        // Get the start date value and handle both datetime-local and date input types
        const startDateValue = startDateInput.val();
        if (!startDateValue) {
            return;
        }
        let startDate;

        if (startDateValue.includes('T')) {
            // For datetime-local input
            startDate = new Date(startDateValue);
        } else {
            // For date input, use the data-time attribute if available
            const timeComponent = startDateInput.attr('data-time') || '00:00';
            startDate = new Date(startDateValue + 'T' + timeComponent);
        }

        const dayName = startDate.toLocaleDateString('en-US', { weekday: 'long' });
        const weekNum = Math.ceil(startDate.getDate() / 7);
        const weekNumText = ['first', 'second', 'third', 'fourth', 'fifth'][weekNum - 1];

        // Update all options that have a format
        recurrenceSelect.find('option').each(function () {
            const $option = $(this);
            const format = $option.data('format');

            if (format) {
                if ($option.val() === 'weekly') {
                    $option.text(format.replace('%s', dayName));
                } else if ($option.val() === 'monthly') {
                    $option.text(format.replace('%s', `${weekNumText} ${dayName}`));
                }
            }
        });
    }

    // Show/hide end date and update text when recurrence type changes
    recurrenceSelect.on('change', function () {
        if ($(this).val() === 'none') {
            endDateDiv.addClass('evge-hidden');
        } else {
            endDateDiv.removeClass('evge-hidden');
        }
        updateRecurrenceText();
    });

    // Update text when start date changes
    startDateInput.on('change', updateRecurrenceText);

    // Also update when all-day toggle changes since it modifies the input type
    // Use event delegation to work with global toggle handler
    $('body').on('click', '#evge-single-setting-all-day .evge-settings-toggle', function () {
        // Wait for the input type to be updated
        setTimeout(updateRecurrenceText, 100);
    });

    // Initial text update
    updateRecurrenceText();

    if ($('.evge-queue-processing-modal-trigger').length) {
        evgeCheckSeriesQueue();
    }

    // Queue processing functionality
    function evgeCheckSeriesQueue() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'evge_check_series_queue',
                nonce: evgeCPTAdmin.nonce
            },
            success: function (response) {
                if (response.success && response.data.pending_tasks > 0) {
                    if (response.data.modal_html) {
                        window.evgeShowQueueProcessingModal(response);
                    }
                } else {
                    $('.evge-modal-close').trigger('click');
                    $('.evge-queue-processing-modal-content').remove();
                }
            }
        });
    }

    window.evgeProcessQueueBatch = function (currentBatch, maxBatches, isCreation, seriesId) {
        if (seriesId) {
            $('.evge-undo-recurrence-create').data('series-id', seriesId);
        }
        // Safety check to prevent infinite loops
        if (currentBatch > maxBatches && maxBatches > 0) {
            console.log('Reached maximum number of batch processing attempts');
            return;
        }

        // Also limit to a reasonable number regardless of estimated batches
        if (currentBatch > 30) {
            console.log('Reached hard limit of batch processing attempts');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'evge_process_series_queue_batch',
                nonce: evgeCPTAdmin.nonce
            },
            success: function (response) {
                if (response.success) {

                    if (response.data.is_completed) {
                        // All done
                        $('.evge-progress-bar').css('width', '100%');
                        $('.evge-processed-count').text($('.evge-total-count').attr('data-total-events'));

                        // Show undo button if we're creating new events
                        if (isCreation && seriesId) {
                            $('.evge-undo-recurrence-create')
                                .prop('disabled', false)
                                .off('click')
                                .on('click', function () {
                                    const $button = $(this);
                                    const seriesId = $('.evge-undo-recurrence-create').data('series-id');
                                    $button.prop('disabled', true);

                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'evge_trash_all_recurrences',
                                            nonce: evgeCPTAdmin.nonce,
                                            series_id: seriesId,
                                            include_template_event: false
                                        },
                                        success: function (response) {
                                            if (response.success) {
                                                // Update modal with success message
                                                $('.evge-modal-content').html(`
													<div class="evge-standard-dialog">
														<p>${response.data.message || 'Recurrence creation has been undone successfully.'}</p>
													</div>
													<div class="evge-modal-actions">
														<button type="button" class="button button-primary evge-modal-close">Close</button>
													</div>
												`);
                                            } else {
                                                $button.prop('disabled', false);
                                                alert(response.data.message || 'Failed to undo recurrence creation.');
                                            }
                                        },
                                        error: function () {
                                            $button.prop('disabled', false);
                                            alert('Failed to undo recurrence creation.');
                                        }
                                    });
                                });
                        }
                    } else {
                        // Update progress
                        const progress = (response.data.processed_events / response.data.total_events) * 100;
                        $('.evge-progress-bar').css('width', progress + '%');
                        $('.evge-total-count').text(response.data.total_events).attr('data-total-events', response.data.total_events);
                        $('.evge-processed-count').text(response.data.processed_events);
                        // Process next batch
                        setTimeout(function () {
                            window.evgeProcessQueueBatch(currentBatch + 1, maxBatches, isCreation, seriesId);
                        }, 1000); // 1 second delay to prevent overwhelming the server
                    }
                } else {
                    // Error handling
                    console.error('Error processing queue batch', response);
                }
            },
            error: function () {
                console.error('AJAX error while processing queue batch');
            }
        });
    }

    // Handle recurrence trash modal
    $('.evge-trash-recurrence').on('click', function (e) {
        e.preventDefault();
        const $link = $(this);
        const recurrenceId = $link.attr('href').split('id=')[1].split('&')[0];
        const templateId = $link.closest('tr').find('input[name="event[]"]').val();

        // Show loading state
        const loadingContent = `
			<div class="evge-narrow-modal-inner evge-recurrence-modal-content-inner">
				<div class="evge-modal-section">
					<div class="evge-standard-dialog">
						<p>${evgeCPTAdmin.i18n.loading}</p>
					</div>
				</div>
			</div>
		`;

        // Show modal with loading state
        $('.evge-modal-placeholder').html(loadingContent);
        $('.evge-modal').addClass('evge-narrow-max-width-modal evge-centered-modal evge-recurrence-modal').removeClass('evge-medium-max-width-modal');
        $('body').addClass('evge-modal-is-open');

        // Fetch series information
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'evge_get_recurrence_trash_options',
                nonce: evgeCPTAdmin.nonce,
                event_id: recurrenceId
            },
            success: function (response) {
                if (response.success) {
                    // Update modal with server-generated HTML
                    $('.evge-modal-placeholder').html(response.data.html);

                    // Handle modal actions
                    $('.evge-trash-single-recurrence').on('click', function () {
                        window.location.href = $link.attr('href');
                    });

                    $('.evge-trash-all-recurrences').on('click', function (event) {
                        event.preventDefault();
                        const $button = $(this);
                        $button.prop('disabled', true);

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'evge_trash_all_recurrences',
                                nonce: evgeCPTAdmin.nonce,
                                series_id: $button.data('series-id')
                            },
                            success: function (response) {
                                if (response.success) {
                                    // Update modal with success content
                                    $('.evge-modal-placeholder').html(response.data.html);

                                    // Add listeners for all modal closing methods
                                    $('.evge-modal-close, .evge-modal-backdrop, .evge-action-modal-close').on('click', function () {
                                        $('body').removeClass('evge-modal-is-open');
                                        window.location.reload();
                                    });
                                } else {
                                    $button.prop('disabled', false);
                                    alert(response.data.message || evgeCPTAdmin.i18n.errorTrashingRecurrences);
                                }
                            },
                            error: function () {
                                $button.prop('disabled', false);
                                alert(evgeCPTAdmin.i18n.errorTrashingRecurrences);
                            }
                        });
                    });

                    $('.evge-modal-close').on('click', function () {
                        $('body').removeClass('evge-modal-is-open');
                    });
                } else {
                    alert(response.data.message || evgeCPTAdmin.i18n.errorLoadingSeriesInfo);
                }
            },
            error: function () {
                alert(evgeCPTAdmin.i18n.errorLoadingSeriesInfo);
            }
        });

        $('.evge-modal-close, .evge-modal-backdrop, .evge-action-modal-close').on('click', function () {
            $('body').removeClass('evge-modal-is-open');
        });
    });

    // Handle template event trash modal
    $('.evge-trash-template').on('click', function (e) {
        e.preventDefault();
        const $link = $(this);
        const eventId = $link.attr('href').split('id=')[1].split('&')[0];

        // Show loading state
        const loadingContent = `
			<div class="evge-narrow-modal-inner evge-recurrence-modal-content-inner">
				<div class="evge-modal-section">
					<div class="evge-standard-dialog">
						<p>${evgeCPTAdmin.i18n.loading}</p>
					</div>
				</div>
			</div>
		`;

        // Show modal with loading state
        $('.evge-modal-placeholder').html(loadingContent);
        $('.evge-modal').addClass('evge-narrow-max-width-modal evge-centered-modal evge-recurrence-modal').removeClass('evge-medium-max-width-modal');
        $('body').addClass('evge-modal-is-open');

        // Fetch template event trash options
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'evge_get_recurrence_template_trash_options',
                nonce: evgeCPTAdmin.nonce,
                event_id: eventId
            },
            success: function (response) {
                if (response.success) {
                    // Update modal with server-generated HTML
                    $('.evge-modal-placeholder').html(response.data.html);

                    $('.evge-modal-close, .evge-modal-backdrop, .evge-action-modal-close').on('click', function () {
                        $('body').removeClass('evge-modal-is-open');
                    });
                    // Handle template event deletion
                    $('.evge-trash-template-event').on('click', function (event) {
                        event.preventDefault();
                        const $button = $(this);
                        const eventId = $button.data('event-id');
                        $button.prop('disabled', true);


                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'evge_trash_all_recurrences',
                                include_template_event: true,
                                nonce: evgeCPTAdmin.nonce,
                                event_id: eventId
                            },
                            success: function (response) {
                                if (response.success) {
                                    window.location.reload();
                                } else {
                                    $button.prop('disabled', false);
                                    alert(response.data.message || evgeCPTAdmin.i18n.errorTrashingTemplate);
                                }
                            },
                            error: function () {
                                $button.prop('disabled', false);
                                alert(evgeCPTAdmin.i18n.errorTrashingTemplate);
                            }
                        });
                    });
                } else {
                    alert(response.data.message || evgeCPTAdmin.i18n.errorLoadingSeriesInfo);
                }
            },
            error: function () {
                alert(evgeCPTAdmin.i18n.errorLoadingSeriesInfo);
            }
        });

        // Handle modal closing
        $('.evge-modal-close, .evge-modal-backdrop, .evge-action-modal-close').on('click', function () {
            $('body').removeClass('evge-modal-is-open');
            // Trigger custom event for admin modal close
            $(document).trigger('evge_admin_modal_closed');
        });
    });

});