/**
 * Snowfall Effect
 * Falling snowflakes particle effect
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const canvas = document.createElement('canvas');
  canvas.id = 'fae-snowfall';
  canvas.className = 'fae-particle-canvas';
  document.body.appendChild(canvas);
  
  const ctx = canvas.getContext('2d');
  function resize() {
    canvas.width = window.innerWidth || innerWidth;
    canvas.height = window.innerHeight || innerHeight;
  }
  window.addEventListener('resize', resize);
  resize();

  // Get settings
  const color = faeCursorSettings.color || '#ffffff';
  const speedSetting = faeCursorSettings.speed || 'normal';
  
  // Speed multipliers
  const speedMultipliers = {
    'slow': 0.6,
    'normal': 1.0,
    'fast': 1.5
  };
  const speedMultiplier = speedMultipliers[speedSetting] || 1.0;

  // Parse color to RGBA
  function hexToRgba(hex, alpha) {
    // Handle 3-digit hex colors
    if (hex.length === 4) {
      hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
    }
    const r = parseInt(hex.slice(1, 3), 16) || 255;
    const g = parseInt(hex.slice(3, 5), 16) || 255;
    const b = parseInt(hex.slice(5, 7), 16) || 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  // Snowflake class
  class Snowflake {
    constructor() {
      this.reset();
      // Start at random position
      this.y = Math.random() * canvas.height;
    }

    reset() {
      this.x = Math.random() * canvas.width;
      this.y = -10;
      this.size = 2 + Math.random() * 4;
      this.speed = (0.5 + Math.random() * 1.5) * speedMultiplier;
      this.rotation = Math.random() * Math.PI * 2;
      this.rotationSpeed = (Math.random() - 0.5) * 0.1 * speedMultiplier;
      this.wobble = Math.random() * Math.PI * 2;
      this.wobbleSpeed = (Math.random() - 0.5) * 0.02;
      this.opacity = 0.6 + Math.random() * 0.4;
    }

    update() {
      // Move down
      this.y += this.speed;
      
      // Wobble horizontally
      this.x += Math.sin(this.wobble) * 0.5;
      this.wobble += this.wobbleSpeed;
      
      // Rotate
      this.rotation += this.rotationSpeed;

      // Wrap horizontally if off screen
      if (this.x < -10) {
        this.x = canvas.width + 10;
      } else if (this.x > canvas.width + 10) {
        this.x = -10;
      }

      // Reset if off screen bottom
      if (this.y > canvas.height + 10) {
        this.reset();
      }
    }

    draw() {
      ctx.save();
      ctx.translate(this.x, this.y);
      ctx.rotate(this.rotation);
      
      const rgba = hexToRgba(color, this.opacity);
      ctx.strokeStyle = rgba;
      ctx.fillStyle = rgba;
      ctx.lineWidth = 1.5;

      // Draw snowflake shape (6-pointed with branches)
      const size = this.size;
      ctx.beginPath();
      
      // Draw 6 main branches
      for (let i = 0; i < 6; i++) {
        const angle = (Math.PI / 3) * i;
        const cos = Math.cos(angle);
        const sin = Math.sin(angle);
        
        // Main branch
        ctx.moveTo(0, 0);
        ctx.lineTo(cos * size, sin * size);
        
        // Side branches
        const branchAngle1 = angle + Math.PI / 6;
        const branchAngle2 = angle - Math.PI / 6;
        const branchLength = size * 0.4;
        const branchPos = size * 0.6;
        
        ctx.moveTo(cos * branchPos, sin * branchPos);
        ctx.lineTo(Math.cos(branchAngle1) * branchLength + cos * branchPos, 
                   Math.sin(branchAngle1) * branchLength + sin * branchPos);
        
        ctx.moveTo(cos * branchPos, sin * branchPos);
        ctx.lineTo(Math.cos(branchAngle2) * branchLength + cos * branchPos, 
                   Math.sin(branchAngle2) * branchLength + sin * branchPos);
      }
      
      ctx.stroke();
      
      // Center circle
      ctx.beginPath();
      ctx.arc(0, 0, size * 0.2, 0, Math.PI * 2);
      ctx.fill();
      
      ctx.restore();
    }
  }

  // Create snowflakes
  const snowflakeCount = 80;
  const snowflakes = [];
  for (let i = 0; i < snowflakeCount; i++) {
    const flake = new Snowflake();
    // Distribute them across the screen initially
    if (canvas.height > 0) {
      flake.y = Math.random() * canvas.height;
    }
    snowflakes.push(flake);
  }

  // Animation loop
  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    snowflakes.forEach(flake => {
      flake.update();
      flake.draw();
    });
    
    requestAnimationFrame(animate);
  }

  animate();
})();

