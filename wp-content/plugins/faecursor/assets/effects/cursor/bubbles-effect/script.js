/**
 * Bubbles Effect - Canvas-based rising bubbles
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const canvas = document.createElement("canvas");
  canvas.id = "fae-bubbles-canvas";
  canvas.style.cssText =
    "position:fixed;top:0;left:0;pointer-events:none;z-index:9999;";
  document.body.appendChild(canvas);

  const ctx = canvas.getContext("2d");
  let width = window.innerWidth;
  let height = window.innerHeight;
  canvas.width = width;
  canvas.height = height;

  window.addEventListener("resize", () => {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width;
    canvas.height = height;
  });

  const bubbles = [];
  const color = (faeCursorSettings.color || "#667eea").toLowerCase();
  const speed = faeCursorSettings.speed || "normal";
  // Check for boolean true, string '1', or number 1 (PHP may pass different types)
  const multiColor = faeCursorSettings.multiColor === true || faeCursorSettings.multiColor === "1" || faeCursorSettings.multiColor === 1;
  
  // Speed multipliers for bubble animation
  const speedMap = {
    slow: {
      velocityMultiplier: 0.6,    // Slower movement
      gravityMultiplier: 0.7,     // Slower gravity
      fadeMultiplier: 0.7         // Slower fade
    },
    normal: {
      velocityMultiplier: 1.0,   // Normal speed
      gravityMultiplier: 1.0,   // Normal gravity
      fadeMultiplier: 1.0        // Normal fade
    },
    fast: {
      velocityMultiplier: 1.5,   // Faster movement
      gravityMultiplier: 1.4,     // Faster gravity
      fadeMultiplier: 1.4         // Faster fade
    }
  };
  
  const speedConfig = speedMap[speed] || speedMap.normal;
  
  // Multi-color palette for rainbow/multi-color mode
  const multiColorPalette = [
    "#FF6B6B", // Red
    "#4ECDC4", // Teal
    "#45B7D1", // Blue
    "#FFA07A", // Light Salmon
    "#98D8C8", // Mint
    "#F7DC6F", // Yellow
    "#BB8FCE", // Purple
    "#85C1E2", // Sky Blue
    "#F8B739", // Orange
    "#52BE80"  // Green
  ];
  
  // Check if using default blue color or dynamic color
  // If color is the default "#667eea", use default blue scheme (bubbles-2 style)
  // Otherwise, use dynamic color based on user's selection
  const useDefaultColor = color === "#667eea" || !color;

  // Convert hex to HSL for color variation (for dynamic mode)
  function hexToHsl(hex) {
    // Normalize hex color (handle 3-digit hex codes)
    let normalizedHex = hex;
    if (hex.length === 4) {
      normalizedHex = "#" + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
    }
    
    const r = parseInt(normalizedHex.slice(1, 3), 16) / 255;
    const g = parseInt(normalizedHex.slice(3, 5), 16) / 255;
    const b = parseInt(normalizedHex.slice(5, 7), 16) / 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;
    if (max === min) {
      h = s = 0;
    } else {
      const d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
        case g: h = ((b - r) / d + 2) / 6; break;
        case b: h = ((r - g) / d + 4) / 6; break;
      }
    }
    return [h * 360, s * 100, l * 100];
  }

  // Convert hex to RGB
  function hexToRgb(hex) {
    let normalizedHex = hex;
    if (hex.length === 4) {
      normalizedHex = "#" + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
    }
    const r = parseInt(normalizedHex.slice(1, 3), 16);
    const g = parseInt(normalizedHex.slice(3, 5), 16);
    const b = parseInt(normalizedHex.slice(5, 7), 16);
    return [r, g, b];
  }

  const [hue] = useDefaultColor ? [220] : hexToHsl(color); // Default blue hue if using default

  function createBubble(x, y, count = 3, burst = false) {
    for (let i = 0; i < count; i++) {
      const angle = burst ? Math.random() * Math.PI * 2 : 0;
      const baseSpeed = burst ? Math.random() * 1.2 + 0.2 : Math.random() * 1.5;
      const bubbleSpeed = baseSpeed * speedConfig.velocityMultiplier;
      
      let bubbleColor;
      let bubbleHue = hue;
      
      if (multiColor) {
        // Multi-color mode: pick a random color from the palette
        const randomColor = multiColorPalette[Math.floor(Math.random() * multiColorPalette.length)];
        const [r, g, b] = hexToRgb(randomColor);
        // Add some variation to the color
        const variation = 30;
        bubbleColor = `rgba(${Math.max(0, Math.min(255, r + (Math.random() - 0.5) * variation))}, ${
          Math.max(0, Math.min(255, g + (Math.random() - 0.5) * variation))
        }, ${Math.max(0, Math.min(255, b + (Math.random() - 0.5) * variation))}, 0.8)`;
      } else if (useDefaultColor) {
        // Default blue color scheme
        bubbleColor = `rgba(${180 + Math.random() * 40}, ${
          220 + Math.random() * 30
        }, 255, 1)`;
      } else {
        // Dynamic color based on user's selection
        // Use the actual color with some variation
        const [r, g, b] = hexToRgb(color);
        const variation = 40;
        bubbleColor = `rgba(${Math.max(0, Math.min(255, r + (Math.random() - 0.5) * variation))}, ${
          Math.max(0, Math.min(255, g + (Math.random() - 0.5) * variation))
        }, ${Math.max(0, Math.min(255, b + (Math.random() - 0.5) * variation))}, 0.8)`;
      }
      
      bubbles.push({
        x: x + (Math.random() - 0.5) * 10,
        y: y + (Math.random() - 0.5) * 10,
        radius: Math.random() * 5 + 2,
        vx: burst ? Math.cos(angle) * bubbleSpeed : (Math.random() - 0.5) * 0.8 * speedConfig.velocityMultiplier,
        vy: burst ? Math.sin(angle) * bubbleSpeed : (-Math.random() * 2 - 0.5) * speedConfig.velocityMultiplier,
        life: 1,
        color: bubbleColor,
        hasShine: useDefaultColor && !multiColor, // Add shine highlight for default color mode only
      });
    }
  }

  document.addEventListener("mousemove", (e) => {
    // Check if mouse is in scope before creating bubbles
    if (typeof faeCursorShouldTrigger === 'function' && 
        !faeCursorShouldTrigger(e.clientX, e.clientY, e.target)) {
      return; // Don't create bubbles if outside scope
    }
    createBubble(e.clientX, e.clientY, 3);
  });

  document.addEventListener("click", (e) => {
    // Check if mouse is in scope before creating burst
    if (typeof faeCursorShouldTrigger === 'function' && 
        !faeCursorShouldTrigger(e.clientX, e.clientY, e.target)) {
      return; // Don't create burst if outside scope
    }
    createBubble(e.clientX, e.clientY, 10, true); // Burst on click
  });

  function animate() {
    ctx.clearRect(0, 0, width, height);

    // Base gravity and fade rates (adjusted by speed and color mode)
    const baseGravity = useDefaultColor ? 0.01 : 0.02;
    const baseFadeRate = useDefaultColor ? 0.01 : 0.015;
    
    // Apply speed multipliers
    const gravity = baseGravity * speedConfig.gravityMultiplier;
    const fadeRate = baseFadeRate * speedConfig.fadeMultiplier;

    for (let i = bubbles.length - 1; i >= 0; i--) {
      const b = bubbles[i];
      b.x += b.vx;
      b.y += b.vy;
      b.vy += gravity;
      b.life -= fadeRate;

      if (b.life <= 0) {
        bubbles.splice(i, 1);
        continue;
      }

      const opacity = Math.max(b.life, 0);
      ctx.beginPath();
      ctx.arc(b.x, b.y, b.radius, 0, Math.PI * 2);
      
      // Update color with opacity
      if (useDefaultColor && !multiColor) {
        // Default color mode uses "1)" format
        ctx.fillStyle = b.color.replace("1)", `${opacity})`);
      } else {
        // Multi-color and custom color modes use "0.8" format
        ctx.fillStyle = b.color.replace(/0\.8\)/, `${opacity.toFixed(2)})`);
      }
      ctx.fill();

      // Add shine highlight for default color mode (bubbles-2 style)
      if (b.hasShine) {
        ctx.beginPath();
        ctx.arc(
          b.x - b.radius / 3,
          b.y - b.radius / 3,
          b.radius / 5,
          0,
          Math.PI * 2
        );
        ctx.fillStyle = `rgba(255,255,255,${0.2 * opacity})`;
        ctx.fill();
      }
    }

    requestAnimationFrame(animate);
  }

  animate();
})();

