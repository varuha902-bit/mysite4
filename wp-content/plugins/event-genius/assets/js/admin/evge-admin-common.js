jQuery(document).ready(function($) {
	window.evgeAdminInit = function() {
		window.EvgeAdmin = new EvgeAdmin();
		window.EvgeAdmin.createPage();
	};

	function EvgeAdmin() {
		this.eventElements = {};
	}

	EvgeAdmin.prototype = {
		createPage: function () {
			var self = this;

			if ($('.evge-modal').length) {
				this.Modal = new EvgeModal();

				this.Modal.init();
			}

			this.Actions = new EvgeActions();
			this.Actions.init();

			$('.evge-data-nav-wrap').on('click',function() {
				var nextIndex = $(this).find('.evge-data-nav').attr('data-next-index');
				$('.evge-data-cell').hide();
				$('.evge-data-group-' + nextIndex).show();
			});
			this.initAutoTriggeredAJAXContent();

			$('.evge-no-action, .evge-settings-toggle-wrap').on('click',function(event) {
				event.preventDefault();
			});

			var evgeButtonPreview = new EvgeButtonPreview();
			evgeButtonPreview.init();

			// Send Confirmation Email button (registration management modal – free and Pro)
			$(document).on('click', '.evge-send-confirmation-email-btn', function() {
				var $btn = $(this);
				var registrationId = $btn.data('registration-id');
				var eventId = $btn.data('event-id');
				var i18n = (typeof evgeAdminCommon !== 'undefined' && evgeAdminCommon.i18n) ? evgeAdminCommon.i18n : {};
				if (!registrationId || !eventId) {
					return;
				}
				var $feedback = $btn.closest('.evge-form-button-wrapper').prev('.evge-send-confirmation-feedback');
				$feedback.empty();
				if (window.EvgeAdmin && typeof window.EvgeAdmin.startProcessing === 'function') {
					window.EvgeAdmin.startProcessing($btn, 'button');
				} else {
					$btn.prop('disabled', true);
				}
				$.ajax({
					url: typeof evgeAdminCommon !== 'undefined' ? evgeAdminCommon.ajaxUrl : ajaxurl,
					type: 'POST',
					data: {
						action: 'evge_send_confirmation_email',
						nonce: (typeof evgeAdminCommon !== 'undefined' && (evgeAdminCommon.nonce || evgeAdminCommon.ajaxNonce)) ? (evgeAdminCommon.nonce || evgeAdminCommon.ajaxNonce) : '',
						registration_id: registrationId,
						event_id: eventId
					},
					success: function(response) {
						if (window.EvgeAdmin && typeof window.EvgeAdmin.stopProcessing === 'function') {
							window.EvgeAdmin.stopProcessing($btn, 'button');
						} else {
							$btn.prop('disabled', false);
						}
						if (response.success) {
							var successText = i18n.successfullySentOneEmail || 'Successfully sent 1 email(s).';
							$feedback.html(
								'<div class="evge-email-summary evge-email-success">' +
								'<strong>' + successText + '</strong></div>'
							);
						} else {
							var errMsg = (response.data && response.data.message) ? response.data.message : (i18n.errorOccurred || 'An error occurred.');
							$feedback.html(
								'<div class="evge-email-summary evge-email-error">' +
								'<strong>' + errMsg + '</strong></div>'
							);
						}
					},
					error: function() {
						if (window.EvgeAdmin && typeof window.EvgeAdmin.stopProcessing === 'function') {
							window.EvgeAdmin.stopProcessing($btn, 'button');
						} else {
							$btn.prop('disabled', false);
						}
						var errMsg = i18n.networkError || 'A network error occurred.';
						$feedback.html(
							'<div class="evge-email-summary evge-email-error">' +
							'<strong>' + errMsg + '</strong></div>'
						);
					}
				});
			});

		},
		fetchSelected: function () {
			var selected = [];
			$('.evge-registrations-data').find('.check-column .evge-row-select:checked').each(function() {
				selected.push($(this).val());
			});
			return selected;
		},
		initAutoTriggeredAJAXContent: function() {
			var self = this;
			$('div[data-autotrigger="true"]').each(function() {
				let submitData = JSON.parse($(this).attr('data-evge-ajax')),
					$container = $(this);
				evgeAjax(submitData,function(data) {
					self.ajaxAddContent(data,$container);
				});
			});

		},
		ajaxAddContent (data,$container) {
			var self = this;
			$container.removeClass('evge-is-processing');
			$('.evge-spinner-container div').fadeOut(function() {
				$('.evge-spinner-container').remove();
			});
			$container.find('.evge-dynamic').after(data.data.html);
			$container.find('.evge-dynamic').fadeOut(function() {
				$container.find('.evge-fade-in').fadeIn();
			});
		},
		spinnerHTML: function() {
			return '<div class="evge-spinner-container"><div class="evge-spinner-circle"></div></div>';
		},
		startProcessing: function($target,context,before = false) {
			if (context === 'button') {
				$target.wrap('<div class="evge-processing-wrap-flex evge-is-processing"></div>');
				$target.addClass('evge-fade').prop('disabled',true);
				if (before) {
					$target.before(window.EvgeAdmin.spinnerHTML());
				} else {
					$target.after(window.EvgeAdmin.spinnerHTML());
				}
			} else if (context === 'area') {
				$target.wrap('<div class="evge-processing-wrap evge-is-processing"></div>');
				$target.addClass('evge-fade');
				$target.after(window.EvgeAdmin.spinnerHTML());
			}else if (context === 'element') {
				$target.addClass('evge-fade evge-is-processing');
				$target.append(window.EvgeAdmin.spinnerHTML());
			}
		},
		stopProcessing: function($target,context) {
			setTimeout(function() {
				if (context === 'button') {
					$target.closest('.evge-processing-wrap-flex').find('.evge-spinner-container').remove();
					$target.unwrap('evge-processing-wrap');
					$target.removeClass('evge-fade').prop('disabled',false);
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
		messageBadge: function(message,success) {
			$('body').append($('.evge-message-badge-success'));
			$('body').append($('.evge-message-badge-error'));
			$('.evge-message-badge').find('.evge-badge-message').html(message);

			if (success) {
				var $target = $('.evge-message-badge-success');
			} else {
				var $target = $('.evge-message-badge-error');
			}

			$target.toggle( 'slide',function() {
				$('.evge-message-badge-inner').css('visibility','visible');
			} );

			$target.delay(3000).toggle( 'slide',function() {
				$('.evge-message-badge').fadeOut( function (){
					$('.evge-message-badge-inner').css('visibility','hidden');
				});
			});
		}

	};


	function EvgeModal() {
		this.$element = $('.evge-modal');
	}

	EvgeModal.prototype = {
		init () {
			this.maybeAutoTrigger();
			this.initTriggers();
		},
		maybeAutoTrigger () {
			if ($('.evge-autotrigger-modal').length) {
				this.$element.find('.evge-modal-placeholder').replaceWith($('.evge-autotrigger-modal'));
				this.openModal();
			}
		},
		applySettings ( settings ) {
			if (typeof settings.width !== 'undefined') {
				if (settings.width === 'narrow') {
					this.$element.removeClass('evge-medium-max-width-modal evge-maximum-max-width-modal evge-small-max-width-modal');
					this.$element.addClass('evge-narrow-max-width-modal');
				} else if (settings.width === 'small') {
					this.$element.removeClass('evge-medium-max-width-modal evge-narrow-max-width-modal evge-maximum-max-width-modal evge-recurrence-modal');
					this.$element.addClass('evge-small-max-width-modal');
				} else if (settings.width === 'max') {
					this.$element.removeClass('evge-medium-max-width-modal evge-narrow-max-width-modal evge-small-max-width-modal evge-recurrence-modal');
					this.$element.addClass('evge-maximum-max-width-modal');
				} else {
					this.$element.removeClass('evge-narrow-max-width-modal evge-maximum-max-width-modal evge-small-max-width-modal evge-recurrence-modal');
					this.$element.addClass('evge-medium-max-width-modal');
				}
			}

			// Handle no-header setting for alert/confirmation modals
			if (typeof settings.noHeader !== 'undefined' && settings.noHeader === true) {
				this.$element.addClass('evge-modal-no-header');
			} else {
				this.$element.removeClass('evge-modal-no-header');
			}

		},
		initTriggers () {
			var self = this;
			$('.evge-modal-trigger').each(function() {
				if ($(this).hasClass('evge-modal-initted')) {
					return;
				}
				$(this).addClass('evge-modal-initted');
				var contentType = 'none';

				//evge-is-processing
				if (typeof $(this).attr('data-evge-modal-content') !== 'undefined') {
					contentType = $(this).attr('data-evge-modal-content');
				}

			$(this).on('click',function(event) {
				event.preventDefault();
				self.openModal();
				self.loadingContentState();
				if (contentType === 'ajax') {
					var toSubmit = JSON.parse($(this).attr('data-evge-ajax')),
						$context = $(this);

					toSubmit.selected_registrations = window.EvgeAdmin.fetchSelected();

					evgeAjax(toSubmit,function( data ) {
						self.ajaxAddContent(data, $context);
					});
				}
			})

			});

			$('.evge-modal-backdrop, .evge-action-modal-close').on('click',function () {
				self.closeModal();
			});

			$('.evge-registrations-data').find('.check-column input').on('change',function() {
				//evge-toggleable-button
				if (window.EvgeAdmin.fetchSelected().length) {
					$('.evge-toggleable-button').removeClass('evge-disabled').prop('disabled',false);
				} else {
					$('.evge-toggleable-button').addClass('evge-disabled').prop('disabled',true);

				}
			});
		},
		ajaxAddContent (data,$self) {
			var self = this;
			this.initRegistrationSettings();
			
			// Store current height before content change
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
			}, 300, function() {
				// Remove fixed height after animation
				$modalContent.css('height', '');
				self.contentLoadedState();
			});

			if (self.$element.find('.evge-registration-form').length) {
				window.EvgeAdmin.RegistrationForm = new EvgeRegistrationForm($('.evge-registration-form').first());
				window.EvgeAdmin.RegistrationForm.init();
			}
			if (self.$element.find('.evge-email-form').length) {
				window.EvgeAdmin.EmailForm = new EvgeEmailForm($('.evge-email-form').first());
				window.EvgeAdmin.EmailForm.init();
			}
			if ( typeof $self.attr('data-evge-modal-settings') !== 'undefined' ) {
				this.applySettings(JSON.parse($self.attr('data-evge-modal-settings')));
			} else if ($('.evge-modal-settings').length) {
				this.applySettings(JSON.parse($('.evge-modal-settings').attr('data-evge-modal-settings')));
			} else {
				this.applySettings({width: 'medium'});
			}
			self.$element.find('.evge-in-modal-action').each(function() {
				$(this).on('click',function() {
					var submitData = JSON.parse($(this).attr('data-evge-ajax')),
						$context = $(this);
					evgeAjax(submitData,function(data) {
						self.ajaxAddContent(data,$context);
					})
				})
			});

			self.$element.removeClass('evge-is-processing');

			// Re-initialize modal triggers for any new buttons in the loaded content
			// This allows buttons inside AJAX-loaded modal content to work
			self.$element.find('.evge-modal-trigger').each(function() {
				if (!$(this).hasClass('evge-modal-initted')) {
					$(this).addClass('evge-modal-initted');
					var contentType = 'none';
					if (typeof $(this).attr('data-evge-modal-content') !== 'undefined') {
						contentType = $(this).attr('data-evge-modal-content');
					}
					$(this).on('click', function(event) {
						event.preventDefault();
						self.openModal();
						self.loadingContentState();
						if (contentType === 'ajax') {
							var toSubmit = JSON.parse($(this).attr('data-evge-ajax')),
								$context = $(this);
							toSubmit.selected_registrations = window.EvgeAdmin.fetchSelected();
							evgeAjax(toSubmit, function(data) {
								self.ajaxAddContent(data, $context);
							});
						}
					});
				}
			});

			$(document).trigger('evge_modal_content_loaded', [self.$element]);
			$(document).trigger('evge_admin_modal_opened');
		},
		initRegistrationSettings() {
			this.$element.removeClass('evge-narrow-max-width-modal evge-recurrence-modal');
			this.$element.addClass('evge-medium-max-width-modal');
		},
		loadingContentState() {
			this.$element.addClass('evge-is-processing').append(window.EvgeAdmin.spinnerHTML())

		},
		contentLoadedState() {
			this.$element.removeClass('evge-is-processing');
			$('.evge-spinner-container div').fadeOut(function() {
				$('.evge-spinner-container').remove();
			});

		},
		openModal () {
			$('body').addClass('evge-modal-is-open');
		},
		closeModal () {
			// Check if modal content contains element with data-evge-refresh-on-close attribute
			var shouldRefresh = this.$element.find('[data-evge-refresh-on-close]').length > 0;
			
			$('body').removeClass('evge-modal-is-open');
			
			// Trigger custom event for admin modal close
			$(document).trigger('evge_admin_modal_closed');
			
			// Refresh page if data attribute is present in modal content
			if (shouldRefresh) {
				location.reload();
			}
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
			$('.evge-action-trigger').each(function() {
				$(this).on('click',function(event) {
					event.preventDefault();
					var $self = $(this);
					window.EvgeAdmin.Modal.$element.addClass('evge-is-processing').append(window.EvgeAdmin.spinnerHTML());

					var submitData = JSON.parse($(this).attr('data-evge-ajax'));
					setTimeout(function() {
						evgeAjax(submitData,function(data) {
							self.ajaxAddContent(data,$self);
						})
					}, 500)


				})
			});

			$('#evge-show-canceled').on('click',function(event) {
				event.preventDefault();
				$('.evge-show-canceled-row').remove();

				$('.evge-canceled-reg-row').fadeIn(function() {

				})

			});

		},
		ajaxAddContent (data) {
			var self = this;
			window.EvgeAdmin.Modal.$element.removeClass('evge-is-processing');
			$('.evge-spinner-container div').fadeOut(function() {
				$('.evge-spinner-container').remove();
			});
			$('.evge-dynamic').fadeOut(function() {
				$('.evge-dynamic').replaceWith(data.data.html);
				$('.evge-dynamic').fadeIn();
			});
		},
	}


	function EvgeRegistrationForm($registrationForm) {
		this.$element = $registrationForm;
		this.$context = $registrationForm.closest('.evge-modal-content');
	}

	EvgeRegistrationForm.prototype = {
		init() {
			this.initTriggers();
		},
		initTriggers () {
		},
	}

	function EvgeEmailForm($emailForm) {
		this.$element = $emailForm;
		this.$context = $emailForm.closest('.evge-modal-content');
	}

	EvgeEmailForm.prototype = {
		init() {
			this.initTriggers();
		},
		initTriggers() {
			var self = this;
			
			// Handle email template selection
			this.$element.find('#evge_email_template').on('change', function() {

			});
			
					// Form submission is handled by the pro version for enhanced functionality
		// including TinyMCE editor support and better validation
		}
	}

	function EvgeButtonPreview() {
	}

	EvgeButtonPreview.prototype = {
		init() {
			this.initTriggers();
		},
		initTriggers () {
			let self = this;
			if ( $('.evge-colorpicker').length ) {
				// Check if wpColorPicker is available before using it
				if (typeof jQuery.wp === 'object' && typeof jQuery.wp.wpColorPicker === 'function') {
					$('.evge-colorpicker').wpColorPicker({
							change: function (event, ui) {
								var element = event.target;
								var color = ui.color.toString();
								var $wrapEl = false;
								if ($(element).closest('.evge-button-preview-wrap').length) {
									$wrapEl = $(element).closest('.evge-button-preview-wrap');
								} else if ($(element).closest('[data-evge-preview-trigger]').length) {
									var value = $(element).closest('[data-evge-preview-trigger]').attr('data-evge-preview-trigger');
									$wrapEl = $('[data-evge-preview="'+value+'"]').closest('.evge-button-preview-wrap');
								}
								if ($(this).hasClass('evge-button-preview-bg')) {
									self.updateButton($wrapEl, 'evge-button-preview-bg', color);
								} else if ($(this).hasClass('evge-button-preview-tc')) {
									self.updateButton($wrapEl, 'evge-button-preview-tc', color);
								} else if ($(this).hasClass('evge-button-preview-bordercolor')) {
									self.updateButton($wrapEl, 'evge-button-preview-bordercolor', color);
								}
							},
							clear: function (event, ui) {
								var element = event.target;
								var $wrapEl = false;
								var $context = $(element).closest('.wp-picker-container');
								if ($(element).closest('.evge-button-preview-wrap').length) {
									$wrapEl = $(element).closest('.evge-button-preview-wrap');
								} else if ($(element).closest('[data-evge-preview-trigger]').length) {
									var value = $(element).closest('[data-evge-preview-trigger]').attr('data-evge-preview-trigger');
									$wrapEl = $('[data-evge-preview="'+value+'"]').closest('.evge-button-preview-wrap');
								}
								if ($context.find('.evge-button-preview-bg').length) {
									self.updateButton($wrapEl, 'evge-button-preview-bg', '');
								} else if ($context.find('.evge-button-preview-tc').length) {
									self.updateButton($wrapEl, 'evge-button-preview-tc', '');
								} else if ($context.find('.evge-button-preview-bordercolor').length) {
									self.updateButton($wrapEl, 'evge-button-preview-bordercolor', '');
								}
							}
						}
					);
				}
			}
			$('.evge-button-preview-wrap').each(function() {
				$(this).find('input').on('change',function() {
					if ($(this).hasClass('evge-button-preview-text')) {
						self.update($(this).closest('.evge-button-preview-wrap'));
					}
				});
			});
		},
		findPreview (name) {
			if ( $('[data-evge-preview="'+name+'"]').length ) {
				return $('[data-evge-preview="'+name+'"]');
			}
			return false;
		},
		update ($context) {
			var $buttonPreview = $context.find('.evge-button-preview');
			var $buttonText = $context.find('.evge-button-preview-text').val();

			$buttonPreview.html($buttonText);
		},
		updateButton ($context,element,color) {
			if (element === 'evge-button-preview-tc') {
				$context.find('.evge-button-preview').css('color', color);
			} if (element === 'evge-button-preview-bordercolor' ) {
				if (color === '') {
					$context.find('.evge-button-preview').css('border', '');
				} else {
					if (!$context.find('.evge-button-preview').css('border-width') || $context.find('.evge-button-preview').css('border-width') === '0px') {
						$context.find('.evge-button-preview').css('border', '1px solid ' + color);
					} else {
						$context.find('.evge-button-preview').css('border-color', color);
					}
				}

			} else if (element === 'evge-button-preview-bg') {
				$context.find('.evge-button-preview').css('background-color', color);
			}
		}
	}


	function evgeAjax(submitData,onSuccess) {
		submitData.nonce = evgeAdminCommon.ajaxNonce;
		$.ajax({
			url: evgeAdminCommon.ajaxUrl,
			type: 'post',
			data: submitData,
			success: onSuccess
		});
	}

	window.evgeAdminInit();

	$('.evge-notice-action, .evge-notice-close').on('click', function(e) {
		const action = $(this).data('action');
		let $context = $(this).closest('.evge-dashboard-notice'),
			$button = $(this),
			redirect = $(this).data('redirect') === 1;
		if (action) {
			e.preventDefault();
			if ( action === 'step_trigger' ) {
				let $step2 = $(this).closest('.evge-dashboard-notice').find('.evge-notice-step-2');
				$(this).closest('.evge-notice-content').fadeOut(function() {
					$step2.fadeIn();
				});

				return;
			}
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'evge_notice_action',
					id: $context.data('id'),
					notice_action: action,
					nonce: evgeAdminCommon.ajaxNonce
				},
				success: function(response) {
					if (response.success) {
						// Remove the notice if it was dismissed
						if (action === 'dismiss' || action === 'close' || action === 'later') {
							$button.closest('.evge-dashboard-notice').fadeOut();
							if (redirect && typeof $button.attr('href') !== 'undefined' && $button.attr('href') !== '') {
								window.location.href = $button.attr('href');
							}
						}
					}
				}
			});
		}
	});

	// Copy functionality
	$(document).on('click', '[data-evge-copy-trigger]', function() {
		// Get the target selector from the button's data attribute
		const targetSelector = $(this).data('evgeCopyTrigger');

		// Find the element containing the content to copy
		const $content = $(`[data-evge-copy-content="${targetSelector}"]`);

		if ($content.length) {
			// Create temporary textarea
			const $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val($content.text()).select();
			document.execCommand('copy');
			$temp.remove();

			// Optional: Show success message
			const $success = $(`[data-evge-copy-success="${targetSelector}"]`);
			if ($success.length) {
				$success.fadeIn(200).delay(1500).fadeOut(200);
			}
		}
	});

	$(document).ready(function () {
		$('.evge-copy-button').on('click', function () {
			const $button = $(this);
			let textToCopy;
			
			// Check if button has data-copy-text attribute (compact copy buttons)
			if ($button.data('copy-text')) {
				textToCopy = $button.data('copy-text');
			} else {
				// Fallback to original behavior (support page copy buttons)
				textToCopy = $button.closest('.evge-support-section').find('.evge-copyable-content').text();
			}
			
			navigator.clipboard.writeText(textToCopy).then(function () {
				const originalText = $button.text();
				$button.text('Copied!');
				setTimeout(function () {
					$button.text(originalText);
				}, 2000);
			}).catch(function (err) {
				console.error('Failed to copy text: ', err);
			});
		});
	});

	// Generic collapsible toggle handler
	// Uses data-evge-collapsible-target on button to match data-evge-collapsible-id on content
	$(document).on('click', '.evge-collapsible-toggle', function(e) {
		e.preventDefault();
		var $button = $(this);
		var targetId = $button.data('evgeCollapsibleTarget');
		var $content = $('[data-evge-collapsible-id="' + targetId + '"]');
		var $iconText = $button.find('.evge-icon-text');
		var isExpanded = $button.attr('aria-expanded') === 'true';

		if (isExpanded) {
			$content.slideUp();
			$button.attr('aria-expanded', 'false');
			// Change text to "Show More"
			$iconText.contents().filter(function() {
				return this.nodeType === 3;
			}).each(function() {
				if (this.textContent.trim() === 'Show Less') {
					this.textContent = 'Show More';
				}
			});
		} else {
			$content.slideDown();
			$button.attr('aria-expanded', 'true');
			// Change text to "Show Less"
			$iconText.contents().filter(function() {
				return this.nodeType === 3;
			}).each(function() {
				if (this.textContent.trim() === 'Show More') {
					this.textContent = 'Show Less';
				}
			});
		}
	});

	// Handle "Other Available Blocks" Show More button (legacy support)
	$(document).on('click', '.evge-other-blocks-show-more', function(e) {
		e.preventDefault();
		var $button = $(this);
		var $content = $button.closest('.evge-other-blocks-intro').next('.evge-other-blocks-content-wrapper');
		var isExpanded = $button.attr('aria-expanded') === 'true';

		if (isExpanded) {
			$content.slideUp();
			$button.attr('aria-expanded', 'false');
			$button.show();
		} else {
			$content.slideDown();
			$button.attr('aria-expanded', 'true');
			$button.hide();
		}
	});

	// Post Editor Blocks Notice
	if ($('#evge-post-editor-blocks-notice').length) {
		var $notice = $('#evge-post-editor-blocks-notice');
		var $expandable = $notice.find('.evge-post-editor-blocks-notice-expandable');
		var $showMoreBtn = $notice.find('.evge-post-editor-blocks-notice-show-more');
		var $details = $notice.find('.evge-post-editor-blocks-notice-details');
		var $dismissBtn = $notice.find('.evge-post-editor-blocks-notice-dismiss');
		var $dismissBtnText = $notice.find('.evge-post-editor-blocks-notice-dismiss-btn');

		// Show notice with fade in
		$notice.fadeIn(300);

		// Handle Show More button
		$showMoreBtn.on('click', function(e) {
			e.preventDefault();
			var isExpanded = $(this).attr('aria-expanded') === 'true';
			var $iconText = $(this).find('.evge-icon-text');

			if (isExpanded) {
				$details.slideUp(200);
				$(this).attr('aria-expanded', 'false');
				$iconText.contents().filter(function() {
					return this.nodeType === 3;
				}).each(function() {
					if (this.textContent.trim() === 'Show Less') {
						this.textContent = 'Show More';
					}
				});
			} else {
				$details.slideDown(200);
				$(this).attr('aria-expanded', 'true');
				$iconText.contents().filter(function() {
					return this.nodeType === 3;
				}).each(function() {
					if (this.textContent.trim() === 'Show More') {
						this.textContent = 'Show Less';
					}
				});
			}
		});

		// Handle dismiss button (X icon in header)
		$dismissBtn.on('click', function(e) {
			e.preventDefault();
			dismissNotice();
		});

		// Handle dismiss button (text button)
		$dismissBtnText.on('click', function(e) {
			e.preventDefault();
			dismissNotice();
		});

		// Function to dismiss the notice
		function dismissNotice() {
			var noticeId = 'post_editor_blocks_notice';
			var nonce = typeof evgePostEditorBlocksNotice !== 'undefined' ? evgePostEditorBlocksNotice.nonce : '';

			// Fade out notice
			$notice.fadeOut(200, function() {
				$notice.remove();
			});

			// Send AJAX request to dismiss
			$.ajax({
				url: evgeAdminCommon.ajaxUrl,
				type: 'POST',
				data: {
					action: 'evge_dismiss_post_editor_blocks_notice',
					nonce: nonce
				},
				success: function(response) {
					if (!response.success) {
						console.error('EVGE: Failed to dismiss notice', response);
					}
				},
				error: function(xhr, status, error) {
					console.error('EVGE: AJAX error dismissing notice', error);
				}
			});
		}
	}

});