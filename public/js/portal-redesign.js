/* Navegação e leitura de agenda; nunca envia formulário, pagamento ou progresso. */
(() => {
  'use strict';
  const root = document.querySelector('.portal-app');
  if (!root) return;
  const menu = document.getElementById('portal-menu');
  const overlay = document.querySelector('[data-portal-overlay]');
  const triggers = [...document.querySelectorAll('[data-portal-open]')];
  let opener = null;
  let savedOverflow = '';
  const mobile = window.matchMedia('(max-width: 900px)');
  const setMenu = (open) => {
    if (!menu || !overlay) return;
    if (open && !mobile.matches) return;
    root.classList.toggle('portal-menu-is-open', open);
    overlay.hidden = !open;
    triggers.forEach((el) => el.setAttribute('aria-expanded', String(open)));
    if (open) {
      savedOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      menu.setAttribute('role', 'dialog');
      menu.setAttribute('aria-modal', 'true');
      document.querySelector('.portal-workspace')?.setAttribute('inert', '');
      document.querySelector('.portal-bottom-nav')?.setAttribute('inert', '');
      menu.querySelector('[data-portal-close]')?.focus();
    } else {
      document.body.style.overflow = savedOverflow;
      menu.removeAttribute('role');
      menu.removeAttribute('aria-modal');
      document.querySelector('.portal-workspace')?.removeAttribute('inert');
      document.querySelector('.portal-bottom-nav')?.removeAttribute('inert');
      if (opener && mobile.matches) opener.focus();
    }
  };
  triggers.forEach((el) => el.addEventListener('click', () => { opener = el; setMenu(true); }));
  document.querySelector('[data-portal-close]')?.addEventListener('click', () => setMenu(false));
  overlay?.addEventListener('click', () => setMenu(false));
  document.addEventListener('keydown', (event) => {
    if (!root.classList.contains('portal-menu-is-open')) return;
    if (event.key === 'Escape') { event.preventDefault(); setMenu(false); }
    if (event.key === 'Tab') {
      const focusable = [...menu.querySelectorAll('a[href],button:not([disabled]),input:not([disabled])')].filter((el) => el.getClientRects().length);
      const first = focusable[0], last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
  });
  mobile.addEventListener('change', () => { if (!mobile.matches) setMenu(false); });

  document.querySelectorAll('.portal-main .overflow-x-auto').forEach((el) => { if (!el.hasAttribute('tabindex')) el.tabIndex=0; if (!el.hasAttribute('role')) el.setAttribute('role','region'); if (!el.hasAttribute('aria-label')) el.setAttribute('aria-label','Tabela com rolagem horizontal'); });

  const monthLabel = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' });
  const fullDate = new Intl.DateTimeFormat('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' });
  const monthShort = new Intl.DateTimeFormat('pt-BR', { month: 'short' });
  const timeLabel = new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' });
  const dateKey = (date) => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
  const parseDate = (value) => typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value) ? new Date(value+'T12:00:00') : new Date(value);
  const eventDates = new Set();
  const calendars = [];
  document.querySelectorAll('[data-portal-calendar]').forEach((calendar) => {
    const now = new Date();
    let showing = new Date(now.getFullYear(), now.getMonth(), 1);
    const render = () => {
      calendar.querySelector('[data-calendar-title]').textContent = monthLabel.format(showing);
      const days = calendar.querySelector('[data-calendar-days]');
      days.replaceChildren();
      const firstDay = new Date(showing.getFullYear(), showing.getMonth(), 1).getDay();
      const count = new Date(showing.getFullYear(), showing.getMonth()+1, 0).getDate();
      const cells = Math.ceil((firstDay+count)/7)*7;
      for (let i=0; i<cells; i++) {
        const date = new Date(showing.getFullYear(), showing.getMonth(), i-firstDay+1);
        const cell = document.createElement('span');
        cell.textContent = String(date.getDate());
        cell.setAttribute('aria-label', fullDate.format(date));
        if (date.getMonth() !== showing.getMonth()) cell.classList.add('is-muted');
        if (dateKey(date) === dateKey(now)) { cell.classList.add('is-today'); cell.setAttribute('aria-current','date'); }
        if (eventDates.has(dateKey(date))) { cell.classList.add('has-event'); cell.setAttribute('aria-label', fullDate.format(date)+', evento na agenda'); }
        days.append(cell);
      }
    };
    calendar.querySelector('[data-calendar-prev]').addEventListener('click', () => { showing = new Date(showing.getFullYear(), showing.getMonth()-1, 1); render(); });
    calendar.querySelector('[data-calendar-next]').addEventListener('click', () => { showing = new Date(showing.getFullYear(), showing.getMonth()+1, 1); render(); });
    calendars.push(render); render();
  });
  const node = (tag, className, text) => { const el=document.createElement(tag); if(className) el.className=className; if(text != null) el.textContent=String(text); return el; };
  document.querySelectorAll('[data-portal-agenda]').forEach((panel) => {
    let loaded = false;
    const list = panel.querySelector('[data-portal-events]');
    const load = async () => {
      if (loaded) return;
      loaded = true; list.setAttribute('aria-busy','true');
      const aborter = new AbortController();
      const timer = setTimeout(() => aborter.abort(), 15000);
      try {
        const response = await fetch(panel.dataset.portalAgenda, { credentials:'same-origin', headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}, signal:aborter.signal });
        if (!response.ok) throw new Error('agenda');
        const data = await response.json();
        if (!Array.isArray(data)) throw new Error('formato');
        const events = data.filter((event) => event && !Number.isNaN(parseDate(event.start).getTime())).sort((a,b) => parseDate(a.start)-parseDate(b.start));
        list.replaceChildren();
        events.forEach((event) => eventDates.add(dateKey(parseDate(event.start))));
        calendars.forEach((render) => render());
        if (!events.length) {
          const empty=node('div','portal-empty');
          empty.append(node('i','fa-regular fa-calendar-check'),node('h3','','Sua agenda está tranquila'),node('p','','Nenhuma aula ou evento previsto para os próximos 7 dias.'));
          empty.querySelector('i').setAttribute('aria-hidden','true'); list.append(empty);
        }
        events.slice(0,3).forEach((event) => {
          const date=parseDate(event.start), item=node('article','portal-agenda-item');
          const box=node('div','portal-date-box');box.append(node('small','',monthShort.format(date).replace('.','')),node('strong','',String(date.getDate()).padStart(2,'0')));
          const copy=node('div','portal-agenda-copy');copy.append(node('h3','',event.title || 'Evento acadêmico'));
          if(event.curso) copy.append(node('p','',event.curso));
          const meta=node('div','portal-agenda-meta');
          meta.append(node('span','',event.allDay ? 'Dia inteiro' : timeLabel.format(date)+(event.end && !Number.isNaN(parseDate(event.end).getTime()) ? ' – '+timeLabel.format(parseDate(event.end)) : '')));
          const presencial=event.modalidade === 'presencial';
          meta.append(node('span','portal-pill'+(presencial ? ' portal-pill--blue' : ''),event.type === 'aula_vivo' ? (presencial ? 'PRESENCIAL' : 'AO VIVO') : 'EVENTO'));
          copy.append(meta); item.append(box,copy);list.append(item);
        });
      } catch (_) {
        list.replaceChildren();const empty=node('div','portal-empty');empty.append(node('h3','','Não foi possível carregar a agenda'),node('p','','Tente novamente ou consulte o calendário completo.'));
        const retry=node('button','portal-button','Tentar novamente');retry.type='button';retry.addEventListener('click',()=>{loaded=false;load();});empty.append(retry);list.append(empty);
      } finally { clearTimeout(timer);list.setAttribute('aria-busy','false'); }
    };
    if ('IntersectionObserver' in window) { const observer=new IntersectionObserver((entries) => { if(entries.some((e)=>e.isIntersecting)){observer.disconnect();load();} },{rootMargin:'120px'});observer.observe(panel); } else load();
  });
})();
