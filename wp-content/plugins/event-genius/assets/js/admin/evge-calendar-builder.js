(function($) {
    'use strict';

    class CalendarBuilder {
        constructor() {
            this.$sidebar = $('.evge-calendar-builder-sidebar');
            this.$preview = $('.evge-calendar-builder-preview');
            this.$form = $('.evge-calendar-form');
            this.$saveButton = $('.evge-save-calendar');
            this.updateTimeout = null;
            
            if (this.$sidebar.length && this.$preview.length) {
                this.initializeListeners();
            }

            this.initializeFilterBuilder();

            this.$pageTitle = $('.wp-heading-inline');
            this.$colorDot = this.$pageTitle.find('.evge-calendar-color-dot');
            this.$calendarName = $('input[name="calendar_name"]');
            this.$calendarColor = $('input[name="calendar_settings[color]"]');
            
            // Add listeners for dynamic updates
            this.$calendarName.on('input change', () => this.updateTitle());
            this.$calendarColor.on('input change', () => this.updateColorDot());

            // Initialize WordPress color picker
            if (typeof jQuery.wp === 'object' && typeof jQuery.wp.wpColorPicker === 'function') {
                $('.evge-color-field').wpColorPicker({
                    change: () => this.handleFieldChange(),
                    clear: () => this.handleFieldChange()
                });
            }

            // Initialize bulk registration message visibility on page load
            this.initializeBulkRegistrationMessage();
        }

        initializeBulkRegistrationMessage() {
            const $bulkField = this.$sidebar.find('.evge-builder-field-bulk-registration');
            if ($bulkField.length) {
                const $toggle = $bulkField.find('.evge-settings-toggle');
                const $message = $bulkField.find('.evge-bulk-registration-message');
                const $hiddenInput = $bulkField.find('input[name="calendar_settings[bulk_registration_enabled]"]');
                
                // Check both toggle class and hidden input value
                const toggleEnabled = $toggle.hasClass('evge-input-toggle--enabled');
                const inputValue = $hiddenInput.length ? $hiddenInput.val() : 'disabled';
                const isEnabled = toggleEnabled || inputValue === 'enabled';
                
                if (isEnabled) {
                    $message.show();
                } else {
                    $message.hide();
                }
            }
        }

        initializeFilterBarOptionsVisibility() {
            const $toolbarField = this.$sidebar.find('.evge-builder-field').has('input[name="calendar_settings[show_toolbar]"]');
            if ($toolbarField.length) {
                const $toggle = $toolbarField.find('.evge-settings-toggle');
                const $hiddenInput = $toolbarField.find('input[name="calendar_settings[show_toolbar]"]');
                const $filterBarOptions = $('.evge-filter-bar-options');
                
                // Check both toggle class and hidden input value
                const toggleEnabled = $toggle.hasClass('evge-input-toggle--enabled');
                const inputValue = $hiddenInput.length ? $hiddenInput.val() : 'enabled';
                const isEnabled = toggleEnabled || inputValue === 'enabled';
                
                if (isEnabled) {
                    $filterBarOptions.show();
                } else {
                    $filterBarOptions.hide();
                }
            }
        }

        initializeListeners() {
            // Listen for changes to form fields
            this.$sidebar.find('input, select').on('change', () => {
                this.handleFieldChange();
            });
            
            // For color and number inputs, also listen for input event
            this.$sidebar.find('input[type="color"], input[type="number"]').on('input', () => {
                this.handleFieldChange();
            });

            // Add specific handler for events per day input
            this.$sidebar.find('input[name="calendar_settings[events_per_day]"]').on('input change', () => {
                this.handleFieldChange();
            });

            // Listen for toolbar toggle changes
            this.$sidebar.find('.evge-settings-toggle-wrap').on('click', () => {
                this.handleFieldChange();
            });

            // Handle toolbar toggle visibility for filter bar options
            this.$sidebar.on('click', '.evge-settings-toggle', (e) => {
                const $toggle = $(e.currentTarget);
                const $toolbarField = $toggle.closest('.evge-builder-field');
                const $toolbarInput = $toolbarField.find('input[name="calendar_settings[show_toolbar]"]');
                
                // Check if this is the toolbar toggle
                if ($toolbarInput.length) {
                    // Wait for toggle state to update (after evge-settings.js handles it)
                    setTimeout(() => {
                        const $filterBarOptions = $('.evge-filter-bar-options');
                        const toggleEnabled = $toggle.hasClass('evge-input-toggle--enabled');
                        const inputValue = $toolbarInput.val();
                        const isEnabled = toggleEnabled || inputValue === 'enabled';
                        
                        if (isEnabled) {
                            $filterBarOptions.slideDown(200);
                        } else {
                            $filterBarOptions.slideUp(200);
                        }
                    }, 100);
                }
            });

            // Initialize filter bar options visibility on page load
            this.initializeFilterBarOptionsVisibility();

            // Handle bulk registration toggle message visibility
            // Use event delegation and listen after the global toggle handler runs
            this.$sidebar.on('click', '.evge-settings-toggle', (e) => {
                const $toggle = $(e.currentTarget);
                const $field = $toggle.closest('.evge-builder-field-bulk-registration');
                const $message = $field.find('.evge-bulk-registration-message');
                const $hiddenInput = $field.find('input[name="calendar_settings[bulk_registration_enabled]"]');
                
                // Check if this is the bulk registration toggle
                if ($message.length && $hiddenInput.length) {
                    // Wait for toggle state to update (after evge-settings.js handles it)
                    setTimeout(() => {
                        // Check both the toggle class and the hidden input value
                        const toggleEnabled = $toggle.hasClass('evge-input-toggle--enabled');
                        const inputValue = $hiddenInput.val();
                        const isEnabled = toggleEnabled || inputValue === 'enabled';
                        
                        if (isEnabled) {
                            $message.slideDown(200);
                        } else {
                            $message.slideUp(200);
                        }
                    }, 100);
                }
            });

            // Initialize select2 for multiple selects
            $('.evge-builder-field select[multiple]').select2({
                width: '100%',
                closeOnSelect: false
            }).on('change', () => {
                this.handleFieldChange();
            });

            // Prevent form submission (only used for preview)
            this.$form.on('submit', (e) => {
                e.preventDefault();
                this.handleSave();
            });
        }

        getFormData(additionalData = {}) {
            const formArray = this.$form.serializeArray();
            const formData = {};

            // Convert array to object
            $.each(formArray, function() {
                const name = this.name;
                const value = this.value;

                // Handle array notation (e.g., calendar_settings[filters][])
                if (name.includes('[')) {
                    const isOpenBracket = name.endsWith('[]');
                    if (isOpenBracket) {
                        const key = name.slice(0, -2);
                        if (!formData[key]) {
                            formData[key] = [];
                        }
                        formData[key].push(value);
                    } else {
                        const matches = name.match(/^([^\[]+)(\[([^\]]*)\])+/);
                        if (matches) {
                            const keys = name.split(/[\[\]]/).filter(Boolean);
                            let current = formData;
    
                            for (let i = 0; i < keys.length - 1; i++) {
                                const key = keys[i];
                                const nextKey = keys[i + 1];
                                
                                // Initialize the current level if it doesn't exist
                                if (!current[key]) {
                                    // If the next key is empty, we need an array
                                    current[key] = nextKey === '' ? [] : {};
                                }
                                
                                // Move to the next level
                                current = current[key];
                            }
    
                            // Handle the final value
                            const lastKey = keys[keys.length - 1];
                            if (lastKey === '') {
                                // This is an array item
                                try {
                                    const parsed = JSON.parse(value);
                                    current.push(parsed);
                                } catch (e) {
                                    current.push(value);
                                }
                            } else {
                                // This is an object property
                                current[lastKey] = value;
                            }
                        }
                    }
                } else {
                    // Handle regular inputs
                    if (name.endsWith('[]')) {
                        const key = name.slice(0, -2);
                        if (!formData[key]) {
                            formData[key] = [];
                        }
                        formData[key].push(value);
                    } else {
                        if (formData[name]) {
                            if (!Array.isArray(formData[name])) {
                                formData[name] = [formData[name]];
                            }
                            formData[name].push(value);
                        } else {
                            formData[name] = value;
                        }
                    }
                }
            });

            // Add any additional data
            return { ...formData, ...additionalData };
        }

        handleFieldChange() {
            // Clear any pending update
            clearTimeout(this.updateTimeout);

            // Debounce the update
            this.updateTimeout = setTimeout(() => {
                this.updatePreview();
            }, 500);
        }

        updatePreview() {
            let self = this;
            const formData = this.getFormData({
                action: 'evge_update_calendar_preview',
                security: evgeAdmin.nonce
            });

            // Start processing state
            window.EvgeAdmin.startProcessing(self.$preview, 'element');

            // Get filters - they may be stored as JSON strings that need parsing
            let filters = [];
            if (formData['calendar_settings[filters]']) {
                // Filters are stored as an array of JSON strings, parse them
                filters = formData['calendar_settings[filters]'].map(filter => {
                    try {
                        return typeof filter === 'string' ? JSON.parse(filter) : filter;
                    } catch (e) {
                        return filter;
                    }
                });
            } else if (formData.calendar_settings?.filters) {
                // Filters are already parsed
                filters = formData.calendar_settings.filters;
            }

            $.ajax({
                url: evgeAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'evge_update_calendar_preview',
                    security: evgeAdmin.nonce,
                    calendar_id: formData.calendar_id,
                    calendar_settings: JSON.stringify(formData.calendar_settings),
                    calendar_settings_filters: JSON.stringify(filters)
                },
                success: (response) => {
                    // Stop processing state
                    window.EvgeAdmin.stopProcessing(self.$preview, 'element');

                    if (response.success) {
                        this.$preview.find('.evge-preview-content').html(response.data.html);
                        
                        if (window.EventCalendar) {
                            new EventCalendar();
                        }

                        this.showMessage('success', evgeAdmin.i18n.previewUpdated);
                    } else {
                        this.showMessage('error', response.data.message || evgeAdmin.i18n.previewError);
                    }
                },
                error: (xhr, status, error) => {
                    // Stop processing state
                    window.EvgeAdmin.stopProcessing(this.$preview, 'element');
                    
                    console.error('Preview update error:', error);
                    this.showMessage('error', evgeAdmin.i18n.previewError);
                }
            });
        }

        handleSave() {
            const formData = this.getFormData({
                action: 'evge_save_calendar',
                security: evgeAdmin.nonce
            });

            // Start processing state for both the save button and the preview area
            window.EvgeAdmin.startProcessing(this.$saveButton, 'button');
            window.EvgeAdmin.startProcessing(this.$preview, 'element');

            // Ensure calendar_settings is properly structured
            const calendarSettings = {
                ...formData.calendar_settings,
                events_per_day: formData.calendar_settings?.events_per_day || 3
            };

            // Get filters - they may be stored as JSON strings that need parsing
            let filters = [];
            if (formData['calendar_settings[filters]']) {
                // Filters are stored as an array of JSON strings, parse them
                filters = formData['calendar_settings[filters]'].map(filter => {
                    try {
                        return typeof filter === 'string' ? JSON.parse(filter) : filter;
                    } catch (e) {
                        return filter;
                    }
                });
            } else if (formData.calendar_settings?.filters) {
                // Filters are already parsed
                filters = formData.calendar_settings.filters;
            }

            $.ajax({
                url: evgeAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'evge_save_calendar',
                    security: evgeAdmin.nonce,
                    calendar_name: formData.calendar_name,
                    calendar_id: formData.calendar_id,
                    calendar_settings: JSON.stringify(calendarSettings),
                    calendar_settings_filters: JSON.stringify(filters)
                },
                success: (response) => {
                    // Stop processing state for both elements
                    window.EvgeAdmin.stopProcessing(this.$saveButton, 'button');
                    window.EvgeAdmin.stopProcessing(this.$preview, 'area');

                    if (response.success) {
                        if (response.data.calendar_id) {
                            const url = new URL(window.location.href);
                            url.searchParams.set('tag_ID', response.data.calendar_id);
                            window.history.pushState({}, '', url);
                        }

                        this.showMessage('success', evgeAdmin.i18n.saveSuccess);
                    } else {
                        this.showMessage('error', response.data.message || evgeAdmin.i18n.saveError);
                    }
                },
                error: (xhr, status, error) => {
                    // Stop processing state for both elements
                    window.EvgeAdmin.stopProcessing(this.$saveButton, 'button');
                    window.EvgeAdmin.stopProcessing(this.$preview, 'area');

                    console.error('Save error:', error);
                    this.showMessage('error', evgeAdmin.i18n.saveError);
                }
            });
        }

        showMessage(type, message) {
            window.EvgeAdmin.messageBadge(message, type === 'success');
        }

        initializeFilterBuilder() {
            let self = this;
            this.$filterBuilder = $('.evge-filter-builder-wrapper');
            this.$filtersList = $('.evge-filters-list');
            this.$filterType = this.$filterBuilder.find('.evge-filter-type');
            this.$filterAction = this.$filterBuilder.find('select.evge-filter-action');
            this.$filterTerms = this.$filterBuilder.find('.evge-filter-terms select');
            this.$filterStep2 = this.$filterBuilder.find('.evge-filter-step-2');
            this.$filterRelationship = $('.evge-filter-relationship');
            this.$saveFilterButton = this.$filterBuilder.find('.evge-save-filter');
            if (!this.$filterTerms.select2) {
                return;
            }
            // Initialize filter relationship select (keep wrapper for visibility, get select for handler)
            const $filterRelationshipSelect = this.$sidebar.find('select[name="calendar_settings[filter_relationship]"]');
            $filterRelationshipSelect.on('change', () => this.updateFilterRelationshipVisibility());

            // Initialize select2 for filter terms with fixed width
            this.$filterTerms.select2({
                width: '300px', // Set a fixed width
                closeOnSelect: false
            });

            // Handle filter type selection
            this.$filterType.on('change', () => {
                this.handleFilterTypeChange();
                this.updateSaveButtonState();
            });

            // Handle action selection
            this.$filterAction.on('change', () => this.updateSaveButtonState());

            // Handle terms selection
            this.$filterTerms.on('change', () => this.updateSaveButtonState());

            // Handle filter save
            this.$saveFilterButton.on('click', () => this.saveFilter());

            // Initially disable save button
            this.$saveFilterButton.prop('disabled', true);

            // Handle filter removal
            this.$filtersList.on('click', '.evge-remove-filter', (e) => {
                $(e.currentTarget).closest('.evge-filter-item').remove();
                self.updateFilterRelationshipVisibility();
                this.handleFieldChange(); // Update preview
            });

            // Add filter button handler
            $('.evge-add-filter').on('click', () => {
                $('.evge-filter-builder-wrapper').slideDown(200);
                this.$filterRelationship.slideDown(200);
                $(this).text(evgeAdmin.i18n.addAnotherFilter);
            });

            // Initial visibility check for filter relationship
            this.updateFilterRelationshipVisibility();
        }

        handleFilterTypeChange() {
            const type = this.$filterType.val();
            
            if (!type) {
                this.$filterStep2.hide();
                return;
            }

            // Show the rest of the form
            this.$filterStep2.show();

            // Update terms label
            const label = type === 'category' ? 'Categories' : 'Tags';
            this.$filterBuilder.find('.evge-filter-terms-label').text(label);

            // Show filter relationship if there are existing filters
            const hasExistingFilters = this.$filtersList.children().length > 0;
            if (hasExistingFilters) {
                this.$filterRelationship.show();
            }

            // Load terms based on type
            this.loadTerms(type);
        }
        updateSaveButtonState() {
            const terms = this.$filterTerms.select2('data');

            // Enable button only if all fields are filled and terms doesn't include the placeholder
            var isValid = terms && terms.length > 0;
            if (isValid) {
                this.$saveFilterButton.prop('disabled', false);
            } else {
                this.$saveFilterButton.prop('disabled', true);
            }
        }

        async loadTerms(type) {
            const taxonomy = type === 'category' ? 'evge_event_cat' : 'evge_event_tag';
            const placeholder = type === 'category' ? '- Select Category -' : '- Select Tag -';
            
            try {
                const response = await $.ajax({
                    url: evgeAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'evge_get_terms',
                        security: evgeAdmin.nonce,
                        taxonomy: taxonomy
                    }
                });

                if (response.success) {
                    // Clear and populate terms dropdown
                    this.$filterTerms.empty();
                    
                    // Add actual terms
                    response.data.forEach(term => {
                        this.$filterTerms.append(new Option(term.name, term.term_id));
                    });

                    // Update select2 with new options
                    this.$filterTerms.trigger('change');
                    
                    // Reinitialize select2 with fixed width
                    this.$filterTerms.select2('destroy');
                    this.$filterTerms.select2({
                        width: '170px',
                        closeOnSelect: true,
                        placeholder: {
                            id: '', // Required for placeholder to work
                            text: placeholder
                        },
                        allowClear: false,
                        minimumResultsForSearch: 0, // Enable search
                        escapeMarkup: function(markup) {
                            return markup;
                        }
                    });

                    // Update save button state
                    this.updateSaveButtonState();
                }
            } catch (error) {
                console.error('Error loading terms:', error);
            }
        }

        updateFilterRelationshipVisibility() {
            const hasFilters = this.$filtersList.children().length > 0;
            
            // Show relationship field whenever there are existing filters
            if (hasFilters) {
                this.$filterRelationship.show();
            } else {
                this.$filterRelationship.hide();
            }
        }

        saveFilter() {
            const type = this.$filterType.val();
            // Query the action select directly using ID selector for reliability
            const $actionSelect = $('#evge-filter-action');
            const action = $actionSelect.length && $actionSelect.val() ? $actionSelect.val() : 'include';
            const selectedData = this.$filterTerms.select2('data');
            const terms = selectedData.map(item => item.id);

            this.updateSaveButtonState();
            // Get selected term names for display
            const termNames = selectedData.map(item => item.text).join(', ');

            // Truncate term names if too long
            const truncatedTerms = termNames.length > 30 
                ? termNames.substring(0, 27) + '...' 
                : termNames;

            const filterData = {
                type,
                action,
                terms
            };

            // Create filter summary HTML
            const prefix = type === 'category' ? 'Cat:' : 'Tag:';
            const actionText = action === 'exclude' ? '(-)' : '';

            const filterHtml = `
                <div class="evge-filter-item">
                    <input type="hidden" name="calendar_settings[filters][]" value='${JSON.stringify(filterData).replace(/'/g, "&#39;")}'>
                    <div class="evge-filter-content">
                        <div class="evge-filter-terms">
                            <span class="evge-filter-type">${prefix}</span>
                            <span class="evge-filter-names">${truncatedTerms}</span>
                        </div>
                        <div class="evge-filter-meta">
                            ${actionText}
                        </div>
                    </div>
                    <button type="button" class="evge-remove-filter" aria-label="${evgeAdmin.i18n.removeFilter}"><svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.616118 0.616115C1.10427 0.12796 1.89573 0.127962 2.38389 0.616118L4 2.23224L5.61611 0.616118C6.10427 0.127962 6.89573 0.12796 7.38388 0.616115C7.87204 1.10427 7.87204 1.89573 7.38389 2.38388L5.76776 4.00001L7.38389 5.61614C7.87204 6.10429 7.87204 6.89575 7.38388 7.3839C6.89573 7.87206 6.10427 7.87206 5.61611 7.3839L4 5.76778L2.38389 7.3839C1.89573 7.87206 1.10427 7.87206 0.616118 7.3839C0.127962 6.89575 0.12796 6.10429 0.616115 5.61614L2.23224 4.00001L0.616115 2.38388C0.12796 1.89573 0.127962 1.10427 0.616118 0.616115Z" fill="#AAAAAA"/></svg></button>
                </div>
            `;

            // Add to filters list
            this.$filtersList.append(filterHtml);

            // Hide the filter builder
            this.$filterBuilder.slideUp(200);

            // Reset form
            this.$filterType.val('').trigger('change');
            this.$filterTerms.val(null).trigger('change');

            // Show filter relationship if this is the first filter
            this.updateFilterRelationshipVisibility();

            // Update preview
            this.handleFieldChange();
        }

        updateTitle() {
            const name = this.$calendarName.val() || evgeAdmin.i18n.newCalendar;
            this.$pageTitle.contents().filter((_, el) => el.nodeType === 3).remove();
            this.$pageTitle.append(name);
        }

        updateColorDot() {
            this.$colorDot.css('backgroundColor', this.$listColor.val() || 'transparent');
        }
    }

    // Initialize the CalendarBuilder class
    $(document).ready(function() {
        new CalendarBuilder();
    });
})(jQuery); 