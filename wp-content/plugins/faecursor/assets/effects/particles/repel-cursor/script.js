/**
 * Repel Cursor Effect
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const canvas = document.createElement("canvas");
  canvas.id = "fae-repel-cursor";
  canvas.className = 'fae-particle-canvas';
  document.body.appendChild(canvas);

  const ctx = canvas.getContext("2d");

  canvas.width = innerWidth;
  canvas.height = innerHeight;

  // Get interactive cursor setting - only enable if explicitly true
  // Check for boolean true, string '1', or number 1
  const interactiveCursor = faeCursorSettings.interactiveCursor === true || 
                            faeCursorSettings.interactiveCursor === '1' || 
                            faeCursorSettings.interactiveCursor === 1;
  
  let mouse = { x: null, y: null, radius: 120 };

  if (interactiveCursor) {
    window.addEventListener("mousemove", e => {
      if (typeof faeCursorShouldTrigger === 'function' && 
          !faeCursorShouldTrigger(e.clientX, e.clientY, e.target)) {
        mouse.x = null;
        mouse.y = null;
        return;
      }
      mouse.x = e.clientX;
      mouse.y = e.clientY;
    });

    window.addEventListener("mouseout", () => {
      mouse.x = null;
      mouse.y = null;
    });
  }

  // Get color from settings
  const color = faeCursorSettings.color || '#33ccff';
  
  // Helper function to convert hex to rgba
  function hexToRgba(hex, alpha) {
    if (hex.length === 4) {
      hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
    }
    const r = parseInt(hex.slice(1, 3), 16) || 51;
    const g = parseInt(hex.slice(3, 5), 16) || 204;
    const b = parseInt(hex.slice(5, 7), 16) || 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  // Calculate responsive values only for small preview screens (admin dashboard)
  // Keep normal values for full screen
  function isSmallPreview() {
    const screenArea = canvas.width * canvas.height;
    // Only adjust for small preview areas (admin dashboard preview box)
    // Threshold: ~700x700 or smaller (490000 pixels)
    return screenArea < 500000;
  }

  function getParticleCount() {
    // Only adjust for small preview areas
    if (isSmallPreview()) {
      return 40; // Fewer particles for small preview
    }
    // Normal screen - keep current behavior
    return 120;
  }

  function getMaxConnectionDistance() {
    // Only adjust for small preview areas
    if (isSmallPreview()) {
      return 6000; // Shorter connection distance for small preview
    }
    // Normal screen - keep current behavior
    return 14000;
  }

  class Particle {
    constructor() {
      this.x = Math.random() * canvas.width;
      this.y = Math.random() * canvas.height;
      this.size = 2 + Math.random() * 2;
      this.baseX = this.x;
      this.baseY = this.y;
      this.speed = 2;
    }

    draw() {
      ctx.fillStyle = color;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fill();
    }

    update() {
      if (!mouse.x) {
        let dx = this.x - this.baseX;
        let dy = this.y - this.baseY;
        this.x -= dx * 0.05;
        this.y -= dy * 0.05;
        return;
      }
      
      let dx = mouse.x - this.x;
      let dy = mouse.y - this.y;
      let distance = Math.sqrt(dx * dx + dy * dy);
      let forceDirectionX = dx / distance;
      let forceDirectionY = dy / distance;
      let maxDistance = mouse.radius;
      let force = (maxDistance - distance) / maxDistance;

      if (distance < mouse.radius) {
        this.x -= forceDirectionX * force * 12;
        this.y -= forceDirectionY * force * 12;
      } else {
        let dx = this.x - this.baseX;
        let dy = this.y - this.baseY;
        this.x -= dx * 0.05;
        this.y -= dy * 0.05;
      }
    }
  }

  // Initialize particles with responsive count
  const particles = [];
  function initParticles() {
    particles.length = 0;
    const particleCount = getParticleCount();
    for (let i = 0; i < particleCount; i++) {
      particles.push(new Particle());
    }
  }
  initParticles();

  function connectLines() {
    const maxDistance = getMaxConnectionDistance();
    for (let a = 0; a < particles.length; a++) {
      for (let b = a; b < particles.length; b++) {
        let dx = particles[a].x - particles[b].x;
        let dy = particles[a].y - particles[b].y;
        let distance = dx * dx + dy * dy;

        if (distance < maxDistance) {
          ctx.strokeStyle = hexToRgba(color, 0.3);
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(particles[a].x, particles[a].y);
          ctx.lineTo(particles[b].x, particles[b].y);
          ctx.stroke();
        }
      }
    }
  }

  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
      p.draw();
      p.update();
    });
    connectLines();
    requestAnimationFrame(animate);
  }

  animate();

  window.addEventListener("resize", () => {
    canvas.width = innerWidth;
    canvas.height = innerHeight;
    
    // Recreate particles with appropriate count for new size
    // This ensures small previews get fewer particles, full screen keeps normal count
    initParticles();
    
    // Reposition existing particles to fit new canvas size
    particles.forEach(p => {
      p.x = Math.random() * canvas.width;
      p.y = Math.random() * canvas.height;
      p.baseX = p.x;
      p.baseY = p.y;
    });
  });
})();

