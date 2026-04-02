let faeLastPosition = null; // Initially set to null so we don't draw any lines until the mouse moves

// Get speed setting
const speed = (typeof faeCursorSettings !== 'undefined' && faeCursorSettings.speed) ? faeCursorSettings.speed : "normal";

// Speed-based duration map (how long the line stays visible)
const speedDurationMap = {
  slow: 1500,    // Slower fade - lines stay longer
  normal: 1000,  // Normal fade
  fast: 600      // Faster fade - lines disappear quicker
};

const faeConfig = {
  lineThickness: 2, // Thickness of the line
  lineDuration: speedDurationMap[speed] || speedDurationMap.normal, // Duration for which the line is visible (based on speed)
  minDistance: 5, // Minimum distance between points to draw the line
  lineColor: (typeof faeCursorSettings !== 'undefined' && faeCursorSettings.color) ? faeCursorSettings.color : "#667eea",
};

const faeAppendElement = (element) => document.body.appendChild(element);

const faeCreateLineSegment = (start, end) => {
  const line = document.createElement("div");

  line.className = "faeline";
  line.style.height = `${faeConfig.lineThickness}px`;

  // Calculate distance and angle
  const deltaX = end.x - start.x;
  const deltaY = end.y - start.y;
  const distance = Math.sqrt(deltaX ** 2 + deltaY ** 2);
  const angle = Math.atan2(deltaY, deltaX) * (180 / Math.PI);

  // Set the line's position and size
  line.style.width = `${distance}px`; // Line's length is the distance between points
  line.style.left = `${start.x}px`; // Start position (X)
  line.style.top = `${start.y}px`; // Start position (Y)
  line.style.position = 'fixed';
  line.style.transform = `rotate(${angle}deg)`; // Rotate the line to the correct angle
  line.style.background = faeConfig.lineColor; // Use configured color

  // Add the line to the document
  faeAppendElement(line);

  // Remove the line after animation duration
  setTimeout(() => {
    line.style.opacity = 0; // Start fading out
    setTimeout(() => {
      if (document.body.contains(line)) {
        document.body.removeChild(line);
      }
    }, faeConfig.lineDuration);
  }, faeConfig.lineDuration);
};

const faeHandleOnMove = (e) => {
  const mousePosition = {
    x: e.clientX, // Use clientX for viewport-relative position (works with fixed positioning)
    y: e.clientY, // Use clientY for viewport-relative position (works with fixed positioning)
  };

  // Initialize faeLastPosition if it was reset or when mouse moves for the first time
  if (faeLastPosition === null) {
    faeLastPosition = { ...mousePosition };
    return;
  }

  // Only draw if the mouse moved more than the minimum distance
  if (
    Math.sqrt(
      (mousePosition.x - faeLastPosition.x) ** 2 +
        (mousePosition.y - faeLastPosition.y) ** 2
    ) > faeConfig.minDistance
  ) {
    // Check if mouse is in scope before creating effect
    if (typeof faeCursorShouldTrigger === 'function' && 
        !faeCursorShouldTrigger(mousePosition.x, mousePosition.y, e.target)) {
      faeLastPosition = mousePosition; // Update position but don't create line
      return;
    }
    faeCreateLineSegment(faeLastPosition, mousePosition);
    faeLastPosition = mousePosition;
  }
};

const faeResetLastPosition = () => {
  faeLastPosition = null; // Reset the last known position
};

window.onmousemove = (e) => faeHandleOnMove(e);
window.ontouchmove = (e) => faeHandleOnMove(e.touches[0]);

// Reset last position when the user scrolls
window.onscroll = faeResetLastPosition;

// Also reset position after the window loses and regains focus
window.onblur = faeResetLastPosition;
window.onfocus = faeResetLastPosition;
