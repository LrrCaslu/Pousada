/** Pousada Costa Serena V4 — gráficos SVG com dados reais entregues pelo Laravel.
 * Sem CDN ou dependências externas. O JavaScript não altera métricas financeiras. */
(() => {
  'use strict';
  const NS = 'http://www.w3.org/2000/svg';
  const br = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 });
  const chartSource = document.getElementById('bi-dashboard-data');

  function create(tag, attributes = {}, text) {
    const item = document.createElementNS(NS, tag);
    Object.entries(attributes).forEach(([key, value]) => item.setAttribute(key, String(value)));
    if (text !== undefined) item.textContent = String(text);
    return item;
  }
  function textNode(svg, label, x, y, options = {}) {
    const node = create('text', {
      x, y, fill: options.color || '#708178', 'font-size': options.size || 11,
      'font-family': "Inter, 'Segoe UI', Arial, sans-serif",
      'text-anchor': options.anchor || 'start', 'font-weight': options.weight || 550
    }, label);
    svg.append(node);
    return node;
  }
  const safe = value => Math.max(0, Number.isFinite(Number(value)) ? Number(value) : 0);

  // Um quarto ocupado em uma pousada de 12 quartos representa 8,33% da capacidade.
  // A altura das barras e da linha deve usar a MESMA escala; nunca normalizar pelo pico
  // observado no intervalo (isso fazia 1 quarto aparecer como uma barra de 100%).
  function occupancyScale(roomsOccupied, capacity, full = false) {
    const peak = Math.max(0, ...roomsOccupied);
    const peakPercent = capacity ? 100 * peak / capacity : 0;
    const autoFocus = capacity > 0 && peak > 0 && peakPercent < 35;
    if (full || !autoFocus) return { maximum: 100, focused: false };
    const maximum = Math.min(100, Math.max(15, Math.ceil(peakPercent * 1.65 / 5) * 5));
    return { maximum, focused: true };
  }
  function nicePercent(value) {
    return br.format(value).replace(/,0$/, '') + '%';
  }
  function drawOccupancy(container, data) {
    const labels = Array.isArray(data.labels) ? data.labels : [];
    const occupancy = Array.isArray(data.occupancy) ? data.occupancy.map(value => Math.min(100, safe(value))) : [];
    const capacity = Math.max(0, Math.floor(safe(data.totalRooms)));
    const rooms = Array.isArray(data.occupiedRooms) ? data.occupiedRooms.map(value => Math.min(capacity, Math.floor(safe(value)))) : [];
    if (!labels.length || labels.length !== occupancy.length || labels.length !== rooms.length || !capacity) {
      return noData(container);
    }
    const zoomButton = document.getElementById('refOccupancyZoom');
    const scaleBadge = document.getElementById('refOccupancyScale');
    const caption = document.getElementById('refOccupancyCaption');
    const isZoomable = occupancyScale(rooms, capacity).focused;
    let fullScale = !isZoomable;

    function render() {
      const { maximum, focused } = occupancyScale(rooms, capacity, fullScale);
      const width = 740, height = 270, left = 54, right = 52, top = 17, bottom = 40;
      const plotWidth = width - left - right, plotHeight = height - top - bottom;
      const baseline = top + plotHeight;
      const atX = index => left + (index + .5) * plotWidth / labels.length;
      const atY = percent => baseline - Math.min(maximum, Math.max(0, percent)) / maximum * plotHeight;
      const barW = Math.min(18, Math.max(1.6, plotWidth / labels.length * .58));
      const svg = create('svg', { viewBox: `0 0 ${width} ${height}`, preserveAspectRatio: 'xMidYMid meet', role: 'presentation', focusable: 'false', 'data-scale-percent': maximum, 'data-capacity': capacity });
      const defs = create('defs');
      const gradient = create('linearGradient', { id: 'refOccupancyArea', x1: '0', x2: '0', y1: '0', y2: '1' });
      gradient.append(create('stop', { offset: '0%', 'stop-color': '#0b8c5d', 'stop-opacity': '.18' }));
      gradient.append(create('stop', { offset: '100%', 'stop-color': '#0b8c5d', 'stop-opacity': '.015' }));
      defs.append(gradient);
      svg.append(defs);

      const stepCount = focused && maximum <= 30 ? maximum / 5 : 4;
      for (let i = 0; i <= stepCount; i++) {
        const value = i * maximum / stepCount;
        const y = atY(value);
        svg.append(create('line', { x1: left, x2: width - right, y1: y, y2: y, stroke: '#e9efeb', 'stroke-width': 1 }));
        textNode(svg, nicePercent(value), left - 10, y + 4, { anchor: 'end', size: 11, color: '#687b72' });
      }
      // Eixo direito: contagem INTEIRA de quartos, com posição compatível
      // com o percentual do eixo esquerdo. Não repetir nem arredondar 0/1.
      const visibleMaxRooms = Math.min(capacity, Math.floor(capacity * maximum / 100 + .00001));
      const countTickStep = visibleMaxRooms <= 6 ? 1 : Math.ceil(visibleMaxRooms / 5);
      const rightTicks = new Set([0]);
      for (let n = countTickStep; n <= visibleMaxRooms; n += countTickStep) rightTicks.add(n);
      rightTicks.add(visibleMaxRooms);
      Array.from(rightTicks).sort((a,b)=>a-b).forEach(count => {
        const y = atY(count / capacity * 100);
        textNode(svg, br.format(count), width - right + 12, y + 4, { anchor: 'start', size: 10.5, color: '#72877a' });
      });
      textNode(svg, 'quartos', width - right + 12, 10, { anchor: 'start', size: 9.5, color: '#74867b' });

      // A cor menta representa QUARTOS ocupados por data, não porcentagem.
      // O percentual é derivado do total ativo da pousada, e não do pico do mês.
      rooms.forEach((count, index) => {
        const barPercent = count / capacity * 100;
        const h = Math.max(0, baseline - atY(barPercent));
        const rect = create('rect', {
          x: atX(index) - barW / 2, y: baseline - h, width: barW,
          height: h, rx: 2, fill: '#9bdcb7', opacity: '.93'
        });
        rect.append(create('title', {}, `${labels[index]}: ${count} de ${capacity} quartos ocupados (${nicePercent(barPercent)})`));
        svg.append(rect);
      });

      // Curvas suaves, sem introduzir valores fictícios nos pontos originais.
      const points = occupancy.map((value, index) => [atX(index), atY(value)]);
      let path = '';
      if (points.length) {
        path = `M ${points[0][0]} ${points[0][1]}`;
        for (let i = 0; i < points.length - 1; i++) {
          const p0 = points[Math.max(0, i - 1)];
          const p1 = points[i];
          const p2 = points[i + 1];
          const p3 = points[Math.min(points.length - 1, i + 2)];
          const factor = .14;
          const c1x = p1[0] + (p2[0] - p0[0]) * factor;
          const c1y = Math.max(top, Math.min(baseline, p1[1] + (p2[1] - p0[1]) * factor));
          const c2x = p2[0] - (p3[0] - p1[0]) * factor;
          const c2y = Math.max(top, Math.min(baseline, p2[1] - (p3[1] - p1[1]) * factor));
          path += ` C ${c1x} ${c1y} ${c2x} ${c2y} ${p2[0]} ${p2[1]}`;
        }
        svg.append(create('path', { d: `${path} L ${points[points.length-1][0]} ${baseline} L ${points[0][0]} ${baseline} Z`, fill: 'url(#refOccupancyArea)' }));
        svg.append(create('path', { d: path, fill: 'none', stroke: '#087b51', 'stroke-width': 3.2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }));
      }
      const everyPoint = Math.max(1, Math.ceil(labels.length / 16));
      occupancy.forEach((value, index) => {
        if (index % everyPoint && index !== occupancy.length - 1) return;
        const dot = create('circle', { cx: atX(index), cy: atY(value), r: 3.5, fill: '#fff', stroke: '#087b51', 'stroke-width': 2.4 });
        dot.append(create('title', {}, `${labels[index]}: ${nicePercent(value)} de ocupação · ${rooms[index]} de ${capacity} quartos`));
        svg.append(dot);
      });
      const everyLabel = Math.max(1, Math.ceil(labels.length / 7));
      labels.forEach((label, index) => {
        if (index % everyLabel && index !== labels.length - 1) return;
        textNode(svg, label, atX(index), height - 12, { size: 10.5, anchor: 'middle', color: '#677a70' });
      });
      container.replaceChildren(svg);
      const peak = Math.max(0, ...rooms);
      if (scaleBadge) scaleBadge.textContent = focused ? `Escala ampliada · 0–${nicePercent(maximum)}` : 'Escala completa · 0–100%';
      if (caption) caption.textContent = `${capacity} quartos ativos · pico de ${peak} ${peak === 1 ? 'quarto ocupado' : 'quartos ocupados'} (${nicePercent(peak * 100 / capacity)}) no período. ${focused ? 'Escala ampliada para visualizar baixas taxas de ocupação.' : 'Escala completa de ocupação.'}`;
      if (zoomButton) {
        zoomButton.hidden = !isZoomable;
        zoomButton.textContent = focused ? 'Ver escala 0–100%' : 'Ampliar leitura';
        zoomButton.setAttribute('aria-pressed', String(focused));
      }
    }
    if (zoomButton) zoomButton.addEventListener('click', () => { fullScale = !fullScale; render(); });
    render();
  }

  function drawMonthly(container, data) {
    const labels = Array.isArray(data.monthlyLabels) ? data.monthlyLabels : [];
    const values = Array.isArray(data.monthlyValues) ? data.monthlyValues.map(safe) : [];
    if (!labels.length || labels.length !== values.length) return noData(container);
    const width = 700, height = 245, left = 69, right = 14, top = 14, bottom = 31;
    const plotWidth = width - left - right, plotHeight = height - top - bottom;
    const maxValue = Math.max(1, ...values) * 1.08;
    const svg = create('svg', { viewBox: `0 0 ${width} ${height}`, preserveAspectRatio: 'none', role: 'presentation', focusable: 'false' });
    const money = document.querySelector('.ref-kpi-revenue .ref-currency') !== null;
    const fmt = value => money ? 'R$ ' + (value >= 1000 ? br.format(value/1000) + ' mil' : br.format(value)) : br.format(value);
    for (let step = 0; step <= 4; step++) {
      const value = maxValue * step / 4;
      const y = top + plotHeight * (1 - step / 4);
      svg.append(create('line', {x1:left,x2:width-right,y1:y,y2:y,stroke:'#e8ece9','stroke-width':1}));
      textNode(svg,fmt(value),left-11,y+4,{anchor:'end',size:10,color:'#536b5d'});
    }
    const gap = plotWidth / labels.length;
    const barW = Math.min(62, gap * .64);
    values.forEach((value,index) => {
      const heightBar = plotHeight * value / maxValue;
      const x = left + gap * index + (gap-barW)/2;
      const y = top + plotHeight - heightBar;
      const bar = create('rect',{x,y,width:barW,height:Math.max(0,heightBar),rx:2,fill:index===values.length-1?'#066d47':'#70c69a'});
      bar.append(create('title',{}, `${labels[index]}: ${fmt(value)}`));
      svg.append(bar);
      textNode(svg,labels[index],left+gap*index+gap/2,height-11,{anchor:'middle',size:11,color:'#627669'});
    });
    container.replaceChildren(svg);
  }
  function noData(container) {
    const fallback = document.createElement('p');
    fallback.className = 'ref-chart-placeholder';
    fallback.textContent = 'Sem dados disponíveis para este gráfico.';
    container.replaceChildren(fallback);
  }
  if (chartSource) {
    try {
      const chartData = JSON.parse(chartSource.textContent);
      document.querySelectorAll('[data-ref-chart="occupancy"]').forEach(element => drawOccupancy(element, chartData));
      document.querySelectorAll('[data-ref-chart="monthly"]').forEach(element => drawMonthly(element, chartData));
    } catch (error) {
      document.querySelectorAll('[data-ref-chart]').forEach(noData);
      console.error('Não foi possível renderizar os gráficos administrativos.', error);
    }
  }

  // Dropdowns acessiveis com <details>: clique fora e Escape fecham o painel.
  const menus = Array.from(document.querySelectorAll('.ref-top-details, .ref-period-menu'));
  document.addEventListener('click', event => {
    menus.forEach(menu => {
      if (menu.open && !menu.contains(event.target)) menu.open = false;
    });
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') menus.forEach(menu => { menu.open = false; });
  });
})();
