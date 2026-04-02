/**
 * Bubble Keys - Keyboard letter bubbles rising from bottom
 */
(function () {
  'use strict';

  if (typeof faeCursorSettings === 'undefined') return;

  // Convert hex color to rgba with transparency
  function hexToRgba(hex, alpha) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  const colorHex = faeCursorSettings.color || '#0096ff';
  const color = hexToRgba(colorHex, 0.3);

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

    const x = 200 + Math.random() * (window.innerWidth - 400);
    const y = window.innerHeight - 80;

    const b = document.createElement('div');
    b.className = 'fae-bubble-key';
    b.textContent = e.key;
    b.style.left = x + 'px';
    b.style.top = y + 'px';
    b.style.background = color;
    document.body.appendChild(b);
    setTimeout(() => b.remove(), 1400);
  });
})();

