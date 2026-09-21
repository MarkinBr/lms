const ICONS = {
  book:'<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
  bookopen:'<path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>',
  users:'<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
  user:'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  userplus:'<path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
  calendar:'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  clock:'<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
  star:'<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
  award:'<circle cx="12" cy="8" r="6"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/>',
  monitor:'<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
  laptop:'<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>',
  check:'<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>',
  cross:'<path d="M12 2v20M7 7h10"/>',
  hand:'<path d="M18 11V6a2 2 0 00-4 0v5M14 10V4a2 2 0 00-4 0v6M10 10.5V6a2 2 0 00-4 0v8"/><path d="M18 8a2 2 0 114 0v6a8 8 0 01-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 012.83-2.82L7 15"/>',
  crown:'<path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7z"/><path d="M3 20h18"/>',
  edit:'<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>',
  dollar:'<path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>',
  target:'<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
  cap:'<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/>',
  phone:'<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0122 16.92z"/>',
  mail:'<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/>',
  pin:'<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
  shield:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  lock:'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
  chat:'<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
  arrow:'<path d="M9 18l6-6-6-6"/>',
  chevronleft:'<path d="M15 18l-6-6 6-6"/>',
  chevrondown:'<path d="M6 9l6 6 6-6"/>',
  fb:'<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>',
  ig:'<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zM17.5 6.5h.01"/>',
  yt:'<path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"/><path d="M9.75 15.02l5.75-3.27-5.75-3.27v6.54z"/>',
  file:'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/>',
  clipboard:'<rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 4a1 1 0 011-1h4a1 1 0 011 1v1H9z"/><path d="M9 12l2 2 4-4"/>',
  trophy:'<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0z"/><path d="M17 5h3v2a3 3 0 01-3 3M7 5H4v2a3 3 0 003 3"/>',
  headset:'<path d="M4 14v-2a8 8 0 0116 0v2"/><rect x="2" y="14" width="4" height="6" rx="1.4"/><rect x="18" y="14" width="4" height="6" rx="1.4"/><path d="M20 19a3 3 0 01-3 3h-3"/>',
  eye:'<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>',
  eyeoff:'<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.4 18.4 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19M1 1l22 22"/>',
  key:'<circle cx="8" cy="15" r="4"/><path d="M10.8 12.2L21 2M16 7l3 3M14 9l2 2"/>',
  bell:'<path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/>',
  chartbar:'<path d="M3 3v18h18"/><path d="M8 17v-5M13 17V8M18 17v-8"/>',
  gear:'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>',
  logout:'<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>',
  shieldcheck:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
  shielduser:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="10" r="2.6"/><path d="M8.4 15.5a3.6 3.6 0 017.2 0"/>',
  xcircle:'<circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/>',
  checkcircle:'<circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/>',
  home:'<path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/>',
  help:'<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 015.8 1c0 2-3 2.5-3 4"/><path d="M12 17h.01"/>',
  more:'<circle cx="12" cy="5" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="12" cy="19" r="1.4"/>',
  pie:'<path d="M21.21 15.89A10 10 0 118 2.83"/><path d="M22 12A10 10 0 0012 2v10z"/>',
  gift:'<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v9h14v-9"/><path d="M12 8S11 3 8.5 3 6 6 6 6s2.5 2 6 2zM12 8s1-5 3.5-5S18 6 18 6s-2.5 2-6 2z"/>',
  wallet:'<path d="M20 12V8H6a2 2 0 010-4h12v4"/><path d="M4 6v12a2 2 0 002 2h14v-4"/><path d="M18 12a2 2 0 000 4h4v-4z"/>',
  swap:'<path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/>',
  minus:'<circle cx="12" cy="12" r="10"/><path d="M8 12h8"/>',
  barcode:'<path d="M3 5v14M7 5v14M11 5v14M15 5v14M19 5v14"/>',
  bank:'<path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/>',
  download:'<path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/>',
  folder:'<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>',
  info:'<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
  hourglass:'<path d="M6 2h12M6 22h12M6 2c0 5 4 7 6 10 2-3 6-5 6-10M6 22c0-5 4-7 6-10 2 3 6 5 6 10"/>',
  filecheck:'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M9 15l2 2 4-4"/>',
  rosette:'<circle cx="12" cy="9" r="6"/><path d="M9 14l-1.5 7L12 19l4.5 2L15 14"/><path d="M9 9l2 2 4-4"/>',
  send:'<path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>',
  paperclip:'<path d="M21.44 11.05l-9.19 9.19a5 5 0 01-7.07-7.07l9.19-9.19a3 3 0 014.24 4.24l-9.2 9.19a1 1 0 01-1.41-1.41l8.49-8.48"/>',
  smile:'<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01M15 9h.01"/>',
  sun:'<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
  moon:'<path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/>',
  sliders:'<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
  palette:'<circle cx="13.5" cy="6.5" r="1.2"/><circle cx="17.5" cy="10.5" r="1.2"/><circle cx="8.5" cy="7.5" r="1.2"/><circle cx="6.5" cy="12.5" r="1.2"/><path d="M12 2a10 10 0 000 20c1.1 0 2-.9 2-2 0-.5-.2-1-.5-1.3-.3-.4-.5-.8-.5-1.2 0-1.1.9-2 2-2h2.3A4.5 4.5 0 0022 11c0-5-4.5-9-10-9z"/>',
  cloud:'<path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"/>',
  cloudup:'<path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"/><path d="M12 16v-5M9.5 13l2.5-2.5L14.5 13"/>',
  share2:'<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
  building:'<rect x="4" y="2" width="16" height="20" rx="1"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
  plug:'<path d="M12 22v-5M9 7V2M15 7V2M6 7h12v3a6 6 0 01-12 0z"/>',
  database:'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0018 0V5M3 12a9 3 0 0018 0"/>',
  archive:'<rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 002 2h12a2 2 0 002-2V8M10 12h4"/>',
  trash:'<path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>',
  layers:'<path d="M12 2l9 5-9 5-9-5 9-5z"/><path d="M3 12l9 5 9-5M3 17l9 5 9-5"/>',
  pluscircle:'<circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/>',
  plus:'<path d="M12 5v14M5 12h14"/>',
  search:'<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>'
};
function svg(name,cls){return `<svg class="${cls||''}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONS[name]||''}</svg>`;}
function paintIcons(root){
  root.querySelectorAll('[data-ic]').forEach(el=>{if(el.dataset.painted)return;if(el.tagName.toLowerCase()==='svg'){const cls=el.getAttribute('class')||'';el.outerHTML=svg(el.dataset.ic,cls);}else{el.insertAdjacentHTML('afterbegin', svg(el.dataset.ic));}});
  root.querySelectorAll('[data-icm]').forEach(el=>{if(el.dataset.painted)return;el.insertAdjacentHTML('afterbegin', svg(el.dataset.icm));});
  root.querySelectorAll('[data-ic],[data-icm]').forEach(el=>el.dataset.painted=1);
}
const DEFAULT_LOGO_MARK = `<img class="logo-mark" src="https://cetsadp.com.br/images/cetsadp2.png" alt="CETSADP" style="width:72px;height:72px;object-fit:contain">`;
const LOGO_MARK = cmsLogo("logo.mark", DEFAULT_LOGO_MARK, "logo-mark");
const DEFAULT_SUB_LOGO = `<img src="https://cetsadp.com.br/images/ieadp2.png" alt="IEADP" style="width:42px;height:42px;object-fit:contain">`;
const SUB_LOGO = cmsLogo("logo.sub", DEFAULT_SUB_LOGO);
const SEAL_TEXT = cmsText("seal.texto","IGREJA EVANGÉLICA • ASSEMBLEIA DE DEUS •");
const DEFAULT_SEAL = ``;
const SEAL = cmsLogo("logo.seal", DEFAULT_SEAL, "seal");
const DEFAULT_SHIELD = `<svg viewBox="0 0 80 92" fill="none"><path d="M22 30c-6-2-12 2-15 9 6-3 12-2 16 3M20 44c-5 0-9 4-11 9 5-2 10-1 13 3" stroke="#c08a23" stroke-width="2.4" stroke-linecap="round"/><path d="M58 30c6-2 12 2 15 9-6-3-12-2-16 3M60 44c5 0 9 4 11 9-5-2-10-1-13 3" stroke="#c08a23" stroke-width="2.4" stroke-linecap="round"/><path d="M40 8 L62 16 V40 C62 58 52 70 40 80 C28 70 18 58 18 40 V16 Z" fill="url(#gg)" stroke="#9c6f15" stroke-width="2"/><path d="M40 26 V54 M30 35 H50" stroke="#0b2350" stroke-width="5" stroke-linecap="round"/><defs><linearGradient id="gg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e7c468"/><stop offset="1" stop-color="#b9821a"/></linearGradient></defs></svg>`;
const SHIELD_GOLD = cmsLogo("logo.shield", DEFAULT_SHIELD);
const SHIELD_TXT = `<span>${cmsText("shield.linha1","ENSINO QUE TRANSFORMA")}<em>${cmsText("shield.linha2","VIDAS E MINISTÉRIOS")}</em></span>`;
const PH_LOGO_HTML = `${LOGO_MARK}<span class="logo-txt" style="max-width:none"><b style="font-size:2.1rem">${cmsText("brand.nome","CETSADP")}</b><small style="max-width:230px;font-size:.62rem">${cmsText("brand.tagline","Centro Educacional, Teológico e Social da Igreja Evangélica Assembleia de Deus em Paracambi")}</small></span>`;
const AS_BRAND_HTML = `${LOGO_MARK}<b>${cmsText("brand.nome","CETSADP")}</b><small>${cmsText("brand.tagline","Centro Educacional, Teológico e Social da Igreja Evangélica Assembleia de Deus em Paracambi")}</small>`;
document.getElementById('topbar-root').innerHTML = `<div class="topbar"><div class="wrap"><div class="tb-left"><span>${svg('pin')} ${cmsText("topbar.cidade","Paracambi - RJ")}</span><a href="tel:${cmsText("contato.telefone_link","2126833271")}">${svg('phone')} ${cmsText("contato.telefone","(21) 2683-3271")}</a><a href="mailto:${cmsText("contato.email","contato@cetsadp.com.br")}">${svg('mail')} ${cmsText("contato.email","contato@cetsadp.com.br")}</a></div><div class="tb-right"><a href="#" data-toast="Redirecionando para validação...">${svg('shield')} ${cmsText("topbar.validar","Validar Documento")}</a><a data-go="login-aluno">${svg('lock')} ${cmsText("topbar.portal_aluno","Portal do Aluno")}</a></div></div></div>`;
document.getElementById('header-root').innerHTML = `<div class="wrap">
  <a class="brand-cluster" data-go="home" style="cursor:pointer">
    <span class="brand-logo">
      ${LOGO_MARK}
      <span class="logo-txt">
        <b>${cmsText("brand.nome","CETSADP")}</b>
        <small>${cmsText("brand.tagline","Centro Educacional, Teológico e Social da Igreja Evangélica Assembleia de Deus em Paracambi")}</small>
      </span>
    </span>
    <span class="brand-divider"></span>
    <img src="https://cetsadp.com.br/images/ieadp2.png" alt="IEADP" style="width:56px;height:56px;object-fit:contain">
  </a>
  <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
  <ul class="nav" id="nav">
    <li><a data-go="home" data-nav="home">${cmsText("menu.inicio","INÍCIO")}</a></li>
    <li><a data-go="sobre" data-nav="sobre">${cmsText("menu.sobre","SOBRE NÓS")}</a></li>
    <li class="dd">
      <a class="dd-toggle" data-nav="cursos">${cmsText("menu.cursos","CURSOS")} ${svg('chevrondown')}</a>
      <div class="dropdown">
        <a data-go="teologia"><b>${cmsText("teologia.nome","Curso Médio em Teologia")}</b><small>Formação bíblica completa · 3 anos</small></a>
        <a data-go="diaconato"><b>${cmsText("diaconato.nome","Habilitação ao Diaconato")}</b><small>${cmsText("menu.diaconato_sub","Sirva com excelência · 7 meses")}</small></a>
        <a data-go="lideranca"><b>${cmsText("lideranca.nome","Liderança Cristã Ministerial")}</b><small>${cmsText("menu.lideranca_sub","Forme líderes · 2 anos")}</small></a>
      </div>
    </li>
    <li><a data-go="professores" data-nav="prof">${cmsText("menu.professores","PROFESSORES")}</a></li>
    <li><a data-go="eventos" data-nav="ev">${cmsText("menu.eventos","EVENTOS")}</a></li>
    <li><a data-go="noticias" data-nav="not">${cmsText("menu.noticias","NOTÍCIAS")}</a></li>
    <li><a data-go="contato" data-nav="cont">${cmsText("menu.contato","CONTATO")}</a></li>
    <li><a data-go="acompanhar-inscricao" data-nav="acomp">${cmsText("menu.acompanhar","ACOMPANHAR INSCRIÇÃO")}</a></li>
    <li class="nav-cta-wrap"><button class="btn btn-outline nav-cta" data-go="inscricao">${cmsText("menu.cta","MATRÍCULAS ABERTAS")}</button></li>
  </ul>
</div>`;
function buildFooter(variant){
  const supMap={site:[['Portal do Aluno','data-go="login-aluno"'],['Acompanhar Inscrição','data-go="acompanhar-inscricao"'],['Termos e Condições','data-go="termos-e-condicoes"'],['Política de Privacidade','data-go="politica-de-privacidade"'],['Perguntas Frequentes','href="#"'],['Fale Conosco','data-go="contato"']],secretaria:[['Perguntas Frequentes','href="#"'],['Tutoriais','href="#"'],['Suporte Técnico','href="#"'],['Fale Conosco','href="#"']],aluno:[['Perguntas Frequentes','href="#"'],['Fale Conosco','href="#"'],['Suporte Técnico','href="#"'],['Tutoriais','href="#"']]};
  const sup=(supMap[variant]||supMap.site).map(([t,a])=>`<li><a ${a}>${t}</a></li>`).join('');
  return `<footer class="footer"><div class="cross-bg">✝</div><div class="wrap footer-top"><div class="fbrand"><div style="display:flex;align-items:center;gap:10px">${LOGO_MARK.replace('class="logo-mark"','class="logo-mark" style="width:54px;height:54px"')}<b>CETSADP</b></div><p>Centro Educacional, Teológico e Social da Igreja Evangélica Assembleia de Deus em Paracambi.</p><div class="socials"><a href="#" aria-label="Facebook">${svg('fb')}</a><a href="#" aria-label="Instagram">${svg('ig')}</a><a href="#" aria-label="YouTube">${svg('yt')}</a></div></div><div><h5>Instituição</h5><ul><li><a data-go="sobre">Sobre Nós</a></li><li><a href="#">Missão e Visão</a></li><li><a href="#">História</a></li><li><a href="#">Diretoria</a></li><li><a href="#">Estrutura</a></li></ul></div><div><h5>Cursos</h5><ul><li><a data-go="teologia">Curso Médio em Teologia</a></li><li><a data-go="diaconato">Habilitação ao Diaconato</a></li><li><a data-go="lideranca">Liderança Cristã Ministerial</a></li><li><a href="#">Calendário Acadêmico</a></li><li><a href="#">Processo Seletivo</a></li></ul></div><div><h5>Suporte</h5><ul>${sup}</ul></div><div class="fcontact"><h5>Contato</h5><div>${svg('pin')}<span>Rua Afonso Franco, 15<br>Centro - Paracambi/RJ<br>CEP: 26600-000</span></div><div>${svg('phone')}<a href="tel:2126833271">(21) 2683-3271</a></div><div>${svg('mail')}<a href="mailto:contato@cetsadp.com.br">contato@cetsadp.com.br</a></div></div></div><div class="footer-bottom"><div class="wrap"><span>© 2026 CETSADP - Todos os direitos reservados.</span><span><a data-go="termos-e-condicoes" style="cursor:pointer">Termos e Condições</a> · <a data-go="politica-de-privacidade" style="cursor:pointer">Política de Privacidade</a></span><span>Desenvolvido com <span class="heart">♥</span> para a obra do Senhor.</span></div></div></footer>`;
}
document.getElementById('footer-root').innerHTML=buildFooter('site');
document.querySelectorAll('.footer-mount').forEach(m=>{m.innerHTML=buildFooter(m.dataset.fv);});
document.querySelectorAll('.ph-logo').forEach(el=>el.innerHTML=PH_LOGO_HTML);
document.querySelectorAll('.ph-seal').forEach(el=>el.innerHTML=SHIELD_GOLD+SHIELD_TXT);
document.querySelectorAll('[data-shield]').forEach(el=>el.innerHTML=SHIELD_GOLD+SHIELD_TXT);
const SEC_NAV=[['Dashboard','home','dash-secretaria'],['Alunos','users','sec-alunos'],['Acadêmico','cap','sec-academico'],['Financeiro','wallet','sec-financeiro'],['Secretaria','edit','sec-secretaria'],['Relatórios','chartbar','sec-relatorios'],['Documentos','file','sec-documentos'],['Certificados','award','sec-certificados'],['Mensagens','mail','sec-mensagens',2],['Configurações','gear','sec-config']];
const ALU_NAV=[['Início','home','dash-aluno'],['Meus Cursos','book',null,'Meus Cursos'],['Aulas','monitor',null,'Aulas'],['Avaliações','edit',null,'Avaliações'],['Atividades','clipboard',null,'Atividades'],['Biblioteca','bookopen',null,'Biblioteca'],['Certificados','award',null,'Certificados'],['Fórum','chat',null,'Fórum'],['Mensagens','mail',null,'Mensagens',2],['Suporte','help',null,'Suporte']];
const ALU_CONTA=[['Perfil','user',null,'Perfil'],['Dados Pessoais','users',null,'Dados Pessoais'],['Alterar Senha','lock',null,'Alterar Senha'],['Sair','logout','login-aluno']];
function navHTML(items,active){return items.map(([label,icon,goP,toastP,bdg])=>{const cls=label===active?' class="active"':'';const attr=goP?`data-go="${goP}"`:`data-toast="${toastP||label} (demonstração)"`;return `<a${cls} ${attr} data-icm="${icon}">${label}${bdg?`<span class="bdg">${bdg}</span>`:''}</a>`;}).join('');}
function buildSide(el){const type=el.dataset.side,active=el.dataset.active;let html=`<div class="as-brand" data-go="home" style="cursor:pointer">${AS_BRAND_HTML}</div><div class="as-label">NAVEGAÇÃO</div><nav class="as-nav">${navHTML(type==='secretaria'?SEC_NAV:ALU_NAV,active)}</nav>`;if(type==='aluno')html+=`<div class="as-label">CONTA</div><nav class="as-nav">${navHTML(ALU_CONTA,'')}</nav>`;html+=`<div class="as-card" data-shield-sm>${SHIELD_GOLD}<span>ENSINO QUE TRANSFORMA<br>VIDAS E MINISTÉRIOS</span></div>`;if(type==='secretaria')html+=`<div class="as-help"><span class="ic" data-ic="headset"></span><div><b>Precisa de ajuda?</b><small>Suporte disponível</small></div></div>`;el.innerHTML=html;}
function buildTop(el){const d=el.dataset;const left=d.crumb?`<div class="hello"><div class="crumbline"><a data-go="home">Início</a>${svg('arrow')}<span style="color:var(--gold-deep)">${d.crumb}</span></div><h1 class="serif">${d.title}</h1><div class="bar"></div><p>${d.desc}</p></div>`:`<div class="hello"><small>${d.hello}</small><h1 class="serif">${d.name}</h1><div class="bar"></div><p>${d.msg}</p></div>`;el.innerHTML=`<div class="ghost"><img src="https://images.unsplash.com/photo-1438032005730-c779502df39b?w=700&q=60" alt="" onerror="this.parentElement.style.display='none'"></div><button class="app-burger" aria-label="Menu" data-ic="home"></button>${left}<div class="right"><div class="top-acts"><button class="bell" data-toast="3 novas notificações" data-ic="bell"><span class="bdg">3</span></button><div class="avatar"><span class="av-pic"><span class="ph">MA</span><img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=120&q=60" alt="" onerror="this.style.display='none'"></span>${svg('chevrondown','chev')}</div></div><div class="verse-chip"><p><span class="qmark">“</span>${d.verse}</p><cite>${d.ref}</cite></div></div>`;}
document.querySelectorAll('.app-side').forEach(buildSide);
document.querySelectorAll('.app-top').forEach(buildTop);
paintIcons(document);
const pages=document.querySelectorAll('.page');
const topbarRoot=document.getElementById('topbar-root'),headerRoot=document.getElementById('header-root'),footerRoot=document.getElementById('footer-root');
function chromeFor(p){topbarRoot.style.display=p.dataset.topbar==='0'?'none':'';headerRoot.style.display=p.dataset.header==='0'?'none':'';footerRoot.style.display=p.dataset.footer==='0'?'none':'';}
function initPanes(pageEl){const tabset=pageEl.querySelector('.tabs');if(!tabset)return;const activeBtn=tabset.querySelector('button.active')||tabset.querySelector('button');if(activeBtn)showPane(pageEl,activeBtn.dataset.tab);}
function showPane(pageEl,tab){pageEl.querySelectorAll('.tabpane').forEach(p=>p.classList.toggle('active',p.dataset.pane===tab));}
function go(name){let target=null;pages.forEach(p=>{const on=p.dataset.page===name;p.classList.toggle('active',on);if(on)target=p;});if(target){chromeFor(target);initPanes(target);}document.querySelectorAll('#nav [data-nav]').forEach(a=>a.classList.remove('active'));const map={home:'home',sobre:'sobre',professores:'prof',teologia:'cursos',diaconato:'cursos',lideranca:'cursos',inscricao:'cursos',eventos:'ev',noticias:'not',contato:'cont','acompanhar-inscricao':'acomp'};const navEl=document.querySelector(`#nav [data-nav="${map[name]}"]`);if(navEl)navEl.classList.add('active');window.scrollTo({top:0,behavior:'instant'});document.getElementById('nav').classList.remove('open');document.querySelectorAll('.app-side').forEach(s=>s.classList.remove('open'));setTimeout(revealObserve,60);}
window.go=go;
document.addEventListener('click',e=>{
  const burger=e.target.closest('.app-burger');if(burger){burger.closest('.app').querySelector('.app-side').classList.toggle('open');return;}
  if(e.target.closest('.app-main')&&!e.target.closest('.app-side')){document.querySelectorAll('.app-side.open').forEach(s=>s.classList.remove('open'));}
  const goEl=e.target.closest('[data-go]'),toastEl=e.target.closest('[data-toast]'),scrollEl=e.target.closest('[data-scroll]');
  if(toastEl&&!goEl){e.preventDefault();toast(toastEl.dataset.toast);return;}
  if(goEl){e.preventDefault();go(goEl.dataset.go);if(scrollEl)setTimeout(()=>scrollToId(scrollEl.dataset.scroll),120);return;}
  if(scrollEl){e.preventDefault();scrollToId(scrollEl.dataset.scroll);}
});
function scrollToId(id){const el=document.getElementById(id);if(el)el.scrollIntoView({behavior:'smooth',block:'start'});}
document.getElementById('burger').addEventListener('click',()=>document.getElementById('nav').classList.toggle('open'));
document.querySelectorAll('.as-nav').forEach(nav=>{nav.addEventListener('click',e=>{const a=e.target.closest('a');if(!a||a.dataset.go)return;e.preventDefault();nav.querySelectorAll('a').forEach(x=>x.classList.remove('active'));a.classList.add('active');});});
document.querySelectorAll('.tabs').forEach(tabset=>{const btns=[...tabset.querySelectorAll('button')];const pageEl=tabset.closest('.page');btns.forEach(b=>b.addEventListener('click',()=>{btns.forEach(x=>x.classList.remove('active'));b.classList.add('active');showPane(pageEl,b.dataset.tab);const tw=pageEl.querySelector('.tabs-wrap');if(tw)tw.scrollIntoView({behavior:'smooth',block:'start'});}));});
document.querySelectorAll('.line-tabs').forEach(t=>{t.addEventListener('click',e=>{const b=e.target.closest('button[data-tab]');if(!b)return;e.preventDefault();t.querySelectorAll('button').forEach(x=>x.classList.remove('active'));b.classList.add('active');if(b.dataset.note)toast(b.dataset.note);});});
document.querySelectorAll('.theme-grid').forEach(g=>{g.addEventListener('click',e=>{const c=e.target.closest('.theme-card');if(!c)return;g.querySelectorAll('.theme-card').forEach(x=>x.classList.remove('active'));c.classList.add('active');toast('Tema "'+c.querySelector('b').textContent+'" aplicado (demonstração).');});});
document.querySelectorAll('.msg-list').forEach(l=>{l.addEventListener('click',e=>{const c=e.target.closest('.conv');if(!c)return;l.querySelectorAll('.conv').forEach(x=>x.classList.remove('active'));c.classList.add('active');});});
const profFilter=document.getElementById('profFilter');
if(profFilter){profFilter.addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;profFilter.querySelectorAll('button').forEach(x=>x.classList.remove('active'));b.classList.add('active');const area=b.dataset.area;document.querySelectorAll('#profGrid .prof2-card').forEach(c=>{c.classList.toggle('hide',area!=='all'&&c.dataset.area!==area);});});}
/* filtros de eventos e notícias */
function bindFilter(barId,gridSel,cardSel){const bar=document.getElementById(barId);if(!bar)return;bar.addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;bar.querySelectorAll('button').forEach(x=>x.classList.remove('active'));b.classList.add('active');const c=b.dataset.cat;document.querySelectorAll(gridSel+' '+cardSel).forEach(card=>{card.classList.toggle('hide',c!=='all'&&card.dataset.cat!==c);});});}
bindFilter('evFilter','.ev-grid','.evcard');
bindFilter('newsFilter','.news-grid','.newscard');
/* FAQ acordeão */
document.querySelectorAll('.faq-item').forEach(it=>{it.querySelector('.faq-q').addEventListener('click',()=>{const open=it.classList.contains('open');document.querySelectorAll('.faq-item').forEach(x=>{x.classList.remove('open');const a=x.querySelector('.faq-a');if(a)a.style.maxHeight=null;});if(!open){it.classList.add('open');const a=it.querySelector('.faq-a');a.style.maxHeight=a.scrollHeight+'px';}});});
/* calendário de eventos dinâmico */
(function(){const el=document.getElementById('evCal');if(!el)return;const y=+el.dataset.year,m=+el.dataset.month,evs=(el.dataset.events||'').split(',').map(Number);const first=new Date(y,m,1).getDay();const days=new Date(y,m+1,0).getDate();const hs=['DOM','SEG','TER','QUA','QUI','SEX','SÁB'];let html=hs.map(h=>`<span class="ch">${h}</span>`).join('');for(let i=0;i<first;i++)html+=`<span class="cd out"></span>`;const today=new Date();for(let d=1;d<=days;d++){const has=evs.includes(d);const isToday=(today.getFullYear()===y&&today.getMonth()===m&&today.getDate()===d);html+=`<span class="cd${has?' has':''}${isToday?' today':''}" ${has?'data-toast="Evento neste dia — confira a programação acima"':''}>${d}</span>`;}el.innerHTML=html;})();
/* contador regressivo */
(function(){const el=document.getElementById('cd');if(!el)return;const target=new Date(el.dataset.target).getTime();const boxes=el.querySelectorAll('.cd-box b');function tick(){const d=target-Date.now();const pad=n=>String(Math.max(0,n)).padStart(2,'0');if(d<=0){boxes.forEach(b=>b.textContent='00');return;}boxes[0].textContent=pad(Math.floor(d/864e5));boxes[1].textContent=pad(Math.floor(d%864e5/36e5));boxes[2].textContent=pad(Math.floor(d%36e5/6e4));boxes[3].textContent=pad(Math.floor(d%6e4/1e3));}tick();setInterval(tick,1000);})();
/* inscrição: catálogo público (cursos + modalidades) da API, com 1 a 3 cursos por envio */
(function(){
  const lista=document.getElementById('inscCursosList');
  const btnAdd=document.getElementById('inscAddCurso');
  if(!lista||!btnAdd)return;
  const MAX_CURSOS=3;
  let cursosCache=[];

  function itens(){return [...lista.querySelectorAll('.insc-curso-item')];}

  function atualizarOpcoesCursos(){
    const usados=itens().map(it=>it.querySelector('.insc-curso-sel').value).filter(v=>v!=='');
    itens().forEach(it=>{
      const sel=it.querySelector('.insc-curso-sel');
      [...sel.options].forEach(opt=>{
        if(opt.value==='')return;
        opt.disabled=usados.includes(opt.value)&&opt.value!==sel.value;
      });
    });
    // botão de adicionar: some quando atinge o máximo ou quando todos os cursos já foram usados
    const esgotou=usados.length>=cursosCache.length&&cursosCache.length>0;
    btnAdd.style.display=(itens().length>=MAX_CURSOS||esgotou)?'none':'';
    // botão de remover: só aparece quando há mais de um item
    itens().forEach((it,i)=>{const rm=it.querySelector('.insc-curso-remove');if(rm)rm.style.display=itens().length>1?'':'none';});
  }

  function preencherSelectCurso(sel){
    if(cursosCache.length===0){sel.innerHTML='<option value="">Carregando cursos…</option>';return;}
    sel.innerHTML='<option value="">Selecione um curso</option>'+
      cursosCache.map(c=>`<option value="${c.id}">${c.titulo}</option>`).join('');
  }

  function preencherModalidades(item,cursoId){
    const modSel=item.querySelector('.insc-mod-sel');
    const curso=cursosCache.find(c=>String(c.id)===String(cursoId));
    if(!curso||!Array.isArray(curso.modalidades)||curso.modalidades.length===0){
      modSel.innerHTML='<option value="">Selecione um curso primeiro</option>';
      return;
    }
    modSel.innerHTML='<option value="">Selecione a modalidade</option>'+
      curso.modalidades.map(m=>`<option value="${m.id}">${m.nome}</option>`).join('');
  }

  function criarItem(){
    const item=document.createElement('div');
    item.className='fgrid insc-curso-item';
    item.innerHTML=`<div class="field"><label>Curso desejado <span class="req">*</span></label><select class="insc-curso-sel" required></select></div><div class="field"><label>Modalidade <span class="req">*</span></label><select class="insc-mod-sel" required><option value="">Selecione um curso primeiro</option></select></div><div class="field" style="align-self:end"><button type="button" class="btn btn-outline insc-curso-remove" style="width:100%">REMOVER</button></div>`;
    preencherSelectCurso(item.querySelector('.insc-curso-sel'));
    return item;
  }

  btnAdd.addEventListener('click',()=>{
    if(itens().length>=MAX_CURSOS){toast('Você pode se inscrever em até 3 cursos por envio.');return;}
    lista.appendChild(criarItem());
    atualizarOpcoesCursos();
  });

  lista.addEventListener('change',e=>{
    const sel=e.target.closest('.insc-curso-sel');
    if(sel){preencherModalidades(sel.closest('.insc-curso-item'),sel.value);}
    atualizarOpcoesCursos();
  });

  lista.addEventListener('click',e=>{
    const rm=e.target.closest('.insc-curso-remove');
    if(!rm)return;
    if(itens().length<=1)return;
    rm.closest('.insc-curso-item').remove();
    atualizarOpcoesCursos();
  });

  fetch('/api/v1/cursos',{headers:{'Accept':'application/json'}})
    .then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
    .then(json=>{
      const dados=(json&&json.data)?json.data:[];
      cursosCache=Array.isArray(dados)?dados:[];
      itens().forEach(it=>preencherSelectCurso(it.querySelector('.insc-curso-sel')));
      if(cursosCache.length===0){
        itens().forEach(it=>{
          it.querySelector('.insc-curso-sel').innerHTML='<option value="">Nenhum curso disponível</option>';
          it.querySelector('.insc-mod-sel').innerHTML='<option value="">Nenhum curso disponível</option>';
        });
      }
      atualizarOpcoesCursos();
    })
    .catch(()=>{
      itens().forEach(it=>{
        it.querySelector('.insc-curso-sel').innerHTML='<option value="">Não foi possível carregar os cursos</option>';
        it.querySelector('.insc-mod-sel').innerHTML='<option value="">Não foi possível carregar as modalidades</option>';
      });
    });
})();
let revealObserve;
(function(){const io=new IntersectionObserver(es=>{es.forEach(en=>{if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target);}});},{threshold:.12});revealObserve=()=>document.querySelectorAll('.page.active .reveal:not(.in)').forEach(el=>io.observe(el));revealObserve();})();
const header=headerRoot,totop=document.getElementById('totop');
window.addEventListener('scroll',()=>{header.classList.toggle('scrolled',window.scrollY>10);totop.classList.toggle('show',window.scrollY>500);},{passive:true});
totop.addEventListener('click',()=>window.scrollTo({top:0,behavior:'smooth'}));
let toastTimer;
function toast(msg){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=msg;t.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(()=>t.classList.remove('show'),3200);}
/* inscrição: envio em lote para /api/v1/applications/batch */
(function(){
  const form=document.getElementById('inscForm');
  if(!form)return;
  const sucesso=document.getElementById('inscSucesso');
  const OPCIONAIS=['rg','estado_civil','nacionalidade','naturalidade_cidade','naturalidade_estado','complemento'];
  const OBRIGATORIOS=['nome_completo','cpf','data_nascimento','sexo','email','telefone','cep','logradouro','numero','bairro','cidade','estado','escolaridade'];
  function getVal(name){const el=form.querySelector(`[name="${name}"]`);return el?String(el.value||'').trim():'';}
  function escapeHtml(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  function montarPayload(){
    const payload={};
    OBRIGATORIOS.forEach(k=>{payload[k]=getVal(k);});
    OPCIONAIS.forEach(k=>{const v=getVal(k);if(v!=='')payload[k]=v;});
    // telefone preenche também whatsapp
    if(payload.telefone)payload.whatsapp=payload.telefone;
    // monta a lista de inscrições (1 a 3 cursos)
    payload.inscricoes=[...form.querySelectorAll('.insc-curso-item')]
      .map(it=>({
        curso_id:Number(it.querySelector('.insc-curso-sel').value),
        course_modality_id:Number(it.querySelector('.insc-mod-sel').value)
      }))
      .filter(i=>i.curso_id>0&&i.course_modality_id>0);
    return payload;
  }
  function exibirSucesso(inscricoes){
    if(!sucesso)return;
    const linhas=(inscricoes||[]).map(i=>`<div class="side-feat" style="align-items:flex-start"><span class="ic" data-ic="check"></span><div><b>${escapeHtml(i.curso&&i.curso.titulo?i.curso.titulo:'Curso')}</b><small>${escapeHtml(i.modalidade&&i.modalidade.nome?i.modalidade.nome:'')}</small><small style="display:block;font-size:1rem;font-weight:800;color:var(--navy);letter-spacing:.5px;margin-top:4px">${escapeHtml(i.protocol||'')}</small></div></div>`).join('');
    sucesso.innerHTML=`<div class="form-sec-title" data-ic="check">INSCRIÇÃO ENVIADA COM SUCESSO!</div>
      <p style="font-size:.9rem;color:var(--body);margin-bottom:14px">Anote ou copie os protocolos abaixo. Eles são necessários para acompanhar cada inscrição. Você também receberá um e-mail de confirmação.</p>
      ${linhas}
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:18px">
        <button type="button" class="btn btn-navy" id="inscCopiarProtocolos">COPIAR PROTOCOLOS</button>
        <button type="button" class="btn btn-outline" data-go="acompanhar-inscricao">ACOMPANHAR INSCRIÇÃO</button>
      </div>`;
    sucesso.style.display='';
    paintIcons(sucesso);
    sucesso.dataset.protocolos=(inscricoes||[]).map(i=>i.protocol).filter(Boolean).join('\n');
    sucesso.scrollIntoView({behavior:'smooth',block:'start'});
    const copiar=sucesso.querySelector('#inscCopiarProtocolos');
    if(copiar)copiar.addEventListener('click',()=>{
      const texto=sucesso.dataset.protocolos||'';
      if(navigator.clipboard&&navigator.clipboard.writeText){
        navigator.clipboard.writeText(texto).then(()=>toast('Protocolos copiados!')).catch(()=>toast('Não foi possível copiar. Anote os protocolos acima.'));
      }else{
        toast('Não foi possível copiar. Anote os protocolos acima.');
      }
    });
  }
  form.addEventListener('submit',async e=>{
    e.preventDefault();
    if(!form.reportValidity())return;
    const payload=montarPayload();
    if(payload.inscricoes.length===0){toast('Selecione ao menos um curso e uma modalidade.');return;}
    if(payload.inscricoes.length>3){toast('Você pode se inscrever em até 3 cursos por envio.');return;}
    const cursos=payload.inscricoes.map(i=>i.curso_id);
    if(new Set(cursos).size!==cursos.length){toast('Não é possível repetir o mesmo curso no mesmo envio.');return;}
    const btn=form.querySelector('button[type="submit"]');
    const btnHtml=btn?btn.innerHTML:'';
    if(btn){btn.disabled=true;btn.innerHTML='Enviando…';}
    if(sucesso){sucesso.style.display='none';sucesso.innerHTML='';}
    try{
      const resp=await fetch('/api/v1/applications/batch',{
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json'},
        body:JSON.stringify(payload)
      });
      let data=null;try{data=await resp.json();}catch(_){}
      if(resp.status===201){
        const inscricoes=(data&&data.data&&data.data.inscricoes)?data.data.inscricoes:[];
        toast(inscricoes.length>1?`${inscricoes.length} inscrições enviadas com sucesso!`:'Inscrição enviada com sucesso!');
        // não limpa a tela: os protocolos ficam visíveis até o usuário copiá-los
        exibirSucesso(inscricoes);
      }else if(resp.status===422){
        let msg='Verifique os dados informados.';
        const details=data&&data.error&&data.error.details;
        if(details&&typeof details==='object'){
          const primeira=Object.values(details)[0];
          if(Array.isArray(primeira)&&primeira.length)msg=primeira[0];
          else if(typeof primeira==='string')msg=primeira;
        }else if(data&&data.error&&data.error.message){
          msg=data.error.message;
        }else if(data&&data.message){
          msg=data.message;
        }
        toast(msg);
      }else{
        toast('Não foi possível enviar a inscrição. Tente novamente em instantes.');
      }
    }catch(err){
      toast('Falha de conexão. Verifique sua internet e tente novamente.');
    }finally{
      if(btn){btn.disabled=false;btn.innerHTML=btnHtml;}
    }
  });
})();
/* acompanhamento: consulta segura protocolo + e-mail */
(function(){
  const form=document.getElementById('acompanharForm');
  if(!form)return;
  const resultado=document.getElementById('acompResultado');
  const erro=document.getElementById('acompErro');
  const MSG_ERRO='Não foi possível localizar a inscrição. Verifique o protocolo e o e-mail informados.';
  function escapeHtml(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  function formatarData(iso){const d=new Date(iso);if(isNaN(d))return iso||'';return d.toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit',year:'2-digit'})+' às '+d.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});}
  function mostrarErro(msg){
    if(resultado){resultado.style.display='none';resultado.innerHTML='';}
    if(!erro)return;
    erro.innerHTML=`<div class="form-sec-title" data-ic="xcircle">NÃO ENCONTRAMOS SUA INSCRIÇÃO</div><p style="font-size:.9rem;color:var(--body)">${escapeHtml(msg||MSG_ERRO)}</p><p style="font-size:.82rem;color:var(--muted);margin-top:10px">Se o problema persistir, fale com a secretaria pelo telefone (21) 2683-3271 ou pelo e-mail contato@cetsadp.com.br.</p>`;
    erro.style.display='';
    paintIcons(erro);
    erro.scrollIntoView({behavior:'smooth',block:'center'});
  }
  form.addEventListener('submit',async e=>{
    e.preventDefault();
    if(!form.reportValidity())return;
    const btn=form.querySelector('button[type="submit"]');
    const btnHtml=btn?btn.innerHTML:'';
    if(btn){btn.disabled=true;btn.innerHTML='Consultando…';}
    try{
      const resp=await fetch('/api/v1/applications/acompanhamento',{
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json'},
        body:JSON.stringify({
          protocol:document.getElementById('acompProtocolo').value.trim(),
          email:document.getElementById('acompEmail').value.trim()
        })
      });
      let data=null;try{data=await resp.json();}catch(_){}
      if(resp.ok&&data&&data.success&&data.data){
        const d=data.data;
        if(erro){erro.style.display='none';erro.innerHTML='';}
        if(resultado){
          resultado.innerHTML=`<div class="form-sec-title" data-ic="check">INSCRIÇÃO LOCALIZADA</div>
            <div class="side-feat"><span class="ic" data-ic="clipboard"></span><div><b>Protocolo</b><small>${escapeHtml(d.protocol||'')}</small></div></div>
            <div class="side-feat"><span class="ic" data-ic="book"></span><div><b>${escapeHtml(d.curso&&d.curso.titulo?d.curso.titulo:'Curso')}</b><small>Curso</small></div></div>
            <div class="side-feat"><span class="ic" data-ic="monitor"></span><div><b>${escapeHtml(d.modalidade&&d.modalidade.nome?d.modalidade.nome:'Modalidade')}</b><small>Modalidade</small></div></div>
            <div class="side-feat"><span class="ic" data-ic="calendar"></span><div><b>${escapeHtml(formatarData(d.data_inscricao))}</b><small>Data da inscrição</small></div></div>
            <div style="margin-top:16px;padding:14px;border-radius:12px;background:rgba(216,169,60,.12);border:1px solid var(--gold);text-align:center"><small style="display:block;font-size:.68rem;font-weight:800;letter-spacing:1px;color:var(--gold-deep)">SITUAÇÃO ATUAL</small><b style="font-family:var(--serif);font-size:1.15rem;color:var(--navy)">${escapeHtml(d.status_label||d.status||'')}</b></div>`;
          resultado.style.display='';
          paintIcons(resultado);
          resultado.scrollIntoView({behavior:'smooth',block:'center'});
        }
      }else if(resp.status===429){
        mostrarErro('Muitas tentativas em sequência. Aguarde um instante e tente novamente.');
      }else{
        mostrarErro();
      }
    }catch(err){
      mostrarErro('Falha de conexão. Verifique sua internet e tente novamente.');
    }finally{
      if(btn){btn.disabled=false;btn.innerHTML=btnHtml;}
    }
  });
})();
document.getElementById('loginSecForm').addEventListener('submit',e=>{e.preventDefault();toast('Acesso concedido. Carregando secretaria...');setTimeout(()=>go('dash-secretaria'),500);});
document.getElementById('loginAluForm').addEventListener('submit',e=>{e.preventDefault();toast('Acesso concedido. Carregando portal...');setTimeout(()=>go('dash-aluno'),500);});
const nlForm=document.getElementById('nlForm');if(nlForm)nlForm.addEventListener('submit',e=>{e.preventDefault();toast('Inscrição na newsletter confirmada! Que Deus abençoe.');nlForm.reset();});
const contactForm=document.getElementById('contactForm');if(contactForm)contactForm.addEventListener('submit',e=>{e.preventDefault();toast('Mensagem enviada! Retornaremos em até 24h úteis.');contactForm.reset();});
document.querySelectorAll('.lfield .si[data-ic], .cfg-field .si[data-ic]').forEach(btn=>{btn.addEventListener('click',()=>{const inp=btn.parentElement.querySelector('input');if(inp.type==='password'){inp.type='text';btn.innerHTML=svg('eye');}else{inp.type='password';btn.innerHTML=svg('eyeoff');}});});
go('home');
