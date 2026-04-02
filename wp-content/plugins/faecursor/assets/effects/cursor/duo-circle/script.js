let faeMainCircle = null;
let faeDuplicate = null;
let faeMouseX = 0,
  faeMouseY = 0;
let faeDuplicateX = 0,
  faeDuplicateY = 0;
let faeMergeThreshold = 3; // Distance for merging (reduced for more engagement during slow movements)
let faeCircleColor =
  typeof faeCursorSettings !== "undefined" && faeCursorSettings.color
    ? faeCursorSettings.color
    : "#667eea";

// Get speed setting and map to follow speed
const speed =
  typeof faeCursorSettings !== "undefined" && faeCursorSettings.speed
    ? faeCursorSettings.speed
    : "normal";

// Speed multipliers for follow speed (how fast the duplicate follows)
// Slightly slower values to keep more separation and engagement
const speedMultipliers = {
  slow: 0.05, // Slower follow (more separation)
  normal: 0.08, // Normal follow (more separation)
  fast: 0.12, // Faster follow (more separation)
};

let faeFollowSpeed =
  speedMultipliers[speed] || speedMultipliers.normal; // Speed at which the duplicate follows the main circle

let animationFrameId = null; // For requestAnimationFrame
let isInitialized = false; // Track if cursor elements are created
let faeNeedsInstantSnap = false; // Snap circles to mouse on next move (after back/visibility restore)

// Lighten any color (hex, rgb, or hsl)
function faeLightenColor(color, percent = 20) {
  let r, g, b;

  // HEX format (#RRGGBB or #RGB)
  if (color.startsWith("#")) {
    color = color.replace("#", "");
    if (color.length === 3) {
      color = color
        .split("")
        .map((c) => c + c)
        .join("");
    }
    r = parseInt(color.substr(0, 2), 16);
    g = parseInt(color.substr(2, 2), 16);
    b = parseInt(color.substr(4, 2), 16);
  }

  // RGB format (rgb(r, g, b))
  else if (color.startsWith("rgb")) {
    const values = color.match(/\d+/g).map(Number);
    [r, g, b] = values;
  }

  // HSL format (hsl(h, s%, l%))
  else if (color.startsWith("hsl")) {
    const [h, s, l] = color.match(/[\d.]+/g).map(Number);
    return `hsl(${h}, ${s}%, ${Math.min(100, l + percent)}%)`;
  }

  // Lighten RGB values
  r = Math.min(255, Math.floor(r + (255 - r) * (percent / 100)));
  g = Math.min(255, Math.floor(g + (255 - g) * (percent / 100)));
  b = Math.min(255, Math.floor(b + (255 - b) * (percent / 100)));

  // Convert back to HEX
  const toHex = (v) => v.toString(16).padStart(2, "0");
  return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
}

// Create the main circle for the cursor
const faeCreateMainCircle = (x, y) => {
  if (!faeMainCircle) {
    faeMainCircle = document.createElement("div");
    faeMainCircle.className = "faecircle";
    faeMainCircle.style.border = `2px solid ${faeLightenColor(
      faeCircleColor,
      70
    )}`;
    // Add smooth transition for opacity
    faeMainCircle.style.transition = "opacity 0.3s ease";
    faeMainCircle.style.opacity = "1";
    document.body.appendChild(faeMainCircle);
  }

  // Use transform for better performance
  faeMainCircle.style.left = `${x}px`;
  faeMainCircle.style.top = `${y}px`;
};

// Create the duplicate circle
const faeCreateDuplicateCircle = () => {
  if (!faeDuplicate) {
    faeDuplicate = document.createElement("div");
    faeDuplicate.className = "faeduplicate";
    faeDuplicate.style.border = `2px solid ${faeCircleColor}`;
    // Add smooth transition for opacity
    faeDuplicate.style.transition = "opacity 0.3s ease";
    faeDuplicate.style.opacity = "1";
    document.body.appendChild(faeDuplicate);
  }
};

// Initialize cursor elements
const faeInitializeCursor = () => {
  if (isInitialized) return;

  faeCreateMainCircle(faeMouseX, faeMouseY);
  faeCreateDuplicateCircle();

  // Initialize duplicate position to match main cursor
  faeDuplicateX = faeMouseX;
  faeDuplicateY = faeMouseY;
  faeDuplicate.style.left = `${faeDuplicateX}px`;
  faeDuplicate.style.top = `${faeDuplicateY}px`;

  isInitialized = true;
};

// Update the position of the duplicate to follow smoothly
const faeUpdateDuplicatePosition = () => {
  if (!faeDuplicate || !isInitialized) return;

  // Calculate distance first
  const distance = Math.sqrt(
    Math.pow(faeDuplicateX - faeMouseX, 2) +
      Math.pow(faeDuplicateY - faeMouseY, 2)
  );

  // Only merge if very close (within threshold), otherwise follow smoothly
  if (distance < faeMergeThreshold) {
    faeDuplicateX = faeMouseX;
    faeDuplicateY = faeMouseY;
  } else {
    // Calculate new position with easing - this keeps them separated and more engaging
    faeDuplicateX += (faeMouseX - faeDuplicateX) * faeFollowSpeed;
    faeDuplicateY += (faeMouseY - faeDuplicateY) * faeFollowSpeed;
  }

  // Update duplicate position
  faeDuplicate.style.left = `${faeDuplicateX}px`;
  faeDuplicate.style.top = `${faeDuplicateY}px`;
};

// Animation loop using requestAnimationFrame for better performance
const animate = () => {
  faeUpdateDuplicatePosition();
  animationFrameId = requestAnimationFrame(animate);
};

// Handle mouse move event
const faeHandleMouseMove = (e) => {
  // Get mouse position relative to the viewport
  faeMouseX = e.clientX;
  faeMouseY = e.clientY;

  // If we just came back from history/visibility change, snap circles instantly
  if (faeNeedsInstantSnap) {
    if (!isInitialized) {
      faeInitializeCursor();
    }
    faeDuplicateX = faeMouseX;
    faeDuplicateY = faeMouseY;
    if (faeDuplicate) {
      faeDuplicate.style.left = `${faeDuplicateX}px`;
      faeDuplicate.style.top = `${faeDuplicateY}px`;
    }
    if (faeMainCircle) {
      faeMainCircle.style.left = `${faeMouseX}px`;
      faeMainCircle.style.top = `${faeMouseY}px`;
    }
    faeNeedsInstantSnap = false;
  }

  // Check if mouse is in scope
  const inScope =
    typeof faeCursorShouldTrigger === "function"
      ? faeCursorShouldTrigger(faeMouseX, faeMouseY, e.target)
      : true;

  // Hide/show cursor based on scope with smooth opacity
  if (faeMainCircle) {
    faeMainCircle.style.opacity = inScope ? "1" : "0";
    // Always keep pointer-events: none to allow clicks on links/buttons
    faeMainCircle.style.pointerEvents = "none";
  }
  if (faeDuplicate) {
    faeDuplicate.style.opacity = inScope ? "1" : "0";
    // Always keep pointer-events: none to allow clicks on links/buttons
    faeDuplicate.style.pointerEvents = "none";
  }

  if (!inScope) return;

  // Initialize cursor if not done yet
  if (!isInitialized) {
    faeInitializeCursor();
  }

  // Update main circle position
  if (faeMainCircle) {
    faeMainCircle.style.left = `${faeMouseX}px`;
    faeMainCircle.style.top = `${faeMouseY}px`;
  }
};

// Handle mouse leave event (when mouse leaves viewport)
const faeHandleMouseLeave = () => {
  // Hide circles smoothly when mouse leaves viewport
  if (faeMainCircle) {
    faeMainCircle.style.opacity = "0";
    // Always keep pointer-events: none
    faeMainCircle.style.pointerEvents = "none";
  }
  if (faeDuplicate) {
    faeDuplicate.style.opacity = "0";
    // Always keep pointer-events: none
    faeDuplicate.style.pointerEvents = "none";
  }
};

// Reset cursor state (used when page is restored from cache or becomes visible again)
const faeResetCursorState = () => {
  // Hide circles until the next mouse move, then snap instantly
  if (faeMainCircle) {
    faeMainCircle.style.opacity = "0";
    faeMainCircle.style.pointerEvents = "none";
  }
  if (faeDuplicate) {
    faeDuplicate.style.opacity = "0";
    faeDuplicate.style.pointerEvents = "none";
  }
  faeNeedsInstantSnap = true;
};

// Initialize the effect
const faeInitEffect = () => {
  window.addEventListener("mousemove", faeHandleMouseMove);
  // Hide circles when mouse leaves viewport
  document.addEventListener("mouseleave", faeHandleMouseLeave);
  document.addEventListener("mouseenter", (e) => {
    // Show circles again when mouse enters viewport (if initialized)
    if (isInitialized && faeMainCircle && faeDuplicate) {
      // Update mouse position from event if available
      if (e && e.clientX !== undefined && e.clientY !== undefined) {
        faeMouseX = e.clientX;
        faeMouseY = e.clientY;
        // Update main circle position immediately
        faeMainCircle.style.left = `${faeMouseX}px`;
        faeMainCircle.style.top = `${faeMouseY}px`;
      }

      // Reset duplicate position to current mouse position to avoid jumping from old position
      faeDuplicateX = faeMouseX;
      faeDuplicateY = faeMouseY;
      faeDuplicate.style.left = `${faeDuplicateX}px`;
      faeDuplicate.style.top = `${faeDuplicateY}px`;

      // Smoothly show circles
      faeMainCircle.style.opacity = "1";
      // Always keep pointer-events: none to allow clicks on links/buttons
      faeMainCircle.style.pointerEvents = "none";
      faeDuplicate.style.opacity = "1";
      // Always keep pointer-events: none to allow clicks on links/buttons
      faeDuplicate.style.pointerEvents = "none";
    }
  });

  // Start animation loop if not already running
  if (!animationFrameId) {
    animate();
  }
};

// Start the effect
window.addEventListener("load", faeInitEffect);

// Handle page restoration from back/forward cache
window.addEventListener("pageshow", (event) => {
  // Reset state when page is restored from bfcache
  if (event.persisted) {
    faeResetCursorState();
    // Restart animation if needed
    if (!animationFrameId) {
      animate();
    }
  }
});

// Handle page visibility changes
document.addEventListener("visibilitychange", () => {
  if (!document.hidden) {
    // Page became visible, reset cursor state
    faeResetCursorState();
  }
});

// Clean up animation frame when page is hidden or unloaded
window.addEventListener("pagehide", () => {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
    animationFrameId = null;
  }
});

window.addEventListener("beforeunload", () => {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
    animationFrameId = null;
  }
});

