/**
 * Magic Trail Effect - Glowing particle trail with soft fade
 * Matches the FaeCursor landing page effect
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const color = faeCursorSettings.color || "#c084fc";
  const speed = faeCursorSettings.speed || "normal";
  const multiColor = faeCursorSettings.multiColor === true || 
                     faeCursorSettings.multiColor === "1" || 
                     faeCursorSettings.multiColor === 1;

  // Multi-color palette - the magic colors from landing page
  const multiColors = ["#c084fc", "#e879f9", "#a855f7", "#6366f1", "#818cf8", "#f472b6", "#fbbf24"];

  // Speed configuration
  const speedConfig = {
    slow: { 
      spawnDistance: 8,      // Spawn every 8px of movement
      decay: 0.008,          // Slower fade
      shrink: 0.985          // Slower shrink
    },
    normal: { 
      spawnDistance: 5,      // Spawn every 5px of movement  
      decay: 0.015,          // Normal fade
      shrink: 0.97           // Normal shrink
    },
    fast: { 
      spawnDistance: 3,      // Spawn every 3px of movement
      decay: 0.025,          // Faster fade
      shrink: 0.95           // Faster shrink
    }
  };

  const config = speedConfig[speed] || speedConfig.normal;

  // Create canvas
  const canvas = document.createElement("canvas");
  canvas.id = "fae-magic-trail-canvas";
  canvas.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 9999;
  `;
  document.body.appendChild(canvas);

  const ctx = canvas.getContext("2d");

  // Set canvas size
  function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener("resize", resize);

  // Particle system
  const particles = [];
  let mouse = { x: -100, y: -100 };
  let lastMouse = { x: -100, y: -100 };
  let lastTarget = null;

  // Particle class - matching landing page feel
  class Particle {
    constructor(x, y) {
      this.x = x;
      this.y = y;
      this.size = Math.random() * 8 + 4;  // 4-12px like landing page
      this.speedX = (Math.random() - 0.5) * 2;  // Spread movement
      this.speedY = (Math.random() - 0.5) * 2;
      this.color = multiColor
        ? multiColors[Math.floor(Math.random() * multiColors.length)]
        : color;
      this.life = 1;
      this.decay = config.decay + Math.random() * 0.01;
    }

    update() {
      this.x += this.speedX;
      this.y += this.speedY;
      this.life -= this.decay;
      this.size *= config.shrink;  // Smooth shrinking like landing page
    }

    draw() {
      if (this.life <= 0 || this.size <= 0.5) return;

      ctx.save();
      ctx.globalAlpha = this.life;
      ctx.fillStyle = this.color;
      ctx.shadowBlur = 15;  // Strong glow like landing page
      ctx.shadowColor = this.color;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }
  }

  // Mouse tracking
  document.addEventListener("mousemove", (e) => {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
    lastTarget = e.target;
  });

  // Animation loop
  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Calculate distance moved
    const dx = mouse.x - lastMouse.x;
    const dy = mouse.y - lastMouse.y;
    const dist = Math.sqrt(dx * dx + dy * dy);

    // Always create particle when mouse moves enough (like landing page)
    if (dist > config.spawnDistance) {
      // Check scope before creating particles
      if (
        typeof faeCursorShouldTrigger === "function" &&
        !faeCursorShouldTrigger(mouse.x, mouse.y, lastTarget)
      ) {
        // Outside scope, just update lastMouse
        lastMouse.x = mouse.x;
        lastMouse.y = mouse.y;
      } else {
        // Create particle - always spawn, no random chance
        particles.push(new Particle(mouse.x, mouse.y));
        lastMouse.x = mouse.x;
        lastMouse.y = mouse.y;
      }
    }

    // Update and draw particles
    for (let i = particles.length - 1; i >= 0; i--) {
      particles[i].update();
      particles[i].draw();

      // Remove dead particles
      if (particles[i].life <= 0 || particles[i].size <= 0.5) {
        particles.splice(i, 1);
      }
    }

    // Limit particle count for performance
    while (particles.length > 100) {
      particles.shift();
    }

    requestAnimationFrame(animate);
  }

  animate();
})();
