/**
 * Gradient Trail Effect
 * Creates a smooth gradient trail from the selected color
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  // Get settings
  const primaryColor = faeCursorSettings.color || "#667eea";
  const speed = faeCursorSettings.speed || "normal";
  
  const speedMap = {
    slow: { follow: 0.05, fade: 0.015, maxLength: 40 },
    normal: { follow: 0.1, fade: 0.025, maxLength: 30 },
    fast: { follow: 0.15, fade: 0.04, maxLength: 20 }
  };
  
  const speedConfig = speedMap[speed] || speedMap.normal;
  const maxTrailLength = speedConfig.maxLength;

  // Create canvas for gradient trail
  const canvas = document.createElement("canvas");
  canvas.id = "fae-gradient-trail";
  canvas.style.cssText = "position:fixed;inset:0;pointer-events:none;z-index:9999;opacity:1;";
  document.body.appendChild(canvas);
  
  const ctx = canvas.getContext("2d");
  let W = window.innerWidth;
  let H = window.innerHeight;
  canvas.width = W;
  canvas.height = H;

  // Resize handler
  window.addEventListener("resize", () => {
    W = window.innerWidth;
    H = window.innerHeight;
    canvas.width = W;
    canvas.height = H;
  });

  // Trail points
  const trail = [];
  let pos = { x: W / 2, y: H / 2 };
  let target = { x: W / 2, y: H / 2 };
  let hasMoved = false; // Track if mouse has moved
  let isMouseInViewport = true; // Track if mouse is inside viewport

  // Track mouse
  window.addEventListener("mousemove", (e) => {
    // Mark mouse as inside viewport (mousemove only fires when inside)
    isMouseInViewport = true;
    
    // Check if mouse is in scope
    const inScope = typeof faeCursorShouldTrigger === 'function' 
      ? faeCursorShouldTrigger(e.clientX, e.clientY, e.target)
      : true;

    if (!inScope) {
      // Clear trail when outside scope
      trail.length = 0;
      return;
    }

    target.x = e.clientX;
    target.y = e.clientY;
    
    // On first mousemove, immediately set position to cursor (no interpolation)
    if (!hasMoved) {
      pos.x = e.clientX;
      pos.y = e.clientY;
      hasMoved = true;
    }
  }, { passive: true });

  // Handle mouse leave event (when mouse leaves viewport)
  const handleMouseLeave = () => {
    // Mark mouse as outside viewport
    isMouseInViewport = false;
    
    // Smoothly fade out canvas
    requestAnimationFrame(() => {
      canvas.style.opacity = '0';
    });
    
    // Clear trail after fade out completes
    setTimeout(() => {
      trail.length = 0;
    }, 300); // Match CSS transition duration
  };

  // Handle mouse enter event (when mouse enters viewport)
  const handleMouseEnter = (e) => {
    // Mark mouse as inside viewport
    isMouseInViewport = true;
    
    // Reset position to current mouse position to avoid jumping from old position
    if (e && e.clientX !== undefined && e.clientY !== undefined) {
      pos.x = e.clientX;
      pos.y = e.clientY;
      target.x = e.clientX;
      target.y = e.clientY;
    }
    
    // Smoothly fade in canvas
    requestAnimationFrame(() => {
      canvas.style.opacity = '1';
    });
  };

  // Convert hex to RGB
  function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : { r: 102, g: 126, b: 234 };
  }

  // Get RGB values for the primary color (will be used with varying opacity)
  const primaryRgb = hexToRgb(primaryColor);

  // Animation loop
  function animate() {
    // Only update and add trail points after mouse has moved and is in viewport
    if (hasMoved && isMouseInViewport) {
      // Update position with smooth following
      pos.x += (target.x - pos.x) * speedConfig.follow;
      pos.y += (target.y - pos.y) * speedConfig.follow;

      // Add point to trail
      trail.push({ x: pos.x, y: pos.y, life: 1.0 });
      if (trail.length > maxTrailLength) {
        trail.shift();
      }
    }

    // Clear canvas
    ctx.clearRect(0, 0, W, H);

    // Draw trail with gradient using same color but varying opacity
    if (trail.length > 1) {
      for (let i = 0; i < trail.length - 1; i++) {
        const point = trail[i];
        const nextPoint = trail[i + 1];
        
        // Fade based on position in trail and life
        point.life -= speedConfig.fade;
        if (point.life < 0) point.life = 0;
        
        // Calculate opacity based on position in trail (newer points = higher opacity)
        // Start of trail (oldest) has lower opacity, end of trail (newest) has higher opacity
        const positionRatio = i / trail.length;
        const baseOpacity = 0.3 + (1 - positionRatio) * 0.5; // Range from 0.3 to 0.8
        const opacity = point.life * baseOpacity;
        
        // Use the same color with varying opacity for gradient effect
        ctx.strokeStyle = `rgba(${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}, ${opacity})`;
        // Line width varies along the trail - thicker at the end, thinner at the start
        ctx.lineWidth = 2 + (i / trail.length) * 4;
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
        
        ctx.beginPath();
        ctx.moveTo(point.x, point.y);
        ctx.lineTo(nextPoint.x, nextPoint.y);
        ctx.stroke();
      }
    }

    // Draw cursor dot - small and transparent so it doesn't block visibility
    if (trail.length > 0) {
      const lastPoint = trail[trail.length - 1];
      const dotSize = 6; // Small dot size
      const gradient = ctx.createRadialGradient(lastPoint.x, lastPoint.y, 0, lastPoint.x, lastPoint.y, dotSize);
      gradient.addColorStop(0, `rgba(${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}, 0.4)`); // Semi-transparent center
      gradient.addColorStop(0.5, `rgba(${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}, 0.2)`); // More transparent
      gradient.addColorStop(1, "transparent");
      
      ctx.fillStyle = gradient;
      ctx.beginPath();
      ctx.arc(lastPoint.x, lastPoint.y, dotSize, 0, Math.PI * 2);
      ctx.fill();
    }

    // Remove dead points
    for (let i = trail.length - 1; i >= 0; i--) {
      if (trail[i].life <= 0) {
        trail.splice(i, 1);
      }
    }

    requestAnimationFrame(animate);
  }

  // Start the effect
  window.addEventListener("load", () => {
    // Hide trail when mouse leaves viewport
    document.addEventListener("mouseleave", handleMouseLeave);
    document.addEventListener("mouseenter", handleMouseEnter);
    
    // Start animation loop
    animate();
  });
})();

