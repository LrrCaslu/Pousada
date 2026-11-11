/** Costa Serena - gráficos locais e edição segura das páginas.
 * Não depende de CDNs nem de npm para o modo ServBay com assets públicos.
 */
(() => {
  'use strict';
  const svgNS = 'http://www.w3.org/2000/svg';
  const ptNumber = new Intl.NumberFormat('pt-BR', {maximumFractionDigits: 1});

  function element(tag, attributes = {}, text = null) {
    const node = document.createElementNS(svgNS, tag);
    for (const [key, value] of Object.entries(attributes)) node.setAttribute(key, String(value));
    if (text !== null) node.textContent = String(text);
    return node;
  }

  function svgText(svg, value, x, y, options = {}) {
    svg.appendChild(element('text', {
      x, y, 'font-size': options.size || 11, fill: options.color || '#91a397',
      'text-anchor': options.anchor || 'start', 'font-family': 'Segoe UI, Arial, sans-serif',
      'font-weight': options.weight || 500
    }, value));
  }

  function chart(container, data, type) {
    const labels = Array.isArray(data.labels) ? data.labels : [];
    const values = type === 'finance' ? data.received : data[type];
    const main = Array.isArray(values) ? values.map(Number) : [];
    const secondary = type === 'finance' ? (data.expenses || []).map(Number) : [];
    if (!labels.length || !main.length || main.length !== labels.length) {
      container.textContent = 'Sem dados para o período selecionado.';
      container.classList.add('bi-chart-empty');
      return;
    }
    const width = 900, height = 245;
    const left = 47, right = 12, top = 20, bottom = 35;
    const plotW = width - left - right, plotH = height - top - bottom;
    const allValues = main.concat(secondary).map(v => Number.isFinite(v) ? v : 0);
    const largest = Math.max(0, ...allValues);
    const maxY = type === 'occupancy' ? 100 : Math.max(type === 'reservations' ? 1 : 10, largest * 1.14);
    const x = i => left + (labels.length === 1 ? plotW / 2 : (i * plotW / (labels.length - 1)));
    const y = value => top + plotH - (Math.min(maxY, Math.max(0, value)) / maxY) * plotH;
    const svg = element('svg', {viewBox: `0 0 ${width} ${height}`, preserveAspectRatio: 'none', 'aria-hidden':'true'});

    for (let step = 0; step <= 4; step++) {
      const value = maxY * step / 4;
      const yy = y(value);
      svg.appendChild(element('line', {x1:left, y1:yy, x2:width-right, y2:yy, stroke:'#e9efea', 'stroke-width':1, 'stroke-dasharray':step===0?'':'4 5'}));
      const text = type === 'occupancy' ? `${Math.round(value)}%` : value >= 1000 ? `${ptNumber.format(value/1000)} mil` : ptNumber.format(value);
      svgText(svg, text, left - 9, yy + 4, {anchor:'end', size:10});
    }

    const tickStep = Math.max(1, Math.ceil(labels.length / 7));
    labels.forEach((label, i) => {
      if (i % tickStep === 0 || i === labels.length - 1) {
        svgText(svg, label, x(i), height - 9, {anchor:'middle', size:10});
      }
    });

    if (type === 'reservations') {
      const barWidth = Math.max(1.5, Math.min(24, plotW / Math.max(1, labels.length) * 0.58));
      main.forEach((value, i) => {
        const yy = y(value), bottomY = y(0);
        const bar = element('rect', {x:x(i) - barWidth / 2, y:yy, width:barWidth, height:Math.max(0.5, bottomY - yy), rx:2, fill:'#c49a62'});
        bar.appendChild(element('title', {}, `${labels[i]} · ${ptNumber.format(value)} reserva(s)`));
        svg.appendChild(bar);
      });
    } else {
      function drawLine(values, color, fill, unit, useArea) {
        const points = values.map((v, i) => `${x(i)},${y(v)}`);
        if (useArea) {
          const d = `M ${x(0)} ${y(0)} L ${points.join(' L ')} L ${x(values.length - 1)} ${y(0)} Z`;
          svg.appendChild(element('path', {d, fill, stroke:'none'}));
        }
        const line = element('path', {d:`M ${points.join(' L ')}`, fill:'none', stroke:color, 'stroke-width':3, 'stroke-linejoin':'round','stroke-linecap':'round'});
        svg.appendChild(line);
        const stride = Math.max(1, Math.ceil(labels.length / 24));
        values.forEach((v, i) => {
          if (i % stride !== 0 && i !== labels.length - 1) return;
          const dot = element('circle', {cx:x(i),cy:y(v),r:3.2,fill:color,stroke:'#fff','stroke-width':1.2});
          dot.appendChild(element('title',{},`${labels[i]} · ${type === 'occupancy' ? ptNumber.format(v) + '%' : unit + ptNumber.format(v)}`));
          svg.appendChild(dot);
        });
      }
      if (type === 'occupancy') drawLine(main, '#388962', '#38896219', '%', true);
      if (type === 'finance') {
        drawLine(main, '#388962', 'transparent', 'R$ ', false);
        drawLine(secondary, '#c49a62', 'transparent', 'R$ ', false);
      }
    }
    container.replaceChildren(svg);
  }

  const jsonData = document.getElementById('bi-dashboard-data');
  if (jsonData) {
    try {
      const data = JSON.parse(jsonData.textContent);
      document.querySelectorAll('[data-bi-chart]').forEach(slot => chart(slot, data, slot.dataset.biChart));
    } catch (error) {
      document.querySelectorAll('[data-bi-chart]').forEach(slot => {slot.textContent = 'Não foi possível renderizar o gráfico.';});
      console.warn('Dashboard: dados dos gráficos inválidos.', error);
    }
  }

  const search = document.getElementById('pageSearch');
  if (search) {
    const items = [...document.querySelectorAll('[data-page-filter]')];
    const empty = document.getElementById('pageSearchEmpty');
    search.addEventListener('input', () => {
      const query = search.value.toLocaleLowerCase('pt-BR').trim();
      let shown = 0;
      items.forEach(item => {
        const matches = item.dataset.pageFilter.includes(query);
        item.hidden = !matches;
        if (matches) shown++;
      });
      if (empty) empty.hidden = shown !== 0;
    });
  }

  const editForm = document.getElementById('pageEditForm');
  if (editForm) {
    const title = document.getElementById('pageTitle');
    const body = document.getElementById('pageBody');
    const previewTitle = document.getElementById('previewTitle');
    const previewBody = document.getElementById('previewBody');
    const count = document.getElementById('pageCharCount');
    const published = document.getElementById('pagePublished');
    const publishValue = document.getElementById('pagePublishedValue');
    const publishText = document.getElementById('pagePublishText');
    const dirtyNote = document.querySelector('.editor-save-note');
    const dirtyText = document.getElementById('editorDirtyText');
    let dirty = false;

    const refresh = () => {
      if (previewTitle) previewTitle.textContent = title.value || 'Página sem título';
      if (previewBody) previewBody.textContent = body.value || 'Adicione o conteúdo desta página.';
      if (count) count.textContent = `${new Intl.NumberFormat('pt-BR').format(body.value.length)} / 40.000 caracteres`;
      if (publishValue) publishValue.value = published.checked ? '1' : '0';
      if (publishText) publishText.textContent = published.checked ? 'Publicada' : 'Não publicada';
    };
    const changed = () => {
      dirty = true;
      if (dirtyText) dirtyText.textContent = 'Há alterações não salvas nesta página.';
      if (dirtyNote) dirtyNote.classList.add('is-dirty');
      refresh();
    };
    title.addEventListener('input', changed);
    body.addEventListener('input', changed);
    published.addEventListener('change', changed);
    refresh();

    const write = document.getElementById('editorWrite');
    const preview = document.getElementById('editorPreview');
    const tabWrite = document.getElementById('editorTabWrite');
    const tabPreview = document.getElementById('editorTabPreview');
    function switchTab(showPreview) {
      write.hidden = showPreview;
      preview.hidden = !showPreview;
      tabWrite.classList.toggle('is-current', !showPreview);
      tabPreview.classList.toggle('is-current', showPreview);
      tabWrite.setAttribute('aria-selected', String(!showPreview));
      tabPreview.setAttribute('aria-selected', String(showPreview));
      if (showPreview) refresh();
    }
    tabWrite.addEventListener('click', () => switchTab(false));
    tabPreview.addEventListener('click', () => switchTab(true));
    editForm.addEventListener('submit', () => {dirty = false;});
    window.addEventListener('beforeunload', event => {
      if (dirty) {event.preventDefault(); event.returnValue = '';}
    });
  }

  // Command palette: only already authorized links are rendered by Blade.
  const commandToggle = document.getElementById('adminCommandToggle');
  const commandPanel = document.getElementById('adminCommandPanel');
  const commandInput = document.getElementById('adminCommandInput');
  if (commandToggle && commandPanel && commandInput) {
    const links = [...commandPanel.querySelectorAll('#adminCommandLinks a')];
    const empty = document.getElementById('adminCommandEmpty');
    const close = () => {commandPanel.hidden = true; commandToggle.setAttribute('aria-expanded','false');};
    const open = () => {commandPanel.hidden = false; commandToggle.setAttribute('aria-expanded','true'); commandInput.focus();};
    commandToggle.addEventListener('click', () => commandPanel.hidden ? open() : close());
    commandInput.addEventListener('input', () => {
      const value = commandInput.value.trim().toLocaleLowerCase('pt-BR');
      let visible = 0;
      links.forEach(link => {const match = link.textContent.toLocaleLowerCase('pt-BR').includes(value);link.hidden = !match;if(match)visible++;});
      if (empty) empty.hidden = visible > 0;
    });
    document.addEventListener('keydown', e => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {e.preventDefault();commandPanel.hidden ? open() : close();}
      if (e.key === 'Escape' && !commandPanel.hidden) {close();commandToggle.focus();}
    });
    document.addEventListener('click', e => {if (!commandPanel.hidden && !e.target.closest('.admin-command')) close();});
  }

  // Exporta exatamente as series filtradas e disponibilizadas ao perfil atual.
  const exportBtn = document.getElementById('biExportCsv');
  if (exportBtn && jsonData) {
    exportBtn.addEventListener('click', () => {
      try {
        const d = JSON.parse(jsonData.textContent);
        const finance = Array.isArray(d.received) && Array.isArray(d.expenses) && d.received.length > 0;
        const cols = ['Dia', 'Ocupacao (%)', 'Novas reservas'];
        if (finance) cols.push('Recebido (R$)', 'Despesas (R$)');
        const rows = [cols];
        (d.labels || []).forEach((day,i) => {
          const row = [day, Number(d.occupancy?.[i] || 0), Number(d.reservations?.[i] || 0)];
          if (finance) row.push(Number(d.received?.[i] || 0), Number(d.expenses?.[i] || 0));
          rows.push(row);
        });
        const quoted = val => '"' + String(val).replace(/"/g,'""') + '"';
        const csv = '\uFEFF' + rows.map(r=>r.map(quoted).join(';')).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], {type:'text/csv;charset=utf-8'}));
        const a = document.createElement('a'); a.href = url; a.download = 'costa-serena-indicadores.csv';
        document.body.appendChild(a); a.click(); a.remove(); setTimeout(()=>URL.revokeObjectURL(url),1000);
      } catch (error) {alert('Nao foi possivel exportar os dados do grafico.');}
    });
  }

  // Visualizacao e selecao da imagem principal do site (persistencia via POST com CSRF).
  const mediaForm = document.getElementById('mediaLibraryForm');
  if (mediaForm) {
    const hero = document.getElementById('mediaHeroPreview');
    const label = document.getElementById('mediaPreviewLabel');
    mediaForm.querySelectorAll('input[name="hero_photo"]').forEach(input => {
      input.addEventListener('change', () => {
        mediaForm.querySelectorAll('.media-choice').forEach(choice=>choice.classList.toggle('is-selected',choice.contains(input)));
        if(hero) hero.src = input.dataset.heroUrl;
        if(label) label.textContent = input.dataset.photoLabel;
      });
    });
  }

})();
