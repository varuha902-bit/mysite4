/**
 * Fireworks Effect - Click-based explosion
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const color = faeCursorSettings.color || "#667eea";

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

  document.addEventListener("click", (e) => {
    // Check if mouse is in scope before creating fireworks
    if (typeof faeCursorShouldTrigger === 'function' && 
        !faeCursorShouldTrigger(e.clientX, e.clientY, e.target)) {
      return; // Don't create fireworks if outside scope
    }
    
    const count = 20;
    for (let i = 0; i < count; i++) {
      const particle = document.createElement("div");
      particle.className = "faefirework";
      particle.style.cssText = `
        position: fixed;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        left: ${e.clientX}px;
        top: ${e.clientY}px;
      `;

      const angle = Math.random() * 2 * Math.PI;
      const distance = 60 + Math.random() * 40;
      const x = Math.cos(angle) * distance;
      const y = Math.sin(angle) * distance;

      // For grayscale colors (white, black, gray), use original color with slight variation
      if (isGrayscale) {
        // Use the original hex color directly for white/black/gray
        particle.style.background = color;
      } else {
        // For colored fireworks, vary the hue slightly for visual interest
        const hueVariation = (Math.random() * 60 - 30); // ±30 degrees
        const hue = (baseHsl.h + hueVariation + 360) % 360; // Keep hue in 0-360 range
        // Use original saturation and lightness, but slightly vary lightness for depth
        const lightness = Math.max(40, Math.min(80, baseHsl.l + (Math.random() * 20 - 10)));
        particle.style.background = `hsl(${hue}, ${baseHsl.s}%, ${lightness}%)`;
      }
      
      particle.style.setProperty("--x", `${x}px`);
      particle.style.setProperty("--y", `${y}px`);

      document.body.appendChild(particle);

      setTimeout(() => particle.remove(), 700);
    }
  });
})();

