(() => {
  'use strict';
  const toggle = document.getElementById('navToggle');
  const sidebar = document.getElementById('csAdminSidebar');
  if (toggle && sidebar) {
    // O script legado também alterna a classe; sincroniza apenas o estado ARIA após o clique.
    toggle.addEventListener('click', () => requestAnimationFrame(() => {
      const expanded = sidebar.classList.contains('open');
      toggle.setAttribute('aria-expanded', String(expanded));
      toggle.setAttribute('aria-label', expanded ? 'Fechar menu administrativo' : 'Abrir menu administrativo');
    }));
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); toggle.focus();
      }
    });
    document.addEventListener('click', event => {
      if (window.matchMedia('(max-width: 850px)').matches && sidebar.classList.contains('open') && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
        sidebar.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }
})();

(() => {
  'use strict';
  const grid = document.getElementById('reservation-calendar');
  if (!grid) return;
  const label = document.getElementById('csCalTitle');
  const details = document.getElementById('csCalDayEvents');
  const selectedTitle = document.getElementById('csCalSelectedTitle');
  const roomSelect = document.getElementById('csCalRoom');
  const prev = document.getElementById('csCalPrev');
  const next = document.getElementById('csCalNext');
  const todayButton = document.getElementById('csCalToday');
  const now = new Date();
  let year = now.getFullYear(), month = now.getMonth();
  let selectedDay = `${year}-${String(month + 1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
  let events = [];
  const iso = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
  const pretty = date => date.toLocaleDateString('pt-BR', {day:'2-digit',month:'long',year:'numeric'});
  function filtered() {
    const room = roomSelect?.value || '';
    return events.filter(e => !room || String(e.title || '').startsWith('Quarto ' + room + ' ·'));
  }
  function openDay(dateStr) {
    selectedDay = dateStr;
    const d = new Date(dateStr + 'T12:00:00');
    selectedTitle.textContent = pretty(d);
    const list = filtered().filter(e => e.start <= dateStr && dateStr < e.end);
    details.replaceChildren();
    if (list.length === 0) {
      const p = document.createElement('p'); p.textContent = 'Nenhuma reserva ou ocupação para este dia e filtro.'; details.append(p);
    } else {
      for (const event of list) {
        const row = document.createElement('div'); row.className = 'cs-calendar-entry';
        const strong = document.createElement('strong'); strong.textContent = event.title; row.append(strong);
        const small = document.createElement('small'); small.textContent = `${event.start} até ${event.end} (saída não inclusa)`; row.append(small);
        if (event.url) {
          try {
            const url = new URL(event.url, window.location.origin);
            if (url.origin === window.location.origin) {
              const a = document.createElement('a'); a.href = url.href; a.textContent = 'Consultar reserva ↗'; row.append(a);
            }
          } catch (_) { /* link indisponível */ }
        }
        details.append(row);
      }
    }
    grid.querySelectorAll('.cs-calendar-day').forEach(day => day.classList.toggle('is-selected', day.dataset.date === selectedDay));
  }
  function render() {
    label.textContent = new Date(year, month, 1).toLocaleDateString('pt-BR', {month:'long',year:'numeric'});
    grid.replaceChildren();
    const first = new Date(year, month, 1, 12);
    const mondayOffset = (first.getDay() + 6) % 7;
    const start = new Date(year, month, 1 - mondayOffset, 12);
    const nowIso = iso(now), list = filtered();
    for (let n = 0; n < 42; n++) {
      const d = new Date(start); d.setDate(d.getDate() + n);
      const dayIso = iso(d);
      const day = document.createElement('button'); day.type = 'button';
      day.className = 'cs-calendar-day'; day.dataset.date = dayIso;
      day.setAttribute('role', 'gridcell'); day.setAttribute('aria-label', pretty(d));
      if (d.getMonth() !== month) day.classList.add('is-outside');
      if (dayIso === nowIso) day.classList.add('is-today');
      if (dayIso === selectedDay) day.classList.add('is-selected');
      const number = document.createElement('span'); number.className = 'cs-day-number'; number.textContent = d.getDate(); day.append(number);
      const dayEvents = list.filter(e => e.start <= dayIso && dayIso < e.end);
      const wrap = document.createElement('span'); wrap.className = 'cs-day-events';
      dayEvents.slice(0, 2).forEach(event => {
        const chip = document.createElement('span');
        chip.className = 'cs-event-chip ' + String(event.className || '').replace(/[^a-zA-Z0-9_-]/g,'');
        chip.textContent = event.title; chip.title = event.title; wrap.append(chip);
      });
      if (dayEvents.length > 2) {const extra = document.createElement('small'); extra.className = 'cs-event-count';extra.textContent = `+${dayEvents.length-2} reserva(s)`;wrap.append(extra);}
      day.append(wrap);
      day.addEventListener('click', () => openDay(dayIso));
      grid.append(day);
    }
    openDay(selectedDay);
  }
  async function load() {
    grid.replaceChildren();
    const loading = document.createElement('p');loading.className='cs-calendar-loading';loading.textContent='Buscando ocupações...';grid.append(loading);
    const from = iso(new Date(year,month,1,12));
    const until = iso(new Date(year,month+1,1,12));
    try {
      const url = new URL(grid.dataset.url, window.location.origin);
      url.searchParams.set('start',from); url.searchParams.set('end',until);
      const response = await fetch(url, {headers:{'Accept':'application/json'},credentials:'same-origin'});
      if (!response.ok) throw new Error('HTTP ' + response.status);
      const data = await response.json();
      if (!Array.isArray(data)) throw new Error('Dados inválidos');
      events = data.filter(e => typeof e.start === 'string' && typeof e.end === 'string');
      render();
    } catch (error) {
      grid.replaceChildren();
      const p=document.createElement('p');p.className='cs-calendar-loading';p.textContent='Não foi possível carregar o calendário. Verifique a conexão e as permissões e atualize a página.';grid.append(p);
      label.textContent = new Date(year,month,1).toLocaleDateString('pt-BR',{month:'long',year:'numeric'});
    }
  }
  function move(delta) {const d=new Date(year,month+delta,1,12); year=d.getFullYear();month=d.getMonth();selectedDay=iso(d);load();}
  prev?.addEventListener('click',()=>move(-1));
  next?.addEventListener('click',()=>move(1));
  todayButton?.addEventListener('click',()=>{year=now.getFullYear();month=now.getMonth();selectedDay=iso(now);load();});
  roomSelect?.addEventListener('change',render);
  load();
})();

(() => {
  const dialog = document.getElementById('csNewPageModal');
  const opener = document.getElementById('csNewPage');
  if (!dialog || !opener) return;
  const close = () => dialog.close();
  opener.addEventListener('click', () => dialog.showModal());
  document.getElementById('csCloseNewPage')?.addEventListener('click', close);
  document.getElementById('csCancelNewPage')?.addEventListener('click', close);
  dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
})();
