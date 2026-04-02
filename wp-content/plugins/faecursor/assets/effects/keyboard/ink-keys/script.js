/**
 * Ink Keys - Keyboard letter ink pop effect
 */
(function () {
  'use strict';

  if (typeof faeCursorSettings === 'undefined') return;

  // Convert hex to RGB for filter manipulation
  function hexToRgb(hex) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return { r, g, b };
  }

  const colorHex = faeCursorSettings.color || '#000000';
  const color = faeCursorSettings.color || '#000000';

  document.addEventListener('keydown', (e) => {
    if (!e.key.match(/^[a-zA-Z0-9]$/)) return;

    // Check scope settings
    const active = document.activeElement;
    const isTextbox = active && (
      active.tagName === 'INPUT' || 
      active.tagName === 'TEXTAREA' || 
      active.isContentEditable
    );

    // Security: Exclude password fields
    if (active && active.tagName === 'INPUT' && active.type === 'password') {
      return;
    }

    // Check scope settings
    const scopeType = faeCursorSettings && faeCursorSettings.scope ? faeCursorSettings.scope.scope_type : 'entire_website';
    
    // If CSS selector scoping is enabled, only trigger for typeable elements that match the selector
    if (scopeType === 'css_selector') {
      if (!isTextbox) {
        return;
      }
      if (typeof faeCursorElementMatchesSelector === 'function') {
        if (!faeCursorElementMatchesSelector(active)) {
          return;
        }
      } else {
        return;
      }
    } else if (typeof faeCursorShouldTrigger === 'function' && active) {
      const rect = active.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      if (!faeCursorShouldTrigger(centerX, centerY, active)) {
        return;
      }
    }

    const x = 100 + Math.random() * 300;
    const y = 150 + Math.random() * 200;

    const el = document.createElement('div');
    el.className = 'fae-ink-key';
    el.textContent = e.key;
    el.style.left = x + 'px';
    el.style.top = y + 'px';
    // For ink effect, we use filter to create the ink effect, but set color for fallback
    const rgb = hexToRgb(colorHex);
    el.style.color = `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 700);
  });
})();

