/** Costa Serena V5.2 — exploracao dos graficos existentes, sem alterar valores. */
(() => {
  'use strict';
  const source = document.getElementById('bi-dashboard-data');
  if (!source || !document.querySelector('.ref-dashboard')) return;
  let data;
  try { data = JSON.parse(source.textContent); } catch { return; }
  const moneyMode = Boolean(document.querySelector('.ref-kpi-revenue .ref-currency'));
  const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
  const count = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 });
  const pct = v => count.format(Number(v) || 0) + '%';
  const svgNS = 'http://www.w3.org/2000/svg';

  function attach(container) {
    const svg = container.querySelector('svg');
    if (!svg || svg.dataset.v52Interactive) return;
    svg.dataset.v52Interactive = 'true';
    const type = container.getAttribute('data-ref-chart');
    const labels = type === 'occupancy' ? data.labels : data.monthlyLabels;
    const values = type === 'occupancy' ? data.occupancy : data.monthlyValues;
    if (!Array.isArray(labels) || !Array.isArray(values) || !labels.length || labels.length !== values.length) return;
    const leftPad = type === 'occupancy' ? 54 : 69;
    const rightPad = type === 'occupancy' ? 52 : 14;
    const originalWidth = type === 'occupancy' ? 740 : 700;
    const inner = originalWidth - leftPad - rightPad;
    const capacity = Math.max(0, Number(data.totalRooms) || 0);
    const occupied = Array.isArray(data.occupiedRooms) ? data.occupiedRooms : [];
    let index = Math.max(0, labels.length - 1);

    const tooltip = document.createElement('div');
    tooltip.className = 'v52-chart-tooltip';
    tooltip.dataset.visible = 'false';
    tooltip.setAttribute('role', 'status');
    tooltip.setAttribute('aria-live', 'polite');
    container.append(tooltip);
    svg.setAttribute('tabindex', '0');
    svg.setAttribute('aria-label', 'Gráfico interativo. Use as setas esquerda e direita para explorar os dados do período.');

    const focusLine = document.createElementNS(svgNS, 'line');
    focusLine.setAttribute('stroke', '#c69a55');
    focusLine.setAttribute('stroke-width', '1.5');
    focusLine.setAttribute('stroke-dasharray', '4 4');
    focusLine.setAttribute('opacity', '0');
    focusLine.setAttribute('pointer-events', 'none');
    focusLine.setAttribute('y1', '14');
    focusLine.setAttribute('y2', type === 'occupancy' ? '230' : '214');
    svg.append(focusLine);

    function show(nextIndex) {
      index = Math.max(0, Math.min(labels.length - 1, nextIndex));
      const text = type === 'occupancy'
        ? `${labels[index]} · Ocupação ${pct(values[index])}\n${count.format(occupied[index] || 0)} de ${count.format(capacity)} quartos ocupados`
        : `${labels[index]}\n${moneyMode ? money.format(Number(values[index]) || 0) : count.format(Number(values[index]) || 0) + ' reservas'}`;
      tooltip.textContent = text;
      const xSvg = leftPad + (index + .5) * inner / labels.length;
      focusLine.setAttribute('x1', String(xSvg));
      focusLine.setAttribute('x2', String(xSvg));
      focusLine.setAttribute('opacity', '1');
      const xPx = (xSvg / originalWidth) * container.clientWidth;
      const ideal = Math.max(90, Math.min(container.clientWidth - 90, xPx));
      tooltip.style.left = `${ideal}px`;
      tooltip.style.top = '77px';
      tooltip.dataset.visible = 'true';
    }
    function hide() { tooltip.dataset.visible = 'false'; focusLine.setAttribute('opacity', '0'); }
    svg.addEventListener('pointermove', event => {
      const bounds = svg.getBoundingClientRect();
      if (!bounds.width) return;
      const xSvg = (event.clientX - bounds.left) / bounds.width * originalWidth;
      if (xSvg < leftPad || xSvg > originalWidth - rightPad) { hide(); return; }
      show(Math.floor((xSvg - leftPad) / inner * labels.length));
    });
    svg.addEventListener('pointerleave', hide);
    svg.addEventListener('focus', () => show(index));
    svg.addEventListener('blur', hide);
    svg.addEventListener('keydown', event => {
      if (event.key === 'Escape') { hide(); return; }
      if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault();
        show(index + (event.key === 'ArrowRight' ? 1 : -1));
      }
    });
  }
  document.querySelectorAll('[data-ref-chart]').forEach(container => {
    attach(container);
    // O script V4 recria o SVG ao alternar entre escala ampliada e 0-100%.
    // Reaplicar somente os controles visuais apos a troca, sem alterar os dados.
    const observer = new MutationObserver(records => {
      if (records.some(record => [...record.addedNodes].some(node => node.nodeType === 1 && node.tagName.toLowerCase() === 'svg'))) attach(container);
    });
    observer.observe(container, { childList: true });
  });
})();
