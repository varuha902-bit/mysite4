/**
 * FaeCursor Scope Handler
 * Handles CSS selector scoping for effects on the frontend
 */

(function() {
  'use strict';

  // Store scoped elements for CSS selector scoping
  let scopedElements = [];
  let isScoped = false;
  let cssSelectorMode = false; // Track if CSS selector mode is enabled
  let cssSelectorString = ''; // Store the CSS selector string for direct matching

  /**
   * Initialize scope handler when settings are available
   */
  function initScopeHandler() {
    // Check if faeCursorSettings exists (will be set by wp_localize_script)
    if (typeof faeCursorSettings === 'undefined' || !faeCursorSettings.scope) {
      return;
    }

    const scopeType = faeCursorSettings.scope.scope_type || 'entire_website';
    const cssSelector = faeCursorSettings.scope.scope_css_selector || '';

    // If CSS selector scoping is enabled, set up the scoped elements
    if (scopeType === 'css_selector' && cssSelector) {
      cssSelectorMode = true;
      isScoped = true; // Enable scoping immediately
      cssSelectorString = cssSelector; // Store the selector string
      setupCssSelectorScope(cssSelector);
    }
  }

  /**
   * Set up CSS selector scoping
   * 
   * @param {string} cssSelector - CSS selector string
   */
  function setupCssSelectorScope(cssSelector) {
    // Split selectors by comma and trim
    const selectors = cssSelector.split(',').map(function(s) {
      return s.trim();
    }).filter(function(s) {
      return s.length > 0;
    });

    if (selectors.length === 0) {
      return;
    }

    // Wait for DOM to be ready
    function findScopedElements() {
      scopedElements = [];
      
      selectors.forEach(function(selector) {
        try {
          const elements = document.querySelectorAll(selector);
          if (elements.length > 0) {
            scopedElements = scopedElements.concat(Array.from(elements));
          }
        } catch (e) {
          // Invalid selector, skip it
          console.warn('FaeCursor: Invalid CSS selector:', selector);
        }
      });

      // isScoped is already set to true when CSS selector mode is enabled
      // If no elements found, scopedElements will be empty and checks will return false
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', findScopedElements);
    } else {
      findScopedElements();
    }

    // Re-check on dynamic content changes (for AJAX-loaded content)
    if (typeof MutationObserver !== 'undefined') {
      const observer = new MutationObserver(function() {
        findScopedElements();
      });

      if (document.body) {
        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      }
    }
  }

  /**
   * Check if a point (x, y) is within any of the scoped elements
   * 
   * @param {number} x - X coordinate
   * @param {number} y - Y coordinate
   * @returns {boolean} True if point is within scoped area
   */
  function isPointInScope(x, y) {
    // If CSS selector mode is enabled but no elements found, block the effect
    if (cssSelectorMode && scopedElements.length === 0) {
      return false; // Block effect if selector doesn't match any elements
    }

    if (!isScoped || scopedElements.length === 0) {
      return true; // No scope restrictions
    }

    // Check if point is within any scoped element
    for (let i = 0; i < scopedElements.length; i++) {
      const rect = scopedElements[i].getBoundingClientRect();
      if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom) {
        return true;
      }
    }

    return false;
  }

  /**
   * Check if an element is within the scoped area
   * 
   * @param {HTMLElement} element - Element to check
   * @returns {boolean} True if element is within scoped area
   */
  window.faeCursorIsInScope = function(element) {
    // If CSS selector mode is enabled but no elements found, block the effect
    if (cssSelectorMode && scopedElements.length === 0) {
      return false; // Block effect if selector doesn't match any elements
    }

    if (!isScoped || scopedElements.length === 0) {
      return true; // No scope restrictions
    }

    // Check if element is within any of the scoped elements
    for (let i = 0; i < scopedElements.length; i++) {
      if (scopedElements[i].contains(element)) {
        return true;
      }
    }

    return false;
  };

  /**
   * Check if an element directly matches the CSS selector (for keyboard effects)
   * This is useful when you want to check if a specific input/textarea matches the selector
   * For keyboard effects, this specifically checks typeable elements (input, textarea, contentEditable)
   * 
   * @param {HTMLElement} element - Element to check (should be a typeable element)
   * @returns {boolean} True if element matches the CSS selector
   */
  window.faeCursorElementMatchesSelector = function(element) {
    if (!cssSelectorMode || !element) {
      return false;
    }

    // Must be a typeable element (input, textarea, or contentEditable)
    const isTypeable = element.tagName === 'INPUT' || 
                       element.tagName === 'TEXTAREA' || 
                       element.isContentEditable;
    
    if (!isTypeable) {
      return false;
    }

    // Security: Exclude password fields
    if (element.tagName === 'INPUT' && element.type === 'password') {
      return false; // Don't allow password fields for security
    }

    // If no CSS selector string, fall back to scoped elements check
    if (!cssSelectorString) {
      // Check if the element itself is one of the scoped elements
      for (let i = 0; i < scopedElements.length; i++) {
        if (scopedElements[i] === element) {
          return true;
        }
      }
      // Also check if element is within any scoped element (for nested inputs)
      return faeCursorIsInScope(element);
    }

    // Split selectors by comma and check each one
    const selectors = cssSelectorString.split(',').map(function(s) {
      return s.trim();
    }).filter(function(s) {
      return s.length > 0;
    });

    // Check if element directly matches any of the selectors
    for (let i = 0; i < selectors.length; i++) {
      try {
        // Check if element itself matches the selector
        if (element.matches && element.matches(selectors[i])) {
          return true;
        }
        // Also check if element is a descendant of an element matching the selector
        if (element.closest && element.closest(selectors[i])) {
          return true;
        }
      } catch (e) {
        // Invalid selector, skip it
        continue;
      }
    }

    // Fallback: Check if the element itself is one of the scoped elements
    for (let i = 0; i < scopedElements.length; i++) {
      if (scopedElements[i] === element) {
        return true;
      }
    }

    // Also check if element is within any scoped element (for nested inputs)
    return faeCursorIsInScope(element);
  };

  /**
   * Check if mouse position is in scope (for cursor effects)
   * This is the main function effects should use
   * 
   * @param {number} x - Mouse X coordinate (optional)
   * @param {number} y - Mouse Y coordinate (optional)
   * @param {HTMLElement} element - Element to check (optional)
   * @returns {boolean} True if mouse is within scoped area
   */
  window.faeCursorShouldTrigger = function(x, y, element) {
    // If CSS selector mode is enabled but no elements found, block the effect
    if (cssSelectorMode && scopedElements.length === 0) {
      return false; // Block effect if selector doesn't match any elements
    }

    if (!isScoped) {
      return true; // No scope restrictions
    }

    // Check element first if provided
    if (element && faeCursorIsInScope(element)) {
      return true;
    }

    // Check point position if provided
    if (x !== undefined && y !== undefined && isPointInScope(x, y)) {
      return true;
    }

    // If neither provided, return false for scoped mode
    return false;
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScopeHandler);
  } else {
    setTimeout(initScopeHandler, 100);
  }

  // Also try to initialize when faeCursorSettings becomes available
  let initAttempts = 0;
  const checkSettings = setInterval(function() {
    initAttempts++;
    if (typeof faeCursorSettings !== 'undefined') {
      clearInterval(checkSettings);
      initScopeHandler();
    } else if (initAttempts > 50) {
      clearInterval(checkSettings);
    }
  }, 100);

})();
