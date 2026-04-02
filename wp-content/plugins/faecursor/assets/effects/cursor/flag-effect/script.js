/**
 * Flag Effect - Custom flag cursor replacement
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  // Remove any existing flag cursor to prevent duplicates on re-initialization
  const existingCursor = document.querySelector('.faeflag-cursor');
  if (existingCursor) {
    existingCursor.remove();
  }

  const color = faeCursorSettings.color || "#667eea";
  const flag = faeCursorSettings.flag || "";
  const size = faeCursorSettings.size || "1.5rem";
  const flagFit = "cover"; // Default to cover (removed as user option, similar to flag position approach)
  const flagPosition = faeCursorSettings.flagPosition || "center";
  const assetsUrl = faeCursorSettings.assetsUrl || "";
  const isAdmin = document.body.classList.contains('wp-admin');
  
  // Get speed setting and map to follow speed (interpolation factor)
  const speed = faeCursorSettings.speed || "normal";
  const speedMultipliers = {
    slow: 0.15,    // Slower follow (more lag)
    normal: 0.25,  // Normal follow (current default)
    fast: 0.35     // Faster follow (less lag)
  };
  const followSpeed = speedMultipliers[speed] || speedMultipliers.normal;

  // Convert size to pixels for cursor
  const sizeInPx = parseFloat(size) * 16; // Convert rem to px (assuming 16px base)
  const cursorSize = Math.max(24, Math.min(64, sizeInPx)); // Clamp between 24px and 64px

  const flagCursor = document.createElement("div");
  flagCursor.className = "faeflag-cursor";
  flagCursor.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: ${cursorSize}px;
    height: ${cursorSize}px;
    pointer-events: none;
    transform-origin: top left;
    z-index: 9999;
    display: none;
  `;

  // Function to calculate preserveAspectRatio based on fit and position
  function calculatePreserveAspectRatio(position, fit) {
    // Determine alignment based on position
    const positionMap = {
      'center': 'xMidYMid',
      'top': 'xMidYMin',
      'bottom': 'xMidYMax',
      'left': 'xMinYMid',
      'right': 'xMaxYMid',
      'top-left': 'xMinYMin',
      'top-right': 'xMaxYMin',
      'bottom-left': 'xMinYMax',
      'bottom-right': 'xMaxYMax'
    };
    const alignment = positionMap[position] || 'xMidYMid';
    
    // Add fit mode
    if (fit === 'cover') {
      return alignment + ' slice';
    } else if (fit === 'contain') {
      return alignment + ' meet';
    } else if (fit === 'fill') {
      return 'none';
    } else {
      return alignment + ' slice'; // Default to cover
    }
  }

  // Function to calculate image position offsets (like CSS margins)
  // Base position: x="3" y="1" width="30" height="34"
  function calculateImagePosition(position, fit) {
    // Base offsets (center position)
    let offsetX = 0;
    let offsetY = 0;
    
    // Adjust offsets based on position (similar to CSS margin adjustments)
    switch(position) {
      case 'top':
        offsetY = -3; // Move up
        break;
      case 'bottom':
        offsetY = 3; // Move down
        break;
      case 'left':
        offsetX = -3; // Move left
        break;
      case 'right':
        offsetX = 3; // Move right
        break;
      case 'top-left':
        offsetX = -3;
        offsetY = -3;
        break;
      case 'top-right':
        offsetX = 3;
        offsetY = -3;
        break;
      case 'bottom-left':
        offsetX = -3;
        offsetY = 3;
        break;
      case 'bottom-right':
        offsetX = 3;
        offsetY = 3;
        break;
      case 'center':
      default:
        offsetX = 0;
        offsetY = 0;
        break;
    }
    
    // Adjust offsets based on fit mode (additional positioning adjustments)
    // Different fit modes may need different positioning to look better
    if (fit === 'contain') {
      // For contain, image might be smaller, so adjust positioning to center it better
      offsetX += 1;
      offsetY += 1;
    } else if (fit === 'fill') {
      // For fill, image fills completely, might need adjustment to align better
      offsetX -= 1;
      offsetY -= 1;
    } else if (fit === 'cover') {
      // For cover, keep base offsets (already set above)
      // No additional adjustment needed, or slight adjustment for better alignment
      offsetX += 0.5;
      offsetY += 0.5;
    }
    
    // Base position in viewBox coordinates
    const baseX = 3;
    const baseY = 1;
    
    return {
      x: baseX + offsetX,
      y: baseY + offsetY
    };
  }

  const preserveAspectRatio = calculatePreserveAspectRatio(flagPosition, flagFit);
  const imagePos = calculateImagePosition(flagPosition, flagFit);

  // Build SVG content based on whether flag is provided
  let svgContent = '';
  let flagImageElement = null; // Store reference to image element for dynamic updates
  
  if (flag && flag.trim() !== '' && assetsUrl) {
    // Use flag image inside the flag shape
    const flagUrl = assetsUrl + 'flags/' + flag;
    svgContent = `
      <svg viewBox="0 0 48 48">
        <defs>
          <clipPath id="faeFlagClip">
            <path d="M4 2 L4 34 L12 26 L20 46 L26 44 L18 24 L32 24 Z" />
          </clipPath>
        </defs>
        <image href="${flagUrl}" 
               x="${imagePos.x}" 
               y="${imagePos.y}" 
               width="42" 
               height="46" 
               clip-path="url(#faeFlagClip)"
               preserveAspectRatio="${preserveAspectRatio}" />
        <path d="M4 2 L4 34 L12 26 L20 46 L26 44 L18 24 L32 24 Z" 
              fill="none" 
              stroke="#111" 
              stroke-width="1.2" 
              stroke-linejoin="round" 
              filter="drop-shadow(0 0 1px rgba(0,0,0,0.3))" />
      </svg>
    `;
  } else {
    // Use color fill as before
    svgContent = `
      <svg viewBox="0 0 48 48">
        <defs>
          <pattern id="faeFlagPattern" patternUnits="userSpaceOnUse" width="48" height="48">
            <rect width="48" height="48" fill="${color}" />
          </pattern>
        </defs>
        <path d="M4 2 L4 34 L12 26 L20 46 L26 44 L18 24 L32 24 Z" 
              fill="url(#faeFlagPattern)" 
              stroke="#111" 
              stroke-width="1.2" 
              stroke-linejoin="round" 
              filter="drop-shadow(0 0 1px rgba(0,0,0,0.3))" />
      </svg>
    `;
  }

  flagCursor.innerHTML = svgContent;
  document.body.appendChild(flagCursor);
  
  // Get reference to the image element for dynamic updates
  if (flag && flag.trim() !== '' && assetsUrl) {
    flagImageElement = flagCursor.querySelector('image');
  }
  
  // Function to update flag position dynamically (for cases where settings change without reload)
  function updateFlagPosition(newPosition, newFit) {
    if (flagImageElement) {
      const position = newPosition || (typeof faeCursorSettings !== 'undefined' ? faeCursorSettings.flagPosition : null) || 'center';
      const fit = newFit || 'cover'; // Default to cover (removed as user option)
      const newPreserveAspectRatio = calculatePreserveAspectRatio(position, fit);
      const newImagePos = calculateImagePosition(position, fit);
      
      // Update both preserveAspectRatio and position (x, y attributes)
      flagImageElement.setAttribute('preserveAspectRatio', newPreserveAspectRatio);
      flagImageElement.setAttribute('x', newImagePos.x);
      flagImageElement.setAttribute('y', newImagePos.y);
    }
  }
  
  // Expose update function globally for potential dynamic updates
  if (typeof window !== 'undefined') {
    window.faeFlagUpdatePosition = updateFlagPosition;
  }

  let mouseX = 0,
    mouseY = 0,
    posX = 0,
    posY = 0;
  let hasMoved = false;
  let isMouseInViewport = true; // Track if mouse is inside viewport
  let lastMouseMoveTime = 0;
  let viewportCheckInterval = null;

  // Function to check if mouse is within viewport bounds
  function checkMouseInViewport(x, y) {
    return (
      x >= 0 &&
      x <= window.innerWidth &&
      y >= 0 &&
      y <= window.innerHeight
    );
  }

  // Function to check viewport boundaries (Safari-compatible method)
  function checkViewportBoundaries() {
    const now = Date.now();
    // If no mousemove for 100ms, check if we should hide cursor
    if (now - lastMouseMoveTime > 100 && hasMoved) {
      // Check if mouse coordinates are outside viewport
      if (mouseX < 0 || mouseX > window.innerWidth || 
          mouseY < 0 || mouseY > window.innerHeight) {
        if (isMouseInViewport) {
          isMouseInViewport = false;
          flagCursor.style.display = 'none';
        }
      }
    }
  }

  function animate() {
    if (hasMoved && isMouseInViewport) {
      posX += (mouseX - posX) * followSpeed;
      posY += (mouseY - posY) * followSpeed;
      flagCursor.style.transform = `translate(${posX}px, ${posY}px)`;
    }
    requestAnimationFrame(animate);
  }

  const handleMouseMove = (e) => {
    lastMouseMoveTime = Date.now();
    
    // Check viewport boundaries (works in Safari)
    const wasInViewport = isMouseInViewport;
    isMouseInViewport = checkMouseInViewport(e.clientX, e.clientY);
    
    // If mouse just entered viewport, show cursor
    if (!wasInViewport && isMouseInViewport && hasMoved) {
      // Ensure cursor is hidden immediately when re-entering
      if (!isAdmin) {
        document.documentElement.style.cursor = 'none';
        document.body.style.cursor = 'none';
      }
      flagCursor.style.display = 'block';
    }
    
    // If mouse is outside viewport, hide cursor
    if (!isMouseInViewport) {
      flagCursor.style.display = 'none';
      return;
    }
    
    // Ensure cursor is always hidden (re-apply to prevent default cursor from showing)
    if (!isAdmin) {
      document.documentElement.style.cursor = 'none';
      document.body.style.cursor = 'none';
    }

    // Check if mouse is in scope
    const inScope = typeof faeCursorShouldTrigger === 'function' 
      ? faeCursorShouldTrigger(e.clientX, e.clientY, e.target)
      : true;

    if (!inScope) {
      flagCursor.style.display = 'none';
      return;
    }

    if (!hasMoved) {
      hasMoved = true;
      flagCursor.style.display = 'block';
      // Snap to current position on first move
      posX = e.clientX;
      posY = e.clientY;
      mouseX = e.clientX;
      mouseY = e.clientY;
    } else {
      mouseX = e.clientX;
      mouseY = e.clientY;
    }
  };

  // Safari-compatible mouseleave handler
  const handleMouseLeave = (e) => {
    // Check if mouse actually left the document (not just a child element)
    // Safari may not provide relatedTarget, so we also check coordinates
    if (!e.relatedTarget && !e.toElement) {
      isMouseInViewport = false;
      flagCursor.style.display = 'none';
    } else if (e.clientX !== undefined && e.clientY !== undefined) {
      // Double-check with coordinates
      if (!checkMouseInViewport(e.clientX, e.clientY)) {
        isMouseInViewport = false;
        flagCursor.style.display = 'none';
      }
    }
  };

  // Safari-compatible mouseenter handler
  const handleMouseEnter = (e) => {
    // Mark mouse as inside viewport
    isMouseInViewport = true;
    
    // Ensure cursor is hidden immediately when re-entering
    if (!isAdmin) {
      document.documentElement.style.cursor = 'none';
      document.body.style.cursor = 'none';
    }
    
    // Show cursor again when mouse enters viewport (if initialized)
    if (hasMoved) {
      // Update mouse position from event if available
      if (e && e.clientX !== undefined && e.clientY !== undefined) {
        mouseX = e.clientX;
        mouseY = e.clientY;
        // Reset position immediately to avoid jumping from old position
        posX = mouseX;
        posY = mouseY;
        flagCursor.style.transform = `translate(${posX}px, ${posY}px)`;
      }
      
      // Show cursor again immediately
      flagCursor.style.display = 'block';
    }
  };

  // Use mouseover on document to catch re-entry more reliably (Safari fallback)
  const handleMouseOver = (e) => {
    if (e && e.clientX !== undefined && e.clientY !== undefined) {
      const inViewport = checkMouseInViewport(e.clientX, e.clientY);
      
      if (!isMouseInViewport && inViewport && hasMoved) {
        isMouseInViewport = true;
        
        // Ensure cursor is hidden immediately when re-entering
        if (!isAdmin) {
          document.documentElement.style.cursor = 'none';
          document.body.style.cursor = 'none';
        }
        
        mouseX = e.clientX;
        mouseY = e.clientY;
        posX = mouseX;
        posY = mouseY;
        flagCursor.style.transform = `translate(${posX}px, ${posY}px)`;
        flagCursor.style.display = 'block';
      }
    }
  };

  // Listen for mouse leaving window (works better in Safari)
  const handleMouseOut = (e) => {
    // Check if mouse left the window
    if (!e.relatedTarget || (e.relatedTarget === document.documentElement || e.relatedTarget === document.body)) {
      isMouseInViewport = false;
      flagCursor.style.display = 'none';
    }
  };

  document.addEventListener("mousemove", handleMouseMove);
  document.addEventListener("mouseleave", handleMouseLeave);
  document.addEventListener("mouseenter", handleMouseEnter);
  document.addEventListener("mouseover", handleMouseOver);
  document.addEventListener("mouseout", handleMouseOut);
  
  // Periodic viewport check for Safari (fallback method)
  viewportCheckInterval = setInterval(checkViewportBoundaries, 50);
  
  // Also ensure cursor is hidden on the document element itself
  if (!isAdmin) {
    document.documentElement.style.cursor = 'none';
    document.body.style.cursor = 'none';
  }

  animate();
  
  // Cleanup on page unload
  window.addEventListener('beforeunload', () => {
    if (viewportCheckInterval) {
      clearInterval(viewportCheckInterval);
    }
  });
})();

