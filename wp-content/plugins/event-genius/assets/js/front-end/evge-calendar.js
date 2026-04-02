jQuery(function($) {
    // Namespace our calendar functionality
    window.EVGE = window.EVGE || {};
    window.EVGE.Calendar = window.EVGE.Calendar || {};

    // Calendar class
    window.EVGE.Calendar.EventCalendar = class {
        constructor() {
            this.container = $('.evge-calendar-wrapper');
            if (!this.container.length) return;

            // Get current date from data attribute
            const currentMonth = this.container.data('currentMonth');
            this.currentDate = currentMonth ? new Date(currentMonth + '-03') : new Date();

            this.tooltip = $('.evge-event-tooltip');
            this.currentView = this.container.find('.evge-view-select').val() || this.container.data('view') || 'list';

            this.toolTipCache = {};
            this.eventCache = new Map();
            this.dayEventsCache = new Map();

            this.isRefreshing = false;
            this.pendingTooltips = new Set();

            // Store initial filter values as defaults
            this.defaultFilters = {
                search: this.container.find('.evge-search-input').val() || '',
                venue_id: this.container.find('.evge-venue-select').val() || '',
                time_filter: this.container.find('.evge-time-select').val() || 'upcoming',
                view: this.container.find('.evge-view-select').val() || this.container.data('view') || 'list',
                paged: parseInt(this.container.data('paged')) || 1
            };

            this.init();
        }

        init() {
            this.container = $('.evge-calendar-wrapper');
            this.dayEventsCalendar = $('.evge-day-events-calendar');
            
            this.initializeEventListeners();
            this.initializeResizeObserver();
        }

        initializeEventListeners() {
            if (!this.container.length) return;

            // Remove any existing event listeners
            this.container.off();

            // Add show more events handler
            this.container.on('click', '.evge-show-more-events', (e) => {
                const $calendarDay = $(e.target).closest('.evge-calendar-day');
                $calendarDay.toggleClass('evge-show-all-events');

                $calendarDay.find('.evge-event-card').removeClass('evge-event-hidden');

                // If we're hiding events, scroll back to top of day
                if (!$calendarDay.hasClass('evge-show-all-events')) {
                    $calendarDay[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });

            // Show more filters when "..." button is clicked
            this.container.on('click', '.evge-filter-more-button', (e) => {
                e.preventDefault();
                $(e.currentTarget).closest('.evge-filter-group').addClass('evge-filter-group-expanded');
            });

            // Handle no events found buttons
            this.container.on('click', '.evge-clear-search', () => {
                // Clear search input
                this.container.find('.evge-search-input').val('');
                // Clear any active filters
                this.container.find('.evge-filter-active').removeClass('evge-filter-active');
                // Trigger calendar refresh
                this.refreshCalendar({
                    search: '',
                    paged: 1
                });
            });

            this.container.on('click', '.evge-clear-filters', () => {
                // Clear any active filters
                this.container.find('.evge-filter-active').removeClass('evge-filter-active');
                // Reset all filter inputs
                this.container.find('.evge-venue-select').val('');
                this.container.find('.evge-time-select').val('upcoming');
                this.container.find('.evge-search-input').val('');
                // Trigger calendar refresh
                this.refreshCalendar({
                    search: '',
                    venue_id: '',
                    time_filter: 'upcoming',
                    paged: 1
                });
            });

            this.container.on('click', '.evge-view-all', () => {
                // Clear all filters and search
                this.container.find('.evge-search-input').val('');
                this.container.find('.evge-filter-active').removeClass('evge-filter-active');
                // Set time filter to 'all' to show all events
                this.container.find('.evge-time-select').val('all');
                this.container.find('.evge-venue-select').val('');
                // Trigger calendar refresh with all events and reset filters
                this.refreshCalendar({
                    search: '',
                    time_filter: 'all',
                    venue_id: '',
                    paged: 1
                });
            });

            // Search input with debounce
            let searchTimeout;
            this.container.on('input', '.evge-search-input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Reset page number and update URL
                    this.container.attr('data-paged', 1);
                    const currentFilters = this.getCurrentFilters();
                    currentFilters.paged = 1;
                    this.updateUrlParams(currentFilters);
                    
                    this.refreshCalendar(currentFilters);
                }, 500);
            });

            // Venue select
            this.container.on('change', '.evge-venue-select', (e) => {
                // Reset page number and update URL
                this.container.attr('data-paged', 1);
                const currentFilters = this.getCurrentFilters();
                currentFilters.paged = 1;
                this.updateUrlParams(currentFilters);
                
                this.refreshCalendar(currentFilters);
            });

            // Category select
            this.container.on('change', '.evge-category-select', (e) => {
                // Reset page number and update URL
                this.container.attr('data-paged', 1);
                const currentFilters = this.getCurrentFilters();
                currentFilters.paged = 1;
                this.updateUrlParams(currentFilters);
                
                this.refreshCalendar(currentFilters);
            });

            // Tag select
            this.container.on('change', '.evge-tag-select', (e) => {
                // Reset page number and update URL
                this.container.attr('data-paged', 1);
                const currentFilters = this.getCurrentFilters();
                currentFilters.paged = 1;
                this.updateUrlParams(currentFilters);
                
                this.refreshCalendar(currentFilters);
            });

            // Time filter select
            this.container.on('change', '.evge-time-select', (e) => {
                // Reset page number and update URL
                this.container.attr('data-paged', 1);
                const currentFilters = this.getCurrentFilters();
                currentFilters.paged = 1;
                this.updateUrlParams(currentFilters);
                
                this.refreshCalendar(currentFilters);
            });

            // View select
            const $viewSelect = this.container.find('.evge-view-select');
            if ($viewSelect.length) {
                $viewSelect.val(this.currentView);

                this.container.on('change', '.evge-view-select', (e) => {
                    this.currentView = e.target.value;
                    // Update the data-view attribute on the wrapper
                    this.container.attr('data-view', e.target.value);
                    
                    // Hide any events listed below the calendar (mobile view)
                    if (this.dayEventsCalendar && this.dayEventsCalendar.length) {
                        this.dayEventsCalendar.hide();
                    }
                    
                    // Reset page number and update URL
                    this.container.attr('data-paged', 1);
                    const currentFilters = this.getCurrentFilters();
                    currentFilters.paged = 1;
                    this.updateUrlParams(currentFilters);
                    this.refreshCalendar(currentFilters);
                });
            }

            // Add month selector handling
            const $monthSelector = this.container.find('.evge-month-calendar-month-name');
            if ($monthSelector.length) {
                const $dropdown = this.container.find('.evge-month-dropdown');
                const $yearDisplay = $dropdown.find('.evge-year-display');

                // Initialize with the calendar's current year
                let currentYear = this.currentDate.getFullYear();

                // Update year display
                const updateYearDisplay = () => {
                    $yearDisplay.text(currentYear);
                };
                updateYearDisplay();

                const updateMonthHighlight = () => {
                    const currentMonth = this.currentDate.getMonth() + 1;
                    const displayedYear = parseInt($yearDisplay.text());

                    this.container.find('.evge-month-option').each((i, option) => {
                        const $option = $(option);
                        if (parseInt($option.data('month')) === currentMonth && this.currentDate.getFullYear() === displayedYear) {
                            $option.addClass('evge-active');
                        } else {
                            $option.removeClass('evge-active');
                        }
                    });
                };

                // Show dropdown
                $monthSelector.on('click', () => {
                    $dropdown.toggleClass('evge-active');
                    if ($dropdown.hasClass('evge-active')) {
                        updateMonthHighlight();
                    }
                });

                // Year navigation (just updates display, no AJAX call)
                $dropdown.find('.evge-year-prev').on('click', (e) => {
                    e.stopPropagation();
                    currentYear--;
                    updateYearDisplay();
                    updateMonthHighlight();
                });

                $dropdown.find('.evge-year-next').on('click', (e) => {
                    e.stopPropagation();
                    currentYear++;
                    updateYearDisplay();
                    updateMonthHighlight();
                });

                // Month selection (triggers AJAX call with selected year and month)
                $dropdown.find('.evge-month-grid').on('click', async (e) => {
                    const $monthOption = $(e.target).closest('.evge-month-option');
                    if ($monthOption.length) {
                        const month = $monthOption.data('month').toString().padStart(2, '0');
                        const year = $yearDisplay.text();
                        const selectedDate = `${year}-${month}`;

                        await this.refreshCalendar({ selectedDate: selectedDate });
                        $dropdown.removeClass('evge-active');
                    }
                });

                // Close dropdown when clicking outside
                $(document).on('click', (e) => {
                    if (!$(e.target).closest('.evge-month-selector').length) {
                        $dropdown.removeClass('evge-active');
                    }
                });
            }

            // Navigation
            this.container.find('.evge-nav-button').on('click', (e) => {
                const $button = $(e.target).closest('.evge-nav-button');
                this.handleNavigation(e, $button);
            });

            // Event tooltips
            this.initializeTooltips();

            // More events popup
            this.container.find('.evge-more-events').on('click', (e) => this.showMoreEvents(e));


            // Day click handler for narrow view
            this.container.on('click', '.evge-calendar-day.evge-has-events', async (e) => {
                if (!this.container.hasClass('evge-calendar-narrow')) return;

                const $calendarDay = $(e.target).closest('.evge-calendar-day');
                if (!$calendarDay.length) return;

                const date = $calendarDay.data('date');

                // Remove any existing day events calendar
                // Load and display events for this day
                await this.loadDayEvents(date, $calendarDay);
            });
        }

        async handleNavigation(e, $button) {
            const action = $button.data('action');
            
            const response = await this.fetchCalendarData(
                this.getAjaxParams(null, {
                    action: 'evge_calendar_navigation',
                    overrides: { direction: action }
                })
            );

            if (response.success) {
                const $dynamicContent = this.container.find('.evge-calendar-dynamic-content');
                $dynamicContent.html(response.data.html);
                
                this.currentDate = new Date(response.data.current_date + '-03');
                this.init();

                // Re-initialize modal triggers for the newly loaded content
                if (window.Evge && window.Evge.Modal && window.Evge.Modal.initTriggers) {
                    setTimeout(() => {
                        window.Evge.Modal.initTriggers();
                    }, 100);
                }
            }
        }

        initializeTooltips() {
            // Initialize Tooltipster on event cards
            this.container.find('.evge-event-card').tooltipster({
                content: $('.evge-tooltipster-container').first(),
                contentAsHTML: true,
                interactive: true,
                contentCloning: true,
                animation: 'fade',
                delay: 200,
                functionBefore: (instance, helper) => {
                    const eventId = $(helper.origin).data('eventId');
                    if (!eventId) {
                        console.error('No event ID found on card');
                        return false;
                    }

                    // Load the actual content when tooltip is about to be shown
                    this.loadEventTooltip(eventId, instance);
                    return true; // Allow the tooltip to be shown
                },
            });
        }

        async loadEventTooltip(eventId, tooltipsterInstance) {
            try {
                let tooltipContent;

                // Check cache first
                if (this.eventCache.has(eventId)) {
                    tooltipContent = this.eventCache.get(eventId);
                } else {
                    // If not in cache, fetch and store
                    const response = await this.fetchCalendarData(
                        this.getAjaxParams(this.container, {
                            action: 'evge_get_event_details',
                            includeFilters: true,
                            overrides: { event_id: eventId }
                        })
                    );

                    if (response.success && response.data) {
                        tooltipContent = response.data.html;
                        this.eventCache.set(eventId, tooltipContent);
                    } else {
                        throw new Error('Invalid response format');
                    }
                }

                // Update the tooltip content
                if (tooltipsterInstance) {
                    tooltipsterInstance.content(tooltipContent);
                    if (window.Evge && window.Evge.Modal && window.Evge.Modal.initTriggers) {
                        setTimeout(() => {
                            window.Evge.Modal.initTriggers();
                        }, 100);
                    }
                    
                    // Sync bulk registration button state if event is already selected
                    if (window.EVGE && window.EVGE.BulkRegistration && window.EVGE.BulkRegistration.Panel) {
                        setTimeout(() => {
                            // Find the select button in the tooltip content
                            // Tooltipster stores content in .evge-event-tooltip within .evge-tooltipster-base
                            const $selectButton = $('.evge-tooltipster-base .evge-bulk-select-button');
                            if ($selectButton.length) {
                                const eventId = $selectButton.data('evge-event-id');
                                if (eventId && window.EVGE.BulkRegistration.Panel.selectedEvents[eventId]) {
                                    // Event is already selected, update button state
                                    $selectButton.addClass('evge-selected');
                                    const selectedText = $selectButton.data('selected-text');
                                    if (selectedText) {
                                        $selectButton.text(selectedText);
                                    } else {
                                        $selectButton.text('Selected');
                                    }
                                }
                            }
                        }, 150);
                    }
                }
            } catch (error) {
                console.error('Error loading event tooltip:', error);
                if (tooltipsterInstance) {
                    tooltipsterInstance.content(`
                        <div class="evge-event-list-item">
                            <h3>Error loading event details</h3>
                            <p>Please try again later</p>
                        </div>
                    `);
                }
            }
        }

        showMoreEvents(e) {
            const $day = $(e.target).closest('.evge-calendar-day');
            const date = $day.data('date');
            // Implementation for showing more events popup
            // This could be another AJAX call or reveal hidden events
        }

        async fetchCalendarData(data) {
            try {                
                const response = await window.Evge.ajax({
                    data: data
                });
                return response;
            } catch (error) {
                console.error('Calendar AJAX error:', error);
                return { success: false };
            }
        }

        updateCalendar(html, currentDateDisplay) {
            const $tempDiv = $('<div>').html(html);
            const currentDate = this.currentDate;
            
            if (this.currentView === 'month') {
                this.container.find('.evge-month-calendar-top').show();
            } else {
                this.container.find('.evge-month-calendar-top').hide();
            }

            this.container.find('.evge-ajax-content').replaceWith(
                $tempDiv.find('.evge-ajax-content')
            );

            // Initialize tooltips in new content
            this.initializeTooltips();

            // Re-initialize modal triggers for the newly loaded content
            if (window.Evge && window.Evge.Modal && window.Evge.Modal.initTriggers) {
                setTimeout(() => {
                    window.Evge.Modal.initTriggers();
                }, 100);
            }

            // Update active month in dropdown
            const currentMonth = this.currentDate.getMonth() + 1;
            this.container.find('.evge-month-option').each((i, option) => {
                const $option = $(option);
                if (parseInt($option.data('month')) === currentMonth) {
                    $option.addClass('evge-active');
                } else {
                    $option.removeClass('evge-active');
                }
            });

            // Update year display
            const $yearDisplay = this.container.find('.evge-year-display');
            if ($yearDisplay.length) {
                $yearDisplay.text(currentDate.getFullYear());
            }
        }

        positionTooltip($eventCard) {
            if (!this.tooltip.length || !$eventCard.length) return;

            $eventCard.append( this.tooltip );

            //Determine whether to show the tooltip to the left or right of card
            var containerWidth = this.container.width(),
                $calDay = $eventCard.closest('.evge-calendar-day'),
                cardWidth = $calDay.innerWidth(),
                cardIndex = $calDay.index()+1,
                roomToEdge = containerWidth - (cardIndex * cardWidth),
                rightVal = (7-cardIndex) * cardWidth;

            if( roomToEdge < this.tooltip.width() ){
                this.tooltip.css({'right' : -rightVal, 'left' : 'auto'});
            } else {
                this.tooltip.css({'left' : 0, 'right' : 'auto'});
            }
        }

        async refreshCalendar(params = {}) {
            if (this.isRefreshing) {
                return;
            }
            
            try {
                this.isRefreshing = true;
                
                // Hide any events listed below the calendar (mobile view)
                if (this.dayEventsCalendar && this.dayEventsCalendar.length) {
                    this.dayEventsCalendar.hide();
                }
                
                const $eventCalendar = this.container.find('.evge-event-calendar');
                const $dynamicContent = this.container.find('.evge-calendar-dynamic-content');
                
                // Store the current height
                const currentHeight = $eventCalendar.height();
                $eventCalendar.css('height', currentHeight + 'px');
                
                this.container.addClass('evge-loading');
                
                // Wait for fade out
                await new Promise(resolve => setTimeout(resolve, 300));
                
                const response = await this.fetchCalendarData(
                    this.getAjaxParams(null, {
                        action: 'evge_calendar_navigation',
                        overrides: params
                    })
                );
                
                if (response.success) {
                    // Insert new content while maintaining height
                    $dynamicContent.html(response.data.html);
                    
                    // Get the new height and animate to it
                    const newHeight = $dynamicContent.height();
                    $eventCalendar.css('height', newHeight + 'px');
                    
                    this.currentDate = new Date(response.data.current_date + '-03');
                    
                    // Update URL with current filters
                    const currentFilters = this.getCurrentFilters();
                    this.updateUrlParams(currentFilters);
                    
                    this.init();

                    // Re-initialize modal triggers for the newly loaded content
                    if (window.Evge && window.Evge.Modal && window.Evge.Modal.initTriggers) {
                        setTimeout(() => {
                            window.Evge.Modal.initTriggers();
                        }, 100);
                    }

                    // Add visible class to event cards and remove fixed height
                    requestAnimationFrame(() => {
                        this.container.removeClass('evge-loading');
                        this.container.find('.evge-event-card').addClass('evge-visible');
                        
                        // After transition completes, remove fixed height
                        setTimeout(() => {
                            $eventCalendar.css('height', '');
                        }, 300);
                    });
                }
            } catch (error) {
                this.container.removeClass('evge-loading');
                this.container.find('.evge-event-calendar').css('height', '');
            } finally {
                this.isRefreshing = false;
            }
        }

        initializeResizeObserver() {
            if (!this.container.length) return;

            // preserve the preview
            if ($('.evge-calendar-builder').length) {
                return;
            }

            const resizeObserver = new ResizeObserver(entries => {
                for (const entry of entries) {
                    const width = entry.contentRect.width;
                    this.container.toggleClass('evge-calendar-narrow', width < 800);
                    this.container.toggleClass('evge-calendar-super-narrow', width < 500);
                }
            });

            resizeObserver.observe(this.container[0]);
        }

        async loadDayEvents(date, $calendarDay) {
            let events;

            if (this.dayEventsCache.has(date)) {
                events = this.dayEventsCache.get(date);
            } else {
                const response = await this.fetchCalendarData(
                    this.getAjaxParams($calendarDay.closest('.evge-calendar-wrapper'), {
                        action: 'evge_get_day_events',
                        includeFilters: true,
                        overrides: { date: date }
                    })
                );

                if (response.success) {
                    events = response.data.html;
                    this.dayEventsCache.set(date, events);
                }
            }

            if (events) {
                this.dayEventsCalendar.html(events).show();
                this.dayEventsCalendar[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // Re-initialize modal triggers for the newly loaded day events
                if (window.Evge && window.Evge.Modal && window.Evge.Modal.initTriggers) {
                    setTimeout(() => {
                        window.Evge.Modal.initTriggers();
                    }, 100);
                }

                // Sync bulk registration button states if events are already selected
                if (window.EVGE && window.EVGE.BulkRegistration && window.EVGE.BulkRegistration.Panel) {
                    setTimeout(() => {
                        const $selectButtons = this.dayEventsCalendar.find('.evge-bulk-select-button');
                        $selectButtons.each((index, button) => {
                            const $button = $(button);
                            const eventId = $button.data('evge-event-id');
                            if (eventId && window.EVGE.BulkRegistration.Panel.selectedEvents[eventId]) {
                                // Event is already selected, update button state
                                $button.addClass('evge-selected');
                                const selectedText = $button.data('selected-text');
                                if (selectedText) {
                                    $button.text(selectedText);
                                } else {
                                    $button.text('Selected');
                                }
                            }
                        });
                    }, 150);
                }
            }
        }

        /**
         * Get standardized parameters for AJAX calls
         * @param {Object} $context - jQuery element to get context from (defaults to container)
         * @param {Object} options - Configuration options
         * @param {string} options.action - The AJAX action to call (e.g., 'evge_calendar_navigation', 'evge_get_event_details')
         * @param {Object} options.overrides - Optional overrides for any parameters
         * @param {boolean} options.includeFilters - Whether to include filter parameters (default: true)
         * @returns {Object} Standardized parameters for AJAX calls
         */
        getAjaxParams($context = null, options = {}) {
            // Use provided context or fall back to container
            const $target = $context || this.container;
            
            // Default options
            const defaults = {
                action: 'evge_calendar_navigation',
                includeFilters: true,
                overrides: {}
            };
            
            // Merge provided options with defaults
            const config = { ...defaults, ...options };
            
            // Start with base parameters
            const params = {
                action: config.action,
            };

            // Add filter parameters if requested
            if (config.includeFilters) {
                Object.assign(params, {
                    current_date: $target.data('current-date') || this.currentDate.toISOString().slice(0, 7),
                    view: $target.find('.evge-view-select').val() || $target.data('view') || this.currentView || 'list',
                    month: $target.data('month') || '',
                    search: $target.find('.evge-search-input').val() || '',
                    venue_id: $target.find('.evge-venue-select').val() || '',
                    time_filter: $target.find('.evge-time-select').val() || 'upcoming',
                    paged: $target.data('paged') || 1,
                    calendar_id: $target.data('calendar-id') || '',
                    category_id: $target.find('.evge-category-select').val() || '',
                    tag_id: $target.find('.evge-tag-select').val() || '',
                    date: ''
                });
            }

            // Apply any overrides
            return { ...params, ...config.overrides };
        }

        /**
         * Get current filter values from the form
         * @returns {Object} Current filter values
         */
        getCurrentFilters() {
            return {
                search: this.container.find('.evge-search-input').val() || '',
                venue_id: this.container.find('.evge-venue-select').val() || '',
                category_id: this.container.find('.evge-category-select').val() || '',
                tag_id: this.container.find('.evge-tag-select').val() || '',
                time_filter: this.container.find('.evge-time-select').val() || 'upcoming',
                view: this.container.find('.evge-view-select').val() || this.container.data('view') || 'list',
                paged: parseInt(this.container.data('paged')) || 1
            };
            
        }

        /**
         * Check if current filters match default values
         * @param {Object} filters Current filter values
         * @returns {boolean} True if filters match defaults
         */
        isDefaultFilters(filters) {
            return Object.keys(this.defaultFilters).every(key => 
                filters[key] === this.defaultFilters[key]
            );
        }

        /**
         * Update URL query parameters based on current filters
         * @param {Object} filters Current filter values
         */
        updateUrlParams(filters) {
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            // Remove all existing evge_ prefixed params
            for (const key of params.keys()) {
                if (key.startsWith('evge_')) {
                    params.delete(key);
                }
            }

            // Add non-default filter values as query params
            Object.entries(filters).forEach(([key, value]) => {
                params.set(`evge_${key}`, encodeURIComponent(value));
            });

            // Update URL with new params
            const newUrl = url.pathname + (params.toString() ? `?${params.toString()}` : '');
            window.history.replaceState({}, '', newUrl);
        }
    };

    // Calendar initializer
    window.EVGE.Calendar.Initializer = {
        init: function() {
            if (!this.checkDependencies()) return;
            window.EVGE.Hooks.addAction('evge_page_created', this.handlePageCreated.bind(this));
        },

        checkDependencies: function() {
            if (typeof window.EVGE.Hooks === 'undefined') {
                console.error('EVGE Calendar: Required dependencies not loaded');
                return false;
            }
            return true;
        },

        handlePageCreated: function() {
            if ($('.evge-calendar-wrapper').length) {
                new window.EVGE.Calendar.EventCalendar();
            }
        }
    };

    // Initialize the calendar functionality
    window.EVGE.Calendar.Initializer.init();

    // Handle pagination clicks
    $(document).on('click', '.evge-pagination a', function(e) {
        e.preventDefault();
        
        const $container = $(this).closest('.evge-calendar-wrapper');
        const page = $(this).data('page');
        const $pagination = $(this).closest('.evge-pagination');

        // If this is archive pagination, let WordPress handle it normally
        if ($pagination.hasClass('evge-archive-pagination')) {
            window.location.href = $(this).attr('href');
            return;
        }

        // Show loading state
        $container.addClass('evge-loading');

        // Get standardized parameters and add pagination specific ones
        const eventCalendar = new window.EVGE.Calendar.EventCalendar();
        const ajaxParams = eventCalendar.getAjaxParams($container, {
            overrides: {
                direction: '',
                paged: page
            }
        });

        // Make AJAX request
        window.Evge.ajax({
            data: ajaxParams,
            success: function(response) {
                if (response.success) {
                    // Update the content
                    $container.find('.evge-events-archive-main').html(response.data.html);
                    
                    // Update data attributes
                    $container.attr('data-current-date', response.data.current_date);
                    $container.attr('data-view', ajaxParams.view);
                    $container.attr('data-month', ajaxParams.month);
                    $container.attr('data-paged', page);

                    // Update URL with new page number
                    const currentFilters = eventCalendar.getCurrentFilters();
                    currentFilters.paged = page;
                    eventCalendar.updateUrlParams(currentFilters);

                    // Re-initialize modal triggers for the newly loaded content
                    if (window.Evge && window.Evge.Modal && window.Evge.Modal.initTriggers) {
                        setTimeout(() => {
                            window.Evge.Modal.initTriggers();
                        }, 100);
                    }

                    // Scroll to top of event listings
                    const $eventsArchive = $container.find('.evge-events-archive-main');
                    if ($eventsArchive.length) {
                        $('html, body').animate({
                            scrollTop: $eventsArchive.offset().top - 100
                        }, 300);
                    }
                }
            },
            complete: function() {
                $container.removeClass('evge-loading');
            }
        });
    });
}); 