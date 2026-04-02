/**
 * Morph Grid Effect
 */
(function () {
  "use strict";

  if (typeof faeCursorSettings === "undefined") return;

  const canvas = document.createElement('canvas');
  canvas.id = 'fae-morph-grid';
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
  const color = faeCursorSettings.color || '#78c8ff';
  
  // Helper function to convert hex to rgba
  function hexToRgba(hex, alpha) {
    if (hex.length === 4) {
      hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
    }
    const r = parseInt(hex.slice(1, 3), 16) || 120;
    const g = parseInt(hex.slice(3, 5), 16) || 200;
    const b = parseInt(hex.slice(5, 7), 16) || 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  const gap=28, cols=Math.ceil(canvas.width/gap), rows=Math.ceil(canvas.height/gap);
  const pts=[];
  for(let y=0;y<=rows;y++){
    for(let x=0;x<=cols;x++){
      const bx = x*gap, by = y*gap;
      pts.push({bx,by,x:bx,y:by,vx:0,vy:0});
    }
  }

  function update(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    pts.forEach(p=>{
      if(mouse.x){
        const dx = p.bx - mouse.x, dy = p.by - mouse.y;
        const d = Math.hypot(dx,dy);
        if(d < 140){
          const away = (140 - d)/140;
          p.vx += (dx/d)*away*6 + (Math.random()-0.5)*0.6;
          p.vy += (dy/d)*away*6 + (Math.random()-0.5)*0.6;
        } else {
          p.vx += (p.bx - p.x) * 0.02;
          p.vy += (p.by - p.y) * 0.02;
        }
      } else {
        p.vx += (p.bx - p.x) * 0.02;
        p.vy += (p.by - p.y) * 0.02;
      }
      p.vx *= 0.88; p.vy *= 0.88;
      p.x += p.vx; p.y += p.vy;
    });

    ctx.strokeStyle = hexToRgba(color, 0.08);
    ctx.lineWidth = 1;
    for(let i=0;i<pts.length;i++){
      const p=pts[i];
      const col = Math.floor(i/(cols+1));
      const idxR = i+1, idxD = i+(cols+1);
      if((i+1) % (cols+1) !== 0){
        const r = pts[idxR];
        ctx.beginPath(); ctx.moveTo(p.x,p.y); ctx.lineTo(r.x,r.y); ctx.stroke();
      }
      if(idxD < pts.length){
        const d = pts[idxD];
        ctx.beginPath(); ctx.moveTo(p.x,p.y); ctx.lineTo(d.x,d.y); ctx.stroke();
      }
    }
    pts.forEach(p=>{ ctx.beginPath(); ctx.fillStyle=hexToRgba(color, 0.9); ctx.arc(p.x,p.y,2.2,0,Math.PI*2); ctx.fill(); });

    requestAnimationFrame(update);
  }
  update();
})();

