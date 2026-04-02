/**
 * Leaf Effect - Falling leaves on mouse shake
 * Optimized for performance
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  // Get speed setting
  const speed = faeCursorSettings.speed || "normal";
  
  // Get color setting
  const baseColor = faeCursorSettings.color || "#22c55e"; // Default green

  // Speed multipliers for leaf animation
  const speedMap = {
    slow: { 
      velocityMultiplier: 0.1,    // Slower falling
      rotationMultiplier: 0.1,     // Slower rotation
      windMultiplier: 0.1          // Less wind effect
    },
    normal: { 
      velocityMultiplier: 0.6,    // Normal speed
      rotationMultiplier: 0.7,    // Normal rotation
      windMultiplier: 0.6          // Normal wind
    },
    fast: { 
      velocityMultiplier: 1,    // Faster falling
      rotationMultiplier: 1,    // Faster rotation
      windMultiplier: 1          // More wind effect
    }
  };

  const speedConfig = speedMap[speed] || speedMap.normal;

  // Performance optimizations
  const MAX_LEAVES = 150; // Maximum number of leaves on screen (increased for better feel)
  const MAX_POSITION_HISTORY = 10; // Limit position tracking array
  const SHAKE_THRESHOLD = 50;
  const POSITION_TRACK_TIME = 200; // ms
  const OFFSCREEN_BUFFER = 100; // pixels (increased buffer for smoother feel)

  const leaves = [];
  let lastPositions = [];
  let rafId = null;
  let animationFrameId = null;
  let isAnimating = false;
  let windowWidth = window.innerWidth;
  let windowHeight = window.innerHeight;

  // Cleanup function to remove all leaves
  function cleanupAllLeaves() {
    for(let i = leaves.length - 1; i >= 0; i--){
      const leaf = leaves[i];
      if (leaf && leaf.el && leaf.el.parentNode) {
        leaf.el.remove();
      }
    }
    leaves.length = 0;
    lastPositions.length = 0;
    isAnimating = false;
  }

  // Cache window dimensions and update on resize
  const resizeHandler = () => {
    windowWidth = window.innerWidth;
    windowHeight = window.innerHeight;
  };
  window.addEventListener('resize', resizeHandler, { passive: true });

  // Cleanup on page unload
  window.addEventListener('beforeunload', () => {
    cleanupAllLeaves();
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
    if (animationFrameId) {
      cancelAnimationFrame(animationFrameId);
      animationFrameId = null;
    }
    window.removeEventListener('resize', resizeHandler);
  });

  // Pause animation when page is hidden (performance optimization)
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      // Page is hidden - pause animation but keep leaves
      if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
      }
      isAnimating = false;
    } else if (leaves.length > 0 && !isAnimating) {
      // Page is visible again - resume animation if there are leaves
      isAnimating = true;
      animate();
    }
  });

  // Utility function
  function random(min, max) {
    return Math.random() * (max - min) + min;
  }

  // Convert hex to HSL for color variation
  function hexToHSL(hex) {
    // Remove # if present
    hex = hex.replace(/^#/, '');
    
    // Parse hex
    let r = parseInt(hex.substring(0, 2), 16) / 255;
    let g = parseInt(hex.substring(2, 4), 16) / 255;
    let b = parseInt(hex.substring(4, 6), 16) / 255;
    
    let max = Math.max(r, g, b);
    let min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;
    
    if (max === min) {
      h = s = 0;
    } else {
      let d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
        case g: h = ((b - r) / d + 2) / 6; break;
        case b: h = ((r - g) / d + 4) / 6; break;
      }
    }
    
    return { h: h * 360, s: s * 100, l: l * 100 };
  }
  
  // Get base color HSL
  const baseHSL = hexToHSL(baseColor);

  // SVG leaf template
  function getLeafSVG() {
    // Add slight variation to the base color for natural look
    const hueVariation = random(-15, 15);
    const lightnessVariation = random(-15, 15);
    const hue = (baseHSL.h + hueVariation + 360) % 360;
    const saturation = Math.max(20, Math.min(100, baseHSL.s));
    const lightness = Math.max(25, Math.min(75, baseHSL.l + lightnessVariation));
    const color = `hsl(${hue}, ${saturation}%, ${lightness}%)`;
    return `
    <svg viewBox="0 0 600 600" width="40" height="40">
      <path style="fill:${color}" d="M135.866,174.895c-14.793,6.357-25.141,14.735-30.451,19.678c-2.421,2.253-6.165,2.255-8.588,0.004
      l0,0c-2.549-2.367-2.704-6.348-0.342-8.902c16.686-18.039,49.381-52.643,129.512-56.5c96.565-4.647,118.192,67.096,204.192,50.096
      c0,0-41.978,3.339-122.456-52.863c-63.7-44.485-137.194-9.567-138.534-9.147c0.6-0.91,39.59-58.99,115.99-58.99
      c85,0,122,109,220,96c0,0-62,111-170,111c-71.288,0-111.748-48.898-147.552-81.646
      C173.614,170.797,153.327,167.39,135.866,174.895z"/>
    </svg>
    `;
  }

  // Create a leaf at mouse position, centered
  function createLeaf(mouseX, mouseY) {
    // Only remove very old off-screen leaves if we're at limit
    if (leaves.length >= MAX_LEAVES) {
      let removed = false;
      for(let i = 0; i < leaves.length; i++){
        const leaf = leaves[i];
        if (!leaf || !leaf.el) continue;
        
        const currentY = leaf.startY + leaf.y;
        const currentX = leaf.startX + leaf.x;
        const farBuffer = OFFSCREEN_BUFFER * 2;
        
        // Only remove if significantly off-screen
        if(currentY > windowHeight + farBuffer || 
           currentX < -farBuffer || 
           currentX > windowWidth + farBuffer ||
           currentY < -farBuffer){
          if (leaf.el.parentNode) {
            leaf.el.remove();
          }
          leaves.splice(i, 1);
          removed = true;
          break;
        }
      }
      // If no off-screen leaves found, skip creating new leaf
      if (!removed) return;
    }

    const leaf = document.createElement('div');
    leaf.className = 'faeleaf';
    leaf.innerHTML = getLeafSVG();
    document.body.appendChild(leaf);

    const size = 40; // leaf width/height
    const leafObj = {
      el: leaf,
      startX: mouseX, // cursor position
      startY: mouseY,
      x: 0, // offset from start position
      y: 0, // offset from start position
      rotation: random(0, 360),
      rotationSpeed: random(-3, 3) * speedConfig.rotationMultiplier,
      velocityX: random(-1, 1) * speedConfig.velocityMultiplier,
      velocityY: random(2, 5) * speedConfig.velocityMultiplier,
      wind: random(-0.5, 0.5) * speedConfig.windMultiplier
    };
    
    // Position leaf at cursor (centered)
    const halfSize = size / 2;
    leaf.style.left = (mouseX - halfSize) + 'px';
    leaf.style.top = (mouseY - halfSize) + 'px';
    leaf.style.zIndex = '9999';
    leaf.style.transform = `translate3d(0, 0, 0) rotate(${leafObj.rotation}deg)`;
    
    leaves.push(leafObj);
    
    // Start animation loop if not already running and page is visible
    if (!isAnimating && !document.hidden) {
      isAnimating = true;
      animate();
    }
  }

  // Detect mouse shake with throttling
  document.addEventListener('mousemove', (e) => {
    // Check if mouse is in scope
    const inScope = typeof faeCursorShouldTrigger === 'function' 
      ? faeCursorShouldTrigger(e.clientX, e.clientY, e.target)
      : true;

    if (!inScope) return;

    // Throttle shake detection using requestAnimationFrame
    if (rafId) return;
    
    rafId = requestAnimationFrame(() => {
      rafId = null;
      
      const now = Date.now();
      const currentPos = { x: e.clientX, y: e.clientY, t: now };
      
      // Add current position
      lastPositions.push(currentPos);
      
      // Remove old positions and limit array size
      lastPositions = lastPositions.filter(p => now - p.t < POSITION_TRACK_TIME);
      if (lastPositions.length > MAX_POSITION_HISTORY) {
        lastPositions = lastPositions.slice(-MAX_POSITION_HISTORY);
      }

      // Calculate total distance moved
      let totalDist = 0;
      const len = lastPositions.length;
      for(let i = 1; i < len; i++){
        const dx = lastPositions[i].x - lastPositions[i-1].x;
        const dy = lastPositions[i].y - lastPositions[i-1].y;
        totalDist += Math.sqrt(dx*dx + dy*dy);
      }

      if(totalDist > SHAKE_THRESHOLD){
        const numLeaves = Math.floor(random(1, 3));
        for(let i = 0; i < numLeaves; i++){
          createLeaf(e.clientX + random(-10, 10), e.clientY + random(-10, 10));
        }
      }
    });
  }, { passive: true });

  // Animate leaves - optimized loop
  function animate() {
    // Early exit if no leaves
    if (leaves.length === 0) {
      isAnimating = false;
      return;
    }

    // Cache window dimensions (updated on resize)
    const w = windowWidth;
    const h = windowHeight;
    const maxX = w + OFFSCREEN_BUFFER;
    const minX = -OFFSCREEN_BUFFER;

    // Batch DOM updates
    for(let i = leaves.length - 1; i >= 0; i--){
      const leaf = leaves[i];
      
      // Safety check - skip if leaf or element is invalid
      if (!leaf || !leaf.el || !leaf.el.parentNode) {
        leaves.splice(i, 1);
        continue;
      }
      
      // Update physics
      leaf.x += leaf.velocityX + leaf.wind;
      leaf.y += leaf.velocityY;
      leaf.rotation += leaf.rotationSpeed;
      leaf.wind += random(-0.05, 0.05) * speedConfig.windMultiplier;
      leaf.wind = Math.max(Math.min(leaf.wind, 1 * speedConfig.windMultiplier), -1 * speedConfig.windMultiplier);

      // Calculate current position
      const currentY = leaf.startY + leaf.y;
      const currentX = leaf.startX + leaf.x;

      // Remove if off screen
      if(currentY > h || currentX < minX || currentX > maxX || currentY < -OFFSCREEN_BUFFER){
        if (leaf.el.parentNode) {
          leaf.el.remove();
        }
        leaves.splice(i, 1);
        continue;
      }

      // Update transform using translate3d for GPU acceleration
      leaf.el.style.transform = `translate3d(${leaf.x}px, ${leaf.y}px, 0) rotate(${leaf.rotation}deg)`;
    }

    // Continue animation if there are leaves
    if (leaves.length > 0) {
      animationFrameId = requestAnimationFrame(animate);
    } else {
      isAnimating = false;
      animationFrameId = null;
    }
  }
})();
