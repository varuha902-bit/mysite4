/**
 * Color Borrower Effect
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const canvas = document.createElement('canvas');
  canvas.id = 'fae-color-borrower';
  canvas.className = 'fae-particle-canvas';
  document.body.appendChild(canvas);
  
  const ctx = canvas.getContext('2d');
  function R(){canvas.width=innerWidth;canvas.height=innerHeight;}addEventListener('resize',R);R();
  
  // Get interactive cursor setting - only enable if explicitly true
  // Check for boolean true, string '1', or number 1
  const interactiveCursor = faeCursorSettings.interactiveCursor === true || 
                            faeCursorSettings.interactiveCursor === '1' || 
                            faeCursorSettings.interactiveCursor === 1;
  
  let mouse={x:null,y:null};
  if (interactiveCursor) {
    addEventListener('mousemove',e=>{
      if (typeof faeCursorShouldTrigger === 'function' && 
          !faeCursorShouldTrigger(e.clientX, e.clientY, e.target)) {
        mouse.x = null;
        mouse.y = null;
        return;
      }
      mouse.x=e.clientX;mouse.y=e.clientY;
    });
    addEventListener('mouseout',()=>{mouse.x=null;mouse.y=null});
  }

  // Get color from settings
  const baseColor = faeCursorSettings.color || '#7AD6FF';
  
  // Generate palette based on base color with variations
  function generatePalette(base) {
    // Convert hex to RGB
    const hex = base.length === 4 ? '#' + base[1] + base[1] + base[2] + base[2] + base[3] + base[3] : base;
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    
    // Generate variations by adjusting brightness and saturation
    const variations = [
      { r: Math.min(255, r + 30), g: Math.min(255, g + 20), b: Math.min(255, b + 10) }, // Lighter
      { r: Math.max(0, r - 20), g: Math.max(0, g - 10), b: Math.min(255, b + 40) }, // More blue
      { r: Math.min(255, r + 20), g: Math.min(255, g + 40), b: Math.max(0, b - 20) }, // More green
      { r: Math.min(255, r + 40), g: Math.max(0, g - 20), b: Math.min(255, b + 20) }, // More pink
      { r: Math.max(0, r - 10), g: Math.max(0, g - 10), b: Math.min(255, b + 30) } // More purple
    ];
    
    return variations.map(v => {
      const toHex = (n) => {
        const hex = Math.round(n).toString(16);
        return hex.length === 1 ? '0' + hex : hex;
      };
      return '#' + toHex(v.r) + toHex(v.g) + toHex(v.b);
    });
  }
  
  const palette = generatePalette(baseColor);
  const N=180,parts=[];
  for(let i=0;i<N;i++){
    parts.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,baseX:0,baseY:0,size:1.8,c:palette[i%palette.length]});
    parts[i].baseX=parts[i].x; parts[i].baseY=parts[i].y;
  }

  function update(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    parts.forEach(p=>{
      let dx = p.x - (mouse.x ?? p.x), dy = p.y - (mouse.y ?? p.y);
      let d = Math.hypot(dx,dy);
      if(mouse.x && d < 120){
        const force = (120-d)/120;
        p.x += (dx/d)*force*6 + (Math.random()-0.5)*0.8;
        p.y += (dy/d)*force*6 + (Math.random()-0.5)*0.8;
      } else {
        p.x += (p.baseX - p.x)*0.02; p.y += (p.baseY - p.y)*0.02;
      }
      const borrow = mouse.x && d<140 ? Math.min(1,(140-d)/140) : 0;
      const base = p.c;
      ctx.beginPath();
      ctx.fillStyle = borrow ? `rgba(255,255,255,${0.5*borrow})` : base;
      ctx.arc(p.x,p.y,p.size + 0.9*borrow,0,Math.PI*2); ctx.fill();
      if(borrow>0) { ctx.beginPath(); ctx.fillStyle=`rgba(255,255,255,${0.06*borrow})`; ctx.arc(p.x,p.y,10*borrow,0,Math.PI*2); ctx.fill(); }
    });
    requestAnimationFrame(update);
  }
  update();
})();

