/**
 * FaeCursor Admin Dashboard JavaScript
 * Handles settings management and interactive features
 */

(function ($) {
  "use strict";

  // Debounce utility function (with browser compatibility)
  const debounce = (func, wait) => {
    let timeout;
    return function executedFunction() {
      const args = arguments;
      const context = this;
      const later = function() {
        clearTimeout(timeout);
        func.apply(context, args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  };

  // Fetch polyfill check for older browsers
  if (typeof fetch === 'undefined') {
    // If fetch is not available, use XMLHttpRequest as fallback
    window.fetch = function(url) {
      return new Promise(function(resolve, reject) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            resolve({
              ok: true,
              status: xhr.status,
              text: function() { return Promise.resolve(xhr.responseText); }
            });
          } else {
            reject(new Error('HTTP ' + xhr.status));
          }
        };
        xhr.onerror = reject;
        xhr.send();
      });
    };
  }

  // Effects with FULL customization (no color/speed limits)
  // All other cursor effects have limited customization for free users
  const FULL_CUSTOMIZATION_EFFECTS = [
    'none',
    'drop-effect',
    'rise-effect',
    'line-effect',
    'duo-circle',
    'duo-circle-2',
  ];
  
  // Keyboard effects with FULL color customization (no limits)
  // All other keyboard effects have limited color for free users
  const KEYBOARD_FULL_COLOR_EFFECTS = [
    'none',
    'sparkle-keys', // Uses multi-color feature instead
  ];
  
  // Particle effects with FULL customization (no limits)
  // All other particle effects have limited customization for free users
  const PARTICLE_FULL_CUSTOMIZATION_EFFECTS = [
    'none',
    'particle-10' // Snowfall - fully customizable
  ];
  
  // Default colors for free users on limited effects
  const FREE_DEFAULT_COLORS = {
    cursor: '#fcba03',
    keyboard: '#667eea',
    particle: '#a855f7'
  };
  
  // Admin Dashboard Controller
  const FaeAdmin = {
    // Store saved effects state (what's actually saved, not form selections)
    savedEffects: {
      cursor: 'none',
      keyboard: 'none',
      particle: 'none'
    },
    
    // Store user-selected colors (to preserve when switching between limited/full effects)
    userSelectedColors: {
      cursor: null,
      keyboard: null,
      particle: null
    },
    
    // Store user-selected speeds (to preserve when switching between limited/full effects)
    userSelectedSpeeds: {
      cursor: null,
      particle: null
    },
    
    // Track which tabs have shown the upgrade modal (to avoid showing multiple times per tab)
    upgradeModalShownForTab: {
      cursor: false,
      keyboard: false,
      particle: false
    },
    
    // Check if Pro plugin is active (from server-side detection)
    // Use truthy check to handle PHP type coercion (boolean/integer/string)
    isPremium: typeof faeAdminData !== 'undefined' && !!faeAdminData.isPremium,
    
    // Check if a cursor effect has limited customization
    effectHasLimitedCustomization: function(effectId) {
      return !FULL_CUSTOMIZATION_EFFECTS.includes(effectId);
    },
    
    // Check if user can customize color for cursor effect (free: only for non-limited effects)
    canCustomizeColor: function(effectId) {
      if (!this.effectHasLimitedCustomization(effectId)) {
        return true;
      }
      return this.isPremium;
    },
    
    // Check if user can customize speed for cursor effect (free: only for non-limited effects)
    canCustomizeSpeed: function(effectId) {
      if (!this.effectHasLimitedCustomization(effectId)) {
        return true;
      }
      return this.isPremium;
    },
    
    // Check if a keyboard effect has limited color customization
    keyboardEffectHasLimitedColor: function(effectId) {
      return !KEYBOARD_FULL_COLOR_EFFECTS.includes(effectId);
    },
    
    // Check if user can customize color for keyboard effect (free: only for non-limited effects)
    canCustomizeKeyboardColor: function(effectId) {
      if (!this.keyboardEffectHasLimitedColor(effectId)) {
        return true;
      }
      return this.isPremium;
    },
    
    // Check if a particle effect has limited customization
    particleEffectHasLimitedCustomization: function(effectId) {
      return !PARTICLE_FULL_CUSTOMIZATION_EFFECTS.includes(effectId);
    },
    
    // Check if user can customize color for particle effect (free: only for non-limited effects)
    canCustomizeParticleColor: function(effectId) {
      if (!this.particleEffectHasLimitedCustomization(effectId)) {
        return true;
      }
      return this.isPremium;
    },
    
    // Check if user can customize speed for particle effect (free: only for non-limited effects)
    canCustomizeParticleSpeed: function(effectId) {
      if (!this.particleEffectHasLimitedCustomization(effectId)) {
        return true;
      }
      return this.isPremium;
    },
    
    init: function () {
      // Initialize saved effects state from form values (which reflect saved state on page load)
      this.savedEffects.cursor = $('input[name="fae_cursor_options[effect]"]:checked').val() || 'none';
      this.savedEffects.keyboard = $('input[name="fae_keyboard_options[effect]"]:checked').val() || 'none';
      this.savedEffects.particle = $('input[name="fae_particle_options[effect]"]:checked').val() || 'none';
      
      // Initialize user-selected colors from saved values (to preserve across effect switches)
      this.userSelectedColors.cursor = $('#fae-color').val() || $('#cursor-effects-tab input[name="fae_cursor_options[color]"]').val() || FREE_DEFAULT_COLORS.cursor;
      this.userSelectedColors.keyboard = $('#fae-keyboard-color').val() || $('#keyboard-effects-tab input[name="fae_keyboard_options[color]"]').val() || FREE_DEFAULT_COLORS.keyboard;
      this.userSelectedColors.particle = $('#fae-particle-color').val() || $('#particle-effects-tab input[name="fae_particle_options[color]"]').val() || FREE_DEFAULT_COLORS.particle;
      
      // Initialize user-selected speeds from saved values
      this.userSelectedSpeeds.cursor = $('#fae-speed').val() || 'normal';
      this.userSelectedSpeeds.particle = $('#fae-particle-speed').val() || 'normal';
      
      this.bindEvents();
      this.updateStats();
      this.loadSettings();
      this.updateEffectTypeRestrictions();
    },

    bindEvents: function () {
      // Tab switching
      $(".fae-tab-button").on("click", this.handleTabSwitch);

      // Cursor effect selection
      $("#cursor-effects-tab .fae-effect-option").on("click", function(e) {
        // Prevent clicking on Pro locked effects
        if ($(this).hasClass('fae-effect-pro-locked')) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        }
        // Only handle if clicking on the option itself, not on nested elements
        if ($(e.target).closest('.fae-effect-option').is($(this))) {
          const $radio = $(this).find('input[type="radio"]');
          if ($radio.length && !$radio.is(':disabled')) {
            const effectValue = $radio.val();
            const wasChecked = $radio.is(':checked');
            
            // Select the effect first (for preview)
            $radio.prop('checked', true);
            
            // Show upgrade modal on first click if another effect type is active
            if (effectValue !== 'none' && FaeAdmin.isAnotherEffectTypeActive('cursor')) {
              // Only show modal once per tab session
              if (!FaeAdmin.upgradeModalShownForTab.cursor) {
                FaeAdmin.showUpgradeNoticeModal();
                FaeAdmin.upgradeModalShownForTab.cursor = true;
              }
            }
            
            // Trigger change event to ensure preview updates (allow preview)
            if (!wasChecked) {
              $radio.trigger('change');
            } else {
              // If already checked, call handler directly to update preview
              FaeAdmin.handleEffectChange.call($radio[0]);
            }
          }
        }
      });
      $('input[name="fae_cursor_options[effect]"]').on(
        "change",
        this.handleEffectChange
      );

      // Keyboard effect selection
      $('input[name="fae_keyboard_options[effect]"]').on(
        "change",
        this.handleKeyboardEffectChange
      );
      
      // Also handle keyboard effect option clicks
      $("#keyboard-effects-tab .fae-effect-option").on("click", function(e) {
        // Prevent clicking on Pro locked effects
        if ($(this).hasClass('fae-effect-pro-locked')) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        }
        const $radio = $(this).find('input[type="radio"]');
        if ($radio.length && !$radio.is(':disabled')) {
          const effectValue = $radio.val();
          const wasChecked = $radio.is(':checked');
          
          // Select the effect first (for preview)
          $radio.prop('checked', true);
          
          // Show upgrade modal on first click if another effect type is active
          if (effectValue !== 'none' && FaeAdmin.isAnotherEffectTypeActive('keyboard')) {
            // Only show modal once per tab session
            if (!FaeAdmin.upgradeModalShownForTab.keyboard) {
              FaeAdmin.showUpgradeNoticeModal();
              FaeAdmin.upgradeModalShownForTab.keyboard = true;
            }
          }
          
          // Trigger change event to ensure preview updates (allow preview)
          if (!wasChecked) {
            $radio.trigger('change');
          } else {
            // If already checked, call handler directly to update preview
            FaeAdmin.handleKeyboardEffectChange.call($radio[0]);
          }
        }
      });

      // Particle effect selection
      $('input[name="fae_particle_options[effect]"]').on(
        "change",
        this.handleParticleEffectChange
      );
      
      // Also handle particle effect option clicks
      $("#particle-effects-tab .fae-effect-option").on("click", function(e) {
        // Prevent clicking on Pro locked effects
        if ($(this).hasClass('fae-effect-pro-locked')) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        }
        const $radio = $(this).find('input[type="radio"]');
        if ($radio.length && !$radio.is(':disabled')) {
          const effectValue = $radio.val();
          const wasChecked = $radio.is(':checked');
          
          // Select the effect first (for preview)
          $radio.prop('checked', true);
          
          // Show upgrade modal on first click if another effect type is active
          if (effectValue !== 'none' && FaeAdmin.isAnotherEffectTypeActive('particle')) {
            // Only show modal once per tab session
            if (!FaeAdmin.upgradeModalShownForTab.particle) {
              FaeAdmin.showUpgradeNoticeModal();
              FaeAdmin.upgradeModalShownForTab.particle = true;
            }
          }
          
          // Trigger change event to ensure preview updates (allow preview)
          if (!wasChecked) {
            $radio.trigger('change');
          } else {
            // If already checked, call handler directly to update preview
            FaeAdmin.handleParticleEffectChange.call($radio[0]);
          }
        }
      });

      // Settings changes
      $(".fae-setting-group input, .fae-setting-group select").on(
        "change",
        this.handleSettingChange
      );
      
      // Checkbox changes (need special handling) - including toggle switch
      $('input[type="checkbox"][name*="fae_cursor_options"]').on(
        "change",
        this.handleCheckboxChange
      );
      
      // Also handle toggle switch specifically
      $('#fae_hide_default_cursor_toggle').on(
        "change",
        this.handleCheckboxChange
      );

      // Color picker changes - use 'input' for real-time updates while selecting
      $(".fae-color-picker").on("input", this.handleColorChange);
      $(".fae-color-picker").on("change", this.handleColorChange);

      // Icon picker events
      $("#fae-icon-picker-trigger").on("click", this.openIconPicker);
      $("#fae-icon-picker-close").on("click", this.closeIconPicker);
      $(".fae-icon-option").on("click", this.selectIcon);
      $("#fae-icon-search").on("input", this.filterIcons);
      $(document).on("click", ".fae-icon-picker-modal", this.handleModalClick);
      $(document).on("keydown", this.handleKeydown);

      // Form submission - bind all three forms
      $("#fae-cursor-form, #fae-keyboard-form, #fae-particle-form").on("submit", this.handleFormSubmit);

      // Scope type dropdown changes
      $(".fae-scope-type-select").on("change", this.handleScopeTypeChange);

      // User restriction type radio button changes
      $(".fae-user-restriction-type").on("change", this.handleUserRestrictionTypeChange);

      // Preview iframe updates
      this.initPreviewIframes();

      // Info button tooltip (close on outside click)
      $(document).on("click", function(e) {
        if (!$(e.target).closest('.fae-info-button').length) {
          $('.fae-info-button').removeClass('active');
        }
      });
      
      // Toggle tooltip on click for mobile/touch devices
      $(document).on("click", ".fae-info-button", function(e) {
        e.stopPropagation();
        $(this).toggleClass('active');
      });
    },

    handleTabSwitch: function (e) {
      e.preventDefault();
      const $button = $(this);
      const targetTab = $button.data("tab");

      // Remove active class from all buttons and tabs
      $(".fae-tab-button").removeClass("active");
      $(".fae-tab-content").removeClass("active");

      // Add active class to clicked button and corresponding tab
      $button.addClass("active");
      $("#" + targetTab + "-tab").addClass("active");
      
      // Update visibility of settings when switching to keyboard effects tab
      if (targetTab === 'keyboard-effects') {
        const keyboardEffect = $('input[name="fae_keyboard_options[effect]"]:checked').val() || 'none';
        if (keyboardEffect === 'sparkle-keys') {
          $('#keyboard-effects-tab .fae-multi-color-setting').show();
          $('#keyboard-effects-tab .fae-color-setting').hide();
          // Always show color picker for sparkle-keys (multi-color is disabled - Pro feature)
          $('#keyboard-effects-tab .fae-color-setting-sparkle').show();
        } else {
          $('#keyboard-effects-tab .fae-multi-color-setting').hide();
          $('#keyboard-effects-tab .fae-color-setting').show();
          $('#keyboard-effects-tab .fae-color-setting-sparkle').hide();
        }
      }
      
      // Don't reset the upgrade modal flag - it should persist across tab switches
      // Modal will only show once per tab per session, even if user switches away and comes back
    },

    handleKeyboardEffectChange: function () {
      const effect = $(this).val();
      const effectSettings = $(".fae-keyboard-effect-settings");

      // Show/hide settings based on effect
      if (effect === "none") {
        effectSettings.hide();
      } else {
        effectSettings.show();
      }

      // Show/hide multi-color setting for sparkle-keys
      if (effect === 'sparkle-keys') {
        $('#keyboard-effects-tab .fae-multi-color-setting').show();
        $('#keyboard-effects-tab .fae-color-setting').hide();
        // Always show color picker for sparkle-keys (multi-color is disabled - Pro feature)
        $('#keyboard-effects-tab .fae-color-setting-sparkle').show();
      } else {
        $('#keyboard-effects-tab .fae-multi-color-setting').hide();
        $('#keyboard-effects-tab .fae-color-setting').show();
        $('#keyboard-effects-tab .fae-color-setting-sparkle').hide();
      }

      // Hide/show entire appearance section in preview when effect is 'none'
      const $appearanceSection = $('#keyboard-effects-tab .fae-appearance-section');
      if (effect === 'none') {
        $appearanceSection.hide();
      } else {
        $appearanceSection.show();
      }

      // Visually mark selected option within its grid (purple state)
      const $option = $(this).closest(".fae-effect-option");
      if ($option.length) {
        const $grid = $option.closest(".fae-effect-grid");
        if ($grid.length) {
          $grid.find(".fae-effect-option").removeClass("fae-effect-selected");
        } else {
          $("#keyboard-effects-tab .fae-effect-option").removeClass("fae-effect-selected");
        }
        $option.addClass("fae-effect-selected");
      }

      // Update preview iframe
      if (typeof FaeAdmin.updatePreviewIframe === 'function') {
        FaeAdmin.updatePreviewIframe('keyboard');
      }
      
      // Update effect type restrictions when effect changes
      FaeAdmin.updateEffectTypeRestrictions();
      
      // Update limited customization UI for keyboard effects
      FaeAdmin.updateKeyboardLimitedCustomization(effect);
    },
    
    // Update keyboard effect color UI based on limited customization rules
    updateKeyboardLimitedCustomization: function(effect) {
      const hasLimitedColor = this.keyboardEffectHasLimitedColor(effect);
      const canCustomizeColor = this.canCustomizeKeyboardColor(effect);
      
      // Update data attributes for CSS
      $('#keyboard-effects-tab .fae-keyboard-color-setting').attr('data-limited-customization', hasLimitedColor ? '1' : '0');
      
      // Update color setting (but not for sparkle-keys which uses multi-color)
      if (effect !== 'sparkle-keys') {
        const $colorSetting = $('#keyboard-effects-tab .fae-keyboard-color-setting');
        const $colorLabel = $colorSetting.find('label');
        const $colorInput = $colorSetting.find('.fae-color-input-inline');
        
        // Update Pro badge in label
        if (hasLimitedColor && !canCustomizeColor) {
          // SAVE user's current color before locking (if not already the default)
          const currentColorVal = $('#fae-keyboard-color').val();
          if (currentColorVal && currentColorVal !== FREE_DEFAULT_COLORS.keyboard) {
            this.userSelectedColors.keyboard = currentColorVal;
          }
          
          // Add Pro badge if not present
          if (!$colorLabel.find('.fae-pro-badge-inline').length) {
            $colorLabel.append('<span class="fae-pro-badge fae-pro-badge-inline">PRO</span>');
          }
          // Replace color input with locked swatch
          $colorInput.addClass('fae-color-locked');
          $colorInput.html(
            '<div class="fae-color-swatch-locked" style="background-color: ' + FREE_DEFAULT_COLORS.keyboard + ';" title="Upgrade to Pro to customize color"></div>' +
            '<input type="hidden" name="fae_keyboard_options[color]" value="' + FREE_DEFAULT_COLORS.keyboard + '" id="fae-keyboard-color">' +
            '<span class="fae-color-locked-text">' + FREE_DEFAULT_COLORS.keyboard + '</span>'
          );
        } else {
          // Remove Pro badge if present
          $colorLabel.find('.fae-pro-badge-inline').remove();
          // Restore color input with user's SAVED color (not current hidden input value)
          $colorInput.removeClass('fae-color-locked');
          const restoreColor = this.userSelectedColors.keyboard || FREE_DEFAULT_COLORS.keyboard;
          $colorInput.html(
            '<input type="color" class="fae-color-picker-inline" name="fae_keyboard_options[color]" value="' + restoreColor + '" id="fae-keyboard-color">' +
            '<input type="text" value="' + restoreColor + '" id="fae-keyboard-color-text">'
          );
          // Re-bind color change events
          this.bindColorEvents();
        }
      }
    },

    handleParticleEffectChange: function () {
      const effect = $(this).val();
      const effectSettings = $("#particle-effects-tab .fae-effect-settings");
      const $appearanceSection = $('#particle-effects-tab .fae-appearance-section');

      // Show/hide settings and appearance based on effect
      if (effect === "none") {
        effectSettings.hide();
        $appearanceSection.hide();
        $('#particle-effects-tab .fae-hide-cursor-toggle-wrapper').hide();
      } else {
        effectSettings.show();
        $appearanceSection.show();
        
        // Get effect config for showing/hiding settings
        const effectConfig = typeof FAE_PARTICLE_EFFECTS_CONFIG !== 'undefined' && FAE_PARTICLE_EFFECTS_CONFIG[effect]
          ? FAE_PARTICLE_EFFECTS_CONFIG[effect]
          : {};
        
        // Show/hide toggle in header based on config
        const showHideCursor = effectConfig.supportsHideCursor !== false;
        $('#particle-effects-tab .fae-hide-cursor-toggle-wrapper').toggle(showHideCursor);
        
        // Show/hide color setting based on effect config (supportsColor)
        const showColor = effectConfig.supportsColor !== false;
        $('#particle-effects-tab .fae-particle-color-setting').toggle(showColor);
        
        // Show/hide speed setting based on effect config (supportsSpeed)
        const showSpeed = effectConfig.supportsSpeed !== false;
        $('#particle-effects-tab .fae-particle-speed-setting').toggle(showSpeed);
        
        // Show/hide Interactive Cursor option based on effect config
        const showInteractiveCursor = effectConfig.supportsInteractiveCursor === true;
        $('#particle-effects-tab .fae-interactive-cursor-setting').toggle(showInteractiveCursor);
      }

      // Visually mark selected option within its grid (purple state)
      const $option = $(this).closest(".fae-effect-option");
      if ($option.length) {
        const $grid = $option.closest(".fae-effect-grid");
        if ($grid.length) {
          $grid.find(".fae-effect-option").removeClass("fae-effect-selected");
        } else {
          $("#particle-effects-tab .fae-effect-option").removeClass("fae-effect-selected");
        }
        $option.addClass("fae-effect-selected");
      }

      // Update preview iframe
      if (typeof FaeAdmin.updatePreviewIframe === 'function') {
        FaeAdmin.updatePreviewIframe('particle');
      }
      
      // Update effect type restrictions when effect changes
      FaeAdmin.updateEffectTypeRestrictions();
      
      // Update limited customization UI for particle effects
      FaeAdmin.updateParticleLimitedCustomization(effect);
    },
    
    // Update particle effect color/speed UI based on limited customization rules
    updateParticleLimitedCustomization: function(effect) {
      const hasLimitedCustomization = this.particleEffectHasLimitedCustomization(effect);
      const canCustomizeColor = this.canCustomizeParticleColor(effect);
      const canCustomizeSpeed = this.canCustomizeParticleSpeed(effect);
      
      // Update data attributes for CSS
      $('#particle-effects-tab .fae-particle-color-setting').attr('data-limited-customization', hasLimitedCustomization ? '1' : '0');
      $('#particle-effects-tab .fae-particle-speed-setting').attr('data-limited-customization', hasLimitedCustomization ? '1' : '0');
      
      // Update color setting
      const $colorSetting = $('#particle-effects-tab .fae-particle-color-setting');
      const $colorLabel = $colorSetting.find('label');
      const $colorInput = $colorSetting.find('.fae-color-input-inline');
      
      // Update Pro badge in label
      if (hasLimitedCustomization && !canCustomizeColor) {
        // SAVE user's current color before locking (if not already the default)
        const currentColorVal = $('#fae-particle-color').val();
        if (currentColorVal && currentColorVal !== FREE_DEFAULT_COLORS.particle) {
          this.userSelectedColors.particle = currentColorVal;
        }
        
        // Add Pro badge if not present
        if (!$colorLabel.find('.fae-pro-badge-inline').length) {
          $colorLabel.append('<span class="fae-pro-badge fae-pro-badge-inline">PRO</span>');
        }
        // Replace color input with locked swatch
        $colorInput.addClass('fae-color-locked');
        $colorInput.html(
          '<div class="fae-color-swatch-locked" style="background-color: ' + FREE_DEFAULT_COLORS.particle + ';" title="Upgrade to Pro to customize color"></div>' +
          '<input type="hidden" name="fae_particle_options[color]" value="' + FREE_DEFAULT_COLORS.particle + '" id="fae-particle-color">' +
          '<span class="fae-color-locked-text">' + FREE_DEFAULT_COLORS.particle + '</span>'
        );
      } else {
        // Remove Pro badge if present
        $colorLabel.find('.fae-pro-badge-inline').remove();
        // Restore color input with user's SAVED color (not current hidden input value)
        $colorInput.removeClass('fae-color-locked');
        const restoreColor = this.userSelectedColors.particle || FREE_DEFAULT_COLORS.particle;
        $colorInput.html(
          '<input type="color" class="fae-color-picker-inline" name="fae_particle_options[color]" value="' + restoreColor + '" id="fae-particle-color">' +
          '<input type="text" value="' + restoreColor + '" id="fae-particle-color-text">'
        );
        // Re-bind color change events
        this.bindColorEvents();
      }
      
      // Update speed setting
      const $speedSetting = $('#particle-effects-tab .fae-particle-speed-setting');
      const $speedSelect = $speedSetting.find('select');
      
      if (hasLimitedCustomization && !canCustomizeSpeed) {
        // SAVE user's current speed before locking
        const currentSpeedVal = $speedSelect.val();
        if (currentSpeedVal && currentSpeedVal !== 'normal') {
          this.userSelectedSpeeds.particle = currentSpeedVal;
        }
        
        // Disable slow and fast options, force normal
        $speedSelect.addClass('fae-speed-locked');
        $speedSelect.find('option').each(function() {
          const val = $(this).val();
          if (val === 'slow' || val === 'fast') {
            $(this).prop('disabled', true);
            if (!$(this).text().includes('(Pro)')) {
              $(this).text($(this).text() + ' (Pro)');
            }
          } else {
            $(this).prop('disabled', false);
          }
        });
        // Force to normal
        $speedSelect.val('normal');
      } else {
        // Enable all speed options and RESTORE user's saved speed
        $speedSelect.removeClass('fae-speed-locked');
        $speedSelect.find('option').each(function() {
          $(this).prop('disabled', false);
          $(this).text($(this).text().replace(' (Pro)', ''));
        });
        // Restore user's saved speed
        if (this.userSelectedSpeeds.particle) {
          $speedSelect.val(this.userSelectedSpeeds.particle);
        }
      }
    },

    handleEffectChange: function () {
      // Support both label click and direct radio change
      const $option = $(this).closest(".fae-effect-option").length
        ? $(this).closest(".fae-effect-option")
        : $(this).parent(".fae-effect-option");
      const effect =
        $option.find('input[type="radio"]').val() || $(this).val();

      // Visually mark selected option within its grid (purple state)
      if ($option.length) {
        const $grid = $option.closest(".fae-effect-grid");
        if ($grid.length) {
          $grid.find(".fae-effect-option").removeClass("fae-effect-selected");
        } else {
          // Fallback: clear across all options if grid not found
          $(".fae-effect-option").removeClass("fae-effect-selected");
        }
        $option.addClass("fae-effect-selected");
      }

      FaeAdmin.updateAdvancedSettings(effect);
      
      // Hide/show entire appearance section in preview when effect is 'none'
      const $appearanceSection = $('#cursor-effects-tab .fae-appearance-section');
      if (effect === 'none') {
        $appearanceSection.hide();
      } else {
        $appearanceSection.show();
        // For flag-effect, color visibility is handled by flag selection logic
        // Other settings visibility is handled by updateAdvancedSettings
      }
      
      // Update preview iframe
      if (typeof FaeAdmin.updatePreviewIframe === 'function') {
        FaeAdmin.updatePreviewIframe('cursor');
      }
      
      // Update effect type restrictions when effect changes
      FaeAdmin.updateEffectTypeRestrictions();
      
      // Update limited customization UI for cursor effects
      FaeAdmin.updateCursorLimitedCustomization(effect);
      
      // Status indicators only update after save (from PHP), not on selection change
    },
    
    // Update cursor effect color/speed UI based on limited customization rules
    updateCursorLimitedCustomization: function(effect) {
      const hasLimitedCustomization = this.effectHasLimitedCustomization(effect);
      const canCustomizeColor = this.canCustomizeColor(effect);
      const canCustomizeSpeed = this.canCustomizeSpeed(effect);
      
      // Update data attributes for CSS
      $('#cursor-effects-tab .fae-color-setting').attr('data-limited-customization', hasLimitedCustomization ? '1' : '0');
      $('#cursor-effects-tab .fae-speed-setting').attr('data-limited-customization', hasLimitedCustomization ? '1' : '0');
      
      // Update color setting
      const $colorSetting = $('#cursor-effects-tab .fae-color-setting');
      const $colorLabel = $colorSetting.find('label');
      const $colorInput = $colorSetting.find('.fae-color-input-inline');
      
      // Update Pro badge in label
      if (hasLimitedCustomization && !canCustomizeColor) {
        // SAVE user's current color before locking (if not already the default)
        const currentColorVal = $('#fae-cursor-color').val();
        if (currentColorVal && currentColorVal !== FREE_DEFAULT_COLORS.cursor) {
          this.userSelectedColors.cursor = currentColorVal;
        }
        
        // Add Pro badge if not present
        if (!$colorLabel.find('.fae-pro-badge-inline').length) {
          $colorLabel.append('<span class="fae-pro-badge fae-pro-badge-inline">PRO</span>');
        }
        // Replace color input with locked swatch
        $colorInput.addClass('fae-color-locked');
        $colorInput.html(
          '<div class="fae-color-swatch-locked" style="background-color: ' + FREE_DEFAULT_COLORS.cursor + ';" title="Upgrade to Pro to customize color"></div>' +
          '<input type="hidden" name="fae_cursor_options[color]" value="' + FREE_DEFAULT_COLORS.cursor + '" id="fae-cursor-color">' +
          '<span class="fae-color-locked-text">' + FREE_DEFAULT_COLORS.cursor + '</span>'
        );
      } else {
        // Remove Pro badge if present
        $colorLabel.find('.fae-pro-badge-inline').remove();
        // Restore color input with user's SAVED color (not current hidden input value)
        $colorInput.removeClass('fae-color-locked');
        const restoreColor = this.userSelectedColors.cursor || FREE_DEFAULT_COLORS.cursor;
        $colorInput.html(
          '<input type="color" class="fae-color-picker-inline" name="fae_cursor_options[color]" value="' + restoreColor + '" id="fae-cursor-color">' +
          '<input type="text" value="' + restoreColor + '" id="fae-cursor-color-text">'
        );
        // Re-bind color change events
        this.bindColorEvents();
      }
      
      // Update speed setting
      const $speedSetting = $('#cursor-effects-tab .fae-speed-setting');
      const $speedSelect = $speedSetting.find('select');
      
      if (hasLimitedCustomization && !canCustomizeSpeed) {
        // SAVE user's current speed before locking
        const currentSpeedVal = $speedSelect.val();
        if (currentSpeedVal && currentSpeedVal !== 'normal') {
          this.userSelectedSpeeds.cursor = currentSpeedVal;
        }
        
        // Disable slow and fast options, enable only normal
        $speedSelect.addClass('fae-speed-locked');
        $speedSelect.find('option').each(function() {
          const val = $(this).val();
          if (val === 'slow' || val === 'fast') {
            $(this).prop('disabled', true);
            if (!$(this).text().includes('(Pro)')) {
              $(this).text($(this).text() + ' (Pro)');
            }
          } else {
            $(this).prop('disabled', false);
          }
        });
        // Force to normal
        $speedSelect.val('normal');
      } else {
        // Enable all speed options and RESTORE user's saved speed
        $speedSelect.removeClass('fae-speed-locked');
        $speedSelect.find('option').each(function() {
          $(this).prop('disabled', false);
          $(this).text($(this).text().replace(' (Pro)', ''));
        });
        // Restore user's saved speed
        if (this.userSelectedSpeeds.cursor) {
          $speedSelect.val(this.userSelectedSpeeds.cursor);
        }
      }
    },
    
    // Bind/re-bind color picker events
    bindColorEvents: function() {
      // Cursor color
      $('#fae-cursor-color').off('input change').on('input change', function() {
        const color = $(this).val();
        $('#fae-cursor-color-text').val(color);
        // Track user's color choice
        FaeAdmin.userSelectedColors.cursor = color;
        FaeAdmin.updatePreviewIframe('cursor');
      });
      
      $('#fae-cursor-color-text').off('input change').on('input change', function() {
        let color = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
          $('#fae-cursor-color').val(color);
          // Track user's color choice
          FaeAdmin.userSelectedColors.cursor = color;
          FaeAdmin.updatePreviewIframe('cursor');
        }
      });
      
      // Keyboard color
      $('#fae-keyboard-color').off('input change').on('input change', function() {
        const color = $(this).val();
        $('#fae-keyboard-color-text').val(color);
        // Track user's color choice
        FaeAdmin.userSelectedColors.keyboard = color;
        FaeAdmin.updatePreviewIframe('keyboard');
      });
      
      // Particle color
      $('#fae-particle-color').off('input change').on('input change', function() {
        const color = $(this).val();
        $('#fae-particle-color-text').val(color);
        // Track user's color choice
        FaeAdmin.userSelectedColors.particle = color;
        FaeAdmin.updatePreviewIframe('particle');
      });
      
      // Particle color text input
      $('#fae-particle-color-text').off('input change').on('input change', function() {
        let color = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
          $('#fae-particle-color').val(color);
          // Track user's color choice
          FaeAdmin.userSelectedColors.particle = color;
          FaeAdmin.updatePreviewIframe('particle');
        }
      });
      
      // Keyboard color text input
      $('#fae-keyboard-color-text').off('input change').on('input change', function() {
        let color = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
          $('#fae-keyboard-color').val(color);
          // Track user's color choice
          FaeAdmin.userSelectedColors.keyboard = color;
          FaeAdmin.updatePreviewIframe('keyboard');
        }
      });
    },

    handleCheckboxChange: function () {
      const setting = $(this).attr("name");
      const value = $(this).is(':checked') ? '1' : '0';
      
      // Update hidden input if it exists
      const hiddenInput = $('input[type="hidden"][name="' + setting + '"]');
      if (hiddenInput.length) {
        hiddenInput.val(value);
      }
    },

    handleSettingChange: function () {
      // Settings change handler
    },

    handleColorChange: function () {
      const color = $(this).val();

      // Update the text input if it exists
      const textInput = $(this).siblings('input[type="text"]');
      if (textInput.length) {
        textInput.val(color);
      }

      // Update the selected icon display
      const selectedIcon = $("#fae-icon-picker-trigger .fae-selected-icon");
      selectedIcon.find("svg path").css({
        fill: color,
        stroke: color,
      });
    },

    handleFormSubmit: function (e) {
      e.preventDefault();
      
      const $form = $(this);
      const formId = $form.attr('id');
      
      // Determine which form type and action
      let action, nonce, formData, effectType;
      
      if (formId === 'fae-cursor-form') {
        action = 'fae_save_cursor_settings';
        effectType = 'cursor';
        nonce = typeof faeAdminData !== 'undefined' && faeAdminData.nonces ? faeAdminData.nonces.cursor : '';
        formData = $form.serialize();
      } else if (formId === 'fae-keyboard-form') {
        action = 'fae_save_keyboard_settings';
        effectType = 'keyboard';
        nonce = typeof faeAdminData !== 'undefined' && faeAdminData.nonces ? faeAdminData.nonces.keyboard : '';
        
        // Security: Force multi_color to always be 0 (Pro feature protection)
        // Even if someone inspects and removes disabled attribute, this ensures it's always 0
        // 1. Remove name attribute from checkbox so it won't be submitted
        // 2. Hidden input with same name will ensure value is always 0
        $form.find('#fae-keyboard-multi-color').removeAttr('name').prop('disabled', true).prop('checked', false);
        
        formData = $form.serialize();
      } else if (formId === 'fae-particle-form') {
        action = 'fae_save_particle_settings';
        effectType = 'particle';
        nonce = typeof faeAdminData !== 'undefined' && faeAdminData.nonces ? faeAdminData.nonces.particle : '';
        formData = $form.serialize();
      } else {
        return;
      }
      
      // Show loading state first (before validation check)
      const submitBtn = $form.find('button[type="submit"], input[type="submit"]');
      const originalHtml = submitBtn.html();
      const originalText = submitBtn.text() || submitBtn.val();
      // Store original HTML for restoration
      submitBtn.data('original-html', originalHtml);
      submitBtn.data('original-text', originalText);
      submitBtn.prop("disabled", true);
      submitBtn.html('<span style="display: inline-flex; align-items: center; gap: 6px;"><svg class="fae-icon" viewBox="0 0 24 24" style="width: 16px; height: 16px; animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>Saving...</span>');
      
      // Check if trying to save a non-none effect while another effect type is active
      // Allow preview but prevent saving
      const selectedEffect = $form.find('input[name*="[effect]"]:checked').val() || 'none';
      if (selectedEffect !== 'none' && FaeAdmin.isAnotherEffectTypeActive(effectType)) {
        // Show upgrade modal and prevent form submission
        FaeAdmin.showUpgradeNoticeModal();
        // Reset button state
        submitBtn.prop("disabled", false);
        submitBtn.html(originalHtml);
        return false;
      }
      
      // Prepare AJAX data
      const ajaxData = formData + '&action=' + action + '&nonce=' + nonce;

      // Make AJAX request
      $.ajax({
        url: typeof faeAdminData !== 'undefined' ? faeAdminData.ajaxUrl : ajaxurl,
        type: 'POST',
        data: ajaxData,
        success: function(response) {
          if (response.success) {
            FaeAdmin.showNotice(response.data.message || 'Settings saved successfully!', 'success');
            
            // Determine which tab was saved and update only that tab's status indicator
            let savedType = null;
            if (formId === 'fae-cursor-form') {
              savedType = 'cursor';
            } else if (formId === 'fae-keyboard-form') {
              savedType = 'keyboard';
            } else if (formId === 'fae-particle-form') {
              savedType = 'particle';
            }
            
            // Update only the saved tab's status indicator using saved options from response
            if (savedType && response.data && response.data.options) {
              FaeAdmin.updateTabStatusIndicator(savedType, response.data.options);
              // Update active effect badges for the saved tab only
              FaeAdmin.updateActiveEffectBadges(savedType, response.data.options);
              // Update saved effects state
              if (response.data.options.effect) {
                FaeAdmin.savedEffects[savedType] = response.data.options.effect;
              } else {
                FaeAdmin.savedEffects[savedType] = 'none';
              }
            }
            
            // Update stats after successful save
            FaeAdmin.updateStats();
            // Update status indicators (legacy)
            FaeAdmin.updateStatusIndicators();
            // Update effect type restrictions after save
            FaeAdmin.updateEffectTypeRestrictions();
          } else {
            FaeAdmin.showNotice(response.data && response.data.message ? response.data.message : 'Failed to save settings. Please try again.', 'error');
          }
        },
        error: function(xhr, status, error) {
          let errorMessage = 'An error occurred while saving settings. Please try again.';
          let showUpgradeNotice = false;
          
          // Check if response contains error data with upgrade notice
          if (xhr.responseJSON && xhr.responseJSON.data) {
            if (xhr.responseJSON.data.message) {
              errorMessage = xhr.responseJSON.data.message;
            }
            if (xhr.responseJSON.data.upgrade_notice) {
              showUpgradeNotice = true;
            }
          }
          
          FaeAdmin.showNotice(errorMessage, 'error');
          
          // Show upgrade notice modal if needed
          if (showUpgradeNotice) {
            FaeAdmin.showUpgradeNoticeModal();
          }
          
          console.error('AJAX Error:', error);
        },
        complete: function() {
          // Reset button state
          submitBtn.prop("disabled", false);
          submitBtn.html(originalHtml);
        }
      });
    },

    updateAdvancedSettings: function (effect) {
      const effectSettings = $("#cursor-effects-tab .fae-effect-settings");

      // Show/hide settings based on effect
      if (effect === "none") {
        effectSettings.hide();
        // Hide cursor appearance subgroup
        $('#fae-cursor-appearance-subgroup').hide();
        // Trigger height update
        $(document).trigger('faeSettingsUpdated');
      } else {
        effectSettings.show();

        // Get effect config
        const effectConfig = typeof getEffectConfig !== 'undefined' 
          ? getEffectConfig(effect) 
          : {};

        // Show/hide cursor appearance subgroup based on config
        const showHideCursor = effectConfig.supportsHideCursor !== false;
        const $hideCursorSubgroup = $('#fae-cursor-appearance-subgroup');
        if (showHideCursor) {
          $hideCursorSubgroup.show();
        } else {
          $hideCursorSubgroup.hide();
        }

        // Show/hide icon settings wrapper based on effect config
        const showIcon = effectConfig.effectType === 'icon';
        if (showIcon) {
          $('.fae-icon-settings-wrapper').show();
        } else {
          $('.fae-icon-settings-wrapper').hide();
        }
        
        // Show/hide icon settings (Size and Icon) based on effect config
        // Size should show for effects that support size (drop-effect, rise-effect, flag-effect)
        // Icon should show for effects that support icon (drop-effect, rise-effect)
        const showSize = effectConfig.supportsSize === true;
        const showIconSetting = effectConfig.supportsIcon === true;
        
        $('.fae-icon-setting').each(function() {
          const $setting = $(this);
          // Check if this is the Size setting or Icon setting
          const isSizeSetting = $setting.find('select[name*="[size]"]').length > 0;
          const isIconSetting = $setting.find('input[name*="[icon]"]').length > 0 || $setting.find('.fae-icon-picker-inline').length > 0;
          
          if (isSizeSetting) {
            // Size setting: show if effect supports size
            $setting.toggle(showSize);
          } else if (isIconSetting) {
            // Icon setting: show if effect supports icon
            $setting.toggle(showIconSetting);
          }
        });

        // Show/hide multi-color setting for bubbles-effect and magic-trail
        if (effect === 'bubbles-effect' || effect === 'magic-trail') {
          $('.fae-multi-color-setting').show();
        } else {
          $('.fae-multi-color-setting').hide();
        }

        // Show/hide flag setting for flag-effect
        if (effect === 'flag-effect') {
          $('.fae-flag-setting').show();
          // For flag-effect, handle color and flag position based on flag selection
          const selectedFlag = $('#fae-cursor-flag').val();
          if (selectedFlag) {
            $('.fae-color-setting').hide();
            $('.fae-flag-position-setting').show();
        } else {
            $('.fae-color-setting').show();
            $('.fae-flag-position-setting').hide();
          }
        } else {
          // Hide all flag-related settings for non-flag effects
          $('.fae-flag-setting').hide();
          $('.fae-flag-position-setting').hide();
          // Show color setting if effect supports color
          if (effectConfig.supportsColor !== false) {
            $('.fae-color-setting').show();
          } else {
            $('.fae-color-setting').hide();
          }
        }

        // Show/hide settings based on config
        $(".fae-settings-section").each(function () {
          const $section = $(this);
          const effectType = $section.data("effect-type");

          if (!effectType) {
            // Global settings - show/hide based on config
            const showColor = effectConfig.supportsColor !== false;
            const showSpeed = effectConfig.supportsSpeed !== false;
            
            // Show color setting if supported
            $section.find('.fae-setting-group').each(function() {
              const $group = $(this);
              if ($group.find('input[name*="[color]"]').length && !showColor) {
                $group.hide();
              } else if ($group.find('input[name*="[color]"]').length && showColor) {
                $group.show();
              }
              if ($group.find('select[name*="[speed]"]').length && !showSpeed) {
                $group.hide();
              } else if ($group.find('select[name*="[speed]"]').length && showSpeed) {
                $group.show();
              }
            });
            $section.show();
            return;
          }

          // Show section if effect type matches
          if (effectType === effectConfig.effectType) {
            $section.show();
          } else {
            $section.hide();
          }
        });
        
        // Also handle new grouped layout setting items
        $("#cursor-effects-tab .fae-setting-item").each(function() {
          const $item = $(this);
          const hasColor = $item.find('input[name*="[color]"]').length > 0;
          const hasSpeed = $item.find('select[name*="[speed]"]').length > 0;
          
          if (hasColor) {
            const showColor = effectConfig.supportsColor !== false;
            $item.toggle(showColor);
          }
          if (hasSpeed) {
            const showSpeed = effectConfig.supportsSpeed !== false;
            $item.toggle(showSpeed);
          }
        });
        
        // Handle inline settings in appearance section
        $(".fae-inline-setting").each(function() {
          const $setting = $(this);
          
          // Skip flag-specific settings (handled separately above)
          if ($setting.hasClass('fae-flag-setting') || $setting.hasClass('fae-flag-position-setting')) {
            return;
          }
          
          // Skip color setting for all effects (handled separately above based on effect and flag selection)
          if ($setting.hasClass('fae-color-setting')) {
            return;
          }
          
          // Skip multi-color setting (handled separately above)
          if ($setting.hasClass('fae-multi-color-setting')) {
            return;
          }
          
          // Skip icon settings (Size and Icon - handled separately above)
          if ($setting.hasClass('fae-icon-setting')) {
            return;
          }
          
          const hasColor = $setting.find('input[name*="[color]"]').length > 0;
          const hasSpeed = $setting.find('select[name*="[speed]"]').length > 0;
          
          if (hasColor) {
            const showColor = effectConfig.supportsColor !== false;
            $setting.toggle(showColor);
          }
          if (hasSpeed) {
            const showSpeed = effectConfig.supportsSpeed !== false;
            $setting.toggle(showSpeed);
          }
        });
      }
      
      // Trigger height update after settings change
      setTimeout(() => {
        $(document).trigger('faeSettingsUpdated');
      }, 150);
    },

    loadSettings: function () {
      // Initially hide all effect settings and appearance sections on page load
      // They will only be shown when user actually clicks/selects an effect
      $("#cursor-effects-tab .fae-effect-settings").hide();
      $("#keyboard-effects-tab .fae-keyboard-effect-settings").hide();
      $("#keyboard-effects-tab .fae-appearance-section").hide();
      $("#particle-effects-tab .fae-effect-settings").hide();
      $("#particle-effects-tab .fae-appearance-section").hide();
      $('#fae-cursor-appearance-subgroup').hide();
      $('.fae-icon-settings-wrapper').hide();

      // Load saved cursor effect settings - but don't show them yet
      const effect = $(
        'input[name="fae_cursor_options[effect]"]:checked'
      ).val();
      // Don't call updateAdvancedSettings here - wait for user to click
      FaeAdmin.updateStatusIndicators();

      // Note: Visual "selected" state (purple highlight) is not applied on initial load
      // It will be applied when user clicks on an effect
      // Settings will also be shown when user clicks on an effect

      // Load saved keyboard effect settings - but don't show them yet
      const keyboardEffect = $(
        'input[name="fae_keyboard_options[effect]"]:checked'
      ).val();
      // Don't show settings here - wait for user to click
      // Note: Visual "selected" state is not applied on initial load
      // It will be applied when user clicks on an effect

      // Load saved particle effect settings
      const particleEffect = $(
        'input[name="fae_particle_options[effect]"]:checked'
      ).val();
      
      // Initialize particle appearance settings visibility on page load
      if (particleEffect && particleEffect !== 'none') {
        // Show effect settings and appearance section for saved effect
        $("#particle-effects-tab .fae-effect-settings").show();
        $("#particle-effects-tab .fae-appearance-section").show();
        
        // Get effect config to show/hide color, speed, and interactive cursor settings
        const effectConfig = typeof FAE_PARTICLE_EFFECTS_CONFIG !== 'undefined' && FAE_PARTICLE_EFFECTS_CONFIG[particleEffect]
          ? FAE_PARTICLE_EFFECTS_CONFIG[particleEffect]
          : {};
        
        // Show/hide color setting based on effect config
        const showColor = effectConfig.supportsColor !== false;
        $('#particle-effects-tab .fae-particle-color-setting').toggle(showColor);
        
        // Show/hide speed setting based on effect config
        const showSpeed = effectConfig.supportsSpeed !== false;
        $('#particle-effects-tab .fae-particle-speed-setting').toggle(showSpeed);
        
        // Show/hide Interactive Cursor option based on effect config
        const showInteractiveCursor = effectConfig.supportsInteractiveCursor === true;
        $('#particle-effects-tab .fae-interactive-cursor-setting').toggle(showInteractiveCursor);
        
        // Update limited customization UI
        FaeAdmin.updateParticleLimitedCustomization(particleEffect);
      }

      // Update the selected icon display
      if ($("#cursor-effects-tab .fae-color-picker").val()) {
        const color = $("#cursor-effects-tab .fae-color-picker").val();
        const selectedIcon = $("#fae-icon-picker-trigger .fae-selected-icon");
        selectedIcon.find("svg path").css({
          fill: color,
          stroke: color,
        });
      }
      
      // Update all tab status indicators on page load
      FaeAdmin.updateAllTabStatusIndicators();
      
      // Update active effect badges based on saved state (not form selections)
      FaeAdmin.updateActiveEffectBadges();
      
      // Update effect type restrictions on page load
      FaeAdmin.updateEffectTypeRestrictions();
    },

    updateStats: function () {
      // Update dashboard statistics
      // Use saved effects state (what's actually saved), not form selections
      const cursorEffect = FaeAdmin.savedEffects.cursor || 'none';
      const keyboardEffect = FaeAdmin.savedEffects.keyboard || 'none';
      const particleEffect = FaeAdmin.savedEffects.particle || 'none';
      
      // Collect active effects
      let activeEffects = [];
      if (cursorEffect && cursorEffect !== "none") {
        const cursorConfig = typeof FAE_CURSOR_EFFECTS_CONFIG !== 'undefined' && FAE_CURSOR_EFFECTS_CONFIG[cursorEffect]
          ? FAE_CURSOR_EFFECTS_CONFIG[cursorEffect]
          : null;
        const cursorName = cursorConfig && cursorConfig.displayName 
          ? cursorConfig.displayName 
          : cursorEffect.replace(/-/g, " ").replace(/\b\w/g, (l) => l.toUpperCase());
        activeEffects.push(cursorName);
      }
      if (keyboardEffect && keyboardEffect !== "none") {
        // Keyboard effects don't have a JS config, so use formatted name
        activeEffects.push(keyboardEffect.replace(/-/g, " ").replace(/\b\w/g, (l) => l.toUpperCase()));
      }
      if (particleEffect && particleEffect !== "none") {
        const particleConfig = typeof FAE_PARTICLE_EFFECTS_CONFIG !== 'undefined' && FAE_PARTICLE_EFFECTS_CONFIG[particleEffect]
          ? FAE_PARTICLE_EFFECTS_CONFIG[particleEffect]
          : null;
        const particleName = particleConfig && particleConfig.displayName 
          ? particleConfig.displayName 
          : particleEffect.replace(/-/g, " ").replace(/\b\w/g, (l) => l.toUpperCase());
        activeEffects.push(particleName);
      }
      
      const isActive = activeEffects.length > 0;
      const $statCards = $(".fae-stat-card");
      const $statusCard = $statCards.eq(0);      // First card: Status
      const $activeEffectsCard = $statCards.eq(1); // Second card: Active Effects
      // Third card (Effects Available) doesn't need JS updates
      
      // Update Status card
      $statusCard.find(".fae-stat-value").text(isActive ? "Active" : "Inactive");
      $statusCard.toggleClass("fae-stat-card-inactive", !isActive);
      
      // Update Active Effects card
      $activeEffectsCard.toggleClass("fae-stat-card-inactive", !isActive);
      const $effectsValue = $activeEffectsCard.find(".fae-stat-value");
      let $effectsDetail = $activeEffectsCard.find(".fae-stat-detail");
      
      if (activeEffects.length === 0) {
        $effectsValue.text("None").removeClass("fae-stat-value-small");
        $effectsDetail.remove();
      } else if (activeEffects.length === 1) {
        $effectsValue.text(activeEffects[0]).removeClass("fae-stat-value-small");
        $effectsDetail.remove();
      } else {
        $effectsValue.text(activeEffects.length + " Active").addClass("fae-stat-value-small");
        if ($effectsDetail.length === 0) {
          $activeEffectsCard.append('<p class="fae-stat-detail">' + activeEffects.join(", ") + '</p>');
        } else {
          $effectsDetail.text(activeEffects.join(", "));
        }
      }
    },

    updateStatusIndicators: function () {
      // Status indicators are now based on saved settings only (from PHP)
      // They update only after form submission, not on selection change
      // This function is kept for potential future use but doesn't update UI dynamically
    },
    
    updateAllTabStatusIndicators: function () {
      // Update cursor effect tab status - read from saved options, not form values
      // This prevents showing green dots on tabs with unsaved selections
      const cursorEffect = $('input[name="fae_cursor_options[effect]"]:checked').val() || 'none';
      const $cursorStatus = $('.fae-tab-status[data-effect-type="cursor"]');
      if (cursorEffect !== 'none') {
        $cursorStatus.addClass('active').attr('title', 'Active');
      } else {
        $cursorStatus.removeClass('active').attr('title', 'Inactive');
      }
      
      // Update keyboard effect tab status - read from saved options, not form values
      const keyboardEffect = $('input[name="fae_keyboard_options[effect]"]:checked').val() || 'none';
      const $keyboardStatus = $('.fae-tab-status[data-effect-type="keyboard"]');
      if (keyboardEffect !== 'none') {
        $keyboardStatus.addClass('active').attr('title', 'Active');
      } else {
        $keyboardStatus.removeClass('active').attr('title', 'Inactive');
      }
      
      // Update particle effect tab status - read from saved options, not form values
      const particleEffect = $('input[name="fae_particle_options[effect]"]:checked').val() || 'none';
      const $particleStatus = $('.fae-tab-status[data-effect-type="particle"]');
      if (particleEffect !== 'none') {
        $particleStatus.addClass('active').attr('title', 'Active');
      } else {
        $particleStatus.removeClass('active').attr('title', 'Inactive');
      }
    },
    
    updateTabStatusIndicator: function (type, savedOptions) {
      // Update status indicator for a specific tab using saved options
      // This ensures we only show green dots for actually saved effects
      let effect = 'none';
      if (savedOptions && savedOptions.effect) {
        effect = savedOptions.effect;
      }
      
      const $status = $('.fae-tab-status[data-effect-type="' + type + '"]');
      if (effect !== 'none') {
        $status.addClass('active').attr('title', 'Active');
      } else {
        $status.removeClass('active').attr('title', 'Inactive');
      }
    },

    // Check if another effect type is currently active (using saved state)
    isAnotherEffectTypeActive: function(currentType) {
      // Use saved effects state (what's actually saved, not form selections)
      const cursorEffect = FaeAdmin.savedEffects.cursor || 'none';
      const keyboardEffect = FaeAdmin.savedEffects.keyboard || 'none';
      const particleEffect = FaeAdmin.savedEffects.particle || 'none';
      
      if (currentType === 'cursor') {
        return (keyboardEffect !== 'none' || particleEffect !== 'none');
      } else if (currentType === 'keyboard') {
        return (cursorEffect !== 'none' || particleEffect !== 'none');
      } else if (currentType === 'particle') {
        return (cursorEffect !== 'none' || keyboardEffect !== 'none');
      }
      return false;
    },
    
    // Update UI to mark other effect types when one is active (for visual indication)
    // But don't disable them - allow preview, only prevent saving
    updateEffectTypeRestrictions: function() {
      // Use saved effects state (what's actually saved, not form selections)
      const cursorEffect = FaeAdmin.savedEffects.cursor || 'none';
      const keyboardEffect = FaeAdmin.savedEffects.keyboard || 'none';
      const particleEffect = FaeAdmin.savedEffects.particle || 'none';
      
      // Check which effect types are active
      const cursorActive = cursorEffect !== 'none';
      const keyboardActive = keyboardEffect !== 'none';
      const particleActive = particleEffect !== 'none';
      
      // Mark keyboard and particle effects if cursor is active (for visual indication only)
      // Don't disable - allow preview, only prevent saving
      if (cursorActive) {
        $('#keyboard-effects-tab .fae-effect-option:not([data-effect-id="none"])').addClass('fae-effect-type-disabled');
        $('#particle-effects-tab .fae-effect-option:not([data-effect-id="none"])').addClass('fae-effect-type-disabled');
        // Don't disable radio buttons - allow selection for preview
      } else {
        $('#keyboard-effects-tab .fae-effect-option').removeClass('fae-effect-type-disabled');
        $('#particle-effects-tab .fae-effect-option').removeClass('fae-effect-type-disabled');
      }
      
      // Mark cursor and particle effects if keyboard is active (for visual indication only)
      if (keyboardActive) {
        $('#cursor-effects-tab .fae-effect-option:not([data-effect-id="none"])').addClass('fae-effect-type-disabled');
        $('#particle-effects-tab .fae-effect-option:not([data-effect-id="none"])').addClass('fae-effect-type-disabled');
        // Don't disable radio buttons - allow selection for preview
      } else {
        $('#cursor-effects-tab .fae-effect-option').removeClass('fae-effect-type-disabled');
        $('#particle-effects-tab .fae-effect-option').removeClass('fae-effect-type-disabled');
      }
      
      // Mark cursor and keyboard effects if particle is active (for visual indication only)
      if (particleActive) {
        $('#cursor-effects-tab .fae-effect-option:not([data-effect-id="none"])').addClass('fae-effect-type-disabled');
        $('#keyboard-effects-tab .fae-effect-option:not([data-effect-id="none"])').addClass('fae-effect-type-disabled');
        // Don't disable radio buttons - allow selection for preview
      } else {
        $('#cursor-effects-tab .fae-effect-option').removeClass('fae-effect-type-disabled');
        $('#keyboard-effects-tab .fae-effect-option').removeClass('fae-effect-type-disabled');
      }
    },
    
    // Show upgrade notice modal
    showUpgradeNoticeModal: function() {
      const upgradeUrl = typeof faeAdminData !== 'undefined' && faeAdminData.upgradeUrl 
        ? faeAdminData.upgradeUrl 
        : 'https://faecursor.com/';
      
      // Create modal if it doesn't exist
      if ($('#fae-upgrade-notice-modal').length === 0) {
        const modalHtml = `
          <div id="fae-upgrade-notice-modal" class="fae-upgrade-modal">
            <div class="fae-upgrade-modal-backdrop"></div>
            <div class="fae-upgrade-modal-content">
              <button type="button" class="fae-upgrade-modal-close" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 20px; height: 20px;">
                  <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <div class="fae-upgrade-modal-body">
                <svg class="fae-upgrade-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                <h3>Pro Feature</h3>
                <p>Only one effect type can be active at a time in the free version. Upgrade to Pro to use multiple effects (Cursor, Keyboard, and Screen) simultaneously.</p>
                <a href="${upgradeUrl}" target="_blank" class="fae-upgrade-button">Upgrade to Pro</a>
              </div>
            </div>
          </div>
        `;
        $('body').append(modalHtml);
        
        // Close modal handlers
        $('#fae-upgrade-notice-modal .fae-upgrade-modal-close, #fae-upgrade-notice-modal .fae-upgrade-modal-backdrop').on('click', function() {
          $('#fae-upgrade-notice-modal').removeClass('active');
        });
        
        // Close on Escape key
        $(document).on('keydown', function(e) {
          if (e.key === 'Escape' && $('#fae-upgrade-notice-modal').hasClass('active')) {
            $('#fae-upgrade-notice-modal').removeClass('active');
          }
        });
      }
      
      // Show modal
      $('#fae-upgrade-notice-modal').addClass('active');
    },

    updateActiveEffectBadges: function (specificType, savedOptions) {
      // If specificType and savedOptions are provided, update only that tab
      // Otherwise, update all tabs based on saved state
      if (specificType && savedOptions) {
        // Remove badges and classes only for the specific tab being updated
        const $tab = $('#' + specificType + '-effects-tab');
        $tab.find('.fae-effect-active-badge').remove();
        $tab.find('.fae-effect-option').removeClass('fae-effect-active');
        
        // Update only the specific tab that was saved
        const effect = savedOptions.effect || 'none';
        if (effect !== 'none') {
          // Update saved state
          FaeAdmin.savedEffects[specificType] = effect;
          
          // Update badge for this specific tab
          const $option = $tab.find('.fae-effect-option[data-effect-id="' + effect + '"]');
          if ($option.length) {
            $option.addClass('fae-effect-active');
            $option.prepend('<span class="fae-effect-active-badge" title="Currently active on website">Active</span>');
          }
        } else {
          // Effect was disabled
          FaeAdmin.savedEffects[specificType] = 'none';
        }
      } else {
        // Remove all existing active badges and classes (for full refresh)
        $('.fae-effect-active-badge').remove();
        $('.fae-effect-option').removeClass('fae-effect-active');
        // Update all tabs based on saved state (not form values)
        // Update cursor effects (within cursor effects tab)
        const cursorEffect = FaeAdmin.savedEffects.cursor;
        if (cursorEffect && cursorEffect !== 'none') {
          const $cursorOption = $('#cursor-effects-tab .fae-effect-option[data-effect-id="' + cursorEffect + '"]');
          if ($cursorOption.length) {
            $cursorOption.addClass('fae-effect-active');
            $cursorOption.prepend('<span class="fae-effect-active-badge" title="Currently active on website">Active</span>');
          }
        }
        
        // Update keyboard effects (within keyboard effects tab)
        const keyboardEffect = FaeAdmin.savedEffects.keyboard;
        if (keyboardEffect && keyboardEffect !== 'none') {
          const $keyboardOption = $('#keyboard-effects-tab .fae-effect-option[data-effect-id="' + keyboardEffect + '"]');
          if ($keyboardOption.length) {
            $keyboardOption.addClass('fae-effect-active');
            $keyboardOption.prepend('<span class="fae-effect-active-badge" title="Currently active on website">Active</span>');
          }
        }
        
        // Update particle effects (within particle effects tab)
        const particleEffect = FaeAdmin.savedEffects.particle;
        if (particleEffect && particleEffect !== 'none') {
          const $particleOption = $('#particle-effects-tab .fae-effect-option[data-effect-id="' + particleEffect + '"]');
          if ($particleOption.length) {
            $particleOption.addClass('fae-effect-active');
            $particleOption.prepend('<span class="fae-effect-active-badge" title="Currently active on website">Active</span>');
          }
        }
      }
    },

    showNotice: function (message, type) {
      // Remove any existing notices first with smooth fade out
      $('.fae-ajax-notice').each(function() {
        const $existing = $(this);
        if (!$existing.hasClass('fade-out')) {
          $existing.addClass('fade-out');
          setTimeout(() => {
            $existing.remove();
          }, 350);
        }
      });
      
      // Wait a bit for existing notice to fade out before showing new one
      setTimeout(() => {
        const icon = type === 'success' 
          ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 20px; height: 20px; flex-shrink: 0;"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
          : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 20px; height: 20px; flex-shrink: 0;"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        
        // Calculate top position - account for WordPress admin bar
        const getTopPosition = function() {
          const adminBar = $('#wpadminbar');
          if (adminBar.length && adminBar.is(':visible')) {
            return (adminBar.outerHeight() + 20) + 'px';
          }
          // Check if we're in WordPress admin
          if ($('body').hasClass('wp-admin')) {
            return '32px';
          }
          return '20px';
        };
        const topPosition = getTopPosition();
        
      const notice = $(
          '<div class="fae-ajax-notice fae-ajax-notice-' + type + '">' +
          '<div class="fae-notice-content">' +
          '<span class="fae-notice-icon">' + icon + '</span>' +
          '<span class="fae-notice-message">' + message + '</span>' +
          '<button type="button" class="fae-notice-close" title="Dismiss">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 18px; height: 18px;"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
          '</button>' +
          '</div>' +
          '</div>'
        );
        
        // Add animation styles if not already added
        if (!$('#fae-notice-styles').length) {
          $('head').append(
            '<style id="fae-notice-styles">' +
            '.fae-ajax-notice { ' +
            '  position: fixed; ' +
            '  right: 20px; ' +
            '  z-index: 100001; ' +
            '  min-width: 320px; ' +
            '  max-width: 500px; ' +
            '  pointer-events: none; ' +
            '  opacity: 0; ' +
            '  transform: translateX(calc(100% + 20px)); ' +
            '  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); ' +
            '}' +
            '.fae-ajax-notice.show { ' +
            '  opacity: 1; ' +
            '  transform: translateX(0); ' +
            '  pointer-events: auto; ' +
            '}' +
            '.fae-ajax-notice.fade-out { ' +
            '  opacity: 0; ' +
            '  transform: translateX(calc(100% + 20px)); ' +
            '  pointer-events: none; ' +
            '}' +
            '.fae-notice-content { ' +
            '  background: ' + (type === 'success' ? '#10b981' : '#ef4444') + '; ' +
            '  color: white; ' +
            '  padding: 16px 20px; ' +
            '  border-radius: 12px; ' +
            '  box-shadow: 0 20px 40px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05); ' +
            '  display: flex; ' +
            '  align-items: center; ' +
            '  gap: 14px; ' +
            '  backdrop-filter: blur(10px); ' +
            '}' +
            '.fae-notice-icon { ' +
            '  flex-shrink: 0; ' +
            '  display: flex; ' +
            '  align-items: center; ' +
            '  justify-content: center; ' +
            '}' +
            '.fae-notice-message { ' +
            '  flex: 1; ' +
            '  font-size: 14px; ' +
            '  line-height: 1.5; ' +
            '  font-weight: 500; ' +
            '  letter-spacing: -0.01em; ' +
            '}' +
            '.fae-notice-close { ' +
            '  background: rgba(255,255,255,0.2); ' +
            '  border: none; ' +
            '  color: white; ' +
            '  cursor: pointer; ' +
            '  padding: 6px; ' +
            '  margin-left: 4px; ' +
            '  border-radius: 6px; ' +
            '  display: flex; ' +
            '  align-items: center; ' +
            '  justify-content: center; ' +
            '  opacity: 0.8; ' +
            '  transition: all 0.2s ease; ' +
            '  flex-shrink: 0; ' +
            '}' +
            '.fae-notice-close:hover { ' +
            '  opacity: 1; ' +
            '  background: rgba(255,255,255,0.3); ' +
            '  transform: scale(1.1); ' +
            '}' +
            '.fae-notice-close:active { ' +
            '  transform: scale(0.95); ' +
            '}' +
            '@keyframes spin { ' +
            '  from { transform: rotate(0deg); } ' +
            '  to { transform: rotate(360deg); } ' +
            '}' +
            '@media (max-width: 782px) { ' +
            '  .fae-ajax-notice { ' +
            '    right: 10px; ' +
            '    left: 10px; ' +
            '    min-width: auto; ' +
            '    max-width: none; ' +
            '  }' +
            '}' +
            '</style>'
          );
        }
        
        // Set top position dynamically
        notice.css('top', topPosition);
        
        $('body').append(notice);
        
        // Trigger animation by adding show class after a tiny delay
      setTimeout(() => {
          notice.addClass('show');
        }, 10);
        
        // Auto-dismiss after 4.5 seconds
        const autoDismiss = setTimeout(() => {
          notice.removeClass('show').addClass('fade-out');
          setTimeout(() => {
          notice.remove();
          }, 400);
        }, 4500);
        
        // Manual dismiss
        notice.find('.fae-notice-close').on('click', function(e) {
          e.stopPropagation();
          clearTimeout(autoDismiss);
          notice.removeClass('show').addClass('fade-out');
          setTimeout(() => {
            notice.remove();
          }, 400);
        });
      }, 100);
    },

    // Icon Picker Methods
    openIconPicker: function (e) {
      e.preventDefault();
      e.stopPropagation();

      const modal = $("#fae-icon-picker-modal");
      const trigger = $("#fae-icon-picker-trigger");

      modal.addClass("active");
      trigger.addClass("active");

      // Focus on search input
      setTimeout(() => {
        $("#fae-icon-search").focus();
      }, 100);
    },

    closeIconPicker: function (e) {
      e.preventDefault();
      e.stopPropagation();

      const modal = $("#fae-icon-picker-modal");
      const trigger = $("#fae-icon-picker-trigger");

      modal.removeClass("active");
      trigger.removeClass("active");

      // Clear search
      $("#fae-icon-search").val("");
      FaeAdmin.filterIcons();
    },

    handleModalClick: function (e) {
      // Close modal if clicking on the backdrop
      if (e.target === this) {
        FaeAdmin.closeIconPicker(e);
      }
    },

    handleKeydown: function (e) {
      const modal = $("#fae-icon-picker-modal");

      // Close modal on Escape key
      if (e.key === "Escape" && modal.hasClass("active")) {
        FaeAdmin.closeIconPicker(e);
      }
    },

    selectIcon: function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $this = $(this);
      const iconFile = $this.data("icon");
      const iconName = $this.data("name");

      // Update hidden input
      $("#fae-selected-icon-input").val(iconFile);

      // Update trigger display
      const trigger = $("#fae-icon-picker-trigger");
      const selectedIcon = trigger.find(".fae-selected-icon");
      const iconNameSpan = trigger.find(".fae-icon-name");

      // Update icon display
      const iconSvg = $this.find(".fae-icon-preview svg").clone();
      selectedIcon.html(iconSvg);

      // Apply color to the selected icon
      const currentColor =
        $('input[name="fae_cursor_options[color]"]').val() || "#667eea";
      selectedIcon.find("svg path").css({
        fill: currentColor,
        stroke: currentColor,
      });

      // Update name
      iconNameSpan.text(iconName);

      // Update selection in grid
      $(".fae-icon-option").removeClass("selected");
      $this.addClass("selected");
    },

    filterIcons: function () {
      const searchTerm = $(this).val().toLowerCase();
      const iconOptions = $(".fae-icon-option");

      iconOptions.each(function () {
        const $this = $(this);
        const iconName = $this.data("name").toLowerCase();

        if (iconName.includes(searchTerm)) {
          $this.show();
        } else {
          $this.hide();
        }
      });
    },

    handleScopeTypeChange: function () {
      const $select = $(this);
      const scopeType = $select.val();
      // Look for scope options in multiple possible parent structures (old and new layout)
      let $scopeOptions = $select.closest('.fae-settings-section').find('.fae-scope-option');
      if ($scopeOptions.length === 0) {
        $scopeOptions = $select.closest('.fae-settings-subgroup').find('.fae-scope-option');
      }
      if ($scopeOptions.length === 0) {
        $scopeOptions = $select.closest('.fae-settings-subgroup-content').find('.fae-scope-option');
      }
      
      // Hide all scope options with animation
      $scopeOptions.slideUp(200);
      
      // Show the selected scope option with animation
      $scopeOptions.filter('[data-scope-type="' + scopeType + '"]').slideDown(200);
    },

    handleUserRestrictionTypeChange: function () {
      const $radio = $(this);
      
      // Don't handle if radio is disabled (Pro feature)
      if ($radio.is(':disabled')) {
        return;
      }
      
      const restrictionType = $radio.val();
      // Look for roles wrapper in multiple possible parent structures (old and new layout)
      let $wrapper = $radio.closest('.fae-setting-group').find('.fae-specific-roles-wrapper');
      if ($wrapper.length === 0) {
        $wrapper = $radio.closest('.fae-settings-subgroup-content').find('.fae-specific-roles-wrapper');
      }
      if ($wrapper.length === 0) {
        $wrapper = $radio.closest('.fae-settings-subgroup').find('.fae-specific-roles-wrapper');
      }
      
      if (restrictionType === 'specific') {
        $wrapper.slideDown(200);
      } else {
        $wrapper.slideUp(200);
        // Uncheck all role checkboxes when switching to "All Users"
        $wrapper.find('input[type="checkbox"][name*="[user_roles]"]').prop('checked', false);
      }
    },

    initPreviewIframes: function() {
      const adminUrl = typeof faeAdminData !== 'undefined' ? faeAdminData.adminUrl : (window.location.origin + '/wp-admin/admin.php');
      
      // Effects that support icon/size (from config)
      const iconEffects = ['drop-effect', 'rise-effect'];
      
      // Show/hide icon settings based on selected effect
      function updateIconSettingsVisibility(effect) {
        const showIcon = iconEffects.includes(effect);
        $('.fae-icon-setting').toggle(showIcon);
      }
      
      // Debounced iframe update - make it accessible to handlers
      const updateIframe = debounce(function(type) {
        let iframe, effect, color, speed, size, icon, flag, flagPosition, multiColor, bg;
        
        if (type === 'cursor') {
          iframe = document.getElementById('fae-cursor-preview-iframe');
          effect = $('input[name="fae_cursor_options[effect]"]:checked').val() || 'none';
          color = $('#fae-cursor-color').val() || '#667eea';
          speed = $('#fae-cursor-speed').val() || 'normal';
          size = $('#fae-cursor-size').val() || '1.5rem';
          icon = $('#fae-cursor-icon').val() || 'star.svg';
          flag = $('#fae-cursor-flag').val() || '';
          flagPosition = $('#fae-cursor-flag-position').val() || 'center';
          multiColor = $('#fae-cursor-multi-color').is(':checked') ? '1' : '0';
          bg = $('#fae-cursor-preview-bg').attr('data-bg') || 'dark';
          
          // Update icon settings visibility
          updateIconSettingsVisibility(effect);
        } else if (type === 'keyboard') {
          iframe = document.getElementById('fae-keyboard-preview-iframe');
          effect = $('input[name="fae_keyboard_options[effect]"]:checked').val() || 'none';
          // Use sparkle color picker if sparkle-keys is selected, otherwise use regular color picker
          if (effect === 'sparkle-keys') {
            color = $('#fae-keyboard-color-sparkle').val() || $('#fae-keyboard-color').val() || '#667eea';
          } else {
            color = $('#fae-keyboard-color').val() || '#667eea';
          }
          speed = 'normal';
          size = '1.5rem';
          icon = 'star.svg';
          flag = '';
          flagPosition = 'center';
          multiColor = '0'; // Multi-color is disabled (Pro feature)
          bg = $('#fae-keyboard-preview-bg').attr('data-bg') || 'dark';
        } else if (type === 'particle') {
          iframe = document.getElementById('fae-particle-preview-iframe');
          effect = $('input[name="fae_particle_options[effect]"]:checked').val() || 'none';
          color = $('#fae-particle-color').val() || '#667eea';
          speed = $('#fae-particle-speed').val() || 'normal';
          size = '1.5rem';
          icon = 'star.svg';
          flag = '';
          flagPosition = 'center';
          multiColor = '0';
          bg = $('#fae-particle-preview-bg').attr('data-bg') || 'dark';
        }
        
        if (iframe) {
          // Remove loaded class to hide iframe during reload
          iframe.classList.remove('loaded');
          
          const params = new URLSearchParams({
            fae_embed_preview: '1',
            type: type,
            effect: effect,
            color: color,
            speed: speed,
            size: size,
            icon: icon,
            flag: flag || '',
            flag_position: flagPosition || 'center',
            multi_color: multiColor,
            bg: bg || 'dark',
            _t: Date.now() // Cache-busting parameter to ensure iframe reloads
          });
          iframe.src = adminUrl + '?' + params.toString();
          
          // Add loaded class when iframe finishes loading
          iframe.onload = function() {
            this.classList.add('loaded');
          };
        }
      }, 300);
      
      // Make updateIframe accessible to handlers
      FaeAdmin.updatePreviewIframe = updateIframe;
      
      // Cursor effect changes
      $('input[name="fae_cursor_options[effect]"]').on('change', function() {
        const effect = $(this).val();
        // Update all settings visibility based on effect
        FaeAdmin.updateAdvancedSettings(effect);
        // For flag-effect, also update color picker visibility based on flag selection
        if (effect === 'flag-effect') {
          updateColorPickerVisibility();
        }
        updateIframe('cursor');
      });
      
      // Initialize icon settings visibility on page load
      const initialEffect = $('input[name="fae_cursor_options[effect]"]:checked').val() || 'none';
      updateIconSettingsVisibility(initialEffect);
      // Initialize all settings visibility using updateAdvancedSettings
      FaeAdmin.updateAdvancedSettings(initialEffect);
      // For flag-effect, also update color picker visibility based on flag selection
      if (initialEffect === 'flag-effect') {
        updateColorPickerVisibility();
      }
      $('#fae-cursor-color').on('input', function() {
        $('#fae-cursor-color-text').val(this.value);
        updateIframe('cursor');
      });
      $('#fae-cursor-color-text').on('change', function() {
        $('#fae-cursor-color').val(this.value);
        updateIframe('cursor');
      });
      $('#fae-cursor-speed').on('change', function() {
        // Track user's speed choice
        FaeAdmin.userSelectedSpeeds.cursor = $(this).val();
        updateIframe('cursor');
      });
      $('#fae-cursor-size, #fae-cursor-flag-position').on('change', function() {
        updateIframe('cursor');
      });
      $('#fae-cursor-multi-color').on('change', function() {
        updateIframe('cursor');
      });
      
      // Icon picker toggle
      $('#fae-icon-trigger-cursor').on('click', function(e) {
        e.preventDefault();
        $('#fae-icon-dropdown-cursor').toggleClass('active');
      });
      
      // Flag picker toggle
      $('#fae-flag-trigger-cursor').on('click', function(e) {
        e.preventDefault();
        $('#fae-flag-dropdown-cursor').toggleClass('active');
      });
      
      // Flag selection
      $('#fae-flag-dropdown-cursor').on('click', '.fae-flag-dropdown-item', function() {
        const $item = $(this);
        const flagFile = $item.data('flag') || '';
        const flagCode = $item.data('name') || '';
        
        // Update hidden input
        $('#fae-cursor-flag').val(flagFile);
        
        // Update button display
        if (flagFile) {
          const flagUrl = (typeof faeAdminData !== 'undefined' ? faeAdminData.assetsUrl : '') + 'flags/' + flagFile;
          $('#fae-flag-preview-cursor').html('<img src="' + flagUrl + '" alt="' + flagCode + '" style="width: 24px; height: 18px; object-fit: cover; border-radius: 2px; border: 1px solid #e5e7eb;">');
          $('#fae-flag-name-cursor').text(flagCode);
          // Hide color picker and show flag position when flag is selected
          $('.fae-color-setting').hide();
          $('.fae-flag-position-setting').show();
        } else {
          $('#fae-flag-preview-cursor').html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; color: #9ca3af;"><path d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 1 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10"/></svg>');
          $('#fae-flag-name-cursor').text('FILL');
          // Show color picker and hide flag position when FILL is selected
          $('.fae-color-setting').show();
          $('.fae-flag-position-setting').hide();
        }
        
        // Update selected state
        $('#fae-flag-dropdown-cursor .fae-flag-dropdown-item').removeClass('selected');
        $item.addClass('selected');
        
        // Close dropdown
        $('#fae-flag-dropdown-cursor').removeClass('active');
        
        // Update preview
        updateIframe('cursor');
      });
      
      // Show/hide color picker and flag position based on initial flag selection
      function updateColorPickerVisibility() {
        const selectedFlag = $('#fae-cursor-flag').val();
        if ($('input[name="fae_cursor_options[effect]"]:checked').val() === 'flag-effect') {
          if (selectedFlag) {
            $('.fae-color-setting').hide();
            $('.fae-flag-position-setting').show();
          } else {
            $('.fae-color-setting').show();
            $('.fae-flag-position-setting').hide();
          }
        }
      }
      
      // Initialize color picker visibility on page load
      updateColorPickerVisibility();
      
      // Note: Effect change handling is done in the handler above (line 776)
      // This ensures all settings are properly updated via updateAdvancedSettings
      
      // Flag search - search by country name
      $('#fae-flag-search-cursor').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#fae-flag-grid-cursor .fae-flag-dropdown-item').each(function() {
          const countryName = $(this).data('country-name') || '';
          if (countryName.includes(searchTerm)) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });
      });
      
      // Close flag dropdown when clicking outside
      $(document).on('click', function(e) {
        if (!$(e.target).closest('.fae-flag-picker-inline').length) {
          $('#fae-flag-dropdown-cursor').removeClass('active');
        }
      });
      
      // Icon selection
      $('#fae-icon-dropdown-cursor').on('click', '.fae-icon-dropdown-item', function() {
        const $item = $(this);
        const iconFile = $item.data('icon');
        const iconSvg = $item.html();
        const iconName = iconFile.replace('.svg', '');
        
        // Update hidden input
        $('#fae-cursor-icon').val(iconFile);
        
        // Update button display
        $('#fae-icon-preview-cursor').html(iconSvg);
        $('#fae-icon-name-cursor').text(iconName);
        
        // Update selected state
        $('#fae-icon-dropdown-cursor .fae-icon-dropdown-item').removeClass('selected');
        $item.addClass('selected');
        
        // Close dropdown
        $('#fae-icon-dropdown-cursor').removeClass('active');
        
        // Update preview
        updateIframe('cursor');
      });
      
      // Close dropdown when clicking outside
      $(document).on('click', function(e) {
        if (!$(e.target).closest('.fae-icon-picker-inline').length) {
          $('.fae-icon-dropdown').removeClass('active');
        }
        if (!$(e.target).closest('.fae-flag-picker-inline').length) {
          $('.fae-flag-dropdown').removeClass('active');
        }
      });
      
      // Keyboard effect changes
      $('input[name="fae_keyboard_options[effect]"]').on('change', function() {
        updateIframe('keyboard');
      });
      $('#fae-keyboard-color, #fae-keyboard-color-sparkle').on('input', function() {
        const $textInput = $(this).attr('id') === 'fae-keyboard-color' ? $('#fae-keyboard-color-text') : $('#fae-keyboard-color-text-sparkle');
        $textInput.val(this.value);
        // Sync both color inputs if sparkle-keys
        if ($('input[name="fae_keyboard_options[effect]"]:checked').val() === 'sparkle-keys') {
          if ($(this).attr('id') === 'fae-keyboard-color') {
            $('#fae-keyboard-color-sparkle').val(this.value);
            $('#fae-keyboard-color-text-sparkle').val(this.value);
          } else {
            $('#fae-keyboard-color').val(this.value);
            $('#fae-keyboard-color-text').val(this.value);
          }
        }
        updateIframe('keyboard');
      });
      $('#fae-keyboard-color-text, #fae-keyboard-color-text-sparkle').on('change', function() {
        const $colorInput = $(this).attr('id') === 'fae-keyboard-color-text' ? $('#fae-keyboard-color') : $('#fae-keyboard-color-sparkle');
        $colorInput.val(this.value);
        // Sync both color inputs if sparkle-keys
        if ($('input[name="fae_keyboard_options[effect]"]:checked').val() === 'sparkle-keys') {
          if ($(this).attr('id') === 'fae-keyboard-color-text') {
            $('#fae-keyboard-color-sparkle').val(this.value);
            $('#fae-keyboard-color-text-sparkle').val(this.value);
          } else {
            $('#fae-keyboard-color').val(this.value);
            $('#fae-keyboard-color-text').val(this.value);
          }
        }
        updateIframe('keyboard');
      });
      // Multi-color is disabled (Pro feature) - prevent interaction
      $('#fae-keyboard-multi-color').on('click', function(e) {
        e.preventDefault();
        return false;
      });
      
      // Particle effect changes
      $('input[name="fae_particle_options[effect]"]').on('change', function() {
        updateIframe('particle');
      });
      $('#fae-particle-color').on('input', function() {
        $('#fae-particle-color-text').val(this.value);
        updateIframe('particle');
      });
      $('#fae-particle-color-text').on('change', function() {
        $('#fae-particle-color').val(this.value);
        updateIframe('particle');
      });
      $('#fae-particle-speed').on('change', function() {
        // Track user's speed choice
        FaeAdmin.userSelectedSpeeds.particle = $(this).val();
        updateIframe('particle');
      });
      
      // Load saved background preference from localStorage (fallback to cookie, then default)
      function getSavedBg() {
        // Try localStorage first
        let savedBg = localStorage.getItem('fae_preview_bg');
        if (!savedBg) {
          // Fallback to reading from existing toggle button data attribute (set by PHP from cookie)
          savedBg = $('.fae-preview-bg-toggle').first().attr('data-bg') || 'dark';
          // Save to localStorage for future use
          localStorage.setItem('fae_preview_bg', savedBg);
        }
        return savedBg || 'dark';
      }
      
      // Set cookie helper function
      function setPreviewBgCookie(bg) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (365 * 24 * 60 * 60 * 1000)); // 1 year
        document.cookie = 'fae_preview_bg=' + bg + ';expires=' + expires.toUTCString() + ';path=/';
      }
      
      // Background color toggle button clicks
      $('.fae-preview-bg-toggle').on('click', function() {
        const $toggle = $(this);
        const currentBg = $toggle.attr('data-bg');
        const newBg = currentBg === 'dark' ? 'light' : 'dark';
        
        // Save to both localStorage and cookie
        localStorage.setItem('fae_preview_bg', newBg);
        setPreviewBgCookie(newBg);
        
        // Apply to all toggles (keep them in sync)
        $('.fae-preview-bg-toggle').attr('data-bg', newBg);
        
        // Update iframe wrapper backgrounds immediately to prevent flash
        const bgGradient = newBg === 'light' 
          ? 'linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%)' 
          : 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)';
        $('.fae-preview-iframe-wrapper').css('background', bgGradient);
        
        const type = $toggle.attr('id').replace('fae-', '').replace('-preview-bg', '');
        updateIframe(type);
      });
      
      // Fullscreen preview modal
      const $modal = $('#fae-preview-modal');
      const $modalIframe = $('#fae-modal-iframe');
      const $modalHint = $('#fae-modal-hint');
      
      // Hint messages for each type
      const hintMessages = {
        cursor: 'Move mouse around to see the effect',
        keyboard: 'Click inside preview and type to see the effect',
        particle: 'Move mouse around to see the effect'
      };
      
      // Expand button click
      $('.fae-expand-btn').on('click', function() {
        const type = $(this).data('preview-type');
        const $iframe = $('#fae-' + type + '-preview-iframe');
        if ($iframe.length) {
          // Get current iframe src and update modal
          $modalIframe.attr('src', $iframe.attr('src'));
          $modalHint.text(hintMessages[type] || hintMessages.cursor);
          $modal.addClass('active');
          $('body').css('overflow', 'hidden');
        }
      });
      
      // Close modal
      $('#fae-modal-close').on('click', function() {
        $modal.removeClass('active');
        $modalIframe.attr('src', '');
        $('body').css('overflow', '');
      });
      
      // Close on Escape key
      $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $modal.hasClass('active')) {
          $modal.removeClass('active');
          $modalIframe.attr('src', '');
          $('body').css('overflow', '');
        }
      });
      
      // Close on backdrop click
      $modal.on('click', function(e) {
        if (e.target === this) {
          $modal.removeClass('active');
          $modalIframe.attr('src', '');
          $('body').css('overflow', '');
        }
      });
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    FaeAdmin.init();
    
    // Move WordPress admin notices below the header
    FaeAdmin.moveNoticesBelowHeader();
    
    // Watch for dynamically added notices and move them too
    const noticeObserver = new MutationObserver(function(mutations) {
      FaeAdmin.moveNoticesBelowHeader();
    });
    
    // Observe the body for new notices
    if (document.body) {
      noticeObserver.observe(document.body, {
        childList: true,
        subtree: true
      });
    }
  });
  
  // Function to move notices below header
  FaeAdmin.moveNoticesBelowHeader = function() {
    const dashboard = $('.fae-cursor-dashboard');
    if (dashboard.length === 0) return;
    
    const header = dashboard.find('.fae-dashboard-header');
    if (header.length === 0) return;
    
    // Find all WordPress notices that are not FaeCursor notices
    const notices = $('.notice:not(.fae-notice), .update-nag, .error:not(.fae-notice), .updated:not(.fae-notice)');
    
    notices.each(function() {
      const $notice = $(this);
      // Only process if notice is not already positioned correctly
      const isAfterHeader = $notice.prevAll('.fae-dashboard-header').length > 0;
      const isInDashboard = $notice.closest('.fae-cursor-dashboard').length > 0;
      
      if (!isAfterHeader || !isInDashboard) {
        // Move notice to appear right after the header
        header.after($notice);
      }
    });
  };
})(jQuery);
