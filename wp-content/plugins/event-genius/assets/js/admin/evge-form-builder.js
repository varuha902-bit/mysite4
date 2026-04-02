jQuery(document).ready(function($) {
	function EvgeFormBuilder() {
		if ( ! $('#evge-form-builder').length ) {
			return;
		}
		this.$wrapper = $('#evge-form-builder');
	}

	EvgeFormBuilder.prototype = {
		initSubmitButtonPreview: function() {
			// Only target button wrappers within the form preview section
			$('.evge-form-preview-wrap .evge-form-button-wrapper').addClass('evge-button-preview-wrap').find('button').attr('data-evge-preview','submitButton');
			$('.evge-form-preview-wrap .evge-form-button-wrapper').find('button').addClass('evge-button-preview');
		},
		init: function() {
			let self = this;
			// Initialize submit button preview classes
			this.initSubmitButtonPreview();
			$('.evge-field-edit-wrap').each(function() {
				self.initFieldListeners($(this));
			});
			if (!self.$wrapper || !self.$wrapper.length) {
				return;
			}
			$('.evge-form-save-button').on('click', function(e) {
				e.preventDefault();
				self.saveFormClicked(self.$wrapper);
			});
			$('#evge-add-new-field').on('click', function(e) {
				e.preventDefault();
				self.createFieldClicked(self.$wrapper);
			});
			self.initFormPreview();
			$('.evge-nav-tab').on('click', function(e) {
				e.preventDefault();
				self.handleTabs($(this).attr('data-evge-id'));
			});
			$('#evge-fb-emails').find('input, textarea, select').on('change', function(e) {
				self.flagChangeMade($(this),'settings');
			});
			$('#evge-fb-settings').find('input, textarea, select').on('change', function(e) {
				self.flagChangeMade($(this),'settings');
			});
			self.initExitProtection();
		},
		initFormPreview: function() {
			let self = this;
			$('.evge-field-wrapper').each(function() {
				self.initInFormFieldListeners($(this));
			});
			// Target .evge-main-registration as the sortable container since fields are direct children
			// This works with the current form structure where display_form_fields outputs fields directly
			$('.evge-main-registration').sortable({
				axis: "y",
				placeholder: "evge-ui-state-highlight",
				items: ".evge-field-wrapper",
				start: function(event, ui) {
					// Set the height of the placeholder to the height of the item being dragged
					ui.placeholder.height(ui.item.outerHeight() + 1);
				},
				over: function(event, ui) {
					// Update the placeholder height if the item being dragged over is a different height
					ui.placeholder.height(ui.helper.outerHeight() + 1);
				},
				//handle: '.evge-field-wrapper',
				cursor: "move",
				stop: function() {

				},
				update: function(event, ui) {
					self.flagChangeMade(self.$wrapper,'form');
				}
			});
			self.updateFieldAvailableOptions();
			self.updateFieldAddButtons();
		},
		getTopInFormOptionsHTML: function() {
			return this.$wrapper.find('.evge-in-form-options-bar-wrap .evge-form-field-in-form-top').html();
		},
		getBottomInFormOptionsHTML: function() {
			return this.$wrapper.find('.evge-in-form-options-bar-wrap .evge-form-field-in-form-bottom').html();
		},
		updateFieldAvailableOptions: function ($context) {
			let self = this;
			this.$wrapper.find($('.evge-field-edit-wrap')).each(function() {
				self.updateSingleFieldAvailableOptions($(this));
			})
		},
		updateSingleFieldAvailableOptions: function ($context) {
			var typeSelected = $context.find('select[name=type]').val();
			$context.find('.evge-settings-section').each(function () {
				var fieldFor = typeof $(this).attr('data-for') !== 'undefined' ? $(this).attr('data-for') : false;

				if (fieldFor) {
					var fieldForArray = fieldFor.split(',');

					if (fieldForArray.length > 0) {
						if (fieldForArray.indexOf(typeSelected) > -1) {
							$(this).show();
						} else {
							$(this).hide();
						}
					}
				}
			});
		},
		updateFieldAddButtons: function () {
			let self = this;
			this.$wrapper.find($('.evge-field-edit-wrap')).each(function() {
				var targetSlug = $(this).attr('data-id'),
					$this = $(this),
					found = false;

				self.$wrapper.find('.evge-field-wrapper').each(function() {
					var slug = $(this).attr('data-id');

					if (slug === targetSlug) {
						found = true;
					}
				});

				if (!found) {
					if (! $this.find('.evge-disabled-always').length) {
						$this.find('.evge-form-field-add').removeClass('evge-disabled');
					}
				} else {
					$this.find('.evge-form-field-add').addClass( 'evge-disabled' );
				}

			})
		},
		initFieldListeners: function ($context) {
			let self = this;
			$context.find('.evge-field-save-button').on('click', function(e) {
				e.preventDefault();
				self.saveFieldClicked($(this));
			});

			$context.find('.evge-field-delete-button').on('click', function(e) {
				e.preventDefault();
				self.deleteFieldClicked($(this));
			});

			$context.find('.evge-field-edit-summary').on('click', function(e) {
				e.preventDefault();
				self.toggleFieldEdit($context);
			});
			$context.find('.evge-in-form-field-add-button').on('click', function(e) {
				e.preventDefault();
				self.inFormFieldAddClicked($context);
				self.flagChangeMade($context,'form');
			});

			$context.find('.evge-field-edit-settings-tab').on('click', function(e) {
				e.preventDefault();
				self.toggleTab($(this));
			});

			$context.find('select[name=type]').on('change', function(e) {
				e.preventDefault();
				self.updateFieldAvailableOptions($context);
			});

			$context.find('input, select, textarea').each(function() {
				$(this).on('change', function() {
					self.flagChangeMade($context,'fields');
				});
			});
		},
		initInFormFieldListeners: function ($context) {
			$context.prepend(this.getTopInFormOptionsHTML());
			$context.append(this.getBottomInFormOptionsHTML());
			let self = this;

			// Trigger event for other scripts to hook into
			$(document).trigger('evge_in_form_field_initialized', [$context, self]);
			
			$context.find('input[name=id]').val($context.attr('data-id'));

			// Set required toggle
			if ($context.attr('data-required') === '1') {
				$context.find('.evge-toggle-setting-required').closest('.evge-toggle-setting').find('.evge-settings-toggle').removeClass('evge-input-toggle--disabled').addClass('evge-input-toggle--enabled');
				$context.find('input[name=required]').val('enabled');
			} else {
				$context.find('.evge-toggle-setting-required').closest('.evge-toggle-setting').find('.evge-settings-toggle').removeClass('evge-input-toggle--enabled').addClass('evge-input-toggle--disabled');
				$context.find('input[name=required]').val('disabled');
			}
			// Set show in attendee list toggle
			if ($context.attr('data-show-in-attendee-list') === '1') {
				$context.find('.evge-toggle-setting-show-in-attendee-list .evge-settings-toggle').removeClass('evge-input-toggle--disabled').addClass('evge-input-toggle--enabled');
				$context.find('input[name=show_in_attendee_list]').val('enabled');
			} else {
				$context.find('.evge-toggle-setting-show-in-attendee-list .evge-settings-toggle').removeClass('evge-input-toggle--enabled').addClass('evge-input-toggle--disabled');
				$context.find('input[name=show_in_attendee_list]').val('disabled');
			}
			// Initialize toggle state
			self.handleToggle($context.find('.evge-toggle-setting-required').closest('.evge-toggle-setting'));
			self.handleToggle($context.find('.evge-toggle-setting-show-in-attendee-list'));

			$context.find('.evge-in-form-field-edit-button').on('click', function(e) {
				e.preventDefault();
				self.toggleFieldInFormOptionsEdit($context);
			});
			$context.find('.evge-in-form-field-remove-button').on('click', function(e) {
				e.preventDefault();
				$context.remove();
				self.updateFieldAddButtons();
				self.flagChangeMade($context,'form');
			});
			$context.find('.evge-form-field-in-form-options').find('input, select, textarea').on('change', function(e) {
				self.flagChangeMade($context,'form');
			});
			$('.evge-button-preview-settings').each(function() {
				$(this).find('input').on('change',function() {
					if ($(this).hasClass('evge-button-preview-text')) {
						$wrapEl = $(this).closest('.evge-button-preview-wrap');
						if ($(this).closest('.evge-button-preview-wrap').length) {
							$wrapEl = $(this).closest('.evge-button-preview-wrap');
						} else if ($(this).closest('[data-evge-preview-trigger]').length) {
							var value = $(this).closest('[data-evge-preview-trigger]').attr('data-evge-preview-trigger');
							$wrapEl = $('[data-evge-preview="'+value+'"]').closest('.evge-button-preview-wrap');
						}
						$wrapEl.find('.evge-button-preview').text($(this).val());
					}
				});
			});
		},
		toggleFieldEdit: function ($context) {
			if ($context.find('.evge-field-edit-settings').is(':visible')) {
				$context.find('.evge-field-edit-settings').hide();
			} else {
				$context.find('.evge-field-edit-settings').show();
			}
		},
		toggleFieldInFormOptionsEdit: function ($context) {
			if ($context.find('.evge-form-field-in-form-options').is(':visible')) {
				$context.find('.evge-form-field-in-form-options').hide();
			} else {
				$context.find('.evge-form-field-in-form-options').show();
			}
		},
		toggleTab: function ($button) {
			var tab = $button.attr('data-tab');
			$button.closest('.evge-field-edit-group').find('.evge-field-edit-settings-tab').removeClass('nav-tab-active');
			$button.closest('.evge-field-edit-group').find('.evge-field-edit-settings-subsection').each(function() {
				var $this = $(this);
				if ($this.attr('data-tab') === tab) {
					$this.show();
					$button.addClass('nav-tab-active');
				} else {
					$this.hide();
				}
			});
		},
		inFormFieldAddClicked: function ($context) {
			var self = this,
				fieldId = $context.attr('data-id'),
				data = {
					action : 'evge_in_form_add_field',
					nonce: evgeAdminCommon.ajaxNonce,
					id : fieldId
				};
			$.ajax({
				url: evgeAdminCommon.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					if (response.success) {
						// Insert the new field after the last field in the form
						var $lastField = self.$wrapper.find('.evge-field-wrapper').last();
						var $newField = $(response.data.html);
						
						// Verify the field ID matches before inserting
						if ($newField.attr('data-id') !== fieldId) {
							console.warn('Field ID mismatch when adding field to form');
							return;
						}
						
						if ($lastField.length) {
							$lastField.after($newField);
						} else {
							// If no fields exist yet, append to the form container
							self.$wrapper.find('.evge-main-registration').append($newField);
						}
						
						// Initialize listeners on the newly inserted field
						self.initInFormFieldListeners($newField);
						
						self.updateFieldAddButtons();
					}
				}
			});
		},
		saveFieldClicked: function ($button) {
			this.unFlagChangeMade($button.closest('.evge-field-edit-group'),'field');
			window.EvgeAdmin.startProcessing($button,'button');

			var self = this,
				$context = $button.closest('.evge-field-edit-group'),
				data = {
				fields : this.getFieldValues($context),
				id : $context.find('.evge-field-id').val(),
				action : 'evge_save_field',
					nonce: evgeAdminCommon.ajaxNonce
			};

			$.ajax({
				url: evgeAdminCommon.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					window.EvgeAdmin.stopProcessing($button,'button');

					if (response.success) {
						self.updateInFormFieldHTML(data.id, response.data.html);
						self.initInFormFieldListeners(self.$wrapper.find('.evge-field-wrapper[data-id=' + data.id + ']'));

						$context.find('.evge-field-label .evge-icon-text').html(response.data.fieldLabelHTML)
						window.EvgeAdmin.messageBadge(response.data.badgeMessage,true);
					}
				}
			});
		},
		deleteFieldClicked: function ($button) {
			if ( confirm(window.evgeFB.textSettings.confirmDelete) === false ) {
				return;
			}
			window.EvgeAdmin.startProcessing($button.closest('.evge-field-edit-group'),'element');
			var self = this,
				$context = $button.closest('.evge-field-edit-group'),
				data = {
					id : $context.find('.evge-field-id').val(),
					action : 'evge_delete_field',
					nonce: evgeAdminCommon.ajaxNonce
				};

			$.ajax({
				url: evgeAdminCommon.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					window.EvgeAdmin.stopProcessing($button.closest('.evge-field-edit-group'),'element');

					if (response.success) {
						$context.closest('.evge-field-edit-wrap').remove();
						self.$wrapper.find('.evge-field-wrapper[data-id=' + data.id + ']').remove();
						window.EvgeAdmin.messageBadge(response.data.badgeMessage,true);

					}
				}
			});
		},
		saveFormClicked: function ($context) {
			var self = this;
			window.EvgeAdmin.startProcessing($('.evge-form-save-button'),'button', true);
			window.EvgeAdmin.startProcessing(self.$wrapper.find('.evge-form-preview-wrap'),'area');

			if (self.$wrapper.find('.evge-form-fields-section .evge-changes-made').length) {
				self.saveAll($context);
			} else {
				self.saveForm($context);
			}
		},
		saveAll($formContext) {
			var self = this,
				data = {
					all_fields : {},
					action : 'evge_save_all_fields',
					nonce: evgeAdminCommon.ajaxNonce
				};


			self.$wrapper.find('.evge-form-fields-section .evge-changes-made').each(function() {
				var $context = $(this);
				self.unFlagChangeMade($context,'field');
				data.all_fields[ $context.find('.evge-field-id').val() ] = self.getFieldValues($context);
			});

			$.ajax({
				url: evgeAdminCommon.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					if (response.success) {
						response.data.allFieldsHTML.forEach(function(item) {
							self.$wrapper.find('.evge-field-edit-wrap[data-id=' + item.id + ']').find('.evge-field-label .evge-icon-text').html(item.html)
						});

						self.saveForm($formContext);
					}
				}
			});

		},
		saveForm($context) {
			this.unFlagChangeMade($context,'form');

			// Get TinyMCE content before serializing form data
			$('#evge-fb-emails, #evge-fb-settings').find('.wp-editor-area').each(function() {
				var editor = tinyMCE.get($(this).attr('id'));
				if (editor && !editor.isHidden()) {
					editor.save(); // Save content from visual editor to textarea
				}
			});

			const emailFormData = $('#evge-fb-emails').serializeArray().reduce((obj, item) => {
				var key = item.name,
					maybeMatch = key.match(/\[(.*?)\]/);
				if (maybeMatch) {
					key = maybeMatch[1];
				}
				obj[key] = item.value;
				return obj;
			}, {});
			const settingsFormData = $('#evge-fb-settings').serializeArray().reduce((obj, item) => {
				var key = item.name,
					maybeMatch = key.match(/\[(.*?)\]/);
				if (maybeMatch) {
					key = maybeMatch[1];
				}
				obj[key] = item.value;
				return obj;
			}, {});

			// Extract form name separately (not part of settings array)
			var formName = $('#evge-form-name').val() || '';

			var data = {
					'form_id' : $('#evge-form-builder').attr('data-form-id'),
					'form_name' : formName,
					'fields' : {},
					'submit_button' : this.getFieldValues($('.evge-field-edit-wrap-submit-button')),
					'email' : emailFormData,
					'settings' : settingsFormData
				},
				self = this;

			$context.find('.evge-field-wrapper .evge-form-field-in-form-options').each(function(index) {
				data.fields[index] = self.getFieldValues($(this));
			})
			data.action = 'evge_save_form';
			data.nonce = evgeAdminCommon.ajaxNonce;

			$.ajax({
				url: evgeAdminCommon.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					window.EvgeAdmin.stopProcessing($('.evge-form-save-button'),'button');
					window.EvgeAdmin.stopProcessing(self.$wrapper.find('.evge-form-preview-wrap'),'area');

					if (response.success) {
						self.$wrapper.find('.evge-form-preview-wrap').html(response.data.html);
						self.initFormPreview();
						// Re-apply submit button preview classes after HTML is replaced
						self.initSubmitButtonPreview();
						window.EvgeAdmin.messageBadge(response.data.badgeMessage,true);
						
						// Update page title if form name was changed
						var newFormName = $('#evge-form-name').val();
						if (newFormName) {
							$('.evge-current-page').text(newFormName);
						}
						// Clear all pending change flags after successful save
						self.clearAllPendingChangeFlags();
					}
				}
			});
		},
		createFieldClicked: function ($context) {
			var data = {},
				self = this;

			window.EvgeAdmin.startProcessing($('#evge-add-new-field'),'button');

			data.action = 'evge_create_field';
			data.nonce = evgeAdminCommon.ajaxNonce;

			$.ajax({
				url: evgeAdminCommon.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					window.EvgeAdmin.stopProcessing($('#evge-add-new-field'),'button');

					if (response.data.success) {
						var $newField = $('.evge-field-edit-wrap').last();
						$newField.after(response.data.html);
						$newField = $('.evge-field-edit-wrap').last();
						self.initFieldListeners($newField);
						self.updateSingleFieldAvailableOptions($newField);
						
						// Trigger event for Pro/Standard extensions to initialize field type settings
						$(document).trigger('evge_field_edit_wrap_initialized', [$newField, self]);

						// Re-initialize modal triggers so "All field types" upsell links
						// inside newly created fields work correctly.
						if (window.EvgeAdmin && window.EvgeAdmin.Modal && typeof window.EvgeAdmin.Modal.initTriggers === 'function') {
							window.EvgeAdmin.Modal.initTriggers();
						}
						
						$newField.find('.evge-toggle-edit-icon').trigger('click');
					}
				}
			});
		},
		getFieldValues($context) {
			var data = {};
			$context.find('input, select, textarea').each(function() {
				var $this = $(this);
				var name = $this.attr('name');
				var value = $(this).val();
				
				if (!name) {
					return;
				}
				
				// Handle nested array notation like misc[terms_text]
				if (name.indexOf('[') !== -1 && name.indexOf(']') !== -1) {
					var matches = name.match(/^([^\[]+)\[([^\]]+)\]$/);
					if (matches) {
						var parentKey = matches[1];
						var childKey = matches[2];
						
						// Initialize parent object if it doesn't exist
						if (!data[parentKey]) {
							data[parentKey] = {};
						}
						
						// Set the nested value
						data[parentKey][childKey] = value;
						return;
					}
				}
				
				// Handle array-type inputs (checkboxes, multiple selects)
				if (name.endsWith('[]')) {
					// Remove the [] from the name for the data object key
					var cleanName = name.replace('[]', '');
					
					// Initialize array if it doesn't exist
					if (!data[cleanName]) {
						data[cleanName] = [];
					}
					
					// For checkboxes, only add value if checked
					if ($this.attr('type') === 'checkbox') {
						if ($this.is(':checked')) {
							data[cleanName].push(value);
						}
					} else {
						// For other array inputs (like multiple select), add all values
						data[cleanName].push(value);
					}
				} else {
					// Handle regular inputs
					if ($this.attr('type') === 'checkbox') {
						// For single checkboxes, store boolean value
						data[name] = $this.is(':checked');
					} else {
						data[name] = value;
					}
				}
			});
			return data;
		},
		updateInFormFieldHTML(fieldId, html) {
			this.$wrapper.find('.evge-field-wrapper[data-id=' + fieldId + ']').replaceWith(html);
		},
		flagChangeMade: function($elementContext,context) {
			if (context === 'form') {
				this.$wrapper.find('.evge-form-preview-section').addClass('evge-changes-made');
			} else {
				$elementContext.addClass('evge-changes-made');
				$elementContext.closest('.evge-field-edit-wrap').addClass('evge-changes-made');
				this.$wrapper.find('.evge-all-fields-header').addClass('evge-changes-made');
			}
		},
		unFlagChangeMade: function($elementContext,context) {
			if (context === 'form') {
				this.$wrapper.find('.evge-form-preview-section').removeClass('evge-changes-made');
			} else {
				$elementContext.removeClass('evge-changes-made');
				$elementContext.closest('.evge-field-edit-wrap').removeClass('evge-changes-made');
				this.$wrapper.find('.evge-all-fields-header').removeClass('evge-changes-made');
			}
		},
		clearAllPendingChangeFlags: function() {
			// Clear all pending change flags from the entire form builder
			$('.evge-changes-made').removeClass('evge-changes-made');
		},
		handleToggle($context){
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
		},
		handleTabs(activeTab) {
			$('.evge-form-builder-pages').each(function() {
				var $this = $(this);
				if ($this.attr('data-evge-tab') === activeTab) {
					$this.show();
				} else {
					$this.hide();
				}
			});
			$('.evge-nav-tab').each(function() {
				var $this = $(this);
				if ($this.attr('data-evge-id') === activeTab) {
					$this.addClass('evge-nav-tab-active');
				} else {
					$this.removeClass('evge-nav-tab-active');
				}
			});
		},
		initExitProtection: function() {
			if ( ! $('#evge-back-to-forms').length ) {
				return;
			}
			let self = this;

			// Handle internal navigation (links, menu clicks, etc)
			$('#evge-back-to-forms, #wpadminbar a, #adminmenu a').on('click', function(e) {
				if (self.hasUnsavedChanges()) {
					if (!confirm(window.evgeFB.textSettings.confirmLeave)) {
						e.preventDefault();
						return false;
					}
				}
			});
		},
		hasUnsavedChanges: function() {
			return (
				this.$wrapper.find('.evge-changes-made').length > 0 ||
				this.$wrapper.find('.evge-form-preview-section.evge-changes-made').length > 0
			);
		}

	}

	window.evgeFormBuilder = new EvgeFormBuilder();
	evgeFormBuilder.init();

	// Email Template Modal Handler
	function EvgeEmailTemplateModal() {
		this.$element = null;
		this.$context = null;
	}

	EvgeEmailTemplateModal.prototype = {
		init: function() {
			// Initialize placeholder insertion functionality
			this.initPlaceholderInsertion();
		},
		initPlaceholderInsertion: function() {
			var self = this;
			
			// Handle placeholder insertion clicks
			$('.evge-placeholder-insert').off('click.evgeEmailTemplate').on('click.evgeEmailTemplate', function() {
				var placeholder = $(this).data('placeholder');
				var $textarea = $('#evge_email_content');
				
				if ($textarea.length) {
					// Insert placeholder into textarea
					var cursorPos = $textarea[0].selectionStart;
					var textBefore = $textarea.val().substring(0, cursorPos);
					var textAfter = $textarea.val().substring(cursorPos);
					$textarea.val(textBefore + placeholder + textAfter);
					
					// Set cursor position after the inserted text
					var newPos = cursorPos + placeholder.length;
					$textarea[0].setSelectionRange(newPos, newPos);
					$textarea.focus();
				}
				
				// Visual feedback for the click
				$(this).css('background-color', '#e2e2e2');
				setTimeout(function() {
					$(this).css('background-color', '');
				}.bind(this), 200);
			});
		},
		handleFormSubmission: function($form) {
			var self = this;
			var $submitButton = $form.find('.evge-save-template');
			
			// Prevent multiple submissions
			if ($submitButton.prop('disabled')) {
				return;
			}
			
			// Disable submit button and show loading state
			window.EvgeAdmin.startProcessing($submitButton, 'button', true);
			
			// Get form data
			var formData = new FormData($form[0]);
			
			// Add TinyMCE content - try to get from TinyMCE first, fallback to textarea
			var editorContent = '';
			var editorId = 'evge_modal_editor_0';
			
			if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
				editorContent = tinymce.get(editorId).getContent();
			} else {
				// Fallback to textarea value
				editorContent = $('#' + editorId).val() || '';
			}
			
			formData.set('evge_rich_editor_email_content', editorContent);
			
			// Submit via AJAX
			$.ajax({
				url: evgeAdminCommon.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					window.EvgeAdmin.stopProcessing($submitButton, 'button');
					
					if (response.success) {
						// Show success message
						window.EvgeAdmin.messageBadge(response.data.message, true);
						
						// Check if we're editing an existing template
						var isEditing = $form.find('input[name="template_id"]').length > 0 && parseInt($form.find('input[name="template_id"]').val()) !== 0;
						
						if (isEditing) {
							// For editing: refresh the templates list instead of closing modal
							$(document).trigger('evge_template_saved');
						} else {
							// For creating: close modal and refresh select field
							window.EvgeAdmin.Modal.closeModal();
							
							// Handle scheduled email templates
							if (response.data.field_id && response.data.field_id.startsWith('scheduled_email_')) {
								// Trigger event with response data for scheduled emails
								$(document).trigger('evge_template_saved', [response]);
							} else {
								// Use the existing refresh for form builder fields
							self.refreshSelectField(response.data.field_id, response.data.template_id, response.data.template_title);
							}
						}
					} else {
						// Show error message
						window.EvgeAdmin.messageBadge(response.data.message, false);
					}
				},
				error: function(xhr, status, error) {
					window.EvgeAdmin.stopProcessing($submitButton, 'button');
					window.EvgeAdmin.messageBadge('An error occurred while saving the template', false);
				}
			});
		},
		refreshSelectField: function(fieldId, templateId, templateTitle) {
			// Find the select field and add the new option
			var $select = $('#evge_' + fieldId);
			if ($select.length) {
				// Add new option
				var $newOption = $('<option>')
					.val(templateId)
					.text(templateTitle)
					.prop('selected', true);
				$select.append($newOption);
				
				// Trigger change event to update any dependent elements
				$select.trigger('change');
			}
		}
	};

	// Set up email template modal event listeners globally
	$(document).on('submit', '#evge-email-template-form', function(e) {
		e.preventDefault();
		var emailTemplateModal = new EvgeEmailTemplateModal();
		emailTemplateModal.handleFormSubmission($(this));
	});

	$(document).on('click', '.evge-cancel-template', function(e) {
		e.preventDefault();
		window.EvgeAdmin.Modal.closeModal();
	});

	// Initialize email template modal when modal content is loaded
	$(document).on('evge_admin_modal_opened', function() {
		if ($('.evge-email-template-modal-content').length) {
			var emailTemplateModal = new EvgeEmailTemplateModal();
			emailTemplateModal.init();
		}
	});

	// Initialize email template modal when modal content is loaded (for edit modals)
	$(document).on('evge_modal_content_loaded', function(event, $modalElement) {
		if ($modalElement && $modalElement.find('.evge-email-template-modal-content').length) {
			var emailTemplateModal = new EvgeEmailTemplateModal();
			emailTemplateModal.init();
		}
	});

	// additional guests settings
	function evgeFormBuilderToggleAdditionalGuestsSettings($select) {
		var selectedValue = $select.val();
		
		// Handle additional guests only fields
		if (selectedValue === 'additional_guests') {
			$('.evge-additional-guests-only').closest('tr').show();
		} else {
			$('.evge-additional-guests-only').closest('tr').hide();
		}
		
		// Handle min and max guest count fields - hide when "none" is selected
		if (selectedValue === 'none') {
			$('#evge_min_guest_count').closest('tr').hide();
			$('#evge_max_guest_count').closest('tr').hide();
		} else {
			$('#evge_min_guest_count').closest('tr').show();
			$('#evge_max_guest_count').closest('tr').show();
		}
		
	} evgeFormBuilderToggleAdditionalGuestsSettings($('#evge_guest_registration_type'));

	// additional guests settings
	$(document).on('change', '#evge_guest_registration_type', function() {
		evgeFormBuilderToggleAdditionalGuestsSettings($(this));
		// Trigger event for pro script to handle preview section updates
		$(document).trigger('evge_guest_registration_type_changed', [$(this).val()]);
	});

	// Confirmation condition: show/hide message fields based on "when confirmed" setting (free and Pro)
	function evgeFormBuilderToggleConfirmationMessages() {
		var $container = $('.evge-confirmation-condition-select');
		var $select = $container.find('select');
		if (!$select.length) {
			$select = $('#evge_confirmation_condition');
		}
		if (!$select.length) {
			return;
		}
		var val = $select.val();
		if (val === 'manual_only') {
			$('.evge-message-for-payment').closest('tr').hide();
			$('.evge-message-for-manual').closest('tr').show();
		} else {
			$('.evge-message-for-payment').closest('tr').show();
			$('.evge-message-for-manual').closest('tr').hide();
		}
	}
	evgeFormBuilderToggleConfirmationMessages();
	$(document).on('change', '.evge-confirmation-condition-select select, #evge_confirmation_condition', evgeFormBuilderToggleConfirmationMessages);

});