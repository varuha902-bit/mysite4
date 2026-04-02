/**
 * Spark Effect - Electric spark trail with click burst
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const color = faeCursorSettings.color || "#00d4ff";
  const speed = faeCursorSettings.speed || "normal";
  
  // Speed multipliers for animation duration (longer = slower fade)
  const speedDurationMap = {
    slow: {
      spark: 1.2,    // Slower fade - 1.2s
      burst: 0.75    // Slower fade - 0.75s
    },
    normal: {
      spark: 0.8,    // Normal fade - 0.8s
      burst: 0.5     // Normal fade - 0.5s
    },
    fast: {
      spark: 0.5,    // Faster fade - 0.5s
      burst: 0.3     // Faster fade - 0.3s
    }
  };
  
  const speedConfig = speedDurationMap[speed] || speedDurationMap.normal;

  document.addEventListener("mousemove", (e) => {
    // Check if mouse is in scope before creating sparks
    if (typeof faeCursorShouldTrigger === 'function' && 
        !faeCursorShouldTrigger(e.clientX, e.clientY, e.target)) {
      return; // Don't create sparks if outside scope
    }
    
    for (let i = 0; i < 2; i++) {
      const spark = document.createElement("div");
      spark.className = "faespark";
      spark.style.cssText = `
        position: fixed;
        width: 3px;
        height: 3px;
        border-radius: 50%;
        background: ${color};
        opacity: 0.9;
        pointer-events: none;
        box-shadow: 0 0 10px ${color}, 0 0 20px ${color};
        z-index: 9999;
        left: ${e.clientX}px;
        top: ${e.clientY}px;
        animation: faeSparkMove ${speedConfig.spark}s ease-out forwards;
      `;
      spark.style.setProperty("--x", `${(Math.random() - 0.5) * 60}px`);
      spark.style.setProperty("--y", `${(Math.random() - 0.5) * 60}px`);
      document.body.appendChild(spark);
      setTimeout(() => spark.remove(), speedConfig.spark * 1000);
    }
  });

  document.addEventListener("click", (e) => {
    // Check if mouse is in scope before creating burst
    if (typeof faeCursorShouldTrigger === 'function' && 
        !faeCursorShouldTrigger(e.clientX, e.clientY, e.target)) {
      return; // Don't create burst if outside scope
    }
    
    const burst = document.createElement("div");
    burst.className = "faespark-burst";
    burst.style.cssText = `
      position: fixed;
      pointer-events: none;
      z-index: 9999;
      left: ${e.clientX}px;
      top: ${e.clientY}px;
    `;

    for (let i = 0; i < 10; i++) {
      const p = document.createElement("div");
      p.className = "faespark-particle";
      p.style.cssText = `
        position: absolute;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: ${color};
        box-shadow: 0 0 10px ${color};
        opacity: 1;
        animation: faeSparkBurst ${speedConfig.burst}s ease-out forwards;
      `;
      p.style.setProperty("--x", `${(Math.random() - 0.5) * 80}px`);
      p.style.setProperty("--y", `${(Math.random() - 0.5) * 80}px`);
      burst.appendChild(p);
    }

    document.body.appendChild(burst);
    setTimeout(() => burst.remove(), speedConfig.burst * 1000);
  });
})();

