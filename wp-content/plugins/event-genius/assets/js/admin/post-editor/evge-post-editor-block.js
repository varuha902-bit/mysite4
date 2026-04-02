(function (wp, $) {
    // Wait for editor to be fully initialized
    wp.domReady(function () {
        // Make sure we have access to the editor
        if (!wp.data || !wp.data.dispatch('core/editor')) {
            console.log('Editor not available');
            return;
        }

        // Get reference to the editor dispatch
        const editor = wp.data.dispatch('core/editor');
        // Store the original savePost function
        const savePost = editor.savePost;

        /**
         * Handle cleanup and refresh of newly created venues and organizers
         * @param {Object} post - The current post object
         * @param {boolean} onlyIfCreated - Only clear if a new venue/organizer was actually created
         */
        function evgeHandleNewVenuesAndOrganizers(post, onlyIfCreated) {
            // Only clear if a new venue was actually created (check for the flag)
            const venueWasCreated = $('input[name="evge_quick_create_venue"]').length > 0;
            if (venueWasCreated || (!onlyIfCreated && $('.evge-venue-quick-create').length && $('.evge-venue-quick-create').is(':visible'))) {
                // Refresh the venue list
                $.ajax({
                    url: evgePostSave.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'evge_refresh_venue_list',
                        post_id: post.id,
                        nonce: evgePostSave.nonce
                    },
                    success: function (response) {
                        if (response.success && response.data.html) {
                            $('#evge-single-setting-venue').replaceWith(response.data.html);
                            // Close the quick create form
                            $('.evge-venue-quick-create').slideUp();
                            // Clear form fields
                            $('.evge-venue-quick-create input[type="text"], .evge-venue-quick-create input[type="hidden"]').val('');
                            $('.evge-venue-quick-create .evge-image-preview').empty();
                            // Remove the quick create flag
                            $('input[name="evge_quick_create_venue"]').remove();
                            // Reinitialize venue tools
                            if (typeof window.evgeInitVenueOrganizerTools === 'function') {
                                window.evgeInitVenueOrganizerTools();
                            }
                        }
                    }
                });
            }

            // Only clear if a new organizer was actually created (check for the flag)
            const organizerWasCreated = $('input[name="evge_quick_create_organizer"]').length > 0;
            if (organizerWasCreated || (!onlyIfCreated && $('.evge-organizer-quick-create').length && $('.evge-organizer-quick-create').is(':visible'))) {
                // Refresh the organizer list
                $.ajax({
                    url: evgePostSave.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'evge_refresh_organizer_list',
                        post_id: post.id,
                        nonce: evgePostSave.nonce
                    },
                    success: function (response) {
                        if (response.success && response.data.html) {
                            $('#evge-single-setting-organizer').replaceWith(response.data.html);
                            // Close the quick create form
                            $('.evge-organizer-quick-create').slideUp();
                            // Clear form fields
                            $('.evge-organizer-quick-create input[type="text"], .evge-organizer-quick-create input[type="hidden"]').val('');
                            $('.evge-organizer-quick-create .evge-image-preview').empty();
                            // Remove the quick create flag
                            $('input[name="evge_quick_create_organizer"]').remove();
                            // Reinitialize organizer tools
                            if (typeof window.evgeInitVenueOrganizerTools === 'function') {
                                window.evgeInitVenueOrganizerTools();
                            }
                        }
                    }
                });
            }
        }

        // Replace the savePost function with our custom implementation
        editor.savePost = function (options) {
            options = options || {};

            // Only intercept manual saves, not autosaves
            if (options.isAutosave) {
                return savePost(options);
            }

            // Get the current post data
            const post = wp.data.select('core/editor').getCurrentPost();

            // Only intercept for our specific post type
            if (post.type !== evgePostSave.postType) {
                return savePost(options);
            }

            // if we are previewing or doing anything other than updating, continue with save
            if (options.preview || options.publish) {
                return savePost(options);
            }

            // Check if we need to handle recurrence changes
            if (!window.evgeCheckRecurrenceChanges()) {
                return savePost(options);
            }

            return new Promise((resolve, reject) => {
                // Append the modal HTML to the body if it doesn't exist yet
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
                    // Proceed with the original save
                    savePost(options)
                        .then(() => {
                            // Make AJAX call to check for series data
                            $.ajax({
                                url: evgePostSave.ajaxUrl,
                                type: 'POST',
                                data: {
                                    action: 'evge_check_series_queue_after_save',
                                    post_id: post.id,
                                    nonce: evgePostSave.nonce
                                },
                                success: function (response) {
                                    if (response.success) {
                                        if (response.data.modal_html) {
                                            // If we have pending tasks, start processing the queue
                                            if (response.data.pending_tasks > 0) {
                                                window.evgeShowQueueProcessingModal(response);
                                            } else {
                                                $('body').removeClass('evge-modal-is-open');
                                            }
                                        }
                                    }
                                    // Handle newly created venues and organizers after successful save
                                    evgeHandleNewVenuesAndOrganizers(post);
                                    resolve();
                                },
                                error: function (error) {
                                    console.error('Error checking series queue:', error);
                                    resolve();
                                }
                            });
                        })
                        .catch(reject);
                });

                // Handle cancel button click
                $('.evge-cancel-save, .evge-modal-close').one('click', function () {
                    $('body').removeClass('evge-modal-is-open');
                    reject(new Error('Save cancelled by user'));
                });
            });
        };

        // Add a listener for the post save event
        let wasSaving = false;
        let wasAutosaving = false;
        wp.data.subscribe(function () {
            const post = wp.data.select('core/editor').getCurrentPost();

            // Early exit if not our post type
            if (!post || post.type !== evgePostSave.postType) {
                wasSaving = false;
                wasAutosaving = false;
                return;
            }

            const isSavingPost = wp.data.select('core/editor').isSavingPost();
            const isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();
            const isPostSavingLocked = wp.data.select('core/editor').isPostSavingLocked();

            // If we were saving and now we're not, and it's not an autosave
            // Only clear fields if a new venue/organizer was actually created (check for flags)
            // Check both wasAutosaving (previous state) and isAutosavingPost (current state) to catch autosaves
            if (wasSaving && !isSavingPost && !isPostSavingLocked && !wasAutosaving && !isAutosavingPost) {
                // Only clear if the quick create flags exist (indicating a new venue/organizer was created)
                evgeHandleNewVenuesAndOrganizers(post, true);
            }

            // Update the saving state
            wasSaving = isSavingPost;
            wasAutosaving = isAutosavingPost;
        });
    });
})(window.wp, jQuery);