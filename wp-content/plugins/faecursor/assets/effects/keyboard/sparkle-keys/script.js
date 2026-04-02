/**
 * Sparkle Keys - Keyboard Letter Effects with Fireworks & Physics
 * Adapted for FaeCursor WordPress Plugin
 */
(function () {
  'use strict';

  // Wait for settings to be available
  if (typeof faeCursorSettings === 'undefined') {
    return;
  }

  // Get settings
  const color = (faeCursorSettings.color || '#667eea').toLowerCase();
  // Multi-color is disabled (Pro feature) - always use single color
  const multiColor = false;

  // ----- Config -----
  const CONFIG = {
    fireworksEnabled: true,
    fallEnabled: true,
    particleCount: 22,
    particleSpeed: [60, 220],
    particleSize: [2, 6],
    particleLifespan: 900,
    gravity: 1600,
    letterBounce: 0.55,
    letterFriction: 0.995,
    letterLifetime: 3500,
    letterFadeDuration: 800,
    defaultFontSize: 28
  };

  // Multi-color palette removed (Pro feature)

  // ----- DOM refs -----
  const container = document.createElement('div');
  container.className = 'fae-keyboard-effect-wrap';
  container.id = 'fae-keyboard-effects';
  document.body.appendChild(container);

  const canvas = document.createElement('canvas');
  canvas.className = 'fae-keyboard-canvas';
  canvas.id = 'fae-keyboard-canvas';
  container.appendChild(canvas);
  const ctx = canvas.getContext('2d', { alpha: true });

  // ----- Canvas sizing -----
  function resizeCanvas() {
    canvas.width = window.innerWidth * window.devicePixelRatio;
    canvas.height = window.innerHeight * window.devicePixelRatio;
    canvas.style.width = window.innerWidth + 'px';
    canvas.style.height = window.innerHeight + 'px';
    ctx.setTransform(window.devicePixelRatio, 0, 0, window.devicePixelRatio, 0, 0);
  }
  window.addEventListener('resize', resizeCanvas, { passive: true });
  resizeCanvas();

  // ----- Particle system for fireworks -----
  const particles = [];

  function spawnFireworks(x, y, baseColor) {
    const count = CONFIG.particleCount;
    for (let i = 0; i < count; i++) {
      const angle = Math.random() * Math.PI * 2;
      const speed = CONFIG.particleSpeed[0] + Math.random() * (CONFIG.particleSpeed[1] - CONFIG.particleSpeed[0]);
      const vx = Math.cos(angle) * speed;
      const vy = Math.sin(angle) * speed;
      const size = CONFIG.particleSize[0] + Math.random() * (CONFIG.particleSize[1] - CONFIG.particleSize[0]);
      const life = CONFIG.particleLifespan * (0.8 + Math.random() * 0.6);
      
      // Always use single color mode (multi-color is Pro feature)
      const particleColor = shadeColor(baseColor, (Math.random() * 30) - 15);
      
      particles.push({
        x, y, vx, vy, size, life, age: 0, color: particleColor, alpha: 1
      });
    }
  }

  // Utility to lighten/darken color hex slightly
  function shadeColor(hex, percent) {
    const f = parseInt(hex.slice(1), 16);
    const t = percent < 0 ? 0 : 255;
    const p = Math.abs(percent) / 100;
    const R = Math.round((t - (f >> 16)) * p) + (f >> 16);
    const G = Math.round((t - ((f >> 8) & 0x00FF)) * p) + ((f >> 8) & 0x00FF);
    const B = Math.round((t - (f & 0x0000FF)) * p) + (f & 0x0000FF);
    return '#' + (0x1000000 + (R << 16) + (G << 8) + B).toString(16).slice(1);
  }

  // Convert hex to RGB
  function hexToRgb(hex) {
    let normalizedHex = hex;
    if (hex.length === 4) {
      normalizedHex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
    }
    const r = parseInt(normalizedHex.slice(1, 3), 16);
    const g = parseInt(normalizedHex.slice(3, 5), 16);
    const b = parseInt(normalizedHex.slice(5, 7), 16);
    return [r, g, b];
  }

  // Get color - always use single color (multi-color is Pro feature)
  function getColor() {
    return color;
  }

  // ----- Falling letters (DOM elements) -----
  const falling = [];

  function spawnFallingLetter(ch, x) {
    const el = document.createElement('div');
    el.className = 'fae-falling-letter';
    el.innerText = ch;
    const size = CONFIG.defaultFontSize + Math.round(Math.random() * 10 - 4);
    el.style.fontSize = size + 'px';
    el.style.left = '0px';
    el.style.top = '0px';
    el.style.opacity = '1';
    
    // Always use single color mode with slight variation (multi-color is Pro feature)
    const [r, g, b] = hexToRgb(color);
    const variation = 30;
    const newR = Math.max(0, Math.min(255, r + (Math.random() - 0.5) * variation));
    const newG = Math.max(0, Math.min(255, g + (Math.random() - 0.5) * variation));
    const newB = Math.max(0, Math.min(255, b + (Math.random() - 0.5) * variation));
    const letterColor = `rgb(${Math.round(newR)}, ${Math.round(newG)}, ${Math.round(newB)})`;
    
    el.style.color = letterColor;
    container.appendChild(el);
    const startX = Math.max(8, Math.min(window.innerWidth - 8, x || (window.innerWidth * 0.5 + (Math.random() - 0.5) * 200)));
    const startY = -20 - Math.random() * 20;
    const vx = (Math.random() - 0.5) * 120;
    const vy = 80 + Math.random() * 200;
    const angularV = (Math.random() - 0.5) * 6;
    const createdAt = performance.now();
    falling.push({ el, x: startX, y: startY, vx, vy, angle: 0, angularV, createdAt, size, removed: false });
    el.style.transform = `translate(${startX}px, ${startY}px) rotate(0deg)`;
  }

  // ----- Keyboard listener -----
  window.addEventListener('keydown', (ev) => {
    const k = ev.key;
    // Only trigger on letters and numbers (a-z, A-Z, 0-9) - exclude all special keys
    if (!k || !k.match(/^[a-zA-Z0-9]$/)) return;

    const active = document.activeElement;
    
    // First check: Must be typing in a textbox/input/textarea
    const isTextbox = active && (
      active.tagName === 'INPUT' || 
      active.tagName === 'TEXTAREA' || 
      active.isContentEditable
    );

    // Security: Exclude password fields
    if (active && active.tagName === 'INPUT' && active.type === 'password') {
      return; // Don't trigger effects on password fields for security
    }

    // Check scope settings
    const scopeType = faeCursorSettings && faeCursorSettings.scope ? faeCursorSettings.scope.scope_type : 'entire_website';
    
    // If CSS selector scoping is enabled, only trigger for typeable elements that match the selector
    if (scopeType === 'css_selector') {
      // CSS selector mode: only trigger when typing in input/textarea/contentEditable that matches the selector
      if (!isTextbox) {
        return; // Must be a typeable element
      }
      if (typeof faeCursorElementMatchesSelector === 'function') {
        if (!faeCursorElementMatchesSelector(active)) {
          return; // Don't create effects if textbox doesn't match CSS selector
        }
      } else {
        return; // Function not available, don't trigger
      }
    } else if (typeof faeCursorShouldTrigger === 'function' && active) {
      // For other scope modes (entire_website, specific_pages), check if active element is in scope
      const rect = active.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      if (!faeCursorShouldTrigger(centerX, centerY, active)) {
        return; // Don't create effects if outside scope
      }
    }

    let x = null;
    if (isTextbox) {
      const rect = active.getBoundingClientRect();
      x = rect.left + rect.width / 2 + (Math.random() - 0.5) * Math.min(rect.width / 4, 80);
    } else {
      x = window.innerWidth / 2 + (Math.random() - 0.5) * 500;
    }

    // k is guaranteed to be a single letter or number at this point
    const letterChar = k;

    if (CONFIG.fireworksEnabled) {
      const y = 80 + Math.random() * 160;
      const fireworksColor = getColor();
      spawnFireworks(x, y, fireworksColor);
      const temp = document.createElement('div');
      temp.className = 'fae-falling-letter';
      temp.innerText = letterChar;
      temp.style.fontSize = (CONFIG.defaultFontSize + 6) + 'px';
      temp.style.left = '0px';
      temp.style.top = '0px';
      temp.style.color = '#fff';
      temp.style.opacity = '1';
      temp.style.pointerEvents = 'none';
      container.appendChild(temp);
      temp.style.transform = `translate(${x}px, ${y - 10}px) rotate(${(Math.random() - 0.5) * 30}deg)`;
      setTimeout(() => temp.remove(), 650);
    }

    if (CONFIG.fallEnabled) {
      spawnFallingLetter(letterChar, x);
    }
  });

  // ----- Animation loop -----
  let last = performance.now();

  function frame(now) {
    const dt = Math.min(40, now - last);
    last = now;
    updateParticles(dt);
    updateFalling(dt);
    renderParticles();
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);

  function updateParticles(ms) {
    const sec = ms / 1000;
    for (let i = particles.length - 1; i >= 0; i--) {
      const p = particles[i];
      p.vy += 600 * sec;
      p.x += p.vx * sec;
      p.y += p.vy * sec;
      p.age += ms;
      p.alpha = 1 - (p.age / p.life);
      if (p.age >= p.life) particles.splice(i, 1);
    }
  }

  function renderParticles() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    for (let p of particles) {
      ctx.globalAlpha = Math.max(0, p.alpha);
      ctx.beginPath();
      ctx.fillStyle = p.color;
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.globalAlpha = 1;
  }

  function updateFalling(ms) {
    const now = performance.now();
    for (let i = falling.length - 1; i >= 0; i--) {
      const obj = falling[i];
      if (obj.removed) continue;
      const dt = ms / 1000;
      obj.vy += CONFIG.gravity * dt;
      obj.x += obj.vx * dt;
      obj.y += obj.vy * dt;
      obj.vx *= CONFIG.letterFriction;
      obj.angle += obj.angularV * dt;

      const el = obj.el;
      const rect = el.getBoundingClientRect();
      const height = rect.height || obj.size;
      const bottom = obj.y + height;
      const ground = window.innerHeight - 8;
      if (bottom >= ground) {
        obj.y = ground - height;
        obj.vy = -obj.vy * CONFIG.letterBounce;
        obj.vx *= 0.85;
        if (Math.abs(obj.vy) < 40 && Math.abs(obj.vx) < 10) {
          obj.createdAt -= CONFIG.letterLifetime;
        }
      }

      const age = now - obj.createdAt;
      if (age > CONFIG.letterLifetime) {
        const fadeAge = age - CONFIG.letterLifetime;
        const alpha = Math.max(0, 1 - fadeAge / CONFIG.letterFadeDuration);
        el.style.opacity = alpha;
        if (alpha <= 0.02) {
          obj.removed = true;
          el.remove();
          falling.splice(i, 1);
          continue;
        }
      }

      el.style.transform = `translate(${obj.x}px, ${obj.y}px) rotate(${obj.angle}rad)`;
    }
  }

  // Cleanup to avoid DOM overload
  setInterval(() => {
    const MAX = 120;
    if (falling.length > MAX) {
      const removeCount = falling.length - MAX;
      for (let i = 0; i < removeCount; i++) {
        const it = falling.shift();
        if (it && it.el) it.el.remove();
      }
    }
  }, 1000);

})();

