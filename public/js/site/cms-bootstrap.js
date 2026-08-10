/* Bootstrap conecta o site ao painel admin (localStorage) e/ou a um JSON embutido.
   ORDEM DE PRIORIDADE (o último vence):
     1. window.CETSADP_CONTENT (JSON embutido no HTML — modo standalone)
     2. localStorage["cetsadp_admin_v3"]  (edição direta no painel v3)
     3. localStorage["cetsadp_admin_v4"]  (painel v4 ampliado)
*/
(function(){
  const KEYS = ["cetsadp_admin_v3","cetsadp_admin_v4"];
  let state = { texts:{}, images:{}, logos:{}, badges:{}, wallpapers:{}, palette:{}, meta:{} };
  function merge(a,b){ if(!b) return; for(const k in b){ if(b[k] && typeof b[k]==='object' && !Array.isArray(b[k])){ a[k]=a[k]||{}; merge(a[k], b[k]); } else if(b[k]!==undefined) a[k]=b[k]; } }
  for(const k of KEYS){
    try{
      const raw = localStorage.getItem(k);
      if(!raw) continue;
      const d = JSON.parse(raw);
      // Estrutura painel: {saved:{texts,images,logos,badges,wallpapers,palette}}
      const s = d.saved || d;
      merge(state, {
        texts: s.texts||{}, images: s.images||{}, logos: s.logos||{},
        badges: s.badges||{}, wallpapers: s.wallpapers||{}, palette: s.palette||{}
      });
    }catch(e){}
  }
  if(window.CETSADP_CONTENT){ merge(state, window.CETSADP_CONTENT); }
  window.CMS = state;

  // Aplica paleta (CSS vars)
  const P = state.palette||{};
  const styleEl = document.getElementById('cms-palette-vars');
  if(styleEl){
    let css = ':root{';
    const map = {navy:'--navy',navyDeep:'--navy-deep',navySoft:'--navy-soft',navyLine:'--navy-line',
      gold:'--gold',gold2:'--gold-2',gold3:'--gold-3',goldDeep:'--gold-deep',
      cream:'--cream',paper:'--paper',ink:'--ink',body:'--body',muted:'--muted',line:'--line'};
    for(const k in map){ if(P[k]) css += map[k]+':'+P[k]+';'; }
    css += '}';
    styleEl.textContent = css;
  }

  // Ajusta favicon
  if(state.logos && state.logos['favicon']){
    let ic = document.querySelector("link[rel~='icon']");
    if(!ic){ ic=document.createElement('link'); ic.rel='icon'; document.head.appendChild(ic); }
    ic.href = state.logos['favicon'];
  }
})();

/* Utilitário global: se houver upload de logo, devolve <img>; senão devolve o SVG default. */
function cmsLogo(key, defaultSVG, extraClass){
  const url = (window.CMS && window.CMS.logos && window.CMS.logos[key]) || '';
  const cls = extraClass ? ` class="${extraClass}"` : '';
  if(url){
    return `<img${cls || ' class="logo-mark"'} src="${url}" alt="${key}" style="object-fit:contain">`;
  }
  return defaultSVG;
}
function cmsText(key, def){
  const v = (window.CMS && window.CMS.texts && window.CMS.texts[key]);
  return (v!==undefined && v!==null && v!=='') ? v : def;
}
function cmsImg(key, def){
  const v = (window.CMS && window.CMS.images && window.CMS.images[key]);
  return (v!==undefined && v!==null && v!=='') ? v : def;
}
