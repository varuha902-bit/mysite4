/**
 * Matrix Keys - Keyboard letter matrix rain effect
 */
(function () {
  'use strict';

  if (typeof faeCursorSettings === 'undefined') return;

  const color = faeCursorSettings.color || '#00ff00';

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

    const x = Math.random() * window.innerWidth;

    const el = document.createElement('div');
    el.className = 'fae-matrix-key';
    el.textContent = e.key;
    el.style.left = x + 'px';
    el.style.top = '0px';
    el.style.color = color;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 1000);
  });
})();

