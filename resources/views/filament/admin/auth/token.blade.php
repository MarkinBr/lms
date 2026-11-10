<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#07172f">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="referrer" content="same-origin">
<title>Token de acesso — CETSADP</title>
<meta name="description" content="Acesso administrativo por token do CETSADP.">
<style>
@font-face{font-family:'Manrope';src:url('{{ asset('fonts/manrope-latin.woff2') }}') format('woff2');font-style:normal;font-weight:400 800;font-display:swap}
@font-face{font-family:'Playfair Display';src:url('{{ asset('fonts/playfair-display-latin.woff2') }}') format('woff2');font-style:normal;font-weight:600 700;font-display:swap}
:root{
  --navy-950:#061126;
  --navy-900:#081832;
  --navy-850:#0b1e3f;
  --navy-800:#10294f;
  --navy-700:#163967;
  --gold-600:#a9771e;
  --gold-500:#c8942f;
  --gold-400:#d8ad56;
  --gold-300:#ecd39b;
  --paper:#f5f6f8;
  --paper-2:#eceff3;
  --white:#fff;
  --ink:#12213d;
  --text:#344158;
  --muted:#758095;
  --line:#dfe4eb;
  --green:#1f9d67;
  --red:#d14f4f;
  --amber:#b87818;
  --shadow-lg:0 34px 90px rgba(6,17,38,.17);
  --shadow-md:0 18px 50px rgba(6,17,38,.12);
  --shadow-sm:0 8px 24px rgba(6,17,38,.08);
  --serif:'Playfair Display',Georgia,serif;
  --sans:'Manrope',Inter,system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  --radius-xl:34px;
  --radius-lg:24px;
  --radius-md:16px;
  --radius-sm:12px;
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{min-height:100%}
html{background:var(--paper-2)}
body{font-family:var(--sans);color:var(--text);background:
  radial-gradient(circle at 10% 8%,rgba(200,148,47,.12),transparent 24%),
  radial-gradient(circle at 90% 88%,rgba(16,41,79,.10),transparent 27%),
  linear-gradient(135deg,#f7f8fa 0%,#eef1f5 48%,#e7ebf0 100%);-webkit-font-smoothing:antialiased;overflow-x:hidden}
button,input{font:inherit}
button{cursor:pointer}
img{display:block;max-width:100%}
::selection{background:rgba(200,148,47,.24);color:var(--navy-950)}

/* ---------- utilidades ---------- */
.ic{width:20px;height:20px;display:block;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round;flex:none;align-self:center}
.screen{display:none;min-height:100vh;width:100%}
.screen.active{display:block;animation:screenIn .6s cubic-bezier(.2,.8,.2,1)}
@keyframes screenIn{from{opacity:0;transform:translateY(12px);filter:blur(2px)}to{opacity:1;transform:none;filter:none}}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(31,157,103,.35)}50%{box-shadow:0 0 0 7px rgba(31,157,103,0)}}
@keyframes drift{0%,100%{transform:translate3d(0,0,0) rotate(-1deg)}50%{transform:translate3d(-12px,9px,0) rotate(1deg)}}
@keyframes floatSoft{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@keyframes pop{0%{opacity:0;transform:scale(.82)}70%{transform:scale(1.04)}100%{opacity:1;transform:none}}
@keyframes shake{0%,100%{transform:none}20%,60%{transform:translateX(-8px)}40%,80%{transform:translateX(8px)}}

/* ---------- toast ---------- */
.toast{position:fixed;top:22px;right:22px;z-index:200;width:min(390px,calc(100vw - 32px));padding:14px 16px;border-radius:15px;color:#fff;font-size:13px;font-weight:700;box-shadow:0 22px 55px rgba(6,17,38,.25);opacity:0;transform:translateY(-12px) scale(.98);pointer-events:none;transition:.28s ease;backdrop-filter:blur(12px)}
.toast.show{opacity:1;transform:none}
.toast.info{background:rgba(8,24,50,.94)}
.toast.success{background:rgba(22,132,84,.94)}
.toast.error{background:rgba(188,56,56,.95)}

/* ============================================================
   LOGIN PRINCIPAL
   ============================================================ */
.login-stage{min-height:100vh;padding:24px;display:grid;grid-template-columns:minmax(0,1.08fr) minmax(440px,.92fr);gap:22px;max-width:1640px;margin:0 auto}

/* Painel institucional */
.brand-panel{position:relative;min-height:calc(100vh - 48px);overflow:hidden;border-radius:var(--radius-xl);padding:42px 46px 34px;color:#fff;background:
  linear-gradient(145deg,rgba(8,24,50,.99),rgba(10,31,63,.97) 58%,rgba(14,42,80,.98)),
  var(--navy-900);box-shadow:var(--shadow-lg);isolation:isolate;display:flex;flex-direction:column}
.brand-panel::before{content:"";position:absolute;inset:-20%;z-index:-2;background:
  radial-gradient(circle at 14% 12%,rgba(216,173,86,.26),transparent 24%),
  radial-gradient(circle at 78% 74%,rgba(78,126,195,.18),transparent 28%);animation:drift 14s ease-in-out infinite}
.brand-panel::after{content:"";position:absolute;right:-120px;top:-120px;width:450px;height:450px;border:1px solid rgba(236,211,155,.12);border-radius:50%;box-shadow:0 0 0 46px rgba(255,255,255,.022),0 0 0 92px rgba(255,255,255,.014);z-index:-1}
.brand-watermark{position:absolute;right:-90px;bottom:-80px;width:min(520px,55%);opacity:.045;filter:brightness(0) invert(1);pointer-events:none;user-select:none;transform:rotate(-5deg)}
.brand-top{display:flex;align-items:flex-start;justify-content:space-between;gap:24px;position:relative;z-index:2}
.brand-logo{width:min(360px,72%);height:auto;filter:brightness(0) invert(1);transform-origin:left center;animation:floatSoft 6s ease-in-out infinite}
.restricted-badge{display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid rgba(236,211,155,.22);border-radius:999px;background:rgba(255,255,255,.055);color:#e9d7ac;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap;backdrop-filter:blur(8px)}
.restricted-badge .dot{width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse 2.2s infinite}
.restricted-badge .dot.offline{background:var(--red);animation:none}
.brand-copy{margin-top:clamp(62px,11vh,120px);max-width:720px;position:relative;z-index:2}
.eyebrow{display:flex;align-items:center;gap:10px;color:var(--gold-300);font-size:12px;font-weight:800;letter-spacing:.17em;text-transform:uppercase;margin-bottom:17px}
.eyebrow::before{content:"";width:34px;height:1px;background:linear-gradient(90deg,var(--gold-400),transparent)}
.brand-copy h1{font-family:var(--serif);font-size:clamp(42px,4.9vw,76px);line-height:.98;letter-spacing:-.025em;font-weight:700;max-width:760px;text-wrap:balance}
.brand-copy h1 em{font-style:normal;color:var(--gold-400)}
.brand-copy>p{margin-top:23px;max-width:620px;color:#c9d2e2;font-size:14px;line-height:1.85}
.capabilities{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:34px;max-width:770px}
.capability{padding:16px 16px 15px;border-radius:16px;border:1px solid rgba(255,255,255,.085);background:linear-gradient(180deg,rgba(255,255,255,.065),rgba(255,255,255,.035));backdrop-filter:blur(8px);transition:.25s ease}
.capability:hover{transform:translateY(-3px);border-color:rgba(216,173,86,.25);background:rgba(255,255,255,.075)}
.cap-icon{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;background:rgba(200,148,47,.12);color:var(--gold-400);margin-bottom:12px;border:1px solid rgba(216,173,86,.14)}
.cap-icon>.ic,.system-icon>.ic,.admin-mark>.ic,.security-icon>.ic,.flow-icon>.ic,.right-action>.ic{margin:auto}
.capability strong{display:block;color:#fff;font-size:12px;margin-bottom:4px}
.capability span{display:block;color:#98a8c0;font-size:10.5px;line-height:1.5}
.brand-bottom{margin-top:auto;padding-top:32px;display:flex;align-items:flex-end;justify-content:space-between;gap:18px;position:relative;z-index:2}
.system-card{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:15px;background:rgba(5,16,35,.34);border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(10px)}
.system-icon{width:36px;height:36px;border-radius:12px;display:grid;place-items:center;background:rgba(31,157,103,.11);color:#61d69d;border:1px solid rgba(97,214,157,.14)}
.system-card strong{display:block;font-size:11px;color:#fff}
.system-card small{display:block;font-size:10px;color:#92a2bb;margin-top:2px}
.brand-time{text-align:right;color:#9ba9bd;font-size:10.5px;line-height:1.65}
.brand-time b{display:block;color:#e7d8b8;font-size:14px;font-variant-numeric:tabular-nums;letter-spacing:.04em}

/* Lado do formulário */
.access-panel{position:relative;min-height:calc(100vh - 48px);display:flex;align-items:center;justify-content:center;border-radius:var(--radius-xl);overflow:hidden;background:
  radial-gradient(circle at 87% 12%,rgba(200,148,47,.12),transparent 24%),
  radial-gradient(circle at 10% 92%,rgba(16,41,79,.09),transparent 28%),
  linear-gradient(180deg,rgba(255,255,255,.76),rgba(246,248,251,.70));border:1px solid rgba(255,255,255,.8);box-shadow:inset 0 1px 0 #fff,var(--shadow-md);isolation:isolate;padding:50px 28px}
.access-panel::before{content:"";position:absolute;left:12%;top:10%;width:9px;height:9px;border-radius:50%;background:var(--gold-400);box-shadow:0 0 0 8px rgba(200,148,47,.07),0 0 42px 12px rgba(200,148,47,.12);opacity:.7}
.access-watermark{position:absolute;width:640px;height:640px;right:-230px;top:-105px;object-fit:contain;opacity:.105;filter:brightness(0) saturate(100%) invert(12%) sepia(22%) saturate(2600%) hue-rotate(184deg) brightness(86%) contrast(97%);mix-blend-mode:multiply;pointer-events:none;animation:drift 14s ease-in-out infinite;z-index:-1}
.access-wrap{width:min(520px,100%);position:relative;z-index:2}
.access-minihead{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;padding:0 4px}
.portal-tag{display:inline-flex;align-items:center;justify-content:flex-start;gap:9px;min-height:32px;padding:0 12px 0 10px;border-radius:999px;color:var(--navy-700);font-size:10.5px;font-weight:800;letter-spacing:.085em;text-transform:uppercase;background:rgba(255,255,255,.52);border:1px solid rgba(16,41,79,.08);box-shadow:0 5px 16px rgba(6,17,38,.035);white-space:nowrap}
.portal-tag .line{display:block;flex:0 0 24px;width:24px;height:2px;border-radius:999px;background:linear-gradient(90deg,var(--gold-500),var(--gold-300));box-shadow:0 0 8px rgba(200,148,47,.20)}
.font-tool{height:34px;min-width:42px;border-radius:11px;border:1px solid rgba(16,41,79,.12);background:rgba(255,255,255,.58);color:var(--navy-800);font-size:11px;font-weight:800;box-shadow:0 4px 14px rgba(6,17,38,.04);transition:.2s}
.font-tool:hover{background:#fff;border-color:rgba(200,148,47,.38);transform:translateY(-1px)}

.login-card{position:relative;padding:34px 34px 30px;border-radius:28px;background:linear-gradient(145deg,rgba(255,255,255,.72),rgba(255,255,255,.50));border:1px solid rgba(255,255,255,.86);box-shadow:0 28px 65px rgba(6,17,38,.13),inset 0 1px 0 rgba(255,255,255,.9);backdrop-filter:blur(18px) saturate(125%);-webkit-backdrop-filter:blur(18px) saturate(125%);overflow:hidden;transition:.3s ease}
.login-card::before{content:"";position:absolute;inset:0;border-radius:inherit;padding:1px;background:linear-gradient(145deg,rgba(216,173,86,.34),rgba(255,255,255,.2) 45%,rgba(16,41,79,.08));-webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none}
.login-card::after{content:"";position:absolute;width:150px;height:150px;border-radius:50%;right:-70px;top:-70px;background:radial-gradient(circle,rgba(200,148,47,.15),transparent 68%);pointer-events:none}
.login-card.shake,.flow-card.shake{animation:shake .42s}
.card-heading{display:grid;grid-template-columns:auto 1fr;gap:15px;align-items:center;margin-bottom:22px}
.admin-mark{width:54px;height:54px;border-radius:17px;display:grid;place-items:center;color:var(--gold-500);background:linear-gradient(145deg,#fffdf7,#f3ead6);border:1px solid rgba(200,148,47,.25);box-shadow:0 10px 25px rgba(200,148,47,.12)}
.admin-mark .ic{width:27px;height:27px;stroke-width:1.7}
.card-heading h2{font-family:var(--serif);color:var(--navy-900);font-size:25px;line-height:1.05;letter-spacing:-.015em;outline:none}
.card-heading p{margin-top:6px;color:var(--muted);font-size:11.5px;line-height:1.55}

.access-note{display:flex;align-items:flex-start;gap:10px;margin:0 0 18px;padding:11px 12px;border-radius:13px;background:rgba(8,24,50,.045);border:1px solid rgba(8,24,50,.055);color:#536078;font-size:10.5px;line-height:1.55}
.access-note .ic{width:17px;height:17px;color:var(--gold-600);margin-top:1px}

.field{margin-top:15px}
.field label{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 7px;color:#41506a;font-size:10.5px;font-weight:800;letter-spacing:.065em;text-transform:uppercase}
.field label .optional{color:#9ba5b4;font-size:9px;font-weight:700;letter-spacing:.04em}
.control{position:relative}
.control>.left-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#8792a4;width:18px;height:18px;pointer-events:none;transition:.2s}
.control input{width:100%;height:49px;padding:0 44px 0 42px;border-radius:13px;border:1px solid rgba(16,41,79,.12);outline:none;background:rgba(255,255,255,.78);color:var(--ink);font-size:13px;font-weight:600;box-shadow:inset 0 1px 0 rgba(255,255,255,.8);transition:.22s ease}
.control input::placeholder{color:#a6afbd;font-weight:500}
.control input:hover{border-color:rgba(16,41,79,.20);background:rgba(255,255,255,.88)}
.control input:focus{border-color:rgba(200,148,47,.72);background:#fff;box-shadow:0 0 0 4px rgba(200,148,47,.11),0 10px 26px rgba(6,17,38,.055);transform:translateY(-1px)}
.control input:focus~.left-icon,.field.has-value .left-icon{color:var(--gold-600)}
.control .right-action{position:absolute;right:7px;top:50%;transform:translateY(-50%);width:35px;height:35px;border:0;border-radius:10px;background:transparent;color:#7f8a9d;display:grid;place-items:center;transition:.2s}
.control .right-action:hover{background:rgba(16,41,79,.055);color:var(--navy-800)}
.control .right-action .ic{width:17px;height:17px}
.field.valid .control::after{content:"✓";position:absolute;right:45px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:900;color:var(--green);animation:pop .22s ease}
.field.invalid input{border-color:rgba(209,79,79,.75);box-shadow:0 0 0 4px rgba(209,79,79,.08)}
.err{display:none;color:var(--red);font-size:10.5px;font-weight:700;margin-top:6px}
.field.invalid .err{display:block;animation:fadeUp .2s ease}
.caps-warning{display:none;align-items:center;gap:7px;margin-top:7px;color:#94620d;font-size:10.5px;font-weight:700}
.caps-warning.show{display:flex;animation:fadeUp .2s ease}
.caps-warning .ic{width:15px;height:15px}

.form-options{display:flex;align-items:center;justify-content:space-between;gap:14px;margin:15px 0 18px}
.check{display:flex;align-items:center;gap:8px;color:#536078;font-size:11px;font-weight:700;text-transform:none;letter-spacing:0;margin:0;cursor:pointer}
.check input{appearance:none;width:17px;height:17px;border-radius:5px;border:1px solid rgba(16,41,79,.22);background:rgba(255,255,255,.84);display:grid;place-items:center;transition:.2s}
.check input::after{content:"";width:7px;height:4px;border-left:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(-45deg) scale(0);margin-top:-1px;transition:.18s}
.check input:checked{background:var(--navy-800);border-color:var(--navy-800)}
.check input:checked::after{transform:rotate(-45deg) scale(1)}
.link{border:0;background:transparent;color:var(--gold-600);font-size:10.8px;font-weight:800;text-decoration:none;padding:3px 0}
.link:hover{text-decoration:underline;text-underline-offset:3px}
.link:disabled{opacity:.5;cursor:not-allowed;text-decoration:none}

.btn{position:relative;width:100%;height:50px;border-radius:14px;border:1px solid transparent;display:flex;align-items:center;justify-content:center;gap:9px;font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;overflow:hidden;transition:.22s ease;isolation:isolate}
.btn::before{content:"";position:absolute;left:-40%;top:-140%;width:30%;height:380%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.28),transparent);transform:rotate(16deg);transition:left .6s ease;z-index:-1}
.btn:hover::before{left:125%}
.btn:hover{transform:translateY(-2px)}
.btn:active{transform:translateY(0) scale(.995)}
.btn-primary{color:#fff;background:linear-gradient(135deg,var(--navy-900),var(--navy-700));border-color:rgba(255,255,255,.05);box-shadow:0 12px 25px rgba(8,24,50,.20)}
.btn-primary:hover{box-shadow:0 17px 32px rgba(8,24,50,.24)}
.btn-secondary{color:var(--navy-800);background:rgba(255,255,255,.72);border-color:rgba(16,41,79,.12);box-shadow:0 7px 18px rgba(6,17,38,.04)}
.btn-secondary:hover{background:#fff;border-color:rgba(200,148,47,.34)}
.btn .ic{width:17px;height:17px}
.btn.loading{color:transparent;pointer-events:none}
.btn.loading::after{content:"";position:absolute;inset:0;margin:auto;width:18px;height:18px;border:2px solid rgba(255,255,255,.34);border-top-color:#fff;border-radius:50%;animation:spin .75s linear infinite}
.btn-secondary.loading::after{border-color:rgba(16,41,79,.16);border-top-color:var(--navy-800)}
.ripple{position:absolute;border-radius:50%;transform:scale(0);background:rgba(255,255,255,.30);pointer-events:none;animation:ripple .6s linear}
.btn-secondary .ripple{background:rgba(16,41,79,.10)}
@keyframes ripple{to{transform:scale(4);opacity:0}}

.separator{display:flex;align-items:center;gap:11px;color:#9ca6b5;font-size:9.5px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;margin:15px 0}
.separator::before,.separator::after{content:"";height:1px;flex:1;background:linear-gradient(90deg,transparent,rgba(16,41,79,.11))}
.separator::after{background:linear-gradient(90deg,rgba(16,41,79,.11),transparent)}

.security-strip{display:grid;grid-template-columns:1fr auto;gap:14px;align-items:center;margin-top:18px;padding-top:16px;border-top:1px solid rgba(16,41,79,.075)}
.security-info{display:flex;gap:10px;align-items:center}
.security-icon{width:35px;height:35px;border-radius:12px;display:grid;place-items:center;color:var(--gold-600);background:rgba(200,148,47,.09);border:1px solid rgba(200,148,47,.12)}
.security-icon .ic{width:17px;height:17px}
.security-info strong{display:block;color:#4b5870;font-size:10.8px}
.security-info span{display:block;color:#8b95a5;font-size:9.5px;margin-top:2px}
.connection{display:flex;align-items:center;gap:6px;color:#68758b;font-size:9.5px;font-weight:800;white-space:nowrap}
.connection .status-dot{width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse 2.2s infinite}
.connection .status-dot.offline{background:var(--red);animation:none}
.last-access{margin-top:11px;color:#8b95a5;text-align:center;font-size:9.3px;line-height:1.4}


/* ============================================================
   LOGIN CLÁSSICO — visual do arquivo animado dentro do layout premium
   ============================================================ */
.login-card.classic-login{
  width:min(440px,100%);
  margin-inline:auto;
  padding:36px 34px 30px;
  border-radius:16px;
  background:rgba(255,255,255,.50);
  border:1px solid rgba(255,255,255,.68);
  box-shadow:0 10px 30px rgba(10,22,49,.10);
  -webkit-backdrop-filter:blur(5px);
  backdrop-filter:blur(5px);
  overflow:visible;
  text-align:center;
  animation:classicCardIn .72s .08s cubic-bezier(.2,.8,.2,1) both;
}
.login-card.classic-login::before,
.login-card.classic-login::after{display:none}
.login-card.classic-login:hover{box-shadow:0 20px 55px rgba(10,22,49,.16);border-color:rgba(201,150,46,.35)}
@keyframes classicCardIn{from{opacity:0;transform:translateY(22px) scale(.975)}to{opacity:1;transform:none}}

.classic-badge{
  width:58px;height:58px;margin:0 auto 14px;
  display:flex;align-items:center;justify-content:center;
  color:var(--gold-500);
  animation:classicBadgePop .65s .22s both cubic-bezier(.2,.8,.2,1)
}
.classic-badge .ic{width:58px;height:58px;stroke-width:1.5;margin:auto}
@keyframes classicBadgePop{0%{opacity:0;transform:scale(.72) rotate(-8deg)}70%{transform:scale(1.06) rotate(1deg)}100%{opacity:1;transform:none}}
.classic-title{font-family:var(--serif);font-size:21px;letter-spacing:.06em;color:var(--navy-800);text-transform:uppercase;font-weight:700;outline:none;line-height:1.25}
.classic-crown{color:var(--gold-500);margin:8px auto 10px;width:20px;height:20px;display:block}
.classic-sub{font-size:13px;color:var(--muted);line-height:1.6;max-width:300px;margin:0 auto 2px}
.classic-login form{text-align:left}

.classic-field{margin-top:16px}
.classic-field label{display:block;margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:.08em;color:#374151;text-transform:uppercase}
.classic-control{position:relative;transition:transform .2s ease}
.classic-control input{
  width:100%;height:auto;padding:12px 42px 12px 13px;
  border:1px solid var(--line);border-radius:8px;
  font-size:14px;font-family:inherit;font-weight:500;color:var(--ink);
  background:rgba(255,255,255,.90);outline:none;
  box-shadow:none;transition:border-color .2s,box-shadow .2s,transform .2s,background .2s
}
.classic-control input::placeholder{color:#9ca3af;font-weight:400}
.classic-control input:hover{background:rgba(255,255,255,.96);border-color:#d7dce4}
.classic-control input:focus{border-color:var(--gold-500);box-shadow:0 0 0 4px rgba(201,150,46,.13),0 7px 18px rgba(10,22,49,.06);transform:translateY(-1px);background:#fff}
.classic-ic-right{position:absolute;right:13px;top:50%;transform:translateY(-50%) !important;color:#9ca3af;width:18px;height:18px;z-index:2}
.classic-control>svg.classic-ic-right{pointer-events:none}
.classic-eye{display:flex;align-items:center;justify-content:center;background:none;border:none;padding:0;line-height:0;cursor:pointer}
.classic-eye:hover{color:var(--navy-800)}
.classic-eye .ic{width:18px;height:18px;margin:auto}
.classic-field.valid .classic-control::after{content:"✓";position:absolute;right:38px;top:50%;transform:translateY(-50%);color:var(--green);font-size:13px;font-weight:800;animation:pop .25s ease}
.classic-field.invalid input{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,38,.08)}
.classic-field .err{display:none;color:var(--red);font-size:12px;margin-top:6px;font-weight:600}
.classic-field.invalid .err{display:block}
.classic-caps{margin-top:7px;font-size:11.5px}
.classic-caps .ic{width:15px;height:15px}

.classic-row{display:flex;justify-content:space-between;align-items:center;gap:14px;margin:14px 0}
.classic-check{display:flex !important;align-items:center !important;gap:8px !important;margin:0 !important;color:#374151 !important;font-size:13px !important;font-weight:500 !important;text-transform:none !important;letter-spacing:0 !important;cursor:pointer}
.classic-check input{appearance:auto;width:15px;height:15px;accent-color:var(--navy-800);margin:0}
.classic-link{background:none;border:none;color:var(--gold-600);font-size:12.5px;font-weight:700;text-decoration:none;padding:2px 0}
.classic-link:hover{text-decoration:underline;text-underline-offset:3px}

.classic-btn{
  height:47px;border-radius:8px;font-size:13px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;
  display:flex !important;align-items:center !important;justify-content:center !important;gap:9px;
  overflow:hidden;transition:transform .2s,box-shadow .2s,background .2s,border-color .2s
}
.classic-btn .ic{width:17px;height:17px;margin:0 !important;align-self:center !important}
.classic-btn:hover{transform:translateY(-2px)}
.classic-btn-navy{background:var(--navy-800);color:#fff;border:none;box-shadow:0 6px 14px rgba(14,31,69,.25)}
.classic-btn-navy:hover{background:var(--navy-700);box-shadow:0 10px 24px rgba(14,31,69,.25)}
.classic-btn-outline{background:#fffdf8;color:var(--gold-600);border:1px solid var(--gold-300);box-shadow:none}
.classic-btn-outline:hover{background:#fdf6e8;border-color:var(--gold-500)}
.classic-divider{display:flex;align-items:center;gap:12px;margin:16px 0;color:#9ca3af;font-size:11px;letter-spacing:.1em}
.classic-divider::before,.classic-divider::after{content:"";flex:1;height:1px;background:var(--line)}

.classic-safe{display:flex;gap:12px;align-items:center;background:rgba(248,249,251,.82);border:1px solid rgba(238,240,243,.9);border-radius:10px;padding:13px 14px;margin-top:18px;text-align:left}
.classic-safe-icon{width:28px;height:28px;display:flex;align-items:center;justify-content:center;color:var(--gold-500);flex:none}
.classic-safe-icon .ic{width:22px;height:22px;margin:auto;animation:classicLockPulse 3s ease-in-out infinite}
@keyframes classicLockPulse{0%,100%{transform:scale(1);filter:none}50%{transform:scale(1.08);filter:drop-shadow(0 0 5px rgba(201,150,46,.3))}}
.classic-safe strong{font-size:12.5px;color:#374151;display:block;margin-bottom:3px}
.classic-safe p{font-size:12px;color:var(--muted);line-height:1.55;margin:0}
.classic-login-meta{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:13px;padding-top:12px;border-top:1px solid rgba(229,231,235,.72);font-size:10.5px;color:var(--muted);text-align:left}
.classic-conn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:27px;padding:0 10px;border-radius:999px;white-space:nowrap;color:#24734f;font-weight:800;background:rgba(34,160,90,.075);border:1px solid rgba(34,160,90,.14)}
.classic-conn .status-dot{display:block;flex:0 0 7px;width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 0 0 rgba(34,160,90,.36);animation:classicStatusPulse 2s infinite}
.classic-conn .status-dot.offline{background:var(--red);box-shadow:none;animation:none}
@keyframes classicStatusPulse{0%{box-shadow:0 0 0 0 rgba(34,160,90,.34)}70%{box-shadow:0 0 0 6px rgba(34,160,90,0)}100%{box-shadow:0 0 0 0 rgba(34,160,90,0)}}
.classic-login-meta time{font-variant-numeric:tabular-nums;text-align:right;font-weight:600;color:#7f8998}
.classic-last-access{margin-top:8px;text-align:center;color:#8992a3;font-size:10.5px;line-height:1.45}

body.large-ui .classic-title{font-size:23px}
body.large-ui .classic-sub{font-size:14px}
body.large-ui .classic-control input{font-size:15px;min-height:51px}
body.large-ui .classic-btn{height:53px;font-size:14px}

@media(max-width:560px){
  .login-card.classic-login{padding:28px 20px 26px;border-radius:16px}
  .classic-row{align-items:flex-start;gap:10px}
  .classic-login-meta{flex-direction:column;align-items:flex-start}
  .classic-login-meta time{text-align:left}
}

/* ============================================================
   TELAS DE FLUXO (senha, token, código, sucesso)
   ============================================================ */
.flow-stage{min-height:100vh;padding:24px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
.flow-stage::before,.flow-stage::after{content:"";position:absolute;border-radius:50%;pointer-events:none}
.flow-stage::before{width:520px;height:520px;left:-180px;top:-210px;background:radial-gradient(circle,rgba(200,148,47,.13),transparent 68%)}
.flow-stage::after{width:650px;height:650px;right:-260px;bottom:-290px;background:radial-gradient(circle,rgba(16,41,79,.12),transparent 67%)}
.flow-watermark{position:absolute;width:760px;height:760px;right:-250px;top:-160px;object-fit:contain;opacity:.07;filter:brightness(0) saturate(100%) invert(12%) sepia(22%) saturate(2600%) hue-rotate(184deg) brightness(86%) contrast(97%);mix-blend-mode:multiply;animation:drift 15s ease-in-out infinite;pointer-events:none}
.flow-shell{width:min(520px,100%);position:relative;z-index:2}
.flow-logo{width:230px;height:auto;margin:0 auto 18px;filter:brightness(0) saturate(100%) invert(12%) sepia(22%) saturate(2200%) hue-rotate(184deg) brightness(84%);opacity:.95}
.flow-card{padding:34px;border-radius:28px;background:rgba(255,255,255,.66);border:1px solid rgba(255,255,255,.87);box-shadow:var(--shadow-lg);backdrop-filter:blur(19px) saturate(125%);-webkit-backdrop-filter:blur(19px) saturate(125%);text-align:center;position:relative;overflow:hidden}
.flow-card::before{content:"";position:absolute;inset:0;border-radius:inherit;padding:1px;background:linear-gradient(145deg,rgba(216,173,86,.28),rgba(255,255,255,.16) 45%,rgba(16,41,79,.08));-webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none}
.flow-icon{width:60px;height:60px;border-radius:19px;display:grid;place-items:center;margin:0 auto 17px;color:var(--gold-600);background:linear-gradient(145deg,#fffdf6,#f1e7d2);border:1px solid rgba(200,148,47,.24);box-shadow:0 12px 28px rgba(200,148,47,.11);animation:pop .55s ease}
.flow-icon .ic{width:27px;height:27px}
.flow-icon.success{color:var(--green);background:linear-gradient(145deg,#f4fff9,#e1f4e9);border-color:rgba(31,157,103,.22)}
.flow-card h2{font-family:var(--serif);font-size:27px;line-height:1.05;color:var(--navy-900);outline:none}
.flow-sub{max-width:380px;margin:10px auto 0;color:var(--muted);font-size:11.8px;line-height:1.7}
.flow-card form{margin-top:22px;text-align:left}
.flow-back{display:flex;align-items:center;justify-content:center;gap:7px;margin:16px auto 0;border:0;background:transparent;color:#61718b;font-size:10.5px;font-weight:800;padding:8px 12px;border-radius:10px;transition:.2s}
.flow-back:hover{background:rgba(255,255,255,.58);color:var(--navy-800)}
.flow-back .ic{width:15px;height:15px}
.flow-foot{margin-top:14px;display:flex;align-items:center;justify-content:center;gap:9px;color:#8894a6;font-size:9.5px;text-align:center}
.flow-foot img{height:25px;width:auto;opacity:.84}
.sent{display:none}
.sent.show{display:block;animation:fadeUp .42s ease}
.sent-actions{margin-top:20px}

.code-label{text-align:center!important;justify-content:center!important}
.code-wrap{display:flex;gap:9px;justify-content:center;margin:13px 0 7px}
.code-input{width:48px;height:56px;border-radius:13px;border:1px solid rgba(16,41,79,.14);outline:none;text-align:center;font-size:20px;font-weight:800;color:var(--navy-900);background:rgba(255,255,255,.82);transition:.2s ease}
.code-input:focus{transform:translateY(-3px);border-color:rgba(200,148,47,.70);box-shadow:0 0 0 4px rgba(200,148,47,.10),0 10px 20px rgba(6,17,38,.05);background:#fff}
.code-input.filled{border-color:rgba(31,157,103,.42);background:#f3fbf7;animation:pop .2s ease}
.code-error{display:none;text-align:center;color:var(--red);font-size:10.5px;font-weight:700;margin-top:7px}
.resend{text-align:center;color:#7d899b;font-size:10.5px;margin:12px 0 17px}
.resend .link{font-size:10.5px}
.spinner-gold{width:42px;height:42px;border-radius:50%;border:3px solid rgba(200,148,47,.18);border-top-color:var(--gold-500);margin:22px auto 0;animation:spin .85s linear infinite}
.success-check{position:relative}
.success-check::after{content:"";position:absolute;inset:-8px;border:1px solid rgba(31,157,103,.13);border-radius:23px;animation:pulse 2.4s infinite}

/* ============================================================
   ACESSIBILIDADE E RESPONSIVIDADE
   ============================================================ */
body.large-ui{font-size:108%}
body.large-ui .login-card,body.large-ui .flow-card{width:100%}
body.large-ui .control input{font-size:14px;height:52px}

@media (max-width:1180px){
  .login-stage{grid-template-columns:minmax(0,1fr) minmax(400px,.85fr)}
  .brand-panel{padding:36px}
  .brand-copy{margin-top:70px}
  .brand-copy h1{font-size:clamp(40px,5vw,60px)}
  .capabilities{grid-template-columns:1fr}
  .capability{display:grid;grid-template-columns:38px 1fr;column-gap:12px;align-items:center;padding:12px 14px}
  .cap-icon{grid-row:1/3;margin:0}
}
@media (max-width:900px){
  .login-stage{display:block;padding:0}
  .brand-panel{min-height:auto;border-radius:0;padding:28px 24px 30px;box-shadow:none}
  .brand-panel::after{opacity:.55}
  .brand-logo{width:250px}
  .restricted-badge{font-size:9px;padding:7px 9px}
  .brand-copy{margin-top:42px;max-width:650px}
  .brand-copy h1{font-size:clamp(36px,10vw,56px)}
  .brand-copy>p{font-size:13px}
  .capabilities{display:none}
  .brand-bottom{padding-top:24px}
  .access-panel{min-height:auto;border-radius:0;padding:42px 18px 48px;box-shadow:none;border:0}
  .access-watermark{width:520px;height:520px;right:-220px;top:-70px}
}
@media (max-width:560px){
  .brand-panel{padding:24px 18px 26px}
  .brand-top{align-items:center}
  .brand-logo{width:210px;max-width:65%}
  .restricted-badge{max-width:140px;white-space:normal;text-align:center;justify-content:center}
  .brand-copy{margin-top:36px}
  .brand-copy h1{font-size:37px}
  .brand-copy>p{font-size:12.2px;line-height:1.7}
  .brand-bottom{align-items:center}
  .brand-time{display:none}
  .system-card{width:100%}
  .access-panel{padding:34px 13px 42px}
  .access-minihead{margin-bottom:12px}
  .login-card,.flow-card{padding:27px 20px;border-radius:22px}
  .card-heading{grid-template-columns:1fr;text-align:center;gap:10px}
  .admin-mark{margin:0 auto}
  .card-heading h2{font-size:23px}
  .access-note{text-align:left}
  .form-options{align-items:flex-start}
  .security-strip{grid-template-columns:1fr}
  .connection{justify-content:flex-start}
  .flow-stage{padding:18px 12px}
  .flow-logo{width:190px}
  .flow-card h2{font-size:25px}
  .code-wrap{gap:5px}
  .code-input{width:42px;height:52px;border-radius:11px}
}
@media (max-width:385px){
  .brand-copy h1{font-size:33px}
  .form-options{flex-direction:column;gap:10px}
  .code-input{width:38px;height:49px}
}
@media (hover:none){.btn:hover,.capability:hover,.font-tool:hover{transform:none}}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}}


/* ============================================================
   AJUSTE FINAL — CENTRALIZAÇÃO HORIZONTAL + VERTICAL DOS ÍCONES
   ============================================================ */
svg.ic{
  display:block;
  flex:0 0 auto;
  vertical-align:middle;
}

/* Contêineres quadrados/circulares de ícones */
.cap-icon,
.system-icon,
.admin-mark,
.security-icon,
.flow-icon,
.control .right-action{
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
  line-height:0 !important;
}

.cap-icon > svg.ic,
.system-icon > svg.ic,
.admin-mark > svg.ic,
.security-icon > svg.ic,
.flow-icon > svg.ic,
.control .right-action > svg.ic{
  margin:0 !important;
  align-self:center !important;
  justify-self:center !important;
  position:static !important;
  top:auto !important;
  left:auto !important;
  right:auto !important;
  bottom:auto !important;
  transform:none !important;
}

/* Botões: texto e ícone no mesmo eixo visual */
.btn,
.flow-back{
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
}
.btn > svg.ic,
.flow-back > svg.ic{
  margin:0 !important;
  align-self:center !important;
  transform:translateY(0) !important;
}

/* Avisos e linhas informativas */
.access-note,
.caps-warning,
.security-info,
.portal-tag,
.connection,
.restricted-badge,
.system-card{
  align-items:center !important;
}
.access-note > svg.ic,
.caps-warning > svg.ic{
  margin-top:0 !important;
  align-self:center !important;
}

/* Ícones posicionados dentro dos inputs continuam exatamente no meio */
.control > svg.left-icon{
  top:50% !important;
  margin:0 !important;
  transform:translateY(-50%) !important;
}



/* ============================================================
   TOKEN LARAVEL — complementos da versão PHP
   ============================================================ */
.token-shell{width:min(560px,100%)}
.token-card{padding:34px}
.token-section-title{display:flex;align-items:center;gap:9px;margin:2px 0 4px;color:var(--navy-800);font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase}
.token-section-title .ic{width:16px;height:16px;color:var(--gold-600)}
.token-divider{display:flex;align-items:center;gap:12px;margin:22px 0 4px;color:#97a1b0;font-size:9.5px;font-weight:800;letter-spacing:.15em;text-transform:uppercase}
.token-divider::before,.token-divider::after{content:"";height:1px;flex:1;background:linear-gradient(90deg,transparent,rgba(16,41,79,.12))}
.token-divider::after{background:linear-gradient(90deg,rgba(16,41,79,.12),transparent)}
.token-hint{display:flex;align-items:flex-start;gap:8px;margin:8px 0 0;color:#7d899b;font-size:10.5px;line-height:1.55}
.token-hint .ic{width:15px;height:15px;color:var(--gold-600);margin-top:1px}
.server-notice,.server-error{display:flex;align-items:flex-start;gap:10px;margin:18px 0 2px;padding:12px 13px;border-radius:13px;font-size:10.8px;font-weight:700;line-height:1.55;text-align:left}
.server-notice{color:#276a4b;background:rgba(31,157,103,.08);border:1px solid rgba(31,157,103,.16)}
.server-error{color:#9e3f3f;background:rgba(209,79,79,.075);border:1px solid rgba(209,79,79,.15)}
.server-notice .ic,.server-error .ic{width:17px;height:17px;flex:none;margin-top:1px}
.server-notice .ic{color:var(--green)}
.server-error .ic{color:var(--red)}
.token-expiry{margin-top:18px;padding:12px 13px;border-radius:13px;background:rgba(8,24,50,.04);border:1px solid rgba(8,24,50,.06);display:flex;gap:10px;align-items:center;text-align:left}
.token-expiry .ic{width:18px;height:18px;color:var(--gold-600);flex:none}
.token-expiry strong{display:block;color:#4c5a71;font-size:10.7px}
.token-expiry span{display:block;margin-top:2px;color:#8a95a6;font-size:9.7px;line-height:1.45}
.token-card .field{margin-top:14px}
.token-card .btn{margin-top:16px}
.token-card .flow-sub{max-width:430px}
.token-card .control input[name="phrase"]{padding-left:42px}
.token-back{display:flex;align-items:center;justify-content:center;gap:7px;margin:16px auto 0;color:#61718b;font-size:10.5px;font-weight:800;text-decoration:none;padding:8px 12px;border-radius:10px;transition:.2s;width:max-content}
.token-back:hover{background:rgba(255,255,255,.58);color:var(--navy-800)}
.token-back .ic{width:15px;height:15px}
@media(max-width:560px){.token-card{padding:27px 20px}.token-divider{margin-top:20px}}
</style>
</head>
<body>
<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
  <symbol id="i-shield-gear" viewBox="0 0 24 24"><path d="M12 2l8 3v6c0 5.2-3.4 9.4-8 11-4.6-1.6-8-5.8-8-11V5z"/><circle cx="12" cy="11" r="2.6"/><path d="M12 7.4v1.2M12 13.4v1.2M8.4 11h1.2M14.4 11h1.2M9.6 8.6l.9.9M13.5 12.5l.9.9M14.4 8.6l-.9.9M10.5 12.5l-.9.9"/></symbol>
  <symbol id="i-shield-lock" viewBox="0 0 24 24"><path d="M12 2l8 3v6c0 5.2-3.4 9.4-8 11-4.6-1.6-8-5.8-8-11V5z"/><rect x="9.2" y="10.6" width="5.6" height="4.4" rx="1"/><path d="M10.4 10.6V9.3a1.6 1.6 0 0 1 3.2 0v1.3"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></symbol>
  <symbol id="i-lock" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></symbol>
  <symbol id="i-eye" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></symbol>
  <symbol id="i-eye-off" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16-3.19m-6.72-1.07a3 3 0 1 1-4.24 4.24"/><line x1="1" y1="1" x2="23" y2="23"/></symbol>
  <symbol id="i-key" viewBox="0 0 24 24"><circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3 21 2M18 5l3 3"/></symbol>
  <symbol id="i-mail" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,6 12,13 2,6"/></symbol>
  <symbol id="i-send" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></symbol>
  <symbol id="i-check-circle" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m8 12.5 2.8 2.8L16 9.5"/></symbol>
  <symbol id="i-arrow-left" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></symbol>
  <symbol id="i-layout" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></symbol>
  <symbol id="i-book" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></symbol>
  <symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21h-4v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3v-4h.1a1.7 1.7 0 0 0 1.5-1A1.7 1.7 0 0 0 4.3 7l-.1-.1L7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V3h4v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.1v4h-.1a1.7 1.7 0 0 0-1.5 1z"/></symbol>
  <symbol id="i-wifi" viewBox="0 0 24 24"><path d="M5 12.6a10 10 0 0 1 14 0M8.5 16.1a5 5 0 0 1 7 0M12 20h.01M2 9.1a15 15 0 0 1 20 0"/></symbol>
  <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
  <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3 2.5 20h19z"/><path d="M12 9v4M12 17h.01"/></symbol>
  <symbol id="i-shield-check" viewBox="0 0 24 24"><path d="M12 2l8 3v6c0 5.2-3.4 9.4-8 11-4.6-1.6-8-5.8-8-11V5z"/><path d="m8.5 11.5 2.2 2.2 4.8-5"/></symbol>
</svg>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<section class="screen active" id="sc-token">
  <main class="flow-stage">
    <img class="flow-watermark" src="{{ asset('assets/images/logo.png') }}" alt="" aria-hidden="true">

    <div class="flow-shell token-shell">
      <img class="flow-logo" src="{{ asset('assets/images/logo.png') }}" alt="CETSADP">

      <div class="flow-card token-card">
        <span class="flow-icon">
          <svg class="ic" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet"><use href="#i-key"/></svg>
        </span>

        <h2 tabindex="-1">Acesso com token</h2>
        <p class="flow-sub">Solicite uma combinação temporária de <strong style="color:#42516a">24 palavras</strong> enviada ao e-mail do administrador e use-a para acessar o painel com segurança.</p>

        @if (session('token_requested'))
          <div class="server-notice" role="status">
            <svg class="ic" viewBox="0 0 24 24"><use href="#i-check-circle"/></svg>
            <span>Se o e-mail informado pertencer a uma conta administrativa ativa, as instruções foram enviadas. Verifique também a caixa de spam.</span>
          </div>
        @endif

        @if ($errors->any())
          <div class="server-error" role="alert">
            <svg class="ic" viewBox="0 0 24 24"><use href="#i-alert"/></svg>
            <span>{{ $errors->first() }}</span>
          </div>
        @endif

        <div class="token-expiry">
          <svg class="ic" viewBox="0 0 24 24"><use href="#i-clock"/></svg>
          <div>
            <strong>Token temporário e de uso único</strong>
            <span>A combinação expira em 10 minutos e deixa de funcionar depois de ser utilizada.</span>
          </div>
        </div>

        <form method="POST" action="{{ route('admin.token.request') }}" class="token-form" data-loading-form>
          @csrf
          <div class="token-section-title">
            <svg class="ic" viewBox="0 0 24 24"><use href="#i-send"/></svg>
            Solicitar combinação
          </div>

          <div class="field{{ $errors->has('email') ? ' invalid' : '' }}">
            <label for="request-email">E-mail administrativo</label>
            <div class="control">
              <input id="request-email" name="email" type="email" value="{{ old('email') }}" placeholder="Digite seu e-mail cadastrado" autocomplete="email" autocapitalize="off" required>
              <svg class="ic left-icon" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet"><use href="#i-mail"/></svg>
            </div>
          </div>

          <button class="btn btn-primary" type="submit">
            <svg class="ic" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet"><use href="#i-send"/></svg>
            Enviar combinação por e-mail
          </button>
        </form>

        <div class="token-divider">Já recebeu o token?</div>

        <form method="POST" action="{{ route('admin.token.authenticate') }}" class="token-form" data-loading-form>
          @csrf
          <div class="token-section-title">
            <svg class="ic" viewBox="0 0 24 24"><use href="#i-shield-check"/></svg>
            Validar acesso
          </div>

          <div class="field">
            <label for="auth-email">E-mail administrativo</label>
            <div class="control">
              <input id="auth-email" name="email" type="email" value="{{ old('email') }}" placeholder="Digite o mesmo e-mail" autocomplete="email" autocapitalize="off" required>
              <svg class="ic left-icon" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet"><use href="#i-mail"/></svg>
            </div>
          </div>

          <div class="field">
            <label for="phrase">Combinação de 24 palavras</label>
            <div class="control">
              <input id="phrase" name="phrase" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="512" placeholder="Cole aqui as 24 palavras recebidas" required>
              <svg class="ic left-icon" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet"><use href="#i-key"/></svg>
            </div>
            <p class="token-hint">
              <svg class="ic" viewBox="0 0 24 24"><use href="#i-shield-lock"/></svg>
              <span>Separe as palavras por espaços. Nunca compartilhe esta combinação com terceiros.</span>
            </p>
          </div>

          <button class="btn btn-primary" type="submit">
            <svg class="ic" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet"><use href="#i-shield-check"/></svg>
            Validar e entrar no painel
          </button>
        </form>
      </div>

      <a class="token-back" href="{{ url('/admin/login') }}">
        <svg class="ic" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet"><use href="#i-arrow-left"/></svg>
        Voltar para o login com senha
      </a>

      <div class="flow-foot">
        <img src="{{ asset('assets/images/logo3.png') }}" alt="IEADP">
        Igreja Evangélica Assembleia de Deus em Paracambi
      </div>
    </div>
  </main>
</section>


<script>
'use strict';
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let toastTimer;
function toast(msg,tipo='info'){
  const t=$('#toast');
  if(!t) return;
  t.textContent=msg;
  t.className='toast show '+tipo;
  clearTimeout(toastTimer);
  toastTimer=setTimeout(()=>t.classList.remove('show'),3600);
}

$$('.field input').forEach(inp=>{
  const field=inp.closest('.field');
  const sync=()=>field && field.classList.toggle('has-value',!!inp.value.trim());
  inp.addEventListener('input',sync);
  inp.addEventListener('blur',sync);
  sync();
});

$$('.btn').forEach(btn=>{
  btn.addEventListener('pointerdown',e=>{
    if(prefersReducedMotion||btn.disabled) return;
    const r=btn.getBoundingClientRect();
    const size=Math.max(r.width,r.height);
    const span=document.createElement('span');
    span.className='ripple';
    span.style.width=span.style.height=size+'px';
    span.style.left=(e.clientX-r.left-size/2)+'px';
    span.style.top=(e.clientY-r.top-size/2)+'px';
    btn.appendChild(span);
    span.addEventListener('animationend',()=>span.remove());
  });
});

$$('[data-loading-form]').forEach(form=>{
  form.addEventListener('submit',()=>{
    const btn=form.querySelector('button[type="submit"]');
    if(btn){btn.classList.add('loading');btn.disabled=true;}
  });
});
</script>

</body>
</html>
