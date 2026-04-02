jQuery(function ($) {
    // Core namespace
    window.EVGE = window.EVGE || {};
    
    // Hook system must be initialized first
    window.EVGE.Hooks = {
        hooks: {},
        
        /**
         * Add an action hook
         * @param {string} action - The name of the action
         * @param {Function} callback - The callback function
         * @param {number} priority - The priority of the action (default: 10)
         */
        addAction: function (action, callback, priority = 10) {
            if (!this.hooks[action]) {
                this.hooks[action] = [];
            }
            this.hooks[action].push({
                callback,
                priority
            });
            // Sort by priority
            this.hooks[action].sort((a, b) => a.priority - b.priority);
        },
        
        /**
         * Execute an action hook
         * @param {string} action - The name of the action
         * @param {...any} args - Arguments to pass to the callback
         */
        doAction: function (action, ...args) {
            if (this.hooks[action]) {
                this.hooks[action].forEach(hook => {
                    try {
                        hook.callback(...args);
                    } catch (error) {
                        console.error(`EVGE Hook Error (${action}):`, error);
                    }
                });
            }
        },
        
        /**
         * Remove an action hook
         * @param {string} action - The name of the action
         * @param {Function} callback - The callback function to remove
         */
        removeAction: function (action, callback) {
            if (this.hooks[action]) {
                this.hooks[action] = this.hooks[action].filter(hook => 
                    hook.callback !== callback
                );
            }
        },
        
        /**
         * Check if an action has any hooks
         * @param {string} action - The name of the action
         * @returns {boolean}
         */
        hasAction: function (action) {
            return this.hooks[action] && this.hooks[action].length > 0;
        }
    };

    // Make hooks available globally for backward compatibility
    window.EvgeHooks = window.EVGE.Hooks;

    function Evge() {
        this.displayElements = {};
    }

    Evge.prototype = {
        createPage: function () {
            var self = this;
            $('.evge').each(function (index) {
                window.EVGE.Hooks.doAction('evge_page_loading', $(this), index);
            });

            if ($('.evge-modal').length) {
                this.Modal = new EvgeModal();
                this.Modal.init();
            }

            this.Actions = new EvgeActions();
            this.Actions.init();

            window.EVGE.Hooks.doAction('evge_page_created');
        },
        spinnerHTML: function () {
            return '<div class="evge-spinner-container"><div class="evge-spinner-circle"></div></div>';
        },
        /**
         * Unified AJAX method for handling all AJAX requests
         * @param {Object} options - Configuration options
         * @param {string} options.url - The AJAX URL (defaults to evge.ajaxUrl)
         * @param {string} options.type - The request type (defaults to 'post')
         * @param {Object} options.data - The data to send
         * @param {boolean} options.processData - Whether to process the data (defaults to true)
         * @param {string|boolean} options.contentType - Content type of the request (defaults to 'application/x-www-form-urlencoded; charset=UTF-8')
         * @param {boolean} options.cache - Whether to cache the request (defaults to false)
         * @param {number} options.timeout - Request timeout in milliseconds (defaults to 30000)
         * @param {Function} options.success - Success callback
         * @param {Function} options.error - Error callback
         * @param {Function} options.complete - Complete callback
         * @returns {Promise} - Returns a Promise that resolves with the response
         */
        ajax: function(options) {
            // Validate options
            if (options && typeof options !== 'object') {
                return Promise.reject(new Error('Options must be an object'));
            }

            // Default options
            const defaults = {
                url: typeof evge !== 'undefined' && evge.ajaxUrl ? evge.ajaxUrl : '/wp-admin/admin-ajax.php',
                type: 'post',
                processData: true,
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                cache: false,
                timeout: 30000,
                success: null,
                error: null,
                complete: null
            };

            // Merge provided options with defaults
            const config = { ...defaults, ...options };

            // Validate required fields
            if (!config.url) {
                return Promise.reject(new Error('URL is required'));
            }

            // Create a Promise to handle the AJAX request
            return new Promise((resolve, reject) => {
                const jqXHR = $.ajax({
                    url: config.url,
                    type: config.type,
                    data: config.data,
                    processData: config.processData,
                    contentType: config.contentType,
                    cache: config.cache,
                    timeout: config.timeout,
                    success: function(response) {
                        try {
                            // Call success callback if provided
                            if (typeof config.success === 'function') {
                                config.success(response);
                            }
                            resolve(response);
                        } catch (error) {
                            console.error('EVGE AJAX Success Handler Error:', error);
                            reject(error);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const errorDetails = {
                            status: jqXHR.status,
                            statusText: jqXHR.statusText,
                            responseText: jqXHR.responseText,
                            error: errorThrown,
                            textStatus: textStatus
                        };

                        // Log error details
                        console.error('EVGE AJAX Error:', errorDetails);

                        // Handle specific error cases
                        if (textStatus === 'timeout') {
                            console.error('EVGE AJAX Timeout Error: Request took too long to complete');
                        } else if (textStatus === 'abort') {
                            console.error('EVGE AJAX Abort Error: Request was aborted');
                        } else if (jqXHR.status === 0) {
                            console.error('EVGE AJAX Network Error: Could not connect to server');
                        }

                        // Call error callback if provided
                        if (typeof config.error === 'function') {
                            try {
                                config.error(jqXHR, textStatus, errorThrown);
                            } catch (error) {
                                console.error('EVGE AJAX Error Handler Error:', error);
                            }
                        }

                        reject(errorDetails);
                    },
                    complete: function() {
                        // Call complete callback if provided
                        if (typeof config.complete === 'function') {
                            try {
                                config.complete();
                            } catch (error) {
                                console.error('EVGE AJAX Complete Handler Error:', error);
                            }
                        }
                    }
                });

                // Add abort method to the Promise
                const promise = Promise.resolve(jqXHR);
                promise.abort = function() {
                    jqXHR.abort();
                };

                return promise;
            });
        }
    };

    function EvgeActions() {

    }

    EvgeActions.prototype = {
        init() {
            this.initTriggers();
        },
        initTriggers() {
            var self = this;
            $('.evge-action-trigger').each(function () {
                $(this).on('click', function (event) {
                    event.preventDefault();
                    window.Evge.Modal.$element.addClass('evge-is-processing').append(window.Evge.spinnerHTML());

                    var submitData = JSON.parse($(this).attr('data-evge-ajax'));
                    setTimeout(function () {
                        window.Evge.ajax({
                            data: submitData,
                            success: function(data) {
                                self.ajaxAddContent(data);
                            }
                        });
                    }, 500)
                })
            });

            this.initListExportOptions();
            this.initShowMore();
            this.initSeriesEventsLoadMore();
        },
        ajaxAddContent(data) {
            var self = this;
            window.Evge.Modal.$element.removeClass('evge-is-processing');
            $('.evge-spinner-container div').fadeOut(function () {
                $('.evge-spinner-container').remove();
            });
            $('.evge-dynamic').fadeOut(function () {
                $('.evge-dynamic').replaceWith(data.data.html);
                $('.evge-dynamic').fadeIn();
            });
        },
        initListExportOptions($context) {
            if (typeof $context === 'undefined') {
                $context = $('body');
            }

            var self = this;
            $(document).on('click', '.evge-export-list', (e) => {
                e.preventDefault();
                
                var $dropdown = $(e.target).closest('.evge-export-list-wrap').find('.evge-export-options-dropdown');
                var isExpanded = $dropdown.is(':visible');

                // Toggle the dropdown
                $dropdown.slideToggle(100);

                // Update aria-expanded state
                $(e.target).attr('aria-expanded', !isExpanded);

                // Update hidden attribute
                if (!isExpanded) {
                    $dropdown.removeAttr('hidden');
                } else {
                    $dropdown.attr('hidden', '');
                }
            });
        },
        initShowMore() {
            // Handle truncated text clicks using event delegation
            $(document).on('click', '.evge-more', (e) => {
                e.preventDefault();

                const $moreLink = $(e.target);
                const $hiddenContent = $moreLink.prev('.evge-hidden');

                // Show hidden content
                $hiddenContent
                    .attr('aria-hidden', 'false')
                    .show();

                // Remove the more link
                $moreLink.remove();
            });
        },

        initSeriesEventsLoadMore() {
            // Handle series events load more button clicks
            $(document).on('click', '.evge-load-more-events', (e) => {
                e.preventDefault();

                // Use closest to ensure we get the button even if a child element was clicked
                const $button = $(e.target).closest('.evge-load-more-events');
                const $eventsList = $button.closest('.evge-event-list-content').find('.evge-series-events-list');
                
                if ($button.attr('aria-busy') === 'true') {
                    return; // Already loading
                }

                const seriesId = $button.data('series-id');
                const offset = parseInt($button.data('offset'), 10);
                const limit = parseInt($button.data('limit'), 10);

                // Set loading state using existing processing pattern
                $button.attr('aria-busy', 'true');
                $button.wrap('<div class="evge-processing-wrap-flex evge-is-processing"></div>');
                $button.addClass('evge-fade').prop('disabled', true);
                $button.after(window.Evge.spinnerHTML());

                // Make AJAX request
                window.Evge.ajax({
                    data: {
                        action: 'evge_load_more_series_events',
                        series_id: seriesId,
                        offset: offset,
                        limit: limit,
                        nonce: window.EVGE.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Append new events
                            $eventsList.append(response.data.html);
                            
                            // Re-initialize modal triggers for the newly loaded events
                            if (window.Evge && window.Evge.Modal && window.Evge.Modal.initTriggers) {
                                window.Evge.Modal.initTriggers();
                            }
                            
                            // Re-initialize other components that might be needed
                            if (window.EVGE && window.EVGE.Hooks) {
                                window.EVGE.Hooks.doAction('evge_modal_content_loaded', response.data);
                            }
                            
                            // Update offset
                            const newOffset = offset + response.data.count;
                            $button.data('offset', newOffset);
                            
                            // Check if there are more events
                            if (response.data.has_more) {
                                // Reset button state
                                $button.attr('aria-busy', 'false');
                                setTimeout(function() {
                                    $button.closest('.evge-processing-wrap-flex').find('.evge-spinner-container').remove();
                                    $button.unwrap('.evge-processing-wrap-flex');
                                    $button.removeClass('evge-fade').prop('disabled', false);
                                }, 500);
                            } else {
                                // No more events, remove button
                                $button.closest('.evge-series-events-load-more').remove();
                            }
                        } else {
                            // Handle error
                            console.error('Error loading more events:', response.data);
                            $button.attr('aria-busy', 'false');
                            setTimeout(function() {
                                $button.closest('.evge-processing-wrap-flex').find('.evge-spinner-container').remove();
                                $button.unwrap('.evge-processing-wrap-flex');
                                $button.removeClass('evge-fade').prop('disabled', false);
                            }, 500);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error loading more events:', error);
                        $button.attr('aria-busy', 'false');
                        setTimeout(function() {
                            $button.closest('.evge-processing-wrap-flex').find('.evge-spinner-container').remove();
                            $button.unwrap('.evge-processing-wrap-flex');
                            $button.removeClass('evge-fade').prop('disabled', false);
                        }, 500);
                    }
                });
            });
        }
    }

    function EvgeModal() {
        this.$element = $('.evge-modal');
        this.$lastFocusedElement = null;
    }

    EvgeModal.prototype = {
        init() {
            if (!this.$element.length) return;
            this.maybeAutoTrigger();
            this.initTriggers();
            this.initAccessibility();
        },

        initAccessibility() {
            // Store the last focused element when modal opens
            $(document).on('focusin', function (e) {
                if (!$(e.target).closest('.evge-modal').length) {
                    this.$lastFocusedElement = e.target;
                }
            }.bind(this));

            // Handle ESC key to close modal
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $('body').hasClass('evge-modal-is-open')) {
                    this.closeModal();
                }
            }.bind(this));

            // Trap focus within modal
            this.$element.on('keydown', function (e) {
                if (e.key === 'Tab') {
                    const focusableElements = this.$element.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    const firstFocusable = focusableElements.first();
                    const lastFocusable = focusableElements.last();

                    if (e.shiftKey) {
                        if (document.activeElement === firstFocusable[0]) {
                            lastFocusable.focus();
                            e.preventDefault();
                        }
                    } else {
                        if (document.activeElement === lastFocusable[0]) {
                            firstFocusable.focus();
                            e.preventDefault();
                        }
                    }
                }
            }.bind(this));
        },

        maybeAutoTrigger() {
            if ($('.evge-autotrigger-modal').length) {
                this.$element.find('.evge-modal-placeholder').replaceWith($('.evge-autotrigger-modal'));
                this.applySettings(JSON.parse($('.evge-autotrigger-modal').attr('data-evge-modal-settings')));
                this.openModal();
            }
        },

        applySettings(settings) {
            if (typeof settings.width !== 'undefined') {
                if (settings.width === 'narrow') {
                    this.$element.removeClass('evge-medium-max-width-modal');
                    this.$element.addClass('evge-narrow-max-width-modal');
                } else {
                    this.$element.removeClass('evge-narrow-max-width-modal');
                    this.$element.addClass('evge-medium-max-width-modal');
                }
            }
        },

        initTriggers() {
            var self = this;
            $('.evge-modal-trigger').each(function () {
                if ($(this).hasClass('evge-modal-initted')) {
                    return;
                }
                
                $(this).addClass('evge-modal-initted');
                var contentType = 'none';

                if (typeof $(this).attr('data-evge-modal-content') !== 'undefined') {
                    contentType = $(this).attr('data-evge-modal-content');
                }

                $(this).on('click', function (event) {
                    event.preventDefault();
                    
                    // Check if we have cached content for this modal
                    var cacheKey = $(this).attr('data-evge-cache-key');
                    
                    if (cacheKey && window.EVGE.Payments && window.EVGE.Payments.cache && window.EVGE.Payments.cache[cacheKey]) {
                        // Use cached content
                        self.openModal();
                        self.applySettings(JSON.parse($(this).attr('data-evge-modal-settings') || '{}'));
                        $('.evge-modal-content').html(window.EVGE.Payments.cache[cacheKey]);
                        self.contentLoadedState();
                        
                        // Reinitialize payments after loading cached content
                        if (window.EVGE.Payments && window.EVGE.Payments.Initializer) {
                            window.EVGE.Payments.Initializer.initCheckout();
                        }
                        return;
                    }
                    
                    self.openModal();
                    self.loadingContentState();
                    
                    if (typeof $(this).attr('data-evge-modal-settings') !== 'undefined') {
                        self.applySettings(JSON.parse($(this).attr('data-evge-modal-settings')));
                    }
                    
                    if (contentType === 'ajax') {
                        window.Evge.ajax({
                            data: JSON.parse($(this).attr('data-evge-ajax')),
                            success: function(data) {
                                self.ajaxAddContent(data);
                            }
                        });
                    }
                });
            });

            $('.evge-modal-backdrop, .evge-action-modal-close').on('click', function () {
                self.closeModal();
            });
        },

        ajaxAddContent(data) {
            // Store current height before content change
            var self = this;
            var $modalContent = $('.evge-modal-content');
            var currentHeight = $modalContent.height();

            // Set fixed height to prevent jump
            $modalContent.css('height', currentHeight + 'px');

            // Create a temporary clone to measure height
            var $tempContent = $('<div>').css({
                position: 'absolute',
                visibility: 'hidden',
                width: $modalContent.width() + 'px'
            });

            // Add the clone to the DOM temporarily
            $('body').append($tempContent);

            // Set the content in the clone
            $tempContent.html(data.data.html);
            $('.evge-modal-reveal').show();

            // Get the height from the clone
            var newHeight = $tempContent.height();

            // Remove the temporary clone
            $tempContent.remove();

            // Update the actual content
            $modalContent.html(data.data.html);
            $('.evge-modal-reveal').show();

            // Animate to new height
            $modalContent.animate({
                height: newHeight + 'px'
            }, 300, function () {
                // Remove fixed height after animation
                $modalContent.css('height', '');
                this.contentLoadedState();

                $modalContent.find('.evge-action-trigger').each(function () {
                    $(this).on('click', function (event) {
                        event.preventDefault();
                        window.Evge.Modal.$element.addClass('evge-is-processing').append(window.Evge.spinnerHTML());

                        var submitData = JSON.parse($(this).attr('data-evge-ajax'));
                        setTimeout(function () {
                            window.Evge.ajax({
                                data: submitData,
                                success: function (data) {
                                    self.ajaxAddContent(data);
                                }
                            });
                        }, 500)
                    })
                });
                if (window.EVGE.Payments && window.EVGE.Payments.Initializer) {
                    window.EVGE.Payments.Initializer.init();
                }

                // Trigger after content loaded hook
                window.EVGE.Hooks.doAction('evge_modal_content_loaded', data);
            }.bind(this));
        },

        loadingContentState() {
            this.$element.addClass('evge-is-processing').append(window.Evge.spinnerHTML());
        },

        contentLoadedState() {
            this.$element.removeClass('evge-is-processing');
            $('.evge-spinner-container div').fadeOut(function () {
                $('.evge-spinner-container').remove();
            });
        },

        openModal() {
            $('body').addClass('evge-modal-is-open');
            window.EVGE.Hooks.doAction('evge_modal_opened');

            // Focus the first focusable element in the modal
            const firstFocusable = this.$element.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
            firstFocusable.focus();
        },

        closeModal() {
            // Cache the current modal content before closing if it's a checkout modal
            var $modalContent = $('.evge-modal-content');
            if ($modalContent.find('.evge-checkout-modal').length) {
                var eventID = $modalContent.find('input[name="evge_event_id"]').val();
                if (eventID && window.EVGE.Payments && window.EVGE.Payments.cache) {
                    window.EVGE.Payments.cache[eventID] = $modalContent.html();
                }
            }
            
            // Check if modal content contains element with data-evge-refresh-on-close attribute
            var shouldRefresh = this.$element.find('[data-evge-refresh-on-close]').length > 0;
            
            $('body').removeClass('evge-modal-is-open');
            window.EVGE.Hooks.doAction('evge_modal_closed');

            $('.evge-modal-content').html('<div class="evge-modal-placeholder"></div>');

            // Return focus to the element that triggered the modal
            if (this.$lastFocusedElement) {
                this.$lastFocusedElement.focus();
            }
            
            // Refresh page if data attribute is present in modal content
            if (shouldRefresh) {
                // Remove query parameters that might trigger modals to appear again
                var url = new URL(window.location.href);
                var paramsToRemove = ['evge_action', 'evge_key', 'evge_post', 'evge_confirm'];
                paramsToRemove.forEach(function(param) {
                    url.searchParams.delete(param);
                });
                
                // Reload with cleaned URL
                window.location.href = url.toString();
            }
        }
    };

    /**
     * Dynamic Content Refresh Manager
     * Detects stale content and refreshes it automatically when caching plugins are used
     */
    window.EVGE.DynamicContentRefresh = {
        // Configuration
        STALENESS_THRESHOLD: function() {
            // Get threshold from localized script if available
            if (typeof window.evge !== 'undefined' && 
                window.evge.dynamicContentRefresh && 
                window.evge.dynamicContentRefresh.stalenessThreshold) {
                return window.evge.dynamicContentRefresh.stalenessThreshold;
            }
            return 180; // Default: 3 minutes in seconds
        },

        // State
        updateInProgress: false,
        initialized: false,

        /**
         * Initialize the refresh system - runs once on page load when DOM is ready
         */
        init: function() {
            if (this.initialized) {
                return;
            }

            // Check if feature is disabled
            if (this.isDisabled()) {
                return;
            }

            this.initialized = true;

            $(document).ready($.proxy(this.checkAllContent, this));
        },
        
        /**
         * Check if dynamic content refresh is disabled
         */
        isDisabled: function() {
            // Check JavaScript variable from localized script
            if (typeof window.evge !== 'undefined' && 
                window.evge.dynamicContentRefresh && 
                window.evge.dynamicContentRefresh.enabled === false) {
                return true;
            }
            
            // Fallback: Check body data attribute
            if ($('body').data('evge-disable-dynamic-refresh') === true) {
                return true;
            }
            
            return false;
        },
        
        /**
         * Check all dynamic content on the page
         */
        checkAllContent: function() {
            if (this.updateInProgress) {
                return; // Don't start new update if one is in progress
            }
            
            // Check if we're on a single event page
            if (this.isSingleEventPage()) {
                // For single event pages, check individual dynamic elements
                const forceRefresh = false; // Set to false to re-enable staleness check
                const staleElements = forceRefresh ? $('.evge-dynamic-content').toArray() : this.findStaleElements();
                
                if (staleElements.length > 0) {
                    this.updateSingleEventPage();
                }
            } else {
                // For calendar/list pages, check calendar wrapper timestamp
                this.checkCalendarStaleness();
            }
        },
        
        /**
         * Check if calendar content is stale based on wrapper timestamp
         * Only refreshes if there are no URL parameters (to preserve pagination/filtering)
         */
        checkCalendarStaleness: function() {
            const $calendarWrapper = $('.evge-calendar-wrapper');
            
            if ($calendarWrapper.length === 0) {
                return; // Not a calendar page
            }
            
            // Only refresh if there are no URL parameters
            // This prevents breaking pagination links and filter states
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.toString() !== '') {
                return; // Has URL parameters, skip auto-refresh
            }
            
            const renderedAt = parseInt($calendarWrapper.data('rendered-at'), 10);
            
            if (!renderedAt) {
                return; // No timestamp found
            }
            
            const currentTime = Math.floor(Date.now() / 1000);
            const stalenessThreshold = (typeof window.evge !== 'undefined' && 
                                       window.evge.dynamicContentRefresh && 
                                       window.evge.dynamicContentRefresh.stalenessThreshold) 
                                       ? window.evge.dynamicContentRefresh.stalenessThreshold 
                                       : 180; // Default 3 minutes
            
            const timeDiff = currentTime - renderedAt;

            if (timeDiff > stalenessThreshold) {
                // Content is stale, refresh using calendar.js method
                this.refreshCalendar();
            }
        },
        
        /**
         * Check if we're on a single event page
         */
        isSingleEventPage: function() {
            // Check for single event page indicators
            return $('[data-evge-type="single-event"]').length > 0 || 
                   $('.evge-events-single-main').length > 0;
        },
        
        /**
         * Update single event page - make one API call for all dynamic content
         */
        updateSingleEventPage: function() {
            // TESTING: Get all elements or just stale ones
            const forceRefresh = true; // Set to false to re-enable staleness check
            const staleElements = forceRefresh ? $('.evge-dynamic-content').toArray() : this.findStaleElements();
            
            if (staleElements.length === 0) {
                return;
            }
            
            // Group by event ID (should be same event on single page, but handle multiple)
            const eventGroups = {};
            staleElements.forEach(function(element) {
                const $element = $(element);
                const eventId = $element.data('event-id');
                
                if (!eventId) {
                    return; // Skip if no event ID
                }
                
                if (!eventGroups[eventId]) {
                    eventGroups[eventId] = [];
                }
                
                eventGroups[eventId].push({
                    element: $element,
                    contentType: $element.data('content-type'),
                    updateEndpoint: $element.data('update-endpoint')
                });
            });
            
            // For each event, make one API call to get all content
            Object.keys(eventGroups).forEach(function(eventId) {
                const elements = eventGroups[eventId];
                
                // Collect unique content types needed
                const contentTypes = elements.map(function(item) {
                    return item.contentType;
                });
                
                // Show loading for all elements
                elements.forEach(function(item) {
                    this.showLoading(item.element);
                }.bind(this));
                
                // Make single API call for all content types
                this.updateAllContentForEvent(eventId, elements);
            }.bind(this));
        },
        
        /**
         * Update all dynamic content for a single event in one API call
         */
        updateAllContentForEvent: function(eventId, elements) {
            const self = this;
            this.updateInProgress = true;
            
            // Make AJAX call to get all content at once (using standard pattern)
            window.Evge.ajax({
                data: {
                    action: 'evge_get_dynamic_content',
                    event_id: eventId
                },
                success: function(response) {
                    // Backend sends { success: true, data: { data: { 'registration-status': '...', etc. } } }
                    if (!response || !response.success || !response.data) {
                        console.error('Invalid response format:', response);
                        elements.forEach(function(item) {
                            self.hideLoading(item.element);
                        });
                        self.updateInProgress = false;
                        return;
                    }
                    
                    // Update each element with its corresponding content
                    elements.forEach(function(item) {
                        const $element = item.element;
                        const contentType = item.contentType;
                        
                        // Get content for this type from response.data
                        const newContent = self.extractContent(response, contentType);
                        
                        if (newContent) {
                            // Update content: use inner wrapper if present (block theme), else update element directly
                            const $target = $element.find('.evge-dynamic-content-inner').length
                                ? $element.find('.evge-dynamic-content-inner').first()
                                : $element;
                            $target.html(newContent);
                            
                            // Update timestamp
                            $element.data('rendered-at', Math.floor(Date.now() / 1000));
                            
                            // Hide loading
                            self.hideLoading($element);
                            
                            // Trigger re-initialization
                            self.reinitializeContent($element, contentType);
                        } else {
                            // No content found, just hide loading
                            self.hideLoading($element);
                        }
                    });
                    
                    self.updateInProgress = false;
                },
                error: function(xhr, status, error) {
                    console.error('Error updating dynamic content:', {
                        eventId: eventId,
                        error: error,
                        status: status
                    });
                    
                    elements.forEach(function(item) {
                        self.hideLoading(item.element);
                    });
                    
                    self.updateInProgress = false;
                }
            });
        },
        
        /**
         * Refresh calendar using existing calendar.js refresh method
         * Preserves current filters and pagination state
         */
        refreshCalendar: function() {
            // Use the calendar.js refresh method if available
            if (typeof window.EVGE !== 'undefined' && 
                typeof window.EVGE.Calendar !== 'undefined' && 
                typeof window.EVGE.Calendar.EventCalendar !== 'undefined') {
                
                const $calendarWrapper = $('.evge-calendar-wrapper');
                if ($calendarWrapper.length > 0) {
                    // Read current filters and pagination directly from DOM before creating instance
                    // This ensures we capture the actual current state
                    // Use the same parameter names that getAjaxParams expects
                    const currentMonth = $calendarWrapper.data('current-month') || '';
                    const currentDate = $calendarWrapper.data('current-date') || currentMonth;
                    
                    const currentFilters = {
                        search: $calendarWrapper.find('.evge-search-input').val() || '',
                        venue_id: $calendarWrapper.find('.evge-venue-select').val() || '',
                        time_filter: $calendarWrapper.find('.evge-time-select').val() || 'upcoming',
                        view: $calendarWrapper.find('.evge-view-select').val() || 'list',
                        paged: parseInt($calendarWrapper.data('paged')) || 1,
                        month: currentMonth,
                        current_date: currentDate,
                        calendar_id: $calendarWrapper.data('calendar-id') || ''
                    };
                    
                    // Create a new calendar instance
                    const calendar = new window.EVGE.Calendar.EventCalendar();
                    
                    // Refresh calendar with current filters/pagination preserved
                    // The overrides will be merged with getAjaxParams, so we need to pass all the values
                    calendar.refreshCalendar(currentFilters);
                }
            } else {
                // Fallback: if calendar.js is not available, log warning
                // Do not reload page - let user see the cached content
                console.warn('Calendar refresh method not available. Content may be stale.');
            }
        },
        
        /**
         * Find all elements that are stale
         */
        findStaleElements: function() {
            const stale = [];
            const currentTime = Math.floor(Date.now() / 1000);
            
            $('.evge-dynamic-content').each(function() {
                const $element = $(this);
                const renderedAt = parseInt($element.data('rendered-at'), 10);
                
                if (isNaN(renderedAt)) {
                    return; // Skip if no timestamp
                }  
                const ageInSeconds = currentTime - renderedAt;
                const threshold = typeof window.EVGE.DynamicContentRefresh.STALENESS_THRESHOLD === 'function' 
                    ? window.EVGE.DynamicContentRefresh.STALENESS_THRESHOLD() 
                    : window.EVGE.DynamicContentRefresh.STALENESS_THRESHOLD;
                
                if (ageInSeconds > threshold) {
                    stale.push($element);
                }
            });
            
            return stale;
        },
        
        
        /**
         * Extract content from AJAX response based on content type
         * Backend sends wp_send_json_success( array( 'data' => $response_data ) ), so content is at response.data.data
         */
        extractContent: function(response, contentType) {
            if (!response || !response.data) {
                return null;
            }
            
            // Response structure: { success: true, data: { data: { 'registration-status': '...', 'event-cta': '...', etc. } } }
            const payload = response.data.data || response.data;
            if (payload[contentType]) {
                return payload[contentType];
            }
            
            // Fallback: try direct html property
            if (payload.html) {
                return payload.html;
            }
            
            return null;
        },
        
        /**
         * Show loading indicator for an element
         * Matches existing processing pattern:
         * - For CTA: overlay spinner (centered) using evge-is-processing
         * - For about items and other inline elements: append spinner inline using evge-fade evge-is-processing
         */
        showLoading: function($element) {
            const contentType = $element.data('content-type');
            
            // Check if this is the CTA (should have overlay spinner)
            if (contentType === 'event-cta') {
                // CTA uses overlay pattern: add evge-is-processing and append spinner (centered via CSS)
                $element.addClass('evge-is-processing');
                if ($element.find('.evge-spinner-container').length === 0) {
                    $element.append(window.Evge.spinnerHTML());
                }
            } else {
                // About items and other inline elements: use element pattern
                // Add fade and processing classes, append spinner inline
                $element.addClass('evge-fade evge-is-processing');
                if ($element.find('.evge-spinner-container').length === 0) {
                    $element.append(window.Evge.spinnerHTML());
                }
            }
        },
        
        /**
         * Hide loading indicator for an element
         * Matches existing stopProcessing pattern
         */
        hideLoading: function($element) {
            // Remove processing classes
            $element.removeClass('evge-is-processing evge-fade');
            
            // Remove spinner with fade out (matching existing pattern)
            $element.find('.evge-spinner-container').fadeOut(function() {
                $(this).remove();
            });
        },
        
        /**
         * Re-initialize content that may need it after update
         */
        reinitializeContent: function($element, contentType) {
            // Trigger hook for content re-initialization
            window.EVGE.Hooks.doAction('evge_dynamic_content_refreshed', {
                element: $element,
                contentType: contentType
            });
            
            // Re-initialize modal triggers if present
            if (window.Evge && window.Evge.Modal && window.Evge.Modal.initTriggers) {
                $element.find('.evge-modal-trigger').each(function() {
                    if (!$(this).hasClass('evge-modal-initted')) {
                        // Remove class and let modal system re-initialize
                        $(this).removeClass('evge-modal-initted');
                    }
                });
                window.Evge.Modal.initTriggers();
            }
            
            // Re-initialize registration forms if present
            if (window.EVGE && window.EVGE.Registration && window.EVGE.Registration.Initializer) {
                window.EVGE.Registration.Initializer.initializeMainForm($element);
            }
        },
        
        /**
         * Handle successful completion of all updates
         */
        onUpdatesComplete: function() {
            this.updateInProgress = false;
        },
        
        /**
         * Handle errors during updates
         */
        onUpdateError: function(error) {
            console.error('Dynamic content refresh error:', error);
            this.updateInProgress = false;
        },
        
        /**
         * Cleanup
         */
        destroy: function() {
            this.initialized = false;
            this.updateInProgress = false;
        }
    };

    // Initialize Evge last
    window.evgeInit = function () {
        window.Evge = new Evge();
        window.Evge.createPage();
        
        // Initialize dynamic content refresh
        if (window.EVGE && window.EVGE.DynamicContentRefresh) {
            window.EVGE.DynamicContentRefresh.init();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        window.evgeInit();
    });
});