jQuery(function ($) {
    // Namespace our registration form functionality
    window.EVGE = window.EVGE || {};
    window.EVGE.Registration = window.EVGE.Registration || {};

    // Registration form initializer
    window.EVGE.Registration.Initializer = {
        init: function() {
            if (!this.checkDependencies()) return;
            
            // Handle initial page load
            window.EVGE.Hooks.addAction('evge_page_created', this.handlePageCreated.bind(this));
            
            // Handle modal content loading
            window.EVGE.Hooks.addAction('evge_modal_content_loaded', this.handleModalContentLoaded.bind(this));
        },

        checkDependencies: function() {
            if (typeof window.EVGE.Hooks === 'undefined') {
                console.error('EVGE Registration: Required dependencies not loaded');
                return false;
            }
            return true;
        },

        handlePageCreated: function() {
            const $context = this.getContext();
            if (!$context.length) return;

            this.initializeMainForm($context);
            this.initializeStandaloneForms();
        },

        handleModalContentLoaded: function(data) {
            // Check if the loaded content contains a registration form
            const $modalContent = $('.evge-modal-content');
            this.initializeMainForm($modalContent);
            
            // If the modal content contains a registration form, handle edit mode fields
            const $registrationForm = $modalContent.find('#evge-registration-form');
            if ($registrationForm.length) {
                const isEditMode = $registrationForm.find('input[name="registration_id"]').length > 0;
                if (isEditMode) {
                    // Create a temporary form instance to handle edit mode fields
                    const tempForm = new EvgeRegistrationForm($registrationForm);
                    tempForm.handleEditModeFieldsInContext($modalContent);
                }
            }
        },

        getContext: function() {
            return $('.evge-standalone-registration-form').length 
                ? $('.evge-standalone-registration-form').first() 
                : $('.evge-modal-content').first();
        },

        initializeMainForm: function($context) {
            window.Evge.RegistrationForm = new EvgeRegistrationForm($context);
            window.Evge.RegistrationForm.init();
        },

        initializeStandaloneForms: function() {
            if (!$('.evge-standalone-registration-form').length) return;

            window.Evge.StandaloneRegistrationForms = [];
            $('.evge-standalone-registration-form').each((index, element) => {
                // add form source to the form if it doesn't exist
                if (!$(element).find('input[name="evge_form_source"]').length) {
                    $(element).find('form').append('<input type="hidden" name="evge_form_source" value="standalone">');
                }
                if (index > 0) {
                    window.Evge.StandaloneRegistrationForms[index] = new EvgeRegistrationForm($(element).find('form'));
                    window.Evge.StandaloneRegistrationForms[index].init();
                }
            });
        }
    };

    // Initialize the registration form functionality
    window.EVGE.Registration.Initializer.init();

    function EvgeRegistrationForm($registrationForm) {
        this.$element = $registrationForm;

        this.$context = $registrationForm.closest('.evge-modal-content').length ? $registrationForm.closest('.evge-modal-content') : $registrationForm.closest('.evge-standalone-registration-form');
        this.$dynamic = this.$context.find('.evge-left-dynamic').length ? this.$context.find('.evge-left-dynamic') : this.$element;
        if (this.$context.find('.evge-standalone-registration-form-container-inner').length) {
            this.$dynamic = this.$context.find('.evge-standalone-registration-form-container-inner');
        }
        
        // Try to get payment JSON from modal summary first, then from standalone form container
        var paymentJsonAttr = this.$context.find('.evge-modal-summary').attr('data-payment-json');
        if (typeof paymentJsonAttr === 'undefined' || paymentJsonAttr === null) {
            // Fallback to standalone form container data attribute
            paymentJsonAttr = this.$context.attr('data-payment-json');
        }
        this.registrationData = typeof paymentJsonAttr !== 'undefined' && paymentJsonAttr !== null ? JSON.parse(paymentJsonAttr) : false;
        this.fields = [];
        this.Validator = new EvgeValidator();
        this.isEditMode = this.$element.find('input[name="registration_id"]').length > 0;
    }
    EvgeRegistrationForm.prototype = {
        init() {
            this.update();
            this.initTriggers();
            this.initAccessibility();
            this.handleEditModeFields();
        },
        handleEditModeFields() {
            // If this is edit mode, make non-editable fields readonly (PHP outputs data-editable="0" or "1")
            if (this.isEditMode) {
                this.handleEditModeFieldsInContext(this.$context);
            }
        },
        handleEditModeFieldsInContext($context) {
            // Make non-editable fields readonly (PHP outputs data-editable="0", not "false")
            $context.find('.evge-field-wrapper[data-editable="0"]').each(function() {
                const $fieldWrapper = $(this);
                const $input = $fieldWrapper.find('input, select, textarea');
                
                if ($input.length) {
                    // Make the input readonly
                    $input.prop('readonly', true);
                    
                    // For select elements, disable them instead of readonly
                    if ($input.is('select')) {
                        $input.prop('disabled', true);
                    }
                    
                    // Add visual indication that the field is readonly
                    $fieldWrapper.addClass('evge-field-readonly');
                }
            });
        },
        initAccessibility() {
            // Initialize form validation messages
            this.$formMessages = this.$context.find('#evge-form-messages');

            // Handle form validation
            this.$element.on('submit', function (e) {
                this.validateForm(e);
            }.bind(this));

            // Add ARIA attributes to form fields
            this.$context.find('input, select, textarea').each(function () {
                const $field = $(this);
                const $label = $('label[for="' + $field.attr('id') + '"]');

                if ($label.length) {
                    $field.attr('aria-labelledby', $label.attr('id'));
                }

                if ($field.attr('required')) {
                    $field.attr('aria-required', 'true');
                }
            });
        },
        validateForm(e) {
            let isValid = true;
            let firstError = null;

            this.fields.forEach(function (field) {
                if (!field.isValid()) {
                    isValid = false;
                    if (!firstError) {
                        firstError = field.$context;
                    }
                    field.$context.addClass('evge-has-error');
                    field.$input.attr('aria-invalid', 'true');
                } else {
                    field.$context.removeClass('evge-has-error');
                    field.$input.attr('aria-invalid', 'false');
                }
            });

            if (!isValid) {
                this.updateFormMessage(
                    'Please correct the errors in the form before submitting.',
                    'error'
                );
                if (firstError) {
                    firstError.find('input, select, textarea').first().focus();
                }
                return;
            }

            this.submitForm();
        },
        updateFormMessage(message, type = 'info') {
            this.$formMessages
                .attr('role', 'alert')
                .attr('aria-live', 'polite')
                .text(message);
        },
        submitForm() {
            this.updateFormMessage('Processing your registration...', 'info');

            // ... existing form submission code ...

            // After successful submission
            this.updateFormMessage('Registration successful!', 'success');

            // After error
            this.updateFormMessage('There was an error processing your registration. Please try again.', 'error');
        },
        initTriggers() {
            this.initCalculators();
            this.initFields();
            this.initSubmitButton();
        },
        
        startProcessing: function($target, context = 'element') {
            if (context === 'button') {
                $target.wrap('<div class="evge-processing-wrap-flex evge-is-processing"></div>');
                $target.addClass('evge-fade').prop('disabled', true);
                $target.after(window.Evge.spinnerHTML());
            } else if (context === 'area') {
                $target.wrap('<div class="evge-processing-wrap evge-is-processing"></div>');
                $target.addClass('evge-fade');
                $target.after(window.Evge.spinnerHTML());
            } else if (context === 'element') {
                $target.addClass('evge-fade evge-is-processing');
                $target.append(window.Evge.spinnerHTML());
            }
        },
        
        stopProcessing: function($target, context = 'element') {
            setTimeout(function() {
                if (context === 'button') {
                    $target.closest('.evge-processing-wrap-flex').find('.evge-spinner-container').remove();
                    $target.unwrap('evge-processing-wrap');
                    $target.removeClass('evge-fade').prop('disabled', false);
                } else if (context === 'area') {
                    $target.closest('.evge-processing-wrap').find('.evge-spinner-container').remove();
                    $target.removeClass('evge-fade evge-is-processing');
                    $target.unwrap('evge-processing-wrap');
                } else if (context === 'element') {
                    $target.find('.evge-spinner-container').remove();
                    $target.removeClass('evge-fade evge-is-processing');
                }
            }, 500);
        },
        initCalculators() {
            if (this.$context.find('.evge-add').length) {
                this.initQuantityButtons();
            }
        },
        initFields() {
            var self = this;
            self.$context.find('.evge-field-wrapper').each(function () {
                self.fields.push(new EvgeFormField($(this)));
            });
            // loop through fields and call init on each
            this.fields.forEach(function (field) {
                field.init();
            });
        },
        update() {
            this.updateCalculations();
            this.updateDisplay();
        },
        updateCalculations() {
            // Check if this is a bulk order (handle even without registrationData.costs)
            if (this.$context.find('.evge-bulk-order-summary').length) {
                this.updateBulkOrderCalculations();
                return;
            }
            
            if (typeof this.registrationData.costs === 'undefined') {
                return;
            }
            
            // Calculate total quantity including additional guests
            var totalQuantity = this.getTotalQuantity();
            this.$context.find('.evge-line-item-quantity').html(totalQuantity);
            this.$context.find('#evge-line-item-event .evge-line-item-total span').html(this.calculateEventCost());
            this.$context.find('#evge-line-item-subtotal .evge-line-item-total span').html(this.calculateLineItemSubtotal());
            this.$context.find('#evge-line-item-fees .evge-line-item-total span').html(this.calculateFee());
            this.$context.find('#evge-line-item-total .evge-line-item-total span').html(this.calculateTotal());
        },
        updateBulkOrderCalculations() {
            // Get the total quantity (applies to all events in bulk order)
            var totalQuantity = this.getTotalQuantity();
            
            // Update quantity for each event line item
            var self = this;
            var totalCost = 0;
            var currencyBefore = '';
            var currencyAfter = '';
            
            this.$context.find('.evge-bulk-order-summary .evge-cost-item').each(function() {
                var $lineItem = $(this);
                var baseCost = parseFloat($lineItem.attr('data-base-cost')) || 0;
                var eventCost = baseCost * totalQuantity;
                
                // Update quantity display
                $lineItem.find('.evge-line-item-quantity').html(totalQuantity);
                
                // Update cost display
                $lineItem.find('.evge-line-item-cost').html(eventCost.toFixed(2));
                
                // Track currency symbols from first item with cost
                if (eventCost > 0 && !currencyBefore) {
                    currencyBefore = $lineItem.attr('data-currency-before') || '';
                    currencyAfter = $lineItem.attr('data-currency-after') || '';
                }
                
                totalCost += eventCost;
            });
            
            // Update total
            var $totalLine = this.$context.find('#evge-line-item-total');
            if ($totalLine.length) {
                if (totalCost > 0) {
                    $totalLine.show();
                    // Get currency from first event with cost if not already set
                    if (!currencyBefore) {
                        var $firstItem = this.$context.find('.evge-bulk-order-summary .evge-cost-item').first();
                        currencyBefore = $firstItem.attr('data-currency-before') || '';
                        currencyAfter = $firstItem.attr('data-currency-after') || '';
                    }
                    $totalLine.find('.evge-total-cost').html(totalCost.toFixed(2));
                } else {
                    $totalLine.hide();
                }
            }
        },
        updateDisplay() {
            if (typeof this.registrationData.costs === 'undefined') {
                return;
            }
            
            var totalQuantity = this.getTotalQuantity();
            var effectiveMaxQuantity = this.getEffectiveMaxQuantity();
            
            if (totalQuantity >= effectiveMaxQuantity) {
                this.$context.find('.evge-add').addClass('evge-button-disabled');
            } else {
                this.$context.find('.evge-add').removeClass('evge-button-disabled');
            }

            // Check minimum quantity (default to 1 if not set)
            var minQuantity = (this.registrationData && this.registrationData.restrictions && this.registrationData.restrictions.minQuantity !== undefined) 
                ? this.registrationData.restrictions.minQuantity 
                : 1;
            if (totalQuantity <= minQuantity) {
                this.$context.find('.evge-subtract').addClass('evge-button-disabled');
            } else {
                this.$context.find('.evge-subtract').removeClass('evge-button-disabled');
            }
        },
        getEffectiveMaxQuantity() {
            // Default to a high number if restrictions data is not available
            var maxQuantity = (this.registrationData && this.registrationData.restrictions && this.registrationData.restrictions.maxQuantity !== undefined)
                ? this.registrationData.restrictions.maxQuantity
                : 999;

            // If we're in edit mode and have current user capacity data, adjust the max
            if (this.isEditMode && this.registrationData && this.registrationData.restrictions && this.registrationData.restrictions.currentUserCapacity) {
                var currentUserCapacity = this.registrationData.restrictions.currentUserCapacity;
                // Add back the current user's capacity since they're editing their existing registration
                maxQuantity += currentUserCapacity;
            }
            
            return maxQuantity;
        },
        calculateEventCost() {
            var totalQuantity = this.getTotalQuantity();
            var eventCost = (this.registrationData && this.registrationData.costs && this.registrationData.costs.eventCost !== undefined)
                ? this.registrationData.costs.eventCost
                : 0;
            var rawCost = totalQuantity * eventCost;

            return (Math.round(rawCost * 100) / 100).toFixed(2);
        },
        calculateLineItemSubtotal() {
            var self = this;
            this.registrationData.costs.subTotal = 0;
            this.$context.find('.evge-cost-item').each(function () {
                self.registrationData.costs.subTotal = self.registrationData.costs.subTotal + parseFloat($(this).find('.evge-line-item-total span').text());
            });

            this.registrationData.costs.subTotal = (Math.round(this.registrationData.costs.subTotal * 100) / 100).toFixed(2);

            return this.registrationData.costs.subTotal;
        },
        getSelectedGateway() {
            return 'offline';
        },
        /**
         * Gateway key to use for fee/total lookups. Uses selected gateway if present in data,
         * otherwise the first available gateway (e.g. when extension overrides to woocommerce only).
         */
        getEffectiveGateway() {
            var gateways = this.registrationData && this.registrationData.costs && this.registrationData.costs.fees && this.registrationData.costs.fees.gateways ? this.registrationData.costs.fees.gateways : {};
            var selected = this.getSelectedGateway();
            if (gateways[selected]) {
                return selected;
            }
            var keys = Object.keys(gateways);
            return keys.length ? keys[0] : selected;
        },
        feeSettings() {
            let gateway = this.getEffectiveGateway(),
                g = this.registrationData.costs.fees.gateways[gateway];
            if (!g) {
                return { feePercent: 0, feeFlat: 0 };
            }
            let feePercent = parseFloat(g.feePercent),
                feeFlat = parseFloat(g.feeFlat);

            return {
                feePercent: feePercent,
                feeFlat: feeFlat
            }
        },
        calculateFee() {
            let gateway = this.getEffectiveGateway();
            var g = this.registrationData.costs.fees.gateways[gateway];
            if (!g) {
                return 0;
            }
            g.feeCalculated = (this.feeSettings().feePercent / 100) * this.registrationData.costs.subTotal + this.feeSettings().feeFlat;
            g.feeCalculated = (Math.round(g.feeCalculated * 100) / 100).toFixed(2);
            return g.feeCalculated;
        },
        calculateTotal() {
            var gateway = this.getEffectiveGateway(),
                g = this.registrationData.costs.fees.gateways[gateway];
            if (!g) {
                this.registrationData.costs.total = (Math.round(parseFloat(this.registrationData.costs.subTotal || 0) * 100) / 100).toFixed(2);
                return this.registrationData.costs.total;
            }
            var rawTotal = parseFloat(g.feeCalculated) + parseFloat(this.registrationData.costs.subTotal);

            this.registrationData.costs.total = (Math.round(rawTotal * 100) / 100).toFixed(2);

            return this.registrationData.costs.total;
        },
        getTotalQuantity() {
            // First, try to get the quantity from the count display (how many registrations area)
            var countDisplay = this.$context.find('.evge-count span');
            if (countDisplay.length) {
                var displayQuantity = parseInt(countDisplay.text());
                if (!isNaN(displayQuantity) && displayQuantity > 0) {
                    return displayQuantity;
                }
            }
            
            // Fallback to registration data
            var baseQuantity = (this.registrationData && this.registrationData.costs && this.registrationData.costs.quantity !== undefined)
                ? this.registrationData.costs.quantity
                : 1;
            
            // Count additional guest tabs if they exist
            var additionalGuestTabs = this.$context.find('.evge-guest-tab:not([data-guest-number="main"])');
            var additionalGuestCount = additionalGuestTabs.length;

            //evge-guest-count
            if (this.$context.find('.evge-guest-count').length && $('.evge-already-registered-management-modal').length) {
                var guestCount = this.$context.find('.evge-guest-count strong').text();
                return guestCount;
            }
            
            // Return total quantity (base + additional guests)
            return baseQuantity + additionalGuestCount;
        },
        initQuantityButtons() {
            var self = this;
            self.$context.find('.evge-counter-button').on('click', function (event) {
                event.preventDefault();
                if ($(this).hasClass('evge-button-disabled')) {
                    return;
                }
                var buttonType = $(this).hasClass('evge-add') ? 'add' : 'subtract',
                    currentValue = parseInt(self.$context.find('.evge-count span').text()),
                    minQuantity = (self.registrationData && self.registrationData.restrictions && self.registrationData.restrictions.minQuantity !== undefined)
                        ? self.registrationData.restrictions.minQuantity
                        : 1,
                    maxQuantity = self.getEffectiveMaxQuantity();

                if (buttonType === 'add') {
                    currentValue++;
                } else {
                    currentValue--;
                }
                
                // Enforce minimum and maximum limits
                currentValue = Math.max(currentValue, minQuantity);
                currentValue = Math.min(currentValue, maxQuantity);
                
                // Update both the count display and registration data
                self.$context.find('.evge-count span').html(currentValue);
                
                if (typeof self.registrationData.costs !== 'undefined') {
                    self.registrationData.costs.quantity = currentValue;
                } else {
                    self.$context.find('.evge-line-item-quantity').text(currentValue);
                }

                self.update();
            })
        },
        initSubmitButton() {
            var self = this;
            self.$context.find('.evge-registration-form').on('submit', function (event) {
                event.preventDefault();
                if (self.$context.find('button[type=submit]').hasClass('evge-button-disabled') || self.$context.find('button[type=submit]').hasClass('evge-ajax-check-processing')) {
                    return;
                }
                self.clearErrors();

                // Enable this for testing
                $(this).find('button[type=submit]').addClass('evge-button-disabled');

                let form = event.currentTarget;
                let formData = new FormData(form);

                if (self.fieldValuesAreValid(formData)) {
                    self.$context.addClass('evge-is-processing').append(window.Evge.spinnerHTML());
                    // Add the quantity selector to the form submission
                    if (self.$context.find('.evge-count').length) {
                        formData.append('quantity', self.$context.find('.evge-count').text());
                    }

                    window.Evge.ajax({
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            self.$context.removeClass('evge-is-processing');
                            $('.evge-spinner-container div').fadeOut(function () {
                                $('.evge-spinner-container').remove();
                            });
                            // Enable this for testing
                            // Remove the submit button to prevent confusion after successful submission
                            self.$context.find('button[type=submit]').fadeOut(300, function() {
                                $(this).remove();
                            });
                            
     
                            if ($('.evge-modal-col.evge-modal-col-left').length) {
                                self.$dynamic.fadeOut(function () {
                                    self.$context.find('.evge-left-dynamic').html('<div class="evge-message-default" data-evge-refresh-on-close><div class="evge-message-centered-content">' + data.data.response_html).fadeIn(function() {
                                        // Trigger event for other scripts to hook into after content is loaded
                                        $(document).trigger('evge_registration_success', [data.data]);
                                    }) + '</div></div>';
                                });
                            } else {
                                self.$dynamic.fadeOut(function () {
                                    self.$context.append('<div class="evge-left-dynamic evge-standalone-dynamic"></div>');
                                    self.$context.find('.evge-left-dynamic').html('<div class="evge-message-default" data-evge-refresh-on-close><div class="evge-message-centered-content">' + data.data.response_html).fadeIn(function() {
                                        // Trigger event for other scripts to hook into after content is loaded
                                        $(document).trigger('evge_registration_success', [data.data]);
                                    }) + '</div></div>';
                                });
                            }
                        }
                    });
                } else {
                    self.handleErrors();
                    $(this).find('button[type=submit]').removeClass('evge-button-disabled');
                    self.scrollToError();
                }
            });

            self.$context.find('#evge-cancel-form').on('submit', function (event) {
                event.preventDefault();
                var $context = $(this);
                if ($(this).find('button[type=submit]').hasClass('evge-button-disabled')) {
                    return;
                }
                self.clearErrors();

                $context.find('button[type=submit]').addClass('evge-button-disabled');

                let form = event.currentTarget;
                let formData = new FormData(form);
                let field = new EvgeFormField(self.$context.find('#evge_cancel_email').closest('.evge-field-wrapper'));

                if (field.isValid()) {
                    window.Evge.Modal.$element.addClass('evge-is-processing').append(window.Evge.spinnerHTML());
                    $context.find('.evge-field-error').attr('aria-busy', 'true');

                    window.Evge.ajax({
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            window.Evge.Modal.$element.removeClass('evge-is-processing');
                            $context.find('button[type=submit]').removeClass('evge-button-disabled');
                            $('.evge-spinner-container div').fadeOut(function () {
                                $('.evge-spinner-container').remove();
                            });

                            if (data.data.submission_status !== 'success') {
                                if (typeof data.data.error_fields === 'object') {
                                    data.data.error_fields.forEach(function (error) {
                                        self.addError(error);
                                    });
                                    $context.find('.evge-field-error')
                                        .attr('aria-busy', 'false')
                                        .attr('role', 'alert')
                                        .attr('aria-live', 'polite');
                                }
                            } else {
                                // Remove the submit button to prevent confusion after successful cancellation
                                $context.find('button[type=submit]').fadeOut(300, function() {
                                    $(this).remove();
                                });
                                
                                $('.evge-left-dynamic').fadeOut(function () {
                                    $('.evge-modal-col.evge-modal-col-left').append('<div class="evge-left-dynamic" style="visibility: hidden"></div>');
                                    $('.evge-left-dynamic')
                                        .html('<div class="evge-message-center"><div class="evge-message-centered-content">' + data.data.response_html)
                                        .attr('role', 'status')
                                        .attr('aria-live', 'polite')
                                        .fadeIn();
                                });
                            }
                        },
                        error: function() {
                            window.Evge.Modal.$element.removeClass('evge-is-processing');
                            $context.find('button[type=submit]').removeClass('evge-button-disabled');
                            $('.evge-spinner-container div').fadeOut(function () {
                                $('.evge-spinner-container').remove();
                            });
                            $context.find('.evge-field-error')
                                .attr('aria-busy', 'false')
                                .attr('role', 'alert')
                                .attr('aria-live', 'polite')
                                .text(evge.i18n.ajaxError);
                        }
                    });
                } else {
                    field.addError('cancel_email');
                    $(this).find('button[type=submit]').removeClass('evge-button-disabled');
                    self.scrollToError();
                }
            });
        },
        fieldValuesAreValid(formData) {
            var isValid = true;

            this.fields.forEach(function (field) {
                if (!field.isValid()) {
                    isValid = false;
                }
            });

            // Also validate additional guest fields if they exist
            if (window.Evge.AdditionalGuests && typeof window.Evge.AdditionalGuests.validateGuestFields === 'function') {
                var guestValidation = window.Evge.AdditionalGuests.validateGuestFields();
                if (!guestValidation.isValid) {
                    isValid = false;
                }
            }

            return isValid;
        },
        addError(errorName) {
            let field = new EvgeFormField(this.$context.find('#evge_' + errorName).closest('.evge-field-wrapper'));
            field.clearErrors();
            field.$context.addClass('evge-has-error');
            field.$input.attr('aria-invalid', 'true');
            field.errorInput
                .attr('role', 'alert')
                .attr('aria-live', 'polite')
                .attr('aria-atomic', 'true')
                .show();
        },
        handleErrors() {
            this.fields.forEach(function (field) {
                field.clearErrors();

                if (!field.isValid()) {
                    field.$context.addClass('evge-has-error');
                    field.$input.attr('aria-invalid', true);
                }
                
                // Trigger validation state change for each field
                field.triggerValidationStateChange();
            });

            // Also handle guest field errors
            if (window.Evge.AdditionalGuests && typeof window.Evge.AdditionalGuests.validateGuestFields === 'function') {
                window.Evge.AdditionalGuests.validateGuestFields();
            }
        },
        clearErrors() {
            this.fields.forEach(function (field) {
                field.clearErrors();
            });

            // Also clear guest field errors
            if (window.Evge.AdditionalGuests && typeof window.Evge.AdditionalGuests.clearGuestFieldErrors === 'function') {
                window.Evge.AdditionalGuests.clearGuestFieldErrors();
            }
        },
        scrollToError() {
            $('.evge-modal').animate({
                scrollTop: this.$context.find('.evge-has-error').first().offset().top - 400
            }, 750);
        }
    }

    // Make EvgeFormField globally accessible
    window.EvgeFormField = function($formFieldWrap) {
        this.$context = $formFieldWrap;
        this.$input = this.getInputElement($formFieldWrap);
        this.type = this.getFieldType($formFieldWrap);
        this.required = $formFieldWrap.attr('data-required') !== undefined ?
            $formFieldWrap.attr('data-required') === '1' :
            false;
        this.validation = this.getValidationRules();
        this.errorInput = this.$context.find('.evge-field-error');
        this.errorInputText = this.$context.find('.evge-field-error').text();
        this.afterInput = false;
    }

    EvgeFormField.prototype = {
        init() {
            if (this.type === 'email') {
                this.initEmailValidation();
            }
            
            // Initialize file upload change button functionality
            if (this.type === 'file') {
                this.initFileUploadChangeButton();
            }
        },
        initEmailValidation() {
            let self = this;
            let validationTimer;

            this.$input.on('input', function () {
                // Clear any previous timer
                clearTimeout(validationTimer);

                // Set a new timer to validate after user stops typing
                validationTimer = setTimeout(function () {
                    self.validateEmail();
                }, 1500);
            });
        },
        
        initFileUploadChangeButton() {
            let self = this;
            
            // Find the change file button within this field context
            const $changeButton = this.$context.find('.evge-file-upload-change');
            
            if ($changeButton.length) {
                $changeButton.on('click', function(e) {
                    e.preventDefault();
                    
                    // Start processing state using helper method
                    window.Evge.RegistrationForm.startProcessing($changeButton, 'button');
                    
                    // After 2 second delay, fade out the existing file info and show the upload field
                    setTimeout(function() {
                        // Stop processing state using helper method
                        window.Evge.RegistrationForm.stopProcessing($changeButton, 'button');
                        
                        // Fade out the existing file information
                        const $existingInfo = self.$context.find('.evge-file-upload-existing');
                        $existingInfo.fadeOut(300, function() {
                            // Show the file upload field
                            const $uploadField = self.$context.find('.evge-file-upload-field');
                            $uploadField.removeClass('evge-hidden').fadeIn(300);
                            
                            $existingInfo.remove();
                            
                            // Initialize file size validation after the field becomes visible
                            self.initFileSizeValidation();
                        });
                    }, 1000);
                });
            }
            
            // Initialize file size validation (for fields that are already visible)
            this.initFileSizeValidation();
        },
        
        initFileSizeValidation() {
            let self = this;
            const $fileInput = this.$context.find('input[type="file"].evge-file-input');
            
            if (!$fileInput.length) {
                return;
            }
            
            // Get max file size from data attribute (in MB)
            const maxSizeMB = $fileInput.attr('data-max-size');
            
            if (!maxSizeMB || maxSizeMB === '') {
                return;
            }
            
            // Convert MB to bytes
            const maxSizeBytes = parseFloat(maxSizeMB) * 1024 * 1024;
            
            // Remove any existing handlers to prevent duplicates
            $fileInput.off('change.evgeFileSizeValidation');
            
            // Listen for file selection
            $fileInput.on('change.evgeFileSizeValidation', function(e) {
                const file = this.files[0];
                
                if (!file) {
                    self.clearErrors();
                    return;
                }
                
                // Check file size
                if (file.size > maxSizeBytes) {
                    // File is too large
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    const errorMessage = evge.i18n.fileSizeExceeds || 
                        'File size (' + fileSizeMB + ' MB) exceeds maximum allowed size of ' + maxSizeMB + ' MB.';
                    
                    self.addError('file_size');
                    self.errorInput.text(errorMessage);
                    self.$input.attr('aria-invalid', 'true');
                    
                    // Clear the file input
                    $(this).val('');
                } else {
                    // File size is valid
                    self.clearErrors();
                    self.$input.attr('aria-invalid', 'false');
                }
            });
        },
        validateEmail() {
            let self = this;
            let email = this.getValue();
            let eventId = this.$context.closest('.evge-registration-form').find('input[name="event_id"]').val();
            let $submitButton = this.$context.closest('.evge-registration-form').find('button[type=submit]');

            if (!email || !eventId) {
                return;
            }

            // First validate email format using existing validator
            if (!window.Evge.RegistrationForm.Validator.validateEmail(email)) {
                self.addError('email_format');
                self.errorInput.text(self.errorInputText);
                self.$input.removeAttr('data-email-duplicate');
                return;
            }

            if (!evgeRegistrationForm.settings.validateDuplicateEmail) {
                return;
            }

            // Disable submit button while validating
            $submitButton.addClass('evge-ajax-check-processing');

            // If email format is valid and duplicate validation is enabled, check for duplicates
            window.Evge.ajax({
                data: { 
                    action: 'evge_validate_email',
                    email: email,
                    event_id: eventId
                },
                success: function(response) {
                    if (!response.data.prevent_registration) {
                        self.clearErrors();
                        self.$input.attr('aria-invalid', 'false');
                        self.$input.removeAttr('data-email-duplicate');
                    } else {
                        self.addError('email_duplicate');
                        self.errorInput.text(response.data.message);
                        self.$input.attr('data-email-duplicate', 'true');
                    }
                    // Re-enable submit button after validation completes
                    $submitButton.removeClass('evge-ajax-check-processing');
                },
                error: function() {
                    self.addError('email_validation_error');
                    self.errorInput.text('Error validating email. Please try again.');
                    self.$input.removeAttr('data-email-duplicate');
                    // Re-enable submit button on error
                    $submitButton.removeClass('evge-ajax-check-processing');
                }
            });
        },
        getInputElement($wrap) {
            // Handle different input types
            if ($wrap.find('textarea').length) {
                return $wrap.find('textarea');
            } else if ($wrap.find('select').length) {
                return $wrap.find('select');
            } else if ($wrap.find('input[type="radio"]').length) {
                return $wrap.find('input[type="radio"]');
            } else if ($wrap.find('input[type="checkbox"]').length) {
                return $wrap.find('input[type="checkbox"]');
            }
            return $wrap.find('input');
        },

        getFieldType($wrap) {
            if ($wrap.find('textarea').length) return 'textarea';
            if ($wrap.find('select').length) return 'select';
            if ($wrap.find('input[type="radio"]').length) return 'radio';
            if ($wrap.find('input[type="checkbox"]').length) {
                // Check for data-single-checkbox attribute
                if ($wrap.find('input[type="checkbox"]').attr('data-single-checkbox') === 'true') {
                    return 'single-checkbox';
                }
                return 'checkbox';
            }
            if ($wrap.find('input[type="tel"]').length) return 'phone';
            if ($wrap.find('input[type="file"]').length) return 'file';
            if ($wrap.find('input[type="email"]').length) return 'email';

            // Check if this is an email field by name or data attribute
            let fieldName = this.$input.attr('name') || '';
            if (fieldName.includes('email') || $wrap.attr('data-field-type') === 'email') {
                return 'email';
            }

            let inputType = this.$input.attr('type');
            return inputType || 'text';
        },

        getValidationRules() {
            let rules = {
                type: 'length',
                min: this.required ? 1 : 0,
                max: 'none'
            };

            switch (this.type) {
                case 'file':
                    rules.type = 'file';
                    break;
                case 'email':
                    rules.type = 'email';
                    break;
                case 'phone':
                    rules.type = 'phone';
                    break;
                case 'radio':
                case 'checkbox':
                    rules.type = 'checked';
                    break;
                case 'single-checkbox':
                    rules.type = 'single-checked';
                    break;
            }

            return rules;
        },

        getValue() {
            switch (this.type) {
                case 'file':
                    // For file inputs, return the file value or empty string
                    let fileVal = this.$input.val();
                    return fileVal !== null && fileVal !== undefined ? fileVal : '';
                    
                case 'radio':
                    // Return empty string if nothing checked
                    let checkedRadio = this.$input.filter(':checked').val();
                    return checkedRadio || '';

                case 'checkbox':
                    let values = [];
                    this.$input.filter(':checked').each(function () {
                        let val = $(this).val();
                        // Only add defined, non-null values
                        if (val !== undefined && val !== null) {
                            values.push(val);
                        }
                    });
                    return values;

                case 'single-checkbox':
                    // For single checkbox, return the value if checked, otherwise empty string
                    let checkboxVal = this.$input.val();
                    return this.$input.is(':checked') ? true : '';

                case 'select':
                    let selectVal = this.$input.val();
                    return selectVal !== null && selectVal !== undefined ? selectVal : '';

                default:
                    let inputVal = this.$input.val();
                    return inputVal !== null && inputVal !== undefined ? inputVal.trim() : '';
            }
        },

        isValid() {
            let val = this.getValue();
            if (!this.required && (val === '' || val.length === 0)) {
                return true;
            }

            switch (this.validation.type) {
                case 'file':
                    return this.validateFileUpload(val);
                case 'email':
                    if (evgeRegistrationForm.settings.validateDuplicateEmail) {
                        if (this.$input.attr('data-email-duplicate') === 'true') {
                            return false;
                        }
                    }
                    return window.Evge.RegistrationForm.Validator.validateEmail(val);
                case 'phone':
                    return window.Evge.RegistrationForm.Validator.validatePhone(val);
                case 'number':
                    return window.Evge.RegistrationForm.Validator.validateNumber(val, this.validation.min, this.validation.max);
                case 'checked':
                    return window.Evge.RegistrationForm.Validator.validateChecked(val);
                case 'single-checked':
                    return window.Evge.RegistrationForm.Validator.validateSingleChecked(val);
                case 'select':
                    return window.Evge.RegistrationForm.Validator.validateSelect(val);
                case 'count':
                    return window.Evge.RegistrationForm.Validator.validateCount(val, this.validation.acceptableCounts, this.validation.countWhat);
                case 'length':
                default:
                    return window.Evge.RegistrationForm.Validator.validateLength(val, this.validation.min, this.validation.max);
            }
        },
        
        validateFileUpload(val) {
            // Check if there's a file size error (set by initFileSizeValidation)
            if (this.$input.attr('aria-invalid') === 'true' && this.$context.hasClass('evge-has-error')) {
                return false;
            }
            
            // If field is not required, it's always valid (unless there's a size error)
            if (!this.required) {
                return true;
            }
            
            // Check if there's an original file value (meaning a file was previously submitted)
            const $originalInput = this.$context.find('input[name$="_original"]');
            const hasOriginalFile = $originalInput.length > 0 && $originalInput.val() !== '';
            
            // If there's an original file, the field is valid even if no new file is selected
            if (hasOriginalFile) {
                return true;
            }
            
            // If no original file and field is required, check if a new file is selected
            return val !== '' && val !== null && val !== undefined;
        },

        clearErrors() {
            this.$context.removeClass('evge-has-error');
            this.$input.attr('aria-invalid', false);
            this.errorInput.hide();
            
            // Trigger event for field validation state change
            this.triggerValidationStateChange();
        },

        addError(errorType) {
            this.$context.addClass('evge-has-error');
            this.$input.attr('aria-invalid', 'true');
            this.errorInput
                .attr('role', 'alert')
                .attr('aria-live', 'polite')
                .attr('aria-atomic', 'true')
                .show();
            
            // Trigger event for field validation state change
            this.triggerValidationStateChange();
        },

        triggerValidationStateChange() {
            var isValid = this.isValid();
            
            // Trigger custom event for validation state change
            $(document).trigger('evge_field_validation_changed', {
                isValid: isValid,
                fieldContext: this.$context
            });
        }
    }

    function EvgeValidator() {
        this.validateLength = function (val, min, max) {
            let workingMax = max;
            let workingMin = min;

            // Match PHP validator max/min handling
            if (workingMax === 'none' || workingMax > 50000) {
                workingMax = 50000;
            }

            if (workingMin < 0) {
                workingMin = 0;
            }

            return val.length >= workingMin && val.length <= workingMax;
        };

        this.validateEmail = function (val) {
            // Email validation that requires proper domain structure with TLD
            var regEx = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/;
            return regEx.test(val.trim());
        };

        this.validatePhone = function (val) {
            // Count the number of digits in the string
            let digitCount = (val.match(/\d/g) || []).length;
            return digitCount >= 5;
        };

        this.validateNumber = function (val, minVal, maxVal) {
            let workingMax = maxVal;
            let workingMin = minVal;

            // Match PHP validator number ranges
            if (workingMax === 'no-max' || workingMax > 9999999) {
                workingMax = 9999999;
            }

            if (workingMin === 'no-min' || workingMin < -9999999) {
                workingMin = -9999999;
            }

            let num = parseFloat(val);
            return !isNaN(num) && num >= workingMin && num <= workingMax;
        };

        this.validateCount = function (val, acceptableCounts, countWhat = 'numbers') {
            let strippedSubject = val;

            if (countWhat === 'numbers') {
                strippedSubject = val.replace(/\D/g, '');
            } else if (countWhat === 'letters') {
                strippedSubject = val.replace(/[^a-zA-Z]/g, '');
            }

            // Handle both array and comma-separated string formats
            let counts = Array.isArray(acceptableCounts)
                ? acceptableCounts
                : acceptableCounts.split(',').map(n => parseInt(n.trim()));

            // Special case for letters - always return true (matching PHP)
            if (countWhat === 'letters') {
                return true;
            }

            return counts.includes(strippedSubject.length);
        };

        this.validateChecked = function (val) {
            if (Array.isArray(val)) {
                return val.length > 0;
            }
            return !!val;
        };

        this.validateSingleChecked = function (val) {
            return val === true;
        };

        this.validateSelect = function (val) {
            return val !== '' && val !== null && val !== undefined;
        };
    }
});