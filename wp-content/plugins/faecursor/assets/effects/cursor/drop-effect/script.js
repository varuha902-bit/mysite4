(function() {
  'use strict';

  // Check for required settings
  if (typeof faeCursorSettings === 'undefined') {
    return;
  }

  const start = Date.now();
  const faeOriginPosition = { x: 0, y: 0 };

  // Global variables for icon settings
  let faeIconSvg = null;
  let faeIconColor = faeCursorSettings.color || "#ffcc00";
  let iconLoadPromise = null;

  const faeLast = {
    starTimestamp: start,
    starPosition: faeOriginPosition,
    mousePosition: faeOriginPosition,
  };

  const faeConfig = {
    starAnimationDuration: 1000,
    minimumTimeBetweenStars: 100,
    minimumDistanceBetweenStars: 20,
    sizes: faeCursorSettings.size ? [faeCursorSettings.size] : ["1rem", "1.5rem", "2rem"],
    spread: 20,
  };

  // Helper functions
  const faeRand = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
  const faeSelectRandom = (items) => items[faeRand(0, items.length - 1)];
  const faePx = (value) => value + "px";

  const faeCalcDistance = (a, b) => {
    const diffX = b.x - a.x;
    const diffY = b.y - a.y;
    return Math.sqrt(diffX * diffX + diffY * diffY);
  };

  const faeCalcElapsedTime = (start, end) => end - start;

  // Load icon SVG asynchronously
  const loadIconSvg = () => {
    if (iconLoadPromise) return iconLoadPromise;
    if (faeIconSvg) return Promise.resolve(faeIconSvg);
    if (!faeCursorSettings.assetsUrl || !faeCursorSettings.icon) {
      return Promise.resolve(null);
    }

    iconLoadPromise = fetch(faeCursorSettings.assetsUrl + 'ionicons/' + faeCursorSettings.icon)
      .then(response => response.ok ? response.text() : null)
      .catch(() => null);

    iconLoadPromise.then(svg => {
      if (svg) faeIconSvg = svg;
    });

    return iconLoadPromise;
  };

  // Preload icon on initialization
  loadIconSvg();

  const faeCreateStar = (position) => {
    const star = document.createElement("div");
    star.className = "faestar";

    const offsetX = faeRand(-faeConfig.spread, faeConfig.spread);
    const offsetY = faeRand(-faeConfig.spread, faeConfig.spread);

    star.style.left = faePx(position.x + offsetX);
    star.style.top = faePx(position.y + offsetY);
    star.style.width = faeSelectRandom(faeConfig.sizes);
    star.style.height = faeSelectRandom(faeConfig.sizes);
    star.style.fill = faeIconColor;
    star.style.position = "fixed";
    star.style.pointerEvents = "none";
    star.style.zIndex = "9999";

    if (faeIconSvg) {
      star.innerHTML = faeIconSvg;
      const svgEl = star.querySelector('svg');
      if (svgEl) {
        svgEl.style.width = '100%';
        svgEl.style.height = '100%';
      }
    } else {
      // Try to load if not already loaded
      loadIconSvg().then(svg => {
        if (svg && star.parentNode) {
          star.innerHTML = svg;
          const svgEl = star.querySelector('svg');
          if (svgEl) {
            svgEl.style.width = '100%';
            svgEl.style.height = '100%';
          }
        }
      });
    }

    document.body.appendChild(star);

    setTimeout(() => {
      if (star.parentNode) {
        star.parentNode.removeChild(star);
      }
    }, faeConfig.starAnimationDuration);
  };

  const faeUpdateLastStar = (position) => {
    faeLast.starTimestamp = Date.now();
    faeLast.starPosition = position;
  };

  const faeUpdateLastMousePosition = (position) => {
    faeLast.mousePosition = { x: position.x, y: position.y };
  };

  const faeAdjustLastMousePosition = (position) => {
    if (faeLast.mousePosition.x === 0 && faeLast.mousePosition.y === 0) {
      faeLast.mousePosition = { x: position.x, y: position.y };
    }
  };

  // Throttle mouse move handler using requestAnimationFrame
  let rafId = null;
  const faeHandleOnMove = (e) => {
    if (rafId) return;

    rafId = requestAnimationFrame(() => {
      rafId = null;
      const mousePosition = {
        x: e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0),
        y: e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0),
      };

      if (!mousePosition.x && !mousePosition.y) return;

      faeAdjustLastMousePosition(mousePosition);

      const now = Date.now();
      const distanceMoved = faeCalcDistance(faeLast.starPosition, mousePosition);
      const hasMovedFarEnough = distanceMoved >= faeConfig.minimumDistanceBetweenStars;
      const hasBeenLongEnough = faeCalcElapsedTime(faeLast.starTimestamp, now) > faeConfig.minimumTimeBetweenStars;

      if (hasMovedFarEnough || hasBeenLongEnough) {
        // Check if mouse is in scope before creating effect
        if (typeof faeCursorShouldTrigger === 'function' && 
            !faeCursorShouldTrigger(mousePosition.x, mousePosition.y, e.target)) {
          return; // Don't create effect if outside scope
        }
        faeCreateStar(mousePosition);
        faeUpdateLastStar(mousePosition);
      }

      faeUpdateLastMousePosition(mousePosition);
    });
  };

  // Add event listeners with passive option for better performance
  const passiveOpt = { passive: true };
  if (window.addEventListener) {
    window.addEventListener('mousemove', faeHandleOnMove, passiveOpt);
    window.addEventListener('touchmove', faeHandleOnMove, passiveOpt);
    document.body.addEventListener('mouseleave', () => {
      faeUpdateLastMousePosition({ x: 0, y: 0 });
    });
  } else {
    // Fallback for older browsers
    window.onmousemove = faeHandleOnMove;
    window.ontouchmove = faeHandleOnMove;
    document.body.onmouseleave = () => faeUpdateLastMousePosition({ x: 0, y: 0 });
  }
})();
