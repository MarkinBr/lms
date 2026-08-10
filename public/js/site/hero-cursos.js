(function(){
  var root = document.getElementById('heroCursos');
  if(!root) return;
  var slides = [].slice.call(root.querySelectorAll('.ch-slide'));
  var photos = [].slice.call(root.querySelectorAll('.ch-photo'));
  var tabs   = [].slice.call(root.querySelectorAll('.ch-tab'));
  var fills  = tabs.map(function(t){ return t.querySelector('.ch-tab__bar i'); });
  var DURATION = 6500;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var idx = 0, elapsed = 0, last = performance.now(), paused = false, inView = true;

  function setLinks(on){
    slides.forEach(function(s,i){
      var active = i === on;
      s.querySelectorAll('a,button').forEach(function(el){ el.tabIndex = active ? 0 : -1; });
      if('inert' in s) s.inert = !active;
    });
  }
  function show(n){
    idx = (n + slides.length) % slides.length;
    elapsed = 0; last = performance.now();
    slides.forEach(function(s,i){ s.classList.toggle('is-active', i === idx); s.setAttribute('aria-hidden', String(i !== idx)); });
    photos.forEach(function(p,i){ p.classList.toggle('is-active', i === idx); });
    tabs.forEach(function(t,i){ t.classList.toggle('is-active', i === idx); t.setAttribute('aria-selected', String(i === idx)); });
    fills.forEach(function(f){ f.style.width = '0%'; });
    if(reduce) fills[idx].style.width = '100%';
    setLinks(idx);
  }
  (function loop(now){
    var dt = now - last; last = now;
    if(!paused && inView && !reduce && !document.hidden){
      elapsed += dt;
      var p = Math.min(elapsed / DURATION, 1);
      fills[idx].style.width = (p * 100).toFixed(2) + '%';
      if(p >= 1) show(idx + 1);
    }
    requestAnimationFrame(loop);
  })(performance.now());
  root.querySelector('.ch-nav--prev').addEventListener('click', function(){ show(idx - 1); });
  root.querySelector('.ch-nav--next').addEventListener('click', function(){ show(idx + 1); });
  tabs.forEach(function(t,i){ t.addEventListener('click', function(){ show(i); }); });
  root.addEventListener('pointerenter', function(){ paused = true; });
  root.addEventListener('pointerleave', function(){ paused = false; });
  root.addEventListener('focusin',  function(){ paused = true; });
  root.addEventListener('focusout', function(e){ if(!root.contains(e.relatedTarget)) paused = false; });
  root.addEventListener('keydown', function(e){
    if(e.key === 'ArrowLeft')  show(idx - 1);
    if(e.key === 'ArrowRight') show(idx + 1);
  });
  var sx = 0, sy = 0;
  root.addEventListener('pointerdown', function(e){ if(e.pointerType === 'touch'){ sx = e.clientX; sy = e.clientY; } });
  root.addEventListener('pointerup', function(e){
    if(e.pointerType !== 'touch') return;
    var dx = e.clientX - sx, dy = e.clientY - sy;
    if(Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(dx < 0 ? idx + 1 : idx - 1);
  });
  if('IntersectionObserver' in window){
    new IntersectionObserver(function(en){ inView = en[0].isIntersecting; }, { threshold:.25 }).observe(root);
  }
  show(0);
})();
