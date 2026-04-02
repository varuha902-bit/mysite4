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
let isMouseInViewport = true; // Track if mouse is inside viewport
let faeNeedsInstantSnap = false; // Snap circles to mouse on next move (after back/visibility restore)

// Create the main circle for the cursor
const faeCreateMainCircle = (x, y) => {
  if (!faeMainCircle) {
    faeMainCircle = document.createElement("div");
    faeMainCircle.className = "faecircle";
    faeMainCircle.style.background = faeCircleColor;
    // Transition is set in CSS for better performance
    document.body.appendChild(faeMainCircle);
  }
  faeMainCircle.style.left = `${x}px`;
  faeMainCircle.style.top = `${y}px`;
};

// Create the duplicate circle
const faeCreateDuplicateCircle = () => {
  if (!faeDuplicate) {
    faeDuplicate = document.createElement("div");
    faeDuplicate.className = "faeduplicate";
    faeDuplicate.style.background = faeCircleColor;
    // Transition is set in CSS for better performance
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
  // Don't update if mouse is outside viewport or elements not initialized
  if (!faeDuplicate || !isInitialized || !isMouseInViewport) return;

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
  // Mark mouse as inside viewport (mousemove only fires when inside)
  isMouseInViewport = true;

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
    // Duplicate should use 0.3 opacity when visible (from CSS), 0 when hidden
    faeDuplicate.style.opacity = inScope ? "0.3" : "0";
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
  // Mark mouse as outside viewport
  isMouseInViewport = false;

  // Hide circles smoothly when mouse leaves viewport
  // Use requestAnimationFrame to ensure transition is applied
  requestAnimationFrame(() => {
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
  });
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
    // Mark mouse as inside viewport
    isMouseInViewport = true;

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

      // Smoothly show circles (duplicate uses 0.3 opacity from CSS)
      // Use requestAnimationFrame to ensure transition is applied
      requestAnimationFrame(() => {
        faeMainCircle.style.opacity = "1";
        // Always keep pointer-events: none to allow clicks on links/buttons
        faeMainCircle.style.pointerEvents = "none";
        faeDuplicate.style.opacity = "0.3";
        // Always keep pointer-events: none to allow clicks on links/buttons
        faeDuplicate.style.pointerEvents = "none";
      });
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

