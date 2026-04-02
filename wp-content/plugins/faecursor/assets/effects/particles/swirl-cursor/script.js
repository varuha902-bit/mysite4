/**
 * Swirl Cursor Effect
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const canvas = document.createElement("canvas");
  canvas.id = "fae-swirl-cursor";
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
  
  let mouse = { x: null, y: null, radius: 150 };

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
  const color = faeCursorSettings.color || '#00ffcc';

  class Particle {
    constructor() {
      this.x = Math.random() * canvas.width;
      this.y = Math.random() * canvas.height;
      this.baseX = this.x;
      this.baseY = this.y;
      this.size = 2;
      this.angle = Math.random() * Math.PI * 2;
    }

    draw() {
      ctx.fillStyle = color;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fill();
    }

    update() {
      if (!mouse.x) {
        this.x += (this.baseX - this.x) * 0.03;
        this.y += (this.baseY - this.y) * 0.03;
        return;
      }
      
      let dx = mouse.x - this.x;
      let dy = mouse.y - this.y;
      let distance = Math.sqrt(dx * dx + dy * dy);

      if (distance < mouse.radius) {
        this.x += dx * 0.05;
        this.y += dy * 0.05;
        this.angle += 0.1;
        this.x += Math.cos(this.angle) * 2;
        this.y += Math.sin(this.angle) * 2;
      } else {
        this.x += (this.baseX - this.x) * 0.03;
        this.y += (this.baseY - this.y) * 0.03;
      }
    }
  }

  const particles = [];
  for (let i = 0; i < 150; i++) particles.push(new Particle());

  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    particles.forEach(p => {
      p.update();
      p.draw();
    });

    requestAnimationFrame(animate);
  }

  animate();

  window.addEventListener("resize", () => {
    canvas.width = innerWidth;
    canvas.height = innerHeight;
  });
})();

