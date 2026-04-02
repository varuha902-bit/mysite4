/**
 * Genuine Effect - Complex cursor with particle trail and ripple
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const color = faeCursorSettings.color || "#7c3aed";
  const speed = faeCursorSettings.speed || "normal";

  // Speed multipliers for particle spread speed
  const speedMultipliers = {
    slow: 0.6,     // Slower spread
    normal: 1.0,    // Normal spread
    fast: 1.5       // Faster spread
  };
  const spreadSpeedMultiplier = speedMultipliers[speed] || speedMultipliers.normal;

  function hexToHsl(hex) {
    const r = parseInt(hex.slice(1, 3), 16) / 255;
    const g = parseInt(hex.slice(3, 5), 16) / 255;
    const b = parseInt(hex.slice(5, 7), 16) / 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;
    
    if (max === min) {
      h = s = 0; // Achromatic (grayscale)
    } else {
      const d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
        case g: h = ((b - r) / d + 2) / 6; break;
        case b: h = ((r - g) / d + 4) / 6; break;
      }
    }
    
    return {
      h: h * 360,
      s: s * 100,
      l: l * 100
    };
  }

  const baseHsl = hexToHsl(color);
  
  // For grayscale colors (white, black, gray), use the original hex color directly
  const isGrayscale = baseHsl.s === 0;

  // Helper function to convert hex to RGB
  function hexToRgb(hex) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return { r, g, b };
  }

  const canvas = document.createElement("canvas");
  canvas.id = "fae-genuine-trail";
  canvas.style.cssText =
    "position:fixed;inset:0;pointer-events:none;z-index:998;";
  document.body.appendChild(canvas);

  const cursor = document.createElement("div");
  cursor.className = "fae-genuine-cursor";
  const core = document.createElement("div");
  core.className = "fae-genuine-core";
  cursor.appendChild(core);
  
  // Set cursor colors dynamically based on selected color
  if (isGrayscale) {
    // For grayscale colors, use the original hex color
    cursor.style.background = `radial-gradient(circle at 30% 30%, ${color}33, transparent 70%)`;
    core.style.background = `radial-gradient(circle at 30% 30%, ${color}, ${color}A6 10%, ${color}00 40%)`;
    core.style.boxShadow = `0 6px 24px rgba(0, 0, 0, 0.25), 0 0 18px ${color}59`;
  } else {
    // For colored cursor, convert to RGB for gradient
    const rgb = hexToRgb(color);
    cursor.style.background = `radial-gradient(circle at 30% 30%, rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.1), transparent 70%)`;
    core.style.background = `radial-gradient(circle at 30% 30%, rgb(${rgb.r}, ${rgb.g}, ${rgb.b}), rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.65) 10%, rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0) 40%)`;
    core.style.boxShadow = `0 6px 24px rgba(0, 0, 0, 0.25), 0 0 18px rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.35)`;
  }
  
  cursor.style.cssText += `
    position: fixed;
    top: 0;
    left: 0;
    transform: translate(-50%, -50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    z-index: 999;
    pointer-events: none;
    mix-blend-mode: screen;
    display: flex;
    align-items: center;
    justify-content: center;
    will-change: transform, opacity, filter;
    transition: width 0.18s ease, height 0.18s ease, background-color 0.18s ease, transform 0.06s ease;
  `;
  document.body.appendChild(cursor);

  const ripple = document.createElement("div");
  ripple.className = "fae-genuine-ripple";
  ripple.style.cssText = `
    position: fixed;
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(0);
    opacity: 0;
    pointer-events: none;
    z-index: 997;
  `;
  document.body.appendChild(ripple);

  const ctx = canvas.getContext("2d", { alpha: true });
  let W = (canvas.width = innerWidth);
  let H = (canvas.height = innerHeight);
  window.addEventListener("resize", () => {
    W = canvas.width = innerWidth;
    H = canvas.height = innerHeight;
  });

  const particles = [];
  const MAX_PARTICLES = 80;

  function rand(min, max) {
    return Math.random() * (max - min) + min;
  }

  class Particle {
    constructor(x, y, vx, vy, r, life, colorValue, isGrayscale) {
      this.x = x;
      this.y = y;
      this.vx = vx;
      this.vy = vy;
      this.r = r;
      this.life = life;
      this.maxLife = life;
      this.colorValue = colorValue; // Can be hue (number) or hex color (string)
      this.isGrayscale = isGrayscale;
    }
    update(dt) {
      this.x += this.vx * dt;
      this.y += this.vy * dt;
      this.vy += 30 * dt;
      this.life -= dt;
    }
    draw(ctx) {
      const t = Math.max(this.life, 0) / this.maxLife;
      ctx.beginPath();
      ctx.arc(this.x, this.y, Math.max(0.1, this.r * t), 0, Math.PI * 2);
      
      if (this.isGrayscale) {
        // For grayscale colors, use the original hex color with opacity
        const opacity = Math.min(0.9, t * 0.9);
        ctx.fillStyle = this.colorValue; // Use stored hex color
        ctx.globalAlpha = opacity;
        ctx.fill();
        ctx.globalAlpha = 1.0; // Reset alpha
      } else {
        // For colored particles, use HSL with actual saturation and lightness
        const hue = this.colorValue; // This is the hue value
        ctx.fillStyle = `hsla(${hue}, ${baseHsl.s}%, ${baseHsl.l}%, ${Math.min(0.9, t * 0.9)})`;
        ctx.fill();
      }
    }
  }

  const pos = { x: W / 2, y: H / 2 };
  const target = { x: W / 2, y: H / 2 };

  window.addEventListener(
    "pointermove",
    (e) => {
      // Check if mouse is in scope
      const inScope = typeof faeCursorShouldTrigger === 'function' 
        ? faeCursorShouldTrigger(e.clientX, e.clientY, e.target)
        : true;

      // Hide/show cursor based on scope
      if (cursor) {
        cursor.style.display = inScope ? 'flex' : 'none';
      }

      if (!inScope) {
        target.x = e.clientX;
        target.y = e.clientY;
        return;
      }

      target.x = e.clientX;
      target.y = e.clientY;
      spawnParticles(
        e.clientX,
        e.clientY,
        Math.min(6, Math.hypot(e.movementX || 0, e.movementY || 0) + 2)
      );
    },
    { passive: true }
  );

  function checkHoverTargets(x, y) {
    const el = document.elementFromPoint(x, y);
    return (
      el &&
      (el.tagName === "A" ||
        el.tagName === "BUTTON" ||
        el.onclick ||
        el.hasAttribute("role"))
    );
  }

  let lastTime = performance.now();
  function frame(now) {
    const dt = Math.min(0.036, (now - lastTime) / 1000);
    lastTime = now;

    pos.x += (target.x - pos.x) * Math.min(1, 18 * dt);
    pos.y += (target.y - pos.y) * Math.min(1, 18 * dt);
    cursor.style.transform = `translate(${pos.x}px, ${pos.y}px) translate(-50%,-50%)`;

    if (checkHoverTargets(pos.x, pos.y)) cursor.classList.add("hover");
    else cursor.classList.remove("hover");

    ctx.clearRect(0, 0, W, H);
    // Removed black overlay - just particles

    for (let i = particles.length - 1; i >= 0; i--) {
      const p = particles[i];
      p.update(dt);
      p.draw(ctx);
      if (p.life <= 0) particles.splice(i, 1);
    }

    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);

  function spawnParticles(x, y, count) {
    for (let i = 0; i < count; i++) {
      if (particles.length > MAX_PARTICLES) break;
      const ang = rand(0, Math.PI * 2);
      const baseSpeed = rand(30, 160);
      const speed = baseSpeed * spreadSpeedMultiplier;
      const vx = Math.cos(ang) * speed;
      const vy = Math.sin(ang) * speed * 0.4 - rand(10, 40);
      const r = rand(1.6, 4.6);
      const life = rand(0.45, 1.6);
      
      let colorValue;
      if (isGrayscale) {
        // For grayscale, use the original hex color
        colorValue = color;
      } else {
        // For colored particles, vary the hue slightly for visual interest
        const hueVariation = rand(-40, 40);
        colorValue = (baseHsl.h + hueVariation + 360) % 360; // Keep hue in 0-360 range
      }
      
      particles.push(
        new Particle(x + rand(-6, 6), y + rand(-6, 6), vx, vy, r, life, colorValue, isGrayscale)
      );
    }
  }

  window.addEventListener("pointerdown", (e) => {
    // Check if mouse is in scope before creating ripple and particles
    if (typeof faeCursorShouldTrigger === 'function' && 
        !faeCursorShouldTrigger(e.clientX, e.clientY, e.target)) {
      return; // Don't create effects if outside scope
    }
    
    const x = e.clientX,
      y = e.clientY;
    ripple.style.left = x + "px";
    ripple.style.top = y + "px";
    ripple.style.width = "16px";
    ripple.style.height = "16px";
    
    // Use selected color for ripple effect
    if (isGrayscale) {
      // For grayscale colors, use the original hex color
      const rgb = hexToRgb(color);
      ripple.style.background = `radial-gradient(circle, ${color}E6, ${color}1A 30%)`;
    } else {
      // For colored ripple, convert to RGB
      const rgb = hexToRgb(color);
      ripple.style.background = `radial-gradient(circle, rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.9), rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.1) 30%)`;
    }
    
    ripple.style.opacity = "0.95";
    ripple.style.transform = "translate(-50%,-50%) scale(0)";
    ripple.animate(
      [
        { transform: "translate(-50%,-50%) scale(0)", opacity: 0.95 },
        { transform: "translate(-50%,-50%) scale(10)", opacity: 0 },
      ],
      { duration: 520, easing: "cubic-bezier(.2,.8,.2,1)" }
    );
    spawnParticles(x, y, 12);
  });
})();

