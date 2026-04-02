jQuery(document).ready(function($) {
	function evgeProcessTimelineSelection() {
		let openType = $('#evge_open_type').val();
		let closeType = $('#evge_close_type').val();
		let cancellationCloseType = $('#evge_cancellation_close_type').val();

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

		$('.evge-cancellation-close-type-sub').each(function () {
			if ($(this).attr('data-type') === cancellationCloseType) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});

	}

	evgeProcessTimelineSelection();

	$('#evge_open_type, #evge_close_type, #evge_cancellation_close_type').on('change', function () {
		evgeProcessTimelineSelection();
	});

	// Template Description Notice Toggling
	function evgeToggleTemplateNotices() {
		const selectedTemplate = $('#evge_event_template').val();
		$('.evge-template-notice').each(function() {
			const $notice = $(this);
			if ($notice.data('template') === selectedTemplate) {
				$notice.slideDown(200);
			} else {
				$notice.slideUp(200);
			}
		});
	} evgeToggleTemplateNotices();

	$('#evge_event_template').on('change', function() {
		evgeToggleTemplateNotices();
	});

	//
	function evgeToggleEmailFromAddress() {
		if ($('#evge-email-address-custom .evge-input-toggle--enabled').length) {
			$('#evge-email-address-custom-value').slideDown();
		} else {
			$('#evge-email-address-custom-value').slideUp();
		}

	} evgeToggleEmailFromAddress();

	// Abandoned Payment Settings Toggle
	function evgeToggleAbandonedPaymentSettings() {
		var $toggle = $('.evge-abandoned-payment-toggle .evge-settings-toggle');
		var isEnabled = $toggle.hasClass('evge-input-toggle--enabled');
		
		// Find all table rows that contain abandoned payment settings
		// Settings are rendered in table rows, so we need to find the rows containing the setting fields
		$('tr').each(function() {
			var $row = $(this);
			// Check if this row contains an abandoned payment setting field
			// The class is applied to input, textarea, or wrapper div elements
			if ($row.find('input.evge-abandoned-payment-setting, textarea.evge-abandoned-payment-setting, select.evge-abandoned-payment-setting, .evge-abandoned-payment-setting').length > 0) {
				if (isEnabled) {
					$row.slideDown();
				} else {
					$row.slideUp();
				}
			}
		});
	}
	
	// Initialize on page load
	evgeToggleAbandonedPaymentSettings();
	
	// Handle toggle click - use event delegation since toggles are handled globally
	$('body').on('click', '.evge-abandoned-payment-toggle .evge-settings-toggle-wrap', function () {
		evgeToggleAbandonedPaymentSettings();
	});

	// Toggles
	$('body').on(
		'click',
		'.evge-settings-toggle',	
		function(e){
			e.preventDefault();

			var $container = $(this).closest('.evge-settings-toggle-wrap');
			var $toggle = $(this);

			if ($toggle.hasClass('evge-input-toggle--enabled')) {
				$toggle.removeClass('evge-input-toggle--enabled').addClass('evge-input-toggle--disabled');
			} else {
				$toggle.removeClass('evge-input-toggle--disabled').addClass('evge-input-toggle--enabled');
			}
			evgeHandleToggle($container.closest('.evge-toggle-setting'));
			
			// Check if this is the email address custom toggle
			if ($toggle.closest('#evge-email-address-custom').length) {
				evgeToggleEmailFromAddress();
			}
			
			// Check if this is the all-day event toggle
			if ($toggle.closest('#evge-single-setting-all-day').length && typeof window.evgeUpdateStartEndDateTimeInputs === 'function') {
				const isEnabled = $toggle.hasClass('evge-input-toggle--enabled');
				window.evgeUpdateStartEndDateTimeInputs(isEnabled);
			}
			
			// Check if this is the PayPal sandbox mode toggle
			if ($toggle.closest('.evge-toggle-setting').find('input[name*="paypal_sandbox_mode"]').length) {
				evgeUpdatePayPalRequiredFields();
			}
		}
	);

	function evgeHandleToggle($context){
		let multi = $context.find('.evge-toggle-setting-enabled').attr('data-multiple') === "1",
			enabled = '';
		$context.find('.evge-settings-toggle-wrap').each(function(){
			if($(this).find('.evge-settings-toggle').hasClass('evge-input-toggle--enabled')){
				if(multi){
					enabled += $(this).attr('data-value') + ',';
				} else{
					enabled = 'enabled';
				}
			} else {
				if(!multi){
					enabled = 'disabled';
				}
			}
		});
		$context.find('.evge-toggle-setting-enabled').val(enabled);
		
		// Update status label if it exists
		$context.find('.evge-toggle-status-label').each(function(){
			var $label = $(this);
			var $toggle = $context.find('.evge-settings-toggle');
			if($toggle.hasClass('evge-input-toggle--enabled')){
				$label.text($label.attr('data-enabled-text') || 'Enabled');
			} else {
				$label.text($label.attr('data-disabled-text') || 'Disabled');
			}
		});
	}

	/**
	 * Gateway settings
	 */
	let $paymentsSettingsTable = $('.evge-collapse-label-column').closest('.form-table');
	$paymentsSettingsTable.find('th').first().hide();
	$paymentsSettingsTable.find('td').first().css('padding','0');

	$( '.evge_gateways_table' ).on(
		'click',
		'.evge-payment-gateway-method-toggle-enabled',
		function () {
			var $link = $( this ),
				$row = $link.closest( 'tr' ),
				$toggle = $link.find( '.evge-input-toggle' );

			var data = {
				action: 'evge_toggle_gateway_enabled',
				nonce : evgeSettings.toggleGatewayEnabledNonce,
				gateway_id: $row.data( 'gateway-id' ),
			};

			$toggle.addClass( 'evge-input-toggle--loading' );

			$.ajax( {
				url: evgeSettings.ajaxUrl,
				data: data,
				dataType: 'json',
				type: 'POST',
				success: function ( response ) {
					if ( true === response.data ) {
						$toggle.removeClass(
							'evge-input-toggle--enabled, evge-input-toggle--disabled'
						);
						$toggle.addClass(
							'evge-input-toggle--enabled'
						);
						$toggle.removeClass(
							'evge-input-toggle--loading'
						);
					} else if ( false === response.data ) {
						$toggle.removeClass(
							'evge-input-toggle--enabled, evge-input-toggle--disabled'
						);
						$toggle.addClass(
							'evge-input-toggle--disabled'
						);
						$toggle.removeClass(
							'evge-input-toggle--loading'
						);
					} else if ( 'needs_setup' === response.data ) {
						$link.closest('tr').addClass('evge-gateway-redirecting').find('.evge-redirect-needed-alert').show();
						setTimeout( function () {
							window.location.href = $link.attr( 'href' );
						}, 2000);
					}
				},
			} );

			return false;
		}
	);


	// Re-order buttons.
	$( '.evge-item-reorder-nav' )
		.find( '.evge-move-up, .evge-move-down' )
		.on( 'click', function () {
			var moveBtn = $( this ),
				$row = moveBtn.closest( 'tr' );

			moveBtn.trigger( 'focus' );

			var isMoveUp = moveBtn.is( '.evge-move-up' ),
				isMoveDown = moveBtn.is( '.evge-move-down' );

			if ( isMoveUp ) {
				var $previewRow = $row.prev( 'tr' );

				if ( $previewRow && $previewRow.length ) {
					$previewRow.before( $row );
					//wp.a11y.speak( params.i18n_moved_up );
				}
			} else if ( isMoveDown ) {
				var $nextRow = $row.next( 'tr' );

				if ( $nextRow && $nextRow.length ) {
					$nextRow.after( $row );
					//wp.a11y.speak( params.i18n_moved_down );
				}
			}

			moveBtn.trigger( 'focus' ); // Re-focus after the container was moved.
			moveBtn.closest( 'table' ).trigger( 'updateMoveButtons' );
		} );

	$( '.evge-item-reorder-nav' )
		.closest( 'table' )
		.on( 'updateMoveButtons', function () {
			var table = $( this ),
				lastRow = $( this ).find( 'tbody tr:last' ),
				firstRow = $( this ).find( 'tbody tr:first' );

			table
				.find( '.evge-item-reorder-nav .evge-move-disabled' )
				.removeClass( 'evge-move-disabled' )
				.attr( { tabindex: '0', 'aria-hidden': 'false' } );
			firstRow
				.find( '.evge-item-reorder-nav .evge-move-up' )
				.addClass( 'evge-move-disabled' )
				.attr( { tabindex: '-1', 'aria-hidden': 'true' } );
			lastRow
				.find( '.evge-item-reorder-nav .evge-move-down' )
				.addClass( 'evge-move-disabled' )
				.attr( { tabindex: '-1', 'aria-hidden': 'true' } );
		} );

	$( '.evge-item-reorder-nav' )
		.closest( 'table' )
		.trigger( 'updateMoveButtons' );

	$('.evge-hidden-field').closest('tr').hide();

	// Placeholder Reference



	function evgeToggleCustomFormatInput() {
		$('.evge-datetime-setting-wrap').each(function () {
			if ($(this).find('select option:selected').val() === 'custom') {
				$(this).find('.evge-datetime-setting-custom').slideDown();
			} else {
				$(this).find('.evge-datetime-setting-custom').slideUp();
			}
		});
	}evgeToggleCustomFormatInput();
	$('.evge-datetime-setting-wrap select').on('change', function () {
		evgeToggleCustomFormatInput();
	});

	// Custom slugs: show/hide slug options
	$('body').on('click', '.evge-show-slug-options', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $wrap = $btn.closest('.evge-custom-slugs-wrap');
		var $content = $wrap.find('.evge-slug-options-content');
		var $showText = $btn.find('.evge-show-slug-options-text');
		var $hideText = $btn.find('.evge-hide-slug-options-text');
		if ($content.attr('hidden')) {
			$content.removeAttr('hidden').slideDown(200);
			$btn.attr('aria-expanded', 'true');
			$showText.hide();
			$hideText.show();
		} else {
			$content.slideUp(200, function () {
				$content.attr('hidden', 'hidden');
			});
			$btn.attr('aria-expanded', 'false');
			$showText.show();
			$hideText.hide();
		}
	});

	function initPlaceholderReference() {
		if (!$('.evge-placeholder-reference').length) return;

		$('.evge-placeholder-reference').each(function () {
			const $reference = $(this);
			$reference.find('.evge-placeholder-tab').first().addClass('evge-active');
			$reference.find('.evge-placeholder-category').first().addClass('evge-active');

			// Tab switching
			$reference.on('click', '.evge-placeholder-tab', function() {
				const category = $(this).data('category');

				$reference.find('.evge-placeholder-tab').removeClass('evge-active');
				$(this).addClass('evge-active');

				$reference.find('.evge-placeholder-category').removeClass('evge-active');
				$reference.find(`.evge-placeholder-category[data-category="${category}"]`).addClass('evge-active');
			});

			// Search functionality
			$reference.on('input', '.evge-placeholder-search-input', function() {
				const searchTerm = $(this).val().toLowerCase();
				if ( searchTerm ) { // if search term is not empty, show all placeholders	
					$reference.find('.evge-show-more-placeholders').trigger('click');
				}

				$reference.find('.evge-placeholder-item').each(function() {
					const $item = $(this);
					const code = $item.find('.evge-placeholder-code').text().toLowerCase();
					const description = $item.find('.evge-placeholder-description').text().toLowerCase();

					if (code.includes(searchTerm) || description.includes(searchTerm)) {
						$item.removeClass('evge-placeholder-search-hidden');
					} else {
						$item.addClass('evge-placeholder-search-hidden');
					}
				});
			});

			// Show more placeholders
			$reference.on('click', '.evge-show-more-placeholders', function() {
				$(this).closest('.evge-placeholder-category').addClass('evge-category-expanded');
			});
		});
		// Set initial active tab


		$(".evge-placeholder-insert").on("click", function() {
			var placeholder = $(this).data("placeholder"),
				$context = $(this).closest(".evge-placeholderable-field");

			var textareaId = $context.find(".wp-editor-area").attr("id");
			var editor = tinyMCE.get(textareaId);

			if (editor && !editor.isHidden()) {
				// Visual mode
				editor.execCommand("mceInsertContent", false, placeholder);
			} else {
				// Text mode
				var $textarea = $context.find(".wp-editor-area");
				var cursorPos = $textarea[0].selectionStart;
				var textBefore = $textarea.val().substring(0, cursorPos);
				var textAfter = $textarea.val().substring(cursorPos);
				$textarea.val(textBefore + placeholder + textAfter);

				// Set cursor position after the inserted text
				var newPos = cursorPos + placeholder.length;
				$textarea[0].setSelectionRange(newPos, newPos);
			}

			// Visual feedback for the click
			$(this).css("background-color", "#e2e2e2");
			setTimeout(() => {
				$(this).css("background-color", "");
			}, 200);
		});
	}
	initPlaceholderReference();

	// Event Settings functionality
	var EvgeSettings = {
		init: function() {
			this.initEventTemplateHandler();
		},
		
		initEventTemplateHandler: function() {
			var self = this;
			
			// Check if we're on the event settings page and the template select exists
			if ($('#evge_event_template').length) {
				// Initial setup
				this.updateEventElementsVisibility();
				
				// Listen for changes to the event template select
				$('#evge_event_template').on('change', function() {
					self.updateEventElementsVisibility();
				});
			}
		},
		
		updateEventElementsVisibility: function() {
			var selectedTemplate = $('#evge_event_template').val();
			var isBlockTheme = window.evgeSettings.isBlockTheme || false;
			var shouldDisableThemeElements = isBlockTheme || selectedTemplate === 'theme';
			
			// Elements that should be disabled for theme templates or block themes
			var themeElements = ['title', 'featured_image'];
			
			// Loop through inputs in the multi-checkbox container
			$('.evge-multi-checkbox-single_event_elements input[type="checkbox"]').each(function() {
				var $input = $(this);
				var elementValue = $input.val();
				var $wrapper = $input.closest('.evge-flex-center');
				
				// Check if this element should be disabled
				if (themeElements.indexOf(elementValue) !== -1) {
					if (shouldDisableThemeElements) {
						// Disable the element
						$wrapper.addClass('evge-disabled-row');
						
						// Add visual styling to the wrapper
						$wrapper.css('opacity', '0.6');
						$wrapper.find('label').css('opacity', '0.5');
						
					} else {
						// Enable the element
						$wrapper.removeClass('evge-disabled-row');
						
						// Remove visual styling
						$wrapper.css('opacity', '');
						$wrapper.find('label').css('opacity', '');
					}
				}
			});
			
			// Show/hide alert message
			this.updateEventElementsAlert(shouldDisableThemeElements, isBlockTheme, selectedTemplate);
		},
		
		updateEventElementsAlert: function(shouldDisable, isBlockTheme, selectedTemplate) {
			var $existingAlert = $('.evge-event-elements-alert');
			var $container = $('.evge-multi-checkbox-single_event_elements');
			
			if (shouldDisable) {
				var message = window.evgeSettings.themeTemplateMessage;
				
				if ($existingAlert.length) {
					$existingAlert.find('p').text(message);
				} else {
					var alertHtml = '<div class="evge-event-elements-alert evge-exclamation-notice" style="margin-bottom: 15px;">' +
						'<div class="evge-notice-icon">' +
						'<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">' +
						'<circle cx="8" cy="8" r="8" fill="#AAAAAA"/>' +
						'<circle cx="8" cy="4" r="1" fill="white"/>' +
						'<rect x="7" y="7" width="2" height="6" fill="white"/>' +
						'</svg>' +
						'</div>' +
						'<p>' + message + '</p>' +
						'</div>';
					
					// Insert alert before the multi-checkbox container
					$container.before(alertHtml);
				}
				
				// Add additional visual indication to the container
				$container.addClass('evge-has-disabled-elements');
				
			} else {
				$existingAlert.remove();
				$container.removeClass('evge-has-disabled-elements');
			}
		},
		
		getElementLabel: function(element) {
			var labels = {
				'title': 'Title',
				'featured_image': 'Featured Image',
				'date': 'Date',
				'locations': 'Locations',
				'map': 'Map',
				'about_details': 'About Details',
				'organizers': 'Organizers',
				'categories': 'Categories',
				'tags': 'Tags',
				'capacity': 'Capacity'
			};
			return labels[element] || element;
		}
	};
	
	// Initialize settings functionality
	EvgeSettings.init();

	/**
	 * Update PayPal required fields based on sandbox mode
	 */
	function evgeUpdatePayPalRequiredFields() {
		// Find the PayPal configuration section (look for PayPal-specific fields)
		const $paypalSection = $('.evge-needs-configuration').has('input[name*="paypal_sandbox_mode"]');
		if (!$paypalSection.length) {
			return;
		}

		// Check if sandbox mode is enabled
		const $sandboxToggle = $paypalSection.find('input[name*="paypal_sandbox_mode"]').closest('.evge-toggle-setting').find('.evge-settings-toggle');
		const isSandboxMode = $sandboxToggle.hasClass('evge-input-toggle--enabled');

		// Get email field values
		const $sandboxEmailInput = $paypalSection.find('input[name*="paypal_sandbox_business_email"]');
		const $liveEmailInput = $paypalSection.find('input[name*="paypal_live_business_email"]');
		const sandboxEmail = $sandboxEmailInput.val() || '';
		const liveEmail = $liveEmailInput.val() || '';

		// Email validation regex
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

		// Determine which field should be required
		let requiredFieldId = '';
		if (isSandboxMode) {
			// Sandbox mode is enabled - require sandbox email
			if (!sandboxEmail || !emailRegex.test(sandboxEmail)) {
				requiredFieldId = 'paypal_sandbox_business_email';
			}
		} else {
			// Sandbox mode is disabled - require live email
			if (!liveEmail || !emailRegex.test(liveEmail)) {
				requiredFieldId = 'paypal_live_business_email';
			}
		}

		// Get the configuration required wrap and notice
		const $fieldsWrap = $paypalSection.find('.evge-configuration-required-wrap');
		const $notice = $paypalSection.find('.evge-alert-notice');
		const $alertIcon = $notice.find('svg');

		// Remove all existing alert icons from email field labels
		$paypalSection.find('label[for="evge_paypal_sandbox_business_email"] svg').remove();
		$paypalSection.find('label[for="evge_paypal_live_business_email"] svg').remove();

		// Update the data-fields attribute
		if (requiredFieldId) {
			$fieldsWrap.attr('data-fields', requiredFieldId);
			$notice.show();

			// Find the label for the required field and add alert icon
			const $fieldLabel = $paypalSection.find('label[for="evge_' + requiredFieldId + '"]');
			if ($fieldLabel.length > 0 && $alertIcon.length > 0) {
				const $clonedIcon = $alertIcon.clone();
				$fieldLabel.append($clonedIcon);
			}
		} else {
			// No fields required - hide notice
			$fieldsWrap.attr('data-fields', '');
			$notice.hide();
		}
	}

	// Initialize PayPal required fields on page load
	evgeUpdatePayPalRequiredFields();

	// Update PayPal required fields when email fields change
	$('body').on('input', 'input[name*="paypal_sandbox_business_email"], input[name*="paypal_live_business_email"]', function() {
		evgeUpdatePayPalRequiredFields();
	});

});